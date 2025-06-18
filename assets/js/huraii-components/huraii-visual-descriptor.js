/**
 * HURAII Visual Descriptor JavaScript
 * 
 * Handles frontend interactions for visual analysis and description using
 * Marianne Nems' Seed-Art Technique
 */

(function(global, $) {
    'use strict';
    
    // Visual Descriptor Module
    const VisualDescriptor = {
        /**
         * Module name
         */
        name: 'visualDescriptor',
        
        /**
         * Module configuration
         */
        config: {
            maxFileSize: 10 * 1024 * 1024, // 10MB
            allowedTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            analysisSteps: [
                'Uploading image...',
                'Analyzing sacred geometry...',
                'Extracting color harmony...',
                'Evaluating composition...',
                'Researching cultural elements...',
                'Generating descriptions...',
                'Creating prompt variations...'
            ]
        },
        
        /**
         * Module state
         */
        state: {
            isAnalyzing: false,
            currentStep: 0,
            analysisResult: null,
            uploadedImageData: null
        },
        
        /**
         * Initialize Visual Descriptor module
         * @param {Object} core HURAII core instance
         */
        init: function(core) {
            this.core = core;
            
            // Initialize UI components
            this._initUI();
            
            // Register event handlers
            this._registerEventHandlers();
            
            // Register with core
            core.registerComponent(this.name, this);
            
            return this;
        },
        
        /**
         * Initialize UI components
         * @private
         */
        _initUI: function() {
            // Add describe button to command interface if it doesn't exist
            if (!$('.command-btn.describe').length) {
                this._addDescribeButton();
            }
            
            // Create analysis modal
            this._createAnalysisModal();
            
            // Create upload dropzone
            this._createUploadDropzone();
        },
        
        /**
         * Add describe button to command interface
         * @private
         */
        _addDescribeButton: function() {
            const $commandButtons = $('.vortex-command-buttons, .command-buttons');
            
            if ($commandButtons.length) {
                const $describeBtn = $('<button>', {
                    type: 'button',
                    class: 'vortex-command-btn command-btn describe',
                    'data-command': 'describe',
                    'title': 'Analyze and describe uploaded image'
                }).html('<i class="fas fa-comment-alt"></i> Describe');
                
                $commandButtons.append($describeBtn);
            }
        },
        
        /**
         * Create analysis modal
         * @private
         */
        _createAnalysisModal: function() {
            const modalHtml = `
                <div id="huraii-visual-analysis-modal" class="huraii-modal" style="display: none;">
                    <div class="huraii-modal-content">
                        <div class="huraii-modal-header">
                            <h3><i class="fas fa-eye"></i> HURAII Visual Analysis</h3>
                            <button type="button" class="huraii-modal-close">&times;</button>
                        </div>
                        
                        <div class="huraii-modal-body">
                            <!-- Upload Section -->
                            <div class="huraii-upload-section" id="upload-section">
                                <div class="huraii-dropzone" id="image-dropzone">
                                    <div class="dropzone-content">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <h4>Upload Image for Analysis</h4>
                                        <p>Drop your image here or click to browse</p>
                                        <p class="file-requirements">Supports: JPEG, PNG, WebP, GIF (Max: 10MB)</p>
                                    </div>
                                    <input type="file" id="image-file-input" accept="image/*" style="display: none;">
                                </div>
                            </div>
                            
                            <!-- Analysis Progress Section -->
                            <div class="huraii-analysis-progress" id="analysis-progress" style="display: none;">
                                <div class="progress-header">
                                    <h4>Analyzing with Seed-Art Technique</h4>
                                    <div class="progress-steps">
                                        <div class="step-indicator" id="step-indicator">
                                            <span class="step-text">Initializing...</span>
                                            <div class="step-progress">
                                                <div class="progress-bar">
                                                    <div class="progress-fill" id="progress-fill"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Results Section -->
                            <div class="huraii-analysis-results" id="analysis-results" style="display: none;">
                                <div class="results-tabs">
                                    <button class="tab-btn active" data-tab="description">Description</button>
                                    <button class="tab-btn" data-tab="prompts">Prompt Variations</button>
                                    <button class="tab-btn" data-tab="analysis">Detailed Analysis</button>
                                    <button class="tab-btn" data-tab="cultural">Cultural Context</button>
                                </div>
                                
                                <div class="results-content">
                                    <!-- Description Tab -->
                                    <div class="tab-content active" id="tab-description">
                                        <div class="description-section">
                                            <h5>Primary Description</h5>
                                            <div class="description-text" id="primary-description"></div>
                                            <button class="copy-btn" data-copy="primary-description">
                                                <i class="fas fa-copy"></i> Copy Description
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Prompts Tab -->
                                    <div class="tab-content" id="tab-prompts">
                                        <div class="prompt-variations" id="prompt-variations"></div>
                                    </div>
                                    
                                    <!-- Analysis Tab -->
                                    <div class="tab-content" id="tab-analysis">
                                        <div class="detailed-analysis" id="detailed-analysis"></div>
                                    </div>
                                    
                                    <!-- Cultural Tab -->
                                    <div class="tab-content" id="tab-cultural">
                                        <div class="cultural-context" id="cultural-context"></div>
                                    </div>
                                </div>
                                
                                <div class="results-actions">
                                    <button class="action-btn generate-variations" id="generate-from-description">
                                        <i class="fas fa-magic"></i> Generate Variations
                                    </button>
                                    <button class="action-btn save-analysis" id="save-analysis">
                                        <i class="fas fa-save"></i> Save Analysis
                                    </button>
                                    <button class="action-btn new-analysis" id="new-analysis">
                                        <i class="fas fa-plus"></i> Analyze New Image
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
        },
        
        /**
         * Create upload dropzone
         * @private
         */
        _createUploadDropzone: function() {
            const $dropzone = $('#image-dropzone');
            const $fileInput = $('#image-file-input');
            
            // Handle click to open file dialog
            $dropzone.on('click', () => {
                $fileInput.click();
            });
            
            // Handle file selection
            $fileInput.on('change', (e) => {
                const files = e.target.files;
                if (files && files.length > 0) {
                    this.handleFileUpload(files[0]);
                }
            });
            
            // Handle drag and drop
            $dropzone.on('dragover', (e) => {
                e.preventDefault();
                $dropzone.addClass('dragover');
            });
            
            $dropzone.on('dragleave', (e) => {
                e.preventDefault();
                $dropzone.removeClass('dragover');
            });
            
            $dropzone.on('drop', (e) => {
                e.preventDefault();
                $dropzone.removeClass('dragover');
                
                const files = e.originalEvent.dataTransfer.files;
                if (files && files.length > 0) {
                    this.handleFileUpload(files[0]);
                }
            });
        },
        
        /**
         * Register event handlers
         * @private
         */
        _registerEventHandlers: function() {
            // Describe button click
            $(document).on('click', '.command-btn.describe', () => {
                this.openAnalysisModal();
            });
            
            // Modal close
            $(document).on('click', '.huraii-modal-close', () => {
                this.closeAnalysisModal();
            });
            
            // Tab switching
            $(document).on('click', '.results-tabs .tab-btn', (e) => {
                this.switchTab($(e.target).data('tab'));
            });
            
            // Copy button clicks
            $(document).on('click', '.copy-btn', (e) => {
                this.copyToClipboard($(e.target).data('copy'));
            });
            
            // Action button clicks
            $(document).on('click', '#generate-from-description', () => {
                this.generateFromDescription();
            });
            
            $(document).on('click', '#new-analysis', () => {
                this.resetAnalysis();
            });
        },
        
        /**
         * Open analysis modal
         */
        openAnalysisModal: function() {
            $('#huraii-visual-analysis-modal').fadeIn(300);
            this.resetAnalysis();
        },
        
        /**
         * Close analysis modal
         */
        closeAnalysisModal: function() {
            $('#huraii-visual-analysis-modal').fadeOut(300);
        },
        
        /**
         * Handle file upload and validation
         * @param {File} file Selected file
         */
        handleFileUpload: function(file) {
            // Validate file type
            if (!this.config.allowedTypes.includes(file.type)) {
                this.showError('Invalid file type. Please upload JPEG, PNG, WebP, or GIF.');
                return;
            }
            
            // Validate file size
            if (file.size > this.config.maxFileSize) {
                this.showError('File size too large. Maximum allowed size is 10MB.');
                return;
            }
            
            // Show preview
            this.showImagePreview(file);
            
            // Start analysis
            this.startAnalysis(file);
        },
        
        /**
         * Show image preview
         * @param {File} file Image file
         */
        showImagePreview: function(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const $dropzone = $('#image-dropzone');
                $dropzone.html(`
                    <div class="image-preview">
                        <img src="${e.target.result}" alt="Uploaded image" style="max-width: 100%; max-height: 200px;">
                        <p class="file-name">${file.name}</p>
                    </div>
                `);
                this.state.uploadedImageData = e.target.result;
            };
            reader.readAsDataURL(file);
        },
        
        /**
         * Start visual analysis
         * @param {File} file Image file
         */
        startAnalysis: function(file) {
            this.state.isAnalyzing = true;
            this.state.currentStep = 0;
            
            // Hide upload section, show progress
            $('#upload-section').hide();
            $('#analysis-progress').show();
            
            // Start progress animation
            this.animateProgress();
            
            // Prepare form data
            const formData = new FormData();
            formData.append('image', file);
            formData.append('action', 'huraii_describe_visual');
            formData.append('nonce', huraiiDescriptor.nonce);
            
            // Send analysis request
            $.ajax({
                url: huraiiDescriptor.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.displayResults(response.data);
                    } else {
                        this.showError(response.data || 'Analysis failed');
                    }
                },
                error: (xhr, status, error) => {
                    this.showError('Network error: ' + error);
                },
                complete: () => {
                    this.state.isAnalyzing = false;
                }
            });
        },
        
        /**
         * Animate analysis progress
         * @private
         */
        animateProgress: function() {
            const $stepText = $('#step-indicator .step-text');
            const $progressFill = $('#progress-fill');
            
            const animateStep = (step) => {
                if (step >= this.config.analysisSteps.length || !this.state.isAnalyzing) {
                    return;
                }
                
                $stepText.text(this.config.analysisSteps[step]);
                $progressFill.css('width', ((step + 1) / this.config.analysisSteps.length * 100) + '%');
                
                setTimeout(() => {
                    animateStep(step + 1);
                }, 1500);
            };
            
            animateStep(0);
        },
        
        /**
         * Display analysis results
         * @param {Object} results Analysis results
         */
        displayResults: function(results) {
            this.state.analysisResult = results;
            
            // Hide progress, show results
            $('#analysis-progress').hide();
            $('#analysis-results').show();
            
            // Populate primary description
            $('#primary-description').text(results.primary_description);
            
            // Populate prompt variations
            this.displayPromptVariations(results.prompt_variations);
            
            // Populate detailed analysis
            this.displayDetailedAnalysis(results.detailed_analysis);
            
            // Populate cultural context
            this.displayCulturalContext(results.cultural_context);
        },
        
        /**
         * Display prompt variations
         * @param {Array} variations Prompt variations
         */
        displayPromptVariations: function(variations) {
            const $container = $('#prompt-variations');
            $container.empty();
            
            if (!variations || variations.length === 0) {
                $container.html('<p>No prompt variations generated.</p>');
                return;
            }
            
            variations.forEach((variation, index) => {
                const $variation = $(`
                    <div class="prompt-variation">
                        <div class="variation-header">
                            <h6>Variation ${index + 1}: ${variation.focus}</h6>
                            <button class="copy-btn" data-copy-text="${variation.prompt}">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <div class="variation-prompt">${variation.prompt}</div>
                        <button class="generate-btn" data-prompt="${variation.prompt}">
                            <i class="fas fa-magic"></i> Generate with this prompt
                        </button>
                    </div>
                `);
                $container.append($variation);
            });
        },
        
        /**
         * Display detailed analysis
         * @param {Object} analysis Detailed analysis data
         */
        displayDetailedAnalysis: function(analysis) {
            const $container = $('#detailed-analysis');
            $container.html(`
                <div class="analysis-content">
                    <p>${analysis || 'Detailed analysis not available.'}</p>
                </div>
            `);
        },
        
        /**
         * Display cultural context
         * @param {Object} cultural Cultural context data
         */
        displayCulturalContext: function(cultural) {
            const $container = $('#cultural-context');
            $container.html(`
                <div class="cultural-content">
                    <p>${cultural || 'Cultural context not available.'}</p>
                </div>
            `);
        },
        
        /**
         * Switch result tabs
         * @param {string} tabName Tab to switch to
         */
        switchTab: function(tabName) {
            $('.results-tabs .tab-btn').removeClass('active');
            $('.tab-content').removeClass('active');
            
            $(`.results-tabs .tab-btn[data-tab="${tabName}"]`).addClass('active');
            $(`#tab-${tabName}`).addClass('active');
        },
        
        /**
         * Copy text to clipboard
         * @param {string} elementId Element ID to copy from
         */
        copyToClipboard: function(elementId) {
            const text = $(`#${elementId}`).text() || $(elementId).text();
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showSuccess('Copied to clipboard!');
                });
            } else {
                // Fallback for older browsers
                const $temp = $('<textarea>').val(text).appendTo('body').select();
                document.execCommand('copy');
                $temp.remove();
                this.showSuccess('Copied to clipboard!');
            }
        },
        
        /**
         * Generate artwork from description
         */
        generateFromDescription: function() {
            if (!this.state.analysisResult) {
                this.showError('No analysis result available');
                return;
            }
            
            const prompt = this.state.analysisResult.primary_description;
            
            // Close modal and trigger generation
            this.closeAnalysisModal();
            
            // Use the main generation interface
            if (this.core && this.core.getComponent('midjourneyUI')) {
                const mjUI = this.core.getComponent('midjourneyUI');
                mjUI._executeCommand('imagine', { prompt: prompt });
            } else {
                // Fallback: populate prompt input
                $('#vortex-midjourney-prompt, .prompt-input').val(prompt).focus();
            }
        },
        
        /**
         * Reset analysis state
         */
        resetAnalysis: function() {
            this.state.isAnalyzing = false;
            this.state.currentStep = 0;
            this.state.analysisResult = null;
            this.state.uploadedImageData = null;
            
            // Reset UI
            $('#upload-section').show();
            $('#analysis-progress, #analysis-results').hide();
            
            // Reset dropzone
            $('#image-dropzone').html(`
                <div class="dropzone-content">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h4>Upload Image for Analysis</h4>
                    <p>Drop your image here or click to browse</p>
                    <p class="file-requirements">Supports: JPEG, PNG, WebP, GIF (Max: 10MB)</p>
                </div>
            `);
            
            // Reset file input
            $('#image-file-input').val('');
        },
        
        /**
         * Show error message
         * @param {string} message Error message
         */
        showError: function(message) {
            // Use existing UI error system if available
            if (this.core && this.core.getComponent('ui')) {
                this.core.getComponent('ui').showError(message);
            } else {
                alert('Error: ' + message);
            }
        },
        
        /**
         * Show success message
         * @param {string} message Success message
         */
        showSuccess: function(message) {
            // Use existing UI success system if available
            if (this.core && this.core.getComponent('ui')) {
                this.core.getComponent('ui').showSuccess(message);
            } else {
                // Simple success indication
                console.log('Success: ' + message);
            }
        }
    };
    
    // Register with HURAII when loaded
    if (global.HURAII) {
        global.HURAII.registerComponent('visualDescriptor', VisualDescriptor);
    } else {
        // Wait for HURAII to be defined
        document.addEventListener('DOMContentLoaded', () => {
            if (global.HURAII) {
                global.HURAII.registerComponent('visualDescriptor', VisualDescriptor);
            } else {
                console.warn('HURAII core module not found. Visual Descriptor module initialization failed.');
            }
        });
    }
    
})(window, jQuery); 