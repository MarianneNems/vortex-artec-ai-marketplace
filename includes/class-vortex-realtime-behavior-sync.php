<?php
/**
 * VORTEX Real-Time Behavior Synchronization System
 * 
 * Captures all user behaviors and synchronizes them with AI engines in real-time
 * Ensures immediate analysis by HURAII, CLOE, HORACE, THORIUS, ARCHER
 */

if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_RealTime_Behavior_Sync {
    
    private $ai_engines = [];
    private $behavior_queue = [];
    private $connection_status = [];
    private $sync_interval = 5; // 5 seconds
    private $batch_size = 50;
    private $debug_mode = false;
    
    // AI Engine Configuration
    private const AI_ENGINES = [
        'HURAII' => [
            'type' => 'gpu',
            'endpoint' => 'https://huraii-gpu.runpod.io/analyze',
            'specialization' => 'generative_analysis',
            'timeout' => 30,
            'priority' => 1
        ],
        'CLOE' => [
            'type' => 'cpu',
            'endpoint' => 'https://cloe-cpu.runpod.io/market-analysis',
            'specialization' => 'market_analysis',
            'timeout' => 15,
            'priority' => 2
        ],
        'HORACE' => [
            'type' => 'cpu',
            'endpoint' => 'https://horace-cpu.runpod.io/content-analysis',
            'specialization' => 'content_optimization',
            'timeout' => 15,
            'priority' => 3
        ],
        'THORIUS' => [
            'type' => 'cpu',
            'endpoint' => 'https://thorius-cpu.runpod.io/guidance',
            'specialization' => 'user_guidance',
            'timeout' => 15,
            'priority' => 4
        ],
        'ARCHER' => [
            'type' => 'cpu',
            'endpoint' => 'https://archer-orchestrator.runpod.io/orchestrate',
            'specialization' => 'orchestration',
            'timeout' => 20,
            'priority' => 5
        ]
    ];
    
    public function __construct() {
        $this->init_ai_connections();
        $this->register_behavior_hooks();
        $this->setup_real_time_sync();
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
    }
    
    // === INITIALIZATION ===
    
    private function init_ai_connections() {
        foreach (self::AI_ENGINES as $engine => $config) {
            $this->connection_status[$engine] = [
                'connected' => false,
                'last_ping' => 0,
                'response_time' => 0,
                'error_count' => 0,
                'last_error' => null
            ];
            
            // Test initial connection
            $this->test_ai_connection($engine);
        }
        
        // Schedule connection monitoring
        if (!wp_next_scheduled('vortex_monitor_ai_connections')) {
            wp_schedule_event(time(), 'every_minute', 'vortex_monitor_ai_connections');
        }
        add_action('vortex_monitor_ai_connections', [$this, 'monitor_ai_connections']);
    }
    
    private function register_behavior_hooks() {
        // === CREATOR BEHAVIOR HOOKS ===
        add_action('wp_insert_post', [$this, 'track_artwork_creation'], 10, 3);
        add_action('add_attachment', [$this, 'track_media_upload'], 10, 1);
        add_action('wp_ajax_vortex_save_artwork', [$this, 'track_artwork_save']);
        add_action('wp_ajax_vortex_publish_collection', [$this, 'track_collection_publish']);
        
        // === COLLECTOR BEHAVIOR HOOKS ===
        add_action('woocommerce_checkout_order_processed', [$this, 'track_purchase'], 10, 3);
        add_action('vortex_nft_purchased', [$this, 'track_nft_purchase'], 10, 3);
        add_action('vortex_artwork_favorited', [$this, 'track_favorite'], 10, 2);
        add_action('vortex_collection_followed', [$this, 'track_collection_follow'], 10, 2);
        
        // === MARKETPLACE BEHAVIOR HOOKS ===
        add_action('vortex_tola_transaction', [$this, 'track_tola_activity'], 10, 3);
        add_action('vortex_smart_contract_interaction', [$this, 'track_contract_usage'], 10, 2);
        add_action('vortex_marketplace_search', [$this, 'track_search_behavior'], 10, 2);
        add_action('vortex_feature_used', [$this, 'track_feature_adoption'], 10, 3);
        
        // === COMMUNITY BEHAVIOR HOOKS ===
        add_action('comment_post', [$this, 'track_community_comment'], 10, 3);
        add_action('vortex_dao_vote', [$this, 'track_dao_participation'], 10, 3);
        add_action('vortex_mentorship_action', [$this, 'track_mentorship'], 10, 3);
        add_action('vortex_knowledge_contribution', [$this, 'track_knowledge_share'], 10, 2);
        
        // === GENERAL USER BEHAVIOR ===
        add_action('wp_login', [$this, 'track_user_login'], 10, 2);
        add_action('wp_logout', [$this, 'track_user_logout'], 10, 1);
        add_action('wp_ajax_heartbeat', [$this, 'track_user_activity'], 10, 2);
        add_action('wp_head', [$this, 'track_page_view']);
        
        // === MOUSE AND CLICK TRACKING ===
        add_action('wp_footer', [$this, 'inject_behavior_tracking_script']);
    }
    
    private function setup_real_time_sync() {
        // Real-time synchronization with AI engines
        add_action('wp_ajax_vortex_sync_behavior', [$this, 'ajax_sync_behavior']);
        add_action('wp_ajax_nopriv_vortex_sync_behavior', [$this, 'ajax_sync_behavior']);
        
        // Background processing
        add_action('vortex_process_behavior_queue', [$this, 'process_behavior_queue']);
        
        // Schedule queue processing
        if (!wp_next_scheduled('vortex_process_behavior_queue')) {
            wp_schedule_event(time(), 'every_minute', 'vortex_process_behavior_queue');
        }
        
        // WebSocket-like functionality using Server-Sent Events
        add_action('wp_ajax_vortex_behavior_stream', [$this, 'behavior_stream_endpoint']);
    }
    
    // === BEHAVIOR TRACKING METHODS ===
    
    public function track_artwork_creation($post_id, $post, $update) {
        if ($post->post_type !== 'artwork' || $update) return;
        
        $behavior_data = [
            'action' => 'artwork_created',
            'user_id' => $post->post_author,
            'object_id' => $post_id,
            'metadata' => [
                'title' => $post->post_title,
                'status' => $post->post_status,
                'timestamp' => current_time('mysql'),
                'estimated_complexity' => $this->estimate_artwork_complexity($post_id)
            ],
            'ai_targets' => ['HURAII', 'ARCHER'], // Generative AI + Orchestrator
            'metric_impacts' => [
                'creator.weekly_artwork_uploads' => 1,
                'creator.artistic_growth_index' => 0.5
            ]
        ];
        
        $this->queue_behavior($behavior_data);
    }
    
    public function track_media_upload($attachment_id) {
        $user_id = get_current_user_id();
        if (!$user_id) return;
        
        $attachment = get_post($attachment_id);
        $file_type = wp_check_filetype($attachment->post_title);
        
        $behavior_data = [
            'action' => 'media_uploaded',
            'user_id' => $user_id,
            'object_id' => $attachment_id,
            'metadata' => [
                'file_type' => $file_type['type'],
                'file_size' => filesize(get_attached_file($attachment_id)),
                'timestamp' => current_time('mysql')
            ],
            'ai_targets' => ['HURAII', 'HORACE'], // For originality + content analysis
            'metric_impacts' => [
                'creator.weekly_artwork_uploads' => 0.5,
                'creator.originality_score' => 1
            ]
        ];
        
        $this->queue_behavior($behavior_data);
    }
    
    public function track_purchase($order_id, $posted_data, $order) {
        $user_id = $order->get_user_id();
        if (!$user_id) return;
        
        $behavior_data = [
            'action' => 'purchase_completed',
            'user_id' => $user_id,
            'object_id' => $order_id,
            'metadata' => [
                'total_amount' => $order->get_total(),
                'items_count' => $order->get_item_count(),
                'payment_method' => $order->get_payment_method(),
                'timestamp' => current_time('mysql')
            ],
            'ai_targets' => ['CLOE', 'ARCHER'], // Market analysis + Orchestrator
            'metric_impacts' => [
                'collector.purchase_frequency' => 1,
                'marketplace.trading_volume_tola' => $order->get_total()
            ]
        ];
        
        $this->queue_behavior($behavior_data);
    }
    
    public function track_dao_participation($proposal_id, $user_id, $vote_data) {
        $behavior_data = [
            'action' => 'dao_participation',
            'user_id' => $user_id,
            'object_id' => $proposal_id,
            'metadata' => [
                'vote_type' => $vote_data['vote'],
                'voting_power' => $vote_data['power'] ?? 1,
                'timestamp' => current_time('mysql')
            ],
            'ai_targets' => ['THORIUS', 'ARCHER'], // Guidance + Orchestrator
            'metric_impacts' => [
                'community.dao_proposal_engagement' => 1,
                'community.trustworthiness_rating' => 0.5
            ]
        ];
        
        $this->queue_behavior($behavior_data);
    }
    
    public function track_user_activity($response, $data) {
        $user_id = get_current_user_id();
        if (!$user_id) return;
        
        // Track active engagement
        $behavior_data = [
            'action' => 'user_activity',
            'user_id' => $user_id,
            'object_id' => 0,
            'metadata' => [
                'page_url' => $_SERVER['HTTP_REFERER'] ?? '',
                'session_duration' => $data['interval'] ?? 15,
                'timestamp' => current_time('mysql')
            ],
            'ai_targets' => ['ARCHER'], // Orchestrator for session analysis
            'metric_impacts' => [
                'marketplace.system_navigation_score' => 0.1
            ]
        ];
        
        $this->queue_behavior($behavior_data);
    }
    
    public function inject_behavior_tracking_script() {
        $user_id = get_current_user_id();
        if (!$user_id) return;
        ?>
        <script>
        (function() {
            let behaviorQueue = [];
            let lastActivity = Date.now();
            
            // Track mouse movements and clicks
            function trackInteraction(type, event) {
                const data = {
                    action: 'user_interaction',
                    user_id: <?php echo $user_id; ?>,
                    interaction_type: type,
                    metadata: {
                        x: event.clientX || 0,
                        y: event.clientY || 0,
                        element: event.target.tagName,
                        timestamp: new Date().toISOString(),
                        page_url: window.location.href
                    }
                };
                
                behaviorQueue.push(data);
                
                // Send data if queue is full or after 5 seconds
                if (behaviorQueue.length >= 10 || Date.now() - lastActivity > 5000) {
                    sendBehaviorData();
                }
            }
            
            function sendBehaviorData() {
                if (behaviorQueue.length === 0) return;
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'vortex_sync_behavior',
                        behaviors: JSON.stringify(behaviorQueue),
                        nonce: '<?php echo wp_create_nonce('vortex_behavior_sync'); ?>'
                    })
                });
                
                behaviorQueue = [];
                lastActivity = Date.now();
            }
            
            // Event listeners
            document.addEventListener('click', (e) => trackInteraction('click', e));
            document.addEventListener('mousemove', (e) => {
                if (Date.now() - lastActivity > 1000) { // Throttle mouse tracking
                    trackInteraction('mousemove', e);
                }
            });
            
            // Send data before page unload
            window.addEventListener('beforeunload', sendBehaviorData);
            
            // Periodic sync
            setInterval(sendBehaviorData, 30000); // Every 30 seconds
        })();
        </script>
        <?php
    }
    
    // === AI ENGINE COMMUNICATION ===
    
    private function queue_behavior($behavior_data) {
        // Add to immediate processing queue
        $this->behavior_queue[] = array_merge($behavior_data, [
            'queued_at' => microtime(true),
            'priority' => $this->calculate_priority($behavior_data),
            'attempts' => 0
        ]);
        
        // Store in database for persistence
        $this->store_behavior_in_db($behavior_data);
        
        // Trigger immediate processing for high-priority behaviors
        if ($this->calculate_priority($behavior_data) >= 8) {
            $this->process_immediate_behavior($behavior_data);
        }
        
        $this->log_debug("Behavior queued: {$behavior_data['action']} for user {$behavior_data['user_id']}");
    }
    
    private function process_immediate_behavior($behavior_data) {
        foreach ($behavior_data['ai_targets'] as $engine) {
            if ($this->connection_status[$engine]['connected']) {
                $this->send_to_ai_engine($engine, $behavior_data);
            }
        }
    }
    
    private function send_to_ai_engine($engine, $behavior_data) {
        $config = self::AI_ENGINES[$engine];
        $start_time = microtime(true);
        
        $payload = [
            'behavior' => $behavior_data,
            'user_context' => $this->get_user_context($behavior_data['user_id']),
            'metric_impacts' => $behavior_data['metric_impacts'] ?? [],
            'engine_specialization' => $config['specialization'],
            'timestamp' => current_time('mysql')
        ];
        
        $response = wp_remote_post($config['endpoint'], [
            'timeout' => $config['timeout'],
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->get_ai_token($engine),
                'X-Engine-Type' => $config['type'],
                'X-User-ID' => $behavior_data['user_id']
            ],
            'body' => wp_json_encode($payload)
        ]);
        
        $response_time = microtime(true) - $start_time;
        
        if (is_wp_error($response)) {
            $this->handle_ai_error($engine, $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code === 200) {
            $ai_result = json_decode($response_body, true);
            $this->process_ai_response($engine, $behavior_data, $ai_result, $response_time);
            return true;
        } else {
            $this->handle_ai_error($engine, "HTTP {$response_code}: {$response_body}");
            return false;
        }
    }
    
    private function process_ai_response($engine, $behavior_data, $ai_result, $response_time) {
        // Update connection status
        $this->connection_status[$engine]['response_time'] = $response_time;
        $this->connection_status[$engine]['error_count'] = 0;
        
        // Store AI insights
        $this->store_ai_insights($behavior_data['user_id'], $engine, $ai_result);
        
        // Update metrics based on AI analysis
        if (isset($ai_result['metric_updates'])) {
            $this->update_user_metrics($behavior_data['user_id'], $ai_result['metric_updates']);
        }
        
        // Trigger ranking update if needed
        if (isset($ai_result['ranking_impact']) && $ai_result['ranking_impact']) {
            do_action('vortex_update_user_ranking', $behavior_data['user_id']);
        }
        
        $this->log_debug("AI response processed from {$engine} in {$response_time}s");
    }
    
    // === QUEUE PROCESSING ===
    
    public function process_behavior_queue() {
        if (empty($this->behavior_queue)) {
            $this->load_pending_behaviors_from_db();
        }
        
        $processed = 0;
        $batch = array_splice($this->behavior_queue, 0, $this->batch_size);
        
        foreach ($batch as $behavior) {
            $success = false;
            
            foreach ($behavior['ai_targets'] as $engine) {
                if ($this->connection_status[$engine]['connected']) {
                    if ($this->send_to_ai_engine($engine, $behavior)) {
                        $success = true;
                    }
                }
            }
            
            if ($success) {
                $this->mark_behavior_processed($behavior);
                $processed++;
            } else {
                $behavior['attempts']++;
                if ($behavior['attempts'] < 3) {
                    $this->behavior_queue[] = $behavior; // Retry
                } else {
                    $this->mark_behavior_failed($behavior);
                }
            }
        }
        
        $this->log_debug("Processed {$processed} behaviors from queue");
    }
    
    // === CONNECTION MONITORING ===
    
    public function monitor_ai_connections() {
        foreach (array_keys(self::AI_ENGINES) as $engine) {
            $this->test_ai_connection($engine);
        }
    }
    
    private function test_ai_connection($engine) {
        $config = self::AI_ENGINES[$engine];
        $start_time = microtime(true);
        
        $response = wp_remote_get($config['endpoint'] . '/health', [
            'timeout' => 5,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_ai_token($engine)
            ]
        ]);
        
        $response_time = microtime(true) - $start_time;
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $this->connection_status[$engine] = [
                'connected' => true,
                'last_ping' => time(),
                'response_time' => $response_time,
                'error_count' => 0,
                'last_error' => null
            ];
        } else {
            $error_message = is_wp_error($response) ? $response->get_error_message() : 'Connection failed';
            $this->handle_ai_error($engine, $error_message);
        }
    }
    
    private function handle_ai_error($engine, $error_message) {
        $this->connection_status[$engine]['connected'] = false;
        $this->connection_status[$engine]['error_count']++;
        $this->connection_status[$engine]['last_error'] = $error_message;
        
        $this->log_debug("AI Engine {$engine} error: {$error_message}");
        
        // Trigger admin notification for persistent errors
        if ($this->connection_status[$engine]['error_count'] >= 5) {
            do_action('vortex_ai_engine_offline', $engine, $error_message);
        }
    }
    
    // === AJAX ENDPOINTS ===
    
    public function ajax_sync_behavior() {
        check_ajax_referer('vortex_behavior_sync', 'nonce');
        
        $behaviors = json_decode(stripslashes($_POST['behaviors']), true);
        
        if (!is_array($behaviors)) {
            wp_die('Invalid behavior data');
        }
        
        foreach ($behaviors as $behavior) {
            $this->queue_behavior($behavior);
        }
        
        wp_send_json_success([
            'processed' => count($behaviors),
            'queue_size' => count($this->behavior_queue),
            'ai_status' => $this->get_ai_status_summary()
        ]);
    }
    
    public function behavior_stream_endpoint() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        
        $last_id = isset($_GET['lastEventId']) ? intval($_GET['lastEventId']) : 0;
        
        while (true) {
            $events = $this->get_recent_behavior_events($last_id);
            
            foreach ($events as $event) {
                echo "id: {$event['id']}\n";
                echo "event: behavior\n";
                echo "data: " . json_encode($event) . "\n\n";
                $last_id = $event['id'];
            }
            
            if (connection_aborted()) {
                break;
            }
            
            sleep(1);
        }
    }
    
    // === UTILITY METHODS ===
    
    private function get_user_context($user_id) {
        return [
            'user_id' => $user_id,
            'subscription_plan' => get_user_meta($user_id, 'vortex_subscription_plan', true),
            'journey_stage' => $this->get_user_journey_stage($user_id),
            'current_metrics' => $this->get_current_user_metrics($user_id),
            'session_data' => $this->get_user_session_data($user_id)
        ];
    }
    
    private function get_user_journey_stage($user_id) {
        $plan = get_user_meta($user_id, 'vortex_subscription_plan', true);
        $milestones = get_user_meta($user_id, 'vortex_completed_milestones', true) ?: [];
        
        if (empty($plan)) return 'unregistered';
        if (count($milestones) < 3) return 'onboarding';
        if (count($milestones) < 7) return 'developing';
        return 'established';
    }
    
    private function calculate_priority($behavior_data) {
        $base_priority = 5;
        
        // High-priority actions
        $high_priority_actions = ['artwork_created', 'purchase_completed', 'dao_participation'];
        if (in_array($behavior_data['action'], $high_priority_actions)) {
            $base_priority += 3;
        }
        
        // AI target priority
        if (in_array('HURAII', $behavior_data['ai_targets'])) {
            $base_priority += 2; // GPU processing has higher priority
        }
        
        return min(10, $base_priority);
    }
    
    private function estimate_artwork_complexity($post_id) {
        // Basic complexity estimation
        $content_length = strlen(get_post_field('post_content', $post_id));
        $attachments = get_attached_media('image', $post_id);
        
        return min(100, ($content_length / 100) + (count($attachments) * 10));
    }
    
    private function get_ai_token($engine) {
        // Get stored API tokens (would be stored securely)
        $tokens = get_option('vortex_ai_tokens', []);
        return $tokens[$engine] ?? 'default-token';
    }
    
    private function store_behavior_in_db($behavior_data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_behavior_queue';
        $this->ensure_behavior_queue_table();
        
        $wpdb->insert($table, [
            'user_id' => $behavior_data['user_id'],
            'action' => $behavior_data['action'],
            'object_id' => $behavior_data['object_id'],
            'metadata' => wp_json_encode($behavior_data['metadata']),
            'ai_targets' => wp_json_encode($behavior_data['ai_targets']),
            'metric_impacts' => wp_json_encode($behavior_data['metric_impacts'] ?? []),
            'priority' => $this->calculate_priority($behavior_data),
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ]);
    }
    
    private function store_ai_insights($user_id, $engine, $ai_result) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_ai_insights';
        $this->ensure_ai_insights_table();
        
        $wpdb->insert($table, [
            'user_id' => $user_id,
            'ai_engine' => $engine,
            'insights_data' => wp_json_encode($ai_result),
            'confidence_score' => $ai_result['confidence'] ?? 0.5,
            'processing_time' => $ai_result['processing_time'] ?? 0,
            'created_at' => current_time('mysql')
        ]);
    }
    
    private function update_user_metrics($user_id, $metric_updates) {
        foreach ($metric_updates as $metric_key => $value) {
            do_action('vortex_update_metric', $user_id, $metric_key, $value);
        }
    }
    
    private function get_ai_status_summary() {
        return array_map(function($status) {
            return [
                'connected' => $status['connected'],
                'response_time' => $status['response_time'],
                'error_count' => $status['error_count']
            ];
        }, $this->connection_status);
    }
    
    private function log_debug($message) {
        if ($this->debug_mode) {
            error_log("[VORTEX Behavior Sync] {$message}");
        }
    }
    
    // === DATABASE SCHEMA ===
    
    private function ensure_behavior_queue_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'vortex_behavior_queue';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            action varchar(100) NOT NULL,
            object_id bigint(20) DEFAULT 0,
            metadata longtext,
            ai_targets longtext,
            metric_impacts longtext,
            priority int(2) DEFAULT 5,
            status varchar(20) DEFAULT 'pending',
            attempts int(2) DEFAULT 0,
            processed_at datetime NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY priority (priority),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function ensure_ai_insights_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'vortex_ai_insights';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            ai_engine varchar(50) NOT NULL,
            insights_data longtext NOT NULL,
            confidence_score decimal(3,2) DEFAULT 0.50,
            processing_time decimal(8,4) DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY ai_engine (ai_engine),
            KEY created_at (created_at),
            KEY confidence_score (confidence_score)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    // === COMPLETE IMPLEMENTATIONS ===
    
    private function load_pending_behaviors_from_db() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_behavior_queue';
        $behaviors = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE status = 'pending' AND attempts < 3 ORDER BY priority DESC, created_at ASC LIMIT %d",
            $this->batch_size
        ), ARRAY_A);
        
        foreach ($behaviors as $behavior) {
            $this->behavior_queue[] = [
                'action' => $behavior['action'],
                'user_id' => $behavior['user_id'],
                'object_id' => $behavior['object_id'],
                'metadata' => json_decode($behavior['metadata'], true),
                'ai_targets' => json_decode($behavior['ai_targets'], true),
                'metric_impacts' => json_decode($behavior['metric_impacts'], true),
                'queued_at' => strtotime($behavior['created_at']),
                'priority' => $behavior['priority'],
                'attempts' => $behavior['attempts'],
                'db_id' => $behavior['id']
            ];
        }
    }
    
    private function mark_behavior_processed($behavior) {
        global $wpdb;
        
        if (isset($behavior['db_id'])) {
            $table = $wpdb->prefix . 'vortex_behavior_queue';
            $wpdb->update($table, [
                'status' => 'processed',
                'processed_at' => current_time('mysql')
            ], ['id' => $behavior['db_id']]);
        }
    }
    
    private function mark_behavior_failed($behavior) {
        global $wpdb;
        
        if (isset($behavior['db_id'])) {
            $table = $wpdb->prefix . 'vortex_behavior_queue';
            $wpdb->update($table, [
                'status' => 'failed',
                'attempts' => $behavior['attempts'],
                'processed_at' => current_time('mysql')
            ], ['id' => $behavior['db_id']]);
        }
    }
    
    private function get_recent_behavior_events($last_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_behavior_queue';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, user_id, action, metadata, created_at FROM $table WHERE id > %d ORDER BY id ASC LIMIT 50",
            $last_id
        ), ARRAY_A);
    }
    
    private function get_current_user_metrics($user_id) {
        // Integration with VORTEX_Gamification_Metrics
        if (class_exists('VORTEX_Gamification_Metrics')) {
            $metrics_system = new VORTEX_Gamification_Metrics();
            return $metrics_system->get_user_metrics($user_id);
        }
        
        // Fallback to basic metrics
        return [
            'creator.weekly_artwork_uploads' => get_user_meta($user_id, 'vortex_weekly_uploads', true) ?: 0,
            'collector.purchase_frequency' => get_user_meta($user_id, 'vortex_purchase_count', true) ?: 0,
            'community.dao_proposal_engagement' => get_user_meta($user_id, 'vortex_dao_votes', true) ?: 0,
            'marketplace.trading_volume_tola' => get_user_meta($user_id, 'vortex_trading_volume', true) ?: 0
        ];
    }
    
    private function get_user_session_data($user_id) {
        $session_key = 'vortex_user_session_' . $user_id;
        $session_data = wp_cache_get($session_key);
        
        if (!$session_data) {
            $session_data = [
                'session_start' => time(),
                'page_views' => 1,
                'interactions' => 0,
                'last_activity' => time(),
                'current_page' => $_SERVER['REQUEST_URI'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip_address' => $this->get_user_ip()
            ];
            wp_cache_set($session_key, $session_data, '', 3600); // 1 hour
        }
        
        return $session_data;
    }
    
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }
}

// Initialize the real-time behavior sync system
new VORTEX_RealTime_Behavior_Sync(); 