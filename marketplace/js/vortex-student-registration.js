/**
 * JavaScript for handling student registration and validation
 *
 * @link       https://www.vortexaimarketplace.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle student registration
        $('#vortex-student-registration-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $messages = $form.find('.form-messages');
            var formData = new FormData(this);
            
            // Clear previous messages
            $messages.empty();
            
            // Validate form
            var isValid = validateStudentForm($form);
            
            if (!isValid) {
                return false;
            }
            
            // Disable submit button
            $form.find('button[type="submit"]').prop('disabled', true).text('Processing...');
            
            $.ajax({
                url: vortex_ajax.ajax_url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response.success) {
                        $messages.html('<div class="success-message">' + response.data.message + '</div>');
                        
                        // If redirect URL is provided, redirect after a short delay
                        if (response.data.redirect_url) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect_url;
                            }, 2000);
                        } else {
                            $form.find('input:not([type="hidden"]), select, textarea').val('');
                            $form.find('input[type="checkbox"]').prop('checked', false);
                        }
                    } else {
                        $messages.html('<div class="error-message">' + response.data.message + '</div>');
                    }
                },
                error: function() {
                    $messages.html('<div class="error-message">An error occurred while processing your registration. Please try again later.</div>');
                },
                complete: function() {
                    // Re-enable submit button
                    $form.find('button[type="submit"]').prop('disabled', false).text('Register');
                }
            });
        });
        
        // Validate code on input
        $('#verification_code').on('input', function() {
            $(this).val($(this).val().toUpperCase());
        });
        
        // Show status tooltip on hover
        $('.status-indicator').hover(
            function() {
                $(this).find('.status-tooltip').fadeIn(200);
            },
            function() {
                $(this).find('.status-tooltip').fadeOut(200);
            }
        );
        
        // Check application status
        $('#check-application-status').on('click', function(e) {
            e.preventDefault();
            
            var email = $('#status-email').val();
            var $statusResults = $('#application-status-results');
            
            if (!email || !validateEmail(email)) {
                $statusResults.html('<div class="error-message">Please enter a valid email address.</div>');
                return;
            }
            
            $(this).prop('disabled', true).text('Checking...');
            
            $.ajax({
                url: vortex_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_check_student_status',
                    email: email,
                    security: $('#vortex_student_status_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        $statusResults.html('<div class="status-message ' + response.data.status_class + '">' + 
                            response.data.message + '</div>');
                    } else {
                        $statusResults.html('<div class="error-message">' + response.data.message + '</div>');
                    }
                },
                error: function() {
                    $statusResults.html('<div class="error-message">An error occurred while checking your status. Please try again later.</div>');
                },
                complete: function() {
                    $('#check-application-status').prop('disabled', false).text('Check Status');
                }
            });
        });
    });
    
    /**
     * Validate the student registration form
     */
    function validateStudentForm($form) {
        var isValid = true;
        var $messages = $form.find('.form-messages');
        
        // Clear previous error messages
        $messages.empty();
        $form.find('.field-error').remove();
        
        // Validate verification code format (if present)
        var code = $('#verification_code').val();
        if (code && !/^[A-Z0-9]{8}$/.test(code)) {
            appendError($('#verification_code'), 'Verification code must be 8 characters (letters and numbers).');
            isValid = false;
        }
        
        // Validate password match (if registration form)
        if ($('#password').length && $('#password_confirm').length) {
            var password = $('#password').val();
            var passwordConfirm = $('#password_confirm').val();
            
            if (password !== passwordConfirm) {
                appendError($('#password_confirm'), 'Passwords do not match.');
                isValid = false;
            }
            
            if (password.length < 8) {
                appendError($('#password'), 'Password must be at least 8 characters long.');
                isValid = false;
            }
        }
        
        // Validate required checkboxes
        $form.find('input[type="checkbox"][required]').each(function() {
            if (!$(this).is(':checked')) {
                appendError($(this), 'This field is required.');
                isValid = false;
            }
        });
        
        // If any validation failed, show general error message
        if (!isValid) {
            $messages.html('<div class="error-message">Please correct the errors below.</div>');
        }
        
        return isValid;
    }
    
    /**
     * Append error message to a form field
     */
    function appendError($element, message) {
        if ($element.next('.field-error').length === 0) {
            $element.after('<div class="field-error">' + message + '</div>');
        }
    }
    
    /**
     * Validate email format
     */
    function validateEmail(email) {
        var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }

})(jQuery); 