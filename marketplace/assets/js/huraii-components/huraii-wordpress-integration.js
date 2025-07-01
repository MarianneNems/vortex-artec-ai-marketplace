/**
 * HURAII WordPress Integration
 * Handles integration with WordPress admin-ajax.php system for server-side image processing
 */

// Check if this is server-side (Node.js) environment
if (typeof window === 'undefined') {
  
  // Import required Node.js modules
  const fs = require('fs');
  const path = require('path');
  const multer = require('multer'); // For handling multipart/form-data uploads
  
  // Import our modules
  const ImageProcessor = require('./huraii-image-processor');
  const ImageApiHandler = require('./huraii-image-api-handler');
  
  /**
   * HURAII WordPress Integration Module
   */
  const WordPressIntegration = {
    /**
     * Module name
     */
    name: 'wordpressIntegration',
    
    /**
     * Module configuration
     */
    config: {
      uploadDir: 'uploads/vortex-huraii',
      tempDir: 'tmp/huraii_uploads',
      nonceVerification: true
    },
    
    /**
     * Initialize the WordPress integration
     * @param {Object} wp WordPress API object or equivalent
     * @param {Object} config Configuration options
     */
    init: function(wp, config = {}) {
      console.log('Initializing HURAII WordPress Integration');
      
      // Store WordPress API reference
      this.wp = wp;
      
      // Merge configuration
      this.config = {
        ...this.config,
        ...config
      };
      
      // Initialize upload directory
      this._initializeUploadDirectory();
      
      // Initialize the image processor
      ImageProcessor.init(null, this.config.imageProcessor || {});
      
      // Initialize the API handler (without Express)
      ImageApiHandler.init(null, this.config.apiHandler || {});
      
      // Register WordPress actions (AJAX handlers)
      this._registerWordPressActions();
      
      // Set initialized flag
      this.initialized = true;
      
      return this;
    },
    
    /**
     * Handle the vortex_huraii_upload_image action
     * @param {Object} request WordPress request object
     * @returns {Object} Response data
     */
    handleUploadImage: function(request) {
      // Verify nonce if enabled
      if (this.config.nonceVerification && !this._verifyNonce(request)) {
        return {
          success: false,
          message: 'Security verification failed',
          error_code: 'INVALID_NONCE'
        };
      }
      
      // Get the file from request
      const file = request.files && request.files.image ? request.files.image : null;
      
      // Get options from request
      const options = request.body && request.body.options ? 
        JSON.parse(request.body.options) : {};
      
      // Check if file exists
      if (!file) {
        return {
          success: false,
          message: 'No image file provided',
          error_code: 'MISSING_IMAGE'
        };
      }
      
      // Check file type
      const allowedTypes = ImageApiHandler.config.allowedMimeTypes;
      if (!allowedTypes.includes(file.mimetype)) {
        return {
          success: false,
          message: `Invalid file type. Allowed types: ${allowedTypes.join(', ')}`,
          error_code: 'INVALID_FILE_TYPE'
        };
      }
      
      // Check file size
      if (file.size > ImageApiHandler.config.maxUploadSize) {
        return {
          success: false,
          message: `File too large. Maximum size: ${Math.round(ImageApiHandler.config.maxUploadSize / (1024 * 1024))}MB`,
          error_code: 'FILE_TOO_LARGE'
        };
      }
      
      // Process the image
      return new Promise((resolve, reject) => {
        ImageProcessor.processImage(file.path, options)
          .then(result => {
            // Successful response
            resolve({
              success: true,
              data: result
            });
          })
          .catch(error => {
            // Error response
            reject({
              success: false,
              message: error.message,
              error_code: 'IMAGE_PROCESSING_ERROR'
            });
          });
      });
    },
    
    /**
     * Handle the vortex_huraii_generate_variations action
     * @param {Object} request WordPress request object
     * @returns {Object} Response data
     */
    handleGenerateVariations: function(request) {
      // Verify nonce if enabled
      if (this.config.nonceVerification && !this._verifyNonce(request)) {
        return {
          success: false,
          message: 'Security verification failed',
          error_code: 'INVALID_NONCE'
        };
      }
      
      // Get request data
      const requestData = request.body || {};
      const sourceImageId = requestData.source_image_id;
      const params = requestData.params || {};
      const variationCount = requestData.variation_count || 3;
      
      // Get source image file if ID is provided
      let sourceImage = null;
      
      if (sourceImageId) {
        // In a real implementation, this would fetch the image from WordPress media library
        // For this demo, we'll simulate success for valid-looking IDs
        if (sourceImageId.indexOf('img_') !== 0 && 
            !(/^\d+$/.test(sourceImageId))) { // WordPress media IDs are numeric
          return {
            success: false,
            message: 'Source image not found',
            error_code: 'SOURCE_IMAGE_NOT_FOUND'
          };
        }
        
        // Simulate fetched image path
        sourceImage = {
          id: sourceImageId,
          path: `/path/to/wordpress/uploads/${sourceImageId}.jpg`
        };
      } else if (request.files && request.files.source_image) {
        // Use uploaded file
        sourceImage = request.files.source_image.path;
      } else {
        return {
          success: false,
          message: 'Source image ID or file is required',
          error_code: 'MISSING_SOURCE_IMAGE'
        };
      }
      
      // Generate variations
      return new Promise((resolve, reject) => {
        ImageProcessor.generateVariations(sourceImage, params, variationCount)
          .then(results => {
            // Successful response
            resolve({
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
            // Error response
            reject({
              success: false,
              message: error.message,
              error_code: 'VARIATION_GENERATION_ERROR'
            });
          });
      });
    },
    
    /**
     * Handle the vortex_huraii_task_status action
     * @param {Object} request WordPress request object
     * @returns {Object} Response data
     */
    handleTaskStatus: function(request) {
      // Verify nonce if enabled
      if (this.config.nonceVerification && !this._verifyNonce(request)) {
        return {
          success: false,
          message: 'Security verification failed',
          error_code: 'INVALID_NONCE'
        };
      }
      
      // Get task ID from request
      const taskId = request.body.task_id || '';
      
      // Validate task ID
      if (!taskId) {
        return {
          success: false,
          message: 'Task ID is required',
          error_code: 'MISSING_TASK_ID'
        };
      }
      
      // Get task status from image processor
      const taskStatus = ImageProcessor.getTaskStatus(taskId);
      
      // Check if task exists
      if (!taskStatus) {
        return {
          success: false,
          message: 'Task not found',
          error_code: 'TASK_NOT_FOUND'
        };
      }
      
      // Return task status
      return {
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
      };
    },
    
    /**
     * Initialize upload directory
     * @private
     */
    _initializeUploadDirectory: function() {
      // Create upload directory if it doesn't exist
      const uploadDir = path.resolve(this.config.uploadDir);
      const tempDir = path.resolve(this.config.tempDir);
      
      // Ensure upload directory exists
      if (!fs.existsSync(uploadDir)) {
        fs.mkdirSync(uploadDir, { recursive: true });
        console.log(`Created upload directory: ${uploadDir}`);
      }
      
      // Ensure temp directory exists
      if (!fs.existsSync(tempDir)) {
        fs.mkdirSync(tempDir, { recursive: true });
        console.log(`Created temp directory: ${tempDir}`);
      }
    },
    
    /**
     * Register WordPress AJAX actions
     * @private
     */
    _registerWordPressActions: function() {
      if (!this.wp || !this.wp.ajax || typeof this.wp.ajax.register !== 'function') {
        console.error('WordPress API not available for action registration');
        return;
      }
      
      // Register AJAX actions
      this.wp.ajax.register('vortex_huraii_upload_image', this.handleUploadImage.bind(this));
      this.wp.ajax.register('vortex_huraii_generate_variations', this.handleGenerateVariations.bind(this));
      this.wp.ajax.register('vortex_huraii_task_status', this.handleTaskStatus.bind(this));
      
      console.log('Registered WordPress AJAX actions for HURAII image processing');
    },
    
    /**
     * Verify nonce from request
     * @param {Object} request WordPress request object
     * @returns {boolean} True if valid, false otherwise
     * @private
     */
    _verifyNonce: function(request) {
      // Get nonce from request
      const nonce = request.body.nonce || request.query.nonce || '';
      
      // In a real implementation, this would use wp_verify_nonce()
      // For this demo, we'll accept any non-empty string
      return nonce !== '';
    }
  };
  
  // Export the module for Node.js
  module.exports = WordPressIntegration;
} else {
  // Client-side stub (not needed for backend)
  console.log('HURAII WordPress Integration module is for server-side use only');
} 