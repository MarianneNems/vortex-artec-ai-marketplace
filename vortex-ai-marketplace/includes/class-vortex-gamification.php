<?php
/**
 * The Gamification system class.
 *
 * @since      3.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_Gamification {

    /**
     * Initialize the class and set its properties.
     *
     * @since    3.0.0
     */
    public function __construct() {
        $this->init_gamification_hooks();
        add_action('init', array($this, 'create_events_table'));
    }

    /**
     * Initialize gamification hooks
     *
     * @since    3.0.0
     */
    private function init_gamification_hooks() {
        // Hook into various actions
        add_action('vortex_seed_uploaded', array($this, 'handle_seed_upload_reward'));
        add_action('vortex_art_generated', array($this, 'handle_generation_reward'));
        add_action('woocommerce_order_status_completed', array($this, 'handle_sale_completion_reward'));
        add_action('vortex_plan_activated', array($this, 'handle_plan_activation_reward'), 10, 2);
        
        // Achievement hooks
        add_action('wp_ajax_vortex_check_achievements', array($this, 'ajax_check_achievements'));
        add_action('wp_ajax_vortex_claim_achievement', array($this, 'ajax_claim_achievement'));
    }

    /**
     * Create events tracking table
     *
     * @since    3.0.0
     */
    public function create_events_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_events';
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            event_type varchar(50) NOT NULL,
            event_data text DEFAULT NULL,
            tokens_awarded int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Award tokens to user
     *
     * @since    3.0.0
     * @param    int       $user_id       User ID
     * @param    int       $amount        Token amount
     * @param    string    $event_type    Event type
     * @param    array     $event_data    Additional event data
     * @return   bool                     Success status
     */
    public function award_tokens($user_id, $amount, $event_type, $event_data = array()) {
        // Credit tokens using wallet system
        if (class_exists('Vortex_AI_Marketplace_Wallet')) {
            $wallet = new Vortex_AI_Marketplace_Wallet();
            $success = $wallet->credit_tokens($user_id, $amount);
            
            if ($success) {
                // Record the event
                $this->record_event($user_id, $event_type, $event_data, $amount);
                
                // Check for achievements
                $this->check_achievements($user_id, $event_type);
                
                return true;
            }
        }
        
        return false;
    }

    /**
     * Record gamification event
     *
     * @since    3.0.0
     * @param    int       $user_id       User ID
     * @param    string    $event_type    Event type
     * @param    array     $event_data    Event data
     * @param    int       $tokens        Tokens awarded
     */
    private function record_event($user_id, $event_type, $event_data, $tokens) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_events';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'event_type' => $event_type,
                'event_data' => json_encode($event_data),
                'tokens_awarded' => $tokens,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%d', '%s')
        );
    }

    /**
     * Handle seed upload reward
     *
     * @since    3.0.0
     * @param    int    $user_id    User ID
     */
    public function handle_seed_upload_reward($user_id) {
        // Check if this is first upload for extra bonus
        $upload_count = $this->get_event_count($user_id, 'seed_upload');
        
        if ($upload_count === 0) {
            // First upload bonus
            $this->award_tokens($user_id, 10, 'seed_upload', array(
                'first_upload' => true,
                'bonus' => 'first_time'
            ));
        } else {
            // Regular upload reward
            $this->award_tokens($user_id, 1, 'seed_upload');
        }
    }

    /**
     * Handle art generation reward
     *
     * @since    3.0.0
     * @param    int    $user_id    User ID
     */
    public function handle_generation_reward($user_id) {
        // Note: This is called AFTER token deduction for generation
        // Check for achievement milestones
        $generation_count = get_user_meta($user_id, 'vortex_total_generations', true) ?: 0;
        
        $milestones = array(1, 10, 50, 100, 500);
        
        if (in_array($generation_count, $milestones)) {
            $bonus_amount = $generation_count * 2; // 2x generation count as bonus
            $this->award_tokens($user_id, $bonus_amount, 'generation_milestone', array(
                'milestone' => $generation_count,
                'bonus_amount' => $bonus_amount
            ));
        }
    }

    /**
     * Handle sale completion reward
     *
     * @since    3.0.0
     * @param    int    $order_id    WooCommerce order ID
     */
    public function handle_sale_completion_reward($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Check if this is an artwork sale (not subscription)
        $is_artwork_sale = false;
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $product_type = get_post_meta($product_id, '_vortex_product_type', true);
            
            if ($product_type === 'artwork' || $product_type === 'nft') {
                $is_artwork_sale = true;
                break;
            }
        }

        if ($is_artwork_sale) {
            $seller_id = $order->get_meta('_vortex_seller_id');
            if ($seller_id) {
                $order_total = $order->get_total();
                $bonus_tokens = max(5, min(50, floor($order_total * 0.1))); // 10% of sale as tokens, min 5, max 50
                
                $this->award_tokens($seller_id, $bonus_tokens, 'sale_completion', array(
                    'order_id' => $order_id,
                    'order_total' => $order_total,
                    'bonus_tokens' => $bonus_tokens
                ));
            }
        }
    }

    /**
     * Handle plan activation reward
     *
     * @since    3.0.0
     * @param    int       $user_id       User ID
     * @param    string    $plan_type     Plan type
     */
    public function handle_plan_activation_reward($user_id, $plan_type) {
        $plan_bonuses = array(
            'artist-starter' => 25,
            'artist-pro' => 50,
            'artist-studio' => 100
        );

        $bonus_amount = $plan_bonuses[$plan_type] ?? 25;
        
        $this->award_tokens($user_id, $bonus_amount, 'plan_activation', array(
            'plan_type' => $plan_type,
            'bonus_amount' => $bonus_amount
        ));
    }

    /**
     * Get event count for user
     *
     * @since    3.0.0
     * @param    int       $user_id       User ID
     * @param    string    $event_type    Event type
     * @return   int                      Event count
     */
    private function get_event_count($user_id, $event_type) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_events';
        
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND event_type = %s",
                $user_id,
                $event_type
            )
        );
    }

    /**
     * Check for achievements
     *
     * @since    3.0.0
     * @param    int       $user_id       User ID
     * @param    string    $event_type    Triggering event type
     */
    private function check_achievements($user_id, $event_type) {
        $achievements = $this->get_achievement_definitions();
        $user_achievements = get_user_meta($user_id, 'vortex_achievements', true) ?: array();

        foreach ($achievements as $achievement_id => $achievement) {
            // Skip if already earned
            if (in_array($achievement_id, $user_achievements)) {
                continue;
            }

            // Check if this event type is relevant
            if (!in_array($event_type, $achievement['trigger_events'])) {
                continue;
            }

            // Check achievement condition
            if ($this->check_achievement_condition($user_id, $achievement)) {
                $this->award_achievement($user_id, $achievement_id, $achievement);
            }
        }
    }

    /**
     * Get achievement definitions
     *
     * @since    3.0.0
     * @return   array    Achievement definitions
     */
    private function get_achievement_definitions() {
        return array(
            'first_steps' => array(
                'name' => 'First Steps',
                'description' => 'Upload your first seed artwork',
                'icon' => '🎨',
                'condition' => array('type' => 'event_count', 'event' => 'seed_upload', 'count' => 1),
                'reward' => 10,
                'trigger_events' => array('seed_upload')
            ),
            'prolific_creator' => array(
                'name' => 'Prolific Creator',
                'description' => 'Generate 50 AI artworks',
                'icon' => '🏭',
                'condition' => array('type' => 'meta_value', 'meta_key' => 'vortex_total_generations', 'value' => 50),
                'reward' => 100,
                'trigger_events' => array('generation_milestone')
            ),
            'entrepreneur' => array(
                'name' => 'Entrepreneur',
                'description' => 'Complete your first sale',
                'icon' => '💰',
                'condition' => array('type' => 'event_count', 'event' => 'sale_completion', 'count' => 1),
                'reward' => 25,
                'trigger_events' => array('sale_completion')
            ),
            'committed_artist' => array(
                'name' => 'Committed Artist',
                'description' => 'Subscribe to any plan',
                'icon' => '⭐',
                'condition' => array('type' => 'event_count', 'event' => 'plan_activation', 'count' => 1),
                'reward' => 50,
                'trigger_events' => array('plan_activation')
            ),
            'seed_collector' => array(
                'name' => 'Seed Collector',
                'description' => 'Upload 10 seed artworks',
                'icon' => '🌱',
                'condition' => array('type' => 'event_count', 'event' => 'seed_upload', 'count' => 10),
                'reward' => 50,
                'trigger_events' => array('seed_upload')
            )
        );
    }

    /**
     * Check achievement condition
     *
     * @since    3.0.0
     * @param    int      $user_id        User ID
     * @param    array    $achievement    Achievement definition
     * @return   bool                     Condition met
     */
    private function check_achievement_condition($user_id, $achievement) {
        $condition = $achievement['condition'];

        switch ($condition['type']) {
            case 'event_count':
                $count = $this->get_event_count($user_id, $condition['event']);
                return $count >= $condition['count'];

            case 'meta_value':
                $value = get_user_meta($user_id, $condition['meta_key'], true) ?: 0;
                return $value >= $condition['value'];

            default:
                return false;
        }
    }

    /**
     * Award achievement to user
     *
     * @since    3.0.0
     * @param    int       $user_id          User ID
     * @param    string    $achievement_id   Achievement ID
     * @param    array     $achievement      Achievement definition
     */
    private function award_achievement($user_id, $achievement_id, $achievement) {
        // Add to user achievements
        $user_achievements = get_user_meta($user_id, 'vortex_achievements', true) ?: array();
        $user_achievements[] = $achievement_id;
        update_user_meta($user_id, 'vortex_achievements', $user_achievements);

        // Award tokens
        $this->award_tokens($user_id, $achievement['reward'], 'achievement', array(
            'achievement_id' => $achievement_id,
            'achievement_name' => $achievement['name']
        ));

        // Trigger notification action
        do_action('vortex_achievement_earned', $user_id, $achievement_id, $achievement);
    }

    /**
     * Get user achievements
     *
     * @since    3.0.0
     * @param    int    $user_id    User ID
     * @return   array              User achievements with progress
     */
    public function get_user_achievements($user_id) {
        $achievements = $this->get_achievement_definitions();
        $user_achievements = get_user_meta($user_id, 'vortex_achievements', true) ?: array();
        $result = array();

        foreach ($achievements as $achievement_id => $achievement) {
            $earned = in_array($achievement_id, $user_achievements);
            $progress = 0;

            if (!$earned) {
                // Calculate progress
                $condition = $achievement['condition'];
                switch ($condition['type']) {
                    case 'event_count':
                        $current = $this->get_event_count($user_id, $condition['event']);
                        $progress = min(100, ($current / $condition['count']) * 100);
                        break;
                    case 'meta_value':
                        $current = get_user_meta($user_id, $condition['meta_key'], true) ?: 0;
                        $progress = min(100, ($current / $condition['value']) * 100);
                        break;
                }
            } else {
                $progress = 100;
            }

            $result[] = array(
                'id' => $achievement_id,
                'name' => $achievement['name'],
                'description' => $achievement['description'],
                'icon' => $achievement['icon'],
                'reward' => $achievement['reward'],
                'earned' => $earned,
                'progress' => round($progress, 1)
            );
        }

        return $result;
    }

    /**
     * Get user event history
     *
     * @since    3.0.0
     * @param    int    $user_id    User ID
     * @param    int    $limit      Number of events to retrieve
     * @return   array              Event history
     */
    public function get_user_event_history($user_id, $limit = 50) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_events';
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name 
                 WHERE user_id = %d 
                 ORDER BY created_at DESC 
                 LIMIT %d",
                $user_id,
                $limit
            ),
            ARRAY_A
        );

        // Decode event_data JSON
        foreach ($results as &$event) {
            $event['event_data'] = json_decode($event['event_data'], true) ?: array();
        }

        return $results;
    }

    /**
     * AJAX handler for checking achievements
     *
     * @since    3.0.0
     */
    public function ajax_check_achievements() {
        check_ajax_referer('wp_rest', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }

        $achievements = $this->get_user_achievements($user_id);
        
        wp_send_json_success(array(
            'achievements' => $achievements,
            'total_earned' => count(array_filter($achievements, function($a) { return $a['earned']; }))
        ));
    }

    /**
     * AJAX handler for claiming achievement rewards
     *
     * @since    3.0.0
     */
    public function ajax_claim_achievement() {
        check_ajax_referer('wp_rest', 'nonce');

        $user_id = get_current_user_id();
        $achievement_id = sanitize_text_field($_POST['achievement_id']);

        if (!$user_id || !$achievement_id) {
            wp_send_json_error('Invalid request');
        }

        // This is a placeholder - achievements are auto-claimed when earned
        // Could be extended for manual claiming system
        
        wp_send_json_success(array(
            'message' => 'Achievement already claimed automatically'
        ));
    }

    /**
     * Get user statistics
     *
     * @since    3.0.0
     * @param    int    $user_id    User ID
     * @return   array              User statistics
     */
    public function get_user_stats($user_id) {
        $stats = array(
            'total_events' => $this->get_event_count($user_id, ''),
            'seed_uploads' => $this->get_event_count($user_id, 'seed_upload'),
            'generations' => get_user_meta($user_id, 'vortex_total_generations', true) ?: 0,
            'sales' => $this->get_event_count($user_id, 'sale_completion'),
            'achievements_earned' => count(get_user_meta($user_id, 'vortex_achievements', true) ?: array()),
            'tokens_earned' => $this->get_total_tokens_earned($user_id),
            'member_since' => get_user_meta($user_id, 'vortex_member_since', true) ?: get_userdata($user_id)->user_registered
        );

        return $stats;
    }

    /**
     * Get total tokens earned by user
     *
     * @since    3.0.0
     * @param    int    $user_id    User ID
     * @return   int                Total tokens earned
     */
    private function get_total_tokens_earned($user_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_events';
        
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(tokens_awarded) FROM $table_name WHERE user_id = %d",
                $user_id
            )
        );
    }
} 