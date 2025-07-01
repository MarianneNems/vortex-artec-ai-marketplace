<?php
/**
 * Plugin Name: Vortex HURAII
 * Plugin URI: https://vortex-ai.com/huraii
 * Description: A powerful AI image generation system for WordPress powered by HURAII technology. Generate stunning artwork directly from your WordPress site.
 * Version: 1.0.0
 * Author: Vortex AI Team
 * Author URI: https://vortex-ai.com
 * Text Domain: vortex-huraii
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package Vortex_HURAII
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VORTEX_HURAII_VERSION', '1.0.0');
define('VORTEX_HURAII_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VORTEX_HURAII_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VORTEX_HURAII_ASSETS_URL', VORTEX_HURAII_PLUGIN_URL . 'assets/');

/**
 * Main plugin class
 */
class Vortex_HURAII {
    /**
     * Singleton instance
     *
     * @var Vortex_HURAII
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return Vortex_HURAII Main instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init'));
        
        // Register activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('vortex-huraii', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Include required files
        $this->includes();
        
        // Register assets
        add_action('wp_enqueue_scripts', array($this, 'register_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'register_admin_assets'));
        
        // Register shortcode
        add_shortcode('huraii', array($this, 'shortcode_callback'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'admin_menu'));
        
        // AJAX handlers
        add_action('wp_ajax_huraii_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_huraii_clear_cache', array($this, 'ajax_clear_cache'));
    }

    /**
     * Include required files
     */
    private function includes() {
        // Admin functions
        if (is_admin()) {
            require_once VORTEX_HURAII_PLUGIN_DIR . 'includes/admin-functions.php';
        }
        
        // Core functions
        require_once VORTEX_HURAII_PLUGIN_DIR . 'includes/core-functions.php';
        
        // API handlers
        require_once VORTEX_HURAII_PLUGIN_DIR . 'includes/api-handlers.php';
    }

    /**
     * Register frontend assets
     */
    public function register_frontend_assets() {
        // Register styles
        wp_register_style(
            'vortex-huraii-frontend',
            VORTEX_HURAII_ASSETS_URL . 'css/frontend.css',
            array(),
            VORTEX_HURAII_VERSION
        );
        
        // HURAII core scripts
        wp_register_script(
            'huraii-lru-cache',
            VORTEX_HURAII_ASSETS_URL . 'js/huraii-components/huraii-lru-cache.js',
            array(),
            VORTEX_HURAII_VERSION,
            true
        );
        
        wp_register_script(
            'huraii-service-worker',
            VORTEX_HURAII_ASSETS_URL . 'js/huraii-components/huraii-service-worker.js',
            array(),
            VORTEX_HURAII_VERSION,
            true
        );
        
        wp_register_script(
            'huraii-api',
            VORTEX_HURAII_ASSETS_URL . 'js/huraii-components/huraii-api.js',
            array('huraii-lru-cache'),
            VORTEX_HURAII_VERSION,
            true
        );
        
        wp_register_script(
            'huraii-learning',
            VORTEX_HURAII_ASSETS_URL . 'js/huraii-components/huraii-learning.js',
            array('huraii-api'),
            VORTEX_HURAII_VERSION,
            true
        );
        
        wp_register_script(
            'huraii-pattern-worker',
            VORTEX_HURAII_ASSETS_URL . 'js/huraii-components/huraii-pattern-worker.js',
            array(),
            VORTEX_HURAII_VERSION,
            true
        );
        
        wp_register_script(
            'huraii-core',
            VORTEX_HURAII_ASSETS_URL . 'js/huraii-components/huraii-core.js',
            array('huraii-api', 'huraii-learning'),
            VORTEX_HURAII_VERSION,
            true
        );
        
        wp_register_script(
            'huraii-ui',
            VORTEX_HURAII_ASSETS_URL . 'js/huraii-components/huraii-ui.js',
            array('huraii-core'),
            VORTEX_HURAII_VERSION,
            true
        );
        
        // Frontend script
        wp_register_script(
            'vortex-huraii-frontend',
            VORTEX_HURAII_ASSETS_URL . 'js/frontend.js',
            array('jquery', 'huraii-ui'),
            VORTEX_HURAII_VERSION,
            true
        );
        
        // Localize script with settings
        wp_localize_script('vortex-huraii-frontend', 'vortexHuraii', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_huraii_nonce'),
            'settings' => $this->get_frontend_settings()
        ));
        
        // Register service worker
        add_action('wp_head', array($this, 'register_service_worker'));
    }

    /**
     * Register service worker
     */
    public function register_service_worker() {
        if (!is_admin()) {
            ?>
            <script>
                if ('serviceWorker' in navigator) {
                    window.addEventListener('load', function() {
                        navigator.serviceWorker.register('<?php echo VORTEX_HURAII_ASSETS_URL; ?>js/huraii-sw.js')
                            .then(function(registration) {
                                console.log('HURAII ServiceWorker registration successful with scope: ', registration.scope);
                            })
                            .catch(function(error) {
                                console.log('HURAII ServiceWorker registration failed: ', error);
                            });
                    });
                }
            </script>
            <?php
        }
    }

    /**
     * Get frontend settings
     * 
     * @return array Frontend settings
     */
    private function get_frontend_settings() {
        return array(
            'apiEndpoint' => get_option('vortex_huraii_api_endpoint', 'http://localhost:8080'),
            'cacheEnabled' => get_option('vortex_huraii_cache_enabled', true),
            'cacheTime' => get_option('vortex_huraii_cache_time', 3600),
            'imageQuality' => get_option('vortex_huraii_image_quality', 90),
            'maxVariations' => get_option('vortex_huraii_max_variations', 6),
            'defaultWidth' => get_option('vortex_huraii_default_width', 1024),
            'defaultHeight' => get_option('vortex_huraii_default_height', 1024)
        );
    }

    /**
     * Register admin assets
     * 
     * @param string $hook_suffix Current admin page
     */
    public function register_admin_assets($hook_suffix) {
        if ('toplevel_page_vortex-huraii' !== $hook_suffix) {
            return;
        }
        
        // Admin styles
        wp_enqueue_style(
            'vortex-huraii-admin',
            VORTEX_HURAII_ASSETS_URL . 'css/admin.css',
            array(),
            VORTEX_HURAII_VERSION
        );
        
        // Admin scripts
        wp_enqueue_script(
            'vortex-huraii-admin',
            VORTEX_HURAII_ASSETS_URL . 'js/admin.js',
            array('jquery'),
            VORTEX_HURAII_VERSION,
            true
        );
        
        // Localize script with admin settings
        wp_localize_script('vortex-huraii-admin', 'vortexHuraii', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_huraii_admin_nonce')
        ));
    }

    /**
     * Register admin menu
     */
    public function admin_menu() {
        add_menu_page(
            __('HURAII Image Generator', 'vortex-huraii'),
            __('HURAII', 'vortex-huraii'),
            'manage_options',
            'vortex-huraii',
            array($this, 'admin_page'),
            'dashicons-art',
            20
        );
    }

    /**
     * Admin page callback
     */
    public function admin_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle form submissions
        if (isset($_POST['vortex_huraii_settings_nonce']) && 
            wp_verify_nonce($_POST['vortex_huraii_settings_nonce'], 'vortex_huraii_save_settings')) {
            
            // Save settings
            update_option('vortex_huraii_api_endpoint', sanitize_text_field($_POST['api_endpoint']));
            update_option('vortex_huraii_cache_enabled', isset($_POST['cache_enabled']));
            update_option('vortex_huraii_cache_time', absint($_POST['cache_time']));
            update_option('vortex_huraii_image_quality', absint($_POST['image_quality']));
            update_option('vortex_huraii_max_variations', absint($_POST['max_variations']));
            update_option('vortex_huraii_default_width', absint($_POST['default_width']));
            update_option('vortex_huraii_default_height', absint($_POST['default_height']));
            
            // Set admin notice
            add_settings_error(
                'vortex_huraii_messages',
                'vortex_huraii_message',
                __('Settings saved.', 'vortex-huraii'),
                'updated'
            );
        }
        
        // Show admin page template
        include VORTEX_HURAII_PLUGIN_DIR . 'templates/admin-page.php';
    }

    /**
     * Shortcode callback
     * 
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function shortcode_callback($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'mode' => 'full',
            'width' => '100%',
            'height' => 'auto'
        ), $atts, 'huraii');
        
        // Enqueue frontend assets
        wp_enqueue_style('vortex-huraii-frontend');
        wp_enqueue_script('vortex-huraii-frontend');
        
        // Start output buffering
        ob_start();
        
        // Include shortcode template
        include VORTEX_HURAII_PLUGIN_DIR . 'templates/shortcode.php';
        
        // Return output
        return ob_get_clean();
    }

    /**
     * AJAX test connection
     */
    public function ajax_test_connection() {
        // Check nonce
        check_ajax_referer('vortex_huraii_admin_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'vortex-huraii')));
        }
        
        // Get API endpoint
        $api_endpoint = get_option('vortex_huraii_api_endpoint', 'http://localhost:8080');
        
        // Test connection
        $response = wp_remote_get($api_endpoint . '/api/status');
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        } else {
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            if ($status_code === 200) {
                wp_send_json_success(array(
                    'message' => __('Connection successful!', 'vortex-huraii'),
                    'data' => json_decode($body)
                ));
            } else {
                wp_send_json_error(array(
                    'message' => sprintf(__('Connection failed with status %s', 'vortex-huraii'), $status_code)
                ));
            }
        }
    }

    /**
     * AJAX clear cache
     */
    public function ajax_clear_cache() {
        // Check nonce
        check_ajax_referer('vortex_huraii_admin_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'vortex-huraii')));
        }
        
        // Clear transients
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_vortex_huraii_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_timeout_vortex_huraii_%'");
        
        // Send success message
        wp_send_json_success(array('message' => __('Cache cleared successfully!', 'vortex-huraii')));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create necessary directories
        $upload_dir = wp_upload_dir();
        $huraii_dir = $upload_dir['basedir'] . '/vortex-huraii';
        
        if (!file_exists($huraii_dir)) {
            wp_mkdir_p($huraii_dir);
        }
        
        // Add default options
        add_option('vortex_huraii_api_endpoint', 'http://localhost:8080');
        add_option('vortex_huraii_cache_enabled', true);
        add_option('vortex_huraii_cache_time', 3600);
        add_option('vortex_huraii_image_quality', 90);
        add_option('vortex_huraii_max_variations', 6);
        add_option('vortex_huraii_default_width', 1024);
        add_option('vortex_huraii_default_height', 1024);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Note: Service worker unregistration should be handled client-side
        // We cannot directly execute JavaScript here in PHP context
    }
}

// Initialize the plugin
function vortex_huraii() {
    return Vortex_HURAII::instance();
}

// Start the plugin
vortex_huraii(); 