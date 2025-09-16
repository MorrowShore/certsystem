<?php
/**
 * @package certsystem_Certificate_Management
 * @version 1.0
 */
/**
 * Plugin Name: Certificate System: Independent Certification
 * Plugin URI: https://morrowshore.com/
 * Description: A comprehensive independent certification system for creating, managing, and verifying digital certificates with 10-digit unique IDs and QR code validation.
 * Version: 1.0
 * Author: Morrow Shore
 * Author URI: https://morrowshore.com
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * License: AGPLv3 or later
 * License URI: https://www.gnu.org/licenses/agpl-3.0.en.html
 */

if (! defined( 'ABSPATH' )) {
	exit;
}

// Plugin constants - these help identify and configure the plugin
if (!defined('certsystem_PLUGIN_VERSION')) {
    define('certsystem_PLUGIN_VERSION', '1.1');
}
if (!defined('certsystem_PLUGIN_PATH')) {
    define('certsystem_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

function certsystem_certificate_admin_assets($hook) {
    if (!isset($_GET['page']) || strpos(sanitize_text_field(wp_unslash($_GET['page'])), 'certsystem') !== 0) {
        return; 
    }
    
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script('dataTable-js', plugin_dir_url(__FILE__) . 'assets/js/jquery.dataTables.min.js', array('jquery'), certsystem_PLUGIN_VERSION, true);
    wp_enqueue_style('certsystem-user', plugin_dir_url(__FILE__) . 'assets/css/certsystem-user.css', array(), certsystem_PLUGIN_VERSION);
    wp_enqueue_style('certsystem-admin-css', plugin_dir_url(__FILE__) . 'assets/css/certsystem-admin.css', array(), certsystem_PLUGIN_VERSION);
    wp_enqueue_style('material-icons', plugin_dir_url(__FILE__) . 'assets/css/material-icons.css', array(), certsystem_PLUGIN_VERSION);
    wp_enqueue_script('certsystem-admin-js', plugin_dir_url(__FILE__) . 'assets/js/certsystem-admin.js', array('jquery', 'jquery-ui-datepicker', 'dataTable-js'), certsystem_PLUGIN_VERSION, true);
    
    // Enqueue WordPress media scripts for image uploader on settings page
    if ($_GET['page'] === 'certsystem-settings') {
        wp_enqueue_media();
    }
}

add_action('admin_enqueue_scripts', 'certsystem_certificate_admin_assets');


add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('certsystem-user', plugin_dir_url(__FILE__).'assets/css/certsystem-user.css', array(), certsystem_PLUGIN_VERSION);
});




add_action('wp_ajax_certsystem_search', 'certsystem_ajax_search_handler');
add_action('wp_ajax_nopriv_certsystem_search', 'certsystem_ajax_search_handler');

function certsystem_ajax_search_handler() {
    if (!wp_verify_nonce($_POST['search_nonce'], 'search_certificate')) {
        wp_die('Security check failed');
    }
    
    $code = sanitize_text_field($_POST['certificate_id']);
    if (empty($code) || strlen($code) != 10) {
        echo '<div class="danger">Invalid certificate ID format. Must be exactly 10 characters.</div>';
        wp_die();
    }
    
    // Copy the existing search logic from certsystem_certificate_search_form()
    $cache_key = 'certsystem_certificate_' . md5($code);
    $rows = wp_cache_get($cache_key, 'certsystem_plugin');
    if (false === $rows) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'certsystem_certificate_management';
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %s", $code));
        wp_cache_set($cache_key, $rows, 'certsystem_plugin', 600);
    }
    
    if (!empty($rows)) {
        foreach ($rows as $data) {
            $tick = '<span class="cf-tick" title="Verified"><svg viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="10" fill="#27ae60"/><path d="M6 10.5L9 13.5L14 7.5" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>';
            $new_tab_url = certsystem_certificate_get_url($data->id) . '&display=certificate';
            
            echo '<h3 class="rs-heading">Search Result</h3>';
            echo '<div class="cf-result-card">
                <div class="cf-row"><div class="cf-label">Candidate Name</div><div class="cf-value">'.esc_html($data->student_name).' '.$tick.'</div></div>
                <div class="cf-row"><div class="cf-label">Project / Course Title</div><div class="cf-value">'.esc_html($data->course_name).'</div></div>
                <div class="cf-row"><div class="cf-label">Role / Designation</div><div class="cf-value">'.esc_html($data->course_hours).'</div></div>
                <div class="cf-row"><div class="cf-label">Certificate ID</div><div class="cf-value">'.esc_html($data->id).'</div></div>
                <div class="cf-row"><div class="cf-label">Date of Completion</div><div class="cf-value date-field">'.esc_html(gmdate("d/m/Y", strtotime($data->dob))).'</div></div>
                <div class="cf-row"><div class="cf-label">Certificate Link</div><div class="cf-value">
                    <a href="#" class="btn btn-primary btn-sm view-certificate-popup" data-certificate-id="'.esc_attr($data->id).'">View Certificate</a>
                    <a href="'.esc_url($new_tab_url).'" target="_blank" class="btn btn-secondary btn-sm new-tab-btn" title="Open in new tab">↗️</a>
                </div></div>
            </div>';
        }
    } else {
        echo '<div class="danger">No result found against this ID <strong>'.esc_html($code).'</strong></div>';
    }
    wp_die();
}




/**
 * Generate the certificate search form and handle search results
 * This creates the shortcode [certsystem] that users can place on any page
 */
function certsystem_certificate_search_form(){ 
    $output = '';
    $output .= '<div class="cf-search">
        <form id="certsystem-search-form">
            ' . wp_nonce_field('search_certificate', 'search_nonce', true, false) . '
            <input type="text" required class="cf-field" placeholder="Enter Certificate ID" name="certificate_id">
            <input type="submit" class="cf-btn" value="Search" name="code_data">
        </form>
    </div>
    <div class="cs-container">
        <div id="certsystem-results"></div>
    </div>
    <script>
jQuery(document).ready(function($) {
    $("#certsystem-search-form").on("submit", function(e) {
        e.preventDefault();
        $("#certsystem-results").html("<div style=\"text-align: center;\">Searching...</div>");
        $.post("' . admin_url('admin-ajax.php') . '", $(this).serialize() + "&action=certsystem_search", function(data) {
            $("#certsystem-results").html(data);
        });
    });
    
    // View certificate popup functionality
    $(document).on("click", ".view-certificate-popup", function(e) {
        e.preventDefault();
        var certificateId = $(this).data("certificate-id");
        var certificateSlug = "certificate";
        
        var overlayHtml = "<div id=\"certsystem-iframe-overlay\" style=\"position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; display: flex; justify-content: center; align-items: center;\">" +
            "<div style=\"position: relative; width: 95%; height: 95%; border-radius: 0px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.5);\">" +
            "<div id=\"certsystem-iframe-controls\" style=\"position: absolute; top: 10px; right: 10px; z-index: 10000;\">" +
            "<button id=\"certsystem-close-iframe\" title=\"Close\" style=\"background: #dc3232; color: white; border: none; padding: 8px 12px; border-radius: 3px; cursor: pointer;\">×</button>" +
            "</div>" +
            "<iframe id=\"certsystem-certificate-iframe\" src=\"" + window.location.origin + "/" + certificateSlug + "/?id=" + certificateId + "&display=certificate\" style=\"width: 100%; height: 100%; border: none;\"></iframe>" +
            "</div>" +
            "</div>";
        
        $("#certsystem-iframe-overlay").remove();
        $("body").append(overlayHtml);
        
        $("#certsystem-close-iframe").on("click", function() {
            $("#certsystem-iframe-overlay").remove();
        });
        
        $("#certsystem-iframe-overlay").on("click", function(e) {
            if (e.target === this) {
                $(this).remove();
            }
        });
        
        $(document).on("keydown.certsystem-popup", function(e) {
            if (e.key === "Escape") {
                $("#certsystem-iframe-overlay").remove();
                $(document).off("keydown.certsystem-popup");
            }
        });
    });
});
</script>';
    return $output;
}
// Register the shortcode
add_shortcode( 'certsystem', 'certsystem_certificate_search_form' );

require_once plugin_dir_path(__FILE__) . 'admin/admin-menu.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-settings.php';

require_once plugin_dir_path(__FILE__) . 'inc/core-functions.php';

register_activation_hook(__FILE__, 'certsystem_certificate_certsystem_certificate_onActivation');
require_once plugin_dir_path(__FILE__) . 'install.php';

if (is_admin()) {
    add_action('admin_init', function() {
        require_once plugin_dir_path(__FILE__) . 'install.php';
        certsystem_certificate_ensure_table_exists();
    });
}

// Register rewrite rule for certificate pages
add_action('init', function() {
    $certificate_slug = get_option('certsystem_certificate_slug', 'certificate');
    add_rewrite_rule('^' . $certificate_slug . '/?$', 'index.php?certsystem_page=1', 'top');
});

add_filter('query_vars', function($vars) {
    $vars[] = 'certsystem_page';
    return $vars;
});

// Handle certificate page requests
add_action('template_redirect', function() {
    $certificate_slug = get_option('certsystem_certificate_slug', 'certificate');
    $current_url = $_SERVER['REQUEST_URI'];
    
    // Check if we're on the certificate page
    if (strpos($current_url, '/' . $certificate_slug . '/') !== false || 
        strpos($current_url, '/' . $certificate_slug . '?') !== false) {
        
        $certificate_id = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
        
        if (!empty($certificate_id)) {
            // If certificate ID is present, show iframe popup
            add_action('wp_footer', function() use ($certificate_id) {
                $certificate_url = home_url('/' . get_option('certsystem_certificate_slug', 'certificate') . '/?id=' . $certificate_id . '&display=certificate');
                ?>
                <div id="certsystem-iframe-overlay">
                    <div>
                        <div id="certsystem-iframe-controls">
                            <!-- backup button in case shit goes south
                        <button id="certsystem-download-png" title="Download as PNG">↓</button>
                        -->
                            <button id="certsystem-close-iframe" title="Close">×</button>
                        </div>
                        <iframe id="certsystem-certificate-iframe" src="<?php echo esc_url($certificate_url); ?>"></iframe>
                    </div>
                </div>
                <link rel="stylesheet" href="<?php echo plugin_dir_url(__FILE__) . 'assets/css/certsystem-certificate.css'; ?>">
                <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
                <script>
                (function() {
                    // Show the iframe overlay after page load
                    document.addEventListener('DOMContentLoaded', function() {
                        var overlay = document.getElementById('certsystem-iframe-overlay');
                        var closeBtn = document.getElementById('certsystem-close-iframe');
                        var downloadBtn = document.getElementById('certsystem-download-png');
                        var iframe = document.getElementById('certsystem-certificate-iframe');
                        
                        overlay.style.display = 'flex';
                        
                        // Close button functionality
                        closeBtn.addEventListener('click', function() {
                            overlay.style.display = 'none';
                            // Remove the ID parameter from URL without reloading
                            var url = new URL(window.location);
                            url.searchParams.delete('id');
                            window.history.replaceState({}, '', url);
                        });
                        
                        // Download button functionality
                        downloadBtn.addEventListener('click', function() {
                            try {
                                // Wait for iframe to load completely
                                iframe.onload = function() {
                                    try {
                                        var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                                        var certificateContainer = iframeDoc.querySelector('.certsystem-container');
                                        
                                        if (certificateContainer) {
                                            // Use html2canvas to capture the certificate as an image
                                            html2canvas(certificateContainer, {
                                                scale: 2,
                                                useCORS: true,
                                                logging: false,
                                                allowTaint: true
                                            }).then(function(canvas) {
                                                // Convert canvas to image data
                                                var imgData = canvas.toDataURL('image/png');
                                                
                                                // Create download link
                                                var link = document.createElement('a');
                                                link.download = 'certificate-' + new Date().getTime() + '.png';
                                                link.href = imgData;
                                                document.body.appendChild(link);
                                                link.click();
                                                document.body.removeChild(link);
                                            }).catch(function(error) {
                                                console.error('Error capturing certificate:', error);
                                                alert('Error downloading certificate. Please try again.');
                                            });
                                        }
                                    } catch (e) {
                                        console.error('Error accessing iframe content:', e);
                                        alert('Error accessing certificate content. Please try again.');
                                    }
                                };
                                
                                // Trigger load if already loaded
                                if (iframe.contentDocument && iframe.contentDocument.readyState === 'complete') {
                                    iframe.onload();
                                }
                            } catch (e) {
                                console.error('Error downloading certificate:', e);
                                alert('Error downloading certificate. Please try again.');
                            }
                        });
                        
                        // Close on overlay click (outside iframe)
                        overlay.addEventListener('click', function(e) {
                            if (e.target === this) {
                                overlay.style.display = 'none';
                                var url = new URL(window.location);
                                url.searchParams.delete('id');
                                window.history.replaceState({}, '', url);
                            }
                        });
                        
                        // Escape key to close
                        document.addEventListener('keydown', function(e) {
                            if (e.key === 'Escape') {
                                overlay.style.display = 'none';
                                var url = new URL(window.location);
                                url.searchParams.delete('id');
                                window.history.replaceState({}, '', url);
                            }
                        });
                    });
                })();
                </script>
                <?php
            });
        }
    }
    
    // Handle direct certificate display requests
    if (isset($_GET['display']) && $_GET['display'] === 'certificate' && isset($_GET['id'])) {
        $certificate_id = sanitize_text_field($_GET['id']);
        if (!empty($certificate_id)) {
            certsystem_certificate_display_single($certificate_id);
            exit;
        }
    }
});

// Prevent certificate plugin CSS from loading on certificate pages to avoid conflicts
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('certsystem-user', plugin_dir_url(__FILE__).'assets/css/certsystem-user.css', array(), certsystem_PLUGIN_VERSION);
    wp_enqueue_script('jquery');
});

/**
 * Display single certificate page
 * @param mixed $identifier Certificate ID or code
 */
function certsystem_certificate_display_single($identifier) {
    global $wpdb;
    
    $certificate = certsystem_certificate_get($identifier);
    if (!$certificate) {
        wp_die('Certificate not found. Please check the certificate ID or code.');
    }
    
    // Use certification-template.php file directly
    $template_file = plugin_dir_path(__FILE__) . 'certification-template.php';
    
    if (!file_exists($template_file)) {
        wp_die('Certificate template file not found.');
    }
    
    // Set up variables for the template
    $certsystem = $certificate;
    $qr_code_url = certsystem_certificate_get_url($certificate->id);
    
    // Get custom settings
    $custom_html = get_option('certsystem_custom_html_snippet', '');
    $certificate_background = get_option('certsystem_certificate_background', '');
    
    // Output certificate with custom background and HTML
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Certificate - <?php echo esc_html($certificate->student_name); ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="<?php echo plugin_dir_url(__FILE__) . 'assets/css/certsystem-certificate.css'; ?>">
        <style>
            <?php if (!empty($certificate_background)): ?>
            .certsystem-container {
                background: url('<?php echo esc_url($certificate_background); ?>') center/cover !important;
            }
            <?php endif; ?>
        </style>
        <?php echo $custom_html; ?>
    </head>
    <body class="certificate-page-body">
        <button class="certificate-download-btn" id="download-certificate-png" title="Download as PNG">↓</button>
        <div class="certsystem-container">
            <?php include $template_file; ?>
        </div>
        
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var downloadBtn = document.getElementById('download-certificate-png');
            
            downloadBtn.addEventListener('click', function() {
                var certificateContainer = document.querySelector('.certsystem-container');
                
                if (certificateContainer) {
                    // Use html2canvas to capture the certificate as an image
                    html2canvas(certificateContainer, {
                        scale: 2,
                        useCORS: true,
                        logging: false
                    }).then(function(canvas) {
                        // Convert canvas to image data
                        var imgData = canvas.toDataURL('image/png');
                        
                        // Create download link
                        var link = document.createElement('a');
                        link.download = 'certificate-<?php echo esc_js($certificate->id); ?>.png';
                        link.href = imgData;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }).catch(function(error) {
                        console.error('Error capturing certificate:', error);
                        alert('Error downloading certificate. Please try again.');
                    });
                }
            });
        });
        </script>
    </body>
    </html>
    <?php
    exit;
}



// Update  templates with QR code container
function certsystem_certificate_update_templates() {
    global $wpdb;
    $templates_table_name = $wpdb->prefix . 'certsystem_certificate_templates';
    
    $templates = $wpdb->get_results("SELECT * FROM {$templates_table_name}");
    foreach ($templates as $template) {
        // Update the template HTML to use the qr-code class
        $updated_html = str_replace(
            '<div style="position: absolute; bottom: 20px; right: 20px; width: 100px; height: 100px;">',
            '<div class="qr-code">',
            $template->template_html
        );
        
        if ($updated_html !== $template->template_html) {
            $wpdb->update(
                $templates_table_name,
                array('template_html' => $updated_html),
                array('id' => $template->id)
            );
            error_log("Updated template {$template->id} with QR code class");
        }
    }
}

//  rewrite rules on plugin activation/deactivation
register_activation_hook(__FILE__, function() {
    flush_rewrite_rules();
    certsystem_certificate_update_templates();
});

register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});
