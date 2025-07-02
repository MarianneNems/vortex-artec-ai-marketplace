<?php
/**
 * Listings API handler for marketplace artwork listings and sales.
 */
class Vortex_Listings_API {

    private $namespace = 'vortex/v1';

    public function register_routes() {
        register_rest_route($this->namespace, '/listings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_listings'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route($this->namespace, '/listings', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_listing'),
            'permission_callback' => array($this, 'check_user_permission'),
        ));

        register_rest_route($this->namespace, '/listings/(?P<id>\d+)/purchase', array(
            'methods' => 'POST',
            'callback' => array($this, 'purchase_listing'),
            'permission_callback' => array($this, 'check_user_permission'),
        ));
    }

    public function get_listings($request) {
        $category = $request->get_param('category');
        $price_min = $request->get_param('price_min');
        $price_max = $request->get_param('price_max');
        
        $args = array(
            'post_type' => 'vortex_listing',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'meta_query' => array(
                array(
                    'key' => 'vortex_listing_status',
                    'value' => 'active',
                    'compare' => '='
                )
            )
        );
        
        if ($price_min || $price_max) {
            $price_query = array('key' => 'vortex_listing_price');
            if ($price_min) $price_query['value'] = array($price_min);
            if ($price_max) $price_query['value'][] = $price_max;
            if ($price_min && $price_max) {
                $price_query['compare'] = 'BETWEEN';
                $price_query['type'] = 'NUMERIC';
            }
            $args['meta_query'][] = $price_query;
        }
        
        $listings = get_posts($args);
        $response_data = array();
        
        foreach ($listings as $listing) {
            $response_data[] = array(
                'id' => $listing->ID,
                'title' => $listing->post_title,
                'price' => get_post_meta($listing->ID, 'vortex_listing_price', true),
                'currency' => 'TOLA',
                'image_url' => get_the_post_thumbnail_url($listing->ID),
                'artist' => get_user_by('ID', $listing->post_author)->display_name,
                'created_at' => $listing->post_date,
                'view_count' => get_post_meta($listing->ID, 'vortex_listing_views', true) ?: 0,
            );
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'listings' => $response_data,
            'total' => count($response_data),
        ), 200);
    }

    public function create_listing($request) {
        $title = $request->get_param('title');
        $price = $request->get_param('price');
        $artwork_id = $request->get_param('artwork_id');
        $description = $request->get_param('description');
        
        if (empty($title) || empty($price) || empty($artwork_id)) {
            return new WP_Error('missing_required', 'Title, price, and artwork_id are required', array('status' => 400));
        }
        
        $listing_id = wp_insert_post(array(
            'post_type' => 'vortex_listing',
            'post_title' => $title,
            'post_content' => $description,
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ));
        
        if ($listing_id) {
            update_post_meta($listing_id, 'vortex_listing_price', floatval($price));
            update_post_meta($listing_id, 'vortex_listing_artwork_id', $artwork_id);
            update_post_meta($listing_id, 'vortex_listing_status', 'active');
            update_post_meta($listing_id, 'vortex_listing_created', current_time('mysql'));
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'listing_id' => $listing_id,
            'message' => 'Listing created successfully',
        ), 201);
    }

    public function purchase_listing($request) {
        $listing_id = $request->get_param('id');
        $buyer_wallet = $request->get_param('wallet_address');
        $user_id = get_current_user_id();
        
        $listing = get_post($listing_id);
        if (!$listing || $listing->post_type !== 'vortex_listing') {
            return new WP_Error('listing_not_found', 'Listing not found', array('status' => 404));
        }
        
        $price = get_post_meta($listing_id, 'vortex_listing_price', true);
        $status = get_post_meta($listing_id, 'vortex_listing_status', true);
        
        if ($status !== 'active') {
            return new WP_Error('listing_unavailable', 'Listing is no longer available', array('status' => 400));
        }
        
        // Check buyer's TOLA balance
        $buyer_balance = $this->get_user_tola_balance($user_id);
        if ($buyer_balance < $price) {
            return new WP_Error('insufficient_balance', 'Insufficient TOLA balance', array('status' => 400));
        }
        
        // Process purchase transaction
        $transaction_id = $this->process_purchase_transaction($listing_id, $user_id, $price);
        
        // Update listing status
        update_post_meta($listing_id, 'vortex_listing_status', 'sold');
        update_post_meta($listing_id, 'vortex_listing_sold_to', $user_id);
        update_post_meta($listing_id, 'vortex_listing_sold_at', current_time('mysql'));
        
        return new WP_REST_Response(array(
            'success' => true,
            'transaction_id' => $transaction_id,
            'listing_id' => $listing_id,
            'price' => $price,
            'message' => 'Purchase completed successfully',
        ), 200);
    }

    private function get_user_tola_balance($user_id) {
        return get_user_meta($user_id, 'vortex_tola_balance', true) ?: 0;
    }

    private function process_purchase_transaction($listing_id, $buyer_id, $price) {
        $seller_id = get_post_field('post_author', $listing_id);
        
        // Deduct from buyer
        $buyer_balance = $this->get_user_tola_balance($buyer_id);
        update_user_meta($buyer_id, 'vortex_tola_balance', $buyer_balance - $price);
        
        // Add to seller (minus platform fee)
        $platform_fee = $price * 0.05; // 5% platform fee
        $seller_amount = $price - $platform_fee;
        $seller_balance = $this->get_user_tola_balance($seller_id);
        update_user_meta($seller_id, 'vortex_tola_balance', $seller_balance + $seller_amount);
        
        return 'tx_' . uniqid();
    }

    public function check_user_permission($request) {
        return is_user_logged_in();
    }
} 