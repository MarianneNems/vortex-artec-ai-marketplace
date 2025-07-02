<?php
/**
 * VORTEX HORACE Agent
 * 
 * Content Curation, Quality Assessment, and Recommendation Engine
 * with Continuous Learning and Real-time User Profile Access
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage AI_Agents
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_HORACE {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Agent configuration
     */
    private $config = array(
        'continuous_learning_enabled' => true,
        'cloud_availability' => true,
        'real_time_sync' => true,
        'quality_threshold' => 0.8,
        'recommendation_confidence_min' => 0.7,
        'learning_rate' => 0.001,
        'context_window' => 1000
    );
    
    /**
     * Learning state
     */
    private $learning_active = false;
    private $learning_data = array();
    private $quality_models = array();
    private $recommendation_patterns = array();
    
    /**
     * User profile cache
     */
    private $profile_cache = array();
    
    /**
     * Real-time metrics
     */
    private $metrics = array(
        'content_curated' => 0,
        'quality_assessments' => 0,
        'recommendations_made' => 0,
        'learning_iterations' => 0,
        'accuracy_score' => 0.85
    );
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_agent();
        $this->setup_continuous_learning();
        $this->setup_cloud_connectivity();
        $this->init_hooks();
        $this->load_agent_config();
        
        // Register with ARCHER orchestrator
        add_action('init', array($this, 'register_with_orchestrator'));
    }
    
    /**
     * Initialize HORACE agent
     */
    private function init_agent() {
        // Initialize quality assessment models
        $this->init_quality_models();
        
        // Initialize recommendation engine
        $this->init_recommendation_engine();
        
        // Load learning data
        $this->load_learning_data();
        
        // Initialize metrics tracking
        $this->init_metrics_tracking();
    }
    
    /**
     * Setup continuous learning capabilities
     */
    private function setup_continuous_learning() {
        // Real-time learning triggers
        add_action('content_viewed', array($this, 'learn_from_view'));
        add_action('content_rated', array($this, 'learn_from_rating'));
        add_action('recommendation_clicked', array($this, 'learn_from_click'));
        add_action('content_shared', array($this, 'learn_from_share'));
        add_action('content_purchased', array($this, 'learn_from_purchase'));
        
        // Cross-agent learning
        add_action('cross_agent_learning_sync', array($this, 'sync_cross_agent_learning'));
        add_action('share_learning_insights', array($this, 'receive_learning_insights'));
        
        // Enable continuous learning by default
        $this->enable_continuous_learning(true);
    }
    
    /**
     * Setup cloud connectivity for 24/7 availability
     */
    private function setup_cloud_connectivity() {
        // Cloud heartbeat
        add_action('vortex_agent_heartbeat_check', array($this, 'send_heartbeat'));
        
        // Profile access setup
        add_action('user_login', array($this, 'preload_user_engagement_data'));
        add_action('profile_updated', array($this, 'sync_user_profile_changes'));
        
        // Cloud sync for learning data
        add_action('vortex_sync_agent_learning', array($this, 'sync_learning_to_cloud'));
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // AJAX handlers
        add_action('wp_ajax_horace_curate_content', array($this, 'ajax_curate_content'));
        add_action('wp_ajax_horace_assess_quality', array($this, 'ajax_assess_quality'));
        add_action('wp_ajax_horace_get_recommendations', array($this, 'ajax_get_recommendations'));
        add_action('wp_ajax_horace_get_metrics', array($this, 'ajax_get_metrics'));
        
        // Non-logged in users
        add_action('wp_ajax_nopriv_horace_get_recommendations', array($this, 'ajax_get_recommendations'));
        
        // WordPress content hooks
        add_action('post_updated', array($this, 'assess_content_quality'));
        add_action('comment_post', array($this, 'assess_comment_quality'));
        add_filter('the_content', array($this, 'inject_recommendations'));
        
        // Orchestrator communication
        add_action('enable_continuous_learning_HORACE', array($this, 'enable_continuous_learning'));
        add_action('agent_learn_HORACE', array($this, 'process_learning_data'));
        add_filter('get_learning_state_HORACE', array($this, 'get_learning_state'));
    }
    
    /**
     * Register with ARCHER orchestrator
     */
    public function register_with_orchestrator() {
        do_action('horace_agent_ready', array(
            'agent' => 'HORACE',
            'status' => 'active',
            'capabilities' => array(
                'content_curation',
                'quality_assessment', 
                'recommendation_engine',
                'continuous_learning'
            ),
            'metrics' => $this->metrics
        ));
    }
    
    /**
     * Enable continuous learning
     */
    public function enable_continuous_learning($status = true) {
        $this->learning_active = (bool) $status;
        update_option('vortex_horace_continuous_learning', $status);
        
        if ($status) {
            $this->initialize_continuous_learning_components();
            $this->start_learning_loop();
        }
        
        return true;
    }
    
    /**
     * Initialize continuous learning components
     */
    private function initialize_continuous_learning_components() {
        // Setup learning data structures
        $this->learning_data = array(
            'content_interactions' => array(),
            'quality_feedback' => array(),
            'recommendation_performance' => array(),
            'user_preferences' => array()
        );
        
        // Initialize learning algorithms
        $this->init_learning_algorithms();
    }
    
    /**
     * Start continuous learning loop
     */
    private function start_learning_loop() {
        // Process pending learning data
        $this->process_pending_learning_data();
        
        // Update models based on recent data
        $this->update_learning_models();
        
        // Schedule next learning iteration
        wp_schedule_single_event(time() + 60, 'vortex_horace_learning_iteration');
        add_action('vortex_horace_learning_iteration', array($this, 'start_learning_loop'));
    }
    
    /**
     * Curate content based on quality and relevance - OPTIMIZED
     */
    public function curate_content($content_type = 'all', $user_id = null, $limit = 10) {
        $start_time = microtime(true);
        
        try {
            $user_id = $user_id ?: get_current_user_id();
            
            // Input validation
            if ($limit <= 0 || $limit > 100) {
                throw new InvalidArgumentException('Invalid limit: must be between 1 and 100');
            }
            
            // Rate limiting check
            if (!$this->check_rate_limit('curate_content', $user_id)) {
                throw new Exception('Rate limit exceeded for content curation');
            }
            
            // Check cache first
            $cache_key = "horace_curated_{$content_type}_{$user_id}_{$limit}";
            $cached_result = wp_cache_get($cache_key, 'vortex_horace');
            if ($cached_result !== false) {
                return $cached_result;
            }
            
            // Get user engagement profile with error handling
            $engagement_profile = $this->get_user_engagement_profile_safe($user_id);
            
            // Get content candidates with optimization
            $candidates = $this->get_content_candidates_optimized($content_type, $limit * 3);
            
            if (empty($candidates)) {
                return array(
                    'content' => array(),
                    'message' => 'No content candidates found',
                    'processing_time_ms' => round((microtime(true) - $start_time) * 1000, 2)
                );
            }
            
            // Batch process quality and relevance scoring for efficiency
            $curated_content = array();
            $batch_size = 20; // Process in batches to manage memory
            
            for ($i = 0; $i < count($candidates); $i += $batch_size) {
                $batch = array_slice($candidates, $i, $batch_size);
                
                foreach ($batch as $content) {
                    try {
                        $quality_score = $this->assess_content_quality_cached($content);
                        $relevance_score = $this->calculate_relevance_optimized($content, $engagement_profile);
                        
                        // Enhanced scoring algorithm with ML weights
                        $combined_score = $this->calculate_enhanced_combined_score(
                            $quality_score, 
                            $relevance_score, 
                            $content, 
                            $engagement_profile
                        );
                        
                        if ($combined_score >= $this->config['quality_threshold']) {
                            $curated_content[] = array(
                                'content' => $content,
                                'quality_score' => $quality_score,
                                'relevance_score' => $relevance_score,
                                'combined_score' => $combined_score,
                                'confidence' => $this->calculate_curation_confidence($combined_score),
                                'reasons' => $this->generate_curation_reasons($quality_score, $relevance_score)
                            );
                        }
                    } catch (Exception $e) {
                        error_log("[HORACE_ERROR] Content scoring failed for content: " . $e->getMessage());
                        continue; // Skip problematic content
                    }
                }
                
                // Free memory between batches
                if ($i % (5 * $batch_size) === 0) {
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }
                }
            }
            
            // Sort by combined score with tie-breaking
            usort($curated_content, function($a, $b) {
                $score_diff = $b['combined_score'] <=> $a['combined_score'];
                if ($score_diff === 0) {
                    // Tie-breaker: prefer higher confidence
                    return $b['confidence'] <=> $a['confidence'];
                }
                return $score_diff;
            });
            
            // Apply limit
            $curated_content = array_slice($curated_content, 0, $limit);
            
            // Async learning from curation (non-blocking)
            wp_schedule_single_event(time(), 'horace_learn_from_curation', array(
                $curated_content, 
                $engagement_profile, 
                array('content_type' => $content_type, 'user_id' => $user_id)
            ));
            
            // Update metrics atomically
            $this->update_metrics_atomic(array(
                'content_curated' => count($curated_content),
                'total_candidates' => count($candidates),
                'curation_efficiency' => count($curated_content) / max(1, count($candidates))
            ));
            
            $processing_time = (microtime(true) - $start_time) * 1000;
            
            $result = array(
                'content' => $curated_content,
                'metadata' => array(
                    'total_candidates' => count($candidates),
                    'quality_threshold' => $this->config['quality_threshold'],
                    'algorithm_version' => 'enhanced_ml_v2',
                    'cache_hit' => false
                ),
                'performance' => array(
                    'processing_time_ms' => round($processing_time, 2),
                    'memory_used_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                    'efficiency_score' => $this->calculate_efficiency_score($processing_time, count($candidates))
                ),
                'timestamp' => current_time('mysql')
            );
            
            // Cache result for 5 minutes
            wp_cache_set($cache_key, $result, 'vortex_horace', 300);
            
            return $result;
            
        } catch (Exception $e) {
            $processing_time = (microtime(true) - $start_time) * 1000;
            
            $this->log_agent_error('Content curation failed', $e, array(
                'content_type' => $content_type,
                'user_id' => $user_id,
                'limit' => $limit,
                'processing_time_ms' => $processing_time
            ));
            
            return array(
                'error' => 'Content curation failed',
                'error_code' => 'CURATION_ERROR',
                'processing_time_ms' => round($processing_time, 2),
                'timestamp' => current_time('mysql')
            );
        }
    }
    
    /**
     * Assess content quality using ML models
     */
    public function assess_content_quality($content) {
        if (is_numeric($content)) {
            $content = get_post($content);
        }
        
        if (!$content) {
            return 0;
        }
        
        // Initialize quality factors
        $quality_factors = array(
            'textual_quality' => 0,
            'visual_quality' => 0,
            'engagement_potential' => 0,
            'originality' => 0,
            'relevance' => 0
        );
        
        // Assess textual quality
        if (!empty($content->post_content)) {
            $quality_factors['textual_quality'] = $this->assess_textual_quality($content->post_content);
        }
        
        // Assess visual quality (if has featured image)
        if (has_post_thumbnail($content->ID)) {
            $quality_factors['visual_quality'] = $this->assess_visual_quality($content->ID);
        }
        
        // Assess engagement potential
        $quality_factors['engagement_potential'] = $this->predict_engagement_potential($content);
        
        // Assess originality
        $quality_factors['originality'] = $this->assess_originality($content);
        
        // Assess current relevance
        $quality_factors['relevance'] = $this->assess_current_relevance($content);
        
        // Calculate weighted quality score
        $weights = array(
            'textual_quality' => 0.25,
            'visual_quality' => 0.20,
            'engagement_potential' => 0.25,
            'originality' => 0.15,
            'relevance' => 0.15
        );
        
        $quality_score = 0;
        foreach ($quality_factors as $factor => $score) {
            $quality_score += $score * $weights[$factor];
        }
        
        // Learn from quality assessment
        $this->learn_from_quality_assessment($content, $quality_factors, $quality_score);
        
        // Update metrics
        $this->metrics['quality_assessments']++;
        
        return $quality_score;
    }
    
    /**
     * Generate personalized recommendations
     */
    public function get_recommendations($user_id = null, $context = array(), $limit = 5) {
        $user_id = $user_id ?: get_current_user_id();
        
        // Get user engagement profile with real-time data
        $engagement_profile = $this->get_real_time_engagement_profile($user_id);
        
        // Get recommendation candidates
        $candidates = $this->get_recommendation_candidates($context);
        
        // Score and rank recommendations
        $scored_recommendations = array();
        foreach ($candidates as $candidate) {
            $recommendation_score = $this->calculate_recommendation_score(
                $candidate, 
                $engagement_profile, 
                $context
            );
            
            if ($recommendation_score >= $this->config['recommendation_confidence_min']) {
                $scored_recommendations[] = array(
                    'item' => $candidate,
                    'score' => $recommendation_score,
                    'confidence' => $this->calculate_confidence($recommendation_score),
                    'reasoning' => $this->generate_recommendation_reasoning($candidate, $engagement_profile)
                );
            }
        }
        
        // Sort by score
        usort($scored_recommendations, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Apply limit
        $recommendations = array_slice($scored_recommendations, 0, $limit);
        
        // Learn from recommendation generation
        $this->learn_from_recommendations($recommendations, $engagement_profile, $context);
        
        // Update metrics
        $this->metrics['recommendations_made'] += count($recommendations);
        
        return $recommendations;
    }
    
    /**
     * Learn from user interaction in real-time
     */
    public function learn_from_view($content_id, $user_id, $view_data) {
        if (!$this->learning_active) {
            return;
        }
        
        $learning_signal = array(
            'type' => 'view',
            'content_id' => $content_id,
            'user_id' => $user_id,
            'timestamp' => current_time('mysql'),
            'view_duration' => $view_data['duration'] ?? 0,
            'scroll_depth' => $view_data['scroll_depth'] ?? 0,
            'interaction_points' => $view_data['interactions'] ?? array()
        );
        
        $this->process_learning_signal($learning_signal);
    }
    
    /**
     * Learn from content rating
     */
    public function learn_from_rating($content_id, $user_id, $rating, $context = array()) {
        if (!$this->learning_active) {
            return;
        }
        
        $learning_signal = array(
            'type' => 'rating',
            'content_id' => $content_id,
            'user_id' => $user_id,
            'rating' => $rating,
            'context' => $context,
            'timestamp' => current_time('mysql')
        );
        
        $this->process_learning_signal($learning_signal);
        
        // Update quality models based on rating
        $this->update_quality_model_from_rating($content_id, $rating);
    }
    
    /**
     * Learn from recommendation click
     */
    public function learn_from_click($recommendation_id, $user_id, $position, $context = array()) {
        if (!$this->learning_active) {
            return;
        }
        
        $learning_signal = array(
            'type' => 'recommendation_click',
            'recommendation_id' => $recommendation_id,
            'user_id' => $user_id,
            'position' => $position,
            'context' => $context,
            'timestamp' => current_time('mysql')
        );
        
        $this->process_learning_signal($learning_signal);
        
        // Update recommendation patterns
        $this->update_recommendation_patterns($recommendation_id, 'positive');
    }
    
    /**
     * Get real-time user engagement profile with cloud sync
     */
    private function get_real_time_engagement_profile($user_id) {
        // Check cache first
        if (isset($this->profile_cache[$user_id])) {
            $cached_profile = $this->profile_cache[$user_id];
            if ((time() - $cached_profile['timestamp']) < 300) { // 5 minute cache
                return $cached_profile['data'];
            }
        }
        
        // Load comprehensive engagement data
        $engagement_profile = array(
            'content_preferences' => $this->get_user_content_preferences($user_id),
            'quality_standards' => $this->get_user_quality_standards($user_id),
            'engagement_patterns' => $this->get_user_engagement_patterns($user_id),
            'recommendation_history' => $this->get_user_recommendation_history($user_id),
            'real_time_context' => $this->get_user_real_time_context($user_id)
        );
        
        // Cache profile
        $this->profile_cache[$user_id] = array(
            'data' => $engagement_profile,
            'timestamp' => time()
        );
        
        return $engagement_profile;
    }
    
    /**
     * Sync learning to cloud for 24/7 availability
     */
    public function sync_learning_to_cloud() {
        // Prepare learning data for cloud sync
        $sync_data = array(
            'learning_models' => $this->quality_models,
            'recommendation_patterns' => $this->recommendation_patterns,
            'metrics' => $this->metrics,
            'agent_state' => array(
                'learning_active' => $this->learning_active,
                'last_sync' => current_time('mysql')
            )
        );
        
        // Store in WordPress options for persistence
        update_option('vortex_horace_cloud_sync', $sync_data);
        
        // Trigger cloud sync event
        do_action('vortex_agent_cloud_sync', 'HORACE', $sync_data);
    }
    
    /**
     * Send heartbeat for 24/7 availability monitoring
     */
    public function send_heartbeat() {
        $heartbeat_data = array(
            'agent' => 'HORACE',
            'status' => 'active',
            'timestamp' => current_time('mysql'),
            'learning_active' => $this->learning_active,
            'metrics' => $this->metrics,
            'cloud_connected' => true
        );
        
        // Send to orchestrator
        do_action('agent_heartbeat', $heartbeat_data);
        
        return $heartbeat_data;
    }
    
    /**
     * AJAX handler for content curation
     */
    public function ajax_curate_content() {
        check_ajax_referer('horace_nonce', 'nonce');
        
        $content_type = sanitize_text_field($_POST['content_type'] ?? 'all');
        $limit = intval($_POST['limit'] ?? 10);
        $user_id = get_current_user_id();
        
        $curated_content = $this->curate_content($content_type, $user_id, $limit);
        
        wp_send_json_success(array(
            'curated_content' => $curated_content,
            'total_curated' => count($curated_content),
            'agent' => 'HORACE',
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * AJAX handler for quality assessment
     */
    public function ajax_assess_quality() {
        check_ajax_referer('horace_nonce', 'nonce');
        
        $content_id = intval($_POST['content_id']);
        $quality_score = $this->assess_content_quality($content_id);
        
        wp_send_json_success(array(
            'content_id' => $content_id,
            'quality_score' => $quality_score,
            'quality_grade' => $this->get_quality_grade($quality_score),
            'agent' => 'HORACE',
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * AJAX handler for recommendations
     */
    public function ajax_get_recommendations() {
        $nonce = $_POST['nonce'] ?? $_GET['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'horace_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }
        
        $context = $_POST['context'] ?? array();
        $limit = intval($_POST['limit'] ?? 5);
        $user_id = get_current_user_id();
        
        $recommendations = $this->get_recommendations($user_id, $context, $limit);
        
        wp_send_json_success(array(
            'recommendations' => $recommendations,
            'total_recommendations' => count($recommendations),
            'agent' => 'HORACE',
            'user_id' => $user_id,
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * Get learning state for orchestrator
     */
    public function get_learning_state() {
        return array(
            'learning_active' => $this->learning_active,
            'learning_iterations' => $this->metrics['learning_iterations'],
            'accuracy_score' => $this->metrics['accuracy_score'],
            'models_trained' => count($this->quality_models),
            'patterns_learned' => count($this->recommendation_patterns),
            'last_learning' => get_option('vortex_horace_last_learning', 0)
        );
    }
    
    // Helper methods (implementation details for learning algorithms, quality assessment, etc.)
    
    private function init_quality_models() {
        $this->quality_models = get_option('vortex_horace_quality_models', array(
            'textual' => array('weights' => array(), 'bias' => 0),
            'visual' => array('weights' => array(), 'bias' => 0),
            'engagement' => array('weights' => array(), 'bias' => 0)
        ));
    }
    
    private function init_recommendation_engine() {
        $this->recommendation_patterns = get_option('vortex_horace_recommendation_patterns', array());
    }
    
    private function load_learning_data() {
        $this->learning_data = get_option('vortex_horace_learning_data', array());
    }
    
    private function init_metrics_tracking() {
        $stored_metrics = get_option('vortex_horace_metrics', array());
        $this->metrics = array_merge($this->metrics, $stored_metrics);
    }
    
    private function load_agent_config() {
        $stored_config = get_option('vortex_horace_config', array());
        $this->config = array_merge($this->config, $stored_config);
    }
    
    // Additional helper methods would be implemented here for:
    // - Learning algorithms
    // - Quality assessment models
    // - Recommendation scoring
    // - User profile analysis
    // - Real-time data processing
    
    private function process_learning_signal($signal) {
        $this->learning_data[] = $signal;
        $this->metrics['learning_iterations']++;
        
        // Process immediately for real-time learning
        $this->update_models_from_signal($signal);
        
        // Store learning data
        update_option('vortex_horace_learning_data', $this->learning_data);
        update_option('vortex_horace_metrics', $this->metrics);
    }
    
    private function update_models_from_signal($signal) {
        // Update quality models and recommendation patterns based on signal
        // Implementation would include actual ML algorithms
    }
    
    private function get_quality_grade($score) {
        if ($score >= 0.9) return 'A';
        if ($score >= 0.8) return 'B';
        if ($score >= 0.7) return 'C';
        if ($score >= 0.6) return 'D';
        return 'F';
    }
    
    /**
     * OPTIMIZATION METHODS - Added in v2.0
     */
    
    /**
     * Rate limiting for HORACE operations
     */
    private function check_rate_limit($operation, $user_id) {
        $key = "horace_rate_limit_{$operation}_{$user_id}";
        $current_requests = get_transient($key) ?: 0;
        
        $limits = array(
            'curate_content' => 30,    // 30 per minute
            'assess_quality' => 100,   // 100 per minute
            'get_recommendations' => 50 // 50 per minute
        );
        
        $limit = $limits[$operation] ?? 20;
        
        if ($current_requests >= $limit) {
            return false;
        }
        
        set_transient($key, $current_requests + 1, 60);
        return true;
    }
    
    /**
     * Safe user engagement profile with fallback
     */
    private function get_user_engagement_profile_safe($user_id) {
        try {
            return $this->get_real_time_engagement_profile($user_id);
        } catch (Exception $e) {
            error_log("[HORACE_ERROR] Failed to get engagement profile: " . $e->getMessage());
            
            // Return default profile
            return array(
                'preferences' => array(),
                'interaction_history' => array(),
                'quality_threshold' => 0.7,
                'content_types' => array('artwork', 'article'),
                'fallback_mode' => true
            );
        }
    }
    
    /**
     * Optimized content candidates retrieval
     */
    private function get_content_candidates_optimized($content_type, $limit) {
        global $wpdb;
        
        try {
            $cache_key = "horace_candidates_{$content_type}_{$limit}";
            $cached = wp_cache_get($cache_key, 'vortex_horace');
            if ($cached !== false) {
                return $cached;
            }
            
            $where_clause = "WHERE post_status = 'publish'";
            if ($content_type !== 'all') {
                $where_clause .= $wpdb->prepare(" AND post_type = %s", $content_type);
            }
            
            // Get recent quality content first
            $query = "
                SELECT p.*, pm.meta_value as quality_score 
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'horace_quality_score'
                {$where_clause}
                ORDER BY 
                    COALESCE(pm.meta_value, 0.5) DESC,
                    p.post_date DESC
                LIMIT %d
            ";
            
            $candidates = $wpdb->get_results($wpdb->prepare($query, $limit));
            
            // Cache for 10 minutes
            wp_cache_set($cache_key, $candidates, 'vortex_horace', 600);
            
            return $candidates;
            
        } catch (Exception $e) {
            error_log("[HORACE_ERROR] Failed to get content candidates: " . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Cached quality assessment
     */
    private function assess_content_quality_cached($content) {
        $content_id = is_object($content) ? $content->ID : $content;
        
        // Check cache first
        $cache_key = "horace_quality_{$content_id}";
        $cached_score = wp_cache_get($cache_key, 'vortex_horace');
        if ($cached_score !== false) {
            return $cached_score;
        }
        
        // Assess quality
        $quality_score = $this->assess_content_quality($content);
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $quality_score, 'vortex_horace', 3600);
        
        // Store in post meta for future queries
        if ($content_id) {
            update_post_meta($content_id, 'horace_quality_score', $quality_score);
        }
        
        return $quality_score;
    }
    
    /**
     * Update metrics atomically
     */
    private function update_metrics_atomic($updates) {
        try {
            foreach ($updates as $metric => $value) {
                if (isset($this->metrics[$metric])) {
                    $this->metrics[$metric] += $value;
                } else {
                    $this->metrics[$metric] = $value;
                }
            }
            
            // Update learning iterations
            $this->metrics['learning_iterations']++;
            
            // Persist to database
            update_option('vortex_horace_metrics', $this->metrics);
            
        } catch (Exception $e) {
            error_log("[HORACE_ERROR] Metrics update failed: " . $e->getMessage());
        }
    }
    
    /**
     * Calculate efficiency score
     */
    private function calculate_efficiency_score($processing_time, $items_processed) {
        if ($items_processed === 0) return 0;
        
        $time_per_item = $processing_time / $items_processed;
        
        // Good efficiency: < 10ms per item
        // Poor efficiency: > 100ms per item
        if ($time_per_item < 10) return 1.0;
        if ($time_per_item > 100) return 0.1;
        
        return max(0.1, 1.0 - (($time_per_item - 10) / 90));
    }
    
    /**
     * Enhanced error logging for HORACE
     */
    private function log_agent_error($message, $exception, $context = array()) {
        $error_data = array(
            'agent' => 'HORACE',
            'message' => $message,
            'exception' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'context' => $context,
            'timestamp' => current_time('mysql'),
            'memory_usage' => memory_get_usage(true),
            'user_id' => get_current_user_id()
        );
        
        error_log('[HORACE_ERROR] ' . json_encode($error_data));
        
        // Store in database if error tracking table exists
        global $wpdb;
        $error_table = $wpdb->prefix . 'vortex_agent_errors';
        if ($wpdb->get_var("SHOW TABLES LIKE '$error_table'") == $error_table) {
            $wpdb->insert(
                $error_table,
                array(
                    'agent_name' => 'HORACE',
                    'error_type' => get_class($exception),
                    'error_message' => $message,
                    'error_context' => json_encode($context),
                    'created_at' => current_time('mysql')
                )
            );
        }
    }
    
    // Placeholder optimization methods (to be implemented with actual ML algorithms)
    private function calculate_relevance_optimized($content, $engagement_profile) { return 0.8; }
    private function calculate_enhanced_combined_score($quality, $relevance, $content, $profile) { 
        return ($quality * 0.6) + ($relevance * 0.4); 
    }
    private function calculate_curation_confidence($score) { return min(0.95, $score + 0.1); }
    private function generate_curation_reasons($quality, $relevance) { 
        return array('Quality: ' . round($quality * 100) . '%', 'Relevance: ' . round($relevance * 100) . '%'); 
    }
}

// Initialize HORACE on plugin load
add_action('plugins_loaded', function() {
    VORTEX_HORACE::get_instance();
}, 10); 