<?php
/**
 * Test REST API endpoints for the Artist Journey implementation.
 *
 * @since      2.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/tests
 */

class Test_VORTEX_API_Endpoints extends WP_UnitTestCase {

    private $user_id;
    private $admin_id;
    private $api;

    /**
     * Set up test fixtures.
     */
    public function setUp(): void {
        parent::setUp();
        
        // Create test users
        $this->user_id = $this->factory->user->create(array(
            'role' => 'subscriber',
            'user_login' => 'testartist',
            'user_email' => 'test@example.com',
        ));
        
        $this->admin_id = $this->factory->user->create(array(
            'role' => 'administrator',
            'user_login' => 'testadmin',
            'user_email' => 'admin@example.com',
        ));

        // Set up subscription plan for test user
        update_user_meta($this->user_id, 'vortex_subscription_plan', 'pro');
        update_user_meta($this->user_id, 'vortex_subscription_status', 'active');

        // Initialize API
        $this->api = new Vortex_AI_API_Enhanced();

        // Set up user with active plan and tokens
        update_user_meta($this->user_id, 'vortex_plan', 'artist-pro');
        update_user_meta($this->user_id, 'vortex_plan_status', 'active');
        update_user_meta($this->user_id, 'vortex_plan_expires', time() + 86400);
        update_user_meta($this->user_id, 'vortex_tola_balance', 100);
    }

    /**
     * Test health check endpoint.
     */
    public function test_health_check_endpoint() {
        $request = new WP_REST_Request('GET', '/vortex/v1/health');
        $response = rest_get_server()->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertArrayHasKey('status', $data);
        $this->assertEquals('healthy', $data['status']);
        $this->assertArrayHasKey('agents', $data);
        $this->assertArrayHasKey('HURAII', $data['agents']);
    }

    /**
     * Test plans endpoint returns correct data.
     */
    public function test_plans_endpoint() {
        $request = new WP_REST_Request('GET', '/vortex/v1/plans');
        $response = rest_get_server()->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertArrayHasKey('plans', $data);
        $this->assertCount(3, $data['plans']); // starter, pro, studio
        
        $plan_names = array_column($data['plans'], 'id');
        $this->assertContains('starter', $plan_names);
        $this->assertContains('pro', $plan_names);
        $this->assertContains('studio', $plan_names);
    }

    /**
     * Test specific plan details endpoint.
     */
    public function test_plan_details_endpoint() {
        $request = new WP_REST_Request('GET', '/vortex/v1/plans/pro');
        $response = rest_get_server()->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertArrayHasKey('plan', $data);
        $this->assertEquals('pro', $data['plan']['id']);
        $this->assertEquals(39.99, $data['plan']['price']);
    }

    /**
     * Test invalid plan returns 404.
     */
    public function test_invalid_plan_returns_404() {
        $request = new WP_REST_Request('GET', '/vortex/v1/plans/invalid');
        $response = rest_get_server()->dispatch($request);
        
        $this->assertEquals(404, $response->get_status());
    }

    /**
     * Test user plan endpoint requires authentication.
     */
    public function test_user_plan_requires_auth() {
        $request = new WP_REST_Request('GET', '/vortex/v1/users/' . $this->user_id . '/plan');
        $response = rest_get_server()->dispatch($request);
        
        $this->assertEquals(401, $response->get_status());
    }

    /**
     * Test authenticated user can access their plan.
     */
    public function test_authenticated_user_can_access_plan() {
        wp_set_current_user($this->user_id);
        
        $request = new WP_REST_Request('GET', '/vortex/v1/users/' . $this->user_id . '/plan');
        $response = rest_get_server()->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertEquals($this->user_id, $data['user_id']);
        $this->assertEquals('pro', $data['current_plan']);
    }

    /**
     * Test user cannot access another user's plan.
     */
    public function test_user_cannot_access_other_user_plan() {
        $other_user = $this->factory->user->create();
        wp_set_current_user($this->user_id);
        
        $request = new WP_REST_Request('GET', '/vortex/v1/users/' . $other_user . '/plan');
        $response = rest_get_server()->dispatch($request);
        
        $this->assertEquals(403, $response->get_status());
    }

    /**
     * Test admin can access any user's plan.
     */
    public function test_admin_can_access_any_user_plan() {
        wp_set_current_user($this->admin_id);
        
        $request = new WP_REST_Request('GET', '/vortex/v1/users/' . $this->user_id . '/plan');
        $response = rest_get_server()->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
    }

    /**
     * Test role quiz endpoint.
     */
    public function test_role_quiz_endpoint() {
        wp_set_current_user($this->user_id);
        
        // Test GET (empty quiz)
        $request = new WP_REST_Request('GET', '/vortex/v1/users/' . $this->user_id . '/role-quiz');
        $response = rest_get_server()->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertEquals($this->user_id, $data['user_id']);
        $this->assertFalse($data['quiz_completed']);
        
        // Test POST (submit quiz)
        $quiz_answers = array(
            array('question_0' => 'artist', 'role_preference' => 'artist'),
        );
        
        $request = new WP_REST_Request('POST', '/vortex/v1/users/' . $this->user_id . '/role-quiz');
        $request->set_param('answers', $quiz_answers);
        $response = rest_get_server()->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('recommended_role', $data);
    }

    /**
     * Test terms of service acceptance.
     */
    public function test_accept_tos_endpoint() {
        wp_set_current_user($this->user_id);
        
        $request = new WP_REST_Request('POST', '/vortex/v1/users/' . $this->user_id . '/accept-tos');
        $request->set_param('tos_version', '1.0');
        $response = rest_get_server()->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertEquals('1.0', $data['tos_version']);
        
        // Verify user meta was updated
        $tos_accepted = get_user_meta($this->user_id, 'vortex_tos_accepted', true);
        $this->assertEquals(true, $tos_accepted);
    }

    /**
     * Test wallet connection endpoint.
     */
    public function test_wallet_connect_endpoint() {
        wp_set_current_user($this->user_id);
        
        $wallet_address = 'HN7cABqLq46Es1jh92dQQi5jipxu48PfAAMUBY4ik5VY';
        
        $request = new WP_REST_Request('POST', '/vortex/v1/wallet/connect');
        $request->set_param('wallet_address', $wallet_address);
        $request->set_param('wallet_type', 'phantom');
        $response = rest_get_server()->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertEquals($wallet_address, $data['wallet_address']);
        
        // Verify user meta was updated
        $stored_address = get_user_meta($this->user_id, 'vortex_wallet_address', true);
        $this->assertEquals($wallet_address, $stored_address);
    }

    /**
     * Test wallet balance endpoint.
     */
    public function test_wallet_balance_endpoint() {
        wp_set_current_user($this->user_id);
        
        // First connect a wallet
        update_user_meta($this->user_id, 'vortex_wallet_address', 'HN7cABqLq46Es1jh92dQQi5jipxu48PfAAMUBY4ik5VY');
        
        $request = new WP_REST_Request('GET', '/vortex/v1/wallet/balance');
        $response = rest_get_server()->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('balances', $data);
        $this->assertArrayHasKey('TOLA', $data['balances']);
    }

    /**
     * Test wallet balance without connected wallet.
     */
    public function test_wallet_balance_without_wallet() {
        wp_set_current_user($this->user_id);
        
        $request = new WP_REST_Request('GET', '/vortex/v1/wallet/balance');
        $response = rest_get_server()->dispatch($request);
        
        $this->assertEquals(400, $response->get_status());
    }

    /**
     * Test Chloe inspiration endpoint.
     */
    public function test_chloe_inspiration_endpoint() {
        wp_set_current_user($this->user_id);
        
        $request = new WP_REST_Request('GET', '/vortex/v1/api/chloe/inspiration');
        $request->set_param('style', 'abstract');
        $request->set_param('mood', 'energetic');
        $response = rest_get_server()->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('inspirations', $data);
        $this->assertGreaterThan(0, count($data['inspirations']));
    }

    /**
     * Test Chloe collector matching endpoint.
     */
    public function test_chloe_match_endpoint() {
        wp_set_current_user($this->user_id);
        
        $request = new WP_REST_Request('POST', '/vortex/v1/api/chloe/match');
        $request->set_param('artwork_style', 'abstract');
        $request->set_param('price_range', '50-100');
        $response = rest_get_server()->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('matches', $data);
        $this->assertArrayHasKey('recommendations', $data);
    }

    /**
     * Test AI generation endpoint with valid request
     */
    public function test_ai_generation_endpoint_success() {
        wp_set_current_user($this->user_id);
        
        $request = new WP_REST_Request('POST', '/vortex/v1/generate');
        $request->set_param('prompt', 'A beautiful landscape with mountains');
        $request->set_param('style', 'realistic');
        
        $response = $this->api->handle_ai_generation($request);
        
        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('image_url', $data);
        $this->assertArrayHasKey('generation_id', $data);
        $this->assertArrayHasKey('remaining_tokens', $data);
        
        // Check token was deducted
        $new_balance = get_user_meta($this->user_id, 'vortex_tola_balance', true);
        $this->assertEquals(99, $new_balance); // 100 - 1 for generation
    }

    /**
     * Test AI generation endpoint with insufficient tokens
     */
    public function test_ai_generation_insufficient_tokens() {
        wp_set_current_user($this->user_id);
        
        // Set low token balance
        update_user_meta($this->user_id, 'vortex_tola_balance', 0);
        
        $request = new WP_REST_Request('POST', '/vortex/v1/generate');
        $request->set_param('prompt', 'A beautiful landscape');
        
        $response = $this->api->handle_ai_generation($request);
        
        $this->assertEquals(403, $response->get_status());
        
        $data = $response->get_data();
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Insufficient TOLA tokens', $data['error']);
    }

    /**
     * Test AI generation endpoint without subscription
     */
    public function test_ai_generation_no_subscription() {
        wp_set_current_user($this->user_id);
        
        // Remove subscription plan
        delete_user_meta($this->user_id, 'vortex_plan');
        
        $request = new WP_REST_Request('POST', '/vortex/v1/generate');
        $request->set_param('prompt', 'A beautiful landscape');
        
        $response = $this->api->handle_ai_generation($request);
        
        $this->assertEquals(403, $response->get_status());
        
        $data = $response->get_data();
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('No active subscription', $data['error']);
    }

    /**
     * Test AI generation endpoint with invalid prompt
     */
    public function test_ai_generation_invalid_prompt() {
        wp_set_current_user($this->user_id);
        
        $request = new WP_REST_Request('POST', '/vortex/v1/generate');
        $request->set_param('prompt', 'ab'); // Too short
        
        $response = $this->api->handle_ai_generation($request);
        
        $this->assertEquals(400, $response->get_status());
        
        $data = $response->get_data();
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('at least 3 characters', $data['error']);
    }

    /**
     * Test seed upload endpoint success
     */
    public function test_seed_upload_endpoint_success() {
        wp_set_current_user($this->user_id);
        
        // Mock file upload
        $_FILES['file'] = array(
            'name' => 'test-image.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => $this->create_test_image(),
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        );
        
        $request = new WP_REST_Request('POST', '/vortex/v1/upload-seed');
        
        $response = $this->api->handle_seed_upload($request);
        
        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('filename', $data);
        $this->assertArrayHasKey('url', $data);
        
        // Clean up
        unlink($_FILES['file']['tmp_name']);
        unset($_FILES['file']);
    }

    /**
     * Test seed upload endpoint without file
     */
    public function test_seed_upload_no_file() {
        wp_set_current_user($this->user_id);
        
        $request = new WP_REST_Request('POST', '/vortex/v1/upload-seed');
        
        $response = $this->api->handle_seed_upload($request);
        
        $this->assertEquals(400, $response->get_status());
        
        $data = $response->get_data();
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('No file uploaded', $data['error']);
    }

    /**
     * Test get user balance endpoint
     */
    public function test_get_user_balance_endpoint() {
        wp_set_current_user($this->user_id);
        
        $request = new WP_REST_Request('GET', '/vortex/v1/balance');
        
        $response = $this->api->get_user_balance($request);
        
        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertEquals(100, $data['balance']);
        $this->assertEquals('100 TOLA', $data['formatted']);
    }

    /**
     * Test get seed gallery endpoint
     */
    public function test_get_seed_gallery_endpoint() {
        wp_set_current_user($this->user_id);
        
        // Create some test seed artworks in database
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_seed_artworks';
        
        for ($i = 1; $i <= 3; $i++) {
            $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $this->user_id,
                    'filename' => "test-seed-{$i}.jpg",
                    'original_filename' => "original-{$i}.jpg",
                    's3_key' => "users/{$this->user_id}/seed/test-seed-{$i}.jpg",
                    's3_url' => "https://mock-s3.com/test-seed-{$i}.jpg",
                    'file_size' => 1024 * $i,
                    'file_type' => 'image/jpeg',
                    'status' => 'active'
                ),
                array('%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
            );
        }
        
        $request = new WP_REST_Request('GET', '/vortex/v1/seed-gallery');
        
        $response = $this->api->get_seed_gallery($request);
        
        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertEquals(3, $data['count']);
        $this->assertCount(3, $data['artworks']);
        
        // Check artwork structure
        $artwork = $data['artworks'][0];
        $this->assertArrayHasKey('filename', $artwork);
        $this->assertArrayHasKey('s3_url', $artwork);
        $this->assertArrayHasKey('file_size', $artwork);
    }

    /**
     * Test unauthorized access to endpoints
     */
    public function test_unauthorized_access() {
        // Test without being logged in
        $request = new WP_REST_Request('POST', '/vortex/v1/generate');
        $request->set_param('prompt', 'Test prompt');
        
        $permission_check = $this->api->check_user_permissions();
        $this->assertFalse($permission_check);
    }

    /**
     * Test generation statistics update
     */
    public function test_generation_statistics_update() {
        wp_set_current_user($this->user_id);
        
        $initial_total = get_user_meta($this->user_id, 'vortex_total_generations', true) ?: 0;
        $current_month = date('Y-m');
        $initial_monthly = get_user_meta($this->user_id, "vortex_generations_{$current_month}", true) ?: 0;
        
        $request = new WP_REST_Request('POST', '/vortex/v1/generate');
        $request->set_param('prompt', 'Test generation for statistics');
        
        $response = $this->api->handle_ai_generation($request);
        
        $this->assertEquals(200, $response->get_status());
        
        // Check statistics were updated
        $new_total = get_user_meta($this->user_id, 'vortex_total_generations', true);
        $new_monthly = get_user_meta($this->user_id, "vortex_generations_{$current_month}", true);
        
        $this->assertEquals($initial_total + 1, $new_total);
        $this->assertEquals($initial_monthly + 1, $new_monthly);
    }

    /**
     * Test first generation milestone
     */
    public function test_first_generation_milestone() {
        wp_set_current_user($this->user_id);
        
        // Ensure this is the first generation
        delete_user_meta($this->user_id, 'vortex_total_generations');
        delete_user_meta($this->user_id, 'vortex_completed_milestones');
        
        $initial_balance = get_user_meta($this->user_id, 'vortex_tola_balance', true);
        
        $request = new WP_REST_Request('POST', '/vortex/v1/generate');
        $request->set_param('prompt', 'First generation test');
        
        $response = $this->api->handle_ai_generation($request);
        
        $this->assertEquals(200, $response->get_status());
        
        // Check milestone was awarded
        $milestones = get_user_meta($this->user_id, 'vortex_completed_milestones', true);
        $this->assertContains('first_generation', $milestones);
        
        // Check bonus tokens were awarded (15 TOLA bonus - 1 TOLA generation cost)
        $new_balance = get_user_meta($this->user_id, 'vortex_tola_balance', true);
        $this->assertEquals($initial_balance + 14, $new_balance); // +15 bonus -1 generation cost
    }

    /**
     * Test monthly generation limits
     */
    public function test_monthly_generation_limits() {
        wp_set_current_user($this->user_id);
        
        // Set user to starter plan with 50 generation limit
        update_user_meta($this->user_id, 'vortex_plan', 'artist-starter');
        
        $current_month = date('Y-m');
        update_user_meta($this->user_id, "vortex_generations_{$current_month}", 50); // At limit
        
        $request = new WP_REST_Request('POST', '/vortex/v1/generate');
        $request->set_param('prompt', 'Over limit test');
        
        $response = $this->api->handle_ai_generation($request);
        
        $this->assertEquals(403, $response->get_status());
        
        $data = $response->get_data();
        $this->assertStringContainsString('Monthly generation limit reached', $data['error']);
    }

    /**
     * Create a test image file
     */
    private function create_test_image() {
        $temp_file = tempnam(sys_get_temp_dir(), 'vortex_test_');
        
        // Create a simple 1x1 pixel image
        $image = imagecreate(1, 1);
        imagecolorallocate($image, 255, 255, 255);
        imagejpeg($image, $temp_file);
        imagedestroy($image);
        
        return $temp_file;
    }

    /**
     * Clean up after tests.
     */
    public function tearDown(): void {
        // Clean up user meta
        delete_user_meta($this->user_id, 'vortex_subscription_plan');
        delete_user_meta($this->user_id, 'vortex_subscription_status');
        delete_user_meta($this->user_id, 'vortex_wallet_address');
        
        // Clean up test data
        wp_delete_user($this->user_id);
        wp_delete_user($this->admin_id);
        
        // Clean up database tables
        global $wpdb;
        $tables = array(
            $wpdb->prefix . 'vortex_seed_artworks',
            $wpdb->prefix . 'vortex_token_transactions',
            $wpdb->prefix . 'vortex_events'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DELETE FROM $table WHERE user_id = {$this->user_id}");
        }
        
        parent::tearDown();
    }
} 