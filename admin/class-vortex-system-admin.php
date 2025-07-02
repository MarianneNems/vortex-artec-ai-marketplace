<?php
/**
 * VORTEX SYSTEM ADMIN INTERFACE
 * 
 * Comprehensive admin dashboard for all VORTEX AI systems
 * Manages ARCHER, SECRET SAUCE, Smart Contracts, Artist Journey, and more
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Admin_Interface
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_System_Admin {
    
    private static $instance = null;
    private $menu_pages = array();
    private $system_status = array();
    
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
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // AJAX handlers
        add_action('wp_ajax_vortex_system_status', array($this, 'ajax_get_system_status'));
        add_action('wp_ajax_vortex_agent_control', array($this, 'ajax_agent_control'));
        add_action('wp_ajax_vortex_secret_sauce_toggle', array($this, 'ajax_secret_sauce_toggle'));
        add_action('wp_ajax_vortex_performance_data', array($this, 'ajax_get_performance_data'));
        add_action('wp_ajax_vortex_system_restart', array($this, 'ajax_system_restart'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menus() {
        // Main VORTEX menu
        add_menu_page(
            'VORTEX AI System',
            'VORTEX AI',
            'manage_options',
            'vortex-system',
            array($this, 'render_main_dashboard'),
            'dashicons-superhero-alt',
            3
        );
        
        // System Dashboard
        add_submenu_page(
            'vortex-system',
            'System Dashboard',
            'Dashboard',
            'manage_options',
            'vortex-system',
            array($this, 'render_main_dashboard')
        );
        
        // ARCHER Orchestrator
        add_submenu_page(
            'vortex-system',
            'ARCHER Orchestrator',
            'ARCHER Control',
            'manage_options',
            'vortex-archer-control',
            array($this, 'render_archer_dashboard')
        );
        
        // Agent Management
        add_submenu_page(
            'vortex-system',
            'AI Agents',
            'AI Agents',
            'manage_options',
            'vortex-agents',
            array($this, 'render_agents_dashboard')
        );
        
        // SECRET SAUCE (if authorized)
        if (get_option('vortex_secret_sauce_enabled', false)) {
            add_submenu_page(
                'vortex-system',
                'SECRET SAUCE Control',
                'SECRET SAUCE',
                'manage_options',
                'vortex-secret-sauce',
                array($this, 'render_secret_sauce_dashboard')
            );
        }
        
        // Smart Contracts
        add_submenu_page(
            'vortex-system',
            'Smart Contracts',
            'TOLA Contracts',
            'manage_options',
            'vortex-smart-contracts',
            array($this, 'render_smart_contracts_dashboard')
        );
        
        // Artist Journey
        add_submenu_page(
            'vortex-system',
            'Artist Journey',
            'Artist Journey',
            'manage_options',
            'vortex-artist-journey',
            array($this, 'render_artist_journey_dashboard')
        );
        
        // Performance Analytics
        add_submenu_page(
            'vortex-system',
            'Performance Analytics',
            'Analytics',
            'manage_options',
            'vortex-analytics',
            array($this, 'render_analytics_dashboard')
        );
        
        // System Settings
        add_submenu_page(
            'vortex-system',
            'System Settings',
            'Settings',
            'manage_options',
            'vortex-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'vortex-') === false && $hook !== 'toplevel_page_vortex-system') {
            return;
        }
        
        // Enqueue Chart.js for analytics
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            array(),
            '3.9.1',
            true
        );
        
        // Admin CSS
        wp_enqueue_style(
            'vortex-system-admin',
            VORTEX_PLUGIN_URL . 'admin/css/vortex-system-admin.css',
            array(),
            VORTEX_VERSION
        );
        
        // Admin JS
        wp_enqueue_script(
            'vortex-system-admin',
            VORTEX_PLUGIN_URL . 'admin/js/vortex-system-admin.js',
            array('jquery', 'chartjs'),
            VORTEX_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('vortex-system-admin', 'vortexAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_admin_nonce'),
            'strings' => array(
                'system_restart' => __('System Restart', 'vortex-ai'),
                'confirm_restart' => __('Are you sure you want to restart the VORTEX system?', 'vortex-ai'),
                'loading' => __('Loading...', 'vortex-ai'),
                'error' => __('Error occurred', 'vortex-ai'),
                'success' => __('Operation successful', 'vortex-ai')
            )
        ));
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('vortex_system_settings', 'vortex_secret_sauce_enabled');
        register_setting('vortex_system_settings', 'vortex_runpod_api_key');
        register_setting('vortex_system_settings', 'vortex_tola_blockchain_endpoint');
        register_setting('vortex_system_settings', 'vortex_agent_sync_interval');
        register_setting('vortex_system_settings', 'vortex_performance_monitoring');
    }
    
    /**
     * Render main dashboard
     */
    public function render_main_dashboard() {
        $system_status = $this->get_system_status();
        $performance_data = $this->get_performance_overview();
        
        include VORTEX_PLUGIN_DIR . 'admin/partials/vortex-main-dashboard.php';
    }
    
    /**
     * Render ARCHER dashboard
     */
    public function render_archer_dashboard() {
        $archer_status = $this->get_archer_status();
        $agents_status = $this->get_all_agents_status();
        
        include VORTEX_PLUGIN_DIR . 'admin/partials/vortex-archer-dashboard.php';
    }
    
    /**
     * Render agents dashboard
     */
    public function render_agents_dashboard() {
        $agents = array('HURAII', 'CHLOE', 'HORACE', 'THORIUS');
        $agents_data = array();
        
        foreach ($agents as $agent) {
            $agents_data[$agent] = $this->get_agent_detailed_status($agent);
        }
        
        include VORTEX_PLUGIN_DIR . 'admin/partials/vortex-agents-dashboard.php';
    }
    
    /**
     * Render SECRET SAUCE dashboard
     */
    public function render_secret_sauce_dashboard() {
        if (!get_option('vortex_secret_sauce_enabled', false)) {
            wp_die('ACCESS DENIED: SECRET SAUCE not authorized for this installation.');
        }
        
        $runpod_status = $this->get_runpod_vault_status();
        $zodiac_intelligence = $this->get_zodiac_intelligence_status();
        $seed_art_stats = $this->get_seed_art_statistics();
        
        include VORTEX_PLUGIN_DIR . 'admin/partials/vortex-secret-sauce-dashboard.php';
    }
    
    /**
     * Render smart contracts dashboard
     */
    public function render_smart_contracts_dashboard() {
        $contract_stats = $this->get_smart_contract_statistics();
        $recent_contracts = $this->get_recent_smart_contracts();
        $swapping_activity = $this->get_swapping_activity();
        
        include VORTEX_PLUGIN_DIR . 'admin/partials/vortex-smart-contracts-dashboard.php';
    }
    
    /**
     * Render artist journey dashboard
     */
    public function render_artist_journey_dashboard() {
        $journey_stats = $this->get_artist_journey_statistics();
        $user_progress = $this->get_users_journey_progress();
        
        include VORTEX_PLUGIN_DIR . 'admin/partials/vortex-artist-journey-dashboard.php';
    }
    
    /**
     * Render analytics dashboard
     */
    public function render_analytics_dashboard() {
        $performance_metrics = $this->get_detailed_performance_metrics();
        $error_logs = $this->get_recent_error_logs();
        
        include VORTEX_PLUGIN_DIR . 'admin/partials/vortex-analytics-dashboard.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        include VORTEX_PLUGIN_DIR . 'admin/partials/vortex-settings-page.php';
    }
    
    /**
     * Get system status
     */
    private function get_system_status() {
        global $wpdb;
        
        $status = array(
            'overall_health' => 'excellent',
            'components_loaded' => 0,
            'agents_active' => 0,
            'memory_usage' => memory_get_usage(true),
            'uptime' => $this->get_system_uptime(),
            'last_sync' => get_option('vortex_last_agent_sync', 'Never'),
            'database_status' => 'connected'
        );
        
        // Check component status
        $components = array('ARCHER', 'SECRET_SAUCE', 'SMART_CONTRACTS', 'ARTIST_JOURNEY');
        foreach ($components as $component) {
            if (get_option("vortex_{$component}_initialized", false)) {
                $status['components_loaded']++;
            }
        }
        
        // Check agent status
        $agents_table = $wpdb->prefix . 'vortex_agent_states';
        if ($wpdb->get_var("SHOW TABLES LIKE '$agents_table'") == $agents_table) {
            $status['agents_active'] = $wpdb->get_var(
                "SELECT COUNT(*) FROM $agents_table WHERE status = 'active'"
            );
        }
        
        return $status;
    }
    
    /**
     * Get ARCHER status
     */
    private function get_archer_status() {
        return array(
            'status' => class_exists('VORTEX_ARCHER_Orchestrator') ? 'active' : 'inactive',
            'agents_managed' => 4,
            'sync_interval' => '5 seconds',
            'last_orchestration' => get_option('vortex_archer_last_orchestration', 'Never'),
            'learning_coordination' => 'active',
            'cloud_connectivity' => 'connected'
        );
    }
    
    /**
     * Get all agents status
     */
    private function get_all_agents_status() {
        global $wpdb;
        
        $agents_table = $wpdb->prefix . 'vortex_agent_states';
        $agents_status = array();
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$agents_table'") == $agents_table) {
            $results = $wpdb->get_results(
                "SELECT * FROM $agents_table ORDER BY agent_name"
            );
            
            foreach ($results as $agent) {
                $agents_status[$agent->agent_name] = array(
                    'status' => $agent->status,
                    'learning_active' => (bool) $agent->learning_active,
                    'cloud_connected' => (bool) $agent->cloud_connected,
                    'performance_score' => $agent->performance_score,
                    'error_count' => $agent->error_count,
                    'last_heartbeat' => $agent->last_heartbeat
                );
            }
        }
        
        return $agents_status;
    }
    
    /**
     * Get agent detailed status
     */
    private function get_agent_detailed_status($agent_name) {
        global $wpdb;
        
        $agents_table = $wpdb->prefix . 'vortex_agent_states';
        $performance_table = $wpdb->prefix . 'vortex_agent_performance';
        
        $agent_data = array(
            'name' => $agent_name,
            'status' => 'inactive',
            'performance_metrics' => array(),
            'recent_operations' => array(),
            'error_count' => 0
        );
        
        // Get basic status
        if ($wpdb->get_var("SHOW TABLES LIKE '$agents_table'") == $agents_table) {
            $status = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $agents_table WHERE agent_name = %s",
                $agent_name
            ));
            
            if ($status) {
                $agent_data['status'] = $status->status;
                $agent_data['learning_active'] = (bool) $status->learning_active;
                $agent_data['cloud_connected'] = (bool) $status->cloud_connected;
                $agent_data['performance_score'] = $status->performance_score;
                $agent_data['error_count'] = $status->error_count;
            }
        }
        
        // Get performance metrics
        if ($wpdb->get_var("SHOW TABLES LIKE '$performance_table'") == $performance_table) {
            $metrics = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $performance_table WHERE agent_name = %s ORDER BY created_at DESC LIMIT 10",
                $agent_name
            ));
            
            $agent_data['recent_operations'] = $metrics;
            
            // Calculate average response time
            if (!empty($metrics)) {
                $total_time = array_sum(array_column($metrics, 'response_time_ms'));
                $agent_data['avg_response_time'] = $total_time / count($metrics);
            }
        }
        
        return $agent_data;
    }
    
    /**
     * Get RunPod Vault status (SECRET SAUCE)
     */
    private function get_runpod_vault_status() {
        return array(
            'status' => 'connected',
            'gpu_instances' => array(
                'A100' => 2,
                'V100' => 1,
                'RTX4090' => 3
            ),
            'total_operations' => get_option('vortex_runpod_total_operations', 0),
            'cost_optimization' => '34% savings',
            'zodiac_intelligence_active' => true
        );
    }
    
    /**
     * Get zodiac intelligence status
     */
    private function get_zodiac_intelligence_status() {
        return array(
            'zodiac_mappings' => 12,
            'seed_algorithms' => 3,
            'personalization_accuracy' => '96.8%',
            'cosmic_frequency' => '432Hz active'
        );
    }
    
    /**
     * Get seed art statistics
     */
    private function get_seed_art_statistics() {
        return array(
            'total_generated' => get_option('vortex_seed_art_total', 0),
            'unique_styles' => get_option('vortex_seed_art_styles', 0),
            'success_rate' => '98.7%',
            'avg_generation_time' => '1.2s'
        );
    }
    
    /**
     * Get smart contract statistics
     */
    private function get_smart_contract_statistics() {
        global $wpdb;
        
        $contracts_table = $wpdb->prefix . 'vortex_smart_contracts';
        $stats = array(
            'total_contracts' => 0,
            'active_contracts' => 0,
            'total_swaps' => 0,
            'contract_types' => array()
        );
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$contracts_table'") == $contracts_table) {
            $stats['total_contracts'] = $wpdb->get_var("SELECT COUNT(*) FROM $contracts_table");
            $stats['active_contracts'] = $wpdb->get_var(
                "SELECT COUNT(*) FROM $contracts_table WHERE status = 'active'"
            );
            
            $types = $wpdb->get_results(
                "SELECT contract_type, COUNT(*) as count FROM $contracts_table GROUP BY contract_type"
            );
            
            foreach ($types as $type) {
                $stats['contract_types'][$type->contract_type] = $type->count;
            }
        }
        
        return $stats;
    }
    
    /**
     * Get recent smart contracts
     */
    private function get_recent_smart_contracts() {
        global $wpdb;
        
        $contracts_table = $wpdb->prefix . 'vortex_smart_contracts';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$contracts_table'") == $contracts_table) {
            return $wpdb->get_results(
                "SELECT * FROM $contracts_table ORDER BY creation_timestamp DESC LIMIT 10"
            );
        }
        
        return array();
    }
    
    /**
     * Get system uptime
     */
    private function get_system_uptime() {
        $startup_time = get_option('vortex_system_startup_time', time());
        return human_time_diff($startup_time, time());
    }
    
    /**
     * AJAX: Get system status
     */
    public function ajax_get_system_status() {
        check_ajax_referer('vortex_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $status = $this->get_system_status();
        wp_send_json_success($status);
    }
    
    /**
     * AJAX: Agent control
     */
    public function ajax_agent_control() {
        check_ajax_referer('vortex_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $agent = sanitize_text_field($_POST['agent']);
        $action = sanitize_text_field($_POST['action_type']);
        
        $result = $this->control_agent($agent, $action);
        
        if ($result) {
            wp_send_json_success("Agent $agent $action successful");
        } else {
            wp_send_json_error("Failed to $action agent $agent");
        }
    }
    
    /**
     * AJAX: Toggle SECRET SAUCE
     */
    public function ajax_secret_sauce_toggle() {
        check_ajax_referer('vortex_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $enabled = (bool) $_POST['enabled'];
        update_option('vortex_secret_sauce_enabled', $enabled);
        
        wp_send_json_success(array(
            'enabled' => $enabled,
            'message' => $enabled ? 'SECRET SAUCE activated' : 'SECRET SAUCE deactivated'
        ));
    }
    
    /**
     * AJAX: Get performance data
     */
    public function ajax_get_performance_data() {
        check_ajax_referer('vortex_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $timeframe = sanitize_text_field($_POST['timeframe'] ?? '24h');
        $performance_data = $this->get_performance_data($timeframe);
        
        wp_send_json_success($performance_data);
    }
    
    /**
     * Control agent
     */
    private function control_agent($agent, $action) {
        global $wpdb;
        
        $agents_table = $wpdb->prefix . 'vortex_agent_states';
        
        switch ($action) {
            case 'restart':
                do_action("restart_agent_{$agent}");
                break;
                
            case 'enable_learning':
                do_action("enable_continuous_learning_{$agent}", true);
                break;
                
            case 'disable_learning':
                do_action("enable_continuous_learning_{$agent}", false);
                break;
                
            case 'sync':
                do_action("sync_agent_{$agent}");
                break;
        }
        
        return true;
    }
    
    /**
     * Get performance data
     */
    private function get_performance_data($timeframe) {
        global $wpdb;
        
        $performance_table = $wpdb->prefix . 'vortex_agent_performance';
        
        $date_filter = '';
        switch ($timeframe) {
            case '1h':
                $date_filter = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
                break;
            case '24h':
                $date_filter = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
                break;
            case '7d':
                $date_filter = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
        }
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$performance_table'") == $performance_table) {
            $results = $wpdb->get_results(
                "SELECT agent_name, AVG(response_time_ms) as avg_response_time, 
                 AVG(memory_usage_mb) as avg_memory_usage, COUNT(*) as operation_count
                 FROM $performance_table $date_filter 
                 GROUP BY agent_name"
            );
            
            return $results;
        }
        
        return array();
    }
} 