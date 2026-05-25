<?php
/**
 * Plugin Name: GitHub Core Update Checker
 * Description: Checks GitHub for new releases instead of wordpress.org, with auto-update capability
 * Version: 1.1
 */

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/misc.php';

class GitHub_Core_Updater {
	private const OWNER     = 'cbuntingde';
	private const REPO      = 'WordPress-7.0-Clean';
	private const CACHE_KEY = 'wp_github_core_version';

	/**
	 * Track if an auto-update is in progress
	 */
	private static bool $autoUpdating = false;

	public function __construct() {
		// Add custom settings page
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Manual update trigger
		add_action( 'admin_init', array( $this, 'handle_update_action' ) );

		// Auto-update hook (runs on wp_auto_update cron)
		add_action( 'wp_maybe_auto_update', array( $this, 'maybe_auto_update' ), 5 );

		// Filter to provide our own version info
		add_filter( 'pre_update_feed_global', array( $this, 'inject_core_update_info' ), 5 );
	}

	/**
	 * Override WordPress core update check with GitHub info
	 */
	public function inject_core_update_info( $value ) {
		$status = $this->get_update_status();

		if ( $status['available'] ) {
			// Hijack the update response to show our version
			$value = array(
				'offered' => array(
					'version' => $status['remote'],
					'package' => $status['update']['download'],
					'php_required' => '8.5',
					'mysql_required' => '8.0',
					'new_bundled_php' => '8.5',
					'new_bundled_mysql' => '8.0',
				),
			);
		}

		return $value;
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
					'Accept'        => 'application/vnd.github+json',
					'User-Agent'   => 'WordPress-GitHub-Updater',
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
			'version'  => ltrim( $data['tag_name'], 'v' ),
			'download' => $download_url,
			'body'    => $data['body'] ?? '',
			'url'     => $data['html_url'] ?? '',
			'date'    => $data['published_at'] ?? '',
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
				'message'  => 'Could not check for updates',
			);
		}

		$has_update = version_compare( $local, $remote['version'], '<' );

		return array(
			'available' => $has_update,
			'local'    => $local,
			'remote'   => $remote['version'],
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

		// Prevent recursive updates
		if ( self::$autoUpdating ) {
			return false;
		}
		self::$autoUpdating = true;

		// Create a temp file for the download
		$temp_dir = get_temp_dir();
		$temp_zip = $temp_dir . 'wp-github-update.zip';

		// Download the zip
		$upgrader = new WP_Upgrader();
		$result  = $upgrader->run( array(
			'source'            => $update['download'],
			'destination'      => $temp_dir,
			'download_file'    => true,
			'download_filename' => 'wp-github-update.zip',
		) );

		if ( is_wp_error( $result ) ) {
			self::$autoUpdating = false;
			return false;
		}

		// Extract over current installation
		$unzip_result = unzip_file( $temp_zip, ABSPATH );

		@unlink( $temp_zip );
		self::$autoUpdating = false;

		if ( is_wp_error( $unzip_result ) ) {
			return false;
		}

		// Clear version cache
		delete_transient( self::CACHE_KEY );
		delete_transient( 'update_core' );

		return true;
	}

	/**
	 * Auto-update hook (called by WP cron)
	 */
	public function maybe_auto_update(): void {
		$status = $this->get_update_status();

		if ( ! $status['available'] ) {
			return;
		}

		// Check if auto-updates are enabled for this install
		$auto_enabled = get_option( 'github_core_auto_update', false );

		if ( ! $auto_enabled ) {
			return;
		}

		$this->perform_update();
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

		echo '<tr><th>Auto-Update</th><td>';
		echo '<label><input type="checkbox" name="github_core_auto_update" value="1" ' . checked( get_option( 'github_core_auto_update', false ), true, false ) . '> Enable automatic updates</label>';
		echo '<p class="description">When enabled, WordPress will automatically download and install new releases from GitHub.</p>';
		echo '</td></tr>';

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