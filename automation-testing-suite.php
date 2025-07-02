<?php
/**
 * VORTEX AI ENGINE - AUTOMATION TESTING SUITE
 * 
 * Comprehensive testing and validation system for all automation components
 */

if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_Automation_Testing_Suite {
    
    private static $instance = null;
    private $test_results = array();
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * RUN COMPREHENSIVE AUTOMATION TESTS
     */
    public function run_full_automation_test() {
        $this->log_test('Starting comprehensive automation test suite');
        
        $test_results = array(
            'archer_orchestrator' => $this->test_archer_orchestrator(),
            'ai_agents' => $this->test_all_ai_agents(),
            'runpod_vault' => $this->test_runpod_vault_connectivity(),
            's3_integration' => $this->test_s3_integration(),
            'tola_art_automation' => $this->test_tola_art_automation(),
            'error_recovery' => $this->test_error_recovery_system(),
            'performance' => $this->test_system_performance(),
            'api_endpoints' => $this->test_all_api_endpoints()
        );
        
        $overall_score = $this->calculate_overall_test_score($test_results);
        
        return array(
            'overall_score' => $overall_score,
            'status' => $overall_score >= 90 ? 'EXCELLENT' : ($overall_score >= 75 ? 'GOOD' : 'NEEDS_IMPROVEMENT'),
            'test_results' => $test_results,
            'recommendations' => $this->generate_recommendations($test_results),
            'timestamp' => current_time('mysql')
        );
    }
    
    /**
     * TEST ARCHER ORCHESTRATOR
     */
    private function test_archer_orchestrator() {
        $this->log_test('Testing ARCHER Orchestrator');
        
        $tests = array(
            'class_exists' => class_exists('VORTEX_ARCHER_Orchestrator'),
            'instance_available' => method_exists('VORTEX_ARCHER_Orchestrator', 'get_instance'),
            'status_operational' => get_option('vortex_archer_status') === 'operational',
            'sync_scheduled' => wp_next_scheduled('vortex_real_time_agent_sync') !== false,
            'heartbeat_scheduled' => wp_next_scheduled('vortex_agent_heartbeat_check') !== false,
            'learning_sync_scheduled' => wp_next_scheduled('vortex_sync_agent_learning') !== false
        );
        
        // Test orchestrator functionality
        if ($tests['class_exists'] && $tests['instance_available']) {
            try {
                $orchestrator = VORTEX_ARCHER_Orchestrator::get_instance();
                $tests['orchestrator_responsive'] = true;
            } catch (Exception $e) {
                $tests['orchestrator_responsive'] = false;
                $this->log_test('Orchestrator not responsive: ' . $e->getMessage());
            }
        }
        
        $success_rate = (array_sum($tests) / count($tests)) * 100;
        
        return array(
            'success_rate' => round($success_rate, 1),
            'tests' => $tests,
            'status' => $success_rate >= 80 ? 'PASS' : 'FAIL'
        );
    }
    
    /**
     * TEST ALL AI AGENTS
     */
    private function test_all_ai_agents() {
        $this->log_test('Testing all AI agents');
        
        $agents = array('HURAII', 'CLOE', 'HORACE', 'THORIUS');
        $agent_results = array();
        
        foreach ($agents as $agent) {
            $agent_results[$agent] = $this->test_individual_agent($agent);
        }
        
        $total_agents = count($agents);
        $healthy_agents = count(array_filter($agent_results, function($result) {
            return $result['status'] === 'HEALTHY';
        }));
        
        $health_percentage = ($healthy_agents / $total_agents) * 100;
        
        return array(
            'health_percentage' => round($health_percentage, 1),
            'healthy_agents' => $healthy_agents,
            'total_agents' => $total_agents,
            'agent_details' => $agent_results,
            'status' => $health_percentage >= 75 ? 'PASS' : 'FAIL'
        );
    }
    
    /**
     * TEST INDIVIDUAL AGENT
     */
    private function test_individual_agent($agent_name) {
        $class_name = 'VORTEX_' . $agent_name;
        
        $tests = array(
            'class_exists' => class_exists($class_name),
            'instance_method' => method_exists($class_name, 'get_instance'),
            'learning_enabled' => get_option("vortex_{$agent_name}_learning_enabled", false),
            'last_heartbeat' => $this->check_agent_heartbeat($agent_name)
        );
        
        // Test agent responsiveness
        if ($tests['class_exists'] && $tests['instance_method']) {
            try {
                $agent_instance = call_user_func(array($class_name, 'get_instance'));
                $tests['responsive'] = !is_null($agent_instance);
            } catch (Exception $e) {
                $tests['responsive'] = false;
            }
        } else {
            $tests['responsive'] = false;
        }
        
        $success_rate = (array_sum($tests) / count($tests)) * 100;
        
        return array(
            'success_rate' => round($success_rate, 1),
            'tests' => $tests,
            'status' => $success_rate >= 80 ? 'HEALTHY' : 'UNHEALTHY'
        );
    }
    
    /**
     * TEST RUNPOD VAULT CONNECTIVITY
     */
    private function test_runpod_vault_connectivity() {
        $this->log_test('Testing RunPod Vault connectivity');
        
        $vault_endpoints = array(
            'seed_art_generation' => '/vault/v1/secret-sauce/seed-art',
            'zodiac_analysis' => '/vault/v1/secret-sauce/zodiac',
            'agent_orchestration' => '/vault/v1/secret-sauce/orchestrate',
            'real_time_sync' => '/vault/v1/sync/realtime'
        );
        
        $base_url = get_option('vortex_runpod_vault_endpoint', 'https://api.runpod.ai');
        $api_key = get_option('vortex_runpod_vault_api_key');
        
        $tests = array(
            'vault_configured' => !empty($base_url) && !empty($api_key),
            'vault_connected' => get_option('vortex_runpod_vault_connected', false)
        );
        
        $endpoint_results = array();
        
        foreach ($vault_endpoints as $name => $endpoint) {
            $endpoint_results[$name] = $this->test_runpod_endpoint($base_url . $endpoint, $api_key);
        }
        
        $healthy_endpoints = array_sum($endpoint_results);
        $total_endpoints = count($endpoint_results);
        $connectivity_rate = ($healthy_endpoints / $total_endpoints) * 100;
        
        return array(
            'connectivity_rate' => round($connectivity_rate, 1),
            'healthy_endpoints' => $healthy_endpoints,
            'total_endpoints' => $total_endpoints,
            'configuration_tests' => $tests,
            'endpoint_results' => $endpoint_results,
            'status' => $connectivity_rate >= 80 ? 'PASS' : 'FAIL'
        );
    }
    
    /**
     * TEST S3 INTEGRATION
     */
    private function test_s3_integration() {
        $this->log_test('Testing AWS S3 integration');
        
        $s3_buckets = array(
            'user_art' => get_option('vortex_s3_bucket_public_art', 'vortex-user-generated-art'),
            'user_galleries' => get_option('vortex_s3_bucket_user_galleries', 'vortex-user-galleries'),
            'marketplace' => get_option('vortex_s3_bucket_marketplace', 'vortex-marketplace-assets')
        );
        
        $s3_config = array(
            'region' => get_option('vortex_s3_region', 'us-east-1'),
            'access_key' => get_option('vortex_s3_access_key'),
            'secret_key' => get_option('vortex_s3_secret_key')
        );
        
        $tests = array(
            's3_configured' => !empty($s3_config['access_key']) && !empty($s3_config['secret_key']),
            'buckets_configured' => !empty(array_filter($s3_buckets))
        );
        
        $bucket_results = array();
        
        foreach ($s3_buckets as $name => $bucket) {
            if (!empty($bucket)) {
                $bucket_results[$name] = $this->test_s3_bucket_connectivity($bucket, $s3_config);
            }
        }
        
        $healthy_buckets = array_sum($bucket_results);
        $total_buckets = count($bucket_results);
        $s3_health = $total_buckets > 0 ? ($healthy_buckets / $total_buckets) * 100 : 0;
        
        return array(
            's3_health' => round($s3_health, 1),
            'healthy_buckets' => $healthy_buckets,
            'total_buckets' => $total_buckets,
            'configuration_tests' => $tests,
            'bucket_results' => $bucket_results,
            'status' => $s3_health >= 80 ? 'PASS' : 'FAIL'
        );
    }
    
    /**
     * TEST TOLA-ART AUTOMATION
     */
    private function test_tola_art_automation() {
        $this->log_test('Testing TOLA-ART automation system');
        
        $tests = array(
            'class_exists' => class_exists('VORTEX_TOLA_Art_Daily_Automation'),
            'daily_generation_scheduled' => wp_next_scheduled('vortex_midnight_art_generation') !== false,
            'database_tables_exist' => $this->check_tola_art_tables(),
            'huraii_integration' => class_exists('Vortex_HURAII_GPU_Backend'),
            'last_generation_success' => $this->check_last_tola_art_generation()
        );
        
        $success_rate = (array_sum($tests) / count($tests)) * 100;
        
        return array(
            'success_rate' => round($success_rate, 1),
            'tests' => $tests,
            'next_generation' => wp_next_scheduled('vortex_midnight_art_generation') ? 
                               date('Y-m-d H:i:s', wp_next_scheduled('vortex_midnight_art_generation')) : 'Not scheduled',
            'status' => $success_rate >= 80 ? 'PASS' : 'FAIL'
        );
    }
    
    /**
     * TEST ERROR RECOVERY SYSTEM
     */
    private function test_error_recovery_system() {
        $this->log_test('Testing error recovery system');
        
        $tests = array(
            'recovery_class_exists' => class_exists('Vortex_Thorius_Recovery'),
            'recovery_scheduled' => wp_next_scheduled('vortex_thorius_retry_critical_operations') !== false,
            'error_logging_enabled' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
            'recovery_queue_exists' => is_array(get_option('vortex_thorius_recovery_queue', false))
        );
        
        $success_rate = (array_sum($tests) / count($tests)) * 100;
        
        return array(
            'success_rate' => round($success_rate, 1),
            'tests' => $tests,
            'status' => $success_rate >= 75 ? 'PASS' : 'FAIL'
        );
    }
    
    /**
     * TEST SYSTEM PERFORMANCE
     */
    private function test_system_performance() {
        $this->log_test('Testing system performance');
        
        $start_time = microtime(true);
        
        // Simulate typical operations
        $operations = array(
            'database_query' => $this->test_database_performance(),
            'file_system' => $this->test_file_system_performance(),
            'memory_usage' => $this->test_memory_usage(),
            'cache_performance' => $this->test_cache_performance()
        );
        
        $total_time = (microtime(true) - $start_time) * 1000;
        
        $performance_score = 100;
        if ($total_time > 1000) $performance_score -= 20; // > 1 second
        if (memory_get_usage(true) > 128 * 1024 * 1024) $performance_score -= 20; // > 128MB
        
        return array(
            'performance_score' => max(0, $performance_score),
            'total_test_time_ms' => round($total_time, 2),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'operations' => $operations,
            'status' => $performance_score >= 80 ? 'PASS' : 'FAIL'
        );
    }
    
    /**
     * TEST ALL API ENDPOINTS
     */
    private function test_all_api_endpoints() {
        $this->log_test('Testing API endpoints');
        
        $endpoints = array(
            'health_check' => '/health',
            'agent_status' => '/api/v1/agents/status',
            'system_status' => '/api/v1/system/status'
        );
        
        $endpoint_results = array();
        
        foreach ($endpoints as $name => $endpoint) {
            $endpoint_results[$name] = $this->test_api_endpoint($endpoint);
        }
        
        $healthy_endpoints = array_sum($endpoint_results);
        $total_endpoints = count($endpoint_results);
        $api_health = ($healthy_endpoints / $total_endpoints) * 100;
        
        return array(
            'api_health' => round($api_health, 1),
            'healthy_endpoints' => $healthy_endpoints,
            'total_endpoints' => $total_endpoints,
            'endpoint_results' => $endpoint_results,
            'status' => $api_health >= 80 ? 'PASS' : 'FAIL'
        );
    }
    
    /**
     * HELPER METHODS FOR TESTING
     */
    private function test_runpod_endpoint($endpoint_url, $api_key) {
        $response = wp_remote_get($endpoint_url . '/ping', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 10
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    private function test_s3_bucket_connectivity($bucket, $s3_config) {
        $test_url = "https://{$bucket}.s3.{$s3_config['region']}.amazonaws.com/";
        
        $response = wp_remote_head($test_url, array('timeout' => 10));
        
        return !is_wp_error($response);
    }
    
    private function check_agent_heartbeat($agent_name) {
        $last_heartbeat = get_option("vortex_{$agent_name}_last_heartbeat", 0);
        $current_time = time();
        
        // Consider agent healthy if heartbeat is within last 5 minutes
        return ($current_time - $last_heartbeat) < 300;
    }
    
    private function check_tola_art_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'vortex_daily_art',
            $wpdb->prefix . 'vortex_artist_participation',
            $wpdb->prefix . 'vortex_royalty_distribution'
        );
        
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
                return false;
            }
        }
        
        return true;
    }
    
    private function check_last_tola_art_generation() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'vortex_daily_art';
        $last_generation = $wpdb->get_var(
            "SELECT generation_status FROM $table ORDER BY date DESC LIMIT 1"
        );
        
        return $last_generation === 'completed';
    }
    
    private function test_database_performance() {
        global $wpdb;
        
        $start_time = microtime(true);
        $wpdb->get_results("SELECT 1 as test_query");
        $query_time = (microtime(true) - $start_time) * 1000;
        
        return array(
            'query_time_ms' => round($query_time, 2),
            'status' => $query_time < 100 ? 'GOOD' : 'SLOW'
        );
    }
    
    private function test_file_system_performance() {
        $upload_dir = wp_upload_dir();
        $test_file = $upload_dir['path'] . '/vortex_test.txt';
        
        $start_time = microtime(true);
        file_put_contents($test_file, 'test');
        $content = file_get_contents($test_file);
        unlink($test_file);
        $file_time = (microtime(true) - $start_time) * 1000;
        
        return array(
            'file_operation_time_ms' => round($file_time, 2),
            'status' => $file_time < 50 ? 'GOOD' : 'SLOW'
        );
    }
    
    private function test_memory_usage() {
        $memory_usage = memory_get_usage(true);
        $memory_limit = ini_get('memory_limit');
        
        return array(
            'current_usage_mb' => round($memory_usage / 1024 / 1024, 2),
            'memory_limit' => $memory_limit,
            'status' => $memory_usage < (128 * 1024 * 1024) ? 'GOOD' : 'HIGH'
        );
    }
    
    private function test_cache_performance() {
        $cache_key = 'vortex_test_cache';
        
        $start_time = microtime(true);
        wp_cache_set($cache_key, 'test_data', '', 300);
        $cached_data = wp_cache_get($cache_key);
        $cache_time = (microtime(true) - $start_time) * 1000;
        
        wp_cache_delete($cache_key);
        
        return array(
            'cache_operation_time_ms' => round($cache_time, 2),
            'cache_working' => $cached_data === 'test_data',
            'status' => $cached_data === 'test_data' && $cache_time < 10 ? 'GOOD' : 'SLOW'
        );
    }
    
    private function test_api_endpoint($endpoint) {
        $base_url = home_url();
        $test_url = $base_url . $endpoint;
        
        $response = wp_remote_get($test_url, array('timeout' => 10));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    private function calculate_overall_test_score($test_results) {
        $scores = array();
        
        foreach ($test_results as $category => $result) {
            if (isset($result['success_rate'])) {
                $scores[] = $result['success_rate'];
            } elseif (isset($result['health_percentage'])) {
                $scores[] = $result['health_percentage'];
            } elseif (isset($result['connectivity_rate'])) {
                $scores[] = $result['connectivity_rate'];
            } elseif (isset($result['performance_score'])) {
                $scores[] = $result['performance_score'];
            }
        }
        
        return !empty($scores) ? round(array_sum($scores) / count($scores), 1) : 0;
    }
    
    private function generate_recommendations($test_results) {
        $recommendations = array();
        
        foreach ($test_results as $category => $result) {
            if (isset($result['status']) && $result['status'] === 'FAIL') {
                switch ($category) {
                    case 'archer_orchestrator':
                        $recommendations[] = 'ARCHER Orchestrator needs attention - check class loading and scheduling';
                        break;
                    case 'ai_agents':
                        $recommendations[] = 'AI agents require optimization - verify agent classes and heartbeat systems';
                        break;
                    case 'runpod_vault':
                        $recommendations[] = 'RunPod Vault connectivity issues - verify API keys and endpoint configuration';
                        break;
                    case 's3_integration':
                        $recommendations[] = 'AWS S3 integration problems - check credentials and bucket permissions';
                        break;
                    case 'tola_art_automation':
                        $recommendations[] = 'TOLA-ART automation needs setup - verify scheduling and database tables';
                        break;
                    case 'performance':
                        $recommendations[] = 'System performance is below optimal - consider resource optimization';
                        break;
                }
            }
        }
        
        if (empty($recommendations)) {
            $recommendations[] = 'All systems are functioning optimally!';
        }
        
        return $recommendations;
    }
    
    private function log_test($message) {
        error_log("[VORTEX_AUTOMATION_TEST] {$message}");
    }
    
    /**
     * AJAX HANDLER FOR RUNNING TESTS
     */
    public function ajax_run_automation_tests() {
        check_ajax_referer('vortex_automation_tests', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $test_results = $this->run_full_automation_test();
        wp_send_json_success($test_results);
    }
}

// Initialize testing suite
add_action('wp_ajax_vortex_run_automation_tests', array(VORTEX_Automation_Testing_Suite::get_instance(), 'ajax_run_automation_tests')); 