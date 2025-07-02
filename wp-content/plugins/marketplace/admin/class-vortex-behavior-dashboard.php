<?php
/**
 * VORTEX Real-Time Behavior Dashboard
 *
 * Admin dashboard for monitoring user behavior patterns and AI insights
 */

if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_Behavior_Dashboard {
    
    private $gamification_metrics;
    
    public function __construct() {
        $this->gamification_metrics = new VORTEX_Gamification_Metrics();
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_vortex_get_behavior_data', [$this, 'ajax_get_behavior_data']);
        add_action('wp_ajax_vortex_get_metric_details', [$this, 'ajax_get_metric_details']);
        add_action('wp_ajax_vortex_get_user_journey_analytics', [$this, 'ajax_get_user_journey_analytics']);
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'vortex-admin',
            'Behavior Analytics',
            'Behavior Analytics',
            'manage_options',
            'vortex-behavior-dashboard',
            [$this, 'render_dashboard']
        );
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'vortex_page_vortex-behavior-dashboard') return;
        
        wp_enqueue_script(
            'vortex-behavior-dashboard',
            plugin_dir_url(__FILE__) . '../assets/js/behavior-dashboard.js',
            ['jquery', 'wp-api'],
            '1.0.0',
            true
        );
        
        wp_enqueue_style(
            'vortex-behavior-dashboard',
            plugin_dir_url(__FILE__) . '../assets/css/behavior-dashboard.css',
            [],
            '1.0.0'
        );
        
        wp_localize_script('vortex-behavior-dashboard', 'vortexBehavior', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_behavior_nonce'),
            'refreshInterval' => 30000 // 30 seconds
        ]);
    }
    
    public function render_dashboard() {
        $overview = $this->gamification_metrics->get_real_time_behavior_overview();
        ?>
        <div class="wrap vortex-behavior-dashboard">
            <h1>🔍 VORTEX AI Behavior Analytics Dashboard</h1>
            <p class="description">Real-time monitoring of user behavior patterns and AI insights across the 29-metric ranking system.</p>
            
            <!-- Real-Time Stats Overview -->
            <div class="vortex-stats-grid">
                <div class="vortex-stat-card active-users">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3>Active Users</h3>
                        <div class="stat-number" id="active-users-count"><?php echo $overview['active_users_count']; ?></div>
                        <span class="stat-period">Last Hour</span>
                    </div>
                </div>
                
                <div class="vortex-stat-card behaviors-analyzed">
                    <div class="stat-icon">🧠</div>
                    <div class="stat-info">
                        <h3>AI Behaviors Analyzed</h3>
                        <div class="stat-number" id="behaviors-analyzed"><?php echo $overview['ai_insights_summary']['total_behaviors_analyzed']; ?></div>
                        <span class="stat-period">Total</span>
                    </div>
                </div>
                
                <div class="vortex-stat-card engagement-growth">
                    <div class="stat-icon">📈</div>
                    <div class="stat-info">
                        <h3>Engagement Growth</h3>
                        <div class="stat-number" id="engagement-growth"><?php echo $overview['behavior_trends']['engagement_growth']; ?>%</div>
                        <span class="stat-period">24h</span>
                    </div>
                </div>
                
                <div class="vortex-stat-card ai-confidence">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-info">
                        <h3>AI Confidence Score</h3>
                        <div class="stat-number" id="ai-confidence"><?php echo round($overview['ai_insights_summary']['ai_confidence_score'] * 100); ?>%</div>
                        <span class="stat-period">Current</span>
                    </div>
                </div>
            </div>
            
            <!-- 29-Metric Categories Overview -->
            <div class="vortex-metrics-overview">
                <h2>📊 29-Metric System Overview</h2>
                <div class="metrics-categories-grid">
                    <?php foreach ($overview['metric_categories'] as $category => $data): ?>
                        <div class="metric-category-card" data-category="<?php echo $category; ?>">
                            <div class="category-header">
                                <h3><?php echo ucfirst($category); ?> Metrics</h3>
                                <span class="category-weight"><?php echo ($data['weight'] * 100); ?>% Weight</span>
                            </div>
                            <div class="category-stats">
                                <div class="stat-item">
                                    <span class="label">Metrics Count:</span>
                                    <span class="value"><?php echo $data['metric_count']; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="label">Avg Score (24h):</span>
                                    <span class="value"><?php echo round($data['avg_score'], 1); ?></span>
                                </div>
                                <div class="top-metrics">
                                    <span class="label">Top Metrics:</span>
                                    <ul>
                                        <?php foreach ($data['top_metrics'] as $metric): ?>
                                            <li><?php echo str_replace('_', ' ', ucwords($metric, '_')); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Real-Time Activity Feed -->
            <div class="vortex-activity-section">
                <div class="section-left">
                    <h2>⚡ Real-Time Activity Feed</h2>
                    <div class="activity-feed" id="activity-feed">
                        <?php foreach ($overview['recent_activity'] as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon"><?php echo $this->get_activity_icon($activity['action_type']); ?></div>
                                <div class="activity-details">
                                    <span class="activity-type"><?php echo str_replace('_', ' ', ucwords($activity['action_type'], '_')); ?></span>
                                    <span class="activity-count"><?php echo $activity['count']; ?> actions</span>
                                    <span class="activity-avg">Avg: <?php echo round($activity['avg_value'], 2); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="section-right">
                    <h2>🏆 Top Performers by Category</h2>
                    <div class="top-performers-container">
                        <?php foreach ($overview['top_performers'] as $category => $performers): ?>
                            <div class="performers-category">
                                <h4><?php echo ucfirst($category); ?></h4>
                                <div class="performers-list">
                                    <?php foreach ($performers as $index => $performer): ?>
                                        <div class="performer-item">
                                            <span class="rank">#<?php echo $index + 1; ?></span>
                                            <span class="name"><?php echo esc_html($performer['display_name']); ?></span>
                                            <span class="score"><?php echo round($performer['avg_score'], 1); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- AI Insights Panel -->
            <div class="vortex-ai-insights">
                <h2>🤖 AI Behavior Insights</h2>
                <div class="insights-grid">
                    <div class="insight-card positive-patterns">
                        <h4>✅ Positive Patterns Detected</h4>
                        <div class="insight-number"><?php echo $overview['ai_insights_summary']['positive_patterns']; ?></div>
                        <p>Users showing strong engagement across multiple metrics</p>
                    </div>
                    
                    <div class="insight-card improvement-opportunities">
                        <h4>🎯 Improvement Opportunities</h4>
                        <div class="insight-number"><?php echo $overview['ai_insights_summary']['improvement_opportunities']; ?></div>
                        <p>Areas where users could enhance their platform engagement</p>
                    </div>
                    
                    <div class="insight-card trending-behaviors">
                        <h4>📈 Trending Behaviors</h4>
                        <div class="trending-list">
                            <?php foreach ($overview['behavior_trends']['trending_metrics'] as $metric): ?>
                                <span class="trending-metric"><?php echo str_replace('_', ' ', ucwords($metric, '_')); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="insight-card ai-recommendations">
                        <h4>💡 AI Recommendations</h4>
                        <ul class="recommendations-list">
                            <li>Focus on creator engagement programs</li>
                            <li>Enhance collector diversity initiatives</li>
                            <li>Promote community mentorship activities</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Artist Journey Correlation -->
            <div class="vortex-journey-correlation">
                <h2>🎨 Artist Journey Stage Analytics</h2>
                <div class="journey-stages-container" id="journey-stages">
                    <!-- Loaded via AJAX -->
                </div>
            </div>
            
            <!-- Controls -->
            <div class="vortex-dashboard-controls">
                <button type="button" class="button button-primary" id="refresh-data">🔄 Refresh Data</button>
                <button type="button" class="button" id="export-insights">📊 Export Insights</button>
                <button type="button" class="button" id="toggle-auto-refresh">⏸️ Pause Auto-Refresh</button>
                <span class="last-updated">Last updated: <span id="last-updated-time"><?php echo current_time('H:i:s'); ?></span></span>
            </div>
        </div>
        
        <style>
        .vortex-behavior-dashboard {
            background: #f1f1f1;
            padding: 20px;
        }
        
        .vortex-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .vortex-stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            transition: transform 0.2s;
        }
        
        .vortex-stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-icon {
            font-size: 3em;
            margin-right: 15px;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #2271b1;
        }
        
        .stat-period {
            color: #666;
            font-size: 0.9em;
        }
        
        .metrics-categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .metric-category-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #2271b1;
        }
        
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .category-weight {
            background: #2271b1;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
        }
        
        .vortex-activity-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .activity-feed {
            max-height: 400px;
            overflow-y: auto;
            background: white;
            border-radius: 8px;
            padding: 20px;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .activity-icon {
            font-size: 1.5em;
            margin-right: 15px;
        }
        
        .top-performers-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .performer-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .vortex-ai-insights {
            margin-bottom: 30px;
        }
        
        .insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .insight-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .insight-number {
            font-size: 2em;
            font-weight: bold;
            color: #2271b1;
            margin: 10px 0;
        }
        
        .vortex-dashboard-controls {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .vortex-dashboard-controls button {
            margin: 0 10px;
        }
        
        .last-updated {
            margin-left: 20px;
            color: #666;
        }
        </style>
        <?php
    }
    
    public function ajax_get_behavior_data() {
        check_ajax_referer('vortex_behavior_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'vortex-marketplace'));
        }
        
        $overview = $this->gamification_metrics->get_real_time_behavior_overview();
        
        wp_send_json_success([
            'overview' => $overview,
            'timestamp' => current_time('mysql'),
            'formatted_time' => current_time('H:i:s')
        ]);
    }
    
    public function ajax_get_metric_details() {
        check_ajax_referer('vortex_behavior_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'vortex-marketplace'));
        }
        
        $category = sanitize_text_field($_POST['category']);
        $user_id = intval($_POST['user_id']);
        
        if ($user_id > 0) {
            $metrics = $this->gamification_metrics->get_user_complete_metrics($user_id);
            wp_send_json_success(['user_metrics' => $metrics[$category] ?? []]);
        } else {
            // Return category overview
            wp_send_json_success(['category_overview' => $this->get_category_overview($category)]);
        }
    }
    
    public function ajax_get_user_journey_analytics() {
        check_ajax_referer('vortex_behavior_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'vortex-marketplace'));
        }
        
        $journey_analytics = $this->get_user_journey_analytics();
        wp_send_json_success($journey_analytics);
    }
    
    private function get_activity_icon($action_type) {
        $icons = [
            'creator_action' => '🎨',
            'collector_action' => '🛒',
            'marketplace_action' => '🏪',
            'community_action' => '🤝',
            'swap_completed' => '🔄',
            'artwork_uploaded' => '⬆️',
            'purchase_completed' => '💰'
        ];
        
        return $icons[$action_type] ?? '📊';
    }
    
    private function get_category_overview($category) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_user_metrics';
        
        $overview = $wpdb->get_results($wpdb->prepare("
            SELECT 
                metric_key,
                COUNT(*) as user_count,
                AVG(metric_value) as avg_value,
                MAX(metric_value) as max_value,
                MIN(metric_value) as min_value
            FROM $table 
            WHERE metric_category = %s 
            AND updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY metric_key
            ORDER BY avg_value DESC
        ", $category), ARRAY_A);
        
        return $overview;
    }
    
    private function get_user_journey_analytics() {
        global $wpdb;
        
        $stages = ['unregistered', 'onboarding', 'developing', 'established'];
        $analytics = [];
        
        foreach ($stages as $stage) {
            $behavior_table = $wpdb->prefix . 'vortex_behavior_patterns';
            
            $stage_data = $wpdb->get_row($wpdb->prepare("
                SELECT 
                    COUNT(DISTINCT user_id) as user_count,
                    COUNT(*) as total_actions,
                    AVG(metric_value) as avg_metric_value
                FROM $behavior_table 
                WHERE journey_stage = %s 
                AND recorded_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ", $stage), ARRAY_A);
            
            $analytics[$stage] = $stage_data ?: [
                'user_count' => 0,
                'total_actions' => 0,
                'avg_metric_value' => 0
            ];
        }
        
        return $analytics;
    }
}

// Initialize the dashboard
new VORTEX_Behavior_Dashboard(); 