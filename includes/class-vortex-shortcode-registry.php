/**
 * VORTEX Shortcode Registry
 * Comprehensive shortcode system for WordPress and Elementor
 */

if (!defined('ABSPATH')) exit;

class VORTEX_Shortcode_Registry {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->register_all_shortcodes();
        add_action('wp_enqueue_scripts', array($this, 'enqueue_shortcode_assets'));
    }
    
    private function register_all_shortcodes() {
        // AI AGENTS & CHAT
        add_shortcode('vortex_thorius_chat', array($this, 'thorius_chatbot'));
        add_shortcode('vortex_thorius_button', array($this, 'thorius_chat_button'));
        add_shortcode('vortex_huraii_interface', array($this, 'huraii_interface'));
        add_shortcode('vortex_cloe_dashboard', array($this, 'cloe_dashboard'));
        add_shortcode('vortex_horace_curator', array($this, 'horace_curator'));
        add_shortcode('vortex_agents_status', array($this, 'agents_status'));
        
        // ARTIST JOURNEY
        add_shortcode('vortex_artist_registration', array($this, 'artist_registration'));
        add_shortcode('vortex_subscription_plans', array($this, 'subscription_plans'));
        add_shortcode('vortex_artist_dashboard', array($this, 'artist_dashboard'));
        add_shortcode('vortex_journey_progress', array($this, 'journey_progress'));
        add_shortcode('vortex_milestone_tracker', array($this, 'milestone_tracker'));
        
        // MARKETPLACE
        add_shortcode('vortex_artist_grid', array($this, 'artist_grid'));
        add_shortcode('vortex_artwork_gallery', array($this, 'artwork_gallery'));
        add_shortcode('vortex_shopping_cart', array($this, 'shopping_cart'));
        add_shortcode('vortex_checkout_form', array($this, 'checkout_form'));
        
        // SECRET SAUCE
        add_shortcode('vortex_zodiac_profile', array($this, 'zodiac_profile'));
        add_shortcode('vortex_seed_art_upload', array($this, 'seed_art_upload'));
        add_shortcode('vortex_ai_art_studio', array($this, 'ai_art_studio'));
        
        // SMART CONTRACTS
        add_shortcode('vortex_tola_wallet', array($this, 'tola_wallet'));
        add_shortcode('vortex_contract_creator', array($this, 'contract_creator'));
        add_shortcode('vortex_swapping_marketplace', array($this, 'swapping_marketplace'));
        
        // ANALYTICS
        add_shortcode('vortex_system_dashboard', array($this, 'system_dashboard'));
        add_shortcode('vortex_performance_metrics', array($this, 'performance_metrics'));
        add_shortcode('vortex_live_stats', array($this, 'live_stats'));
    }
    
    public function thorius_chatbot($atts) {
        $atts = shortcode_atts(array(
            'style' => 'floating',
            'position' => 'bottom-right',
            'color' => 'default'
        ), $atts);
        
        return '<div class="vortex-thorius-shortcode" data-style="' . esc_attr($atts['style']) . '"></div>';
    }
    
    public function artist_grid($atts) {
        $atts = shortcode_atts(array(
            'columns' => '3',
            'limit' => '12',
            'category' => ''
        ), $atts);
        
        return '<div class="vortex-artist-grid-shortcode" data-columns="' . esc_attr($atts['columns']) . '" data-limit="' . esc_attr($atts['limit']) . '"></div>';
    }
    
    public function __call($method, $args) {
        $atts = isset($args[0]) ? $args[0] : array();
        $shortcode_name = str_replace('_', '-', $method);
        
        return '<div class="vortex-shortcode-' . esc_attr($shortcode_name) . '" data-shortcode="' . esc_attr($method) . '">Loading...</div>';
    }
    
    public function enqueue_shortcode_assets() {
        wp_enqueue_style('vortex-shortcodes', plugin_dir_url(__FILE__) . '../assets/css/vortex-shortcodes.css', array(), '1.0.0');
        wp_enqueue_script('vortex-shortcodes', plugin_dir_url(__FILE__) . '../assets/js/vortex-shortcodes.js', array('jquery'), '1.0.0', true);
    }
}

VORTEX_Shortcode_Registry::get_instance(); 