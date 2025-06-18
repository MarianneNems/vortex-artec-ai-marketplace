/**
 * HURAII Image Processor Module
 * Handles server-side image processing, including uploaded seed images and variation generation
 */

(function() {
  'use strict';
  
  // Image Processor Module
  const ImageProcessor = {
    /**
     * Module name
     */
    name: 'imageProcessor',
    
    /**
     * Module configuration
     */
    config: {
      maxImageSize: 10 * 1024 * 1024, // 10MB
      supportedFormats: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
      tempDir: '/tmp/huraii_uploads',
      cacheDir: '/cache/huraii_images',
      maxCacheAge: 7 * 24 * 60 * 60 * 1000, // 7 days
      variationStrengths: [0.25, 0.5, 0.75], // Default strengths for variations
      optimizationEnabled: true,
      watermarkEnabled: false,
      watermarkPath: '/assets/images/huraii_watermark.png'
    },
    
    /**
     * Image processing queue
     */
    processingQueue: [],
    
    /**
     * Active processing tasks
     */
    activeTasks: {},
    
    /**
     * Initialize the Image Processor module
     * @param {Object} server Server instance
     * @param {Object} config Configuration options
     */
    init: function(server, config = {}) {
      console.log('Initializing HURAII Image Processor');
      
      // Store server reference
      this.server = server;
      
      // Merge configuration
      this.config = {
        ...this.config,
        ...config
      };
      
      // Initialize the processing queue
      this._startProcessingQueue();
      
      // Initialize cache cleanup
      this._initializeCacheCleanup();
      
      // Register API endpoints
      this._registerEndpoints();
      
      return this;
    },
    
    /**
     * Process an uploaded image
     * @param {Object} imageData Image data (buffer or path)
     * @param {Object} options Processing options
     * @returns {Promise<Object>} Promise resolving to processed image data
     */
    processImage: function(imageData, options = {}) {
      return new Promise((resolve, reject) => {
        // Generate task ID
        const taskId = this._generateTaskId();
        
        // Default options
        const defaultOptions = {
          resize: true,
          optimize: this.config.optimizationEnabled,
          format: 'png',
          watermark: this.config.watermarkEnabled,
          metadata: {}
        };
        
        // Merge options
        const processingOptions = {
          ...defaultOptions,
          ...options,
          taskId: taskId
        };
        
        // Add to processing queue
        this.processingQueue.push({
          taskId: taskId,
          imageData: imageData,
          options: processingOptions,
          resolve: resolve,
          reject: reject,
          timestamp: Date.now()
        });
        
        // Update active tasks
        this.activeTasks[taskId] = {
          status: 'queued',
          progress: 0,
          options: processingOptions,
          startTime: Date.now()
        };
        
        console.log(`Image processing task queued: ${taskId}`);
      });
    },
    
    /**
     * Generate image variations
     * @param {Object} sourceImage Source image data
     * @param {Object} params Generation parameters
     * @param {number} count Number of variations to generate
     * @returns {Promise<Array>} Promise resolving to array of variation results
     */
    generateVariations: function(sourceImage, params, count = 3) {
      return new Promise((resolve, reject) => {
        // Validate inputs
        if (!sourceImage) {
          return reject(new Error('Source image is required'));
        }
        
        if (count < 1 || count > 10) {
          return reject(new Error('Variation count must be between 1 and 10'));
        }
        
        // Array to hold variation promises
        const variationPromises = [];
        
        // Generate unique seeds for variations
        const baseSeed = params.seed || Math.floor(Math.random() * 1000000);
        
        // Create a different strength for each variation
        // We'll distribute strengths evenly unless specific ones are specified
        const strengths = params.variationStrengths || this.config.variationStrengths;
        
        // Generate each variation
        for (let i = 0; i < count; i++) {
          // Calculate a new seed based on the original
          const variationSeed = (baseSeed + (i * 17713)) % 999999; // Prime number offset
          
          // Strength index (wrapped if needed)
          const strengthIndex = i % strengths.length;
          
          // Clone parameters and modify for this variation
          const variationParams = {
            ...params,
            seed: variationSeed,
            strength: strengths[strengthIndex],
            variation_id: `var_${i + 1}_${variationSeed}`,
            is_variation: true,
            parent_id: params.request_id || params.taskId || baseSeed.toString()
          };
          
          // Create a variation task
          const variationPromise = this.processImage(sourceImage, {
            ...variationParams,
            metadata: {
              variation_number: i + 1,
              variation_of: params.request_id,
              strength: strengths[strengthIndex]
            }
          });
          
          variationPromises.push(variationPromise);
        }
        
        // Wait for all variations to complete
        Promise.all(variationPromises)
          .then(results => {
            console.log(`Generated ${results.length} variations successfully`);
            resolve(results);
          })
          .catch(error => {
            console.error('Error generating variations:', error);
            reject(error);
          });
      });
    },
    
    /**
     * Get processing task status
     * @param {string} taskId Task ID
     * @returns {Object|null} Task status or null if not found
     */
    getTaskStatus: function(taskId) {
      return this.activeTasks[taskId] || null;
    },
    
    /**
     * Start the processing queue
     * @private
     */
    _startProcessingQueue: function() {
      // Process queue every 100ms
      setInterval(() => {
        // Skip if queue is empty
        if (this.processingQueue.length === 0) {
          return;
        }
        
        // Get next task
        const task = this.processingQueue.shift();
        
        // Update task status
        this.activeTasks[task.taskId].status = 'processing';
        this.activeTasks[task.taskId].progress = 10;
        
        // Process the image (simulated for this demo)
        this._processImageTask(task)
          .then(result => {
            // Update task status
            this.activeTasks[task.taskId].status = 'completed';
            this.activeTasks[task.taskId].progress = 100;
            this.activeTasks[task.taskId].completionTime = Date.now();
            this.activeTasks[task.taskId].processingTime = 
              Date.now() - this.activeTasks[task.taskId].startTime;
            
            // Resolve the task promise
            task.resolve(result);
            
            // Clean up after a delay
            setTimeout(() => {
              delete this.activeTasks[task.taskId];
            }, 1800000); // Keep for 30 minutes for debugging
            
            console.log(`Task ${task.taskId} completed in ${this.activeTasks[task.taskId].processingTime}ms`);
          })
          .catch(error => {
            // Update task status
            this.activeTasks[task.taskId].status = 'failed';
            this.activeTasks[task.taskId].error = error.message;
            
            // Reject the task promise
            task.reject(error);
            
            console.error(`Task ${task.taskId} failed:`, error);
          });
      }, 100);
    },
    
    /**
     * Process an image task
     * @param {Object} task Processing task
     * @returns {Promise<Object>} Promise resolving to processed image data
     * @private
     */
    _processImageTask: function(task) {
      return new Promise((resolve, reject) => {
        // Simulate image processing steps with progressive updates
        setTimeout(() => {
          // Update progress to 30%
          this.activeTasks[task.taskId].progress = 30;
          
          // Validate image format (would be implemented in a real system)
          if (!this._validateImage(task.imageData)) {
            return reject(new Error('Invalid image format'));
          }
          
          // Next step after a delay
          setTimeout(() => {
            // Update progress to 60%
            this.activeTasks[task.taskId].progress = 60;
            
            // Process the image based on options
            // In a real implementation, this would use image processing libraries
            // For this demo, we'll simulate processing
            
            // Add watermark if enabled
            if (task.options.watermark) {
              // Simulated watermark application
              console.log(`Applying watermark to task ${task.taskId}`);
            }
            
            // Optimize if enabled
            if (task.options.optimize) {
              // Simulated image optimization
              console.log(`Optimizing image for task ${task.taskId}`);
            }
            
            // Final step
            setTimeout(() => {
              // Update progress to 90%
              this.activeTasks[task.taskId].progress = 90;
              
              // Generate the final result
              const result = this._generateResult(task);
              
              // Resolve with the result
              resolve(result);
            }, 200);
          }, 300);
        }, 200);
      });
    },
    
    /**
     * Validate an image
     * @param {Object} imageData Image data
     * @returns {boolean} True if valid, false otherwise
     * @private
     */
    _validateImage: function(imageData) {
      // In a real implementation, this would validate the image format, size, etc.
      // For this demo, we'll assume all images are valid
      return true;
    },
    
    /**
     * Generate a result object for a processing task
     * @param {Object} task Processing task
     * @returns {Object} Result object
     * @private
     */
    _generateResult: function(task) {
      // Generate paths (in a real system, these would be actual file paths)
      const timestamp = Date.now();
      const randomPart = Math.floor(Math.random() * 10000);
      const basePath = `/uploads/vortex-huraii/${timestamp}_${randomPart}`;
      
      // Create result object
      return {
        success: true,
        taskId: task.taskId,
        image_url: `${basePath}.${task.options.format || 'png'}`,
        thumbnail_url: `${basePath}_thumb.${task.options.format || 'png'}`,
        width: task.options.width || 1024,
        height: task.options.height || 1024,
        format: task.options.format || 'png',
        processing_time: Date.now() - this.activeTasks[task.taskId].startTime,
        seed: task.options.seed || Math.floor(Math.random() * 1000000),
        metadata: task.options.metadata || {},
        created_at: new Date().toISOString(),
        model: task.options.model || 'default_model',
        is_variation: !!task.options.is_variation,
        variation_id: task.options.variation_id || null,
        strength: task.options.strength || 1.0
      };
    },
    
    /**
     * Initialize cache cleanup
     * @private
     */
    _initializeCacheCleanup: function() {
      // Clean up cache once a day
      setInterval(() => {
        console.log('Cleaning up image cache');
        
        // In a real implementation, this would delete old cached files
        // For this demo, we'll just log the action
      }, 24 * 60 * 60 * 1000); // Once per day
    },
    
    /**
     * Register API endpoints
     * @private
     */
    _registerEndpoints: function() {
      // In a real implementation, this would register API endpoints
      // For this demo, we'll assume the endpoints are registered elsewhere
      console.log('Image processor endpoints registered');
    },
    
    /**
     * Generate a unique task ID
     * @returns {string} Unique task ID
     * @private
     */
    _generateTaskId: function() {
      return `img_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }
  };
  
  // Export the module
  if (typeof module !== 'undefined' && module.exports) {
    module.exports = ImageProcessor;
  } else if (typeof window !== 'undefined') {
    window.HURAII_ImageProcessor = ImageProcessor;
  }
})(); 