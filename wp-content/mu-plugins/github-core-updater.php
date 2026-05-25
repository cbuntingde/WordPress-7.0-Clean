<?php
/**
 * Plugin Name: GitHub Core Update Checker
 * Description: Checks GitHub for new releases instead of wordpress.org
 * Version: 2.0
 */

defined( 'ABSPATH' ) || exit;

class GitHub_Core_Updater {
	private const OWNER     = 'cbuntingde';
	private const REPO      = 'WordPress-7.0-Clean';
	private const CACHE_TTL  = 3600; // 1 hour

	public function __construct() {
		// Inject our update info into WP's core update transient
		add_filter( 'site_transient_update_core', array( $this, 'inject_core_update' ) );

		// Add settings page
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Manual update trigger
		add_action( 'admin_init', array( $this, 'handle_update_action' ) );
	}

	/**
	 * Inject our GitHub release into WP's core update transient
	 */
	public function inject_core_update( $transient ) {
		$release = $this->check_for_updates();

		if ( ! $release ) {
			return $transient;
		}

		$local = $this->get_local_version();
		$remote_ver = $release['version'];

		$cmp = version_compare( $local, $remote_ver );
		if ( $cmp >= 0 ) {
			return $transient;
		}

		// Build update offer from our GitHub release
		$offer = array(
			'response'      => 'upgrade',
			'download'     => $release['download'],
			'locale'       => 'en_US',
			'packages'    => array(
				'full'        => $release['download'],
				'new_bundled' => '',
				'partial'    => '',
				'rollback'   => '',
				'no_content' => '',
			),
			'current'     => $release['version'],
			'version'    => $release['version'],
			'php_version'   => '8.5',
			'mysql_version' => '8.0',
			'new_bundled'   => '8.5',
			'new_files'     => false,
		);

		// Update the transient
		if ( ! isset( $transient->updates ) ) {
			$transient->updates = array();
		}

		// Filter out any existing WP core offers
		$transient->updates = array_filter(
			$transient->updates,
			function( $offer ) {
				return ! isset( $offer->response ) || 'upgrade' !== $offer->response;
			}
		);

		// Add our offer
		$transient->updates[] = (object) $offer;

		return $transient;
	}

	/**
	 * Check GitHub for latest release
	 */
	private function check_for_updates() {
		$cached = get_transient( 'wp_github_core_release' );
		if ( false !== $cached ) {
			return $cached;
		}

		$response = wp_remote_get(
			"https://api.github.com/repos/" . self::OWNER . "/" . self::REPO . "/releases/latest",
			array(
				'headers' => array(
					'Accept'               => 'application/vnd.github+json',
					'User-Agent'          => 'WordPress-GitHub-Updater/' . self::REPO,
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

		// Find .zip asset
		$download_url = '';
		if ( ! empty( $data['assets'] ) ) {
			foreach ( $data['assets'] as $asset ) {
				if ( substr( $asset['name'], -4 ) === '.zip' ) {
					$download_url = $asset['browser_download_url'];
					break;
				}
			}
		}

		// Fallback to source zipball
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

		set_transient( 'wp_github_core_release', $result, self::CACHE_TTL );

		return $result;
	}

	/**
	 * Get local version
	 */
	private function get_local_version() {
		require ABSPATH . 'wp-includes/version.php';
		return $wp_version;
	}

	/**
	 * Get current release info for display
	 */
	public function get_status() {
		$release = $this->check_for_updates();
		$local   = $this->get_local_version();

		if ( ! $release ) {
			return array(
				'available' => false,
				'local'    => $local,
			);
		}

		$has_update = version_compare( $local, $release['version'], '<' );

		return array(
			'available' => $has_update,
			'local'     => $local,
			'remote'   => $release['version'],
			'release'  => $release,
		);
	}

	/**
	 * Handle manual update action
	 */
	public function handle_update_action() {
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

		// Trigger WP's built-in update which will use our injected package
		include ABSPATH . 'wp-admin/includes/class-core-upgrader.php';
		include ABSPATH . 'wp-admin/includes/file.php';

		$skin = new WP_Upgrader_Skin();
		$upgrader = new Core_Upgrader( $skin );
		$result = $upgrader->upgrade( $this->get_local_version() );

		if ( is_wp_error( $result ) ) {
			set_transient( 'wp_github_update_error', $result->get_error_message(), 60 );
		} else {
			set_transient( 'wp_github_updated', 1, 60 );
		}

		$redirect = add_query_arg( 'updated', $result ? '1' : '0', remove_query_arg( 'action', $_SERVER['REQUEST_URI'] ) );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Add settings page
	 */
	public function add_settings_page() {
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
	public function register_settings() {
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
	public function render_settings_page() {
		$status = $this->get_status();

		echo '<div class="wrap">';
		echo '<h1>GitHub Core Updater</h1>';

		if ( ! empty( $_GET['updated'] ) ) {
			$error = get_transient( 'wp_github_update_error' );
			if ( $_GET['updated'] === '1' && ! $error ) {
				echo '<div class="notice notice-success"><p>Core updated successfully!</p></div>';
			} elseif ( $error ) {
				echo '<div class="notice notice-error"><p>Update failed: ' . esc_html( $error ) . '</p></div>';
			}
		}

		echo '<table class="form-table">';
		echo '<tr><th>Current Version</th><td>' . esc_html( $status['local'] ) . '</td></tr>';

		if ( $status['available'] ) {
			echo '<tr><th>Available Version</th><td><strong>' . esc_html( $status['remote'] ) . '</strong></td></tr>';
			echo '<tr><th>Release Notes</th><td>' . nl2br( esc_html( substr( $status['release']['body'], 0, 2000 ) ) ) . '</td></tr>';

			echo '<tr><th>Action</th><td>';
			echo '<a href="' . wp_nonce_url( admin_url( 'options-general.php?page=github-core-updater&action=update_now' ), 'github-core-update' ) . '" class="button button-primary">Update Now</a>';
			echo '</td></tr>';
		} else {
			echo '<tr><th>Status</th><td><span class="notice notice-success">You are up to date!</span></td></tr>';
		}

		echo '<tr><th>Auto-Update</th><td>';
		echo '<label><input type="checkbox" name="github_core_auto_update" value="1" ' . checked( get_option( 'github_core_auto_update', false ), true, false ) . '> Enable automatic updates</label>';
		echo '<p class="description">Automatically download and install new releases from GitHub.</p>';
		echo '</td></tr>';

		echo '</table>';
		echo '</div>';
	}
}

// Initialize
new GitHub_Core_Updater();

// Admin notice for update availability
add_action( 'admin_notices', 'github_core_notice' );

function github_core_notice() {
	if ( ! current_user_can( 'update_core' ) ) {
		return;
	}

	$updater = new GitHub_Core_Updater();
	$status  = $updater->get_status();

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