<?php
/**
 * Plugin Name: Layout Recompiler for Brizy
 * Description: Recompiles all Brizy-enabled pages to fix broken layouts. Runs page-by-page using AJAX to prevent memory exhaustion and timeouts.
 * Version:     1.5.2
 * Author:      just another tech
 * Author URI:  https://justanothertech.online
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires PHP: 7.2
 * Requires Plugins: brizy
 * Text Domain: layout-recompiler-for-brizy
 */

defined( 'ABSPATH' ) || exit;

class Brizy_Fix {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		
		// AJAX Actions.
		add_action( 'wp_ajax_brizy_fix_get_posts', array( $this, 'ajax_get_posts' ) );
		add_action( 'wp_ajax_brizy_fix_compile_post', array( $this, 'ajax_compile_post' ) );
		add_filter( 'brizy_asset_url', array( $this, 'normalize_brizy_asset_url' ), 10, 2 );
	}

	/**
	 * Add Tools submenu page.
	 */
	public function add_admin_menu() {
		add_management_page(
			esc_html__( 'Layout Recompiler for Brizy', 'layout-recompiler-for-brizy' ),
			esc_html__( 'Layout Recompiler', 'layout-recompiler-for-brizy' ),
			'manage_options',
			'brizy-fix',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Enqueue stylesheet and script helpers.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'tools_page_brizy-fix' !== $hook ) {
			return;
		}
		
		// Add some styling for the progress bar.
		wp_add_inline_style( 'common', '
			.brizy-fix-progress-container {
				margin: 20px 0;
				max-width: 600px;
				display: none;
			}
			.brizy-fix-progress-bar-wrapper {
				background: #ddd;
				border-radius: 4px;
				overflow: hidden;
				height: 20px;
				margin-bottom: 10px;
			}
			.brizy-fix-progress-bar {
				background: #2271b1;
				height: 100%;
				width: 0%;
				transition: width 0.3s ease;
			}
			.brizy-fix-log {
				background: #fff;
				border: 1px solid #ccc;
				padding: 10px;
				max-height: 200px;
				overflow-y: auto;
				font-family: monospace;
				font-size: 12px;
			}
			.brizy-fix-log-item {
				margin-bottom: 4px;
			}
			.brizy-fix-log-item.success { color: green; }
			.brizy-fix-log-item.error { color: red; }
			#brizy-fix-review-invitation {
				display: none;
				padding-top: 50px;
			}
		' );

		// Enqueue the external JS file and localize data.
		wp_enqueue_script( 'brizy-fix-admin', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), '1.5.2', true );
		wp_localize_script( 'brizy-fix-admin', 'brizyFixData', array(
			'ajaxurl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'brizy_fix_nonce' ),
			'messages' => array(
				'processing' => esc_html__( 'Processing...', 'layout-recompiler-for-brizy' ),
				'fetching'   => esc_html__( 'Fetching pages list...', 'layout-recompiler-for-brizy' ),
				'noPages'    => esc_html__( 'No Brizy-enabled pages found or security check failed.', 'layout-recompiler-for-brizy' ),
				'failedList' => esc_html__( 'Failed to retrieve pages list.', 'layout-recompiler-for-brizy' ),
				'complete'   => esc_html__( 'Recompilation Complete!', 'layout-recompiler-for-brizy' ),
				'finished'   => esc_html__( 'Finished recompiling all posts.', 'layout-recompiler-for-brizy' ),
				'compiling'  => esc_html__( 'Compiling ', 'layout-recompiler-for-brizy' ),
				'compiled'   => esc_html__( ' compiled successfully.', 'layout-recompiler-for-brizy' ),
				'failed'     => esc_html__( ' failed: ', 'layout-recompiler-for-brizy' ),
				'reqFailed'  => esc_html__( ' request failed.', 'layout-recompiler-for-brizy' ),
				'successful' => esc_html__( 'successful', 'layout-recompiler-for-brizy' ),
				'failedSkipped' => esc_html__( 'failed/skipped', 'layout-recompiler-for-brizy' ),
				'start'      => esc_html__( 'Start Recompilation', 'layout-recompiler-for-brizy' )
			)
		) );
	}

	/**
	 * Render the settings page.
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Validation: Check if Brizy Builder is active.
		if ( ! class_exists( 'Brizy_Editor_Post' ) ) {
			?>
			<div class="wrap">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Brizy Builder is not installed or active. Please install and activate Brizy Builder to use this utility.', 'layout-recompiler-for-brizy' ); ?></p>
				</div>
			</div>
			<?php
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p><?php esc_html_e( 'Force recompilation of all Brizy-enabled pages and posts to fix broken layouts. This utility runs incrementally to prevent timeouts and PHP memory limit issues.', 'layout-recompiler-for-brizy' ); ?></p>
			
			<div class="card" style="max-width: 600px; margin-top: 20px;">
				<h2><?php esc_html_e( 'Run Recompilation', 'layout-recompiler-for-brizy' ); ?></h2>
				<p><?php esc_html_e( 'Click the button below to start the page-by-page recompilation process.', 'layout-recompiler-for-brizy' ); ?></p>
				
				<button id="brizy-fix-start-btn" class="button button-primary">
					<?php esc_html_e( 'Start Recompilation', 'layout-recompiler-for-brizy' ); ?>
				</button>
			</div>

			<div class="brizy-fix-progress-container" id="brizy-fix-progress-section">
				<h3 id="brizy-fix-progress-title"><?php esc_html_e( 'Initializing...', 'layout-recompiler-for-brizy' ); ?></h3>
				<div class="brizy-fix-progress-bar-wrapper">
					<div class="brizy-fix-progress-bar" id="brizy-fix-progress-bar"></div>
				</div>
				<p id="brizy-fix-progress-text">0 / 0</p>

				<h4><?php esc_html_e( 'Process Log', 'layout-recompiler-for-brizy' ); ?></h4>
				<div class="brizy-fix-log" id="brizy-fix-log"></div>
			</div>

			<div id="brizy-fix-review-invitation">
				<p class="description">
					<?php esc_html_e( 'If', 'layout-recompiler-for-brizy' ); ?>
					<strong><?php esc_html_e( 'Layout Recompiler for Brizy', 'layout-recompiler-for-brizy' ); ?></strong>
					<?php esc_html_e( 'helped you, please consider sharing an honest review on WordPress.org.', 'layout-recompiler-for-brizy' ); ?>
					<a href="<?php echo esc_url( 'https://wordpress.org/support/plugin/layout-recompiler-for-brizy/reviews/#new-post' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Leave a review', 'layout-recompiler-for-brizy' ); ?></a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX endpoint to get list of posts.
	 */
	public function ajax_get_posts() {
		check_ajax_referer( 'brizy_fix_nonce', 'security' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'Permission denied', 'layout-recompiler-for-brizy' ) );
		}

		if ( ! class_exists( 'Brizy_Editor_Post' ) ) {
			wp_send_json_error( esc_html__( 'Brizy is not active', 'layout-recompiler-for-brizy' ) );
		}

		$post_ids = Brizy_Editor_Post::get_all_brizy_post_ids();
		if ( ! is_array( $post_ids ) ) {
			$post_ids = array();
		}

		$posts_data = array();
		foreach ( $post_ids as $id ) {
			$title = get_the_title( $id );
			
			// Check if title is a 32-character hexadecimal string or empty.
			if ( preg_match( '/^[a-f0-9]{32}$/i', $title ) || empty( $title ) ) {
				$post_type = get_post_type( $id );
				if ( 'brizy-global-block' === $post_type ) {
					$title = esc_html__( 'Global Block', 'layout-recompiler-for-brizy' );
				} elseif ( 'brizy-saved-block' === $post_type ) {
					$title = esc_html__( 'Saved section', 'layout-recompiler-for-brizy' );
				} else {
					$title = esc_html__( 'Other internal custom post type', 'layout-recompiler-for-brizy' );
				}
			} else {
				$title = wp_html_excerpt( wp_strip_all_tags( $title ), 40, '...' );
			}

			$posts_data[] = array(
				'id'    => absint( $id ),
				'title' => $title,
			);
		}

		wp_send_json_success( $posts_data );
	}

	/**
	 * AJAX endpoint to compile a single post.
	 */
	public function ajax_compile_post() {
		check_ajax_referer( 'brizy_fix_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'Permission denied', 'layout-recompiler-for-brizy' ) );
		}

		// Dynamically raise memory limit if allowed.
		wp_raise_memory_limit( 'admin' );

		// Read the submitted post ID from AJAX, remove request slashes, and force it to a positive integer.
		$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
		if ( ! $post_id ) {
			wp_send_json_error( array( 'error' => esc_html__( 'Invalid post ID', 'layout-recompiler-for-brizy' ) ) );
		}

		if ( ! class_exists( 'Brizy_Editor_Post' ) || ! class_exists( 'Brizy_Editor_Compiler' ) ) {
			wp_send_json_error( array( 'error' => esc_html__( 'Brizy classes not found', 'layout-recompiler-for-brizy' ) ) );
		}

		$post = Brizy_Editor_Post::get( $post_id );
		if ( ! $post ) {
			wp_send_json_error( array( 'error' => esc_html__( 'Post object not found', 'layout-recompiler-for-brizy' ) ) );
		}

		try {
			$compiler = new Brizy_Editor_Compiler(
				Brizy_Editor_Project::get(),
				new Brizy_Admin_Blocks_Manager( Brizy_Admin_Blocks_Main::CP_GLOBAL ),
				new Brizy_Editor_UrlBuilder( Brizy_Editor_Project::get(), $post ),
				Brizy_Config::getCompilerUrls(),
				Brizy_Config::getCompilerDownloadUrl()
			);
			$editorConfig = Brizy_Editor_Editor_Editor::get( Brizy_Editor_Project::get(), $post )
				->config( Brizy_Editor_Editor_Editor::COMPILE_CONTEXT );
			
			$res = $compiler->compilePost( $post, $editorConfig );
			if ( $res ) {
				wp_send_json_success( array(
					'success'          => true,
					'compiler_version' => sanitize_text_field( get_post_meta( $post_id, 'brizy-post-compiler-version', true ) )
				) );
			} else {
				wp_send_json_error( array( 'error' => esc_html__( 'Compiler did not return true', 'layout-recompiler-for-brizy' ) ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'error' => sanitize_text_field( $e->getMessage() ) ) );
		}
	}

	/**
	 * Normalize Brizy beta asset URLs on subdirectory installs.
	 *
	 * Brizy beta can return paths such as /demos/beta/wp-content/... as compiled asset
	 * paths. Brizy's URL builder then prepends home_url(), which creates
	 * /demos/beta/demos/beta/wp-content/... and prevents front-end CSS/JS from loading.
	 *
	 * @param string $url   Asset URL.
	 * @param object $asset Brizy asset object.
	 * @return string
	 */
	public function normalize_brizy_asset_url( $url, $asset ) {
		$home      = untrailingslashit( home_url() );
		$home_path = wp_parse_url( $home, PHP_URL_PATH );

		if ( ! $home_path ) {
			return $url;
		}

		$duplicate = $home . $home_path . '/wp-content/';
		$correct   = $home . '/wp-content/';

		if ( 0 === strpos( $url, $duplicate ) ) {
			return $correct . substr( $url, strlen( $duplicate ) );
		}

		return $url;
	}
}

new Brizy_Fix();
