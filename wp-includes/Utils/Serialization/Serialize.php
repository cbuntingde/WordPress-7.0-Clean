<?php
/**
 * Serialization utilities.
 *
 * @package WordPress
 * @subpackage Utils
 */

namespace WordPress\Utils\Serialization;

/**
 * Serialize data if needed.
 *
 * @since 2.0.5
 * @access private
 *
 * @param mixed $data Data that might be serialized.
 * @return mixed A serialized data if needed, otherwise unchanged.
 */
function maybe_serialize( $data ) {
	if ( is_array( $data ) || is_object( $data ) ) {
		return serialize( $data );
	}

	if ( \is_serialized( $data ) ) {
		return serialize( $data );
	}

	return $data;
}

/**
 * Unserialize data if needed.
 *
 * @since 2.0.5
 * @access private
 *
 * @param mixed $data Data that might be unserialized.
 * @return mixed Unserialized data, or original data if not serializable.
 */
function maybe_unserialize( $data ) {
	if ( \is_serialized_string( $data ) ) {
		return @unserialize( $data );
	}

	return $data;
}