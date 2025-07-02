/**
 * HURAII GPU Interface JavaScript
 * 
 * Comprehensive interface for AI agent interactions with real-time
 * satisfaction tracking and GPU/CPU allocation management
 */

(function($) {
    'use strict';
    
    // Main HURAII GPU Interface object
    const HuraiiGPUInterface = {
        // Configuration
        config: {
            ajaxUrl: huraiiGPUData?.ajaxUrl || '/wp-admin/admin-ajax.php',
            nonce: huraiiGPUData?.nonce || '',
            userId: huraiiGPUData?.userId || 0,
            maxFileSize: 50 * 1024 * 1024, // 50MB
            supportedImageTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            supportedDocTypes: ['application/pdf', 'application/msword', 'text/plain'],
            feedbackUpdateInterval: 5000, // 5 seconds
            satisfactionThreshold: 70 // 70% satisfaction threshold
        },
        
        // State management
        state: {
            activeTab: 'upload-file',
            uploadedFiles: {
                images: [],
                documents: []
            },
            agentSatisfaction: {
                huraii: { likes: 0, dislikes: 0, satisfaction: 0 },
                cloe: { likes: 0, dislikes: 0, satisfaction: 0 },
                horace: { likes: 0, dislikes: 0, satisfaction: 0 },
                thorius: { likes: 0, dislikes: 0, satisfaction: 0 },
                archer: { likes: 0, dislikes: 0, satisfaction: 0 }
            },
            currentProcessingMode: 'gpu',
            activeAgent: 'huraii',
            generationInProgress: false,
            sessionStartTime: Date.now(),
            totalInteractions: 0,
            realtimeFeedback: true
        },
        
        /**
         * Initialize the interface
         */
        init: function() {
            this.bindEvents();
            this.initializeTabs();
            this.loadSatisfactionData();
            this.startRealtimeUpdates();
            this.setupFileUpload();
            console.log('HURAII GPU Interface initialized');
        },
        
        /**
         * Bind all event listeners
         */
        bindEvents: function() {
            const self = this;
            
            // Tab navigation
            $('.vortex-tab-button').on('click', function(e) {
                e.preventDefault();
                const tabId = $(this).data('tab');
                self.switchTab(tabId);
            });
            
            // File upload triggers
            $('#image-dropzone .upload-btn').on('click', () => $('#image-upload').click());
            $('#document-dropzone .upload-btn').on('click', () => $('#document-upload').click());
            
            // File input changes
            $('#image-upload').on('change', (e) => this.handleFileUpload(e, 'image'));
            $('#document-upload').on('change', (e) => this.handleFileUpload(e, 'document'));
            
            // Processing mode selection
            $('.select-mode-btn').on('click', function() {
                const mode = $(this).data('mode');
                self.setProcessingMode(mode);
            });
            
            // Agent assignment
            $('input[name="upload_agent"], #reading-agent').on('change', function() {
                self.setActiveAgent($(this).val());
            });
            
            // Satisfaction feedback buttons
            $('.feedback-btn').on('click', function(e) {
                e.preventDefault();
                const agent = $(this).closest('.agent-feedback-card').data('agent') || 
                             $(this).closest('.agent-status').data('agent');
                const feedback = $(this).data('feedback');
                self.recordFeedback(agent, feedback);
            });
            
            // Generation button
            $('#start-generation-btn').on('click', (e) => {
                e.preventDefault();
                this.startGeneration();
            });
            
            // File analysis button
            $('#analyze-file-btn').on('click', (e) => {
                e.preventDefault();
                this.analyzeFile();
            });
            
            // Satisfaction tracking in status bar
            $('.satisfaction-score .thumb-up, .satisfaction-score .thumb-down').on('click', function(e) {
                e.preventDefault();
                const agent = $(this).closest('.agent-status').data('agent');
                const feedback = $(this).data('feedback');
                self.recordFeedback(agent, feedback);
            });
            
            // Modal controls
            $('.modal-close').on('click', function() {
                $(this).closest('.vortex-modal').hide();
            });
            
            // Feedback details buttons
            $('.details-btn').on('click', function() {
                const agent = $(this).data('agent');
                self.showFeedbackDetails(agent);
            });
            
            // Drag and drop functionality
            this.setupDragAndDrop();
        },
        
        /**
         * Initialize tab system
         */
        initializeTabs: function() {
            this.switchTab(this.state.activeTab);
        },
        
        /**
         * Switch between tabs
         */
        switchTab: function(tabId) {
            // Update active states
            $('.vortex-tab-button').removeClass('active');
            $('.vortex-tab-button[data-tab="' + tabId + '"]').addClass('active');
            
            $('.vortex-tab-content').removeClass('active');
            $('.vortex-tab-content[data-tab="' + tabId + '"]').addClass('active');
            
            this.state.activeTab = tabId;
            this.onTabSwitch(tabId);
            
            // Track tab switch for learning
            this.trackInteraction('tab_switch', { tab: tabId });
        },
        
        /**
         * Handle tab switch specific actions
         */
        onTabSwitch: function(tabId) {
            switch(tabId) {
                case 'upload-file':
                    this.refreshUploadStatus();
                    break;
                case 'prompt-read':
                    this.populateReadableFiles();
                    break;
                case 'generate':
                    this.updateGenerationForm();
                    break;
                case 'feedback':
                    this.refreshFeedbackData();
                    break;
            }
        },
        
        /**
         * Setup file upload handling
         */
        setupFileUpload: function() {
            const self = this;
            
            // Setup drag and drop
            $('.dropzone-area').on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });
            
            $('.dropzone-area').on('dragleave', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            });
            
            $('.dropzone-area').on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
                
                const files = e.originalEvent.dataTransfer.files;
                const uploadType = $(this).attr('id').includes('image') ? 'image' : 'document';
                self.processDroppedFiles(files, uploadType);
            });
        },
        
        /**
         * Handle file upload
         */
        handleFileUpload: function(event, type) {
            const files = event.target.files;
            this.processFiles(files, type);
        },
        
        /**
         * Process uploaded files
         */
        processFiles: function(files, type) {
            const self = this;
            
            Array.from(files).forEach(file => {
                if (this.validateFile(file, type)) {
                    this.uploadFile(file, type).then(response => {
                        self.addUploadedFile(file, response, type);
                    }).catch(error => {
                        self.showNotification('Upload failed: ' + error.message, 'error');
                    });
                }
            });
        },
        
        /**
         * Validate file before upload
         */
        validateFile: function(file, type) {
            // Check file size
            if (file.size > this.config.maxFileSize) {
                this.showNotification('File too large. Maximum size: 50MB', 'error');
                return false;
            }
            
            // Check file type
            const allowedTypes = type === 'image' ? 
                this.config.supportedImageTypes : 
                this.config.supportedDocTypes;
                
            if (!allowedTypes.includes(file.type)) {
                this.showNotification('Unsupported file type', 'error');
                return false;
            }
            
            return true;
        },
        
        /**
         * Upload file to server
         */
        uploadFile: function(file, type) {
            const formData = new FormData();
            formData.append('action', 'huraii_upload_file');
            formData.append('nonce', this.config.nonce);
            formData.append('file', file);
            formData.append('type', type);
            formData.append('agent', this.state.activeAgent);
            
            return $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            const percent = Math.round((e.loaded / e.total) * 100);
                            // Update progress indicator
                        }
                    });
                    return xhr;
                }
            });
        },
        
        /**
         * Add uploaded file to UI
         */
        addUploadedFile: function(file, response, type) {
            const fileData = {
                name: file.name,
                size: file.size,
                type: file.type,
                id: response.file_id,
                url: response.file_url,
                uploadTime: Date.now()
            };
            
            this.state.uploadedFiles[type + 's'].push(fileData);
            this.renderUploadedFile(fileData, type);
            this.trackInteraction('file_uploaded', { 
                type: type, 
                size: file.size, 
                agent: this.state.activeAgent 
            });
        },
        
        /**
         * Render uploaded file in UI
         */
        renderUploadedFile: function(fileData, type) {
            const container = type === 'image' ? '#uploaded-images' : '#uploaded-documents';
            const fileElement = this.createFileElement(fileData, type);
            $(container).append(fileElement);
        },
        
        /**
         * Create file element
         */
        createFileElement: function(fileData, type) {
            const isImage = type === 'image';
            const preview = isImage ? 
                `<img src="${fileData.url}" alt="${fileData.name}" class="file-preview">` :
                `<i class="fas fa-file-alt file-icon"></i>`;
                
            return $(`
                <div class="uploaded-file" data-file-id="${fileData.id}">
                    <div class="file-preview-container">
                        ${preview}
                    </div>
                    <div class="file-info">
                        <div class="file-name">${fileData.name}</div>
                        <div class="file-size">${this.formatFileSize(fileData.size)}</div>
                        <div class="file-actions">
                            <button class="file-action-btn use-btn" data-action="use">
                                <i class="fas fa-check"></i> Use
                            </button>
                            <button class="file-action-btn delete-btn" data-action="delete">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            `);
        },
        
        /**
         * Set processing mode
         */
        setProcessingMode: function(mode) {
            this.state.currentProcessingMode = mode;
            
            // Update UI
            $('.mode-card').removeClass('selected');
            $(`.mode-card.${mode}-mode`).addClass('selected');
            
            // Update configuration display
            const modeNames = {
                'gpu': 'GPU Only',
                'cpu': 'CPU Agents',
                'hybrid': 'Hybrid Mode'
            };
            
            $('#active-mode').text(modeNames[mode]);
            $('#primary-agent').text(mode === 'gpu' ? 'HURAII' : 'CPU Agents');
            $('#processing-type').text(mode.toUpperCase());
            
            this.trackInteraction('processing_mode_changed', { mode: mode });
        },
        
        /**
         * Set active agent
         */
        setActiveAgent: function(agent) {
            this.state.activeAgent = agent;
            $('.agent-status').removeClass('active');
            $(`.agent-status[data-agent="${agent}"]`).addClass('active');
            
            this.trackInteraction('agent_selected', { agent: agent });
        },
        
        /**
         * Record user feedback for agents
         */
        recordFeedback: function(agent, feedback) {
            if (!this.state.agentSatisfaction[agent]) return;
            
            // Update local state
            if (feedback === 'like') {
                this.state.agentSatisfaction[agent].likes++;
            } else {
                this.state.agentSatisfaction[agent].dislikes++;
            }
            
            // Calculate satisfaction percentage
            const total = this.state.agentSatisfaction[agent].likes + 
                         this.state.agentSatisfaction[agent].dislikes;
            const satisfaction = total > 0 ? 
                (this.state.agentSatisfaction[agent].likes / total) * 100 : 0;
            
            this.state.agentSatisfaction[agent].satisfaction = satisfaction;
            
            // Update UI
            this.updateSatisfactionUI(agent);
            
            // Send to server for persistence
            this.saveFeedbackToServer(agent, feedback);
            
            // Show notification
            this.showSatisfactionNotification(agent, feedback);
            
            // Track interaction
            this.trackInteraction('feedback_recorded', { 
                agent: agent, 
                feedback: feedback,
                satisfaction: satisfaction 
            });
        },
        
        /**
         * Update satisfaction UI
         */
        updateSatisfactionUI: function(agent) {
            const data = this.state.agentSatisfaction[agent];
            const satisfaction = Math.round(data.satisfaction);
            
            // Update counts
            $(`#${agent}-likes`).text(data.likes);
            $(`#${agent}-dislikes`).text(data.dislikes);
            $(`#${agent}-percent`).text(satisfaction + '%');
            
            // Update satisfaction bar
            $(`#${agent}-satisfaction`).css('width', satisfaction + '%');
            
            // Update status bar score
            $(`.agent-status[data-agent="${agent}"] .score-value`).text(satisfaction);
            $(`.agent-status[data-agent="${agent}"]`).attr('data-score', satisfaction);
            
            // Update overall satisfaction
            this.updateOverallSatisfaction();
        },
        
        /**
         * Update overall satisfaction metrics
         */
        updateOverallSatisfaction: function() {
            const agents = Object.keys(this.state.agentSatisfaction);
            const totalSatisfaction = agents.reduce((sum, agent) => {
                return sum + this.state.agentSatisfaction[agent].satisfaction;
            }, 0);
            
            const overallSatisfaction = Math.round(totalSatisfaction / agents.length);
            
            // Update overall meter
            $('#overall-satisfaction-fill').css('width', overallSatisfaction + '%');
            $('#overall-satisfaction-value').text(overallSatisfaction + '%');
            
            // Update analytics
            const totalLikes = agents.reduce((sum, agent) => {
                return sum + this.state.agentSatisfaction[agent].likes;
            }, 0);
            
            const totalDislikes = agents.reduce((sum, agent) => {
                return sum + this.state.agentSatisfaction[agent].dislikes;
            }, 0);
            
            const totalFeedback = totalLikes + totalDislikes;
            const positiveFeedback = totalFeedback > 0 ? 
                Math.round((totalLikes / totalFeedback) * 100) : 0;
            
            $('#total-interactions').text(this.state.totalInteractions);
            $('#positive-feedback').text(positiveFeedback + '%');
            $('#session-score').text(overallSatisfaction + '%');
            
            // Find most liked agent
            let mostLikedAgent = agents[0];
            let highestSatisfaction = 0;
            
            agents.forEach(agent => {
                if (this.state.agentSatisfaction[agent].satisfaction > highestSatisfaction) {
                    highestSatisfaction = this.state.agentSatisfaction[agent].satisfaction;
                    mostLikedAgent = agent;
                }
            });
            
            $('#most-liked-agent').text(mostLikedAgent.toUpperCase());
        },
        
        /**
         * Show satisfaction notification
         */
        showSatisfactionNotification: function(agent, feedback) {
            const notification = $('#satisfaction-notification');
            const icon = feedback === 'like' ? 'fas fa-thumbs-up' : 'fas fa-thumbs-down';
            const message = `Feedback recorded for ${agent.toUpperCase()}`;
            
            notification.find('.notification-icon').attr('class', `notification-icon ${icon}`);
            notification.find('.notification-message').text(message);
            
            notification.addClass(feedback === 'like' ? 'success' : 'warning').fadeIn();
            
            setTimeout(() => {
                notification.fadeOut().removeClass('success warning');
            }, 3000);
        },
        
        /**
         * Save feedback to server
         */
        saveFeedbackToServer: function(agent, feedback) {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'huraii_save_feedback',
                    nonce: this.config.nonce,
                    agent: agent,
                    feedback: feedback,
                    user_id: this.config.userId,
                    timestamp: Date.now()
                }
            });
        },
        
        /**
         * Start generation process
         */
        startGeneration: function() {
            const prompt = $('#generation-prompt').val().trim();
            if (!prompt) {
                this.showNotification('Please enter a generation prompt', 'error');
                return;
            }
            
            this.state.generationInProgress = true;
            this.showGenerationProgress();
            
            const generationData = {
                prompt: prompt,
                type: $('input[name="generation_type"]:checked').val(),
                mode: $('input[name="processing_mode"]:checked').val(),
                files: this.getSelectedFiles(),
                agent: this.state.activeAgent
            };
            
            this.performGeneration(generationData);
        },
        
        /**
         * Show generation progress
         */
        showGenerationProgress: function() {
            $('#generation-progress').show();
            $('#active-agent-display').html(`
                <div class="agent-working">
                    <i class="fas fa-cog fa-spin"></i>
                    <span>Working with ${this.state.activeAgent.toUpperCase()}</span>
                </div>
            `);
        },
        
        /**
         * Perform generation via AJAX
         */
        performGeneration: function(data) {
            const self = this;
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'huraii_generate_content',
                    nonce: this.config.nonce,
                    generation_data: JSON.stringify(data)
                },
                success: function(response) {
                    self.handleGenerationComplete(response);
                },
                error: function(xhr, status, error) {
                    self.handleGenerationError(error);
                }
            });
        },
        
        /**
         * Handle generation completion
         */
        handleGenerationComplete: function(response) {
            this.state.generationInProgress = false;
            $('#generation-progress').hide();
            
            if (response.success) {
                this.displayGenerationResults(response.data);
                this.trackInteraction('generation_completed', { 
                    success: true,
                    agent: this.state.activeAgent 
                });
            } else {
                this.handleGenerationError(response.data.message);
            }
        },
        
        /**
         * Handle generation error
         */
        handleGenerationError: function(error) {
            this.state.generationInProgress = false;
            $('#generation-progress').hide();
            this.showNotification('Generation failed: ' + error, 'error');
            
            this.trackInteraction('generation_failed', { 
                error: error,
                agent: this.state.activeAgent 
            });
        },
        
        /**
         * Display generation results
         */
        displayGenerationResults: function(results) {
            const container = $('#generated-images');
            container.empty();
            
            results.forEach(result => {
                const resultElement = this.createResultElement(result);
                container.append(resultElement);
            });
            
            $('#generation-results').show();
        },
        
        /**
         * Track user interactions
         */
        trackInteraction: function(action, data) {
            this.state.totalInteractions++;
            
            // Send to server for learning
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'huraii_track_interaction',
                    nonce: this.config.nonce,
                    interaction_action: action,
                    interaction_data: JSON.stringify(data),
                    timestamp: Date.now(),
                    user_id: this.config.userId
                }
            });
        },
        
        /**
         * Start real-time updates
         */
        startRealtimeUpdates: function() {
            if (this.state.realtimeFeedback) {
                setInterval(() => {
                    this.updateRealtimeMetrics();
                }, this.config.feedbackUpdateInterval);
            }
        },
        
        /**
         * Update real-time metrics
         */
        updateRealtimeMetrics: function() {
            // Update session duration
            const sessionDuration = Date.now() - this.state.sessionStartTime;
            
            // Update analytics
            this.updateOverallSatisfaction();
            
            // Check for satisfaction thresholds
            this.checkSatisfactionThresholds();
        },
        
        /**
         * Check satisfaction thresholds and alert if needed
         */
        checkSatisfactionThresholds: function() {
            Object.keys(this.state.agentSatisfaction).forEach(agent => {
                const satisfaction = this.state.agentSatisfaction[agent].satisfaction;
                if (satisfaction < this.config.satisfactionThreshold) {
                    // Low satisfaction alert could be implemented here
                }
            });
        },
        
        /**
         * Show notification
         */
        showNotification: function(message, type = 'info') {
            // Simple notification system
            const notification = $(`
                <div class="huraii-notification ${type}">
                    <span>${message}</span>
                    <button class="close-notification">&times;</button>
                </div>
            `);
            
            $('body').append(notification);
            
            setTimeout(() => {
                notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        /**
         * Format file size for display
         */
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        /**
         * Load initial satisfaction data
         */
        loadSatisfactionData: function() {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'huraii_load_satisfaction',
                    nonce: this.config.nonce,
                    user_id: this.config.userId
                },
                success: (response) => {
                    if (response.success) {
                        this.state.agentSatisfaction = response.data;
                        this.updateAllSatisfactionUI();
                    }
                }
            });
        },
        
        /**
         * Update all satisfaction UI elements
         */
        updateAllSatisfactionUI: function() {
            Object.keys(this.state.agentSatisfaction).forEach(agent => {
                this.updateSatisfactionUI(agent);
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        HuraiiGPUInterface.init();
    });
    
    // Export to global scope
    window.HuraiiGPUInterface = HuraiiGPUInterface;
    
})(jQuery); 