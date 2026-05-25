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

// PHP 8.5 has native sodium extension - no polyfill needed.
// Previous sodium_compat polyfill has been removed.