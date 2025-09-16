<?php
/**
 * Core Database Functions for Certificate System: Independent Certification
 * 
 * @package Certificate_System
 * @author Morrow Shore
 * @link https://morrowshore.com
 */

// generate unique certificate id
function certsystem_certificate_generate_id() {
    $random_part = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 4);
    $date_part = gmdate('ymd');
    return $random_part . $date_part;
}

/**
 * Add or update a certificate in the database
 * @param string $certificate_id Certificate ID (empty for auto-generation)
 * @param string $name Student name
 * @param string $course Course name
 * @param string $hours Hours completed
 * @param string $doc Date of completion
 * @param string $editid ID for editing (empty for new certificates)
 * @return int 1 for success, 0 for failure
 */
function certsystem_certificate_add_course_certificate($certificate_id, $name, $course, $hours, $doc, $editid){
	global $wpdb;
    
    // Define table name with WordPress prefix
    $table_name = $wpdb->prefix . 'certsystem_certificate_management';
    
    // Ensure table has correct structure (no auto-increment fields)
    require_once plugin_dir_path(__FILE__) . '../install.php';
    certsystem_certificate_ensure_table_exists();
    
    // Convert date format if needed (from mm/dd/yyyy to proper format)
    if (!empty($doc)) {
        // Use WordPress timezone-safe date conversion
        $timestamp = strtotime($doc);
        $doc = gmdate('Y-m-d', $timestamp);
    }
    
    // Auto-generate certificate ID if empty or just whitespace
    error_log("Certificate ID before processing: '" . $certificate_id . "'");
    error_log("Empty check: " . (empty(trim($certificate_id)) ? "true" : "false"));
    
    if (empty(trim($certificate_id))) {
        $certificate_id = certsystem_certificate_generate_id();
        error_log("Generated certificate ID: " . $certificate_id);
        // Ensure uniqueness
        $attempts = 0;
        while ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE id = %s", $certificate_id)) > 0 && $attempts < 10) {
            $certificate_id = certsystem_certificate_generate_id();
            $attempts++;
            error_log("Regenerated certificate ID (attempt $attempts): " . $certificate_id);
        }
        error_log("Final certificate ID: " . $certificate_id);
        error_log("Input certificate_id was empty or whitespace");
    } else {
        error_log("Passed certificate ID: " . $certificate_id);
        error_log("Input certificate_id was: '" . $certificate_id . "'");
    }
    
    // If editid is provided, update existing certificate
    if( !empty($editid) ) {
        $result = $wpdb->update($table_name, array(
            'id' => $certificate_id,
            'student_name' => $name,
            'course_name'  => $course,
            'course_hours' => $hours,
            'dob' => $doc,
            ),
            array( 'id' => $editid )
        );
        // Return 1 for success, 0 for failure
        $success = ($result !== false) ? 1 : 0;
        
        // Clear cache when certificate is updated
        if ($success) {
            wp_cache_delete('certsystem_certificates_all', 'certsystem_plugin');
            wp_cache_delete('certsystem_certificate_' . md5($certificate_id), 'certsystem_plugin');
        }
        
        return $success;
    } else {
        // Add new certificate to database
        $insert_data = array(
            'id' => $certificate_id,
            'student_name' => $name,
            'course_name'  => $course,
            'course_hours' => $hours,
            'dob' => $doc,
        );
        error_log("Insert data: " . print_r($insert_data, true));
        error_log("Certificate ID length: " . strlen($certificate_id));
        error_log("Certificate ID type: " . gettype($certificate_id));
        
        $result = $wpdb->insert($table_name, $insert_data);
        
        // Debug: log database insertion result and table info
        error_log("Database insert result: " . ($result !== false ? "Success" : "Failed"));
        error_log("Inserted certificate ID: " . $certificate_id);
        error_log("Table name: " . $table_name);
        error_log("Last error: " . $wpdb->last_error);
        error_log("Last query: " . $wpdb->last_query);
        error_log("Insert ID: " . $wpdb->insert_id);
        
        // Return 1 for success, 0 for failure
        $success = ($result !== false) ? 1 : 0;
        
        // Clear cache when new certificate is added
        if ($success) {
            wp_cache_delete('certsystem_certificates_all', 'certsystem_plugin');
            wp_cache_delete('certsystem_certificate_' . md5($certificate_id), 'certsystem_plugin');
        }
        
        return $success;
    }
}

/**
 * Get default certificate template
 * @return object Template object
 */
function certsystem_certificate_get_default_template() {
    global $wpdb;
    $templates_table = $wpdb->prefix . 'certsystem_certificate_templates';
    $default_template = $wpdb->get_row("SELECT * FROM {$templates_table} WHERE is_default = 1 LIMIT 1");
    
    // If no default template exists, create one from the file
    if (!$default_template) {
        $default_template_html = certsystem_certificate_get_default_template_html();
        
        $wpdb->insert($templates_table, array(
            'template_name' => 'Default Template',
            'template_html' => $default_template_html,
            'is_default' => 1
        ));
        
        $default_template = $wpdb->get_row("SELECT * FROM {$templates_table} WHERE is_default = 1 LIMIT 1");
    }
    
    return $default_template;
}

/**
 * Get default template HTML from template file
 * @return string Default template HTML
 */
function certsystem_certificate_get_default_template_html() {
    $template_file = plugin_dir_path(__FILE__) . '../certification-template.php';
    $template_content = file_get_contents($template_file);
    
    // Extract only the HTML part (after the closing PHP tag)
    $html_start = strpos($template_content, '?>');
    if ($html_start !== false) {
        $template_html = substr($template_content, $html_start + 2);
        $template_html = trim($template_html);
        
        // Replace PHP variables with placeholder-style variables for custom templates
        $template_html = str_replace(
            array(
                '<?php echo $student_name; ?>',
                '<?php echo $course_name; ?>',
                '<?php echo $course_hours; ?>',
                '<?php echo $completion_date; ?>',
                '<?php echo $certificate_display_id; ?>',
                '<?php echo $qr_code_svg; ?>'
            ),
            array(
                '{{student_name}}',
                '{{course_name}}',
                '{{course_hours}}',
                '{{completion_date}}',
                '{{certificate_id}}',
                '{{qr_code}}'
            ),
            $template_html
        );
        
        return $template_html;
    }
    
    return ''; // Return empty string if HTML not found
}



/**
 * Get certificate URL by ID
 * @param int $certificate_id Certificate ID
 * @return string Certificate URL
 */
function certsystem_certificate_get_url($certificate_id) {
    $certificate_slug = get_option('certsystem_certificate_slug', 'certificate');
    return home_url('/' . $certificate_slug . '/?id=' . $certificate_id);
}

/**
 * Get certificate by ID
 * @param string $certificate_id Certificate ID
 * @return object Certificate object
 */
function certsystem_certificate_get($certificate_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'certsystem_certificate_management';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %s", $certificate_id));
}

/**
 * Debug function to check database structure
 */
function certsystem_certificate_debug_db_structure() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'certsystem_certificate_management';
    $results = $wpdb->get_results("DESCRIBE {$table_name}");
    error_log("Database structure for {$table_name}:");
    foreach ($results as $row) {
        error_log("Field: {$row->Field}, Type: {$row->Type}, Null: {$row->Null}, Key: {$row->Key}, Default: {$row->Default}, Extra: {$row->Extra}");
    }
    
    // Also check if there's an auto-increment field
    $auto_increment_field = $wpdb->get_var("SHOW COLUMNS FROM {$table_name} WHERE Extra LIKE '%auto_increment%'");
    error_log("Auto-increment field: " . ($auto_increment_field ? $auto_increment_field : "None"));
}

/**
 * Generate QR code using JavaScript library
 * @param string $url URL to encode
 * @param int $size QR code size in pixels
 * @return string HTML container for QR code
 */
function certsystem_certificate_generate_qr_svg($url, $size = 100) {
    static $script_added = false;
    
    $id = 'qr-' . uniqid();
    $html = '';
    
    // Add QR.js library only once
    if (!$script_added) {
        $html .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>';
        $script_added = true;
    }
    
    $html .= '<canvas id="' . $id . '" width="' . $size . '" height="' . $size . '" class="qr-code-canvas"></canvas>';
    $html .= '<script>
        new QRious({
            element: document.getElementById("' . $id . '"),
            value: "' . esc_js($url) . '",
            size: ' . intval($size) . ',
            background: "transparent",
            foreground: "#000000"
        });
    </script>';
    
    return $html;
}

/**
 * Delete a certificate from the database
 * @param string $certificate_id Certificate ID to delete
 * @return bool True if successful, false if failed
 */
function certsystem_certificate_delete_course_certificate($certificate_id) {
    global $wpdb;
    
    // Define table name with WordPress prefix
    $table_name = $wpdb->prefix . 'certsystem_certificate_management';
    $result = 0;
    
    // Only delete if we have a valid ID
    if( !empty($certificate_id) ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table requires direct query
        $delete_result = $wpdb->delete($table_name, array( 'id' => $certificate_id ));
        // Return 1 for success, 0 for failure
        $result = ($delete_result !== false) ? 1 : 0;
        
        // Clear cache when certificate is deleted
        if ($result) {
            wp_cache_delete('certsystem_certificates_all', 'certsystem_plugin');
            wp_cache_delete('certsystem_certificate_' . md5($certificate_id), 'certsystem_plugin');
        }
    }
    return $result;
}

/**
 * Get all certificates from the database with caching
 * @return array Array of certificate objects
 */
function certsystem_certificate_get_all_certificates() {
    // Get certificates with caching for better performance
    $cache_key = 'certsystem_certificates_all';
    $certificates = wp_cache_get($cache_key, 'certsystem_plugin');
    
    if (false === $certificates) {        global $wpdb;
        $table_name = $wpdb->prefix . 'certsystem_certificate_management';
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table requires direct query, table name cannot be parameterized
        $certificates = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY STR_TO_DATE(dob, '%d/%m/%Y') DESC");
        
        // Debug: log what certificates are returned
        error_log("Retrieved certificates: " . count($certificates));
        foreach ($certificates as $cert) {
            error_log("Certificate ID: " . $cert->id . ", Type: " . gettype($cert->id));
        }
        
        // Cache for 5 minutes (300 seconds)
        wp_cache_set($cache_key, $certificates, 'certsystem_plugin', 300);
    }
    
    return $certificates ? $certificates : array();
}