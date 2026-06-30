<?php
/**
 * Plugin Name: Brizy Fix
 * Plugin URI:  https://justanothertech.online
 * Description: Recompiles all Brizy-enabled pages to fix broken layouts. Runs page-by-page using AJAX to prevent memory exhaustion and timeouts.
 * Version:     1.1.1
 * Author:      just another tech
 * Author URI:  https://justanothertech.online
 * License:     GPL-2.0
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: brizy-fix
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
	}

	/**
	 * Add Tools submenu page.
	 */
	public function add_admin_menu() {
		add_management_page(
			esc_html__( 'Brizy Fix', 'brizy-fix' ),
			esc_html__( 'Brizy Fix', 'brizy-fix' ),
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
		' );
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
					<p><?php esc_html_e( 'Brizy Builder is not installed or active. Please install and activate Brizy Builder to use this utility.', 'brizy-fix' ); ?></p>
				</div>
			</div>
			<?php
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p><?php esc_html_e( 'Force recompilation of all Brizy-enabled pages and posts to fix broken layouts. This utility runs incrementally to prevent timeouts and PHP memory limit issues.', 'brizy-fix' ); ?></p>
			
			<div class="card" style="max-width: 600px; margin-top: 20px;">
				<h2><?php esc_html_e( 'Run Recompilation', 'brizy-fix' ); ?></h2>
				<p><?php esc_html_e( 'Click the button below to start the page-by-page recompilation process.', 'brizy-fix' ); ?></p>
				
				<button id="brizy-fix-start-btn" class="button button-primary">
					<?php esc_html_e( 'Start Recompilation', 'brizy-fix' ); ?>
				</button>
			</div>

			<div class="brizy-fix-progress-container" id="brizy-fix-progress-section">
				<h3 id="brizy-fix-progress-title"><?php esc_html_e( 'Initializing...', 'brizy-fix' ); ?></h3>
				<div class="brizy-fix-progress-bar-wrapper">
					<div class="brizy-fix-progress-bar" id="brizy-fix-progress-bar"></div>
				</div>
				<p id="brizy-fix-progress-text">0 / 0</p>

				<h4><?php esc_html_e( 'Process Log', 'brizy-fix' ); ?></h4>
				<div class="brizy-fix-log" id="brizy-fix-log"></div>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			var postIds = [];
			var currentIndex = 0;
			var compiledCount = 0;
			var failedCount = 0;

			$('#brizy-fix-start-btn').on('click', function(e) {
				e.preventDefault();
				
				// Disable button.
				$(this).prop('disabled', true).text('<?php esc_html_e( 'Processing...', 'brizy-fix' ); ?>');
				
				// Show progress section and clear log.
				$('#brizy-fix-progress-section').show();
				$('#brizy-fix-log').empty();
				$('#brizy-fix-progress-title').text('<?php esc_html_e( 'Fetching pages list...', 'brizy-fix' ); ?>');
				
				// Step 1: Get posts.
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'brizy_fix_get_posts',
						security: '<?php echo esc_js( wp_create_nonce( "brizy_fix_nonce" ) ); ?>'
					},
					success: function(response) {
						if (response.success && response.data && response.data.length > 0) {
							postIds = response.data;
							currentIndex = 0;
							compiledCount = 0;
							failedCount = 0;
							updateProgress();
							compileNext();
						} else {
							logMessage('<?php esc_html_e( 'No Brizy-enabled pages found or security check failed.', 'brizy-fix' ); ?>', 'error');
							resetBtn();
						}
					},
					error: function() {
						logMessage('<?php esc_html_e( 'Failed to retrieve pages list.', 'brizy-fix' ); ?>', 'error');
						resetBtn();
					}
				});
			});

			function compileNext() {
				if (currentIndex >= postIds.length) {
					// Done!
					$('#brizy-fix-progress-title').text('<?php esc_html_e( 'Recompilation Complete!', 'brizy-fix' ); ?>');
					logMessage('<?php esc_html_e( 'Finished recompiling all posts.', 'brizy-fix' ); ?>', 'success');
					resetBtn();
					return;
				}

				var postId = postIds[currentIndex];
				$('#brizy-fix-progress-title').text('<?php esc_html_e( 'Compiling page ID ', 'brizy-fix' ); ?>' + postId + '...');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'brizy_fix_compile_post',
						post_id: postId,
						security: '<?php echo esc_js( wp_create_nonce( "brizy_fix_nonce" ) ); ?>'
					},
					success: function(response) {
						if (response.success && response.data && response.data.success) {
							compiledCount++;
							logMessage('<?php esc_html_e( 'Page ID ', 'brizy-fix' ); ?>' + postId + '<?php esc_html_e( ' compiled successfully.', 'brizy-fix' ); ?>', 'success');
						} else {
							failedCount++;
							var errMsg = (response.data && response.data.error) ? response.data.error : '<?php esc_html_e( 'unknown error', 'brizy-fix' ); ?>';
							logMessage('<?php esc_html_e( 'Page ID ', 'brizy-fix' ); ?>' + postId + '<?php esc_html_e( ' failed: ', 'brizy-fix' ); ?>' + errMsg, 'error');
						}
						currentIndex++;
						updateProgress();
						compileNext();
					},
					error: function() {
						failedCount++;
						logMessage('<?php esc_html_e( 'Page ID ', 'brizy-fix' ); ?>' + postId + '<?php esc_html_e( ' request failed.', 'brizy-fix' ); ?>', 'error');
						currentIndex++;
						updateProgress();
						compileNext();
					}
				});
			}

			function updateProgress() {
				var percent = (currentIndex / postIds.length) * 100;
				$('#brizy-fix-progress-bar').css('width', percent + '%');
				$('#brizy-fix-progress-text').text(currentIndex + ' / ' + postIds.length + ' (' + compiledCount + ' <?php esc_html_e( 'successful', 'brizy-fix' ); ?>, ' + failedCount + ' <?php esc_html_e( 'failed/skipped', 'brizy-fix' ); ?>)');
			}

			function logMessage(msg, type) {
				var item = $('<div class="brizy-fix-log-item"></div>').text(msg).addClass(type);
				var log = $('#brizy-fix-log');
				log.append(item);
				log.scrollTop(log[0].scrollHeight);
			}

			function resetBtn() {
				$('#brizy-fix-start-btn').prop('disabled', false).text('<?php esc_html_e( 'Start Recompilation', 'brizy-fix' ); ?>');
			}
		});
		</script>
		<?php
	}

	/**
	 * AJAX endpoint to get list of posts.
	 */
	public function ajax_get_posts() {
		check_ajax_referer( 'brizy_fix_nonce', 'security' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		if ( ! class_exists( 'Brizy_Editor_Post' ) ) {
			wp_send_json_error( 'Brizy is not active' );
		}

		$post_ids = Brizy_Editor_Post::get_all_brizy_post_ids();
		if ( ! is_array( $post_ids ) ) {
			$post_ids = array();
		}

		wp_send_json_success( $post_ids );
	}

	/**
	 * AJAX endpoint to compile a single post.
	 */
	public function ajax_compile_post() {
		check_ajax_referer( 'brizy_fix_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		// Dynamically raise memory limit if allowed.
		wp_raise_memory_limit( 'admin' );

		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_send_json_error( array( 'error' => 'Invalid post ID' ) );
		}

		if ( ! class_exists( 'Brizy_Editor_Post' ) || ! class_exists( 'Brizy_Editor_Compiler' ) ) {
			wp_send_json_error( array( 'error' => 'Brizy classes not found' ) );
		}

		$post = Brizy_Editor_Post::get( $post_id );
		if ( ! $post ) {
			wp_send_json_error( array( 'error' => 'Post object not found' ) );
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
					'compiler_version' => get_post_meta( $post_id, 'brizy-post-compiler-version', true )
				) );
			} else {
				wp_send_json_error( array( 'error' => 'Compiler did not return true' ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}
	}
}

new Brizy_Fix();
