<?php
/**
 * Plugin Name: VORTEX TOLA-ART Daily Generation
 * Plugin URI: https://github.com/MarianneNems/vortex-artec-ai-marketplace
 * Description: AI-powered daily art generation with dual royalty structure: First Sale (5% creator + 95% artists), Resale (5% creator + 15% artists + 80% owner/reseller). Features HURAII AI generation, smart contract automation, and TOLA token integration.
 * Version: 2.1.0
 * Author: Marianne Nems - VORTEX ARTEC
 * Author URI: https://vortexartec.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: vortex-tola-art
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * 
 * TOLA-ART ROYALTY STRUCTURE:
 * First Sale: 5% Creator + 95% Participating Artists + 0% Marketplace Fee
 * Resale: 5% Creator + 15% Artists + 80% Owner/Reseller + 0% Marketplace Fee
 * 
 * @package VortexTOLAArt
 * @version 2.1.0
 * @author Marianne Nems
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VORTEX_TOLA_ART_VERSION', '2.1.0');
define('VORTEX_TOLA_ART_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VORTEX_TOLA_ART_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('VORTEX_TOLA_ART_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Define royalty structure constants
define('VORTEX_TOLA_CREATOR_ROYALTY', 5); // 5% to Marianne Nems (all sales)
define('VORTEX_TOLA_FIRST_SALE_ARTIST_SHARE', 95); // 95% to artists (first sale)
define('VORTEX_TOLA_RESALE_ARTIST_SHARE', 15); // 15% to artists (resale)
define('VORTEX_TOLA_RESALE_OWNER_SHARE', 80); // 80% to owner/reseller (resale)

/**
 * Main Plugin Class
 */
class Vortex_TOLA_Art_Plugin {
    
    /**
     * Single instance of the plugin
     */
    private static $instance = null;
    
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
        add_filter('plugin_action_links_' . VORTEX_TOLA_ART_PLUGIN_BASENAME, array($this, 'plugin_action_links'));
        
        // Load plugin
        $this->load_dependencies();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('vortex-tola-art', false, dirname(VORTEX_TOLA_ART_PLUGIN_BASENAME) . '/languages');
        
        // Initialize components
        $this->init_components();
        
        // Admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            add_action('admin_notices', array($this, 'wordpress_version_notice'));
            return;
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return;
        }
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Core classes
        require_once VORTEX_TOLA_ART_PLUGIN_PATH . 'includes/class-vortex-tola-art-daily-automation.php';
        require_once VORTEX_TOLA_ART_PLUGIN_PATH . 'includes/class-vortex-tola-smart-contract.php';
        require_once VORTEX_TOLA_ART_PLUGIN_PATH . 'includes/class-vortex-tola-admin.php';
        require_once VORTEX_TOLA_ART_PLUGIN_PATH . 'includes/class-vortex-huraii-integration.php';
        
        // Admin classes
        if (is_admin()) {
            require_once VORTEX_TOLA_ART_PLUGIN_PATH . 'admin/class-vortex-tola-admin-menus.php';
        }
        
        // Public classes
        require_once VORTEX_TOLA_ART_PLUGIN_PATH . 'public/class-vortex-tola-public.php';
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize daily automation
        Vortex_TOLA_Art_Daily_Automation::get_instance();
        
        // Initialize admin interface
        if (is_admin()) {
            Vortex_TOLA_Admin_Menus::get_instance();
        }
        
        // Initialize public interface
        Vortex_TOLA_Public::get_instance();
        
        // Initialize HURAII integration
        Vortex_HURAII_Integration::get_instance();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->create_database_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Schedule daily automation
        $this->schedule_daily_automation();
        
        // Set activation flag
        update_option('vortex_tola_art_activated', true);
        update_option('vortex_tola_art_version', VORTEX_TOLA_ART_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        $this->activated = true;
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled hooks
        wp_clear_scheduled_hook('vortex_daily_art_generation');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set deactivation flag
        update_option('vortex_tola_art_activated', false);
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Daily art table
        $daily_art_table = $wpdb->prefix . 'vortex_daily_art';
        $daily_art_sql = "CREATE TABLE IF NOT EXISTS {$daily_art_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            artwork_id bigint(20) UNSIGNED DEFAULT NULL,
            prompt longtext NOT NULL,
            generation_settings longtext DEFAULT NULL,
            huraii_response longtext DEFAULT NULL,
            marketplace_listing_id bigint(20) UNSIGNED DEFAULT NULL,
            smart_contract_address varchar(42) DEFAULT NULL,
            generation_status enum('pending','generating','completed','failed','listed') DEFAULT 'pending',
            total_sales decimal(18,8) UNSIGNED DEFAULT 0,
            royalties_distributed decimal(18,8) UNSIGNED DEFAULT 0,
            participating_artists_count int UNSIGNED DEFAULT 0,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_date (date),
            KEY artwork_id (artwork_id),
            KEY generation_status (generation_status)
        ) $charset_collate;";
        
        // Artist participation table
        $participation_table = $wpdb->prefix . 'vortex_artist_participation';
        $participation_sql = "CREATE TABLE IF NOT EXISTS {$participation_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            wallet_address varchar(42) NOT NULL,
            participation_date date NOT NULL,
            daily_art_id bigint(20) UNSIGNED NOT NULL,
            participation_weight decimal(10,4) UNSIGNED DEFAULT 1.0000,
            royalty_share decimal(18,8) UNSIGNED DEFAULT 0,
            payment_status enum('pending','processing','completed','failed') DEFAULT 'pending',
            payment_transaction_hash varchar(66) DEFAULT NULL,
            joined_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_participation (user_id, daily_art_id),
            KEY user_id (user_id),
            KEY daily_art_id (daily_art_id),
            KEY participation_date (participation_date)
        ) $charset_collate;";
        
        // Royalty distribution table
        $royalty_table = $wpdb->prefix . 'vortex_royalty_distribution';
        $royalty_sql = "CREATE TABLE IF NOT EXISTS {$royalty_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            daily_art_id bigint(20) UNSIGNED NOT NULL,
            sale_transaction_hash varchar(66) NOT NULL,
            sale_amount decimal(18,8) UNSIGNED NOT NULL,
            creator_royalty decimal(18,8) UNSIGNED NOT NULL,
            artist_pool decimal(18,8) UNSIGNED NOT NULL,
            marketplace_fee decimal(18,8) UNSIGNED NOT NULL,
            owner_amount decimal(18,8) UNSIGNED DEFAULT 0,
            participating_artists int UNSIGNED NOT NULL,
            individual_artist_share decimal(18,8) UNSIGNED NOT NULL,
            sale_type enum('first_sale','resale') DEFAULT 'first_sale',
            distribution_status enum('pending','processing','completed','failed') DEFAULT 'pending',
            distribution_transaction_hash varchar(66) DEFAULT NULL,
            block_number bigint(20) UNSIGNED DEFAULT NULL,
            gas_used bigint(20) UNSIGNED DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_sale (sale_transaction_hash),
            KEY daily_art_id (daily_art_id),
            KEY sale_type (sale_type),
            KEY distribution_status (distribution_status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($daily_art_sql);
        dbDelta($participation_sql);
        dbDelta($royalty_sql);
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $default_options = array(
            'vortex_tola_creator_wallet' => '',
            'vortex_tola_contract_address' => '',
            'vortex_tola_marketplace_address' => '',
            'vortex_huraii_api_endpoint' => '',
            'vortex_daily_generation_time' => '00:00',
            'vortex_enable_daily_automation' => true,
            'vortex_max_participating_artists' => 50,
            'vortex_default_artwork_price' => 100,
            'vortex_royalty_structure' => array(
                'creator_percentage' => VORTEX_TOLA_CREATOR_ROYALTY,
                'first_sale_artist_percentage' => VORTEX_TOLA_FIRST_SALE_ARTIST_SHARE,
                'resale_artist_percentage' => VORTEX_TOLA_RESALE_ARTIST_SHARE,
                'resale_owner_percentage' => VORTEX_TOLA_RESALE_OWNER_SHARE
            )
        );
        
        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
    
    /**
     * Schedule daily automation
     */
    private function schedule_daily_automation() {
        if (!wp_next_scheduled('vortex_daily_art_generation')) {
            wp_schedule_event(
                strtotime('00:00:00'),
                'daily',
                'vortex_daily_art_generation'
            );
        }
    }
    
    /**
     * Plugin action links
     */
    public function plugin_action_links($links) {
        $action_links = array(
            'settings' => '<a href="' . admin_url('admin.php?page=vortex-tola-art') . '">' . __('Settings', 'vortex-tola-art') . '</a>',
            'dashboard' => '<a href="' . admin_url('admin.php?page=vortex-tola-dashboard') . '">' . __('Dashboard', 'vortex-tola-art') . '</a>',
        );
        
        return array_merge($action_links, $links);
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        if ($this->activated && get_option('vortex_tola_art_activated')) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong><?php _e('VORTEX TOLA-ART activated successfully!', 'vortex-tola-art'); ?></strong><br>
                    <?php _e('Royalty Structure: First Sale (5% creator + 95% artists) | Resale (5% creator + 15% artists + 80% owner)', 'vortex-tola-art'); ?><br>
                    <a href="<?php echo admin_url('admin.php?page=vortex-tola-art'); ?>"><?php _e('Configure Settings', 'vortex-tola-art'); ?></a> | 
                    <a href="<?php echo admin_url('admin.php?page=vortex-tola-dashboard'); ?>"><?php _e('View Dashboard', 'vortex-tola-art'); ?></a>
                </p>
            </div>
            <?php
            delete_option('vortex_tola_art_activated');
        }
    }
    
    /**
     * WordPress version notice
     */
    public function wordpress_version_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php _e('VORTEX TOLA-ART requires WordPress 5.0 or higher.', 'vortex-tola-art'); ?></strong><br>
                <?php printf(__('You are running WordPress %s. Please upgrade to activate this plugin.', 'vortex-tola-art'), get_bloginfo('version')); ?>
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
                <strong><?php _e('VORTEX TOLA-ART requires PHP 7.4 or higher.', 'vortex-tola-art'); ?></strong><br>
                <?php printf(__('You are running PHP %s. Please upgrade to activate this plugin.', 'vortex-tola-art'), PHP_VERSION); ?>
            </p>
        </div>
        <?php
    }
}

/**
 * Initialize the plugin
 */
function vortex_tola_art_init() {
    return Vortex_TOLA_Art_Plugin::get_instance();
}

// Start the plugin
vortex_tola_art_init();

/**
 * Plugin information for WordPress.org
 */
if (!function_exists('vortex_tola_art_plugin_info')) {
    function vortex_tola_art_plugin_info() {
        return array(
            'name' => 'VORTEX TOLA-ART Daily Generation',
            'version' => VORTEX_TOLA_ART_VERSION,
            'description' => 'AI-powered daily art generation with dual royalty structure',
            'author' => 'Marianne Nems - VORTEX ARTEC',
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
            ),
            'features' => array(
                'HURAII AI Art Generation',
                'Smart Contract Automation',
                'TOLA Token Integration',
                'Dual Royalty Structure',
                'Artist Participation Tracking',
                'Blockchain Verification',
                'Daily Automation',
                'Admin Dashboard'
            )
        );
    }
} 