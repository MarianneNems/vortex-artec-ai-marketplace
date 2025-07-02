<?php
/**
 * Plugin Name: Vortex AI Engine
 * Plugin URI: https://github.com/MarianneNems/vortex-artec-ai-marketplace
 * Description: Complete AI-powered marketplace engine featuring ARCHER Orchestrator, HURAII/CLOE/HORACE/THORIUS agents, TOLA-ART daily generation, smart contract automation, artist journey management, and comprehensive blockchain integration. Dual royalty structure: First Sale (5% creator + 95% artists) | Resale (5% creator + 15% artists + 80% owner/reseller).
 * Version: 3.0.0
 * Author: Marianne Nems - VORTEX ARTEC
 * Author URI: https://vortexartec.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: vortex-ai-engine
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * 
 * COMPREHENSIVE AI ENGINE FEATURES:
 * ✅ ARCHER Orchestrator - Master AI coordination system
 * ✅ HURAII - GPU-powered generative AI (Stable Diffusion, Image Generation)
 * ✅ CLOE - Market analysis and collector matching
 * ✅ HORACE - Content optimization and SEO
 * ✅ THORIUS - Platform guide and security monitoring
 * ✅ TOLA-ART Daily Generation - Automated art creation with dual royalty structure
 * ✅ Smart Contract Automation - Blockchain-verified transactions
 * ✅ Artist Journey Management - Complete onboarding and tracking
 * ✅ Secret Sauce System - Proprietary algorithms with zodiac personalization
 * ✅ RunPod Vault Integration - Secure AI processing with GPU/CPU optimization
 * ✅ Blockchain Integration - TOLA token, Solana, smart contracts
 * ✅ Subscription Management - Starter ($29), Pro ($59), Studio ($99)
 * ✅ Admin Dashboard - Real-time monitoring and control
 * 
 * TOLA-ART Daily ROYALTY STRUCTURE:
 * First Sale: 5% Creator + 95% Participating Artists + 0% Marketplace Fee
 * Resale: 5% Creator + 15% Artists + 80% Owner/Reseller + 0% Marketplace Fee
 * 
 * MARKETPLACE FEE STRUCTURE:
 * ✅ Swap Fee: $3 per transaction
 * ✅ Sale Transaction Fee: $89 per sale
 * ✅ Commission: 15% on all sales
 * ✅ Wallet Management: 0.5% monthly fee
 * ✅ Subscription Tiers: Starter ($29), Pro ($59), Studio ($99)
 * 
 * ROYALTY STRUCTURE (After 15% marketplace commission):
 * First Sale: 5% Creator + 80% Participating Artists + 15% Marketplace
 * Resale: 5% Creator + 10% Artists + 70% Owner/Reseller + 15% Marketplace
 * 
 * @package VortexAIEngine
 * @version 3.0.0
 * @author Marianne Nems
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VORTEX_AI_ENGINE_VERSION', '3.0.0');
define('VORTEX_AI_ENGINE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VORTEX_AI_ENGINE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('VORTEX_AI_ENGINE_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Define AI system constants
define('VORTEX_ARCHER_SYNC_INTERVAL', 5); // 5-second sync intervals
define('VORTEX_HURAII_GPU_ENABLED', true);
define('VORTEX_RUNPOD_VAULT_ENABLED', true);
define('VORTEX_SECRET_SAUCE_ENABLED', true);

// Define marketplace fee constants
define('VORTEX_MARKETPLACE_SWAP_FEE', 3.00); // $3 per swap
define('VORTEX_MARKETPLACE_SALE_TRANSACTION_FEE', 89.00); // $89 per sale transaction
define('VORTEX_MARKETPLACE_COMMISSION_RATE', 15.00); // 15% commission on sales
define('VORTEX_MARKETPLACE_WALLET_MANAGEMENT_FEE', 0.5); // 0.5% monthly wallet management fee

// Define TOLA-ART royalty constants (adjusted for marketplace fees)
define('VORTEX_TOLA_CREATOR_ROYALTY', 5); // 5% to Marianne Nems (all sales)
define('VORTEX_TOLA_FIRST_SALE_ARTIST_SHARE', 80); // 80% to artists (first sale, after 15% commission)
define('VORTEX_TOLA_RESALE_ARTIST_SHARE', 10); // 10% to artists (resale, after 15% commission)
define('VORTEX_TOLA_RESALE_OWNER_SHARE', 70); // 70% to owner/reseller (resale, after 15% commission)

// Define subscription pricing
define('VORTEX_SUBSCRIPTION_STARTER', 29.00);
define('VORTEX_SUBSCRIPTION_PRO', 59.00);
define('VORTEX_SUBSCRIPTION_STUDIO', 99.00);

/**
 * Main Vortex AI Engine Class
 */
class Vortex_AI_Engine {
    
    /**
     * Single instance of the plugin
     */
    private static $instance = null;
    
    /**
     * AI agents instances
     */
    private $archer_orchestrator = null;
    private $huraii_agent = null;
    private $cloe_agent = null;
    private $horace_agent = null;
    private $thorius_agent = null;
    
    /**
     * Core systems
     */
    private $tola_art_automation = null;
    private $secret_sauce = null;
    private $artist_journey = null;
    private $runpod_vault = null;
    
    /**
     * Plugin activation flag
     */
    private $activated = false;
    
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
        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Plugin action links
        add_filter('plugin_action_links_' . VORTEX_AI_ENGINE_PLUGIN_BASENAME, array($this, 'plugin_action_links'));
        
        // Load plugin dependencies
        $this->load_dependencies();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('vortex-ai-engine', false, dirname(VORTEX_AI_ENGINE_PLUGIN_BASENAME) . '/languages');
        
        // Initialize all systems
        $this->init_ai_systems();
        $this->init_tola_art_system();
        $this->init_blockchain_systems();
        $this->init_artist_journey();
        $this->init_admin_systems();
        
        // Start AI orchestration
        $this->start_ai_orchestration();
        
        // Admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Version compatibility checks
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            add_action('admin_notices', array($this, 'wordpress_version_notice'));
            return;
        }
        
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return;
        }
    }
    
    /**
     * Load all plugin dependencies
     */
    private function load_dependencies() {
        // Core AI systems
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/ai-agents/class-vortex-archer-orchestrator.php';
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/ai-agents/class-vortex-huraii-agent.php';
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/ai-agents/class-vortex-cloe-agent.php';
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/ai-agents/class-vortex-horace-agent.php';
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/ai-agents/class-vortex-thorius-agent.php';
        
        // TOLA-ART system
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/tola-art/class-vortex-tola-art-daily-automation.php';
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/tola-art/class-vortex-tola-smart-contract-automation.php';
        
        // Secret sauce and proprietary systems
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/secret-sauce/class-vortex-secret-sauce.php';
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/secret-sauce/class-vortex-zodiac-intelligence.php';
        
        // Artist journey and subscription management
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/artist-journey/class-vortex-artist-journey.php';
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/subscriptions/class-vortex-subscription-manager.php';
        
        // RunPod and cloud integration
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/cloud/class-vortex-runpod-vault.php';
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/cloud/class-vortex-gradio-client.php';
        
        // Blockchain and smart contracts
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/blockchain/class-vortex-smart-contract-manager.php';
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/blockchain/class-vortex-tola-token-handler.php';
        
        // Database and storage
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/database/class-vortex-database-manager.php';
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'includes/storage/class-vortex-storage-router.php';
        
        // Admin interfaces
        if (is_admin()) {
            require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'admin/class-vortex-admin-controller.php';
            require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'admin/class-vortex-admin-dashboard.php';
        }
        
        // Public interfaces
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/class-vortex-public-interface.php';
        require_once VORTEX_AI_ENGINE_PLUGIN_PATH . 'public/class-vortex-marketplace-frontend.php';
    }
    
    /**
     * Initialize AI systems
     */
    private function init_ai_systems() {
        // Initialize ARCHER Orchestrator (master coordinator)
        $this->archer_orchestrator = Vortex_ARCHER_Orchestrator::get_instance();
        
        // Initialize all AI agents
        $this->huraii_agent = Vortex_HURAII_Agent::get_instance(); // GPU generative AI
        $this->cloe_agent = Vortex_CLOE_Agent::get_instance(); // Market analysis
        $this->horace_agent = Vortex_HORACE_Agent::get_instance(); // Content optimization
        $this->thorius_agent = Vortex_THORIUS_Agent::get_instance(); // Platform guide
        
        // Register agents with orchestrator
        if ($this->archer_orchestrator) {
            $this->archer_orchestrator->register_agent('HURAII', $this->huraii_agent);
            $this->archer_orchestrator->register_agent('CLOE', $this->cloe_agent);
            $this->archer_orchestrator->register_agent('HORACE', $this->horace_agent);
            $this->archer_orchestrator->register_agent('THORIUS', $this->thorius_agent);
        }
    }
    
    /**
     * Initialize TOLA-ART system
     */
    private function init_tola_art_system() {
        // Initialize daily art automation
        $this->tola_art_automation = Vortex_TOLA_Art_Daily_Automation::get_instance();
        
        // Initialize smart contract automation
        Vortex_TOLA_Smart_Contract_Automation::get_instance();
    }
    
    /**
     * Initialize blockchain systems
     */
    private function init_blockchain_systems() {
        // Initialize smart contract manager
        Vortex_Smart_Contract_Manager::get_instance();
        
        // Initialize TOLA token handler
        Vortex_TOLA_Token_Handler::get_instance();
    }
    
    /**
     * Initialize artist journey
     */
    private function init_artist_journey() {
        // Initialize artist journey management
        $this->artist_journey = Vortex_Artist_Journey::get_instance();
        
        // Initialize subscription manager
        Vortex_Subscription_Manager::get_instance();
    }
    
    /**
     * Initialize admin systems
     */
    private function init_admin_systems() {
        if (is_admin()) {
            // Initialize admin controller
            Vortex_Admin_Controller::get_instance();
            
            // Initialize admin dashboard
            Vortex_Admin_Dashboard::get_instance();
        }
        
        // Initialize public interface
        Vortex_Public_Interface::get_instance();
        
        // Initialize marketplace frontend
        Vortex_Marketplace_Frontend::get_instance();
    }
    
    /**
     * Start AI orchestration system
     */
    private function start_ai_orchestration() {
        if ($this->archer_orchestrator) {
            // Start orchestration with 5-second sync intervals
            $this->archer_orchestrator->start_orchestration();
            
            // Initialize secret sauce if enabled
            if (VORTEX_SECRET_SAUCE_ENABLED) {
                $this->secret_sauce = Vortex_Secret_Sauce::get_instance();
                $this->archer_orchestrator->register_system('SECRET_SAUCE', $this->secret_sauce);
            }
            
            // Initialize RunPod vault if enabled
            if (VORTEX_RUNPOD_VAULT_ENABLED) {
                $this->runpod_vault = Vortex_RunPod_Vault::get_instance();
                $this->archer_orchestrator->register_system('RUNPOD_VAULT', $this->runpod_vault);
            }
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create all database tables
        $this->create_database_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Schedule automation tasks
        $this->schedule_automation_tasks();
        
        // Initialize RunPod vault
        $this->initialize_runpod_vault();
        
        // Set activation flag
        update_option('vortex_ai_engine_activated', true);
        update_option('vortex_ai_engine_version', VORTEX_AI_ENGINE_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        $this->activated = true;
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear all scheduled hooks
        wp_clear_scheduled_hook('vortex_daily_art_generation');
        wp_clear_scheduled_hook('vortex_archer_orchestration');
        wp_clear_scheduled_hook('vortex_ai_health_check');
        wp_clear_scheduled_hook('vortex_secret_sauce_optimization');
        
        // Stop AI orchestration
        if ($this->archer_orchestrator) {
            $this->archer_orchestrator->stop_orchestration();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set deactivation flag
        update_option('vortex_ai_engine_activated', false);
    }
    
    /**
     * Create all database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // All the database tables we've created throughout the system
        $tables = array(
            // TOLA-ART tables
            'vortex_daily_art',
            'vortex_artist_participation', 
            'vortex_royalty_distribution',
            
            // AI orchestration tables
            'vortex_ai_orchestration_log',
            'vortex_ai_agent_performance',
            'vortex_ai_task_queue',
            
            // Artist journey tables
            'vortex_artist_profiles',
            'vortex_artist_journey_progress',
            'vortex_subscription_tiers',
            
            // Secret sauce tables
            'vortex_secret_sauce_algorithms',
            'vortex_zodiac_intelligence',
            'vortex_seed_art_generation',
            
            // Smart contract tables  
            'vortex_smart_contracts',
            'vortex_blockchain_transactions',
            'vortex_tola_token_operations',
            
            // System monitoring tables
            'vortex_system_health',
            'vortex_performance_metrics',
            'vortex_error_logs'
        );
        
        // Create tables using our existing database manager
        $database_manager = new Vortex_Database_Manager();
        $database_manager->create_all_tables();
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $default_options = array(
            // AI system settings
            'vortex_archer_enabled' => true,
            'vortex_archer_sync_interval' => VORTEX_ARCHER_SYNC_INTERVAL,
            'vortex_huraii_gpu_enabled' => VORTEX_HURAII_GPU_ENABLED,
            'vortex_runpod_vault_enabled' => VORTEX_RUNPOD_VAULT_ENABLED,
            'vortex_secret_sauce_enabled' => VORTEX_SECRET_SAUCE_ENABLED,
            
            // TOLA-ART settings
            'vortex_tola_creator_wallet' => '',
            'vortex_tola_contract_address' => '',
            'vortex_tola_daily_generation_enabled' => true,
            'vortex_tola_royalty_structure' => array(
                'creator_percentage' => VORTEX_TOLA_CREATOR_ROYALTY,
                'first_sale_artist_percentage' => VORTEX_TOLA_FIRST_SALE_ARTIST_SHARE,
                'resale_artist_percentage' => VORTEX_TOLA_RESALE_ARTIST_SHARE,
                'resale_owner_percentage' => VORTEX_TOLA_RESALE_OWNER_SHARE
            ),
            
            // Subscription settings
            'vortex_subscription_starter_price' => VORTEX_SUBSCRIPTION_STARTER,
            'vortex_subscription_pro_price' => VORTEX_SUBSCRIPTION_PRO,
            'vortex_subscription_studio_price' => VORTEX_SUBSCRIPTION_STUDIO,
            
            // API endpoints
            'vortex_huraii_api_endpoint' => '',
            'vortex_runpod_api_key' => '',
            'vortex_gradio_endpoints' => array(),
            
            // Blockchain settings
            'vortex_solana_network' => 'mainnet-beta',
            'vortex_tola_token_address' => '',
            'vortex_smart_contract_factory' => '',
            
            // Performance settings
            'vortex_max_concurrent_tasks' => 10,
            'vortex_cache_duration' => 3600,
            'vortex_optimization_level' => 'high',
            
            // Marketplace fee settings
            'vortex_marketplace_swap_fee' => VORTEX_MARKETPLACE_SWAP_FEE,
            'vortex_marketplace_sale_transaction_fee' => VORTEX_MARKETPLACE_SALE_TRANSACTION_FEE,
            'vortex_marketplace_commission_rate' => VORTEX_MARKETPLACE_COMMISSION_RATE,
            'vortex_marketplace_wallet_management_fee' => VORTEX_MARKETPLACE_WALLET_MANAGEMENT_FEE
        );
        
        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
    
    /**
     * Schedule automation tasks
     */
    private function schedule_automation_tasks() {
        // Daily art generation (midnight)
        if (!wp_next_scheduled('vortex_daily_art_generation')) {
            wp_schedule_event(strtotime('00:00:00'), 'daily', 'vortex_daily_art_generation');
        }
        
        // AI orchestration (every 5 seconds)
        if (!wp_next_scheduled('vortex_archer_orchestration')) {
            wp_schedule_event(time(), 'vortex_five_seconds', 'vortex_archer_orchestration');
        }
        
        // Health check (every 5 minutes)
        if (!wp_next_scheduled('vortex_ai_health_check')) {
            wp_schedule_event(time(), 'vortex_five_minutes', 'vortex_ai_health_check');
        }
        
        // Secret sauce optimization (hourly)
        if (!wp_next_scheduled('vortex_secret_sauce_optimization')) {
            wp_schedule_event(time(), 'hourly', 'vortex_secret_sauce_optimization');
        }
        
        // Add custom schedules
        add_filter('cron_schedules', array($this, 'add_custom_cron_schedules'));
    }
    
    /**
     * Add custom cron schedules
     */
    public function add_custom_cron_schedules($schedules) {
        $schedules['vortex_five_seconds'] = array(
            'interval' => 5,
            'display' => __('Every 5 Seconds', 'vortex-ai-engine')
        );
        
        $schedules['vortex_five_minutes'] = array(
            'interval' => 300,
            'display' => __('Every 5 Minutes', 'vortex-ai-engine')
        );
        
        return $schedules;
    }
    
    /**
     * Initialize RunPod vault
     */
    private function initialize_runpod_vault() {
        if (VORTEX_RUNPOD_VAULT_ENABLED) {
            // Initialize RunPod vault with secure AI processing
            $vault = Vortex_RunPod_Vault::get_instance();
            $vault->initialize_vault();
        }
    }
    
    /**
     * Plugin action links
     */
    public function plugin_action_links($links) {
        $action_links = array(
            'dashboard' => '<a href="' . admin_url('admin.php?page=vortex-ai-dashboard') . '"><strong>' . __('AI Dashboard', 'vortex-ai-engine') . '</strong></a>',
            'tola-art' => '<a href="' . admin_url('admin.php?page=vortex-tola-art') . '">' . __('TOLA-ART', 'vortex-ai-engine') . '</a>',
            'settings' => '<a href="' . admin_url('admin.php?page=vortex-ai-settings') . '">' . __('Settings', 'vortex-ai-engine') . '</a>',
        );
        
        return array_merge($action_links, $links);
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        if ($this->activated && get_option('vortex_ai_engine_activated')) {
            ?>
            <div class="notice notice-success is-dismissible">
                <h3><strong><?php _e('🚀 Vortex AI Engine Activated Successfully!', 'vortex-ai-engine'); ?></strong></h3>
                <div style="background: #f0f8ff; padding: 15px; border-radius: 8px; margin: 10px 0;">
                    <h4 style="margin-top: 0;">✅ AI Systems Initialized:</h4>
                    <ul style="margin: 5px 0; padding-left: 20px;">
                        <li><strong>ARCHER Orchestrator:</strong> Master AI coordination (5-second sync)</li>
                        <li><strong>HURAII:</strong> GPU-powered generative AI</li>
                        <li><strong>CLOE:</strong> Market analysis and collector matching</li>
                        <li><strong>HORACE:</strong> Content optimization and SEO</li>
                        <li><strong>THORIUS:</strong> Platform guide and security</li>
                    </ul>
                    
                    <h4>🎨 TOLA-ART System Ready:</h4>
                    <p style="margin: 5px 0;"><strong>Dual Royalty Structure:</strong> First Sale (5% creator + 95% artists) | Resale (5% creator + 15% artists + 80% owner)</p>
                    
                    <h4>🔗 Quick Actions:</h4>
                    <p style="margin: 5px 0;">
                        <a href="<?php echo admin_url('admin.php?page=vortex-ai-dashboard'); ?>" class="button button-primary">🎛️ AI Dashboard</a>
                        <a href="<?php echo admin_url('admin.php?page=vortex-tola-art'); ?>" class="button">🎨 TOLA-ART</a>
                        <a href="<?php echo admin_url('admin.php?page=vortex-ai-settings'); ?>" class="button">⚙️ Settings</a>
                        <a href="<?php echo admin_url('admin.php?page=vortex-artist-journey'); ?>" class="button">👥 Artist Journey</a>
                    </p>
                </div>
            </div>
            <?php
            delete_option('vortex_ai_engine_activated');
        }
    }
    
    /**
     * WordPress version notice
     */
    public function wordpress_version_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php _e('Vortex AI Engine requires WordPress 5.0 or higher.', 'vortex-ai-engine'); ?></strong><br>
                <?php printf(__('You are running WordPress %s. Please upgrade to activate this plugin.', 'vortex-ai-engine'), get_bloginfo('version')); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * PHP version notice
     */
    public function php_version_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php _e('Vortex AI Engine requires PHP 7.4 or higher.', 'vortex-ai-engine'); ?></strong><br>
                <?php printf(__('You are running PHP %s. Please upgrade to activate this plugin.', 'vortex-ai-engine'), PHP_VERSION); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Get plugin information
     */
    public static function get_plugin_info() {
        return array(
            'name' => 'Vortex AI Engine',
            'version' => VORTEX_AI_ENGINE_VERSION,
            'description' => 'Complete AI-powered marketplace engine with orchestration, automation, and blockchain integration',
            'author' => 'Marianne Nems - VORTEX ARTEC',
            'ai_agents' => array('ARCHER', 'HURAII', 'CLOE', 'HORACE', 'THORIUS'),
            'features' => array(
                'AI Orchestration',
                'TOLA-ART Daily Generation',
                'Smart Contract Automation',
                'Artist Journey Management',
                'Subscription Management',
                'Blockchain Integration',
                'RunPod Vault Processing',
                'Secret Sauce Algorithms',
                'Real-time Analytics',
                'Dual Royalty Structure'
            ),
            'royalty_structure' => array(
                'first_sale' => array(
                    'creator' => '5%',
                    'artists' => '95%',
                    'marketplace' => '0%'
                ),
                'resale' => array(
                    'creator' => '5%',
                    'artists' => '15%',
                    'owner' => '80%'
                )
            )
        );
    }
}

/**
 * Initialize the Vortex AI Engine
 */
function vortex_ai_engine_init() {
    return Vortex_AI_Engine::get_instance();
}

// Start the AI engine
vortex_ai_engine_init();

/**
 * Global function to get AI engine instance
 */
function vortex_ai() {
    return Vortex_AI_Engine::get_instance();
} 