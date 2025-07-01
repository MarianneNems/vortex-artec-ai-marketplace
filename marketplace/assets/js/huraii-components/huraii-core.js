/**
 * HURAII Core Module
 * Handles core functionality, dependency management, and module loading
 */

(function(global, $) {
  'use strict';

  // Main HURAII namespace
  const HURAII = {
    version: '2.0.0',
    
    // Component registry
    components: {},
    
    // Configuration settings
    config: {
      apiEndpoint: '',
      nonce: '',
      userId: 0,
      defaultModel: 'sd-v2-1',
      maxWidth: 1024,
      maxHeight: 1024,
      supportedFormats: ['png', 'jpg', 'webp', 'gif', 'mp4', 'obj', 'glb'],
      enabledFeatures: {
        '2d': true,
        '3d': true,
        'video': true,
        'audio': false
      },
      learningEnabled: true,
      i18n: {}
    },
    
    // Global state
    state: {
      isInitialized: false,
      isGenerating: false,
      currentFormat: '2d',
      currentModel: '',
      seedValue: 0,
      interactionHistory: [],
      generationHistory: [],
      processingStart: null,
      connectionStatus: 'online',
      registeredEventHandlers: [],
      loadedComponents: []
    },
    
    /**
     * Initialize HURAII system
     * @param {Object} config Configuration options
     * @returns {Promise<Object>} Promise that resolves when initialization is complete
     */
    init: function(config = {}) {
      return new Promise((resolve, reject) => {
        try {
          // Merge provided config with defaults
          this.config = { ...this.config, ...config };
          
          // Generate random seed if not provided
          this.state.seedValue = Math.floor(Math.random() * 1000000);
          
          // Set default model
          this.state.currentModel = this.config.defaultModel;
          
          // Initialize service worker if browser supports it
          this._initializeServiceWorker();
          
          // Initialize connection monitoring
          this._initializeConnectionMonitoring();
          
          // Track initialization for AI learning
          this._trackActivity('system_initialization', {
            browser: navigator.userAgent,
            screenSize: `${window.innerWidth}x${window.innerHeight}`,
            timestamp: new Date().toISOString(),
            components: this.state.loadedComponents
          });
          
          // Set initialization flag
          this.state.isInitialized = true;
          this.state.processingStart = new Date();
          
          resolve(this);
        } catch (error) {
          reject(error);
        }
      });
    },
    
    /**
     * Register a component module
     * @param {string} name Component name
     * @param {Object} component Component implementation
     * @returns {HURAII} HURAII instance for chaining
     */
    registerComponent: function(name, component) {
      if (this.components[name]) {
        console.warn(`Component ${name} is already registered. Overwriting.`);
      }
      
      this.components[name] = component;
      this.state.loadedComponents.push(name);
      
      // Initialize component if HURAII is already initialized
      if (this.state.isInitialized && component.init) {
        component.init(this);
      }
      
      return this;
    },
    
    /**
     * Get a registered component
     * @param {string} name Component name
     * @returns {Object|null} Component object or null if not found
     */
    getComponent: function(name) {
      return this.components[name] || null;
    },
    
    /**
     * Load component modules asynchronously
     * @param {Array<string>} componentNames Array of component names to load
     * @returns {Promise<Array>} Promise that resolves when all components are loaded
     */
    loadComponents: function(componentNames) {
      const promises = componentNames.map(name => {
        return new Promise((resolve, reject) => {
          // Skip if already loaded
          if (this.components[name]) {
            resolve(this.components[name]);
            return;
          }
          
          // Load the component script
          const script = document.createElement('script');
          script.src = `${this.config.assetPath}/js/huraii-components/huraii-${name}.js`;
          script.async = true;
          
          script.onload = () => {
            // Component should register itself via registerComponent
            // Check if registration succeeded
            if (this.components[name]) {
              resolve(this.components[name]);
            } else {
              reject(new Error(`Component ${name} did not register properly`));
            }
          };
          
          script.onerror = () => {
            reject(new Error(`Failed to load component: ${name}`));
          };
          
          document.head.appendChild(script);
        });
      });
      
      return Promise.all(promises);
    },
    
    /**
     * Subscribe to an event
     * @param {string} eventName Name of the event
     * @param {Function} callback Callback function
     * @returns {Function} Unsubscribe function
     */
    on: function(eventName, callback) {
      if (!this.eventHandlers) {
        this.eventHandlers = {};
      }
      
      if (!this.eventHandlers[eventName]) {
        this.eventHandlers[eventName] = [];
      }
      
      this.eventHandlers[eventName].push(callback);
      
      // Track subscription for cleanup
      this.state.registeredEventHandlers.push({ event: eventName, callback });
      
      // Return unsubscribe function
      return () => {
        if (this.eventHandlers[eventName]) {
          this.eventHandlers[eventName] = this.eventHandlers[eventName].filter(
            cb => cb !== callback
          );
        }
      };
    },
    
    /**
     * Emit an event
     * @param {string} eventName Name of the event
     * @param {*} data Event data
     */
    emit: function(eventName, data) {
      if (!this.eventHandlers || !this.eventHandlers[eventName]) {
        return;
      }
      
      this.eventHandlers[eventName].forEach(callback => {
        try {
          callback(data);
        } catch (error) {
          console.error(`Error in event handler for ${eventName}:`, error);
        }
      });
    },
    
    /**
     * Internal method to initialize service worker
     * @private
     */
    _initializeServiceWorker: function() {
      if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/assets/js/huraii-components/huraii-service-worker.js')
          .then(registration => {
            console.log('HURAII Service Worker registered with scope:', registration.scope);
          })
          .catch(error => {
            console.error('HURAII Service Worker registration failed:', error);
          });
      }
    },
    
    /**
     * Initialize connection monitoring
     * @private
     */
    _initializeConnectionMonitoring: function() {
      // Monitor online/offline status
      window.addEventListener('online', () => {
        this.state.connectionStatus = 'online';
        this.emit('connection_change', { status: 'online' });
      });
      
      window.addEventListener('offline', () => {
        this.state.connectionStatus = 'offline';
        this.emit('connection_change', { status: 'offline' });
      });
      
      // Initial status
      this.state.connectionStatus = navigator.onLine ? 'online' : 'offline';
    },
    
    /**
     * Track user activity for AI learning
     * @param {string} action The action name
     * @param {Object} data Action data
     * @private
     */
    _trackActivity: function(action, data) {
      if (!this.config.learningEnabled) {
        return;
      }
      
      // Add to interaction history with timestamp
      const activityData = {
        action,
        data,
        timestamp: new Date().toISOString()
      };
      
      this.state.interactionHistory.push(activityData);
      
      // Limit history size
      if (this.state.interactionHistory.length > 100) {
        this.state.interactionHistory = this.state.interactionHistory.slice(-100);
      }
      
      // Emit activity event
      this.emit('activity_tracked', activityData);
      
      // If learning component is loaded, send activity data
      if (this.components.learning) {
        this.components.learning.processActivity(activityData);
      }
    },
    
    /**
     * Utility method for debouncing function calls
     * @param {Function} func Function to debounce
     * @param {number} wait Wait time in milliseconds
     * @returns {Function} Debounced function
     */
    debounce: function(func, wait) {
      let timeout;
      return function(...args) {
        const context = this;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
      };
    },
    
    /**
     * Utility method for throttling function calls
     * @param {Function} func Function to throttle
     * @param {number} limit Limit in milliseconds
     * @returns {Function} Throttled function
     */
    throttle: function(func, limit) {
      let inThrottle;
      return function(...args) {
        const context = this;
        if (!inThrottle) {
          func.apply(context, args);
          inThrottle = true;
          setTimeout(() => inThrottle = false, limit);
        }
      };
    },
    
    /**
     * Get session identifier
     * @returns {string} Unique session ID
     */
    getSessionId: function() {
      if (!this._sessionId) {
        const timestamp = new Date().getTime();
        const random = Math.floor(Math.random() * 1000000);
        this._sessionId = `huraii_${timestamp}_${random}`;
      }
      return this._sessionId;
    }
  };
  
  // Expose HURAII to global scope
  global.HURAII = HURAII;
  
})(window, jQuery); 