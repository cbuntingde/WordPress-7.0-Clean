<?php
/**
 * PSR-4 Autoloader for WordPress Utils namespace.
 *
 * @package WordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

spl_autoload_register(
	function ( $class ) {
		$prefix = 'WordPress\\Utils\\';
		$base_dir = __DIR__ . '/';

		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );
		$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);