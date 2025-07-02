<?php
/**
 * The core plugin class - FIXED VERSION
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
        // Core classes - only load if they exist
        if (file_exists(VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-loader.php')) {
            require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-loader.php';
            $this->loader = new Vortex_Loader();
        }
        
        if (file_exists(VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-i18n.php')) {
            require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-i18n.php';
        }

        // Custom Post Types
        if (file_exists(VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-post-types.php')) {
            require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-post-types.php';
        }

        // API Classes - only load existing ones
        if (file_exists(VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-ai-api.php')) {
            require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-ai-api.php';
        }
        
        // Load API classes that exist
        $api_classes = array(
            'class-plans-api.php',
            'class-wallet-api.php', 
            'class-quiz-api.php',
            'class-milestones-api.php',
            'class-collections-api.php',
            'class-listings-api.php',
            'class-chloe-api.php',
            'class-generate-api.php',
            'class-nft-api.php'
        );
        
        foreach ($api_classes as $api_class) {
            $file_path = VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/api/' . $api_class;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }

        // Shortcodes and Widgets
        if (file_exists(VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-shortcodes.php')) {
            require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-shortcodes.php';
        }
        
        if (file_exists(VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-elementor.php')) {
            require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-elementor.php';
        }

        // Admin classes
        if (file_exists(VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'admin/class-vortex-admin.php')) {
            require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'admin/class-vortex-admin.php';
        }

        // Initialize loader if it wasn't loaded
        if (!$this->loader && class_exists('Vortex_Loader')) {
            $this->loader = new Vortex_Loader();
        }
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * @since    2.0.0
     * @access   private
     */
    private function set_locale() {
        if (class_exists('Vortex_i18n')) {
            $plugin_i18n = new Vortex_i18n();
            if ($this->loader) {
                $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
            }
        }
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     *
     * @since    2.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        if (class_exists('Vortex_Admin')) {
            $plugin_admin = new Vortex_Admin($this->get_plugin_name(), $this->get_version());
            
            if ($this->loader) {
                // Admin menu and pages
                $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
                $this->loader->add_action('admin_init', $plugin_admin, 'admin_init');
            }
        }
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     *
     * @since    2.0.0
     * @access   private
     */
    private function define_public_hooks() {
        // Shortcodes
        if (class_exists('Vortex_Shortcodes')) {
            $shortcodes = new Vortex_Shortcodes();
            if ($this->loader) {
                $this->loader->add_action('init', $shortcodes, 'register_shortcodes');
            }
        }

        // Elementor widgets
        if (did_action('elementor/loaded') && class_exists('Vortex_Elementor')) {
            $elementor = new Vortex_Elementor();
            if ($this->loader) {
                $this->loader->add_action('elementor/widgets/widgets_registered', $elementor, 'register_widgets');
            }
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
        if (class_exists('Vortex_Post_Types')) {
            $post_types = new Vortex_Post_Types();
            if ($this->loader) {
                $this->loader->add_action('init', $post_types, 'register_post_types');
            }
        }

        // Main API class
        if (class_exists('Vortex_AI_API')) {
            $api = new Vortex_AI_API();
            if ($this->loader) {
                $this->loader->add_action('rest_api_init', $api, 'register_routes');
            }
        }

        // Individual API handlers - only register if classes exist
        $api_handlers = array(
            'Vortex_Plans_API' => 'plans_api',
            'Vortex_Wallet_API' => 'wallet_api',
            'Vortex_Quiz_API' => 'quiz_api',
            'Vortex_Milestones_API' => 'milestones_api',
            'Vortex_Collections_API' => 'collections_api',
            'Vortex_Listings_API' => 'listings_api',
            'Vortex_Chloe_API' => 'chloe_api',
            'Vortex_Generate_API' => 'generate_api',
            'Vortex_NFT_API' => 'nft_api'
        );

        foreach ($api_handlers as $class_name => $var_name) {
            if (class_exists($class_name)) {
                $$var_name = new $class_name();
                if ($this->loader) {
                    $this->loader->add_action('rest_api_init', $$var_name, 'register_routes');
                }
            }
        }
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    2.0.0
     */
    public function run() {
        if ($this->loader) {
            $this->loader->run();
        }
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