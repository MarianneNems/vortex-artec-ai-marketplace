/**
 * HURAII API Module
 * Handles all server communications with optimized request handling
 */

(function(global, $) {
  'use strict';
  
  // API Module
  const API = {
    /**
     * Module name
     */
    name: 'api',
    
    /**
     * Module configuration
     */
    config: {
      endpoints: {},
      requestTimeout: 60000, // 60 seconds
      retryCount: 3,
      retryDelay: 1000,
      batchSize: 20,
      useQueue: true,
      circuitBreakerThreshold: 5,
      circuitBreakerTimeout: 60000,
      useWebSocket: true,
      useAdvancedCache: true
    },
    
    /**
     * Request queue for batch processing
     */
    requestQueue: {
      standard: [],
      priority: [],
      background: []
    },
    
    /**
     * Active requests tracking
     */
    activeRequests: {},
    
    /**
     * Request cache
     */
    requestCache: null,
    
    /**
     * Ongoing request count
     */
    ongoingRequestCount: 0,
    
    /**
     * Initialize API module
     * @param {Object} core HURAII core instance
     */
    init: function(core) {
      this.core = core;
      
      // Merge config
      this.config = { 
        ...this.config, 
        ...core.config.api 
      };
      
      // Initialize request cache
      if (this.config.useAdvancedCache && core.components.lruCache) {
        this.requestCache = core.components.lruCache;
        this._log('Using advanced LRU cache for requests');
      } else {
        this.requestCache = new Map();
        this._log('Using simple Map cache for requests');
      }
      
      // Set up default endpoints
      this.config.endpoints = {
        generate: `${core.config.apiEndpoint}?action=vortex_huraii_generate`,
        analyze: `${core.config.apiEndpoint}?action=vortex_analyze_artwork`,
        models: `${core.config.apiEndpoint}?action=vortex_get_huraii_models`,
        save: `${core.config.apiEndpoint}?action=vortex_save_huraii_artwork`,
        cancel: `${core.config.apiEndpoint}?action=vortex_cancel_huraii_generation`,
        cross_agent: `${core.config.apiEndpoint}?action=vortex_cross_agent_request`,
        learn: `${core.config.apiEndpoint}?action=vortex_huraii_learn`,
        progress: `${core.config.apiEndpoint}?action=vortex_get_generation_progress`,
        ...this.config.endpoints
      };
      
      // Set up request processing
      if (this.config.useQueue) {
        this._startQueueProcessor();
      }
      
      // Set up request progress monitoring
      if (this.config.useWebSocket && core.components.websocket) {
        this._log('Using WebSocket for progress monitoring');
        // WebSocket progress tracking is handled by the WebSocket component
      } else {
        this._log('Using polling for progress monitoring');
        this._initProgressMonitoring();
      }
      
      // Register response interceptors for learning
      this._registerInterceptors();
      
      // Register with core
      core.registerComponent(this.name, this);
      
      return this;
    },
    
    /**
     * Generate artwork
     * @param {Object} params Generation parameters
     * @returns {Promise<Object>} Promise resolving to generation result
     */
    generateArtwork: function(params) {
      const requestData = {
        action: 'vortex_huraii_generate',
        params: params,
        nonce: this.core.config.nonce,
        learning_context: {
          interaction_history: this.core.state.interactionHistory.slice(-10),
          browser: navigator.userAgent,
          screen_size: `${window.innerWidth}x${window.innerHeight}`,
          session_start: this.core.state.processingStart.toISOString()
        }
      };
      
      // Set generation state
      this.core.state.isGenerating = true;
      
      return this.request('generate', requestData, {
        priority: true,
        progressTracking: true,
        retries: 1  // Less retries for generation to avoid duplicate generations
      }).then(response => {
        // Reset generation state
        this.core.state.isGenerating = false;
        return response;
      }).catch(error => {
        // Reset generation state
        this.core.state.isGenerating = false;
        throw error;
      });
    },
    
    /**
     * Cancel ongoing generation
     * @returns {Promise<Object>} Promise resolving when cancelled
     */
    cancelGeneration: function() {
      return this.request('cancel', {
        action: 'vortex_cancel_huraii_generation',
        nonce: this.core.config.nonce
      }, {
        priority: true
      });
    },
    
    /**
     * Get available models for a format
     * @param {string} format Content format (2d, 3d, video, audio)
     * @returns {Promise<Object>} Promise resolving to available models
     */
    getModelsForFormat: function(format) {
      const cacheKey = `models_${format}`;
      
      // Check cache first
      if (this.requestCache.has(cacheKey)) {
        return Promise.resolve(this.requestCache.get(cacheKey));
      }
      
      return this.request('models', {
        action: 'vortex_get_huraii_models',
        format: format,
        nonce: this.core.config.nonce
      }).then(response => {
        // Cache the response
        this.requestCache.set(cacheKey, response.models);
        // Cache expires after 5 minutes
        setTimeout(() => {
          this.requestCache.delete(cacheKey);
        }, 5 * 60 * 1000);
        
        return response.models;
      });
    },
    
    /**
     * Save generated artwork
     * @param {Object} result Generation result
     * @param {Object} metadata Artwork metadata
     * @returns {Promise<Object>} Promise resolving to save result
     */
    saveArtwork: function(result, metadata) {
      return this.request('save', {
        action: 'vortex_save_huraii_artwork',
        result: result,
        metadata: metadata,
        nonce: this.core.config.nonce
      }, {
        priority: true
      });
    },
    
    /**
     * Send learning data to server
     * @param {Array} interactions Array of user interactions
     * @returns {Promise<Object>} Promise resolving to learning result
     */
    sendLearningData: function(interactions) {
      return this.request('learn', {
        action: 'vortex_huraii_learn',
        interactions: interactions,
        user_id: this.core.config.userId,
        session_id: this.core.getSessionId(),
        nonce: this.core.config.nonce
      }, {
        background: true,
        retries: 5,
        timeout: 30000
      });
    },
    
    /**
     * Make cross-agent request
     * @param {string} targetAgent Target agent name
     * @param {Object} requestData Request data
     * @returns {Promise<Object>} Promise resolving to agent response
     */
    crossAgentRequest: function(targetAgent, requestData) {
      return this.request('cross_agent', {
        action: 'vortex_cross_agent_request',
        target_agent: targetAgent,
        request_data: requestData,
        nonce: this.core.config.nonce
      });
    },
    
    /**
     * Make generic API request
     * @param {string} endpoint Endpoint name or URL
     * @param {Object} data Request data
     * @param {Object} options Request options
     * @returns {Promise<Object>} Promise resolving to response data
     */
    request: function(endpoint, data, options = {}) {
      // Get endpoint URL
      const url = this.config.endpoints[endpoint] || endpoint;
      
      // Request options with defaults
      const requestOptions = {
        url: url,
        data: data,
        method: options.method || 'POST',
        priority: !!options.priority,
        background: !!options.background,
        progressTracking: !!options.progressTracking,
        cache: options.cache !== undefined ? options.cache : false,
        retries: options.retries !== undefined ? options.retries : this.config.retryCount,
        timeout: options.timeout || this.config.requestTimeout,
        requestId: options.requestId || this._generateRequestId(),
        cacheType: options.cacheType || null,
        cacheTTL: options.cacheTTL || null
      };
      
      // Check if circuit breaker is open for this endpoint
      if (!this._checkCircuitBreaker(url)) {
        return Promise.reject(new Error(`Circuit breaker open for endpoint: ${url}`));
      }
      
      // Check cache if enabled
      if (requestOptions.cache) {
        const cacheKey = this._getCacheKey(endpoint, data);
        
        // Check if using advanced cache
        if (this.config.useAdvancedCache && this.core.components.lruCache) {
          if (this.core.components.lruCache.has(cacheKey)) {
            this._updateMetrics('cache_hit', { endpoint: endpoint });
            return Promise.resolve(this.core.components.lruCache.get(cacheKey));
          } else {
            this._updateMetrics('cache_miss', { endpoint: endpoint });
          }
        } else if (this.requestCache.has(cacheKey)) {
          this._updateMetrics('cache_hit', { endpoint: endpoint });
          return Promise.resolve(this.requestCache.get(cacheKey));
        } else {
          this._updateMetrics('cache_miss', { endpoint: endpoint });
        }
      }
      
      // Create Promise
      const promise = new Promise((resolve, reject) => {
        // Add to appropriate queue if queue processing is enabled
        if (this.config.useQueue) {
          const queue = requestOptions.priority ? 'priority' : 
                        (requestOptions.background ? 'background' : 'standard');
          
          this.requestQueue[queue].push({
            options: requestOptions,
            resolve: resolve,
            reject: reject
          });
          
          // Track in active requests
          this.activeRequests[requestOptions.requestId] = {
            status: 'queued',
            timestamp: Date.now(),
            options: requestOptions
          };
          
          // Notify about queued request
          this.core.emit('request_queued', {
            requestId: requestOptions.requestId,
            endpoint: endpoint,
            queue: queue,
            timestamp: Date.now()
          });
          
          // Update metrics
          this._updateMetrics('request_queued', {
            endpoint: endpoint,
            queue: queue
          });
        } else {
          // Execute request immediately if queue is disabled
          this._executeRequest(requestOptions, resolve, reject);
        }
      });
      
      return promise;
    },
    
    /**
     * Execute a request
     * @param {Object} options Request options
     * @param {Function} resolve Promise resolve function
     * @param {Function} reject Promise reject function
     * @private
     */
    _executeRequest: function(options, resolve, reject) {
      // Update request status
      this.activeRequests[options.requestId] = {
        status: 'active',
        timestamp: Date.now(),
        options: options,
        retryCount: 0
      };
      
      this.ongoingRequestCount++;
      
      // Notify about request start
      this.core.emit('request_started', {
        requestId: options.requestId,
        endpoint: options.url,
        timestamp: Date.now()
      });
      
      // Update metrics
      this._updateMetrics('request_start', {
        endpoint: options.url
      });
      
      // Subscribe to WebSocket progress if available and tracking is enabled
      if (options.progressTracking && 
          this.config.useWebSocket && 
          this.core.components.websocket &&
          options.data.action === 'vortex_huraii_generate') {
        
        this.core.components.websocket.subscribeToProgress(
          options.data.params.request_id || options.requestId
        ).catch(error => {
          this._log('Error subscribing to WebSocket progress:', error);
        });
      }
      
      // Execute AJAX request
      $.ajax({
        url: options.url,
        type: options.method,
        data: options.data,
        timeout: options.timeout,
        success: (response) => {
          if (response.success) {
            // Update cache if caching is enabled
            if (options.cache) {
              const cacheKey = this._getCacheKey(options.url, options.data);
              
              // Use advanced cache if available
              if (this.config.useAdvancedCache && this.core.components.lruCache) {
                this.core.components.lruCache.set(cacheKey, response.data, {
                  type: options.cacheType,
                  ttl: options.cacheTTL
                });
              } else {
                this.requestCache.set(cacheKey, response.data);
                
                // Simple expiration for Map cache
                if (options.cacheTTL) {
                  setTimeout(() => {
                    this.requestCache.delete(cacheKey);
                  }, options.cacheTTL);
                }
              }
            }
            
            // Reset circuit breaker failures if exists
            if (this.circuitBreakers && this.circuitBreakers[options.url]) {
              this.circuitBreakers[options.url].failures = 0;
              this.circuitBreakers[options.url].lastSuccess = Date.now();
              
              // If in half-open state, close the circuit
              if (this.circuitBreakers[options.url].state === 'half-open') {
                this.circuitBreakers[options.url].state = 'closed';
                
                // Emit circuit breaker event
                this.core.emit('circuit_breaker_closed', {
                  endpoint: options.url,
                  timestamp: Date.now()
                });
              }
            }
            
            // Update request status
            this.activeRequests[options.requestId].status = 'completed';
            
            // Notify about request success
            this.core.emit('request_success', {
              requestId: options.requestId,
              endpoint: options.url,
              timestamp: Date.now(),
              responseTime: Date.now() - this.activeRequests[options.requestId].timestamp
            });
            
            // Resolve promise with response data
            resolve(response.data);
          } else {
            // Create error object
            const error = new Error(response.data?.message || 'Server error');
            error.response = response;
            
            // Update request status
            this.activeRequests[options.requestId].status = 'failed';
            this.activeRequests[options.requestId].error = error;
            
            // Notify about request failure
            this.core.emit('request_error', {
              requestId: options.requestId,
              endpoint: options.url,
              error: error.message,
              timestamp: Date.now()
            });
            
            // Reject promise with error
            reject(error);
          }
        },
        error: (xhr, status, errorThrown) => {
          // Create error object
          const error = new Error(errorThrown || status || 'Network error');
          error.xhr = xhr;
          error.status = status;
          
          // Check if retry is possible
          const requestInfo = this.activeRequests[options.requestId];
          const retryCount = (requestInfo.retryCount || 0) + 1;
          
          if (retryCount <= options.retries && status !== 'abort') {
            // Update retry count
            this.activeRequests[options.requestId].retryCount = retryCount;
            
            // Calculate retry delay with exponential backoff and jitter
            const baseDelay = this.config.retryDelay * Math.pow(2, retryCount - 1);
            const jitter = Math.random() * (baseDelay * 0.2); // 20% jitter
            const delay = baseDelay + jitter;
            
            // Notify about retry
            this.core.emit('request_retry', {
              requestId: options.requestId,
              endpoint: options.url,
              retryCount: retryCount,
              delay: delay,
              timestamp: Date.now()
            });
            
            // Update metrics
            this._updateMetrics('request_retry', {
              endpoint: options.url,
              retryCount: retryCount
            });
            
            // Retry after delay
            setTimeout(() => {
              this._executeRequest(options, resolve, reject);
            }, delay);
          } else {
            // Update request status
            this.activeRequests[options.requestId].status = 'failed';
            this.activeRequests[options.requestId].error = error;
            
            // Notify about request failure
            this.core.emit('request_error', {
              requestId: options.requestId,
              endpoint: options.url,
              error: error.message,
              retries: retryCount - 1,
              timestamp: Date.now()
            });
            
            // Reject promise with error
            reject(error);
          }
        },
        complete: () => {
          this.ongoingRequestCount--;
          
          // Cleanup for completed requests
          if (this.activeRequests[options.requestId].status !== 'active') {
            setTimeout(() => {
              delete this.activeRequests[options.requestId];
            }, 60000); // Keep request info for 1 minute for debugging
          }
        }
      });
    },
    
    /**
     * Start queue processor
     * @private
     */
    _startQueueProcessor: function() {
      // Process queues every 100ms
      setInterval(() => {
        // Process priority queue first
        if (this.requestQueue.priority.length > 0) {
          const request = this.requestQueue.priority.shift();
          this._executeRequest(request.options, request.resolve, request.reject);
        }
        // Then process standard queue
        else if (this.requestQueue.standard.length > 0) {
          const request = this.requestQueue.standard.shift();
          this._executeRequest(request.options, request.resolve, request.reject);
        }
        // Finally process background queue, but only if not many ongoing requests
        else if (this.requestQueue.background.length > 0 && this.ongoingRequestCount < 3) {
          const request = this.requestQueue.background.shift();
          this._executeRequest(request.options, request.resolve, request.reject);
        }
      }, 100);
      
      // Process batch requests for learning data
      setInterval(() => {
        this._processBatchRequests();
      }, 5000);
    },
    
    /**
     * Process batch requests for learning data
     * @private
     */
    _processBatchRequests: function() {
      // Find all learning data requests in the background queue
      const learningRequests = this.requestQueue.background.filter(req => 
        req.options.data.action === 'vortex_huraii_learn'
      );
      
      // Process in batches if we have enough requests
      if (learningRequests.length >= this.config.batchSize) {
        // Remove requests from queue
        this.requestQueue.background = this.requestQueue.background.filter(req => 
          req.options.data.action !== 'vortex_huraii_learn'
        );
        
        // Group learning data
        const batchedInteractions = [];
        const requestResolvers = [];
        const requestRejecters = [];
        const requestIds = [];
        
        // Collect data from each request
        learningRequests.slice(0, this.config.batchSize).forEach(request => {
          // Add interactions to batched data
          if (request.options.data.interactions && Array.isArray(request.options.data.interactions)) {
            batchedInteractions.push(...request.options.data.interactions);
          }
          
          // Store resolvers and rejecters
          requestResolvers.push(request.resolve);
          requestRejecters.push(request.reject);
          requestIds.push(request.options.requestId);
          
          // Update request status
          this.activeRequests[request.options.requestId].status = 'batched';
        });
        
        // Create batched request
        const batchedRequest = {
          url: this.config.endpoints.learn,
          method: 'POST',
          data: {
            action: 'vortex_huraii_learn_batch',
            interactions: batchedInteractions,
            user_id: this.core.config.userId,
            session_id: this.core.getSessionId(),
            nonce: this.core.config.nonce,
            batch_size: batchedInteractions.length,
            original_request_ids: requestIds
          },
          requestId: this._generateRequestId(),
          retries: this.config.retryCount,
          timeout: this.config.requestTimeout
        };
        
        // Execute batched request
        this.core.emit('batch_request_started', {
          requestId: batchedRequest.requestId,
          originalRequestIds: requestIds,
          batchSize: batchedInteractions.length,
          timestamp: Date.now()
        });
        
        // Update metrics
        this._updateMetrics('batch_created', {
          size: batchedInteractions.length,
          requestCount: requestIds.length
        });
        
        // Execute the batched request
        $.ajax({
          url: batchedRequest.url,
          type: batchedRequest.method,
          data: batchedRequest.data,
          timeout: batchedRequest.timeout,
          success: (response) => {
            if (response.success) {
              // Resolve all promises with success
              requestResolvers.forEach(resolve => {
                resolve(response.data);
              });
              
              // Update metrics
              this._updateMetrics('batch_success', {
                size: batchedInteractions.length,
                responseTime: Date.now() - this.activeRequests[batchedRequest.requestId]?.timestamp
              });
              
              // Emit event
              this.core.emit('batch_request_success', {
                requestId: batchedRequest.requestId,
                originalRequestIds: requestIds,
                timestamp: Date.now()
              });
            } else {
              // Create error object
              const error = new Error(response.data?.message || 'Batch request failed');
              error.response = response;
              
              // Reject all promises with error
              requestRejecters.forEach(reject => {
                reject(error);
              });
              
              // Update metrics
              this._updateMetrics('batch_error', {
                size: batchedInteractions.length,
                error: error.message
              });
              
              // Emit event
              this.core.emit('batch_request_error', {
                requestId: batchedRequest.requestId,
                originalRequestIds: requestIds,
                error: error.message,
                timestamp: Date.now()
              });
            }
          },
          error: (xhr, status, errorThrown) => {
            // Create error object
            const error = new Error(errorThrown || status || 'Network error in batch request');
            error.xhr = xhr;
            error.status = status;
            
            // Reject all promises with error
            requestRejecters.forEach(reject => {
              reject(error);
            });
            
            // Update metrics
            this._updateMetrics('batch_error', {
              size: batchedInteractions.length,
              error: error.message
            });
            
            // Emit event
            this.core.emit('batch_request_error', {
              requestId: batchedRequest.requestId,
              originalRequestIds: requestIds,
              error: error.message,
              timestamp: Date.now()
            });
          }
        });
      }
    },
    
    /**
     * Initialize progress monitoring for generation requests
     * @private
     */
    _initProgressMonitoring: function() {
      // Progress polling interval for generation requests
      setInterval(() => {
        // Find active generation requests
        const generationRequests = Object.values(this.activeRequests).filter(
          request => request.status === 'active' && 
                    request.options.progressTracking && 
                    request.options.data.action === 'vortex_huraii_generate'
        );
        
        // Poll progress for each request
        generationRequests.forEach(request => {
          $.ajax({
            url: this.config.endpoints.progress || this.core.config.apiEndpoint,
            type: 'POST',
            data: {
              action: 'vortex_get_generation_progress',
              request_id: request.options.data.params.request_id || request.options.requestId,
              nonce: this.core.config.nonce
            },
            success: (response) => {
              if (response.success && response.data) {
                // Emit progress event
                this.core.emit('generation_progress', {
                  requestId: request.options.requestId,
                  progress: response.data.progress,
                  step: response.data.step,
                  totalSteps: response.data.total_steps,
                  status: response.data.status,
                  message: response.data.message,
                  previewImage: response.data.preview_url,
                  timestamp: Date.now()
                });
              }
            }
          });
        });
      }, 1000);
    },
    
    /**
     * Register response interceptors for learning
     * @private
     */
    _registerInterceptors: function() {
      // Listen for request success events for learning purposes
      this.core.on('request_success', (data) => {
        // Only track certain types of requests
        const request = this.activeRequests[data.requestId];
        if (request && 
            (request.options.data.action === 'vortex_huraii_generate' || 
             request.options.data.action === 'vortex_analyze_artwork')) {
          
          // Track for learning if component exists
          if (this.core.components.learning) {
            this.core.components.learning.processRequestSuccess(data, request);
          }
        }
        
        // Update metrics
        this._updateMetrics('request_success', {
          endpoint: data.endpoint,
          responseTime: data.responseTime
        });
      });
      
      // Listen for request error events for learning purposes
      this.core.on('request_error', (data) => {
        // Track all errors for learning to improve reliability
        if (this.core.components.learning) {
          this.core.components.learning.processRequestError(data);
        }
        
        // Update metrics
        this._updateMetrics('request_error', {
          endpoint: data.endpoint,
          error: data.error
        });
        
        // Update circuit breaker if implemented
        this._updateCircuitBreaker(data.endpoint);
      });
    },
    
    /**
     * Generate unique request ID
     * @returns {string} Unique request ID
     * @private
     */
    _generateRequestId: function() {
      return `req_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    },
    
    /**
     * Generate cache key for a request
     * @param {string} endpoint Request endpoint
     * @param {Object} data Request data
     * @returns {string} Cache key
     * @private
     */
    _getCacheKey: function(endpoint, data) {
      return `${endpoint}_${JSON.stringify(data)}`;
    },
    
    /**
     * Update performance metrics
     * @param {string} metricType Type of metric to update
     * @param {Object} data Metric data
     * @private
     */
    _updateMetrics: function(metricType, data) {
      // Initialize metrics object if not exists
      if (!this.performanceMetrics) {
        this.performanceMetrics = {
          requestCounts: {
            total: 0,
            success: 0,
            error: 0,
            retry: 0,
            cancelled: 0
          },
          responseTimes: {
            average: 0,
            min: Infinity,
            max: 0,
            samples: 0
          },
          cacheStats: {
            hits: 0,
            misses: 0,
            ratio: 0
          },
          batchStats: {
            total: 0,
            success: 0,
            error: 0,
            averageSize: 0
          },
          endpointStats: {},
          lastUpdated: Date.now()
        };
      }
      
      // Update appropriate metrics based on type
      switch (metricType) {
        case 'request_start':
          this.performanceMetrics.requestCounts.total++;
          
          // Update endpoint stats
          if (data.endpoint) {
            if (!this.performanceMetrics.endpointStats[data.endpoint]) {
              this.performanceMetrics.endpointStats[data.endpoint] = {
                total: 0,
                success: 0,
                error: 0,
                avgResponseTime: 0
              };
            }
            this.performanceMetrics.endpointStats[data.endpoint].total++;
          }
          break;
          
        case 'request_success':
          this.performanceMetrics.requestCounts.success++;
          
          // Update response time stats
          if (data.responseTime) {
            const rt = this.performanceMetrics.responseTimes;
            const newSamples = rt.samples + 1;
            rt.average = ((rt.average * rt.samples) + data.responseTime) / newSamples;
            rt.min = Math.min(rt.min, data.responseTime);
            rt.max = Math.max(rt.max, data.responseTime);
            rt.samples = newSamples;
            
            // Update endpoint stats
            if (data.endpoint && this.performanceMetrics.endpointStats[data.endpoint]) {
              const endpointStats = this.performanceMetrics.endpointStats[data.endpoint];
              endpointStats.success++;
              endpointStats.avgResponseTime = 
                ((endpointStats.avgResponseTime * (endpointStats.success - 1)) + data.responseTime) / 
                endpointStats.success;
            }
          }
          break;
          
        case 'request_error':
          this.performanceMetrics.requestCounts.error++;
          
          // Update endpoint stats
          if (data.endpoint && this.performanceMetrics.endpointStats[data.endpoint]) {
            this.performanceMetrics.endpointStats[data.endpoint].error++;
          }
          break;
          
        case 'request_retry':
          this.performanceMetrics.requestCounts.retry++;
          break;
          
        case 'request_cancel':
          this.performanceMetrics.requestCounts.cancelled++;
          break;
          
        case 'cache_hit':
          this.performanceMetrics.cacheStats.hits++;
          this._updateCacheRatio();
          break;
          
        case 'cache_miss':
          this.performanceMetrics.cacheStats.misses++;
          this._updateCacheRatio();
          break;
          
        case 'batch_created':
          this.performanceMetrics.batchStats.total++;
          this.performanceMetrics.batchStats.averageSize = 
            ((this.performanceMetrics.batchStats.averageSize * (this.performanceMetrics.batchStats.total - 1)) + 
             data.size) / this.performanceMetrics.batchStats.total;
          break;
          
        case 'batch_success':
          this.performanceMetrics.batchStats.success++;
          break;
          
        case 'batch_error':
          this.performanceMetrics.batchStats.error++;
          break;
      }
      
      this.performanceMetrics.lastUpdated = Date.now();
      
      // Emit metrics update event periodically (no more than once per second)
      if (!this.lastMetricsEmitted || (Date.now() - this.lastMetricsEmitted > 1000)) {
        this.core.emit('performance_metrics_updated', {
          metrics: this.performanceMetrics,
          timestamp: Date.now()
        });
        this.lastMetricsEmitted = Date.now();
      }
    },
    
    /**
     * Update cache hit ratio
     * @private
     */
    _updateCacheRatio: function() {
      const stats = this.performanceMetrics.cacheStats;
      const total = stats.hits + stats.misses;
      stats.ratio = total > 0 ? stats.hits / total : 0;
    },
    
    /**
     * Circuit breaker pattern implementation for endpoints
     * @private
     */
    _updateCircuitBreaker: function(endpoint) {
      if (!endpoint) return;
      
      // Initialize circuit breakers if not exists
      if (!this.circuitBreakers) {
        this.circuitBreakers = {};
      }
      
      // Initialize circuit breaker for this endpoint if not exists
      if (!this.circuitBreakers[endpoint]) {
        this.circuitBreakers[endpoint] = {
          state: 'closed', // closed, open, half-open
          failures: 0,
          lastFailure: 0,
          lastSuccess: 0,
          nextAttempt: 0
        };
      }
      
      const breaker = this.circuitBreakers[endpoint];
      
      // Update failure count and time
      breaker.failures++;
      breaker.lastFailure = Date.now();
      
      // Check if we should open the circuit
      if (breaker.state === 'closed' && breaker.failures >= this.config.circuitBreakerThreshold) {
        breaker.state = 'open';
        breaker.nextAttempt = Date.now() + this.config.circuitBreakerTimeout;
        
        // Emit circuit breaker event
        this.core.emit('circuit_breaker_open', {
          endpoint: endpoint,
          failures: breaker.failures,
          timeout: this.config.circuitBreakerTimeout,
          timestamp: Date.now()
        });
      }
    },
    
    /**
     * Check circuit breaker status for an endpoint
     * @param {string} endpoint Endpoint to check
     * @returns {boolean} True if request can proceed, false if circuit is open
     * @private
     */
    _checkCircuitBreaker: function(endpoint) {
      if (!this.circuitBreakers || !this.circuitBreakers[endpoint]) {
        return true; // No circuit breaker for this endpoint
      }
      
      const breaker = this.circuitBreakers[endpoint];
      
      if (breaker.state === 'open') {
        // Check if we should try half-open
        if (Date.now() >= breaker.nextAttempt) {
          breaker.state = 'half-open';
          
          // Emit circuit breaker event
          this.core.emit('circuit_breaker_half_open', {
            endpoint: endpoint,
            timestamp: Date.now()
          });
          
          return true; // Allow one request to test the endpoint
        }
        
        return false; // Circuit is open, reject request
      }
      
      return true; // Circuit is closed or half-open
    },
    
    /**
     * Log debug message
     * @private
     */
    _log: function(...args) {
      if (this.core.config.debug) {
        console.log('[HURAII API]', ...args);
      }
    }
  };
  
  // Register with HURAII when loaded
  if (global.HURAII) {
    global.HURAII.registerComponent('api', API);
  } else {
    // Wait for HURAII to be defined
    document.addEventListener('DOMContentLoaded', () => {
      if (global.HURAII) {
        global.HURAII.registerComponent('api', API);
      } else {
        console.error('HURAII core module not found. API module initialization failed.');
      }
    });
  }
  
})(window, jQuery); 