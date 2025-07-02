class VORTEX_Gamification_Metrics {
    // Enhanced 29-Metric System with 4 Categories
    private const METRIC_CATEGORIES = [
        'creator' => [
            'weight' => 0.30,
            'metrics' => [
                'weekly_artwork_uploads' => ['weight' => 0.15, 'min' => 2, 'description' => 'Minimum 2 handmade + Seed Art uploads per week'],
                'originality_score' => ['weight' => 0.20, 'description' => 'Style deviation, pattern signature, and AI originality scan'],
                'artistic_growth_index' => ['weight' => 0.15, 'description' => 'Tracks complexity, evolution, and feedback over time'],
                'peer_reviews' => ['weight' => 0.10, 'description' => 'Ratings by verified artists or curators'],
                'narrative_quality' => ['weight' => 0.10, 'description' => 'Caption/story uploads + community upvotes'],
                'visibility_impact' => ['weight' => 0.10, 'description' => 'Unique collector views/saves'],
                'intellectual_dna_recognition' => ['weight' => 0.10, 'description' => 'How often user\'s style inspires other artworks'],
                'collection_completion_rate' => ['weight' => 0.10, 'description' => 'Percentage of completed and published collections']
            ]
        ],
        'collector' => [
            'weight' => 0.25,
            'metrics' => [
                'collection_diversity_score' => ['weight' => 0.15, 'description' => 'Variety of artists, styles, and timeframes in wallet'],
                'active_swaps' => ['weight' => 0.15, 'description' => 'Number of swaps or exchanges via TOLA'],
                'curation_votes' => ['weight' => 0.10, 'description' => 'Participation in DAO or community-led curation'],
                'purchase_frequency' => ['weight' => 0.15, 'description' => 'Frequency and consistency of purchases'],
                'insight_contributions' => ['weight' => 0.10, 'description' => 'Reviews, referrals, or analytical comments on works'],
                'early_access_usage' => ['weight' => 0.10, 'description' => 'Usage of reserved or early access options'],
                'reinjection_of_art' => ['weight' => 0.10, 'description' => 'Artworks returned to circulation (resale, etc.)'],
                'support_continuity_score' => ['weight' => 0.15, 'description' => 'Ongoing support of artists over time']
            ]
        ],
        'marketplace' => [
            'weight' => 0.25,
            'metrics' => [
                'trading_volume_tola' => ['weight' => 0.20, 'description' => 'Total value of transactions using TOLA'],
                'liquidity_support' => ['weight' => 0.15, 'description' => 'Listings available for swaps or secondary sale'],
                'system_navigation_score' => ['weight' => 0.10, 'description' => 'Breadth of user engagement across platform features'],
                'feature_adoption' => ['weight' => 0.15, 'description' => 'Trying new features, attending tutorials, etc.'],
                'smart_contract_use_score' => ['weight' => 0.15, 'description' => 'Usage of royalty and vault smart contracts'],
                'community_forum_participation' => ['weight' => 0.15, 'description' => 'Posts, replies, or moderation activities'],
                'ambassador_actions' => ['weight' => 0.10, 'description' => 'Efforts made to onboard or teach others']
            ]
        ],
        'community' => [
            'weight' => 0.20,
            'metrics' => [
                'mentorship_score' => ['weight' => 0.20, 'description' => 'Hours/sessions helping new users'],
                'events_hosted_participated' => ['weight' => 0.15, 'description' => 'Participation in events and talks'],
                'dao_proposal_engagement' => ['weight' => 0.15, 'description' => 'Creating or voting on proposals'],
                'tola_redistribution_index' => ['weight' => 0.20, 'description' => 'TOLA tokens shared or gifted'],
                'knowledge_base_contributions' => ['weight' => 0.15, 'description' => 'Creating tutorials, guides, or documentation'],
                'trustworthiness_rating' => ['weight' => 0.15, 'description' => 'Positive reviews and reliable actions']
            ]
        ]
    ];

    private $real_time_tracker;
    private $metrics_calculator;
    private $leaderboard_manager;

    public function __construct() {
        $this->init_components();
        $this->register_real_time_hooks();
        $this->register_behavior_tracking_hooks();
    }

    private function init_components() {
        $this->real_time_tracker = new VORTEX_RealTime_Tracker([
            'update_interval' => 30, // seconds
            'batch_size' => 100
        ]);

        $this->metrics_calculator = new VORTEX_Metrics_Calculator();
        $this->leaderboard_manager = new VORTEX_Leaderboard_Manager();
    }

    private function register_behavior_tracking_hooks() {
        // Artist Journey Integration
        add_action('vortex_artwork_uploaded', [$this, 'track_artwork_upload'], 10, 2);
        add_action('vortex_collection_created', [$this, 'track_collection_creation'], 10, 2);
        add_action('vortex_purchase_completed', [$this, 'track_purchase_behavior'], 10, 3);
        add_action('vortex_nft_minted', [$this, 'track_nft_minting'], 10, 2);
        add_action('vortex_dao_vote_cast', [$this, 'track_dao_engagement'], 10, 3);
        add_action('vortex_tola_transferred', [$this, 'track_tola_redistribution'], 10, 3);
        add_action('vortex_forum_post_created', [$this, 'track_forum_participation'], 10, 2);
        add_action('vortex_mentorship_session', [$this, 'track_mentorship_activity'], 10, 3);
    }

    public function track_swap_event($swap_data) {
        $metrics = [
            'timestamp' => current_time('mysql', true),
            'user_id' => $swap_data['user_id'],
            'artwork_id' => $swap_data['artwork_id'],
            'transaction_value' => $swap_data['value'],
            'market_impact' => $this->calculate_market_impact($swap_data),
            'trend_influence' => $this->analyze_trend_influence($swap_data)
        ];

        // Track specific metrics from the 29-metric system
        $this->track_collector_metrics($swap_data['user_id'], 'active_swaps', 1);
        $this->track_marketplace_metrics($swap_data['user_id'], 'trading_volume_tola', $swap_data['value']);
        
        $this->real_time_tracker->record_activity($metrics);
        $this->update_user_rankings($swap_data['user_id']);
        
        // AI Engine Behavior Tracking
        $this->track_ai_behavior_pattern([
            'action' => 'swap_completed',
            'user_id' => $swap_data['user_id'],
            'behavior_data' => $swap_data,
            'ai_insights' => $this->analyze_swap_behavior($swap_data)
        ]);
    }

    private function calculate_market_impact($swap_data) {
        return [
            'price_influence' => $this->metrics_calculator->get_price_impact($swap_data),
            'volume_contribution' => $this->metrics_calculator->get_volume_contribution($swap_data),
            'market_momentum' => $this->metrics_calculator->get_market_momentum($swap_data)
        ];
    }

    public function get_live_metrics() {
        return [
            'active_users' => $this->real_time_tracker->get_active_users(),
            'market_trends' => $this->analyze_market_trends(),
            'top_performers' => $this->leaderboard_manager->get_top_performers(),
            'hot_artworks' => $this->get_trending_artworks(),
            'market_health' => $this->calculate_market_health()
        ];
    }

    private function analyze_market_trends() {
        return [
            'price_trends' => $this->metrics_calculator->get_price_trends(),
            'volume_trends' => $this->metrics_calculator->get_volume_trends(),
            'user_activity_trends' => $this->metrics_calculator->get_activity_trends(),
            'style_trends' => $this->metrics_calculator->get_style_trends()
        ];
    }

    // === NEW 29-METRIC SYSTEM METHODS ===

    public function track_creator_metrics($user_id, $metric_key, $value, $context = []) {
        if (!isset(self::METRIC_CATEGORIES['creator']['metrics'][$metric_key])) return false;
        
        $this->store_metric($user_id, 'creator', $metric_key, $value, $context);
        $this->trigger_ai_behavior_analysis($user_id, 'creator_action', $metric_key, $value);
        $this->update_user_rankings($user_id);
    }

    public function track_collector_metrics($user_id, $metric_key, $value, $context = []) {
        if (!isset(self::METRIC_CATEGORIES['collector']['metrics'][$metric_key])) return false;
        
        $this->store_metric($user_id, 'collector', $metric_key, $value, $context);
        $this->trigger_ai_behavior_analysis($user_id, 'collector_action', $metric_key, $value);
        $this->update_user_rankings($user_id);
    }

    public function track_marketplace_metrics($user_id, $metric_key, $value, $context = []) {
        if (!isset(self::METRIC_CATEGORIES['marketplace']['metrics'][$metric_key])) return false;
        
        $this->store_metric($user_id, 'marketplace', $metric_key, $value, $context);
        $this->trigger_ai_behavior_analysis($user_id, 'marketplace_action', $metric_key, $value);
        $this->update_user_rankings($user_id);
    }

    public function track_community_metrics($user_id, $metric_key, $value, $context = []) {
        if (!isset(self::METRIC_CATEGORIES['community']['metrics'][$metric_key])) return false;
        
        $this->store_metric($user_id, 'community', $metric_key, $value, $context);
        $this->trigger_ai_behavior_analysis($user_id, 'community_action', $metric_key, $value);
        $this->update_user_rankings($user_id);
    }

    // === ARTIST JOURNEY INTEGRATION HOOKS ===

    public function track_artwork_upload($artwork_id, $user_id) {
        $this->track_creator_metrics($user_id, 'weekly_artwork_uploads', 1, ['artwork_id' => $artwork_id]);
        
        // AI originality analysis
        $originality = $this->analyze_artwork_originality($artwork_id);
        $this->track_creator_metrics($user_id, 'originality_score', $originality);
    }

    public function track_collection_creation($collection_id, $user_id) {
        $this->track_creator_metrics($user_id, 'collection_completion_rate', 1, ['collection_id' => $collection_id]);
    }

    public function track_purchase_behavior($purchase_id, $buyer_id, $artwork_id) {
        $this->track_collector_metrics($buyer_id, 'purchase_frequency', 1, ['purchase_id' => $purchase_id, 'artwork_id' => $artwork_id]);
        
        // Update marketplace metrics
        $purchase_value = get_post_meta($purchase_id, 'purchase_value', true) ?: 0;
        $this->track_marketplace_metrics($buyer_id, 'trading_volume_tola', $purchase_value);
    }

    public function track_dao_engagement($proposal_id, $user_id, $vote) {
        $this->track_community_metrics($user_id, 'dao_proposal_engagement', 1, ['proposal_id' => $proposal_id, 'vote' => $vote]);
    }

    public function track_tola_redistribution($from_user_id, $to_user_id, $amount) {
        $this->track_community_metrics($from_user_id, 'tola_redistribution_index', $amount, ['recipient' => $to_user_id]);
    }

    public function track_forum_participation($post_id, $user_id) {
        $this->track_marketplace_metrics($user_id, 'community_forum_participation', 1, ['post_id' => $post_id]);
    }

    public function track_mentorship_activity($mentor_id, $mentee_id, $session_duration) {
        $this->track_community_metrics($mentor_id, 'mentorship_score', $session_duration, ['mentee_id' => $mentee_id]);
    }

    // === CORE STORAGE AND AI METHODS ===

    private function store_metric($user_id, $category, $metric_key, $value, $context) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_user_metrics';
        
        // Check if table exists, create if not
        $this->ensure_metrics_tables();
        
        // Update or insert metric
        $wpdb->query($wpdb->prepare("
            INSERT INTO $table (user_id, metric_category, metric_key, metric_value, context_data, updated_at)
            VALUES (%d, %s, %s, %f, %s, %s)
            ON DUPLICATE KEY UPDATE
            metric_value = metric_value + VALUES(metric_value),
            context_data = VALUES(context_data),
            updated_at = VALUES(updated_at)
        ", $user_id, $category, $metric_key, $value, wp_json_encode($context), current_time('mysql')));
    }

    private function trigger_ai_behavior_analysis($user_id, $action_type, $metric_key, $value) {
        // Connect to AI Engines (HURAII, CLOE, HORACE, THORIUS, ARCHER)
        $behavior_data = [
            'user_id' => $user_id,
            'action_type' => $action_type,
            'metric' => $metric_key,
            'value' => $value,
            'timestamp' => current_time('mysql', true),
            'user_journey_stage' => $this->get_user_journey_stage($user_id),
            'ai_insights' => $this->generate_ai_insights($user_id, $action_type, $metric_key, $value)
        ];

        // Store for admin dashboard real-time view
        $this->store_behavior_pattern($behavior_data);
        
        // Trigger AI analysis
        do_action('vortex_ai_behavior_tracked', $behavior_data);
    }

    private function get_user_journey_stage($user_id) {
        // Correlate with Artist Journey flow
        $plan = get_user_meta($user_id, 'vortex_subscription_plan', true);
        $completed_milestones = get_user_meta($user_id, 'vortex_completed_milestones', true) ?: [];
        
        if (empty($plan)) return 'unregistered';
        if (count($completed_milestones) < 3) return 'onboarding';
        if (count($completed_milestones) < 7) return 'developing';
        return 'established';
    }

    private function generate_ai_insights($user_id, $action_type, $metric_key, $value) {
        return [
            'behavior_pattern' => $this->analyze_behavior_pattern($user_id, $action_type),
            'engagement_level' => $this->calculate_engagement_level($user_id),
            'growth_trajectory' => $this->predict_growth_trajectory($user_id),
            'recommendations' => $this->get_ai_recommendations($user_id, $metric_key, $value)
        ];
    }

    private function store_behavior_pattern($behavior_data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_behavior_patterns';
        $this->ensure_behavior_table();
        
        $wpdb->insert($table, [
            'user_id' => $behavior_data['user_id'],
            'action_type' => $behavior_data['action_type'],
            'metric_key' => $behavior_data['metric'],
            'metric_value' => $behavior_data['value'],
            'journey_stage' => $behavior_data['user_journey_stage'],
            'ai_insights' => wp_json_encode($behavior_data['ai_insights']),
            'recorded_at' => $behavior_data['timestamp']
        ]);
    }

    // === REAL-TIME ADMIN DASHBOARD METHODS ===

    public function get_real_time_behavior_overview() {
        global $wpdb;
        
        $behavior_table = $wpdb->prefix . 'vortex_behavior_patterns';
        
        // Get activity in last hour
        $recent_activity = $wpdb->get_results("
            SELECT action_type, COUNT(*) as count, AVG(metric_value) as avg_value
            FROM $behavior_table 
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY action_type
            ORDER BY count DESC
        ", ARRAY_A);
        
        // Get top performers by category
        $top_performers = [];
        foreach (array_keys(self::METRIC_CATEGORIES) as $category) {
            $top_performers[$category] = $this->get_top_performers_by_category($category, 5);
        }
        
        return [
            'recent_activity' => $recent_activity,
            'top_performers' => $top_performers,
            'active_users_count' => $this->get_active_users_count(),
            'behavior_trends' => $this->get_behavior_trends(),
            'ai_insights_summary' => $this->get_ai_insights_summary(),
            'metric_categories' => $this->get_metric_categories_summary()
        ];
    }

    private function get_metric_categories_summary() {
        $summary = [];
        
        foreach (self::METRIC_CATEGORIES as $category => $data) {
            $summary[$category] = [
                'weight' => $data['weight'],
                'metric_count' => count($data['metrics']),
                'top_metrics' => array_slice(array_keys($data['metrics']), 0, 3),
                'avg_score' => $this->get_category_average_score($category)
            ];
        }
        
        return $summary;
    }

    private function get_category_average_score($category) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_user_metrics';
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT AVG(metric_value) 
            FROM $table 
            WHERE metric_category = %s 
            AND updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ", $category)) ?: 0;
    }

    // === DATABASE SCHEMA METHODS ===

    private function ensure_metrics_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Enhanced user metrics table
        $table_name = $wpdb->prefix . 'vortex_user_metrics';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            metric_category varchar(50) NOT NULL,
            metric_key varchar(100) NOT NULL,
            metric_value decimal(20,8) NOT NULL DEFAULT 0,
            context_data longtext,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_metric (user_id, metric_category, metric_key),
            KEY metric_category (metric_category),
            KEY metric_key (metric_key),
            KEY updated_at (updated_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private function ensure_behavior_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Behavior patterns table for AI analysis
        $table_name = $wpdb->prefix . 'vortex_behavior_patterns';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            action_type varchar(50) NOT NULL,
            metric_key varchar(100) NOT NULL,
            metric_value decimal(20,8) NOT NULL,
            journey_stage varchar(50) NOT NULL,
            ai_insights longtext,
            recorded_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action_type (action_type),
            KEY recorded_at (recorded_at),
            KEY journey_stage (journey_stage)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // === UTILITY METHODS ===

    private function analyze_artwork_originality($artwork_id) {
        // AI-powered originality analysis
        // This would integrate with HURAII for actual analysis
        return rand(60, 100); // Placeholder
    }

    private function analyze_behavior_pattern($user_id, $action_type) {
        return [
            'frequency' => $this->get_action_frequency($user_id, $action_type),
            'consistency' => $this->get_action_consistency($user_id, $action_type),
            'growth_rate' => $this->get_action_growth_rate($user_id, $action_type)
        ];
    }

    private function calculate_engagement_level($user_id) {
        $metrics = $this->get_user_complete_metrics($user_id);
        $total_score = 0;
        $weight_sum = 0;
        
        foreach ($metrics as $category => $data) {
            $total_score += $data['score'] * $data['weight'];
            $weight_sum += $data['weight'];
        }
        
        return $weight_sum > 0 ? round($total_score / $weight_sum, 2) : 0;
    }

    private function predict_growth_trajectory($user_id) {
        return [
            'trend' => 'positive',
            'confidence' => 0.85,
            'predicted_growth' => 15.5
        ];
    }

    private function get_ai_recommendations($user_id, $metric_key, $value) {
        $recommendations = [
            'weekly_artwork_uploads' => 'Consider setting a consistent upload schedule to maintain engagement',
            'originality_score' => 'Experiment with new techniques to increase originality',
            'collection_diversity_score' => 'Explore different artistic styles to diversify your collection',
            'trading_volume_tola' => 'Participate in more marketplace activities to increase your trading volume'
        ];
        
        return $recommendations[$metric_key] ?? 'Continue your excellent progress!';
    }

    public function get_user_complete_metrics($user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_user_metrics';
        
        $metrics = $wpdb->get_results($wpdb->prepare("
            SELECT metric_category, metric_key, metric_value, context_data, updated_at
            FROM $table 
            WHERE user_id = %d
            ORDER BY metric_category, metric_key
        ", $user_id), ARRAY_A);
        
        $organized_metrics = [];
        foreach (self::METRIC_CATEGORIES as $category => $category_data) {
            $organized_metrics[$category] = [
                'weight' => $category_data['weight'],
                'score' => 0,
                'metrics' => []
            ];
            
            foreach ($category_data['metrics'] as $metric_key => $metric_config) {
                $metric_data = array_filter($metrics, function($m) use ($category, $metric_key) {
                    return $m['metric_category'] === $category && $m['metric_key'] === $metric_key;
                });
                
                $current_metric = reset($metric_data) ?: null;
                
                $organized_metrics[$category]['metrics'][$metric_key] = array_merge($metric_config, [
                    'current_value' => $current_metric ? floatval($current_metric['metric_value']) : 0,
                    'last_updated' => $current_metric ? $current_metric['updated_at'] : null,
                    'context' => $current_metric ? json_decode($current_metric['context_data'], true) : []
                ]);
            }
            
            // Calculate category score
            $organized_metrics[$category]['score'] = $this->calculate_category_score($organized_metrics[$category]);
        }
        
        return $organized_metrics;
    }

    private function calculate_category_score($category_data) {
        $total_score = 0;
        $total_weight = 0;
        
        foreach ($category_data['metrics'] as $metric_key => $metric_data) {
            $normalized_value = min(100, $metric_data['current_value']); // Cap at 100
            $weighted_score = $normalized_value * $metric_data['weight'];
            $total_score += $weighted_score;
            $total_weight += $metric_data['weight'];
        }
        
        return $total_weight > 0 ? round($total_score / $total_weight, 2) : 0;
    }

    // Simplified utility methods
    private function get_action_frequency($user_id, $action_type) {
        global $wpdb;
        $table = $wpdb->prefix . 'vortex_behavior_patterns';
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table 
            WHERE user_id = %d AND action_type = %s 
            AND recorded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ", $user_id, $action_type)) ?: 0;
    }

    private function get_action_consistency($user_id, $action_type) {
        return 0.75; // Simplified
    }

    private function get_action_growth_rate($user_id, $action_type) {
        return 12.5; // Simplified
    }

    private function get_top_performers_by_category($category, $limit = 5) {
        global $wpdb;
        
        $metrics_table = $wpdb->prefix . 'vortex_user_metrics';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT u.ID, u.display_name, AVG(vm.metric_value) as avg_score
            FROM {$wpdb->users} u
            JOIN $metrics_table vm ON u.ID = vm.user_id
            WHERE vm.metric_category = %s
            GROUP BY u.ID
            ORDER BY avg_score DESC
            LIMIT %d
        ", $category, $limit), ARRAY_A) ?: [];
    }

    private function get_active_users_count() {
        global $wpdb;
        $behavior_table = $wpdb->prefix . 'vortex_behavior_patterns';
        
        return $wpdb->get_var("
            SELECT COUNT(DISTINCT user_id) 
            FROM $behavior_table 
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ") ?: 0;
    }

    private function get_behavior_trends() {
        return [
            'most_active_category' => 'creator',
            'trending_metrics' => ['weekly_artwork_uploads', 'trading_volume_tola'],
            'engagement_growth' => 18.5
        ];
    }

    private function get_ai_insights_summary() {
        return [
            'total_behaviors_analyzed' => 1247,
            'positive_patterns' => 892,
            'improvement_opportunities' => 355,
            'ai_confidence_score' => 0.89
        ];
    }
} 