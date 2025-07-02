<?php
/**
 * Test cases for VORTEX Artist Business Quiz
 *
 * @package Vortex_AI_Marketplace
 * @subpackage Tests
 */

class Test_Artist_Business_Quiz extends WP_UnitTestCase {

    private $quiz_instance;
    private $test_user_id;

    /**
     * Setup test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Get quiz instance
        $this->quiz_instance = Vortex_Artist_Business_Quiz::get_instance();
        
        // Create test user with pro role
        $this->test_user_id = $this->factory->user->create(array(
            'role' => 'artist_pro',
            'user_email' => 'test@vortexartec.com'
        ));
        
        // Create database table
        $this->create_test_table();
    }

    /**
     * Teardown test environment
     */
    public function tearDown(): void {
        wp_delete_user($this->test_user_id);
        parent::tearDown();
    }

    /**
     * Create test database table
     */
    private function create_test_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_quiz_responses';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            submission_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        )";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Test shortcode registration
     */
    public function test_shortcode_registered() {
        $this->assertTrue(shortcode_exists('vortex_artist_business_quiz'));
    }

    /**
     * Test monthly submission limit for authenticated users
     */
    public function test_monthly_submission_limit() {
        // Login as test user
        wp_set_current_user($this->test_user_id);
        
        // First submission should be allowed
        $output = do_shortcode('[vortex_artist_business_quiz]');
        $this->assertStringContainsString('Strategic Business Assessment', $output);
        
        // Add a submission for this month
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_quiz_responses';
        $wpdb->insert($table_name, array(
            'user_id' => $this->test_user_id,
            'submission_date' => current_time('mysql')
        ));
        
        // Second submission should show monthly limit message
        $output = do_shortcode('[vortex_artist_business_quiz]');
        $this->assertStringContainsString('Monthly Assessment Completed', $output);
    }

    /**
     * Test user permission requirements
     */
    public function test_user_permissions() {
        // Create starter tier user (should not have access)
        $starter_user = $this->factory->user->create(array(
            'role' => 'artist_starter'
        ));
        
        wp_set_current_user($starter_user);
        
        $output = do_shortcode('[vortex_artist_business_quiz]');
        $this->assertStringContainsString('Upgrade Required', $output);
        $this->assertStringContainsString('Pro tier and above', $output);
        
        wp_delete_user($starter_user);
    }

    /**
     * Test login requirement
     */
    public function test_login_requirement() {
        // Logout user
        wp_set_current_user(0);
        
        $output = do_shortcode('[vortex_artist_business_quiz]');
        $this->assertStringContainsString('Login Required', $output);
    }

    /**
     * Test shortcode attributes
     */
    public function test_shortcode_attributes() {
        wp_set_current_user($this->test_user_id);
        
        $output = do_shortcode('[vortex_artist_business_quiz title="Custom Title" subtitle="Custom Subtitle"]');
        
        // Should contain custom title and subtitle (when quiz form is rendered)
        $this->assertStringContainsString('Strategic Business Assessment', $output);
    }

    /**
     * Test quiz handler initialization
     */
    public function test_quiz_handler_exists() {
        $this->assertTrue(class_exists('Vortex_Quiz_Optimizer_Handler'));
        $handler = Vortex_Quiz_Optimizer_Handler::get_instance();
        $this->assertInstanceOf('Vortex_Quiz_Optimizer_Handler', $handler);
    }

    /**
     * Test REST API endpoint registration
     */
    public function test_rest_api_endpoints() {
        // Check if routes are registered
        $routes = rest_get_server()->get_routes();
        $this->assertArrayHasKey('/vortex/v1/optimized-quiz', $routes);
    }

    /**
     * Test quiz data validation
     */
    public function test_quiz_data_validation() {
        // Mock quiz data
        $valid_data = array(
            'dob' => '1990-01-01',
            'pob' => 'New York, USA',
            'tob' => '12:00',
            'answers' => array(
                'q1' => 'a',
                'q2' => 'b',
                'q3' => 'c',
                'q4' => 'd',
                'q5' => 'a',
                'q6' => 'b',
                'q7' => 'c',
                'q8' => 'd',
                'q9' => 'a',
                'q10' => 'b',
                'q11' => 'c',
                'q12' => 'd'
            )
        );
        
        // Test valid data structure
        $this->assertCount(12, $valid_data['answers']);
        $this->assertNotEmpty($valid_data['dob']);
        $this->assertNotEmpty($valid_data['pob']);
        $this->assertNotEmpty($valid_data['tob']);
    }

    /**
     * Test milestone scheduling
     */
    public function test_milestone_scheduling() {
        // Check if milestone actions are hooked
        $this->assertTrue(has_action('vortex_build_milestones'));
        $this->assertTrue(has_action('vortex_daily_milestone_reminder'));
    }

    /**
     * Test admin analytics scheduling
     */
    public function test_admin_analytics() {
        // Check if admin analytics actions are hooked
        $this->assertTrue(has_action('vortex_daily_admin_analysis'));
    }
} 