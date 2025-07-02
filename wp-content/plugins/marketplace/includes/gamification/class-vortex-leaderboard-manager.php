class VORTEX_Leaderboard_Manager {
    private const LEADERBOARD_CATEGORIES = [
        'top_traders',
        'top_artists',
        'top_innovators',
        'trending_creators',
        'community_leaders',
        // Enhanced 29-Metric Categories
        'top_creators',
        'top_collectors',
        'marketplace_leaders',
        'community_champions'
    ];

    // 29-Metric System Integration
    private const METRIC_WEIGHTS = [
        'creator' => 0.30,
        'collector' => 0.25,
        'marketplace' => 0.25,
        'community' => 0.20
    ];

    public function update_rankings($user_id, $metrics) {
        global $wpdb;

        $score = $this->calculate_composite_score($metrics);
        
        $wpdb->replace(
            $wpdb->prefix . 'vortex_user_rankings',
            [
                'user_id' => $user_id,
                'composite_score' => $score,
                'ranking_data' => wp_json_encode($metrics),
                'last_updated' => current_time('mysql')
            ]
        );

        $this->update_leaderboards($user_id, $score, $metrics);
        
        // Update category-specific rankings
        $this->update_category_rankings($user_id, $metrics);
        
        // Track ranking changes for AI analysis
        $this->track_ranking_changes($user_id, $score, $metrics);
    }

    public function get_user_stats($user_id) {
        return [
            'ranking' => $this->get_user_ranking($user_id),
            'achievements' => $this->get_user_achievements($user_id),
            'influence_score' => $this->calculate_influence_score($user_id),
            'activity_history' => $this->get_activity_history($user_id),
            // Enhanced stats for 29-metric system
            'category_rankings' => $this->get_user_category_rankings($user_id),
            'metric_breakdown' => $this->get_user_metric_breakdown($user_id),
            'growth_trajectory' => $this->get_user_growth_trajectory($user_id),
            'ai_insights' => $this->get_user_ai_insights($user_id)
        ];
    }

    // === 29-METRIC SYSTEM METHODS ===

    private function calculate_composite_score($metrics) {
        $total_score = 0;
        
        foreach (self::METRIC_WEIGHTS as $category => $weight) {
            if (isset($metrics[$category])) {
                $category_score = $this->calculate_category_score($metrics[$category]);
                $total_score += $category_score * $weight;
            }
        }
        
        return round($total_score, 2);
    }

    private function calculate_category_score($category_metrics) {
        if (empty($category_metrics) || !is_array($category_metrics)) {
            return 0;
        }
        
        $total_score = 0;
        $metric_count = 0;
        
        foreach ($category_metrics as $metric_key => $metric_data) {
            if (is_array($metric_data) && isset($metric_data['value'], $metric_data['weight'])) {
                $normalized_value = min(100, max(0, $metric_data['value'])); // Normalize 0-100
                $weighted_score = $normalized_value * $metric_data['weight'];
                $total_score += $weighted_score;
                $metric_count++;
            }
        }
        
        return $metric_count > 0 ? round($total_score / $metric_count, 2) : 0;
    }

    private function update_category_rankings($user_id, $metrics) {
        global $wpdb;
        
        $rankings_table = $wpdb->prefix . 'vortex_category_rankings';
        
        foreach (self::METRIC_WEIGHTS as $category => $weight) {
            if (isset($metrics[$category])) {
                $category_score = $this->calculate_category_score($metrics[$category]);
                
                $wpdb->replace($rankings_table, [
                    'user_id' => $user_id,
                    'category' => $category,
                    'score' => $category_score,
                    'rank_position' => 0, // Will be calculated in batch update
                    'metrics_data' => wp_json_encode($metrics[$category]),
                    'updated_at' => current_time('mysql')
                ]);
            }
        }
        
        // Update rank positions for all categories
        $this->calculate_category_rank_positions();
    }

    private function calculate_category_rank_positions() {
        global $wpdb;
        
        $rankings_table = $wpdb->prefix . 'vortex_category_rankings';
        
        foreach (array_keys(self::METRIC_WEIGHTS) as $category) {
            // Get all users in this category ordered by score
            $users = $wpdb->get_results($wpdb->prepare("
                SELECT user_id, score 
                FROM $rankings_table 
                WHERE category = %s 
                ORDER BY score DESC
            ", $category));
            
            // Update rank positions
            foreach ($users as $index => $user) {
                $rank_position = $index + 1;
                $wpdb->update($rankings_table, 
                    ['rank_position' => $rank_position],
                    ['user_id' => $user->user_id, 'category' => $category],
                    ['%d'],
                    ['%d', '%s']
                );
            }
        }
    }

    private function track_ranking_changes($user_id, $score, $metrics) {
        // Get previous ranking
        $previous_ranking = $this->get_user_previous_ranking($user_id);
        
        if ($previous_ranking) {
            $ranking_change = [
                'user_id' => $user_id,
                'previous_score' => $previous_ranking['composite_score'],
                'new_score' => $score,
                'score_change' => $score - $previous_ranking['composite_score'],
                'metrics_snapshot' => $metrics,
                'timestamp' => current_time('mysql'),
                'ai_analysis' => $this->analyze_ranking_change($user_id, $previous_ranking, $score, $metrics)
            ];
            
            // Trigger AI behavior analysis
            do_action('vortex_ranking_changed', $ranking_change);
        }
    }

    private function analyze_ranking_change($user_id, $previous_ranking, $new_score, $metrics) {
        $score_difference = $new_score - $previous_ranking['composite_score'];
        
        $analysis = [
            'trend' => $score_difference >= 0 ? 'positive' : 'negative',
            'magnitude' => abs($score_difference),
            'primary_driver' => $this->identify_primary_driver($metrics),
            'recommendations' => $this->generate_improvement_recommendations($user_id, $metrics)
        ];
        
        return $analysis;
    }

    private function identify_primary_driver($metrics) {
        $category_scores = [];
        
        foreach (self::METRIC_WEIGHTS as $category => $weight) {
            if (isset($metrics[$category])) {
                $category_scores[$category] = $this->calculate_category_score($metrics[$category]) * $weight;
            }
        }
        
        if (empty($category_scores)) {
            return 'unknown';
        }
        
        return array_search(max($category_scores), $category_scores);
    }

    private function generate_improvement_recommendations($user_id, $metrics) {
        $recommendations = [];
        
        foreach (self::METRIC_WEIGHTS as $category => $weight) {
            if (isset($metrics[$category])) {
                $category_score = $this->calculate_category_score($metrics[$category]);
                
                if ($category_score < 50) { // Below average
                    $recommendations[] = $this->get_category_recommendation($category, $category_score);
                }
            }
        }
        
        return $recommendations;
    }

    private function get_category_recommendation($category, $score) {
        $recommendations = [
            'creator' => "Focus on improving your artwork uploads and originality scores. Current score: {$score}%",
            'collector' => "Diversify your collection and increase your marketplace activity. Current score: {$score}%",
            'marketplace' => "Engage more with trading and platform features. Current score: {$score}%",
            'community' => "Participate in mentorship and DAO activities. Current score: {$score}%"
        ];
        
        return $recommendations[$category] ?? "Improve your {$category} metrics.";
    }

    // === ENHANCED USER STATS METHODS ===

    private function get_user_category_rankings($user_id) {
        global $wpdb;
        
        $rankings_table = $wpdb->prefix . 'vortex_category_rankings';
        
        $rankings = $wpdb->get_results($wpdb->prepare("
            SELECT category, score, rank_position, updated_at
            FROM $rankings_table 
            WHERE user_id = %d
            ORDER BY score DESC
        ", $user_id), ARRAY_A);
        
        return $rankings ?: [];
    }

    private function get_user_metric_breakdown($user_id) {
        global $wpdb;
        
        $metrics_table = $wpdb->prefix . 'vortex_user_metrics';
        
        $metrics = $wpdb->get_results($wpdb->prepare("
            SELECT metric_category, metric_key, metric_value, updated_at
            FROM $metrics_table 
            WHERE user_id = %d
            ORDER BY metric_category, metric_key
        ", $user_id), ARRAY_A);
        
        return $metrics ?: [];
    }

    private function get_user_growth_trajectory($user_id) {
        global $wpdb;
        
        $rankings_table = $wpdb->prefix . 'vortex_user_rankings';
        
        // Get last 30 days of ranking data
        $trajectory = $wpdb->get_results($wpdb->prepare("
            SELECT composite_score, last_updated
            FROM $rankings_table 
            WHERE user_id = %d 
            AND last_updated >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY last_updated ASC
        ", $user_id), ARRAY_A);
        
        if (empty($trajectory)) {
            return ['trend' => 'no_data', 'growth_rate' => 0];
        }
        
        $first_score = reset($trajectory)['composite_score'];
        $last_score = end($trajectory)['composite_score'];
        $growth_rate = $first_score > 0 ? (($last_score - $first_score) / $first_score) * 100 : 0;
        
        return [
            'trend' => $growth_rate >= 0 ? 'positive' : 'negative',
            'growth_rate' => round($growth_rate, 2),
            'data_points' => $trajectory
        ];
    }

    private function get_user_ai_insights($user_id) {
        global $wpdb;
        
        $behavior_table = $wpdb->prefix . 'vortex_behavior_patterns';
        
        // Get recent AI insights
        $insights = $wpdb->get_row($wpdb->prepare("
            SELECT ai_insights, recorded_at
            FROM $behavior_table 
            WHERE user_id = %d 
            ORDER BY recorded_at DESC 
            LIMIT 1
        ", $user_id), ARRAY_A);
        
        if ($insights && !empty($insights['ai_insights'])) {
            return json_decode($insights['ai_insights'], true);
        }
        
        return ['status' => 'no_recent_analysis'];
    }

    // === LEADERBOARD METHODS ===

    public function get_category_leaderboard($category, $limit = 10) {
        global $wpdb;
        
        $rankings_table = $wpdb->prefix . 'vortex_category_rankings';
        
        $leaderboard = $wpdb->get_results($wpdb->prepare("
            SELECT cr.user_id, cr.score, cr.rank_position, u.display_name, u.user_email
            FROM $rankings_table cr
            JOIN {$wpdb->users} u ON cr.user_id = u.ID
            WHERE cr.category = %s
            ORDER BY cr.score DESC
            LIMIT %d
        ", $category, $limit), ARRAY_A);
        
        return $leaderboard ?: [];
    }

    public function get_overall_leaderboard($limit = 10) {
        global $wpdb;
        
        $rankings_table = $wpdb->prefix . 'vortex_user_rankings';
        
        $leaderboard = $wpdb->get_results($wpdb->prepare("
            SELECT ur.user_id, ur.composite_score, u.display_name, u.user_email,
                   ROW_NUMBER() OVER (ORDER BY ur.composite_score DESC) as rank_position
            FROM $rankings_table ur
            JOIN {$wpdb->users} u ON ur.user_id = u.ID
            ORDER BY ur.composite_score DESC
            LIMIT %d
        ", $limit), ARRAY_A);
        
        return $leaderboard ?: [];
    }

    public function get_top_performers() {
        $performers = [];
        
        foreach (array_keys(self::METRIC_WEIGHTS) as $category) {
            $performers[$category] = $this->get_category_leaderboard($category, 5);
        }
        
        return $performers;
    }

    // === UTILITY METHODS ===

    private function get_user_previous_ranking($user_id) {
        global $wpdb;
        
        $rankings_table = $wpdb->prefix . 'vortex_user_rankings';
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $rankings_table 
            WHERE user_id = %d 
            ORDER BY last_updated DESC 
            LIMIT 1 OFFSET 1
        ", $user_id), ARRAY_A);
    }

    private function get_user_ranking($user_id) {
        global $wpdb;
        
        $rankings_table = $wpdb->prefix . 'vortex_user_rankings';
        
        // Get user's current rank
        $user_rank = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) + 1 
            FROM $rankings_table 
            WHERE composite_score > (
                SELECT composite_score 
                FROM $rankings_table 
                WHERE user_id = %d
            )
        ", $user_id));
        
        return $user_rank ?: 'Unranked';
    }

    private function get_user_achievements($user_id) {
        global $wpdb;
        
        $achievements_table = $wpdb->prefix . 'vortex_user_achievements';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT achievement_type, achievement_level, unlock_date
            FROM $achievements_table 
            WHERE user_id = %d
            ORDER BY unlock_date DESC
        ", $user_id), ARRAY_A) ?: [];
    }

    private function calculate_influence_score($user_id) {
        // Calculate based on multiple factors
        $metrics = $this->get_user_metric_breakdown($user_id);
        $base_score = 0;
        
        foreach ($metrics as $metric) {
            $base_score += floatval($metric['metric_value']);
        }
        
        return round($base_score / max(1, count($metrics)), 2);
    }

    private function get_activity_history($user_id) {
        global $wpdb;
        
        $behavior_table = $wpdb->prefix . 'vortex_behavior_patterns';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT action_type, metric_key, metric_value, recorded_at
            FROM $behavior_table 
            WHERE user_id = %d
            ORDER BY recorded_at DESC
            LIMIT 20
        ", $user_id), ARRAY_A) ?: [];
    }

    // === DATABASE SCHEMA METHODS ===

    public static function create_category_rankings_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'vortex_category_rankings';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            category varchar(50) NOT NULL,
            score decimal(10,2) NOT NULL DEFAULT 0,
            rank_position int(11) NOT NULL DEFAULT 0,
            metrics_data longtext,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_category (user_id, category),
            KEY category (category),
            KEY score (score),
            KEY rank_position (rank_position),
            KEY updated_at (updated_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
} 