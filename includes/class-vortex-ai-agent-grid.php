<?php
/**
 * VORTEX AI Agent Grid Interface
 * 
 * Creates a 4-agent grid interface with individual prompt/response windows
 * Shows GPU/CPU allocation and provides real-time communication
 */

if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_AI_Agent_Grid {
    
    private $agents = [
        'HURAII' => [
            'name' => 'HURAII',
            'title' => 'Generative AI Master',
            'type' => 'GPU',
            'specialization' => 'Image & Content Generation',
            'endpoint' => 'https://huraii-gpu.runpod.io/generate',
            'color' => '#00ff88',
            'icon' => '🎨',
            'description' => 'GPU-powered generative AI for artwork creation and analysis'
        ],
        'CLOE' => [
            'name' => 'CLOE', 
            'title' => 'Market Intelligence',
            'type' => 'CPU',
            'specialization' => 'Market Analysis & Trends',
            'endpoint' => 'https://cloe-cpu.runpod.io/analyze',
            'color' => '#ff6b35',
            'icon' => '📈',
            'description' => 'CPU-optimized market analysis and collector matching'
        ],
        'HORACE' => [
            'name' => 'HORACE',
            'title' => 'Content Curator',
            'type' => 'CPU', 
            'specialization' => 'Content Optimization',
            'endpoint' => 'https://horace-cpu.runpod.io/optimize',
            'color' => '#4ecdc4',
            'icon' => '📝',
            'description' => 'CPU-powered content optimization and SEO enhancement'
        ],
        'THORIUS' => [
            'name' => 'THORIUS',
            'title' => 'Platform Guide',
            'type' => 'CPU',
            'specialization' => 'User Guidance & Support', 
            'endpoint' => 'https://thorius-cpu.runpod.io/guide',
            'color' => '#9b59b6',
            'icon' => '🧭',
            'description' => 'CPU-optimized platform guidance and user assistance'
        ]
    ];
    
    public function __construct() {
        $this->init_shortcodes();
        $this->init_ajax_handlers();
        $this->enqueue_assets();
    }
    
    // === SHORTCODE REGISTRATION ===
    
    private function init_shortcodes() {
        // Main 4-agent grid
        add_shortcode('vortex_ai_grid', [$this, 'render_ai_grid']);
        
        // Individual agent shortcodes
        add_shortcode('vortex_huraii_agent', [$this, 'render_huraii_agent']);
        add_shortcode('vortex_cloe_agent', [$this, 'render_cloe_agent']);
        add_shortcode('vortex_horace_agent', [$this, 'render_horace_agent']);
        add_shortcode('vortex_thorius_agent', [$this, 'render_thorius_agent']);
        
        // Compact versions
        add_shortcode('vortex_ai_mini_grid', [$this, 'render_mini_grid']);
    }
    
    private function init_ajax_handlers() {
        // AJAX handlers for each agent
        add_action('wp_ajax_vortex_chat_huraii', [$this, 'ajax_chat_huraii']);
        add_action('wp_ajax_vortex_chat_cloe', [$this, 'ajax_chat_cloe']);
        add_action('wp_ajax_vortex_chat_horace', [$this, 'ajax_chat_horace']);
        add_action('wp_ajax_vortex_chat_thorius', [$this, 'ajax_chat_thorius']);
        
        // System status
        add_action('wp_ajax_vortex_agent_status', [$this, 'ajax_agent_status']);
    }
    
    private function enqueue_assets() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }
    
    // === MAIN GRID SHORTCODE ===
    
    public function render_ai_grid($atts) {
        $atts = shortcode_atts([
            'layout' => '2x2', // 2x2 or 1x4 or 4x1
            'theme' => 'dark',
            'show_status' => 'true',
            'height' => '600px'
        ], $atts);
        
        ob_start();
        ?>
        <div class="vortex-ai-grid" data-layout="<?php echo esc_attr($atts['layout']); ?>" data-theme="<?php echo esc_attr($atts['theme']); ?>" style="height: <?php echo esc_attr($atts['height']); ?>;">
            
            <!-- Grid Header -->
            <div class="vortex-grid-header">
                <h3>🤖 VORTEX AI Agent Grid</h3>
                <div class="vortex-grid-controls">
                    <button class="vortex-btn vortex-btn-clear-all" onclick="vortexClearAllChats()">Clear All</button>
                    <button class="vortex-btn vortex-btn-status" onclick="vortexRefreshStatus()">
                        <span class="status-indicator" id="system-status"></span> Status
                    </button>
                </div>
            </div>
            
            <!-- Agent Grid -->
            <div class="vortex-agents-container">
                <?php foreach ($this->agents as $agent_key => $agent): ?>
                    <div class="vortex-agent-window" data-agent="<?php echo strtolower($agent_key); ?>">
                        
                        <!-- Agent Header -->
                        <div class="vortex-agent-header" style="border-color: <?php echo $agent['color']; ?>;">
                            <div class="agent-info">
                                <span class="agent-icon"><?php echo $agent['icon']; ?></span>
                                <div class="agent-details">
                                    <h4><?php echo $agent['name']; ?></h4>
                                    <span class="agent-title"><?php echo $agent['title']; ?></span>
                                </div>
                            </div>
                            <div class="agent-status">
                                <span class="resource-type <?php echo strtolower($agent['type']); ?>" title="<?php echo $agent['type']; ?> Processing">
                                    <?php echo $agent['type']; ?>
                                    <?php if ($agent['type'] === 'GPU'): ?>
                                        <span class="gpu-indicator">⚡</span>
                                    <?php else: ?>
                                        <span class="cpu-indicator">💻</span>
                                    <?php endif; ?>
                                </span>
                                <div class="connection-status" id="status-<?php echo strtolower($agent_key); ?>">
                                    <span class="status-dot offline"></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Chat Interface -->
                        <div class="vortex-chat-container">
                            <div class="vortex-chat-messages" id="messages-<?php echo strtolower($agent_key); ?>">
                                <div class="agent-intro">
                                    <p><strong><?php echo $agent['description']; ?></strong></p>
                                    <p>Specialization: <em><?php echo $agent['specialization']; ?></em></p>
                                </div>
                            </div>
                            
                            <!-- Input Area -->
                            <div class="vortex-chat-input-area">
                                <div class="input-wrapper">
                                    <textarea 
                                        class="vortex-chat-input" 
                                        id="input-<?php echo strtolower($agent_key); ?>" 
                                        placeholder="Ask <?php echo $agent['name']; ?>..."
                                        rows="2"
                                    ></textarea>
                                    <button 
                                        class="vortex-send-btn" 
                                        onclick="vortexSendMessage('<?php echo strtolower($agent_key); ?>')"
                                        style="background: <?php echo $agent['color']; ?>;"
                                    >
                                        <span class="send-icon">➤</span>
                                    </button>
                                </div>
                                <div class="input-controls">
                                    <span class="char-count" id="count-<?php echo strtolower($agent_key); ?>">0/500</span>
                                    <button class="clear-btn" onclick="vortexClearChat('<?php echo strtolower($agent_key); ?>')">Clear</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Processing Indicator -->
                        <div class="processing-indicator" id="processing-<?php echo strtolower($agent_key); ?>">
                            <div class="processing-spinner"></div>
                            <span>Processing with <?php echo $agent['type']; ?>...</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- System Info Panel -->
            <?php if ($atts['show_status'] === 'true'): ?>
            <div class="vortex-system-info">
                <div class="system-metrics">
                    <div class="metric">
                        <span class="metric-label">GPU Utilization:</span>
                        <div class="metric-bar">
                            <div class="metric-fill gpu-usage" style="width: 0%"></div>
                        </div>
                        <span class="metric-value" id="gpu-usage">0%</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">CPU Utilization:</span>
                        <div class="metric-bar">
                            <div class="metric-fill cpu-usage" style="width: 0%"></div>
                        </div>
                        <span class="metric-value" id="cpu-usage">0%</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <script>
        // Initialize AI Grid
        document.addEventListener('DOMContentLoaded', function() {
            vortexInitializeAIGrid();
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    // === INDIVIDUAL AGENT SHORTCODES ===
    
    public function render_huraii_agent($atts) {
        return $this->render_single_agent('HURAII', $atts);
    }
    
    public function render_cloe_agent($atts) {
        return $this->render_single_agent('CLOE', $atts);
    }
    
    public function render_horace_agent($atts) {
        return $this->render_single_agent('HORACE', $atts);
    }
    
    public function render_thorius_agent($atts) {
        return $this->render_single_agent('THORIUS', $atts);
    }
    
    private function render_single_agent($agent_key, $atts) {
        $atts = shortcode_atts([
            'width' => '100%',
            'height' => '400px',
            'theme' => 'dark'
        ], $atts);
        
        $agent = $this->agents[$agent_key];
        
        ob_start();
        ?>
        <div class="vortex-single-agent" data-agent="<?php echo strtolower($agent_key); ?>" data-theme="<?php echo esc_attr($atts['theme']); ?>" style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;">
            
            <!-- Agent Header -->
            <div class="vortex-agent-header" style="border-color: <?php echo $agent['color']; ?>;">
                <div class="agent-info">
                    <span class="agent-icon"><?php echo $agent['icon']; ?></span>
                    <div class="agent-details">
                        <h4><?php echo $agent['name']; ?></h4>
                        <span class="agent-title"><?php echo $agent['title']; ?></span>
                    </div>
                </div>
                <div class="agent-status">
                    <span class="resource-type <?php echo strtolower($agent['type']); ?>">
                        <?php echo $agent['type']; ?>
                        <?php if ($agent['type'] === 'GPU'): ?>
                            <span class="gpu-indicator">⚡</span>
                        <?php else: ?>
                            <span class="cpu-indicator">💻</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            
            <!-- Chat Interface -->
            <div class="vortex-chat-container">
                <div class="vortex-chat-messages" id="messages-<?php echo strtolower($agent_key); ?>">
                    <div class="agent-intro">
                        <p><strong><?php echo $agent['description']; ?></strong></p>
                    </div>
                </div>
                
                <div class="vortex-chat-input-area">
                    <div class="input-wrapper">
                        <textarea 
                            class="vortex-chat-input" 
                            id="input-<?php echo strtolower($agent_key); ?>" 
                            placeholder="Ask <?php echo $agent['name']; ?>..."
                            rows="2"
                        ></textarea>
                        <button 
                            class="vortex-send-btn" 
                            onclick="vortexSendMessage('<?php echo strtolower($agent_key); ?>')"
                            style="background: <?php echo $agent['color']; ?>;"
                        >
                            ➤
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // === AJAX HANDLERS ===
    
    public function ajax_chat_huraii() {
        $this->handle_agent_chat('HURAII');
    }
    
    public function ajax_chat_cloe() {
        $this->handle_agent_chat('CLOE');
    }
    
    public function ajax_chat_horace() {
        $this->handle_agent_chat('HORACE');
    }
    
    public function ajax_chat_thorius() {
        $this->handle_agent_chat('THORIUS');
    }
    
    private function handle_agent_chat($agent_key) {
        check_ajax_referer('vortex_ai_chat', 'nonce');
        
        $message = sanitize_textarea_field($_POST['message']);
        $user_id = get_current_user_id();
        
        if (empty($message)) {
            wp_send_json_error('Message cannot be empty');
        }
        
        $agent = $this->agents[$agent_key];
        $start_time = microtime(true);
        
        // Prepare the request payload
        $payload = [
            'message' => $message,
            'user_id' => $user_id,
            'user_context' => $this->get_user_context($user_id),
            'agent_type' => $agent['type'], // GPU or CPU
            'specialization' => $agent['specialization'],
            'timestamp' => current_time('mysql')
        ];
        
        // Send to AI engine
        $response = wp_remote_post($agent['endpoint'], [
            'timeout' => $agent['type'] === 'GPU' ? 30 : 15, // GPU gets more time
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->get_ai_token($agent_key),
                'X-Agent-Type' => $agent['type'],
                'X-User-ID' => $user_id
            ],
            'body' => wp_json_encode($payload)
        ]);
        
        $processing_time = microtime(true) - $start_time;
        
        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => 'Connection error: ' . $response->get_error_message(),
                'processing_time' => $processing_time
            ]);
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            wp_send_json_error([
                'message' => "Agent error (HTTP {$response_code})",
                'processing_time' => $processing_time
            ]);
        }
        
        $ai_response = json_decode($response_body, true);
        
        if (!$ai_response || !isset($ai_response['response'])) {
            wp_send_json_error([
                'message' => 'Invalid response from AI agent',
                'processing_time' => $processing_time
            ]);
        }
        
        // Log the interaction
        $this->log_agent_interaction($user_id, $agent_key, $message, $ai_response, $processing_time);
        
        wp_send_json_success([
            'response' => $ai_response['response'],
            'agent' => $agent_key,
            'processing_time' => round($processing_time, 3),
            'resource_type' => $agent['type'],
            'confidence' => $ai_response['confidence'] ?? 0.8,
            'suggestions' => $ai_response['suggestions'] ?? []
        ]);
    }
    
    public function ajax_agent_status() {
        check_ajax_referer('vortex_ai_status', 'nonce');
        
        $status = [];
        
        foreach ($this->agents as $agent_key => $agent) {
            $health_endpoint = $agent['endpoint'] . '/health';
            $start_time = microtime(true);
            
            $response = wp_remote_get($health_endpoint, [
                'timeout' => 5,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->get_ai_token($agent_key)
                ]
            ]);
            
            $response_time = microtime(true) - $start_time;
            
            $status[strtolower($agent_key)] = [
                'online' => !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200,
                'response_time' => round($response_time * 1000), // milliseconds
                'resource_type' => $agent['type'],
                'last_check' => current_time('mysql')
            ];
        }
        
        // Add system resource usage (simulated)
        $status['system'] = [
            'gpu_usage' => rand(45, 75), // Simulated GPU usage
            'cpu_usage' => rand(30, 60), // Simulated CPU usage
            'memory_usage' => rand(40, 70),
            'active_connections' => count(array_filter($status, function($s) { return $s['online']; }))
        ];
        
        wp_send_json_success($status);
    }
    
    // === UTILITY METHODS ===
    
    private function get_user_context($user_id) {
        return [
            'user_id' => $user_id,
            'subscription_plan' => get_user_meta($user_id, 'vortex_subscription_plan', true),
            'journey_stage' => get_user_meta($user_id, 'vortex_journey_stage', true),
            'preferences' => get_user_meta($user_id, 'vortex_ai_preferences', true) ?: []
        ];
    }
    
    private function get_ai_token($agent_key) {
        $tokens = get_option('vortex_ai_tokens', []);
        return $tokens[$agent_key] ?? 'default-token-' . strtolower($agent_key);
    }
    
    private function log_agent_interaction($user_id, $agent, $message, $response, $processing_time) {
        global $wpdb;
        
        $table = $wp_prefix . 'vortex_ai_interactions';
        
        // Ensure table exists
        $this->ensure_interactions_table();
        
        $wpdb->insert($table, [
            'user_id' => $user_id,
            'agent' => $agent,
            'user_message' => $message,
            'agent_response' => wp_json_encode($response),
            'processing_time' => $processing_time,
            'resource_type' => $this->agents[$agent]['type'],
            'created_at' => current_time('mysql')
        ]);
    }
    
    private function ensure_interactions_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'vortex_ai_interactions';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            agent varchar(50) NOT NULL,
            user_message text NOT NULL,
            agent_response longtext NOT NULL,
            processing_time decimal(8,4) DEFAULT 0,
            resource_type varchar(10) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY agent (agent),
            KEY created_at (created_at),
            KEY resource_type (resource_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    // === ASSET ENQUEUEING ===
    
    public function enqueue_scripts() {
        wp_enqueue_script('vortex-ai-grid-js', plugin_dir_url(__FILE__) . '../assets/js/vortex-ai-grid.js', ['jquery'], '1.0.0', true);
        
        wp_localize_script('vortex-ai-grid-js', 'vortexAIGrid', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'chat_nonce' => wp_create_nonce('vortex_ai_chat'),
            'status_nonce' => wp_create_nonce('vortex_ai_status'),
            'agents' => array_keys($this->agents)
        ]);
    }
    
    public function enqueue_styles() {
        wp_enqueue_style('vortex-ai-grid-css', plugin_dir_url(__FILE__) . '../assets/css/vortex-ai-grid.css', [], '1.0.0');
    }
}

// Initialize the AI Agent Grid system
new VORTEX_AI_Agent_Grid(); 