/**
 * HURAII Learning Module
 * Handles AI learning, pattern detection, and cross-agent communication
 */

(function(global, $) {
  'use strict';
  
  // Learning Module
  const Learning = {
    /**
     * Module name
     */
    name: 'learning',
    
    /**
     * Module configuration
     */
    config: {
      batchSize: 20,
      sendInterval: 60000, // 1 minute
      compressionEnabled: true,
      storageName: 'vortex_huraii_learning_data',
      maxStorageItems: 500,
      crossAgentEnabled: true
    },
    
    /**
     * Learning data storage
     */
    data: {
      interactions: [],
      patterns: {},
      userPreferences: {},
      pendingBatch: [],
      trainingStatus: 'idle'
    },
    
    /**
     * Initialize Learning module
     * @param {Object} core HURAII core instance
     */
    init: function(core) {
      this.core = core;
      
      // Merge config
      this.config = { 
        ...this.config, 
        ...core.config.learning 
      };
      
      // Load stored learning data
      this._loadStoredData();
      
      // Start learning processes
      this._startDataSyncProcess();
      
      // Register pattern analyzer worker if available
      this._initializePatternAnalyzer();
      
      // Register with core
      core.registerComponent(this.name, this);
      
      return this;
    },
    
    /**
     * Process user activity for learning
     * @param {Object} activity Activity data
     */
    processActivity: function(activity) {
      if (!activity || !activity.action) return;
      
      // Add to interactions history
      this.data.interactions.push(activity);
      
      // Limit interactions history size
      if (this.data.interactions.length > this.config.maxStorageItems) {
        this.data.interactions = this.data.interactions.slice(-this.config.maxStorageItems);
      }
      
      // Add to pending batch
      this.data.pendingBatch.push(activity);
      
      // Store periodically
      this._debouncedStore();
      
      // Process activity for immediate learning
      this._processForImmediateLearning(activity);
    },
    
    /**
     * Process successful requests for learning
     * @param {Object} data Request success data
     * @param {Object} request Request information
     */
    processRequestSuccess: function(data, request) {
      // Skip if data is missing
      if (!data || !request) return;
      
      const actionType = request.options.data.action;
      let learningData = {
        action: 'request_success',
        requestType: actionType,
        responseTime: data.responseTime,
        requestId: data.requestId,
        timestamp: data.timestamp
      };
      
      // Add specific data for generation requests
      if (actionType === 'vortex_huraii_generate') {
        learningData.generationParams = request.options.data.params;
      }
      
      // Process learning data
      this.processActivity(learningData);
    },
    
    /**
     * Process request errors for learning
     * @param {Object} data Error data
     */
    processRequestError: function(data) {
      // Skip if data is missing
      if (!data) return;
      
      const learningData = {
        action: 'request_error',
        endpoint: data.endpoint,
        error: data.error,
        retries: data.retries,
        timestamp: data.timestamp
      };
      
      // Process learning data
      this.processActivity(learningData);
    },
    
    /**
     * Get user preference for a specific aspect
     * @param {string} aspect The aspect to get preference for
     * @returns {*} The preference value or null if not found
     */
    getUserPreference: function(aspect) {
      return this.data.userPreferences[aspect] || null;
    },
    
    /**
     * Get generation suggestions based on current input
     * @param {string} prompt Current prompt
     * @param {Object} params Current parameters
     * @returns {Object} Suggestions for generation
     */
    getGenerationSuggestions: function(prompt, params) {
      const suggestions = {
        promptAdditions: [],
        parameterAdjustments: {},
        styleRecommendations: []
      };
      
      // Analyze prompt for suggestions
      if (prompt && prompt.length > 3) {
        // Find similar successful prompts from history
        const similarPrompts = this._findSimilarPrompts(prompt);
        
        // Extract prompt additions from similar prompts
        if (similarPrompts.length > 0) {
          suggestions.promptAdditions = this._extractPromptAdditions(prompt, similarPrompts);
          
          // Extract parameter adjustments from similar prompts
          suggestions.parameterAdjustments = this._extractParamAdjustments(similarPrompts, params);
          
          // Extract style recommendations
          suggestions.styleRecommendations = this._extractStyleRecommendations(similarPrompts);
        }
      }
      
      // Apply user preferences to suggestions
      this._applyUserPreferencesToSuggestions(suggestions);
      
      return suggestions;
    },
    
    /**
     * Get predictions for improved generation
     * @param {Object} result Generation result
     * @param {Object} params Generation parameters
     * @returns {Object} Predictions for improved results
     */
    getPredictiveImprovements: function(result, params) {
      if (!result || !params) return {};
      
      const improvements = {
        parameterSuggestions: {},
        compositionalAdjustments: {},
        styleEnhancements: []
      };
      
      // TODO: Implement predictive improvements based on result analysis
      
      return improvements;
    },
    
    /**
     * Synchronize learning data with server
     * @returns {Promise} Promise resolving when sync is complete
     */
    syncLearningData: function() {
      // Skip if no data to sync
      if (this.data.pendingBatch.length === 0) {
        return Promise.resolve({success: true, message: 'No data to sync'});
      }
      
      // Update status
      this.data.trainingStatus = 'syncing';
      
      // Prepare batch data
      const batchData = [...this.data.pendingBatch];
      
      // Clear pending batch
      this.data.pendingBatch = [];
      
      // Get API component
      const api = this.core.getComponent('api');
      if (!api) {
        this.data.trainingStatus = 'error';
        return Promise.reject(new Error('API component not available'));
      }
      
      // Send data to server
      return api.sendLearningData(batchData)
        .then(response => {
          this.data.trainingStatus = 'idle';
          
          // Apply any model adjustments from server
          if (response.adjustments) {
            this._applyModelAdjustments(response.adjustments);
          }
          
          return response;
        })
        .catch(error => {
          // On error, add back to pending batch
          this.data.pendingBatch = [...batchData, ...this.data.pendingBatch];
          this.data.trainingStatus = 'error';
          
          throw error;
        });
    },
    
    /**
     * Start periodic data synchronization
     * @private
     */
    _startDataSyncProcess: function() {
      // Sync every minute
      setInterval(() => {
        // Only sync if there's data and not already syncing
        if (this.data.pendingBatch.length > 0 && this.data.trainingStatus !== 'syncing') {
          this.syncLearningData().catch(error => {
            console.error('Error syncing learning data:', error);
          });
        }
      }, this.config.sendInterval);
    },
    
    /**
     * Initialize pattern analyzer worker
     * @private
     */
    _initializePatternAnalyzer: function() {
      if ('Worker' in window) {
        try {
          this.patternWorker = new Worker('/assets/js/huraii-components/huraii-pattern-worker.js');
          
          this.patternWorker.onmessage = (e) => {
            if (e.data.type === 'patterns_updated') {
              this.data.patterns = e.data.patterns;
              this._storeData();
            }
          };
          
          // Initial pattern analysis with existing data
          if (this.data.interactions.length > 0) {
            this.patternWorker.postMessage({
              type: 'analyze_patterns',
              interactions: this.data.interactions
            });
          }
        } catch (error) {
          console.error('Pattern worker initialization failed:', error);
        }
      }
    },
    
    /**
     * Process activity for immediate learning
     * @param {Object} activity Activity data
     * @private
     */
    _processForImmediateLearning: function(activity) {
      // Extract user preferences from activity
      if (activity.action === 'generation_requested') {
        this._updateUserPreferences(activity.data);
      }
      
      // Update pattern analysis if worker is available
      if (this.patternWorker && this.data.pendingBatch.length >= 5) {
        this.patternWorker.postMessage({
          type: 'analyze_patterns',
          interactions: this.data.pendingBatch
        });
      }
      
      // Share with other agents if enabled
      if (this.config.crossAgentEnabled && 
          (activity.action === 'generation_completed' || 
           activity.action === 'artwork_saved')) {
        this._shareLearningWithOtherAgents(activity);
      }
    },
    
    /**
     * Update user preferences based on activity
     * @param {Object} data Activity data
     * @private
     */
    _updateUserPreferences: function(data) {
      if (!data) return;
      
      // Update generation preferences
      if (data.parameters) {
        const params = data.parameters;
        
        // Update format preference
        if (params.format) {
          this._updatePreferenceCount('format', params.format);
        }
        
        // Update model preference
        if (params.model) {
          this._updatePreferenceCount('model', params.model);
        }
        
        // Update dimensions preference
        if (params.width && params.height) {
          const dimensionKey = `${params.width}x${params.height}`;
          this._updatePreferenceCount('dimensions', dimensionKey);
        }
        
        // Update style preference if present
        if (params.style_preset) {
          this._updatePreferenceCount('style', params.style_preset);
        }
        
        // Extract keywords from prompt for subject preferences
        if (params.prompt) {
          const keywords = this._extractKeywords(params.prompt);
          keywords.forEach(keyword => {
            this._updatePreferenceCount('subject', keyword);
          });
        }
      }
    },
    
    /**
     * Update preference count for specific aspect
     * @param {string} aspect Preference aspect
     * @param {string} value Preference value
     * @private
     */
    _updatePreferenceCount: function(aspect, value) {
      if (!this.data.userPreferences[aspect]) {
        this.data.userPreferences[aspect] = {};
      }
      
      if (!this.data.userPreferences[aspect][value]) {
        this.data.userPreferences[aspect][value] = 0;
      }
      
      this.data.userPreferences[aspect][value]++;
      
      // Normalize preferences periodically
      if (Object.keys(this.data.userPreferences[aspect]).length > 20) {
        this._normalizePreferences(aspect);
      }
    },
    
    /**
     * Normalize preferences to prevent overflow
     * @param {string} aspect Preference aspect to normalize
     * @private
     */
    _normalizePreferences: function(aspect) {
      const preferences = this.data.userPreferences[aspect];
      
      // Find max value
      const maxValue = Math.max(...Object.values(preferences));
      
      // Normalize all values
      for (const key in preferences) {
        preferences[key] = preferences[key] / maxValue;
      }
    },
    
    /**
     * Extract keywords from prompt
     * @param {string} prompt Prompt text
     * @returns {Array} Array of keywords
     * @private
     */
    _extractKeywords: function(prompt) {
      if (!prompt) return [];
      
      // Split by common separators
      const parts = prompt.toLowerCase()
        .replace(/[.,;:!?(){}[\]<>]/g, ' ')
        .split(/\s+/);
      
      // Filter out common words and short words
      const stopWords = ['a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'with', 'by', 'of'];
      return parts.filter(word => 
        word.length > 3 && !stopWords.includes(word)
      );
    },
    
    /**
     * Share learning data with other AI agents
     * @param {Object} activity Activity to share
     * @private
     */
    _shareLearningWithOtherAgents: function(activity) {
      // Get API component
      const api = this.core.getComponent('api');
      if (!api) return;
      
      // Share with CLOE
      api.crossAgentRequest('cloe', {
        action: 'process_huraii_learning',
        data: activity
      }).catch(() => {
        // Silent failure is ok for cross-agent communication
      });
      
      // Share with BusinessStrategist if the activity is related to saved artwork
      if (activity.action === 'artwork_saved') {
        api.crossAgentRequest('business_strategist', {
          action: 'process_artwork_data',
          data: activity
        }).catch(() => {
          // Silent failure is ok for cross-agent communication
        });
      }
    },
    
    /**
     * Apply model adjustments from server
     * @param {Object} adjustments Adjustments to apply
     * @private
     */
    _applyModelAdjustments: function(adjustments) {
      if (!adjustments) return;
      
      // Apply prompt suggestions
      if (adjustments.promptSuggestions) {
        // TODO: Update UI with prompt suggestions
      }
      
      // Apply model recommendations
      if (adjustments.recommendedModel) {
        // TODO: Update UI with model recommendations
      }
      
      // Apply UI recommendations
      if (adjustments.uiRecommendations) {
        // TODO: Update UI layout
      }
    },
    
    /**
     * Load stored learning data
     * @private
     */
    _loadStoredData: function() {
      try {
        const storedData = localStorage.getItem(this.config.storageName);
        if (storedData) {
          const parsedData = JSON.parse(storedData);
          
          // Merge with default data structure
          this.data = {
            ...this.data,
            ...parsedData
          };
        }
      } catch (error) {
        console.error('Error loading stored learning data:', error);
      }
    },
    
    /**
     * Store learning data
     * @private
     */
    _storeData: function() {
      try {
        const dataToStore = {
          patterns: this.data.patterns,
          userPreferences: this.data.userPreferences,
          // Don't store full interactions, just the count
          interactionCount: this.data.interactions.length
        };
        
        localStorage.setItem(this.config.storageName, JSON.stringify(dataToStore));
      } catch (error) {
        console.error('Error storing learning data:', error);
      }
    },
    
    /**
     * Debounced store function
     * @private
     */
    _debouncedStore: function() {
      if (this._storeTimeout) {
        clearTimeout(this._storeTimeout);
      }
      
      this._storeTimeout = setTimeout(() => {
        this._storeData();
      }, 2000);
    },
    
    /**
     * Find similar prompts in history
     * @param {string} prompt Current prompt
     * @returns {Array} Array of similar prompts
     * @private
     */
    _findSimilarPrompts: function(prompt) {
      // Simplified implementation - in a real system this would use embeddings or more sophisticated text similarity
      const keywords = this._extractKeywords(prompt);
      
      return this.data.interactions
        .filter(interaction => 
          interaction.action === 'generation_completed' && 
          interaction.data && 
          interaction.data.parameters && 
          interaction.data.parameters.prompt
        )
        .map(interaction => {
          const interactionKeywords = this._extractKeywords(interaction.data.parameters.prompt);
          const matchCount = keywords.filter(kw => interactionKeywords.includes(kw)).length;
          return {
            interaction,
            similarity: matchCount / Math.max(keywords.length, interactionKeywords.length)
          };
        })
        .filter(item => item.similarity > 0.3) // Only include reasonably similar prompts
        .sort((a, b) => b.similarity - a.similarity)
        .slice(0, 5)
        .map(item => item.interaction);
    },
    
    /**
     * Extract prompt additions from similar prompts
     * @param {string} currentPrompt Current prompt
     * @param {Array} similarPrompts Similar prompts
     * @returns {Array} Prompt addition suggestions
     * @private
     */
    _extractPromptAdditions: function(currentPrompt, similarPrompts) {
      // Find successful prompt patterns that aren't in the current prompt
      const currentWords = new Set(this._extractKeywords(currentPrompt));
      const suggestions = new Set();
      
      similarPrompts.forEach(interaction => {
        if (!interaction.data || !interaction.data.parameters || !interaction.data.parameters.prompt) return;
        
        const promptWords = this._extractKeywords(interaction.data.parameters.prompt);
        
        // Extract phrases not in current prompt
        const promptParts = interaction.data.parameters.prompt.split(',');
        
        promptParts.forEach(part => {
          part = part.trim();
          if (part.length > 0 && !currentPrompt.includes(part)) {
            const partWords = this._extractKeywords(part);
            // Only add if the part contains some words not in the current prompt
            if (partWords.some(word => !currentWords.has(word))) {
              suggestions.add(part);
            }
          }
        });
      });
      
      return Array.from(suggestions).slice(0, 3);
    },
    
    /**
     * Extract parameter adjustments from similar prompts
     * @param {Array} similarPrompts Similar prompts
     * @param {Object} currentParams Current parameters
     * @returns {Object} Parameter adjustment suggestions
     * @private
     */
    _extractParamAdjustments: function(similarPrompts, currentParams) {
      const adjustments = {};
      
      // Only consider numeric parameters for now
      const numericParams = ['cfg_scale', 'steps'];
      
      numericParams.forEach(param => {
        if (!currentParams[param]) return;
        
        // Collect values from similar prompts
        const values = similarPrompts
          .filter(interaction => 
            interaction.data && 
            interaction.data.parameters && 
            interaction.data.parameters[param] !== undefined
          )
          .map(interaction => interaction.data.parameters[param]);
        
        if (values.length > 0) {
          // Calculate average
          const avg = values.reduce((sum, val) => sum + val, 0) / values.length;
          const currentVal = currentParams[param];
          
          // Only suggest adjustment if significantly different
          if (Math.abs(avg - currentVal) / currentVal > 0.2) {
            adjustments[param] = Math.round(avg * 100) / 100;
          }
        }
      });
      
      return adjustments;
    },
    
    /**
     * Extract style recommendations from similar prompts
     * @param {Array} similarPrompts Similar prompts
     * @returns {Array} Style recommendations
     * @private
     */
    _extractStyleRecommendations: function(similarPrompts) {
      const styles = new Map();
      
      similarPrompts.forEach(interaction => {
        if (!interaction.data || !interaction.data.parameters) return;
        
        const params = interaction.data.parameters;
        
        // Check for explicit style preset
        if (params.style_preset) {
          const count = styles.get(params.style_preset) || 0;
          styles.set(params.style_preset, count + 1);
        }
        
        // Extract style from prompt
        if (params.prompt) {
          // Style-related keywords
          const styleKeywords = [
            'style of', 'in style', 'aesthetic', 'inspired by', 'like a', 
            'similar to', 'art by', 'painted by'
          ];
          
          const promptLower = params.prompt.toLowerCase();
          
          styleKeywords.forEach(keyword => {
            const index = promptLower.indexOf(keyword);
            if (index >= 0) {
              // Extract style phrase (up to 30 chars after keyword)
              const stylePhrase = params.prompt.substr(index, keyword.length + 30).split(',')[0].trim();
              const count = styles.get(stylePhrase) || 0;
              styles.set(stylePhrase, count + 1);
            }
          });
        }
      });
      
      // Sort by count and return top styles
      return Array.from(styles.entries())
        .sort((a, b) => b[1] - a[1])
        .slice(0, 3)
        .map(entry => entry[0]);
    },
    
    /**
     * Apply user preferences to suggestions
     * @param {Object} suggestions Suggestions to modify
     * @private
     */
    _applyUserPreferencesToSuggestions: function(suggestions) {
      // Adjust parameter suggestions based on user preferences
      if (this.data.userPreferences.model) {
        const modelPreferences = this.data.userPreferences.model;
        const topModel = Object.entries(modelPreferences)
          .sort((a, b) => b[1] - a[1])
          .shift();
        
        if (topModel) {
          suggestions.parameterAdjustments.model = topModel[0];
        }
      }
      
      // Add dimension preferences
      if (this.data.userPreferences.dimensions) {
        const dimensionPreferences = this.data.userPreferences.dimensions;
        const topDimension = Object.entries(dimensionPreferences)
          .sort((a, b) => b[1] - a[1])
          .shift();
        
        if (topDimension) {
          const [width, height] = topDimension[0].split('x').map(Number);
          suggestions.parameterAdjustments.width = width;
          suggestions.parameterAdjustments.height = height;
        }
      }
    }
  };
  
  // Register with HURAII when loaded
  if (global.HURAII) {
    global.HURAII.registerComponent('learning', Learning);
  } else {
    // Wait for HURAII to be defined
    document.addEventListener('DOMContentLoaded', () => {
      if (global.HURAII) {
        global.HURAII.registerComponent('learning', Learning);
      } else {
        console.error('HURAII core module not found. Learning module initialization failed.');
      }
    });
  }
  
})(window, jQuery); 