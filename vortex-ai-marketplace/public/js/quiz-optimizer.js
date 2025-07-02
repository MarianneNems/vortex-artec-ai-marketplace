/**
 * VORTEX Strategic Assessment Optimizer
 * Monitors form validation and handles submission
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        const form = $('#vortex-strategic-quiz-form');
        const submitBtn = $('#submit-quiz-btn');
        const dobField = $('#dob');
        const pobField = $('#pob');
        const tobField = $('#tob');
        const confirmCheckboxes = $('.question-confirm');
        
        if (!form.length) return;

        // Initialize form state
        updateSubmitButton();

        // Monitor required fields
        dobField.add(pobField).add(tobField).on('change input', function() {
            updateSubmitButton();
        });

        // Monitor confirmation checkboxes
        confirmCheckboxes.on('change', function() {
            updateSubmitButton();
        });

        // Update submit button state
        function updateSubmitButton() {
            const dobFilled = dobField.val() && dobField.val().trim() !== '';
            const pobFilled = pobField.val() && pobField.val().trim() !== '';
            const tobFilled = tobField.val() && tobField.val().trim() !== '';
            const allConfirmed = confirmCheckboxes.length === confirmCheckboxes.filter(':checked').length;
            
            const canSubmit = dobFilled && pobFilled && tobFilled && allConfirmed;
            
            submitBtn.prop('disabled', !canSubmit);
            
            if (canSubmit) {
                submitBtn.removeClass('disabled').addClass('enabled');
            } else {
                submitBtn.removeClass('enabled').addClass('disabled');
            }
            
            // Update progress indicator
            updateProgressIndicator();
        }

        // Update visual progress indicator
        function updateProgressIndicator() {
            const totalRequirements = 3 + confirmCheckboxes.length; // DOB, POB, TOB + confirmations
            let completedRequirements = 0;
            
            if (dobField.val()) completedRequirements++;
            if (pobField.val()) completedRequirements++;
            if (tobField.val()) completedRequirements++;
            completedRequirements += confirmCheckboxes.filter(':checked').length;
            
            const progressPercent = (completedRequirements / totalRequirements) * 100;
            
            // Update any progress indicators in the UI
            $('.progress-indicator').css('width', progressPercent + '%');
        }

        // Handle form submission
        form.on('submit', function(e) {
            e.preventDefault();
            
            if (submitBtn.prop('disabled')) {
                return false;
            }

            // Show loading state
            submitBtn.prop('disabled', true).text('Processing Assessment...');
            
            // Collect form data
            const formData = {
                dob: dobField.val(),
                pob: pobField.val(),
                tob: tobField.val(),
                answers: {},
                notes: {}
            };

            // Collect all question answers
            $('input[type="radio"]:checked').each(function() {
                const questionName = $(this).attr('name');
                formData.answers[questionName] = $(this).val();
            });

            // Collect all additional notes
            $('textarea[name$="_notes"]').each(function() {
                const noteName = $(this).attr('name');
                const noteValue = $(this).val().trim();
                if (noteValue) {
                    formData.notes[noteName] = noteValue;
                }
            });

            // Submit via REST API
            $.ajax({
                url: '/wp-json/vortex/v1/optimized-quiz',
                method: 'POST',
                data: JSON.stringify(formData),
                contentType: 'application/json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', VortexQuizOptimizer.nonce);
                },
                success: function(response) {
                    if (response.success) {
                        showMessage(response.message, 'success');
                        form.fadeOut(500);
                        setTimeout(function() {
                            showSuccessPage();
                        }, 800);
                    } else {
                        showMessage(response.message || 'An error occurred during assessment', 'error');
                        resetSubmitButton();
                    }
                },
                error: function(xhr, status, error) {
                    let errorMessage = 'Assessment submission failed. Please try again.';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.status === 403) {
                        errorMessage = 'Permission denied. Please refresh the page and try again.';
                    } else if (xhr.status === 400) {
                        errorMessage = 'Invalid assessment data. Please check your responses and try again.';
                    }
                    
                    showMessage(errorMessage, 'error');
                    resetSubmitButton();
                }
            });
        });

        // Reset submit button to normal state
        function resetSubmitButton() {
            submitBtn.prop('disabled', false).text('Complete Assessment');
            updateSubmitButton();
        }

        // Show message to user
        function showMessage(message, type) {
            const messageClass = type === 'success' ? 'success-message' : 'error-message';
            const backgroundColor = type === 'success' ? '#d4edda' : '#f8d7da';
            const textColor = type === 'success' ? '#155724' : '#721c24';
            const borderColor = type === 'success' ? '#c3e6cb' : '#f5c6cb';
            
            const messageHtml = `
                <div class="${messageClass}" style="
                    padding: 15px; 
                    margin: 15px 0; 
                    border-radius: 8px; 
                    background: ${backgroundColor}; 
                    color: ${textColor}; 
                    border: 1px solid ${borderColor};
                    font-weight: 500;
                    text-align: center;
                    animation: slideIn 0.3s ease;
                ">
                    ${message}
                </div>
            `;
            
            // Remove existing messages
            $('.success-message, .error-message').remove();
            
            // Add new message at top of form
            form.before(messageHtml);
            
            // Auto-remove error messages after 6 seconds
            if (type === 'error') {
                setTimeout(function() {
                    $('.error-message').fadeOut(300);
                }, 6000);
            }

            // Scroll to message
            $('html, body').animate({
                scrollTop: $('.' + messageClass).offset().top - 100
            }, 500);
        }

        // Show success page
        function showSuccessPage() {
            const successHtml = `
                <div class="assessment-success-page" style="
                    text-align: center; 
                    padding: 50px 30px; 
                    background: linear-gradient(135deg, #007cba 0%, #005a87 100%); 
                    color: white; 
                    border-radius: 15px; 
                    margin: 20px 0;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                ">
                    <div style="font-size: 4em; margin-bottom: 20px;">🎯</div>
                    <h2 style="margin-bottom: 15px; font-size: 2.5em; font-weight: 300;">Assessment Complete!</h2>
                    <p style="font-size: 1.3em; margin-bottom: 30px; opacity: 0.9;">Your strategic assessment has been submitted successfully.</p>
                    
                    <div style="background: rgba(255,255,255,0.1); padding: 30px; border-radius: 12px; margin: 30px 0; backdrop-filter: blur(10px);">
                        <h3 style="margin-bottom: 20px; color: #ffd700; font-size: 1.5em;">📋 What Happens Next?</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; text-align: left;">
                            <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 8px;">
                                <div style="font-size: 1.5em; margin-bottom: 10px;">🧠</div>
                                <strong>Strategic Analysis</strong><br>
                                Horace analyzes your responses for personalized insights
                            </div>
                            <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 8px;">
                                <div style="font-size: 1.5em; margin-bottom: 10px;">📊</div>
                                <strong>Milestone Plan Creation</strong><br>
                                30-day roadmap with daily action steps
                            </div>
                            <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 8px;">
                                <div style="font-size: 1.5em; margin-bottom: 10px;">📧</div>
                                <strong>Email Delivery</strong><br>
                                PDF roadmap sent to your inbox
                            </div>
                            <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 8px;">
                                <div style="font-size: 1.5em; margin-bottom: 10px;">⏰</div>
                                <strong>Daily Coaching</strong><br>
                                8:00 AM reminders for 30 days
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: rgba(255,212,0,0.15); padding: 20px; border-radius: 10px; margin: 25px 0; border: 2px solid rgba(255,212,0,0.3);">
                        <h4 style="margin-bottom: 10px; color: #ffd700;">⚡ Immediate Action Required</h4>
                        <p style="margin: 0; font-size: 1.1em;">Check your email within the next 10 minutes for your personalized milestone roadmap!</p>
                    </div>
                    
                    <div style="margin-top: 35px;">
                        <a href="/dashboard/" style="
                            background: #ffd700; 
                            color: #333; 
                            padding: 15px 30px; 
                            text-decoration: none; 
                            border-radius: 8px; 
                            font-weight: bold; 
                            display: inline-block;
                            transition: all 0.3s ease;
                            margin: 0 10px;
                        " onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                            View Dashboard
                        </a>
                        <a href="/milestones/" style="
                            background: rgba(255,255,255,0.2); 
                            color: white; 
                            padding: 15px 30px; 
                            text-decoration: none; 
                            border-radius: 8px; 
                            font-weight: bold; 
                            display: inline-block;
                            transition: all 0.3s ease;
                            margin: 0 10px;
                            border: 2px solid rgba(255,255,255,0.3);
                        " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                            Track Progress
                        </a>
                    </div>

                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.2);">
                        <p style="font-size: 0.9em; opacity: 0.7; margin: 0;">
                            Your next assessment will be available next month. Focus on executing your milestone plan!
                        </p>
                    </div>
                </div>
            `;
            
            form.closest('.quiz-container').html(successHtml);
            
            // Scroll to top of success page
            $('html, body').animate({
                scrollTop: $('.assessment-success-page').offset().top - 50
            }, 800);
        }

        // Add visual feedback for form interactions
        confirmCheckboxes.each(function() {
            const checkbox = $(this);
            const questionBlock = checkbox.closest('.question-block');
            
            checkbox.on('change', function() {
                if (this.checked) {
                    questionBlock.addClass('confirmed');
                    questionBlock.find('.question-content h4').prepend('<span class="check-icon" style="color: #28a745; margin-right: 8px;">✓</span>');
                } else {
                    questionBlock.removeClass('confirmed');
                    questionBlock.find('.check-icon').remove();
                }
            });
        });

        // Add visual feedback for required fields
        dobField.add(pobField).add(tobField).on('focus', function() {
            $(this).closest('.input-group').addClass('focused');
        }).on('blur', function() {
            $(this).closest('.input-group').removeClass('focused');
            
            // Validate field on blur
            if ($(this).val().trim() !== '') {
                $(this).addClass('completed');
            } else {
                $(this).removeClass('completed');
            }
        });

        // Add character counter for text areas
        $('textarea[name$="_notes"]').each(function() {
            const textarea = $(this);
            const maxLength = 500;
            
            // Add character counter
            const counter = $('<div class="char-counter" style="text-align: right; font-size: 0.8em; color: #666; margin-top: 5px;">0/' + maxLength + ' characters</div>');
            textarea.after(counter);
            
            textarea.on('input', function() {
                const currentLength = $(this).val().length;
                counter.text(currentLength + '/' + maxLength + ' characters');
                
                if (currentLength > maxLength * 0.9) {
                    counter.css('color', '#ff6b6b');
                } else if (currentLength > maxLength * 0.7) {
                    counter.css('color', '#ffa726');
                } else {
                    counter.css('color', '#666');
                }
                
                // Limit to max length
                if (currentLength > maxLength) {
                    $(this).val($(this).val().substring(0, maxLength));
                    counter.text(maxLength + '/' + maxLength + ' characters (limit reached)');
                }
            });
        });

        // Add smooth animations
        const styleSheet = document.createElement('style');
        styleSheet.textContent = `
            @keyframes slideIn {
                from { opacity: 0; transform: translateY(-20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .question-block {
                transition: all 0.3s ease;
            }
            
            .question-block.confirmed {
                transform: translateX(5px);
                box-shadow: 0 4px 12px rgba(40, 167, 69, 0.15);
            }
            
            .input-group.focused {
                transform: scale(1.02);
                transition: transform 0.2s ease;
            }
            
            .input-group input.completed {
                border-color: #28a745 !important;
                background-color: #f8fff8 !important;
            }
            
            .progress-indicator {
                height: 4px;
                background: linear-gradient(135deg, #007cba, #005a87);
                border-radius: 2px;
                transition: width 0.5s ease;
                position: fixed;
                top: 0;
                left: 0;
                z-index: 1000;
            }
        `;
        document.head.appendChild(styleSheet);

        // Add fixed progress indicator
        const progressBar = $('<div class="progress-indicator"></div>');
        $('body').prepend(progressBar);

        // Add milestone completion tracking functionality
        window.VortexMilestones = {
            completeMilestone: function(dayNumber, rating, feedback) {
                $.ajax({
                    url: '/wp-json/vortex/v1/milestone/complete',
                    method: 'POST',
                    data: JSON.stringify({
                        day_number: dayNumber,
                        rating: rating || 0,
                        feedback: feedback || ''
                    }),
                    contentType: 'application/json',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', VortexQuizOptimizer.nonce);
                    },
                    success: function(response) {
                        if (response.success) {
                            console.log('Milestone ' + dayNumber + ' completed successfully');
                            // Trigger completion celebration
                            window.VortexMilestones.celebrateCompletion(dayNumber);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Failed to record milestone completion:', error);
                    }
                });
            },

            celebrateCompletion: function(dayNumber) {
                // Create celebration animation
                const celebration = $(`
                    <div class="milestone-celebration" style="
                        position: fixed;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        background: linear-gradient(135deg, #28a745, #20c997);
                        color: white;
                        padding: 30px;
                        border-radius: 15px;
                        text-align: center;
                        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                        z-index: 10000;
                        animation: milestonePopIn 0.5s ease;
                    ">
                        <div style="font-size: 3em; margin-bottom: 15px;">🎉</div>
                        <h3 style="margin: 0 0 10px 0;">Milestone ${dayNumber} Complete!</h3>
                        <p style="margin: 0; opacity: 0.9;">Great progress on your business journey!</p>
                    </div>
                `);

                $('body').append(celebration);

                // Auto-remove after 3 seconds
                setTimeout(function() {
                    celebration.fadeOut(500, function() {
                        celebration.remove();
                    });
                }, 3000);

                // Add milestone animation styles
                if (!$('#milestone-styles').length) {
                    $('head').append(`
                        <style id="milestone-styles">
                        @keyframes milestonePopIn {
                            0% { opacity: 0; transform: translate(-50%, -50%) scale(0.5); }
                            100% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
                        }
                        </style>
                    `);
                }
            },

            trackEngagement: function() {
                // Track user engagement patterns for adaptive learning
                const engagementData = {
                    timeOnPage: Date.now() - window.vortexStartTime,
                    scrollDepth: $(window).scrollTop() / ($(document).height() - $(window).height()),
                    interactionCount: window.vortexInteractionCount || 0
                };

                // Store engagement data for analysis
                if (typeof(Storage) !== "undefined") {
                    localStorage.setItem('vortex_engagement', JSON.stringify(engagementData));
                }
            }
        };

        // Initialize engagement tracking
        window.vortexStartTime = Date.now();
        window.vortexInteractionCount = 0;

        // Track user interactions
        $(document).on('click change input', function() {
            window.vortexInteractionCount++;
        });

        // Track engagement on page unload
        $(window).on('beforeunload', function() {
            if (window.VortexMilestones) {
                window.VortexMilestones.trackEngagement();
            }
        });
    });

})(jQuery); 