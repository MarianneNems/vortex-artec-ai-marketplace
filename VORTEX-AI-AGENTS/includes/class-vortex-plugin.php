<?php
namespace Vortex\AI;

class VortexPlugin {
    private \;
    private \;
    private \;

    public function __construct() {
        \->plugin_name = 'vortex-ai';
        \->version = VORTEX_VERSION;
        \->load_dependencies();
    }

    private function load_dependencies() {
        // Core systems
        require_once VORTEX_PLUGIN_DIR . 'includes/ai/auth/class-vortex-authentication.php';
        require_once VORTEX_PLUGIN_DIR . 'includes/ai/blockchain/class-vortex-blockchain.php';
        require_once VORTEX_PLUGIN_DIR . 'includes/ai/smart-contract/class-vortex-smart-contract.php';
        
        // Frontend components
        require_once VORTEX_PLUGIN_DIR . 'includes/ai/frontend/components/class-vortex-dashboard.php';
    }

    public function initialize() {
        add_action('init', [\, 'register_post_types']);
        add_action('wp_enqueue_scripts', [\, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [\, 'enqueue_admin_scripts']);
    }

    public function register_post_types() {
        register_post_type('vortex_nft', [
            'labels' => [
                'name' => __('NFTs', 'vortex-ai'),
                'singular_name' => __('NFT', 'vortex-ai')
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail']
        ]);
    }

    public function enqueue_scripts() {
        wp_enqueue_style('vortex-main', VORTEX_PLUGIN_URL . 'assets/css/components/main.css', [], \->version);
        wp_enqueue_script('vortex-main', VORTEX_PLUGIN_URL . 'assets/js/components/main.js', ['jquery'], \->version, true);
        
        wp_localize_script('vortex-main', 'vortexData', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex-nonce')
        ]);
    }
}
