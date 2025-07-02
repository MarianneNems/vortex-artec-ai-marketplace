<?php
/**
 * NFT API handler for minting and managing NFTs on TOLA blockchain.
 *
 * @since      2.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/api
 */
class Vortex_NFT_API {

    private $namespace;

    public function __construct() {
        $this->namespace = 'vortex/v1';
    }

    public function register_routes() {
        register_rest_route($this->namespace, '/nft/mint', array(
            'methods' => 'POST',
            'callback' => array($this, 'mint_nft'),
            'permission_callback' => array($this, 'check_user_permission'),
        ));

        register_rest_route($this->namespace, '/nft/(?P<token_id>[\w-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_nft_details'),
            'permission_callback' => '__return_true',
            'args' => array(
                'token_id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return !empty($param);
                    }
                ),
            ),
        ));

        register_rest_route($this->namespace, '/nft/(?P<token_id>[\w-]+)/transfer', array(
            'methods' => 'POST',
            'callback' => array($this, 'transfer_nft'),
            'permission_callback' => array($this, 'check_owner_permission'),
        ));
    }

    public function mint_nft($request) {
        $user_id = get_current_user_id();
        $artwork_id = $request->get_param('artwork_id');
        $image_url = $request->get_param('image_url');
        $name = $request->get_param('name');
        $description = $request->get_param('description');
        $royalty_percentage = $request->get_param('royalty_percentage') ?: 5.0;

        // Validate inputs
        if (empty($artwork_id) && empty($image_url)) {
            return new WP_Error('missing_artwork', 'Either artwork_id or image_url is required', array('status' => 400));
        }

        if (empty($name)) {
            return new WP_Error('missing_name', 'NFT name is required', array('status' => 400));
        }

        // Check user's NFT minting limits based on plan
        if (!$this->check_minting_limits($user_id)) {
            return new WP_Error('minting_limit_exceeded', 'Monthly NFT minting limit exceeded', array('status' => 429));
        }

        // Validate royalty percentage
        if ($royalty_percentage < 0 || $royalty_percentage > 15) {
            return new WP_Error('invalid_royalty', 'Royalty percentage must be between 0% and 15%', array('status' => 400));
        }

        // Get user's wallet address
        $wallet_address = get_user_meta($user_id, 'vortex_wallet_address', true);
        if (empty($wallet_address)) {
            return new WP_Error('no_wallet', 'Please connect your wallet first', array('status' => 400));
        }

        // Generate unique token ID
        $token_id = 'TOLA_' . uniqid() . '_' . $user_id;

        // Prepare NFT metadata
        $metadata = array(
            'name' => $name,
            'description' => $description,
            'image' => $image_url ?: $this->get_artwork_image_url($artwork_id),
            'attributes' => array(
                array('trait_type' => 'Artist', 'value' => get_user_meta($user_id, 'display_name', true)),
                array('trait_type' => 'Creation Date', 'value' => current_time('Y-m-d')),
                array('trait_type' => 'Platform', 'value' => 'VORTEX AI Marketplace'),
                array('trait_type' => 'Royalty', 'value' => $royalty_percentage . '%'),
            ),
            'properties' => array(
                'creator' => $wallet_address,
                'royalty_percentage' => $royalty_percentage,
                'platform_fee' => 5.0, // 5% platform fee
            ),
        );

        // Upload metadata to IPFS (mock implementation)
        $metadata_uri = $this->upload_to_ipfs($metadata);

        // Initiate blockchain minting process
        $mint_result = $this->initiate_blockchain_mint($token_id, $wallet_address, $metadata_uri, $royalty_percentage);

        if (is_wp_error($mint_result)) {
            return $mint_result;
        }

        // Store NFT record in database
        $nft_data = array(
            'token_id' => $token_id,
            'user_id' => $user_id,
            'artwork_id' => $artwork_id,
            'name' => $name,
            'description' => $description,
            'image_url' => $metadata['image'],
            'metadata_uri' => $metadata_uri,
            'owner_wallet' => $wallet_address,
            'creator_wallet' => $wallet_address,
            'royalty_percentage' => $royalty_percentage,
            'mint_status' => 'pending',
            'transaction_hash' => $mint_result['transaction_hash'],
            'created_at' => current_time('mysql'),
        );

        $this->store_nft_record($nft_data);

        // Update user's monthly minting count
        $this->increment_monthly_mints($user_id);

        return new WP_REST_Response(array(
            'success' => true,
            'token_id' => $token_id,
            'transaction_hash' => $mint_result['transaction_hash'],
            'metadata_uri' => $metadata_uri,
            'estimated_completion' => date('Y-m-d H:i:s', strtotime('+2 minutes')),
            'message' => 'NFT minting initiated successfully',
        ), 202);
    }

    public function get_nft_details($request) {
        $token_id = $request->get_param('token_id');
        
        // Fetch NFT details from database
        $nft_data = $this->get_nft_by_token_id($token_id);
        
        if (!$nft_data) {
            return new WP_Error('nft_not_found', 'NFT not found', array('status' => 404));
        }

        // Get current blockchain status
        $blockchain_status = $this->get_blockchain_status($token_id);
        
        // Get current market data
        $market_data = $this->get_market_data($token_id);

        return new WP_REST_Response(array(
            'success' => true,
            'nft' => array_merge($nft_data, array(
                'blockchain_status' => $blockchain_status,
                'market_data' => $market_data,
                'creator_info' => $this->get_creator_info($nft_data['user_id']),
                'ownership_history' => $this->get_ownership_history($token_id),
            )),
        ), 200);
    }

    public function transfer_nft($request) {
        $token_id = $request->get_param('token_id');
        $to_wallet = $request->get_param('to_wallet');
        $user_id = get_current_user_id();

        if (empty($to_wallet)) {
            return new WP_Error('missing_wallet', 'Destination wallet address is required', array('status' => 400));
        }

        // Verify NFT ownership
        $nft_data = $this->get_nft_by_token_id($token_id);
        if (!$nft_data) {
            return new WP_Error('nft_not_found', 'NFT not found', array('status' => 404));
        }

        $user_wallet = get_user_meta($user_id, 'vortex_wallet_address', true);
        if ($nft_data['owner_wallet'] !== $user_wallet) {
            return new WP_Error('not_owner', 'You do not own this NFT', array('status' => 403));
        }

        // Initiate blockchain transfer
        $transfer_result = $this->initiate_blockchain_transfer($token_id, $user_wallet, $to_wallet);

        if (is_wp_error($transfer_result)) {
            return $transfer_result;
        }

        // Update NFT ownership record
        $this->update_nft_ownership($token_id, $to_wallet, $transfer_result['transaction_hash']);

        return new WP_REST_Response(array(
            'success' => true,
            'token_id' => $token_id,
            'from_wallet' => $user_wallet,
            'to_wallet' => $to_wallet,
            'transaction_hash' => $transfer_result['transaction_hash'],
            'message' => 'NFT transfer initiated successfully',
        ), 200);
    }

    private function check_minting_limits($user_id) {
        $plan = get_user_meta($user_id, 'vortex_subscription_plan', true);
        $limits = array(
            'starter' => 5,
            'pro' => 25,
            'studio' => -1, // Unlimited
        );

        if ($plan === 'studio') {
            return true; // Unlimited
        }

        $monthly_key = 'vortex_monthly_nft_mints_' . date('Y-m');
        $monthly_count = get_user_meta($user_id, $monthly_key, true) ?: 0;
        $limit = $limits[$plan] ?? 0;

        return $monthly_count < $limit;
    }

    private function get_artwork_image_url($artwork_id) {
        if (empty($artwork_id)) return '';
        
        $attachment_url = wp_get_attachment_url($artwork_id);
        return $attachment_url ?: 'https://via.placeholder.com/512x512.png?text=Artwork';
    }

    private function upload_to_ipfs($metadata) {
        // Mock IPFS upload - in real implementation, use IPFS service
        $hash = 'Qm' . md5(json_encode($metadata));
        return 'https://ipfs.io/ipfs/' . $hash;
    }

    private function initiate_blockchain_mint($token_id, $wallet_address, $metadata_uri, $royalty_percentage) {
        // Mock blockchain minting - in real implementation, interact with Solana
        $transaction_hash = 'mint_' . md5($token_id . time());
        
        return array(
            'transaction_hash' => $transaction_hash,
            'status' => 'pending',
            'estimated_confirmation' => date('Y-m-d H:i:s', strtotime('+2 minutes')),
        );
    }

    private function store_nft_record($nft_data) {
        global $wpdb;
        
        // In real implementation, store in dedicated NFT table
        // For now, using post meta as example
        $post_id = wp_insert_post(array(
            'post_type' => 'vortex_nft',
            'post_title' => $nft_data['name'],
            'post_content' => $nft_data['description'],
            'post_status' => 'publish',
            'post_author' => $nft_data['user_id'],
        ));

        if ($post_id) {
            foreach ($nft_data as $key => $value) {
                update_post_meta($post_id, 'vortex_nft_' . $key, $value);
            }
        }

        return $post_id;
    }

    private function increment_monthly_mints($user_id) {
        $monthly_key = 'vortex_monthly_nft_mints_' . date('Y-m');
        $current_count = get_user_meta($user_id, $monthly_key, true) ?: 0;
        update_user_meta($user_id, $monthly_key, $current_count + 1);
    }

    private function get_nft_by_token_id($token_id) {
        $args = array(
            'post_type' => 'vortex_nft',
            'meta_query' => array(
                array(
                    'key' => 'vortex_nft_token_id',
                    'value' => $token_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1,
        );

        $posts = get_posts($args);
        if (empty($posts)) {
            return null;
        }

        $post = $posts[0];
        $meta = get_post_meta($post->ID);
        
        return array(
            'token_id' => $meta['vortex_nft_token_id'][0] ?? '',
            'user_id' => $post->post_author,
            'name' => $post->post_title,
            'description' => $post->post_content,
            'image_url' => $meta['vortex_nft_image_url'][0] ?? '',
            'owner_wallet' => $meta['vortex_nft_owner_wallet'][0] ?? '',
            'creator_wallet' => $meta['vortex_nft_creator_wallet'][0] ?? '',
            'royalty_percentage' => floatval($meta['vortex_nft_royalty_percentage'][0] ?? 5.0),
            'created_at' => $post->post_date,
        );
    }

    private function get_blockchain_status($token_id) {
        // Mock blockchain status check
        return array(
            'status' => 'confirmed',
            'confirmations' => 15,
            'block_number' => 1234567,
            'last_updated' => current_time('mysql'),
        );
    }

    private function get_market_data($token_id) {
        // Mock market data
        return array(
            'current_price' => null,
            'last_sale_price' => 75.00,
            'price_currency' => 'TOLA',
            'view_count' => rand(10, 100),
            'favorite_count' => rand(1, 25),
            'listing_status' => 'not_listed',
        );
    }

    private function get_creator_info($user_id) {
        $user = get_userdata($user_id);
        return array(
            'name' => $user->display_name,
            'wallet' => get_user_meta($user_id, 'vortex_wallet_address', true),
            'verified' => (bool) get_user_meta($user_id, 'vortex_verified_artist', true),
        );
    }

    private function get_ownership_history($token_id) {
        // Mock ownership history
        return array(
            array(
                'event' => 'minted',
                'from' => null,
                'to' => 'HN7cABqLq46Es1jh92dQQi5jipxu48PfAAMUBY4ik5VY',
                'price' => null,
                'timestamp' => current_time('mysql'),
                'transaction_hash' => 'mint_' . md5($token_id),
            ),
        );
    }

    private function initiate_blockchain_transfer($token_id, $from_wallet, $to_wallet) {
        // Mock blockchain transfer
        $transaction_hash = 'transfer_' . md5($token_id . $from_wallet . $to_wallet . time());
        
        return array(
            'transaction_hash' => $transaction_hash,
            'status' => 'pending',
        );
    }

    private function update_nft_ownership($token_id, $new_owner_wallet, $transaction_hash) {
        $args = array(
            'post_type' => 'vortex_nft',
            'meta_query' => array(
                array(
                    'key' => 'vortex_nft_token_id',
                    'value' => $token_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1,
        );

        $posts = get_posts($args);
        if (!empty($posts)) {
            update_post_meta($posts[0]->ID, 'vortex_nft_owner_wallet', $new_owner_wallet);
            update_post_meta($posts[0]->ID, 'vortex_nft_last_transfer_hash', $transaction_hash);
        }
    }

    public function check_user_permission($request) {
        return is_user_logged_in();
    }

    public function check_owner_permission($request) {
        if (!is_user_logged_in()) {
            return false;
        }

        $token_id = $request->get_param('token_id');
        $nft_data = $this->get_nft_by_token_id($token_id);
        
        if (!$nft_data) {
            return false;
        }

        $user_wallet = get_user_meta(get_current_user_id(), 'vortex_wallet_address', true);
        return $nft_data['owner_wallet'] === $user_wallet;
    }
} 