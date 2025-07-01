/**
 * JavaScript for handling AI agent interactions in the Vortex AI Marketplace.
 *
 * @link       https://vortexai.io
 * @since      1.0.0
 *
 * @package    Vortex
 * @subpackage Vortex/js
 */

(function($) {
    'use strict';

    // Store the current active agent
    let currentAgent = null;
    // Store the conversation history
    let conversations = {};
    
    $(document).ready(function() {
        // Initialize agent conversations
        $('.vortex-agent-card').each(function() {
            const agentId = $(this).data('agent-id');
            conversations[agentId] = [];
        });
        
        // Handle agent card click
        $('.vortex-agent-card').on('click', function() {
            currentAgent = $(this).data('agent-id');
            
            // Update modal title and icon
            const agentName = $(this).data('agent-name');
            const agentIcon = $(this).data('agent-icon');
            
            $('#vortex-chat-modal-title').text(agentName);
            $('#vortex-chat-modal-icon').attr('class', agentIcon);
            
            // Clear message area and load conversation history
            $('#vortex-chat-messages').empty();
            
            // Display conversation history if any
            if (conversations[currentAgent].length > 0) {
                conversations[currentAgent].forEach(function(message) {
                    appendMessage(message.sender, message.content);
                });
            } else {
                // Display greeting message
                const greeting = $(this).data('agent-greeting');
                appendMessage('agent', greeting);
                
                // Add to conversation history
                conversations[currentAgent].push({
                    sender: 'agent',
                    content: greeting
                });
            }
            
            // Show the modal
            $('#vortex-chat-modal').show();
        });
        
        // Close modal button
        $('.vortex-chat-close').on('click', function() {
            $('#vortex-chat-modal').hide();
        });
        
        // Also close on click outside content area
        $(window).on('click', function(event) {
            if ($(event.target).is('#vortex-chat-modal')) {
                $('#vortex-chat-modal').hide();
            }
        });
        
        // Handle message submission
        $('#vortex-chat-form').on('submit', function(e) {
            e.preventDefault();
            
            const userMessage = $('#vortex-chat-input').val().trim();
            
            if (userMessage === '' || !currentAgent) {
                return;
            }
            
            // Add user message to chat
            appendMessage('user', userMessage);
            
            // Add to conversation history
            conversations[currentAgent].push({
                sender: 'user',
                content: userMessage
            });
            
            // Clear input
            $('#vortex-chat-input').val('');
            
            // Show loading indicator
            appendMessage('loading', '');
            
            // Send to server
            $.ajax({
                type: 'POST',
                url: vortex_agent_data.ajax_url,
                data: {
                    action: 'vortex_agent_message',
                    nonce: vortex_agent_data.nonce,
                    agent_id: currentAgent,
                    message: userMessage
                },
                success: function(response) {
                    // Remove loading indicator
                    $('.vortex-chat-message.loading').remove();
                    
                    if (response.success) {
                        // Display agent response
                        appendMessage('agent', response.data.response);
                        
                        // Add to conversation history
                        conversations[currentAgent].push({
                            sender: 'agent',
                            content: response.data.response
                        });
                    } else {
                        // Display error
                        appendMessage('error', 'Sorry, there was an error processing your request.');
                    }
                },
                error: function() {
                    // Remove loading indicator
                    $('.vortex-chat-message.loading').remove();
                    
                    // Display error
                    appendMessage('error', 'Sorry, there was a connection error. Please try again later.');
                }
            });
        });
    });
    
    /**
     * Append a message to the chat area
     * 
     * @param {string} sender - 'user', 'agent', 'error', or 'loading'
     * @param {string} message - The message content
     */
    function appendMessage(sender, message) {
        const messageArea = $('#vortex-chat-messages');
        let messageClass = 'vortex-chat-message';
        let messageContent = '';
        
        switch (sender) {
            case 'user':
                messageClass += ' user-message';
                messageContent = `<div class="message-content">${message}</div>`;
                break;
                
            case 'agent':
                messageClass += ' agent-message';
                messageContent = `<div class="message-content">${message}</div>`;
                break;
                
            case 'error':
                messageClass += ' error-message';
                messageContent = `<div class="message-content">${message}</div>`;
                break;
                
            case 'loading':
                messageClass += ' loading';
                messageContent = `<div class="loading-indicator"><span></span><span></span><span></span></div>`;
                break;
        }
        
        messageArea.append(`<div class="${messageClass}">${messageContent}</div>`);
        
        // Scroll to bottom
        messageArea.scrollTop(messageArea[0].scrollHeight);
    }

})(jQuery); 