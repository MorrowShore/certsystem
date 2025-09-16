<?php
/**
 * Installation Functions for Certificate System: Independent Certification
 * 
 * @package Certificate_System
 * @author Morrow Shore
 * @link https://morrowshore.com
 */

// Prevent direct access to this file for security
if (! defined( 'ABSPATH' )) { 
    exit; 
}

// plugin activation - creates database table
function certsystem_certificate_certsystem_certificate_onActivation(){
	global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Define table name with WordPress prefix
    $table_name = $wpdb->prefix . 'certsystem_certificate_management';
    
    // Create the certificates table with all necessary fields
    $create_table_query = "
    CREATE TABLE IF NOT EXISTS `{$table_name}` (
        id VARCHAR(20) NOT NULL,
        student_name TEXT NOT NULL,
        course_name TEXT NOT NULL,
        course_hours TEXT NOT NULL,
        dob TEXT NOT NULL,
        certificate_template TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_certificate_id (id)
    ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $create_table_query );
    
    // Create certificate templates table
    $templates_table_name = $wpdb->prefix . 'certsystem_certificate_templates';
    $create_templates_table_query = "
    CREATE TABLE IF NOT EXISTS `{$templates_table_name}` (
        id INTEGER NOT NULL AUTO_INCREMENT,
        template_name VARCHAR(255) NOT NULL,
        template_html TEXT NOT NULL,
        is_default TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    dbDelta( $create_templates_table_query );
    
    // Create default template from template file if it doesn't exist
    $default_template = $wpdb->get_row("SELECT * FROM {$templates_table_name} WHERE template_name = 'Default Template'");
    
    if (!$default_template) {
        // Load core functions to get default template HTML
        require_once plugin_dir_path(__FILE__) . 'inc/core-functions.php';
        $default_template_html = certsystem_certificate_get_default_template_html();
        
        // Insert default template
        $wpdb->insert($templates_table_name, array(
            'template_name' => 'Default Template',
            'template_html' => $default_template_html,
            'is_default' => 1
        ));
    }
    
    // Set use_custom_templates option to false by default
    update_option('certsystem_use_custom_templates', false);
}


function certsystem_certificate_ensure_table_exists() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'certsystem_certificate_management';
    $templates_table_name = $wpdb->prefix . 'certsystem_certificate_templates';
    
    // Check if main table exists
    $table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );
    $templates_table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $templates_table_name ) );
    
    if ($table_exists != $table_name || $templates_table_exists != $templates_table_name) {
        // Table doesn't exist, create it
        certsystem_certificate_certsystem_certificate_onActivation();
    } else {
        // Table exists, but check if it has the correct structure
        $auto_increment_field = $wpdb->get_var("SHOW COLUMNS FROM {$table_name} WHERE Extra LIKE '%auto_increment%'");
        if ($auto_increment_field) {
            // Table has auto-increment field, which is wrong - recreate it
            error_log("Table has auto-increment field, recreating table...");
            $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
            certsystem_certificate_certsystem_certificate_onActivation();
        }
    }
}