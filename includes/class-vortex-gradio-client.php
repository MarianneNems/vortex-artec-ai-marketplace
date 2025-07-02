<?php
/**
 * VORTEX Gradio Client Integration
 * 
 * Connects VORTEX AI Agents to live Gradio interface
 * Handles AI model communication and response processing
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage AI_Integration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_Gradio_Client {
    
    private static $instance = null;
    private $gradio_url;
    private $session_hash;
    private $fn_index = 0;
    private $connected = false;
    
    // AI Agent endpoints mapping
    private $agent_endpoints = array(
        'huraii' => 0,
        'cloe' => 1, 
        'horace' => 2,
        'thorius' => 3,
        'archer' => 4
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
        $this->gradio_url = 'https://4416007023f09466f6.gradio.live';
        $this->session_hash = $this->generate_session_hash();
        $this->init_hooks();
        $this->test_connection();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_vortex_gradio_predict', array($this, 'ajax_predict'));
        add_action('wp_ajax_nopriv_vortex_gradio_predict', array($this, 'ajax_predict'));
        add_action('wp_ajax_vortex_gradio_status', array($this, 'ajax_connection_status'));
        
        // Enqueue frontend scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Integration with existing AI agents
        add_filter('vortex_huraii_predict', array($this, 'huraii_gradio_predict'), 10, 2);
        add_filter('vortex_cloe_analyze', array($this, 'cloe_gradio_analyze'), 10, 2);
        add_filter('vortex_horace_curate', array($this, 'horace_gradio_curate'), 10, 2);
        add_filter('vortex_thorius_chat', array($this, 'thorius_gradio_chat'), 10, 2);
        add_filter('vortex_archer_orchestrate', array($this, 'archer_gradio_orchestrate'), 10, 2);
    }
    
    /**
     * Test connection to Gradio interface
     */
    private function test_connection() {
        try {
            $response = $this->make_request('/api/predict', array(
                'data' => array('ping'),
                'fn_index' => 0
            ));
            
            if ($response && !is_wp_error($response)) {
                $this->connected = true;
                $this->log_event('Gradio connection established: ' . $this->gradio_url);
                update_option('vortex_gradio_status', 'connected');
                update_option('vortex_gradio_last_ping', current_time('mysql'));
            }
        } catch (Exception $e) {
            $this->connected = false;
            $this->log_event('Gradio connection failed: ' . $e->getMessage(), 'error');
            update_option('vortex_gradio_status', 'disconnected');
        }
    }
    
    /**
     * Make prediction request to Gradio
     */
    public function predict($data, $fn_index = 0, $agent = 'huraii', $timeout = null) {
        if (!$this->connected) {
            return new WP_Error('gradio_disconnected', 'Gradio interface not connected');
        }
        
        // Set timeout based on agent type (GPU vs CPU)
        if ($timeout === null) {
            $timeout = ($agent === 'huraii') ? 60 : 15; // GPU needs more time
        }
        
        $request_data = array(
            'data' => is_array($data) ? $data : array($data),
            'fn_index' => isset($this->agent_endpoints[$agent]) ? $this->agent_endpoints[$agent] : $fn_index,
            'session_hash' => $this->session_hash,
            'hardware_type' => ($agent === 'huraii') ? 'GPU' : 'CPU',
            'priority' => $this->get_agent_priority($agent)
        );
        
        $response = $this->make_request('/api/predict', $request_data, $timeout);
        
        if (is_wp_error($response)) {
            $this->log_event("Gradio prediction failed for {$agent}: " . $response->get_error_message(), 'error');
            return $response;
        }
        
        return $this->process_gradio_response($response, $agent);
    }
    
    /**
     * HURAII agent Gradio integration (GPU-Optimized Generative AI)
     */
    public function huraii_gradio_predict($input, $context = array()) {
        $prompt = array(
            'role' => 'huraii_generative_engine',
            'task' => 'ai_art_generation',
            'input' => $input,
            'context' => $context,
            'user_profile' => $this->get_user_context(),
            'hardware_optimization' => array(
                'gpu_acceleration' => true,
                'tensor_cores' => true,
                'mixed_precision' => true,
                'batch_processing' => true
            )
        );
        
        // GPU processing with longer timeout
        $response = $this->predict($prompt, 0, 'huraii', 60);
        
        if (!is_wp_error($response) && isset($response['data'])) {
            return array(
                'generated_art' => $response['data'][0] ?? '',
                'style_analysis' => $response['data'][1] ?? array(),
                'neural_features' => $response['data'][2] ?? array(),
                'generation_time' => $response['data'][3] ?? 0,
                'gpu_utilization' => $response['data'][4] ?? '0%',
                'confidence' => $response['data'][5] ?? 0.9,
                'timestamp' => current_time('mysql')
            );
        }
        
        return $input; // Fallback to original input
    }
    
    /**
     * CLOE agent Gradio integration
     */
    public function cloe_gradio_analyze($input, $context = array()) {
        $prompt = array(
            'role' => 'cloe_trend_analyzer',
            'task' => 'trend_analysis',
            'input' => $input,
            'market_data' => $this->get_market_context(),
            'context' => $context
        );
        
        $response = $this->predict($prompt, 1, 'cloe');
        
        if (!is_wp_error($response) && isset($response['data'])) {
            return array(
                'trends' => $response['data'][0] ?? array(),
                'market_insights' => $response['data'][1] ?? '',
                'collector_matches' => $response['data'][2] ?? array(),
                'confidence' => $response['data'][3] ?? 0.85,
                'timestamp' => current_time('mysql')
            );
        }
        
        return $input;
    }
    
    /**
     * HORACE agent Gradio integration
     */
    public function horace_gradio_curate($input, $context = array()) {
        $prompt = array(
            'role' => 'horace_content_curator',
            'task' => 'content_optimization',
            'input' => $input,
            'content_type' => $context['type'] ?? 'artwork',
            'optimization_goals' => $context['goals'] ?? array('engagement', 'discovery')
        );
        
        $response = $this->predict($prompt, 2, 'horace');
        
        if (!is_wp_error($response) && isset($response['data'])) {
            return array(
                'optimized_content' => $response['data'][0] ?? $input,
                'seo_tags' => $response['data'][1] ?? array(),
                'engagement_score' => $response['data'][2] ?? 0.7,
                'suggestions' => $response['data'][3] ?? array(),
                'timestamp' => current_time('mysql')
            );
        }
        
        return $input;
    }
    
    /**
     * THORIUS agent Gradio integration (Chatbot)
     */
    public function thorius_gradio_chat($input, $context = array()) {
        $prompt = array(
            'role' => 'thorius_platform_guide',
            'task' => 'user_assistance',
            'input' => $input,
            'user_context' => $this->get_user_context(),
            'platform_state' => $this->get_platform_context(),
            'conversation_history' => $context['history'] ?? array()
        );
        
        $response = $this->predict($prompt, 3, 'thorius');
        
        if (!is_wp_error($response) && isset($response['data'])) {
            return array(
                'response' => $response['data'][0] ?? 'Hello! How can I help you today?',
                'actions' => $response['data'][1] ?? array(),
                'follow_up' => $response['data'][2] ?? array(),
                'intent' => $response['data'][3] ?? 'general',
                'confidence' => $response['data'][4] ?? 0.9,
                'timestamp' => current_time('mysql')
            );
        }
        
        return array('response' => $input);
    }
    
    /**
     * ARCHER orchestrator Gradio integration
     */
    public function archer_gradio_orchestrate($input, $context = array()) {
        $prompt = array(
            'role' => 'archer_orchestrator',
            'task' => 'system_coordination',
            'input' => $input,
            'agents_status' => $this->get_agents_status(),
            'system_metrics' => $this->get_system_metrics(),
            'context' => $context
        );
        
        $response = $this->predict($prompt, 4, 'archer');
        
        if (!is_wp_error($response) && isset($response['data'])) {
            return array(
                'coordination_plan' => $response['data'][0] ?? array(),
                'agent_assignments' => $response['data'][1] ?? array(),
                'priority_tasks' => $response['data'][2] ?? array(),
                'system_health' => $response['data'][3] ?? 'optimal',
                'timestamp' => current_time('mysql')
            );
        }
        
        return $input;
    }
    
    /**
     * Make HTTP request to Gradio interface
     */
    private function make_request($endpoint, $data = array(), $timeout = 30) {
        $url = rtrim($this->gradio_url, '/') . $endpoint;
        
        $args = array(
            'method' => 'POST',
            'timeout' => $timeout,
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'VORTEX-AI-Marketplace/1.0',
                'X-Hardware-Type' => $data['hardware_type'] ?? 'CPU',
                'X-Agent-Priority' => $data['priority'] ?? 'medium'
            ),
            'body' => wp_json_encode($data),
            'sslverify' => false // For development, enable in production
        );
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            return new WP_Error('gradio_http_error', "HTTP {$response_code}: {$response_body}");
        }
        
        $decoded = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('gradio_json_error', 'Invalid JSON response from Gradio');
        }
        
        return $decoded;
    }
    
    /**
     * Process Gradio response
     */
    private function process_gradio_response($response, $agent) {
        // Log successful prediction
        $this->log_event("Gradio prediction successful for agent: {$agent}");
        
        // Store response for analytics
        $this->store_prediction_analytics($response, $agent);
        
        return $response;
    }
    
    /**
     * Generate session hash
     */
    private function generate_session_hash() {
        return wp_generate_password(12, false);
    }
    
    /**
     * Get user context for AI agents
     */
    private function get_user_context() {
        $user = wp_get_current_user();
        
        if (!$user->ID) {
            return array('role' => 'guest');
        }
        
        return array(
            'user_id' => $user->ID,
            'role' => $user->roles[0] ?? 'subscriber',
            'preferences' => get_user_meta($user->ID, 'vortex_ai_preferences', true) ?: array(),
            'history' => $this->get_user_interaction_history($user->ID)
        );
    }
    
    /**
     * Get market context
     */
    private function get_market_context() {
        return array(
            'trending_artworks' => $this->get_trending_artworks(),
            'active_collectors' => $this->get_active_collectors(),
            'market_trends' => $this->get_market_trends()
        );
    }
    
    /**
     * Get platform context
     */
    private function get_platform_context() {
        return array(
            'active_users' => $this->get_active_users_count(),
            'system_status' => get_option('vortex_system_status', 'unknown'),
            'current_events' => $this->get_platform_events()
        );
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'vortex-gradio-integration',
            VORTEX_PLUGIN_URL . 'assets/js/vortex-gradio-integration.js',
            array('jquery'),
            VORTEX_VERSION,
            true
        );
        
        wp_localize_script('vortex-gradio-integration', 'vortex_gradio_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_gradio_nonce'),
            'gradio_url' => $this->gradio_url,
            'connected' => $this->connected,
            'agents' => $this->agent_endpoints
        ));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook_suffix) {
        // Only load on VORTEX admin pages
        if (strpos($hook_suffix, 'vortex') === false) {
            return;
        }
        
        wp_enqueue_script(
            'vortex-gradio-admin',
            VORTEX_PLUGIN_URL . 'assets/js/vortex-gradio-integration.js',
            array('jquery'),
            VORTEX_VERSION,
            true
        );
        
        wp_localize_script('vortex-gradio-admin', 'vortex_gradio_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_gradio_nonce'),
            'gradio_url' => $this->gradio_url,
            'connected' => $this->connected,
            'agents' => $this->agent_endpoints,
            'is_admin' => true
        ));
    }
    
    /**
     * AJAX handler for predictions
     */
    public function ajax_predict() {
        check_ajax_referer('vortex_gradio_nonce', 'nonce');
        
        $agent = sanitize_text_field($_POST['agent'] ?? 'huraii');
        $input = sanitize_textarea_field($_POST['input'] ?? '');
        $context = array();
        
        if (isset($_POST['context'])) {
            $context = json_decode(stripslashes($_POST['context']), true) ?: array();
        }
        
        $result = $this->predict($input, 0, $agent);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX handler for connection status
     */
    public function ajax_connection_status() {
        check_ajax_referer('vortex_gradio_nonce', 'nonce');
        
        $this->test_connection();
        
        wp_send_json_success(array(
            'connected' => $this->connected,
            'url' => $this->gradio_url,
            'last_ping' => get_option('vortex_gradio_last_ping', 'Never'),
            'session_hash' => $this->session_hash
        ));
    }
    
    /**
     * Store prediction analytics
     */
    private function store_prediction_analytics($response, $agent) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_gradio_analytics';
        
        $wpdb->insert(
            $table_name,
            array(
                'agent' => $agent,
                'response_time' => $response['response_time'] ?? 0,
                'success' => 1,
                'user_id' => get_current_user_id(),
                'timestamp' => current_time('mysql')
            ),
            array('%s', '%f', '%d', '%d', '%s')
        );
    }
    
    /**
     * Log system events
     */
    private function log_event($message, $level = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("VORTEX Gradio Client [{$level}]: {$message}");
        }
        
        // Store in database for admin dashboard
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_system_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'component' => 'gradio_client',
                'level' => $level,
                'message' => $message,
                'timestamp' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get connection status
     */
    public function is_connected() {
        return $this->connected;
    }
    
    /**
     * Get Gradio URL
     */
    public function get_gradio_url() {
        return $this->gradio_url;
    }
    
    /**
     * Update Gradio URL
     */
    public function update_gradio_url($new_url) {
        $this->gradio_url = rtrim($new_url, '/');
        $this->test_connection();
        update_option('vortex_gradio_url', $this->gradio_url);
    }
    
    // Helper methods for context gathering
    private function get_trending_artworks() {
        // Implementation for trending artworks
        return array();
    }
    
    private function get_active_collectors() {
        // Implementation for active collectors
        return array();
    }
    
    private function get_market_trends() {
        // Implementation for market trends
        return array();
    }
    
    private function get_user_interaction_history($user_id) {
        // Implementation for user history
        return array();
    }
    
    private function get_agents_status() {
        // Implementation for agent status
        return array();
    }
    
    private function get_system_metrics() {
        // Implementation for system metrics
        return array();
    }
    
    private function get_active_users_count() {
        // Implementation for active users count
        return 0;
    }
    
    private function get_platform_events() {
        // Implementation for platform events
        return array();
    }
    
    /**
     * Get agent priority for request routing
     */
    private function get_agent_priority($agent) {
        $priorities = array(
            'huraii' => 'high',     // GPU generative AI
            'archer' => 'critical', // Master orchestrator  
            'thorius' => 'high',    // Real-time chat
            'cloe' => 'medium',     // Market analysis
            'horace' => 'medium'    // Content curation
        );
        
        return $priorities[$agent] ?? 'medium';
    }
} 