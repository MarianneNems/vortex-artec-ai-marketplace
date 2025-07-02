<?php
/**
 * TOLA-ART Admin Menus Class
 * 
 * @package VortexTOLAArt
 * @version 2.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Vortex_TOLA_Admin_Menus {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
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
        add_action('admin_init', array($this, 'register_settings'));
        
        // AJAX handlers
        add_action('wp_ajax_vortex_trigger_daily_art', array($this, 'ajax_trigger_daily_art'));
        add_action('wp_ajax_vortex_get_daily_art_stats', array($this, 'ajax_get_stats'));
    }
    
    /**
     * Add admin menus
     */
    public function add_admin_menus() {
        // Main menu
        add_menu_page(
            __('TOLA-ART Dashboard', 'vortex-tola-art'),
            __('TOLA-ART', 'vortex-tola-art'),
            'manage_options',
            'vortex-tola-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-art',
            30
        );
        
        // Submenu pages
        add_submenu_page(
            'vortex-tola-dashboard',
            __('Settings', 'vortex-tola-art'),
            __('Settings', 'vortex-tola-art'),
            'manage_options',
            'vortex-tola-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'vortex-tola-dashboard',
            __('Royalty Reports', 'vortex-tola-art'),
            __('Royalty Reports', 'vortex-tola-art'),
            'manage_options',
            'vortex-tola-reports',
            array($this, 'reports_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('vortex_tola_settings', 'vortex_tola_creator_wallet');
        register_setting('vortex_tola_settings', 'vortex_tola_contract_address');
        register_setting('vortex_tola_settings', 'vortex_huraii_api_endpoint');
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        include VORTEX_TOLA_ART_PLUGIN_PATH . 'admin/partials/tola-art-admin-page.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('TOLA-ART Settings', 'vortex-tola-art'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('vortex_tola_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Creator Wallet Address', 'vortex-tola-art'); ?></th>
                        <td><input type="text" name="vortex_tola_creator_wallet" value="<?php echo esc_attr(get_option('vortex_tola_creator_wallet')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Smart Contract Address', 'vortex-tola-art'); ?></th>
                        <td><input type="text" name="vortex_tola_contract_address" value="<?php echo esc_attr(get_option('vortex_tola_contract_address')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('HURAII API Endpoint', 'vortex-tola-art'); ?></th>
                        <td><input type="text" name="vortex_huraii_api_endpoint" value="<?php echo esc_attr(get_option('vortex_huraii_api_endpoint')); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Reports page
     */
    public function reports_page() {
        echo '<div class="wrap"><h1>' . __('Royalty Reports', 'vortex-tola-art') . '</h1><p>Royalty reporting interface coming soon...</p></div>';
    }
    
    /**
     * AJAX: Trigger daily art generation
     */
    public function ajax_trigger_daily_art() {
        check_ajax_referer('vortex_daily_art_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized access', 'vortex-tola-art'));
        }
        
        // Trigger daily art generation
        do_action('vortex_daily_art_generation');
        
        wp_send_json_success(__('Daily art generation triggered successfully', 'vortex-tola-art'));
    }
    
    /**
     * AJAX: Get daily art stats
     */
    public function ajax_get_stats() {
        check_ajax_referer('vortex_daily_art_nonce', 'nonce');
        
        global $wpdb;
        $daily_art_table = $wpdb->prefix . 'vortex_daily_art';
        
        $stats = array(
            'total_generated' => $wpdb->get_var("SELECT COUNT(*) FROM {$daily_art_table}"),
            'total_sales' => $wpdb->get_var("SELECT SUM(total_sales) FROM {$daily_art_table}") ?: 0,
            'total_royalties' => $wpdb->get_var("SELECT SUM(royalties_distributed) FROM {$daily_art_table}") ?: 0
        );
        
        wp_send_json_success($stats);
    }
} 