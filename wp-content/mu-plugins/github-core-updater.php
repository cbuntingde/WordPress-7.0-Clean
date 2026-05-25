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

		// Bypass VCS check since we use stable GitHub releases
		add_filter( 'automatic_updates_is_vcs_checkout', '__return_false' );

		// Hide the default WP reinstall/nightly links - use our releases only
		add_filter( 'update_core_allowed_actions', array( $this, 'filter_update_actions' ) );

		// Add settings page
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Manual update trigger
		add_action( 'admin_init', array( $this, 'handle_update_action' ) );
	}

	/**
	 * Filter out default WP actions - we only support upgrade to our releases
	 */
	public function filter_update_actions( $actions ) {
		return array( 'upgrade' => $actions['upgrade'] );
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
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( ! empty( $_GET['updated'] ) ) : ?>
				<?php $error = get_transient( 'wp_github_update_error' ); ?>
				<?php if ( $_GET['updated'] === '1' && ! $error ) : ?>
					<div class="notice notice-success is-dismissible"><p>Core updated successfully!</p></div>
				<?php elseif ( $error ) : ?>
					<div class="notice notice-error is-dismissible"><p>Update failed: <?php echo esc_html( $error ); ?></p></div>
				<?php endif; ?>
			<?php endif; ?>

			<form method="post" action="<?php echo admin_url( 'options.php' ); ?>">
				<?php settings_fields( 'github-core-updater' ); ?>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">Current Version</th>
							<td><?php echo esc_html( $status['local'] ); ?></td>
						</tr>
						<?php if ( $status['available'] ) : ?>
						<tr>
							<th scope="row">Available Version</th>
							<td><strong><?php echo esc_html( $status['remote'] ); ?></strong></td>
						</tr>
						<tr>
							<th scope="row">Release Notes</th>
							<td><?php echo wp_kses_post( $status['release']['body'] ); ?></td>
						</tr>
						<tr>
							<th scope="row">Action</th>
							<td>
								<a href="<?php echo wp_nonce_url( admin_url( 'options-general.php?page=github-core-updater&action=update_now' ), 'github-core-update' ); ?>" class="button button-primary">Update Now</a>
							</td>
						</tr>
						<?php else : ?>
						<tr>
							<th scope="row">Status</th>
							<td><span class="notice notice-success">You are up to date!</span></td>
						</tr>
						<?php endif; ?>
						<tr>
							<th scope="row">Auto-Update</th>
							<td>
								<fieldset>
									<label for="github_core_auto_update">
										<input type="checkbox" id="github_core_auto_update" name="github_core_auto_update" value="1" <?php checked( get_option( 'github_core_auto_update', false ), true ); ?>>
										Enable automatic updates
									</label>
									<p class="description">Automatically download and install new releases from GitHub.</p>
								</fieldset>
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button( 'Save Changes' ); ?>
			</form>
		</div>
		<?php
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