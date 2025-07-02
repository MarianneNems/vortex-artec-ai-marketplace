<?php
/**
 * The core plugin class - FIXED STRUCTURE VERSION
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
        $core_files = array(
            'includes/class-vortex-loader.php',
            'includes/class-vortex-i18n.php',
            'includes/class-vortex-activator.php',
            'includes/class-vortex-deactivator.php'
        );
        
        foreach ($core_files as $file) {
            $file_path = plugin_dir_path(dirname(__FILE__)) . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }

        // Admin and public classes
        $admin_public_files = array(
            'admin/class-vortex-admin.php',
            'public/class-vortex-public.php'
        );
        
        foreach ($admin_public_files as $file) {
            $file_path = plugin_dir_path(dirname(__FILE__)) . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }

        // Post types and taxonomies
        $post_type_files = array(
            'includes/class-vortex-post-types.php',
            'includes/class-vortex-taxonomies.php'
        );
        
        foreach ($post_type_files as $file) {
            $file_path = plugin_dir_path(dirname(__FILE__)) . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }

        // New WooCommerce and AI integration classes
        $new_integration_files = array(
            'includes/class-vortex-subscriptions.php',
            'includes/class-vortex-ai-marketplace-wallet.php',
            'includes/class-vortex-artist-journey-shortcodes.php',
            'includes/class-vortex-seed-art-manager.php',
            'includes/class-vortex-gamification.php',
            'includes/class-vortex-artist-registration.php',
            'includes/class-vortex-horace-business-quiz.php'
        );
        
        foreach ($new_integration_files as $file) {
            $file_path = plugin_dir_path(dirname(__FILE__)) . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }

        // API Classes - only existing ones
        $api_files = array(
            'includes/api/class-vortex-ai-api.php',
            'includes/api/class-plans-api.php',
            'includes/api/class-wallet-api.php',
            'includes/api/class-quiz-api.php',
            'includes/api/class-milestones-api.php',
            'includes/api/class-collections-api.php',
            'includes/api/class-listings-api.php',
            'includes/api/class-chloe-api.php',
            'includes/api/class-generate-api.php',
            'includes/api/class-nft-api.php'
        );
        
        foreach ($api_files as $file) {
            $file_path = plugin_dir_path(dirname(__FILE__)) . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }

        // Shortcodes and widgets
        $shortcode_files = array(
            'includes/class-vortex-shortcodes.php',
            'includes/class-vortex-elementor.php'
        );
        
        foreach ($shortcode_files as $file) {
            $file_path = plugin_dir_path(dirname(__FILE__)) . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }

        // TOLA-ART Daily Automation
        $tola_art_file = plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-tola-art-daily-automation.php';
        if (file_exists($tola_art_file)) {
            require_once $tola_art_file;
        }

        // Metrics
        $metrics_file = plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-metrics.php';
        if (file_exists($metrics_file)) {
            require_once $metrics_file;
        }

        // Initialize loader if available
        if (class_exists('Vortex_Loader')) {
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
        if (class_exists('Vortex_i18n') && $this->loader) {
            $plugin_i18n = new Vortex_i18n();
            $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
        }
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     *
     * @since    2.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        if (class_exists('Vortex_Admin') && $this->loader) {
            $plugin_admin = new Vortex_Admin($this->get_plugin_name(), $this->get_version());
            
            // Admin menu and pages
            $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
            $this->loader->add_action('admin_init', $plugin_admin, 'admin_init');
            
            // Admin scripts and styles
            $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
            $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        }
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     *
     * @since    2.0.0
     * @access   private
     */
    private function define_public_hooks() {
        if (class_exists('Vortex_Public') && $this->loader) {
            $plugin_public = new Vortex_Public($this->get_plugin_name(), $this->get_version());
            
            $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
            $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        }
        
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

        // Taxonomies
        if (class_exists('Vortex_Taxonomies')) {
            $taxonomies = new Vortex_Taxonomies();
            if ($this->loader) {
                $this->loader->add_action('init', $taxonomies, 'register_taxonomies');
            }
        }

        // Main API class
        if (class_exists('Vortex_AI_API')) {
            $api = new Vortex_AI_API();
            if ($this->loader) {
                $this->loader->add_action('rest_api_init', $api, 'register_routes');
            }
        }

        // Initialize new integration systems
        if (class_exists('Vortex_Subscriptions')) {
            new Vortex_Subscriptions();
        }
        
        if (class_exists('Vortex_AI_Marketplace_Wallet')) {
            new Vortex_AI_Marketplace_Wallet();
        }
        
        if (class_exists('Vortex_Artist_Journey_Shortcodes')) {
            new Vortex_Artist_Journey_Shortcodes();
        }
        
        if (class_exists('Vortex_Seed_Art_Manager')) {
            new Vortex_Seed_Art_Manager();
        }
        
        if (class_exists('Vortex_Gamification')) {
            new Vortex_Gamification();
        }
        
        if (class_exists('Vortex_Artist_Registration')) {
            Vortex_Artist_Registration::get_instance();
        }
        
        if (class_exists('Vortex_HORACE_Business_Quiz')) {
            Vortex_HORACE_Business_Quiz::get_instance();
        }

        // Individual API handlers - only register if classes exist
        $api_handlers = array(
            'Vortex_AI_API_Enhanced',
            'Vortex_Plans_API',
            'Vortex_Wallet_API',
            'Vortex_Quiz_API',
            'Vortex_Milestones_API',
            'Vortex_Collections_API',
            'Vortex_Listings_API',
            'Vortex_Chloe_API',
            'Vortex_Generate_API',
            'Vortex_NFT_API'
        );

        foreach ($api_handlers as $class_name) {
            if (class_exists($class_name)) {
                $instance = new $class_name();
                if ($this->loader && method_exists($instance, 'register_routes')) {
                    $this->loader->add_action('rest_api_init', $instance, 'register_routes');
                }
            }
        }

        // Initialize TOLA-ART Daily Automation
        if (class_exists('Vortex_TOLA_Art_Daily_Automation')) {
            Vortex_TOLA_Art_Daily_Automation::get_instance();
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
     * The name of the plugin used to uniquely identify it.
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