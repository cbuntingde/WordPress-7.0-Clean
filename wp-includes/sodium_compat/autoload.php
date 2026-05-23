<?php

if (!class_exists('ParagonIE_Sodium_Compat', false)) {
    require_once dirname(__FILE__) . '/src/Compat.php';
}

if (!class_exists('SodiumException', false)) {
    require_once dirname(__FILE__) . '/src/SodiumException.php';
}

require_once dirname(__FILE__) . '/lib/namespaced.php';
require_once dirname(__FILE__) . '/lib/sodium_compat.php';
if (!defined('SODIUM_CRYPTO_AEAD_AEGIS128L_KEYBYTES')) {
    require_once dirname(__FILE__) . '/lib/php84compat_const.php';
}

if (!extension_loaded('sodium')) {
    require_once dirname(__FILE__) . '/lib/php72compat_const.php';
    require_once dirname(__FILE__) . '/lib/php72compat.php';
} elseif (!function_exists('sodium_crypto_stream_xchacha20_xor')) {
    require_once dirname(__FILE__) . '/lib/php72compat.php';
}

if (!extension_loaded('sodium') || PHP_VERSION_ID < 80400) {
    require_once dirname(__FILE__) . '/lib/php84compat.php';
}

require_once dirname(__FILE__) . '/lib/stream-xchacha20.php';
require_once dirname(__FILE__) . '/lib/ristretto255.php';