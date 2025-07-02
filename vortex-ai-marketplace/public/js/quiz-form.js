/**
 * VORTEX Artist Business Quiz Form Handler
 * Monitors confirmation checkboxes and required DOB field
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        const form = $('#vortex-business-quiz-form');
        const submitBtn = $('#submit-quiz-btn');
        const dobField = $('#dob');
        const confirmCheckboxes = $('.question-confirm');
        
        if (!form.length) return;

        // Initialize form state
        updateSubmitButton();

        // Monitor DOB field changes
        dobField.on('change input', function() {
            updateSubmitButton();
        });

        // Monitor confirmation checkboxes
        confirmCheckboxes.on('change', function() {
            updateSubmitButton();
        });

        // Update submit button state
        function updateSubmitButton() {
            const dobFilled = dobField.val() && dobField.val().trim() !== '';
            const allConfirmed = confirmCheckboxes.length === confirmCheckboxes.filter(':checked').length;
            
            const canSubmit = dobFilled && allConfirmed;
            
            submitBtn.prop('disabled', !canSubmit);
            
            if (canSubmit) {
                submitBtn.removeClass('disabled').addClass('enabled');
            } else {
                submitBtn.removeClass('enabled').addClass('disabled');
            }
        }

        // Handle form submission
        form.on('submit', function(e) {
            e.preventDefault();
            
            if (submitBtn.prop('disabled')) {
                return false;
            }

            // Show loading state
            submitBtn.prop('disabled', true).text('Submitting...');
            
            // Collect form data
            const formData = {
                dob: dobField.val(),
                pob: $('#pob').val(),
                tob: $('#tob').val(),
                answers: {},
                nonce: VortexQuiz.nonce
            };

            // Collect all question answers
            $('input[type="radio"]:checked').each(function() {
                const questionName = $(this).attr('name');
                formData.answers[questionName] = $(this).val();
            });

            // Submit via REST API
            $.ajax({
                url: '/wp-json/vortex/v1/business-quiz',
                method: 'POST',
                data: JSON.stringify(formData),
                contentType: 'application/json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', VortexQuiz.nonce);
                },
                success: function(response) {
                    if (response.success) {
                        showMessage(response.message, 'success');
                        form.fadeOut();
                        setTimeout(function() {
                            showSuccessPage();
                        }, 1000);
                    } else {
                        showMessage(response.message || 'An error occurred', 'error');
                        resetSubmitButton();
                    }
                },
                error: function(xhr, status, error) {
                    let errorMessage = 'An error occurred while submitting your quiz.';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.status === 403) {
                        errorMessage = 'Permission denied. Please refresh and try again.';
                    }
                    
                    showMessage(errorMessage, 'error');
                    resetSubmitButton();
                }
            });
        });

        // Reset submit button to normal state
        function resetSubmitButton() {
            submitBtn.prop('disabled', false).text('Submit Quiz');
            updateSubmitButton();
        }

        // Show message to user
        function showMessage(message, type) {
            const messageClass = type === 'success' ? 'success-message' : 'error-message';
            const messageHtml = `<div class="${messageClass}" style="padding: 15px; margin: 15px 0; border-radius: 5px; ${type === 'success' ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'}">${message}</div>`;
            
            // Remove existing messages
            $('.success-message, .error-message').remove();
            
            // Add new message
            form.before(messageHtml);
            
            // Auto-remove error messages after 5 seconds
            if (type === 'error') {
                setTimeout(function() {
                    $('.error-message').fadeOut();
                }, 5000);
            }
        }

        // Show success page
        function showSuccessPage() {
            const successHtml = `
                <div class="quiz-success-page" style="text-align: center; padding: 40px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px; margin: 20px 0;">
                    <h2 style="margin-bottom: 20px; font-size: 2em;">🎉 Quiz Submitted Successfully!</h2>
                    <p style="font-size: 1.2em; margin-bottom: 25px;">Thank you for completing the Artist Business Quiz!</p>
                    
                    <div style="background: rgba(255,255,255,0.1); padding: 25px; border-radius: 10px; margin: 25px 0;">
                        <h3 style="margin-bottom: 15px; color: #ffd700;">🚀 What Happens Next?</h3>
                        <ul style="list-style: none; padding: 0; text-align: left; max-width: 400px; margin: 0 auto;">
                            <li style="margin: 10px 0; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.2);">
                                ✨ <strong>Horace</strong> will analyze your responses
                            </li>
                            <li style="margin: 10px 0; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.2);">
                                📋 Your personalized 30-day roadmap will be created
                            </li>
                            <li style="margin: 10px 0; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.2);">
                                📧 Roadmap PDF will be emailed to you
                            </li>
                            <li style="margin: 10px 0; padding: 8px 0;">
                                🔔 Daily guidance notifications will begin at 9:00 AM
                            </li>
                        </ul>
                    </div>
                    
                    <p style="margin-top: 30px; font-size: 1.1em;">
                        <strong>Check your email</strong> for your personalized roadmap!
                    </p>
                    
                    <div style="margin-top: 25px;">
                        <a href="/dashboard/" style="background: #ffd700; color: #333; padding: 12px 25px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;">
                            Go to Dashboard
                        </a>
                    </div>
                </div>
            `;
            
            form.closest('.quiz-container').html(successHtml);
        }

        // Add visual feedback for checkbox interactions
        confirmCheckboxes.each(function() {
            const checkbox = $(this);
            const questionBlock = checkbox.closest('.question-block');
            
            checkbox.on('change', function() {
                if (this.checked) {
                    questionBlock.addClass('confirmed');
                } else {
                    questionBlock.removeClass('confirmed');
                }
            });
        });

        // Add visual feedback for DOB field
        dobField.on('focus', function() {
            $(this).closest('.input-group').addClass('focused');
        }).on('blur', function() {
            $(this).closest('.input-group').removeClass('focused');
        });
    });

})(jQuery);

// Add some basic styles
jQuery(document).ready(function($) {
    const styles = `
        <style>
        .question-block {
            transition: all 0.3s ease;
            border-left: 4px solid #ddd;
            padding: 20px;
            margin: 15px 0;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .question-block.confirmed {
            border-left-color: #28a745;
            background: #f0f8f0;
        }
        
        .input-group.focused {
            transform: scale(1.02);
            transition: transform 0.2s ease;
        }
        
        #submit-quiz-btn.disabled {
            background: #6c757d !important;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        #submit-quiz-btn.enabled {
            background: #28a745 !important;
            cursor: pointer;
            opacity: 1;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
        }
        
        .question-confirm {
            margin-right: 8px;
            transform: scale(1.2);
        }
        
        .quiz-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        </style>
    `;
    
    $('head').append(styles);
}); 