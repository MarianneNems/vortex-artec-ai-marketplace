/**
 * AI Agents JavaScript for the Vortex AI Marketplace
 *
 * @link       https://aimarketplace.vortex-it.com/
 * @since      1.0.0
 */

(function($) {
    'use strict';

    // Store the current active agent
    let currentAgent = null;
    let chatHistory = {};

    // Initialize when document is ready
    $(document).ready(function() {
        initializeAgents();
    });

    /**
     * Initialize agent card event listeners and chat functionality
     */
    function initializeAgents() {
        // Click event for agent cards
        $('.vortex-agent-card').on('click', function() {
            const agentId = $(this).data('agent-id');
            openChatModal(agentId);
        });

        // Close chat modal
        $('.vortex-chat-close').on('click', function() {
            closeChatModal();
        });

        // Close modal when clicking outside
        $('.vortex-chat-modal').on('click', function(e) {
            if ($(e.target).hasClass('vortex-chat-modal')) {
                closeChatModal();
            }
        });

        // Handle chat form submission
        $('#vortex-chat-form').on('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });

        // Send message on Enter key, but allow Shift+Enter for new line
        $('#vortex-chat-input').on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Send button click
        $('.vortex-chat-send').on('click', function() {
            sendMessage();
        });
    }

    /**
     * Open the chat modal for a specific agent
     * 
     * @param {string} agentId The ID of the agent to chat with
     */
    function openChatModal(agentId) {
        currentAgent = agentId;
        
        // Get agent details
        const $agentCard = $(`.vortex-agent-card[data-agent-id="${agentId}"]`);
        const agentName = $agentCard.data('agent-name');
        const agentIcon = $agentCard.data('agent-icon');
        
        // Update modal with agent details
        $('.vortex-chat-title').text(agentName);
        $('.vortex-chat-header-icon i').attr('class', agentIcon);
        
        // Clear input field
        $('#vortex-chat-input').val('');
        
        // Show modal
        $('.vortex-chat-modal').addClass('active');
        $('#vortex-chat-input').focus();
        
        // Load chat history if exists
        loadChatHistory(agentId);
        
        // If no messages, send greeting
        if (!chatHistory[agentId] || chatHistory[agentId].length === 0) {
            const greeting = $agentCard.data('agent-greeting');
            if (greeting) {
                displayAgentMessage(greeting);
                saveChatMessage(agentId, 'agent', greeting);
            }
        }
    }

    /**
     * Close the chat modal
     */
    function closeChatModal() {
        $('.vortex-chat-modal').removeClass('active');
        currentAgent = null;
    }
    
    /**
     * Load chat history for a specific agent
     * 
     * @param {string} agentId The ID of the agent
     */
    function loadChatHistory(agentId) {
        const $chatMessages = $('.vortex-chat-messages');
        $chatMessages.empty();
        
        // Initialize history array if not exists
        if (!chatHistory[agentId]) {
            chatHistory[agentId] = [];
            return;
        }
        
        // Display each message from history
        chatHistory[agentId].forEach(function(message) {
            if (message.type === 'user') {
                displayUserMessage(message.content);
            } else {
                displayAgentMessage(message.content);
            }
        });
        
        // Scroll to bottom
        scrollToBottom();
    }

    /**
     * Send a message to the agent
     */
    function sendMessage() {
        const $input = $('#vortex-chat-input');
        const message = $input.val().trim();
        
        if (!message || !currentAgent) {
            return;
        }
        
        // Display user message
        displayUserMessage(message);
        
        // Save to history
        saveChatMessage(currentAgent, 'user', message);
        
        // Clear input
        $input.val('');
        
        // Show loading indicator
        showLoading();
        
        // Send to server
        $.ajax({
            url: vortex_agents.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_handle_agent_message',
                agent_id: currentAgent,
                message: message,
                security: vortex_agents.nonce
            },
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    displayAgentMessage(response.data.response);
                    saveChatMessage(currentAgent, 'agent', response.data.response);
                } else {
                    displayAgentMessage("I'm sorry, I'm having trouble processing your request. Please try again later.");
                }
                
                scrollToBottom();
            },
            error: function() {
                hideLoading();
                displayAgentMessage("I'm sorry, there was an error communicating with the server. Please try again later.");
                scrollToBottom();
            }
        });
    }

    /**
     * Display a user message in the chat
     * 
     * @param {string} message The message to display
     */
    function displayUserMessage(message) {
        const $chatMessages = $('.vortex-chat-messages');
        const $message = $('<div class="vortex-chat-message user"></div>');
        
        // Replace URLs with clickable links
        message = linkifyText(message);
        
        $message.html(message);
        $chatMessages.append($message);
        scrollToBottom();
    }

    /**
     * Display an agent message in the chat
     * 
     * @param {string} message The message to display
     */
    function displayAgentMessage(message) {
        const $chatMessages = $('.vortex-chat-messages');
        const $message = $('<div class="vortex-chat-message agent"></div>');
        
        // Replace URLs with clickable links
        message = linkifyText(message);
        
        $message.html(message);
        $chatMessages.append($message);
        scrollToBottom();
    }

    /**
     * Save a chat message to history
     * 
     * @param {string} agentId The ID of the agent
     * @param {string} type The message type ('user' or 'agent')
     * @param {string} content The message content
     */
    function saveChatMessage(agentId, type, content) {
        if (!chatHistory[agentId]) {
            chatHistory[agentId] = [];
        }
        
        chatHistory[agentId].push({
            type: type,
            content: content,
            timestamp: new Date().getTime()
        });
        
        // Limit history to 50 messages per agent
        if (chatHistory[agentId].length > 50) {
            chatHistory[agentId].shift();
        }
    }

    /**
     * Show the loading indicator
     */
    function showLoading() {
        const $chatMessages = $('.vortex-chat-messages');
        const $loading = $('.vortex-chat-loading').clone().show();
        $chatMessages.append($loading);
        scrollToBottom();
    }

    /**
     * Hide the loading indicator
     */
    function hideLoading() {
        $('.vortex-chat-messages .vortex-chat-loading').remove();
    }

    /**
     * Scroll the chat messages to the bottom
     */
    function scrollToBottom() {
        const $chatMessages = $('.vortex-chat-messages');
        $chatMessages.scrollTop($chatMessages[0].scrollHeight);
    }

    /**
     * Convert URLs in text to clickable links
     * 
     * @param {string} text The text to process
     * @return {string} The text with URLs converted to links
     */
    function linkifyText(text) {
        const urlRegex = /(https?:\/\/[^\s]+)/g;
        return text.replace(urlRegex, function(url) {
            return '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + url + '</a>';
        });
    }

})(jQuery); 