<?php
/**
 * Settings Page Interface for Certificate System: Independent Certification
 * 
 * @package Certificate_System
 * @author Morrow Shore
 * @link https://morrowshore.com
 */

// Prevent direct access to this file for security
if (! defined( 'ABSPATH' )) {
	exit;
}

// main admin interface for certificate management
function certsystem_certificate_admin_certificate_ui() {

	// only administrators can access
	if ( ! current_user_can( 'manage_options' ) ) return;
	$error = "";
	
	// handle form submissions
	if( isset($_POST['add_certificate']) ) {
		// verify nonce for security
		if( ! isset( $_POST['course_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['course_nonce'] ) ), 'admin_certificate_ui' ) ) {
			echo wp_kses_post('<div class="certsystem-alert certsystem-alert-danger">Try Again Verification Failed!!</div>');
		} else if( isset($_POST['add_certificate']) && $_POST['add_certificate'] == "Delete" ) {
			// handle certificate deletion
			if ( ! isset($_POST['editid']) ) {
				$error = '<div class="certsystem-alert certsystem-alert-danger hide-alert">No certificate ID provided for deletion!<button type="button" class="alert-close" aria-label="Close">×</button></div>';
			} else {
				$editid = sanitize_text_field( wp_unslash( $_POST['editid'] ) );
				if (strpos($editid, ',') !== false) {
					$editid = explode(",", $editid);
					foreach( $editid as $edt ) {
						$result = certsystem_certificate_delete_course_certificate( $edt );
					}
				} else {
					$result = certsystem_certificate_delete_course_certificate($editid);
				}
				if( $result == 1 ) {
	                $error = '<div class="certsystem-alert certsystem-alert-success hide-alert">Certificate deleted successfully!<button type="button" class="alert-close" aria-label="Close">×</button></div>';
	            } else {
	                $error = '<div class="certsystem-alert certsystem-alert-danger hide-alert">Error while deleting!<button type="button" class="alert-close" aria-label="Close">×</button></div>';
	            }
			}
		} else if( empty($_POST['std_name']) || empty($_POST['course_name']) || empty($_POST['doc']) ) {
			$error = '<div class="certsystem-alert certsystem-alert-danger hide-alert">Student name, course name, and date of completion are required!<button type="button" class="alert-close" aria-label="Close">×</button></div>';
		} else {
			$code = isset($_POST['certificate_id']) ? trim(sanitize_text_field( wp_unslash( $_POST['certificate_id'] ) )) : '';
			error_log("Form certificate_id: '" . $_POST['certificate_id'] . "'");
			error_log("Processed code: '" . $code . "'");
			$name = sanitize_text_field( wp_unslash( $_POST['std_name'] ) );
			$course = sanitize_text_field( wp_unslash( $_POST['course_name'] ) );
			$hours = sanitize_text_field( wp_unslash( $_POST['course_hours'] ) );
			$doc = sanitize_text_field( wp_unslash( $_POST['doc'] ) );
			$editid = isset($_POST['editid']) ? sanitize_text_field( wp_unslash( $_POST['editid'] ) ) : '';
			$result = certsystem_certificate_add_course_certificate($code, $name, $course, $hours, $doc, $editid);
			if( $result == 1 ) {
				if( $editid != "" ) {
	                $error = '<div class="certsystem-alert certsystem-alert-success hide-alert">Certificate updated successfully!<button type="button" class="alert-close" aria-label="Close">×</button></div>';
				} else {
                  $error = '<div class="certsystem-alert certsystem-alert-success hide-alert">Certificate added successfully!<button type="button" class="alert-close" aria-label="Close">×</button></div>';
				}
            } else {
                  $error = '<div class="certsystem-alert certsystem-alert-danger hide-alert">Submission failed!<button type="button" class="alert-close" aria-label="Close">×</button></div>';
            }
		}
	} else if( isset($_POST['bulk_upload']) && isset($_FILES['bulk_certificate_csv']) && !empty($_FILES['bulk_certificate_csv']['tmp_name']) ) {
        if( ! isset( $_POST['course_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['course_nonce'] ) ), 'admin_certificate_ui' ) ) {
            echo wp_kses_post('<div class="alert alert-danger">Bulk Upload: Verification Failed!</div>');        } else {
            // Sanitize the uploaded file path
            $csvFile = sanitize_text_field( $_FILES['bulk_certificate_csv']['tmp_name'] );
            
            // Initialize WordPress filesystem
            global $wp_filesystem;
            if (empty($wp_filesystem)) {
                require_once ABSPATH . '/wp-admin/includes/file.php';
                WP_Filesystem();
            }
            
            // Read CSV file using WP_Filesystem
            $csv_content = $wp_filesystem->get_contents($csvFile);
            if ($csv_content !== false) {
                $lines = explode("\n", $csv_content);
                $row = 0;
                $uploaded_count = 0;
                
                foreach ($lines as $line) {
                    if (empty(trim($line))) continue;
                    $data = str_getcsv($line);
                    
                    // Skip header row if present
                    if ($row == 0 && (strtolower($data[0]) == 'certificate_id' || strtolower($data[0]) == 'student_name')) { 
                        $row++; 
                        continue; 
                    }
                    // Ensure we have at least 5 columns: certificate_id, student_name, course_name, course_hours (optional), date_of_completion
                    if (count($data) < 5) continue;
                      $certificate_id = sanitize_text_field($data[0]);
                    $name = sanitize_text_field($data[1]);
                    $course = sanitize_text_field($data[2]);
                    $hours = sanitize_text_field($data[3]);
                    $doc = sanitize_text_field($data[4]);
                      if (!empty($name) && !empty($course)) {
                        certsystem_certificate_add_course_certificate($certificate_id, $name, $course, $hours, $doc, '');
                        $uploaded_count++;                    }
                    $row++;
                }
                $error = '<div class="certsystem-alert certsystem-alert-success hide-alert">Bulk upload completed! ' . esc_html($uploaded_count) . ' certificates uploaded successfully.<button type="button" class="alert-close" aria-label="Close">×</button></div>';
            } else {
                $error = '<div class="certsystem-alert certsystem-alert-danger hide-alert">Bulk upload failed to read file!<button type="button" class="alert-close" aria-label="Close">×</button></div>';
            }
        }    }
		// get certificates
	$certificates = certsystem_certificate_get_all_certificates();    // Safely get 'pg' from $_GET with nonce verification for admin pagination
    $cpage = 1;
    if (isset($_GET['pg']) && is_numeric($_GET['pg']) && isset($_GET['pg_nonce']) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['pg_nonce'] ) ), 'pagination_nonce')) {
        $cpage = intval($_GET['pg']);
    } elseif (isset($_GET['pg']) && is_numeric($_GET['pg']) && !isset($_GET['pg_nonce'])) {
        // Allow first page load without nonce for initial access
        $cpage = intval($_GET['pg']);
    }
    $cpage_offset = $cpage > 0 ? (($cpage-1)*10) : 0;
    $certificatesNew = array_slice($certificates, $cpage_offset, 10, true);
    ?>

	<div class="cs-container">
	  <div class="table-wrapper">
	    <div class="table-title">
        <div class="table-title-header">
          <div class="table-title-left">
            <h2>Manage <b>Certificates</b></h2>
          </div>
          <div class="cs-button-container">
            <a href="#addcsmodal" class="cs-button cs-button-success" data-toggle="csmodal"><i class="material-icons">&#xE147;</i> <span>Add Certificate</span></a>
            <a href="javascript:void(0);" class="cs-button cs-button-danger deleteMultiple" data-toggle="csmodal"><i class="material-icons">&#xE15C;</i> <span>Delete</span></a>
            <a href="#bulkUploadcsmodal" class="cs-button cs-button-primary" data-toggle="csmodal"><i class="material-icons">&#xE2C6;</i> <span>Upload</span></a>
          </div>
        </div>
	    </div>
	    <?php echo wp_kses_post($error);?>
		<div class="alert alert-info" role="alert">
		  	Copy and paste the shortcode <strong>[certsystem]</strong> on a page to add the system page. We recommend a page with the /certificate slug.
		</div>
	    <table id="certificates-table" class="certsystem-table">
	      <thead>
	        <tr>
	          <th>
	            <span class="custom-checkbox">
					<input type="checkbox" id="selectAll">
					<label for="selectAll"></label>
				</span>
	          </th>
	          <th>Name</th>
	          <th>Course or Project</th>
	          <th>Role</th>
	          <th>Certificate ID</th>
	          <th>Date of Completion</th>
	          <th>Actions</th>
	        </tr>
	      </thead>
	      <tbody>
	        <?php foreach ($certificatesNew as $value) { ?>
	    	 	<tr>
	    	 		<td>
			            <span class="custom-checkbox">
						<input type="checkbox" id="checkbox<?php echo esc_attr($value->id);?>" value="<?php echo esc_attr($value->id);?>" class="checkedcert">
						<label for="checkbox<?php echo esc_attr($value->id);?>"></label>
						</span>
			        </td>	                <td class="sname"><?php echo esc_html($value->student_name); ?></td>
	                <td class="cname"><?php echo esc_html($value->course_name); ?></td>
	                <td class="chour"><?php echo esc_html($value->course_hours); ?></td>
					<td class="ccode"><?php echo esc_html($value->id); ?></td>

                <td class="cadt" date="<?php echo esc_attr($value->dob); ?>"><?php echo esc_html(gmdate("d/m/Y", strtotime($value->dob))); ?></td>			        <td>
			        	<div class="actions">
                           <a href="<?php echo esc_url(certsystem_certificate_get_url($value->id)); ?>" target="_blank" class="view" data-bs-toggle="tooltip" title="View Certificate">
                               <i class="material-icons">&#xE417;</i>
                           </a>
						   <a href="javascript:void();" class="edit editcsmodal" data-id="<?php echo esc_attr($value->id);?>"><i class="material-icons" data-bs-toggle="tooltip" title="Edit">&#xE254;</i></a>
						   <a href="javascript:void(0);" class="delete deletecsmodal" data-id="<?php echo esc_attr($value->id);?>"><i class="material-icons" data-bs-toggle="tooltip" title="Delete">&#xE872;</i></a>
			        	</div>
			        </td>
	            </tr>
	        <?php } ?>
	      </tbody>
	    </table>
	    <div class="clearfix">
	    	<?php if( count($certificates) > 0 ) { ?>
		      <ul class="pagination">
		        <?php
		        $pages = ceil(count($certificates)/10);
		        $currentpage = isset($_GET['pg']) && is_numeric($_GET['pg']) ? intval($_GET['pg']) : 1;
		        
		        // Previous button
		        if ($currentpage > 1) {
		            $prev_url = wp_nonce_url(admin_url('admin.php?page=certsystem-certificate-management&pg='.($currentpage-1)), 'pagination_nonce', 'pg_nonce');
		            echo '<li class="page-item"><a href="'.esc_url($prev_url).'" class="page-link" aria-label="Previous">&lt;</a></li>';
		        } else {
		            echo '<li class="page-item disabled"><span class="page-link">&lt;</span></li>';
		        }
		        
		        // Page numbers with smart truncation
		        $max_visible_pages = 5;
		        $start_page = max(1, $currentpage - floor($max_visible_pages/2));
		        $end_page = min($pages, $start_page + $max_visible_pages - 1);
		        
		        if ($start_page > 1) {
		            $first_url = wp_nonce_url(admin_url('admin.php?page=certsystem-certificate-management&pg=1'), 'pagination_nonce', 'pg_nonce');
		            echo '<li class="page-item"><a href="'.esc_url($first_url).'" class="page-link">1</a></li>';
		            if ($start_page > 2) {
		                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
		            }
		        }
		        
		        for($i = $start_page; $i <= $end_page; $i++) { 
		            $page_url = wp_nonce_url(admin_url('admin.php?page=certsystem-certificate-management&pg='.$i), 'pagination_nonce', 'pg_nonce');
		            echo '<li class="page-item '.esc_attr(($currentpage==$i) ? 'active' : '').'"><a href="'.esc_url($page_url).'" class="page-link">'.esc_html($i).'</a></li>';
		        }
		        
		        if ($end_page < $pages) {
		            if ($end_page < $pages - 1) {
		                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
		            }
		            $last_url = wp_nonce_url(admin_url('admin.php?page=certsystem-certificate-management&pg='.$pages), 'pagination_nonce', 'pg_nonce');
		            echo '<li class="page-item"><a href="'.esc_url($last_url).'" class="page-link">'.esc_html($pages).'</a></li>';
		        }
		        
		        // Next button
		        if ($currentpage < $pages) {
		            $next_url = wp_nonce_url(admin_url('admin.php?page=certsystem-certificate-management&pg='.($currentpage+1)), 'pagination_nonce', 'pg_nonce');
		            echo '<li class="page-item"><a href="'.esc_url($next_url).'" class="page-link" aria-label="Next">&gt;</a></li>';
		        } else {
		            echo '<li class="page-item disabled"><span class="page-link">&gt;</span></li>';
		        }
		        ?>
		      </ul>
	    	<?php } ?>
	    </div>
	  </div>
	</div>
	<!-- Edit csmodal HTML -->
	<div id="addcsmodal" class="csmodal">
	  <div class="csmodal-dialog">
	    <div class="csmodal-content">
		<form method="POST" style="margin-top: 40px;">
			<?php wp_nonce_field( 'admin_certificate_ui', 'course_nonce' );?>
	        <div class="csmodal-header">
	          <h4 class="csmodal-title">Add Certificate</h4>
            <button type="button" class="alert-close" data-dismiss="csmodal" aria-label="Close">×</button>
	        </div>
	        <div class="csmodal-body">
	          <div class="form-group">
	            <label>Candidate Name</label>
	            <input type="text" class="form-control" required name="std_name">
	          </div>
	          <div class="form-group">
				<label>Project / Course Title</label>
				<input type="text" required class="form-control" name="course_name">
	          </div>
	          <div class="form-group">
				<label>Role / Designation (Optional)</label>
				<input type="text" class="form-control" name="course_hours" placeholder="Optional field">
	          </div>
	          <div class="form-group">
				<label>Certificate ID</label>
				<input type="text" class="form-control" value="" name="certificate_id" placeholder="Leave empty to auto-generate">
				<small class="form-text text-muted">If left empty, a unique 10-character ID will be automatically generated (format: 4 random chars + YYMMDD + unique digits)</small>
	          </div>
	          <div class="form-group">
				<label>Date of Completion</label>
				<input type="text" id="doc" required class="form-control" readonly="readonly">
				<input type="hidden" id="adoc" name="doc">
			  </div>
	        </div>
	        <div class="csmodal-footer">
	          <input type="button" class="cs-button cs-button-secondary" data-dismiss="csmodal" value="Cancel">
	          <input type="submit" class="cs-button cs-button-success" value="Add" name="add_certificate">
	        </div>
	      </form>
	    </div>
	  </div>
	</div>
	<!-- Edit csmodal HTML -->
	<div id="editcsmodal" class="csmodal">
	  <div class="csmodal-dialog">
	    <div class="csmodal-content">
		<form class="mt-40" method="POST">
			<?php wp_nonce_field( 'admin_certificate_ui', 'course_nonce' );?>
	        <div class="csmodal-header">
	          <h4 class="csmodal-title">Edit Certificate</h4>
            <button type="button" class="alert-close" data-dismiss="csmodal" aria-label="Close">×</button>
	        </div>
	        <div class="csmodal-body">
	          <div class="form-group">
	            <label>Candidate Name</label>
	            <input type="text" class="form-control" required name="std_name">
	          </div>
	          <div class="form-group">
				<label>Project / Course Title</label>
				<input type="text" required class="form-control" name="course_name">
	          </div>
	          <div class="form-group">
				<label>Role / Designation</label>
				<input type="text" required class="form-control" name="course_hours">
	          </div>
	          <div class="form-group">
				<label>Certificate ID</label>
				<input type="text" class="form-control" value="" name="certificate_id" placeholder="Certificate ID (cannot be changed)" readonly>
				<small class="form-text text-muted">Certificate ID cannot be modified once created</small>
	          </div>
	          <div class="form-group">
				<label>Date of Completion</label>
				<input type="text" id="editdoc" required class="form-control" readonly="readonly">
				<input type="hidden" id="eeditdoc" name="doc">
			  </div>
	        </div>
	        <div class="csmodal-footer">
				<input type="hidden" name="editid" value="">
	        	<input type="button" class="cs-button cs-button-secondary" data-dismiss="csmodal" value="Cancel">
	        	<input type="submit" class="cs-button cs-button-success" value="Update" name="add_certificate">
	        </div>
	      </form>
	    </div>
	  </div>
	</div>
	<!-- Delete csmodal HTML -->
	<div id="deletecsmodal" class="csmodal">
	  <div class="csmodal-dialog">
	    <div class="csmodal-content">
	      <form method="POST">
			<?php wp_nonce_field( 'admin_certificate_ui', 'course_nonce' );?>
	        <div class="csmodal-header">
	          <h4 class="csmodal-title">Delete Certificate</h4>
            <button type="button" class="alert-close" data-dismiss="csmodal" aria-label="Close">×</button>
	        </div>
	        <div class="csmodal-body">
	          <p>Are you sure you want to delete these certificates?</p>
	          <p class="text-warning"><small>This action cannot be undone.</small></p>
	        </div>
	        <div class="csmodal-footer">
	          <input type="hidden" name="editid" value="">
	          <input type="button" class="cs-button cs-button-secondary" data-dismiss="csmodal" value="Cancel">
	          <input type="submit" class="cs-button cs-button-danger" value="Delete" name="add_certificate">
	        </div>
	      </form>
	    </div>
	  </div>
	</div>
	<!-- Bulk Upload csmodal HTML -->
	<div id="bulkUploadcsmodal" class="csmodal">
	  <div class="csmodal-dialog">
	    <div class="csmodal-content">
	      <form class="mt-40" method="POST" enctype="multipart/form-data">
	        <?php wp_nonce_field( 'admin_certificate_ui', 'course_nonce' );?>
	        <div class="csmodal-header">
	          <h4 class="csmodal-title">Bulk Upload Certificates</h4>
            <button type="button" class="alert-close" data-dismiss="csmodal" aria-label="Close">×</button>
	        </div>        <div class="csmodal-body">
	          <div class="form-group">
	            <label>Upload CSV File</label>
            <small class="form-text text-muted">
            	<strong>CSV Format:</strong> certificate_id, student_name, project_course_title, role_designation, date_of_completion<br>
            	<strong>Example:</strong> a1b2250910, John Doe, Web Development Project, Senior Developer, 12/25/2023<br>
            	<em>Note: Date format should be MM/DD/YYYY (e.g., 12/25/2023). Leave certificate_id empty to auto-generate.</em>
            </small>
	          </div>
	        </div>
	        <div class="csmodal-footer">
            <input type="button" class="cs-button cs-button-secondary" data-dismiss="csmodal" value="Cancel">
	          <input type="submit" class="cs-button cs-button-success" value="Upload" name="bulk_upload">
	        </div>
	      </form>
	    </div>
	  </div>
	</div>

	<?php
}

// Remove duplicate enqueues for material-icons, certsystem-admin-inline, and inline JS/CSS
// All admin assets are now enqueued from the main plugin file for consistency


/**
 * Settings Page for Certificate System: Independent Certification
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
 * Settings page for Certificate System plugin
 */
function certsystem_certificate_settings_ui() {
    // Security check - only administrators can access this page
    if ( ! current_user_can( 'manage_options' ) ) return;
    
    global $wpdb;
    $templates_table = $wpdb->prefix . 'certsystem_certificate_templates';
    $error = "";
    $success = "";
    
    // Handle template operations
    if (isset($_POST['save_template'])) {
        if (!isset($_POST['template_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['template_nonce'])), 'template_builder')) {
            $error = '<div class="alert alert-danger">Security verification failed!</div>';
        } else {
            $template_name = sanitize_text_field(wp_unslash($_POST['template_name']));
            $template_html = wp_kses_post(wp_unslash($_POST['template_html']));
            $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
            
            if (empty($template_name) || empty($template_html)) {
                $error = '<div class="alert alert-danger">Template name and content are required!</div>';
            } else {
                // Check if we're editing the default template with a different name
                $is_editing_default = false;
                $original_template = null;
                
                if ($template_id > 0) {
                    $original_template = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$templates_table} WHERE id = %d", $template_id));
                    $is_editing_default = $original_template && $original_template->is_default;
                }
                
                // If editing default template with a different name, create a new template instead
                if ($is_editing_default && $template_name !== $original_template->template_name) {
                    // Insert new template instead of updating the default
                    $result = $wpdb->insert($templates_table, array(
                        'template_name' => $template_name,
                        'template_html' => $template_html
                    ));
                    $success = '<div class="alert alert-success">New template created successfully! The default template remains unchanged.</div>';
                } else if ($template_id > 0) {
                    // Update existing template (but never update the default template's content)
                    if ($is_editing_default) {
                        // For default template, only allow name changes if it's the same name
                        if ($template_name === $original_template->template_name) {
                            $result = $wpdb->update($templates_table, array(
                                'template_html' => $template_html,
                                'updated_at' => current_time('mysql')
                            ), array('id' => $template_id));
                        } else {
                            // Create new template if name is different
                            $result = $wpdb->insert($templates_table, array(
                                'template_name' => $template_name,
                                'template_html' => $template_html
                            ));
                            $success = '<div class="alert alert-success">New template created successfully! The default template remains unchanged.</div>';
                        }
                    } else {
                        // Regular template update
                        $result = $wpdb->update($templates_table, array(
                            'template_name' => $template_name,
                            'template_html' => $template_html,
                            'updated_at' => current_time('mysql')
                        ), array('id' => $template_id));
                    }
                } else {
                    // Insert new template
                    $result = $wpdb->insert($templates_table, array(
                        'template_name' => $template_name,
                        'template_html' => $template_html
                    ));
                }
                
                if ($result !== false && !isset($success)) {
                    $success = '<div class="alert alert-success">Template saved successfully!</div>';
                } else if ($result === false && !isset($success)) {
                    $error = '<div class="alert alert-danger">Error saving template!</div>';
                }
            }
        }
    }
    
    // Handle template deletion
    if (isset($_GET['delete_template'])) {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'delete_template')) {
            $error = '<div class="alert alert-danger">Security verification failed!</div>';
        } else {
            $template_id = intval($_GET['delete_template']);
            
            // Check if this is the default template
            $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$templates_table} WHERE id = %d", $template_id));
            
            if ($template && $template->is_default) {
                $error = '<div class="alert alert-danger">Cannot delete the default template!</div>';
            } else {
                $result = $wpdb->delete($templates_table, array('id' => $template_id));
                
                if ($result !== false) {
                    $success = '<div class="alert alert-success">Template deleted successfully!</div>';
                } else {
                    $error = '<div class="alert alert-danger">Error deleting template!</div>';
                }
            }
        }
    }
    
    // Handle set as default
    if (isset($_GET['set_default'])) {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'set_default')) {
            $error = '<div class="alert alert-danger">Security verification failed!</div>';
        } else {
            $template_id = intval($_GET['set_default']);
            
            // First, unset all defaults
            $wpdb->update($templates_table, array('is_default' => 0), array('is_default' => 1));
            
            // Set the new default
            $result = $wpdb->update($templates_table, array('is_default' => 1), array('id' => $template_id));
            
            if ($result !== false) {
                $success = '<div class="alert alert-success">Default template set successfully!</div>';
            } else {
                $error = '<div class="alert alert-danger">Error setting default template!</div>';
            }
        }
    }
    
    // Handle settings form submission
    if (isset($_POST['save_settings'])) {
        if (!isset($_POST['settings_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['settings_nonce'])), 'certsystem_settings')) {
            $error = '<div class="alert alert-danger">Security verification failed!</div>';
        } else {
            // Save custom HTML snippet
            $custom_html = wp_kses_post(wp_unslash($_POST['custom_html_snippet']));
            update_option('certsystem_custom_html_snippet', $custom_html);
            
            // Save certificate page slug
            $certificate_slug = sanitize_title(wp_unslash($_POST['certificate_slug']));
            update_option('certsystem_certificate_slug', $certificate_slug);
            
            // Save certificate background image URL
            $certificate_background = esc_url_raw(wp_unslash($_POST['certificate_background']));
            update_option('certsystem_certificate_background', $certificate_background);
            
            // Save custom templates toggle
            $use_custom_templates = isset($_POST['use_custom_templates']) ? true : false;
            update_option('certsystem_use_custom_templates', $use_custom_templates);
            
            $success = '<div class="alert alert-success">Settings saved successfully!</div>';
        }
    }
    
    // Get all templates
    $templates = $wpdb->get_results("SELECT * FROM {$templates_table} ORDER BY is_default DESC, template_name ASC");
    
    // Get template for editing if specified
    $edit_template = null;
    if (isset($_GET['edit_template'])) {
        $template_id = intval($_GET['edit_template']);
        $edit_template = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$templates_table} WHERE id = %d", $template_id));
    }
    
    // Get current settings
    $custom_html = get_option('certsystem_custom_html_snippet', '');
    $certificate_slug = get_option('certsystem_certificate_slug', 'certificate');
    $certificate_background = get_option('certsystem_certificate_background', '');
    
    ?>
    <div class="wrap">
        <h1>certsystem Settings</h1>
        
        <?php if ($error) echo $error; ?>
        <?php if ($success) echo $success; ?>
        
        <div class="certsystem-settings-grid">
            <div class="certsystem-settings-column">
                <div class="certsystem-card">
                    <div class="certsystem-card-header">
                        <h2>General Settings</h2>
                    </div>
                    <div class="certsystem-card-body">
                        <form method="post">
                            <?php wp_nonce_field('certsystem_settings', 'settings_nonce'); ?>
                            
                            <div class="certsystem-form-group">
                                <label for="use_custom_templates">
                                    <input type="checkbox" id="use_custom_templates" name="use_custom_templates" value="1" 
                                           <?php checked(get_option('certsystem_use_custom_templates', false)); ?>>
                                    Use Custom Templates
                                </label>
                                <div class="certsystem-form-text">
                                    When enabled, certificates will use templates from the database instead of the default hardcoded design.
                                    <strong>Warning:</strong> Enable only after creating at least one template.
                                </div>
                            </div>
                            
                            <div class="certsystem-form-group">
                                <label for="certificate_slug">Certificate Page Slug</label>
                                <input type="text" class="certsystem-form-control" id="certificate_slug" name="certificate_slug" 
                                       value="<?php echo esc_attr($certificate_slug); ?>" required>
                                <div class="certsystem-form-text">
                                    The URL slug for individual certificate pages (e.g., <?php echo home_url('/' . esc_attr($certificate_slug) . '/?id=123'); ?>)
                                </div>
                            </div>
                            
                            <div class="certsystem-form-group">
                                <label for="certificate_background">Certificate Background Image</label>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="text" class="certsystem-form-control" id="certificate_background" name="certificate_background" 
                                           value="<?php echo esc_attr(get_option('certsystem_certificate_background', '')); ?>" 
                                           placeholder="Select an image or enter URL" style="flex: 1;">
                                    <button type="button" class="button" id="certificate_background_button">Select Image</button>
                                    <button type="button" class="button" id="certificate_background_remove" style="<?php echo empty(get_option('certsystem_certificate_background', '')) ? 'display: none;' : ''; ?>">Remove</button>
                                </div>
                                <div class="certsystem-form-text">
                                    Select an image from the media library or enter a direct URL. Leave empty to use the default background.
                                </div>
                                <div id="certificate_background_preview" style="margin-top: 10px; <?php echo empty(get_option('certsystem_certificate_background', '')) ? 'display: none;' : ''; ?>">
                                    <?php if (!empty(get_option('certsystem_certificate_background', ''))): ?>
                                    <img src="<?php echo esc_url(get_option('certsystem_certificate_background', '')); ?>" style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; padding: 5px;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="certsystem-form-group">
                                <label for="custom_html_snippet">Custom HTML Snippet</label>
                                <textarea class="certsystem-form-control" id="custom_html_snippet" name="custom_html_snippet" rows="8"><?php 
                                    echo esc_textarea($custom_html); 
                                ?></textarea>
                                <div class="certsystem-form-text">
                                    This HTML will be included on individual certificate pages. You can add tracking codes, custom styling, etc.
                                </div>
                            </div>
                            
                            <button type="submit" name="save_settings" class="certsystem-btn certsystem-btn-primary">Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="certsystem-settings-column">
                <div class="certsystem-card">
                    <div class="certsystem-card-header">
                        <h2>Certificate Template Builder</h2>
                    </div>
                    <div class="certsystem-card-body">
                        <form method="post">
                            <?php wp_nonce_field('template_builder', 'template_nonce'); ?>
                            <?php if ($edit_template): ?>
                                <input type="hidden" name="template_id" value="<?php echo esc_attr($edit_template->id); ?>">
                            <?php endif; ?>
                            
                            <div class="certsystem-form-group">
                                <label for="template_name">Template Name</label>
                                <input type="text" class="certsystem-form-control" id="template_name" name="template_name" 
                                       value="<?php echo $edit_template ? esc_attr($edit_template->template_name) : ''; ?>" required>
                            </div>
                            
                            <div class="certsystem-form-group">
                                <label for="template_html">Template HTML</label>
                                <textarea class="certsystem-form-control" id="template_html" name="template_html" rows="15" required><?php 
                                    if ($edit_template && $edit_template->is_default) {
                                        // For default template, get HTML directly from template file
                                        require_once plugin_dir_path(__FILE__) . '../inc/core-functions.php';
                                        echo esc_textarea(certsystem_certificate_get_default_template_html());
                                    } else {
                                        echo $edit_template ? esc_textarea($edit_template->template_html) : ''; 
                                    }
                                ?></textarea>
                                <div class="certsystem-form-text">
                                    Use these placeholders: {{student_name}}, {{course_name}}, {{course_hours}}, {{completion_date}}, {{certificate_id}}, {{qr_code}}
                                    <?php if ($edit_template && $edit_template->is_default): ?>
                                    <br><strong>Note:</strong> This is the default template. Changes will be saved as a new template if you change the name.
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <button type="submit" name="save_template" class="certsystem-btn certsystem-btn-primary">Save Template</button>
                            <?php if ($edit_template): ?>
                                <a href="?page=certsystem-settings" class="certsystem-btn certsystem-btn-secondary">Cancel</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="certsystem-card" style="margin-top: 25px;">
            <div class="certsystem-card-header">
                <h2>Available Templates</h2>
            </div>
            <div class="certsystem-card-body">
                <?php if ($templates): ?>
                    <table class="certsystem-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Default</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($templates as $template): ?>
                                <tr>
                                    <td><?php echo esc_html($template->template_name); ?></td>
                                    <td>
                                        <?php if ($template->is_default): ?>
                                            <span class="certsystem-badge">Default</span>
                                        <?php else: ?>
                                            <a href="<?php echo wp_nonce_url('?page=certsystem-settings&set_default=' . $template->id, 'set_default', '_wpnonce'); ?>" 
                                               class="certsystem-btn certsystem-btn-sm certsystem-btn-secondary">Set Default</a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo gmdate('M j, Y', strtotime($template->created_at)); ?></td>
                                    <td class="certsystem-template-actions">
                                        <a href="?page=certsystem-settings&edit_template=<?php echo $template->id; ?>" 
                                           class="certsystem-btn certsystem-btn-sm certsystem-btn-primary">Edit</a>
                                        <?php if (!$template->is_default): ?>
                                        <a href="<?php echo wp_nonce_url('?page=certsystem-settings&delete_template=' . $template->id, 'delete_template', '_wpnonce'); ?>" 
                                           class="certsystem-btn certsystem-btn-sm certsystem-btn-danger" onclick="return confirm('Are you sure you want to delete this template?')">Delete</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No templates found. Create your first template above.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

// Add media uploader script
add_action('admin_footer', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'certsystem-settings') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            var certificateBackgroundFrame;
            
            // Media uploader for certificate background
            $('#certificate_background_button').click(function(e) {
                e.preventDefault();
                
                // If the media frame already exists, reopen it.
                if (certificateBackgroundFrame) {
                    certificateBackgroundFrame.open();
                    return;
                }
                
                // Create a new media frame
                certificateBackgroundFrame = wp.media({
                    title: 'Select Certificate Background Image',
                    button: {
                        text: 'Use this image'
                    },
                    multiple: false
                });
                
                // When an image is selected in the media frame...
                certificateBackgroundFrame.on('select', function() {
                    // Get media attachment details from the frame state
                    var attachment = certificateBackgroundFrame.state().get('selection').first().toJSON();
                    
                    // Set the input field value to the attachment URL
                    $('#certificate_background').val(attachment.url);
                    
                    // Show preview
                    $('#certificate_background_preview').show().html('<img src="' + attachment.url + '" style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; padding: 5px;">');
                    
                    // Show remove button
                    $('#certificate_background_remove').show();
                });
                
                // Finally, open the modal on click
                certificateBackgroundFrame.open();
            });
            
            // Remove image button
            $('#certificate_background_remove').click(function(e) {
                e.preventDefault();
                $('#certificate_background').val('');
                $('#certificate_background_preview').hide().html('');
                $(this).hide();
            });
            
            // Handle input changes to show/hide preview
            $('#certificate_background').on('input', function() {
                var url = $(this).val();
                if (url) {
                    $('#certificate_background_preview').show().html('<img src="' + url + '" style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; padding: 5px;">');
                    $('#certificate_background_remove').show();
                } else {
                    $('#certificate_background_preview').hide().html('');
                    $('#certificate_background_remove').hide();
                }
            });
        });
        </script>
        <?php
    }
});

