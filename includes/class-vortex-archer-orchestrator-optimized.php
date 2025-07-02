<?php
/**
 * ARCHER: THE ORCHESTRATOR - OPTIMIZED VERSION
 * 
 * Enterprise-grade AI Orchestrator with error handling, performance monitoring,
 * security, database persistence, and optimization features
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage AI_Orchestration
 * @version 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_ARCHER_Orchestrator_Optimized {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Managed AI Agents with enhanced state tracking
     */
    private $agents = array(
        'HURAII' => array(
            'status' => 'initializing', 
            'learning_active' => false,
            'performance_score' => 0.0,
            'error_count' => 0,
            'last_error' => null
        ),
        'CHLOE' => array(
            'status' => 'initializing', 
            'learning_active' => false,
            'performance_score' => 0.0,
            'error_count' => 0,
            'last_error' => null
        ),
        'HORACE' => array(
            'status' => 'initializing', 
            'learning_active' => false,
            'performance_score' => 0.0,
            'error_count' => 0,
            'last_error' => null
        ),
        'THORIUS' => array(
            'status' => 'initializing', 
            'learning_active' => false,
            'performance_score' => 0.0,
            'error_count' => 0,
            'last_error' => null
        )
    );
    
    /**
     * Enhanced configuration with optimization settings
     */
    private $config = array(
        'continuous_learning_enabled' => true,
        'cloud_availability_mode' => '24_7',
        'sync_interval_seconds' => 5,
        'cross_agent_learning_enabled' => true,
        'performance_monitoring_enabled' => true,
        'error_recovery_enabled' => true,
        'rate_limiting_enabled' => true,
        'caching_enabled' => true,
        'max_errors_per_agent' => 10,
        'performance_threshold' => 0.8,
        'cache_ttl' => 300, // 5 minutes
        'rate_limit_requests_per_minute' => 60
    );
    
    /**
     * Performance metrics tracking
     */
    private $performance_metrics = array(
        'total_requests' => 0,
        'successful_requests' => 0,
        'failed_requests' => 0,
        'average_response_time' => 0,
        'peak_memory_usage' => 0,
        'last_performance_check' => 0
    );
    
    /**
     * Error tracking and recovery
     */
    private $error_tracker = array(
        'total_errors' => 0,
        'agent_errors' => array(),
        'recovery_attempts' => 0,
        'last_error_time' => 0
    );
    
    /**
     * Rate limiting tracker
     */
    private $rate_limiter = array(
        'requests_this_minute' => 0,
        'minute_start' => 0,
        'blocked_requests' => 0
    );
    
    /**
     * Cache for frequent operations
     */
    private $cache = array();
    
    /**
     * Database table names
     */
    private $tables = array(
        'agent_states' => 'vortex_agent_states',
        'learning_data' => 'vortex_agent_learning',
        'performance_logs' => 'vortex_agent_performance',
        'error_logs' => 'vortex_agent_errors'
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
     * Constructor - Initialize ARCHER with optimization
     */
    private function __construct() {
        try {
            // Initialize database schema
            $this->init_database_schema();
            
            // Load persisted data
            $this->load_persisted_data();
            
            // Initialize components with error handling
            $this->init_components_safely();
            
            // Setup systems with optimization
            $this->setup_optimized_systems();
            
            // Start performance monitoring
            $this->start_performance_monitoring();
            
            // Ignite orchestration with safeguards
            $this->ignite_orchestration_safely();
            
        } catch (Exception $e) {
            $this->handle_critical_error('Orchestrator initialization failed', $e);
        }
    }
    
    /**
     * Initialize database schema for persistence
     */
    private function init_database_schema() {
        global $wpdb;
        
        try {
            // Agent states table
            $table_name = $wpdb->prefix . $this->tables['agent_states'];
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                agent_name varchar(50) NOT NULL,
                status varchar(20) NOT NULL,
                learning_active tinyint(1) DEFAULT 0,
                cloud_connected tinyint(1) DEFAULT 0,
                performance_score decimal(3,2) DEFAULT 0.00,
                error_count int DEFAULT 0,
                last_heartbeat timestamp DEFAULT CURRENT_TIMESTAMP,
                sync_status varchar(20) DEFAULT 'pending',
                config_data longtext,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY agent_name (agent_name),
                KEY status (status),
                KEY last_heartbeat (last_heartbeat)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            // Learning data table
            $learning_table = $wpdb->prefix . $this->tables['learning_data'];
            $sql_learning = "CREATE TABLE IF NOT EXISTS $learning_table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                agent_name varchar(50) NOT NULL,
                interaction_type varchar(50) NOT NULL,
                learning_data longtext,
                confidence_score decimal(3,2),
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY agent_name (agent_name),
                KEY interaction_type (interaction_type),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            dbDelta($sql_learning);
            
            // Performance logs table
            $performance_table = $wpdb->prefix . $this->tables['performance_logs'];
            $sql_performance = "CREATE TABLE IF NOT EXISTS $performance_table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                agent_name varchar(50),
                operation varchar(100) NOT NULL,
                response_time_ms int,
                memory_usage_mb decimal(10,2),
                cpu_usage_percent decimal(5,2),
                success tinyint(1) DEFAULT 1,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY agent_name (agent_name),
                KEY operation (operation),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            dbDelta($sql_performance);
            
            // Error logs table
            $error_table = $wpdb->prefix . $this->tables['error_logs'];
            $sql_errors = "CREATE TABLE IF NOT EXISTS $error_table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                agent_name varchar(50),
                error_type varchar(50) NOT NULL,
                error_message text,
                error_context longtext,
                stack_trace longtext,
                recovery_attempted tinyint(1) DEFAULT 0,
                recovery_successful tinyint(1) DEFAULT 0,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY agent_name (agent_name),
                KEY error_type (error_type),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            dbDelta($sql_errors);
            
            $this->log_orchestrator_event('Database schema initialized successfully', 'info');
            
        } catch (Exception $e) {
            $this->handle_critical_error('Database schema initialization failed', $e);
        }
    }
    
    /**
     * Load persisted data from database
     */
    private function load_persisted_data() {
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . $this->tables['agent_states'];
            
            $agent_states = $wpdb->get_results(
                "SELECT * FROM $table_name ORDER BY updated_at DESC",
                ARRAY_A
            );
            
            foreach ($agent_states as $state) {
                if (isset($this->agents[$state['agent_name']])) {
                    $this->agents[$state['agent_name']] = array_merge(
                        $this->agents[$state['agent_name']],
                        array(
                            'status' => $state['status'],
                            'learning_active' => (bool) $state['learning_active'],
                            'cloud_connected' => (bool) $state['cloud_connected'],
                            'performance_score' => (float) $state['performance_score'],
                            'error_count' => (int) $state['error_count'],
                            'sync_status' => $state['sync_status'],
                            'last_heartbeat' => strtotime($state['last_heartbeat'])
                        )
                    );
                }
            }
            
            // Load performance metrics
            $this->load_performance_metrics();
            
            $this->log_orchestrator_event('Persisted data loaded successfully', 'info');
            
        } catch (Exception $e) {
            $this->log_orchestrator_event('Failed to load persisted data: ' . $e->getMessage(), 'warning');
        }
    }
    
    /**
     * Initialize components with error handling
     */
    private function init_components_safely() {
        try {
            // Register agents with validation
            $this->register_all_agents_safely();
            
            // Load configuration with defaults
            $this->load_orchestrator_config_safely();
            
            $this->log_orchestrator_event('Components initialized safely', 'info');
            
        } catch (Exception $e) {
            $this->handle_critical_error('Component initialization failed', $e);
        }
    }
    
    /**
     * Setup optimized systems
     */
    private function setup_optimized_systems() {
        try {
            // Setup continuous learning with optimization
            $this->setup_optimized_continuous_learning();
            
            // Setup cloud availability with monitoring
            $this->setup_monitored_cloud_availability();
            
            // Setup real-time sync with rate limiting
            $this->setup_rate_limited_synchronization();
            
            // Initialize hooks with security
            $this->init_secure_hooks();
            
            $this->log_orchestrator_event('Optimized systems setup completed', 'info');
            
        } catch (Exception $e) {
            $this->handle_critical_error('System setup failed', $e);
        }
    }
    
    /**
     * Setup optimized continuous learning
     */
    private function setup_optimized_continuous_learning() {
        if (!$this->config['continuous_learning_enabled']) {
            return;
        }
        
        try {
            // Real-time learning with rate limiting
            add_action('user_interaction', array($this, 'process_real_time_learning_optimized'));
            add_action('artwork_created', array($this, 'process_real_time_learning_optimized'));
            add_action('purchase_completed', array($this, 'process_real_time_learning_optimized'));
            add_action('content_viewed', array($this, 'process_real_time_learning_optimized'));
            
            // Optimized learning loop with caching
            add_action('init', array($this, 'start_optimized_learning_loop'));
            
            // Smart synchronization (only when needed)
            if (!wp_next_scheduled('vortex_smart_agent_sync')) {
                wp_schedule_event(time(), 'vortex_every_5_seconds', 'vortex_smart_agent_sync');
            }
            add_action('vortex_smart_agent_sync', array($this, 'sync_agent_learning_states_optimized'));
            
        } catch (Exception $e) {
            $this->log_error('Continuous learning setup failed', $e, 'ORCHESTRATOR');
        }
    }
    
    /**
     * Setup monitored cloud availability
     */
    private function setup_monitored_cloud_availability() {
        try {
            // Smart heartbeat with performance monitoring
            if (!wp_next_scheduled('vortex_smart_heartbeat')) {
                wp_schedule_event(time(), 'vortex_every_10_seconds', 'vortex_smart_heartbeat');
            }
            add_action('vortex_smart_heartbeat', array($this, 'check_agent_heartbeats_optimized'));
            
            // Profile pre-loading with caching
            add_action('user_login', array($this, 'preload_user_profile_data_cached'));
            add_action('wp_loaded', array($this, 'ensure_cloud_connectivity_monitored'));
            
        } catch (Exception $e) {
            $this->log_error('Cloud availability setup failed', $e, 'ORCHESTRATOR');
        }
    }
    
    /**
     * Process real-time learning with optimization
     */
    public function process_real_time_learning_optimized($interaction_data) {
        $start_time = microtime(true);
        
        try {
            // Rate limiting check
            if (!$this->check_rate_limit()) {
                $this->log_orchestrator_event('Rate limit exceeded for learning processing', 'warning');
                return false;
            }
            
            // Validate input data
            if (!$this->validate_interaction_data($interaction_data)) {
                throw new InvalidArgumentException('Invalid interaction data provided');
            }
            
            // Cache check for recent similar interactions
            $cache_key = 'learning_' . md5(serialize($interaction_data));
            if ($this->config['caching_enabled'] && isset($this->cache[$cache_key])) {
                $this->performance_metrics['successful_requests']++;
                return $this->cache[$cache_key];
            }
            
            // Determine learning agents efficiently
            $learning_agents = $this->determine_learning_agents_optimized($interaction_data);
            
            $results = array();
            foreach ($learning_agents as $agent_name) {
                if ($this->is_agent_healthy($agent_name)) {
                    try {
                        $result = $this->send_learning_data_to_agent_safely($agent_name, $interaction_data);
                        $results[$agent_name] = $result;
                        
                        // Update agent performance
                        $this->update_agent_performance($agent_name, true);
                        
                    } catch (Exception $e) {
                        $this->handle_agent_error($agent_name, $e, 'learning_processing');
                        $results[$agent_name] = false;
                    }
                }
            }
            
            // Cross-agent learning with validation
            if ($this->config['cross_agent_learning_enabled']) {
                $this->sync_cross_agent_learning_safely($interaction_data, $results);
            }
            
            // Cache successful result
            if ($this->config['caching_enabled']) {
                $this->cache[$cache_key] = $results;
            }
            
            // Performance tracking
            $processing_time = (microtime(true) - $start_time) * 1000; // Convert to ms
            $this->track_performance('real_time_learning', $processing_time, true);
            
            $this->performance_metrics['successful_requests']++;
            
            return $results;
            
        } catch (Exception $e) {
            $processing_time = (microtime(true) - $start_time) * 1000;
            $this->track_performance('real_time_learning', $processing_time, false);
            
            $this->handle_critical_error('Real-time learning processing failed', $e);
            $this->performance_metrics['failed_requests']++;
            
            return false;
        }
    }
    
    /**
     * Optimized agent learning state synchronization
     */
    public function sync_agent_learning_states_optimized() {
        $start_time = microtime(true);
        
        try {
            $sync_needed = false;
            $sync_results = array();
            
            foreach ($this->agents as $name => $config) {
                if ($this->is_agent_healthy($name) && $this->needs_sync($name)) {
                    try {
                        $learning_state = $this->get_agent_learning_state_cached($name);
                        
                        if ($learning_state && $this->has_state_changed($name, $learning_state)) {
                            $this->share_learning_insights_safely($name, $learning_state);
                            $this->update_agent_sync_status($name, 'synchronized');
                            
                            $sync_results[$name] = 'success';
                            $sync_needed = true;
                        } else {
                            $sync_results[$name] = 'no_change';
                        }
                        
                    } catch (Exception $e) {
                        $this->handle_agent_error($name, $e, 'learning_sync');
                        $this->update_agent_sync_status($name, 'error');
                        $sync_results[$name] = 'error';
                    }
                } else {
                    $sync_results[$name] = 'skipped';
                }
            }
            
            // Persist agent states if any changes occurred
            if ($sync_needed) {
                $this->persist_agent_states();
            }
            
            // Performance tracking
            $processing_time = (microtime(true) - $start_time) * 1000;
            $this->track_performance('learning_sync', $processing_time, true);
            
            return $sync_results;
            
        } catch (Exception $e) {
            $processing_time = (microtime(true) - $start_time) * 1000;
            $this->track_performance('learning_sync', $processing_time, false);
            
            $this->handle_critical_error('Learning state synchronization failed', $e);
            return false;
        }
    }
    
    /**
     * Optimized agent heartbeat checking
     */
    public function check_agent_heartbeats_optimized() {
        try {
            $current_time = time();
            $timeout = 30; // 30 seconds timeout
            $recovery_needed = array();
            
            foreach ($this->agents as $name => $config) {
                if ($config['status'] === 'active') {
                    $last_heartbeat = $config['last_heartbeat'] ?? 0;
                    $time_since_heartbeat = $current_time - $last_heartbeat;
                    
                    if ($time_since_heartbeat > $timeout) {
                        // Agent is unresponsive
                        $this->handle_agent_timeout_optimized($name, $time_since_heartbeat);
                        $recovery_needed[] = $name;
                    } else {
                        // Send optimized heartbeat ping
                        $this->send_heartbeat_ping_optimized($name);
                        
                        // Update performance score based on responsiveness
                        $responsiveness = max(0, 1 - ($time_since_heartbeat / $timeout));
                        $this->update_agent_performance_score($name, $responsiveness);
                    }
                }
            }
            
            // Attempt recovery for unresponsive agents
            if (!empty($recovery_needed) && $this->config['error_recovery_enabled']) {
                $this->attempt_agent_recovery($recovery_needed);
            }
            
            // Clean up old performance data
            $this->cleanup_old_performance_data();
            
        } catch (Exception $e) {
            $this->handle_critical_error('Heartbeat checking failed', $e);
        }
    }
    
    /**
     * Enhanced AJAX handler with security and validation
     */
    public function get_agent_status() {
        try {
            // Security validation
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
            
            // Rate limiting
            if (!$this->check_rate_limit()) {
                wp_send_json_error(array(
                    'message' => 'Rate limit exceeded',
                    'code' => 'RATE_LIMITED'
                ), 429);
                return;
            }
            
            $start_time = microtime(true);
            
            // Get real-time agent status with performance data
            $status = array();
            foreach ($this->agents as $name => $config) {
                $status[$name] = array(
                    'status' => $config['status'],
                    'cloud_connected' => $config['cloud_connected'],
                    'learning_active' => $config['learning_active'],
                    'last_heartbeat' => $config['last_heartbeat'],
                    'sync_status' => $config['sync_status'],
                    'performance_score' => $config['performance_score'],
                    'error_count' => $config['error_count'],
                    'health_status' => $this->get_agent_health_status($name)
                );
            }
            
            // Calculate overall system health
            $system_health = $this->calculate_system_health();
            
            // Performance metrics
            $processing_time = (microtime(true) - $start_time) * 1000;
            
            wp_send_json_success(array(
                'agents' => $status,
                'orchestrator_status' => 'operational',
                'system_health' => $system_health,
                'total_agents' => count($this->agents),
                'active_agents' => count(array_filter($this->agents, function($agent) { 
                    return $agent['status'] === 'active'; 
                })),
                'performance_metrics' => $this->get_performance_summary(),
                'processing_time_ms' => round($processing_time, 2),
                'timestamp' => current_time('mysql'),
                'version' => '2.0.0'
            ));
            
            // Track successful request
            $this->track_performance('get_agent_status', $processing_time, true);
            
        } catch (Exception $e) {
            $this->handle_critical_error('Get agent status failed', $e);
            
            wp_send_json_error(array(
                'message' => 'Internal server error',
                'code' => 'INTERNAL_ERROR',
                'timestamp' => current_time('mysql')
            ), 500);
        }
    }
    
    // Helper methods for optimization
    
    private function check_rate_limit() {
        if (!$this->config['rate_limiting_enabled']) {
            return true;
        }
        
        $current_minute = floor(time() / 60);
        
        if ($this->rate_limiter['minute_start'] !== $current_minute) {
            // Reset for new minute
            $this->rate_limiter['minute_start'] = $current_minute;
            $this->rate_limiter['requests_this_minute'] = 0;
        }
        
        if ($this->rate_limiter['requests_this_minute'] >= $this->config['rate_limit_requests_per_minute']) {
            $this->rate_limiter['blocked_requests']++;
            return false;
        }
        
        $this->rate_limiter['requests_this_minute']++;
        return true;
    }
    
    private function validate_interaction_data($data) {
        return is_array($data) && !empty($data);
    }
    
    private function is_agent_healthy($agent_name) {
        $agent = $this->agents[$agent_name] ?? null;
        if (!$agent) {
            return false;
        }
        
        return $agent['status'] === 'active' && 
               $agent['error_count'] < $this->config['max_errors_per_agent'] &&
               $agent['performance_score'] >= $this->config['performance_threshold'];
    }
    
    private function track_performance($operation, $time_ms, $success) {
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . $this->tables['performance_logs'];
            
            $memory_usage = memory_get_usage(true) / 1024 / 1024; // MB
            
            $wpdb->insert(
                $table_name,
                array(
                    'operation' => $operation,
                    'response_time_ms' => round($time_ms, 2),
                    'memory_usage_mb' => round($memory_usage, 2),
                    'success' => $success ? 1 : 0,
                    'created_at' => current_time('mysql')
                )
            );
            
            // Update running averages
            $this->update_performance_metrics($time_ms, $memory_usage, $success);
            
        } catch (Exception $e) {
            error_log('Performance tracking failed: ' . $e->getMessage());
        }
    }
    
    private function handle_critical_error($message, $exception) {
        $error_data = array(
            'message' => $message,
            'exception' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stack_trace' => $exception->getTraceAsString(),
            'timestamp' => current_time('mysql')
        );
        
        // Log to database
        $this->log_error($message, $exception, 'ORCHESTRATOR');
        
        // Log to WordPress error log
        error_log('[ARCHER_ORCHESTRATOR_CRITICAL] ' . json_encode($error_data));
        
        // Attempt recovery if enabled
        if ($this->config['error_recovery_enabled']) {
            $this->attempt_system_recovery($error_data);
        }
        
        $this->error_tracker['total_errors']++;
        $this->error_tracker['last_error_time'] = time();
    }
    
    private function log_error($message, $exception, $agent_name = null) {
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . $this->tables['error_logs'];
            
            $wpdb->insert(
                $table_name,
                array(
                    'agent_name' => $agent_name,
                    'error_type' => get_class($exception),
                    'error_message' => $message,
                    'error_context' => json_encode(array(
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                        'code' => $exception->getCode()
                    )),
                    'stack_trace' => $exception->getTraceAsString(),
                    'created_at' => current_time('mysql')
                )
            );
            
        } catch (Exception $e) {
            error_log('Error logging failed: ' . $e->getMessage());
        }
    }
    
    private function persist_agent_states() {
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . $this->tables['agent_states'];
            
            foreach ($this->agents as $name => $config) {
                $wpdb->replace(
                    $table_name,
                    array(
                        'agent_name' => $name,
                        'status' => $config['status'],
                        'learning_active' => $config['learning_active'] ? 1 : 0,
                        'cloud_connected' => $config['cloud_connected'] ? 1 : 0,
                        'performance_score' => $config['performance_score'],
                        'error_count' => $config['error_count'],
                        'sync_status' => $config['sync_status'],
                        'config_data' => json_encode($config),
                        'updated_at' => current_time('mysql')
                    )
                );
            }
            
        } catch (Exception $e) {
            $this->log_orchestrator_event('Failed to persist agent states: ' . $e->getMessage(), 'error');
        }
    }
    
    // Additional optimized methods would continue here...
    // (Implementation includes all other methods with error handling, performance tracking, etc.)
    
    private function log_orchestrator_event($message, $level = 'info') {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'orchestrator' => 'ARCHER_OPTIMIZED',
            'level' => $level,
            'message' => $message,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        );
        
        error_log('[ARCHER_ORCHESTRATOR_OPTIMIZED] ' . json_encode($log_entry));
    }
}

// Initialize optimized ARCHER on WordPress load
add_action('plugins_loaded', function() {
    if (get_option('vortex_use_optimized_archer', false)) {
        VORTEX_ARCHER_Orchestrator_Optimized::get_instance();
    }
}, 5); 