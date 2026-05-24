<?php
/**
 * Plugin Name: GitHub Core Update Checker
 * Description: Checks GitHub for new releases instead of wordpress.org, with auto-update capability
 * Version: 1.0
 */

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/misc.php';

class GitHub_Core_Updater {
	private const OWNER    = 'cbuntingde';
	private const REPO     = 'WordPress-7.0-Clean';
	private const CACHE_KEY = 'wp_github_core_version';

	public function __construct() {
		// Hook into WordPress update system
		add_filter( 'pre_update_feed_global', '__return_false' );
		add_filter( 'auto_update_core', '__return_false' );

		// Hide default WordPress update UI elements
		add_action( 'admin_init', array( $this, 'hide_core_updates' ) );

		// Add custom settings page
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Manual update trigger
		add_action( 'admin_init', array( $this, 'handle_update_action' ) );
	}

	/**
	 * Hide WordPress core update UI elements
	 */
	public function hide_core_updates(): void {
		// Remove update nag
		remove_action( 'network_admin_notices', 'update_nag' );
		remove_action( 'admin_notices', 'update_nag' );
		remove_action( 'admin_notices', 'maintenance_nag' );

		// Hide the Updates admin menu item (optional - commented out if you want to retain access)
		// remove_submenu_page( 'index.php', 'update-core.php' );
	}

	/**
	 * Check for updates
	 */
	private function check_for_updates(): ?array {
		$cached = get_transient( self::CACHE_KEY );
		if ( false !== $cached ) {
			return $cached;
		}

		$response = wp_remote_get(
			"https://api.github.com/repos/" . self::OWNER . "/" . self::REPO . "/releases/latest",
			array(
				'headers' => array(
					'Accept'       => 'application/vnd.github+json',
					'User-Agent'  => 'WordPress-GitHub-Updater',
					'X-GitHub-Api-Version' => '2022-11-28',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! $data || empty( $data['tag_name'] ) ) {
			return null;
		}

		// Find the .zip asset
		$download_url = '';
		if ( ! empty( $data['assets'] ) ) {
			foreach ( $data['assets'] as $asset ) {
				if ( preg_match( '/\.zip$/', $asset['name'] ) ) {
					$download_url = $asset['browser_download_url'];
					break;
				}
			}
		}

		// Fallback to zipball if no asset
		if ( ! $download_url ) {
			$download_url = $data['zipball_url'] ?? '';
		}

		$result = array(
			'version' => ltrim( $data['tag_name'], 'v' ),
			'download' => $download_url,
			'body'   => $data['body'] ?? '',
			'url'    => $data['html_url'] ?? '',
			'date'   => $data['published_at'] ?? '',
		);

		set_transient( self::CACHE_KEY, $result, HOUR_IN_SECONDS );

		return $result;
	}

	/**
	 * Get local version
	 */
	public function get_local_version(): string {
		require ABSPATH . 'wp-includes/version.php';
		return $wp_version;
	}

	/**
	 * Compare versions and return update status
	 */
	public function get_update_status(): array {
		$remote = $this->check_for_updates();
		$local  = $this->get_local_version();

		if ( ! $remote ) {
			return array(
				'available' => false,
				'message' => 'Could not check for updates',
			);
		}

		$has_update = version_compare( $local, $remote['version'], '<' );

		return array(
			'available' => $has_update,
			'local'   => $local,
			'remote'  => $remote['version'],
			'update'  => $remote,
		);
	}

	/**
	 * Handle manual update action from settings page
	 */
	public function handle_update_action(): void {
		if ( ! isset( $_GET['page'] ) || 'github-core-updater' !== $_GET['page'] ) {
			return;
		}

		if ( ! current_user_can( 'update_core' ) ) {
			return;
		}

		if ( ! isset( $_GET['action'] ) || 'update_now' !== $_GET['action'] ) {
			return;
		}

		check_admin_referer( 'github-core-update' );

		$this->perform_update();

		wp_safe_redirect( remove_query_arg( 'action', add_query_arg( 'updated', '1', $_SERVER['REQUEST_URI'] ) ) );
		exit;
	}

	/**
	 * Apply the update
	 */
	public function perform_update(): bool {
		$status = $this->get_update_status();

		if ( ! $status['available'] ) {
			return false;
		}

		$update = $status['update'];

		// Prevent auto-updates on our site during the process
		update_option( 'auto_update_core', 'disabled' );
		wp_clear_scheduled_event( 'wp_version_check' );
		wp_clear_scheduled_event( 'wp_update_plugins' );
		wp_clear_scheduled_event( 'wp_update_themes' );

		// Create a temp file for the download
		$temp_dir  = get_temp_dir();
		$temp_zip = $temp_dir . 'wp-github-update.zip';

		// Download the zip
		$nace = new WP_Upgrader();
		$result = $nace->run( array(
			'source'           => $update['download'],
			'destination'      => $temp_dir,
			'download_file'    => true,
			'download_filename' => 'wp-github-update.zip',
		) );

		if ( is_wp_error( $result ) ) {
			wp_die(
				sprintf(
					'Download failed: %s',
					$result->get_error_message()
				)
			);
		}

		// Extract over current installation (careful!)
		// This is a dangerous operation - we're replacing WordPress core files
		$extract_dir = trailingslashit( ABSPATH );

		// Unzip directly over the current installation
		$unzip_result = unzip_file( $temp_zip, dirname( ABSPATH ) );

		@unlink( $temp_zip );

		if ( is_wp_error( $unzip_result ) ) {
			wp_die(
				sprintf(
					'Extraction failed: %s',
					$unzip_result->get_error_message()
				)
			);
		}

		// Update local version cache
		delete_transient( self::CACHE_KEY );

		return true;
	}

	/**
	 * Add settings page
	 */
	public function add_settings_page(): void {
		add_options_page(
			'GitHub Core Updater',
			'Core Updater',
			'update_core',
			'github-core-updater',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings(): void {
		register_setting(
			'github-core-updater',
			'github_core_auto_update',
			array(
				'type'    => 'boolean',
				'default' => false,
			)
		);
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page(): void {
		$status = $this->get_update_status();

		echo '<div class="wrap">';
		echo '<h1>GitHub Core Updater</h1>';

		if ( ! empty( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>Core updated successfully!</p></div>';
		}

		echo '<table class="form-table">';
		echo '<tr><th>Current Version</th><td>' . esc_html( $status['local'] ?? $this->get_local_version() ) . '</td></tr>';

		if ( $status['available'] ) {
			echo '<tr><th>Available Version</th><td><strong>' . esc_html( $status['remote'] ) . '</strong></td></tr>';
			echo '<tr><th>Release Notes</th><td>' . nl2br( esc_html( substr( $status['update']['body'], 0, 2000 ) ) ) . '</td></tr>';

			echo '<tr><th>Action</th><td>';
			echo '<a href="' . wp_nonce_url( admin_url( 'options-general.php?page=github-core-updater&action=update_now' ), 'github-core-update' ) . '" class="button button-primary">Update Now</a>';
			echo '</td></tr>';
		} else {
			echo '<tr><th>Status</th><td><span class="notice notice-success">You are up to date!</span></td></tr>';
		}

		echo '</table>';
		echo '</div>';
	}
}

new GitHub_Core_Updater();

// Hook into WordPress to show update notification
add_action( 'admin_notices', 'github_core_check_notice' );

function github_core_check_notice(): void {
	if ( ! current_user_can( 'update_core' ) ) {
		return;
	}

	if ( ! class_exists( 'GitHub_Core_Updater' ) ) {
		return;
	}

	$updater = new GitHub_Core_Updater();
	$status  = $updater->get_update_status();

	if ( ! $status['available'] ) {
		return;
	}

	$settings_url = admin_url( 'options-general.php?page=github-core-updater' );

	printf(
		'<div class="notice notice-info is-dismissible">
			<p>
				<strong>New WordPress release available:</strong> %1$s (you have %2$s)<br>
				<a href="%3$s">Go to Settings &rarr; Core Updater</a> to update.
			</p>
		</div>',
		esc_html( $status['remote'] ),
		esc_html( $status['local'] ),
		esc_url( $settings_url )
	);
}