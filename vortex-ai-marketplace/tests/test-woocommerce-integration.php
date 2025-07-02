<?php
/**
 * Test WooCommerce integration functionality
 *
 * @package Vortex_AI_Marketplace
 */

class Test_WooCommerce_Integration extends WP_UnitTestCase {

    private $user_id;
    private $subscriptions;
    private $wallet;

    public function setUp(): void {
        parent::setUp();
        
        // Create test user
        $this->user_id = $this->factory->user->create(array(
            'user_login' => 'testartist',
            'user_email' => 'test@example.com'
        ));

        // Initialize classes
        $this->subscriptions = new Vortex_Subscriptions();
        $this->wallet = new Vortex_AI_Marketplace_Wallet();

        // Mock WooCommerce
        if (!class_exists('WC_Product_Simple')) {
            $this->markTestSkipped('WooCommerce not available for testing');
        }
    }

    /**
     * Test plan purchase and role assignment
     */
    public function test_plan_purchase_role_assignment() {
        // Create a mock WooCommerce order
        $order = $this->create_mock_order('artist-pro-plan', $this->user_id);
        
        // Simulate order completion
        $this->subscriptions->handle_plan_purchase($order->get_id());
        
        // Check user role was assigned
        $user = get_user_by('ID', $this->user_id);
        $this->assertTrue($user->has_role('artist_pro'));
        
        // Check plan metadata
        $plan_type = get_user_meta($this->user_id, 'vortex_plan', true);
        $this->assertEquals('artist-pro', $plan_type);
        
        $plan_status = get_user_meta($this->user_id, 'vortex_plan_status', true);
        $this->assertEquals('active', $plan_status);
        
        // Check expiration date (should be ~1 month from now)
        $plan_expires = get_user_meta($this->user_id, 'vortex_plan_expires', true);
        $this->assertGreaterThan(time(), $plan_expires);
        $this->assertLessThan(time() + (32 * 24 * 60 * 60), $plan_expires); // Within 32 days
    }

    /**
     * Test token pack purchase and balance credit
     */
    public function test_token_pack_purchase() {
        $initial_balance = $this->wallet->get_balance($this->user_id);
        
        // Create mock token pack order
        $order = $this->create_mock_order('tola-tokens-pack', $this->user_id, 100); // 100 tokens
        
        // Simulate payment completion
        $this->subscriptions->handle_payment_complete($order->get_id());
        
        // Check balance was credited
        $new_balance = $this->wallet->get_balance($this->user_id);
        $this->assertEquals($initial_balance + 100, $new_balance);
        
        // Check transaction record exists
        $transactions = $this->wallet->get_transaction_history($this->user_id, 10);
        $this->assertNotEmpty($transactions);
        $this->assertEquals('credit', $transactions[0]['type']);
        $this->assertEquals(100, $transactions[0]['amount']);
    }

    /**
     * Test subscription expiry handling
     */
    public function test_subscription_expiry() {
        // Set up user with expired subscription
        update_user_meta($this->user_id, 'vortex_plan', 'artist-starter');
        update_user_meta($this->user_id, 'vortex_plan_status', 'active');
        update_user_meta($this->user_id, 'vortex_plan_expires', time() - 86400); // Expired yesterday
        
        $user = get_user_by('ID', $this->user_id);
        $user->add_role('artist_starter');
        
        // Process expired subscriptions
        $this->subscriptions->process_expired_subscriptions();
        
        // Check role was removed
        $user = get_user_by('ID', $this->user_id);
        $this->assertFalse($user->has_role('artist_starter'));
        
        // Check status was updated
        $plan_status = get_user_meta($this->user_id, 'vortex_plan_status', true);
        $this->assertEquals('expired', $plan_status);
    }

    /**
     * Test get user plan functionality
     */
    public function test_get_user_plan() {
        // Test user with no plan
        $plan = $this->subscriptions->get_user_plan($this->user_id);
        $this->assertFalse($plan);
        
        // Set up active plan
        update_user_meta($this->user_id, 'vortex_plan', 'artist-pro');
        update_user_meta($this->user_id, 'vortex_plan_status', 'active');
        update_user_meta($this->user_id, 'vortex_plan_expires', time() + 86400);
        
        $plan = $this->subscriptions->get_user_plan($this->user_id);
        $this->assertIsArray($plan);
        $this->assertEquals('artist-pro', $plan['type']);
        $this->assertEquals('active', $plan['status']);
        $this->assertTrue($plan['is_active']);
    }

    /**
     * Test plan features retrieval
     */
    public function test_get_plan_features() {
        $starter_features = $this->subscriptions->get_plan_features('artist-starter');
        $this->assertIsArray($starter_features);
        $this->assertEquals('Artist Starter', $starter_features['name']);
        $this->assertEquals(29, $starter_features['price']);
        $this->assertEquals(50, $starter_features['generation_limit']);
        
        $pro_features = $this->subscriptions->get_plan_features('artist-pro');
        $this->assertEquals('Artist Pro', $pro_features['name']);
        $this->assertEquals(59, $pro_features['price']);
        $this->assertEquals(200, $pro_features['generation_limit']);
        
        $studio_features = $this->subscriptions->get_plan_features('artist-studio');
        $this->assertEquals('Artist Studio', $studio_features['name']);
        $this->assertEquals(99, $studio_features['price']);
        $this->assertEquals(-1, $studio_features['generation_limit']); // Unlimited
    }

    /**
     * Test role upgrade scenario
     */
    public function test_role_upgrade() {
        // Start with starter plan
        $user = get_user_by('ID', $this->user_id);
        $user->add_role('artist_starter');
        update_user_meta($this->user_id, 'vortex_plan', 'artist-starter');
        
        // Upgrade to pro
        $this->subscriptions->assign_plan_to_user($this->user_id, 'artist-pro');
        
        // Check old role was removed and new role added
        $user = get_user_by('ID', $this->user_id);
        $this->assertFalse($user->has_role('artist_starter'));
        $this->assertTrue($user->has_role('artist_pro'));
        
        // Check plan metadata was updated
        $plan_type = get_user_meta($this->user_id, 'vortex_plan', true);
        $this->assertEquals('artist-pro', $plan_type);
    }

    /**
     * Helper method to create mock WooCommerce order
     */
    private function create_mock_order($product_slug, $user_id, $quantity = 1) {
        // Create mock product
        $product = new WC_Product_Simple();
        $product->set_name('Test Product');
        $product->set_slug($product_slug);
        $product->set_regular_price(29.00);
        $product->set_virtual(true);
        $product->save();
        
        // Set product metadata based on slug
        if (strpos($product_slug, 'plan') !== false) {
            update_post_meta($product->get_id(), '_vortex_product_type', 'subscription_plan');
            update_post_meta($product->get_id(), '_vortex_plan_type', str_replace('-plan', '', $product_slug));
        } elseif ($product_slug === 'tola-tokens-pack') {
            update_post_meta($product->get_id(), '_vortex_product_type', 'token_pack');
        }
        
        // Create mock order
        $order = new WC_Order();
        $order->set_status('completed');
        $order->set_customer_id($user_id);
        
        // Add order item
        $item = new WC_Order_Item_Product();
        $item->set_product($product);
        $item->set_quantity($quantity);
        $order->add_item($item);
        
        $order->save();
        
        return $order;
    }

    public function tearDown(): void {
        // Clean up test data
        wp_delete_user($this->user_id);
        parent::tearDown();
    }
} 