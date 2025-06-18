/**
 * HURAII Demo - Optimized Artist Journey
 * 
 * This demo showcases the optimized HURAII API for an artist's workflow
 * from concept to finished artwork, demonstrating:
 * - Batch processing for learning data
 * - Advanced LRU caching
 * - WebSocket real-time updates
 * - Performance telemetry
 * - Image upload and multi-variation generation
 */
(function(global, $) {
  'use strict';
  
  // Demo initialization
  $(document).ready(function() {
    // Initialize HURAII if needed
    initializeHURAII().then(() => {
      // Setup UI elements and event handlers
      setupUI();
      
      // Show welcome message
      showStatus('HURAII Artist Journey Demo Ready. Let\'s create something amazing!');
      
      // Log performance metrics periodically
      setInterval(logPerformanceMetrics, 5000);
    }).catch(error => {
      showError('Failed to initialize HURAII: ' + error.message);
    });
  });
  
  /**
   * Initialize HURAII with optimized components
   */
  function initializeHURAII() {
    return new Promise((resolve, reject) => {
      try {
        // Check if HURAII is already initialized
        if (window.HURAII && window.HURAII.initialized) {
          showStatus('HURAII already initialized');
          resolve(window.HURAII);
          return;
        }
        
        // Initialize HURAII core
        window.HURAII = {
          name: 'HURAII',
          version: '2.0.0',
          initialized: false,
          components: {},
          state: {
            isGenerating: false,
            processingStart: new Date(),
            interactionHistory: [],
            generation: {
              currentRequest: null,
              recentImages: []
            },
            seedImageData: null,  // For storing uploaded image
            multiVariationResults: []  // For storing multiple variation results
          },
          config: {
            apiEndpoint: '/wp-admin/admin-ajax.php',
            userId: window.vortexUserId || 'anonymous',
            nonce: window.vortexNonce || '',
            debug: true
          },
          
          // Component registration
          registerComponent: function(name, component) {
            this.components[name] = component;
            console.log(`HURAII: Component ${name} registered`);
          },
          
          // Event system
          _eventListeners: {},
          on: function(event, callback) {
            if (!this._eventListeners[event]) {
              this._eventListeners[event] = [];
            }
            this._eventListeners[event].push(callback);
            return this;
          },
          off: function(event, callback) {
            if (!this._eventListeners[event]) return this;
            if (!callback) {
              this._eventListeners[event] = [];
            } else {
              this._eventListeners[event] = this._eventListeners[event].filter(cb => cb !== callback);
            }
            return this;
          },
          emit: function(event, data) {
            if (!this._eventListeners[event]) return;
            this._eventListeners[event].forEach(callback => {
              try {
                callback(data);
              } catch (e) {
                console.error(`Error in ${event} event handler:`, e);
              }
            });
          },
          
          // Session management
          getSessionId: function() {
            if (!sessionStorage.getItem('huraii_session_id')) {
              sessionStorage.setItem('huraii_session_id', `session_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`);
            }
            return sessionStorage.getItem('huraii_session_id');
          },
          
          // Initialization
          init: function() {
            // Initialize in sequence to ensure dependencies
            Promise.resolve()
              // First, initialize LRU cache
              .then(() => {
                if (this.components.lruCache) {
                  return this.components.lruCache.init(this, {
                    maxSize: 1000,
                    persistToDisk: true
                  });
                }
              })
              // Next, initialize WebSocket for real-time updates
              .then(() => {
                if (this.components.websocket) {
                  return this.components.websocket.init(this, {
                    endpoint: 'wss://' + window.location.host + '/huraii/ws',
                    debug: true
                  });
                }
              })
              // Finally, initialize API component
              .then(() => {
                if (this.components.api) {
                  return this.components.api.init(this, {
                    useWebSocket: true,
                    useAdvancedCache: true,
                    circuitBreakerThreshold: 5,
                    batchSize: 20
                  });
                }
              })
              .then(() => {
                this.initialized = true;
                this.emit('system_ready', { timestamp: Date.now() });
                console.log('HURAII system initialized successfully');
                resolve(this);
              })
              .catch(error => {
                console.error('HURAII initialization error:', error);
                reject(error);
              });
            
            return this;
          }
        };
        
        // Start initialization process
        window.HURAII.init();
        
      } catch (error) {
        console.error('Failed to initialize HURAII:', error);
        reject(error);
      }
    });
  }
  
  /**
   * Set up UI elements and event handlers
   */
  function setupUI() {
    // Add event listeners for demo buttons
    $('#generate-artwork-btn').click(startGenerationProcess);
    $('#cancel-generation-btn').click(cancelGeneration);
    $('#save-artwork-btn').click(saveArtwork);
    $('#batch-learning-btn').click(sendBatchLearningData);
    
    // Set up HURAII event listeners
    const HURAII = window.HURAII;
    
    // Generation progress updates
    HURAII.on('generation_progress', data => {
      updateProgressBar(data.progress);
      if (data.previewImage) {
        updatePreviewImage(data.previewImage);
      }
      showStatus(`Generation progress: ${data.progress}% - ${data.message || 'Processing...'}`);
      
      // Log source of progress update (WebSocket vs Polling)
      console.log(`Progress update from ${data.source || 'polling'}:`, data);
    });
    
    // Request events
    HURAII.on('request_success', data => {
      logEvent('Request Success', `Request ${data.requestId} completed in ${data.responseTime}ms`);
    });
    
    HURAII.on('request_error', data => {
      logEvent('Request Error', `Request ${data.requestId} failed: ${data.error}`);
    });
    
    HURAII.on('batch_request_success', data => {
      logEvent('Batch Success', `Batch request ${data.requestId} processed ${data.originalRequestIds.length} items`);
    });
    
    // WebSocket events
    HURAII.on('websocket_connected', () => {
      $('#connection-status').text('Connected').removeClass('disconnected').addClass('connected');
    });
    
    HURAII.on('websocket_disconnected', () => {
      $('#connection-status').text('Disconnected').removeClass('connected').addClass('disconnected');
    });
    
    // Cache events
    HURAII.on('cache_hit', data => {
      logEvent('Cache Hit', `Key: ${data.key}, Type: ${data.type || 'unknown'}`);
    });
    
    // Performance metrics
    HURAII.on('performance_metrics_updated', data => {
      // Update metrics display
      updatePerformanceDisplay(data.metrics);
    });
    
    // Activity tracking
    HURAII.on('activity_tracked', data => {
      // Log activities for debugging
      console.log('Activity tracked:', data);
      
      // Process for learning if available
      if (HURAII.components.learning) {
        HURAII.components.learning.processActivity(data);
      }
    });
  }
  
  /**
   * Start the artwork generation process
   */
  function startGenerationProcess() {
    const HURAII = window.HURAII;
    const api = HURAII.components.api;
    
    if (!api) {
      showError('API component not initialized');
      return;
    }
    
    // Determine if we're using text or image prompt
    const isImageMode = $('#image-upload-content').hasClass('active');
    
    // Check if seed image is uploaded in image mode
    if (isImageMode && !HURAII.state.seedImageData) {
      showError('Please upload a seed image first');
      return;
    }
    
    // Get common generation parameters from UI
    const style = $('#style-select').val() || 'realistic';
    const format = $('#format-select').val() || '2d';
    
    // Show status based on mode
    if (isImageMode) {
      const imagePrompt = $('#image-prompt').val() || 'Enhance colors, add dramatic lighting';
      showStatus(`Starting image-based generation with prompt: "${imagePrompt}"`);
      
      // Track interaction for learning
      trackInteraction('generate_from_image', { 
        prompt: imagePrompt, 
        style, 
        format, 
        strength: parseInt($('#image-strength').val()) / 100 || 0.75
      });
    } else {
      const textPrompt = $('#prompt-input').val() || 'Colorful landscape with mountains at sunset';
      showStatus(`Starting text-based generation with prompt: "${textPrompt}"`);
      
      // Track interaction for learning
      trackInteraction('generate_from_text', { 
        prompt: textPrompt, 
        style, 
        format 
      });
    }
    
    // First, get available models for the selected format
    api.getModelsForFormat(format)
      .then(models => {
        // Select appropriate model based on style
        const model = selectModelForStyle(models, style);
        
        // Base parameters
        const baseParams = {
          model: model,
          format: format,
          style: style,
          width: parseInt($('#width-input').val()) || 1024,
          height: parseInt($('#height-input').val()) || 1024,
          steps: parseInt($('#steps-input').val()) || 30,
          seed: Math.floor(Math.random() * 1000000),
          request_id: HURAII.components.api._generateRequestId()
        };
        
        // Add mode-specific parameters
        let params;
        if (isImageMode) {
          params = {
            ...baseParams,
            init_image: HURAII.state.seedImageData,
            prompt: $('#image-prompt').val() || 'Enhance colors, add dramatic lighting',
            strength: parseInt($('#image-strength').val()) / 100 || 0.75
          };
        } else {
          params = {
            ...baseParams,
            prompt: $('#prompt-input').val() || 'Colorful landscape with mountains at sunset',
            negative_prompt: $('#negative-prompt').val() || 'blurry, distorted, low quality'
          };
        }
        
        // Show generation parameters
        $('#generation-params').html(
          Object.entries(params)
            .filter(([key]) => key !== 'init_image') // Skip image data for display
            .map(([key, value]) => 
              `<div><strong>${key}:</strong> ${value}</div>`
            ).join('')
        );
        
        // Start generation
        return api.generateArtwork(params);
      })
      .then(result => {
        // Generation completed
        showSuccess('Generation completed successfully!');
        
        // Store result
        HURAII.state.generation.currentResult = result;
        
        // Display result
        displayGenerationResult(result);
        
        // If we're in image mode, also update panel view
        if (isImageMode) {
          $('#panel2-image').attr('src', result.image_url);
        }
        
        // Enable save button
        $('#save-artwork-btn').prop('disabled', false);
        
        // Track successful generation
        trackInteraction('generation_complete', { 
          success: true,
          mode: isImageMode ? 'image_based' : 'text_based'
        });
      })
      .catch(error => {
        showError('Generation failed: ' + error.message);
        console.error('Generation error:', error);
        
        // Track failed generation
        trackInteraction('generation_error', { 
          error: error.message,
          mode: isImageMode ? 'image_based' : 'text_based'
        });
      });
  }
  
  /**
   * Cancel the current generation process
   */
  function cancelGeneration() {
    const api = window.HURAII.components.api;
    
    if (!api) {
      showError('API component not initialized');
      return;
    }
    
    if (!window.HURAII.state.isGenerating) {
      showStatus('No active generation to cancel');
      return;
    }
    
    showStatus('Cancelling generation...');
    
    api.cancelGeneration()
      .then(() => {
        showStatus('Generation cancelled successfully');
        
        // Track cancellation
        trackInteraction('generation_cancelled', {
          stage: $('#progress-bar').attr('aria-valuenow') || 0
        });
      })
      .catch(error => {
        showError('Failed to cancel generation: ' + error.message);
      });
  }
  
  /**
   * Save the generated artwork
   */
  function saveArtwork() {
    const api = window.HURAII.components.api;
    const HURAII = window.HURAII;
    
    // Check if we're saving a single image or multiple variations
    const isMultiPanel = $('#multi-panel-toggle').is(':checked');
    const currentResult = isMultiPanel ? 
      (HURAII.state.multiVariationResults.length > 0 ? HURAII.state.multiVariationResults[0] : null) : 
      HURAII.state.generation.currentResult;
    
    if (!api) {
      showError('API component not initialized');
      return;
    }
    
    if (!currentResult) {
      showError('No artwork to save');
      return;
    }
    
    showStatus('Saving artwork...');
    
    // Get metadata from UI
    const metadata = {
      title: $('#artwork-title').val() || 'Untitled Artwork',
      description: $('#artwork-description').val() || '',
      tags: ($('#artwork-tags').val() || '').split(',').map(tag => tag.trim()),
      public: $('#artwork-public').is(':checked'),
      is_variation_set: isMultiPanel,
      variation_count: isMultiPanel ? HURAII.state.multiVariationResults.length : 0
    };
    
    api.saveArtwork(currentResult, metadata)
      .then(result => {
        showSuccess('Artwork saved successfully!');
        
        // Show permalink
        if (result.permalink) {
          $('#artwork-permalink').html(`<a href="${result.permalink}" target="_blank">View Saved Artwork</a>`);
        }
        
        // Track save
        trackInteraction('artwork_saved', {
          artworkId: result.id,
          title: metadata.title,
          is_variation_set: isMultiPanel
        });
        
        // Add to recent images
        if (HURAII.state.generation.recentImages.length >= 5) {
          HURAII.state.generation.recentImages.pop();
        }
        HURAII.state.generation.recentImages.unshift({
          id: result.id,
          thumbnail: result.thumbnail,
          title: metadata.title
        });
        
        // Update recent images display
        updateRecentImages();
        
        // If we have multiple variations, save those too
        if (isMultiPanel && HURAII.state.multiVariationResults.length > 1) {
          saveVariations(metadata, result.id);
        }
      })
      .catch(error => {
        showError('Failed to save artwork: ' + error.message);
      });
  }
  
  /**
   * Save variation artworks
   * @param {Object} metadata Base metadata
   * @param {string|number} parentId Parent artwork ID
   */
  function saveVariations(metadata, parentId) {
    const api = window.HURAII.components.api;
    const variations = window.HURAII.state.multiVariationResults.slice(1); // Skip the first one (already saved)
    
    if (variations.length === 0) return;
    
    showStatus(`Saving ${variations.length} additional variations...`);
    
    const savePromises = variations.map((variation, index) => {
      // Create variation-specific metadata
      const variationMetadata = {
        ...metadata,
        title: `${metadata.title} - Variation ${index + 1}`,
        parent_id: parentId,
        is_variation: true,
        variation_index: index + 1
      };
      
      // Save the variation
      return api.saveArtwork(variation, variationMetadata);
    });
    
    Promise.all(savePromises)
      .then(results => {
        showSuccess(`Successfully saved ${results.length} variations!`);
        
        // Track variation saves
        trackInteraction('variations_saved', {
          count: results.length,
          parent_id: parentId
        });
      })
      .catch(error => {
        showError(`Failed to save some variations: ${error.message}`);
      });
  }
  
  /**
   * Send batch learning data
   */
  function sendBatchLearningData() {
    const api = window.HURAII.components.api;
    
    if (!api) {
      showError('API component not initialized');
      return;
    }
    
    showStatus('Preparing learning data batch...');
    
    // Create multiple learning requests to demonstrate batching
    const batchCount = parseInt($('#batch-count').val()) || 10;
    const promises = [];
    
    for (let i = 0; i < batchCount; i++) {
      // Create synthetic interactions for demo
      const interactions = [
        {
          type: 'view',
          item_id: `demo_artwork_${i}`,
          duration: Math.floor(Math.random() * 60) + 10,
          timestamp: Date.now() - (i * 1000)
        },
        {
          type: 'rate',
          item_id: `demo_artwork_${i}`,
          rating: Math.floor(Math.random() * 5) + 1,
          timestamp: Date.now() - (i * 500)
        }
      ];
      
      // Send learning data (these will be batched by the API)
      promises.push(api.sendLearningData(interactions));
    }
    
    // Wait for all requests to complete
    Promise.all(promises)
      .then(() => {
        showSuccess(`Successfully sent ${batchCount} learning requests (batched)`);
      })
      .catch(error => {
        showError('Failed to send learning data: ' + error.message);
      });
  }
  
  /**
   * Track user interaction for learning
   * @param {string} type Interaction type
   * @param {Object} data Interaction data
   */
  function trackInteraction(type, data) {
    const interaction = {
      type: type,
      data: data,
      timestamp: Date.now()
    };
    
    // Add to interaction history
    window.HURAII.state.interactionHistory.push(interaction);
    
    // Keep history at reasonable size
    if (window.HURAII.state.interactionHistory.length > 100) {
      window.HURAII.state.interactionHistory.shift();
    }
    
    // Emit tracking event for learning components
    window.HURAII.emit('activity_tracked', interaction);
  }
  
  /**
   * Select the best model for a given style
   * @param {Array} models Available models
   * @param {string} style Desired style
   * @returns {string} Selected model ID
   */
  function selectModelForStyle(models, style) {
    // For demo purposes, just return the first model or a default
    if (models && models.length > 0) {
      // Try to find a model matching the style
      const matchingModel = models.find(model => 
        model.name.toLowerCase().includes(style.toLowerCase()) || 
        (model.styles && model.styles.includes(style))
      );
      
      return matchingModel ? matchingModel.id : models[0].id;
    }
    
    return 'default_model';
  }
  
  /**
   * Display the generation result
   * @param {Object} result Generation result
   */
  function displayGenerationResult(result) {
    // Update result image
    if (result.image_url) {
      $('#result-image').attr('src', result.image_url).show();
    }
    
    // Update result details
    $('#result-details').html(`
      <div class="result-info">
        <div><strong>Model:</strong> ${result.model || 'Unknown'}</div>
        <div><strong>Seed:</strong> ${result.seed || 'Random'}</div>
        <div><strong>Resolution:</strong> ${result.width || 0}x${result.height || 0}</div>
        <div><strong>Generation Time:</strong> ${result.generation_time || 0}s</div>
      </div>
    `);
    
    // Show result section
    $('#result-section').show();
  }
  
  /**
   * Update the progress bar
   * @param {number} progress Progress percentage
   */
  function updateProgressBar(progress) {
    $('#progress-bar')
      .width(`${progress}%`)
      .attr('aria-valuenow', progress)
      .text(`${progress}%`);
    
    $('#progress-container').show();
  }
  
  /**
   * Update the preview image during generation
   * @param {string} imageUrl Preview image URL
   */
  function updatePreviewImage(imageUrl) {
    $('#preview-image').attr('src', imageUrl).show();
  }
  
  /**
   * Update the recent images display
   */
  function updateRecentImages() {
    const recentImages = window.HURAII.state.generation.recentImages;
    const $container = $('#recent-images');
    
    // Clear container
    $container.empty();
    
    // Add recent images
    recentImages.forEach(image => {
      $container.append(`
        <div class="recent-image">
          <img src="${image.thumbnail}" alt="${image.title}" />
          <div class="recent-image-title">${image.title}</div>
        </div>
      `);
    });
  }
  
  /**
   * Update performance metrics display
   * @param {Object} metrics Performance metrics
   */
  function updatePerformanceDisplay(metrics) {
    if (!metrics) return;
    
    $('#metrics-request-count').text(metrics.requestCounts.total || 0);
    $('#metrics-success-rate').text(
      metrics.requestCounts.total ? 
      `${Math.round((metrics.requestCounts.success / metrics.requestCounts.total) * 100)}%` : 
      'N/A'
    );
    $('#metrics-avg-response').text(
      metrics.responseTimes.average ? 
      `${Math.round(metrics.responseTimes.average)}ms` : 
      'N/A'
    );
    $('#metrics-cache-hit-ratio').text(
      `${Math.round(metrics.cacheStats.ratio * 100)}%`
    );
    $('#metrics-batch-count').text(
      metrics.batchStats.total || 0
    );
  }
  
  /**
   * Log performance metrics
   */
  function logPerformanceMetrics() {
    const api = window.HURAII.components.api;
    
    if (!api || !api.performanceMetrics) {
      return;
    }
    
    console.log('Current Performance Metrics:', api.performanceMetrics);
  }
  
  /**
   * Show status message
   * @param {string} message Status message
   */
  function showStatus(message) {
    $('#status-message').text(message).removeClass('error success').addClass('info');
  }
  
  /**
   * Show error message
   * @param {string} message Error message
   */
  function showError(message) {
    $('#status-message').text(message).removeClass('info success').addClass('error');
  }
  
  /**
   * Show success message
   * @param {string} message Success message
   */
  function showSuccess(message) {
    $('#status-message').text(message).removeClass('error info').addClass('success');
  }
  
  /**
   * Log event to the event log
   * @param {string} type Event type
   * @param {string} message Event message
   */
  function logEvent(type, message) {
    const $log = $('#event-log');
    const timestamp = new Date().toLocaleTimeString();
    
    $log.prepend(`<div class="event-item ${type.toLowerCase().replace(/\s+/g, '-')}">
      <span class="event-time">${timestamp}</span>
      <span class="event-type">${type}</span>
      <span class="event-message">${message}</span>
    </div>`);
    
    // Limit log entries
    if ($log.children().length > 50) {
      $log.children().last().remove();
    }
  }
  
  // Make key functions available globally for other modules
  window.HURAII_demo = {
    showStatus,
    showError,
    showSuccess,
    logEvent,
    trackInteraction
  };
  
})(window, jQuery); 