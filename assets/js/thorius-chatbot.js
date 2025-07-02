/**
 * THORIUS Chatbot JavaScript
 * Real-time chat interface with vault-locked AI core
 */

jQuery(document).ready(function($) {
    'use strict';
    
    const ThoruisChatbot = {
        
        // Configuration
        config: {
            maxMessageLength: 500,
            typingDelay: 1500,
            autoScrollDelay: 100,
            sessionStartTime: Date.now()
        },
        
        // Elements
        elements: {
            toggle: $('#thorius-chatbot-toggle'),
            container: $('#thorius-chatbot-container'),
            messages: $('#thorius-chatbot-messages'),
            input: $('#thorius-message-input'),
            sendBtn: $('#thorius-send-btn'),
            quickBtns: $('.thorius-quick-btn'),
            minimizeBtn: $('#thorius-minimize'),
            closeBtn: $('#thorius-close'),
            notificationBadge: $('.thorius-notification-badge')
        },
        
        // State
        state: {
            isOpen: false,
            isMinimized: false,
            isTyping: false,
            messageHistory: [],
            unreadCount: 0
        },
        
        /**
         * Initialize chatbot
         */
        init: function() {
            this.bindEvents();
            this.showWelcomeMessage();
            this.startHeartbeat();
        },
        
        /**
         * Bind event listeners
         */
        bindEvents: function() {
            const self = this;
            
            // Toggle chatbot
            self.elements.toggle.on('click', function() {
                self.toggleChatbot();
            });
            
            // Minimize/Close buttons
            self.elements.minimizeBtn.on('click', function() {
                self.minimizeChatbot();
            });
            
            self.elements.closeBtn.on('click', function() {
                self.closeChatbot();
            });
            
            // Send message
            self.elements.sendBtn.on('click', function() {
                self.sendMessage();
            });
            
            // Enter key to send
            self.elements.input.on('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    self.sendMessage();
                }
            });
            
            // Quick action buttons
            self.elements.quickBtns.on('click', function() {
                const message = $(this).data('message');
                self.elements.input.val(message);
                self.sendMessage();
            });
            
            // Input character limit
            self.elements.input.on('input', function() {
                const length = $(this).val().length;
                if (length > self.config.maxMessageLength) {
                    $(this).val($(this).val().substring(0, self.config.maxMessageLength));
                }
            });
            
            // Click outside to close
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#thorius-chatbot-container, #thorius-chatbot-toggle').length) {
                    if (self.state.isOpen && !self.state.isMinimized) {
                        // Don't auto-close, just minimize
                        // self.minimizeChatbot();
                    }
                }
            });
        },
        
        /**
         * Toggle chatbot visibility
         */
        toggleChatbot: function() {
            if (this.state.isOpen) {
                if (this.state.isMinimized) {
                    this.restoreChatbot();
                } else {
                    this.minimizeChatbot();
                }
            } else {
                this.openChatbot();
            }
        },
        
        /**
         * Open chatbot
         */
        openChatbot: function() {
            this.elements.container.removeClass('thorius-hidden');
            this.state.isOpen = true;
            this.state.isMinimized = false;
            this.clearNotifications();
            this.focusInput();
            this.scrollToBottom();
        },
        
        /**
         * Close chatbot
         */
        closeChatbot: function() {
            this.elements.container.addClass('thorius-hidden');
            this.state.isOpen = false;
            this.state.isMinimized = false;
        },
        
        /**
         * Minimize chatbot
         */
        minimizeChatbot: function() {
            this.elements.container.addClass('thorius-hidden');
            this.state.isMinimized = true;
        },
        
        /**
         * Restore minimized chatbot
         */
        restoreChatbot: function() {
            this.elements.container.removeClass('thorius-hidden');
            this.state.isMinimized = false;
            this.focusInput();
            this.scrollToBottom();
        },
        
        /**
         * Send message to THORIUS
         */
        sendMessage: function() {
            const message = this.elements.input.val().trim();
            
            if (!message || this.state.isTyping) {
                return;
            }
            
            // Add user message to chat
            this.addMessage(message, 'user');
            
            // Clear input
            this.elements.input.val('');
            
            // Show typing indicator
            this.showTypingIndicator();
            
            // Send to vault-locked THORIUS
            this.sendToThorius(message);
        },
        
        /**
         * Send message to vault-locked THORIUS core
         */
        sendToThorius: function(message) {
            const self = this;
            
            const requestData = {
                action: 'thorius_chat',
                message: message,
                nonce: thorius_ajax.nonce,
                current_page: window.location.href,
                session_duration: Math.floor((Date.now() - this.config.sessionStartTime) / 1000)
            };
            
            $.ajax({
                url: thorius_ajax.ajax_url,
                type: 'POST',
                data: requestData,
                timeout: 30000,
                success: function(response) {
                    self.hideTypingIndicator();
                    
                    if (response.success && response.data) {
                        self.handleThoruisResponse(response.data);
                    } else {
                        self.addMessage('I apologize, but I\'m having trouble connecting right now. Please try again in a moment.', 'bot', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    self.hideTypingIndicator();
                    console.error('THORIUS chat error:', error);
                    
                    let errorMessage = 'I\'m experiencing technical difficulties. ';
                    if (status === 'timeout') {
                        errorMessage += 'The connection timed out. Please try a shorter message.';
                    } else {
                        errorMessage += 'Please try again in a moment.';
                    }
                    
                    self.addMessage(errorMessage, 'bot', 'error');
                }
            });
        },
        
        /**
         * Handle response from THORIUS
         */
        handleThoruisResponse: function(data) {
            // Add THORIUS response
            const responseType = data.response_type || 'info';
            this.addMessage(data.response, 'bot', responseType);
            
            // Add suggested actions if provided
            if (data.suggested_actions && data.suggested_actions.length > 0) {
                this.addSuggestedActions(data.suggested_actions);
            }
            
            // Handle escalation if needed
            if (data.escalation_needed) {
                this.handleEscalation();
            }
        },
        
        /**
         * Add message to chat
         */
        addMessage: function(text, sender, type = 'info') {
            const timestamp = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            const messageClass = `thorius-message thorius-${sender}`;
            const typeClass = type !== 'info' ? ` thorius-${type}-message` : '';
            
            let messageHtml = '';
            
            if (sender === 'bot') {
                messageHtml = `
                    <div class="${messageClass}">
                        <div class="thorius-message-avatar">T</div>
                        <div>
                            <div class="thorius-message-content${typeClass}">${this.formatMessage(text)}</div>
                            <div class="thorius-message-time">${timestamp}</div>
                        </div>
                    </div>
                `;
            } else {
                messageHtml = `
                    <div class="${messageClass}">
                        <div>
                            <div class="thorius-message-content">${this.escapeHtml(text)}</div>
                            <div class="thorius-message-time">${timestamp}</div>
                        </div>
                    </div>
                `;
            }
            
            this.elements.messages.append(messageHtml);
            this.state.messageHistory.push({text, sender, timestamp, type});
            
            // Auto-scroll to bottom
            setTimeout(() => {
                this.scrollToBottom();
            }, this.config.autoScrollDelay);
            
            // Update notifications if chat is closed
            if (!this.state.isOpen || this.state.isMinimized) {
                if (sender === 'bot') {
                    this.incrementNotifications();
                }
            }
        },
        
        /**
         * Add suggested actions
         */
        addSuggestedActions: function(actions) {
            const self = this;
            let actionsHtml = '<div class="thorius-suggested-actions">';
            
            actions.forEach(function(action) {
                actionsHtml += `<button class="thorius-action-btn" data-action="${action.action}">${action.label}</button>`;
            });
            
            actionsHtml += '</div>';
            
            this.elements.messages.append(actionsHtml);
            
            // Bind action buttons
            $('.thorius-action-btn').off('click').on('click', function() {
                const actionText = $(this).text();
                self.elements.input.val(actionText);
                self.sendMessage();
                $(this).parent().remove(); // Remove suggested actions after use
            });
            
            this.scrollToBottom();
        },
        
        /**
         * Show typing indicator
         */
        showTypingIndicator: function() {
            if (this.state.isTyping) return;
            
            this.state.isTyping = true;
            this.elements.sendBtn.prop('disabled', true);
            
            const typingHtml = `
                <div class="thorius-typing-indicator" id="thorius-typing">
                    <div class="thorius-message-avatar">T</div>
                    <div class="thorius-typing-dots">
                        <div class="thorius-typing-dot"></div>
                        <div class="thorius-typing-dot"></div>
                        <div class="thorius-typing-dot"></div>
                    </div>
                </div>
            `;
            
            this.elements.messages.append(typingHtml);
            this.scrollToBottom();
        },
        
        /**
         * Hide typing indicator
         */
        hideTypingIndicator: function() {
            this.state.isTyping = false;
            this.elements.sendBtn.prop('disabled', false);
            $('#thorius-typing').remove();
        },
        
        /**
         * Show welcome message
         */
        showWelcomeMessage: function() {
            setTimeout(() => {
                const welcomeText = "Hello! I'm THORIUS, your platform concierge. 👋\n\nI'm here to help guide you through the VORTEX platform, answer questions, and ensure you have the best creative experience possible.\n\nHow can I assist you today?";
                this.addMessage(welcomeText, 'bot', 'success');
            }, 1000);
        },
        
        /**
         * Handle escalation to human support
         */
        handleEscalation: function() {
            const escalationMessage = "I've flagged this conversation for priority human support. A team member will reach out to you shortly. 🚀";
            this.addMessage(escalationMessage, 'bot', 'warning');
        },
        
        /**
         * Format message text (basic formatting)
         */
        formatMessage: function(text) {
            return this.escapeHtml(text)
                .replace(/\n/g, '<br>')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/`(.*?)`/g, '<code>$1</code>');
        },
        
        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        /**
         * Scroll to bottom of messages
         */
        scrollToBottom: function() {
            this.elements.messages.scrollTop(this.elements.messages[0].scrollHeight);
        },
        
        /**
         * Focus input field
         */
        focusInput: function() {
            setTimeout(() => {
                this.elements.input.focus();
            }, 100);
        },
        
        /**
         * Clear notifications
         */
        clearNotifications: function() {
            this.state.unreadCount = 0;
            this.elements.notificationBadge.addClass('thorius-hidden');
        },
        
        /**
         * Increment notification count
         */
        incrementNotifications: function() {
            this.state.unreadCount++;
            this.elements.notificationBadge.text(this.state.unreadCount).removeClass('thorius-hidden');
        },
        
        /**
         * Start heartbeat to maintain connection
         */
        startHeartbeat: function() {
            setInterval(() => {
                // Ping server to maintain session
                $.ajax({
                    url: thorius_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'thorius_heartbeat',
                        nonce: thorius_ajax.nonce
                    },
                    timeout: 5000,
                    error: function() {
                        console.log('THORIUS heartbeat failed - connection may be unstable');
                    }
                });
            }, 60000); // Every minute
        }
    };
    
    // Initialize chatbot
    ThoruisChatbot.init();
    
    // Make chatbot globally accessible for debugging
    window.ThoruisChatbot = ThoruisChatbot;
    
    // Handle page visibility changes
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden && ThoruisChatbot.state.unreadCount > 0) {
            // Page became visible, user likely saw notifications
            setTimeout(() => {
                if (ThoruisChatbot.state.isOpen) {
                    ThoruisChatbot.clearNotifications();
                }
            }, 2000);
        }
    });
    
    // Auto-save chat history to localStorage
    setInterval(function() {
        try {
            localStorage.setItem('thorius_chat_history', JSON.stringify(ThoruisChatbot.state.messageHistory.slice(-20))); // Keep last 20 messages
        } catch (e) {
            console.warn('Could not save THORIUS chat history to localStorage');
        }
    }, 30000); // Every 30 seconds
    
    // Load previous chat history on page load
    try {
        const savedHistory = localStorage.getItem('thorius_chat_history');
        if (savedHistory) {
            const history = JSON.parse(savedHistory);
            history.forEach(function(msg) {
                if (msg.sender === 'bot' || msg.sender === 'user') {
                    ThoruisChatbot.addMessage(msg.text, msg.sender, msg.type || 'info');
                }
            });
        }
    } catch (e) {
        console.warn('Could not load THORIUS chat history from localStorage');
    }
}); 