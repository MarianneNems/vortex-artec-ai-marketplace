<?php
/**
 * Quiz API handler for role discovery and Horas business quizzes.
 */
class Vortex_Quiz_API {

    private $namespace = 'vortex/v1';

    public function register_routes() {
        register_rest_route($this->namespace, '/quiz/role/questions', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_role_quiz'),
            'permission_callback' => array($this, 'check_user_permission'),
        ));

        register_rest_route($this->namespace, '/quiz/role/submit', array(
            'methods' => 'POST',
            'callback' => array($this, 'submit_role_quiz'),
            'permission_callback' => array($this, 'check_user_permission'),
        ));

        register_rest_route($this->namespace, '/quiz/horas/questions', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_horas_quiz'),
            'permission_callback' => array($this, 'check_pro_permission'),
        ));

        register_rest_route($this->namespace, '/quiz/horas/submit', array(
            'methods' => 'POST', 
            'callback' => array($this, 'submit_horas_quiz'),
            'permission_callback' => array($this, 'check_pro_permission'),
        ));
    }

    public function get_role_quiz($request) {
        $questions = array(
            array(
                'id' => 1,
                'question' => 'What motivates you most in the art world?',
                'options' => array(
                    'A' => 'Creating original artwork',
                    'B' => 'Discovering new artists',
                    'C' => 'Building art communities',
                    'D' => 'Art investment and trading'
                )
            ),
            array(
                'id' => 2,
                'question' => 'How do you prefer to engage with art?',
                'options' => array(
                    'A' => 'Hands-on creation',
                    'B' => 'Curating collections',
                    'C' => 'Teaching and mentoring',
                    'D' => 'Market analysis and trends'
                )
            ),
            array(
                'id' => 3,
                'question' => 'What's your ideal art-related activity?',
                'options' => array(
                    'A' => 'Studio time creating',
                    'B' => 'Gallery browsing',
                    'C' => 'Hosting art events',
                    'D' => 'Researching art values'
                )
            ),
        );
        
        return new WP_REST_Response(array(
            'success' => true,
            'quiz_type' => 'role_discovery',
            'questions' => $questions,
            'total_questions' => count($questions),
        ), 200);
    }

    public function submit_role_quiz($request) {
        $answers = $request->get_param('answers');
        $user_id = get_current_user_id();
        
        if (empty($answers) || !is_array($answers)) {
            return new WP_Error('invalid_answers', 'Valid answers array required', array('status' => 400));
        }
        
        // Calculate role based on answers
        $role_scores = array('A' => 0, 'B' => 0, 'C' => 0, 'D' => 0);
        foreach ($answers as $answer) {
            if (isset($role_scores[$answer])) {
                $role_scores[$answer]++;
            }
        }
        
        $dominant_role = array_keys($role_scores, max($role_scores))[0];
        $roles = array(
            'A' => 'Creator',
            'B' => 'Collector', 
            'C' => 'Community Builder',
            'D' => 'Trader'
        );
        
        $result = array(
            'role' => $roles[$dominant_role],
            'scores' => $role_scores,
            'description' => $this->get_role_description($roles[$dominant_role]),
            'completed_at' => current_time('mysql'),
        );
        
        update_user_meta($user_id, 'vortex_role_quiz_results', $result);
        
        return new WP_REST_Response(array(
            'success' => true,
            'result' => $result,
            'message' => 'Role quiz completed successfully',
        ), 200);
    }

    public function get_horas_quiz($request) {
        $questions = array(
            array(
                'id' => 1,
                'question' => 'What is your primary business goal?',
                'options' => array(
                    'A' => 'Maximize profit margins',
                    'B' => 'Build brand recognition',
                    'C' => 'Create sustainable income',
                    'D' => 'Expand market reach'
                )
            ),
            array(
                'id' => 2,
                'question' => 'How do you handle pricing strategy?',
                'options' => array(
                    'A' => 'Cost-plus pricing',
                    'B' => 'Market-based pricing',
                    'C' => 'Value-based pricing',
                    'D' => 'Dynamic pricing'
                )
            ),
        );
        
        return new WP_REST_Response(array(
            'success' => true,
            'quiz_type' => 'horas_business',
            'questions' => $questions,
            'total_questions' => count($questions),
        ), 200);
    }

    public function submit_horas_quiz($request) {
        $answers = $request->get_param('answers');
        $user_id = get_current_user_id();
        
        if (empty($answers) || !is_array($answers)) {
            return new WP_Error('invalid_answers', 'Valid answers array required', array('status' => 400));
        }
        
        $business_profile = $this->analyze_business_answers($answers);
        
        update_user_meta($user_id, 'vortex_horas_quiz_results', $business_profile);
        
        return new WP_REST_Response(array(
            'success' => true,
            'business_profile' => $business_profile,
            'message' => 'Horas business quiz completed successfully',
        ), 200);
    }

    private function get_role_description($role) {
        $descriptions = array(
            'Creator' => 'You are a natural artist who thrives on bringing new ideas to life.',
            'Collector' => 'You have an eye for quality and enjoy curating meaningful art collections.',
            'Community Builder' => 'You excel at connecting people and building vibrant art communities.',
            'Trader' => 'You understand market dynamics and excel at art investment strategies.',
        );
        
        return $descriptions[$role] ?? 'Your unique role in the art ecosystem.';
    }

    private function analyze_business_answers($answers) {
        return array(
            'business_type' => 'growth_focused',
            'strategy_recommendations' => array(
                'Focus on building a strong brand identity',
                'Develop multiple revenue streams',
                'Invest in marketing and community building',
            ),
            'completed_at' => current_time('mysql'),
        );
    }

    public function check_user_permission($request) {
        return is_user_logged_in();
    }

    public function check_pro_permission($request) {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user_plan = get_user_meta(get_current_user_id(), 'vortex_subscription_plan', true);
        return in_array($user_plan, array('pro', 'studio'));
    }
} 