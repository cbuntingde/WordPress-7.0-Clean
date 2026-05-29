<?php
/**
 * Plugin Name: Secure Endpoints
 * Description: Disables XML-RPC and restricts REST API to authenticated users only
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Disable XML-RPC API
 * Prevents pingbacks and unwanted XML-RPC access
 */
add_filter('xmlrpc_enabled', '__return_false');

/**
 * Disable REST API for anonymous users
 * Requires authentication for all REST endpoints
 */
add_filter('rest_authentication_errors', function ($result) {
    if (!is_user_logged_in()) {
        return new WP_Error(
            'rest_not_logged_in',
            'REST API requires authentication.',
            array('status' => 401)
        );
    }
    return $result;
});

/**
 * Rate limiting for login attempts
 * Protects against brute force attacks
 */
add_action('wp_login_failed', function ($username) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $transient_name = 'login_fail_' . md5($ip);

    $fail_count = get_transient($transient_name) ?: 0;
    set_transient($transient_name, $fail_count + 1, 300);

    if ($fail_count >= 4) {
        sleep(5);
    }
});

add_action('wp_login_errors', function ($errors) {
    if ($errors->get_error_code() === 'invalid_username' ||
        $errors->get_error_code() === 'invalid_password' ||
        $errors->get_error_code() === 'invalid_email') {

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $transient_name = 'login_fail_' . md5($ip);
        $fail_count = get_transient($transient_name) ?: 0;

        if ($fail_count >= 3) {
            $errors->add('rate_limited', __('Too many login attempts. Please wait 5 minutes.'), array('status' => 403));
        }
    }
    return $errors;
});