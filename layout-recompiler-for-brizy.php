<?php
/**
 * Plugin Name: Layout Recompiler for Brizy
 * Description: Fixes broken Brizy layouts after a plugin update or site migration.
 * Version:     1.4.0
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
		add_action( 'wp_ajax_brizy_fix_get_media_scan_posts', array( $this, 'ajax_get_media_scan_posts' ) );
		add_action( 'wp_ajax_brizy_fix_scan_media_post', array( $this, 'ajax_scan_media_post' ) );
		add_action( 'wp_ajax_brizy_fix_create_media_placeholders', array( $this, 'ajax_create_media_placeholders' ) );
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
			.brizy-fix-log-item.warning { color: #8a6d00; }
			.brizy-fix-media-results {
				display: none;
				margin-top: 20px;
				max-width: 1000px;
			}
			.brizy-fix-media-table {
				margin-top: 10px;
			}
			.brizy-fix-media-table td,
			.brizy-fix-media-table th {
				vertical-align: top;
			}
			.brizy-fix-path {
				word-break: break-all;
				font-family: monospace;
				font-size: 12px;
			}
			.brizy-fix-step {
				margin-top: 20px;
			}
		' );

		// Enqueue the external JS file and localize data.
		wp_enqueue_script( 'brizy-fix-admin', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), '1.4.0', true );
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
				'start'      => esc_html__( 'Start Recompilation', 'layout-recompiler-for-brizy' ),
				'mediaScanStart' => esc_html__( 'Scanning Brizy pages for missing media...', 'layout-recompiler-for-brizy' ),
				'mediaScanDone' => esc_html__( 'Media scan complete.', 'layout-recompiler-for-brizy' ),
				'mediaScanNone' => esc_html__( 'No missing Brizy media files were found.', 'layout-recompiler-for-brizy' ),
				'mediaScanning' => esc_html__( 'Checking media used by ', 'layout-recompiler-for-brizy' ),
				'mediaFound' => esc_html__( 'Missing media found in ', 'layout-recompiler-for-brizy' ),
				'mediaPlaceholderStart' => esc_html__( 'Creating safe yellow placeholder files for missing media...', 'layout-recompiler-for-brizy' ),
				'mediaPlaceholderDone' => esc_html__( 'Placeholder repair complete. You can replace the yellow placeholder files with the original images later.', 'layout-recompiler-for-brizy' ),
				'mediaPlaceholderButton' => esc_html__( 'Create Yellow Placeholders', 'layout-recompiler-for-brizy' ),
				'mediaScanButton' => esc_html__( 'Scan Missing Brizy Media', 'layout-recompiler-for-brizy' ),
				'mediaNoReport' => esc_html__( 'Please run the media scan first.', 'layout-recompiler-for-brizy' )
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
			
			<div class="card brizy-fix-step" style="max-width: 700px;">
				<h2><?php esc_html_e( 'Step 1: Scan Missing Brizy Media', 'layout-recompiler-for-brizy' ); ?></h2>
				<p><?php esc_html_e( 'Checks Brizy pages one at a time and looks for image files that Brizy expects in uploads but cannot find. This scan does not change your database or overwrite files.', 'layout-recompiler-for-brizy' ); ?></p>
				
				<button id="brizy-fix-media-scan-btn" class="button">
					<?php esc_html_e( 'Scan Missing Brizy Media', 'layout-recompiler-for-brizy' ); ?>
				</button>
			</div>

			<div class="brizy-fix-progress-container" id="brizy-fix-media-progress-section">
				<h3 id="brizy-fix-media-progress-title"><?php esc_html_e( 'Ready to scan media.', 'layout-recompiler-for-brizy' ); ?></h3>
				<div class="brizy-fix-progress-bar-wrapper">
					<div class="brizy-fix-progress-bar" id="brizy-fix-media-progress-bar"></div>
				</div>
				<p id="brizy-fix-media-progress-text">0 / 0</p>

				<h4><?php esc_html_e( 'Media Scan Log', 'layout-recompiler-for-brizy' ); ?></h4>
				<div class="brizy-fix-log" id="brizy-fix-media-log"></div>
			</div>

			<div class="brizy-fix-media-results" id="brizy-fix-media-results">
				<h3><?php esc_html_e( 'Missing Brizy Media Report', 'layout-recompiler-for-brizy' ); ?></h3>
				<p id="brizy-fix-media-summary"></p>
				<table class="widefat striped brizy-fix-media-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Media', 'layout-recompiler-for-brizy' ); ?></th>
							<th><?php esc_html_e( 'Missing local file', 'layout-recompiler-for-brizy' ); ?></th>
							<th><?php esc_html_e( 'Original source link', 'layout-recompiler-for-brizy' ); ?></th>
							<th><?php esc_html_e( 'Affected pages', 'layout-recompiler-for-brizy' ); ?></th>
						</tr>
					</thead>
					<tbody id="brizy-fix-media-results-body"></tbody>
				</table>
			</div>

			<div class="card brizy-fix-step" style="max-width: 700px;">
				<h2><?php esc_html_e( 'Step 2: Repair Missing Media', 'layout-recompiler-for-brizy' ); ?></h2>
				<p><?php esc_html_e( 'Creates yellow placeholder image files only for missing upload paths. It never overwrites existing files and does not change WordPress database content. Replace those placeholder files later with the original images listed in the report.', 'layout-recompiler-for-brizy' ); ?></p>
				
				<button id="brizy-fix-media-placeholder-btn" class="button" disabled>
					<?php esc_html_e( 'Create Yellow Placeholders', 'layout-recompiler-for-brizy' ); ?>
				</button>
			</div>

			<div class="card brizy-fix-step" style="max-width: 700px;">
				<h2><?php esc_html_e( 'Step 3: Run Recompilation', 'layout-recompiler-for-brizy' ); ?></h2>
				<p><?php esc_html_e( 'Click the button below to start the page-by-page recompilation process. This existing recompilation process runs separately from the media scan and repair tools.', 'layout-recompiler-for-brizy' ); ?></p>
				
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
	 * AJAX endpoint to get Brizy posts for media scanning.
	 */
	public function ajax_get_media_scan_posts() {
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
			$posts_data[] = array(
				'id'    => absint( $id ),
				'title' => $this->get_display_title( $id ),
			);
		}

		wp_send_json_success( $posts_data );
	}

	/**
	 * AJAX endpoint to scan one Brizy post for missing media.
	 */
	public function ajax_scan_media_post() {
		check_ajax_referer( 'brizy_fix_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'Permission denied', 'layout-recompiler-for-brizy' ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
		if ( ! $post_id ) {
			wp_send_json_error( array( 'error' => esc_html__( 'Invalid post ID', 'layout-recompiler-for-brizy' ) ) );
		}

		$content = $this->get_post_scan_content( $post_id );
		$uids    = $this->extract_brizy_media_uids( $content );
		$missing = array();

		foreach ( $uids as $uid ) {
			$item = $this->get_missing_media_item( $uid, $post_id );
			if ( $item ) {
				$missing[] = $item;
			}
		}

		wp_send_json_success(
			array(
				'post_id'       => $post_id,
				'post_title'    => $this->get_display_title( $post_id ),
				'checked_count' => count( $uids ),
				'missing'       => $missing,
			)
		);
	}

	/**
	 * AJAX endpoint to create placeholder images for missing media files.
	 */
	public function ajax_create_media_placeholders() {
		check_ajax_referer( 'brizy_fix_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'Permission denied', 'layout-recompiler-for-brizy' ) );
		}

		$uids = isset( $_POST['uids'] ) && is_array( $_POST['uids'] )
			? array_map( 'sanitize_text_field', wp_unslash( $_POST['uids'] ) )
			: array();
		$uids = array_map( array( $this, 'sanitize_media_uid' ), $uids );
		$uids = array_values( array_filter( array_unique( $uids ) ) );

		if ( empty( $uids ) ) {
			wp_send_json_error( array( 'error' => esc_html__( 'No missing media report was provided.', 'layout-recompiler-for-brizy' ) ) );
		}

		$results = array();
		foreach ( array_slice( $uids, 0, 5 ) as $uid ) {
			$media = $this->get_missing_media_item( $uid, 0 );
			if ( ! $media ) {
				$results[] = array(
					'uid'     => $uid,
					'status'  => 'skipped',
					'message' => esc_html__( 'The file now exists or the media item could not be resolved.', 'layout-recompiler-for-brizy' ),
				);
				continue;
			}

			$created = $this->create_yellow_placeholder( $media['server_path'] );
			$results[] = array(
				'uid'        => $uid,
				'title'      => $media['title'],
				'local_path' => $media['local_path'],
				'status'     => $created ? 'created' : 'failed',
				'message'    => $created
					? esc_html__( 'A yellow placeholder was created for this missing file.', 'layout-recompiler-for-brizy' )
					: esc_html__( 'The placeholder could not be created. Please check folder permissions.', 'layout-recompiler-for-brizy' ),
			);
		}

		wp_send_json_success( array( 'results' => $results ) );
	}

	/**
	 * Get a clear title for pages, blocks, and internal Brizy posts.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function get_display_title( $post_id ) {
		$title = get_the_title( $post_id );

		if ( preg_match( '/^[a-f0-9]{32}$/i', $title ) || empty( $title ) ) {
			$post_type = get_post_type( $post_id );
			if ( 'brizy-global-block' === $post_type ) {
				return esc_html__( 'Global Block', 'layout-recompiler-for-brizy' );
			}
			if ( 'brizy-saved-block' === $post_type ) {
				return esc_html__( 'Saved section', 'layout-recompiler-for-brizy' );
			}
			return esc_html__( 'Other internal custom post type', 'layout-recompiler-for-brizy' );
		}

		return wp_html_excerpt( wp_strip_all_tags( $title ), 40, '...' );
	}

	/**
	 * Gather a single post's searchable Brizy content without loading the whole site into memory.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function get_post_scan_content( $post_id ) {
		$post    = get_post( $post_id );
		$content = $post ? (string) $post->post_content : '';
		$meta    = get_post_meta( $post_id );

		foreach ( $meta as $key => $values ) {
			if ( false === strpos( $key, 'brizy' ) ) {
				continue;
			}
			foreach ( (array) $values as $value ) {
				$content .= "\n" . maybe_serialize( $value );
			}
		}

		return $content;
	}

	/**
	 * Extract Brizy media UIDs from a content string.
	 *
	 * @param string $content Content to scan.
	 * @return array
	 */
	private function extract_brizy_media_uids( $content ) {
		$content = rawurldecode( html_entity_decode( (string) $content, ENT_QUOTES, 'UTF-8' ) );
		preg_match_all( '/brizy_media=([^&"\',\s<]+)/', $content, $matches );

		if ( empty( $matches[1] ) ) {
			return array();
		}

		$uids = array();
		foreach ( $matches[1] as $uid ) {
			$uid = html_entity_decode( rawurldecode( $uid ), ENT_QUOTES, 'UTF-8' );
			$uid = $this->sanitize_media_uid( $uid );
			if ( $uid ) {
				$uids[] = $uid;
			}
		}

		return array_values( array_unique( $uids ) );
	}

	/**
	 * Sanitize a Brizy media UID.
	 *
	 * @param string $uid Media UID.
	 * @return string
	 */
	private function sanitize_media_uid( $uid ) {
		return preg_replace( '/[^A-Za-z0-9._-]/', '', (string) $uid );
	}

	/**
	 * Resolve a Brizy media UID and return details only when the expected file is missing.
	 *
	 * @param string $uid     Brizy media UID.
	 * @param int    $post_id Post ID where the UID was found.
	 * @return array|null
	 */
	private function get_missing_media_item( $uid, $post_id ) {
		$uid = $this->sanitize_media_uid( $uid );
		if ( ! $uid ) {
			return null;
		}

		$attachment_id = $this->get_attachment_id_by_brizy_uid( $uid );

		if ( ! $attachment_id ) {
			return array(
				'uid'           => $uid,
				'attachment_id' => 0,
				'title'         => esc_html__( 'Missing attachment record', 'layout-recompiler-for-brizy' ),
				'local_path'    => esc_html__( 'No attachment record was found for this Brizy media ID.', 'layout-recompiler-for-brizy' ),
				'server_path'   => '',
				'source_url'    => '',
				'post_id'       => absint( $post_id ),
				'post_title'    => $post_id ? $this->get_display_title( $post_id ) : '',
			);
		}

		$file = get_attached_file( $attachment_id );
		if ( $file && file_exists( $file ) ) {
			return null;
		}

		$attachment = get_post( $attachment_id );
		$guid       = $attachment ? $attachment->guid : '';

		return array(
			'uid'           => $uid,
			'attachment_id' => $attachment_id,
			'title'         => $attachment ? get_the_title( $attachment_id ) : esc_html__( 'Untitled media', 'layout-recompiler-for-brizy' ),
			'local_path'    => $file ? wp_normalize_path( $file ) : esc_html__( 'WordPress does not have an attached file path for this media item.', 'layout-recompiler-for-brizy' ),
			'server_path'   => $file ? wp_normalize_path( $file ) : '',
			'source_url'    => esc_url_raw( $guid ),
			'post_id'       => absint( $post_id ),
			'post_title'    => $post_id ? $this->get_display_title( $post_id ) : '',
		);
	}

	/**
	 * Resolve a Brizy media UID to an attachment ID using WordPress APIs.
	 *
	 * @param string $uid Brizy media UID.
	 * @return int
	 */
	private function get_attachment_id_by_brizy_uid( $uid ) {
		$uid       = $this->sanitize_media_uid( $uid );
		$cache_key = 'brizy_media_uid_' . md5( $uid );
		$cached    = wp_cache_get( $cache_key, 'layout-recompiler-for-brizy' );

		if ( false !== $cached ) {
			return absint( $cached );
		}

		$attachment_id = 0;
		$page          = 1;
		$batch_size    = 100;

		do {
			$attachments = get_posts(
				array(
					'post_type'              => 'attachment',
					'post_status'            => 'inherit',
					'posts_per_page'         => $batch_size,
					'paged'                  => $page,
					'fields'                 => 'ids',
					'orderby'                => 'date',
					'order'                  => 'DESC',
					'no_found_rows'          => true,
					'update_post_meta_cache' => true,
					'update_post_term_cache' => false,
				)
			);

			foreach ( $attachments as $attachment ) {
				$current_uid = $this->sanitize_media_uid( get_post_meta( $attachment, 'brizy_attachment_uid', true ) );
				if ( $uid === $current_uid ) {
					$attachment_id = absint( $attachment );
					break 2;
				}
			}

			$page++;
		} while ( count( $attachments ) === $batch_size );

		wp_cache_set( $cache_key, $attachment_id, 'layout-recompiler-for-brizy', HOUR_IN_SECONDS );

		return $attachment_id;
	}

	/**
	 * Create a yellow placeholder image at the exact missing upload path.
	 *
	 * @param string $path Target file path.
	 * @return bool
	 */
	private function create_yellow_placeholder( $path ) {
		$path = wp_normalize_path( $path );
		if ( ! $path || file_exists( $path ) ) {
			return false;
		}

		$uploads = wp_get_upload_dir();
		$base    = wp_normalize_path( $uploads['basedir'] );
		if ( 0 !== strpos( $path, $base ) ) {
			return false;
		}

		if ( ! wp_mkdir_p( dirname( $path ) ) ) {
			return false;
		}

		if ( ! function_exists( 'imagecreatetruecolor' ) ) {
			return false;
		}

		$extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		$image     = imagecreatetruecolor( 1600, 1200 );
		if ( ! $image ) {
			return false;
		}

		$yellow = imagecolorallocate( $image, 255, 230, 85 );
		imagefilledrectangle( $image, 0, 0, 1600, 1200, $yellow );

		if ( 'png' === $extension ) {
			$created = imagepng( $image, $path );
		} elseif ( in_array( $extension, array( 'jpg', 'jpeg' ), true ) ) {
			$created = imagejpeg( $image, $path, 90 );
		} else {
			$created = false;
		}

		imagedestroy( $image );

		return (bool) $created;
	}
}

new Brizy_Fix();
