/**
 * VORTEX Quiz Optimizer - Enhanced validation and submission v3.0.0
 * 
 * @package Vortex_AI_Marketplace
 * @version 3.0.0
 */

(function($) {
    'use strict';

    // Configuration with fallbacks
    const config = {
        formSelector: '#vortex-strategic-assessment-form',
        submitButtonSelector: '#submit-quiz-btn',
        questionBlockSelector: '.question-block',
        confirmCheckboxSelector: '.question-confirm',
        requiredFieldSelector: 'input[required], select[required], textarea[required]',
        personalFieldSelector: 'input[name="dob"], input[name="pob"], input[name="tob"]',
        progressSelector: '.quiz-progress-indicator',
        endpoint: (typeof VortexQuizConfig !== 'undefined' ? VortexQuizConfig.restUrl : '/wp-json/vortex/v1/') + 'optimized-quiz',
        nonce: typeof VortexQuizConfig !== 'undefined' ? VortexQuizConfig.nonce : '',
        userId: typeof VortexQuizConfig !== 'undefined' ? VortexQuizConfig.userId : 0,
        isLoggedIn: typeof VortexQuizConfig !== 'undefined' ? VortexQuizConfig.isLoggedIn : false,
        messages: {
            validationError: 'Please complete all required fields and confirmations.',
            submissionError: 'An error occurred. Please try again.',
            submissionSuccess: typeof VortexQuizConfig !== 'undefined' ? VortexQuizConfig.submissionSuccessMessage : 'Thank you! Horace is crafting your personalized 30-day milestone roadmap.',
            monthlyLimit: typeof VortexQuizConfig !== 'undefined' ? VortexQuizConfig.monthlyLimitMessage : 'You have already completed your assessment this month.'
        }
    };

    // Enhanced Quiz Optimizer Object
    const QuizOptimizer = {
        
        init: function() {
            this.checkPrerequisites();
            this.bindEvents();
            this.initializeProgressTracking();
            this.initializeValidation();
            this.enableAccessibilityFeatures();
            this.addAutoSave();
            console.log('VORTEX Quiz Optimizer v3.0.0 initialized');
        },

        checkPrerequisites: function() {
            if (!config.isLoggedIn) {
                this.showMessage('Please log in to access the strategic assessment.', 'warning');
                return false;
            }
            return true;
        },

        bindEvents: function() {
            $(document).on('change', config.confirmCheckboxSelector, this.handleConfirmationChange);
            $(document).on('change', config.requiredFieldSelector, this.validateForm);
            $(document).on('submit', config.formSelector, this.handleFormSubmission);
            $(document).on('input', 'textarea[name$="_notes"]', this.updateCharacterCount);
            $(document).on('keydown', config.formSelector, this.handleKeyboardNavigation);
            $(document).on('focus', 'input, textarea, select', this.handleFieldFocus);
            $(document).on('change', 'input[type="radio"]', this.handleRadioChange);
        },

        initializeProgressTracking: function() {
            const totalQuestions = $(config.questionBlockSelector).length;
            if (totalQuestions > 0) {
                this.createProgressIndicator(totalQuestions);
                this.updateProgress(); // Initial progress calculation
            }
        },

        createProgressIndicator: function(total) {
            const progressHtml = `
                <div class="quiz-progress-indicator" role="progressbar" aria-valuemin="0" aria-valuemax="${total}" aria-valuenow="0">
                    <div class="progress-header">
                        <h4>Assessment Progress</h4>
                        <span class="progress-text">0 of ${total} questions confirmed</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 0%"></div>
                    </div>
                    <div class="milestone-indicators">
                        <span class="milestone" data-milestone="25">25%</span>
                        <span class="milestone" data-milestone="50">50%</span>
                        <span class="milestone" data-milestone="75">75%</span>
                        <span class="milestone" data-milestone="100">100%</span>
                    </div>
                </div>
            `;
            
            $(config.formSelector).prepend(progressHtml);
        },

        handleConfirmationChange: function() {
            const $checkbox = $(this);
            const $questionBlock = $checkbox.closest(config.questionBlockSelector);
            
            if ($checkbox.is(':checked')) {
                $questionBlock.addClass('confirmed').attr('aria-expanded', 'true');
                
                // Add confirmation animation
                $questionBlock.addClass('confirming');
                setTimeout(() => $questionBlock.removeClass('confirming'), 300);
                
                // Check for milestones
                QuizOptimizer.checkMilestone();
            } else {
                $questionBlock.removeClass('confirmed').attr('aria-expanded', 'false');
            }
            
            QuizOptimizer.updateProgress();
            QuizOptimizer.validateForm();
            QuizOptimizer.saveProgress();
        },

        handleRadioChange: function() {
            const $radio = $(this);
            const questionName = $radio.attr('name');
            const $questionBlock = $radio.closest(config.questionBlockSelector);
            
            // Mark question as answered
            $questionBlock.addClass('answered');
            
            // Save progress
            QuizOptimizer.saveProgress();
        },

        updateProgress: function() {
            const totalQuestions = $(config.questionBlockSelector).length;
            const confirmedQuestions = $(config.questionBlockSelector + '.confirmed').length;
            const answeredQuestions = $(config.questionBlockSelector + '.answered').length;
            const progressPercent = (confirmedQuestions / totalQuestions) * 100;
            
            $('.progress-fill').css('width', progressPercent + '%');
            $('.progress-text').text(`${confirmedQuestions} of ${totalQuestions} questions confirmed`);
            $('.quiz-progress-indicator').attr('aria-valuenow', confirmedQuestions);
            
            // Update milestone indicators
            $('.milestone').each(function() {
                const milestone = parseInt($(this).data('milestone'));
                if (progressPercent >= milestone) {
                    $(this).addClass('reached');
                } else {
                    $(this).removeClass('reached');
                }
            });
            
            // Update progress percentage display
            $('.progress-header').find('.percentage').remove();
            $('.progress-header').append(`<span class="percentage">${Math.round(progressPercent)}%</span>`);
        },

        checkMilestone: function() {
            const confirmedQuestions = $(config.questionBlockSelector + '.confirmed').length;
            const totalQuestions = $(config.questionBlockSelector).length;
            const progressPercent = (confirmedQuestions / totalQuestions) * 100;
            
            // Celebration at key milestones
            if (progressPercent === 25 || progressPercent === 50 || progressPercent === 75 || progressPercent === 100) {
                this.celebrateMilestone(progressPercent);
            }
        },

        celebrateMilestone: function(percent) {
            const messages = {
                25: "🌟 Great start! You're 25% complete.",
                50: "🚀 Halfway there! Keep going!",
                75: "💪 Almost done! 75% complete.",
                100: "🎉 All questions confirmed! Ready to submit."
            };
            
            this.showTemporaryMessage(messages[percent], 'milestone');
            
            // Add celebration visual effect
            $('.milestone[data-milestone="' + percent + '"]').addClass('celebrating');
            setTimeout(() => {
                $('.milestone[data-milestone="' + percent + '"]').removeClass('celebrating');
            }, 1000);
        },

        showTemporaryMessage: function(message, type) {
            const $tempMessage = $(`<div class="temp-message temp-message-${type}">${message}</div>`);
            $('.quiz-progress-indicator').append($tempMessage);
            
            setTimeout(() => {
                $tempMessage.fadeOut(300, () => $tempMessage.remove());
            }, 2000);
        },

        initializeValidation: function() {
            this.validateForm();
            this.addRealTimeValidation();
            this.loadSavedProgress();
        },

        addRealTimeValidation: function() {
            // Real-time validation for date field
            $('input[name="dob"]').on('change', function() {
                const birthDate = new Date($(this).val());
                const today = new Date();
                const age = today.getFullYear() - birthDate.getFullYear();
                
                $(this).siblings('.validation-message').remove();
                
                if ($(this).val() === '') {
                    $(this).addClass('invalid');
                    $(this).after('<span class="validation-message error">Date of birth is required</span>');
                } else if (age < 13 || age > 120 || birthDate > today) {
                    $(this).addClass('invalid');
                    $(this).after('<span class="validation-message error">Please enter a valid birth date</span>');
                } else {
                    $(this).removeClass('invalid');
                    $(this).after('<span class="validation-message success">✓ Valid date</span>');
                }
            });
            
            // Real-time validation for place of birth
            $('input[name="pob"]').on('input', function() {
                const value = $(this).val().trim();
                $(this).siblings('.validation-message').remove();
                
                if (value.length === 0) {
                    $(this).addClass('invalid');
                    $(this).after('<span class="validation-message error">Place of birth is required</span>');
                } else if (value.length < 2) {
                    $(this).addClass('invalid');
                    $(this).after('<span class="validation-message error">Please enter a valid location</span>');
                } else {
                    $(this).removeClass('invalid');
                    $(this).after('<span class="validation-message success">✓ Valid location</span>');
                }
            });
            
            // Real-time validation for time of birth
            $('input[name="tob"]').on('change', function() {
                const value = $(this).val();
                $(this).siblings('.validation-message').remove();
                
                if (value === '') {
                    $(this).addClass('invalid');
                    $(this).after('<span class="validation-message error">Time of birth is required</span>');
                } else {
                    $(this).removeClass('invalid');
                    $(this).after('<span class="validation-message success">✓ Valid time</span>');
                }
            });
        },

        validateForm: function() {
            const isValid = QuizOptimizer.checkFormValidity();
            const $submitButton = $(config.submitButtonSelector);
            
            if (isValid) {
                $submitButton.removeClass('disabled').prop('disabled', false);
                $submitButton.find('.btn-text').text('Complete Assessment');
                $submitButton.attr('aria-disabled', 'false');
                $submitButton.addClass('ready');
            } else {
                $submitButton.addClass('disabled').prop('disabled', true);
                $submitButton.find('.btn-text').text('Complete All Fields');
                $submitButton.attr('aria-disabled', 'true');
                $submitButton.removeClass('ready');
            }
            
            return isValid;
        },

        checkFormValidity: function() {
            // Check personal information fields
            let personalFieldsValid = true;
            $(config.personalFieldSelector).each(function() {
                if (!$(this).val() || $(this).hasClass('invalid')) {
                    personalFieldsValid = false;
                    return false;
                }
            });

            // Check all radio button groups are answered
            let allQuestionsAnswered = true;
            for (let i = 1; i <= 12; i++) {
                if (!$(`input[name="q${i}"]:checked`).length) {
                    allQuestionsAnswered = false;
                    break;
                }
            }

            // Check all confirmations are checked
            const totalConfirmations = $(config.confirmCheckboxSelector).length;
            const checkedConfirmations = $(config.confirmCheckboxSelector + ':checked').length;
            const allConfirmed = (totalConfirmations === checkedConfirmations);

            return personalFieldsValid && allQuestionsAnswered && allConfirmed;
        },

        updateCharacterCount: function() {
            const $textarea = $(this);
            const currentLength = $textarea.val().length;
            const maxLength = $textarea.attr('maxlength') || 500;
            const remaining = maxLength - currentLength;
            
            let $counter = $textarea.siblings('.character-count');
            if (!$counter.length) {
                $counter = $('<div class="character-count" role="status" aria-live="polite"></div>');
                $textarea.after($counter);
            }
            
            $counter.html(`
                <span class="current">${currentLength}</span>
                <span class="separator">/</span>
                <span class="max">${maxLength}</span>
                <span class="remaining">${remaining} remaining</span>
            `);
            
            if (remaining < 50) {
                $counter.addClass('near-limit');
            } else {
                $counter.removeClass('near-limit');
            }
            
            if (remaining < 0) {
                $counter.addClass('over-limit');
                $textarea.addClass('invalid');
            } else {
                $counter.removeClass('over-limit');
                $textarea.removeClass('invalid');
            }
        },

        handleKeyboardNavigation: function(e) {
            // Enhanced keyboard navigation
            if (e.key === 'Enter' && e.ctrlKey) {
                // Ctrl+Enter to submit
                if (QuizOptimizer.checkFormValidity()) {
                    $(config.formSelector).submit();
                }
            }
            
            // Arrow key navigation for radio buttons
            if (e.key === 'ArrowDown' || e.key === 'ArrowRight') {
                const $focused = $(e.target);
                if ($focused.is('input[type="radio"]')) {
                    const $next = $focused.closest('.question-options').find('input[type="radio"]').eq(
                        $focused.closest('.question-options').find('input[type="radio"]').index($focused) + 1
                    );
                    if ($next.length) {
                        $next.focus();
                        e.preventDefault();
                    }
                }
            }
            
            if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') {
                const $focused = $(e.target);
                if ($focused.is('input[type="radio"]')) {
                    const $prev = $focused.closest('.question-options').find('input[type="radio"]').eq(
                        $focused.closest('.question-options').find('input[type="radio"]').index($focused) - 1
                    );
                    if ($prev.length) {
                        $prev.focus();
                        e.preventDefault();
                    }
                }
            }
        },

        handleFieldFocus: function() {
            // Remove any validation messages when field gains focus
            $(this).siblings('.validation-message.error').fadeOut();
            
            // Add focus indicator to question block
            $(this).closest(config.questionBlockSelector).addClass('focused');
        },

        enableAccessibilityFeatures: function() {
            // Add ARIA labels and descriptions
            $(config.questionBlockSelector).each(function(index) {
                const questionNumber = index + 1;
                $(this).attr('aria-labelledby', `question-${questionNumber}-title`);
                $(this).find('h4').attr('id', `question-${questionNumber}-title`);
                
                // Add focus management
                $(this).on('focusout', function() {
                    $(this).removeClass('focused');
                });
            });
            
            // Add keyboard shortcuts info
            const shortcutsInfo = `
                <div class="keyboard-shortcuts" role="complementary">
                    <button type="button" class="shortcuts-toggle" aria-expanded="false">
                        ⌨️ Keyboard Shortcuts
                    </button>
                    <div class="shortcuts-content" hidden>
                        <ul>
                            <li><kbd>Tab</kbd> - Navigate between fields</li>
                            <li><kbd>Space</kbd> - Check/uncheck checkboxes</li>
                            <li><kbd>↑↓</kbd> - Navigate radio options</li>
                            <li><kbd>Ctrl + Enter</kbd> - Submit form (when valid)</li>
                            <li><kbd>Ctrl + S</kbd> - Save progress</li>
                        </ul>
                    </div>
                </div>
            `;
            
            $(config.formSelector).append(shortcutsInfo);
            
            $('.shortcuts-toggle').on('click', function() {
                const $content = $(this).siblings('.shortcuts-content');
                const isHidden = $content.attr('hidden') !== undefined;
                
                if (isHidden) {
                    $content.removeAttr('hidden').slideDown(200);
                    $(this).attr('aria-expanded', 'true');
                } else {
                    $content.slideUp(200, function() {
                        $(this).attr('hidden', '');
                    });
                    $(this).attr('aria-expanded', 'false');
                }
            });
        },

        addAutoSave: function() {
            // Auto-save functionality
            let autoSaveTimer;
            
            $(document).on('change input', config.formSelector + ' input, ' + config.formSelector + ' textarea', function() {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(() => {
                    QuizOptimizer.saveProgress();
                }, 2000); // Save after 2 seconds of inactivity
            });
            
            // Manual save with Ctrl+S
            $(document).on('keydown', function(e) {
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    QuizOptimizer.saveProgress();
                    QuizOptimizer.showTemporaryMessage('Progress saved!', 'success');
                }
            });
        },

        saveProgress: function() {
            if (!config.userId) return;
            
            const progressData = this.collectFormData();
            progressData.is_draft = true;
            
            // Save to localStorage as backup
            localStorage.setItem('vortex_quiz_progress_' + config.userId, JSON.stringify(progressData));
            
            // Optionally save to server
            $.ajax({
                url: config.endpoint.replace('optimized-quiz', 'save-progress'),
                method: 'POST',
                data: JSON.stringify(progressData),
                contentType: 'application/json',
                headers: {
                    'X-WP-Nonce': config.nonce
                },
                success: function() {
                    $('.auto-save-indicator').removeClass('saving').addClass('saved');
                },
                error: function() {
                    $('.auto-save-indicator').removeClass('saving').addClass('error');
                }
            });
        },

        loadSavedProgress: function() {
            if (!config.userId) return;
            
            const savedData = localStorage.getItem('vortex_quiz_progress_' + config.userId);
            if (savedData) {
                try {
                    const progressData = JSON.parse(savedData);
                    this.restoreFormData(progressData);
                    this.showTemporaryMessage('Previous progress restored', 'success');
                } catch (e) {
                    console.error('Error loading saved progress:', e);
                }
            }
        },

        restoreFormData: function(data) {
            // Restore personal fields
            if (data.dob) $('input[name="dob"]').val(data.dob).trigger('change');
            if (data.pob) $('input[name="pob"]').val(data.pob).trigger('input');
            if (data.tob) $('input[name="tob"]').val(data.tob).trigger('change');
            
            // Restore answers
            if (data.answers) {
                Object.keys(data.answers).forEach(key => {
                    $(`input[name="${key}"][value="${data.answers[key]}"]`).prop('checked', true).trigger('change');
                });
            }
            
            // Restore notes
            if (data.notes) {
                Object.keys(data.notes).forEach(key => {
                    $(`textarea[name="${key}"]`).val(data.notes[key]).trigger('input');
                });
            }
        },

        handleFormSubmission: function(e) {
            e.preventDefault();
            
            if (!QuizOptimizer.checkFormValidity()) {
                QuizOptimizer.showMessage(config.messages.validationError, 'error');
                // Focus on first invalid field
                const $firstInvalid = $('.invalid').first();
                if ($firstInvalid.length) {
                    $firstInvalid.focus();
                } else {
                    // Find first unanswered question
                    const $firstUnanswered = $(config.questionBlockSelector).not('.answered').first();
                    if ($firstUnanswered.length) {
                        $firstUnanswered.find('input[type="radio"]').first().focus();
                    }
                }
                return false;
            }

            QuizOptimizer.submitQuiz();
        },

        submitQuiz: function() {
            const $form = $(config.formSelector);
            const $submitButton = $(config.submitButtonSelector);
            
            // Show loading state
            $submitButton.addClass('loading').prop('disabled', true);
            $submitButton.find('.btn-text').text('Processing Assessment...');
            $submitButton.find('.btn-icon').text('⏳');
            
            // Add loading overlay
            const loadingOverlay = `
                <div class="submission-overlay">
                    <div class="submission-progress">
                        <div class="spinner"></div>
                        <h4>Processing Your Assessment</h4>
                        <p>Horace is analyzing your responses and crafting your personalized roadmap...</p>
                        <div class="progress-steps">
                            <div class="step active">✓ Validating responses</div>
                            <div class="step">📊 Analyzing patterns</div>
                            <div class="step">🎯 Building milestones</div>
                            <div class="step">📧 Preparing delivery</div>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(loadingOverlay);
            
            // Animate progress steps
            let stepIndex = 1;
            const stepInterval = setInterval(() => {
                $('.progress-steps .step').eq(stepIndex).addClass('active');
                stepIndex++;
                if (stepIndex >= 4) {
                    clearInterval(stepInterval);
                }
            }, 1000);
            
            // Collect form data
            const formData = this.collectFormData();
            
            // Submit via AJAX
            $.ajax({
                url: config.endpoint,
                method: 'POST',
                data: JSON.stringify(formData),
                contentType: 'application/json',
                headers: {
                    'X-WP-Nonce': config.nonce
                },
                timeout: 45000, // 45 second timeout
                success: function(response) {
                    clearInterval(stepInterval);
                    QuizOptimizer.handleSubmissionSuccess(response);
                },
                error: function(xhr, status, error) {
                    clearInterval(stepInterval);
                    QuizOptimizer.handleSubmissionError(xhr, status, error);
                }
            });
        },

        collectFormData: function() {
            const data = {
                dob: $('input[name="dob"]').val(),
                pob: $('input[name="pob"]').val(),
                tob: $('input[name="tob"]').val(),
                answers: {},
                notes: {},
                user_id: config.userId,
                timestamp: Date.now(),
                completion_time: this.calculateCompletionTime()
            };

            // Collect question answers
            for (let i = 1; i <= 12; i++) {
                const answer = $(`input[name="q${i}"]:checked`).val();
                if (answer) {
                    data.answers[`q${i}`] = answer;
                }
                
                const notes = $(`textarea[name="q${i}_notes"]`).val();
                if (notes && notes.trim()) {
                    data.notes[`q${i}_notes`] = notes.trim();
                }
            }

            return data;
        },

        calculateCompletionTime: function() {
            const startTime = localStorage.getItem('vortex_quiz_start_time');
            if (startTime) {
                return Date.now() - parseInt(startTime);
            }
            return null;
        },

        handleSubmissionSuccess: function(response) {
            $('.submission-overlay').remove();
            
            // Clear saved progress
            localStorage.removeItem('vortex_quiz_progress_' + config.userId);
            localStorage.removeItem('vortex_quiz_start_time');
            
            this.showMessage(response.message || config.messages.submissionSuccess, 'success');
            
            // Celebration animation
            this.triggerSuccessAnimation();
            
            // Hide form and show success state
            $(config.formSelector).fadeOut(500, function() {
                const successHtml = `
                    <div class="quiz-submission-success" role="alert" aria-live="assertive">
                        <div class="success-icon">🎉</div>
                        <h3>Strategic Assessment Complete!</h3>
                        <p class="success-message">${response.message || config.messages.submissionSuccess}</p>
                        
                        <div class="success-timeline">
                            <div class="timeline-item">
                                <div class="timeline-icon">📧</div>
                                <div class="timeline-content">
                                    <h4>Within 30 minutes</h4>
                                    <p>Personalized 30-day roadmap delivered to your email</p>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-icon">📱</div>
                                <div class="timeline-content">
                                    <h4>Starting tomorrow</h4>
                                    <p>Daily coaching messages at 8:00 AM for 30 days</p>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-icon">📊</div>
                                <div class="timeline-content">
                                    <h4>Track progress</h4>
                                    <p>Monitor your milestones and celebrate achievements</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="success-actions">
                            <a href="/dashboard/" class="btn btn-primary">
                                <span>View Dashboard</span>
                                <span class="btn-icon">📊</span>
                            </a>
                            <a href="/milestones/" class="btn btn-secondary">
                                <span>Track Milestones</span>
                                <span class="btn-icon">🎯</span>
                            </a>
                        </div>
                        
                        <div class="success-footer">
                            <p>Need support with your roadmap? <a href="/support/">Contact our team</a></p>
                        </div>
                    </div>
                `;
                $(this).parent().append(successHtml);
            });
        },

        triggerSuccessAnimation: function() {
            // Create celebratory animation
            const celebration = $(`
                <div class="celebration-overlay">
                    <div class="confetti-container">
                        ${this.generateConfetti()}
                    </div>
                </div>
            `);
            
            $('body').append(celebration);
            
            setTimeout(() => {
                celebration.fadeOut(1000, () => celebration.remove());
            }, 3000);
        },

        generateConfetti: function() {
            let confetti = '';
            const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57', '#ff9ff3'];
            
            for (let i = 0; i < 50; i++) {
                const color = colors[Math.floor(Math.random() * colors.length)];
                const left = Math.random() * 100;
                const delay = Math.random() * 3;
                const duration = 3 + Math.random() * 2;
                
                confetti += `
                    <div class="confetti-piece" 
                         style="left: ${left}%; 
                                background-color: ${color}; 
                                animation-delay: ${delay}s; 
                                animation-duration: ${duration}s;">
                    </div>
                `;
            }
            
            return confetti;
        },

        handleSubmissionError: function(xhr, status, error) {
            $('.submission-overlay').remove();
            
            const $submitButton = $(config.submitButtonSelector);
            
            // Reset button state
            $submitButton.removeClass('loading').prop('disabled', false);
            $submitButton.find('.btn-text').text('Complete Assessment');
            $submitButton.find('.btn-icon').text('📊');
            
            let errorMessage = config.messages.submissionError;
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (status === 'timeout') {
                errorMessage = 'Request timed out. Please check your connection and try again.';
            } else if (xhr.status === 429) {
                errorMessage = 'Too many requests. Please wait a moment and try again.';
            }
            
            this.showMessage(errorMessage, 'error');
            
            // Log error for debugging
            console.error('Quiz submission error:', {
                status: status,
                error: error,
                response: xhr.responseJSON
            });
        },

        showMessage: function(message, type) {
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                milestone: '🌟',
                info: 'ℹ️'
            };
            
            const messageHtml = `
                <div class="quiz-message quiz-message-${type}" role="alert" aria-live="assertive">
                    <span class="message-icon">${icons[type] || icons.info}</span>
                    <div class="message-content">
                        <p>${message}</p>
                    </div>
                    <button class="close-message" aria-label="Close message" type="button">&times;</button>
                </div>
            `;
            
            // Remove existing messages
            $('.quiz-message').remove();
            
            // Add new message
            $(config.formSelector).before(messageHtml);
            
            // Auto-hide success messages
            if (type === 'success' || type === 'milestone') {
                setTimeout(function() {
                    $('.quiz-message').fadeOut();
                }, 6000);
            }
            
            // Handle close button
            $('.close-message').on('click', function() {
                $(this).parent().fadeOut();
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($(config.formSelector).length) {
            // Store start time for completion tracking
            localStorage.setItem('vortex_quiz_start_time', Date.now());
            
            // Initialize the quiz optimizer
            QuizOptimizer.init();
        }
    });

    // Export for external access
    window.VortexQuizOptimizer = QuizOptimizer;

})(jQuery); 