<?php
/**
 * Enhanced Task Automation System with Artist Journey Integration
 * 
 * Enables AI agents to automate routine tasks, anticipate user needs,
 * and proactively generate content based on learned patterns.
 * Includes Artist Journey specific automation tasks.
 */
class Vortex_Task_Automation {
    
    /**
     * Initialize task automation system
     */
    public function __construct() {
        // Register automated task hooks
        add_action('vortex_run_automated_tasks', array($this, 'execute_automated_tasks'));
        
        // Register AJAX handlers for user task management
        add_action('wp_ajax_vortex_get_automation_tasks', array($this, 'get_user_automation_tasks'));
        add_action('wp_ajax_vortex_create_automation_task', array($this, 'create_automation_task'));
        add_action('wp_ajax_vortex_toggle_automation_task', array($this, 'toggle_automation_task'));
        
        // Artist Journey specific automation hooks
        add_action('wp_ajax_vortex_create_artist_journey_automation', array($this, 'create_artist_journey_automation'));
        add_action('wp_ajax_vortex_check_milestone_progress', array($this, 'check_milestone_progress_automation'));
        
        // Add task automation settings to user profile
        add_action('show_user_profile', array($this, 'add_automation_preferences'));
        add_action('edit_user_profile', array($this, 'add_automation_preferences'));
        add_action('personal_options_update', array($this, 'save_automation_preferences'));
        add_action('edit_user_profile_update', array($this, 'save_automation_preferences'));
        
        // Schedule automated tasks execution
        if (!wp_next_scheduled('vortex_run_automated_tasks')) {
            wp_schedule_event(time(), 'hourly', 'vortex_run_automated_tasks');
        }
        
        // Artist Journey integration hooks
        add_action('vortex_user_registered', array($this, 'auto_create_onboarding_tasks'), 10, 1);
        add_action('vortex_plan_selected', array($this, 'auto_create_plan_specific_tasks'), 10, 2);
        add_action('vortex_milestone_created', array($this, 'auto_create_milestone_reminders'), 10, 2);
    }
    
    /**
     * Execute automated tasks for all users
     */
    public function execute_automated_tasks() {
        global $wpdb;
        
        // Get all active automation tasks
        $tasks_table = $wpdb->prefix . 'vortex_automation_tasks';
        $active_tasks = $wpdb->get_results(
            "SELECT * FROM $tasks_table 
            WHERE active = 1 
            AND next_run <= NOW()",
            ARRAY_A
        );
        
        foreach ($active_tasks as $task) {
            $this->execute_single_task($task);
        }
    }
    
    /**
     * Execute a single automation task
     * 
     * @param array $task Task data
     * @return bool Success status
     */
    private function execute_single_task($task) {
        global $wpdb;
        $tasks_table = $wpdb->prefix . 'vortex_automation_tasks';
        
        try {
            // Update last run time before execution to prevent duplicate runs
            $wpdb->update(
                $tasks_table,
                array(
                    'last_run' => current_time('mysql'),
                    'next_run' => $this->calculate_next_run_time($task)
                ),
                array('id' => $task['id'])
            );
            
            // Check if user has sufficient TOLA tokens (if required)
            if ($this->task_requires_tokens($task['task_type'])) {
                $wallet = Vortex_AI_Marketplace::get_instance()->wallet;
                if (!$wallet->check_llm_api_access($task['user_id'])) {
                    // Log insufficient tokens
                    $this->log_automation_result($task['id'], false, 'Insufficient TOLA tokens');
                    return false;
                }
            }
            
            // Execute task based on type
            switch ($task['task_type']) {
                // Original automation tasks
                case 'artwork_generation':
                    $result = $this->execute_artwork_task($task);
                    break;
                    
                case 'market_analysis':
                    $result = $this->execute_market_analysis_task($task);
                    break;
                    
                case 'strategy_recommendation':
                    $result = $this->execute_strategy_task($task);
                    break;
                
                // Artist Journey specific automation tasks
                case 'onboarding_reminder':
                    $result = $this->execute_onboarding_reminder($task);
                    break;
                    
                case 'milestone_check':
                    $result = $this->execute_milestone_check($task);
                    break;
                    
                case 'pro_upgrade_suggestion':
                    $result = $this->execute_pro_upgrade_suggestion($task);
                    break;
                    
                case 'seed_artwork_reminder':
                    $result = $this->execute_seed_artwork_reminder($task);
                    break;
                    
                case 'horas_quiz_reminder':
                    $result = $this->execute_horas_quiz_reminder($task);
                    break;
                    
                case 'collection_suggestion':
                    $result = $this->execute_collection_suggestion($task);
                    break;
                    
                case 'chloe_inspiration_update':
                    $result = $this->execute_chloe_inspiration_update($task);
                    break;
                    
                case 'progress_celebration':
                    $result = $this->execute_progress_celebration($task);
                    break;
                    
                default:
                    // Unsupported task type
                    $this->log_automation_result($task['id'], false, 'Unsupported task type: ' . $task['task_type']);
                    return false;
            }
            
            // Check result and log
            if (is_wp_error($result)) {
                $this->log_automation_result($task['id'], false, $result->get_error_message());
                return false;
            }
            
            // Log successful execution
            $this->log_automation_result($task['id'], true, json_encode($result));
            
            // Notify user of completed task
            $this->notify_user_of_completed_task($task['user_id'], $task, $result);
            
            return true;
            
        } catch (Exception $e) {
            // Log exception
            $this->log_automation_result($task['id'], false, $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if task type requires TOLA tokens
     */
    private function task_requires_tokens($task_type) {
        $token_required_tasks = array(
            'artwork_generation',
            'market_analysis', 
            'strategy_recommendation',
            'chloe_inspiration_update'
        );
        
        return in_array($task_type, $token_required_tasks);
    }
    
    /**
     * Execute artwork generation task
     * 
     * @param array $task Task data
     * @return array|WP_Error Result data or error
     */
    private function execute_artwork_task($task) {
        try {
            // Get task parameters
            $params = json_decode($task['task_params'], true);
            if (!isset($params['prompt'])) {
                return new WP_Error('invalid_params', 'Prompt is required for artwork generation');
            }
            
            // Set default parameters if not specified
            $style = isset($params['style']) ? $params['style'] : 'realistic';
            $size = isset($params['size']) ? $params['size'] : '512x512';
            
            // Get HURAII service
            $huraii = Vortex_AI_Marketplace::get_instance()->get_artwork_service();
            
            // Generate artwork
            $result = $huraii->generate_artwork(
                $params['prompt'],
                $style,
                $size,
                $task['user_id']
            );
            
            // Deduct TOLA tokens
            $this->deduct_tokens_for_automation($task);
            
            return $result;
            
        } catch (Exception $e) {
            return new WP_Error('artwork_generation_failed', $e->getMessage());
        }
    }
    
    /**
     * Execute market analysis task
     * 
     * @param array $task Task data
     * @return array|WP_Error Result data or error
     */
    private function execute_market_analysis_task($task) {
        try {
            // Get task parameters
            $params = json_decode($task['task_params'], true);
            if (!isset($params['market'])) {
                return new WP_Error('invalid_params', 'Market is required for analysis');
            }
            
            // Set default parameters if not specified
            $timeframe = isset($params['timeframe']) ? $params['timeframe'] : '30';
            $detail_level = isset($params['detail_level']) ? $params['detail_level'] : 'medium';
            
            // Get CLOE service
            $cloe = Vortex_AI_Marketplace::get_instance()->get_cloe_service();
            
            // Generate market analysis
            $result = $cloe->analyze_market(
                $params['market'],
                $timeframe,
                $detail_level,
                $task['user_id']
            );
            
            // Deduct TOLA tokens
            $this->deduct_tokens_for_automation($task);
            
            return $result;
            
        } catch (Exception $e) {
            return new WP_Error('market_analysis_failed', $e->getMessage());
        }
    }
    
    /**
     * Execute strategy recommendation task
     * 
     * @param array $task Task data
     * @return array|WP_Error Result data or error
     */
    private function execute_strategy_task($task) {
        try {
            // Get task parameters
            $params = json_decode($task['task_params'], true);
            if (!isset($params['industry'])) {
                return new WP_Error('invalid_params', 'Industry is required for strategy recommendation');
            }
            
            // Set default parameters if not specified
            $focus = isset($params['focus']) ? $params['focus'] : 'growth';
            $timeframe = isset($params['timeframe']) ? $params['timeframe'] : 'medium';
            
            // Get Business Strategist service
            $strategist = Vortex_AI_Marketplace::get_instance()->get_strategist_service();
            
            // Generate strategy recommendation
            $result = $strategist->generate_strategy(
                $params['industry'],
                $focus,
                $timeframe,
                $task['user_id']
            );
            
            // Deduct TOLA tokens
            $this->deduct_tokens_for_automation($task);
            
            return $result;
            
        } catch (Exception $e) {
            return new WP_Error('strategy_generation_failed', $e->getMessage());
        }
    }
    
    /**
     * Calculate next run time for a task
     * 
     * @param array $task Task data
     * @return string Next run time (MySQL datetime)
     */
    private function calculate_next_run_time($task) {
        $frequency = isset($task['frequency']) ? $task['frequency'] : 'daily';
        $now = current_time('timestamp');
        
        switch ($frequency) {
            case 'hourly':
                $next_run = $now + HOUR_IN_SECONDS;
                break;
            case 'daily':
                $next_run = $now + DAY_IN_SECONDS;
                break;
            case 'weekly':
                $next_run = $now + WEEK_IN_SECONDS;
                break;
            case 'monthly':
                $next_run = $now + 30 * DAY_IN_SECONDS;
                break;
            default:
                $next_run = $now + DAY_IN_SECONDS;
        }
        
        return date('Y-m-d H:i:s', $next_run);
    }
    
    /**
     * Log automation task execution result
     * 
     * @param int $task_id Task ID
     * @param bool $success Success status
     * @param string $message Result message
     */
    private function log_automation_result($task_id, $success, $message) {
        global $wpdb;
        $logs_table = $wpdb->prefix . 'vortex_automation_logs';
        
        $wpdb->insert(
            $logs_table,
            array(
                'task_id' => $task_id,
                'success' => $success ? 1 : 0,
                'message' => $message,
                'execution_time' => current_time('mysql')
            )
        );
    }
    
    /**
     * Deduct TOLA tokens for automated task execution
     * 
     * @param array $task Task data
     */
    private function deduct_tokens_for_automation($task) {
        $wallet = Vortex_AI_Marketplace::get_instance()->wallet;
        $task_type = $task['task_type'];
        
        // Define token costs for automated tasks
        $token_costs = array(
            'artwork_generation' => 15,
            'market_analysis' => 10,
            'strategy_recommendation' => 20
        );
        
        $cost = isset($token_costs[$task_type]) ? $token_costs[$task_type] : 10;
        
        // Deduct tokens
        $wallet->deduct_tola_tokens(
            $task['user_id'],
            $cost,
            'automated_' . $task_type
        );
    }
    
    /**
     * Notify user of completed automated task
     * 
     * @param int $user_id User ID
     * @param array $task Task data
     * @param array $result Task result
     */
    private function notify_user_of_completed_task($user_id, $task, $result) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }
        
        $task_name = isset($task['task_name']) ? $task['task_name'] : 'Automated Task';
        $task_type_label = $this->get_task_type_label($task['task_type']);
        
        // Add notification to user meta
        $notifications = get_user_meta($user_id, 'vortex_ai_notifications', true);
        if (!is_array($notifications)) {
            $notifications = array();
        }
        
        // Add new notification
        $notifications[] = array(
            'id' => uniqid('notify_'),
            'title' => $task_name . ' Completed',
            'message' => 'Your automated ' . $task_type_label . ' task has completed successfully.',
            'type' => 'automation',
            'task_id' => $task['id'],
            'result_id' => isset($result['id']) ? $result['id'] : null,
            'time' => current_time('mysql'),
            'read' => false
        );
        
        // Limit to 50 notifications to prevent metadata bloat
        if (count($notifications) > 50) {
            $notifications = array_slice($notifications, -50);
        }
        
        update_user_meta($user_id, 'vortex_ai_notifications', $notifications);
        
        // Send email notification if enabled in user preferences
        $send_email = get_user_meta($user_id, 'vortex_automation_email_notifications', true);
        if ($send_email) {
            $this->send_task_completion_email($user, $task, $result);
        }
    }
    
    /**
     * Send email notification for completed task
     * 
     * @param WP_User $user User object
     * @param array $task Task data
     * @param array $result Task result
     */
    private function send_task_completion_email($user, $task, $result) {
        $task_name = isset($task['task_name']) ? $task['task_name'] : 'Automated Task';
        $task_type_label = $this->get_task_type_label($task['task_type']);
        $admin_email = get_option('admin_email');
        
        $subject = 'Vortex AI: ' . $task_name . ' Completed';
        
        $message = "Hello " . $user->display_name . ",\n\n";
        $message .= "Your automated " . $task_type_label . " task '" . $task_name . "' has completed successfully.\n\n";
        $message .= "You can view the results by logging into your account and checking your AI dashboard.\n\n";
        $message .= "Thank you for using Vortex AI Marketplace!\n";
        
        wp_mail($user->user_email, $subject, $message, array(
            'From: Vortex AI <' . $admin_email . '>'
        ));
    }
    
    /**
     * Get human-readable label for task type
     * 
     * @param string $task_type Task type
     * @return string Human-readable label
     */
    private function get_task_type_label($task_type) {
        $labels = array(
            'artwork_generation' => 'Artwork Generation',
            'market_analysis' => 'Market Analysis',
            'strategy_recommendation' => 'Strategy Recommendation'
        );
        
        return isset($labels[$task_type]) ? $labels[$task_type] : $task_type;
    }
    
    /**
     * Create database tables for task automation
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tasks table
        $tasks_table = $wpdb->prefix . 'vortex_automation_tasks';
        $tasks_sql = "CREATE TABLE IF NOT EXISTS $tasks_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            task_name varchar(100) NOT NULL,
            task_type varchar(50) NOT NULL,
            task_params longtext NOT NULL,
            frequency varchar(20) NOT NULL DEFAULT 'daily',
            active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL,
            last_run datetime DEFAULT NULL,
            next_run datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY active_next_run (active, next_run)
        ) $charset_collate;";
        
        // Logs table
        $logs_table = $wpdb->prefix . 'vortex_automation_logs';
        $logs_sql = "CREATE TABLE IF NOT EXISTS $logs_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            task_id bigint(20) NOT NULL,
            success tinyint(1) NOT NULL,
            message text NOT NULL,
            execution_time datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY task_id (task_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($tasks_sql);
        dbDelta($logs_sql);
    }
    
    /**
     * ===== ARTIST JOURNEY SPECIFIC AUTOMATION TASKS =====
     */
    
    /**
     * Execute onboarding reminder task
     */
    private function execute_onboarding_reminder($task) {
        $params = json_decode($task['task_params'], true);
        $user_id = $task['user_id'];
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            return new WP_Error('user_not_found', 'User not found');
        }
        
        // Check if user completed onboarding step
        $current_step = get_user_meta($user_id, 'vortex_onboarding_step', true);
        $target_step = $params['target_step'] ?? 'plan_selection';
        
        if ($current_step === $target_step) {
            // User still on same step, send reminder
            $this->send_onboarding_reminder_notification($user, $target_step);
            
            return array(
                'reminder_sent' => true,
                'user_id' => $user_id,
                'step' => $target_step,
                'timestamp' => current_time('mysql')
            );
        }
        
        // User progressed, deactivate reminder
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'vortex_automation_tasks',
            array('active' => 0),
            array('id' => $task['id'])
        );
        
        return array('reminder_not_needed' => true, 'user_progressed' => true);
    }
    
    /**
     * Execute milestone check task
     */
    private function execute_milestone_check($task) {
        $params = json_decode($task['task_params'], true);
        $user_id = $task['user_id'];
        
        // Get user's milestones
        $artist_journey = VORTEX_Artist_Journey::get_instance();
        $milestones = $artist_journey->get_user_milestones($user_id);
        
        $overdue_milestones = array();
        $upcoming_milestones = array();
        
        foreach ($milestones as $milestone) {
            $due_date = strtotime($milestone['due_date']);
            $now = current_time('timestamp');
            
            if ($due_date < $now && $milestone['status'] !== 'completed') {
                $overdue_milestones[] = $milestone;
            } elseif ($due_date - $now <= 24 * 3600 && $milestone['status'] !== 'completed') {
                $upcoming_milestones[] = $milestone;
            }
        }
        
        // Send notifications for overdue milestones
        if (!empty($overdue_milestones)) {
            $this->send_milestone_overdue_notification($user_id, $overdue_milestones);
        }
        
        // Send reminders for upcoming milestones
        if (!empty($upcoming_milestones)) {
            $this->send_milestone_upcoming_notification($user_id, $upcoming_milestones);
        }
        
        return array(
            'overdue_count' => count($overdue_milestones),
            'upcoming_count' => count($upcoming_milestones),
            'notifications_sent' => !empty($overdue_milestones) || !empty($upcoming_milestones)
        );
    }
    
    /**
     * Execute Pro upgrade suggestion task
     */
    private function execute_pro_upgrade_suggestion($task) {
        $user_id = $task['user_id'];
        $current_plan = get_user_meta($user_id, 'vortex_selected_plan', true);
        
        if ($current_plan === 'pro' || $current_plan === 'studio') {
            // User already upgraded, deactivate task
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'vortex_automation_tasks',
                array('active' => 0),
                array('id' => $task['id'])
            );
            return array('suggestion_not_needed' => true, 'already_upgraded' => true);
        }
        
        // Analyze user activity for upgrade suggestion
        $user_stats = $this->analyze_user_activity_for_upgrade($user_id);
        
        if ($user_stats['should_suggest_upgrade']) {
            $this->send_pro_upgrade_suggestion($user_id, $user_stats);
            
            return array(
                'suggestion_sent' => true,
                'user_stats' => $user_stats,
                'timestamp' => current_time('mysql')
            );
        }
        
        return array('suggestion_not_needed' => true, 'criteria_not_met' => true);
    }
    
    /**
     * Execute seed artwork reminder task
     */
    private function execute_seed_artwork_reminder($task) {
        $user_id = $task['user_id'];
        
        // Check if user has uploaded seed artwork
        $seed_artwork_count = get_user_meta($user_id, 'vortex_seed_artwork_count', true) ?: 0;
        
        if ($seed_artwork_count === 0) {
            $this->send_seed_artwork_reminder($user_id);
            
            return array(
                'reminder_sent' => true,
                'seed_artwork_count' => $seed_artwork_count,
                'timestamp' => current_time('mysql')
            );
        }
        
        // User uploaded seed artwork, deactivate reminder
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'vortex_automation_tasks',
            array('active' => 0),
            array('id' => $task['id'])
        );
        
        return array('reminder_not_needed' => true, 'seed_artwork_uploaded' => true);
    }
    
    /**
     * Execute Horas quiz reminder task
     */
    private function execute_horas_quiz_reminder($task) {
        $user_id = $task['user_id'];
        $current_plan = get_user_meta($user_id, 'vortex_selected_plan', true);
        
        if ($current_plan !== 'pro') {
            // User not on Pro plan, deactivate reminder
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'vortex_automation_tasks',
                array('active' => 0),
                array('id' => $task['id'])
            );
            return array('reminder_not_needed' => true, 'not_pro_user' => true);
        }
        
        // Check if user completed Horas quiz
        $horas_completed = get_user_meta($user_id, 'vortex_horas_quiz_completed', true);
        
        if (!$horas_completed) {
            $this->send_horas_quiz_reminder($user_id);
            
            return array(
                'reminder_sent' => true,
                'timestamp' => current_time('mysql')
            );
        }
        
        // Quiz completed, deactivate reminder
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'vortex_automation_tasks',
            array('active' => 0),
            array('id' => $task['id'])
        );
        
        return array('reminder_not_needed' => true, 'quiz_completed' => true);
    }
    
    /**
     * Execute collection suggestion task
     */
    private function execute_collection_suggestion($task) {
        $user_id = $task['user_id'];
        
        // Analyze user's artwork for collection suggestions
        $artwork_analysis = $this->analyze_user_artwork_for_collections($user_id);
        
        if ($artwork_analysis['suggested_collections']) {
            $this->send_collection_suggestion($user_id, $artwork_analysis['suggested_collections']);
            
            return array(
                'suggestions_sent' => true,
                'collection_count' => count($artwork_analysis['suggested_collections']),
                'timestamp' => current_time('mysql')
            );
        }
        
        return array('suggestions_not_available' => true, 'insufficient_artwork' => true);
    }
    
    /**
     * Execute Chloe inspiration update task
     */
    private function execute_chloe_inspiration_update($task) {
        $user_id = $task['user_id'];
        
        // Get fresh inspiration from Chloe
        $artist_journey = VORTEX_Artist_Journey::get_instance();
        $inspiration = $artist_journey->get_chloe_inspiration($user_id);
        
        if ($inspiration && !empty($inspiration['ideas'])) {
            $this->send_chloe_inspiration_notification($user_id, $inspiration);
            
            // Deduct tokens for AI service
            $this->deduct_tokens_for_automation($task);
            
            return array(
                'inspiration_sent' => true,
                'ideas_count' => count($inspiration['ideas']),
                'timestamp' => current_time('mysql')
            );
        }
        
        return new WP_Error('inspiration_failed', 'Failed to get inspiration from Chloe');
    }
    
    /**
     * Execute progress celebration task
     */
    private function execute_progress_celebration($task) {
        $params = json_decode($task['task_params'], true);
        $user_id = $task['user_id'];
        $milestone_type = $params['milestone_type'] ?? 'general';
        
        // Send celebration notification
        $this->send_progress_celebration($user_id, $milestone_type, $params);
        
        return array(
            'celebration_sent' => true,
            'milestone_type' => $milestone_type,
            'timestamp' => current_time('mysql')
        );
    }
    
    /**
     * ===== AUTO-CREATION METHODS =====
     */
    
    /**
     * Auto-create onboarding tasks for new users
     */
    public function auto_create_onboarding_tasks($user_id) {
        // Create onboarding reminder task
        $this->create_task(
            $user_id,
            'Onboarding Reminder',
            'onboarding_reminder',
            json_encode(array('target_step' => 'plan_selection')),
            'daily',
            true
        );
        
        // Create seed artwork reminder task
        $this->create_task(
            $user_id,
            'Seed Artwork Upload Reminder',
            'seed_artwork_reminder',
            json_encode(array()),
            'daily',
            true
        );
    }
    
    /**
     * Auto-create plan-specific tasks
     */
    public function auto_create_plan_specific_tasks($user_id, $plan_type) {
        if ($plan_type === 'pro') {
            // Create Horas quiz reminder for Pro users
            $this->create_task(
                $user_id,
                'Horas Business Quiz Reminder',
                'horas_quiz_reminder',
                json_encode(array()),
                'daily',
                true
            );
        }
        
        // Create Pro upgrade suggestion for Starter users
        if ($plan_type === 'starter') {
            $this->create_task(
                $user_id,
                'Pro Upgrade Suggestion',
                'pro_upgrade_suggestion',
                json_encode(array()),
                'weekly',
                true
            );
        }
    }
    
    /**
     * Auto-create milestone reminder tasks
     */
    public function auto_create_milestone_reminders($user_id, $milestone_id) {
        $this->create_task(
            $user_id,
            'Milestone Progress Check',
            'milestone_check',
            json_encode(array('milestone_id' => $milestone_id)),
            'daily',
            true
        );
    }
    
    /**
     * ===== NOTIFICATION HELPER METHODS =====
     */
    
    /**
     * Send onboarding reminder notification
     */
    private function send_onboarding_reminder_notification($user, $target_step) {
        $step_messages = array(
            'plan_selection' => 'Choose your perfect plan and start your artistic journey!',
            'wallet_connection' => 'Connect your wallet to enable blockchain features and TOLA tokens.',
            'role_quiz' => 'Complete your artist profile to get personalized recommendations.',
            'seed_artwork' => 'Upload your seed artwork to unlock AI-powered features.',
            'horas_quiz' => 'Complete your business strategy quiz to access Pro features.'
        );
        
        $message = $step_messages[$target_step] ?? 'Continue your VortexArtec journey!';
        
        $this->add_user_notification($user->ID, array(
            'title' => 'Continue Your Journey',
            'message' => $message,
            'type' => 'onboarding_reminder',
            'action_url' => home_url('/dashboard#' . $target_step)
        ));
    }
    
    /**
     * Send milestone notifications
     */
    private function send_milestone_overdue_notification($user_id, $overdue_milestones) {
        $count = count($overdue_milestones);
        $message = $count === 1 
            ? "You have 1 overdue milestone: " . $overdue_milestones[0]['title']
            : "You have {$count} overdue milestones that need attention.";
        
        $this->add_user_notification($user_id, array(
            'title' => 'Overdue Milestones',
            'message' => $message,
            'type' => 'milestone_overdue',
            'action_url' => home_url('/dashboard#milestones'),
            'urgency' => 'high'
        ));
    }
    
    private function send_milestone_upcoming_notification($user_id, $upcoming_milestones) {
        $count = count($upcoming_milestones);
        $message = $count === 1 
            ? "Reminder: " . $upcoming_milestones[0]['title'] . " is due soon!"
            : "You have {$count} milestones due within 24 hours.";
        
        $this->add_user_notification($user_id, array(
            'title' => 'Upcoming Milestones',
            'message' => $message,
            'type' => 'milestone_upcoming',
            'action_url' => home_url('/dashboard#milestones')
        ));
    }
    
    /**
     * Send Pro upgrade suggestion
     */
    private function send_pro_upgrade_suggestion($user_id, $user_stats) {
        $benefits = array(
            'Unlimited artwork generation',
            'Advanced AI features',
            'Priority support',
            'Analytics dashboard',
            'Business planning tools'
        );
        
        $message = sprintf(
            "Based on your activity (%d artworks created), you might benefit from our Pro plan! %s",
            $user_stats['artwork_count'],
            implode(', ', array_slice($benefits, 0, 3))
        );
        
        $this->add_user_notification($user_id, array(
            'title' => 'Upgrade to Pro?',
            'message' => $message,
            'type' => 'upgrade_suggestion',
            'action_url' => home_url('/dashboard#upgrade'),
            'cta_button' => 'View Pro Benefits'
        ));
    }
    
    /**
     * Send seed artwork reminder
     */
    private function send_seed_artwork_reminder($user_id) {
        $this->add_user_notification($user_id, array(
            'title' => 'Upload Your Seed Artwork',
            'message' => 'Upload your first artwork to unlock AI-powered features and personalized recommendations!',
            'type' => 'seed_artwork_reminder',
            'action_url' => home_url('/dashboard#upload'),
            'cta_button' => 'Upload Now'
        ));
    }
    
    /**
     * Send Horas quiz reminder
     */
    private function send_horas_quiz_reminder($user_id) {
        $this->add_user_notification($user_id, array(
            'title' => 'Complete Your Business Strategy',
            'message' => 'Complete the Horas business quiz to unlock advanced Pro features and get your personalized business plan!',
            'type' => 'horas_quiz_reminder',
            'action_url' => home_url('/dashboard#horas-quiz'),
            'cta_button' => 'Start Quiz'
        ));
    }
    
    /**
     * Send collection suggestion
     */
    private function send_collection_suggestion($user_id, $suggested_collections) {
        $count = count($suggested_collections);
        $message = $count === 1 
            ? "We found a great collection idea based on your artwork: " . $suggested_collections[0]['name']
            : "We found {$count} collection ideas based on your artwork patterns!";
        
        $this->add_user_notification($user_id, array(
            'title' => 'Collection Suggestions',
            'message' => $message,
            'type' => 'collection_suggestion',
            'action_url' => home_url('/dashboard#collections'),
            'cta_button' => 'View Suggestions',
            'data' => $suggested_collections
        ));
    }
    
    /**
     * Send Chloe inspiration notification
     */
    private function send_chloe_inspiration_notification($user_id, $inspiration) {
        $ideas_count = count($inspiration['ideas']);
        $first_idea = $inspiration['ideas'][0] ?? null;
        
        $message = $first_idea 
            ? "New inspiration from Chloe: " . substr($first_idea['title'], 0, 60) . "..."
            : "Chloe has {$ideas_count} new creative inspirations for you!";
        
        $this->add_user_notification($user_id, array(
            'title' => 'Fresh Creative Inspiration',
            'message' => $message,
            'type' => 'chloe_inspiration',
            'action_url' => home_url('/dashboard#inspiration'),
            'cta_button' => 'View Inspiration',
            'data' => $inspiration
        ));
    }
    
    /**
     * Send progress celebration
     */
    private function send_progress_celebration($user_id, $milestone_type, $params) {
        $celebrations = array(
            'first_artwork' => array(
                'title' => '🎉 First Artwork Created!',
                'message' => 'Congratulations on creating your first AI artwork! Your creative journey begins now.',
                'reward' => '10 TOLA bonus'
            ),
            'first_sale' => array(
                'title' => '💰 First Sale Achievement!', 
                'message' => 'Amazing! You just made your first sale. Keep creating and building your portfolio.',
                'reward' => '25 TOLA bonus'
            ),
            'profile_complete' => array(
                'title' => '✅ Profile Complete!',
                'message' => 'Your artist profile is now complete. You\'ll get better recommendations and matches.',
                'reward' => '15 TOLA bonus'
            ),
            'milestone_reached' => array(
                'title' => '🎯 Milestone Achieved!',
                'message' => 'You\'ve reached an important milestone in your artist journey. Keep up the great work!',
                'reward' => '20 TOLA bonus'
            )
        );
        
        $celebration = $celebrations[$milestone_type] ?? $celebrations['milestone_reached'];
        
        $this->add_user_notification($user_id, array(
            'title' => $celebration['title'],
            'message' => $celebration['message'],
            'type' => 'progress_celebration',
            'urgency' => 'celebration',
            'reward' => $celebration['reward'] ?? null
        ));
        
        // Award TOLA tokens for celebration
        if (isset($celebration['reward'])) {
            $amount = intval(str_replace(' TOLA bonus', '', $celebration['reward']));
            $this->award_tola_tokens($user_id, $amount, 'celebration_' . $milestone_type);
        }
    }
    
    /**
     * ===== ANALYSIS HELPER METHODS =====
     */
    
    /**
     * Analyze user activity for upgrade suggestion
     */
    private function analyze_user_activity_for_upgrade($user_id) {
        global $wpdb;
        
        // Get user artwork count
        $artwork_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_author = %d AND post_type = 'vortex_artwork' AND post_status = 'publish'",
            $user_id
        ));
        
        // Get user login frequency (last 30 days)
        $login_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} 
            WHERE user_id = %d AND meta_key = 'vortex_last_login' 
            AND meta_value > %s",
            $user_id,
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));
        
        // Get AI generation usage
        $ai_usage = get_user_meta($user_id, 'vortex_ai_generations_count', true) ?: 0;
        
        // Determine if should suggest upgrade
        $should_suggest = false;
        $reasons = array();
        
        if ($artwork_count >= 5) {
            $should_suggest = true;
            $reasons[] = 'High artwork creation activity';
        }
        
        if ($ai_usage >= 8) {
            $should_suggest = true;
            $reasons[] = 'Frequent AI generation usage';
        }
        
        if ($login_count >= 15) {
            $should_suggest = true;
            $reasons[] = 'High platform engagement';
        }
        
        return array(
            'should_suggest_upgrade' => $should_suggest,
            'artwork_count' => $artwork_count,
            'login_count' => $login_count,
            'ai_usage' => $ai_usage,
            'reasons' => $reasons
        );
    }
    
    /**
     * Analyze user artwork for collection suggestions
     */
    private function analyze_user_artwork_for_collections($user_id) {
        global $wpdb;
        
        // Get user's artworks with metadata
        $artworks = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_title, p.post_date,
                    MAX(CASE WHEN pm.meta_key = 'vortex_artwork_style' THEN pm.meta_value END) as style,
                    MAX(CASE WHEN pm.meta_key = 'vortex_artwork_category' THEN pm.meta_value END) as category,
                    MAX(CASE WHEN pm.meta_key = 'vortex_artwork_tags' THEN pm.meta_value END) as tags
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_author = %d AND p.post_type = 'vortex_artwork' AND p.post_status = 'publish'
            GROUP BY p.ID
            ORDER BY p.post_date DESC
            LIMIT 20",
            $user_id
        ));
        
        if (count($artworks) < 3) {
            return array('suggested_collections' => array());
        }
        
        // Analyze patterns
        $style_groups = array();
        $category_groups = array();
        $date_groups = array();
        
        foreach ($artworks as $artwork) {
            // Group by style
            if ($artwork->style) {
                $style_groups[$artwork->style][] = $artwork;
            }
            
            // Group by category
            if ($artwork->category) {
                $category_groups[$artwork->category][] = $artwork;
            }
            
            // Group by month
            $month = date('Y-m', strtotime($artwork->post_date));
            $date_groups[$month][] = $artwork;
        }
        
        $suggested_collections = array();
        
        // Suggest collections based on style (if 3+ artworks)
        foreach ($style_groups as $style => $style_artworks) {
            if (count($style_artworks) >= 3) {
                $suggested_collections[] = array(
                    'name' => $style . ' Collection',
                    'type' => 'style',
                    'artwork_count' => count($style_artworks),
                    'suggested_artworks' => array_slice($style_artworks, 0, 5)
                );
            }
        }
        
        // Suggest collections based on category (if 3+ artworks)
        foreach ($category_groups as $category => $category_artworks) {
            if (count($category_artworks) >= 3) {
                $suggested_collections[] = array(
                    'name' => $category . ' Series',
                    'type' => 'category',
                    'artwork_count' => count($category_artworks),
                    'suggested_artworks' => array_slice($category_artworks, 0, 5)
                );
            }
        }
        
        // Suggest monthly collections (if 4+ artworks in a month)
        foreach ($date_groups as $month => $month_artworks) {
            if (count($month_artworks) >= 4) {
                $month_name = date('F Y', strtotime($month . '-01'));
                $suggested_collections[] = array(
                    'name' => $month_name . ' Collection',
                    'type' => 'chronological',
                    'artwork_count' => count($month_artworks),
                    'suggested_artworks' => array_slice($month_artworks, 0, 8)
                );
            }
        }
        
        return array('suggested_collections' => array_slice($suggested_collections, 0, 3));
    }
    
    /**
     * Add notification to user's notification queue
     */
    private function add_user_notification($user_id, $notification_data) {
        $notifications = get_user_meta($user_id, 'vortex_ai_notifications', true);
        if (!is_array($notifications)) {
            $notifications = array();
        }
        
        // Add new notification with ID and timestamp
        $notification_data['id'] = uniqid('notify_');
        $notification_data['time'] = current_time('mysql');
        $notification_data['read'] = false;
        
        $notifications[] = $notification_data;
        
        // Limit to 50 notifications
        if (count($notifications) > 50) {
            $notifications = array_slice($notifications, -50);
        }
        
        update_user_meta($user_id, 'vortex_ai_notifications', $notifications);
    }
    
    /**
     * Award TOLA tokens to user
     */
    private function award_tola_tokens($user_id, $amount, $reason) {
        $current_balance = get_user_meta($user_id, 'vortex_tola_balance', true) ?: 0;
        $new_balance = $current_balance + $amount;
        
        update_user_meta($user_id, 'vortex_tola_balance', $new_balance);
        
        // Record transaction
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'vortex_tola_transactions',
            array(
                'user_id' => $user_id,
                'amount' => $amount,
                'type' => 'automation_reward',
                'description' => $reason,
                'balance_after' => $new_balance,
                'created_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * Create a new automation task
     */
    public function create_task($user_id, $task_name, $task_type, $task_params, $frequency = 'daily', $active = true) {
        global $wpdb;
        
        $next_run = $this->calculate_next_run_time(array('frequency' => $frequency));
        
        return $wpdb->insert(
            $wpdb->prefix . 'vortex_automation_tasks',
            array(
                'user_id' => $user_id,
                'task_name' => $task_name,
                'task_type' => $task_type,
                'task_params' => $task_params,
                'frequency' => $frequency,
                'active' => $active ? 1 : 0,
                'created_at' => current_time('mysql'),
                'next_run' => $next_run
            )
        );
    }
} 