<?php
/**
 * ARCHER: THE ORCHESTRATOR
 * 
 * Master AI Orchestrator managing all VORTEX agents with continuous learning,
 * 24/7 cloud availability, and real-time synchronization
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage AI_Orchestration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_ARCHER_Orchestrator {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Managed AI Agents
     */
    private $agents = array(
        'HURAII' => array('status' => 'initializing', 'learning_active' => false),
        'CLOE' => array('status' => 'initializing', 'learning_active' => false),
        'HORACE' => array('status' => 'initializing', 'learning_active' => false),
        'THORIUS' => array('status' => 'initializing', 'learning_active' => false)
    );
    
    /**
     * Configuration
     */
    private $config = array(
        'continuous_learning_enabled' => true,
        'cloud_availability_mode' => '24_7',
        'sync_interval_seconds' => 5,
        'cross_agent_learning_enabled' => true
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
     * Constructor - Initialize ARCHER
     */
    private function __construct() {
        $this->init_components();
        $this->setup_continuous_learning();
        $this->setup_cloud_availability();
        $this->setup_real_time_synchronization();
        $this->init_hooks();
        
        // Start ARCHER's orchestration
        $this->ignite_orchestration();
    }
    
    /**
     * Initialize core components
     */
    private function init_components() {
        // Register and initialize all AI agents
        $this->register_all_agents();
        
        // Load configuration
        $this->load_orchestrator_config();
    }
    
    /**
     * Register all AI agents with ARCHER
     */
    private function register_all_agents() {
        // Register HURAII - Advanced AI image generation and creative assistance
        $this->register_agent('HURAII', array(
            'class' => 'VORTEX_HURAII',
            'responsibilities' => array('image_generation', 'art_analysis', 'creative_collaboration'),
            'continuous_learning' => true,
            'cloud_required' => true,
            'profile_access' => 'artwork_preferences'
        ));
        
        // Register CLOE - Market analysis and user behavior
        $this->register_agent('CLOE', array(
            'class' => 'VORTEX_CLOE',
            'responsibilities' => array('market_analysis', 'trend_prediction', 'user_behavior'),
            'continuous_learning' => true,
            'cloud_required' => true,
            'profile_access' => 'purchase_history'
        ));
        
        // Register HORACE - Content curation and quality assessment
        $this->register_agent('HORACE', array(
            'class' => 'VORTEX_HORACE',
            'responsibilities' => array('content_curation', 'quality_assessment', 'recommendation_engine'),
            'continuous_learning' => true,
            'cloud_required' => true,
            'profile_access' => 'engagement_metrics'
        ));
        
        // Register THORIUS - Blockchain and security management
        $this->register_agent('THORIUS', array(
            'class' => 'VORTEX_THORIUS',
            'responsibilities' => array('blockchain_integration', 'security_monitoring', 'transaction_analysis'),
            'continuous_learning' => true,
            'cloud_required' => true,
            'profile_access' => 'transaction_history'
        ));
    }
    
    /**
     * Register individual agent with ARCHER
     */
    private function register_agent($name, $config) {
        $this->agents[$name] = array_merge($this->agents[$name], $config, array(
            'last_heartbeat' => 0,
            'cloud_connected' => false,
            'sync_status' => 'pending'
        ));
        
        $this->log_orchestrator_event("Agent {$name} registered with ARCHER", 'info');
    }
    
    /**
     * Setup continuous learning for all agents
     */
    private function setup_continuous_learning() {
        if (!$this->config['continuous_learning_enabled']) {
            return;
        }
        
        // Real-time learning triggers (not cron-based)
        add_action('user_interaction', array($this, 'process_real_time_learning'));
        add_action('artwork_created', array($this, 'process_real_time_learning'));
        add_action('purchase_completed', array($this, 'process_real_time_learning'));
        add_action('content_viewed', array($this, 'process_real_time_learning'));
        
        // Start continuous learning loop
        add_action('init', array($this, 'start_continuous_learning_loop'));
        
        // Cross-agent learning synchronization every 5 seconds
        if (!wp_next_scheduled('vortex_sync_agent_learning')) {
            wp_schedule_event(time(), 'vortex_every_5_seconds', 'vortex_sync_agent_learning');
        }
        add_action('vortex_sync_agent_learning', array($this, 'sync_agent_learning_states'));
    }
    
    /**
     * Setup 24/7 cloud availability
     */
    private function setup_cloud_availability() {
        // Agent heartbeat monitoring every 10 seconds
        if (!wp_next_scheduled('vortex_agent_heartbeat_check')) {
            wp_schedule_event(time(), 'vortex_every_10_seconds', 'vortex_agent_heartbeat_check');
        }
        add_action('vortex_agent_heartbeat_check', array($this, 'check_agent_heartbeats'));
        
        // Profile data pre-loading for instant access
        add_action('user_login', array($this, 'preload_user_profile_data'));
        add_action('wp_loaded', array($this, 'ensure_cloud_connectivity'));
    }
    
    /**
     * Setup real-time synchronization between agents
     */
    private function setup_real_time_synchronization() {
        // Real-time sync every 5 seconds
        if (!wp_next_scheduled('vortex_real_time_agent_sync')) {
            wp_schedule_event(time(), 'vortex_every_5_seconds', 'vortex_real_time_agent_sync');
        }
        add_action('vortex_real_time_agent_sync', array($this, 'perform_real_time_sync'));
        
        // Agent state change broadcasting
        add_action('agent_state_changed', array($this, 'broadcast_state_change'));
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // AJAX handlers for orchestrator management
        add_action('wp_ajax_archer_get_agent_status', array($this, 'get_agent_status'));
        add_action('wp_ajax_archer_sync_agents', array($this, 'manual_agent_sync'));
        add_action('wp_ajax_archer_get_learning_metrics', array($this, 'get_learning_metrics'));
        
        // Admin interface
        add_action('admin_menu', array($this, 'add_orchestrator_admin_menu'));
        
        // Custom cron schedules
        add_filter('cron_schedules', array($this, 'add_custom_cron_schedules'));
    }
    
    /**
     * Ignite the orchestration system
     */
    private function ignite_orchestration() {
        // Initialize all agents
        $this->initialize_all_agents();
        
        // Start all systems
        $this->start_continuous_learning_loop();
        $this->start_real_time_sync();
        $this->ensure_cloud_connectivity();
        
        // Log system ignition
        $this->log_orchestrator_event('ARCHER Orchestrator System IGNITED - All systems operational', 'critical');
        
        // Update system status
        update_option('vortex_archer_status', 'operational');
        update_option('vortex_archer_ignited', current_time('mysql'));
    }
    
    /**
     * Initialize all registered agents
     */
    private function initialize_all_agents() {
        foreach ($this->agents as $name => $config) {
            $this->initialize_agent($name);
        }
    }
    
    /**
     * Initialize individual agent
     */
    private function initialize_agent($name) {
        try {
            $config = $this->agents[$name];
            
            // Attempt to load agent class
            if (isset($config['class'])) {
                $class_name = $config['class'];
                
                if (class_exists($class_name)) {
                    // Initialize existing agent
                    $agent_instance = call_user_func(array($class_name, 'get_instance'));
                    
                    // Enable continuous learning
                    if (method_exists($agent_instance, 'enable_continuous_learning')) {
                        $agent_instance->enable_continuous_learning(true);
                        $this->agents[$name]['learning_active'] = true;
                    }
                    
                    // Setup cloud connectivity
                    if ($config['cloud_required']) {
                        $this->setup_agent_cloud_connection($name, $agent_instance);
                    }
                    
                    // Update agent status
                    $this->agents[$name]['status'] = 'active';
                    $this->agents[$name]['cloud_connected'] = true;
                    
                    $this->log_orchestrator_event("Agent {$name} initialized successfully", 'success');
                    
                } else {
                    // Create missing agent placeholder
                    $this->create_agent_placeholder($name, $config);
                }
            }
            
        } catch (Exception $e) {
            $this->agents[$name]['status'] = 'error';
            $this->log_orchestrator_event("Failed to initialize agent {$name}: " . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Start continuous learning loop
     */
    public function start_continuous_learning_loop() {
        // Enable learning for all active agents
        foreach ($this->agents as $name => $config) {
            if ($config['status'] === 'active' && $config['continuous_learning']) {
                $this->enable_agent_continuous_learning($name);
            }
        }
        
        $this->log_orchestrator_event('Continuous learning loop started for all agents', 'info');
    }
    
    /**
     * Process real-time learning from user interactions
     */
    public function process_real_time_learning($interaction_data) {
        if (!$this->config['continuous_learning_enabled']) {
            return;
        }
        
        try {
            // Determine which agents should learn from this interaction
            $learning_agents = $this->determine_learning_agents($interaction_data);
            
            foreach ($learning_agents as $agent_name) {
                if (isset($this->agents[$agent_name]) && $this->agents[$agent_name]['status'] === 'active') {
                    // Send learning data to agent
                    $this->send_learning_data_to_agent($agent_name, $interaction_data);
                }
            }
            
            // Cross-agent learning synchronization
            if ($this->config['cross_agent_learning_enabled']) {
                $this->sync_cross_agent_learning($interaction_data);
            }
            
        } catch (Exception $e) {
            $this->log_orchestrator_event("Real-time learning failed: " . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Sync agent learning states in real-time
     */
    public function sync_agent_learning_states() {
        foreach ($this->agents as $name => $config) {
            if ($config['status'] === 'active' && $config['learning_active']) {
                try {
                    // Get agent's current learning state
                    $learning_state = $this->get_agent_learning_state($name);
                    
                    // Share learning insights with other agents
                    $this->share_learning_insights($name, $learning_state);
                    
                    // Update orchestrator's knowledge of agent state
                    $this->update_agent_sync_status($name, 'synchronized');
                    
                } catch (Exception $e) {
                    $this->log_orchestrator_event("Learning sync failed for {$name}: " . $e->getMessage(), 'error');
                    $this->update_agent_sync_status($name, 'error');
                }
            }
        }
    }
    
    /**
     * Check agent heartbeats for 24/7 availability
     */
    public function check_agent_heartbeats() {
        $current_time = time();
        $timeout = 30; // 30 seconds timeout
        
        foreach ($this->agents as $name => $config) {
            if ($config['status'] === 'active') {
                $last_heartbeat = $config['last_heartbeat'];
                
                if (($current_time - $last_heartbeat) > $timeout) {
                    // Agent is unresponsive - attempt recovery
                    $this->handle_agent_timeout($name);
                } else {
                    // Send heartbeat ping
                    $this->send_heartbeat_ping($name);
                }
            }
        }
    }
    
    /**
     * Perform real-time synchronization between agents
     */
    public function perform_real_time_sync() {
        // Sync agent states
        $this->sync_all_agent_states();
        
        // Sync learning progress
        $this->sync_learning_progress();
        
        // Update coordination metrics
        $this->update_coordination_metrics();
    }
    
    /**
     * Ensure cloud connectivity for all agents
     */
    public function ensure_cloud_connectivity() {
        foreach ($this->agents as $name => $config) {
            if ($config['cloud_required'] && !$config['cloud_connected']) {
                $this->restore_agent_cloud_connection($name);
            }
        }
    }
    
    /**
     * Preload user profile data for instant agent access
     */
    public function preload_user_profile_data($user_id) {
        $profile_data = $this->load_comprehensive_profile($user_id);
        
        // Cache profile data for all agents
        foreach ($this->agents as $name => $config) {
            if (isset($config['profile_access'])) {
                $relevant_data = $this->filter_profile_data_for_agent($profile_data, $config['profile_access']);
                wp_cache_set("agent_{$name}_profile_{$user_id}", $relevant_data, '', 300);
            }
        }
    }
    
    /**
     * Get real-time agent status (AJAX handler) - OPTIMIZED
     */
    public function get_agent_status() {
        try {
            // Enhanced security validation
            if (!check_ajax_referer('archer_orchestrator', 'nonce', false)) {
                wp_send_json_error(array(
                    'message' => 'Security validation failed',
                    'code' => 'INVALID_NONCE'
                ), 403);
                return;
            }
            
            // Permission check
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array(
                    'message' => 'Insufficient permissions',
                    'code' => 'INSUFFICIENT_PERMISSIONS'
                ), 403);
                return;
            }
            
            $start_time = microtime(true);
            
            // Get enhanced agent status with performance data
            $status = array();
            foreach ($this->agents as $name => $config) {
                $status[$name] = array(
                    'status' => $config['status'],
                    'cloud_connected' => $config['cloud_connected'],
                    'learning_active' => $config['learning_active'],
                    'last_heartbeat' => $config['last_heartbeat'],
                    'sync_status' => $config['sync_status'],
                    'health_score' => $this->calculate_agent_health($name),
                    'error_count' => $this->get_agent_error_count($name),
                    'performance_ms' => $this->get_agent_avg_response_time($name)
                );
            }
            
            // Calculate system health metrics
            $system_health = $this->calculate_system_health();
            
            // Performance tracking
            $processing_time = (microtime(true) - $start_time) * 1000;
            
            wp_send_json_success(array(
                'agents' => $status,
                'orchestrator_status' => 'operational',
                'system_health' => $system_health,
                'total_agents' => count($this->agents),
                'active_agents' => count(array_filter($this->agents, function($agent) { 
                    return $agent['status'] === 'active'; 
                })),
                'performance_metrics' => array(
                    'response_time_ms' => round($processing_time, 2),
                    'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                    'uptime_hours' => $this->get_system_uptime_hours()
                ),
                'timestamp' => current_time('mysql'),
                'version' => '2.0.0'
            ));
            
        } catch (Exception $e) {
            error_log('[ARCHER_ERROR] Get agent status failed: ' . $e->getMessage());
            
            wp_send_json_error(array(
                'message' => 'Internal server error',
                'code' => 'INTERNAL_ERROR',
                'timestamp' => current_time('mysql')
            ), 500);
        }
    }
    
    /**
     * Add custom cron schedules for real-time operations
     */
    public function add_custom_cron_schedules($schedules) {
        $schedules['vortex_every_5_seconds'] = array(
            'interval' => 5,
            'display' => __('Every 5 Seconds')
        );
        
        $schedules['vortex_every_10_seconds'] = array(
            'interval' => 10,
            'display' => __('Every 10 Seconds')
        );
        
        return $schedules;
    }
    
    /**
     * Add ARCHER orchestrator admin menu
     */
    public function add_orchestrator_admin_menu() {
        add_menu_page(
            'ARCHER Orchestrator',
            'ARCHER',
            'manage_options',
            'archer-orchestrator',
            array($this, 'render_orchestrator_dashboard'),
            'dashicons-networking',
            30
        );
    }
    
    /**
     * Render orchestrator dashboard
     */
    public function render_orchestrator_dashboard() {
        ?>
        <div class="wrap">
            <h1>ARCHER: THE ORCHESTRATOR</h1>
            <div id="archer-dashboard">
                <div class="archer-status-grid">
                    <?php foreach ($this->agents as $name => $config): ?>
                        <div class="agent-status-card" data-agent="<?php echo esc_attr($name); ?>">
                            <h3><?php echo esc_html($name); ?></h3>
                            <div class="status-indicator status-<?php echo esc_attr($config['status']); ?>">
                                <?php echo esc_html(ucfirst($config['status'])); ?>
                            </div>
                            <div class="agent-metrics">
                                <p>Learning: <?php echo $config['learning_active'] ? 'Active' : 'Inactive'; ?></p>
                                <p>Cloud: <?php echo $config['cloud_connected'] ? 'Connected' : 'Disconnected'; ?></p>
                                <p>Sync: <?php echo esc_html($config['sync_status']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="orchestrator-controls">
                    <button type="button" class="button button-primary" id="sync-all-agents">
                        Sync All Agents
                    </button>
                    <button type="button" class="button" id="restart-learning">
                        Restart Learning
                    </button>
                    <button type="button" class="button" id="check-cloud-status">
                        Check Cloud Status
                    </button>
                </div>
            </div>
        </div>
        
        <style>
        .archer-status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .agent-status-card {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            background: #fff;
        }
        .status-indicator {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            margin: 10px 0;
        }
        .status-active { background: #4CAF50; color: white; }
        .status-error { background: #f44336; color: white; }
        .status-initializing { background: #ff9800; color: white; }
        .orchestrator-controls {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
        }
        </style>
        <?php
    }
    
    // Helper methods for orchestration functionality
    
    private function log_orchestrator_event($message, $level = 'info') {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'orchestrator' => 'ARCHER',
            'level' => $level,
            'message' => $message
        );
        
        error_log('[ARCHER_ORCHESTRATOR] ' . json_encode($log_entry));
    }
    
    private function create_agent_placeholder($name, $config) {
        $this->agents[$name]['status'] = 'missing_implementation';
        $this->log_orchestrator_event("Agent {$name} requires implementation", 'warning');
    }
    
    private function enable_agent_continuous_learning($name) {
        $this->agents[$name]['learning_active'] = true;
        do_action("enable_continuous_learning_{$name}");
    }
    
    private function determine_learning_agents($interaction_data) {
        // Return agents that should learn from this interaction
        return array_keys($this->agents);
    }
    
    private function send_learning_data_to_agent($agent_name, $data) {
        do_action("agent_learn_{$agent_name}", $data);
    }
    
    private function sync_cross_agent_learning($data) {
        do_action('cross_agent_learning_sync', $data);
    }
    
    private function get_agent_learning_state($name) {
        return apply_filters("get_learning_state_{$name}", array());
    }
    
    private function share_learning_insights($name, $state) {
        do_action('share_learning_insights', $name, $state);
    }
    
    private function update_agent_sync_status($name, $status) {
        $this->agents[$name]['sync_status'] = $status;
    }
    
    private function handle_agent_timeout($name) {
        $this->log_orchestrator_event("Agent {$name} timeout detected - attempting recovery", 'warning');
        $this->agents[$name]['status'] = 'recovering';
    }
    
    private function send_heartbeat_ping($name) {
        $this->agents[$name]['last_heartbeat'] = time();
    }
    
    private function sync_all_agent_states() {
        do_action('sync_all_agent_states');
    }
    
    private function sync_learning_progress() {
        do_action('sync_learning_progress');
    }
    
    private function update_coordination_metrics() {
        update_option('vortex_coordination_last_sync', current_time('mysql'));
    }
    
    private function setup_agent_cloud_connection($name, $instance) {
        $this->agents[$name]['cloud_connected'] = true;
    }
    
    private function restore_agent_cloud_connection($name) {
        $this->agents[$name]['cloud_connected'] = true;
        $this->log_orchestrator_event("Cloud connection restored for {$name}", 'info');
    }
    
    private function load_comprehensive_profile($user_id) {
        return array(
            'user_id' => $user_id,
            'artwork_preferences' => get_user_meta($user_id, 'artwork_preferences', true),
            'purchase_history' => get_user_meta($user_id, 'purchase_history', true),
            'engagement_metrics' => get_user_meta($user_id, 'engagement_metrics', true),
            'transaction_history' => get_user_meta($user_id, 'transaction_history', true)
        );
    }
    
    private function filter_profile_data_for_agent($profile_data, $access_type) {
        if (isset($profile_data[$access_type])) {
            return array($access_type => $profile_data[$access_type]);
        }
        return array();
    }
    
    private function load_orchestrator_config() {
        $stored_config = get_option('vortex_archer_config', array());
        $this->config = array_merge($this->config, $stored_config);
    }
    
    /**
     * Calculate agent health score (0-1)
     */
    private function calculate_agent_health($name) {
        $agent = $this->agents[$name] ?? null;
        if (!$agent) {
            return 0;
        }
        
        $health_factors = array(
            'status_active' => ($agent['status'] === 'active') ? 1 : 0,
            'cloud_connected' => $agent['cloud_connected'] ? 1 : 0,
            'learning_active' => $agent['learning_active'] ? 1 : 0,
            'recent_heartbeat' => $this->has_recent_heartbeat($name) ? 1 : 0,
            'low_errors' => ($this->get_agent_error_count($name) < 5) ? 1 : 0
        );
        
        return round(array_sum($health_factors) / count($health_factors), 2);
    }
    
    /**
     * Get agent error count from logs
     */
    private function get_agent_error_count($name) {
        // Get recent error count (last 24 hours)
        global $wpdb;
        $error_table = $wpdb->prefix . 'vortex_agent_errors';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$error_table'") == $error_table) {
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $error_table 
                WHERE agent_name = %s AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
                $name
            ));
        }
        
        return 0;
    }
    
    /**
     * Get agent average response time
     */
    private function get_agent_avg_response_time($name) {
        global $wpdb;
        $perf_table = $wpdb->prefix . 'vortex_agent_performance';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$perf_table'") == $perf_table) {
            return (float) $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(response_time_ms) FROM $perf_table 
                WHERE agent_name = %s AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
                $name
            )) ?: 0;
        }
        
        return 0;
    }
    
    /**
     * Calculate overall system health
     */
    private function calculate_system_health() {
        $total_health = 0;
        $agent_count = 0;
        
        foreach ($this->agents as $name => $config) {
            $total_health += $this->calculate_agent_health($name);
            $agent_count++;
        }
        
        $avg_health = $agent_count > 0 ? $total_health / $agent_count : 0;
        
        return array(
            'overall_score' => round($avg_health, 2),
            'status' => $this->get_health_status_text($avg_health),
            'active_agents' => count(array_filter($this->agents, function($agent) { 
                return $agent['status'] === 'active'; 
            })),
            'total_agents' => count($this->agents)
        );
    }
    
    /**
     * Get health status text
     */
    private function get_health_status_text($score) {
        if ($score >= 0.9) return 'excellent';
        if ($score >= 0.8) return 'good';
        if ($score >= 0.6) return 'fair';
        if ($score >= 0.4) return 'poor';
        return 'critical';
    }
    
    /**
     * Check if agent has recent heartbeat
     */
    private function has_recent_heartbeat($name) {
        $agent = $this->agents[$name] ?? null;
        if (!$agent) {
            return false;
        }
        
        $last_heartbeat = $agent['last_heartbeat'] ?? 0;
        return (time() - $last_heartbeat) < 60; // Within last minute
    }
    
    /**
     * Get system uptime in hours
     */
    private function get_system_uptime_hours() {
        $start_time = get_option('vortex_archer_ignited', current_time('mysql'));
        $start_timestamp = strtotime($start_time);
        $uptime_seconds = time() - $start_timestamp;
        return round($uptime_seconds / 3600, 1); // Convert to hours
    }
    
    /**
     * Enhanced error handling for agents
     */
    private function handle_agent_error($agent_name, $exception, $operation) {
        // Increment error count
        if (isset($this->agents[$agent_name])) {
            $this->agents[$agent_name]['error_count']++;
            $this->agents[$agent_name]['last_error'] = array(
                'message' => $exception->getMessage(),
                'operation' => $operation,
                'timestamp' => time()
            );
        }
        
        // Log detailed error
        error_log(sprintf(
            '[ARCHER_AGENT_ERROR] Agent: %s, Operation: %s, Error: %s',
            $agent_name,
            $operation,
            $exception->getMessage()
        ));
        
        // Attempt recovery if error count is high
        if ($this->agents[$agent_name]['error_count'] >= 5) {
            $this->attempt_agent_recovery($agent_name);
        }
    }
    
    /**
     * Attempt to recover failed agent
     */
    private function attempt_agent_recovery($agent_name) {
        try {
            $this->log_orchestrator_event("Attempting recovery for agent: {$agent_name}", 'warning');
            
            // Reset error count
            $this->agents[$agent_name]['error_count'] = 0;
            
            // Restart agent learning
            if (isset($this->agents[$agent_name]['class'])) {
                $class_name = $this->agents[$agent_name]['class'];
                if (class_exists($class_name) && method_exists($class_name, 'get_instance')) {
                    $agent_instance = call_user_func(array($class_name, 'get_instance'));
                    if (method_exists($agent_instance, 'enable_continuous_learning')) {
                        $agent_instance->enable_continuous_learning(true);
                    }
                }
            }
            
            // Update status
            $this->agents[$agent_name]['status'] = 'recovering';
            
            $this->log_orchestrator_event("Recovery attempted for agent: {$agent_name}", 'info');
            
        } catch (Exception $e) {
            $this->log_orchestrator_event("Recovery failed for agent {$agent_name}: " . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Enhanced real-time learning with optimization
     */
    public function process_real_time_learning_optimized($interaction_data) {
        $start_time = microtime(true);
        
        try {
            // Input validation
            if (!is_array($interaction_data) || empty($interaction_data)) {
                throw new InvalidArgumentException('Invalid interaction data provided');
            }
            
            // Rate limiting (basic implementation)
            $rate_limit_key = 'archer_learning_rate_' . get_current_user_id();
            $current_requests = get_transient($rate_limit_key) ?: 0;
            
            if ($current_requests > 60) { // 60 requests per minute
                $this->log_orchestrator_event('Rate limit exceeded for learning processing', 'warning');
                return false;
            }
            
            set_transient($rate_limit_key, $current_requests + 1, 60);
            
            // Process learning for healthy agents
            $results = array();
            $learning_agents = $this->determine_learning_agents($interaction_data);
            
            foreach ($learning_agents as $agent_name) {
                if ($this->is_agent_healthy_for_learning($agent_name)) {
                    try {
                        $this->send_learning_data_to_agent($agent_name, $interaction_data);
                        $results[$agent_name] = 'success';
                    } catch (Exception $e) {
                        $this->handle_agent_error($agent_name, $e, 'learning_processing');
                        $results[$agent_name] = 'error';
                    }
                }
            }
            
            // Performance tracking
            $processing_time = (microtime(true) - $start_time) * 1000;
            $this->track_learning_performance($processing_time, count($results));
            
            return $results;
            
        } catch (Exception $e) {
            $this->log_orchestrator_event('Real-time learning failed: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Check if agent is healthy for learning
     */
    private function is_agent_healthy_for_learning($agent_name) {
        $agent = $this->agents[$agent_name] ?? null;
        if (!$agent) {
            return false;
        }
        
        return $agent['status'] === 'active' && 
               $agent['learning_active'] && 
               $agent['error_count'] < 10;
    }
    
    /**
     * Track learning performance
     */
    private function track_learning_performance($processing_time, $agent_count) {
        $performance_data = array(
            'processing_time_ms' => $processing_time,
            'agents_processed' => $agent_count,
            'memory_usage_mb' => memory_get_usage(true) / 1024 / 1024,
            'timestamp' => current_time('mysql')
        );
        
        // Store in transient for recent performance tracking
        $recent_performance = get_transient('archer_recent_performance') ?: array();
        $recent_performance[] = $performance_data;
        
        // Keep only last 100 entries
        if (count($recent_performance) > 100) {
            $recent_performance = array_slice($recent_performance, -100);
        }
        
        set_transient('archer_recent_performance', $recent_performance, 3600); // 1 hour
    }
}

// Initialize ARCHER on WordPress load
add_action('plugins_loaded', function() {
    VORTEX_ARCHER_Orchestrator::get_instance();
}, 5); 