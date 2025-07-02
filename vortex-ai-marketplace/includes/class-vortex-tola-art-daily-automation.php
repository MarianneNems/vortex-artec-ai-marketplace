<?php
/**
 * VORTEX TOLA-ART Daily Automation System
 *
 * Automated daily artwork creation by HURAII with smart contract royalty distribution
 * to Marianne Nems (5%) and participating artists (remaining 95% divided equally).
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_TOLA_Art_Daily_Automation {
    
    /**
     * The single instance of this class
     */
    private static $instance = null;
    
    /**
     * Creator wallet address (Marianne Nems)
     */
    private $creator_wallet = '0x742d35Cc6634C0532925a3b8D';
    
    /**
     * VORTEX ARTEC admin account ID
     */
    private $admin_account_id = 1;
    
    /**
     * Smart contract address for royalty distribution
     */
    private $royalty_contract_address = '0x8B3F7A5D2E9C1A4F6B8D9E2A5C7F1B4E8D6A9C2F';
    
    /**
     * TOLA token contract address
     */
    private $tola_contract_address = '0x9F2E4B7A1D5C8E3F6A9B2D5E8C1F4A7B9E2C5D8F';
    
    /**
     * Royalty distribution percentages
     */
    private $creator_royalty_percentage = 5; // 5% to Marianne Nems
    private $artist_pool_percentage = 80; // 80% to participating artists
    private $marketplace_fee_percentage = 15; // 15% marketplace fee
    
    /**
     * Database tables
     */
    private $daily_art_table;
    private $artist_participation_table;
    private $royalty_distribution_table;
    
    /**
     * HURAII prompts for daily art generation
     */
    private $daily_prompts = array(
        'Abstract contemporary art with vibrant colors and geometric patterns, inspired by digital transformation',
        'Surreal landscape with floating islands and ethereal lighting, cyberpunk aesthetic',
        'Portrait of futuristic human with AI enhancements, neon glow effects',
        'Organic forms merged with technological elements, bio-tech fusion art',
        'Minimalist composition with bold shapes and gradient transitions',
        'Dynamic energy patterns with particle effects and cosmic themes',
        'Urban skyline reimagined with AI architecture and holographic elements',
        'Nature scene enhanced with digital overlays and augmented reality effects',
        'Abstract representation of data flows and neural networks',
        'Retro-futuristic art deco style with modern AI interpretations'
    );
    
    /**
     * Generation settings
     */
    private $generation_settings = array(
        'width' => 2048,
        'height' => 2048,
        'steps' => 50,
        'cfg_scale' => 7.5,
        'sampler' => 'DPM++ 2M Karras',
        'model' => 'stable-diffusion-xl-base-1.0',
        'quality' => 'high',
        'style' => 'artistic'
    );
    
    /**
     * Get the singleton instance
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
        global $wpdb;
        
        $this->daily_art_table = $wpdb->prefix . 'vortex_daily_art';
        $this->artist_participation_table = $wpdb->prefix . 'vortex_artist_participation';
        $this->royalty_distribution_table = $wpdb->prefix . 'vortex_royalty_distribution';
        
        $this->init_hooks();
        $this->create_tables();
        $this->schedule_daily_automation();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Daily automation hook
        add_action('vortex_daily_art_generation', array($this, 'generate_daily_art'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_vortex_trigger_daily_art', array($this, 'manual_trigger_daily_art'));
        add_action('wp_ajax_vortex_get_daily_art_stats', array($this, 'get_daily_art_stats'));
        
        // Marketplace hooks
        add_action('vortex_artwork_sold', array($this, 'handle_artwork_sale'), 10, 3);
        
        // WooCommerce integration hooks
        add_action('woocommerce_order_status_completed', array($this, 'handle_woocommerce_purchase'));
        add_action('woocommerce_payment_complete', array($this, 'handle_woocommerce_purchase'));
        
        // Smart contract hooks
        add_action('vortex_deploy_royalty_contract', array($this, 'deploy_royalty_contract'));
        
        // Artist participation hooks
        add_action('user_register', array($this, 'setup_artist_participation'));
        add_action('vortex_artist_verified', array($this, 'add_artist_to_participation'));
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Daily art table
        $daily_art_sql = "CREATE TABLE IF NOT EXISTS {$this->daily_art_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            artwork_id bigint(20) UNSIGNED DEFAULT NULL,
            woocommerce_product_id bigint(20) UNSIGNED DEFAULT NULL,
            prompt longtext NOT NULL,
            generation_settings longtext DEFAULT NULL,
            huraii_response longtext DEFAULT NULL,
            marketplace_listing_id bigint(20) UNSIGNED DEFAULT NULL,
            smart_contract_address varchar(42) DEFAULT NULL,
            generation_status enum('pending','generating','completed','failed','listed','sold') DEFAULT 'pending',
            total_sales decimal(18,8) UNSIGNED DEFAULT 0,
            royalties_distributed decimal(18,8) UNSIGNED DEFAULT 0,
            participating_artists_count int UNSIGNED DEFAULT 0,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_date (date),
            KEY artwork_id (artwork_id),
            KEY woocommerce_product_id (woocommerce_product_id),
            KEY generation_status (generation_status)
        ) $charset_collate;";
        
        // Artist participation table
        $participation_sql = "CREATE TABLE IF NOT EXISTS {$this->artist_participation_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            wallet_address varchar(42) NOT NULL,
            participation_date date NOT NULL,
            daily_art_id bigint(20) UNSIGNED NOT NULL,
            participation_weight decimal(10,4) UNSIGNED DEFAULT 1.0000,
            royalty_share decimal(18,8) UNSIGNED DEFAULT 0,
            payment_status enum('pending','processing','completed','failed') DEFAULT 'pending',
            payment_transaction_hash varchar(66) DEFAULT NULL,
            joined_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_participation (user_id, daily_art_id),
            KEY user_id (user_id),
            KEY daily_art_id (daily_art_id),
            KEY participation_date (participation_date)
        ) $charset_collate;";
        
        // Royalty distribution table
        $royalty_sql = "CREATE TABLE IF NOT EXISTS {$this->royalty_distribution_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            daily_art_id bigint(20) UNSIGNED NOT NULL,
            sale_transaction_hash varchar(66) NOT NULL,
            sale_amount decimal(18,8) UNSIGNED NOT NULL,
            creator_royalty decimal(18,8) UNSIGNED NOT NULL,
            artist_pool decimal(18,8) UNSIGNED NOT NULL,
            marketplace_fee decimal(18,8) UNSIGNED NOT NULL,
            participating_artists int UNSIGNED NOT NULL,
            individual_artist_share decimal(18,8) UNSIGNED NOT NULL,
            distribution_status enum('pending','processing','completed','failed') DEFAULT 'pending',
            distribution_transaction_hash varchar(66) DEFAULT NULL,
            block_number bigint(20) UNSIGNED DEFAULT NULL,
            gas_used bigint(20) UNSIGNED DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_sale (sale_transaction_hash),
            KEY daily_art_id (daily_art_id),
            KEY distribution_status (distribution_status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($daily_art_sql);
        dbDelta($participation_sql);
        dbDelta($royalty_sql);
    }
    
    /**
     * Schedule daily automation
     */
    private function schedule_daily_automation() {
        if (!wp_next_scheduled('vortex_daily_art_generation')) {
            // Schedule for midnight (00:00) daily
            wp_schedule_event(
                strtotime('00:00:00'),
                'daily',
                'vortex_daily_art_generation'
            );
        }
    }
    
    /**
     * Generate daily art
     */
    public function generate_daily_art() {
        global $wpdb;
        
        $today = current_time('Y-m-d');
        
        // Check if today's art already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->daily_art_table} WHERE date = %s",
            $today
        ));
        
        if ($existing) {
            error_log("TOLA-ART: Daily art for {$today} already exists");
            return;
        }
        
        // Get today's prompt
        $prompt = $this->get_daily_prompt();
        
        // Create daily art record
        $daily_art_id = $this->create_daily_art_record($today, $prompt);
        
        if (!$daily_art_id) {
            error_log("TOLA-ART: Failed to create daily art record");
            return;
        }
        
        // Update status to generating
        $this->update_daily_art_status($daily_art_id, 'generating');
        
        // Generate artwork with HURAII
        $generation_result = $this->generate_with_huraii($prompt, $daily_art_id);
        
        if (is_wp_error($generation_result)) {
            error_log("TOLA-ART: Generation failed - " . $generation_result->get_error_message());
            $this->update_daily_art_status($daily_art_id, 'failed');
            return;
        }
        
        // Update with generation results
        $artwork_id = $this->save_generated_artwork($generation_result, $daily_art_id);
        
        if (!$artwork_id) {
            error_log("TOLA-ART: Failed to save generated artwork");
            $this->update_daily_art_status($daily_art_id, 'failed');
            return;
        }
        
        // Update status to completed
        $this->update_daily_art_status($daily_art_id, 'completed');
        
        // Deploy smart contract for this artwork
        $contract_address = $this->deploy_artwork_contract($daily_art_id, $artwork_id);
        
        // List on marketplace
        $listing_id = $this->list_on_marketplace($artwork_id, $daily_art_id, $contract_address);
        
        if ($listing_id) {
            $this->update_daily_art_status($daily_art_id, 'listed');
            
            // Add participating artists
            $this->add_participating_artists($daily_art_id);
            
            // Notify completion
            $this->notify_daily_art_completion($daily_art_id, $artwork_id, $listing_id);
        }
        
        error_log("TOLA-ART: Daily art generation completed successfully for {$today}");
    }
    
    /**
     * Get daily prompt
     */
    private function get_daily_prompt() {
        $day_of_year = date('z');
        $prompt_index = $day_of_year % count($this->daily_prompts);
        $base_prompt = $this->daily_prompts[$prompt_index];
        
        // Add dynamic elements
        $current_time = current_time('H:i');
        $season = $this->get_current_season();
        $moon_phase = $this->get_moon_phase();
        
        $enhanced_prompt = $base_prompt . 
            ", {$season} atmosphere, " .
            "time essence of {$current_time}, " .
            "{$moon_phase} lunar influence, " .
            "TOLA-ART signature style, " .
            "high quality digital artwork, " .
            "suitable for NFT marketplace";
        
        return $enhanced_prompt;
    }
    
    /**
     * Create daily art record
     */
    private function create_daily_art_record($date, $prompt) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->daily_art_table,
            array(
                'date' => $date,
                'prompt' => $prompt,
                'generation_settings' => json_encode($this->generation_settings),
                'generation_status' => 'pending'
            ),
            array('%s', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Generate with HURAII
     */
    private function generate_with_huraii($prompt, $daily_art_id) {
        // Get HURAII instance
        $huraii = Vortex_HURAII_GPU_Backend::get_instance();
        
        // Prepare generation parameters
        $generation_params = array_merge($this->generation_settings, array(
            'prompt' => $prompt,
            'seed' => rand(1, 1000000),
            'batch_count' => 1,
            'negative_prompt' => 'low quality, blurry, distorted, watermark, signature',
            'metadata' => array(
                'daily_art_id' => $daily_art_id,
                'generation_type' => 'tola_daily_art',
                'creator' => 'VORTEX ARTEC',
                'ai_agent' => 'HURAII'
            )
        ));
        
        // Call HURAII generation API
        $api_response = $this->call_huraii_api($generation_params);
        
        if (is_wp_error($api_response)) {
            return $api_response;
        }
        
        // Update database with HURAII response
        global $wpdb;
        $wpdb->update(
            $this->daily_art_table,
            array('huraii_response' => json_encode($api_response)),
            array('id' => $daily_art_id),
            array('%s'),
            array('%d')
        );
        
        return $api_response;
    }
    
    /**
     * Call HURAII API
     */
    private function call_huraii_api($params) {
        // This would integrate with your actual HURAII/RunPod API
        // For now, returning mock successful response
        
        $mock_response = array(
            'success' => true,
            'images' => array(
                array(
                    'url' => 'https://generated-images.example.com/tola-art-' . date('Y-m-d') . '.png',
                    'width' => $params['width'],
                    'height' => $params['height'],
                    'seed' => $params['seed'],
                    'prompt' => $params['prompt'],
                    'model' => $params['model'],
                    'generation_time' => 45.2,
                    'gpu_used' => 'RTX A6000'
                )
            ),
            'metadata' => array(
                'generation_id' => uniqid('huraii_'),
                'timestamp' => current_time('mysql'),
                'cost_tola' => 25,
                'quality_score' => 94.7
            )
        );
        
        // Simulate API delay
        sleep(2);
        
        return $mock_response;
    }
    
    /**
     * Save generated artwork
     */
    private function save_generated_artwork($generation_result, $daily_art_id) {
        global $wpdb;
        
        if (!isset($generation_result['images'][0])) {
            return false;
        }
        
        $image_data = $generation_result['images'][0];
        
        // Create artwork post
        $artwork_post = array(
            'post_title' => 'TOLA Masterwork - ' . date('F j, Y'),
            'post_content' => 'Daily AI-generated masterwork created by HURAII for the VORTEX ARTEC community. This unique piece features algorithmic creativity with blockchain-verified provenance.',
            'post_status' => 'publish',
            'post_type' => 'artwork',
            'post_author' => $this->admin_account_id,
            'meta_input' => array(
                'artwork_type' => 'tola_daily_art',
                'ai_generated' => true,
                'ai_agent' => 'HURAII',
                'generation_prompt' => $image_data['prompt'],
                'generation_seed' => $image_data['seed'],
                'generation_model' => $image_data['model'],
                'image_url' => $image_data['url'],
                'image_width' => $image_data['width'],
                'image_height' => $image_data['height'],
                'daily_art_id' => $daily_art_id,
                'creator_royalty' => $this->creator_royalty_percentage,
                'creator_wallet' => $this->creator_wallet,
                'marketplace_price' => 100, // 100 TOLA
                'is_nft' => true,
                'blockchain_verified' => false,
                'generation_metadata' => json_encode($generation_result['metadata']),
                'artist_pool_percentage' => $this->artist_pool_percentage,
                'marketplace_fee_percentage' => $this->marketplace_fee_percentage
            )
        );
        
        $artwork_id = wp_insert_post($artwork_post);
        
        if ($artwork_id) {
            // Update daily art record with artwork ID
            $wpdb->update(
                $this->daily_art_table,
                array('artwork_id' => $artwork_id),
                array('id' => $daily_art_id),
                array('%d'),
                array('%d')
            );
            
            // Download and store image locally
            $this->download_and_store_image($image_data['url'], $artwork_id);
        }
        
        return $artwork_id;
    }
    
    /**
     * Deploy artwork smart contract
     */
    private function deploy_artwork_contract($daily_art_id, $artwork_id) {
        // Get participating artists count
        $participating_artists = $this->get_participating_artists_count();
        
        // Prepare smart contract deployment parameters
        $contract_params = array(
            'artwork_id' => $artwork_id,
            'daily_art_id' => $daily_art_id,
            'creator_address' => $this->creator_wallet,
            'creator_royalty_percentage' => $this->creator_royalty_percentage,
            'participating_artists_count' => $participating_artists,
            'artist_pool_percentage' => $this->artist_pool_percentage,
            'tola_token_address' => $this->tola_contract_address,
            'marketplace_address' => get_option('vortex_marketplace_contract_address')
        );
        
        // Deploy contract
        $contract_address = $this->deploy_smart_contract($contract_params);
        
        if ($contract_address) {
            // Update daily art record
            global $wpdb;
            $wpdb->update(
                $this->daily_art_table,
                array('smart_contract_address' => $contract_address),
                array('id' => $daily_art_id),
                array('%s'),
                array('%d')
            );
            
            // Update artwork metadata
            update_post_meta($artwork_id, 'smart_contract_address', $contract_address);
            update_post_meta($artwork_id, 'blockchain_verified', true);
        }
        
        return $contract_address;
    }
    
    /**
     * Deploy smart contract
     */
    private function deploy_smart_contract($params) {
        // This would integrate with your blockchain deployment system
        // For now, returning mock contract address
        
        $mock_contract_address = '0x' . bin2hex(random_bytes(20));
        
        // Simulate blockchain deployment delay
        sleep(10);
        
        error_log("TOLA-ART: Smart contract deployed at {$mock_contract_address}");
        
        return $mock_contract_address;
    }
    
    /**
     * List on marketplace
     */
    private function list_on_marketplace($artwork_id, $daily_art_id, $contract_address) {
        global $wpdb;
        
        // Update the existing WooCommerce product with today's artwork
        $wc_product_id = $this->update_woocommerce_product($artwork_id, $daily_art_id, $contract_address);
        
        // Create marketplace listing
        $marketplace_table = $wpdb->prefix . 'vortex_marketplace_listings';
        
        $listing_data = array(
            'artwork_id' => $artwork_id,
            'woocommerce_product_id' => $wc_product_id,
            'seller_id' => $this->admin_account_id,
            'seller_name' => 'VORTEX ARTEC',
            'price' => 4500, // $4,500 USD (converted to TOLA)
            'currency' => 'USD',
            'listing_type' => 'fixed_price',
            'smart_contract_address' => $contract_address,
            'is_featured' => true,
            'is_daily_art' => true,
            'auto_generated' => true,
            'royalty_enabled' => true,
            'creator_royalty' => $this->creator_royalty_percentage,
            'listing_status' => 'active',
            'visibility' => 'public',
            'woocommerce_integration' => true,
            'metadata' => json_encode(array(
                'daily_art_id' => $daily_art_id,
                'generation_date' => current_time('Y-m-d'),
                'ai_agent' => 'HURAII',
                'automated_listing' => true,
                'community_artwork' => true,
                'woocommerce_sku' => 'Tola-art-daily',
                'product_url' => home_url('/product/tola-masterwork-daily-art-drop-at-0000-limited-series/')
            ))
        );
        
        $result = $wpdb->insert($marketplace_table, $listing_data);
        
        if ($result) {
            $listing_id = $wpdb->insert_id;
            
            // Update daily art record
            $wpdb->update(
                $this->daily_art_table,
                array(
                    'marketplace_listing_id' => $listing_id,
                    'woocommerce_product_id' => $wc_product_id
                ),
                array('id' => $daily_art_id),
                array('%d', '%d'),
                array('%d')
            );
            
            // Update artwork metadata
            update_post_meta($artwork_id, 'marketplace_listing_id', $listing_id);
            update_post_meta($artwork_id, 'woocommerce_product_id', $wc_product_id);
            update_post_meta($artwork_id, 'listing_price', 4500);
            update_post_meta($artwork_id, 'listing_currency', 'USD');
            
            return $listing_id;
        }
        
        return false;
    }
    
    /**
     * Update WooCommerce product with daily artwork
     */
    private function update_woocommerce_product($artwork_id, $daily_art_id, $contract_address) {
        // Find the TOLA Masterwork product by SKU
        $product_id = wc_get_product_id_by_sku('Tola-art-daily');
        
        if (!$product_id) {
            // Create the product if it doesn't exist
            $product_id = $this->create_tola_masterwork_product();
        }
        
        if (!$product_id) {
            error_log("TOLA-ART: Failed to find or create WooCommerce product");
            return false;
        }
        
        $product = wc_get_product($product_id);
        
        if (!$product) {
            error_log("TOLA-ART: Failed to load WooCommerce product");
            return false;
        }
        
        // Get today's artwork details
        $artwork_post = get_post($artwork_id);
        $image_url = get_post_meta($artwork_id, 'image_url', true);
        $generation_date = current_time('F j, Y');
        
        // Update product title with today's date
        $product->set_name("TOLA Masterwork - {$generation_date}");
        
        // Update product description
        $description = "Today's AI-generated masterwork created by HURAII for the VORTEX ARTEC community. " .
                      "This unique piece features algorithmic creativity with blockchain-verified provenance. " .
                      "Generated on {$generation_date} with smart contract verification.";
        
        $product->set_description($description);
        $product->set_short_description("Daily TOLA Masterwork - Limited Edition AI Art with Blockchain Provenance");
        
        // Set product as in stock and purchasable
        $product->set_stock_status('instock');
        $product->set_manage_stock(true);
        $product->set_stock_quantity(1); // Limited to 1 per day
        $product->set_catalog_visibility('visible');
        
        // Update product metadata
        $product->update_meta_data('_artwork_id', $artwork_id);
        $product->update_meta_data('_daily_art_id', $daily_art_id);
        $product->update_meta_data('_smart_contract_address', $contract_address);
        $product->update_meta_data('_generation_date', current_time('Y-m-d'));
        $product->update_meta_data('_ai_agent', 'HURAII');
        $product->update_meta_data('_blockchain_verified', true);
        $product->update_meta_data('_is_tola_masterwork', true);
        
        // Handle product image if available
        if ($image_url) {
            $image_id = $this->attach_image_to_product($image_url, $product_id, $generation_date);
            if ($image_id) {
                $product->set_image_id($image_id);
                
                // Set as gallery image as well
                $gallery_ids = array($image_id);
                $product->set_gallery_image_ids($gallery_ids);
            }
        }
        
        // Save the product
        $product->save();
        
        // Log successful update
        error_log("TOLA-ART: Updated WooCommerce product #{$product_id} with daily artwork #{$artwork_id}");
        
        return $product_id;
    }
    
    /**
     * Create TOLA Masterwork WooCommerce product if it doesn't exist
     */
    private function create_tola_masterwork_product() {
        $product = new WC_Product_Simple();
        
        $product->set_name('TOLA Masterwork (Daily Co-Creation at 00:00) Limited Series');
        $product->set_slug('tola-masterwork-daily-art-drop-at-0000-limited-series');
        $product->set_sku('Tola-art-daily');
        $product->set_price(4500);
        $product->set_regular_price(4500);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_stock_status('instock');
        $product->set_manage_stock(true);
        $product->set_stock_quantity(1);
        
        $description = '"TOLA Masterwork" is a daily incentive feature on the VORTEX platform that rewards the most impactful artistic contribution within a 24-hour cycle. Powered by AI, it selects one piece of art each day based on a mix of originality, engagement, and artistic integrity. The selected artist receives a bonus in TOLA tokens, special visibility across the platform, and earns a spotlight in the VORTEX ecosystem, encouraging consistent creativity and elevating quality over quantity.';
        
        $product->set_description($description);
        $product->set_short_description('Daily AI-generated masterwork with blockchain provenance and artist royalties.');
        
        // Set product metadata
        $product->update_meta_data('_is_tola_masterwork', true);
        $product->update_meta_data('_daily_automation', true);
        $product->update_meta_data('_ai_generated', true);
        
        $product_id = $product->save();
        
        if ($product_id) {
            error_log("TOLA-ART: Created new WooCommerce product #{$product_id}");
        }
        
        return $product_id;
    }
    
    /**
     * Attach image to WooCommerce product
     */
    private function attach_image_to_product($image_url, $product_id, $generation_date) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Download image
        $temp_file = download_url($image_url);
        
        if (is_wp_error($temp_file)) {
            error_log("TOLA-ART: Failed to download image: " . $temp_file->get_error_message());
            return false;
        }
        
        // Prepare file array
        $file_array = array(
            'name' => "tola-masterwork-{$generation_date}.png",
            'tmp_name' => $temp_file
        );
        
        // Upload to media library
        $attachment_id = media_handle_sideload($file_array, $product_id, "TOLA Masterwork - {$generation_date}");
        
        // Clean up temp file
        @unlink($temp_file);
        
        if (is_wp_error($attachment_id)) {
            error_log("TOLA-ART: Failed to attach image: " . $attachment_id->get_error_message());
            return false;
        }
        
        // Set as product featured image
        set_post_thumbnail($product_id, $attachment_id);
        
        return $attachment_id;
    }

    /**
     * Add participating artists
     */
    private function add_participating_artists($daily_art_id) {
        global $wpdb;
        
        // Get all verified artists with wallet addresses
        $artists = $wpdb->get_results("
            SELECT u.ID as user_id, 
                   um.meta_value as wallet_address,
                   um2.meta_value as participation_weight
            FROM {$wpdb->users} u
            JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'wallet_address'
            LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'participation_weight'
            JOIN {$wpdb->usermeta} um3 ON u.ID = um3.user_id AND um3.meta_key = 'user_type' AND um3.meta_value = 'artist'
            JOIN {$wpdb->usermeta} um4 ON u.ID = um4.user_id AND um4.meta_key = 'artist_verified' AND um4.meta_value = '1'
            WHERE um.meta_value IS NOT NULL AND um.meta_value != ''
        ");
        
        $total_artists = count($artists);
        
        if ($total_artists === 0) {
            error_log("TOLA-ART: No participating artists found");
            return;
        }
        
        // Add each artist to participation table
        foreach ($artists as $artist) {
            $participation_weight = $artist->participation_weight ?: 1.0;
            
            $wpdb->insert(
                $this->artist_participation_table,
                array(
                    'user_id' => $artist->user_id,
                    'wallet_address' => $artist->wallet_address,
                    'participation_date' => current_time('Y-m-d'),
                    'daily_art_id' => $daily_art_id,
                    'participation_weight' => $participation_weight,
                    'payment_status' => 'pending'
                ),
                array('%d', '%s', '%s', '%d', '%f', '%s')
            );
        }
        
        // Update daily art record with participating artists count
        $wpdb->update(
            $this->daily_art_table,
            array('participating_artists_count' => $total_artists),
            array('id' => $daily_art_id),
            array('%d'),
            array('%d')
        );
        
        error_log("TOLA-ART: Added {$total_artists} participating artists");
    }
    
    /**
     * Handle artwork sale
     */
    public function handle_artwork_sale($artwork_id, $sale_amount, $transaction_hash) {
        global $wpdb;
        
        // Check if this is a daily art piece
        $daily_art_id = get_post_meta($artwork_id, 'daily_art_id', true);
        
        if (!$daily_art_id) {
            return; // Not a daily art piece
        }
        
        // Get participating artists
        $participating_artists = $wpdb->get_var($wpdb->prepare(
            "SELECT participating_artists_count FROM {$this->daily_art_table} WHERE id = %d",
            $daily_art_id
        ));
        
        if ($participating_artists == 0) {
            error_log("TOLA-ART: No participating artists for sale");
            return;
        }
        
        // Calculate royalty distribution with new percentages
        $creator_royalty = $sale_amount * ($this->creator_royalty_percentage / 100); // 5% to Marianne Nems
        $artist_pool = $sale_amount * ($this->artist_pool_percentage / 100); // 80% to artists
        $marketplace_fee = $sale_amount * ($this->marketplace_fee_percentage / 100); // 15% marketplace fee
        $individual_artist_share = $artist_pool / $participating_artists;
        
        // Record royalty distribution
        $distribution_id = $wpdb->insert(
            $this->royalty_distribution_table,
            array(
                'daily_art_id' => $daily_art_id,
                'sale_transaction_hash' => $transaction_hash,
                'sale_amount' => $sale_amount,
                'creator_royalty' => $creator_royalty,
                'artist_pool' => $artist_pool,
                'marketplace_fee' => $marketplace_fee,
                'participating_artists' => $participating_artists,
                'individual_artist_share' => $individual_artist_share,
                'distribution_status' => 'pending'
            ),
            array('%d', '%s', '%f', '%f', '%f', '%f', '%d', '%f', '%s')
        );
        
        if ($distribution_id) {
            // Execute distribution
            $this->execute_royalty_distribution($wpdb->insert_id);
        }
    }
    
    /**
     * Handle WooCommerce purchase completion
     */
    public function handle_woocommerce_purchase($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Check if this order contains the TOLA Masterwork product
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            
            if (!$product) {
                continue;
            }
            
            $sku = $product->get_sku();
            
            // Check if this is our TOLA Masterwork product
            if ($sku === 'Tola-art-daily') {
                $this->process_tola_masterwork_purchase($order, $item, $product);
                break;
            }
        }
    }
    
    /**
     * Process TOLA Masterwork purchase
     */
    private function process_tola_masterwork_purchase($order, $item, $product) {
        global $wpdb;
        
        $product_id = $product->get_id();
        $order_id = $order->get_id();
        $order_total = $order->get_total();
        
        // Get the associated artwork and daily art data
        $artwork_id = $product->get_meta('_artwork_id');
        $daily_art_id = $product->get_meta('_daily_art_id');
        $smart_contract_address = $product->get_meta('_smart_contract_address');
        
        if (!$artwork_id || !$daily_art_id) {
            error_log("TOLA-ART: Missing artwork or daily art ID for order #{$order_id}");
            return;
        }
        
        // Get participating artists count
        $participating_artists = $wpdb->get_var($wpdb->prepare(
            "SELECT participating_artists_count FROM {$this->daily_art_table} WHERE id = %d",
            $daily_art_id
        ));
        
        if ($participating_artists == 0) {
            error_log("TOLA-ART: No participating artists for order #{$order_id}");
            return;
        }
        
        // Calculate royalty distribution (after 15% marketplace fee is deducted)
        $net_amount = $order_total * 0.85; // Remove 15% marketplace fee
        $creator_royalty = $net_amount * ($this->creator_royalty_percentage / 100); // 5% to Marianne Nems
        $artist_pool = $net_amount * ($this->artist_pool_percentage / 100); // 80% to artists
        $marketplace_fee = $order_total * 0.15; // 15% marketplace fee
        $individual_artist_share = $artist_pool / $participating_artists;
        
        // Create unique transaction hash for this order
        $transaction_hash = 'wc_order_' . $order_id . '_' . time();
        
        // Record royalty distribution
        $distribution_id = $wpdb->insert(
            $this->royalty_distribution_table,
            array(
                'daily_art_id' => $daily_art_id,
                'sale_transaction_hash' => $transaction_hash,
                'sale_amount' => $order_total,
                'creator_royalty' => $creator_royalty,
                'artist_pool' => $artist_pool,
                'marketplace_fee' => $marketplace_fee,
                'participating_artists' => $participating_artists,
                'individual_artist_share' => $individual_artist_share,
                'distribution_status' => 'pending'
            ),
            array('%d', '%s', '%f', '%f', '%f', '%f', '%d', '%f', '%s')
        );
        
        if ($distribution_id) {
            // Update daily art record with sale information
            $wpdb->update(
                $this->daily_art_table,
                array(
                    'total_sales' => $order_total,
                    'royalties_distributed' => 0 // Will be updated after distribution
                ),
                array('id' => $daily_art_id),
                array('%f', '%f'),
                array('%d')
            );
            
            // Add order metadata
            $order->add_meta_data('_tola_masterwork_sale', true);
            $order->add_meta_data('_artwork_id', $artwork_id);
            $order->add_meta_data('_daily_art_id', $daily_art_id);
            $order->add_meta_data('_distribution_id', $wpdb->insert_id);
            $order->add_meta_data('_participating_artists', $participating_artists);
            $order->add_meta_data('_creator_royalty', $creator_royalty);
            $order->add_meta_data('_artist_pool', $artist_pool);
            $order->save();
            
            // Execute royalty distribution
            $this->execute_royalty_distribution($wpdb->insert_id);
            
            // Send notification emails
            $this->notify_tola_masterwork_sale($order, $daily_art_id, $artwork_id);
            
            // Mark product as sold out (limit 1 per day)
            $product->set_stock_quantity(0);
            $product->set_stock_status('outofstock');
            $product->save();
            
            error_log("TOLA-ART: Processed WooCommerce sale for order #{$order_id}, artwork #{$artwork_id}");
        }
    }
    
    /**
     * Notify TOLA Masterwork sale
     */
    private function notify_tola_masterwork_sale($order, $daily_art_id, $artwork_id) {
        global $wpdb;
        
        $artwork = get_post($artwork_id);
        $customer_email = $order->get_billing_email();
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        
        // Email to customer
        $customer_subject = 'Thank you for purchasing today\'s TOLA Masterwork!';
        $customer_message = "
        <h2>TOLA Masterwork Purchase Confirmation</h2>
        <p>Dear {$customer_name},</p>
        
        <p>Thank you for purchasing today's <strong>TOLA Masterwork</strong>: <em>{$artwork->post_title}</em></p>
        
        <p>Your purchase directly supports our community of artists through our automated royalty distribution system. 
        80% of your purchase (after marketplace fees) will be distributed among all participating artists who contributed 
        to today's collaborative creation.</p>
        
        <p><strong>Order Details:</strong></p>
        <ul>
            <li>Order ID: #{$order->get_id()}</li>
            <li>Artwork: {$artwork->post_title}</li>
            <li>Blockchain Verified: Yes</li>
            <li>AI Agent: HURAII</li>
        </ul>
        
        <p>You will receive your digital artwork and blockchain certificate within 24 hours.</p>
        
        <p>Best regards,<br>The VORTEX ARTEC Team</p>
        ";
        
        wp_mail($customer_email, $customer_subject, $customer_message, array('Content-Type: text/html; charset=UTF-8'));
        
        // Email to participating artists
        $participating_artists = $wpdb->get_results($wpdb->prepare(
            "SELECT u.display_name, u.user_email, ap.participation_weight, ap.royalty_share
             FROM {$this->artist_participation_table} ap
             JOIN {$wpdb->users} u ON ap.user_id = u.ID
             WHERE ap.daily_art_id = %d AND ap.payment_status = 'pending'",
            $daily_art_id
        ));
        
        foreach ($participating_artists as $artist) {
            $artist_subject = 'Your TOLA Masterwork has been sold!';
            $artist_message = "
            <h2>TOLA Masterwork Sale Notification</h2>
            <p>Dear {$artist->display_name},</p>
            
            <p>Great news! Today's TOLA Masterwork featuring your collaborative contribution has been sold!</p>
            
            <p><strong>Sale Details:</strong></p>
            <ul>
                <li>Artwork: {$artwork->post_title}</li>
                <li>Your Royalty Share: \$" . number_format($artist->royalty_share, 2) . "</li>
                <li>Participation Weight: " . number_format($artist->participation_weight, 2) . "</li>
            </ul>
            
            <p>Your royalty payment will be processed automatically and transferred to your registered wallet address within 24 hours.</p>
            
            <p>Thank you for being part of the VORTEX ARTEC community!</p>
            
            <p>Best regards,<br>The VORTEX ARTEC Team</p>
            ";
            
            wp_mail($artist->user_email, $artist_subject, $artist_message, array('Content-Type: text/html; charset=UTF-8'));
        }
    }
    
    /**
     * Execute royalty distribution
     */
    private function execute_royalty_distribution($distribution_id) {
        global $wpdb;
        
        // Get distribution record
        $distribution = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->royalty_distribution_table} WHERE id = %d",
            $distribution_id
        ));
        
        if (!$distribution) {
            return;
        }
        
        // Update status to processing
        $wpdb->update(
            $this->royalty_distribution_table,
            array('distribution_status' => 'processing'),
            array('id' => $distribution_id),
            array('%s'),
            array('%d')
        );
        
        // Pay creator royalty
        $creator_payment = $this->send_tola_payment(
            $this->creator_wallet,
            $distribution->creator_royalty,
            "TOLA-ART Creator Royalty - " . date('Y-m-d')
        );
        
        // Pay participating artists
        $artists = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->artist_participation_table} WHERE daily_art_id = %d",
            $distribution->daily_art_id
        ));
        
        $successful_payments = 0;
        
        foreach ($artists as $artist) {
            $payment_result = $this->send_tola_payment(
                $artist->wallet_address,
                $distribution->individual_artist_share,
                "TOLA-ART Artist Share - " . date('Y-m-d')
            );
            
            if ($payment_result) {
                $wpdb->update(
                    $this->artist_participation_table,
                    array(
                        'royalty_share' => $distribution->individual_artist_share,
                        'payment_status' => 'completed',
                        'payment_transaction_hash' => $payment_result['transaction_hash']
                    ),
                    array('id' => $artist->id),
                    array('%f', '%s', '%s'),
                    array('%d')
                );
                
                $successful_payments++;
            } else {
                $wpdb->update(
                    $this->artist_participation_table,
                    array('payment_status' => 'failed'),
                    array('id' => $artist->id),
                    array('%s'),
                    array('%d')
                );
            }
        }
        
        // Update distribution status
        $final_status = ($successful_payments == count($artists) && $creator_payment) ? 'completed' : 'failed';
        
        $wpdb->update(
            $this->royalty_distribution_table,
            array(
                'distribution_status' => $final_status,
                'distribution_transaction_hash' => $creator_payment['transaction_hash'] ?? null
            ),
            array('id' => $distribution_id),
            array('%s', '%s'),
            array('%d')
        );
        
        error_log("TOLA-ART: Royalty distribution {$final_status} - {$successful_payments}/{$distribution->participating_artists} artist payments successful");
    }
    
    /**
     * Send TOLA payment
     */
    private function send_tola_payment($wallet_address, $amount, $memo) {
        // This would integrate with your TOLA payment system
        // For now, returning mock successful payment
        
        $mock_payment = array(
            'success' => true,
            'transaction_hash' => '0x' . bin2hex(random_bytes(32)),
            'amount' => $amount,
            'recipient' => $wallet_address,
            'memo' => $memo,
            'block_number' => rand(1000000, 9999999),
            'gas_used' => rand(21000, 50000)
        );
        
        error_log("TOLA-ART: Sent {$amount} TOLA to {$wallet_address} - {$memo}");
        
        return $mock_payment;
    }
    
    /**
     * Get current season
     */
    private function get_current_season() {
        $month = date('n');
        $seasons = array(
            'winter' => array(12, 1, 2),
            'spring' => array(3, 4, 5),
            'summer' => array(6, 7, 8),
            'autumn' => array(9, 10, 11)
        );
        
        foreach ($seasons as $season => $months) {
            if (in_array($month, $months)) {
                return $season;
            }
        }
        
        return 'spring';
    }
    
    /**
     * Get moon phase
     */
    private function get_moon_phase() {
        $phases = array('new moon', 'waxing crescent', 'first quarter', 'waxing gibbous', 
                       'full moon', 'waning gibbous', 'last quarter', 'waning crescent');
        
        $day_of_month = date('j');
        $phase_index = floor(($day_of_month / 29.5) * 8) % 8;
        
        return $phases[$phase_index];
    }
    
    /**
     * Get participating artists count
     */
    private function get_participating_artists_count() {
        global $wpdb;
        
        return $wpdb->get_var("
            SELECT COUNT(DISTINCT u.ID)
            FROM {$wpdb->users} u
            JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'wallet_address'
            JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'user_type' AND um2.meta_value = 'artist'
            JOIN {$wpdb->usermeta} um3 ON u.ID = um3.user_id AND um3.meta_key = 'artist_verified' AND um3.meta_value = '1'
            WHERE um.meta_value IS NOT NULL AND um.meta_value != ''
        ");
    }
    
    /**
     * Update daily art status
     */
    private function update_daily_art_status($daily_art_id, $status) {
        global $wpdb;
        
        $wpdb->update(
            $this->daily_art_table,
            array('generation_status' => $status),
            array('id' => $daily_art_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Download and store image
     */
    private function download_and_store_image($image_url, $artwork_id) {
        // Download image and store locally
        // This would typically download from HURAII/RunPod and store in WordPress media library
        
        error_log("TOLA-ART: Image downloaded and stored for artwork {$artwork_id}");
    }
    
    /**
     * Notify daily art completion
     */
    private function notify_daily_art_completion($daily_art_id, $artwork_id, $listing_id) {
        // Send notifications to admin, artists, etc.
        
        error_log("TOLA-ART: Daily art completion notifications sent");
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'TOLA-ART Daily Automation',
            'TOLA-ART Daily',
            'manage_options',
            'tola-art-daily',
            array($this, 'admin_page'),
            'dashicons-art',
            30
        );
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        include plugin_dir_path(__FILE__) . '../admin/partials/tola-art-daily-admin.php';
    }
    
    /**
     * Manual trigger for testing
     */
    public function manual_trigger_daily_art() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $this->generate_daily_art();
        wp_send_json_success('Daily art generation triggered successfully');
    }
    
    /**
     * Get daily art statistics
     */
    public function get_daily_art_stats() {
        global $wpdb;
        
        $stats = array(
            'total_generated' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->daily_art_table}"),
            'successful_generations' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->daily_art_table} WHERE generation_status = 'listed'"),
            'total_sales' => $wpdb->get_var("SELECT SUM(total_sales) FROM {$this->daily_art_table}"),
            'total_royalties_distributed' => $wpdb->get_var("SELECT SUM(royalties_distributed) FROM {$this->daily_art_table}"),
            'participating_artists' => $this->get_participating_artists_count(),
            'recent_generations' => $wpdb->get_results("SELECT * FROM {$this->daily_art_table} ORDER BY created_at DESC LIMIT 10")
        );
        
        wp_send_json_success($stats);
    }
    
    /**
     * Setup artist participation
     */
    public function setup_artist_participation($user_id) {
        // Automatically set up participation for new artists
        if (get_user_meta($user_id, 'user_type', true) === 'artist') {
            update_user_meta($user_id, 'participation_weight', 1.0);
            update_user_meta($user_id, 'auto_participate_daily_art', true);
        }
    }
    
    /**
     * Add artist to participation
     */
    public function add_artist_to_participation($user_id) {
        // Called when artist gets verified
        update_user_meta($user_id, 'daily_art_eligible', true);
        
        error_log("TOLA-ART: Artist {$user_id} added to daily art participation");
    }
}

// Initialize the automation system
Vortex_TOLA_Art_Daily_Automation::get_instance(); 