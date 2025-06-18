/**
 * Vortex AI Marketplace - Artist Qualifier JS
 *
 * JavaScript for handling artist qualification quiz interactions with the Artist Qualifier AI agent.
 *
 * @link       https://aimarketplace.vortex-it.com/
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/js
 */

(function($) {
    'use strict';

    /**
     * Initialize the artist qualifier interactions when the document is ready.
     */
    $(document).ready(function() {
        initArtistQualifier();
    });

    /**
     * Initialize the artist qualifier functionality.
     */
    function initArtistQualifier() {
        const $quizForm = $('#vortex-artist-quiz-form');
        const $qualifierAgent = $('#agent-artist_qualifier');
        const $agentChat = $('#vortex-agent-chat');
        const $resultContainer = $('#quiz-result-container');
        
        // Register click event for the Artist Qualifier agent card
        if ($qualifierAgent.length) {
            $qualifierAgent.on('click', function() {
                openQualifierChat($(this));
            });
        }
        
        // Handle quiz form submission
        if ($quizForm.length) {
            $quizForm.on('submit', function(e) {
                e.preventDefault();
                submitQuizToAgent($(this));
            });
        }
        
        // Listen for messages from the agent chat
        $(document).on('vortex:agent_message_received', function(e, data) {
            if (data.agent_id === 'artist_qualifier') {
                handleAgentResponse(data.message);
            }
        });
    }
    
    /**
     * Open the chat with the Artist Qualifier agent.
     * 
     * @param {jQuery} $agentCard The agent card element.
     */
    function openQualifierChat($agentCard) {
        const agentId = $agentCard.data('agent-id');
        const agentName = $agentCard.data('agent-name');
        const agentGreeting = $agentCard.data('agent-greeting');
        
        // Show the chat modal
        const $chatModal = $('#vortex-agent-chat');
        $chatModal.addClass('active');
        
        // Set the active agent
        $chatModal.data('active-agent', agentId);
        $('#agent-chat-title').text(agentName);
        
        // Clear previous messages and add greeting
        const $messagesContainer = $('#agent-messages');
        $messagesContainer.empty();
        
        // Add greeting message
        $messagesContainer.append(
            `<div class="agent-message">
                <div class="agent-avatar">
                    <i class="fas fa-certificate"></i>
                </div>
                <div class="message-content">${agentGreeting}</div>
            </div>`
        );
        
        // Add quiz form if not already present
        if ($('#vortex-artist-quiz-form').length === 0) {
            $messagesContainer.append(createQuizForm());
            initFormBehaviors();
        }
        
        // Scroll to bottom of messages
        $messagesContainer.scrollTop($messagesContainer[0].scrollHeight);
    }
    
    /**
     * Create the quiz form HTML.
     * 
     * @return {string} The HTML for the quiz form.
     */
    function createQuizForm() {
        return `
            <div class="user-message">
                <div class="message-content">I'd like to take the artist qualification quiz.</div>
            </div>
            <div class="agent-message">
                <div class="agent-avatar">
                    <i class="fas fa-certificate"></i>
                </div>
                <div class="message-content">
                    Great! Please complete the following quiz to help me evaluate your artistic background and provide personalized feedback.
                    
                    <form id="vortex-artist-quiz-form" class="artist-qualifier-form">
                        <div class="form-group">
                            <label for="education">What is your artistic education background?</label>
                            <select id="education" name="education" required>
                                <option value="">Select an option</option>
                                <option value="formal_degree">Formal art degree (BFA, MFA, etc.)</option>
                                <option value="formal_courses">Formal art courses or workshops</option>
                                <option value="self_taught">Self-taught</option>
                                <option value="no_training">No formal training</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="self-taught-years-group" style="display:none;">
                            <label for="self_taught_years">How many years have you been practicing as a self-taught artist?</label>
                            <input type="number" id="self_taught_years" name="self_taught_years" min="0" value="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="style">What is your primary artistic style or focus?</label>
                            <select id="style" name="style" required>
                                <option value="">Select an option</option>
                                <option value="abstract">Abstract</option>
                                <option value="realistic">Realistic/Representational</option>
                                <option value="illustrative">Illustrative</option>
                                <option value="conceptual">Conceptual</option>
                                <option value="digital">Digital Art</option>
                                <option value="traditional">Traditional Media</option>
                                <option value="mixed_media">Mixed Media</option>
                                <option value="photography">Photography</option>
                                <option value="3d">3D/Sculpture</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="exhibitions">What exhibition experience do you have?</label>
                            <select id="exhibitions" name="exhibitions" required>
                                <option value="">Select an option</option>
                                <option value="gallery_featured">Featured in galleries/museums</option>
                                <option value="group_exhibitions">Participated in group exhibitions</option>
                                <option value="online_curated">Featured in curated online galleries</option>
                                <option value="social_media">Shared on social media only</option>
                                <option value="no_exhibitions">No exhibition experience</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="price_range">What is your typical price range for selling artwork?</label>
                            <select id="price_range" name="price_range" required>
                                <option value="">Select an option</option>
                                <option value="premium">Premium ($500+)</option>
                                <option value="mid_range">Mid-range ($100-$500)</option>
                                <option value="entry_level">Entry-level ($20-$100)</option>
                                <option value="no_sales">Haven't sold artwork yet</option>
                            </select>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="seed_art_commitment" name="seed_art_commitment" required>
                            <label for="seed_art_commitment">I commit to uploading at least two hand-crafted seed artworks weekly</label>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="button button-primary">Submit Quiz</button>
                        </div>
                        
                        <input type="hidden" name="action" value="vortex_analyze_quiz_responses">
                        <input type="hidden" name="security" value="${vortexAiAgents.nonce}">
                    </form>
                </div>
            </div>
        `;
    }
    
    /**
     * Initialize form behaviors like showing/hiding fields based on selections.
     */
    function initFormBehaviors() {
        const $educationSelect = $('#education');
        const $selfTaughtYears = $('#self-taught-years-group');
        
        // Show/hide self-taught years based on education selection
        $educationSelect.on('change', function() {
            if ($(this).val() === 'self_taught') {
                $selfTaughtYears.show();
            } else {
                $selfTaughtYears.hide();
            }
        });
        
        // Initialize the checkbox confirmation for seed art commitment
        $('#seed_art_commitment').on('change', function() {
            if ($(this).is(':checked')) {
                if (!confirm('By checking this box, you are committing to upload at least two hand-crafted artworks weekly to maintain your artist status. Do you agree to this commitment?')) {
                    $(this).prop('checked', false);
                }
            }
        });
    }
    
    /**
     * Submit the quiz to the Artist Qualifier agent.
     * 
     * @param {jQuery} $form The form element.
     */
    function submitQuizToAgent($form) {
        const $messagesContainer = $('#agent-messages');
        
        // Show loading message
        $messagesContainer.append(
            `<div class="agent-message loading">
                <div class="agent-avatar">
                    <i class="fas fa-certificate"></i>
                </div>
                <div class="message-content">
                    <div class="typing-indicator">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>`
        );
        
        // Scroll to bottom of messages
        $messagesContainer.scrollTop($messagesContainer[0].scrollHeight);
        
        // Disable form submission
        const $submitBtn = $form.find('button[type="submit"]');
        $submitBtn.prop('disabled', true);
        
        // Collect form data
        const formData = new FormData($form[0]);
        
        // Submit via AJAX
        $.ajax({
            url: vortexAiAgents.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // Remove loading message
                $('.agent-message.loading').remove();
                
                if (response.success) {
                    // Store result in local storage for potential redirect
                    localStorage.setItem('vortex_quiz_result', JSON.stringify(response.data));
                    
                    // Display the result
                    displayQuizResult(response.data);
                    
                    // Replace the form with a thank you message
                    $form.replaceWith(`
                        <div class="quiz-completed">
                            <p>Thank you for completing the artist qualification quiz!</p>
                            <p>You can retake the quiz anytime by chatting with the Artist Qualifier again.</p>
                        </div>
                    `);
                } else {
                    // Show error message
                    $messagesContainer.append(
                        `<div class="agent-message error">
                            <div class="agent-avatar">
                                <i class="fas fa-certificate"></i>
                            </div>
                            <div class="message-content">
                                ${response.data.message || 'There was an error processing your quiz. Please try again.'}
                            </div>
                        </div>`
                    );
                    
                    // Re-enable form submission
                    $submitBtn.prop('disabled', false);
                }
                
                // Scroll to bottom of messages
                $messagesContainer.scrollTop($messagesContainer[0].scrollHeight);
            },
            error: function() {
                // Remove loading message
                $('.agent-message.loading').remove();
                
                // Show error message
                $messagesContainer.append(
                    `<div class="agent-message error">
                        <div class="agent-avatar">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <div class="message-content">
                            There was an error processing your quiz. Please try again.
                        </div>
                    </div>`
                );
                
                // Re-enable form submission
                $submitBtn.prop('disabled', false);
                
                // Scroll to bottom of messages
                $messagesContainer.scrollTop($messagesContainer[0].scrollHeight);
            }
        });
    }
    
    /**
     * Display the quiz result in the chat.
     * 
     * @param {Object} result The quiz result data.
     */
    function displayQuizResult(result) {
        const $messagesContainer = $('#agent-messages');
        
        // Create tier badge based on result
        let tierBadge = '';
        if (result.tier === 'premium') {
            tierBadge = '<span class="tier-badge premium">Premium Tier</span>';
        } else if (result.tier === 'advanced') {
            tierBadge = '<span class="tier-badge advanced">Advanced Tier</span>';
        } else {
            tierBadge = '<span class="tier-badge standard">Standard Tier</span>';
        }
        
        // Add result message
        $messagesContainer.append(
            `<div class="agent-message result">
                <div class="agent-avatar">
                    <i class="fas fa-certificate"></i>
                </div>
                <div class="message-content">
                    <div class="result-header">
                        <h3>Your Artist Qualification Result</h3>
                        ${tierBadge}
                    </div>
                    <div class="result-feedback">
                        ${result.feedback}
                    </div>
                </div>
            </div>`
        );
        
        // Trigger custom event for other parts of the application
        $(document).trigger('vortex:qualification_result', [result]);
    }
    
    /**
     * Handle responses from the Artist Qualifier agent.
     * 
     * @param {string} message The message from the agent.
     */
    function handleAgentResponse(message) {
        // Check if this is a structured message (like a result)
        try {
            const data = JSON.parse(message);
            if (data.tier && data.feedback) {
                displayQuizResult(data);
                return;
            }
        } catch (e) {
            // Not JSON, treat as regular message
        }
        
        // If we get here, it's a regular text message
        const $messagesContainer = $('#agent-messages');
        $messagesContainer.append(
            `<div class="agent-message">
                <div class="agent-avatar">
                    <i class="fas fa-certificate"></i>
                </div>
                <div class="message-content">${message}</div>
            </div>`
        );
        
        // Scroll to bottom of messages
        $messagesContainer.scrollTop($messagesContainer[0].scrollHeight);
    }
    
    /**
     * Check for stored quiz results after page load (useful after redirects).
     */
    function checkStoredQuizResult() {
        const storedResult = localStorage.getItem('vortex_quiz_result');
        
        if (storedResult) {
            try {
                const result = JSON.parse(storedResult);
                
                // If we're on a result page, display the result
                const $resultContainer = $('#quiz-result-container');
                if ($resultContainer.length) {
                    // Create tier badge based on result
                    let tierBadge = '';
                    if (result.tier === 'premium') {
                        tierBadge = '<span class="tier-badge premium">Premium Tier</span>';
                    } else if (result.tier === 'advanced') {
                        tierBadge = '<span class="tier-badge advanced">Advanced Tier</span>';
                    } else {
                        tierBadge = '<span class="tier-badge standard">Standard Tier</span>';
                    }
                    
                    // Display the result
                    $resultContainer.html(
                        `<div class="qualification-result">
                            <div class="result-header">
                                <h3>Your Artist Qualification Result</h3>
                                ${tierBadge}
                            </div>
                            <div class="result-feedback">
                                ${result.feedback}
                            </div>
                        </div>`
                    );
                    
                    // Clear the stored result
                    localStorage.removeItem('vortex_quiz_result');
                }
            } catch (e) {
                console.error('Error parsing stored quiz result', e);
                localStorage.removeItem('vortex_quiz_result');
            }
        }
    }
    
    // Check for stored results when page loads
    $(window).on('load', checkStoredQuizResult);

})(jQuery); 