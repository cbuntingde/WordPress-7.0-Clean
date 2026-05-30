<?php
/**
 * Service Locator for WordPress core objects.
 *
 * Provides access to core objects without relying on globals.
 *
 * @package WordPress
 * @subpackage Services
 */

/**
 * Service locator for WordPress core objects.
 */
class ServiceLocator {
	/**
	 * Container instance.
	 *
	 * @var \WordPress\DI\Container
	 */
	private static $container;

	/**
	 * Initialize service locator.
	 *
	 * @param \WordPress\DI\Container $c Container instance.
	 * @return void
	 */
	public static function init( $c ) {
		self::$container = $c;
	}

	/**
	 * Get WP_Query instance.
	 *
	 * @return WP_Query|null
	 */
	public static function wp_query() {
		global $wp_the_query;
		if ( ! empty( $wp_the_query ) ) {
			return $wp_the_query;
		}
		if ( self::$container->has( 'WP_Query' ) ) {
			return self::$container->make( 'WP_Query' );
		}
		return null;
	}

	/**
	 * Get WP_Rewrite instance.
	 *
	 * @return WP_Rewrite|null
	 */
	public static function wp_rewrite() {
		global $wp_rewrite;
		if ( ! empty( $wp_rewrite ) ) {
			return $wp_rewrite;
		}
		if ( self::$container->has( 'WP_Rewrite' ) ) {
			return self::$container->make( 'WP_Rewrite' );
		}
		return null;
	}

	/**
	 * Get wpdb instance.
	 *
	 * @return wpdb|null
	 */
	public static function wpdb() {
		global $wpdb;
		return $wpdb;
	}

	/**
	 * Get WP_Roles instance.
	 *
	 * @return WP_Roles|null
	 */
	public static function wp_roles() {
		global $wp_roles;
		return $wp_roles;
	}

	/**
	 * Get WP_Locale instance.
	 *
	 * @return WP_Locale|null
	 */
	public static function wp_locale() {
		global $wp_locale;
		return $wp_locale;
	}

	/**
	 * Get WP_Widget_Factory instance.
	 *
	 * @return WP_Widget_Factory|null
	 */
	public static function wp_widget_factory() {
		global $wp_widget_factory;
		return $wp_widget_factory;
	}
}