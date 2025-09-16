<?php
/**
 * Uninstallation Functions for Certificate System: Independent Certification
 * 
 * @package Certificate_System
 * @author Morrow Shore
 * @link https://morrowshore.com
 */

// Prevent direct access to this file for security
if (! defined( 'ABSPATH' )) {
	exit;
}

/**
 * Plugin deactivation function - cleanup when plugin is deactivated
 * This runs when the plugin is deactivated (but not uninstalled)
 */
function certsystem_certificate_certsystem_certificate_onDeactivation() {

	// Security check - only users who can activate plugins can deactivate them
	if ( ! current_user_can( 'activate_plugins' ) ) return;

	// Clear any cached rewrite rules
	flush_rewrite_rules();
}

// Hook for plugin uninstall - this removes everything when plugin is deleted
register_uninstall_hook( __FILE__, 'certsystem_certificate_drop_certificate_table' );

/**
 * Plugin uninstall function - complete cleanup when plugin is deleted
 * This permanently removes all plugin data from the database
 */
function certsystem_certificate_drop_certificate_table(){
	// Security check - only administrators can uninstall plugins
	if ( ! current_user_can( 'activate_plugins' ) ) return;
	
	if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) exit ();
	
	// Remove the plugin's database table and any stored options
	global $wpdb;
	$table_name = $wpdb->prefix . 'certsystem_certificate_management';
		// Sanitize table name and execute DROP TABLE - table names cannot be parameterized
	$table_name = esc_sql($table_name);
	
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Intentional direct query for uninstall cleanup, table name cannot be parameterized
	$result = $wpdb->query("DROP TABLE IF EXISTS `{$table_name}`");
	
	// Clean up any plugin options if needed
	delete_option('certsystem_plugin_options');
	delete_option('certsystem_db_version');
	
	// Clear any cached data
	wp_cache_flush();
}