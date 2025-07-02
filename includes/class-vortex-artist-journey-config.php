<?php
/**
 * VORTEX Artist Journey Configuration Manager
 * 
 * Centralized configuration management for optimal performance and maintainability
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Artist_Journey
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_Artist_Journey_Config {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Configuration cache
     */
    private $config_cache = array();
    
    /**
     * Default configuration values
     */
    private $defaults = array(
        // File Upload Settings
        'max_file_size' => 50 * 1024 * 1024, // 50MB
        'max_files_per_upload' => 10,
        'allowed_file_types' => array('image/jpeg', 'image/png', 'image/webp', 'image/gif'),
        'file_copy_chunk_size' => 1024 * 1024, // 1MB chunks
        's3_chunk_size' => 5 * 1024 * 1024, // 5MB chunks for S3 multipart
        
        // Rate Limiting
        'rate_limits' => array(
            'plan_selection' => array('requests' => 5, 'window' => 300), // 5 per 5 minutes
            'payment_processing' => array('requests' => 3, 'window' => 3600), // 3 per hour
            'ai_generation' => array('requests' => 10, 'window' => 3600), // 10 per hour
            'file_upload' => array('requests' => 20, 'window' => 3600), // 20 per hour
            'chloe_api' => array('requests' => 30, 'window' => 3600), // 30 per hour
            'nft_minting' => array('requests' => 5, 'window' => 3600) // 5 per hour
        ),
        
        // Cache TTL Settings
        'cache_ttl' => array(
            'user_progress' => 300, // 5 minutes
            'chloe_inspiration' => 1800, // 30 minutes
            'collector_matches' => 3600, // 1 hour
            'milestones' => 600, // 10 minutes
            'user_artwork_summary' => 300, // 5 minutes
            'monthly_uploads' => 300, // 5 minutes
            'storage_usage' => 600, // 10 minutes
            'default' => 300 // 5 minutes
        ),
        
        // Payment Processing
        'min_purchase_amount' => 1,
        'max_purchase_amount' => 1000,
        'stripe_api_version' => '2023-10-16',
        'paypal_mode' => 'sandbox', // 'sandbox' or 'live'
        
        // AI Integration
        'automatic1111_timeout' => 60,
        'chloe_api_timeout' => 15,
        'max_ai_generations_per_day' => 50,
        
        // Thumbnail Generation
        'thumbnail_sizes' => array(
            'small' => array(150, 150),
            'medium' => array(300, 300),
            'large' => array(600, 600)
        ),
        
        // TOLA Token Rewards
        'tola_rewards' => array(
            'artwork_upload' => 5,
            'artwork_download' => 2,
            'artwork_sale_percentage' => 0.10, // 10%
            'milestone_completion' => 15,
            'collaboration_participation' => 10,
            'daily_login' => 1,
            'profile_completion' => 25
        ),
        
        // Database Query Optimization
        'max_query_results' => 100,
        'artwork_summary_limit' => 5,
        'collector_match_limit' => 10,
        
        // Security Settings
        'max_login_attempts' => 5,
        'login_lockout_duration' => 900, // 15 minutes
        'password_min_length' => 8,
        'require_2fa_for_payments' => false,
        
        // Error Handling
        'log_to_database' => true,
        'max_error_log_entries' => 1000,
        'error_notification_threshold' => 10, // errors per hour
        
        // Performance Optimization
        'enable_object_cache' => true,
        'enable_cdn' => false,
        'lazy_load_images' => true,
        'compress_responses' => true,
        
        // Email Settings
        'email_batch_size' => 50,
        'email_delay_between_batches' => 5, // seconds
        'max_email_retries' => 3,
        
        // Subscription Plan Limits
        'plan_limits' => array(
            'starter' => array(
                'artworks_per_month' => 5,
                'storage_mb' => 100,
                'ai_generations_per_day' => 10,
                'collaborations_per_month' => 2
            ),
            'pro' => array(
                'artworks_per_month' => -1, // unlimited
                'storage_mb' => 1000,
                'ai_generations_per_day' => 50,
                'collaborations_per_month' => 10
            ),
            'studio' => array(
                'artworks_per_month' => -1, // unlimited
                'storage_mb' => 5000,
                'ai_generations_per_day' => 200,
                'collaborations_per_month' => -1 // unlimited
            )
        )
    );
    
    /**
     * Get instance
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
        $this->load_configuration();
    }
    
    /**
     * Load configuration from cache or database
     */
    public function load_configuration() {
        $this->config_cache = wp_cache_get('vortex_artist_journey_config');
        
        if (false === $this->config_cache) {
            $stored_config = get_option('vortex_artist_journey_config', array());
            $this->config_cache = array_merge($this->defaults, $stored_config);
            
            wp_cache_set('vortex_artist_journey_config', $this->config_cache, '', 3600);
        }
    }
    
    /**
     * Get configuration value with path support
     */
    public function get($path, $default = null) {
        $keys = explode('.', $path);
        $value = $this->config_cache;
        
        foreach ($keys as $key) {
            if (isset($value[$key])) {
                $value = $value[$key];
            } else {
                return $default;
            }
        }
        
        return $value;
    }
    
    /**
     * Get all configuration
     */
    public function get_all() {
        return $this->config_cache;
    }
}

// Initialize the configuration manager
function vortex_artist_journey_config() {
    return VORTEX_Artist_Journey_Config::get_instance();
} 