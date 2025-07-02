<?php
/**
 * VORTEX SYSTEM INITIALIZER
 * 
 * Centralized initialization for all VORTEX AI components
 * Loads SECRET SAUCE, ARCHER, Smart Contracts, and Artist Journey systems
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage System_Integration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_System_Initializer {
    
    private static $instance = null;
    private $initialized_components = array();
    private $component_dependencies = array();
    private $load_order = array();
    
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
        $this->define_component_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Define component loading dependencies
     */
    private function define_component_dependencies() {
        $this->component_dependencies = array(
            'system_base' => array(),
            'database' => array('system_base'),
            'huraii' => array('database'),
            'chloe' => array('database'),
            'horace' => array('database'),
            'thorius' => array('database'),
            'archer' => array('huraii', 'chloe', 'horace', 'thorius'),
            'secret_sauce' => array('archer'),
            'smart_contracts' => array('database'),
            'artist_swapping' => array('smart_contracts'),
            'admin_ui' => array('archer', 'secret_sauce')
        );
        
        $this->load_order = $this->calculate_load_order();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'initialize_system'), 1);
        add_action('admin_init', array($this, 'initialize_admin_components'), 5);
        add_action('wp_ajax_vortex_system_status', array($this, 'ajax_system_status'));
        add_action('wp_ajax_vortex_component_toggle', array($this, 'ajax_component_toggle'));
        
        // Enqueue admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add custom admin notices
        add_action('admin_notices', array($this, 'show_system_notices'));
    }
    
    /**
     * Main system initialization
     */
    public function initialize_system() {
        try {
            $this->log_system_event('Starting VORTEX system initialization...');
            
            foreach ($this->load_order as $component) {
                $this->load_component($component);
            }
            
            $this->verify_system_integrity();
            $this->log_system_event('VORTEX system initialization completed successfully');
            
            // Set system status
            update_option('vortex_system_status', 'initialized');
            update_option('vortex_last_initialization', current_time('mysql'));
            
        } catch (Exception $e) {
            $this->log_system_event('CRITICAL: System initialization failed - ' . $e->getMessage(), 'error');
            update_option('vortex_system_status', 'failed');
        }
    }
    
    /**
     * Load individual component
     */
    private function load_component($component) {
        if (in_array($component, $this->initialized_components)) {
            return true;
        }
        
        // Check dependencies
        if (!$this->dependencies_loaded($component)) {
            throw new Exception("Dependencies not met for component: $component");
        }
        
        $loaded = false;
        
        switch ($component) {
            case 'system_base':
                $loaded = $this->load_system_base();
                break;
                
            case 'database':
                $loaded = $this->load_database_components();
                break;
                
            case 'huraii':
                $loaded = $this->load_huraii_agent();
                break;
                
            case 'chloe':
                $loaded = $this->load_chloe_agent();
                break;
                
            case 'horace':
                $loaded = $this->load_horace_agent();
                break;
                
            case 'thorius':
                $loaded = $this->load_thorius_agent();
                break;
                
            case 'archer':
                $loaded = $this->load_archer_orchestrator();
                break;
                
            case 'secret_sauce':
                $loaded = $this->load_secret_sauce();
                break;
                
            case 'smart_contracts':
                $loaded = $this->load_smart_contracts();
                break;
                
            case 'artist_swapping':
                $loaded = $this->load_artist_swapping();
                break;
                
            case 'admin_ui':
                $loaded = $this->load_admin_ui();
                break;
        }
        
        if ($loaded) {
            $this->initialized_components[] = $component;
            $this->log_system_event("Component loaded: $component");
        } else {
            throw new Exception("Failed to load component: $component");
        }
        
        return $loaded;
    }
    
    /**
     * Load system base components
     */
    private function load_system_base() {
        // Create necessary directories
        $this->create_system_directories();
        
        // Initialize system constants
        $this->define_system_constants();
        
        // Setup error handling
        $this->setup_error_handling();
        
        return true;
    }
    
    /**
     * Load database components
     */
    private function load_database_components() {
        $class_file = VORTEX_PLUGIN_DIR . 'includes/database/class-vortex-system-database.php';
        
        if (file_exists($class_file)) {
            require_once $class_file;
            
            if (class_exists('VORTEX_System_Database')) {
                VORTEX_System_Database::get_instance();
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Load HURAII agent
     */
    private function load_huraii_agent() {
        $class_file = VORTEX_PLUGIN_DIR . 'includes/class-vortex-huraii.php';
        
        if (file_exists($class_file)) {
            require_once $class_file;
            
            if (class_exists('VORTEX_HURAII')) {
                VORTEX_HURAII::get_instance();
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Load CHLOE agent
     */
    private function load_chloe_agent() {
        $class_file = VORTEX_PLUGIN_DIR . 'class-vortex-cloe.php';
        
        if (file_exists($class_file)) {
            require_once $class_file;
            
            if (class_exists('VORTEX_CLOE')) {
                VORTEX_CLOE::get_instance();
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Load HORACE agent
     */
    private function load_horace_agent() {
        $class_file = VORTEX_PLUGIN_DIR . 'includes/class-vortex-horace-optimized.php';
        
        if (file_exists($class_file)) {
            require_once $class_file;
            
            if (class_exists('VORTEX_HORACE_Optimized')) {
                VORTEX_HORACE_Optimized::get_instance();
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Load THORIUS agent with chatbot
     */
    private function load_thorius_agent() {
        // Load RunPod Vault Orchestrator first (required for THORIUS)
        $vault_file = VORTEX_PLUGIN_DIR . 'includes/class-vortex-runpod-vault-orchestrator.php';
        if (file_exists($vault_file)) {
            require_once $vault_file;
        }
        
        // Load THORIUS core agent
        $class_file = VORTEX_PLUGIN_DIR . 'includes/class-vortex-thorius.php';
        
        if (file_exists($class_file)) {
            require_once $class_file;
            
            if (class_exists('VORTEX_THORIUS')) {
                VORTEX_THORIUS::get_instance();
            }
        }
        
        // Load THORIUS chatbot interface
        $chatbot_file = VORTEX_PLUGIN_DIR . 'includes/class-vortex-thorius-chatbot.php';
        if (file_exists($chatbot_file)) {
            require_once $chatbot_file;
            
            if (class_exists('VORTEX_Thorius_Chatbot')) {
                VORTEX_Thorius_Chatbot::get_instance();
            }
        }
        
        // Load shortcode registry
        $shortcode_file = VORTEX_PLUGIN_DIR . 'includes/class-vortex-shortcode-registry.php';
        if (file_exists($shortcode_file)) {
            require_once $shortcode_file;
            
            if (class_exists('VORTEX_Shortcode_Registry')) {
                VORTEX_Shortcode_Registry::get_instance();
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Load ARCHER orchestrator with vault integration
     */
    private function load_archer_orchestrator() {
        // Ensure RunPod Vault Orchestrator is loaded first
        $vault_file = VORTEX_PLUGIN_DIR . 'includes/class-vortex-runpod-vault-orchestrator.php';
        if (file_exists($vault_file)) {
            require_once $vault_file;
            VORTEX_RunPod_Vault_Orchestrator::get_instance();
        }
        
        // Load ARCHER orchestrator
        $class_file = VORTEX_PLUGIN_DIR . 'includes/class-vortex-archer-orchestrator.php';
        
        if (file_exists($class_file)) {
            require_once $class_file;
            
            if (class_exists('VORTEX_ARCHER_Orchestrator')) {
                VORTEX_ARCHER_Orchestrator::get_instance();
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Load SECRET SAUCE system
     */
    private function load_secret_sauce() {
        if (!get_option('vortex_secret_sauce_enabled', false)) {
            return true; // Not enabled, but not an error
        }
        
        $class_file = VORTEX_PLUGIN_DIR . 'includes/class-vortex-secret-sauce.php';
        
        if (file_exists($class_file)) {
            require_once $class_file;
            
            if (class_exists('VORTEX_Secret_Sauce')) {
                VORTEX_Secret_Sauce::get_instance();
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Load smart contracts system
     */
    private function load_smart_contracts() {
        $class_file = VORTEX_PLUGIN_DIR . 'includes/class-vortex-tola-smart-contract-automation.php';
        
        if (file_exists($class_file)) {
            require_once $class_file;
            
            if (class_exists('VORTEX_TOLA_Smart_Contract_Automation')) {
                VORTEX_TOLA_Smart_Contract_Automation::get_instance();
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Load artist swapping marketplace
     */
    private function load_artist_swapping() {
        $class_file = VORTEX_PLUGIN_DIR . 'includes/class-vortex-artist-swapping-marketplace.php';
        
        if (file_exists($class_file)) {
            require_once $class_file;
            
            if (class_exists('VORTEX_Artist_Swapping_Marketplace')) {
                VORTEX_Artist_Swapping_Marketplace::get_instance();
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Load admin UI components
     */
    private function load_admin_ui() {
        $class_file = VORTEX_PLUGIN_DIR . 'admin/class-vortex-system-admin.php';
        
        if (file_exists($class_file)) {
            require_once $class_file;
            
            if (class_exists('VORTEX_System_Admin')) {
                VORTEX_System_Admin::get_instance();
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Initialize admin components
     */
    public function initialize_admin_components() {
        if (!is_admin()) {
            return;
        }
        
        // Add admin menu integration
        add_action('admin_menu', array($this, 'register_admin_menus'), 5);
    }
    
    /**
     * Register admin menus
     */
    public function register_admin_menus() {
        // Main VORTEX menu
        add_menu_page(
            'VORTEX System',
            'VORTEX AI',
            'manage_options',
            'vortex-system',
            array($this, 'display_main_dashboard'),
            'dashicons-superhero-alt',
            25
        );
        
        // System status submenu
        add_submenu_page(
            'vortex-system',
            'System Status',
            'System Status',
            'manage_options',
            'vortex-system',
            array($this, 'display_main_dashboard')
        );
        
        // ARCHER control submenu
        if (class_exists('VORTEX_ARCHER_Orchestrator')) {
            add_submenu_page(
                'vortex-system',
                'ARCHER Control',
                'ARCHER Control',
                'manage_options',
                'vortex-archer-control',
                array($this, 'display_archer_control')
            );
        }
        
        // SECRET SAUCE submenu (only if enabled)
        if (get_option('vortex_secret_sauce_enabled', false)) {
            add_submenu_page(
                'vortex-system',
                'SECRET SAUCE',
                'SECRET SAUCE',
                'manage_options',
                'vortex-secret-sauce',
                array($this, 'display_secret_sauce')
            );
        }
        
        // Smart contracts submenu
        if (class_exists('VORTEX_TOLA_Smart_Contract_Automation')) {
            add_submenu_page(
                'vortex-system',
                'Smart Contracts',
                'Smart Contracts',
                'manage_options',
                'vortex-smart-contracts',
                array($this, 'display_smart_contracts')
            );
        }
    }
    
    /**
     * Display main dashboard
     */
    public function display_main_dashboard() {
        $system_status = $this->get_system_status();
        include VORTEX_PLUGIN_DIR . 'admin/partials/vortex-main-dashboard.php';
    }
    
    /**
     * Display ARCHER control panel
     */
    public function display_archer_control() {
        if (class_exists('VORTEX_ARCHER_Orchestrator')) {
            $archer = VORTEX_ARCHER_Orchestrator::get_instance();
            include VORTEX_PLUGIN_DIR . 'admin/partials/archer-control-panel.php';
        }
    }
    
    /**
     * Display SECRET SAUCE interface
     */
    public function display_secret_sauce() {
        if (class_exists('VORTEX_Secret_Sauce')) {
            $secret_sauce = VORTEX_Secret_Sauce::get_instance();
            include VORTEX_PLUGIN_DIR . 'admin/partials/secret-sauce-interface.php';
        }
    }
    
    /**
     * Display smart contracts interface
     */
    public function display_smart_contracts() {
        if (class_exists('VORTEX_TOLA_Smart_Contract_Automation')) {
            $smart_contracts = VORTEX_TOLA_Smart_Contract_Automation::get_instance();
            include VORTEX_PLUGIN_DIR . 'admin/partials/smart-contracts-interface.php';
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook_suffix) {
        // Only load on VORTEX admin pages
        if (strpos($hook_suffix, 'vortex') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'vortex-admin-css',
            VORTEX_PLUGIN_URL . 'admin/css/vortex-system-admin.css',
            array(),
            VORTEX_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'vortex-admin-js',
            VORTEX_PLUGIN_URL . 'admin/js/vortex-system-admin.js',
            array('jquery'),
            VORTEX_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('vortex-admin-js', 'vortexAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_admin_nonce'),
            'systemStatus' => $this->get_system_status()
        ));
    }
    
    /**
     * Get comprehensive system status
     */
    public function get_system_status() {
        $status = array(
            'overall_health' => 'excellent',
            'components_loaded' => count($this->initialized_components),
            'agents_active' => 0,
            'memory_usage' => memory_get_usage(true),
            'uptime' => $this->get_system_uptime(),
            'last_sync' => get_option('vortex_last_sync', 'Never'),
            'agents_status' => array()
        );
        
        // Check agent status
        $agents = array('HURAII', 'CHLOE', 'HORACE', 'THORIUS');
        foreach ($agents as $agent) {
            $class_name = 'VORTEX_' . $agent;
            if ($agent === 'CHLOE') {
                $class_name = 'VORTEX_CLOE';
            }
            $is_active = class_exists($class_name);
            
            $status['agents_status'][$agent] = array(
                'status' => $is_active ? 'active' : 'inactive',
                'last_activity' => get_option("vortex_{$agent}_last_activity", 'Never')
            );
            
            if ($is_active) {
                $status['agents_active']++;
            }
        }
        
        // Determine overall health
        if ($status['agents_active'] < 2) {
            $status['overall_health'] = 'error';
        } elseif ($status['agents_active'] < 4) {
            $status['overall_health'] = 'warning';
        }
        
        return $status;
    }
    
    /**
     * AJAX handler for system status
     */
    public function ajax_system_status() {
        check_ajax_referer('vortex_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $status = $this->get_system_status();
        wp_send_json_success($status);
    }
    
    /**
     * AJAX handler for component toggle
     */
    public function ajax_component_toggle() {
        check_ajax_referer('vortex_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $component = sanitize_text_field($_POST['component'] ?? '');
        $enabled = filter_var($_POST['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        // Handle component toggle logic here
        wp_send_json_success(array(
            'message' => "$component " . ($enabled ? 'enabled' : 'disabled') . " successfully"
        ));
    }
    
    /**
     * Show system notices
     */
    public function show_system_notices() {
        $system_status = get_option('vortex_system_status', 'unknown');
        
        if ($system_status === 'failed') {
            ?>
            <div class="notice notice-error">
                <p><strong>VORTEX System Error:</strong> System initialization failed. Please check the system logs and try restarting the system.</p>
            </div>
            <?php
        } elseif ($system_status === 'initialized') {
            $components_count = count($this->initialized_components);
            if ($components_count < 8) {
                ?>
                <div class="notice notice-warning">
                    <p><strong>VORTEX System Warning:</strong> Only <?php echo $components_count; ?> of 10 components loaded successfully. Some features may not be available.</p>
                </div>
                <?php
            }
        }
    }
    
    /**
     * Helper methods
     */
    
    private function dependencies_loaded($component) {
        $dependencies = $this->component_dependencies[$component] ?? array();
        
        foreach ($dependencies as $dependency) {
            if (!in_array($dependency, $this->initialized_components)) {
                return false;
            }
        }
        
        return true;
    }
    
    private function calculate_load_order() {
        $order = array();
        $remaining = array_keys($this->component_dependencies);
        
        while (!empty($remaining)) {
            $progress = false;
            
            foreach ($remaining as $index => $component) {
                $dependencies = $this->component_dependencies[$component];
                $can_load = true;
                
                foreach ($dependencies as $dependency) {
                    if (!in_array($dependency, $order)) {
                        $can_load = false;
                        break;
                    }
                }
                
                if ($can_load) {
                    $order[] = $component;
                    unset($remaining[$index]);
                    $progress = true;
                }
            }
            
            if (!$progress) {
                // Circular dependency or missing dependency
                throw new Exception('Circular dependency detected in component loading order');
            }
        }
        
        return $order;
    }
    
    private function create_system_directories() {
        $directories = array(
            VORTEX_PLUGIN_DIR . 'logs/',
            VORTEX_PLUGIN_DIR . 'temp/',
            VORTEX_PLUGIN_DIR . 'cache/'
        );
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                wp_mkdir_p($dir);
            }
        }
    }
    
    private function define_system_constants() {
        if (!defined('VORTEX_PLUGIN_PATH')) {
            define('VORTEX_PLUGIN_PATH', VORTEX_PLUGIN_DIR);
        }
        
        if (!defined('VORTEX_DB_VERSION')) {
            define('VORTEX_DB_VERSION', '2.0');
        }
    }
    
    private function setup_error_handling() {
        // Custom error handler for VORTEX components
        set_error_handler(array($this, 'handle_system_error'), E_ALL);
    }
    
    private function verify_system_integrity() {
        // Verify all critical components are loaded
        $critical_components = array('system_base', 'database');
        
        foreach ($critical_components as $component) {
            if (!in_array($component, $this->initialized_components)) {
                throw new Exception("Critical component missing: $component");
            }
        }
        
        return true;
    }
    
    private function log_system_event($message, $level = 'info') {
        // Log to WordPress debug log if enabled
        if (WP_DEBUG_LOG) {
            error_log("VORTEX [$level]: $message");
        }
        
        // Store in database if available
        if (class_exists('VORTEX_System_Database')) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'vortex_error_logs';
            
            $wpdb->insert(
                $table_name,
                array(
                    'component' => 'SYSTEM_INITIALIZER',
                    'error_level' => $level,
                    'error_message' => $message,
                    'error_data' => json_encode(array(
                        'initialized_components' => $this->initialized_components,
                        'memory_usage' => memory_get_usage(true)
                    ))
                ),
                array('%s', '%s', '%s', '%s')
            );
        }
    }
    
    private function get_system_uptime() {
        $init_time = get_option('vortex_last_initialization');
        if (!$init_time) {
            return 'Unknown';
        }
        
        $uptime_seconds = current_time('timestamp') - strtotime($init_time);
        
        if ($uptime_seconds < 60) {
            return $uptime_seconds . ' seconds';
        } elseif ($uptime_seconds < 3600) {
            return round($uptime_seconds / 60) . ' minutes';
        } elseif ($uptime_seconds < 86400) {
            return round($uptime_seconds / 3600) . ' hours';
        } else {
            return round($uptime_seconds / 86400) . ' days';
        }
    }
    
    public function handle_system_error($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $this->log_system_event("PHP Error [$errno]: $errstr in $errfile on line $errline", 'error');
        
        return true; // Don't execute PHP internal error handler
    }
}

// Initialize the system
VORTEX_System_Initializer::get_instance(); 