<?php
/**
 * API Package Index.
 *
 * Maps deprecated function names to their new class-based implementations.
 * This ensures backward compatibility as functions are migrated to classes.
 *
 * @package WordPress
 */

namespace WordPress\Api;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Get WP_Query instance - prefer singleton over global.
 *
 * @return WP_Query|null
 */
function get_wp_query() {
	global $wp_the_query;
	return $wp_the_query ?? null;
}

/**
 * Get WP_Rewrite instance.
 *
 * @return WP_Rewrite|null
 */
function get_wp_rewrite() {
	global $wp_rewrite;
	return $wp_rewrite ?? null;
}

/**
 * Get wpdb instance.
 *
 * @return wpdb|null
 */
function get_wpdb() {
	global $wpdb;
	return $wpdb ?? null;
}

/**
 * Get current user's WP_User object.
 *
 * @return WP_User|null
 */
function get_current_user() {
	return \wp_get_current_user();
}