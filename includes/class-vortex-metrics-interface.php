<?php
/**
 * VORTEX Metrics Interface
 * 
 * Comprehensive dashboard for real-time metrics, user behavior analytics,
 * gamification scores, and AI system performance monitoring
 */

if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_Metrics_Interface {
    
    private $user_id;
    private $metrics_data;
    private $ai_insights;
    
    public function __construct() {
        $this->user_id = get_current_user_id();
        add_action('wp_enqueue_scripts', [$this, 'enqueue_metrics_assets']);
        add_action('wp_ajax_vortex_get_metrics_data', [$this, 'ajax_get_metrics_data']);
        add_action('wp_ajax_vortex_get_ai_insights', [$this, 'ajax_get_ai_insights']);
        add_action('wp_ajax_vortex_export_metrics', [$this, 'ajax_export_metrics']);
        
        // Register shortcodes
        add_shortcode('vortex_metrics_dashboard', [$this, 'render_metrics_dashboard']);
        add_shortcode('vortex_user_analytics', [$this, 'render_user_analytics']);
        add_shortcode('vortex_ai_performance', [$this, 'render_ai_performance']);
        add_shortcode('vortex_behavior_heatmap', [$this, 'render_behavior_heatmap']);
        add_shortcode('vortex_gamification_board', [$this, 'render_gamification_board']);
    }
    
    public function enqueue_metrics_assets() {
        wp_enqueue_style('vortex-metrics-interface', plugins_url('assets/css/metrics-interface.css', __FILE__), [], '1.0.0');
        wp_enqueue_script('vortex-metrics-interface', plugins_url('assets/js/metrics-interface.js', __FILE__), ['jquery', 'chart-js'], '1.0.0', true);
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.9.1', true);
        
        wp_localize_script('vortex-metrics-interface', 'vortex_metrics', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_metrics'),
            'user_id' => $this->user_id,
            'update_interval' => 30000, // 30 seconds
            'animations' => true
        ]);
    }
    
    // === MAIN METRICS DASHBOARD ===
    
    public function render_metrics_dashboard($atts = []) {
        $atts = shortcode_atts([
            'layout' => 'grid', // grid, columns, tabs
            'theme' => 'dark', // dark, light, matrix
            'realtime' => 'true',
            'height' => '800px',
            'cards' => 'all', // all, basic, advanced
            'refresh_rate' => '30'
        ], $atts);
        
        $this->load_metrics_data();
        
        ob_start();
        ?>
        <div class="vortex-metrics-dashboard" 
             data-layout="<?php echo esc_attr($atts['layout']); ?>"
             data-theme="<?php echo esc_attr($atts['theme']); ?>"
             data-realtime="<?php echo esc_attr($atts['realtime']); ?>"
             data-refresh="<?php echo esc_attr($atts['refresh_rate']); ?>"
             style="min-height: <?php echo esc_attr($atts['height']); ?>;">
            
            <!-- Dashboard Header -->
            <div class="metrics-header">
                <div class="dashboard-title">
                    <h2>📊 VORTEX Metrics Dashboard</h2>
                    <div class="last-updated">
                        Last updated: <span id="last-update-time"><?php echo current_time('H:i:s'); ?></span>
                    </div>
                </div>
                <div class="dashboard-controls">
                    <button class="metrics-btn" id="refresh-metrics" title="Refresh Data">🔄</button>
                    <button class="metrics-btn" id="export-metrics" title="Export Data">📤</button>
                    <button class="metrics-btn" id="toggle-realtime" title="Toggle Real-time" data-active="true">📡</button>
                    <select class="metrics-select" id="time-range">
                        <option value="1h">Last Hour</option>
                        <option value="24h" selected>Last 24 Hours</option>
                        <option value="7d">Last 7 Days</option>
                        <option value="30d">Last 30 Days</option>
                    </select>
                </div>
            </div>
            
            <!-- Metrics Grid -->
            <div class="metrics-grid">
                
                <!-- User Overview Card -->
                <div class="metric-card overview-card" data-card="overview">
                    <div class="card-header">
                        <h3>👤 User Overview</h3>
                        <div class="user-status online">Online</div>
                    </div>
                    <div class="card-content">
                        <div class="user-stats">
                            <div class="stat-item">
                                <span class="stat-label">Journey Stage:</span>
                                <span class="stat-value"><?php echo $this->get_user_journey_stage(); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Subscription:</span>
                                <span class="stat-value"><?php echo $this->get_user_subscription(); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Total Score:</span>
                                <span class="stat-value score"><?php echo $this->get_total_user_score(); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- AI Agents Status Card -->
                <div class="metric-card ai-status-card" data-card="ai-status">
                    <div class="card-header">
                        <h3>🤖 AI Agents Status</h3>
                        <div class="system-health" id="system-health">98%</div>
                    </div>
                    <div class="card-content">
                        <div class="agents-grid">
                            <div class="agent-status" data-agent="HURAII">
                                <div class="agent-icon gpu">🎨</div>
                                <div class="agent-info">
                                    <div class="agent-name">HURAII</div>
                                    <div class="agent-type">GPU</div>
                                    <div class="connection-indicator connected"></div>
                                </div>
                                <div class="agent-metrics">
                                    <div class="metric">Load: <span class="value" id="huraii-load">45%</span></div>
                                    <div class="metric">Resp: <span class="value" id="huraii-response">1.2s</span></div>
                                </div>
                            </div>
                            
                            <div class="agent-status" data-agent="CLOE">
                                <div class="agent-icon cpu">📊</div>
                                <div class="agent-info">
                                    <div class="agent-name">CLOE</div>
                                    <div class="agent-type">CPU</div>
                                    <div class="connection-indicator connected"></div>
                                </div>
                                <div class="agent-metrics">
                                    <div class="metric">Load: <span class="value" id="cloe-load">23%</span></div>
                                    <div class="metric">Resp: <span class="value" id="cloe-response">0.8s</span></div>
                                </div>
                            </div>
                            
                            <div class="agent-status" data-agent="HORACE">
                                <div class="agent-icon cpu">✍️</div>
                                <div class="agent-info">
                                    <div class="agent-name">HORACE</div>
                                    <div class="agent-type">CPU</div>
                                    <div class="connection-indicator connected"></div>
                                </div>
                                <div class="agent-metrics">
                                    <div class="metric">Load: <span class="value" id="horace-load">31%</span></div>
                                    <div class="metric">Resp: <span class="value" id="horace-response">0.9s</span></div>
                                </div>
                            </div>
                            
                            <div class="agent-status" data-agent="THORIUS">
                                <div class="agent-icon cpu">🎯</div>
                                <div class="agent-info">
                                    <div class="agent-name">THORIUS</div>
                                    <div class="agent-type">CPU</div>
                                    <div class="connection-indicator connected"></div>
                                </div>
                                <div class="agent-metrics">
                                    <div class="metric">Load: <span class="value" id="thorius-load">18%</span></div>
                                    <div class="metric">Resp: <span class="value" id="thorius-response">0.7s</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Behavior Analytics Card -->
                <div class="metric-card behavior-card" data-card="behavior">
                    <div class="card-header">
                        <h3>🔍 Behavior Analytics</h3>
                        <div class="behavior-score" id="behavior-score">8.7/10</div>
                    </div>
                    <div class="card-content">
                        <canvas id="behavior-chart" width="400" height="200"></canvas>
                        <div class="behavior-insights">
                            <div class="insight-item">
                                <span class="insight-icon">📈</span>
                                <span class="insight-text">Activity increased 15% today</span>
                            </div>
                            <div class="insight-item">
                                <span class="insight-icon">🎨</span>
                                <span class="insight-text">3 artworks created this week</span>
                            </div>
                            <div class="insight-item">
                                <span class="insight-icon">💎</span>
                                <span class="insight-text">2 NFTs purchased recently</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Gamification Scores Card -->
                <div class="metric-card gamification-card" data-card="gamification">
                    <div class="card-header">
                        <h3>🏆 Gamification Scores</h3>
                        <div class="level-badge">Level <?php echo $this->get_user_level(); ?></div>
                    </div>
                    <div class="card-content">
                        <div class="score-rings">
                            <?php foreach ($this->get_gamification_categories() as $category => $data): ?>
                            <div class="score-ring" data-category="<?php echo $category; ?>">
                                <div class="ring-chart" data-score="<?php echo $data['score']; ?>">
                                    <svg viewBox="0 0 36 36" class="circular-chart">
                                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                        <path class="circle" stroke-dasharray="<?php echo $data['score']; ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                        <text x="18" y="20.35" class="percentage"><?php echo $data['score']; ?>%</text>
                                    </svg>
                                </div>
                                <div class="ring-label"><?php echo $data['label']; ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Real-time Activity Feed -->
                <div class="metric-card activity-feed-card" data-card="activity">
                    <div class="card-header">
                        <h3>⚡ Real-time Activity</h3>
                        <div class="activity-count" id="activity-count">12 events</div>
                    </div>
                    <div class="card-content">
                        <div class="activity-feed" id="activity-feed">
                            <!-- Real-time activities will be populated here -->
                        </div>
                    </div>
                </div>
                
                <!-- System Performance Card -->
                <div class="metric-card performance-card" data-card="performance">
                    <div class="card-header">
                        <h3>⚙️ System Performance</h3>
                        <div class="performance-indicator excellent">Excellent</div>
                    </div>
                    <div class="card-content">
                        <div class="performance-metrics">
                            <div class="perf-metric">
                                <div class="metric-label">GPU Usage</div>
                                <div class="metric-bar">
                                    <div class="bar-fill gpu" style="width: 45%"></div>
                                </div>
                                <div class="metric-value">45%</div>
                            </div>
                            <div class="perf-metric">
                                <div class="metric-label">CPU Usage</div>
                                <div class="metric-bar">
                                    <div class="bar-fill cpu" style="width: 28%"></div>
                                </div>
                                <div class="metric-value">28%</div>
                            </div>
                            <div class="perf-metric">
                                <div class="metric-label">Memory</div>
                                <div class="metric-bar">
                                    <div class="bar-fill memory" style="width: 67%"></div>
                                </div>
                                <div class="metric-value">67%</div>
                            </div>
                            <div class="perf-metric">
                                <div class="metric-label">Network</div>
                                <div class="metric-bar">
                                    <div class="bar-fill network" style="width: 82%"></div>
                                </div>
                                <div class="metric-value">82%</div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <!-- Metrics Footer -->
            <div class="metrics-footer">
                <div class="footer-stats">
                    <div class="footer-stat">
                        <span class="stat-icon">📊</span>
                        <span class="stat-text">Data points: 1,247</span>
                    </div>
                    <div class="footer-stat">
                        <span class="stat-icon">🔄</span>
                        <span class="stat-text">Auto-refresh: ON</span>
                    </div>
                    <div class="footer-stat">
                        <span class="stat-icon">💾</span>
                        <span class="stat-text">Last sync: <?php echo current_time('H:i:s'); ?></span>
                    </div>
                </div>
                <div class="vortex-branding">
                    <span>Powered by VORTEX AI</span>
                </div>
            </div>
        </div>
        
        <style>
        .vortex-metrics-dashboard {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
            border-radius: 12px;
            padding: 20px;
            color: #fff;
            font-family: 'Segoe UI', system-ui, sans-serif;
            position: relative;
            overflow: hidden;
        }
        
        .vortex-metrics-dashboard::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(0, 255, 65, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 107, 53, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .vortex-metrics-dashboard > * {
            position: relative;
            z-index: 1;
        }
        
        .metrics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        
        .dashboard-title h2 {
            margin: 0;
            color: #00ff41;
            font-size: 24px;
            text-shadow: 0 0 10px rgba(0, 255, 65, 0.3);
        }
        
        .last-updated {
            color: #888;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .dashboard-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .metrics-btn {
            background: linear-gradient(135deg, #333, #555);
            border: 1px solid #666;
            color: #00ff41;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .metrics-btn:hover {
            background: linear-gradient(135deg, #555, #777);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 255, 65, 0.2);
        }
        
        .metrics-select {
            background: #333;
            border: 1px solid #666;
            color: #fff;
            padding: 8px;
            border-radius: 6px;
            font-size: 12px;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .metric-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border: 1px solid #333;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
        }
        
        .card-header h3 {
            margin: 0;
            font-size: 16px;
            color: #00ff41;
        }
        
        .user-status.online {
            background: #00ff41;
            color: #000;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .system-health {
            background: linear-gradient(135deg, #00ff41, #32cd32);
            color: #000;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .agents-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .agent-status {
            background: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .agent-icon {
            font-size: 24px;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 50%;
        }
        
        .agent-icon.gpu {
            background: rgba(255, 107, 53, 0.2);
            border: 2px solid #ff6b35;
        }
        
        .agent-icon.cpu {
            background: rgba(78, 205, 196, 0.2);
            border: 2px solid #4ecdc4;
        }
        
        .agent-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .agent-type {
            font-size: 11px;
            color: #888;
            margin-bottom: 10px;
        }
        
        .connection-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-bottom: 10px;
        }
        
        .connection-indicator.connected {
            background: #00ff41;
            box-shadow: 0 0 10px rgba(0, 255, 65, 0.5);
            animation: pulse 2s infinite;
        }
        
        .agent-metrics .metric {
            font-size: 11px;
            margin-bottom: 3px;
        }
        
        .agent-metrics .value {
            color: #00ff41;
            font-weight: bold;
        }
        
        .behavior-insights {
            margin-top: 15px;
        }
        
        .insight-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 12px;
        }
        
        .insight-icon {
            font-size: 16px;
        }
        
        .score-rings {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 15px;
        }
        
        .score-ring {
            text-align: center;
        }
        
        .circular-chart {
            width: 80px;
            height: 80px;
        }
        
        .circle-bg {
            fill: none;
            stroke: #333;
            stroke-width: 3.8;
        }
        
        .circle {
            fill: none;
            stroke-width: 2.8;
            stroke-linecap: round;
            animation: progress 1s ease-out forwards;
        }
        
        .score-ring[data-category="creator"] .circle { stroke: #ff6b35; }
        .score-ring[data-category="collector"] .circle { stroke: #4ecdc4; }
        .score-ring[data-category="community"] .circle { stroke: #45b7d1; }
        
        .percentage {
            fill: #fff;
            font-family: sans-serif;
            font-size: 0.5em;
            text-anchor: middle;
        }
        
        .ring-label {
            font-size: 11px;
            color: #888;
            margin-top: 5px;
        }
        
        .activity-feed {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .performance-metrics {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .perf-metric {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .metric-label {
            min-width: 80px;
            font-size: 12px;
            color: #888;
        }
        
        .metric-bar {
            flex: 1;
            height: 8px;
            background: #333;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .bar-fill {
            height: 100%;
            transition: width 0.5s ease;
        }
        
        .bar-fill.gpu { background: linear-gradient(135deg, #ff6b35, #ff8c5a); }
        .bar-fill.cpu { background: linear-gradient(135deg, #4ecdc4, #6dd5ed); }
        .bar-fill.memory { background: linear-gradient(135deg, #45b7d1, #74c0fc); }
        .bar-fill.network { background: linear-gradient(135deg, #00ff41, #32cd32); }
        
        .metric-value {
            min-width: 40px;
            text-align: right;
            font-size: 12px;
            font-weight: bold;
            color: #00ff41;
        }
        
        .metrics-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 2px solid #333;
            font-size: 12px;
            color: #888;
        }
        
        .footer-stats {
            display: flex;
            gap: 20px;
        }
        
        .footer-stat {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .vortex-branding {
            color: #00ff41;
            font-weight: bold;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        @keyframes progress {
            0% { stroke-dasharray: 0 100; }
        }
        
        @media (max-width: 768px) {
            .metrics-grid {
                grid-template-columns: 1fr;
            }
            
            .agents-grid {
                grid-template-columns: 1fr;
            }
            
            .score-rings {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .metrics-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    // === INDIVIDUAL SHORTCODES ===
    
    public function render_user_analytics($atts = []) {
        $atts = shortcode_atts([
            'user_id' => get_current_user_id(),
            'period' => '7d',
            'chart_type' => 'line'
        ], $atts);
        
        ob_start();
        ?>
        <div class="vortex-user-analytics" data-user="<?php echo $atts['user_id']; ?>"
             data-period="<?php echo $atts['period']; ?>">
            <div class="analytics-header">
                <h3>📈 User Analytics</h3>
            </div>
            <div class="analytics-content">
                <canvas id="user-analytics-chart"></canvas>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_ai_performance($atts = []) {
        $atts = shortcode_atts([
            'agents' => 'all', // all, huraii, cloe, horace, thorius
            'metric' => 'response_time' // response_time, accuracy, load
        ], $atts);
        
        ob_start();
        ?>
        <div class="vortex-ai-performance" data-agents="<?php echo $atts['agents']; ?>"
             data-metric="<?php echo $atts['metric']; ?>">
            <div class="performance-header">
                <h3>🤖 AI Performance Monitor</h3>
            </div>
            <div class="performance-content">
                <canvas id="ai-performance-chart"></canvas>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_behavior_heatmap($atts = []) {
        $atts = shortcode_atts([
            'period' => '24h',
            'type' => 'clicks' // clicks, views, interactions
        ], $atts);
        
        ob_start();
        ?>
        <div class="vortex-behavior-heatmap" data-period="<?php echo $atts['period']; ?>"
             data-type="<?php echo $atts['type']; ?>">
            <div class="heatmap-header">
                <h3>🔥 Behavior Heatmap</h3>
            </div>
            <div class="heatmap-content">
                <div id="behavior-heatmap-container"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_gamification_board($atts = []) {
        $atts = shortcode_atts([
            'type' => 'leaderboard', // leaderboard, personal, achievements
            'limit' => '10'
        ], $atts);
        
        ob_start();
        ?>
        <div class="vortex-gamification-board" data-type="<?php echo $atts['type']; ?>"
             data-limit="<?php echo $atts['limit']; ?>">
            <div class="gamification-header">
                <h3>🏆 Gamification Board</h3>
            </div>
            <div class="gamification-content">
                <?php echo $this->render_leaderboard(); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // === AJAX HANDLERS ===
    
    public function ajax_get_metrics_data() {
        check_ajax_referer('vortex_metrics', 'nonce');
        
        $time_range = sanitize_text_field($_POST['time_range'] ?? '24h');
        $user_id = intval($_POST['user_id'] ?? get_current_user_id());
        
        $metrics_data = [
            'user_stats' => $this->get_user_stats($user_id),
            'behavior_data' => $this->get_behavior_data($user_id, $time_range),
            'ai_performance' => $this->get_ai_performance_data(),
            'gamification_scores' => $this->get_gamification_scores($user_id),
            'system_stats' => $this->get_system_stats(),
            'timestamp' => current_time('mysql')
        ];
        
        wp_send_json_success($metrics_data);
    }
    
    public function ajax_get_ai_insights() {
        check_ajax_referer('vortex_metrics', 'nonce');
        
        $user_id = intval($_POST['user_id'] ?? get_current_user_id());
        
        $insights = [
            'behavior_insights' => $this->get_ai_behavior_insights($user_id),
            'recommendations' => $this->get_ai_recommendations($user_id),
            'predictions' => $this->get_ai_predictions($user_id)
        ];
        
        wp_send_json_success($insights);
    }
    
    public function ajax_export_metrics() {
        check_ajax_referer('vortex_metrics', 'nonce');
        
        $user_id = intval($_POST['user_id'] ?? get_current_user_id());
        $format = sanitize_text_field($_POST['format'] ?? 'json');
        
        $export_data = [
            'export_date' => current_time('mysql'),
            'user_id' => $user_id,
            'metrics' => $this->get_complete_metrics_export($user_id)
        ];
        
        wp_send_json_success([
            'download_url' => $this->generate_export_file($export_data, $format),
            'filename' => "vortex-metrics-{$user_id}-" . date('Y-m-d') . ".{$format}"
        ]);
    }
    
    // === DATA METHODS ===
    
    private function load_metrics_data() {
        // Load current user metrics data
        $this->metrics_data = $this->get_user_stats($this->user_id);
    }
    
    private function get_user_journey_stage() {
        $plan = get_user_meta($this->user_id, 'vortex_subscription_plan', true);
        $milestones = get_user_meta($this->user_id, 'vortex_completed_milestones', true) ?: [];
        
        if (empty($plan)) return 'Unregistered';
        if (count($milestones) < 3) return 'Onboarding';
        if (count($milestones) < 7) return 'Developing';
        return 'Established';
    }
    
    private function get_user_subscription() {
        return get_user_meta($this->user_id, 'vortex_subscription_plan', true) ?: 'Free';
    }
    
    private function get_total_user_score() {
        if (class_exists('VORTEX_Gamification_Metrics')) {
            $metrics = new VORTEX_Gamification_Metrics();
            return $metrics->get_total_score($this->user_id);
        }
        return get_user_meta($this->user_id, 'vortex_total_score', true) ?: 0;
    }
    
    private function get_user_level() {
        $total_score = $this->get_total_user_score();
        return floor($total_score / 1000) + 1;
    }
    
    private function get_gamification_categories() {
        return [
            'creator' => [
                'label' => 'Creator',
                'score' => 78
            ],
            'collector' => [
                'label' => 'Collector', 
                'score' => 65
            ],
            'community' => [
                'label' => 'Community',
                'score' => 84
            ]
        ];
    }
    
    private function get_user_stats($user_id) {
        return [
            'total_artworks' => 15,
            'total_purchases' => 8,
            'dao_votes' => 12,
            'mentorship_points' => 340
        ];
    }
    
    private function get_behavior_data($user_id, $time_range) {
        return [
            'page_views' => 156,
            'interactions' => 89,
            'session_duration' => 45.6,
            'bounce_rate' => 0.23
        ];
    }
    
    private function get_ai_performance_data() {
        return [
            'HURAII' => ['load' => 45, 'response_time' => 1.2],
            'CLOE' => ['load' => 23, 'response_time' => 0.8],
            'HORACE' => ['load' => 31, 'response_time' => 0.9],
            'THORIUS' => ['load' => 18, 'response_time' => 0.7]
        ];
    }
    
    private function get_gamification_scores($user_id) {
        return [
            'total_score' => 2340,
            'level' => 3,
            'rank' => 47,
            'achievements' => 12
        ];
    }
    
    private function get_system_stats() {
        return [
            'gpu_usage' => 45,
            'cpu_usage' => 28,
            'memory_usage' => 67,
            'network_quality' => 82
        ];
    }
    
    private function get_ai_behavior_insights($user_id) {
        return [
            'Activity increased 15% today',
            '3 artworks created this week',
            '2 NFTs purchased recently'
        ];
    }
    
    private function get_ai_recommendations($user_id) {
        return [
            'Consider creating more digital art',
            'Engage more with the community',
            'Explore new marketplace features'
        ];
    }
    
    private function get_ai_predictions($user_id) {
        return [
            'Likely to purchase NFT in next 3 days',
            'High engagement expected this week',
            'Potential for community leadership role'
        ];
    }
    
    private function render_leaderboard() {
        return '<div class="leaderboard-placeholder">Leaderboard content...</div>';
    }
    
    private function get_complete_metrics_export($user_id) {
        return [
            'user_stats' => $this->get_user_stats($user_id),
            'behavior_data' => $this->get_behavior_data($user_id, '30d'),
            'gamification_scores' => $this->get_gamification_scores($user_id)
        ];
    }
    
    private function generate_export_file($data, $format) {
        $upload_dir = wp_upload_dir();
        $filename = 'vortex-metrics-export-' . uniqid() . '.' . $format;
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        if ($format === 'json') {
            file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));
        } else {
            // CSV format
            $csv_content = $this->convert_to_csv($data);
            file_put_contents($filepath, $csv_content);
        }
        
        return $upload_dir['url'] . '/' . $filename;
    }
    
    private function convert_to_csv($data) {
        // Simple CSV conversion
        return "Metric,Value\n" . 
               "Export Date," . $data['export_date'] . "\n" .
               "User ID," . $data['user_id'] . "\n";
    }
}

// Initialize the Metrics Interface
new VORTEX_Metrics_Interface(); 