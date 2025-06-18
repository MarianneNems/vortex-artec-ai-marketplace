 * VORTEX Enhanced WebSocket Client
 * 
 * Provides a robust WebSocket client with reconnection, authentication, and fallback capabilities
 */
(function($) {
    'use strict';

    // VortexWebSocket constructor
    var VortexWebSocket = function(options) {
        this.options = $.extend({
            url: '',
            channel: 'default',
            protocols: [],
            onOpen: function() {},
            onMessage: function() {},
            onClose: function() {},
            onError: function() {},
            onReconnect: function() {},
            debug: false,
            autoConnect: true,
            autoReconnect: true,
            reconnectInterval: 3000,
            maxReconnects: 5,
            reconnectDecay: 1.5,
            heartbeatInterval: 30000
        }, options);

        // Internal state
        this.socket = null;
        this.isConnected = false;
        this.reconnectCount = 0;
        this.reconnectTimer = null;
        this.heartbeatTimer = null;
        this.clientId = this.generateClientId();
        this.authToken = null;
        this.pendingMessages = [];

        // Initialize
        this.init();
    };

    // Initialize
    VortexWebSocket.prototype.init = function() {
        if (this.options.debug) {
            console.log('VortexWebSocket: Initializing');
        }

        // Authenticate first if needed
        if (this.options.requireAuth) {
            this.authenticate();
        } else if (this.options.autoConnect) {
            this.connect();
        }
    };

    // Connect to WebSocket server
    VortexWebSocket.prototype.connect = function() {
        var self = this;
        
        // Clear any existing connection
        this.cleanup();
        
        try {
            // Prepare protocols with auth token if available
            var protocols = this.options.protocols.slice();
            if (this.authToken) {
                protocols.push('auth-token-' + this.authToken);
            }
            
            // Create WebSocket connection
            if (protocols.length > 0) {
                this.socket = new WebSocket(this.options.url, protocols);
            } else {
                this.socket = new WebSocket(this.options.url);
            }
            
            // Set up event handlers
            this.socket.onopen = function(event) {
                if (self.options.debug) {
                    console.log('VortexWebSocket: Connection opened');
                }
                
                self.isConnected = true;
                self.reconnectCount = 0;
                
                // Send any pending messages
                if (self.pendingMessages.length > 0) {
                    for (var i = 0; i < self.pendingMessages.length; i++) {
                        self.send(self.pendingMessages[i].type, self.pendingMessages[i].payload);
                    }
                    self.pendingMessages = [];
                }
                
                // Start heartbeat
                self.startHeartbeat();
                
                // Call user callback
                self.options.onOpen(event);
            };
            
            this.socket.onmessage = function(event) {
                var message;
                
                try {
                    message = JSON.parse(event.data);
                } catch(e) {
                    if (self.options.debug) {
                        console.error('VortexWebSocket: Error parsing message', e);
                    }
                    message = event.data;
                }
                
                // Call user callback
                self.options.onMessage(message, event);
            };
            
            this.socket.onclose = function(event) {
                if (self.options.debug) {
                    console.log('VortexWebSocket: Connection closed', event);
                }
                
                self.isConnected = false;
                self.stopHeartbeat();
                
                // Handle reconnection
                if (self.options.autoReconnect && (event.code === 1006 || event.code === 1001)) {
                    self.scheduleReconnect();
                }
                
                // Call user callback
                self.options.onClose(event);
            };
            
            this.socket.onerror = function(error) {
                if (self.options.debug) {
                    console.error('VortexWebSocket: Error', error);
                }
                
                // Call user callback
                self.options.onError(error);
                
                // Ensure we schedule reconnect if autoReconnect is true
                if (self.options.autoReconnect && self.socket.readyState === WebSocket.CLOSED) {
                    self.scheduleReconnect();
                }
            };
        } catch (error) {
            if (self.options.debug) {
                console.error('VortexWebSocket: Failed to create WebSocket', error);
            }
            
            // Fall back to AJAX
            this.initAjaxFallback();
            
            // Call user callback
            self.options.onError(error);
        }
    };

    // Authenticate with server
    VortexWebSocket.prototype.authenticate = function() {
        var self = this;
        
        $.ajax({
            url: vortexWebSocketSettings.ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_websocket_authenticate',
                nonce: vortexWebSocketSettings.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    self.authToken = response.data.token;
                    
                    if (self.options.debug) {
                        console.log('VortexWebSocket: Authentication successful');
                    }
                    
                    // Connect after successful authentication
                    if (self.options.autoConnect) {
                        self.connect();
                    }
                } else {
                    if (self.options.debug) {
                        console.error('VortexWebSocket: Authentication failed', response);
                    }
                    
                    // Fall back to AJAX
                    self.initAjaxFallback();
                    
                    // Call user callback
                    self.options.onError({
                        type: 'authentication_failed',
                        message: response.data.message || 'Authentication failed'
                    });
                }
            },
            error: function(xhr, status, error) {
                if (self.options.debug) {
                    console.error('VortexWebSocket: Authentication request failed', error);
                }
                
                // Fall back to AJAX
                self.initAjaxFallback();
                
                // Call user callback
                self.options.onError({
                    type: 'authentication_request_failed',
                    message: error
                });
            }
        });
    };

    // Schedule reconnection
    VortexWebSocket.prototype.scheduleReconnect = function() {
        var self = this;
        
        if (this.reconnectCount >= this.options.maxReconnects) {
            if (this.options.debug) {
                console.log('VortexWebSocket: Max reconnects reached, falling back to AJAX');
            }
            
            // Fall back to AJAX
            this.initAjaxFallback();
            return;
        }
        
        // Calculate backoff delay
        var delay = this.options.reconnectInterval * Math.pow(this.options.reconnectDecay, this.reconnectCount);
        
        if (this.options.debug) {
            console.log('VortexWebSocket: Scheduling reconnect in ' + delay + 'ms');
        }
        
        // Clear any existing timer
        if (this.reconnectTimer) {
            clearTimeout(this.reconnectTimer);
        }
        
        // Set new timer
        this.reconnectTimer = setTimeout(function() {
            if (self.options.debug) {
                console.log('VortexWebSocket: Attempting to reconnect');
            }
            
            self.reconnectCount++;
            
            // Call user callback
            self.options.onReconnect(self.reconnectCount);
            
            // Attempt to reconnect
            self.connect();
        }, delay);
    };

    // Start heartbeat
    VortexWebSocket.prototype.startHeartbeat = function() {
        var self = this;
        
        // Clear any existing timer
        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
        }
        
        // Set new timer
        this.heartbeatTimer = setInterval(function() {
            self.sendHeartbeat();
        }, this.options.heartbeatInterval);
    };

    // Stop heartbeat
    VortexWebSocket.prototype.stopHeartbeat = function() {
        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
            this.heartbeatTimer = null;
        }
    };

    // Send heartbeat
    VortexWebSocket.prototype.sendHeartbeat = function() {
        var self = this;
        
        if (!this.isConnected) {
            return;
        }
        
        // Also send AJAX heartbeat to update server-side timestamp
        $.ajax({
            url: vortexWebSocketSettings.ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_websocket_heartbeat',
                nonce: vortexWebSocketSettings.nonce,
                client_id: this.clientId,
                channel: this.options.channel
            },
            dataType: 'json',
            success: function(response) {
                if (!response.success) {
                    // Server doesn't recognize our connection, reconnect
                    if (self.options.debug) {
                        console.log('VortexWebSocket: Heartbeat failed, reconnecting');
                    }
                    
                    self.connect();
                }
            },
            error: function() {
                // Connection to server may be lost, reconnect
                if (self.options.debug) {
                    console.log('VortexWebSocket: Heartbeat request failed, reconnecting');
                }
                
                self.connect();
            }
        });
        
        // Send WebSocket heartbeat
        if (this.socket && this.socket.readyState === WebSocket.OPEN) {
            this.send('heartbeat', {
                client_id: this.clientId,
                timestamp: Date.now()
            });
        }
    };

    // Send a message
    VortexWebSocket.prototype.send = function(type, payload) {
        var message = JSON.stringify({
            type: type,
            payload: payload,
            timestamp: Date.now(),
            client_id: this.clientId
        });
        
        if (this.isConnected && this.socket && this.socket.readyState === WebSocket.OPEN) {
            this.socket.send(message);
            return true;
        } else {
            // Queue message for later
            this.pendingMessages.push({
                type: type,
                payload: payload
            });
            
            // Try to reconnect if not connected
            if (!this.isConnected && this.options.autoReconnect) {
                this.connect();
            }
            
            return false;
        }
    };

    // Initialize AJAX fallback
    VortexWebSocket.prototype.initAjaxFallback = function() {
        var self = this;
        
        if (this.options.debug) {
            console.log('VortexWebSocket: Initializing AJAX fallback');
        }
        
        // Set up polling for messages
        this.pollInterval = setInterval(function() {
            self.pollForMessages();
        }, 3000);
        
        // Mark as connected so we can send messages
        this.isConnected = true;
        
        // Simulate open event
        setTimeout(function() {
            self.options.onOpen({
                type: 'open',
                isFallback: true
            });
        }, 10);
    };

    // Poll for messages (AJAX fallback)
    VortexWebSocket.prototype.pollForMessages = function() {
        var self = this;
        
        $.ajax({
            url: vortexWebSocketSettings.ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_websocket_poll',
                nonce: vortexWebSocketSettings.nonce,
                client_id: this.clientId,
                channel: this.options.channel,
                last_message_id: this.lastMessageId || 0
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data.messages) {
                    // Update last message ID
                    if (response.data.messages.length > 0) {
                        self.lastMessageId = response.data.messages[response.data.messages.length - 1].id;
                    }
                    
                    // Process messages
                    for (var i = 0; i < response.data.messages.length; i++) {
                        self.options.onMessage(response.data.messages[i]);
                    }
                }
            }
        });
    };

    // Cleanup resources
    VortexWebSocket.prototype.cleanup = function() {
        // Clear timers
        this.stopHeartbeat();
        
        if (this.reconnectTimer) {
            clearTimeout(this.reconnectTimer);
            this.reconnectTimer = null;
        }
        
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
        
        // Close existing socket
        if (this.socket) {
            try {
                this.socket.onopen = null;
                this.socket.onmessage = null;
                this.socket.onclose = null;
                this.socket.onerror = null;
                
                if (this.socket.readyState === WebSocket.OPEN || this.socket.readyState === WebSocket.CONNECTING) {
                    this.socket.close();
                }
            } catch (e) {
                if (this.options.debug) {
                    console.error('VortexWebSocket: Error closing socket', e);
                }
            }
            
            this.socket = null;
        }
    };

    // Close connection
    VortexWebSocket.prototype.close = function() {
        this.cleanup();
        this.isConnected = false;
    };

    // Generate a unique client ID
    VortexWebSocket.prototype.generateClientId = function() {
        return 'client_' + Math.random().toString(36).substr(2, 9);
    };

    // Export to global scope
    window.VortexWebSocket = VortexWebSocket;

    // Initialize when the DOM is ready
    $(document).ready(function() {
        // Create global instance if settings are available
        if (typeof vortexWebSocketSettings !== 'undefined') {
            window.vortexSocket = new VortexWebSocket({
                url: vortexWebSocketSettings.websocket_url,
                channel: vortexWebSocketSettings.channel,
                reconnectInterval: vortexWebSocketSettings.retry_settings.retry_interval,
                maxReconnects: vortexWebSocketSettings.retry_settings.max_retries,
                reconnectDecay: vortexWebSocketSettings.retry_settings.backoff_factor,
                heartbeatInterval: vortexWebSocketSettings.heartbeat_interval,
                requireAuth: true,
                debug: false,
                onOpen: function(event) {
                    $(document).trigger('vortex_websocket_connected', [event]);
                },
                onMessage: function(message, event) {
                    $(document).trigger('vortex_websocket_message', [message, event]);
                },
                onClose: function(event) {
                    $(document).trigger('vortex_websocket_closed', [event]);
                },
                onError: function(error) {
                    $(document).trigger('vortex_websocket_error', [error]);
                },
                onReconnect: function(count) {
                    $(document).trigger('vortex_websocket_reconnecting', [count]);
                }
            });
        }
    });

})(jQuery); 
/**
 