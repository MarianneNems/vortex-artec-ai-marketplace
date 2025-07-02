<?php
/**
 * Milestones API handler for user progress tracking.
 *
 * @since      2.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/api
 */
class Vortex_Milestones_API {

    private $namespace;

    public function __construct() {
        $this->namespace = 'vortex/v1';
    }

    public function register_routes() {
        register_rest_route($this->namespace, '/users/(?P<id>\d+)/milestones', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_milestones'),
            'permission_callback' => array($this, 'check_user_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));

        register_rest_route($this->namespace, '/users/(?P<id>\d+)/milestones/(?P<milestone_id>\d+)/complete', array(
            'methods' => 'POST',
            'callback' => array($this, 'complete_milestone'),
            'permission_callback' => array($this, 'check_user_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
                'milestone_id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
    }

    public function get_user_milestones($request) {
        $user_id = $request->get_param('id');
        
        // Get user's current plan to determine available milestones
        $user_plan = get_user_meta($user_id, 'vortex_subscription_plan', true) ?: 'none';
        
        // Generate milestones based on plan and progress
        $milestones = $this->generate_user_milestones($user_id, $user_plan);
        
        // Calculate overall progress
        $overall_progress = $this->calculate_overall_progress($milestones);
        
        return new WP_REST_Response(array(
            'success' => true,
            'user_id' => $user_id,
            'milestones' => $milestones,
            'overall_progress' => $overall_progress,
            'total_milestones' => count($milestones),
            'completed_milestones' => count(array_filter($milestones, function($m) { return $m['status'] === 'completed'; })),
        ), 200);
    }

    public function complete_milestone($request) {
        $user_id = $request->get_param('id');
        $milestone_id = $request->get_param('milestone_id');
        
        // Verify milestone exists and can be completed
        $milestone = $this->get_milestone_by_id($milestone_id);
        if (!$milestone) {
            return new WP_Error('milestone_not_found', 'Milestone not found', array('status' => 404));
        }
        
        // Check if user meets completion criteria
        if (!$this->verify_milestone_completion($user_id, $milestone)) {
            return new WP_Error('milestone_not_eligible', 'Milestone completion criteria not met', array('status' => 400));
        }
        
        // Mark milestone as completed
        $completion_data = array(
            'milestone_id' => $milestone_id,
            'completed_at' => current_time('mysql'),
            'reward_claimed' => false,
        );
        
        $completed_milestones = get_user_meta($user_id, 'vortex_completed_milestones', true) ?: array();
        $completed_milestones[$milestone_id] = $completion_data;
        update_user_meta($user_id, 'vortex_completed_milestones', $completed_milestones);
        
        // Award milestone reward
        $reward = $this->award_milestone_reward($user_id, $milestone);
        
        return new WP_REST_Response(array(
            'success' => true,
            'milestone_id' => $milestone_id,
            'milestone_title' => $milestone['title'],
            'reward' => $reward,
            'message' => 'Milestone completed successfully!',
        ), 200);
    }

    private function generate_user_milestones($user_id, $plan) {
        $base_milestones = array(
            array(
                'id' => 1,
                'title' => 'Complete Profile Setup',
                'description' => 'Fill out your artist profile with bio and preferences',
                'type' => 'profile',
                'reward_type' => 'tola',
                'reward_amount' => 10,
                'required_plan' => 'starter',
            ),
            array(
                'id' => 2,
                'title' => 'Connect Your Wallet',
                'description' => 'Link your Solana wallet to start earning TOLA',
                'type' => 'wallet',
                'reward_type' => 'tola',
                'reward_amount' => 25,
                'required_plan' => 'starter',
            ),
            array(
                'id' => 3,
                'title' => 'Complete Role Quiz',
                'description' => 'Discover your perfect role in the art ecosystem',
                'type' => 'quiz',
                'reward_type' => 'tola',
                'reward_amount' => 15,
                'required_plan' => 'starter',
            ),
            array(
                'id' => 4,
                'title' => 'Upload Seed Artwork',
                'description' => 'Upload your first artwork to train our AI',
                'type' => 'upload',
                'reward_type' => 'tola',
                'reward_amount' => 30,
                'required_plan' => 'starter',
            ),
            array(
                'id' => 5,
                'title' => 'Generate First AI Art',
                'description' => 'Create your first AI-generated artwork',
                'type' => 'generation',
                'reward_type' => 'tola',
                'reward_amount' => 50,
                'required_plan' => 'starter',
            ),
            array(
                'id' => 6,
                'title' => 'Complete Horas Business Quiz',
                'description' => 'Develop your personalized business strategy',
                'type' => 'business_quiz',
                'reward_type' => 'tola',
                'reward_amount' => 100,
                'required_plan' => 'pro',
            ),
            array(
                'id' => 7,
                'title' => 'Create First Collection',
                'description' => 'Organize your artwork into a themed collection',
                'type' => 'collection',
                'reward_type' => 'tola',
                'reward_amount' => 75,
                'required_plan' => 'starter',
            ),
            array(
                'id' => 8,
                'title' => 'Mint Your First NFT',
                'description' => 'Convert your artwork into a tradeable NFT',
                'type' => 'nft_mint',
                'reward_type' => 'tola',
                'reward_amount' => 100,
                'required_plan' => 'starter',
            ),
            array(
                'id' => 9,
                'title' => 'Make Your First Sale',
                'description' => 'Sell your first artwork on the marketplace',
                'type' => 'sale',
                'reward_type' => 'tola',
                'reward_amount' => 200,
                'required_plan' => 'starter',
            ),
            array(
                'id' => 10,
                'title' => 'Reach 1000 TOLA',
                'description' => 'Accumulate 1000 TOLA tokens through activities',
                'type' => 'balance',
                'reward_type' => 'badge',
                'reward_amount' => 1,
                'required_plan' => 'starter',
            ),
        );
        
        // Filter milestones based on user's plan
        $available_milestones = array_filter($base_milestones, function($milestone) use ($plan) {
            return $this->plan_includes_milestone($plan, $milestone['required_plan']);
        });
        
        // Add progress and status to each milestone
        foreach ($available_milestones as &$milestone) {
            $milestone['progress'] = $this->calculate_milestone_progress($user_id, $milestone);
            $milestone['status'] = $this->get_milestone_status($user_id, $milestone);
        }
        
        return array_values($available_milestones);
    }

    private function plan_includes_milestone($user_plan, $required_plan) {
        $plan_hierarchy = array('starter' => 1, 'pro' => 2, 'studio' => 3);
        $user_level = $plan_hierarchy[$user_plan] ?? 0;
        $required_level = $plan_hierarchy[$required_plan] ?? 0;
        
        return $user_level >= $required_level;
    }

    private function calculate_milestone_progress($user_id, $milestone) {
        switch ($milestone['type']) {
            case 'profile':
                return $this->check_profile_completion($user_id);
            case 'wallet':
                return get_user_meta($user_id, 'vortex_wallet_address', true) ? 100 : 0;
            case 'quiz':
                return get_user_meta($user_id, 'vortex_role_quiz_results', true) ? 100 : 0;
            case 'upload':
                return get_user_meta($user_id, 'vortex_seed_art_id', true) ? 100 : 0;
            case 'generation':
                return $this->count_user_generations($user_id) > 0 ? 100 : 0;
            case 'business_quiz':
                return get_user_meta($user_id, 'vortex_horas_quiz_results', true) ? 100 : 0;
            case 'collection':
                return $this->count_user_collections($user_id) > 0 ? 100 : 0;
            case 'nft_mint':
                return $this->count_user_nfts($user_id) > 0 ? 100 : 0;
            case 'sale':
                return $this->count_user_sales($user_id) > 0 ? 100 : 0;
            case 'balance':
                $balance = $this->get_user_tola_balance($user_id);
                return min(100, ($balance / 1000) * 100);
            default:
                return 0;
        }
    }

    private function get_milestone_status($user_id, $milestone) {
        $progress = $milestone['progress'];
        $completed_milestones = get_user_meta($user_id, 'vortex_completed_milestones', true) ?: array();
        
        if (isset($completed_milestones[$milestone['id']])) {
            return 'completed';
        } elseif ($progress >= 100) {
            return 'ready'; // Ready to be marked as complete
        } elseif ($progress > 0) {
            return 'in_progress';
        } else {
            return 'pending';
        }
    }

    private function calculate_overall_progress($milestones) {
        if (empty($milestones)) return 0;
        
        $total_progress = array_sum(array_column($milestones, 'progress'));
        return round($total_progress / count($milestones));
    }

    private function check_profile_completion($user_id) {
        $user = get_userdata($user_id);
        $bio = get_user_meta($user_id, 'description', true);
        $avatar = get_avatar_url($user_id);
        
        $completion = 0;
        if (!empty($user->display_name)) $completion += 25;
        if (!empty($bio)) $completion += 25;
        if (!empty($user->user_url)) $completion += 25;
        if ($avatar) $completion += 25;
        
        return $completion;
    }

    private function count_user_generations($user_id) {
        // Mock implementation - in real system, query database
        return rand(0, 5);
    }

    private function count_user_collections($user_id) {
        $args = array(
            'post_type' => 'vortex_collection',
            'author' => $user_id,
            'post_status' => 'any',
            'posts_per_page' => -1,
        );
        return count(get_posts($args));
    }

    private function count_user_nfts($user_id) {
        // Mock implementation - in real system, query blockchain
        return rand(0, 3);
    }

    private function count_user_sales($user_id) {
        // Mock implementation - in real system, query transaction database
        return rand(0, 2);
    }

    private function get_user_tola_balance($user_id) {
        // Mock implementation - in real system, query blockchain
        return rand(0, 1500);
    }

    private function get_milestone_by_id($milestone_id) {
        // In real implementation, this would fetch from database
        $milestones = $this->generate_user_milestones(1, 'pro'); // Mock user for structure
        foreach ($milestones as $milestone) {
            if ($milestone['id'] == $milestone_id) {
                return $milestone;
            }
        }
        return null;
    }

    private function verify_milestone_completion($user_id, $milestone) {
        return $this->calculate_milestone_progress($user_id, $milestone) >= 100;
    }

    private function award_milestone_reward($user_id, $milestone) {
        if ($milestone['reward_type'] === 'tola') {
            // In real implementation, add TOLA to user's wallet
            $current_balance = get_user_meta($user_id, 'vortex_tola_balance', true) ?: 0;
            $new_balance = $current_balance + $milestone['reward_amount'];
            update_user_meta($user_id, 'vortex_tola_balance', $new_balance);
            
            return array(
                'type' => 'tola',
                'amount' => $milestone['reward_amount'],
                'new_balance' => $new_balance,
            );
        } elseif ($milestone['reward_type'] === 'badge') {
            // Award achievement badge
            $badges = get_user_meta($user_id, 'vortex_badges', true) ?: array();
            $badges[] = array(
                'name' => 'TOLA Collector',
                'earned_at' => current_time('mysql'),
            );
            update_user_meta($user_id, 'vortex_badges', $badges);
            
            return array(
                'type' => 'badge',
                'name' => 'TOLA Collector',
            );
        }
        
        return null;
    }

    public function check_user_permission($request) {
        if (!is_user_logged_in()) {
            return false;
        }

        $user_id = $request->get_param('id');
        $current_user_id = get_current_user_id();

        return $current_user_id == $user_id || current_user_can('manage_options');
    }
} 