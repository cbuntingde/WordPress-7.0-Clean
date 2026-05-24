<?php
/**
 * WordPress compatibility functions for PHP 8.5+.
 *
 * This file is loaded extremely early. Only keep functions here that are truly required
 * as fallback for optional extensions.
 *
 * @package PHP
 * @access private
 */

// sodium_crypto_box() requires libsodium extension.
// If not available, load sodium_compat polyfill.
if ( ! function_exists( 'sodium_crypto_box' ) ) {
	require ABSPATH . WPINC . '/sodium_compat/autoload.php';
}
