<?php
/**
 * Main API class for VORTEX AI Marketplace REST endpoints.
 *
 * @since      2.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */
class Vortex_AI_API {

    /**
     * The namespace for REST API routes.
     *
     * @since    2.0.0
     * @access   private
     * @var      string    $namespace
     */
    private $namespace;

    /**
     * Initialize the class and set its properties.
     *
     * @since    2.0.0
     */
    public function __construct() {
        $this->namespace = 'vortex/v1';
    }

    /**
     * Register REST API routes.
     *
     * @since    2.0.0
     */
    public function register_routes() {
        // Base endpoint for API health check
        register_rest_route($this->namespace, '/health', array(
            'methods' => 'GET',
            'callback' => array($this, 'health_check'),
            'permission_callback' => '__return_true',
        ));

        // User management endpoints
        register_rest_route($this->namespace, '/users/(?P<id>\d+)/plan', array(
            'methods' => array('GET', 'POST'),
            'callback' => array($this, 'handle_user_plan'),
            'permission_callback' => array($this, 'check_user_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));

        register_rest_route($this->namespace, '/users/(?P<id>\d+)/role-quiz', array(
            'methods' => array('GET', 'POST'),
            'callback' => array($this, 'handle_role_quiz'),
            'permission_callback' => array($this, 'check_user_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));

        register_rest_route($this->namespace, '/users/(?P<id>\d+)/accept-tos', array(
            'methods' => 'POST',
            'callback' => array($this, 'accept_terms_of_service'),
            'permission_callback' => array($this, 'check_user_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));

        register_rest_route($this->namespace, '/users/(?P<id>\d+)/seed-art/upload', array(
            'methods' => 'POST',
            'callback' => array($this, 'upload_seed_art'),
            'permission_callback' => array($this, 'check_user_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));

        register_rest_route($this->namespace, '/users/(?P<id>\d+)/horas-quiz', array(
            'methods' => array('GET', 'POST'),
            'callback' => array($this, 'handle_horas_quiz'),
            'permission_callback' => array($this, 'check_pro_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
    }

    /**
     * Health check endpoint.
     *
     * @since    2.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function health_check($request) {
        return new WP_REST_Response(array(
            'status' => 'healthy',
            'version' => VORTEX_AI_MARKETPLACE_VERSION,
            'timestamp' => current_time('mysql'),
            'agents' => array(
                'HURAII' => 'active',
                'CLOE' => 'active',
                'HORACE' => 'active',
                'THORIUS' => 'active',
                'ARCHER' => 'active',
            ),
        ), 200);
    }

    /**
     * Handle user plan operations.
     *
     * @since    2.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function handle_user_plan($request) {
        $user_id = $request->get_param('id');
        $method = $request->get_method();

        if ($method === 'GET') {
            $current_plan = get_user_meta($user_id, 'vortex_subscription_plan', true);
            $plan_details = $this->get_plan_details($current_plan);
            
            return new WP_REST_Response(array(
                'user_id' => $user_id,
                'current_plan' => $current_plan ?: 'none',
                'plan_details' => $plan_details,
                'subscription_status' => get_user_meta($user_id, 'vortex_subscription_status', true) ?: 'inactive',
            ), 200);
        } else {
            $plan = $request->get_param('plan');
            if (!in_array($plan, array('starter', 'pro', 'studio'))) {
                return new WP_Error('invalid_plan', 'Invalid subscription plan', array('status' => 400));
            }

            update_user_meta($user_id, 'vortex_subscription_plan', $plan);
            update_user_meta($user_id, 'vortex_subscription_status', 'active');
            update_user_meta($user_id, 'vortex_subscription_date', current_time('mysql'));

            return new WP_REST_Response(array(
                'success' => true,
                'user_id' => $user_id,
                'plan' => $plan,
                'message' => 'Subscription plan updated successfully',
            ), 200);
        }
    }

    /**
     * Handle role quiz operations.
     *
     * @since    2.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function handle_role_quiz($request) {
        $user_id = $request->get_param('id');
        $method = $request->get_method();

        if ($method === 'GET') {
            $quiz_results = get_user_meta($user_id, 'vortex_role_quiz_results', true);
            
            return new WP_REST_Response(array(
                'user_id' => $user_id,
                'quiz_completed' => !empty($quiz_results),
                'results' => $quiz_results ?: null,
                'recommended_role' => get_user_meta($user_id, 'vortex_recommended_role', true),
            ), 200);
        } else {
            $answers = $request->get_param('answers');
            if (empty($answers) || !is_array($answers)) {
                return new WP_Error('invalid_answers', 'Quiz answers are required', array('status' => 400));
            }

            // Process quiz answers and determine role
            $recommended_role = $this->process_quiz_answers($answers);
            
            update_user_meta($user_id, 'vortex_role_quiz_results', $answers);
            update_user_meta($user_id, 'vortex_recommended_role', $recommended_role);
            update_user_meta($user_id, 'vortex_quiz_completed_date', current_time('mysql'));

            return new WP_REST_Response(array(
                'success' => true,
                'user_id' => $user_id,
                'recommended_role' => $recommended_role,
                'message' => 'Role quiz completed successfully',
            ), 200);
        }
    }

    /**
     * Accept Terms of Service.
     *
     * @since    2.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function accept_terms_of_service($request) {
        $user_id = $request->get_param('id');
        $version = $request->get_param('tos_version') ?: '1.0';

        update_user_meta($user_id, 'vortex_tos_accepted', true);
        update_user_meta($user_id, 'vortex_tos_version', $version);
        update_user_meta($user_id, 'vortex_tos_date', current_time('mysql'));

        return new WP_REST_Response(array(
            'success' => true,
            'user_id' => $user_id,
            'tos_version' => $version,
            'message' => 'Terms of Service accepted successfully',
        ), 200);
    }

    /**
     * Upload seed art.
     *
     * @since    2.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function upload_seed_art($request) {
        $user_id = $request->get_param('id');
        $files = $request->get_file_params();

        if (empty($files['seed_art'])) {
            return new WP_Error('no_file', 'Seed art file is required', array('status' => 400));
        }

        $file = $files['seed_art'];
        $upload = wp_handle_upload($file, array('test_form' => false));

        if (isset($upload['error'])) {
            return new WP_Error('upload_error', $upload['error'], array('status' => 400));
        }

        // Create attachment
        $attachment_id = wp_insert_attachment(array(
            'post_title' => sanitize_file_name($file['name']),
            'post_content' => '',
            'post_status' => 'inherit',
            'post_author' => $user_id,
        ), $upload['file']);

        update_user_meta($user_id, 'vortex_seed_art_id', $attachment_id);
        update_user_meta($user_id, 'vortex_seed_art_uploaded', current_time('mysql'));

        return new WP_REST_Response(array(
            'success' => true,
            'user_id' => $user_id,
            'attachment_id' => $attachment_id,
            'file_url' => $upload['url'],
            'message' => 'Seed art uploaded successfully',
        ), 200);
    }

    /**
     * Handle Horas business quiz (Pro users only).
     *
     * @since    2.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function handle_horas_quiz($request) {
        $user_id = $request->get_param('id');
        $method = $request->get_method();

        if ($method === 'GET') {
            $quiz_results = get_user_meta($user_id, 'vortex_horas_quiz_results', true);
            
            return new WP_REST_Response(array(
                'user_id' => $user_id,
                'quiz_completed' => !empty($quiz_results),
                'results' => $quiz_results ?: null,
                'business_plan' => get_user_meta($user_id, 'vortex_business_plan', true),
            ), 200);
        } else {
            $answers = $request->get_param('answers');
            if (empty($answers) || !is_array($answers)) {
                return new WP_Error('invalid_answers', 'Quiz answers are required', array('status' => 400));
            }

            // Process business quiz and generate plan
            $business_plan = $this->generate_business_plan($answers);
            
            update_user_meta($user_id, 'vortex_horas_quiz_results', $answers);
            update_user_meta($user_id, 'vortex_business_plan', $business_plan);
            update_user_meta($user_id, 'vortex_horas_quiz_completed', current_time('mysql'));

            return new WP_REST_Response(array(
                'success' => true,
                'user_id' => $user_id,
                'business_plan' => $business_plan,
                'message' => 'Business quiz completed successfully',
            ), 200);
        }
    }

    /**
     * Check user permission.
     *
     * @since    2.0.0
     * @param WP_REST_Request $request Request object.
     * @return bool
     */
    public function check_user_permission($request) {
        if (!is_user_logged_in()) {
            return false;
        }

        $user_id = $request->get_param('id');
        $current_user_id = get_current_user_id();

        // Users can only access their own data, unless they're admin
        return $current_user_id == $user_id || current_user_can('manage_options');
    }

    /**
     * Check Pro subscriber permission.
     *
     * @since    2.0.0
     * @param WP_REST_Request $request Request object.
     * @return bool
     */
    public function check_pro_permission($request) {
        if (!$this->check_user_permission($request)) {
            return false;
        }

        $user_id = $request->get_param('id');
        $plan = get_user_meta($user_id, 'vortex_subscription_plan', true);
        
        return in_array($plan, array('pro', 'studio'));
    }

    /**
     * Get plan details.
     *
     * @since    2.0.0
     * @param string $plan Plan name.
     * @return array
     */
    private function get_plan_details($plan) {
        $plans = array(
            'starter' => array(
                'name' => 'Starter',
                'price' => 19.99,
                'currency' => 'USD',
                'features' => array('Basic AI generation', 'Community access', 'Basic analytics'),
            ),
            'pro' => array(
                'name' => 'Pro',
                'price' => 39.99,
                'currency' => 'USD',
                'features' => array('Advanced AI generation', 'Business quiz', 'Priority support', 'Advanced analytics'),
            ),
            'studio' => array(
                'name' => 'Studio',
                'price' => 99.99,
                'currency' => 'USD',
                'features' => array('All Pro features', 'Commercial licensing', 'Custom branding', 'API access'),
            ),
        );

        return isset($plans[$plan]) ? $plans[$plan] : null;
    }

    /**
     * Process quiz answers to determine recommended role.
     *
     * @since    2.0.0
     * @param array $answers Quiz answers.
     * @return string
     */
    private function process_quiz_answers($answers) {
        // Simple scoring algorithm - in real implementation, this would be more sophisticated
        $scores = array(
            'artist' => 0,
            'collector' => 0,
            'curator' => 0,
            'investor' => 0,
        );

        foreach ($answers as $answer) {
            if (isset($answer['role_preference'])) {
                $scores[$answer['role_preference']]++;
            }
        }

        return array_keys($scores, max($scores))[0];
    }

    /**
     * Generate business plan based on quiz answers.
     *
     * @since    2.0.0
     * @param array $answers Quiz answers.
     * @return array
     */
    private function generate_business_plan($answers) {
        return array(
            'goals' => $this->extract_goals($answers),
            'timeline' => '6 months',
            'milestones' => $this->generate_milestones($answers),
            'recommendations' => $this->generate_recommendations($answers),
            'created_date' => current_time('mysql'),
        );
    }

    /**
     * Extract goals from quiz answers.
     *
     * @since    2.0.0
     * @param array $answers Quiz answers.
     * @return array
     */
    private function extract_goals($answers) {
        // Placeholder implementation
        return array(
            'Establish online presence',
            'Build collector network',
            'Generate consistent revenue',
        );
    }

    /**
     * Generate milestones based on answers.
     *
     * @since    2.0.0
     * @param array $answers Quiz answers.
     * @return array
     */
    private function generate_milestones($answers) {
        return array(
            array(
                'title' => 'Complete profile setup',
                'deadline' => date('Y-m-d', strtotime('+1 week')),
                'status' => 'pending',
            ),
            array(
                'title' => 'Upload first artwork collection',
                'deadline' => date('Y-m-d', strtotime('+2 weeks')),
                'status' => 'pending',
            ),
            array(
                'title' => 'Make first sale',
                'deadline' => date('Y-m-d', strtotime('+1 month')),
                'status' => 'pending',
            ),
        );
    }

    /**
     * Generate recommendations based on answers.
     *
     * @since    2.0.0
     * @param array $answers Quiz answers.
     * @return array
     */
    private function generate_recommendations($answers) {
        return array(
            'Focus on your unique artistic style',
            'Engage with the community regularly',
            'Price your work competitively',
            'Use AI tools to enhance your workflow',
        );
    }
} 