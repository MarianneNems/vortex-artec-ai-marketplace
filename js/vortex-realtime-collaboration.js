/**
 * VORTEX Realtime Collaboration
 * 
 * Provides real-time collaboration features for the canvas
 */
(function($) {
    'use strict';

    // Collaboration session object
    var VortexCollaboration = {
        session: null,
        canvas: null,
        ctx: null,
        websocket: null,
        isDrawing: false,
        tool: 'brush',
        color: '#000000',
        lineWidth: 5,
        participants: {},
        cursors: {},
        history: [],
        historyIndex: -1,

        /**
         * Initialize collaboration
         */
        init: function() {
            this.setupWebSocket();
            this.setupCanvas();
            this.setupTools();
            this.setupEventHandlers();
        },

        /**
         * Setup WebSocket connection
         */
        setupWebSocket: function() {
            if (typeof vortexCollaboration === 'undefined' || !vortexCollaboration.websocket_url) {
                console.error('WebSocket configuration missing');
                return;
            }

            try {
                this.websocket = new WebSocket(vortexCollaboration.websocket_url);
                
                this.websocket.onopen = function() {
                    console.log('WebSocket connection established');
                    
                    // Join session if session ID is set
                    var sessionId = $('#vortex-collaboration-canvas').data('session-id');
                    if (sessionId) {
                        VortexCollaboration.joinSession(sessionId);
                    }
                };
                
                this.websocket.onmessage = function(event) {
                    VortexCollaboration.handleWebSocketMessage(event.data);
                };
                
                this.websocket.onerror = function(error) {
                    console.error('WebSocket error:', error);
                    
                    // Fall back to AJAX polling
                    VortexCollaboration.startAjaxPolling();
                };
                
                this.websocket.onclose = function() {
                    console.log('WebSocket connection closed');
                    
                    // Fall back to AJAX polling
                    VortexCollaboration.startAjaxPolling();
                };
            } catch (e) {
                console.error('Failed to connect to WebSocket server:', e);
                
                // Fall back to AJAX polling
                this.startAjaxPolling();
            }
        },

        /**
         * Start AJAX polling as fallback
         */
        startAjaxPolling: function() {
            this.pollInterval = setInterval(function() {
                VortexCollaboration.pollForUpdates();
            }, 2000);
        },

        /**
         * Poll for updates using AJAX
         */
        pollForUpdates: function() {
            if (!this.session) return;
            
            $.ajax({
                url: vortexCollaboration.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_get_session_updates',
                    nonce: vortexCollaboration.nonce,
                    session_id: this.session.id,
                    last_update: this.session.canvas_state.version
                },
                success: function(response) {
                    if (response.success && response.data.updates) {
                        VortexCollaboration.processUpdates(response.data.updates);
                    }
                }
            });
        },

        /**
         * Setup canvas
         */
        setupCanvas: function() {
            var canvas = document.getElementById('vortex-collaboration-canvas');
            if (!canvas) return;
            
            this.canvas = canvas;
            this.ctx = canvas.getContext('2d');
            
            // Set canvas size from data attributes or default
            this.canvas.width = canvas.getAttribute('data-width') || 800;
            this.canvas.height = canvas.getAttribute('data-height') || 600;
            
            // Set default styles
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this.ctx.strokeStyle = this.color;
            this.ctx.lineWidth = this.lineWidth;
        },

        /**
         * Setup drawing tools
         */
        setupTools: function() {
            $('.vortex-tool').on('click', function() {
                $('.vortex-tool').removeClass('active');
                $(this).addClass('active');
                
                VortexCollaboration.tool = $(this).data('tool');
            });
            
            // Color picker
            $('#vortex-color-picker').on('change', function() {
                VortexCollaboration.color = $(this).val();
                VortexCollaboration.ctx.strokeStyle = VortexCollaboration.color;
            });
            
            // Line width
            $('#vortex-line-width').on('change', function() {
                VortexCollaboration.lineWidth = parseInt($(this).val());
                VortexCollaboration.ctx.lineWidth = VortexCollaboration.lineWidth;
            });
            
            // Undo button
            $('#vortex-undo').on('click', function() {
                VortexCollaboration.undo();
            });
            
            // Redo button
            $('#vortex-redo').on('click', function() {
                VortexCollaboration.redo();
            });
            
            // Clear button
            $('#vortex-clear').on('click', function() {
                VortexCollaboration.clear();
            });
            
            // Save button
            $('#vortex-save').on('click', function() {
                VortexCollaboration.saveImage();
            });
        },

        /**
         * Setup event handlers
         */
        setupEventHandlers: function() {
            // Canvas mouse events
            $(this.canvas).on('mousedown', function(e) {
                VortexCollaboration.startDrawing(e);
            });
            
            $(this.canvas).on('mousemove', function(e) {
                VortexCollaboration.draw(e);
                VortexCollaboration.updateCursor(e);
            });
            
            $(document).on('mouseup', function() {
                VortexCollaboration.stopDrawing();
            });
            
            // Touch events for mobile
            $(this.canvas).on('touchstart', function(e) {
                var touch = e.originalEvent.touches[0];
                VortexCollaboration.startDrawing({
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                e.preventDefault();
            });
            
            $(this.canvas).on('touchmove', function(e) {
                var touch = e.originalEvent.touches[0];
                VortexCollaboration.draw({
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                e.preventDefault();
            });
            
            $(this.canvas).on('touchend', function(e) {
                VortexCollaboration.stopDrawing();
                e.preventDefault();
            });
            
            // Chat message form
            $('#vortex-chat-form').on('submit', function(e) {
                e.preventDefault();
                
                var messageInput = $('#vortex-chat-message');
                var message = messageInput.val().trim();
                
                if (message && VortexCollaboration.session) {
                    VortexCollaboration.sendChatMessage(message);
                    messageInput.val('');
                }
            });
            
            // Form for creating new session
            $('#vortex-create-session-form').on('submit', function(e) {
                e.preventDefault();
                
                var title = $('#session-title').val();
                var description = $('#session-description').val();
                
                if (title) {
                    VortexCollaboration.createSession(title, description);
                }
            });
            
            // Form for joining session
            $('#vortex-join-session-form').on('submit', function(e) {
                e.preventDefault();
                
                var sessionId = $('#session-id').val();
                
                if (sessionId) {
                    VortexCollaboration.joinSession(sessionId);
                }
            });
        },

        /**
         * Start drawing
         */
        startDrawing: function(e) {
            if (!this.session) return;
            
            this.isDrawing = true;
            
            var pos = this.getCanvasCoordinates(e);
            
            this.ctx.beginPath();
            this.ctx.moveTo(pos.x, pos.y);
            
            // Save first point
            this.currentPath = {
                tool: this.tool,
                color: this.color,
                lineWidth: this.lineWidth,
                points: [pos]
            };
        },

        /**
         * Continue drawing
         */
        draw: function(e) {
            if (!this.isDrawing || !this.session) return;
            
            var pos = this.getCanvasCoordinates(e);
            
            switch (this.tool) {
                case 'brush':
                    this.ctx.lineTo(pos.x, pos.y);
                    this.ctx.stroke();
                    break;
                    
                case 'eraser':
                    var savedColor = this.ctx.strokeStyle;
                    this.ctx.strokeStyle = '#FFFFFF';
                    this.ctx.lineTo(pos.x, pos.y);
                    this.ctx.stroke();
                    this.ctx.strokeStyle = savedColor;
                    break;
                    
                case 'line':
                    // Clear and redraw
                    this.redrawCanvas();
                    
                    // Draw line from start to current position
                    this.ctx.beginPath();
                    this.ctx.moveTo(this.currentPath.points[0].x, this.currentPath.points[0].y);
                    this.ctx.lineTo(pos.x, pos.y);
                    this.ctx.stroke();
                    break;
            }
            
            // Add point to current path
            this.currentPath.points.push(pos);
            
            // Send update to server
            this.sendCanvasUpdate({
                type: 'drawing',
                tool: this.tool,
                color: this.color,
                lineWidth: this.lineWidth,
                from: this.currentPath.points[this.currentPath.points.length - 2],
                to: pos
            });
        },

        /**
         * Stop drawing
         */
        stopDrawing: function() {
            if (!this.isDrawing || !this.session) return;
            
            this.isDrawing = false;
            
            // Add to history
            if (this.currentPath && this.currentPath.points.length > 1) {
                // Trim history if we're not at the end
                if (this.historyIndex < this.history.length - 1) {
                    this.history = this.history.slice(0, this.historyIndex + 1);
                }
                
                this.history.push(this.currentPath);
                this.historyIndex = this.history.length - 1;
                
                // Send complete path
                this.sendCanvasUpdate({
                    type: 'complete_path',
                    path: this.currentPath
                });
            }
            
            this.currentPath = null;
        },

        /**
         * Update cursor position
         */
        updateCursor: function(e) {
            if (!this.session) return;
            
            var pos = this.getCanvasCoordinates(e);
            
            // Send cursor update
            if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
                this.websocket.send(JSON.stringify({
                    type: 'cursor_update',
                    payload: {
                        session_id: this.session.id,
                        user_id: vortexCollaboration.user_id,
                        cursor: {
                            x: pos.x,
                            y: pos.y
                        }
                    }
                }));
            }
        },

        /**
         * Get canvas coordinates from event
         */
        getCanvasCoordinates: function(e) {
            var rect = this.canvas.getBoundingClientRect();
            return {
                x: e.clientX - rect.left,
                y: e.clientY - rect.top
            };
        },

        /**
         * Redraw canvas from history
         */
        redrawCanvas: function() {
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            
            for (var i = 0; i <= this.historyIndex; i++) {
                var path = this.history[i];
                
                this.ctx.beginPath();
                this.ctx.strokeStyle = path.color;
                this.ctx.lineWidth = path.lineWidth;
                
                this.ctx.moveTo(path.points[0].x, path.points[0].y);
                
                for (var j = 1; j < path.points.length; j++) {
                    this.ctx.lineTo(path.points[j].x, path.points[j].y);
                }
                
                this.ctx.stroke();
            }
            
            // Reset current styles
            this.ctx.strokeStyle = this.color;
            this.ctx.lineWidth = this.lineWidth;
        },

        /**
         * Undo last action
         */
        undo: function() {
            if (this.historyIndex >= 0) {
                this.historyIndex--;
                this.redrawCanvas();
                
                // Send undo action
                this.sendCanvasUpdate({
                    type: 'undo'
                });
            }
        },

        /**
         * Redo last undone action
         */
        redo: function() {
            if (this.historyIndex < this.history.length - 1) {
                this.historyIndex++;
                this.redrawCanvas();
                
                // Send redo action
                this.sendCanvasUpdate({
                    type: 'redo'
                });
            }
        },

        /**
         * Clear canvas
         */
        clear: function() {
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            this.history = [];
            this.historyIndex = -1;
            
            // Send clear action
            this.sendCanvasUpdate({
                type: 'clear'
            });
        },

        /**
         * Save image
         */
        saveImage: function() {
            if (!this.canvas) return;
            
            // Create temporary link
            var link = document.createElement('a');
            link.download = 'vortex-collaboration-' + new Date().toISOString().slice(0, 10) + '.png';
            link.href = this.canvas.toDataURL('image/png');
            link.click();
        },

        /**
         * Send canvas update to server
         */
        sendCanvasUpdate: function(update) {
            if (!this.session) return;
            
            // Send via WebSocket if available
            if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
                this.websocket.send(JSON.stringify({
                    type: 'canvas_update',
                    payload: {
                        session_id: this.session.id,
                        user_id: vortexCollaboration.user_id,
                        update: update
                    }
                }));
            } else {
                // Fall back to AJAX
                $.ajax({
                    url: vortexCollaboration.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_update_collaboration_canvas',
                        nonce: vortexCollaboration.nonce,
                        session_id: this.session.id,
                        update: update
                    }
                });
            }
        },

        /**
         * Send chat message
         */
        sendChatMessage: function(message) {
            if (!this.session) return;
            
            // Send via WebSocket if available
            if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
                this.websocket.send(JSON.stringify({
                    type: 'chat_message',
                    payload: {
                        session_id: this.session.id,
                        user_id: vortexCollaboration.user_id,
                        message: message
                    }
                }));
            } else {
                // Fall back to AJAX
                $.ajax({
                    url: vortexCollaboration.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_send_collaboration_message',
                        nonce: vortexCollaboration.nonce,
                        session_id: this.session.id,
                        message: message
                    },
                    success: function(response) {
                        if (response.success && response.data.chat_message) {
                            VortexCollaboration.addChatMessage(response.data.chat_message);
                        }
                    }
                });
            }
        },

        /**
         * Handle WebSocket message
         */
        handleWebSocketMessage: function(data) {
            try {
                var message = JSON.parse(data);
                
                switch (message.type) {
                    case 'canvas_updated':
                        this.handleCanvasUpdate(message.payload);
                        break;
                        
                    case 'cursor_update':
                        this.handleCursorUpdate(message.payload);
                        break;
                        
                    case 'chat_message':
                        this.handleChatMessage(message.payload);
                        break;
                        
                    case 'participant_joined':
                        this.handleParticipantJoined(message.payload);
                        break;
                        
                    case 'participant_left':
                        this.handleParticipantLeft(message.payload);
                        break;
                }
            } catch (e) {
                console.error('Error parsing WebSocket message:', e);
            }
        },

        /**
         * Handle canvas update
         */
        handleCanvasUpdate: function(data) {
            if (!this.session || data.session_id !== this.session.id || data.user_id === vortexCollaboration.user_id) {
                return;
            }
            
            var update = data.update;
            
            switch (update.type) {
                case 'drawing':
                    // Draw remote user's stroke
                    this.ctx.save();
                    this.ctx.strokeStyle = update.color;
                    this.ctx.lineWidth = update.lineWidth;
                    this.ctx.beginPath();
                    this.ctx.moveTo(update.from.x, update.from.y);
                    this.ctx.lineTo(update.to.x, update.to.y);
                    this.ctx.stroke();
                    this.ctx.restore();
                    break;
                    
                case 'complete_path':
                    // Add to history
                    this.history.push(update.path);
                    this.historyIndex = this.history.length - 1;
                    break;
                    
                case 'undo':
                    if (this.historyIndex >= 0) {
                        this.historyIndex--;
                        this.redrawCanvas();
                    }
                    break;
                    
                case 'redo':
                    if (this.historyIndex < this.history.length - 1) {
                        this.historyIndex++;
                        this.redrawCanvas();
                    }
                    break;
                    
                case 'clear':
                    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                    this.history = [];
                    this.historyIndex = -1;
                    break;
            }
        },

        /**
         * Handle cursor update
         */
        handleCursorUpdate: function(data) {
            if (!this.session || data.session_id !== this.session.id || data.user_id === vortexCollaboration.user_id) {
                return;
            }
            
            var userId = data.user_id;
            var cursor = data.cursor;
            
            // Update or create cursor element
            var cursorElement = this.cursors[userId];
            
            if (!cursorElement) {
                cursorElement = $('<div class="vortex-remote-cursor"></div>');
                cursorElement.attr('data-user-id', userId);
                
                if (this.session.participants[userId]) {
                    cursorElement.append('<span class="vortex-cursor-name">' + this.session.participants[userId].name + '</span>');
                }
                
                $('.vortex-collaboration-canvas-container').append(cursorElement);
                this.cursors[userId] = cursorElement;
            }
            
            // Position cursor
            cursorElement.css({
                left: cursor.x + 'px',
                top: cursor.y + 'px'
            });
        },

        /**
         * Handle chat message
         */
        handleChatMessage: function(data) {
            if (!this.session || data.session_id !== this.session.id) {
                return;
            }
            
            this.addChatMessage(data.message);
        },

        /**
         * Add chat message to chat container
         */
        addChatMessage: function(message) {
            var chatContainer = $('.vortex-chat-messages');
            
            var messageHtml = '';
            
            if (message.type === 'system') {
                messageHtml = '<div class="vortex-chat-system-message">' + message.message + '</div>';
            } else {
                var isCurrentUser = message.user_id === parseInt(vortexCollaboration.user_id);
                var className = isCurrentUser ? 'vortex-chat-message-self' : 'vortex-chat-message-other';
                
                messageHtml = '<div class="vortex-chat-message ' + className + '">' +
                    '<div class="vortex-chat-message-header">' +
                    '<span class="vortex-chat-message-name">' + message.user_name + '</span>' +
                    '<span class="vortex-chat-message-time">' + this.formatTime(message.timestamp) + '</span>' +
                    '</div>' +
                    '<div class="vortex-chat-message-content">' + message.message + '</div>' +
                    '</div>';
            }
            
            chatContainer.append(messageHtml);
            chatContainer.scrollTop(chatContainer[0].scrollHeight);
        },

        /**
         * Handle participant joined
         */
        handleParticipantJoined: function(data) {
            if (!this.session || data.session_id !== this.session.id) {
                return;
            }
            
            // Add participant to list
            this.session.participants[data.participant.id] = data.participant;
            
            // Update participants list
            this.updateParticipantsList();
            
            // Show system message
            this.addChatMessage({
                type: 'system',
                message: data.participant.name + ' joined the session',
                timestamp: new Date().toISOString()
            });
        },

        /**
         * Handle participant left
         */
        handleParticipantLeft: function(data) {
            if (!this.session || data.session_id !== this.session.id) {
                return;
            }
            
            if (this.session.participants[data.user_id]) {
                // Mark as inactive
                this.session.participants[data.user_id].active = false;
                
                // Update participants list
                this.updateParticipantsList();
                
                // Remove cursor
                if (this.cursors[data.user_id]) {
                    this.cursors[data.user_id].remove();
                    delete this.cursors[data.user_id];
                }
                
                // Show system message
                this.addChatMessage({
                    type: 'system',
                    message: this.session.participants[data.user_id].name + ' left the session',
                    timestamp: new Date().toISOString()
                });
            }
        },

        /**
         * Update participants list
         */
        updateParticipantsList: function() {
            var participantsList = $('.vortex-participants-list');
            participantsList.empty();
            
            $.each(this.session.participants, function(userId, participant) {
                if (participant.active) {
                    var item = $('<div class="vortex-participant"></div>');
                    item.attr('data-user-id', userId);
                    
                    var roleClass = 'vortex-participant-role-' + participant.role;
                    
                    item.append('<span class="vortex-participant-name">' + participant.name + '</span>');
                    item.append('<span class="vortex-participant-role ' + roleClass + '">' + participant.role + '</span>');
                    
                    participantsList.append(item);
                }
            });
        },

        /**
         * Format timestamp
         */
        formatTime: function(timestamp) {
            var date = new Date(timestamp);
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        },

        /**
         * Create new session
         */
        createSession: function(title, description) {
            $.ajax({
                url: vortexCollaboration.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_create_collaboration_session',
                    nonce: vortexCollaboration.nonce,
                    title: title,
                    description: description
                },
                success: function(response) {
                    if (response.success && response.data.session) {
                        VortexCollaboration.session = response.data.session;
                        
                        // Hide form, show canvas
                        $('#vortex-session-forms').hide();
                        $('#vortex-collaboration-workspace').show();
                        
                        // Update participants list
                        VortexCollaboration.updateParticipantsList();
                        
                        // Show session info
                        $('.vortex-session-title').text(VortexCollaboration.session.title);
                        $('.vortex-session-id').text(VortexCollaboration.session.id);
                    } else {
                        alert(response.data.message || 'Failed to create session');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        },

        /**
         * Join existing session
         */
        joinSession: function(sessionId) {
            $.ajax({
                url: vortexCollaboration.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_join_collaboration_session',
                    nonce: vortexCollaboration.nonce,
                    session_id: sessionId
                },
                success: function(response) {
                    if (response.success && response.data.session) {
                        VortexCollaboration.session = response.data.session;
                        
                        // Hide form, show canvas
                        $('#vortex-session-forms').hide();
                        $('#vortex-collaboration-workspace').show();
                        
                        // Update participants list
                        VortexCollaboration.updateParticipantsList();
                        
                        // Show session info
                        $('.vortex-session-title').text(VortexCollaboration.session.title);
                        $('.vortex-session-id').text(VortexCollaboration.session.id);
                        
                        // Show chat history
                        if (VortexCollaboration.session.chat_history) {
                            $.each(VortexCollaboration.session.chat_history, function(i, message) {
                                VortexCollaboration.addChatMessage(message);
                            });
                        }
                        
                        // Load canvas state
                        if (VortexCollaboration.session.canvas_state) {
                            VortexCollaboration.loadCanvasState(VortexCollaboration.session.canvas_state);
                        }
                    } else {
                        alert(response.data.message || 'Failed to join session');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        },

        /**
         * Load canvas state
         */
        loadCanvasState: function(canvasState) {
            if (!canvasState || !canvasState.layers) return;
            
            // Clear history
            this.history = [];
            this.historyIndex = -1;
            
            // Clear canvas
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            
            // Draw each layer
            for (var i = 0; i < canvasState.layers.length; i++) {
                var layer = canvasState.layers[i];
                
                if (layer.visible && layer.data) {
                    var img = new Image();
                    img.onload = (function(ctx) {
                        return function() {
                            ctx.drawImage(this, 0, 0);
                        };
                    })(this.ctx);
                    img.src = layer.data;
                }
            }
        }
    };

    $(document).ready(function() {
        // Initialize if canvas is present
        if ($('#vortex-collaboration-canvas').length) {
            VortexCollaboration.init();
        }
    });

})(jQuery); 