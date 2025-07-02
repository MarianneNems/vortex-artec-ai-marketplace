<?php
/**
 * The WooCommerce subscription management class.
 *
 * @since      3.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_Subscriptions {

    /**
     * Initialize the class and set its properties.
     *
     * @since    3.0.0
     */
    public function __construct() {
        add_action('init', array($this, 'create_subscription_products'));
        add_action('woocommerce_order_status_completed', array($this, 'handle_plan_purchase'));
        add_action('woocommerce_payment_complete', array($this, 'handle_payment_complete'));
        add_action('wp_loaded', array($this, 'check_subscription_expiry'));
    }

    /**
     * Create WooCommerce subscription products
     *
     * @since    3.0.0
     */
    public function create_subscription_products() {
        if (!class_exists('WooCommerce')) {
            return;
        }

        $this->create_product_if_not_exists('Artist Starter Plan', 29.00, 'artist-starter-plan');
        $this->create_product_if_not_exists('Artist Pro Plan', 59.00, 'artist-pro-plan');
        $this->create_product_if_not_exists('Artist Studio Plan', 99.00, 'artist-studio-plan');
        $this->create_product_if_not_exists('Tola Tokens Pack', 0, 'tola-tokens-pack');
    }

    /**
     * Create a WooCommerce product if it doesn't exist
     *
     * @since    3.0.0
     * @param    string    $name        Product name
     * @param    float     $price       Product price
     * @param    string    $slug        Product slug
     */
    private function create_product_if_not_exists($name, $price, $slug) {
        $existing_product = get_page_by_path($slug, OBJECT, 'product');
        
        if (!$existing_product) {
            $product = new WC_Product_Simple();
            $product->set_name($name);
            $product->set_slug($slug);
            $product->set_regular_price($price);
            $product->set_virtual(true);
            $product->set_downloadable(false);
            $product->set_catalog_visibility('visible');
            $product->set_status('publish');
            
            if ($slug === 'tola-tokens-pack') {
                $product->set_description('Purchase TOLA tokens for AI art generation and marketplace transactions.');
                update_post_meta($product->get_id(), '_vortex_product_type', 'token_pack');
            } else {
                $product->set_description('Monthly subscription plan for artists with AI tools and marketplace access.');
                update_post_meta($product->get_id(), '_vortex_product_type', 'subscription_plan');
                update_post_meta($product->get_id(), '_vortex_plan_type', str_replace('-plan', '', $slug));
            }
            
            $product->save();
        }
    }

    /**
     * Handle plan purchase completion
     *
     * @since    3.0.0
     * @param    int    $order_id    Order ID
     */
    public function handle_plan_purchase($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $product_type = get_post_meta($product_id, '_vortex_product_type', true);
            
            if ($product_type === 'subscription_plan') {
                $plan_type = get_post_meta($product_id, '_vortex_plan_type', true);
                $user_id = $order->get_user_id();
                
                if ($user_id && $plan_type) {
                    $this->assign_plan_to_user($user_id, $plan_type);
                }
            }
        }
    }

    /**
     * Handle payment completion for token purchases
     *
     * @since    3.0.0
     * @param    int    $order_id    Order ID
     */
    public function handle_payment_complete($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $product_type = get_post_meta($product_id, '_vortex_product_type', true);
            
            if ($product_type === 'token_pack') {
                $quantity = $item->get_quantity();
                $user_id = $order->get_user_id();
                
                if ($user_id) {
                    $this->credit_tokens_to_user($user_id, $quantity);
                }
            }
        }
    }

    /**
     * Assign a subscription plan to a user
     *
     * @since    3.0.0
     * @param    int       $user_id     User ID
     * @param    string    $plan_type   Plan type (artist-starter, artist-pro, artist-studio)
     */
    public function assign_plan_to_user($user_id, $plan_type) {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return;
        }

        // Remove existing artist roles
        $existing_roles = array('artist_starter', 'artist_pro', 'artist_studio');
        foreach ($existing_roles as $role) {
            $user->remove_role($role);
        }

        // Add new role based on plan
        $new_role = str_replace('-', '_', $plan_type);
        $user->add_role($new_role);

        // Set plan metadata
        update_user_meta($user_id, 'vortex_plan', $plan_type);
        update_user_meta($user_id, 'vortex_plan_expires', strtotime('+1 month'));
        update_user_meta($user_id, 'vortex_plan_status', 'active');

        // Trigger plan activation hook
        do_action('vortex_plan_activated', $user_id, $plan_type);

        // Log the plan assignment
        error_log("VORTEX: Plan {$plan_type} assigned to user {$user_id}");
    }

    /**
     * Credit TOLA tokens to a user
     *
     * @since    3.0.0
     * @param    int    $user_id    User ID
     * @param    int    $amount     Amount of tokens to credit
     */
    public function credit_tokens_to_user($user_id, $amount) {
        $current_balance = (int) get_user_meta($user_id, 'vortex_tola_balance', true);
        $new_balance = $current_balance + $amount;
        
        update_user_meta($user_id, 'vortex_tola_balance', $new_balance);

        // Create blockchain transaction record
        $wallet_class = $this->get_wallet_handler();
        if ($wallet_class && method_exists($wallet_class, 'credit_tokens')) {
            $wallet_class->credit_tokens($user_id, $amount);
        }

        // Log the transaction
        $this->log_token_transaction($user_id, $amount, 'credit', 'woocommerce_purchase');

        // Trigger token credit hook
        do_action('vortex_tokens_credited', $user_id, $amount);

        error_log("VORTEX: {$amount} TOLA tokens credited to user {$user_id}. New balance: {$new_balance}");
    }

    /**
     * Check subscription expiry daily
     *
     * @since    3.0.0
     */
    public function check_subscription_expiry() {
        if (!wp_next_scheduled('vortex_check_subscription_expiry')) {
            wp_schedule_event(time(), 'daily', 'vortex_check_subscription_expiry');
        }
        
        add_action('vortex_check_subscription_expiry', array($this, 'process_expired_subscriptions'));
    }

    /**
     * Process expired subscriptions
     *
     * @since    3.0.0
     */
    public function process_expired_subscriptions() {
        global $wpdb;
        
        $expired_users = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} 
                 WHERE meta_key = 'vortex_plan_expires' 
                 AND meta_value < %d 
                 AND meta_value != ''",
                time()
            )
        );

        foreach ($expired_users as $user_data) {
            $this->expire_user_subscription($user_data->user_id);
        }
    }

    /**
     * Expire a user's subscription
     *
     * @since    3.0.0
     * @param    int    $user_id    User ID
     */
    private function expire_user_subscription($user_id) {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return;
        }

        // Remove artist roles
        $artist_roles = array('artist_starter', 'artist_pro', 'artist_studio');
        foreach ($artist_roles as $role) {
            $user->remove_role($role);
        }

        // Update plan status
        update_user_meta($user_id, 'vortex_plan_status', 'expired');
        
        // Trigger expiration hook
        do_action('vortex_subscription_expired', $user_id);

        error_log("VORTEX: Subscription expired for user {$user_id}");
    }

    /**
     * Get user's current plan
     *
     * @since    3.0.0
     * @param    int    $user_id    User ID
     * @return   array|false        Plan data or false
     */
    public function get_user_plan($user_id) {
        $plan_type = get_user_meta($user_id, 'vortex_plan', true);
        $plan_expires = get_user_meta($user_id, 'vortex_plan_expires', true);
        $plan_status = get_user_meta($user_id, 'vortex_plan_status', true);

        if (!$plan_type) {
            return false;
        }

        return array(
            'type' => $plan_type,
            'expires' => $plan_expires,
            'status' => $plan_status,
            'is_active' => $plan_status === 'active' && $plan_expires > time()
        );
    }

    /**
     * Get wallet handler instance
     *
     * @since    3.0.0
     * @return   object|null    Wallet handler instance
     */
    private function get_wallet_handler() {
        if (class_exists('Vortex_AI_Marketplace_Wallet')) {
            return new Vortex_AI_Marketplace_Wallet();
        }
        return null;
    }

    /**
     * Log token transaction
     *
     * @since    3.0.0
     * @param    int       $user_id       User ID
     * @param    int       $amount        Token amount
     * @param    string    $type          Transaction type (credit/debit)
     * @param    string    $source        Transaction source
     */
    private function log_token_transaction($user_id, $amount, $type, $source) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_token_transactions';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'amount' => $amount,
                'type' => $type,
                'source' => $source,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
    }

    /**
     * Get plan features
     *
     * @since    3.0.0
     * @param    string    $plan_type    Plan type
     * @return   array                   Plan features
     */
    public function get_plan_features($plan_type) {
        $features = array(
            'artist-starter' => array(
                'name' => 'Artist Starter',
                'price' => 29,
                'features' => array(
                    'Basic AI art generation',
                    'Upload seed artwork',
                    'Basic marketplace access',
                    'Community support'
                ),
                'generation_limit' => 50,
                'storage_limit' => '1GB'
            ),
            'artist-pro' => array(
                'name' => 'Artist Pro',
                'price' => 59,
                'features' => array(
                    'Advanced AI art generation',
                    'Upload seed artwork',
                    'Full marketplace access',
                    'Advanced tools',
                    'Priority support',
                    'HORACE business quiz access'
                ),
                'generation_limit' => 200,
                'storage_limit' => '10GB'
            ),
            'artist-studio' => array(
                'name' => 'Artist Studio',
                'price' => 99,
                'features' => array(
                    'Unlimited AI art generation',
                    'Upload seed artwork',
                    'Full marketplace access',
                    'Studio features',
                    'Priority support',
                    'All AI agents access',
                    'Advanced analytics'
                ),
                'generation_limit' => -1, // Unlimited
                'storage_limit' => '100GB'
            )
        );

        return isset($features[$plan_type]) ? $features[$plan_type] : array();
    }
} 