/**
 * Handle Business Strategist AI Agent interactions
 * Automatic popup for new users after registration
 */
(function($) {
    $(document).ready(function() {
        // Check if user is newly registered and verified
        const isNewUser = getCookie('vortex_new_user') === 'true';
        const isVerified = getCookie('vortex_account_verified') === 'true';
        const hasSeenIntro = localStorage.getItem('vortex_business_strategist_intro');
        
        // Show popup automatically for new verified users who haven't seen it
        if (isNewUser && isVerified && !hasSeenIntro) {
            setTimeout(function() {
                showBusinessStrategistPopup();
            }, 1500); // Slight delay for better UX
            
            // Set cookie so it doesn't show again
            localStorage.setItem('vortex_business_strategist_intro', 'true');
        }
        
        // Manual trigger for business strategist
        $('.vortex-business-strategist-trigger').on('click', function(e) {
            e.preventDefault();
            showBusinessStrategistPopup();
        });
        
        // Handle form submission
        $(document).on('submit', '#vortex-business-idea-form', function(e) {
            e.preventDefault();
            
            const businessIdea = $('#vortex-business-idea').val();
            
            if (!businessIdea || businessIdea.trim() === '') {
                $('.vortex-bs-error').text('Please share your business idea to continue.').show();
                return;
            }
            
            // Show loading state
            $('.vortex-bs-loading').show();
            $('.vortex-business-strategist-form').hide();
            
            // Process the business idea via AJAX
            $.ajax({
                url: vortex_business_strategist.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_process_business_idea',
                    nonce: vortex_business_strategist.nonce,
                    business_idea: businessIdea
                },
                success: function(response) {
                    $('.vortex-bs-loading').hide();
                    
                    if (response.success) {
                        // Display the business plan
                        showBusinessPlan(response.data);
                    } else {
                        $('.vortex-bs-error').text(response.data.message || 'An error occurred. Please try again.').show();
                        $('.vortex-business-strategist-form').show();
                    }
                },
                error: function() {
                    $('.vortex-bs-loading').hide();
                    $('.vortex-bs-error').text('An error occurred. Please try again.').show();
                    $('.vortex-business-strategist-form').show();
                }
            });
        });
        
        // Close popup
        $(document).on('click', '.vortex-bs-close', function() {
            $('#vortex-business-strategist-popup').remove();
        });
    });
    
    // Function to show the business strategist popup
    function showBusinessStrategistPopup() {
        // Remove any existing popup
        $('#vortex-business-strategist-popup').remove();
        
        // Create popup HTML
        const popupHtml = `
            <div id="vortex-business-strategist-popup" class="vortex-modal">
                <div class="vortex-modal-content">
                    <span class="vortex-bs-close">&times;</span>
                    <div class="vortex-modal-header">
                        <img src="${vortex_business_strategist.logo_url}" alt="Business Strategist" class="vortex-bs-avatar">
                        <h2>Business Strategist AI Agent</h2>
                    </div>
                    
                    <div class="vortex-modal-body">
                        <div class="vortex-bs-welcome">
                            <p>Hello and welcome. I am your Vortex AI Business Strategist, here to support you in reaching the highest levels of professional and financial success.</p>
                            
                            <p>At any time, simply open my window to ask questions or seek guidance about your business. I am here to help you build a clear and actionable business plan, complete with defined milestones and scheduled push notifications tailored to your goals.</p>
                            
                            <p>To begin, please share your business idea in full detail. Take a moment to reflect on what sets your concept apart—your unique strengths, vision, goals, target clients, and audience. The more precise you are, the more effectively I can assist you in creating a strategy that helps your business thrive.</p>
                            
                            <p>Let's build something remarkable together.</p>
                        </div>
                        
                        <div class="vortex-bs-error" style="display: none;"></div>
                        
                        <div class="vortex-business-strategist-form">
                            <form id="vortex-business-idea-form">
                                <textarea id="vortex-business-idea" rows="10" placeholder="Please describe your business idea in detail..."></textarea>
                                <button type="submit" class="vortex-button vortex-button-primary">Submit My Business Idea</button>
                            </form>
                        </div>
                        
                        <div class="vortex-bs-loading" style="display: none;">
                            <p>I'm analyzing your business idea and creating a personalized plan...</p>
                            <div class="vortex-loader"></div>
                        </div>
                        
                        <div class="vortex-bs-response" style="display: none;"></div>
                    </div>
                </div>
            </div>
        `;
        
        // Append popup to body
        $('body').append(popupHtml);
    }
    
    // Function to display the business plan
    function showBusinessPlan(data) {
        const businessPlanHtml = `
            <h3>Your Business Plan</h3>
            <div class="vortex-business-plan">
                <div class="vortex-business-plan-summary">
                    ${data.summary}
                </div>
                
                <h4>30-Day Roadmap</h4>
                <div class="vortex-business-plan-roadmap">
                    ${data.roadmap}
                </div>
                
                <h4>Weekly Calendar</h4>
                <div class="vortex-business-plan-calendar">
                    ${data.calendar}
                </div>
                
                <div class="vortex-business-plan-actions">
                    <a href="${data.pdf_url}" class="vortex-button vortex-button-primary" target="_blank" download>
                        <span class="vortex-icon vortex-icon-download"></span> Download PDF
                    </a>
                    <button class="vortex-button vortex-button-primary vortex-save-plan">Save to My Dashboard</button>
                    <button class="vortex-button vortex-button-secondary vortex-bs-close">Close</button>
                </div>
            </div>
        `;
        
        $('.vortex-bs-response').html(businessPlanHtml).show();
        
        // Email notification
        if (data.email_sent) {
            const emailNotification = `
                <div class="vortex-email-notification">
                    <p><span class="vortex-icon vortex-icon-email"></span> Your business plan PDF has been emailed to you!</p>
                </div>
            `;
            $('.vortex-business-plan').append(emailNotification);
        }
        
        // Show milestone reminder prompt
        setTimeout(function() {
            showMilestoneReminderPrompt();
        }, 3000);
    }
    
    // Function to show milestone reminder prompt
    function showMilestoneReminderPrompt() {
        const reminderPrompt = `
            <div class="vortex-milestone-reminder-prompt">
                <div class="vortex-milestone-reminder-content">
                    <h4>Maximize Your Success</h4>
                    <p>Would you like reminders for your milestone goals?</p>
                    <p class="vortex-reminder-description">I'll send you timely notifications to help you stay on track with your business plan milestones.</p>
                    <div class="vortex-milestone-buttons">
                        <button class="vortex-button vortex-button-primary vortex-enable-reminders">Yes, Enable Reminders</button>
                        <button class="vortex-button vortex-button-secondary vortex-skip-reminders">No, Thanks</button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(reminderPrompt);
        
        // Handle reminder choice
        $('.vortex-enable-reminders').on('click', function() {
            enableMilestoneReminders();
            $('.vortex-milestone-reminder-prompt').remove();
        });
        
        $('.vortex-skip-reminders').on('click', function() {
            $('.vortex-milestone-reminder-prompt').remove();
        });
    }
    
    // Function to enable milestone reminders
    function enableMilestoneReminders() {
        // Send AJAX request to enable reminders
        $.ajax({
            url: vortex_business_strategist.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_enable_milestone_reminders',
                nonce: vortex_business_strategist.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show confirmation toast
                    showToast('Milestone reminders enabled. You\'ll receive notifications to help you stay on track!');
                }
            }
        });
    }
    
    // Function to show toast notification
    function showToast(message) {
        const toast = `<div class="vortex-toast">${message}</div>`;
        $('body').append(toast);
        
        setTimeout(function() {
            $('.vortex-toast').addClass('vortex-toast-show');
        }, 100);
        
        setTimeout(function() {
            $('.vortex-toast').removeClass('vortex-toast-show');
            setTimeout(function() {
                $('.vortex-toast').remove();
            }, 500);
        }, 3000);
    }
    
    // Helper function to get cookie value
    function getCookie(name) {
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        if (match) return match[2];
        return null;
    }
})(jQuery); 