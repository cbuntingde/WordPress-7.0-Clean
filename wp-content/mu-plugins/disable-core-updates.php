<?php
/**
 * Plugin Name: Disable Core Updates
 * Description: Disables WordPress core automatic updates while allowing plugin and theme updates.
 * Version: 1.0
 */

defined( 'ABSPATH' ) || exit;

// Disable core updates at the constant level.
if ( ! defined( 'WP_AUTO_UPDATE_CORE' ) ) {
	define( 'WP_AUTO_UPDATE_CORE', false );
}

// Also disable via filter for defense in depth.
add_filter( 'auto_update_core', '__return_false' );