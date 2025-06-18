/**
 * Enhanced JavaScript for Vortex AI Agents
 * 
 * Handles the agent chat functionality including:
 * - Opening/closing the chat modal
 * - Sending/receiving messages
 * - Displaying typing indicators
 * - Chat history management
 * - Message suggestions
 * - Feedback system
 * - Markdown formatting support
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        const $agentCards = $('.vortex-agent-card');
        const $chatModal = $('.vortex-agent-chat-modal');
        const $chatMessages = $('.vortex-agent-chat-messages');
        const $chatName = $('.vortex-agent-chat-name');
        const $chatClose = $('.vortex-agent-chat-close');
        const $chatInput = $('.vortex-agent-chat-input textarea');
        const $chatSend = $('.vortex-agent-chat-send');
        const $suggestionContainer = $('<div>').addClass('vortex-message-suggestions').insertBefore($chatInput.parent());
        const $feedbackContainer = $('<div>').addClass('vortex-message-feedback').hide();
        
        let currentAgentId = '';
        let currentAgentName = '';
        let conversationHistory = {};
        let lastAgentMessageId = null;
        
        // Initialize conversation history from local storage if available
        try {
            const storedHistory = localStorage.getItem('vortexAgentConversations');
            if (storedHistory) {
                conversationHistory = JSON.parse(storedHistory);
            }
        } catch (e) {
            console.error('Error loading conversation history:', e);
            conversationHistory = {};
        }
        
        // Open chat modal when clicking on an agent card
        $agentCards.on('click', function() {
            currentAgentId = $(this).data('agent-id');
            currentAgentName = $(this).data('agent-name');
            
            $chatName.text(currentAgentName);
            $chatModal.addClass('active');
            $chatInput.focus();
            
            // Clear messages
            $chatMessages.empty();
            
            // Load conversation history if available
            if (conversationHistory[currentAgentId] && conversationHistory[currentAgentId].length > 0) {
                conversationHistory[currentAgentId].forEach(function(message) {
                    addMessageToChat(message.sender, message.text, message.id);
                });
                
                // Show suggested follow-ups based on last agent message
                const lastMessage = conversationHistory[currentAgentId][conversationHistory[currentAgentId].length - 1];
                if (lastMessage.sender === 'agent') {
                    showSuggestions(currentAgentId, lastMessage.text);
                }
            } else {
                // Add welcome message
                const welcomeMessage = getWelcomeMessage(currentAgentId);
                showTypingIndicator();
                
                setTimeout(function() {
                    hideTypingIndicator();
                    const messageId = 'msg-' + Date.now();
                    addMessageToChat('agent', welcomeMessage, messageId);
                    saveMessageToHistory('agent', welcomeMessage, messageId);
                    showSuggestions(currentAgentId, welcomeMessage);
                }, 1000);
            }
        });
        
        // Close chat modal
        $chatClose.on('click', function() {
            $chatModal.removeClass('active');
            $suggestionContainer.empty();
            saveConversationHistory();
        });
        
        // Send message when clicking send button
        $chatSend.on('click', function() {
            sendMessage();
        });
        
        // Send message when pressing Enter (but allow Shift+Enter for new lines)
        $chatInput.on('keydown', function(e) {
            if (e.keyCode === 13 && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        // Function to send message
        function sendMessage() {
            const message = $chatInput.val().trim();
            
            if (message === '') {
                return;
            }
            
            // Hide suggestions when sending a new message
            $suggestionContainer.empty();
            
            // Add user message to chat
            const userMessageId = 'msg-' + Date.now();
            addMessageToChat('user', message, userMessageId);
            saveMessageToHistory('user', message, userMessageId);
            
            // Clear input
            $chatInput.val('');
            
            // Show typing indicator
            showTypingIndicator();
            
            // Process message and get response from server
            $.ajax({
                url: vortexAIAgents.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_ai_agent_message',
                    agent_id: currentAgentId,
                    message: message,
                    security: vortexAIAgents.security,
                    history: JSON.stringify(getRecentHistory(currentAgentId, 5))
                },
                success: function(response) {
                    if (response.success) {
                        setTimeout(function() {
                            hideTypingIndicator();
                            lastAgentMessageId = 'msg-' + Date.now();
                            addMessageToChat('agent', response.data.message, lastAgentMessageId);
                            saveMessageToHistory('agent', response.data.message, lastAgentMessageId);
                            
                            // Add feedback options
                            addFeedbackOptions(lastAgentMessageId);
                            
                            // Show message suggestions
                            showSuggestions(currentAgentId, response.data.message);
                        }, getRandomTypingDelay(response.data.message));
                    } else {
                        hideTypingIndicator();
                        lastAgentMessageId = 'msg-' + Date.now();
                        addMessageToChat('agent', 'Sorry, I encountered an error. Please try again later.', lastAgentMessageId);
                    }
                },
                error: function() {
                    hideTypingIndicator();
                    lastAgentMessageId = 'msg-' + Date.now();
                    addMessageToChat('agent', 'Sorry, there was a communication error. Please try again later.', lastAgentMessageId);
                }
            });
        }
        
        // Function to add message to chat
        function addMessageToChat(sender, text, messageId) {
            const messageClass = sender === 'agent' ? 'vortex-agent-message' : 'vortex-user-message';
            const $message = $('<div>').addClass('vortex-chat-message ' + messageClass).attr('id', messageId);
            
            // Process markdown in messages (for agent messages only)
            if (sender === 'agent') {
                // Bold text
                text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                
                // Italic text
                text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
                
                // Links
                text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>');
                
                // Unformatted links
                text = text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank">$1</a>');
                
                // Lists
                text = text.replace(/^- (.*?)$/gm, '<li>$1</li>');
                text = text.replace(/<li>.*?<\/li>/gs, function(match) {
                    return '<ul>' + match + '</ul>';
                });
            } else {
                // Just format links for user messages
                text = text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank">$1</a>');
            }
            
            $message.html(text);
            $chatMessages.append($message);
            
            // Scroll to bottom
            $chatMessages.scrollTop($chatMessages[0].scrollHeight);
            
            return $message;
        }
        
        // Function to add feedback options to agent messages
        function addFeedbackOptions(messageId) {
            const $message = $('#' + messageId);
            const $feedbackButtons = $('<div>').addClass('vortex-message-feedback-buttons');
            
            const $helpfulBtn = $('<button>').addClass('vortex-feedback-helpful').html('<i class="fas fa-thumbs-up"></i>');
            const $unhelpfulBtn = $('<button>').addClass('vortex-feedback-unhelpful').html('<i class="fas fa-thumbs-down"></i>');
            
            $feedbackButtons.append($helpfulBtn).append($unhelpfulBtn);
            $message.append($feedbackButtons);
            
            // Handle feedback clicks
            $helpfulBtn.on('click', function() {
                sendFeedback(messageId, 'helpful');
                $feedbackButtons.hide();
                $message.append($('<span>').addClass('vortex-feedback-thanks').text('Thanks for your feedback!'));
            });
            
            $unhelpfulBtn.on('click', function() {
                sendFeedback(messageId, 'unhelpful');
                $feedbackButtons.hide();
                
                const $feedbackInput = $('<textarea>').attr('placeholder', 'Tell us how we could improve this response (optional)');
                const $submitBtn = $('<button>').text('Submit').addClass('vortex-feedback-submit');
                const $skipBtn = $('<button>').text('Skip').addClass('vortex-feedback-skip');
                const $feedbackForm = $('<div>').addClass('vortex-feedback-form')
                    .append($feedbackInput)
                    .append($('<div>').addClass('vortex-feedback-buttons')
                        .append($submitBtn)
                        .append($skipBtn));
                
                $message.append($feedbackForm);
                
                $submitBtn.on('click', function() {
                    sendFeedback(messageId, 'unhelpful', $feedbackInput.val());
                    $feedbackForm.hide();
                    $message.append($('<span>').addClass('vortex-feedback-thanks').text('Thanks for your feedback!'));
                });
                
                $skipBtn.on('click', function() {
                    $feedbackForm.hide();
                    $message.append($('<span>').addClass('vortex-feedback-thanks').text('Thanks for your feedback!'));
                });
            });
        }
        
        // Function to send feedback to server
        function sendFeedback(messageId, rating, comment = '') {
            $.ajax({
                url: vortexAIAgents.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_ai_agent_feedback',
                    agent_id: currentAgentId,
                    message_id: messageId,
                    rating: rating,
                    comment: comment,
                    security: vortexAIAgents.security
                },
                success: function(response) {
                    console.log('Feedback sent:', response);
                },
                error: function() {
                    console.error('Error sending feedback');
                }
            });
        }
        
        // Function to show message suggestions
        function showSuggestions(agentId, lastMessage) {
            $suggestionContainer.empty();
            
            // Generate suggestions based on agent type and last message
            const suggestions = generateSuggestions(agentId, lastMessage);
            
            if (suggestions.length === 0) {
                return;
            }
            
            // Add heading
            $suggestionContainer.append($('<div>').addClass('suggestion-heading').text('Suggested questions:'));
            
            // Add each suggestion
            suggestions.forEach(function(suggestion) {
                const $suggestion = $('<button>').addClass('vortex-suggestion-btn').text(suggestion);
                $suggestionContainer.append($suggestion);
                
                // Handle click on suggestion
                $suggestion.on('click', function() {
                    $chatInput.val(suggestion);
                    sendMessage();
                });
            });
        }
        
        // Function to generate suggestions based on agent type and last message
        function generateSuggestions(agentId, lastMessage) {
            const commonSuggestions = [
                'Can you explain more?',
                'Why is this important?',
                'How does this work?'
            ];
            
            const agentSpecificSuggestions = {
                'artwork_advisor': [
                    'How can I improve my portfolio?',
                    'What styles are trending right now?',
                    'How should I price my artwork?',
                    'What makes artwork sell well?',
                    'How can I make my artwork stand out?'
                ],
                'marketplace_guide': [
                    'How do I find specific artwork?',
                    'What are the marketplace fees?',
                    'How do I become a featured artist?',
                    'What are the best selling categories?',
                    'How do I contact an artist?'
                ],
                'prompt_engineer': [
                    'How do I write effective prompts?',
                    'What makes a good negative prompt?',
                    'Can you help me improve this prompt?',
                    'How do I achieve a specific style?',
                    'What parameters should I adjust for better results?'
                ],
                'community_assistant': [
                    'Are there any upcoming events?',
                    'How can I participate in challenges?',
                    'Where can I find other artists to collaborate with?',
                    'How do I join artist groups?',
                    'What community resources are available?'
                ],
                'technical_support': [
                    'Why did my upload fail?',
                    'How do I update my profile?',
                    'I can\'t log in, what should I do?',
                    'How do I withdraw my earnings?',
                    'Is there a file size limit for uploads?'
                ]
            };
            
            // Pick 3 random suggestions from agent-specific list
            const specificSuggestions = agentSpecificSuggestions[agentId] || [];
            const randomSpecific = specificSuggestions
                .sort(() => 0.5 - Math.random())
                .slice(0, 3);
            
            // Add 1 common suggestion
            const randomCommon = commonSuggestions
                .sort(() => 0.5 - Math.random())
                .slice(0, 1);
            
            return [...randomSpecific, ...randomCommon];
        }
        
        // Function to save message to history
        function saveMessageToHistory(sender, text, id) {
            if (!conversationHistory[currentAgentId]) {
                conversationHistory[currentAgentId] = [];
            }
            
            conversationHistory[currentAgentId].push({
                id: id,
                sender: sender,
                text: text,
                timestamp: new Date().toISOString()
            });
            
            // Limit history to last 50 messages per agent
            if (conversationHistory[currentAgentId].length > 50) {
                conversationHistory[currentAgentId] = conversationHistory[currentAgentId].slice(-50);
            }
            
            saveConversationHistory();
        }
        
        // Function to get recent history for context
        function getRecentHistory(agentId, count) {
            if (!conversationHistory[agentId]) {
                return [];
            }
            
            return conversationHistory[agentId].slice(-count);
        }
        
        // Function to save conversation history to local storage
        function saveConversationHistory() {
            try {
                localStorage.setItem('vortexAgentConversations', JSON.stringify(conversationHistory));
            } catch (e) {
                console.error('Error saving conversation history:', e);
            }
        }
        
        // Function to show typing indicator
        function showTypingIndicator() {
            const $typingIndicator = $('<div>').addClass('vortex-typing-indicator vortex-agent-message');
            $typingIndicator.html('<span></span><span></span><span></span>');
            $chatMessages.append($typingIndicator);
            $chatMessages.scrollTop($chatMessages[0].scrollHeight);
        }
        
        // Function to hide typing indicator
        function hideTypingIndicator() {
            $('.vortex-typing-indicator').remove();
        }
        
        // Function to get random typing delay based on message length
        function getRandomTypingDelay(message) {
            const baseDelay = 500;
            const charsPerSecond = 20;
            const variability = 0.3; // 30% variability
            
            let delay = baseDelay + (message.length / charsPerSecond) * 1000;
            const randomFactor = 1 + (Math.random() * variability * 2 - variability);
            
            return Math.min(delay * randomFactor, 5000); // Cap at 5 seconds
        }
        
        // Function to get welcome message based on agent ID
        function getWelcomeMessage(agentId) {
            const welcomeMessages = {
                'artwork_advisor': 'Hi there! I\'m your Artwork Advisor. I can help you optimize your portfolio and give advice on selling your artwork. What would you like to know?',
                'marketplace_guide': 'Welcome! I\'m the Marketplace Guide. I can help you navigate the marketplace, understand prompts, and find the right artwork. How can I assist you today?',
                'prompt_engineer': 'Hello! I\'m your Prompt Engineer assistant. I can help you craft effective prompts for AI art generation. What kind of artwork are you looking to create?',
                'community_assistant': 'Hi! I\'m the Community Assistant. I can tell you about events, challenges, and ways to connect with other artists in our community. What are you interested in?',
                'technical_support': 'Welcome to technical support. I can help with issues related to uploads, marketplace features, or general technical questions. What can I help you with today?'
            };
            
            return welcomeMessages[agentId] || 'Hello! How can I help you today?';
        }
        
        // Add clear conversation button
        const $clearButton = $('<button>')
            .addClass('vortex-clear-conversation')
            .html('<i class="fas fa-trash"></i> Clear Conversation')
            .insertAfter($chatClose);
            
        $clearButton.on('click', function(e) {
            e.stopPropagation();
            
            if (confirm('Are you sure you want to clear this conversation? This cannot be undone.')) {
                // Clear messages from display
                $chatMessages.empty();
                
                // Clear from history
                conversationHistory[currentAgentId] = [];
                saveConversationHistory();
                
                // Start fresh with welcome message
                const welcomeMessage = getWelcomeMessage(currentAgentId);
                showTypingIndicator();
                
                setTimeout(function() {
                    hideTypingIndicator();
                    const messageId = 'msg-' + Date.now();
                    addMessageToChat('agent', welcomeMessage, messageId);
                    saveMessageToHistory('agent', welcomeMessage, messageId);
                    showSuggestions(currentAgentId, welcomeMessage);
                }, 1000);
            }
        });
    });
})(jQuery); 