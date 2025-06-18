/**
 * HURAII Server
 * Main server initialization for HURAII image processing functionality
 */

// Check if running in Node.js environment
if (typeof process !== 'undefined' && process.versions && process.versions.node) {
  'use strict';
  
  // Import Node.js modules
  const path = require('path');
  const fs = require('fs');
  
  // Import required components
  const ImageProcessor = require('./huraii-image-processor');
  const ImageApiHandler = require('./huraii-image-api-handler');
  const WordPressIntegration = require('./huraii-wordpress-integration');
  
  /**
   * HURAII Server Module
   */
  const HURAIIServer = {
    /**
     * Module name
     */
    name: 'server',
    
    /**
     * Module configuration
     */
    config: {
      // Server options (for standalone mode)
      serverPort: process.env.HURAII_SERVER_PORT || 3000,
      enableCors: true,
      trustProxy: true,
      logLevel: 'info',
      
      // Image processing options
      imageProcessor: {
        maxImageSize: 20 * 1024 * 1024, // 20MB
        optimizationEnabled: true,
        watermarkEnabled: false
      },
      
      // API handler options
      apiHandler: {
        rateLimit: {
          enabled: true,
          maxRequests: 30,
          timeWindow: 60 * 60 * 1000 // 1 hour
        }
      },
      
      // WordPress integration options
      wordpressIntegration: {
        nonceVerification: true
      },
      
      // Paths
      paths: {
        uploads: path.resolve(process.env.HURAII_UPLOAD_DIR || 'uploads/vortex-huraii'),
        temp: path.resolve(process.env.HURAII_TEMP_DIR || 'tmp/huraii_uploads'),
        logs: path.resolve(process.env.HURAII_LOG_DIR || 'logs/huraii')
      }
    },
    
    /**
     * Initialize the server
     * @param {Object} config Configuration options
     * @param {Object} wordpress WordPress API object (optional)
     * @returns {Object} Server instance
     */
    init: function(config = {}, wordpress = null) {
      console.log('Initializing HURAII Server');
      
      // Merge configuration
      this.config = {
        ...this.config,
        ...config
      };
      
      // Ensure directories exist
      this._ensureDirectories();
      
      // Set up logging
      this._initializeLogging();
      
      // Initialize modules
      this._initializeModules(wordpress);
      
      // Set initialized flag
      this.initialized = true;
      
      return this;
    },
    
    /**
     * Start the standalone server (Express)
     * @returns {Object} Express app instance
     */
    startStandaloneServer: function() {
      if (this.app) {
        console.log('Server already started');
        return this.app;
      }
      
      // Import Express
      const express = require('express');
      const cors = require('cors');
      const morgan = require('morgan');
      const bodyParser = require('body-parser');
      const multer = require('multer');
      
      // Create Express app
      const app = express();
      
      // Configure middleware
      if (this.config.enableCors) {
        app.use(cors());
      }
      
      if (this.config.trustProxy) {
        app.set('trust proxy', true);
      }
      
      // Request logging
      app.use(morgan(this.config.logLevel === 'debug' ? 'dev' : 'combined'));
      
      // Body parsing
      app.use(bodyParser.json({ limit: '50mb' }));
      app.use(bodyParser.urlencoded({ extended: true, limit: '50mb' }));
      
      // Configure file uploads
      const storage = multer.diskStorage({
        destination: (req, file, cb) => {
          cb(null, this.config.paths.temp);
        },
        filename: (req, file, cb) => {
          const uniqueSuffix = `${Date.now()}-${Math.round(Math.random() * 1E9)}`;
          const ext = path.extname(file.originalname);
          cb(null, `${file.fieldname}-${uniqueSuffix}${ext}`);
        }
      });
      
      const upload = multer({ 
        storage: storage,
        limits: { fileSize: this.config.imageProcessor.maxImageSize }
      });
      
      // Initialize API handler with Express app
      ImageApiHandler.init(app, this.config.apiHandler);
      
      // Set up routes
      
      // Upload image route
      app.post('/api/vortex-huraii/upload-image', upload.single('image'), (req, res) => {
        // Convert Express request to our format
        const request = {
          body: req.body,
          files: { image: req.file },
          ip: req.ip
        };
        
        // Process with our handler, converting the response back to Express format
        ImageProcessor.processImage(req.file.path, JSON.parse(req.body.options || '{}'))
          .then(result => {
            res.json({
              success: true,
              data: result
            });
          })
          .catch(error => {
            res.status(500).json({
              success: false,
              message: error.message,
              error_code: 'IMAGE_PROCESSING_ERROR'
            });
          });
      });
      
      // Generate variations route
      app.post('/api/vortex-huraii/generate-variations', upload.single('source_image'), (req, res) => {
        // Get request data
        const sourceImageId = req.body.source_image_id;
        const params = JSON.parse(req.body.params || '{}');
        const variationCount = parseInt(req.body.variation_count || '3', 10);
        
        // Get source image
        let sourceImage = null;
        
        if (req.file) {
          // Use uploaded file
          sourceImage = req.file.path;
        } else if (sourceImageId) {
          // In a real implementation, this would fetch the image from storage
          // For this demo, we'll simulate success for valid-looking IDs
          if (sourceImageId.indexOf('img_') !== 0 && 
              !(/^\d+$/.test(sourceImageId))) {
            return res.status(404).json({
              success: false,
              message: 'Source image not found',
              error_code: 'SOURCE_IMAGE_NOT_FOUND'
            });
          }
          
          // Simulate fetched image
          sourceImage = {
            id: sourceImageId,
            path: `/path/to/images/${sourceImageId}.jpg`
          };
        } else {
          return res.status(400).json({
            success: false,
            message: 'Source image ID or file is required',
            error_code: 'MISSING_SOURCE_IMAGE'
          });
        }
        
        // Generate variations
        ImageProcessor.generateVariations(sourceImage, params, variationCount)
          .then(results => {
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
            res.status(500).json({
              success: false,
              message: error.message,
              error_code: 'VARIATION_GENERATION_ERROR'
            });
          });
      });
      
      // Task status route
      app.get('/api/vortex-huraii/task-status/:taskId', (req, res) => {
        const taskId = req.params.taskId;
        
        // Get task status
        const taskStatus = ImageProcessor.getTaskStatus(taskId);
        
        if (!taskStatus) {
          return res.status(404).json({
            success: false,
            message: 'Task not found',
            error_code: 'TASK_NOT_FOUND'
          });
        }
        
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
      });
      
      // Static files (for testing/demo)
      app.use('/uploads', express.static(this.config.paths.uploads));
      
      // Start the server
      const server = app.listen(this.config.serverPort, () => {
        console.log(`HURAII Server running on port ${this.config.serverPort}`);
      });
      
      // Store references
      this.app = app;
      this.server = server;
      
      return app;
    },
    
    /**
     * Ensure necessary directories exist
     * @private
     */
    _ensureDirectories: function() {
      Object.values(this.config.paths).forEach(dir => {
        if (!fs.existsSync(dir)) {
          fs.mkdirSync(dir, { recursive: true });
          console.log(`Created directory: ${dir}`);
        }
      });
    },
    
    /**
     * Initialize logging
     * @private
     */
    _initializeLogging: function() {
      // Basic console logging
      const logLevels = ['error', 'warn', 'info', 'debug'];
      const activeLevel = this.config.logLevel || 'info';
      const levelIndex = logLevels.indexOf(activeLevel);
      
      // Only enable levels up to the configured level
      logLevels.forEach((level, index) => {
        if (index > levelIndex) {
          // Disable higher levels
          console[level] = function() {};
        }
      });
      
      console.info('Logging initialized at level:', activeLevel);
    },
    
    /**
     * Initialize modules
     * @param {Object} wordpress WordPress API object (optional)
     * @private
     */
    _initializeModules: function(wordpress) {
      // Initialize image processor
      ImageProcessor.init(this, this.config.imageProcessor);
      console.info('Image processor initialized');
      
      // If WordPress integration is requested
      if (wordpress) {
        WordPressIntegration.init(wordpress, this.config.wordpressIntegration);
        console.info('WordPress integration initialized');
      }
    }
  };
  
  // Export the module
  module.exports = HURAIIServer;
  
  // Auto-start if this is the main module
  if (require.main === module) {
    HURAIIServer.init().startStandaloneServer();
  }
} else {
  console.error('This module must be run in a Node.js environment');
} 