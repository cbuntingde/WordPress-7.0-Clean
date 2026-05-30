<?php
/**
 * Date and time utilities.
 *
 * @package    WordPress
 * @subpackage Utils
 */

namespace WordPress\Utils;

use DateTimeZone;

/**
 * Retrieves the current time as an object using the site's timezone.
 *
 * @since 5.3.0
 */
function current_datetime()
{
    return new \DateTimeImmutable('now', wp_timezone());
}

/**
 * Retrieves the timezone of the site as a PHP timezone string.
 *
 * @since 5.3.0
 */
function get_timezone_string()
{
    $timezone_string = \get_option('timezone_string');

    if ($timezone_string ) {
        return $timezone_string;
    }

    $offset  = (float) \get_option('gmt_offset');
    $hours   = (int) $offset;
    $minutes = ( $offset - $hours );

    $sign     = ( $offset < 0 ) ? '-' : '+';
    $abs_hour = abs($hours);
    $abs_mins = abs($minutes * 60);
    $tz_offset = sprintf('%s%02d:%02d', $sign, $abs_hour, $abs_mins);

    return $tz_offset;
}

/**
 * Retrieves the timezone of the site as a DateTimeZone object.
 *
 * @since 5.3.0
 */
function get_timezone()
{
    return new DateTimeZone(get_timezone_string());
}
