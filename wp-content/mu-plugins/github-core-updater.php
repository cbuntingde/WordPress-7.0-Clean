<?php
/**
 * Plugin Name: GitHub Core Update Checker
 * Description: Checks GitHub for new releases instead of wordpress.org
 * Version: 1.0
 */

defined( 'ABSPATH' ) || exit;

class GitHub_Core_Update_Checker {
	private const OWNER = 'cbuntingde';
	private const REPO   = 'WordPress-7.0-Clean';

	public function __construct() {
		add_action( 'admin_init', array( $this, 'check_for_updates' ) );
	}

	public function check_for_updates(): void {
		if ( ! current_user_can( 'update_core' ) ) {
			return;
		}

		// Cache the check for 1 hour
		$cached = get_transient( 'wp_github_core_update' );
		if ( false !== $cached ) {
			return;
		}

		$remote_version = $this->get_remote_version();

		if ( ! $remote_version ) {
			return;
		}

		set_transient( 'wp_github_core_update', $remote_version, HOUR_IN_SECONDS );

		if ( version_compare( $this->get_local_version(), $remote_version['version'], '<' ) ) {
			set_transient( 'wp_github_core_update_available', $remote_version, HOUR_IN_SECONDS );
			add_action( 'admin_notices', array( $this, 'show_update_notice' ) );
		}
	}

	private function get_remote_version(): ?array {
		$response = wp_remote_get(
			"https://api.github.com/repos/" . self::OWNER . "/" . self::REPO . "/releases/latest",
			array(
				'headers' => array(
					'Accept' => 'application/vnd.github+json',
					'User-Agent' => 'WordPress-GitHub-Updater',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! $data || empty( $data['tag_name'] ) ) {
			return null;
		}

		// Clean tag: v7.0.1 → 7.0.1
		$version = ltrim( $data['tag_name'], 'v' );

		return array(
			'version' => $version,
			'download' => $data['zipball_url'] ?? '',
			'body'    => $data['body'] ?? '',
			'url'     => $data['html_url'] ?? '',
		);
	}

	private function get_local_version(): string {
		global $wp_version;
		require ABSPATH . 'wp-includes/version.php';
		return $wp_version;
	}

	public function show_update_notice(): void {
		$update = get_transient( 'wp_github_core_update_available' );
		if ( ! $update ) {
			return;
		}

		printf(
			'<div class="notice notice-info is-dismissible">
				<p>
					<strong>New WordPress release available:</strong> %1$s<br>
					<a href="%2$s" target="_blank">View on GitHub</a> |
					<a href="%3$s">Download Zip</a>
				</p>
				<pre style="max-height:200px;overflow:auto;margin-top:10px;">%4$s</pre>
			</div>',
			esc_html( $update['version'] ),
			esc_url( $update['url'] ),
			esc_url( $update['download'] ),
			esc_html( substr( $update['body'], 0, 1000 ) )
		);
	}
}

new GitHub_Core_Update_Checker();