/**
 * HURAII Image API Handler
 * Handles server-side API requests for image uploads and variations
 */

(function() {
  'use strict';
  
  // Dependencies
  const ImageProcessor = 
    typeof require !== 'undefined' ? require('./huraii-image-processor') : window.HURAII_ImageProcessor;
  
  // Image API Handler Module
  const ImageApiHandler = {
    /**
     * Module name
     */
    name: 'imageApiHandler',
    
    /**
     * Module configuration
     */
    config: {
      uploadPath: '/uploads/vortex-huraii',
      maxUploadSize: 15 * 1024 * 1024, // 15MB
      allowedMimeTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
      defaultFormat: 'png',
      defaultWidth: 1024,
      defaultHeight: 1024,
      securityEnabled: true,
      rateLimit: {
        enabled: true,
        maxRequests: 20,
        timeWindow: 60 * 60 * 1000, // 1 hour
        perIp: true
      }
    },
    
    /**
     * Current rate limit counters
     */
    rateLimitCounters: {},
    
    /**
     * Initialize the API Handler
     * @param {Object} app Express app or server instance
     * @param {Object} config Configuration options
     */
    init: function(app, config = {}) {
      console.log('Initializing HURAII Image API Handler');
      
      // Store app reference
      this.app = app;
      
      // Merge configuration
      this.config = {
        ...this.config,
        ...config
      };
      
      // Initialize the image processor if not already done
      if (!ImageProcessor.initialized) {
        ImageProcessor.init(app, this.config.imageProcessor || {});
      }
      
      // Register API endpoints
      this._registerEndpoints();
      
      // Start rate limit cleaner
      this._startRateLimitCleaner();
      
      // Set module as initialized
      this.initialized = true;
      
      return this;
    },
    
    /**
     * Handle image upload request
     * @param {Object} req Request object
     * @param {Object} res Response object
     */
    handleImageUpload: function(req, res) {
      // In a real implementation, this would handle file uploads from multipart/form-data
      // For this demo, we'll simulate the process with a JSON API
      
      // Get request data
      const requestData = req.body || {};
      const imageData = requestData.image_data;
      const options = requestData.options || {};
      
      // Check rate limit
      if (!this._checkRateLimit(req)) {
        return res.status(429).json({
          success: false,
          message: 'Rate limit exceeded. Please try again later.',
          error_code: 'RATE_LIMIT_EXCEEDED'
        });
      }
      
      // Validate image data
      if (!imageData) {
        return res.status(400).json({
          success: false,
          message: 'Image data is required',
          error_code: 'MISSING_IMAGE_DATA'
        });
      }
      
      // Process the image
      ImageProcessor.processImage(imageData, options)
        .then(result => {
          // Send successful response
          res.json({
            success: true,
            data: result
          });
        })
        .catch(error => {
          // Send error response
          res.status(500).json({
            success: false,
            message: error.message,
            error_code: 'IMAGE_PROCESSING_ERROR'
          });
        });
    },
    
    /**
     * Handle variation generation request
     * @param {Object} req Request object
     * @param {Object} res Response object
     */
    handleVariationGeneration: function(req, res) {
      // Get request data
      const requestData = req.body || {};
      const sourceImageId = requestData.source_image_id;
      const sourceImageData = requestData.source_image_data;
      const params = requestData.params || {};
      const variationCount = requestData.variation_count || 3;
      
      // Check rate limit
      if (!this._checkRateLimit(req)) {
        return res.status(429).json({
          success: false,
          message: 'Rate limit exceeded. Please try again later.',
          error_code: 'RATE_LIMIT_EXCEEDED'
        });
      }
      
      // Validate source image
      if (!sourceImageId && !sourceImageData) {
        return res.status(400).json({
          success: false,
          message: 'Source image ID or data is required',
          error_code: 'MISSING_SOURCE_IMAGE'
        });
      }
      
      // Get source image data if ID is provided
      let sourceImage = sourceImageData;
      
      if (sourceImageId && !sourceImageData) {
        // In a real implementation, this would fetch the image from storage
        // For this demo, we'll simulate an error for invalid IDs
        if (sourceImageId.indexOf('img_') !== 0) {
          return res.status(404).json({
            success: false,
            message: 'Source image not found',
            error_code: 'SOURCE_IMAGE_NOT_FOUND'
          });
        }
        
        // Simulate fetched image data
        sourceImage = {
          id: sourceImageId,
          // Mock image data would be here
          path: `/path/to/images/${sourceImageId}.png`
        };
      }
      
      // Generate variations
      ImageProcessor.generateVariations(sourceImage, params, variationCount)
        .then(results => {
          // Send successful response
          res.json({
            success: true,
            data: {
              variations: results,
              count: results.length,
              source_id: sourceImageId || 'uploaded_image',
              params: params
            }
          });
        })
        .catch(error => {
          // Send error response
          res.status(500).json({
            success: false,
            message: error.message,
            error_code: 'VARIATION_GENERATION_ERROR'
          });
        });
    },
    
    /**
     * Handle task status request
     * @param {Object} req Request object
     * @param {Object} res Response object
     */
    handleTaskStatus: function(req, res) {
      // Get task ID from request
      const taskId = req.params.taskId || req.query.task_id;
      
      // Validate task ID
      if (!taskId) {
        return res.status(400).json({
          success: false,
          message: 'Task ID is required',
          error_code: 'MISSING_TASK_ID'
        });
      }
      
      // Get task status from image processor
      const taskStatus = ImageProcessor.getTaskStatus(taskId);
      
      // Check if task exists
      if (!taskStatus) {
        return res.status(404).json({
          success: false,
          message: 'Task not found',
          error_code: 'TASK_NOT_FOUND'
        });
      }
      
      // Send response with task status
      res.json({
        success: true,
        data: {
          task_id: taskId,
          status: taskStatus.status,
          progress: taskStatus.progress,
          started_at: new Date(taskStatus.startTime).toISOString(),
          processing_time: Date.now() - taskStatus.startTime,
          // Include error if task failed
          ...(taskStatus.error && { error: taskStatus.error }),
          // Include completion time if task completed
          ...(taskStatus.completionTime && { 
            completed_at: new Date(taskStatus.completionTime).toISOString()
          })
        }
      });
    },
    
    /**
     * Check rate limit for a request
     * @param {Object} req Request object
     * @returns {boolean} True if within rate limit, false if exceeded
     * @private
     */
    _checkRateLimit: function(req) {
      // Skip if rate limiting is disabled
      if (!this.config.rateLimit.enabled) {
        return true;
      }
      
      // Get identifier based on configuration
      const identifier = this.config.rateLimit.perIp ? 
        (req.ip || req.connection.remoteAddress) : 
        (req.body.user_id || 'anonymous');
      
      // Initialize counter if not exists
      if (!this.rateLimitCounters[identifier]) {
        this.rateLimitCounters[identifier] = {
          count: 0,
          resetTime: Date.now() + this.config.rateLimit.timeWindow
        };
      }
      
      // Reset counter if time window has passed
      if (Date.now() > this.rateLimitCounters[identifier].resetTime) {
        this.rateLimitCounters[identifier] = {
          count: 0,
          resetTime: Date.now() + this.config.rateLimit.timeWindow
        };
      }
      
      // Increment counter
      this.rateLimitCounters[identifier].count++;
      
      // Check if limit exceeded
      return this.rateLimitCounters[identifier].count <= this.config.rateLimit.maxRequests;
    },
    
    /**
     * Start rate limit cleaner
     * @private
     */
    _startRateLimitCleaner: function() {
      // Clean up rate limit counters periodically
      setInterval(() => {
        const now = Date.now();
        
        // Remove expired counters
        Object.keys(this.rateLimitCounters).forEach(key => {
          if (now > this.rateLimitCounters[key].resetTime) {
            delete this.rateLimitCounters[key];
          }
        });
      }, 10 * 60 * 1000); // Every 10 minutes
    },
    
    /**
     * Register API endpoints
     * @private
     */
    _registerEndpoints: function() {
      // In a real implementation, this would register the endpoints with Express
      // For this demo, we'll just set up handlers that can be called by the server
      
      // Define the endpoints (for documentation)
      this.endpoints = {
        uploadImage: {
          path: '/api/vortex-huraii/upload-image',
          method: 'POST',
          handler: this.handleImageUpload.bind(this)
        },
        generateVariations: {
          path: '/api/vortex-huraii/generate-variations',
          method: 'POST',
          handler: this.handleVariationGeneration.bind(this)
        },
        getTaskStatus: {
          path: '/api/vortex-huraii/task-status/:taskId',
          method: 'GET',
          handler: this.handleTaskStatus.bind(this)
        }
      };
      
      // If we have an Express app, register the endpoints
      if (this.app && typeof this.app.post === 'function') {
        // Register POST endpoints
        this.app.post(this.endpoints.uploadImage.path, this.endpoints.uploadImage.handler);
        this.app.post(this.endpoints.generateVariations.path, this.endpoints.generateVariations.handler);
        
        // Register GET endpoints
        this.app.get(this.endpoints.getTaskStatus.path, this.endpoints.getTaskStatus.handler);
        
        console.log('Registered Image API endpoints with Express');
      } else {
        console.log('Express app not available, endpoints not registered automatically');
      }
    }
  };
  
  // Export the module
  if (typeof module !== 'undefined' && module.exports) {
    module.exports = ImageApiHandler;
  } else if (typeof window !== 'undefined') {
    window.HURAII_ImageApiHandler = ImageApiHandler;
  }
})(); 