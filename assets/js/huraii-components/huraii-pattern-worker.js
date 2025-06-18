/**
 * HURAII Pattern Analysis Worker
 * 
 * Analyzes user interactions and generations to detect patterns
 * This runs in a separate thread to avoid blocking the UI
 */

// WorkerGlobalScope doesn't have window, use self instead
self.addEventListener('message', function(e) {
  const data = e.data;
  
  if (!data || !data.type) {
    self.postMessage({
      type: 'error',
      message: 'Invalid worker message format'
    });
    return;
  }
  
  try {
    switch (data.type) {
      case 'analyze_patterns':
        const patterns = analyzePatterns(data.interactions || []);
        self.postMessage({
          type: 'patterns_updated',
          patterns: patterns
        });
        break;
        
      case 'extract_features':
        const features = extractFeatures(data.image || {}, data.options || {});
        self.postMessage({
          type: 'features_extracted',
          features: features
        });
        break;
        
      case 'find_similar_prompts':
        const similarPrompts = findSimilarPrompts(
          data.prompt || '',
          data.interactions || [],
          data.options || {}
        );
        self.postMessage({
          type: 'similar_prompts_found',
          prompts: similarPrompts
        });
        break;
        
      default:
        self.postMessage({
          type: 'error',
          message: 'Unknown worker command: ' + data.type
        });
    }
  } catch (error) {
    self.postMessage({
      type: 'error',
      message: error.message,
      stack: error.stack
    });
  }
});

/**
 * Analyze interaction patterns
 * @param {Array} interactions Array of user interactions
 * @returns {Object} Patterns extracted from interactions
 */
function analyzePatterns(interactions) {
  // Skip if no interactions
  if (!interactions || interactions.length === 0) {
    return {};
  }
  
  // Initialize patterns object
  const patterns = {
    timePatterns: analyzeTimePatterns(interactions),
    promptPatterns: analyzePromptPatterns(interactions),
    parameterPatterns: analyzeParameterPatterns(interactions),
    userBehaviorPatterns: analyzeUserBehaviorPatterns(interactions),
    successPatterns: analyzeSuccessPatterns(interactions)
  };
  
  return patterns;
}

/**
 * Analyze time-based patterns
 * @param {Array} interactions User interactions
 * @returns {Object} Time-based patterns
 */
function analyzeTimePatterns(interactions) {
  const timePatterns = {
    hourDistribution: Array(24).fill(0),
    weekdayDistribution: Array(7).fill(0),
    averageSessionDuration: 0,
    timeGaps: []
  };
  
  // Skip if not enough interactions
  if (interactions.length < 2) {
    return timePatterns;
  }
  
  // Sort interactions by timestamp
  const sortedInteractions = [...interactions].sort((a, b) => {
    return new Date(a.timestamp) - new Date(b.timestamp);
  });
  
  // Analyze hour and weekday distribution
  sortedInteractions.forEach(interaction => {
    const date = new Date(interaction.timestamp);
    const hour = date.getHours();
    const weekday = date.getDay();
    
    timePatterns.hourDistribution[hour]++;
    timePatterns.weekdayDistribution[weekday]++;
  });
  
  // Analyze session durations and gaps
  let sessionStart = null;
  let lastTimestamp = null;
  let sessionDurations = [];
  
  sortedInteractions.forEach(interaction => {
    const timestamp = new Date(interaction.timestamp).getTime();
    
    // Initialize
    if (sessionStart === null) {
      sessionStart = timestamp;
      lastTimestamp = timestamp;
      return;
    }
    
    // Calculate gap from last interaction
    const gap = (timestamp - lastTimestamp) / 1000; // in seconds
    timePatterns.timeGaps.push(gap);
    
    // If gap is more than 30 minutes, consider it a new session
    if (gap > 30 * 60) {
      const sessionDuration = (lastTimestamp - sessionStart) / 1000;
      sessionDurations.push(sessionDuration);
      sessionStart = timestamp;
    }
    
    lastTimestamp = timestamp;
  });
  
  // Add the last session
  if (sessionStart !== null && lastTimestamp !== null) {
    const sessionDuration = (lastTimestamp - sessionStart) / 1000;
    sessionDurations.push(sessionDuration);
  }
  
  // Calculate average session duration
  if (sessionDurations.length > 0) {
    const totalDuration = sessionDurations.reduce((sum, duration) => sum + duration, 0);
    timePatterns.averageSessionDuration = totalDuration / sessionDurations.length;
  }
  
  return timePatterns;
}

/**
 * Analyze prompt patterns
 * @param {Array} interactions User interactions
 * @returns {Object} Prompt-based patterns
 */
function analyzePromptPatterns(interactions) {
  const promptPatterns = {
    commonTerms: {},
    averagePromptLength: 0,
    promptComplexity: 0,
    popularStyles: {},
    popularSubjects: {}
  };
  
  // Filter to only generation requests with prompts
  const promptInteractions = interactions.filter(interaction => 
    (interaction.action === 'generation_requested' || 
     interaction.action === 'generation_completed') && 
    interaction.data && 
    interaction.data.parameters && 
    interaction.data.parameters.prompt
  );
  
  if (promptInteractions.length === 0) {
    return promptPatterns;
  }
  
  // Track prompt lengths
  let totalLength = 0;
  
  // Analyze each prompt
  promptInteractions.forEach(interaction => {
    const prompt = interaction.data.parameters.prompt.toLowerCase();
    totalLength += prompt.length;
    
    // Analyze prompt complexity (comma count)
    promptPatterns.promptComplexity += (prompt.match(/,/g) || []).length;
    
    // Extract terms from prompt
    const terms = extractTermsFromPrompt(prompt);
    
    // Count term occurrences
    terms.forEach(term => {
      promptPatterns.commonTerms[term] = (promptPatterns.commonTerms[term] || 0) + 1;
    });
    
    // Extract styles and subjects
    const styleTerms = extractStyleTerms(prompt);
    const subjectTerms = extractSubjectTerms(prompt);
    
    // Count style and subject occurrences
    styleTerms.forEach(style => {
      promptPatterns.popularStyles[style] = (promptPatterns.popularStyles[style] || 0) + 1;
    });
    
    subjectTerms.forEach(subject => {
      promptPatterns.popularSubjects[subject] = (promptPatterns.popularSubjects[subject] || 0) + 1;
    });
  });
  
  // Calculate average prompt length
  promptPatterns.averagePromptLength = totalLength / promptInteractions.length;
  
  // Calculate average prompt complexity
  promptPatterns.promptComplexity /= promptInteractions.length;
  
  return promptPatterns;
}

/**
 * Analyze parameter patterns
 * @param {Array} interactions User interactions
 * @returns {Object} Parameter-based patterns
 */
function analyzeParameterPatterns(interactions) {
  const parameterPatterns = {
    dimensions: {},
    cfgScale: {},
    steps: {},
    model: {},
    format: {}
  };
  
  // Filter to only generation requests
  const generationInteractions = interactions.filter(interaction => 
    (interaction.action === 'generation_requested' || 
     interaction.action === 'generation_completed') && 
    interaction.data && 
    interaction.data.parameters
  );
  
  if (generationInteractions.length === 0) {
    return parameterPatterns;
  }
  
  // Analyze each generation request
  generationInteractions.forEach(interaction => {
    const params = interaction.data.parameters;
    
    // Track dimensions
    if (params.width && params.height) {
      const dimensionKey = `${params.width}x${params.height}`;
      parameterPatterns.dimensions[dimensionKey] = (parameterPatterns.dimensions[dimensionKey] || 0) + 1;
    }
    
    // Track CFG scale
    if (params.cfg_scale) {
      const cfgKey = params.cfg_scale.toString();
      parameterPatterns.cfgScale[cfgKey] = (parameterPatterns.cfgScale[cfgKey] || 0) + 1;
    }
    
    // Track steps
    if (params.steps) {
      const stepsKey = params.steps.toString();
      parameterPatterns.steps[stepsKey] = (parameterPatterns.steps[stepsKey] || 0) + 1;
    }
    
    // Track model
    if (params.model) {
      parameterPatterns.model[params.model] = (parameterPatterns.model[params.model] || 0) + 1;
    }
    
    // Track format
    if (params.format) {
      parameterPatterns.format[params.format] = (parameterPatterns.format[params.format] || 0) + 1;
    }
  });
  
  return parameterPatterns;
}

/**
 * Analyze user behavior patterns
 * @param {Array} interactions User interactions
 * @returns {Object} User behavior patterns
 */
function analyzeUserBehaviorPatterns(interactions) {
  const behaviorPatterns = {
    interactionTypes: {},
    promptEditCount: 0,
    generationRate: 0,
    saveRate: 0,
    editRate: 0,
    interactionSequences: {}
  };
  
  // Skip if not enough interactions
  if (interactions.length < 2) {
    return behaviorPatterns;
  }
  
  // Count interaction types
  interactions.forEach(interaction => {
    behaviorPatterns.interactionTypes[interaction.action] = 
      (behaviorPatterns.interactionTypes[interaction.action] || 0) + 1;
  });
  
  // Count prompt edits
  const promptEdits = interactions.filter(interaction => 
    interaction.action === 'prompt_input'
  ).length;
  behaviorPatterns.promptEditCount = promptEdits;
  
  // Calculate generation rate
  const generations = (behaviorPatterns.interactionTypes['generation_requested'] || 0);
  behaviorPatterns.generationRate = generations / interactions.length;
  
  // Calculate save rate
  const saves = (behaviorPatterns.interactionTypes['artwork_saved'] || 0);
  behaviorPatterns.saveRate = generations > 0 ? saves / generations : 0;
  
  // Calculate edit rate (variations, upscales)
  const edits = (behaviorPatterns.interactionTypes['variation_requested'] || 0) + 
                (behaviorPatterns.interactionTypes['upscale_requested'] || 0);
  behaviorPatterns.editRate = generations > 0 ? edits / generations : 0;
  
  // Analyze interaction sequences
  for (let i = 0; i < interactions.length - 1; i++) {
    const currentAction = interactions[i].action;
    const nextAction = interactions[i + 1].action;
    const sequenceKey = `${currentAction}→${nextAction}`;
    
    behaviorPatterns.interactionSequences[sequenceKey] = 
      (behaviorPatterns.interactionSequences[sequenceKey] || 0) + 1;
  }
  
  return behaviorPatterns;
}

/**
 * Analyze success patterns
 * @param {Array} interactions User interactions
 * @returns {Object} Success patterns
 */
function analyzeSuccessPatterns(interactions) {
  const successPatterns = {
    saveToGenerateRatio: 0,
    modelSuccessRates: {},
    promptFactors: {
      length: {
        short: { count: 0, saves: 0 },
        medium: { count: 0, saves: 0 },
        long: { count: 0, saves: 0 }
      },
      complexity: {
        simple: { count: 0, saves: 0 },
        moderate: { count: 0, saves: 0 },
        complex: { count: 0, saves: 0 }
      }
    },
    successfulPromptTerms: {}
  };
  
  // Track generations and saves by jobId
  const jobOutcomes = new Map();
  
  // Find all generation requests and their outcomes
  interactions.forEach(interaction => {
    // Track new generations
    if (interaction.action === 'generation_completed' && 
        interaction.data && 
        interaction.data.job_id) {
      
      const jobId = interaction.data.job_id;
      
      if (!jobOutcomes.has(jobId)) {
        jobOutcomes.set(jobId, {
          generated: true,
          saved: false,
          params: interaction.data.parameters || {},
          prompt: interaction.data.parameters?.prompt || ''
        });
      }
    }
    
    // Track saves
    if (interaction.action === 'artwork_saved' && 
        interaction.data && 
        interaction.data.job_id) {
      
      const jobId = interaction.data.job_id;
      
      if (jobOutcomes.has(jobId)) {
        const outcome = jobOutcomes.get(jobId);
        outcome.saved = true;
        jobOutcomes.set(jobId, outcome);
      }
    }
  });
  
  // Skip if no job outcomes
  if (jobOutcomes.size === 0) {
    return successPatterns;
  }
  
  // Calculate save to generate ratio
  const generates = jobOutcomes.size;
  const saves = Array.from(jobOutcomes.values()).filter(outcome => outcome.saved).length;
  successPatterns.saveToGenerateRatio = generates > 0 ? saves / generates : 0;
  
  // Calculate model success rates
  const modelCounts = {};
  const modelSaves = {};
  
  // Analyze success factors
  jobOutcomes.forEach((outcome, jobId) => {
    const params = outcome.params;
    const prompt = outcome.prompt;
    
    // Track model success
    if (params.model) {
      modelCounts[params.model] = (modelCounts[params.model] || 0) + 1;
      
      if (outcome.saved) {
        modelSaves[params.model] = (modelSaves[params.model] || 0) + 1;
      }
    }
    
    // Track prompt length factor
    const promptLength = prompt.length;
    let lengthCategory;
    
    if (promptLength < 50) {
      lengthCategory = 'short';
    } else if (promptLength < 150) {
      lengthCategory = 'medium';
    } else {
      lengthCategory = 'long';
    }
    
    successPatterns.promptFactors.length[lengthCategory].count++;
    if (outcome.saved) {
      successPatterns.promptFactors.length[lengthCategory].saves++;
    }
    
    // Track prompt complexity factor
    const commaCount = (prompt.match(/,/g) || []).length;
    let complexityCategory;
    
    if (commaCount < 3) {
      complexityCategory = 'simple';
    } else if (commaCount < 8) {
      complexityCategory = 'moderate';
    } else {
      complexityCategory = 'complex';
    }
    
    successPatterns.promptFactors.complexity[complexityCategory].count++;
    if (outcome.saved) {
      successPatterns.promptFactors.complexity[complexityCategory].saves++;
    }
    
    // Track successful prompt terms
    if (outcome.saved) {
      const terms = extractTermsFromPrompt(prompt.toLowerCase());
      
      terms.forEach(term => {
        successPatterns.successfulPromptTerms[term] = 
          (successPatterns.successfulPromptTerms[term] || 0) + 1;
      });
    }
  });
  
  // Calculate model success rates
  Object.keys(modelCounts).forEach(model => {
    const saves = modelSaves[model] || 0;
    const total = modelCounts[model];
    successPatterns.modelSuccessRates[model] = saves / total;
  });
  
  return successPatterns;
}

/**
 * Extract terms from prompt
 * @param {string} prompt Prompt text
 * @returns {Array} Array of terms
 */
function extractTermsFromPrompt(prompt) {
  if (!prompt) return [];
  
  // Split by common separators
  const parts = prompt
    .replace(/[.,;:!?(){}[\]<>]/g, ' ')
    .split(/\s+/);
  
  // Filter out common words and short words
  const stopWords = ['a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'with', 'by', 'of'];
  return parts.filter(word => 
    word.length > 3 && !stopWords.includes(word)
  );
}

/**
 * Extract style terms from prompt
 * @param {string} prompt Prompt text
 * @returns {Array} Array of style terms
 */
function extractStyleTerms(prompt) {
  if (!prompt) return [];
  
  const styleTerms = [];
  
  // Style-related keywords
  const styleKeywords = [
    'style of', 'in style', 'aesthetic', 'inspired by', 'like a', 
    'similar to', 'art by', 'painted by'
  ];
  
  // Extract style phrases
  styleKeywords.forEach(keyword => {
    const index = prompt.indexOf(keyword);
    
    if (index >= 0) {
      // Extract style phrase (up to 30 chars after keyword)
      const stylePhrase = prompt.substr(index, keyword.length + 30).split(',')[0].trim();
      styleTerms.push(stylePhrase);
    }
  });
  
  return styleTerms;
}

/**
 * Extract subject terms from prompt
 * @param {string} prompt Prompt text
 * @returns {Array} Array of subject terms
 */
function extractSubjectTerms(prompt) {
  if (!prompt) return [];
  
  // Simplified implementation - get first few terms from prompt
  const terms = extractTermsFromPrompt(prompt);
  
  // Return up to 3 terms from the beginning of the prompt
  return terms.slice(0, Math.min(3, terms.length));
}

/**
 * Extract features from image
 * This is a placeholder for actual ML-based feature extraction
 * @param {Object} image Image data
 * @param {Object} options Options for feature extraction
 * @returns {Object} Extracted features
 */
function extractFeatures(image, options) {
  // Placeholder for actual image analysis
  // In a real implementation, this would use ML models or computer vision libraries
  return {
    dominantColors: [],
    textures: [],
    composition: {
      balance: 0.5,
      symmetry: 0.5,
      focusPoint: { x: 0.5, y: 0.5 }
    },
    styleVector: []
  };
}

/**
 * Find similar prompts
 * @param {string} prompt User prompt
 * @param {Array} interactions User interactions
 * @param {Object} options Search options
 * @returns {Array} Similar prompts
 */
function findSimilarPrompts(prompt, interactions, options) {
  // Skip if prompt is empty
  if (!prompt) return [];
  
  // Extract terms from prompt
  const promptTerms = extractTermsFromPrompt(prompt.toLowerCase());
  
  // Filter to only generation requests with prompts
  const promptInteractions = interactions.filter(interaction => 
    (interaction.action === 'generation_completed') && 
    interaction.data && 
    interaction.data.parameters && 
    interaction.data.parameters.prompt
  );
  
  // Skip if no interactions with prompts
  if (promptInteractions.length === 0) return [];
  
  // Calculate similarity for each prompt
  const similarities = promptInteractions.map(interaction => {
    const interactionPrompt = interaction.data.parameters.prompt.toLowerCase();
    const interactionTerms = extractTermsFromPrompt(interactionPrompt);
    
    // Calculate term overlap
    const intersection = promptTerms.filter(term => interactionTerms.includes(term));
    const similarity = intersection.length / Math.max(promptTerms.length, interactionTerms.length);
    
    return {
      interaction,
      similarity,
      timestamp: new Date(interaction.timestamp).getTime()
    };
  });
  
  // Sort by similarity and recency (if specified in options)
  const sortedSimilarities = similarities.sort((a, b) => {
    // Consider both similarity and recency
    const recencyWeight = options.recencyWeight || 0.3;
    const maxAge = 30 * 24 * 60 * 60 * 1000; // 30 days in milliseconds
    
    // Calculate recency score (1.0 for newest, approaching 0 for oldest)
    const aAgeScore = Math.max(0, 1 - (Date.now() - a.timestamp) / maxAge);
    const bAgeScore = Math.max(0, 1 - (Date.now() - b.timestamp) / maxAge);
    
    // Combine similarity and recency
    const aScore = a.similarity * (1 - recencyWeight) + aAgeScore * recencyWeight;
    const bScore = b.similarity * (1 - recencyWeight) + bAgeScore * recencyWeight;
    
    return bScore - aScore;
  });
  
  // Return top results (limited by options.limit)
  const limit = options.limit || 5;
  return sortedSimilarities
    .slice(0, limit)
    .map(item => ({
      prompt: item.interaction.data.parameters.prompt,
      similarity: item.similarity,
      timestamp: item.timestamp,
      jobId: item.interaction.data.job_id,
      saved: item.interaction.data.saved || false
    }));
} 