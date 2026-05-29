<?php
/**
 * Utils package index - loads all Utils autoloading.
 *
 * @package WordPress
 */

namespace WordPress\Utils;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Register autoloader once.
require __DIR__ . '/Autoloader.php';