/**
 * HURAII UI Component
 * Adds HURAII-inspired UI features while maintaining compatibility with existing functionality
 */

(function(global, $) {
  'use strict';
  
  // HURAII UI Module
  const HuraiiUI = {
    /**
     * Module name
     */
    name: 'huraiiUI',
    
    /**
     * Module configuration
     */
    config: {
      gridLayout: true,
      showCommandBar: true,
      enableVariations: true,
      enableUpscaling: true,
      variationCount: 4,
      maxGridCols: 4,
      aspectRatios: {
        'square': '1:1',
        'portrait': '2:3',
        'landscape': '3:2',
        'wide': '16:9',
        'cinematic': '21:9'
      },
      defaultCommands: [
        { name: 'imagine', icon: 'magic', description: 'Generate an image from your text prompt' },
        { name: 'variate', icon: 'random', description: 'Create variations of the selected image' },
        { name: 'upscale', icon: 'resize', description: 'Create high-resolution version of the selected image' },
        { name: 'blend', icon: 'object-group', description: 'Blend multiple images together' },
        { name: 'describe', icon: 'comment', description: 'Generate a description for an image' }
      ]
    },
    
    /**
     * UI state
     */
    state: {
      currentCommand: 'imagine',
      selectedImages: [],
      variationSets: {},
      gridLayout: 'adaptive',
      activeVariation: null,
      lastGeneratedJobId: null,
      pendingJobs: [],
      relatedImages: {}
    },
    
    /**
     * Initialize HURAII UI module
     * @param {Object} core HURAII core instance
     */
    init: function(core) {
      this.core = core;
      
      // Merge config from core
      this.config = { 
        ...this.config, 
        ...core.config.huraiiUI 
      };
      
      // Initialize UI components
      this._initUI();
      
      // Register with core
      core.registerComponent(this.name, this);
      
      return this;
    },
    
    /**
     * Initialize UI
     * @private
     */
    _initUI: function() {
      // Create HURAII-like interface container if it doesn't exist
      if (!$('#vortex-huraii-container').length) {
        this._createMainInterface();
      }
      
      // Register event handlers
      this._registerEventHandlers();
      
      // Initialize the command interface
      this._initCommandInterface();
      
      // Initialize grid layout if enabled
      if (this.config.gridLayout) {
        this._initGridLayout();
      }
    },
    
    /**
     * Create main HURAII-like interface
     * @private
     */
    _createMainInterface: function() {
      // Create container for huraii-like interface
      const $container = $('<div>', {
        id: 'vortex-huraii-container',
        class: 'vortex-huraii-interface'
      });
      
      // Create the command bar
      const $commandBar = $('<div>', {
        class: 'vortex-command-bar'
      });
      
      // Create input area with slash commands
      const $inputArea = $('<div>', {
        class: 'vortex-input-area'
      });
      
      // Create grid for results
      const $gridContainer = $('<div>', {
        class: 'vortex-grid-container'
      });
      
      // Add the components to the container
      $inputArea.append(this._createPromptInput());
      $commandBar.append(this._createCommandButtons());
      
      $container.append($commandBar);
      $container.append($inputArea);
      $container.append($gridContainer);
      
      // Add to main interface if it exists, otherwise to body
      const $interface = $('.vortex-huraii-interface');
      if ($interface.length) {
        $interface.append($container);
      } else {
        $('body').append($container);
      }
      
      // Update content container for compatibility
      this.contentContainer = $gridContainer;
    },
    
    /**
     * Create prompt input with slash commands
     * @returns {jQuery} jQuery object containing the input element
     * @private
     */
    _createPromptInput: function() {
      const $inputContainer = $('<div>', {
        class: 'vortex-input-container'
      });
      
      // Prefix area for slash command indicator
      const $prefixArea = $('<div>', {
        class: 'vortex-input-prefix'
      }).text('/imagine');
      
      // Main input for prompt
      const $input = $('<textarea>', {
        id: 'vortex-huraii-prompt',
        class: 'vortex-huraii-prompt',
        placeholder: 'Type a detailed description of what you want to create...'
      });
      
      // Action buttons
      const $actions = $('<div>', {
        class: 'vortex-input-actions'
      });
      
      // Generate button
      const $generateBtn = $('<button>', {
        type: 'button',
        class: 'vortex-generate-btn',
        'data-command': 'imagine'
      }).text('Generate');
      
      // Aspect ratio selector
      const $aspectRatio = $('<select>', {
        class: 'vortex-aspect-ratio'
      });
      
      // Add aspect ratio options
      $.each(this.config.aspectRatios, (key, value) => {
        $aspectRatio.append($('<option>', {
          value: key,
          text: value
        }));
      });
      
      // Add components to container
      $actions.append($aspectRatio);
      $actions.append($generateBtn);
      $inputContainer.append($prefixArea);
      $inputContainer.append($input);
      $inputContainer.append($actions);
      
      return $inputContainer;
    },
    
    /**
     * Create command buttons for HURAII-like interface
     * @returns {jQuery} jQuery object containing the command buttons
     * @private
     */
    _createCommandButtons: function() {
      const $commandButtons = $('<div>', {
        class: 'vortex-command-buttons'
      });
      
      // Add buttons for each command
      $.each(this.config.defaultCommands, (i, command) => {
        const $button = $('<button>', {
          type: 'button',
          class: 'vortex-command-btn' + (command.name === 'imagine' ? ' active' : ''),
          'data-command': command.name,
          'title': command.description
        }).html(`<i class="fas fa-${command.icon}"></i> ${command.name}`);
        
        $commandButtons.append($button);
      });
      
      return $commandButtons;
    },
    
    /**
     * Initialize grid layout
     * @private
     */
    _initGridLayout: function() {
      const $grid = $('<div>', {
        class: 'vortex-grid'
      });
      
      this.contentContainer.append($grid);
      
      // Initialize masonry grid if available
      if ($.fn.masonry) {
        $grid.masonry({
          itemSelector: '.vortex-grid-item',
          columnWidth: '.vortex-grid-sizer',
          percentPosition: true
        });
      }
    },
    
    /**
     * Initialize command interface
     * @private
     */
    _initCommandInterface: function() {
      // Create slash commands autocomplete
      const slashCommands = this.config.defaultCommands.map(cmd => '/' + cmd.name);
      
      // Initialize slash command autocomplete
      $('#vortex-huraii-prompt').on('keydown', (e) => {
        // Show slash commands on '/' key
        if (e.key === '/' && $(e.target).val() === '') {
          this._showSlashCommandSuggestions($(e.target));
        }
      });
    },
    
    /**
     * Register event handlers
     * @private
     */
    _registerEventHandlers: function() {
      // Command button click
      $(document).on('click', '.vortex-command-btn', (e) => {
        const command = $(e.currentTarget).data('command');
        this._handleCommandSelection(command);
      });
      
      // Generate button click
      $(document).on('click', '.vortex-generate-btn', (e) => {
        const command = $(e.currentTarget).data('command');
        this._executeCommand(command);
      });
      
      // Image selection in grid
      $(document).on('click', '.vortex-grid-item', (e) => {
        this._handleImageSelection($(e.currentTarget));
      });
      
      // Variation buttons click
      $(document).on('click', '.vortex-variation-btn', (e) => {
        e.stopPropagation();
        const action = $(e.currentTarget).data('action');
        const imageId = $(e.currentTarget).closest('.vortex-grid-item').data('id');
        this._handleVariationAction(action, imageId);
      });
      
      // Handle Enter key in prompt input
      $('#vortex-huraii-prompt').on('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          this._executeCommand(this.state.currentCommand);
        }
      });
    },
    
    /**
     * Handle command selection
     * @param {string} command The selected command
     * @private
     */
    _handleCommandSelection: function(command) {
      // Update active command
      this.state.currentCommand = command;
      
      // Update UI for selected command
      $('.vortex-command-btn').removeClass('active');
      $(`.vortex-command-btn[data-command="${command}"]`).addClass('active');
      
      // Update input prefix
      $('.vortex-input-prefix').text('/' + command);
      
      // Update input placeholder based on command
      const placeholders = {
        'imagine': 'Type a detailed description of what you want to create...',
        'variate': 'Select an image first, then click Variate',
        'upscale': 'Select an image first, then click Upscale',
        'blend': 'Select multiple images, then click Blend',
        'describe': 'Select an image to generate a description'
      };
      
      $('#vortex-huraii-prompt').attr('placeholder', placeholders[command] || placeholders.imagine);
      
      // Update generate button text
      $('.vortex-generate-btn').text(command.charAt(0).toUpperCase() + command.slice(1));
      $('.vortex-generate-btn').data('command', command);
      
      // If command requires image selection, check if we have selected images
      if (['variate', 'upscale', 'describe'].includes(command) && this.state.selectedImages.length === 0) {
        $('.vortex-generate-btn').prop('disabled', true);
      } else if (command === 'blend' && this.state.selectedImages.length < 2) {
        $('.vortex-generate-btn').prop('disabled', true);
      } else {
        $('.vortex-generate-btn').prop('disabled', false);
      }
    },
    
    /**
     * Execute the current command
     * @param {string} command Command to execute
     * @private
     */
    _executeCommand: function(command) {
      const prompt = $('#vortex-huraii-prompt').val();
      const aspectRatio = $('.vortex-aspect-ratio').val();
      
      // Default parameters
      let params = {
        prompt: prompt,
        aspectRatio: aspectRatio,
        selectedImages: [...this.state.selectedImages]
      };
      
      // Execute based on command type
      switch (command) {
        case 'imagine':
          if (!prompt) {
            this._showError('Please enter a prompt');
            return;
          }
          this._generateImage(params);
          break;
        
        case 'variate':
          if (this.state.selectedImages.length === 0) {
            this._showError('Please select an image to create variations');
            return;
          }
          this._createVariations(params);
          break;
          
        case 'upscale':
          if (this.state.selectedImages.length === 0) {
            this._showError('Please select an image to upscale');
            return;
          }
          this._upscaleImage(params);
          break;
          
        case 'blend':
          if (this.state.selectedImages.length < 2) {
            this._showError('Please select at least 2 images to blend');
            return;
          }
          this._blendImages(params);
          break;
          
        case 'describe':
          if (this.state.selectedImages.length === 0) {
            this._showError('Please select an image to describe');
            return;
          }
          this._describeImage(params);
          break;
          
        default:
          console.warn('Unknown command:', command);
      }
    },
    
    /**
     * Generate image based on prompt
     * @param {Object} params Generation parameters
     * @private
     */
    _generateImage: function(params) {
      // Show loading state
      this._showGeneratingState();
      
      // Calculate dimensions based on aspect ratio
      const dimensions = this._getDimensionsFromAspectRatio(params.aspectRatio);
      
      // Get API component
      const api = this.core.getComponent('api');
      if (!api) {
        this._showError('API component not available');
        this._hideGeneratingState();
        return;
      }
      
      // Assign job ID
      const jobId = this._generateJobId();
      this.state.lastGeneratedJobId = jobId;
      
      // Track pending job
      this.state.pendingJobs.push(jobId);
      
      // Make API request to generate image
      api.generateArtwork({
        prompt: params.prompt,
        negative_prompt: '',
        width: dimensions.width,
        height: dimensions.height,
        variation_count: this.config.variationCount,
        format: '2d',
        job_id: jobId
      }).then(result => {
        // Process result as HURAII grid
        this._processGenerationResult(result, jobId, params.prompt);
      }).catch(error => {
        this._showError(error.message || 'Error generating image');
      }).finally(() => {
        // Remove from pending jobs
        this.state.pendingJobs = this.state.pendingJobs.filter(job => job !== jobId);
        
        // Hide loading state if no more pending jobs
        if (this.state.pendingJobs.length === 0) {
          this._hideGeneratingState();
        }
      });
    },
    
    /**
     * Create variations of the selected image
     * @param {Object} params Variation parameters
     * @private
     */
    _createVariations: function(params) {
      // Get the selected image
      const imageId = params.selectedImages[0];
      
      // Show loading state
      this._showGeneratingState();
      
      // Get API component
      const api = this.core.getComponent('api');
      if (!api) {
        this._showError('API component not available');
        this._hideGeneratingState();
        return;
      }
      
      // Assign job ID
      const jobId = this._generateJobId();
      this.state.lastGeneratedJobId = jobId;
      
      // Track pending job
      this.state.pendingJobs.push(jobId);
      
      // Make API request to create variations
      api.request('generate', {
        action: 'vortex_huraii_generate',
        params: {
          image_id: imageId,
          variation_count: this.config.variationCount,
          format: '2d',
          job_id: jobId
        },
        nonce: this.core.config.nonce
      }).then(result => {
        // Process result as variation grid
        this._processVariationResult(result, jobId, imageId);
      }).catch(error => {
        this._showError(error.message || 'Error creating variations');
      }).finally(() => {
        // Remove from pending jobs
        this.state.pendingJobs = this.state.pendingJobs.filter(job => job !== jobId);
        
        // Hide loading state if no more pending jobs
        if (this.state.pendingJobs.length === 0) {
          this._hideGeneratingState();
        }
      });
    },
    
    /**
     * Upscale selected image
     * @param {Object} params Upscale parameters
     * @private
     */
    _upscaleImage: function(params) {
      // Get the selected image
      const imageId = params.selectedImages[0];
      
      // Show loading state
      this._showGeneratingState();
      
      // Get API component
      const api = this.core.getComponent('api');
      if (!api) {
        this._showError('API component not available');
        this._hideGeneratingState();
        return;
      }
      
      // Assign job ID
      const jobId = this._generateJobId();
      
      // Track pending job
      this.state.pendingJobs.push(jobId);
      
      // Make API request to upscale image
      api.request('upscale', {
        action: 'vortex_huraii_upscale',
        params: {
          image_id: imageId,
          scale_factor: 4,
          job_id: jobId
        },
        nonce: this.core.config.nonce
      }).then(result => {
        // Add upscaled image to grid
        this._addUpscaledImage(result, imageId);
      }).catch(error => {
        this._showError(error.message || 'Error upscaling image');
      }).finally(() => {
        // Remove from pending jobs
        this.state.pendingJobs = this.state.pendingJobs.filter(job => job !== jobId);
        
        // Hide loading state if no more pending jobs
        if (this.state.pendingJobs.length === 0) {
          this._hideGeneratingState();
        }
      });
    },
    
    /**
     * Process generation result and add to grid
     * @param {Object} result Generation result
     * @param {string} jobId Job ID
     * @param {string} prompt Generation prompt
     * @private
     */
    _processGenerationResult: function(result, jobId, prompt) {
      if (!result || !result.images || result.images.length === 0) {
        this._showError('No images were generated');
        return;
      }
      
      // Get grid container
      const $grid = $('.vortex-grid');
      
      // Create job container
      const $jobContainer = $('<div>', {
        class: 'vortex-job-container',
        'data-job-id': jobId
      });
      
      // Add job info
      const $jobInfo = $('<div>', {
        class: 'vortex-job-info'
      }).html(`<div class="vortex-job-prompt">${prompt}</div>`);
      
      $jobContainer.append($jobInfo);
      
      // Create grid row
      const $gridRow = $('<div>', {
        class: 'vortex-grid-row'
      });
      
      // Create and add grid items for each image
      result.images.forEach((image, index) => {
        const imageId = `${jobId}_${index}`;
        
        // Add to related images tracking
        if (!this.state.relatedImages[jobId]) {
          this.state.relatedImages[jobId] = [];
        }
        this.state.relatedImages[jobId].push(imageId);
        
        const $gridItem = this._createGridItem(image.url, imageId, {
          prompt: prompt,
          jobId: jobId,
          index: index,
          seed: image.seed
        });
        
        $gridRow.append($gridItem);
      });
      
      $jobContainer.append($gridRow);
      $grid.prepend($jobContainer);
      
      // Refresh masonry layout if available
      if ($grid.data('masonry')) {
        $grid.masonry('prepended', $jobContainer);
      }
    },
    
    /**
     * Create grid item for image
     * @param {string} imageUrl URL of the image
     * @param {string} imageId ID for the image
     * @param {Object} metadata Image metadata
     * @returns {jQuery} jQuery object containing the grid item
     * @private
     */
    _createGridItem: function(imageUrl, imageId, metadata) {
      const $gridItem = $('<div>', {
        class: 'vortex-grid-item',
        'data-id': imageId
      });
      
      // Create image container
      const $imageContainer = $('<div>', {
        class: 'vortex-image-container'
      });
      
      // Create image
      const $image = $('<img>', {
        src: imageUrl,
        alt: metadata.prompt || 'Generated image',
        loading: 'lazy'
      });
      
      // Create image actions
      const $actions = $('<div>', {
        class: 'vortex-image-actions'
      });
      
      // Create action buttons
      const $variateBtn = $('<button>', {
        type: 'button',
        class: 'vortex-variation-btn',
        'data-action': 'variate',
        'title': 'Create variations'
      }).html('<i class="fas fa-random"></i> V');
      
      const $upscaleBtn = $('<button>', {
        type: 'button',
        class: 'vortex-variation-btn',
        'data-action': 'upscale',
        'title': 'Upscale'
      }).html('<i class="fas fa-expand"></i> U');
      
      // Add elements to container
      $actions.append($variateBtn);
      $actions.append($upscaleBtn);
      $imageContainer.append($image);
      $gridItem.append($imageContainer);
      $gridItem.append($actions);
      
      // Store metadata
      $gridItem.data('metadata', metadata);
      
      return $gridItem;
    },
    
    /**
     * Show generating state
     * @private
     */
    _showGeneratingState: function() {
      // Disable input and generate button
      $('#vortex-huraii-prompt').prop('disabled', true);
      $('.vortex-generate-btn').prop('disabled', true).text('Generating...');
      
      // Show progress indicator
      if (!$('.vortex-progress-indicator').length) {
        const $progress = $('<div>', {
          class: 'vortex-progress-indicator'
        }).html('<div class="vortex-spinner"></div><span>Generating...</span>');
        
        $('.vortex-input-area').append($progress);
      }
    },
    
    /**
     * Hide generating state
     * @private
     */
    _hideGeneratingState: function() {
      // Enable input and generate button
      $('#vortex-huraii-prompt').prop('disabled', false);
      $('.vortex-generate-btn').prop('disabled', false).text(
        this.state.currentCommand.charAt(0).toUpperCase() + this.state.currentCommand.slice(1)
      );
      
      // Hide progress indicator
      $('.vortex-progress-indicator').remove();
    },
    
    /**
     * Show error message
     * @param {string} message Error message
     * @private
     */
    _showError: function(message) {
      // Get UI component for showing message
      const ui = this.core.getComponent('ui');
      if (ui && ui.showError) {
        ui.showError(message);
      } else {
        // Fallback if UI component not available
        alert(message);
      }
    },
    
    /**
     * Generate unique job ID
     * @returns {string} Unique job ID
     * @private
     */
    _generateJobId: function() {
      return `job_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    },
    
    /**
     * Get dimensions from aspect ratio
     * @param {string} aspectRatio Aspect ratio key
     * @returns {Object} Width and height
     * @private
     */
    _getDimensionsFromAspectRatio: function(aspectRatio) {
      const baseSize = 1024;
      
      switch (aspectRatio) {
        case 'portrait':
          return { width: Math.round(baseSize * 2/3), height: baseSize };
        case 'landscape':
          return { width: baseSize, height: Math.round(baseSize * 2/3) };
        case 'wide':
          return { width: baseSize, height: Math.round(baseSize * 9/16) };
        case 'cinematic':
          return { width: baseSize, height: Math.round(baseSize * 9/21) };
        case 'square':
        default:
          return { width: baseSize, height: baseSize };
      }
    },
    
    /**
     * Handle image selection
     * @param {jQuery} $gridItem Selected grid item
     * @private
     */
    _handleImageSelection: function($gridItem) {
      const imageId = $gridItem.data('id');
      
      // Toggle selection
      if ($gridItem.hasClass('selected')) {
        $gridItem.removeClass('selected');
        this.state.selectedImages = this.state.selectedImages.filter(id => id !== imageId);
      } else {
        // If current command only allows one image, deselect others
        if (['upscale', 'variate', 'describe'].includes(this.state.currentCommand)) {
          $('.vortex-grid-item').removeClass('selected');
          this.state.selectedImages = [];
        }
        
        $gridItem.addClass('selected');
        this.state.selectedImages.push(imageId);
      }
      
      // Update generate button state based on selection
      if (['variate', 'upscale', 'describe'].includes(this.state.currentCommand) && this.state.selectedImages.length === 0) {
        $('.vortex-generate-btn').prop('disabled', true);
      } else if (this.state.currentCommand === 'blend' && this.state.selectedImages.length < 2) {
        $('.vortex-generate-btn').prop('disabled', true);
      } else {
        $('.vortex-generate-btn').prop('disabled', false);
      }
    },
    
    /**
     * Handle variation action button click
     * @param {string} action Action to perform
     * @param {string} imageId ID of the image
     * @private
     */
    _handleVariationAction: function(action, imageId) {
      // Select the image
      const $gridItem = $(`.vortex-grid-item[data-id="${imageId}"]`);
      $('.vortex-grid-item').removeClass('selected');
      $gridItem.addClass('selected');
      this.state.selectedImages = [imageId];
      
      // Execute the action
      switch (action) {
        case 'variate':
          this._handleCommandSelection('variate');
          this._executeCommand('variate');
          break;
          
        case 'upscale':
          this._handleCommandSelection('upscale');
          this._executeCommand('upscale');
          break;
      }
    }
  };
  
  // Register with HURAII when loaded
  if (global.HURAII) {
    global.HURAII.registerComponent('huraiiUI', HuraiiUI);
  } else {
    // Wait for HURAII to be defined
    document.addEventListener('DOMContentLoaded', () => {
      if (global.HURAII) {
        global.HURAII.registerComponent('huraiiUI', HuraiiUI);
      } else {
        console.error('HURAII core module not found. HURAII UI module initialization failed.');
      }
    });
  }
  
})(window, jQuery); 