<?php
/**
 * Plugin Name: VORTEX SEO Manager
 * Plugin URI: https://www.vortexartec.com
 * Description: Dynamic SEO metadata management for VORTEX ARTEC powered by AI agents and blockchain technology.
 * Version: 1.0.0
 * Author: Marianne NEMS
 * Author URI: https://www.vortexartec.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: vortex-seo
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * 
 * @package VORTEX_SEO
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VORTEX_SEO_VERSION', '1.0.0');
define('VORTEX_SEO_PLUGIN_FILE', __FILE__);
define('VORTEX_SEO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VORTEX_SEO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VORTEX_SEO_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main VORTEX SEO Plugin Class
 */
class VORTEX_SEO_Plugin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('VORTEX_SEO_Plugin', 'uninstall'));
        
        add_action('plugins_loaded', array($this, 'init'));
        add_action('init', array($this, 'load_textdomain'));
        
        // Admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // Plugin action links
        add_filter('plugin_action_links_' . VORTEX_SEO_PLUGIN_BASENAME, array($this, 'plugin_action_links'));
    }
    
    private function load_dependencies() {
        // Load SEO Manager
        require_once VORTEX_SEO_PLUGIN_DIR . 'includes/class-vortex-seo-manager.php';
        
        // Load admin interface (if in admin)
        if (is_admin()) {
            require_once VORTEX_SEO_PLUGIN_DIR . 'admin/class-vortex-seo-admin.php';
        }
        
        // Load public interface (if not admin)
        if (!is_admin()) {
            require_once VORTEX_SEO_PLUGIN_DIR . 'public/class-vortex-seo-public.php';
        }
    }
    
    public function init() {
        // Check if VORTEX main plugin is active
        if (!$this->is_vortex_active()) {
            add_action('admin_notices', array($this, 'vortex_missing_notice'));
            return;
        }
        
        // Initialize components
        $this->init_components();
        
        // Hook into VORTEX system
        $this->integrate_with_vortex();
    }
    
    private function init_components() {
        // SEO Manager is already initialized in the class file
        
        if (is_admin()) {
            new VORTEX_SEO_Admin();
        } else {
            new VORTEX_SEO_Public();
        }
    }
    
    private function integrate_with_vortex() {
        // Add SEO metadata to VORTEX API responses
        add_filter('vortex_api_response', array($this, 'add_seo_to_api'), 10, 2);
        
        // Add SEO data to VORTEX agent responses
        add_filter('vortex_agent_response', array($this, 'enhance_agent_seo'), 10, 3);
        
        // Hook into VORTEX artwork pages for dynamic SEO
        add_action('vortex_artwork_view', array($this, 'generate_artwork_seo'), 10, 1);
        
        // Hook into VORTEX artist pages
        add_action('vortex_artist_view', array($this, 'generate_artist_seo'), 10, 1);
    }
    
    public function add_seo_to_api($response, $endpoint) {
        if (strpos($endpoint, 'page-meta') !== false) {
            $path = $_GET['path'] ?? '/';
            $response['seo'] = vortex_seo_get_meta($path);
        }
        return $response;
    }
    
    public function enhance_agent_seo($response, $agent, $query) {
        // Add SEO recommendations from AI agents
        if ($agent === 'CLOE' && strpos($query, 'seo') !== false) {
            $response['seo_recommendations'] = array(
                'title_suggestions' => array(
                    'AI-Powered Art Discovery | CLOE Agent - VORTEX ARTEC',
                    'Discover Art with AI | CLOE Recommendations'
                ),
                'description_suggestions' => array(
                    'Let CLOE, our AI art discovery agent, help you find the perfect artwork. Advanced AI analysis meets human creativity.',
                    'Experience personalized art recommendations powered by CLOE AI agent on VORTEX ARTEC marketplace.'
                )
            );
        }
        
        return $response;
    }
    
    public function generate_artwork_seo($artwork_id) {
        // Generate dynamic SEO for artwork pages
        $artwork = get_post($artwork_id);
        if (!$artwork) return;
        
        $custom_meta = array(
            'title' => $artwork->post_title . ' | Digital Art - VORTEX ARTEC',
            'description' => wp_trim_words($artwork->post_content, 25) . ' Discover this unique digital artwork on VORTEX ARTEC marketplace.',
            'ogImage' => get_the_post_thumbnail_url($artwork_id, 'large'),
            'canonical' => get_permalink($artwork_id),
            'keywords' => 'digital art, ' . $artwork->post_title . ', VORTEX ARTEC, NFT'
        );
        
        // Cache the metadata
        set_transient('vortex_seo_artwork_' . $artwork_id, $custom_meta, 3600);
    }
    
    public function generate_artist_seo($artist_id) {
        // Generate dynamic SEO for artist pages
        $artist = get_user_by('id', $artist_id);
        if (!$artist) return;
        
        $custom_meta = array(
            'title' => $artist->display_name . ' | Digital Artist - VORTEX ARTEC',
            'description' => 'Explore artworks by ' . $artist->display_name . ' on VORTEX ARTEC. Discover unique digital art and AI-enhanced creations.',
            'ogImage' => get_avatar_url($artist_id, array('size' => 400)),
            'canonical' => get_author_posts_url($artist_id),
            'keywords' => 'digital artist, ' . $artist->display_name . ', VORTEX ARTEC, art portfolio'
        );
        
        // Cache the metadata
        set_transient('vortex_seo_artist_' . $artist_id, $custom_meta, 3600);
    }
    
    private function is_vortex_active() {
        return class_exists('VORTEX_AI_Marketplace') || 
               is_plugin_active('vortex-ai-marketplace/vortex-ai-marketplace.php') ||
               function_exists('vortex_ai_init');
    }
    
    public function load_textdomain() {
        load_plugin_textdomain(
            'vortex-seo',
            false,
            dirname(VORTEX_SEO_PLUGIN_BASENAME) . '/languages/'
        );
    }
    
    public function activate() {
        // Set default options
        add_option('vortex_seo_api_url', 'https://api.vortexartec.com/api/page-meta');
        add_option('vortex_seo_cache_duration', 3600);
        add_option('vortex_seo_version', VORTEX_SEO_VERSION);
        
        // Create database tables if needed
        $this->create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Schedule cleanup cron
        if (!wp_next_scheduled('vortex_seo_cleanup')) {
            wp_schedule_event(time(), 'daily', 'vortex_seo_cleanup');
        }
    }
    
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('vortex_seo_cleanup');
        
        // Clear cache
        $this->clear_all_cache();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public static function uninstall() {
        // Remove options
        delete_option('vortex_seo_api_url');
        delete_option('vortex_seo_cache_duration');
        delete_option('vortex_seo_version');
        
        // Clear all transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vortex_seo_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_vortex_seo_%'");
        
        // Drop custom tables if any
        // $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}vortex_seo_cache");
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // SEO cache table (optional - using transients for now)
        /*
        $table_name = $wpdb->prefix . 'vortex_seo_cache';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            path varchar(255) NOT NULL,
            metadata longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY path (path),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        */
    }
    
    private function clear_all_cache() {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vortex_seo_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_vortex_seo_%'");
    }
    
    public function admin_notices() {
        // Show notices if needed
    }
    
    public function vortex_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php _e('VORTEX SEO Manager', 'vortex-seo'); ?></strong>
                <?php _e('requires the VORTEX AI Marketplace plugin to be installed and activated.', 'vortex-seo'); ?>
            </p>
        </div>
        <?php
    }
    
    public function plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=vortex-seo') . '">' . __('Settings', 'vortex-seo') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

// Initialize the plugin
VORTEX_SEO_Plugin::get_instance();

// Cleanup cron job
add_action('vortex_seo_cleanup', function() {
    global $wpdb;
    // Clean expired transients
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_vortex_seo_%' AND option_value < UNIX_TIMESTAMP()");
});

// Helper functions
if (!function_exists('vortex_seo_get_meta')) {
    function vortex_seo_get_meta($path = null) {
        if (!$path) {
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        }
        
        $seo_manager = new Vortex_SEO_Manager();
        return $seo_manager->get_page_metadata($path);
    }
}

if (!function_exists('vortex_seo_clear_cache')) {
    function vortex_seo_clear_cache($path = null) {
        if ($path) {
            delete_transient('vortex_seo_meta_' . md5($path));
        } else {
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vortex_seo_%'");
        }
    }
} 