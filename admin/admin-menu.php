<?php
/**
 * Admin Menu for Certificate System: Independent Certification
 * 
 * @package Certificate_System
 * @author Morrow Shore
 * @link https://morrowshore.com
 */

// Prevent direct access to this file for security
if (! defined( 'ABSPATH' )) {
	exit;
}

// create main admin menu
function certsystem_certificate_admin_menu() {
	
	// add main menu page
	add_menu_page(
		'certsystem',
		'certsystem',
		'manage_options',
		'certsystem-certificate-management',
		'certsystem_certificate_admin_certificate_ui',
		plugin_dir_url(__FILE__) . '../assets/images/menu-icon.png',
		null
	);
	
	// add settings submenu
	add_submenu_page(
		'certsystem-certificate-management',
		'Settings',
		'Settings',
		'manage_options',
		'certsystem-settings',
		'certsystem_certificate_settings_ui'
	);
	
}
add_action( 'admin_menu', 'certsystem_certificate_admin_menu' );