<?php
/**
 * Query argument utilities.
 *
 * @package WordPress
 * @subpackage Utils
 */

namespace WordPress\Utils\Query;

/**
 * Build a query string.
 *
 * @since 2.0.5
 * @access private
 *
 * @param mixed $query Original query.
 * @return string Query string.
 */
function build_query( $query ) {
	return \build_query( $query );
}

/**
 * Parse arguments into a query string.
 *
 * @since 2.2.0
 *
 * @param mixed $args       Query arguments.
 * @param mixed $old_args    Optional. Previous arguments for merging.
 * @return string Parsed query string.
 */
function parse_args( $args, $old_args = null ) {
	return \wp_parse_args( $args, $old_args );
}

/**
 * Add query arguments to a URL.
 *
 * @since 1.5.0
 *
 * @param mixed $args    Query arguments to add.
 * @param string $url    Optional. URL to add arguments to.
 * @return string URL with query arguments added.
 */
function add_query_arg( ...$args ) {
	return \add_query_arg( ...$args );
}

/**
 * Remove query arguments from a URL.
 *
 * @since 1.5.0
 *
 * @param mixed $keys   Query keys to remove.
 * @param string $url   Optional. URL to remove arguments from.
 * @return string URL with removed arguments.
 */
function remove_query_arg( $keys, $url = false ) {
	return \remove_query_arg( $keys, $url );
}