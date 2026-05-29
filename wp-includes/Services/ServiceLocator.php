<?php
/**
 * Service Locator for WordPress core objects.
 *
 * Provides access to core objects without relying on globals.
 *
 * @package WordPress
 * @subpackage Services
 */

namespace WordPress\Services;

use WordPress\DI\Container;

class ServiceLocator {
	private static Container $container;

	public static function init( Container $c ): void {
		self::$container = $c;
	}

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

	public static function wpdb() {
		global $wpdb;
		return $wpdb;
	}

	public static function wp_roles() {
		global $wp_roles;
		return $wp_roles ?? null;
	}

	public static function wp_locale() {
		global $wp_locale;
		return $wp_locale ?? null;
	}

	public static function wp_widget_factory() {
		global $wp_widget_factory;
		return $wp_widget_factory ?? null;
	}
}