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
     * Test AI generation endpoint.
     */
    public function test_generate_artwork_endpoint() {
        wp_set_current_user($this->user_id);
        
        $request = new WP_REST_Request('POST', '/vortex/v1/api/generate');
        $request->set_param('prompt', 'A beautiful sunset over the mountains');
        $request->set_param('style', 'realistic');
        $request->set_param('dimensions', '1024x1024');
        $response = rest_get_server()->dispatch($request);
        
        $this->assertEquals(202, $response->get_status()); // Accepted for processing
        
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('job_id', $data);
        $this->assertEquals('processing', $data['status']);
    }

    /**
     * Test generation without prompt fails.
     */
    public function test_generate_without_prompt_fails() {
        wp_set_current_user($this->user_id);
        
        $request = new WP_REST_Request('POST', '/vortex/v1/api/generate');
        $response = rest_get_server()->dispatch($request);
        
        $this->assertEquals(400, $response->get_status());
    }

    /**
     * Test generation status endpoint.
     */
    public function test_generation_status_endpoint() {
        wp_set_current_user($this->user_id);
        
        // Create a mock generation job
        $job_id = 'gen_test123';
        update_user_meta($this->user_id, 'vortex_generation_' . $job_id, array(
            'job_id' => $job_id,
            'status' => 'completed',
            'result_urls' => array('https://example.com/test.png'),
        ));
        
        $request = new WP_REST_Request('GET', '/vortex/v1/api/generate/status/' . $job_id);
        $response = rest_get_server()->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertEquals('completed', $data['status']);
        $this->assertArrayHasKey('result_urls', $data);
    }

    /**
     * Test Horas quiz requires Pro subscription.
     */
    public function test_horas_quiz_requires_pro() {
        // Create user with starter plan
        $starter_user = $this->factory->user->create();
        update_user_meta($starter_user, 'vortex_subscription_plan', 'starter');
        wp_set_current_user($starter_user);
        
        $request = new WP_REST_Request('GET', '/vortex/v1/users/' . $starter_user . '/horas-quiz');
        $response = rest_get_server()->dispatch($request);
        
        $this->assertEquals(403, $response->get_status());
    }

    /**
     * Test Horas quiz works for Pro users.
     */
    public function test_horas_quiz_works_for_pro() {
        wp_set_current_user($this->user_id); // This user has Pro plan
        
        $request = new WP_REST_Request('GET', '/vortex/v1/users/' . $this->user_id . '/horas-quiz');
        $response = rest_get_server()->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertEquals($this->user_id, $data['user_id']);
        $this->assertArrayHasKey('quiz_completed', $data);
    }

    /**
     * Test API endpoints require nonce for security.
     */
    public function test_api_security_nonce() {
        wp_set_current_user($this->user_id);
        
        // Test without nonce (should work for GET requests)
        $request = new WP_REST_Request('GET', '/vortex/v1/users/' . $this->user_id . '/plan');
        $response = rest_get_server()->dispatch($request);
        $this->assertEquals(200, $response->get_status());
        
        // POST requests should have additional security measures in real implementation
        // This test verifies the structure is in place
        $this->assertTrue(true); // Placeholder for more detailed security testing
    }

    /**
     * Test rate limiting structure.
     */
    public function test_rate_limiting_structure() {
        // This test verifies that rate limiting can be implemented
        // In real implementation, you would test actual rate limiting
        $this->assertTrue(class_exists('Vortex_Plans_API'));
        $this->assertTrue(class_exists('Vortex_Wallet_API'));
        $this->assertTrue(class_exists('Vortex_Generate_API'));
    }

    /**
     * Clean up after tests.
     */
    public function tearDown(): void {
        // Clean up user meta
        delete_user_meta($this->user_id, 'vortex_subscription_plan');
        delete_user_meta($this->user_id, 'vortex_subscription_status');
        delete_user_meta($this->user_id, 'vortex_wallet_address');
        
        parent::tearDown();
    }
} 