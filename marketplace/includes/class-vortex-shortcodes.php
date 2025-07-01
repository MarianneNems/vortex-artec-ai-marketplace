<?php
/**
 * Shortcode Registration and Management
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class that handles shortcode registration and loading
 */
class Vortex_Shortcodes {

    /**
     * Initialize and register all shortcodes
     */
    public static function init() {
        $instance = new self();
        add_action('init', array(__CLASS__, 'add_shortcodes'));
        
        // Load shortcode files
        self::load_shortcode_files();
        
        // Register shortcodes
        add_shortcode('vortex_payment_button', 'vortex_payment_button_shortcode');
        add_shortcode('vortex_transaction_history', 'vortex_transaction_history_shortcode');
        add_shortcode('vortex_artist_earnings', 'vortex_artist_earnings_shortcode');
        add_shortcode('vortex_price_estimator', 'vortex_price_estimator_shortcode');
        add_shortcode('vortex_thorius_concierge', 'vortex_thorius_concierge_shortcode');
    }
    
    /**
     * Load all shortcode files
     */
    private static function load_shortcode_files() {
        $plugin_dir = plugin_dir_path(dirname(__FILE__));
        
        // Load each shortcode file
        require_once $plugin_dir . 'public/shortcodes/payment-button-shortcode.php';
        require_once $plugin_dir . 'public/shortcodes/transaction-history-shortcode.php';
        require_once $plugin_dir . 'public/shortcodes/artist-earnings-shortcode.php';
        require_once $plugin_dir . 'public/shortcodes/price-estimator-shortcode.php';
        require_once $plugin_dir . 'public/shortcodes/thorius-concierge-shortcode.php';
    }

    /**
     * Add shortcodes.
     */
    public static function add_shortcodes() {
        add_shortcode('vortex_login_status', array(__CLASS__, 'render_login_status'));
        add_shortcode('vortex_marketplace', array(__CLASS__, 'render_marketplace'));
        add_shortcode('vortex_dashboard', array(__CLASS__, 'render_dashboard'));
        add_shortcode('vortex_ai_assistants', array(__CLASS__, 'render_ai_assistants'));
    }

    /**
     * Render AI assistants.
     *
     * @param array $atts Shortcode attributes.
     * @return string Rendered shortcode.
     */
    public static function render_ai_assistants($atts) {
        $atts = shortcode_atts(array(
            'count' => 5,
            'columns' => 3,
            'layout' => 'grid'
        ), $atts, 'vortex_ai_assistants');
        
        // If not logged in, show login notice
        if (!is_user_logged_in()) {
            return '<div class="vortex-login-notice">' . 
                __('Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to access AI assistants.', 'vortex-ai-marketplace') . 
                '</div>';
        }
        
        // Load AI agents
        if (class_exists('Vortex_AI_Agents')) {
            $ai_agents = new Vortex_AI_Agents();
            
            // Output buffer to capture rendered content
            ob_start();
            
            // Include template
            include VORTEX_PLUGIN_DIR . 'public/partials/dashboard-ai-agents.php';
            
            return ob_get_clean();
        }
        
        return '<div class="vortex-notice">' . __('AI assistants are currently unavailable.', 'vortex-ai-marketplace') . '</div>';
    }

    /**
     * Render login status.
     *
     * @param array $atts Shortcode attributes.
     * @return string Rendered shortcode.
     */
    public static function render_login_status($atts) {
        $atts = shortcode_atts(array(
            'login_text' => __('Log In', 'vortex-ai-marketplace'),
            'logout_text' => __('Log Out', 'vortex-ai-marketplace'),
            'welcome_text' => __('Welcome, %s', 'vortex-ai-marketplace'),
        ), $atts, 'vortex_login_status');
        
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $welcome = sprintf($atts['welcome_text'], $current_user->display_name);
            $logout_url = wp_logout_url(home_url());
            
            return '<div class="vortex-login-status">' . 
                '<span class="vortex-welcome">' . esc_html($welcome) . '</span>' . 
                '<a href="' . esc_url($logout_url) . '" class="vortex-logout">' . esc_html($atts['logout_text']) . '</a>' . 
                '</div>';
        } else {
            $login_url = wp_login_url(get_permalink());
            
            return '<div class="vortex-login-status">' . 
                '<a href="' . esc_url($login_url) . '" class="vortex-login">' . esc_html($atts['login_text']) . '</a>' . 
                '</div>';
        }
    }
    
    /**
     * Render marketplace.
     *
     * @param array $atts Shortcode attributes.
     * @return string Rendered shortcode.
     */
    public static function render_marketplace($atts) {
        $atts = shortcode_atts(array(
            'items_per_page' => 12,
            'columns' => 3,
            'category' => '',
            'tag' => '',
            'style' => '',
        ), $atts, 'vortex_marketplace');
        
        // Output buffer to capture rendered content
        ob_start();
        
        // Include marketplace template if it exists
        $template_path = VORTEX_PLUGIN_DIR . 'public/partials/vortex-marketplace.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="vortex-notice">' . __('Marketplace template not found.', 'vortex-ai-marketplace') . '</div>';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Render dashboard.
     *
     * @param array $atts Shortcode attributes.
     * @return string Rendered shortcode.
     */
    public static function render_dashboard($atts) {
        $atts = shortcode_atts(array(
            'user_role' => '',
            'show_ai_agents' => 'yes',
        ), $atts, 'vortex_dashboard');
        
        // If not logged in, show login notice
        if (!is_user_logged_in()) {
            return '<div class="vortex-login-notice">' . 
                __('Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to access your dashboard.', 'vortex-ai-marketplace') . 
                '</div>';
        }
        
        // Check user role if specified
        if (!empty($atts['user_role'])) {
            $user = wp_get_current_user();
            if (!in_array($atts['user_role'], $user->roles)) {
                return '<div class="vortex-notice">' . 
                    __('You do not have permission to access this dashboard.', 'vortex-ai-marketplace') . 
                    '</div>';
            }
        }
        
        // Output buffer to capture rendered content
        ob_start();
        
        // Include dashboard template if it exists
        $template_path = VORTEX_PLUGIN_DIR . 'public/partials/vortex-dashboard.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="vortex-notice">' . __('Dashboard template not found.', 'vortex-ai-marketplace') . '</div>';
        }
        
        // Include AI agents if enabled
        if ($atts['show_ai_agents'] === 'yes' && class_exists('Vortex_AI_Agents')) {
            echo self::render_ai_assistants(array());
        }
        
        return ob_get_clean();
    }
}

// Initialize shortcodes
add_action('init', array('Vortex_Shortcodes', 'init')); 