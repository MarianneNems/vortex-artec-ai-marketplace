<?php
/**
 * VORTEX System Database Management
 * 
 * Handles database operations for all VORTEX AI components
 * Creates and manages tables for performance tracking, error logging, and system metrics
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Database
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_System_Database {
    
    private static $instance = null;
    private $db_version = '2.0.0';
    
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
        $this->init_hooks();
        $this->check_database_version();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_vortex_database_repair', array($this, 'ajax_database_repair'));
    }
    
    /**
     * Check database version and update if needed
     */
    public function check_database_version() {
        $installed_version = get_option('vortex_system_db_version', '0.0.0');
        
        if (version_compare($installed_version, $this->db_version, '<')) {
            $this->create_all_tables();
            update_option('vortex_system_db_version', $this->db_version);
        }
    }
    
    /**
     * Create all system tables
     */
    public function create_all_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create tables
        $this->create_performance_table($charset_collate);
        $this->create_error_logs_table($charset_collate);
        $this->create_agent_metrics_table($charset_collate);
        $this->create_system_events_table($charset_collate);
        $this->create_secret_sauce_logs_table($charset_collate);
        $this->create_smart_contracts_table($charset_collate);
        $this->create_artist_swapping_table($charset_collate);
        $this->create_runpod_sessions_table($charset_collate);
        $this->create_zodiac_profiles_table($charset_collate);
        $this->create_thorius_tables($charset_collate);
        
        // Run initial data seeding
        $this->seed_initial_data();
    }
    
    /**
     * Create performance monitoring table
     */
    private function create_performance_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_performance';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            agent_name varchar(50) NOT NULL,
            operation_type varchar(100) NOT NULL,
            response_time float NOT NULL,
            memory_usage bigint NOT NULL,
            cpu_usage float DEFAULT 0,
            success_rate float DEFAULT 100,
            error_count int DEFAULT 0,
            operation_data longtext,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY agent_name (agent_name),
            KEY operation_type (operation_type),
            KEY timestamp (timestamp),
            KEY response_time (response_time)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create error logs table
     */
    private function create_error_logs_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_error_logs';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            component varchar(50) NOT NULL,
            error_level varchar(20) NOT NULL,
            error_code varchar(50),
            error_message text NOT NULL,
            error_data longtext,
            stack_trace longtext,
            user_id bigint(20),
            request_url varchar(500),
            user_agent varchar(500),
            ip_address varchar(45),
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            resolved tinyint(1) DEFAULT 0,
            resolution_notes text,
            PRIMARY KEY (id),
            KEY component (component),
            KEY error_level (error_level),
            KEY timestamp (timestamp),
            KEY resolved (resolved)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create agent metrics table
     */
    private function create_agent_metrics_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_agent_metrics';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            agent_name varchar(50) NOT NULL,
            metric_type varchar(100) NOT NULL,
            metric_value decimal(10,4) NOT NULL,
            metric_unit varchar(20),
            additional_data longtext,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY agent_name (agent_name),
            KEY metric_type (metric_type),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create system events table
     */
    private function create_system_events_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_system_events';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            event_type varchar(100) NOT NULL,
            event_category varchar(50) NOT NULL,
            event_message text NOT NULL,
            event_data longtext,
            severity varchar(20) DEFAULT 'info',
            user_id bigint(20),
            session_id varchar(100),
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY event_category (event_category),
            KEY severity (severity),
            KEY timestamp (timestamp),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create SECRET SAUCE logs table
     */
    private function create_secret_sauce_logs_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_secret_sauce_logs';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            operation_type varchar(100) NOT NULL,
            zodiac_sign varchar(20),
            seed_algorithm varchar(50),
            generation_time float,
            runpod_session_id varchar(100),
            gpu_type varchar(50),
            cost decimal(10,4),
            quality_score float,
            user_id bigint(20),
            operation_data longtext,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY operation_type (operation_type),
            KEY zodiac_sign (zodiac_sign),
            KEY user_id (user_id),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create smart contracts table
     */
    private function create_smart_contracts_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_smart_contracts';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            contract_address varchar(100) NOT NULL,
            contract_type varchar(50) NOT NULL,
            artwork_id bigint(20),
            artist_id bigint(20),
            royalty_percentage decimal(5,2),
            transfer_fee decimal(10,4),
            contract_status varchar(20) DEFAULT 'active',
            blockchain_network varchar(20) DEFAULT 'TOLA',
            transaction_hash varchar(100),
            gas_used bigint(20),
            contract_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY contract_address (contract_address),
            KEY contract_type (contract_type),
            KEY artwork_id (artwork_id),
            KEY artist_id (artist_id),
            KEY contract_status (contract_status)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create artist swapping table
     */
    private function create_artist_swapping_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_artist_swapping';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            swap_id varchar(50) NOT NULL,
            initiating_artist_id bigint(20) NOT NULL,
            target_artist_id bigint(20) NOT NULL,
            initiating_artwork_id bigint(20) NOT NULL,
            target_artwork_id bigint(20),
            swap_category varchar(50) NOT NULL,
            swap_status varchar(20) DEFAULT 'pending',
            compatibility_score float,
            escrow_contract varchar(100),
            reputation_impact decimal(5,2),
            swap_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            completed_at datetime NULL,
            PRIMARY KEY (id),
            UNIQUE KEY swap_id (swap_id),
            KEY initiating_artist_id (initiating_artist_id),
            KEY target_artist_id (target_artist_id),
            KEY swap_status (swap_status),
            KEY swap_category (swap_category)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create RunPod sessions table
     */
    private function create_runpod_sessions_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_runpod_sessions';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            pod_id varchar(100),
            gpu_type varchar(50),
            cpu_type varchar(50),
            operation_type varchar(100),
            session_status varchar(20) DEFAULT 'active',
            start_time datetime DEFAULT CURRENT_TIMESTAMP,
            end_time datetime NULL,
            duration_seconds int,
            cost_usd decimal(10,4),
            cost_optimization_percentage decimal(5,2),
            session_data longtext,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY pod_id (pod_id),
            KEY session_status (session_status),
            KEY start_time (start_time)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create zodiac profiles table
     */
    private function create_zodiac_profiles_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_zodiac_profiles';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            zodiac_sign varchar(20) NOT NULL,
            artistic_dna longtext,
            color_signature varchar(500),
            neural_weights longtext,
            seed_modifiers longtext,
            personalization_score float DEFAULT 0,
            learning_data longtext,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY zodiac_sign (zodiac_sign),
            KEY personalization_score (personalization_score)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create THORIUS-related tables
     */
    private function create_thorius_tables($charset_collate) {
        global $wpdb;
        
        // THORIUS Interactions table
        $interactions_table = $wpdb->prefix . 'vortex_thorius_interactions';
        $sql = "CREATE TABLE $interactions_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            user_message text NOT NULL,
            thorius_response longtext NOT NULL,
            response_type varchar(50) NOT NULL DEFAULT 'info',
            escalation_needed tinyint(1) NOT NULL DEFAULT 0,
            interaction_time datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            user_satisfaction int(1) NULL DEFAULT NULL,
            session_id varchar(64) NULL,
            context_data longtext NULL,
            response_time_ms int(11) NULL,
            ip_address varchar(45) NULL,
            user_agent text NULL,
            platform_page varchar(255) NULL,
            conversation_thread varchar(64) NULL,
            feedback_provided tinyint(1) NOT NULL DEFAULT 0,
            helpful_rating int(1) NULL,
            issue_resolved tinyint(1) NULL,
            follow_up_needed tinyint(1) NOT NULL DEFAULT 0,
            category_tags varchar(255) NULL,
            sentiment_score decimal(3,2) NULL,
            language_detected varchar(10) NULL DEFAULT 'en',
            security_flags varchar(255) NULL,
            learning_priority int(1) NOT NULL DEFAULT 3,
            archived tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_interaction_time (interaction_time),
            KEY idx_response_type (response_type),
            KEY idx_escalation (escalation_needed),
            KEY idx_satisfaction (user_satisfaction),
            KEY idx_session (session_id),
            KEY idx_conversation (conversation_thread),
            KEY idx_priority (learning_priority),
            KEY idx_archived (archived),
            KEY idx_user_time (user_id, interaction_time)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // THORIUS Learning Metrics table
        $metrics_table = $wpdb->prefix . 'vortex_thorius_learning_metrics';
        $sql = "CREATE TABLE $metrics_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            metric_date date NOT NULL,
            total_interactions int(11) NOT NULL DEFAULT 0,
            avg_response_time decimal(8,2) NOT NULL DEFAULT 0,
            satisfaction_avg decimal(3,2) NULL,
            escalation_rate decimal(5,2) NOT NULL DEFAULT 0,
            resolution_rate decimal(5,2) NOT NULL DEFAULT 0,
            feedback_count int(11) NOT NULL DEFAULT 0,
            top_categories text NULL,
            improvement_areas text NULL,
            performance_score decimal(3,2) NOT NULL DEFAULT 0,
            learning_iterations int(11) NOT NULL DEFAULT 0,
            optimization_applied tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_date (metric_date),
            KEY idx_performance (performance_score),
            KEY idx_satisfaction (satisfaction_avg),
            KEY idx_created (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // THORIUS Supervision table
        $supervision_table = $wpdb->prefix . 'vortex_thorius_supervision';
        $sql = "CREATE TABLE $supervision_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            action_type varchar(100) NOT NULL,
            action_data longtext NULL,
            supervision_result enum('approved','warning','blocked','flagged') NOT NULL DEFAULT 'approved',
            warning_messages text NULL,
            recommendations text NULL,
            risk_score int(3) NOT NULL DEFAULT 0,
            automated_action varchar(100) NULL,
            manual_review_needed tinyint(1) NOT NULL DEFAULT 0,
            reviewer_id bigint(20) UNSIGNED NULL,
            review_status enum('pending','approved','rejected') NULL,
            review_notes text NULL,
            supervision_time datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resolved_time datetime NULL,
            ip_address varchar(45) NULL,
            session_data text NULL,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_action_type (action_type),
            KEY idx_result (supervision_result),
            KEY idx_risk_score (risk_score),
            KEY idx_manual_review (manual_review_needed),
            KEY idx_supervision_time (supervision_time),
            KEY idx_user_time (user_id, supervision_time)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Seed initial data
     */
    private function seed_initial_data() {
        $this->seed_zodiac_templates();
        $this->seed_smart_contract_templates();
    }
    
    /**
     * Seed zodiac templates
     */
    private function seed_zodiac_templates() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_zodiac_profiles';
        
        // Check if templates already exist
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE user_id = 0");
        
        if ($existing > 0) {
            return; // Templates already seeded
        }
        
        $zodiac_templates = array(
            array(
                'zodiac_sign' => 'aries',
                'artistic_dna' => json_encode(array(
                    'primary_traits' => array('bold', 'dynamic', 'energetic'),
                    'style_preferences' => array('abstract', 'geometric', 'modern'),
                    'composition_style' => 'asymmetrical'
                )),
                'color_signature' => json_encode(array('#FF4500', '#DC143C', '#FF6347')),
                'neural_weights' => json_encode(array('aggression' => 0.8, 'creativity' => 0.9, 'balance' => 0.3)),
                'seed_modifiers' => json_encode(array('fire_element' => 1.2, 'mars_influence' => 0.9))
            ),
            array(
                'zodiac_sign' => 'taurus',
                'artistic_dna' => json_encode(array(
                    'primary_traits' => array('stable', 'luxurious', 'natural'),
                    'style_preferences' => array('realistic', 'landscape', 'classical'),
                    'composition_style' => 'symmetrical'
                )),
                'color_signature' => json_encode(array('#228B22', '#8FBC8F', '#DEB887')),
                'neural_weights' => json_encode(array('stability' => 0.9, 'luxury' => 0.8, 'nature' => 0.9)),
                'seed_modifiers' => json_encode(array('earth_element' => 1.3, 'venus_influence' => 0.8))
            )
            // Add more zodiac signs...
        );
        
        foreach ($zodiac_templates as $template) {
            $template['user_id'] = 0; // Template user ID
            $wpdb->insert($table_name, $template);
        }
    }
    
    /**
     * Seed smart contract templates
     */
    private function seed_smart_contract_templates() {
        // Smart contract templates can be seeded here if needed
        // For now, contracts are created dynamically
    }
    
    /**
     * Get system statistics
     */
    public function get_system_statistics() {
        global $wpdb;
        
        $stats = array();
        
        // Performance statistics
        $perf_table = $wpdb->prefix . 'vortex_performance';
        $stats['performance'] = array(
            'total_operations' => $wpdb->get_var("SELECT COUNT(*) FROM $perf_table"),
            'avg_response_time' => $wpdb->get_var("SELECT AVG(response_time) FROM $perf_table WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"),
            'avg_memory_usage' => $wpdb->get_var("SELECT AVG(memory_usage) FROM $perf_table WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")
        );
        
        // Error statistics
        $error_table = $wpdb->prefix . 'vortex_error_logs';
        $stats['errors'] = array(
            'total_errors' => $wpdb->get_var("SELECT COUNT(*) FROM $error_table"),
            'critical_errors' => $wpdb->get_var("SELECT COUNT(*) FROM $error_table WHERE error_level = 'critical'"),
            'resolved_errors' => $wpdb->get_var("SELECT COUNT(*) FROM $error_table WHERE resolved = 1")
        );
        
        // Smart contracts statistics
        $contracts_table = $wpdb->prefix . 'vortex_smart_contracts';
        $stats['smart_contracts'] = array(
            'total_contracts' => $wpdb->get_var("SELECT COUNT(*) FROM $contracts_table"),
            'active_contracts' => $wpdb->get_var("SELECT COUNT(*) FROM $contracts_table WHERE contract_status = 'active'")
        );
        
        return $stats;
    }
    
    /**
     * AJAX handler for database repair
     */
    public function ajax_database_repair() {
        check_ajax_referer('vortex_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        try {
            $this->create_all_tables();
            wp_send_json_success('Database tables repaired successfully');
        } catch (Exception $e) {
            wp_send_json_error('Database repair failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Clean up old data
     */
    public function cleanup_old_data() {
        global $wpdb;
        
        // Clean up old performance logs (older than 30 days)
        $perf_table = $wpdb->prefix . 'vortex_performance';
        $wpdb->query("DELETE FROM $perf_table WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        
        // Clean up old error logs (older than 90 days)
        $error_table = $wpdb->prefix . 'vortex_error_logs';
        $wpdb->query("DELETE FROM $error_table WHERE timestamp < DATE_SUB(NOW(), INTERVAL 90 DAY)");
        
        // Clean up old system events (older than 60 days)
        $events_table = $wpdb->prefix . 'vortex_system_events';
        $wpdb->query("DELETE FROM $events_table WHERE timestamp < DATE_SUB(NOW(), INTERVAL 60 DAY)");
    }
    
    /**
     * Optimize database tables
     */
    public function optimize_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'vortex_performance',
            $wpdb->prefix . 'vortex_error_logs',
            $wpdb->prefix . 'vortex_agent_metrics',
            $wpdb->prefix . 'vortex_system_events',
            $wpdb->prefix . 'vortex_secret_sauce_logs',
            $wpdb->prefix . 'vortex_smart_contracts',
            $wpdb->prefix . 'vortex_artist_swapping',
            $wpdb->prefix . 'vortex_runpod_sessions',
            $wpdb->prefix . 'vortex_zodiac_profiles'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE $table");
        }
    }
}

// Initialize the database management
VORTEX_System_Database::get_instance(); 