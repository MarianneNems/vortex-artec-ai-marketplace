<?php
/**
 * Plugin Name: Vortex HURAII Integration
 * Description: Integrates HURAII image processing capabilities with WordPress
 * Version: 1.0.0
 * Author: Vortex AI
 * Text Domain: vortex-huraii
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VORTEX_HURAII_VERSION', '1.0.0');
define('VORTEX_HURAII_PATH', plugin_dir_path(__FILE__));
define('VORTEX_HURAII_URL', plugin_dir_url(__FILE__));
define('VORTEX_HURAII_ASSETS_URL', VORTEX_HURAII_URL . 'assets/');
define('VORTEX_HURAII_UPLOADS_DIR', wp_upload_dir()['basedir'] . '/vortex-huraii/');
define('VORTEX_HURAII_TEMP_DIR', wp_upload_dir()['basedir'] . '/vortex-huraii/temp/');

// Main plugin class
class Vortex_HURAII_Integration {
    /**
     * Instance of this class
     * @var object
     */
    private static $instance = null;

    /**
     * Return an instance of this class
     * @return object A single instance of this class
     */
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Initialize the plugin
        add_action('init', array($this, 'init'));
        
        // Register AJAX actions
        add_action('wp_ajax_vortex_huraii_upload_image', array($this, 'ajax_upload_image'));
        add_action('wp_ajax_vortex_huraii_generate_variations', array($this, 'ajax_generate_variations'));
        add_action('wp_ajax_vortex_huraii_task_status', array($this, 'ajax_task_status'));
        add_action('wp_ajax_vortex_huraii_clear_cache', array($this, 'ajax_clear_cache'));
        add_action('wp_ajax_vortex_huraii_test_connection', array($this, 'ajax_test_connection'));
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Enqueue frontend scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register shortcodes
        add_shortcode('vortex_huraii', array($this, 'render_huraii_shortcode'));
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Create upload directories if they don't exist
        $this->create_upload_directories();
        
        // Register custom post type for HURAII artworks
        $this->register_post_types();
    }

    /**
     * Create upload directories
     */
    private function create_upload_directories() {
        if (!file_exists(VORTEX_HURAII_UPLOADS_DIR)) {
            wp_mkdir_p(VORTEX_HURAII_UPLOADS_DIR);
        }
        
        if (!file_exists(VORTEX_HURAII_TEMP_DIR)) {
            wp_mkdir_p(VORTEX_HURAII_TEMP_DIR);
        }
    }

    /**
     * Register custom post types
     */
    private function register_post_types() {
        register_post_type('huraii_artwork', array(
            'labels' => array(
                'name' => __('HURAII Artworks', 'vortex-huraii'),
                'singular_name' => __('HURAII Artwork', 'vortex-huraii'),
                'add_new' => __('Add New', 'vortex-huraii'),
                'add_new_item' => __('Add New Artwork', 'vortex-huraii'),
                'edit_item' => __('Edit Artwork', 'vortex-huraii'),
                'new_item' => __('New Artwork', 'vortex-huraii'),
                'view_item' => __('View Artwork', 'vortex-huraii'),
                'search_items' => __('Search Artworks', 'vortex-huraii'),
                'not_found' => __('No artworks found', 'vortex-huraii'),
                'not_found_in_trash' => __('No artworks found in Trash', 'vortex-huraii'),
            ),
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-art',
            'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'rewrite' => array('slug' => 'huraii-artwork'),
            'show_in_rest' => true,
        ));
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_assets($hook) {
        // Only load on HURAII admin page
        if ('toplevel_page_vortex-huraii' !== $hook) {
            return;
        }
        
        // Enqueue admin styles
        wp_enqueue_style(
            'vortex-huraii-admin',
            VORTEX_HURAII_ASSETS_URL . 'css/huraii-admin.css',
            array(),
            VORTEX_HURAII_VERSION
        );
        
        // Enqueue admin scripts
        wp_enqueue_script(
            'vortex-huraii-admin',
            VORTEX_HURAII_ASSETS_URL . 'js/huraii-wp-integration.js',
            array('jquery'),
            VORTEX_HURAII_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('vortex-huraii-admin', 'vortexHURAII', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_huraii_nonce'),
            'uploads_url' => wp_upload_dir()['baseurl'] . '/vortex-huraii/',
            'user_id' => get_current_user_id(),
        ));
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_assets() {
        // Enqueue frontend styles
        wp_enqueue_style(
            'vortex-huraii',
            VORTEX_HURAII_ASSETS_URL . 'css/huraii-demo.css',
            array(),
            VORTEX_HURAII_VERSION
        );
        
        // Enqueue frontend scripts
        wp_enqueue_script(
            'vortex-huraii-core',
            VORTEX_HURAII_ASSETS_URL . 'js/huraii-components/huraii-core.js',
            array('jquery'),
            VORTEX_HURAII_VERSION,
            true
        );
        
        wp_enqueue_script(
            'vortex-huraii-lru-cache',
            VORTEX_HURAII_ASSETS_URL . 'js/huraii-components/huraii-lru-cache.js',
            array('jquery', 'vortex-huraii-core'),
            VORTEX_HURAII_VERSION,
            true
        );
        
        wp_enqueue_script(
            'vortex-huraii-websocket',
            VORTEX_HURAII_ASSETS_URL . 'js/huraii-components/huraii-websocket.js',
            array('jquery', 'vortex-huraii-core'),
            VORTEX_HURAII_VERSION,
            true
        );
        
        wp_enqueue_script(
            'vortex-huraii-api',
            VORTEX_HURAII_ASSETS_URL . 'js/huraii-components/huraii-api.js',
            array('jquery', 'vortex-huraii-core'),
            VORTEX_HURAII_VERSION,
            true
        );
        
        wp_enqueue_script(
            'vortex-huraii-demo',
            VORTEX_HURAII_ASSETS_URL . 'js/huraii-components/demo-artist-journey.js',
            array('jquery', 'vortex-huraii-core', 'vortex-huraii-api'),
            VORTEX_HURAII_VERSION,
            true
        );
        
        wp_enqueue_script(
            'vortex-huraii-image-upload',
            VORTEX_HURAII_ASSETS_URL . 'js/huraii-components/huraii-image-upload.js',
            array('jquery', 'vortex-huraii-core', 'vortex-huraii-api'),
            VORTEX_HURAII_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('vortex-huraii-core', 'vortexHURAII', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_huraii_nonce'),
            'uploads_url' => wp_upload_dir()['baseurl'] . '/vortex-huraii/',
            'user_id' => get_current_user_id(),
        ));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('HURAII Image Processing', 'vortex-huraii'),
            __('HURAII', 'vortex-huraii'),
            'manage_options',
            'vortex-huraii',
            array($this, 'render_admin_page'),
            'dashicons-art',
            30
        );
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        include_once VORTEX_HURAII_PATH . 'templates/admin-page.php';
    }

    /**
     * Render HURAII shortcode
     */
    public function render_huraii_shortcode($atts) {
        $atts = shortcode_atts(array(
            'mode' => 'full', // full, text-only, image-only
            'width' => '100%',
            'height' => 'auto',
        ), $atts, 'vortex_huraii');
        
        ob_start();
        include_once VORTEX_HURAII_PATH . 'templates/shortcode.php';
        return ob_get_clean();
    }

    /**
     * AJAX handler for image upload
     */
    public function ajax_upload_image() {
        // Verify nonce
        if (!check_ajax_referer('vortex_huraii_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Security check failed'), 403);
        }
        
        // Check permissions
        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => 'You do not have permission to upload files'), 403);
        }
        
        // Check if file was uploaded
        if (empty($_FILES['image'])) {
            wp_send_json_error(array('message' => 'No image file was uploaded'), 400);
        }
        
        // Get uploaded file
        $file = $_FILES['image'];
        
        // Validate file type
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(array(
                'message' => 'Invalid file type. Allowed types: ' . implode(', ', $allowed_types),
                'error_code' => 'INVALID_FILE_TYPE'
            ), 400);
        }
        
        // Validate file size
        $max_size = 15 * 1024 * 1024; // 15MB
        if ($file['size'] > $max_size) {
            wp_send_json_error(array(
                'message' => 'File size too large. Maximum size: 15MB',
                'error_code' => 'FILE_TOO_LARGE'
            ), 400);
        }
        
        // Generate unique filename
        $timestamp = time();
        $random = mt_rand(1000, 9999);
        $filename = $timestamp . '_' . $random . '_' . sanitize_file_name($file['name']);
        $filepath = VORTEX_HURAII_UPLOADS_DIR . $filename;
        
        // Create directory if it doesn't exist
        if (!file_exists(VORTEX_HURAII_UPLOADS_DIR)) {
            wp_mkdir_p(VORTEX_HURAII_UPLOADS_DIR);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            wp_send_json_error(array('message' => 'Failed to save uploaded file'), 500);
        }
        
        // Process options
        $options = isset($_POST['options']) ? json_decode(stripslashes($_POST['options']), true) : array();
        
        // Add default options
        $options = array_merge(array(
            'width' => isset($_POST['width']) ? intval($_POST['width']) : 1024,
            'height' => isset($_POST['height']) ? intval($_POST['height']) : 1024,
            'format' => 'png',
            'taskId' => 'img_' . $timestamp . '_' . $random,
        ), $options);
        
        // Call HURAII image processor (simulated for this demo)
        $result = $this->process_image($filepath, $options);
        
        // Return success response
        wp_send_json_success($result);
    }

    /**
     * AJAX handler for generating variations
     */
    public function ajax_generate_variations() {
        // Verify nonce
        if (!check_ajax_referer('vortex_huraii_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Security check failed'), 403);
        }
        
        // Check permissions
        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => 'You do not have permission to generate variations'), 403);
        }
        
        // Get source image
        $source_image_id = isset($_POST['source_image_id']) ? sanitize_text_field($_POST['source_image_id']) : null;
        $source_image = null;
        
        // Handle uploaded file or image ID
        if (!empty($_FILES['source_image'])) {
            // Handle uploaded file
            $file = $_FILES['source_image'];
            
            // Validate file type
            $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
            if (!in_array($file['type'], $allowed_types)) {
                wp_send_json_error(array(
                    'message' => 'Invalid file type. Allowed types: ' . implode(', ', $allowed_types),
                    'error_code' => 'INVALID_FILE_TYPE'
                ), 400);
            }
            
            // Move uploaded file to temp directory
            $timestamp = time();
            $random = mt_rand(1000, 9999);
            $filename = $timestamp . '_' . $random . '_' . sanitize_file_name($file['name']);
            $filepath = VORTEX_HURAII_TEMP_DIR . $filename;
            
            // Create directory if it doesn't exist
            if (!file_exists(VORTEX_HURAII_TEMP_DIR)) {
                wp_mkdir_p(VORTEX_HURAII_TEMP_DIR);
            }
            
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                wp_send_json_error(array('message' => 'Failed to save uploaded file'), 500);
            }
            
            $source_image = $filepath;
        } elseif ($source_image_id) {
            // Handle image ID
            if (strpos($source_image_id, 'img_') === 0) {
                // HURAII image ID
                $source_image = array(
                    'id' => $source_image_id,
                    'path' => VORTEX_HURAII_UPLOADS_DIR . $source_image_id . '.png'
                );
            } else {
                // WordPress attachment ID
                $attachment_id = intval($source_image_id);
                if ($attachment_id > 0) {
                    $attachment_path = get_attached_file($attachment_id);
                    if ($attachment_path && file_exists($attachment_path)) {
                        $source_image = $attachment_path;
                    } else {
                        wp_send_json_error(array('message' => 'Attachment file not found'), 404);
                    }
                } else {
                    wp_send_json_error(array('message' => 'Invalid attachment ID'), 400);
                }
            }
        } else {
            wp_send_json_error(array('message' => 'Source image is required'), 400);
        }
        
        // Get variation parameters
        $params = isset($_POST['params']) ? json_decode(stripslashes($_POST['params']), true) : array();
        $variation_count = isset($_POST['variation_count']) ? intval($_POST['variation_count']) : 3;
        
        // Generate variations (simulated for this demo)
        $results = $this->generate_variations($source_image, $params, $variation_count);
        
        // Return success response
        wp_send_json_success(array(
            'variations' => $results,
            'count' => count($results),
            'source_id' => $source_image_id ?: 'uploaded_image',
            'params' => $params
        ));
    }

    /**
     * AJAX handler for task status
     */
    public function ajax_task_status() {
        // Verify nonce
        if (!check_ajax_referer('vortex_huraii_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Security check failed'), 403);
        }
        
        // Get task ID
        $task_id = isset($_REQUEST['task_id']) ? sanitize_text_field($_REQUEST['task_id']) : '';
        
        if (empty($task_id)) {
            wp_send_json_error(array('message' => 'Task ID is required'), 400);
        }
        
        // Get task status (simulated for this demo)
        $task_status = $this->get_task_status($task_id);
        
        if (!$task_status) {
            wp_send_json_error(array('message' => 'Task not found'), 404);
        }
        
        // Return task status
        wp_send_json_success($task_status);
    }

    /**
     * AJAX handler for clearing cache
     */
    public function ajax_clear_cache() {
        // Verify nonce
        if (!check_ajax_referer('vortex_huraii_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Security check failed'), 403);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to clear cache'), 403);
        }
        
        // Clear cache (simulated for this demo)
        $this->clear_cache();
        
        // Return success response
        wp_send_json_success(array('message' => 'Cache cleared successfully'));
    }

    /**
     * AJAX handler for testing connection
     */
    public function ajax_test_connection() {
        // Verify nonce
        if (!check_ajax_referer('vortex_huraii_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Security check failed'), 403);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to test connection'), 403);
        }
        
        // Test connection (simulated for this demo)
        $result = $this->test_connection();
        
        if ($result['success']) {
            wp_send_json_success(array('message' => 'Connection successful', 'details' => $result['details']));
        } else {
            wp_send_json_error(array('message' => 'Connection failed', 'details' => $result['details']), 500);
        }
    }

    /**
     * Process an image (simulated for this demo)
     */
    private function process_image($filepath, $options) {
        // In a real implementation, this would call the Node.js backend
        // For this demo, we'll simulate processing
        
        // Generate result paths
        $timestamp = time();
        $random = mt_rand(1000, 9999);
        $filename = basename($filepath);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $format = isset($options['format']) ? $options['format'] : $extension;
        
        // Create result filenames
        $result_filename = $timestamp . '_' . $random . '.' . $format;
        $thumbnail_filename = $timestamp . '_' . $random . '_thumb.' . $format;
        
        // Create result file paths
        $result_filepath = VORTEX_HURAII_UPLOADS_DIR . $result_filename;
        $thumbnail_filepath = VORTEX_HURAII_UPLOADS_DIR . $thumbnail_filename;
        
        // Copy the original file as a placeholder (in a real implementation, this would process the image)
        copy($filepath, $result_filepath);
        copy($filepath, $thumbnail_filepath);
        
        // Create result URLs
        $uploads_url = wp_upload_dir()['baseurl'] . '/vortex-huraii/';
        $result_url = $uploads_url . $result_filename;
        $thumbnail_url = $uploads_url . $thumbnail_filename;
        
        // Create result object
        return array(
            'taskId' => $options['taskId'],
            'image_url' => $result_url,
            'thumbnail_url' => $thumbnail_url,
            'width' => $options['width'],
            'height' => $options['height'],
            'format' => $format,
            'processing_time' => mt_rand(500, 3000), // simulated processing time
            'seed' => isset($options['seed']) ? $options['seed'] : mt_rand(1000000, 9999999),
            'metadata' => isset($options['metadata']) ? $options['metadata'] : array(),
            'created_at' => date('c'),
            'model' => isset($options['model']) ? $options['model'] : 'default_model',
            'is_variation' => isset($options['is_variation']) ? $options['is_variation'] : false,
            'variation_id' => isset($options['variation_id']) ? $options['variation_id'] : null,
            'strength' => isset($options['strength']) ? $options['strength'] : 1.0
        );
    }

    /**
     * Generate variations (simulated for this demo)
     */
    private function generate_variations($source_image, $params, $count) {
        // In a real implementation, this would call the Node.js backend
        // For this demo, we'll simulate generating variations
        
        $results = array();
        $baseSeed = isset($params['seed']) ? intval($params['seed']) : mt_rand(1000000, 9999999);
        $strengths = isset($params['variationStrengths']) ? $params['variationStrengths'] : array(0.25, 0.5, 0.75);
        
        // Generate variations
        for ($i = 0; $i < $count; $i++) {
            // Calculate a new seed based on the original
            $variationSeed = ($baseSeed + ($i * 17713)) % 999999; // Prime number offset
            
            // Strength index (wrapped if needed)
            $strengthIndex = $i % count($strengths);
            
            // Clone parameters and modify for this variation
            $variationParams = $params;
            $variationParams['seed'] = $variationSeed;
            $variationParams['strength'] = $strengths[$strengthIndex];
            $variationParams['variation_id'] = "var_{$i}_{$variationSeed}";
            $variationParams['is_variation'] = true;
            $variationParams['parent_id'] = isset($params['request_id']) ? $params['request_id'] : $baseSeed;
            
            // Add metadata
            $variationParams['metadata'] = array(
                'variation_number' => $i + 1,
                'variation_of' => isset($params['request_id']) ? $params['request_id'] : 'original',
                'strength' => $strengths[$strengthIndex]
            );
            
            // Generate filepath for source image
            $filepath = is_array($source_image) ? $source_image['path'] : $source_image;
            
            // Process the variation
            $result = $this->process_image($filepath, $variationParams);
            $results[] = $result;
        }
        
        return $results;
    }

    /**
     * Get task status (simulated for this demo)
     */
    private function get_task_status($task_id) {
        // In a real implementation, this would call the Node.js backend
        // For this demo, we'll simulate task status
        
        // Check if task ID matches expected format
        if (strpos($task_id, 'img_') !== 0) {
            return null; // Task not found
        }
        
        // Simulate different statuses based on task ID
        $last_char = substr($task_id, -1);
        $last_digit = is_numeric($last_char) ? intval($last_char) : 5;
        
        // Determine status based on last digit
        if ($last_digit < 3) {
            // In progress
            $progress = $last_digit * 30 + 10; // 10, 40, 70
            return array(
                'task_id' => $task_id,
                'status' => 'processing',
                'progress' => $progress,
                'started_at' => date('c', time() - 60),
                'processing_time' => 60000, // 60 seconds
            );
        } elseif ($last_digit < 8) {
            // Completed
            return array(
                'task_id' => $task_id,
                'status' => 'completed',
                'progress' => 100,
                'started_at' => date('c', time() - 120),
                'processing_time' => mt_rand(2000, 5000),
                'completed_at' => date('c', time() - 30)
            );
        } else {
            // Failed
            return array(
                'task_id' => $task_id,
                'status' => 'failed',
                'progress' => mt_rand(10, 90),
                'started_at' => date('c', time() - 180),
                'processing_time' => mt_rand(1000, 3000),
                'error' => 'Simulated processing error'
            );
        }
    }

    /**
     * Clear HURAII cache (simulated for this demo)
     */
    private function clear_cache() {
        // In a real implementation, this would call the Node.js backend
        // For this demo, we'll simulate clearing cache by deleting temp files
        
        // Delete temp files
        $temp_files = glob(VORTEX_HURAII_TEMP_DIR . '*');
        foreach ($temp_files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        
        return true;
    }

    /**
     * Test connection to HURAII server (simulated for this demo)
     */
    private function test_connection() {
        // In a real implementation, this would call the Node.js backend
        // For this demo, we'll simulate a successful connection
        
        return array(
            'success' => true,
            'details' => array(
                'version' => '1.0.0',
                'connected_at' => date('c'),
                'features' => array(
                    'image_upload' => true,
                    'variations' => true,
                    'caching' => true
                )
            )
        );
    }
}

// Initialize the plugin
$vortex_huraii = Vortex_HURAII_Integration::get_instance(); 