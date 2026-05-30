<?php
/**
 * Utils package index.
 *
 * Loads all Utils autoloading.
 *
 * @package WordPress
 */

namespace WordPress\Utils;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Register autoloader once.
require __DIR__ . '/autoloader.php';