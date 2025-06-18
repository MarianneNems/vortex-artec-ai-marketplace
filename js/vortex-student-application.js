/**
 * JavaScript for handling student application form submissions
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/js
 */
 
(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle application form submission
        $('#vortex-student-application-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $statusMessage = $form.find('.form-status-message');
            const $submitButton = $form.find('button[type="submit"]');
            
            // Clear previous status messages
            $statusMessage.empty().removeClass('error success');
            
            // Validate file size
            const maxFileSize = 5 * 1024 * 1024; // 5MB
            const fileInput = document.getElementById('verification_document');
            
            if (fileInput.files.length > 0 && fileInput.files[0].size > maxFileSize) {
                $statusMessage.addClass('error').html('File size exceeds the maximum limit of 5MB.');
                return false;
            }
            
            // Disable submit button and show loading state
            $submitButton.prop('disabled', true).html('<span class="spinner"></span>Submitting...');
            
            // Create FormData object to handle file uploads
            const formData = new FormData(this);
            
            // Ajax submission
            $.ajax({
                url: vortex_student_params.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Re-enable submit button
                    $submitButton.prop('disabled', false).text('Submit Application');
                    
                    if (response.success) {
                        // Clear form
                        $form[0].reset();
                        $('.upload-preview').empty();
                        
                        // Show success message
                        $statusMessage.addClass('success').html(response.data.message);
                        
                        // Scroll to message
                        $('html, body').animate({
                            scrollTop: $statusMessage.offset().top - 100
                        }, 500);
                    } else {
                        // Show error message
                        $statusMessage.addClass('error').html(response.data.message);
                    }
                },
                error: function() {
                    // Re-enable submit button
                    $submitButton.prop('disabled', false).text('Submit Application');
                    
                    // Show generic error message
                    $statusMessage.addClass('error').html('An error occurred. Please try again later.');
                }
            });
        });
        
        // Handle status check form submission
        $('#vortex-student-status-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $statusResult = $form.find('.status-result');
            const $submitButton = $form.find('button[type="submit"]');
            
            // Clear previous status messages
            $statusResult.empty().removeClass('error success pending rejected expired');
            
            // Disable submit button and show loading state
            $submitButton.prop('disabled', true).html('<span class="spinner"></span>Checking...');
            
            // Ajax submission
            $.ajax({
                url: vortex_student_params.ajax_url,
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    // Re-enable submit button
                    $submitButton.prop('disabled', false).text('Check Status');
                    
                    if (response.success) {
                        // Show status result
                        $statusResult.addClass(response.data.status).html(response.data.message);
                        
                        // Add details if available
                        if (response.data.details) {
                            $statusResult.append('<div class="status-details">' + response.data.details + '</div>');
                        }
                    } else {
                        // Show error message
                        $statusResult.addClass('error').html(response.data.message);
                    }
                },
                error: function() {
                    // Re-enable submit button
                    $submitButton.prop('disabled', false).text('Check Status');
                    
                    // Show generic error message
                    $statusResult.addClass('error').html('An error occurred. Please try again later.');
                }
            });
        });
        
        // Validate file input when it changes
        $('#verification_document').on('change', function() {
            const file = this.files[0];
            const $fileError = $(this).siblings('.field-error');
            
            // Remove any existing error message
            if ($fileError.length) {
                $fileError.remove();
            }
            
            if (file) {
                const maxSize = 5 * 1024 * 1024; // 5MB
                const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
                
                // Check file type
                if (!allowedTypes.includes(file.type)) {
                    $(this).after('<p class="field-error">Invalid file type. Please upload a PDF, JPG, or PNG file.</p>');
                    this.value = '';
                    $('.upload-preview').empty();
                    return;
                }
                
                // Check file size
                if (file.size > maxSize) {
                    $(this).after('<p class="field-error">File size exceeds 5MB limit. Please upload a smaller file.</p>');
                    this.value = '';
                    $('.upload-preview').empty();
                }
            }
        });
    });
    
})(jQuery); 