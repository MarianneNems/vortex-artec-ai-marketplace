<?php
/**
 * VORTEX AI Terminal Interface
 * 
 * Multi-agent terminal interface with 4 scalable windows
 * HURAII (GPU), CLOE (CPU), HORACE (CPU), THORIUS (CPU)
 */

if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_AI_Terminal_Interface {
    
    private $ai_agents = [];
    private $session_data = [];
    private $active_connections = [];
    
    // AI Agent Configuration with GPU/CPU allocation
    private const AI_AGENTS = [
        'HURAII' => [
            'name' => 'HURAII',
            'type' => 'gpu',
            'color' => '#00ff41', // Matrix green
            'description' => 'Generative AI Engine',
            'hardware' => 'RTX A6000 16GB VRAM',
            'endpoint' => 'https://huraii-gpu.runpod.io',
            'timeout' => 30,
            'max_tokens' => 4096,
            'icon' => '🎨'
        ],
        'CLOE' => [
            'name' => 'CLOE',
            'type' => 'cpu',
            'color' => '#ff6b35', // Orange
            'description' => 'Market Analysis Agent',
            'hardware' => '8 CPU Cores, 16GB RAM',
            'endpoint' => 'https://cloe-cpu.runpod.io',
            'timeout' => 15,
            'max_tokens' => 2048,
            'icon' => '📊'
        ],
        'HORACE' => [
            'name' => 'HORACE',
            'type' => 'cpu',
            'color' => '#4ecdc4', // Teal
            'description' => 'Content Optimization Agent',
            'hardware' => '8 CPU Cores, 16GB RAM',
            'endpoint' => 'https://horace-cpu.runpod.io',
            'timeout' => 15,
            'max_tokens' => 2048,
            'icon' => '✍️'
        ],
        'THORIUS' => [
            'name' => 'THORIUS',
            'type' => 'cpu',
            'color' => '#45b7d1', // Blue
            'description' => 'User Guidance Agent',
            'hardware' => '8 CPU Cores, 16GB RAM',
            'endpoint' => 'https://thorius-cpu.runpod.io',
            'timeout' => 15,
            'max_tokens' => 2048,
            'icon' => '🎯'
        ]
    ];
    
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_terminal_assets']);
        add_action('wp_ajax_vortex_ai_terminal_query', [$this, 'handle_terminal_query']);
        add_action('wp_ajax_nopriv_vortex_ai_terminal_query', [$this, 'handle_terminal_query']);
        add_action('wp_ajax_vortex_ai_terminal_status', [$this, 'get_terminal_status']);
        
        // Register shortcodes
        add_shortcode('vortex_ai_terminal', [$this, 'render_terminal_interface']);
        add_shortcode('vortex_ai_huraii', [$this, 'render_huraii_terminal']);
        add_shortcode('vortex_ai_cloe', [$this, 'render_cloe_terminal']);
        add_shortcode('vortex_ai_horace', [$this, 'render_horace_terminal']);
        add_shortcode('vortex_ai_thorius', [$this, 'render_thorius_terminal']);
        
        $this->init_session();
    }
    
    public function enqueue_terminal_assets() {
        wp_enqueue_style('vortex-ai-terminal', plugins_url('assets/css/ai-terminal.css', __FILE__), [], '1.0.0');
        wp_enqueue_script('vortex-ai-terminal', plugins_url('assets/js/ai-terminal.js', __FILE__), ['jquery'], '1.0.0', true);
        
        wp_localize_script('vortex-ai-terminal', 'vortex_ai_terminal', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_ai_terminal'),
            'agents' => self::AI_AGENTS,
            'user_id' => get_current_user_id(),
            'session_id' => $this->get_session_id()
        ]);
    }
    
    // === MAIN TERMINAL INTERFACE ===
    
    public function render_terminal_interface($atts = []) {
        $atts = shortcode_atts([
            'layout' => 'grid', // grid, tabs, split
            'resizable' => 'true',
            'theme' => 'matrix', // matrix, cyber, classic
            'height' => '600px',
            'width' => '100%'
        ], $atts);
        
        ob_start();
        ?>
        <div class="vortex-ai-terminal-container" 
             data-layout="<?php echo esc_attr($atts['layout']); ?>"
             data-theme="<?php echo esc_attr($atts['theme']); ?>"
             style="height: <?php echo esc_attr($atts['height']); ?>; width: <?php echo esc_attr($atts['width']); ?>;">
            
            <!-- Terminal Header -->
            <div class="terminal-header">
                <div class="terminal-title">
                    <span class="title-icon">🚀</span>
                    VORTEX AI TERMINAL - Multi-Agent Interface
                </div>
                <div class="terminal-controls">
                    <button class="terminal-btn minimize" title="Minimize">_</button>
                    <button class="terminal-btn maximize" title="Maximize">□</button>
                    <button class="terminal-btn close" title="Close">×</button>
                </div>
            </div>
            
            <!-- Terminal Grid Layout -->
            <div class="terminal-grid <?php echo $atts['resizable'] === 'true' ? 'resizable' : ''; ?>">
                
                <!-- HURAII Terminal (GPU) -->
                <div class="terminal-window" data-agent="HURAII" data-type="gpu">
                    <div class="terminal-window-header">
                        <div class="agent-info">
                            <span class="agent-icon">🎨</span>
                            <span class="agent-name">HURAII</span>
                            <span class="agent-type gpu">GPU</span>
                            <span class="hardware-info">RTX A6000 16GB</span>
                        </div>
                        <div class="terminal-window-controls">
                            <div class="connection-status connected" title="Connected"></div>
                            <button class="resize-btn" title="Resize">⇲</button>
                        </div>
                    </div>
                    <div class="terminal-content">
                        <div class="terminal-output" id="huraii-output">
                            <div class="system-message">
                                <span class="timestamp">[<?php echo current_time('H:i:s'); ?>]</span>
                                <span class="message">HURAII Generative AI Engine initialized</span>
                            </div>
                        </div>
                        <div class="terminal-input-area">
                            <div class="input-prompt">
                                <span class="prompt-symbol">HURAII@GPU:~$</span>
                                <input type="text" class="terminal-input" data-agent="HURAII" 
                                       placeholder="Enter generative AI prompt...">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- CLOE Terminal (CPU) -->
                <div class="terminal-window" data-agent="CLOE" data-type="cpu">
                    <div class="terminal-window-header">
                        <div class="agent-info">
                            <span class="agent-icon">📊</span>
                            <span class="agent-name">CLOE</span>
                            <span class="agent-type cpu">CPU</span>
                            <span class="hardware-info">8 Cores, 16GB RAM</span>
                        </div>
                        <div class="terminal-window-controls">
                            <div class="connection-status connected" title="Connected"></div>
                            <button class="resize-btn" title="Resize">⇲</button>
                        </div>
                    </div>
                    <div class="terminal-content">
                        <div class="terminal-output" id="cloe-output">
                            <div class="system-message">
                                <span class="timestamp">[<?php echo current_time('H:i:s'); ?>]</span>
                                <span class="message">CLOE Market Analysis Agent ready</span>
                            </div>
                        </div>
                        <div class="terminal-input-area">
                            <div class="input-prompt">
                                <span class="prompt-symbol">CLOE@CPU:~$</span>
                                <input type="text" class="terminal-input" data-agent="CLOE" 
                                       placeholder="Enter market analysis query...">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- HORACE Terminal (CPU) -->
                <div class="terminal-window" data-agent="HORACE" data-type="cpu">
                    <div class="terminal-window-header">
                        <div class="agent-info">
                            <span class="agent-icon">✍️</span>
                            <span class="agent-name">HORACE</span>
                            <span class="agent-type cpu">CPU</span>
                            <span class="hardware-info">8 Cores, 16GB RAM</span>
                        </div>
                        <div class="terminal-window-controls">
                            <div class="connection-status connected" title="Connected"></div>
                            <button class="resize-btn" title="Resize">⇲</button>
                        </div>
                    </div>
                    <div class="terminal-content">
                        <div class="terminal-output" id="horace-output">
                            <div class="system-message">
                                <span class="timestamp">[<?php echo current_time('H:i:s'); ?>]</span>
                                <span class="message">HORACE Content Optimization Agent online</span>
                            </div>
                        </div>
                        <div class="terminal-input-area">
                            <div class="input-prompt">
                                <span class="prompt-symbol">HORACE@CPU:~$</span>
                                <input type="text" class="terminal-input" data-agent="HORACE" 
                                       placeholder="Enter content optimization request...">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- THORIUS Terminal (CPU) -->
                <div class="terminal-window" data-agent="THORIUS" data-type="cpu">
                    <div class="terminal-window-header">
                        <div class="agent-info">
                            <span class="agent-icon">🎯</span>
                            <span class="agent-name">THORIUS</span>
                            <span class="agent-type cpu">CPU</span>
                            <span class="hardware-info">8 Cores, 16GB RAM</span>
                        </div>
                        <div class="terminal-window-controls">
                            <div class="connection-status connected" title="Connected"></div>
                            <button class="resize-btn" title="Resize">⇲</button>
                        </div>
                    </div>
                    <div class="terminal-content">
                        <div class="terminal-output" id="thorius-output">
                            <div class="system-message">
                                <span class="timestamp">[<?php echo current_time('H:i:s'); ?>]</span>
                                <span class="message">THORIUS User Guidance Agent active</span>
                            </div>
                        </div>
                        <div class="terminal-input-area">
                            <div class="input-prompt">
                                <span class="prompt-symbol">THORIUS@CPU:~$</span>
                                <input type="text" class="terminal-input" data-agent="THORIUS" 
                                       placeholder="Enter guidance request...">
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <!-- Terminal Footer -->
            <div class="terminal-footer">
                <div class="system-stats">
                    <div class="stat-item">
                        <span class="stat-label">GPU Usage:</span>
                        <div class="stat-bar">
                            <div class="stat-fill gpu-usage" style="width: 45%"></div>
                        </div>
                        <span class="stat-value">45%</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">CPU Usage:</span>
                        <div class="stat-bar">
                            <div class="stat-fill cpu-usage" style="width: 23%"></div>
                        </div>
                        <span class="stat-value">23%</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Memory:</span>
                        <div class="stat-bar">
                            <div class="stat-fill memory-usage" style="width: 67%"></div>
                        </div>
                        <span class="stat-value">67%</span>
                    </div>
                </div>
                <div class="terminal-actions">
                    <button class="terminal-action-btn" id="clear-all">Clear All</button>
                    <button class="terminal-action-btn" id="save-session">Save Session</button>
                    <button class="terminal-action-btn" id="export-log">Export Log</button>
                </div>
            </div>
        </div>
        
        <style>
        .vortex-ai-terminal-container {
            background: #0a0a0a;
            border: 2px solid #333;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            color: #00ff41;
            overflow: hidden;
            position: relative;
        }
        
        .terminal-header {
            background: #1a1a1a;
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #333;
        }
        
        .terminal-title {
            font-weight: bold;
            color: #00ff41;
        }
        
        .terminal-controls {
            display: flex;
            gap: 5px;
        }
        
        .terminal-btn {
            width: 20px;
            height: 20px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
        }
        
        .terminal-btn.minimize { background: #ffd700; color: #000; }
        .terminal-btn.maximize { background: #00ff00; color: #000; }
        .terminal-btn.close { background: #ff4444; color: #fff; }
        
        .terminal-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            height: calc(100% - 120px);
            gap: 2px;
            padding: 2px;
        }
        
        .terminal-window {
            background: #111;
            border: 1px solid #333;
            border-radius: 4px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .terminal-window[data-type="gpu"] {
            border-left: 3px solid #ff6b35;
        }
        
        .terminal-window[data-type="cpu"] {
            border-left: 3px solid #4ecdc4;
        }
        
        .terminal-window-header {
            background: #1a1a1a;
            padding: 8px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #333;
            font-size: 12px;
        }
        
        .agent-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .agent-name {
            font-weight: bold;
            color: #00ff41;
        }
        
        .agent-type {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .agent-type.gpu {
            background: #ff6b35;
            color: #000;
        }
        
        .agent-type.cpu {
            background: #4ecdc4;
            color: #000;
        }
        
        .hardware-info {
            color: #888;
            font-size: 10px;
        }
        
        .connection-status {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #00ff00;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .terminal-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 10px;
        }
        
        .terminal-output {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 10px;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .system-message {
            margin-bottom: 5px;
            color: #888;
        }
        
        .user-message {
            margin-bottom: 5px;
            color: #00ff41;
        }
        
        .ai-response {
            margin-bottom: 10px;
            color: #fff;
            padding: 5px;
            background: rgba(0, 255, 65, 0.1);
            border-radius: 3px;
        }
        
        .timestamp {
            color: #666;
            margin-right: 5px;
        }
        
        .terminal-input-area {
            border-top: 1px solid #333;
            padding-top: 10px;
        }
        
        .input-prompt {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .prompt-symbol {
            color: #00ff41;
            font-weight: bold;
            font-size: 12px;
        }
        
        .terminal-input {
            flex: 1;
            background: transparent;
            border: none;
            color: #00ff41;
            font-family: inherit;
            font-size: 12px;
            outline: none;
        }
        
        .terminal-input:focus {
            background: rgba(0, 255, 65, 0.1);
        }
        
        .terminal-footer {
            background: #1a1a1a;
            padding: 10px 15px;
            border-top: 1px solid #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .system-stats {
            display: flex;
            gap: 20px;
            font-size: 11px;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .stat-bar {
            width: 60px;
            height: 6px;
            background: #333;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .stat-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .gpu-usage { background: #ff6b35; }
        .cpu-usage { background: #4ecdc4; }
        .memory-usage { background: #45b7d1; }
        
        .terminal-actions {
            display: flex;
            gap: 10px;
        }
        
        .terminal-action-btn {
            background: #333;
            border: 1px solid #555;
            color: #00ff41;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 11px;
        }
        
        .terminal-action-btn:hover {
            background: #555;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize terminal interface
            const terminal = new VortexAITerminal();
            terminal.init();
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    // === INDIVIDUAL AGENT SHORTCODES ===
    
    public function render_huraii_terminal($atts = []) {
        return $this->render_single_agent_terminal('HURAII', $atts);
    }
    
    public function render_cloe_terminal($atts = []) {
        return $this->render_single_agent_terminal('CLOE', $atts);
    }
    
    public function render_horace_terminal($atts = []) {
        return $this->render_single_agent_terminal('HORACE', $atts);
    }
    
    public function render_thorius_terminal($atts = []) {
        return $this->render_single_agent_terminal('THORIUS', $atts);
    }
    
    private function render_single_agent_terminal($agent_name, $atts = []) {
        $agent = self::AI_AGENTS[$agent_name];
        $atts = shortcode_atts([
            'height' => '400px',
            'width' => '100%',
            'theme' => 'matrix'
        ], $atts);
        
        ob_start();
        ?>
        <div class="vortex-single-agent-terminal" data-agent="<?php echo $agent_name; ?>" 
             style="height: <?php echo esc_attr($atts['height']); ?>; width: <?php echo esc_attr($atts['width']); ?>;">
            
            <div class="single-agent-header">
                <div class="agent-info">
                    <span class="agent-icon"><?php echo $agent['icon']; ?></span>
                    <span class="agent-name"><?php echo $agent['name']; ?></span>
                    <span class="agent-type <?php echo $agent['type']; ?>"><?php echo strtoupper($agent['type']); ?></span>
                    <span class="agent-description"><?php echo $agent['description']; ?></span>
                </div>
                <div class="connection-status connected"></div>
            </div>
            
            <div class="single-agent-output" id="<?php echo strtolower($agent_name); ?>-single-output">
                <div class="system-message">
                    <span class="timestamp">[<?php echo current_time('H:i:s'); ?>]</span>
                    <span class="message"><?php echo $agent['name']; ?> <?php echo $agent['description']; ?> ready</span>
                </div>
            </div>
            
            <div class="single-agent-input">
                <div class="input-prompt">
                    <span class="prompt-symbol"><?php echo $agent_name; ?>@<?php echo strtoupper($agent['type']); ?>:~$</span>
                    <input type="text" class="terminal-input single-agent" data-agent="<?php echo $agent_name; ?>" 
                           placeholder="Enter your prompt for <?php echo $agent_name; ?>...">
                </div>
            </div>
        </div>
        
        <style>
        .vortex-single-agent-terminal {
            background: #0a0a0a;
            border: 2px solid <?php echo $agent['color']; ?>;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            color: <?php echo $agent['color']; ?>;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .single-agent-header {
            background: #1a1a1a;
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid <?php echo $agent['color']; ?>;
        }
        
        .single-agent-output {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .single-agent-input {
            background: #111;
            padding: 10px 15px;
            border-top: 1px solid <?php echo $agent['color']; ?>;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    // === AJAX HANDLERS ===
    
    public function handle_terminal_query() {
        check_ajax_referer('vortex_ai_terminal', 'nonce');
        
        $agent = sanitize_text_field($_POST['agent']);
        $query = sanitize_textarea_field($_POST['query']);
        $user_id = get_current_user_id();
        
        if (!array_key_exists($agent, self::AI_AGENTS)) {
            wp_send_json_error('Invalid AI agent');
        }
        
        $agent_config = self::AI_AGENTS[$agent];
        
        // Log the query
        $this->log_terminal_query($user_id, $agent, $query);
        
        // Prepare the request for the AI agent
        $request_data = [
            'query' => $query,
            'user_id' => $user_id,
            'agent' => $agent,
            'session_id' => $this->get_session_id(),
            'context' => $this->get_user_context($user_id),
            'hardware_type' => $agent_config['type'],
            'max_tokens' => $agent_config['max_tokens'],
            'timestamp' => current_time('mysql')
        ];
        
        // Send request to AI agent
        $response = $this->send_ai_request($agent, $request_data);
        
        if ($response['success']) {
            wp_send_json_success([
                'agent' => $agent,
                'query' => $query,
                'response' => $response['data'],
                'timestamp' => current_time('H:i:s'),
                'processing_time' => $response['processing_time'],
                'hardware_usage' => $response['hardware_usage']
            ]);
        } else {
            wp_send_json_error([
                'message' => 'AI agent request failed: ' . $response['error'],
                'agent' => $agent
            ]);
        }
    }
    
    public function get_terminal_status() {
        check_ajax_referer('vortex_ai_terminal', 'nonce');
        
        $status = [];
        
        foreach (self::AI_AGENTS as $agent_name => $config) {
            $status[$agent_name] = [
                'connected' => $this->test_agent_connection($agent_name),
                'hardware_type' => $config['type'],
                'response_time' => $this->get_agent_response_time($agent_name),
                'load' => $this->get_agent_load($agent_name)
            ];
        }
        
        wp_send_json_success([
            'agents' => $status,
            'system_stats' => $this->get_system_stats(),
            'timestamp' => current_time('mysql')
        ]);
    }
    
    // === AI COMMUNICATION ===
    
    private function send_ai_request($agent, $request_data) {
        $config = self::AI_AGENTS[$agent];
        $start_time = microtime(true);
        
        // Prepare endpoint based on agent type
        $endpoint = $config['endpoint'];
        if ($config['type'] === 'gpu') {
            $endpoint .= '/generate';
        } else {
            $endpoint .= '/analyze';
        }
        
        // Enhanced request with hardware optimization
        $enhanced_request = array_merge($request_data, [
            'hardware_optimization' => true,
            'priority' => $config['type'] === 'gpu' ? 'high' : 'normal',
            'resource_allocation' => [
                'gpu_memory' => $config['type'] === 'gpu' ? '12GB' : '0GB',
                'cpu_cores' => $config['type'] === 'cpu' ? 8 : 2,
                'ram_limit' => '16GB'
            ]
        ]);
        
        $response = wp_remote_post($endpoint, [
            'timeout' => $config['timeout'],
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->get_ai_token($agent),
                'X-Hardware-Type' => $config['type'],
                'X-Max-Tokens' => $config['max_tokens'],
                'X-Session-ID' => $this->get_session_id()
            ],
            'body' => wp_json_encode($enhanced_request)
        ]);
        
        $processing_time = microtime(true) - $start_time;
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message(),
                'processing_time' => $processing_time
            ];
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code === 200) {
            $ai_data = json_decode($response_body, true);
            
            return [
                'success' => true,
                'data' => $ai_data['response'] ?? $response_body,
                'processing_time' => $processing_time,
                'hardware_usage' => $ai_data['hardware_usage'] ?? [],
                'confidence' => $ai_data['confidence'] ?? 0.8,
                'tokens_used' => $ai_data['tokens_used'] ?? 0
            ];
        } else {
            return [
                'success' => false,
                'error' => "HTTP {$response_code}: {$response_body}",
                'processing_time' => $processing_time
            ];
        }
    }
    
    // === UTILITY METHODS ===
    
    private function init_session() {
        $this->session_data = [
            'session_id' => $this->get_session_id(),
            'user_id' => get_current_user_id(),
            'start_time' => time(),
            'interactions' => 0
        ];
    }
    
    private function get_session_id() {
        $session_id = wp_cache_get('vortex_terminal_session_' . get_current_user_id());
        if (!$session_id) {
            $session_id = 'vortex_' . uniqid() . '_' . time();
            wp_cache_set('vortex_terminal_session_' . get_current_user_id(), $session_id, '', 3600);
        }
        return $session_id;
    }
    
    private function get_user_context($user_id) {
        return [
            'subscription_plan' => get_user_meta($user_id, 'vortex_subscription_plan', true),
            'experience_level' => get_user_meta($user_id, 'vortex_experience_level', true),
            'recent_activity' => $this->get_recent_user_activity($user_id),
            'preferences' => get_user_meta($user_id, 'vortex_ai_preferences', true) ?: []
        ];
    }
    
    private function get_ai_token($agent) {
        $tokens = get_option('vortex_ai_tokens', []);
        return $tokens[$agent] ?? 'default-token';
    }
    
    private function test_agent_connection($agent) {
        $config = self::AI_AGENTS[$agent];
        $response = wp_remote_get($config['endpoint'] . '/health', ['timeout' => 5]);
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    private function get_agent_response_time($agent) {
        return wp_cache_get("vortex_agent_response_time_{$agent}") ?: 0;
    }
    
    private function get_agent_load($agent) {
        return wp_cache_get("vortex_agent_load_{$agent}") ?: 0;
    }
    
    private function get_system_stats() {
        return [
            'gpu_usage' => 45,
            'cpu_usage' => 23,
            'memory_usage' => 67,
            'active_sessions' => 3
        ];
    }
    
    private function log_terminal_query($user_id, $agent, $query) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_terminal_logs';
        $wpdb->insert($table, [
            'user_id' => $user_id,
            'agent' => $agent,
            'query' => $query,
            'session_id' => $this->get_session_id(),
            'timestamp' => current_time('mysql')
        ]);
    }
    
    private function get_recent_user_activity($user_id) {
        // This would integrate with the behavior sync system
        return [];
    }
}

// Initialize the AI Terminal Interface
new VORTEX_AI_Terminal_Interface(); 