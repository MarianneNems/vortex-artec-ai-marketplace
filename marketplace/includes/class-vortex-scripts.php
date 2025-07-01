/**
 * Vortex Scripts Class
 * Handles the registration and enqueuing of all scripts and styles
 */
class Vortex_Scripts {
    
    /**
     * Register the scripts and styles
     */
    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_scripts'));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public static function enqueue_frontend_scripts() {
        // General styles
        wp_enqueue_style('vortex-main', VORTEX_PLUGIN_URL . 'css/vortex-main.css', array(), VORTEX_VERSION);
        
        // Dashboard styles
        wp_enqueue_style('vortex-dashboard', VORTEX_PLUGIN_URL . 'css/vortex-dashboard.css', array(), VORTEX_VERSION);
        
        // AI Agents styles - load on dashboard page only
        if (is_page('dashboard') || is_page('artist-dashboard')) {
            wp_enqueue_style('vortex-ai-agents', VORTEX_PLUGIN_URL . 'css/vortex-ai-agents.css', array(), VORTEX_VERSION);
        }
        
        // Main scripts
        wp_enqueue_script('vortex-main', VORTEX_PLUGIN_URL . 'js/vortex-main.js', array('jquery'), VORTEX_VERSION, true);
        
        // Artist quiz scripts
        wp_enqueue_script('vortex-artist-quiz', VORTEX_PLUGIN_URL . 'js/vortex-artist-quiz.js', array('jquery'), VORTEX_VERSION, true);
        
        // Business Strategist scripts - load only when needed
        if (is_page('dashboard') || is_page('business-plan')) {
            wp_enqueue_script('vortex-business-strategist', VORTEX_PLUGIN_URL . 'js/vortex-business-strategist.js', array('jquery'), VORTEX_VERSION, true);
        }
        
        // AI Agents scripts - load on dashboard page only
        if (is_page('dashboard') || is_page('artist-dashboard')) {
            wp_enqueue_script('vortex-ai-agents', VORTEX_PLUGIN_URL . 'js/vortex-ai-agents.js', array('jquery'), VORTEX_VERSION, true);
            
            // Localize script with AJAX URL and nonce
            wp_localize_script('vortex-ai-agents', 'vortexAIAgents', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'security' => wp_create_nonce('vortex-ai-agents-nonce'),
                'user_id' => get_current_user_id(),
                'is_artist' => current_user_can('artist') ? 'yes' : 'no'
            ));
        }
        
        // Localize main script with AJAX URL and nonce
        wp_localize_script('vortex-main', 'vortexData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('vortex-security')
        ));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public static function enqueue_admin_scripts($hook) {
        // Only load on specific admin pages
        if ($hook != 'toplevel_page_vortex-settings' && $hook != 'vortex_page_vortex-analytics') {
            return;
        }
        
        // Admin styles
        wp_enqueue_style('vortex-admin', VORTEX_PLUGIN_URL . 'admin/css/vortex-admin.css', array(), VORTEX_VERSION);
        
        // Admin scripts
        wp_enqueue_script('vortex-admin', VORTEX_PLUGIN_URL . 'admin/js/vortex-admin.js', array('jquery'), VORTEX_VERSION, true);
        
        // Localize admin script
        wp_localize_script('vortex-admin', 'vortexAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('vortex-admin-nonce')
        ));
    }
} 