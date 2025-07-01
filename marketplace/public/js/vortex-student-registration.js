/**
 * JavaScript for Student Registration and Application
 *
 * @link       https://vortexartec.com
 *  * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/js
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Form selectors
        const $studentForm = $('#vortex-student-registration-form');
        const $applicationForm = $('#vortex-student-application-form');
        const $applicationStatus = $('.vortex-application-status');
        
        // Registration form handling
        if ($studentForm.length) {
            $studentForm.on('submit', function(e) {
                e.preventDefault();
                
                // Show loading state
                const $submitBtn = $(this).find('button[type="submit"]');
                const originalBtnText = $submitBtn.text();
                $submitBtn.prop('disabled', true).text('Processing...');
                
                // Gather form data
                const formData = new FormData(this);
                formData.append('action', 'vortex_register_student');
                formData.append('security', vortex_student_data.nonce);
                
                // Submit form via AJAX
                $.ajax({
                    url: vortex_student_data.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            const $formContainer = $studentForm.parent();
                            $formContainer.html('<div class="vortex-registration-success">' + 
                                '<h3>' + vortex_student_data.i18n.success_title + '</h3>' +
                                '<p>' + response.data.message + '</p>' +
                                '<div class="vortex-next-steps">' +
                                '<p>' + vortex_student_data.i18n.next_steps + '</p>' +
                                '<ol>' +
                                '<li><a href="' + response.data.dashboard_url + '">' + 
                                vortex_student_data.i18n.goto_dashboard + '</a></li>' +
                                '<li><a href="' + response.data.application_url + '">' + 
                                vortex_student_data.i18n.complete_application + '</a></li>' +
                                '</ol></div></div>');
                            
                            // Redirect if URL provided
                            if (response.data.redirect_url) {
                                setTimeout(function() {
                                    window.location.href = response.data.redirect_url;
                                }, 3000);
                            }
                        } else {
                            // Show error message
                            showFormError($studentForm, response.data.message);
                            $submitBtn.prop('disabled', false).text(originalBtnText);
                        }
                    },
                    error: function() {
                        // Show generic error message
                        showFormError($studentForm, vortex_student_data.i18n.error_message);
                        $submitBtn.prop('disabled', false).text(originalBtnText);
                    }
                });
            });
        }
        
        // Application form handling
        if ($applicationForm.length) {
            // Education level change handler
            $('#education').on('change', function() {
                const value = $(this).val();
                if (value === 'self_taught') {
                    $('.self-taught-years').show();
                    $('#self_taught_years').prop('required', true);
                } else {
                    $('.self-taught-years').hide();
                    $('#self_taught_years').prop('required', false);
                }
            }).trigger('change');
            
            // Portfolio file upload preview
            $('#portfolio_files').on('change', function() {
                const $preview = $('#portfolio_preview');
                $preview.empty();
                
                if (this.files && this.files.length > 0) {
                    const fileCount = Math.min(this.files.length, 5); // Limit preview to 5 files
                    
                    $preview.append('<p>' + fileCount + ' files selected:</p>');
                    const $fileList = $('<ul class="vortex-file-list"></ul>');
                    
                    for (let i = 0; i < fileCount; i++) {
                        const file = this.files[i];
                        const $item = $('<li class="vortex-file-item"></li>');
                        
                        // Show thumbnail for images
                        if (file.type.match('image.*')) {
                            const reader = new FileReader();
                            
                            reader.onload = function(e) {
                                $item.html('<div class="vortex-file-preview">' +
                                    '<img src="' + e.target.result + '" alt="' + file.name + '">' +
                                    '</div>' +
                                    '<div class="vortex-file-info">' +
                                    '<span class="vortex-file-name">' + file.name + '</span>' +
                                    '<span class="vortex-file-size">' + formatFileSize(file.size) + '</span>' +
                                    '</div>');
                            };
                            
                            reader.readAsDataURL(file);
                        } else {
                            // Non-image file
                            $item.html('<div class="vortex-file-preview vortex-file-icon">' +
                                '<i class="dashicons dashicons-media-document"></i>' +
                                '</div>' +
                                '<div class="vortex-file-info">' +
                                '<span class="vortex-file-name">' + file.name + '</span>' +
                                '<span class="vortex-file-size">' + formatFileSize(file.size) + '</span>' +
                                '</div>');
                        }
                        
                        $fileList.append($item);
                    }
                    
                    $preview.append($fileList);
                    
                    // Show message if more files were selected than previewed
                    if (this.files.length > fileCount) {
                        $preview.append('<p>And ' + (this.files.length - fileCount) + ' more files...</p>');
                    }
                }
            });
            
            // Application form submission
            $applicationForm.on('submit', function(e) {
                e.preventDefault();
                
                // Validate form
                if (!validateApplicationForm()) {
                    return;
                }
                
                // Show loading state
                const $submitBtn = $(this).find('button[type="submit"]');
                const originalBtnText = $submitBtn.text();
                $submitBtn.prop('disabled', true).text('Submitting Application...');
                
                // Gather form data
                const formData = new FormData(this);
                formData.append('action', 'vortex_submit_student_application');
                formData.append('security', vortex_student_data.nonce);
                
                // Submit form via AJAX
                $.ajax({
                    url: vortex_student_data.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            const $formContainer = $applicationForm.parent();
                            $formContainer.html('<div class="vortex-application-success">' + 
                                '<h3>' + vortex_student_data.i18n.application_success_title + '</h3>' +
                                '<p>' + response.data.message + '</p>' +
                                '<div class="vortex-application-confirmation">' +
                                '<p><strong>' + vortex_student_data.i18n.application_id + ':</strong> ' + 
                                response.data.application_id + '</p>' +
                                '<p><strong>' + vortex_student_data.i18n.submission_date + ':</strong> ' + 
                                response.data.submission_date + '</p>' +
                                '</div>' +
                                '<div class="vortex-next-steps">' +
                                '<p>' + vortex_student_data.i18n.what_happens_next + '</p>' +
                                '<ol>' +
                                '<li>' + vortex_student_data.i18n.application_review + '</li>' +
                                '<li>' + vortex_student_data.i18n.decision_notification + '</li>' +
                                '<li>' + vortex_student_data.i18n.enrollment_instructions + '</li>' +
                                '</ol></div></div>');
                            
                            // Redirect if URL provided
                            if (response.data.redirect_url) {
                                setTimeout(function() {
                                    window.location.href = response.data.redirect_url;
                                }, 3000);
                            }
                        } else {
                            // Show error message
                            showFormError($applicationForm, response.data.message);
                            $submitBtn.prop('disabled', false).text(originalBtnText);
                        }
                    },
                    error: function() {
                        // Show generic error message
                        showFormError($applicationForm, vortex_student_data.i18n.error_message);
                        $submitBtn.prop('disabled', false).text(originalBtnText);
                    }
                });
            });
        }
        
        // Application status check
        if ($applicationStatus.length) {
            const applicationId = $applicationStatus.data('application-id');
            if (applicationId) {
                checkApplicationStatus(applicationId);
                
                // Set up auto-refresh every 5 minutes
                setInterval(function() {
                    checkApplicationStatus(applicationId);
                }, 300000); // 5 minutes
                
                // Manual refresh button
                $('.vortex-refresh-status').on('click', function(e) {
                    e.preventDefault();
                    checkApplicationStatus(applicationId, true);
                });
            }
        }
        
        // Helper functions
        function showFormError($form, message) {
            // Remove any existing error message
            $form.find('.vortex-form-error').remove();
            
            // Add new error message
            $form.prepend('<div class="vortex-form-error">' + message + '</div>');
            
            // Scroll to error
            $('html, body').animate({
                scrollTop: $form.offset().top - 100
            }, 500);
        }
        
        function validateApplicationForm() {
            // Basic validation
            let isValid = true;
            
            // Check required fields
            $applicationForm.find('[required]').each(function() {
                if (!$(this).val()) {
                    isValid = false;
                    $(this).addClass('vortex-field-error');
                } else {
                    $(this).removeClass('vortex-field-error');
                }
            });
            
            // Portfolio files validation
            const portfolioFiles = $('#portfolio_files')[0].files;
            if (!portfolioFiles || portfolioFiles.length === 0) {
                isValid = false;
                $('#portfolio_files').addClass('vortex-field-error');
                showFormError($applicationForm, vortex_student_data.i18n.portfolio_required);
            } else if (portfolioFiles.length > 10) {
                isValid = false;
                $('#portfolio_files').addClass('vortex-field-error');
                showFormError($applicationForm, vortex_student_data.i18n.too_many_files);
            } else {
                $('#portfolio_files').removeClass('vortex-field-error');
                
                // Check file sizes
                let totalSize = 0;
                for (let i = 0; i < portfolioFiles.length; i++) {
                    totalSize += portfolioFiles[i].size;
                }
                
                // 20MB max total size
                const maxSize = 20 * 1024 * 1024;
                if (totalSize > maxSize) {
                    isValid = false;
                    $('#portfolio_files').addClass('vortex-field-error');
                    showFormError($applicationForm, vortex_student_data.i18n.files_too_large);
                }
            }
            
            if (!isValid) {
                // Scroll to top of form where the error message is shown
                $('html, body').animate({
                    scrollTop: $applicationForm.offset().top - 100
                }, 500);
            }
            
            return isValid;
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        function checkApplicationStatus(applicationId, showLoading = false) {
            const $statusContainer = $('.vortex-application-status-content');
            
            if (showLoading) {
                $statusContainer.html('<div class="vortex-status-loading">' +
                    '<div class="vortex-spinner"></div>' +
                    '<p>' + vortex_student_data.i18n.checking_status + '</p>' +
                    '</div>');
            }
            
            $.ajax({
                url: vortex_student_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_check_application_status',
                    security: vortex_student_data.nonce,
                    application_id: applicationId
                },
                success: function(response) {
                    if (response.success) {
                        updateStatusDisplay(response.data);
                    } else {
                        $statusContainer.html('<div class="vortex-status-error">' +
                            '<p>' + response.data.message + '</p>' +
                            '</div>');
                    }
                },
                error: function() {
                    $statusContainer.html('<div class="vortex-status-error">' +
                        '<p>' + vortex_student_data.i18n.status_error + '</p>' +
                        '</div>');
                }
            });
        }
        
        function updateStatusDisplay(data) {
            const $statusContainer = $('.vortex-application-status-content');
            const status = data.status;
            
            // Create status HTML based on current status
            let statusHTML = '<div class="vortex-status-details">';
            
            // Application info
            statusHTML += '<div class="vortex-application-info">' +
                '<p><strong>' + vortex_student_data.i18n.application_id + ':</strong> ' + data.application_id + '</p>' +
                '<p><strong>' + vortex_student_data.i18n.submission_date + ':</strong> ' + data.submission_date + '</p>' +
                '<p><strong>' + vortex_student_data.i18n.current_status + ':</strong> <span class="vortex-status-badge vortex-status-' + 
                status.toLowerCase().replace(' ', '-') + '">' + status + '</span></p>';
            
            if (data.last_updated) {
                statusHTML += '<p><strong>' + vortex_student_data.i18n.last_updated + ':</strong> ' + data.last_updated + '</p>';
            }
            
            statusHTML += '</div>';
            
            // Status specific content
            statusHTML += '<div class="vortex-status-content">';
            
            switch (status.toLowerCase()) {
                case 'submitted':
                    statusHTML += '<p>' + vortex_student_data.i18n.submitted_message + '</p>';
                    break;
                    
                case 'under review':
                    statusHTML += '<p>' + vortex_student_data.i18n.under_review_message + '</p>';
                    statusHTML += '<div class="vortex-progress-tracker">' +
                        '<div class="vortex-progress-step completed">' +
                        '<div class="vortex-step-indicator">1</div>' +
                        '<div class="vortex-step-label">' + vortex_student_data.i18n.step_submitted + '</div>' +
                        '</div>' +
                        '<div class="vortex-progress-connector active"></div>' +
                        '<div class="vortex-progress-step active">' +
                        '<div class="vortex-step-indicator">2</div>' +
                        '<div class="vortex-step-label">' + vortex_student_data.i18n.step_review + '</div>' +
                        '</div>' +
                        '<div class="vortex-progress-connector"></div>' +
                        '<div class="vortex-progress-step">' +
                        '<div class="vortex-step-indicator">3</div>' +
                        '<div class="vortex-step-label">' + vortex_student_data.i18n.step_decision + '</div>' +
                        '</div>' +
                        '</div>';
                    break;
                    
                case 'approved':
                    statusHTML += '<p class="vortex-status-message success">' + vortex_student_data.i18n.approved_message + '</p>';
                    if (data.enrollment_url) {
                        statusHTML += '<div class="vortex-status-actions">' +
                            '<a href="' + data.enrollment_url + '" class="vortex-button vortex-button-primary">' +
                            vortex_student_data.i18n.complete_enrollment + '</a>' +
                            '</div>';
                    }
                    statusHTML += '<div class="vortex-progress-tracker">' +
                        '<div class="vortex-progress-step completed">' +
                        '<div class="vortex-step-indicator">1</div>' +
                        '<div class="vortex-step-label">' + vortex_student_data.i18n.step_submitted + '</div>' +
                        '</div>' +
                        '<div class="vortex-progress-connector completed"></div>' +
                        '<div class="vortex-progress-step completed">' +
                        '<div class="vortex-step-indicator">2</div>' +
                        '<div class="vortex-step-label">' + vortex_student_data.i18n.step_review + '</div>' +
                        '</div>' +
                        '<div class="vortex-progress-connector completed"></div>' +
                        '<div class="vortex-progress-step completed">' +
                        '<div class="vortex-step-indicator">3</div>' +
                        '<div class="vortex-step-label">' + vortex_student_data.i18n.step_decision + '</div>' +
                        '</div>' +
                        '</div>';
                    break;
                    
                case 'rejected':
                    statusHTML += '<p class="vortex-status-message error">' + vortex_student_data.i18n.rejected_message + '</p>';
                    if (data.feedback) {
                        statusHTML += '<div class="vortex-application-feedback">' +
                            '<h4>' + vortex_student_data.i18n.feedback_heading + '</h4>' +
                            '<p>' + data.feedback + '</p>' +
                            '</div>';
                    }
                    if (data.reapply_date) {
                        statusHTML += '<p>' + vortex_student_data.i18n.reapply_message.replace('%s', data.reapply_date) + '</p>';
                    }
                    break;
                    
                case 'incomplete':
                    statusHTML += '<p class="vortex-status-message warning">' + vortex_student_data.i18n.incomplete_message + '</p>';
                    if (data.missing_items) {
                        statusHTML += '<div class="vortex-missing-items">' +
                            '<h4>' + vortex_student_data.i18n.missing_items_heading + '</h4>' +
                            '<ul>';
                        
                        data.missing_items.forEach(function(item) {
                            statusHTML += '<li>' + item + '</li>';
                        });
                        
                        statusHTML += '</ul></div>';
                    }
                    if (data.completion_url) {
                        statusHTML += '<div class="vortex-status-actions">' +
                            '<a href="' + data.completion_url + '" class="vortex-button vortex-button-primary">' +
                            vortex_student_data.i18n.complete_application + '</a>' +
                            '</div>';
                    }
                    break;
                    
                default:
                    statusHTML += '<p>' + vortex_student_data.i18n.status_processing + '</p>';
            }
            
            statusHTML += '</div>'; // End status content
            
            // Contact info
            statusHTML += '<div class="vortex-contact-info">' +
                '<h4>' + vortex_student_data.i18n.questions_heading + '</h4>' +
                '<p>' + vortex_student_data.i18n.contact_message + ' <a href="mailto:' + 
                vortex_student_data.contact_email + '">' + vortex_student_data.contact_email + '</a></p>' +
                '</div>';
            
            statusHTML += '</div>'; // End status details
            
            // Last updated info
            const now = new Date();
            const formattedDate = now.toLocaleDateString() + ' ' + now.toLocaleTimeString();
            statusHTML += '<div class="vortex-status-refresh">' +
                '<p class="vortex-last-checked">' + vortex_student_data.i18n.last_checked + ' ' + formattedDate + '</p>' +
                '<button type="button" class="vortex-button vortex-button-small vortex-refresh-status">' +
                '<span class="dashicons dashicons-update"></span> ' + vortex_student_data.i18n.refresh_status + '</button>' +
                '</div>';
            
            $statusContainer.html(statusHTML);
            
            // Reattach click handler for refresh button
            $('.vortex-refresh-status').on('click', function(e) {
                e.preventDefault();
                checkApplicationStatus(data.application_id, true);
            });
        }
    });
})(jQuery); 