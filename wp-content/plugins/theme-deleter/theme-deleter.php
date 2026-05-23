<?php
/**
 * Plugin Name: Theme Deleter
 * Description: Adds delete capability to installed themes
 * Version: 1.0
 * Author: Custom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_action_delete-theme-custom', 'theme_deleter_delete_theme' );

function theme_deleter_delete_theme() {
	if ( ! current_user_can( 'delete_themes' ) ) {
		wp_die( __( 'You do not have permission to delete themes.' ) );
	}

	$theme = isset( $_GET['theme'] ) ? wp_unslash( $_GET['theme'] ) : '';
	$themes = wp_get_themes();

	if ( empty( $theme ) || ! isset( $themes[ $theme ] ) ) {
		wp_die( __( 'Theme does not exist.' ) );
	}

	$stylesheet = $themes[ $theme ]->get_stylesheet();

	if ( is_active_theme( $stylesheet ) ) {
		wp_die( __( 'Cannot delete the active theme.' ) );
	}

	check_admin_referer( 'delete-theme-' . $stylesheet );

	$result = delete_theme( $stylesheet, false );

	if ( is_wp_error( $result ) ) {
		wp_die( $result );
	}

	wp_redirect( admin_url( 'themes.php?deleted=1' ) );
	exit;
}

function is_active_theme( $stylesheet ) {
	$current = wp_get_theme();
	return $current->get_stylesheet() === $stylesheet || $current->get_template() === $stylesheet;
}

add_filter( 'theme_action_links', 'theme_deleter_add_delete_link', 10, 2 );

function theme_deleter_add_delete_link( $actions, $theme ) {
	if ( ! current_user_can( 'delete_themes' ) ) {
		return $actions;
	}

	$stylesheet = $theme->get_stylesheet();
	$active = wp_get_theme()->get_stylesheet();

	if ( $stylesheet === $active || $stylesheet === wp_get_theme()->get_template() ) {
		return $actions;
	}

	$delete_url = wp_nonce_url(
		admin_url( 'admin.php?action=delete-theme-custom&theme=' . urlencode( $stylesheet ) ),
		'delete-theme-' . $stylesheet
	);

	$actions['delete'] = sprintf(
		'<a href="%s" class="delete" onclick="return confirm(\'Are you sure you want to delete \\\'%s\\\'?\');">%s</a>',
		esc_url( $delete_url ),
		esc_attr( $theme->get( 'Name' ) ),
		__( 'Delete' )
	);

	return $actions;
}