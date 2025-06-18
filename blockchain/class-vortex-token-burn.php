<?php
/**
 * TOLA Token Burn Handler Class
 *
 * Manages token burning operations for the VORTEX AI Marketplace
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/blockchain
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * TOLA Token Burn Handler Class
 *
 * This class handles token burning operations including
 * recording burn history, verifying burns, and managing
 * the deflation strategy.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/blockchain
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Token_Burn {

    /**
     * The token handler instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Token_Handler $token_handler The token handler instance.
     */
    protected $token_handler;

    /**
     * The blockchain integration instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Blockchain_Integration $blockchain The blockchain integration instance.
     */
    protected $blockchain;

    /**
     * The token contract address.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $contract_address The TOLA token contract address.
     */
    protected $contract_address;

    /**
     * The token contract ABI.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array $contract_abi The TOLA token contract ABI.
     */
    protected $contract_abi;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    Vortex_Token_Handler $token_handler The token handler instance.
     * @param    Vortex_Blockchain_Integration $blockchain The blockchain integration instance.
     */
    public function __construct($token_handler, $blockchain) {
        $this->token_handler = $token_handler;
        $this->blockchain = $blockchain;
        $this->contract_address = get_option('vortex_tola_contract_address', 'H6qNYafSrpCjckH8yVwiPmXYPd1nCNBP8uQMZkv5hkky');
        $this->load_contract_abi();
        $this->register_hooks();
    }

    /**
     * Load the token contract ABI from JSON file.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_contract_abi() {
        $abi_file = plugin_dir_path(dirname(__FILE__)) . 'blockchain/tola-token-abi.json';
        
        if (file_exists($abi_file)) {
            $abi_json = file_get_contents($abi_file);
            $this->contract_abi = json_decode($abi_json, true);
        } else {
            error_log('TOLA token ABI file not found: ' . $abi_file);
            $this->contract_abi = array();
        }
    }

    /**
     * Register hooks related to token burning.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_hooks() {
        // AJAX handlers for token burn operations
        add_action('wp_ajax_vortex_burn_tokens', array($this, 'ajax_burn_tokens'));
        
        // Schedule weekly unclaimed rewards burn
        if (!wp_next_scheduled('vortex_weekly_unclaimed_rewards_burn')) {
            wp_schedule_event(time(), 'weekly', 'vortex_weekly_unclaimed_rewards_burn');
        }
        add_action('vortex_weekly_unclaimed_rewards_burn', array($this, 'burn_unclaimed_rewards'));
        
        // Event entry burn
        add_action('vortex_event_entry', array($this, 'process_event_entry_burn'), 10, 2);
        
        // Optional fee burns
        add_action('vortex_transaction_fee_collected', array($this, 'process_fee_burn'), 10, 2);
    }

    /**
     * Burn tokens from a wallet.
     *
     * @since    1.0.0
     * @param    string $wallet_address The wallet address to burn from.
     * @param    float  $amount The amount to burn.
     * @param    string $reason The reason for burning.
     * @return   mixed Transaction hash or WP_Error on failure.
     */
    public function burn_tokens($wallet_address, $amount, $reason = '') {
        if (empty($wallet_address) || empty($amount)) {
            return new WP_Error('invalid_input', __('Invalid wallet address or amount', 'vortex-ai-marketplace'));
        }

        try {
            // Prepare contract call data
            $data = array(
                'contract_address' => $this->contract_address,
                'method' => 'burn',
                'parameters' => array($amount),
                'from' => $wallet_address,
                'abi' => $this->contract_abi
            );

            // Call blockchain integration to execute write method
            $response = $this->blockchain->send_contract_transaction($data);

            if (is_wp_error($response)) {
                return $response;
            }

            // Log the burn transaction
            $this->log_burn_transaction($wallet_address, $amount, $response, $reason);

            return $response;
        } catch (Exception $e) {
            return new WP_Error('burn_error', $e->getMessage());
        }
    }

    /**
     * Log burn transaction in the database.
     *
     * @since    1.0.0
     * @access   private
     * @param    string $wallet_address Wallet address.
     * @param    float  $amount Amount burned.
     * @param    string $tx_hash Transaction hash.
     * @param    string $reason Reason for burning.
     */
    private function log_burn_transaction($wallet_address, $amount, $tx_hash, $reason = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_token_burns';
        
        $wpdb->insert(
            $table_name,
            array(
                'tx_hash' => $tx_hash,
                'wallet_address' => $wallet_address,
                'amount' => $amount,
                'token_address' => $this->contract_address,
                'burn_time' => current_time('mysql'),
                'reason' => $reason,
                'status' => 'pending'
            ),
            array('%s', '%s', '%f', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Process fee burn.
     *
     * @since    1.0.0
     * @param    float  $fee_amount The fee amount collected.
     * @param    string $transaction_type The transaction type.
     */
    public function process_fee_burn($fee_amount, $transaction_type) {
        // Get burn percentage from settings
        $burn_percentage = get_option('vortex_fee_burn_percentage', 10); // Default 10%
        
        if ($burn_percentage <= 0) {
            return; // No burning configured
        }
        
        // Calculate amount to burn
        $burn_amount = ($fee_amount * $burn_percentage) / 100;
        
        if ($burn_amount <= 0) {
            return; // Nothing to burn
        }
        
        // Get treasury wallet
        $treasury_wallet = get_option('vortex_treasury_wallet_address', '');
        
        if (empty($treasury_wallet)) {
            error_log('Treasury wallet not configured for fee burning');
            return;
        }
        
        // Burn tokens
        $reason = sprintf('Fee burn (%s%%): %s', $burn_percentage, $transaction_type);
        $this->burn_tokens($treasury_wallet, $burn_amount, $reason);
    }

    /**
     * Process event entry burn.
     *
     * @since    1.0.0
     * @param    int    $user_id The user ID.
     * @param    string $event_id The event ID.
     */
    public function process_event_entry_burn($user_id, $event_id) {
        // Get event burn amount from settings or use default
        $burn_amount = get_option('vortex_event_entry_burn_amount', 50); // Default 50 TOLA
        
        if ($burn_amount <= 0) {
            return; // No burning required
        }
        
        // Get user wallet
        $wallet_address = get_user_meta($user_id, 'vortex_wallet_address', true);
        
        if (empty($wallet_address)) {
            return; // No wallet connected
        }
        
        // Check if user has sufficient balance
        if (!$this->token_handler->has_sufficient_balance($wallet_address, $burn_amount)) {
            return; // Insufficient balance
        }
        
        // Burn tokens
        $reason = sprintf('Event entry: %s', $event_id);
        $this->burn_tokens($wallet_address, $burn_amount, $reason);
    }

    /**
     * Burn unclaimed rewards.
     *
     * @since    1.0.0
     */
    public function burn_unclaimed_rewards() {
        global $wpdb;
        
        // Get expired unclaimed rewards
        $rewards_table = $wpdb->prefix . 'vortex_user_rewards';
        $expiry_days = get_option('vortex_rewards_expiry_days', 90); // Default 90 days
        
        $expired_rewards = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$rewards_table} 
                 WHERE claimed = 0 
                 AND created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $expiry_days
            )
        );
        
        if (empty($expired_rewards)) {
            return; // No expired rewards
        }
        
        // Get treasury wallet
        $treasury_wallet = get_option('vortex_treasury_wallet_address', '');
        
        if (empty($treasury_wallet)) {
            error_log('Treasury wallet not configured for unclaimed rewards burning');
            return;
        }
        
        // Calculate total amount to burn
        $total_amount = 0;
        foreach ($expired_rewards as $reward) {
            $total_amount += floatval($reward->amount);
            
            // Mark reward as expired
            $wpdb->update(
                $rewards_table,
                array('status' => 'expired'),
                array('id' => $reward->id),
                array('%s'),
                array('%d')
            );
        }
        
        if ($total_amount <= 0) {
            return; // Nothing to burn
        }
        
        // Burn tokens
        $reason = sprintf('Unclaimed rewards burn: %d rewards expired', count($expired_rewards));
        $this->burn_tokens($treasury_wallet, $total_amount, $reason);
    }

    /**
     * AJAX handler for burning tokens.
     *
     * @since    1.0.0
     */
    public function ajax_burn_tokens() {
        // Check nonce
        check_ajax_referer('vortex_blockchain_nonce', 'nonce');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to perform this action', 'vortex-ai-marketplace')));
            return;
        }
        
        $wallet_address = isset($_POST['wallet_address']) ? sanitize_text_field($_POST['wallet_address']) : '';
        $amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;
        $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';
        
        if (empty($wallet_address) || empty($amount)) {
            wp_send_json_error(array('message' => __('Wallet address and amount are required', 'vortex-ai-marketplace')));
            return;
        }
        
        $result = $this->burn_tokens($wallet_address, $amount, $reason);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        wp_send_json_success(array(
            'transaction_hash' => $result,
            'message' => __('Tokens burned successfully', 'vortex-ai-marketplace')
        ));
    }

    /**
     * Get burn history.
     *
     * @since    1.0.0
     * @param    array  $args Query arguments.
     * @return   array  Burn history.
     */
    public function get_burn_history($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'wallet_address' => '',
            'limit' => 10,
            'offset' => 0,
            'orderby' => 'burn_time',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $table_name = $wpdb->prefix . 'vortex_token_burns';
        
        $query = "SELECT * FROM {$table_name} WHERE 1=1";
        $query_args = array();
        
        if (!empty($args['wallet_address'])) {
            $query .= " AND wallet_address = %s";
            $query_args[] = $args['wallet_address'];
        }
        
        $query .= " ORDER BY {$args['orderby']} {$args['order']}";
        $query .= " LIMIT %d OFFSET %d";
        $query_args[] = $args['limit'];
        $query_args[] = $args['offset'];
        
        $prepared_query = $wpdb->prepare($query, $query_args);
        $results = $wpdb->get_results($prepared_query);
        
        return $results;
    }

    /**
     * Get total burned amount.
     *
     * @since    1.0.0
     * @return   float  Total burned amount.
     */
    public function get_total_burned_amount() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_token_burns';
        
        $total = $wpdb->get_var("SELECT SUM(amount) FROM {$table_name} WHERE status = 'completed'");
        
        return floatval($total);
    }

    /**
     * Get burn statistics by reason.
     *
     * @since    1.0.0
     * @return   array  Burn statistics by reason.
     */
    public function get_burn_statistics_by_reason() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_token_burns';
        
        $results = $wpdb->get_results(
            "SELECT 
                SUBSTRING_INDEX(reason, ':', 1) as reason_type,
                SUM(amount) as total_amount,
                COUNT(*) as burn_count
             FROM {$table_name} 
             WHERE status = 'completed'
             GROUP BY reason_type
             ORDER BY total_amount DESC"
        );
        
        return $results;
    }
} 