<?php
/**
 * Certificate Template File
 * This file renders individual certificates and can be iframed
 * 
 * @package Certificate_System
 * @author Morrow Shore
 */

// Prevent direct access unless it's a valid certificate request
if (!defined('ABSPATH') && !isset($_GET['id'])) {
    exit('Direct access not permitted');
}

// If accessed directly, load WordPress
if (!defined('ABSPATH')) {
    // Find WordPress root
    $wp_root = dirname(dirname(dirname(dirname(__FILE__))));
    require_once($wp_root . '/wp-load.php');
}

// Get certificate ID from URL parameter
$certificate_id = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';

if (empty($certificate_id)) {
    wp_die('Certificate ID is required');
}

// Load core functions
require_once plugin_dir_path(__FILE__) . 'inc/core-functions.php';

// Get certificate data
$certificate = certsystem_certificate_get($certificate_id);

if (!$certificate) {
    wp_die('Certificate not found');
}

// Generate QR code URL
$qr_code_url = certsystem_certificate_get_url($certificate->id);

// Prepare certificate data for display
$student_name = esc_html($certificate->student_name);
$course_name = esc_html($certificate->course_name);
$course_hours = esc_html($certificate->course_hours);
$completion_date = esc_html(gmdate("d/m/Y", strtotime($certificate->dob)));
$certificate_display_id = esc_html($certificate->id);

// Generate QR code 
$qr_code_svg = certsystem_certificate_generate_qr_svg($qr_code_url, 108);

// Check if custom templates are enabled
$use_custom_templates = get_option('certsystem_use_custom_templates', false);

if ($use_custom_templates) {
    // Use custom template from database
    $default_template = certsystem_certificate_get_default_template();
    
    if ($default_template && !empty($default_template->template_html)) {
        // Replace placeholders in the custom template with actual values
        $template_html = str_replace(
            array(
                '{{student_name}}',
                '{{course_name}}',
                '{{course_hours}}',
                '{{completion_date}}',
                '{{certificate_id}}',
                '{{qr_code}}'
            ),
            array(
                $student_name,
                $course_name,
                $course_hours,
                $completion_date,
                $certificate_display_id,
                $qr_code_svg
            ),
            $default_template->template_html
        );
        
        echo $template_html;
        exit;
    }
}

// Fall back to default template if custom templates are disabled or no template found
?>
        <div class="certsystem-bg"></div>
        
        <div class="decorative-corners corner-tl"></div>
        <div class="decorative-corners corner-tr"></div>
        <div class="decorative-corners corner-bl"></div>
        <div class="decorative-corners corner-br"></div>
        
        <div class="certsystem-content">
            <div class="decorative-header"></div>
            
            <h1 class="certsystem-title">CERTIFICATE</h1>
            <div class="certsystem-subtitle">OF COMPLETION</div>
            
            <div class="decorative-line"></div>
            
            <p class="certifies-text">This is to certify that</p>
            
            <h2 class="student-name"><?php echo $student_name; ?></h2>
            
            <p class="completion-text">has successfully completed</p>
            
            <h3 class="course-name"><?php echo $course_name; ?></h3>

            <div class="extra-note"> <?php // echo $course_hours; ?> </div>
            

        </div>
        
        <div class="certsystem-footer">
            <div class="completion-date">
                <div class="detail-label">Completed On</div>
                <div class="detail-value"><?php echo $completion_date; ?></div>
            </div>
            <div class="certsystem-id">
                <div class="detail-label">CERTIFICATE ID</div>
                <div class="detail-value"><?php echo $certificate_display_id; ?></div>
            </div>
            <div class="qr-code">
                <?php echo $qr_code_svg; ?>
            </div>
        </div>