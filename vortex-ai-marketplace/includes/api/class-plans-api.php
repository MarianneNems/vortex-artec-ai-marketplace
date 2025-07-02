<?php
/**
 * Plans API handler for subscription plan management.
 *
 * @since      2.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/api
 */
class Vortex_Plans_API {

    /**
     * The namespace for REST API routes.
     *
     * @since    2.0.0
     * @access   private
     * @var      string    $namespace
     */
    private $namespace;

    /**
     * Initialize the class.
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
        register_rest_route($this->namespace, '/plans', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_plans'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route($this->namespace, '/plans/(?P<plan>[\w-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_plan_details'),
            'permission_callback' => '__return_true',
            'args' => array(
                'plan' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return in_array($param, array('starter', 'pro', 'studio'));
                    }
                ),
            ),
        ));
    }

    /**
     * Get all available plans.
     *
     * @since    2.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_plans($request) {
        $plans = array(
            'starter' => array(
                'id' => 'starter',
                'name' => 'Starter Plan',
                'price' => 19.99,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'features' => array(
                    'Basic AI artwork generation',
                    'Community access',
                    'Basic analytics dashboard',
                    '5 NFT mints per month',
                    'Standard support',
                ),
                'limits' => array(
                    'monthly_generations' => 50,
                    'nft_mints' => 5,
                    'storage_gb' => 1,
                ),
                'popular' => false,
            ),
            'pro' => array(
                'id' => 'pro',
                'name' => 'Pro Plan',
                'price' => 39.99,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'features' => array(
                    'Advanced AI artwork generation',
                    'Horas business quiz access',
                    'Priority community support',
                    'Advanced analytics & insights',
                    '25 NFT mints per month',
                    'Custom branding options',
                    'Marketplace priority listing',
                ),
                'limits' => array(
                    'monthly_generations' => 200,
                    'nft_mints' => 25,
                    'storage_gb' => 5,
                ),
                'popular' => true,
            ),
            'studio' => array(
                'id' => 'studio',
                'name' => 'Studio Plan',
                'price' => 99.99,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'features' => array(
                    'Unlimited AI artwork generation',
                    'Full business suite access',
                    'Dedicated account manager',
                    'White-label solutions',
                    'Unlimited NFT mints',
                    'API access & integrations',
                    'Commercial licensing rights',
                    'Advanced collaboration tools',
                ),
                'limits' => array(
                    'monthly_generations' => -1, // Unlimited
                    'nft_mints' => -1, // Unlimited
                    'storage_gb' => 50,
                ),
                'popular' => false,
            ),
        );

        return new WP_REST_Response(array(
            'success' => true,
            'plans' => array_values($plans),
            'currency' => 'USD',
            'total_plans' => count($plans),
        ), 200);
    }

    /**
     * Get specific plan details.
     *
     * @since    2.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_plan_details($request) {
        $plan_id = $request->get_param('plan');
        $all_plans = $this->get_plans($request)->get_data()['plans'];
        
        $plan = null;
        foreach ($all_plans as $p) {
            if ($p['id'] === $plan_id) {
                $plan = $p;
                break;
            }
        }

        if (!$plan) {
            return new WP_Error('plan_not_found', 'Plan not found', array('status' => 404));
        }

        return new WP_REST_Response(array(
            'success' => true,
            'plan' => $plan,
            'upgrade_options' => $this->get_upgrade_options($plan_id),
        ), 200);
    }

    /**
     * Get upgrade options for a plan.
     *
     * @since    2.0.0
     * @param string $current_plan Current plan ID.
     * @return array
     */
    private function get_upgrade_options($current_plan) {
        $upgrade_paths = array(
            'starter' => array('pro', 'studio'),
            'pro' => array('studio'),
            'studio' => array(),
        );

        return $upgrade_paths[$current_plan] ?? array();
    }
} 