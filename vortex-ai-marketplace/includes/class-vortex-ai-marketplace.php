<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks for the Artist Journey implementation.
 *
 * @since      2.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */
class Vortex_AI_Marketplace {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @since    2.0.0
     * @access   protected
     * @var      Vortex_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    2.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    2.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * @since    2.0.0
     */
    public function __construct() {
        if (defined('VORTEX_AI_MARKETPLACE_VERSION')) {
            $this->version = VORTEX_AI_MARKETPLACE_VERSION;
        } else {
            $this->version = '2.0.0';
        }
        $this->plugin_name = 'vortex-ai-marketplace';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_api_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    2.0.0
     * @access   private
     */
    private function load_dependencies() {
        // Core classes
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-loader.php';
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-i18n.php';

        // Custom Post Types and Taxonomies
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-post-types.php';
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-taxonomies.php';

        // API Classes
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-ai-api.php';
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/api/class-plans-api.php';
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/api/class-wallet-api.php';
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/api/class-quiz-api.php';
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/api/class-milestones-api.php';
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/api/class-collections-api.php';
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/api/class-listings-api.php';
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/api/class-chloe-api.php';
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/api/class-generate-api.php';
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/api/class-nft-api.php';
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/api/class-admin-api.php';
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/api/class-rewards-api.php';

        // Shortcodes and Widgets
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-shortcodes.php';
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-elementor.php';

        // Admin classes
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'admin/class-vortex-admin.php';

        // Public classes
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'public/class-vortex-public.php';

        $this->loader = new Vortex_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * @since    2.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Vortex_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     *
     * @since    2.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Vortex_Admin($this->get_plugin_name(), $this->get_version());
        
        // Admin menu and pages
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'admin_init');
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     *
     * @since    2.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Vortex_Public($this->get_plugin_name(), $this->get_version());
        
        // Shortcodes
        $shortcodes = new Vortex_Shortcodes();
        $this->loader->add_action('init', $shortcodes, 'register_shortcodes');

        // Elementor widgets
        if (did_action('elementor/loaded')) {
            $elementor = new Vortex_Elementor();
            $this->loader->add_action('elementor/widgets/widgets_registered', $elementor, 'register_widgets');
        }
    }

    /**
     * Register all API-related hooks.
     *
     * @since    2.0.0
     * @access   private
     */
    private function define_api_hooks() {
        // Custom Post Types
        $post_types = new Vortex_Post_Types();
        $this->loader->add_action('init', $post_types, 'register_post_types');

        // Taxonomies
        $taxonomies = new Vortex_Taxonomies();
        $this->loader->add_action('init', $taxonomies, 'register_taxonomies');

        // Main API class
        $api = new Vortex_AI_API();
        $this->loader->add_action('rest_api_init', $api, 'register_routes');

        // Individual API handlers
        $plans_api = new Vortex_Plans_API();
        $this->loader->add_action('rest_api_init', $plans_api, 'register_routes');

        $wallet_api = new Vortex_Wallet_API();
        $this->loader->add_action('rest_api_init', $wallet_api, 'register_routes');

        $quiz_api = new Vortex_Quiz_API();
        $this->loader->add_action('rest_api_init', $quiz_api, 'register_routes');

        $milestones_api = new Vortex_Milestones_API();
        $this->loader->add_action('rest_api_init', $milestones_api, 'register_routes');

        $collections_api = new Vortex_Collections_API();
        $this->loader->add_action('rest_api_init', $collections_api, 'register_routes');

        $listings_api = new Vortex_Listings_API();
        $this->loader->add_action('rest_api_init', $listings_api, 'register_routes');

        $chloe_api = new Vortex_Chloe_API();
        $this->loader->add_action('rest_api_init', $chloe_api, 'register_routes');

        $generate_api = new Vortex_Generate_API();
        $this->loader->add_action('rest_api_init', $generate_api, 'register_routes');

        $nft_api = new Vortex_NFT_API();
        $this->loader->add_action('rest_api_init', $nft_api, 'register_routes');

        $admin_api = new Vortex_Admin_API();
        $this->loader->add_action('rest_api_init', $admin_api, 'register_routes');

        $rewards_api = new Vortex_Rewards_API();
        $this->loader->add_action('rest_api_init', $rewards_api, 'register_routes');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    2.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     2.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     2.0.0
     * @return    Vortex_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     2.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
} 