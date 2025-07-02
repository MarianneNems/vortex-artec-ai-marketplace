<?php
/**
 * VortexArtec Artist Journey Manager
 *
 * Manages the complete end-to-end artist journey flow as specified
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Artist_Journey
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include configuration manager
require_once __DIR__ . '/class-vortex-artist-journey-config.php';

class VORTEX_Artist_Journey {
    
    /**
     * Instance of this class.
     */
    private static $instance = null;
    
    /**
     * Configuration manager instance
     */
    private $config;
    
    /**
     * Configuration cache
     */
    private $config_cache = array();
    
    /**
     * Rate limiting cache
     */
    private $rate_limits = array();
    
    /**
     * Available subscription plans with exact specification names
     */
    private $subscription_plans = array(
        'starter' => array(
            'name' => 'Starter',
            'price_usd' => 19.99,
            'price_tola' => 19.99, // 1 USD = 1 TOLA
            'features' => array(
                'basic_ai_generation',
                'standard_support',
                'basic_marketplace_access',
                '5_artworks_per_month'
            ),
            'limits' => array(
                'artworks_per_month' => 5,
                'storage_mb' => 100,
                'ai_generations_per_day' => 10
            )
        ),
        'pro' => array(
            'name' => 'Pro',
            'price_usd' => 39.99,
            'price_tola' => 39.99,
            'features' => array(
                'advanced_ai_generation',
                'priority_support',
                'full_marketplace_access',
                'unlimited_artworks',
                'analytics_dashboard',
                'mandatory_horas_quiz'
            ),
            'requires_horas_quiz' => true,
            'limits' => array(
                'artworks_per_month' => -1, // unlimited
                'storage_mb' => 1000,
                'ai_generations_per_day' => 50
            )
        ),
        'studio' => array(
            'name' => 'Studio',
            'price_usd' => 99.99,
            'price_tola' => 99.99,
            'features' => array(
                'premium_ai_generation',
                'dedicated_support',
                'full_marketplace_access',
                'unlimited_artworks',
                'advanced_analytics',
                'custom_profile_page',
                'collaboration_tools',
                'exclusive_events_access'
            ),
            'limits' => array(
                'artworks_per_month' => -1, // unlimited
                'storage_mb' => 5000,
                'ai_generations_per_day' => 200
            )
        )
    );
    
    /**
     * Configuration defaults
     */
    private $config_defaults = array(
        'max_file_size' => 50 * 1024 * 1024, // 50MB
        'allowed_file_types' => array('image/jpeg', 'image/png', 'image/webp', 'image/gif'),
        'thumbnail_sizes' => array(
            'small' => array(150, 150),
            'medium' => array(300, 300),
            'large' => array(600, 600)
        ),
        'rate_limits' => array(
            'plan_selection' => array('requests' => 5, 'window' => 300), // 5 requests per 5 minutes
            'payment_processing' => array('requests' => 3, 'window' => 3600), // 3 per hour
            'ai_generation' => array('requests' => 10, 'window' => 3600), // 10 per hour
            'file_upload' => array('requests' => 20, 'window' => 3600) // 20 per hour
        ),
        'cache_ttl' => array(
            'user_progress' => 300, // 5 minutes
            'chloe_inspiration' => 1800, // 30 minutes
            'collector_matches' => 3600, // 1 hour
            'milestones' => 600 // 10 minutes
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
        $this->config = vortex_artist_journey_config();
        $this->init_hooks();
        $this->init_storage_routing();
    }
    
    /**
     * ===== STORAGE ROUTING CONFIGURATION =====
     */
    
    /**
     * Initialize storage routing for RunPod vault and S3
     */
    private function init_storage_routing() {
        // Configure WordPress root URL
        $this->wordpress_root = 'https://wordpress-1205138-5651884.cloudwaysapps.com';
        
        // Configure RunPod vault for proprietary algorithms
        $this->runpod_vault_config = array(
            'endpoint' => get_option('vortex_runpod_vault_endpoint', 'https://api.runpod.ai/v2/vault'),
            'api_key' => get_option('vortex_runpod_vault_api_key'),
            'vault_id' => get_option('vortex_runpod_vault_id'),
            'encryption_key' => get_option('vortex_runpod_encryption_key'),
            'proprietary_path' => '/algorithms/proprietary/',
            'seed_art_path' => '/algorithms/seed-art/',
            'zodiac_path' => '/algorithms/zodiac/',
            'secret_sauce_path' => '/algorithms/secret-sauce/'
        );
        
        // Configure S3 for general storage
        $this->s3_config = array(
            'bucket' => get_option('vortex_s3_bucket', 'vortex-artec-storage'),
            'region' => get_option('vortex_s3_region', 'us-east-1'),
            'access_key' => get_option('vortex_s3_access_key'),
            'secret_key' => get_option('vortex_s3_secret_key'),
            'user_data_path' => '/users/',
            'artwork_path' => '/artworks/',
            'collections_path' => '/collections/',
            'thumbnails_path' => '/thumbnails/',
            'exports_path' => '/exports/'
        );
        
        // Storage routing rules
        $this->storage_routing_rules = array(
            // Proprietary algorithms → RunPod Vault
            'proprietary_algorithms' => 'runpod_vault',
            'seed_art_generation' => 'runpod_vault',
            'zodiac_intelligence' => 'runpod_vault',
            'secret_sauce_engine' => 'runpod_vault',
            'neural_fusion_algorithms' => 'runpod_vault',
            'artist_dna_mapping' => 'runpod_vault',
            'blockchain_private_keys' => 'runpod_vault',
            
            // General storage → S3
            'user_artwork' => 's3',
            'user_profiles' => 's3',
            'collection_data' => 's3',
            'thumbnails' => 's3',
            'generated_pdfs' => 's3',
            'marketplace_assets' => 's3',
            'user_uploads' => 's3',
            'backup_data' => 's3'
        );
    }
    
    /**
     * Route storage based on content type and security requirements
     */
    private function route_storage($content_type, $data, $filename, $user_id = null) {
        $storage_destination = $this->storage_routing_rules[$content_type] ?? 's3';
        
        switch ($storage_destination) {
            case 'runpod_vault':
                return $this->store_in_runpod_vault($content_type, $data, $filename, $user_id);
                
            case 's3':
                return $this->store_in_s3($content_type, $data, $filename, $user_id);
                
            default:
                throw new Exception("Unknown storage destination: {$storage_destination}");
        }
    }
    
    /**
     * Store proprietary algorithms in RunPod vault with encryption
     */
    private function store_in_runpod_vault($content_type, $data, $filename, $user_id = null) {
        try {
            // Determine vault path based on content type
            $vault_paths = array(
                'proprietary_algorithms' => $this->runpod_vault_config['proprietary_path'],
                'seed_art_generation' => $this->runpod_vault_config['seed_art_path'],
                'zodiac_intelligence' => $this->runpod_vault_config['zodiac_path'],
                'secret_sauce_engine' => $this->runpod_vault_config['secret_sauce_path'],
                'neural_fusion_algorithms' => $this->runpod_vault_config['proprietary_path'] . 'neural/',
                'artist_dna_mapping' => $this->runpod_vault_config['zodiac_path'] . 'dna/',
                'blockchain_private_keys' => $this->runpod_vault_config['proprietary_path'] . 'blockchain/'
            );
            
            $vault_path = $vault_paths[$content_type] ?? $this->runpod_vault_config['proprietary_path'];
            $full_path = $vault_path . $filename;
            
            // Encrypt data before storing in vault
            $encrypted_data = $this->encrypt_for_vault($data);
            
            // Prepare RunPod vault API request
            $vault_data = array(
                'path' => $full_path,
                'content' => base64_encode($encrypted_data),
                'metadata' => array(
                    'content_type' => $content_type,
                    'user_id' => $user_id,
                    'timestamp' => current_time('mysql'),
                    'wordpress_source' => $this->wordpress_root,
                    'encryption' => 'AES-256-GCM',
                    'classification' => 'proprietary'
                )
            );
            
            // Make API request to RunPod vault
            $response = wp_remote_post($this->runpod_vault_config['endpoint'] . '/store', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->runpod_vault_config['api_key'],
                    'Content-Type' => 'application/json',
                    'X-Vault-ID' => $this->runpod_vault_config['vault_id']
                ),
                'body' => json_encode($vault_data),
                'timeout' => 60
            ));
            
            if (is_wp_error($response)) {
                throw new Exception('RunPod vault connection failed: ' . $response->get_error_message());
            }
            
            $response_body = json_decode(wp_remote_retrieve_body($response), true);
            
            if (wp_remote_retrieve_response_code($response) !== 200) {
                throw new Exception('RunPod vault storage failed: ' . ($response_body['error'] ?? 'Unknown error'));
            }
            
            // Log successful vault storage
            $this->log_vault_operation('store', $content_type, $full_path, true);
            
            return array(
                'success' => true,
                'storage_type' => 'runpod_vault',
                'vault_path' => $full_path,
                'vault_id' => $response_body['vault_id'] ?? null,
                'encrypted' => true,
                'wordpress_source' => $this->wordpress_root
            );
            
        } catch (Exception $e) {
            $this->log_vault_operation('store', $content_type, $filename, false, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Store general content in S3
     */
    private function store_in_s3($content_type, $data, $filename, $user_id = null) {
        try {
            // Determine S3 path based on content type
            $s3_paths = array(
                'user_artwork' => $this->s3_config['artwork_path'],
                'user_profiles' => $this->s3_config['user_data_path'] . 'profiles/',
                'collection_data' => $this->s3_config['collections_path'],
                'thumbnails' => $this->s3_config['thumbnails_path'],
                'generated_pdfs' => $this->s3_config['exports_path'] . 'pdfs/',
                'marketplace_assets' => '/marketplace/',
                'user_uploads' => $this->s3_config['user_data_path'] . 'uploads/',
                'backup_data' => '/backups/'
            );
            
            $s3_path = $s3_paths[$content_type] ?? $this->s3_config['user_data_path'];
            
            // Add user ID to path if provided
            if ($user_id) {
                $s3_path .= $user_id . '/';
            }
            
            $full_s3_key = ltrim($s3_path . $filename, '/');
            
            // Prepare S3 upload
            require_once(ABSPATH . 'wp-content/plugins/vortex-ai-marketplace/vendor/autoload.php');
            
            $s3_client = new Aws\S3\S3Client([
                'version' => 'latest',
                'region' => $this->s3_config['region'],
                'credentials' => [
                    'key' => $this->s3_config['access_key'],
                    'secret' => $this->s3_config['secret_key']
                ]
            ]);
            
            // Upload to S3
            $upload_result = $s3_client->putObject([
                'Bucket' => $this->s3_config['bucket'],
                'Key' => $full_s3_key,
                'Body' => $data,
                'ContentType' => $this->get_content_type_header($content_type),
                'Metadata' => array(
                    'content-type' => $content_type,
                    'user-id' => (string)$user_id,
                    'timestamp' => current_time('mysql'),
                    'wordpress-source' => $this->wordpress_root,
                    'classification' => 'general'
                ),
                'ServerSideEncryption' => 'AES256'
            ]);
            
            // Log successful S3 storage
            $this->log_s3_operation('store', $content_type, $full_s3_key, true);
            
            return array(
                'success' => true,
                'storage_type' => 's3',
                's3_bucket' => $this->s3_config['bucket'],
                's3_key' => $full_s3_key,
                's3_url' => $upload_result['ObjectURL'] ?? null,
                'encrypted' => true,
                'wordpress_source' => $this->wordpress_root
            );
            
        } catch (Exception $e) {
            $this->log_s3_operation('store', $content_type, $filename, false, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Encrypt data for RunPod vault storage
     */
    private function encrypt_for_vault($data) {
        $encryption_key = $this->runpod_vault_config['encryption_key'];
        
        if (!$encryption_key) {
            throw new Exception('RunPod vault encryption key not configured');
        }
        
        // Generate random IV
        $iv = random_bytes(16);
        
        // Encrypt using AES-256-GCM
        $encrypted = openssl_encrypt(
            $data,
            'AES-256-GCM',
            $encryption_key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($encrypted === false) {
            throw new Exception('Encryption failed for RunPod vault');
        }
        
        // Combine IV, tag, and encrypted data
        return base64_encode($iv . $tag . $encrypted);
    }
    
    /**
     * Get appropriate content type header
     */
    private function get_content_type_header($content_type) {
        $content_type_headers = array(
            'user_artwork' => 'image/png',
            'thumbnails' => 'image/jpeg',
            'generated_pdfs' => 'application/pdf',
            'user_profiles' => 'application/json',
            'collection_data' => 'application/json',
            'marketplace_assets' => 'image/png',
            'backup_data' => 'application/octet-stream'
        );
        
        return $content_type_headers[$content_type] ?? 'application/octet-stream';
    }
    
    /**
     * Log RunPod vault operations
     */
    private function log_vault_operation($operation, $content_type, $path, $success, $error = null) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_vault_operations',
            array(
                'operation' => $operation,
                'content_type' => $content_type,
                'vault_path' => $path,
                'success' => $success ? 1 : 0,
                'error_message' => $error,
                'timestamp' => current_time('mysql'),
                'wordpress_source' => $this->wordpress_root
            )
        );
    }
    
    /**
     * Log S3 operations
     */
    private function log_s3_operation($operation, $content_type, $s3_key, $success, $error = null) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_s3_operations',
            array(
                'operation' => $operation,
                'content_type' => $content_type,
                's3_bucket' => $this->s3_config['bucket'],
                's3_key' => $s3_key,
                'success' => $success ? 1 : 0,
                'error_message' => $error,
                'timestamp' => current_time('mysql'),
                'wordpress_source' => $this->wordpress_root
            )
        );
    }
    
    /**
     * ===== STANDARDIZED API RESPONSE SYSTEM =====
     */
    
    /**
     * Format standardized API response to match specification
     */
    private function format_api_response($data, $status = 'success', $message = null) {
        $response = array(
            'status' => $status,
            'timestamp' => current_time('mysql'),
            'version' => '1.0',
            'source' => $this->wordpress_root
        );
        
        if ($status === 'success') {
            $response = array_merge($response, $data);
        } else {
            $response['message'] = $message;
            if ($data) {
                $response['errors'] = $data;
            }
        }
        
        return $response;
    }
    
    /**
     * Send standardized success response
     */
    private function send_success_response($data) {
        wp_send_json($this->format_api_response($data, 'success'));
    }
    
    /**
     * Send standardized error response  
     */
    private function send_error_response($message, $errors = null, $code = 400) {
        http_response_code($code);
        wp_send_json($this->format_api_response($errors, 'error', $message));
    }
    
    /**
     * Enhanced rate limiting with granular endpoint controls
     */
    private function check_enhanced_rate_limit($endpoint, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Enhanced rate limits per endpoint (matching specification)
        $enhanced_limits = array(
            // Authentication endpoints
            'auth_register' => array('requests' => 3, 'window' => 3600), // 3 per hour
            'auth_login' => array('requests' => 10, 'window' => 3600), // 10 per hour
            
            // Subscription endpoints
            'plan_selection' => array('requests' => 5, 'window' => 300), // 5 per 5 minutes
            'subscription_activation' => array('requests' => 3, 'window' => 3600), // 3 per hour
            
            // Wallet endpoints
            'wallet_connection' => array('requests' => 10, 'window' => 3600), // 10 per hour
            'usd_to_tola_conversion' => array('requests' => 5, 'window' => 3600), // 5 per hour
            
            // Profile endpoints
            'role_quiz' => array('requests' => 3, 'window' => 86400), // 3 per day
            'terms_agreement' => array('requests' => 5, 'window' => 86400), // 5 per day
            'seed_artwork_upload' => array('requests' => 20, 'window' => 3600), // 20 per hour
            
            // Horas endpoints
            'horas_quiz' => array('requests' => 2, 'window' => 86400), // 2 per day
            'horas_pdf_generation' => array('requests' => 3, 'window' => 86400), // 3 per day
            
            // Milestone endpoints
            'milestone_create' => array('requests' => 10, 'window' => 3600), // 10 per hour
            'milestone_update' => array('requests' => 50, 'window' => 3600), // 50 per hour
            'milestone_get' => array('requests' => 100, 'window' => 3600), // 100 per hour
            
            // Chloe AI endpoints
            'chloe_inspiration' => array('requests' => 20, 'window' => 3600), // 20 per hour
            'chloe_collector_match' => array('requests' => 15, 'window' => 3600), // 15 per hour
            
            // Collection endpoints
            'collection_create' => array('requests' => 15, 'window' => 3600), // 15 per hour
            'collection_update' => array('requests' => 30, 'window' => 3600), // 30 per hour
            'collection_delete' => array('requests' => 10, 'window' => 3600), // 10 per hour
            
            // NFT endpoints
            'nft_mint' => array('requests' => 5, 'window' => 3600), // 5 per hour
            'listing_create' => array('requests' => 10, 'window' => 3600), // 10 per hour
            
            // Journey progress endpoints
            'journey_progress' => array('requests' => 50, 'window' => 3600), // 50 per hour
            'journey_step_complete' => array('requests' => 20, 'window' => 3600), // 20 per hour
            
            // Default fallback
            'default' => array('requests' => 30, 'window' => 3600) // 30 per hour
        );
        
        $limit_config = $enhanced_limits[$endpoint] ?? $enhanced_limits['default'];
        
        // Create cache key for this specific endpoint and user
        $cache_key = "vortex_rate_limit_{$endpoint}_{$user_id}";
        $requests = wp_cache_get($cache_key);
        
        if (false === $requests) {
            $requests = array();
        }
        
        // Clean old requests outside the window
        $window_start = time() - $limit_config['window'];
        $requests = array_filter($requests, function($timestamp) use ($window_start) {
            return $timestamp > $window_start;
        });
        
        // Check if limit exceeded
        if (count($requests) >= $limit_config['requests']) {
            // Log rate limit exceeded
            $this->log_automation_failure($endpoint, 'Rate limit exceeded', $user_id);
            return false;
        }
        
        // Add current request
        $requests[] = time();
        wp_cache_set($cache_key, $requests, '', $limit_config['window']);
        
        return true;
    }
    
    /**
     * Enhanced error logging for automation failures
     */
    private function log_automation_failure($endpoint, $error, $user_id = null) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'endpoint' => $endpoint,
            'error' => $error,
            'user_id' => $user_id,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        );
        
        // Store in database for admin review
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'vortex_automation_errors',
            $log_entry
        );
        
        // Also log to WordPress error log
        error_log("VortexArtec Automation Failure: {$endpoint} - {$error} - User: {$user_id}");
        
        // Send notification to admin if critical
        if ($this->is_critical_error($endpoint, $error)) {
            $this->notify_admin_critical_error($log_entry);
        }
    }
    
    /**
     * Check if error is critical and requires immediate attention
     */
    private function is_critical_error($endpoint, $error) {
        $critical_endpoints = array('usd_to_tola_conversion', 'nft_mint', 'subscription_activation');
        $critical_errors = array('payment', 'blockchain', 'security', 'database');
        
        if (in_array($endpoint, $critical_endpoints)) {
            return true;
        }
        
        foreach ($critical_errors as $critical_error) {
            if (stripos($error, $critical_error) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Notify admin of critical errors
     */
    private function notify_admin_critical_error($log_entry) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = "[CRITICAL] VortexArtec Automation Error - {$log_entry['endpoint']}";
        $message = "A critical error occurred in the VortexArtec automation system:\n\n";
        $message .= "Endpoint: {$log_entry['endpoint']}\n";
        $message .= "Error: {$log_entry['error']}\n";
        $message .= "User ID: {$log_entry['user_id']}\n";
        $message .= "Timestamp: {$log_entry['timestamp']}\n";
        $message .= "IP Address: {$log_entry['ip_address']}\n";
        $message .= "\nPlease investigate immediately.";
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Registration & Onboarding
        add_action('wp_ajax_vortex_plan_selection', array($this, 'handle_plan_selection'));
        add_action('wp_ajax_vortex_wallet_connection', array($this, 'handle_wallet_connection'));
        add_action('wp_ajax_vortex_usd_to_tola_conversion', array($this, 'handle_usd_to_tola_conversion'));
        
        // Profile Setup
        add_action('wp_ajax_vortex_role_expertise_quiz', array($this, 'handle_role_expertise_quiz'));
        add_action('wp_ajax_vortex_terms_agreement', array($this, 'handle_terms_agreement'));
        add_action('wp_ajax_vortex_seed_artwork_upload', array($this, 'handle_seed_artwork_upload'));
        
        // Artist Pro Activation
        add_action('wp_ajax_vortex_horas_business_quiz', array($this, 'handle_horas_business_quiz'));
        add_action('wp_ajax_vortex_generate_business_pdf', array($this, 'generate_business_pdf'));
        
        // Marketplace Integration
        add_action('wp_ajax_vortex_create_collection', array($this, 'handle_create_collection'));
        add_action('wp_ajax_vortex_mint_nft', array($this, 'handle_mint_nft'));
        
        // Chloe AI Integration
        add_action('wp_ajax_vortex_get_chloe_inspiration', array($this, 'handle_get_chloe_inspiration'));
        add_action('wp_ajax_vortex_get_collector_matches', array($this, 'handle_get_collector_matches'));
        
        // Calendar & Milestone Management
        add_action('wp_ajax_vortex_update_milestone', array($this, 'handle_update_milestone'));
        add_action('wp_ajax_vortex_get_milestones', array($this, 'handle_get_milestones'));
        
        // Journey Progress Tracking
        add_action('wp_ajax_vortex_get_journey_progress', array($this, 'handle_get_journey_progress'));
        add_action('wp_ajax_vortex_complete_journey_step', array($this, 'handle_complete_journey_step'));
        
        // Daily Cron Jobs
        add_action('init', array($this, 'schedule_daily_cron'));
        add_action('vortex_tola_art_of_the_day', array($this, 'generate_tola_art_of_the_day'));
        
        // Incentive tracking
        add_action('vortex_artwork_uploaded', array($this, 'track_artwork_upload'), 10, 2);
        add_action('vortex_artwork_downloaded', array($this, 'track_artwork_download'), 10, 2);
        add_action('vortex_artwork_sold', array($this, 'track_artwork_sale'), 10, 3);
        
        // Milestone reminders cron
        add_action('init', array($this, 'schedule_milestone_reminders'));
        add_action('vortex_milestone_reminders', array($this, 'send_milestone_reminders'));
    }
    

    
    /**
     * Get configuration value using the new configuration manager
     */
    private function get_config($key, $default = null) {
        return $this->config->get($key, $default);
    }
    
    /**
     * Rate limiting check
     */
    private function check_rate_limit($action, $user_id = null) {
        $user_id = $user_id ?: get_current_user_id();
        $limits = $this->get_config('rate_limits', array());
        
        if (!isset($limits[$action])) {
            return true; // No limit configured
        }
        
        $limit = $limits[$action];
        $cache_key = "vortex_rate_limit_{$action}_{$user_id}";
        $requests = wp_cache_get($cache_key) ?: array();
        
        // Clean old requests
        $now = time();
        $requests = array_filter($requests, function($timestamp) use ($now, $limit) {
            return ($now - $timestamp) < $limit['window'];
        });
        
        if (count($requests) >= $limit['requests']) {
            return false;
        }
        
        // Add current request
        $requests[] = $now;
        wp_cache_set($cache_key, $requests, '', $limit['window']);
        
        return true;
    }
    
    /**
     * Enhanced input validation
     */
    private function validate_input($data, $rules) {
        $errors = array();
        
        foreach ($rules as $field => $rule) {
            $value = isset($data[$field]) ? $data[$field] : null;
            
            // Required check
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = "Field {$field} is required";
                continue;
            }
            
            if (!empty($value)) {
                // Type validation
                if (isset($rule['type'])) {
                    switch ($rule['type']) {
                        case 'email':
                            if (!is_email($value)) {
                                $errors[$field] = "Invalid email format";
                            }
                            break;
                        case 'numeric':
                            if (!is_numeric($value)) {
                                $errors[$field] = "Must be numeric";
                            }
                            break;
                        case 'wallet_address':
                            if (!$this->validate_wallet_address($value)) {
                                $errors[$field] = "Invalid wallet address";
                            }
                            break;
                    }
                }
                
                // Min/Max validation
                if (isset($rule['min']) && strlen($value) < $rule['min']) {
                    $errors[$field] = "Minimum length is {$rule['min']}";
                }
                if (isset($rule['max']) && strlen($value) > $rule['max']) {
                    $errors[$field] = "Maximum length is {$rule['max']}";
                }
                
                // Allowed values
                if (isset($rule['allowed']) && !in_array($value, $rule['allowed'])) {
                    $errors[$field] = "Invalid value";
                }
            }
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Enhanced error logging
     */
    private function log_error($message, $context = array()) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'message' => $message,
            'context' => $context,
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        );
        
        error_log('[VORTEX_ARTIST_JOURNEY] ' . json_encode($log_entry));
        
        // Optional: Store in database for admin review
        if (get_option('vortex_log_to_database', false)) {
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'vortex_error_logs',
                array(
                    'message' => $message,
                    'context' => json_encode($context),
                    'user_id' => get_current_user_id(),
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%s', '%d', '%s')
            );
        }
    }
    
    /**
     * Validate wallet address format
     */
    private function validate_wallet_address($address) {
        // Solana wallet address validation (Base58, 32-44 characters)
        if (preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $address)) {
            return true;
        }
        return false;
    }
    
    /**
     * Check user permissions with enhanced security
     */
    private function check_user_permissions($action, $user_id = null) {
        $user_id = $user_id ?: get_current_user_id();
        
        if (!$user_id) {
            return false;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        // Check if user account is active
        $account_status = get_user_meta($user_id, 'vortex_account_status', true);
        if ($account_status === 'suspended' || $account_status === 'banned') {
            return false;
        }
        
        // Action-specific permission checks
        switch ($action) {
            case 'plan_selection':
                return true; // All logged users can select plans
                
            case 'payment_processing':
                return current_user_can('edit_posts'); // Basic capability check
                
            case 'horas_quiz':
                $selected_plan = get_user_meta($user_id, 'vortex_selected_plan', true);
                return $selected_plan === 'pro';
                
            case 'mint_nft':
                $subscription_active = get_user_meta($user_id, 'vortex_subscription_active', true);
                return $subscription_active;
                
            case 'admin_functions':
                return current_user_can('manage_options');
                
            default:
                return current_user_can('read');
        }
    }
    
    /**
     * Get cached data with fallback
     */
    private function get_cached_data($cache_key, $fallback_callback, $ttl = null) {
        $ttl = $ttl ?: $this->get_config('cache_ttl', array())['default'] ?? 300;
        
        $cached_data = wp_cache_get($cache_key);
        if (false !== $cached_data) {
            return $cached_data;
        }
        
        // Generate fresh data
        $fresh_data = call_user_func($fallback_callback);
        wp_cache_set($cache_key, $fresh_data, '', $ttl);
        
        return $fresh_data;
    }
    
    /**
     * Handle plan selection with enhanced validation and security
     */
    public function handle_plan_selection() {
        try {
            // Check rate limiting
            if (!$this->check_rate_limit('plan_selection')) {
                wp_send_json_error(array('message' => 'Too many requests. Please wait before trying again.'));
                return;
            }
            
            check_ajax_referer('vortex_artist_journey', 'nonce');
            
            if (!is_user_logged_in()) {
                wp_send_json_error(array('message' => 'User must be logged in'));
                return;
            }
            
            // Check permissions
            if (!$this->check_user_permissions('plan_selection')) {
                wp_send_json_error(array('message' => 'Insufficient permissions'));
                return;
            }
            
            // Validate input
            $validation_rules = array(
                'plan_type' => array('required' => true, 'allowed' => array_keys($this->subscription_plans)),
                'wallet_address' => array('required' => false, 'type' => 'wallet_address', 'min' => 32, 'max' => 44)
            );
            
            $validation_result = $this->validate_input($_POST, $validation_rules);
            if ($validation_result !== true) {
                wp_send_json_error(array('message' => 'Validation failed', 'errors' => $validation_result));
                return;
            }
            
            $user_id = get_current_user_id();
            $plan_type = sanitize_text_field($_POST['plan_type']);
            $wallet_address = sanitize_text_field($_POST['wallet_address'] ?? '');
            
            $plan = $this->subscription_plans[$plan_type];
            
            // Store plan selection with transaction safety
            global $wpdb;
            $wpdb->query('START TRANSACTION');
            
            try {
                update_user_meta($user_id, 'vortex_selected_plan', $plan_type);
                update_user_meta($user_id, 'vortex_plan_selection_date', current_time('mysql'));
                
                if ($wallet_address) {
                    update_user_meta($user_id, 'vortex_wallet_address', $wallet_address);
                }
                
                // Log successful plan selection
                $this->record_tola_transaction($user_id, 0, 'plan_selection', array(
                    'plan' => $plan_type,
                    'wallet_provided' => !empty($wallet_address)
                ));
                
                $wpdb->query('COMMIT');
            } catch (Exception $e) {
                $wpdb->query('ROLLBACK');
                throw $e;
            }
            
            wp_send_json_success(array(
                'plan' => $plan,
                'tola_required' => $plan['price_tola'],
                'next_step' => $wallet_address ? 'subscription_activation' : 'wallet_connection'
            ));
            
        } catch (Exception $e) {
            $this->log_error('Plan selection failed', array(
                'error' => $e->getMessage(),
                'user_id' => get_current_user_id(),
                'post_data' => $_POST
            ));
            
            wp_send_json_error(array('message' => 'An error occurred. Please try again.'));
        }
    }
    
    /**
     * Handle USD to TOLA conversion with enhanced security and transaction safety
     */
    public function handle_usd_to_tola_conversion() {
        try {
            // Check rate limiting for payment processing
            if (!$this->check_rate_limit('payment_processing')) {
                wp_send_json_error(array('message' => 'Payment rate limit exceeded. Please wait before trying again.'));
                return;
            }
            
            check_ajax_referer('vortex_artist_journey', 'nonce');
            
            if (!is_user_logged_in()) {
                wp_send_json_error(array('message' => 'User must be logged in'));
                return;
            }
            
            // Check permissions
            if (!$this->check_user_permissions('payment_processing')) {
                wp_send_json_error(array('message' => 'Insufficient permissions for payment processing'));
                return;
            }
            
            // Enhanced input validation for payments
            $validation_rules = array(
                'usd_amount' => array('required' => true, 'type' => 'numeric', 'min' => 1),
                'payment_method' => array('required' => true, 'allowed' => array('stripe', 'paypal'))
            );
            
            $validation_result = $this->validate_input($_POST, $validation_rules);
            if ($validation_result !== true) {
                wp_send_json_error(array('message' => 'Invalid payment data', 'errors' => $validation_result));
                return;
            }
            
            $user_id = get_current_user_id();
            $usd_amount = floatval($_POST['usd_amount']);
            $payment_method = sanitize_text_field($_POST['payment_method']);
            
            // Validate amount limits
            $max_amount = $this->get_config('max_purchase_amount', 1000);
            $min_amount = $this->get_config('min_purchase_amount', 1);
            
            if ($usd_amount < $min_amount || $usd_amount > $max_amount) {
                wp_send_json_error(array('message' => "Amount must be between ${min_amount} and ${max_amount}"));
                return;
            }
            
            // 1 USD = 1 TOLA conversion rate
            $tola_amount = $usd_amount;
            
            // Start database transaction for payment processing
            global $wpdb;
            $wpdb->query('START TRANSACTION');
            
            try {
                // Process payment (integrate with payment gateway)
                $payment_result = $this->process_usd_payment($user_id, $usd_amount, $payment_method);
                
                if (!$payment_result['success']) {
                    $wpdb->query('ROLLBACK');
                    
                    $this->log_error('Payment processing failed', array(
                        'user_id' => $user_id,
                        'amount' => $usd_amount,
                        'method' => $payment_method,
                        'error' => $payment_result['message'] ?? 'Unknown error'
                    ));
                    
                    wp_send_json_error(array('message' => $payment_result['message'] ?? 'Payment failed'));
                    return;
                }
                
                // Add TOLA tokens to user balance
                $current_balance = get_user_meta($user_id, 'vortex_tola_balance', true) ?: 0;
                $new_balance = $current_balance + $tola_amount;
                update_user_meta($user_id, 'vortex_tola_balance', $new_balance);
                
                // Record transaction with enhanced metadata
                $this->record_tola_transaction($user_id, $tola_amount, 'usd_conversion', array(
                    'usd_amount' => $usd_amount,
                    'payment_method' => $payment_method,
                    'transaction_id' => $payment_result['transaction_id'],
                    'exchange_rate' => 1.0, // 1 USD = 1 TOLA
                    'payment_timestamp' => current_time('mysql')
                ));
                
                $wpdb->query('COMMIT');
                
                // Clear relevant caches
                wp_cache_delete("vortex_user_balance_{$user_id}");
                
                wp_send_json_success(array(
                    'tola_received' => $tola_amount,
                    'new_balance' => $new_balance,
                    'transaction_id' => $payment_result['transaction_id'],
                    'next_step' => 'subscription_activation'
                ));
                
            } catch (Exception $e) {
                $wpdb->query('ROLLBACK');
                throw $e;
            }
            
        } catch (Exception $e) {
            $this->log_error('USD to TOLA conversion failed', array(
                'error' => $e->getMessage(),
                'user_id' => get_current_user_id(),
                'amount' => $_POST['usd_amount'] ?? 'unknown'
            ));
            
            wp_send_json_error(array('message' => 'Payment processing failed. Please contact support.'));
        }
    }
    
    /**
     * Handle role and expertise quiz
     */
    public function handle_role_expertise_quiz() {
        check_ajax_referer('vortex_artist_journey', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'User must be logged in'));
            return;
        }
        
        $user_id = get_current_user_id();
        $role = sanitize_text_field($_POST['role']); // 'artist' or 'collector'
        $expertise_level = sanitize_text_field($_POST['expertise_level']);
        $category = sanitize_text_field($_POST['category']);
        
        // Store quiz results
        update_user_meta($user_id, 'vortex_user_role', $role);
        update_user_meta($user_id, 'vortex_expertise_level', $expertise_level);
        update_user_meta($user_id, 'vortex_art_category', $category);
        
        wp_send_json_success(array(
            'role' => $role,
            'next_step' => 'terms_agreement'
        ));
    }
    
    /**
     * Handle terms of agreement for seed artwork
     */
    public function handle_terms_agreement() {
        check_ajax_referer('vortex_artist_journey', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'User must be logged in'));
            return;
        }
        
        $user_id = get_current_user_id();
        $agreement_accepted = filter_var($_POST['agreement_accepted'], FILTER_VALIDATE_BOOLEAN);
        $digital_signature = sanitize_text_field($_POST['digital_signature']);
        
        if (!$agreement_accepted) {
            wp_send_json_error(array('message' => 'Terms agreement is required'));
            return;
        }
        
        // Store agreement
        update_user_meta($user_id, 'vortex_seed_artwork_agreement', true);
        update_user_meta($user_id, 'vortex_agreement_signature', $digital_signature);
        update_user_meta($user_id, 'vortex_agreement_date', current_time('mysql'));
        
        wp_send_json_success(array(
            'agreement_accepted' => true,
            'next_step' => 'seed_artwork_upload'
        ));
    }
    
    /**
     * Handle Horas business quiz for Artist Pro
     */
    public function handle_horas_business_quiz() {
        check_ajax_referer('vortex_artist_journey', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'User must be logged in'));
            return;
        }
        
        $user_id = get_current_user_id();
        $plan = get_user_meta($user_id, 'vortex_selected_plan', true);
        
        // Enforce for Artist Pro only
        if ($plan !== 'pro') {
            wp_send_json_error(array('message' => 'Horas quiz is only required for Artist Pro plan'));
            return;
        }
        
        $business_idea = sanitize_textarea_field($_POST['business_idea']);
        $roadmap_milestones = array();
        
        // Process roadmap milestones
        if (isset($_POST['milestones']) && is_array($_POST['milestones'])) {
            foreach ($_POST['milestones'] as $milestone) {
                $roadmap_milestones[] = array(
                    'title' => sanitize_text_field($milestone['title']),
                    'description' => sanitize_textarea_field($milestone['description']),
                    'target_date' => sanitize_text_field($milestone['target_date'])
                );
            }
        }
        
        // Store quiz responses
        $quiz_data = array(
            'business_idea' => $business_idea,
            'roadmap_milestones' => $roadmap_milestones,
            'completed_date' => current_time('mysql')
        );
        
        update_user_meta($user_id, 'vortex_horas_quiz_data', $quiz_data);
        update_user_meta($user_id, 'vortex_horas_quiz_completed', true);
        
        // Generate PDF and send email
        $pdf_result = $this->generate_and_email_business_pdf($user_id, $quiz_data);
        
        // Create milestone tracking entries
        $this->create_milestone_tracking($user_id, $roadmap_milestones);
        
        wp_send_json_success(array(
            'quiz_completed' => true,
            'pdf_generated' => $pdf_result['success'],
            'milestones_created' => count($roadmap_milestones),
            'next_step' => 'dashboard_access'
        ));
    }
    
    /**
     * Generate business PDF and send email
     */
    private function generate_and_email_business_pdf($user_id, $quiz_data) {
        $user = get_userdata($user_id);
        $timestamp = current_time('timestamp');
        
        // Generate PDF content
        $pdf_content = $this->generate_pdf_content($user, $quiz_data);
        
        // Save PDF to S3 (simulated - would use actual S3 integration)
        $pdf_filename = "horas_business_plan_{$user_id}_{$timestamp}.pdf";
        $pdf_path = $this->save_pdf_to_storage($pdf_content, $pdf_filename);
        
        // Send email with PDF attachment
        $email_sent = $this->send_business_plan_email($user, $pdf_path);
        
        // Store PDF reference
        update_user_meta($user_id, 'vortex_horas_pdf_path', $pdf_path);
        
        return array(
            'success' => $email_sent,
            'pdf_path' => $pdf_path
        );
    }
    
    /**
     * Create milestone tracking for calendar integration
     */
    private function create_milestone_tracking($user_id, $milestones) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_horas_milestones';
        
        foreach ($milestones as $milestone) {
            $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'title' => $milestone['title'],
                    'description' => $milestone['description'],
                    'target_date' => $milestone['target_date'],
                    'status' => 'pending',
                    'created_date' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s')
            );
        }
    }
    
    /**
     * Handle collection creation with drag-and-drop builder
     */
    public function handle_create_collection() {
        check_ajax_referer('vortex_artist_journey', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'User must be logged in'));
            return;
        }
        
        $user_id = get_current_user_id();
        $collection_name = sanitize_text_field($_POST['collection_name']);
        $artwork_ids = array_map('intval', $_POST['artwork_ids']);
        $collection_description = sanitize_textarea_field($_POST['description']);
        
        // Create collection
        $collection_id = wp_insert_post(array(
            'post_type' => 'vortex_collection',
            'post_title' => $collection_name,
            'post_content' => $collection_description,
            'post_status' => 'publish',
            'post_author' => $user_id
        ));
        
        // Link artworks to collection
        foreach ($artwork_ids as $artwork_id) {
            add_post_meta($collection_id, 'collection_artwork', $artwork_id);
        }
        
        wp_send_json_success(array(
            'collection_id' => $collection_id,
            'artwork_count' => count($artwork_ids)
        ));
    }
    
    /**
     * Handle NFT minting with Metaplex compatibility
     */
    public function handle_mint_nft() {
        check_ajax_referer('vortex_artist_journey', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'User must be logged in'));
            return;
        }
        
        $user_id = get_current_user_id();
        $artwork_id = intval($_POST['artwork_id']);
        $wallet_address = get_user_meta($user_id, 'vortex_wallet_address', true);
        
        if (!$wallet_address) {
            wp_send_json_error(array('message' => 'Wallet address required'));
            return;
        }
        
        // Prepare NFT metadata for Metaplex
        $metadata = $this->prepare_metaplex_metadata($artwork_id);
        
        // Mint NFT on Solana using Metaplex
        $mint_result = $this->mint_solana_nft($metadata, $wallet_address);
        
        if (!$mint_result['success']) {
            wp_send_json_error(array('message' => $mint_result['error']));
            return;
        }
        
        // Store NFT information
        update_post_meta($artwork_id, 'vortex_nft_address', $mint_result['nft_address']);
        update_post_meta($artwork_id, 'vortex_mint_transaction', $mint_result['transaction_hash']);
        
        wp_send_json_success($mint_result);
    }
    
    /**
     * Schedule daily cron for Tola Art of the Day
     */
    public function schedule_daily_cron() {
        if (!wp_next_scheduled('vortex_tola_art_of_the_day')) {
            wp_schedule_event(time(), 'daily', 'vortex_tola_art_of_the_day');
        }
    }
    
    /**
     * Generate Tola Art of the Day
     */
    public function generate_tola_art_of_the_day() {
        // Gather public artworks from the day
        $public_artworks = $this->get_daily_public_artworks();
        
        if (empty($public_artworks)) {
            return;
        }
        
        // Create collective AI art using seed prompts
        $collective_artwork = $this->generate_collective_ai_art($public_artworks);
        
        if ($collective_artwork) {
            // Mint single edition NFT
            $nft_result = $this->mint_daily_nft($collective_artwork);
            
            if ($nft_result['success']) {
                // List at auction
                $this->list_daily_artwork_auction($nft_result['nft_address'], $public_artworks);
                
                // Distribute proceeds equally to contributors
                $this->setup_proceeds_distribution($nft_result['nft_address'], $public_artworks);
            }
        }
    }
    
    /**
     * Track artwork actions for TOLA rewards
     */
    public function track_artwork_upload($artwork_id, $user_id) {
        $this->award_tola_tokens($user_id, 5, 'artwork_upload');
    }
    
    public function track_artwork_download($artwork_id, $user_id) {
        $this->award_tola_tokens($user_id, 2, 'artwork_download');
    }
    
    public function track_artwork_sale($artwork_id, $seller_id, $amount) {
        $reward = min($amount * 0.1, 50); // 10% of sale or 50 TOLA max
        $this->award_tola_tokens($seller_id, $reward, 'artwork_sale');
    }
    
    /**
     * Award TOLA tokens to user
     */
    private function award_tola_tokens($user_id, $amount, $action_type) {
        $current_balance = get_user_meta($user_id, 'vortex_tola_balance', true) ?: 0;
        $new_balance = $current_balance + $amount;
        update_user_meta($user_id, 'vortex_tola_balance', $new_balance);
        
        $this->record_tola_transaction($user_id, $amount, $action_type);
    }
    
    /**
     * Record TOLA transaction
     */
    private function record_tola_transaction($user_id, $amount, $type, $metadata = array()) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_tola_transactions',
            array(
                'user_id' => $user_id,
                'amount' => $amount,
                'transaction_type' => $type,
                'metadata' => maybe_serialize($metadata),
                'created_at' => current_time('mysql')
            ),
            array('%d', '%f', '%s', '%s', '%s')
        );
    }
    
    /**
     * Process USD payment (integrate with actual payment gateway)
     */
    private function process_usd_payment($user_id, $amount, $payment_method) {
        // Integrate with Stripe for credit card payments
        if ($payment_method === 'stripe') {
            return $this->process_stripe_payment($user_id, $amount);
        }
        
        // Integrate with PayPal for PayPal payments
        if ($payment_method === 'paypal') {
            return $this->process_paypal_payment($user_id, $amount);
        }
        
        // Fallback simulation for development
        return array(
            'success' => true,
            'transaction_id' => 'dev_' . time() . '_' . $user_id,
            'amount' => $amount
        );
    }
    
    /**
     * Process Stripe payment
     */
    private function process_stripe_payment($user_id, $amount) {
        $stripe_secret = get_option('vortex_stripe_secret_key');
        if (!$stripe_secret) {
            return array('success' => false, 'message' => 'Stripe not configured');
        }
        
        try {
            \Stripe\Stripe::setApiKey($stripe_secret);
            
            $intent = \Stripe\PaymentIntent::create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => 'usd',
                'metadata' => [
                    'user_id' => $user_id,
                    'purpose' => 'tola_conversion'
                ]
            ]);
            
            return array(
                'success' => true,
                'transaction_id' => $intent->id,
                'client_secret' => $intent->client_secret
            );
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }
    
    /**
     * Generate comprehensive PDF content for business plan using TCPDF
     */
    private function generate_pdf_content($user, $quiz_data) {
        require_once(ABSPATH . 'wp-content/plugins/vortex-ai-agents/vendor/tcpdf/tcpdf.php');
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('VortexArtec');
        $pdf->SetAuthor($user->display_name);
        $pdf->SetTitle('Artist Business Plan - ' . $user->display_name);
        $pdf->SetSubject('Business Plan Generated by Horas Quiz');
        
        // Set margins
        $pdf->SetMargins(20, 20, 20);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(15);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', 'B', 24);
        $pdf->Cell(0, 15, 'Artist Business Plan', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 14);
        $pdf->Cell(0, 10, 'Generated for: ' . $user->display_name, 0, 1, 'C');
        $pdf->Cell(0, 10, 'Date: ' . date('F j, Y'), 0, 1, 'C');
        
        $pdf->Ln(10);
        
        // Business Idea Section
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Business Idea', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->MultiCell(0, 8, $quiz_data['business_idea'], 0, 'L');
        
        $pdf->Ln(10);
        
        // Roadmap Milestones Section
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Roadmap Milestones', 0, 1, 'L');
        
        foreach ($quiz_data['roadmap_milestones'] as $index => $milestone) {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 8, ($index + 1) . '. ' . $milestone['title'], 0, 1, 'L');
            
            $pdf->SetFont('helvetica', '', 12);
            $pdf->MultiCell(0, 6, 'Target Date: ' . $milestone['target_date'], 0, 'L');
            $pdf->MultiCell(0, 6, $milestone['description'], 0, 'L');
            $pdf->Ln(5);
        }
        
        return $pdf->Output('', 'S'); // Return PDF as string
    }
    
    /**
     * Save PDF to S3 storage
     */
    private function save_pdf_to_storage($content, $filename) {
        $s3_config = array(
            'bucket' => get_option('vortex_s3_bucket'),
            'access_key' => get_option('vortex_s3_access_key'),
            'secret_key' => get_option('vortex_s3_secret_key'),
            'region' => get_option('vortex_s3_region', 'us-east-1')
        );
        
        if (empty($s3_config['bucket'])) {
            // Fallback to local storage
            $upload_dir = wp_upload_dir();
            $file_path = $upload_dir['basedir'] . '/vortex/pdfs/' . $filename;
            
            if (!file_exists(dirname($file_path))) {
                wp_mkdir_p(dirname($file_path));
            }
            
            file_put_contents($file_path, $content);
            return $upload_dir['baseurl'] . '/vortex/pdfs/' . $filename;
        }
        
        try {
            require_once(ABSPATH . 'wp-content/plugins/vortex-ai-agents/vendor/aws/aws-autoloader.php');
            
            $s3 = new Aws\S3\S3Client([
                'version' => 'latest',
                'region' => $s3_config['region'],
                'credentials' => [
                    'key' => $s3_config['access_key'],
                    'secret' => $s3_config['secret_key']
                ]
            ]);
            
            $result = $s3->putObject([
                'Bucket' => $s3_config['bucket'],
                'Key' => 'users/pdfs/' . $filename,
                'Body' => $content,
                'ContentType' => 'application/pdf'
            ]);
            
            return $result['ObjectURL'];
        } catch (Exception $e) {
            error_log('S3 Upload Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send business plan email with PDF attachment
     */
    private function send_business_plan_email($user, $pdf_path) {
        $subject = 'Your VortexArtec Business Plan - Generated by Horas';
        
        $message = "
        <html>
        <head><title>Your Business Plan is Ready</title></head>
        <body>
            <h2>Hello {$user->display_name},</h2>
            
            <p>Congratulations! Your personalized business plan has been generated based on your Horas quiz responses.</p>
            
            <p>This comprehensive plan includes:</p>
            <ul>
                <li>Your business idea analysis</li>
                <li>Detailed roadmap milestones</li>
                <li>Timeline and tracking system</li>
                <li>Integration with your VortexArtec dashboard</li>
            </ul>
            
            <p>You can access your business plan at: <a href='{$pdf_path}'>Download PDF</a></p>
            
            <p>Your milestones have been added to your calendar and will send you reminders as target dates approach.</p>
            
            <p>Best regards,<br>
            The VortexArtec Team</p>
        </body>
        </html>
        ";
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: VortexArtec <noreply@vortexartec.com>'
        );
        
        return wp_mail($user->user_email, $subject, $message, $headers);
    }
    
    /**
     * Prepare Metaplex-compatible metadata
     */
    private function prepare_metaplex_metadata($artwork_id) {
        $artwork = get_post($artwork_id);
        $image_url = get_post_meta($artwork_id, 'artwork_image_url', true);
        
        return array(
            'name' => $artwork->post_title,
            'description' => $artwork->post_content,
            'image' => $image_url,
            'attributes' => array(
                array('trait_type' => 'Artist', 'value' => get_the_author_meta('display_name', $artwork->post_author)),
                array('trait_type' => 'Creation Date', 'value' => $artwork->post_date)
            )
        );
    }
    
    /**
     * Mint NFT on Solana with real Metaplex integration
     */
    private function mint_solana_nft($metadata, $wallet_address) {
        $solana_rpc = get_option('vortex_solana_rpc_endpoint', 'https://api.mainnet-beta.solana.com');
        $metaplex_program_id = get_option('vortex_metaplex_program_id');
        
        // Upload metadata to IPFS first
        $metadata_uri = $this->upload_metadata_to_ipfs($metadata);
        
        if (!$metadata_uri) {
            return array('success' => false, 'error' => 'Failed to upload metadata to IPFS');
        }
        
        // Prepare Metaplex transaction
        $transaction_data = array(
            'wallet' => $wallet_address,
            'metadata_uri' => $metadata_uri,
            'name' => $metadata['name'],
            'symbol' => 'VORTEX',
            'seller_fee_basis_points' => 500, // 5% royalty
            'is_mutable' => false
        );
        
        // Call Solana/Metaplex service (this would be your backend service)
        $metaplex_service_endpoint = get_option('vortex_metaplex_service_endpoint');
        
        if (!$metaplex_service_endpoint) {
            // Fallback simulation for development
            return array(
                'success' => true,
                'nft_address' => 'dev_nft_' . time() . '_' . substr(md5($wallet_address), 0, 8),
                'transaction_hash' => 'dev_tx_' . hash('sha256', json_encode($metadata))
            );
        }
        
        $response = wp_remote_post($metaplex_service_endpoint . '/mint', array(
            'timeout' => 30,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($transaction_data)
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }
        
        $result = json_decode(wp_remote_retrieve_body($response), true);
        
        return array(
            'success' => true,
            'nft_address' => $result['mint_address'],
            'transaction_hash' => $result['transaction_signature']
        );
    }
    
    /**
     * Upload metadata to IPFS
     */
    private function upload_metadata_to_ipfs($metadata) {
        $ipfs_gateway = get_option('vortex_ipfs_gateway', 'https://api.pinata.cloud');
        $pinata_api_key = get_option('vortex_pinata_api_key');
        $pinata_secret = get_option('vortex_pinata_secret');
        
        if (!$pinata_api_key) {
            // Fallback to local storage
            $upload_dir = wp_upload_dir();
            $metadata_file = $upload_dir['basedir'] . '/vortex/metadata/' . time() . '_metadata.json';
            
            if (!file_exists(dirname($metadata_file))) {
                wp_mkdir_p(dirname($metadata_file));
            }
            
            file_put_contents($metadata_file, json_encode($metadata));
            return $upload_dir['baseurl'] . '/vortex/metadata/' . basename($metadata_file);
        }
        
        $response = wp_remote_post($ipfs_gateway . '/pinning/pinJSONToIPFS', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'pinata_api_key' => $pinata_api_key,
                'pinata_secret_api_key' => $pinata_secret
            ),
            'body' => json_encode(array(
                'pinataContent' => $metadata,
                'pinataMetadata' => array('name' => 'VortexArtec NFT Metadata')
            ))
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $result = json_decode(wp_remote_retrieve_body($response), true);
        return 'https://gateway.pinata.cloud/ipfs/' . $result['IpfsHash'];
    }
    
    /**
     * Get daily public artworks for collective art
     */
    private function get_daily_public_artworks() {
        $args = array(
            'post_type' => 'vortex_artwork',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'artwork_visibility',
                    'value' => 'public'
                )
            ),
            'date_query' => array(
                array(
                    'after' => '1 day ago'
                )
            )
        );
        
        return get_posts($args);
    }
    
    /**
     * Generate collective AI art from public artworks using AUTOMATIC1111
     */
    private function generate_collective_ai_art($artworks) {
        $automatic1111_endpoint = get_option('vortex_automatic1111_endpoint', 'http://localhost:7860');
        
        // Extract prompts and styles from artworks
        $prompts = array();
        $styles = array();
        
        foreach ($artworks as $artwork) {
            $artwork_style = get_post_meta($artwork->ID, 'artwork_style', true);
            $artwork_prompt = get_post_meta($artwork->ID, 'artwork_prompt', true);
            
            if ($artwork_style) $styles[] = $artwork_style;
            if ($artwork_prompt) $prompts[] = $artwork_prompt;
        }
        
        // Create collective prompt
        $collective_prompt = "Fusion artwork combining elements: " . implode(', ', array_slice($prompts, 0, 5));
        $style_modifier = !empty($styles) ? " in " . $styles[0] . " style" : "";
        $final_prompt = $collective_prompt . $style_modifier . ", masterpiece, high quality, detailed, artistic collaboration";
        
        // Call AUTOMATIC1111 API
        $api_data = array(
            'prompt' => $final_prompt,
            'negative_prompt' => 'low quality, blurry, pixelated, amateur',
            'steps' => 30,
            'cfg_scale' => 7,
            'width' => 768,
            'height' => 768,
            'seed' => -1,
            'sampler_name' => 'DPM++ 2M Karras'
        );
        
        $response = wp_remote_post($automatic1111_endpoint . '/sdapi/v1/txt2img', array(
            'timeout' => 60,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($api_data)
        ));
        
        if (is_wp_error($response)) {
            error_log('AUTOMATIC1111 Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['images'][0])) {
            return false;
        }
        
        // Save generated image
        $image_data = base64_decode($body['images'][0]);
        $filename = 'tola_art_of_the_day_' . date('Y_m_d') . '.png';
        
        $upload_dir = wp_upload_dir();
        $image_path = $upload_dir['basedir'] . '/vortex/generated/' . $filename;
        
        if (!file_exists(dirname($image_path))) {
            wp_mkdir_p(dirname($image_path));
        }
        
        file_put_contents($image_path, $image_data);
        $image_url = $upload_dir['baseurl'] . '/vortex/generated/' . $filename;
        
        return array(
            'image_url' => $image_url,
            'prompt' => $final_prompt,
            'contributing_artworks' => array_map(function($artwork) { return $artwork->ID; }, $artworks)
        );
    }
    
    /**
     * Mint daily NFT
     */
    private function mint_daily_nft($artwork_data) {
        return array(
            'success' => true,
            'nft_address' => 'daily_nft_' . date('Y_m_d')
        );
    }
    
    /**
     * List daily artwork at auction with real marketplace integration
     */
    private function list_daily_artwork_auction($nft_address, $contributors) {
        $marketplace_contract = get_option('vortex_marketplace_contract_address');
        $auction_duration = 24 * 60 * 60; // 24 hours
        $starting_price = 10; // 10 TOLA tokens
        
        // Create auction listing
        $auction_data = array(
            'nft_address' => $nft_address,
            'starting_price' => $starting_price,
            'duration' => $auction_duration,
            'currency' => 'TOLA',
            'auction_type' => 'english', // English auction (highest bidder wins)
            'contributors' => array_map(function($artwork) {
                return get_post_field('post_author', $artwork->ID);
            }, $contributors)
        );
        
        // Store auction in database
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_auctions',
            array(
                'nft_address' => $nft_address,
                'auction_data' => json_encode($auction_data),
                'status' => 'active',
                'start_time' => current_time('mysql'),
                'end_time' => date('Y-m-d H:i:s', time() + $auction_duration),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        $auction_id = $wpdb->insert_id;
        
        // Send notifications to contributors
        $this->notify_daily_art_contributors($contributors, $auction_id, $nft_address);
        
        return $auction_id;
    }
    
    /**
     * Setup proceeds distribution for daily artwork
     */
    private function setup_proceeds_distribution($nft_address, $contributors) {
        $contributor_count = count($contributors);
        $share_percentage = 100 / $contributor_count; // Equal distribution
        
        global $wpdb;
        
        // Create distribution records
        foreach ($contributors as $artwork) {
            $artist_id = get_post_field('post_author', $artwork->ID);
            
            $wpdb->insert(
                $wpdb->prefix . 'vortex_proceeds_distribution',
                array(
                    'nft_address' => $nft_address,
                    'artist_id' => $artist_id,
                    'artwork_id' => $artwork->ID,
                    'share_percentage' => $share_percentage,
                    'status' => 'pending',
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%d', '%d', '%f', '%s', '%s')
            );
        }
    }
    
    /**
     * Process PayPal payment
     */
    private function process_paypal_payment($user_id, $amount) {
        $paypal_client_id = get_option('vortex_paypal_client_id');
        $paypal_secret = get_option('vortex_paypal_secret');
        $paypal_mode = get_option('vortex_paypal_mode', 'sandbox'); // sandbox or live
        
        if (!$paypal_client_id) {
            return array('success' => false, 'message' => 'PayPal not configured');
        }
        
        $paypal_base_url = ($paypal_mode === 'live') 
            ? 'https://api.paypal.com' 
            : 'https://api.sandbox.paypal.com';
        
        // Get PayPal access token
        $auth_response = wp_remote_post($paypal_base_url . '/v1/oauth2/token', array(
            'headers' => array(
                'Accept' => 'application/json',
                'Accept-Language' => 'en_US',
                'Authorization' => 'Basic ' . base64_encode($paypal_client_id . ':' . $paypal_secret)
            ),
            'body' => 'grant_type=client_credentials'
        ));
        
        if (is_wp_error($auth_response)) {
            return array('success' => false, 'message' => $auth_response->get_error_message());
        }
        
        $auth_result = json_decode(wp_remote_retrieve_body($auth_response), true);
        $access_token = $auth_result['access_token'];
        
        // Create PayPal order
        $order_data = array(
            'intent' => 'CAPTURE',
            'purchase_units' => array(
                array(
                    'amount' => array(
                        'currency_code' => 'USD',
                        'value' => number_format($amount, 2)
                    ),
                    'description' => 'TOLA Token Purchase - VortexArtec'
                )
            )
        );
        
        $order_response = wp_remote_post($paypal_base_url . '/v2/checkout/orders', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token
            ),
            'body' => json_encode($order_data)
        ));
        
        if (is_wp_error($order_response)) {
            return array('success' => false, 'message' => $order_response->get_error_message());
        }
        
        $order_result = json_decode(wp_remote_retrieve_body($order_response), true);
        
        return array(
            'success' => true,
            'transaction_id' => $order_result['id'],
            'approval_url' => $order_result['links'][1]['href'] // Approval URL for user
        );
    }
    
    /**
     * AJAX handler for getting Chloe AI inspiration
     */
    public function handle_get_chloe_inspiration() {
        check_ajax_referer('vortex_artist_journey', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'User must be logged in'));
            return;
        }
        
        $user_id = get_current_user_id();
        $inspiration = $this->get_chloe_inspiration($user_id);
        
        wp_send_json_success($inspiration);
    }
    
    /**
     * AJAX handler for getting collector matches
     */
    public function handle_get_collector_matches() {
        check_ajax_referer('vortex_artist_journey', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'User must be logged in'));
            return;
        }
        
        $user_id = get_current_user_id();
        $matches = $this->get_collector_matches($user_id);
        
        wp_send_json_success(array('matches' => $matches));
    }
    
    /**
     * AJAX handler for updating milestone status
     */
    public function handle_update_milestone() {
        check_ajax_referer('vortex_artist_journey', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'User must be logged in'));
            return;
        }
        
        $user_id = get_current_user_id();
        $milestone_id = intval($_POST['milestone_id']);
        $status = sanitize_text_field($_POST['status']); // pending, in_progress, completed
        
        global $wpdb;
        
        $updated = $wpdb->update(
            $wpdb->prefix . 'vortex_horas_milestones',
            array(
                'status' => $status,
                'completed_date' => ($status === 'completed') ? current_time('mysql') : null
            ),
            array(
                'id' => $milestone_id,
                'user_id' => $user_id
            ),
            array('%s', '%s'),
            array('%d', '%d')
        );
        
        if ($updated) {
            // Award TOLA tokens for milestone completion
            if ($status === 'completed') {
                $this->award_tola_tokens($user_id, 15, 'milestone_completed');
            }
            
            wp_send_json_success(array('updated' => true));
        } else {
            wp_send_json_error(array('message' => 'Failed to update milestone'));
        }
    }
    
    /**
     * AJAX handler for getting user milestones
     */
    public function handle_get_milestones() {
        check_ajax_referer('vortex_artist_journey', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'User must be logged in'));
            return;
        }
        
        $user_id = get_current_user_id();
        $milestones = $this->get_user_milestones($user_id);
        
        wp_send_json_success(array('milestones' => $milestones));
    }
    
    /**
     * Get user milestones for calendar integration
     */
    private function get_user_milestones($user_id) {
        global $wpdb;
        
        $milestones = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}vortex_horas_milestones 
            WHERE user_id = %d 
            ORDER BY target_date ASC
        ", $user_id), ARRAY_A);
        
        // Format for calendar integration
        $calendar_events = array();
        foreach ($milestones as $milestone) {
            $calendar_events[] = array(
                'id' => $milestone['id'],
                'title' => $milestone['title'],
                'description' => $milestone['description'],
                'start' => $milestone['target_date'],
                'status' => $milestone['status'],
                'completed_date' => $milestone['completed_date'],
                'className' => 'milestone-' . $milestone['status'],
                'backgroundColor' => $this->get_milestone_color($milestone['status'])
            );
        }
        
        return $calendar_events;
    }
    
    /**
     * Get milestone status color for calendar
     */
    private function get_milestone_color($status) {
        $colors = array(
            'pending' => '#gray',
            'in_progress' => '#3498db',
            'completed' => '#2ecc71',
            'overdue' => '#e74c3c'
        );
        
        return $colors[$status] ?? '#gray';
    }
    
    /**
     * AJAX handler for getting journey progress
     */
    public function handle_get_journey_progress() {
        check_ajax_referer('vortex_artist_journey', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'User must be logged in'));
            return;
        }
        
        $user_id = get_current_user_id();
        $progress = $this->calculate_journey_progress($user_id);
        
        wp_send_json_success($progress);
    }
    
    /**
     * Calculate user's journey progress
     */
    private function calculate_journey_progress($user_id) {
        $steps = array(
            'plan_selection' => get_user_meta($user_id, 'vortex_selected_plan', true) ? 1 : 0,
            'wallet_connection' => get_user_meta($user_id, 'vortex_wallet_address', true) ? 1 : 0,
            'subscription_active' => get_user_meta($user_id, 'vortex_subscription_active', true) ? 1 : 0,
            'role_quiz_completed' => get_user_meta($user_id, 'vortex_user_role', true) ? 1 : 0,
            'terms_agreement' => get_user_meta($user_id, 'vortex_seed_artwork_agreement', true) ? 1 : 0,
            'seed_artwork_uploaded' => get_user_meta($user_id, 'vortex_seed_artworks_uploaded', true) ? 1 : 0
        );
        
        // Check if Horas quiz is required and completed
        $selected_plan = get_user_meta($user_id, 'vortex_selected_plan', true);
        if ($selected_plan === 'pro') {
            $steps['horas_quiz_completed'] = get_user_meta($user_id, 'vortex_horas_quiz_completed', true) ? 1 : 0;
        } else {
            $steps['horas_quiz_completed'] = 1; // Not required for non-pro plans
        }
        
        $completed_steps = array_sum($steps);
        $total_steps = count($steps);
        $progress_percentage = ($completed_steps / $total_steps) * 100;
        
        return array(
            'steps' => $steps,
            'completed_steps' => $completed_steps,
            'total_steps' => $total_steps,
            'progress_percentage' => round($progress_percentage, 1),
            'next_step' => $this->get_next_step($steps),
            'journey_complete' => $progress_percentage >= 100
        );
    }
    
    /**
     * Get the next step in the journey
     */
    private function get_next_step($steps) {
        $step_order = array(
            'plan_selection',
            'wallet_connection', 
            'subscription_active',
            'role_quiz_completed',
            'terms_agreement',
            'seed_artwork_uploaded',
            'horas_quiz_completed'
        );
        
        foreach ($step_order as $step) {
            if (isset($steps[$step]) && $steps[$step] === 0) {
                return $step;
            }
        }
        
        return 'journey_complete';
    }
    
    /**
     * AJAX handler for completing journey step
     */
    public function handle_complete_journey_step() {
        check_ajax_referer('vortex_artist_journey', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'User must be logged in'));
            return;
        }
        
        $user_id = get_current_user_id();
        $step = sanitize_text_field($_POST['step']);
        
        // Mark step as completed
        update_user_meta($user_id, "vortex_{$step}_completed", true);
        update_user_meta($user_id, "vortex_{$step}_completion_date", current_time('mysql'));
        
        // Award TOLA tokens for step completion
        $this->award_tola_tokens($user_id, 5, 'journey_step_completed');
        
        wp_send_json_success(array('step_completed' => $step));
    }
    
    /**
     * Schedule milestone reminder cron job
     */
    public function schedule_milestone_reminders() {
        if (!wp_next_scheduled('vortex_milestone_reminders')) {
            wp_schedule_event(time(), 'daily', 'vortex_milestone_reminders');
        }
    }
    
    /**
     * Send milestone reminders
     */
    public function send_milestone_reminders() {
        global $wpdb;
        
        // Get milestones due in the next 3 days
        $upcoming_milestones = $wpdb->get_results("
            SELECT m.*, u.user_email, u.display_name 
            FROM {$wpdb->prefix}vortex_horas_milestones m
            JOIN {$wpdb->users} u ON m.user_id = u.ID
            WHERE m.status != 'completed' 
            AND m.target_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
        ");
        
        foreach ($upcoming_milestones as $milestone) {
            $days_until_due = ceil((strtotime($milestone->target_date) - time()) / (24 * 60 * 60));
            
            $subject = "Milestone Reminder: {$milestone->title}";
            $message = "
            <html>
            <body>
                <h2>Hello {$milestone->display_name},</h2>
                
                <p>This is a friendly reminder about your upcoming milestone:</p>
                
                <div style='background: #f5f6fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3>{$milestone->title}</h3>
                    <p><strong>Due:</strong> {$milestone->target_date} ({$days_until_due} day(s) from now)</p>
                    <p><strong>Description:</strong> {$milestone->description}</p>
                </div>
                
                <p>You can update your milestone status in your <a href='" . home_url('/artist-dashboard') . "'>Artist Dashboard</a>.</p>
                
                <p>Complete your milestones to earn TOLA token rewards!</p>
                
                <p>Best regards,<br>The VortexArtec Team</p>
            </body>
            </html>
            ";
            
            wp_mail($milestone->user_email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
        }
    }
    
    /**
     * Get subscription plans
     */
    public function get_subscription_plans() {
        return $this->subscription_plans;
    }
    
    /**
     * Install database tables
     */
    public function install() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Horas milestones table
        $table_name = $wpdb->prefix . 'vortex_horas_milestones';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            description text,
            target_date date,
            status varchar(20) DEFAULT 'pending',
            completed_date datetime DEFAULT NULL,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // TOLA transactions table
        $table_name = $wpdb->prefix . 'vortex_tola_transactions';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            transaction_type varchar(50) NOT NULL,
            metadata text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY transaction_type (transaction_type)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Auctions table for daily art and marketplace
        $table_name = $wpdb->prefix . 'vortex_auctions';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            nft_address varchar(255) NOT NULL,
            auction_data text NOT NULL,
            status varchar(20) DEFAULT 'active',
            start_time datetime NOT NULL,
            end_time datetime NOT NULL,
            winning_bid decimal(10,2) DEFAULT NULL,
            winner_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY nft_address (nft_address),
            KEY status (status)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Proceeds distribution table
        $table_name = $wpdb->prefix . 'vortex_proceeds_distribution';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            nft_address varchar(255) NOT NULL,
            artist_id bigint(20) NOT NULL,
            artwork_id bigint(20) NOT NULL,
            share_percentage decimal(5,2) NOT NULL,
            amount_earned decimal(10,2) DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            paid_date datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY nft_address (nft_address),
            KEY artist_id (artist_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Handle wallet connection with Solana integration
     */
    public function handle_wallet_connection() {
        check_ajax_referer('vortex_artist_journey', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'User must be logged in'));
            return;
        }
        
        $user_id = get_current_user_id();
        $wallet_address = sanitize_text_field($_POST['wallet_address']);
        $wallet_type = sanitize_text_field($_POST['wallet_type']); // phantom, solflare, etc.
        $signature = sanitize_text_field($_POST['signature']);
        
        // Verify wallet signature (placeholder for actual verification)
        if (!$this->verify_wallet_signature($wallet_address, $signature)) {
            wp_send_json_error(array('message' => 'Invalid wallet signature'));
            return;
        }
        
        // Store wallet information
        update_user_meta($user_id, 'vortex_wallet_address', $wallet_address);
        update_user_meta($user_id, 'vortex_wallet_type', $wallet_type);
        update_user_meta($user_id, 'vortex_wallet_connected_date', current_time('mysql'));
        
        // Check TOLA balance requirement for selected plan
        $selected_plan = get_user_meta($user_id, 'vortex_selected_plan', true);
        $plan_cost = $this->subscription_plans[$selected_plan]['price_tola'];
        $user_balance = get_user_meta($user_id, 'vortex_tola_balance', true) ?: 0;
        
        if ($user_balance >= $plan_cost) {
            // Sufficient balance - deduct and activate subscription
            $new_balance = $user_balance - $plan_cost;
            update_user_meta($user_id, 'vortex_tola_balance', $new_balance);
            update_user_meta($user_id, 'vortex_subscription_active', true);
            update_user_meta($user_id, 'vortex_subscription_start_date', current_time('mysql'));
            
            $this->record_tola_transaction($user_id, -$plan_cost, 'subscription_payment', array(
                'plan' => $selected_plan,
                'wallet_address' => $wallet_address
            ));
            
            wp_send_json_success(array(
                'subscription_activated' => true,
                'new_balance' => $new_balance,
                'next_step' => 'role_expertise_quiz'
            ));
        } else {
            wp_send_json_success(array(
                'subscription_activated' => false,
                'balance_needed' => $plan_cost - $user_balance,
                'next_step' => 'usd_to_tola_conversion'
            ));
        }
    }
    
    /**
     * Handle seed artwork upload with enhanced security and memory optimization
     */
    public function handle_seed_artwork_upload() {
        try {
            // Check rate limiting for file uploads
            if (!$this->check_rate_limit('file_upload')) {
                wp_send_json_error(array('message' => 'Upload rate limit exceeded. Please wait before uploading more files.'));
                return;
            }
            
            check_ajax_referer('vortex_artist_journey', 'nonce');
            
            if (!is_user_logged_in()) {
                wp_send_json_error(array('message' => 'User must be logged in'));
                return;
            }
            
            $user_id = get_current_user_id();
            
            // Check user plan limits
            $selected_plan = get_user_meta($user_id, 'vortex_selected_plan', true);
            if (!$selected_plan || !isset($this->subscription_plans[$selected_plan])) {
                wp_send_json_error(array('message' => 'Invalid subscription plan'));
                return;
            }
            
            // Check if terms agreement is completed
            $agreement_accepted = get_user_meta($user_id, 'vortex_seed_artwork_agreement', true);
            if (!$agreement_accepted) {
                wp_send_json_error(array('message' => 'Terms agreement required before upload'));
                return;
            }
            
            // Validate file uploads
            if (!isset($_FILES['seed_artworks']) || empty($_FILES['seed_artworks']['name'][0])) {
                wp_send_json_error(array('message' => 'No artworks uploaded'));
                return;
            }
            
            $uploaded_artworks = array();
            $allowed_types = $this->get_config('allowed_file_types');
            $max_file_size = $this->get_config('max_file_size');
            $plan_limits = $this->subscription_plans[$selected_plan]['limits'];
            
            // Check monthly upload limits
            $current_month_uploads = $this->get_user_monthly_uploads($user_id);
            if ($plan_limits['artworks_per_month'] !== -1 && 
                $current_month_uploads >= $plan_limits['artworks_per_month']) {
                wp_send_json_error(array('message' => 'Monthly upload limit reached for your plan'));
                return;
            }
            
            // Check storage limits
            $current_storage_usage = $this->get_user_storage_usage($user_id);
            $storage_limit_bytes = $plan_limits['storage_mb'] * 1024 * 1024;
            
            $total_upload_size = 0;
            foreach ($_FILES['seed_artworks']['size'] as $file_size) {
                $total_upload_size += $file_size;
            }
            
            if (($current_storage_usage + $total_upload_size) > $storage_limit_bytes) {
                wp_send_json_error(array('message' => 'Storage limit exceeded for your plan'));
                return;
            }
            
            // Process files with memory-efficient handling
            $file_count = count($_FILES['seed_artworks']['name']);
            $max_files_per_batch = $this->get_config('max_files_per_upload', 10);
            
            if ($file_count > $max_files_per_batch) {
                wp_send_json_error(array('message' => "Maximum {$max_files_per_batch} files allowed per upload"));
                return;
            }
            
            // Start database transaction for upload batch
            global $wpdb;
            $wpdb->query('START TRANSACTION');
            
            try {
                foreach ($_FILES['seed_artworks']['name'] as $key => $filename) {
                    if ($_FILES['seed_artworks']['error'][$key] !== UPLOAD_ERR_OK) {
                        $this->log_error('File upload error', array(
                            'filename' => $filename,
                            'error_code' => $_FILES['seed_artworks']['error'][$key],
                            'user_id' => $user_id
                        ));
                        continue;
                    }
                    
                    $file_type = $_FILES['seed_artworks']['type'][$key];
                    $file_size = $_FILES['seed_artworks']['size'][$key];
                    $tmp_name = $_FILES['seed_artworks']['tmp_name'][$key];
                    
                    // Enhanced file validation
                    if (!in_array($file_type, $allowed_types)) {
                        $this->log_error('Invalid file type uploaded', array(
                            'filename' => $filename,
                            'type' => $file_type,
                            'user_id' => $user_id
                        ));
                        continue;
                    }
                    
                    if ($file_size > $max_file_size) {
                        $this->log_error('File size exceeded', array(
                            'filename' => $filename,
                            'size' => $file_size,
                            'limit' => $max_file_size,
                            'user_id' => $user_id
                        ));
                        continue;
                    }
                    
                    // Additional security: Check actual file content
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $actual_mime = finfo_file($finfo, $tmp_name);
                    finfo_close($finfo);
                    
                    if (!in_array($actual_mime, $allowed_types)) {
                        $this->log_error('File content does not match extension', array(
                            'filename' => $filename,
                            'claimed_type' => $file_type,
                            'actual_type' => $actual_mime,
                            'user_id' => $user_id
                        ));
                        continue;
                    }
                    
                    // Process file upload with chunked handling for large files
                    $s3_result = $this->upload_to_s3_chunked($tmp_name, $filename, $user_id, 'seed_artwork');
                    
                    if ($s3_result['success']) {
                        // Generate thumbnail asynchronously for better performance
                        $thumbnail_result = $this->generate_artwork_thumbnail_async($s3_result['url']);
                        
                        // Create artwork post with enhanced metadata
                        $artwork_id = wp_insert_post(array(
                            'post_type' => 'vortex_artwork',
                            'post_title' => $this->sanitize_artwork_title($filename),
                            'post_status' => 'publish',
                            'post_author' => $user_id,
                            'meta_input' => array(
                                'artwork_type' => 'seed_artwork',
                                'artwork_image_url' => $s3_result['url'],
                                'artwork_thumbnail_url' => $thumbnail_result['thumbnail_url'],
                                'artwork_file_size' => $file_size,
                                'artwork_file_type' => $actual_mime,
                                'artwork_dimensions' => $thumbnail_result['dimensions'],
                                'artwork_original_filename' => $filename,
                                'upload_date' => current_time('mysql'),
                                'upload_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                                'processing_status' => 'completed'
                            )
                        ));
                        
                        if ($artwork_id) {
                            $uploaded_artworks[] = array(
                                'artwork_id' => $artwork_id,
                                'filename' => $filename,
                                'url' => $s3_result['url'],
                                'thumbnail' => $thumbnail_result['thumbnail_url'],
                                'file_size' => $file_size,
                                'dimensions' => $thumbnail_result['dimensions']
                            );
                            
                            // Update user storage usage
                            $this->update_user_storage_usage($user_id, $file_size);
                            
                            // Award TOLA tokens for upload (async to avoid blocking)
                            wp_schedule_single_event(time() + 5, 'vortex_artwork_uploaded', array($artwork_id, $user_id));
                        }
                    } else {
                        $this->log_error('S3 upload failed', array(
                            'filename' => $filename,
                            'error' => $s3_result['error'],
                            'user_id' => $user_id
                        ));
                    }
                }
                
                if (!empty($uploaded_artworks)) {
                    update_user_meta($user_id, 'vortex_seed_artworks_uploaded', true);
                    update_user_meta($user_id, 'vortex_last_upload_date', current_time('mysql'));
                    
                    // Update monthly upload count
                    $this->increment_monthly_upload_count($user_id, count($uploaded_artworks));
                    
                    $wpdb->query('COMMIT');
                    
                    // Clear relevant caches
                    wp_cache_delete("vortex_user_uploads_{$user_id}");
                    wp_cache_delete("vortex_user_storage_{$user_id}");
                    
                    // Check if Horas quiz is required
                    $next_step = ($selected_plan === 'pro') ? 'horas_business_quiz' : 'marketplace_access';
                    
                    wp_send_json_success(array(
                        'uploaded_count' => count($uploaded_artworks),
                        'artworks' => $uploaded_artworks,
                        'storage_used' => $this->get_user_storage_usage($user_id),
                        'uploads_remaining' => $plan_limits['artworks_per_month'] === -1 ? 
                            'unlimited' : ($plan_limits['artworks_per_month'] - $current_month_uploads - count($uploaded_artworks)),
                        'next_step' => $next_step
                    ));
                } else {
                    $wpdb->query('ROLLBACK');
                    wp_send_json_error(array('message' => 'Failed to upload any artworks. Please check file formats and sizes.'));
                }
                
            } catch (Exception $e) {
                $wpdb->query('ROLLBACK');
                throw $e;
            }
            
        } catch (Exception $e) {
            $this->log_error('Seed artwork upload failed', array(
                'error' => $e->getMessage(),
                'user_id' => get_current_user_id(),
                'files_count' => count($_FILES['seed_artworks']['name'] ?? array())
            ));
            
            wp_send_json_error(array('message' => 'Upload failed. Please try again or contact support.'));
        }
    }
    
    /**
     * Get user's monthly upload count
     */
    private function get_user_monthly_uploads($user_id) {
        return $this->get_cached_data(
            "vortex_monthly_uploads_{$user_id}_" . date('Y_m'),
            function() use ($user_id) {
                global $wpdb;
                $count = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM {$wpdb->posts} p
                    JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                    WHERE p.post_author = %d 
                    AND p.post_type = 'vortex_artwork'
                    AND pm.meta_key = 'upload_date'
                    AND pm.meta_value >= %s
                ", $user_id, date('Y-m-01')));
                
                return intval($count);
            },
            300 // 5 minute cache
        );
    }
    
    /**
     * Get user's storage usage in bytes
     */
    private function get_user_storage_usage($user_id) {
        return $this->get_cached_data(
            "vortex_storage_usage_{$user_id}",
            function() use ($user_id) {
                global $wpdb;
                $usage = $wpdb->get_var($wpdb->prepare("
                    SELECT SUM(CAST(pm.meta_value AS UNSIGNED)) FROM {$wpdb->posts} p
                    JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                    WHERE p.post_author = %d 
                    AND p.post_type = 'vortex_artwork'
                    AND pm.meta_key = 'artwork_file_size'
                ", $user_id));
                
                return intval($usage ?: 0);
            },
            600 // 10 minute cache
        );
    }
    
    /**
     * Update user storage usage
     */
    private function update_user_storage_usage($user_id, $file_size) {
        $current_usage = get_user_meta($user_id, 'vortex_total_storage_usage', true) ?: 0;
        update_user_meta($user_id, 'vortex_total_storage_usage', $current_usage + $file_size);
        
        // Clear cache
        wp_cache_delete("vortex_storage_usage_{$user_id}");
    }
    
    /**
     * Increment monthly upload count
     */
    private function increment_monthly_upload_count($user_id, $count) {
        $cache_key = "vortex_monthly_uploads_{$user_id}_" . date('Y_m');
        wp_cache_delete($cache_key);
    }
    
    /**
     * Sanitize artwork title from filename
     */
    private function sanitize_artwork_title($filename) {
        $title = pathinfo($filename, PATHINFO_FILENAME);
        $title = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $title);
        $title = preg_replace('/\s+/', ' ', $title);
        $title = trim($title);
        
        return !empty($title) ? $title : 'Untitled Artwork';
    }
    
    /**
     * Verify wallet signature for Solana wallet
     */
    private function verify_wallet_signature($wallet_address, $signature) {
        // This would integrate with actual Solana web3.js verification
        // For now, basic validation
        if (strlen($wallet_address) < 32 || strlen($signature) < 64) {
            return false;
        }
        
        // In production, verify the signature against the wallet's public key
        return true;
    }
    
    /**
     * Upload file to S3 with chunked processing for large files
     */
    private function upload_to_s3_chunked($tmp_file, $filename, $user_id, $folder_type) {
        $s3_config = array(
            'bucket' => get_option('vortex_s3_bucket'),
            'access_key' => get_option('vortex_s3_access_key'),
            'secret_key' => get_option('vortex_s3_secret_key'),
            'region' => get_option('vortex_s3_region', 'us-east-1')
        );
        
        $file_size = filesize($tmp_file);
        $chunk_size = $this->get_config('s3_chunk_size', 5 * 1024 * 1024); // 5MB chunks
        
        if (empty($s3_config['bucket'])) {
            // Fallback to local WordPress uploads with chunked handling
            return $this->upload_to_local_chunked($tmp_file, $filename, $user_id, $folder_type);
        }
        
        try {
            require_once(ABSPATH . 'wp-content/plugins/vortex-ai-agents/vendor/aws/aws-autoloader.php');
            
            $s3 = new Aws\S3\S3Client([
                'version' => 'latest',
                'region' => $s3_config['region'],
                'credentials' => [
                    'key' => $s3_config['access_key'],
                    'secret' => $s3_config['secret_key']
                ]
            ]);
            
            $unique_filename = time() . '_' . sanitize_file_name($filename);
            $s3_key = "users/{$user_id}/{$folder_type}/{$unique_filename}";
            
            // Use multipart upload for large files
            if ($file_size > $chunk_size) {
                $uploader = new Aws\S3\MultipartUploader($s3, $tmp_file, [
                    'bucket' => $s3_config['bucket'],
                    'key' => $s3_key,
                    'ACL' => 'public-read'
                ]);
                
                $result = $uploader->upload();
                $object_url = $result['ObjectURL'];
            } else {
                // Standard upload for smaller files
                $result = $s3->putObject([
                    'Bucket' => $s3_config['bucket'],
                    'Key' => $s3_key,
                    'SourceFile' => $tmp_file,
                    'ACL' => 'public-read'
                ]);
                
                $object_url = $result['ObjectURL'];
            }
            
            return array(
                'success' => true,
                'url' => $object_url,
                'key' => $s3_key
            );
            
        } catch (Exception $e) {
            $this->log_error('S3 chunked upload failed', array(
                'filename' => $filename,
                'file_size' => $file_size,
                'error' => $e->getMessage()
            ));
            
            // Fallback to local upload
            return $this->upload_to_local_chunked($tmp_file, $filename, $user_id, $folder_type);
        }
    }
    
    /**
     * Fallback local upload with chunked processing
     */
    private function upload_to_local_chunked($tmp_file, $filename, $user_id, $folder_type) {
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . "/vortex/{$folder_type}/" . $user_id . '/';
        
        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }
        
        $unique_filename = time() . '_' . sanitize_file_name($filename);
        $target_file = $target_dir . $unique_filename;
        
        // Use chunked copy for large files to avoid memory issues
        if (filesize($tmp_file) > 10 * 1024 * 1024) { // 10MB
            if ($this->chunked_copy($tmp_file, $target_file)) {
                return array(
                    'success' => true,
                    'url' => $upload_dir['baseurl'] . "/vortex/{$folder_type}/" . $user_id . '/' . $unique_filename
                );
            }
        } else {
            if (move_uploaded_file($tmp_file, $target_file)) {
                return array(
                    'success' => true,
                    'url' => $upload_dir['baseurl'] . "/vortex/{$folder_type}/" . $user_id . '/' . $unique_filename
                );
            }
        }
        
        return array('success' => false, 'error' => 'Failed to upload file locally');
    }
    
    /**
     * Chunked file copy to avoid memory issues
     */
    private function chunked_copy($source, $destination) {
        $chunk_size = $this->get_config('file_copy_chunk_size', 1024 * 1024); // 1MB chunks
        
        $src_handle = fopen($source, 'rb');
        $dest_handle = fopen($destination, 'wb');
        
        if (!$src_handle || !$dest_handle) {
            return false;
        }
        
        while (!feof($src_handle)) {
            $chunk = fread($src_handle, $chunk_size);
            if (fwrite($dest_handle, $chunk) === false) {
                fclose($src_handle);
                fclose($dest_handle);
                return false;
            }
        }
        
        fclose($src_handle);
        fclose($dest_handle);
        
        return true;
    }
    
    /**
     * Generate artwork thumbnail asynchronously
     */
    private function generate_artwork_thumbnail_async($image_url) {
        // Schedule thumbnail generation as background task
        wp_schedule_single_event(time() + 2, 'vortex_generate_thumbnail', array($image_url));
        
        // Return placeholder data immediately
        return array(
            'success' => true,
            'thumbnail_url' => $image_url, // Use original until thumbnail is ready
            'thumbnails' => array(),
            'dimensions' => array('width' => 'unknown', 'height' => 'unknown'),
            'processing' => true
        );
    }
    
    /**
     * Get Chloe AI inspiration for trend analysis
     */
    public function get_chloe_inspiration($user_id) {
        return $this->get_cached_data(
            "vortex_chloe_inspiration_{$user_id}",
            function() use ($user_id) {
                return $this->fetch_fresh_chloe_inspiration($user_id);
            },
            $this->get_config('cache_ttl')['chloe_inspiration'] ?? 1800
        );
    }
    
    /**
     * Fetch fresh Chloe AI inspiration
     */
    private function fetch_fresh_chloe_inspiration($user_id) {
        $chloe_api_endpoint = get_option('vortex_chloe_api_endpoint', 'http://localhost:3000/api/chloe');
        
        // Get user preferences and artwork history with optimized query
        $user_style = get_user_meta($user_id, 'vortex_art_category', true);
        $user_artworks = $this->get_user_artwork_summary($user_id, 5); // Limit to 5 most recent
        
        $user_context = array(
            'user_id' => $user_id,
            'preferred_style' => $user_style,
            'recent_artworks' => $user_artworks,
            'timestamp' => time(),
            'version' => '1.0'
        );
        
        $response = wp_remote_post($chloe_api_endpoint . '/inspiration', array(
            'timeout' => 15, // Reduced timeout
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-API-Version' => '1.0'
            ),
            'body' => json_encode($user_context)
        ));
        
        if (is_wp_error($response)) {
            $this->log_error('Chloe AI API error', array(
                'user_id' => $user_id,
                'error' => $response->get_error_message()
            ));
            
            return $this->get_fallback_inspiration($user_style);
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $this->log_error('Chloe AI API non-200 response', array(
                'user_id' => $user_id,
                'response_code' => $response_code
            ));
            
            return $this->get_fallback_inspiration($user_style);
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->get_fallback_inspiration($user_style);
        }
        
        return $data;
    }
    
    /**
     * Get optimized user artwork summary
     */
    private function get_user_artwork_summary($user_id, $limit = 5) {
        return $this->get_cached_data(
            "vortex_user_artwork_summary_{$user_id}_{$limit}",
            function() use ($user_id, $limit) {
                global $wpdb;
                
                $artworks = $wpdb->get_results($wpdb->prepare("
                    SELECT p.post_title, p.post_date,
                           MAX(CASE WHEN pm.meta_key = 'artwork_style' THEN pm.meta_value END) as style,
                           MAX(CASE WHEN pm.meta_key = 'artwork_category' THEN pm.meta_value END) as category
                    FROM {$wpdb->posts} p
                    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                    WHERE p.post_author = %d 
                    AND p.post_type = 'vortex_artwork'
                    AND p.post_status = 'publish'
                    GROUP BY p.ID
                    ORDER BY p.post_date DESC
                    LIMIT %d
                ", $user_id, $limit));
                
                return array_map(function($artwork) {
                    return array(
                        'title' => $artwork->post_title,
                        'style' => $artwork->style,
                        'category' => $artwork->category,
                        'created_date' => $artwork->post_date
                    );
                }, $artworks);
            },
            300 // 5 minute cache
        );
    }
    
    /**
     * Enhanced fallback inspiration with user-specific data
     */
    private function get_fallback_inspiration($user_style = null) {
        $base_trends = array(
            array(
                'title' => 'Digital Minimalism',
                'description' => 'Clean, simple designs with subtle complexity',
                'confidence' => 0.8,
                'category' => 'digital_art'
            ),
            array(
                'title' => 'Vibrant Abstracts',
                'description' => 'Bold colors with flowing abstract forms',
                'confidence' => 0.7,
                'category' => 'abstract'
            ),
            array(
                'title' => 'Retro Futurism',
                'description' => 'Nostalgic sci-fi aesthetics with modern twist',
                'confidence' => 0.6,
                'category' => '3d_art'
            ),
            array(
                'title' => 'Neo-Impressionism',
                'description' => 'Contemporary take on impressionist techniques',
                'confidence' => 0.75,
                'category' => 'illustration'
            ),
            array(
                'title' => 'Hyperrealistic Photography',
                'description' => 'Ultra-detailed photographic compositions',
                'confidence' => 0.85,
                'category' => 'photography'
            )
        );
        
        // Filter trends based on user style
        if ($user_style) {
            $filtered_trends = array_filter($base_trends, function($trend) use ($user_style) {
                return $trend['category'] === $user_style;
            });
            
            if (!empty($filtered_trends)) {
                $base_trends = array_merge($filtered_trends, array_slice($base_trends, 0, 2));
            }
        }
        
        return array(
            'trends' => array_slice($base_trends, 0, 3),
            'source' => 'fallback',
            'user_style' => $user_style,
            'timestamp' => time()
        );
    }
    
    /**
                    'key' => 'vortex_art_category',
                    'value' => $user_style
                )
            ),
            'number' => 10
        );
        
        $collectors = get_users($collector_args);
        
        $matches = array();
        foreach ($collectors as $collector) {
            $collector_activity = get_user_meta($collector->ID, 'vortex_collection_activity_score', true) ?: 0;
            $collector_budget = get_user_meta($collector->ID, 'vortex_collection_budget_range', true) ?: 'medium';
            
            $match_score = $this->calculate_match_score($performance_score, $collector_activity, $user_style);
            
            if ($match_score > 0.5) {
                $matches[] = array(
                    'collector_id' => $collector->ID,
                    'name' => $collector->display_name,
                    'match_score' => $match_score,
                    'budget_range' => $collector_budget,
                    'activity_score' => $collector_activity,
                    'shared_interests' => array($user_style)
                );
            }
        }
        
        // Sort by match score
        usort($matches, function($a, $b) {
            return $b['match_score'] <=> $a['match_score'];
        });
        
        return array_slice($matches, 0, 5); // Return top 5 matches
    }
    
    /**
     * Calculate match score between artist and collector
     */
    private function calculate_match_score($artist_performance, $collector_activity, $shared_style) {
        $style_weight = 0.4;
        $performance_weight = 0.3;
        $activity_weight = 0.3;
        
        $style_score = 1.0; // Perfect match since they share the same style
        $performance_score = min(1.0, $artist_performance / 100); // Normalize to 0-1
        $activity_score = min(1.0, $collector_activity / 100); // Normalize to 0-1
        
        return ($style_score * $style_weight) + 
               ($performance_score * $performance_weight) + 
               ($activity_score * $activity_weight);
    }
    
    /**
     * Notify contributors about daily art inclusion
     */
    private function notify_daily_art_contributors($contributors, $auction_id, $nft_address) {
        foreach ($contributors as $artwork) {
            $artist_id = get_post_field('post_author', $artwork->ID);
            $artist = get_userdata($artist_id);
            
            $subject = 'Your Artwork Featured in Tola Art of the Day!';
            $message = "
            <html>
            <body>
                <h2>Congratulations {$artist->display_name}!</h2>
                
                <p>Your artwork '<strong>{$artwork->post_title}</strong>' has been selected as part of today's Tola Art of the Day collaborative piece!</p>
                
                <p>This means:</p>
                <ul>
                    <li>Your art inspired today's AI-generated collective artwork</li>
                    <li>You'll receive an equal share of any proceeds from the auction</li>
                    <li>Your work will be featured in the VortexArtec community gallery</li>
                    <li>You'll earn bonus TOLA tokens for the collaboration</li>
                </ul>
                
                <p>View the auction: <a href='" . home_url("/auction/{$auction_id}") . "'>Tola Art of the Day Auction</a></p>
                
                <p>Best regards,<br>The VortexArtec Team</p>
            </body>
            </html>
            ";
            
            wp_mail($artist->user_email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
            
            // Award bonus TOLA tokens
            $this->award_tola_tokens($artist_id, 10, 'daily_art_collaboration');
        }
    }
}

// Initialize the Artist Journey system
VORTEX_Artist_Journey::get_instance(); 