/**
 * HURAII WebSocket Client
 * Handles real-time communication for HURAII API
 * 
 * Features:
 * - Real-time generation progress tracking
 * - Connection management with automatic reconnection
 * - Message queuing during disconnection
 * - Event-based architecture
 */
(function(global, $) {
  'use strict';
  
  const WebSocketClient = {
    name: 'websocket',
    core: null,
    
    // Default configuration
    config: {
      endpoint: null,         // WebSocket endpoint (required)
      reconnectInterval: 3000, // Reconnection interval in ms
      maxReconnectAttempts: 10, // Maximum reconnection attempts
      heartbeatInterval: 30000, // Heartbeat interval in ms
      debug: false            // Debug mode
    },
    
    // Internal properties
    _socket: null,           // WebSocket connection
    _connected: false,       // Connection status
    _reconnectAttempts: 0,   // Current reconnection attempts
    _reconnectTimer: null,   // Reconnection timer
    _heartbeatTimer: null,   // Heartbeat timer
    _messageQueue: [],       // Queue for messages during disconnection
    _activeSubscriptions: new Set(), // Active subscriptions
    _pendingRequests: new Map(), // Pending request IDs and callbacks
    
    /**
     * Initialize the WebSocket client
     * @param {Object} core Core component
     * @param {Object} config Configuration
     * @returns {Object} This component
     */
    init: function(core, config = {}) {
      this.core = core;
      
      // Merge configs
      this.config = {...this.config, ...config};
      
      // Validate required config
      if (!this.config.endpoint) {
        console.error('HURAII WebSocket: Endpoint URL is required');
        return this;
      }
      
      // Connect to WebSocket server
      this._connect();
      
      // Register with core
      core.registerComponent(this.name, this);
      
      return this;
    },
    
    /**
     * Subscribe to generation progress updates
     * @param {string} requestId Generation request ID
     * @returns {Promise<boolean>} Promise resolving to subscription success
     */
    subscribeToProgress: function(requestId) {
      return this._subscribe('generation_progress', { request_id: requestId });
    },
    
    /**
     * Unsubscribe from generation progress updates
     * @param {string} requestId Generation request ID
     * @returns {Promise<boolean>} Promise resolving to unsubscription success
     */
    unsubscribeFromProgress: function(requestId) {
      return this._unsubscribe('generation_progress', { request_id: requestId });
    },
    
    /**
     * Send a message to the server
     * @param {string} type Message type
     * @param {Object} data Message data
     * @returns {Promise<Object>} Promise resolving to server response
     */
    send: function(type, data = {}) {
      return new Promise((resolve, reject) => {
        const message = {
          type: type,
          data: data,
          id: this._generateId(),
          timestamp: Date.now(),
          nonce: this.core.config.nonce
        };
        
        // Store callback for response
        this._pendingRequests.set(message.id, {
          resolve: resolve,
          reject: reject,
          timestamp: Date.now(),
          timeout: setTimeout(() => {
            if (this._pendingRequests.has(message.id)) {
              const pendingRequest = this._pendingRequests.get(message.id);
              this._pendingRequests.delete(message.id);
              pendingRequest.reject(new Error('Request timeout'));
            }
          }, 30000) // 30 seconds timeout
        });
        
        // Send or queue message
        if (this._connected) {
          this._sendMessage(message);
        } else {
          this._messageQueue.push(message);
          this._log('Message queued:', message);
        }
      });
    },
    
    /**
     * Close the WebSocket connection
     */
    close: function() {
      if (this._socket) {
        // Clear timers
        clearTimeout(this._reconnectTimer);
        clearInterval(this._heartbeatTimer);
        
        // Close connection
        this._socket.close(1000, 'Client closed connection');
        this._socket = null;
        this._connected = false;
        
        // Reject all pending requests
        for (const [id, request] of this._pendingRequests.entries()) {
          clearTimeout(request.timeout);
          request.reject(new Error('Connection closed'));
          this._pendingRequests.delete(id);
        }
      }
    },
    
    /**
     * Subscribe to a topic
     * @param {string} topic Topic to subscribe to
     * @param {Object} params Subscription parameters
     * @returns {Promise<boolean>} Promise resolving to subscription success
     * @private
     */
    _subscribe: function(topic, params = {}) {
      const subscriptionKey = `${topic}:${JSON.stringify(params)}`;
      
      // Check if already subscribed
      if (this._activeSubscriptions.has(subscriptionKey)) {
        return Promise.resolve(true);
      }
      
      // Send subscription request
      return this.send('subscribe', {
        topic: topic,
        params: params
      }).then(response => {
        if (response.success) {
          this._activeSubscriptions.add(subscriptionKey);
          this._log('Subscribed to:', subscriptionKey);
          return true;
        }
        return false;
      });
    },
    
    /**
     * Unsubscribe from a topic
     * @param {string} topic Topic to unsubscribe from
     * @param {Object} params Subscription parameters
     * @returns {Promise<boolean>} Promise resolving to unsubscription success
     * @private
     */
    _unsubscribe: function(topic, params = {}) {
      const subscriptionKey = `${topic}:${JSON.stringify(params)}`;
      
      // Check if not subscribed
      if (!this._activeSubscriptions.has(subscriptionKey)) {
        return Promise.resolve(true);
      }
      
      // Send unsubscription request
      return this.send('unsubscribe', {
        topic: topic,
        params: params
      }).then(response => {
        if (response.success) {
          this._activeSubscriptions.delete(subscriptionKey);
          this._log('Unsubscribed from:', subscriptionKey);
          return true;
        }
        return false;
      });
    },
    
    /**
     * Connect to WebSocket server
     * @private
     */
    _connect: function() {
      try {
        // Create WebSocket connection
        this._socket = new WebSocket(this.config.endpoint);
        
        // Set up event handlers
        this._socket.onopen = this._handleOpen.bind(this);
        this._socket.onmessage = this._handleMessage.bind(this);
        this._socket.onclose = this._handleClose.bind(this);
        this._socket.onerror = this._handleError.bind(this);
        
        this._log('Connecting to WebSocket server:', this.config.endpoint);
      } catch (error) {
        this._log('Connection error:', error);
        this._scheduleReconnect();
      }
    },
    
    /**
     * Handle WebSocket open event
     * @private
     */
    _handleOpen: function() {
      this._connected = true;
      this._reconnectAttempts = 0;
      
      this._log('WebSocket connected');
      
      // Send auth message
      this.send('authenticate', {
        user_id: this.core.config.userId,
        session_id: this.core.getSessionId(),
        client_info: {
          user_agent: navigator.userAgent,
          screen_size: `${window.innerWidth}x${window.innerHeight}`,
          timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
        }
      });
      
      // Emit connection event
      this.core.emit('websocket_connected', {
        timestamp: Date.now()
      });
      
      // Start heartbeat
      this._startHeartbeat();
      
      // Process message queue
      this._processQueue();
      
      // Resubscribe to active topics
      this._resubscribe();
    },
    
    /**
     * Handle WebSocket message event
     * @param {Object} event Message event
     * @private
     */
    _handleMessage: function(event) {
      try {
        const message = JSON.parse(event.data);
        
        this._log('Received message:', message);
        
        // Handle different message types
        switch (message.type) {
          case 'response':
            this._handleResponse(message);
            break;
            
          case 'event':
            this._handleEvent(message);
            break;
            
          case 'pong':
            // Heartbeat response
            break;
            
          default:
            this._log('Unknown message type:', message.type);
        }
      } catch (error) {
        this._log('Error parsing message:', error, event.data);
      }
    },
    
    /**
     * Handle WebSocket close event
     * @param {Object} event Close event
     * @private
     */
    _handleClose: function(event) {
      this._connected = false;
      
      this._log('WebSocket disconnected, code:', event.code, 'reason:', event.reason);
      
      // Emit disconnection event
      this.core.emit('websocket_disconnected', {
        code: event.code,
        reason: event.reason,
        timestamp: Date.now()
      });
      
      // Clear heartbeat timer
      clearInterval(this._heartbeatTimer);
      
      // Schedule reconnect if not a normal closure
      if (event.code !== 1000) {
        this._scheduleReconnect();
      }
    },
    
    /**
     * Handle WebSocket error event
     * @param {Object} error Error event
     * @private
     */
    _handleError: function(error) {
      this._log('WebSocket error:', error);
      
      // Emit error event
      this.core.emit('websocket_error', {
        error: error.message || 'Unknown error',
        timestamp: Date.now()
      });
    },
    
    /**
     * Handle server response
     * @param {Object} message Response message
     * @private
     */
    _handleResponse: function(message) {
      const id = message.id;
      
      if (this._pendingRequests.has(id)) {
        const request = this._pendingRequests.get(id);
        this._pendingRequests.delete(id);
        
        clearTimeout(request.timeout);
        
        if (message.success) {
          request.resolve(message.data);
        } else {
          request.reject(new Error(message.error || 'Unknown error'));
        }
      }
    },
    
    /**
     * Handle server event
     * @param {Object} message Event message
     * @private
     */
    _handleEvent: function(message) {
      const eventName = message.event;
      const eventData = message.data;
      
      // Map WebSocket events to core events
      switch (eventName) {
        case 'generation_progress':
          this.core.emit('generation_progress', {
            ...eventData,
            timestamp: Date.now(),
            source: 'websocket'
          });
          break;
          
        case 'generation_complete':
          this.core.emit('generation_complete', {
            ...eventData,
            timestamp: Date.now(),
            source: 'websocket'
          });
          break;
          
        case 'generation_error':
          this.core.emit('generation_error', {
            ...eventData,
            timestamp: Date.now(),
            source: 'websocket'
          });
          break;
          
        default:
          // Forward other events
          this.core.emit(eventName, {
            ...eventData,
            timestamp: Date.now(),
            source: 'websocket'
          });
      }
    },
    
    /**
     * Schedule reconnection
     * @private
     */
    _scheduleReconnect: function() {
      // Clear existing timer
      clearTimeout(this._reconnectTimer);
      
      // Check max reconnect attempts
      if (this._reconnectAttempts >= this.config.maxReconnectAttempts) {
        this._log('Max reconnect attempts reached');
        
        // Emit event
        this.core.emit('websocket_reconnect_failed', {
          attempts: this._reconnectAttempts,
          timestamp: Date.now()
        });
        
        return;
      }
      
      // Increment attempts
      this._reconnectAttempts++;
      
      // Calculate backoff delay with jitter
      const delay = Math.min(
        this.config.reconnectInterval * Math.pow(1.5, this._reconnectAttempts - 1),
        30000
      );
      const jitter = Math.random() * 1000;
      const totalDelay = delay + jitter;
      
      this._log(`Reconnecting in ${Math.round(totalDelay / 1000)}s (attempt ${this._reconnectAttempts}/${this.config.maxReconnectAttempts})`);
      
      // Schedule reconnect
      this._reconnectTimer = setTimeout(() => {
        this._connect();
      }, totalDelay);
      
      // Emit event
      this.core.emit('websocket_reconnecting', {
        attempt: this._reconnectAttempts,
        delay: totalDelay,
        timestamp: Date.now()
      });
    },
    
    /**
     * Process queued messages
     * @private
     */
    _processQueue: function() {
      if (this._messageQueue.length > 0 && this._connected) {
        this._log(`Processing ${this._messageQueue.length} queued messages`);
        
        // Process all queued messages
        const queue = [...this._messageQueue];
        this._messageQueue = [];
        
        queue.forEach(message => {
          this._sendMessage(message);
        });
      }
    },
    
    /**
     * Send message to server
     * @param {Object} message Message to send
     * @private
     */
    _sendMessage: function(message) {
      if (this._connected && this._socket.readyState === WebSocket.OPEN) {
        try {
          this._socket.send(JSON.stringify(message));
          this._log('Sent message:', message);
        } catch (error) {
          this._log('Error sending message:', error);
          
          // Re-queue message on error
          this._messageQueue.push(message);
          
          // Reject request if it's a response-expected message
          if (message.id && this._pendingRequests.has(message.id)) {
            const request = this._pendingRequests.get(message.id);
            this._pendingRequests.delete(message.id);
            clearTimeout(request.timeout);
            request.reject(new Error('Failed to send message: ' + error.message));
          }
        }
      } else {
        // Queue message if not connected
        this._messageQueue.push(message);
      }
    },
    
    /**
     * Start heartbeat to keep connection alive
     * @private
     */
    _startHeartbeat: function() {
      // Clear existing timer
      clearInterval(this._heartbeatTimer);
      
      // Start new timer
      this._heartbeatTimer = setInterval(() => {
        if (this._connected && this._socket.readyState === WebSocket.OPEN) {
          this._sendMessage({
            type: 'ping',
            timestamp: Date.now(),
            id: this._generateId()
          });
        }
      }, this.config.heartbeatInterval);
    },
    
    /**
     * Resubscribe to active topics after reconnection
     * @private
     */
    _resubscribe: function() {
      if (this._activeSubscriptions.size > 0) {
        this._log(`Resubscribing to ${this._activeSubscriptions.size} topics`);
        
        for (const subscription of this._activeSubscriptions) {
          try {
            const [topic, paramsJson] = subscription.split(':', 2);
            const params = JSON.parse(paramsJson);
            
            this.send('subscribe', {
              topic: topic,
              params: params
            }).catch(error => {
              this._log('Resubscription error:', error);
            });
          } catch (error) {
            this._log('Error parsing subscription:', error, subscription);
          }
        }
      }
    },
    
    /**
     * Generate unique ID for messages
     * @returns {string} Unique ID
     * @private
     */
    _generateId: function() {
      return `${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    },
    
    /**
     * Log debug messages
     * @private
     */
    _log: function(...args) {
      if (this.config.debug) {
        console.log('[HURAII WebSocket]', ...args);
      }
    }
  };
  
  // Register with HURAII when loaded
  if (global.HURAII) {
    global.HURAII.registerComponent('websocket', WebSocketClient);
  } else {
    // Wait for HURAII to be defined
    document.addEventListener('DOMContentLoaded', () => {
      if (global.HURAII) {
        global.HURAII.registerComponent('websocket', WebSocketClient);
      } else {
        console.error('HURAII core module not found. WebSocket module initialization failed.');
      }
    });
  }
  
})(window, jQuery); 