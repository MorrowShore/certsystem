/**
 * certsystem Certificate Management - Admin JavaScript
 * Handles datepickers, csmodals, DataTables, and other interactive features
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Check if jQuery is loaded
    if (typeof jQuery === 'undefined') {
        return;
    }
    
    // Check if $ is available
    if (typeof $ === 'undefined') {
        return;
    }
    
    // initialize datatable
    function initializeDataTable() {
        // wait for table to load
        setTimeout(function() {
            var $table = $('#certificates-table');
            
            // If main table not found, try alternative selectors
            if ($table.length === 0) {
                $table = $('.table-wrapper table:first, table.table:first');
                if ($table.length > 0) {
                    $table.attr('id', 'certificates-table');
                }
            }
            
            if ($table.length > 0) {
                try {
                    // Destroy existing DataTable if it exists
                    if ($.fn.DataTable.isDataTable($table)) {
                        $table.DataTable().destroy();
                    }
                    
                    // Initialize DataTable with custom options (without Bootstrap)
                    $table.DataTable({
                        "pageLength": 10,
                        "lengthChange": true,
                        "searching": true,
                        "ordering": true,
                        "info": true,
                        "autoWidth": false,
                        "processing": true,
                        "language": {
                            "search": "Search certificates:",
                            "lengthMenu": "Show _MENU_ certificates per page",
                            "info": "Showing _START_ to _END_ of _TOTAL_ certificates",
                            "infoEmpty": "No certificates found",
                            "infoFiltered": "(filtered from _MAX_ total certificates)",
                            "emptyTable": "No certificate data available",
                            "zeroRecords": "No matching certificates found",
                            "processing": "Loading certificates..."
                        },
                        "columnDefs": [
                            {
                                "targets": 0, // First column (checkbox)
                                "orderable": false,
                                "searchable": false,
                                "width": "40px"
                            },
                            {
                                "targets": -1, // Last column (actions)
                                "orderable": false,
                                "searchable": false,
                                "width": "100px"
                            }
                        ],
                        "order": [[1, 'asc']], // Sort by candidate name by default
                        "initComplete": function() {
                            console.log('DataTable initialized successfully');
                            // Re-bind checkbox events after DataTable initialization
                            bindCheckboxEvents();
                        }
                    });
                } catch (error) {
                    console.error('DataTable initialization failed:', error);
                    // Fallback: at least make table sortable manually
                    addFallbackTableFeatures($table);
                }
            } else {
                console.warn('No table found for DataTable initialization');
            }
        }, 100);
    }
    
    // Fallback function for basic table functionality if DataTable fails
    function addFallbackTableFeatures($table) {
        console.log('Adding fallback table features');
        $table.addClass('sortable-fallback');
        // Add basic search functionality
        if ($('.dataTables_filter').length === 0) {
            var searchBox = '<div class="table-search mb-3"><input type="text" class="form-control" placeholder="Search certificates..." id="table-search-fallback"></div>';
            $table.before(searchBox);
            
            $('#table-search-fallback').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $table.find('tbody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });
        }
    }
    
    // Bind checkbox events
    function bindCheckboxEvents() {
        // Unbind previous events to avoid duplicates
        $(document).off('change.certsystem', '#selectAll');
        $(document).off('change.certsystem', 'table tbody input[type="checkbox"]');
        
        // Select all checkbox handler
        $(document).on('change.certsystem', '#selectAll', function() {
            var checkbox = $('table tbody input[type="checkbox"]');
            if (this.checked) {
                checkbox.each(function() {
                    this.checked = true;
                });
            } else {
                checkbox.each(function() {
                    this.checked = false;
                });
            }
        });
        
        // Individual checkbox handler
        $(document).on('change.certsystem', 'table tbody input[type="checkbox"]', function() {
            if (!this.checked) {
                $('#selectAll').prop('checked', false);
            } else {
                // Check if all checkboxes are selected to update "Select All"
                var totalCheckboxes = $('table tbody input[type="checkbox"]').length;
                var checkedCheckboxes = $('table tbody input[type="checkbox"]:checked').length;
                $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
            }
        });
    }
      // Initialize datepickers with improved positioning and styling
    function initializeDatepicker(selector, altField) {
        if ($(selector).length) {
            $(selector).datepicker({
                dateFormat: 'dd/m/yy',
                changeMonth: true,
                changeYear: true,
                yearRange: '1940:' + new Date().getFullYear(),
                altField: altField,
                altFormat: 'mm/dd/yy',
                showAnim: 'fadeIn',
                showButtonPanel: false,
                showOtherMonths: true,
                selectOtherMonths: true,
                beforeShow: function(input, inst) {
                    var $input = $(input);
                    var isIncsmodal = $input.closest('.csmodal').length > 0;
                    
                    // Set high z-index for csmodal context
                    if (isIncsmodal) {
                        inst.dpDiv.addClass('ui-datepicker-csmodal');
                    }
                    
                    // Use setTimeout to ensure DOM is ready
                    setTimeout(function() {
                        var $datepicker = inst.dpDiv;
                        
                        // Apply base styles
                        $datepicker.css({
                            'z-index': isIncsmodal ? 100000 : 9999,
                            'position': isIncsmodal ? 'fixed' : 'absolute'
                        });
                        
                        // Position appropriately if in csmodal
                        if (isIncsmodal) {
                            var inputOffset = $input.offset();
                            var inputHeight = $input.outerHeight();
                            var datepickerHeight = $datepicker.outerHeight();
                            var windowHeight = $(window).height();
                            var scrollTop = $(window).scrollTop();
                            
                            // Calculate available space
                            var spaceAbove = inputOffset.top - scrollTop;
                            var spaceBelow = windowHeight - (inputOffset.top - scrollTop + inputHeight);
                            
                            var top, left;
                            
                            // Position above if there's more space or if below would be cut off
                            if (spaceAbove > datepickerHeight + 10 || spaceAbove > spaceBelow) {
                                top = inputOffset.top - datepickerHeight - 5;
                            } else {
                                top = inputOffset.top + inputHeight + 5;
                            }
                            
                            left = inputOffset.left;
                            
                            // Ensure datepicker stays within window bounds
                            var datepickerWidth = $datepicker.outerWidth();
                            var windowWidth = $(window).width();
                            if (left + datepickerWidth > windowWidth) {
                                left = windowWidth - datepickerWidth - 10;
                            }
                            if (left < 0) {
                                left = 10;
                            }
                            
                            $datepicker.css({
                                'top': top + 'px',
                                'left': left + 'px'
                            });
                        }
                    }, 1);
                },
                onClose: function(selectedDate) {
                    // Remove csmodal class when closing
                    $(this).datepicker('widget').removeClass('ui-datepicker-csmodal');
                }
            });
        }
    }
    // Initialize all components
    function initializeComponents() {
        // Initialize DataTable
        initializeDataTable();
        
    // Initialize all datepickers
    initializeDatepicker('#doc', '#adoc');
    initializeDatepicker('#editdoc', '#eeditdoc');
        
        // Bind checkbox events initially
        bindCheckboxEvents();
        
        // Check if modal elements exist
        var addModal = document.getElementById('addcsmodal');
        var editModal = document.getElementById('editcsmodal');
        var deleteModal = document.getElementById('deletecsmodal');
        
        console.log('Modal elements found:', {
            addModal: !!addModal,
            editModal: !!editModal,
            deleteModal: !!deleteModal
        });
        
        console.log('All components initialized');
    }
    
    // Add new certificate csmodal handler
    $(document).on('click', '[data-toggle="csmodal"], [data-toggle="modal"]', function(e) {
        e.preventDefault();
        
        var target = $(this).attr('href') || $(this).data('target');
        
        // Skip invalid targets like javascript:void(0)
        if (target && target.startsWith('#') && target !== '#') {
            var modalId = target.substring(1);
            var modal = $('#' + modalId);
            
            if (modal.length) {
                // If this is the add modal, prefill date with today's date
                if (modalId === 'addcsmodal') {
                    var today = new Date();
                    var formattedDate = today.getDate() + '/' + (today.getMonth() + 1) + '/' + today.getFullYear();
                    $('#doc').val(formattedDate);
                    $('#adoc').val(today.getMonth() + 1 + '/' + today.getDate() + '/' + today.getFullYear());
                }
                // Show modal with proper CSS classes
                modal.css({
                    'display': 'flex',
                    'z-index': '100000'
                });
                
                // Add show class for CSS transitions
                setTimeout(function() {
                    modal.addClass('show');
                }, 10);
            }
        }
    });

    // Edit csmodal handler
    $(document).on('click', '.editcsmodal', function(e) {
        e.preventDefault();
        
        var id = $(this).data('id');
        var $row = $(this).closest('tr');
        var sname = $('.sname', $row).html();
        var cname = $('.cname', $row).html();
        var ccode = $('.ccode', $row).html();
        var chour = $('.chour', $row).html();
        var cadt = $('.cadt', $row).html();
        var ocadt = $('.cadt', $row).attr('date');
        
        // Populate edit csmodal
        $('#editcsmodal input[name=editid]').val(id);
        $('#editcsmodal input[name=std_name]').val(sname);
        $('#editcsmodal input[name=course_name]').val(cname);
        $('#editcsmodal input[name=course_hours]').val(chour);
        $('#editcsmodal input[name=certificate_id]').val(ccode).prop('readonly', true);
        $('#editcsmodal input[name=doc]').val(ocadt);
        $('#editcsmodal #editdoc').val(cadt);
        
        // Show csmodal with proper CSS classes
        var modal = $('#editcsmodal');
        if (modal.length) {
            modal.css({
                'display': 'flex',
                'z-index': '100000'
            });
            setTimeout(function() {
                modal.addClass('show');
            }, 10);
        }
    });
    
    // Delete csmodal handler
    $(document).on('click', '.deletecsmodal', function(e) {
        e.preventDefault();
        
        var id = $(this).data('id');
        $('#deletecsmodal input[name=editid]').val(id);
        
        // Show delete modal with proper CSS classes
        var modal = $('#deletecsmodal');
        if (modal.length) {
            modal.css({
                'display': 'flex',
                'z-index': '100000'
            });
            setTimeout(function() {
                modal.addClass('show');
            }, 10);
        }
    });
    
    // Delete multiple handler
    $(document).on('click', '.deleteMultiple', function() {
        var allIds = [];
        $('.checkedcert:checkbox:checked').each(function() {
            allIds.push($(this).val());
        });
        
        if (allIds.length === 0) {
            alert('Please select at least one certificate to delete.');
            return;
        }
        
        $('#deletecsmodal input[name=editid]').val(allIds.join(','));
        
        // Show delete modal with proper CSS classes
        var modal = $('#deletecsmodal');
        if (modal.length) {
            modal.css({
                'display': 'flex',
                'z-index': '100000'
            });
            setTimeout(function() {
                modal.addClass('show');
            }, 10);
        }
    });
    
    // Auto-hide alerts
    setTimeout(function() {
        $('.hide-alert').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 5000);
    
    // Basic csmodal close functionality
    $(document).on('click', '[data-dismiss="csmodal"], .alert-close', function() {
        var csmodal = $(this).closest('.csmodal');
        if (csmodal.length) {
            csmodal.removeClass('show');
            setTimeout(function() {
                csmodal.hide();
            }, 300);
        } else {
            $(this).closest('.certsystem-alert').hide();
        }
    });
    
    // Close csmodal when clicking outside
    $(document).on('click', function(e) {
        if ($(e.target).hasClass('csmodal')) {
            var csmodal = $(e.target);
            csmodal.removeClass('show');
            setTimeout(function() {
                csmodal.hide();
            }, 300);
        }
    });
    
    // Initialize everything when DOM is ready
    initializeComponents();
    
    // Re-initialize DataTable after AJAX requests or dynamic content changes
    $(document).on('DOMNodeInserted', function(e) {
        if ($(e.target).hasClass('table-wrapper') || $(e.target).find('table').length) {
            setTimeout(initializeDataTable, 100);
        }
    });
    
    // Certificate popup functionality
    $(document).on('click', '.view-certificate-popup', function(e) {
        e.preventDefault();
        var certificateId = $(this).data('certificate-id');
        var certificateSlug = 'certificate'; // Default slug, can be made dynamic if needed
        
        // Create iframe overlay using existing infrastructure
        var overlayHtml = '<div id="certsystem-iframe-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; display: flex; justify-content: center; align-items: center;">' +
            '<div style="position: relative; width: 95%; height: 95%; border-radius: 0px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.5);">' +
            '<div id="certsystem-iframe-controls" style="position: absolute; top: 10px; right: 10px; z-index: 10000;">' +
            '<button id="certsystem-close-iframe" title="Close" style="background: #dc3232; color: white; border: none; padding: 8px 12px; border-radius: 3px; cursor: pointer;">×</button>' +
            '</div>' +
            '<iframe id="certsystem-certificate-iframe" src="' + window.location.origin + '/' + certificateSlug + '/?id=' + certificateId + '&display=certificate" style="width: 100%; height: 100%; border: none;"></iframe>' +
            '</div>' +
            '</div>';
        
        // Remove any existing overlay
        $('#certsystem-iframe-overlay').remove();
        
        // Add new overlay
        $('body').append(overlayHtml);
        
        // Close button functionality
        $('#certsystem-close-iframe').on('click', function() {
            $('#certsystem-iframe-overlay').remove();
        });
        
        // Close on overlay click (outside iframe)
        $('#certsystem-iframe-overlay').on('click', function(e) {
            if (e.target === this) {
                $(this).remove();
            }
        });
        
        // Escape key to close
        $(document).on('keydown.certsystem-popup', function(e) {
            if (e.key === 'Escape') {
                $('#certsystem-iframe-overlay').remove();
                $(document).off('keydown.certsystem-popup');
            }
        });
    });
});
