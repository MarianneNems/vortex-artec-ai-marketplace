<?php
/**
 * The TOLA wallet management class.
 *
 * @since      3.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_AI_Marketplace_Wallet {

    /**
     * Solana RPC URL
     *
     * @since    3.0.0
     * @access   private
     * @var      string    $rpc_url    Solana RPC URL
     */
    private $rpc_url;

    /**
     * Initialize the class and set its properties.
     *
     * @since    3.0.0
     */
    public function __construct() {
        $this->rpc_url = get_option('vortex_solana_rpc_url', 'https://api.mainnet-beta.solana.com');
        
        add_action('init', array($this, 'init_wallet_system'));
        add_action('wp_ajax_vortex_get_balance', array($this, 'ajax_get_balance'));
        add_action('wp_ajax_vortex_transfer_tokens', array($this, 'ajax_transfer_tokens'));
    }

    /**
     * Initialize wallet system
     *
     * @since    3.0.0
     */
    public function init_wallet_system() {
        // Create wallet tables if they don't exist
        $this->create_wallet_tables();
    }

    /**
     * Create wallet-related database tables
     *
     * @since    3.0.0
     */
    private function create_wallet_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_token_transactions';
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            amount decimal(18,8) NOT NULL,
            type varchar(20) NOT NULL,
            source varchar(50) NOT NULL,
            transaction_hash varchar(100) DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY type (type),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Create wallets table
        $wallets_table = $wpdb->prefix . 'vortex_wallets';
        
        $sql_wallets = "CREATE TABLE $wallets_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            wallet_address varchar(100) NOT NULL,
            wallet_type varchar(20) DEFAULT 'solana',
            private_key_encrypted text DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_wallet (user_id, wallet_type),
            KEY wallet_address (wallet_address)
        ) $charset_collate;";

        dbDelta($sql_wallets);
    }

    /**
     * Credit tokens to a user
     *
     * @since    3.0.0
     * @param    int    $user_id    User ID
     * @param    int    $amount     Amount of tokens to credit
     * @return   bool               Success status
     */
    public function credit_tokens($user_id, $amount) {
        global $wpdb;

        try {
            // Start transaction
            $wpdb->query('START TRANSACTION');

            // Update user balance
            $current_balance = (int) get_user_meta($user_id, 'vortex_tola_balance', true);
            $new_balance = $current_balance + $amount;
            update_user_meta($user_id, 'vortex_tola_balance', $new_balance);

            // Create transaction record
            $transaction_id = $this->create_transaction_record($user_id, $amount, 'credit', 'purchase');

            // Attempt blockchain transaction
            $blockchain_result = $this->execute_blockchain_transaction($user_id, $amount, 'mint');
            
            if ($blockchain_result['success']) {
                // Update transaction with hash
                $this->update_transaction_status($transaction_id, 'completed', $blockchain_result['hash']);
                $wpdb->query('COMMIT');
                
                return true;
            } else {
                // Rollback on blockchain failure but keep local balance
                $this->update_transaction_status($transaction_id, 'blockchain_pending', null);
                $wpdb->query('COMMIT');
                
                // Schedule retry
                $this->schedule_blockchain_retry($transaction_id);
                
                return true; // Still return true as local balance is updated
            }

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log("VORTEX Wallet Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Debit tokens from a user
     *
     * @since    3.0.0
     * @param    int    $user_id    User ID
     * @param    int    $amount     Amount of tokens to debit
     * @return   bool               Success status
     */
    public function debit_tokens($user_id, $amount) {
        $current_balance = (int) get_user_meta($user_id, 'vortex_tola_balance', true);
        
        if ($current_balance < $amount) {
            return false; // Insufficient balance
        }

        $new_balance = $current_balance - $amount;
        update_user_meta($user_id, 'vortex_tola_balance', $new_balance);

        // Create transaction record
        $this->create_transaction_record($user_id, $amount, 'debit', 'usage');

        return true;
    }

    /**
     * Get user's TOLA balance
     *
     * @since    3.0.0
     * @param    int    $user_id    User ID
     * @return   int                Token balance
     */
    public function get_balance($user_id) {
        return (int) get_user_meta($user_id, 'vortex_tola_balance', true);
    }

    /**
     * Create transaction record
     *
     * @since    3.0.0
     * @param    int       $user_id    User ID
     * @param    int       $amount     Amount
     * @param    string    $type       Transaction type
     * @param    string    $source     Transaction source
     * @return   int                   Transaction ID
     */
    private function create_transaction_record($user_id, $amount, $type, $source) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_token_transactions';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'amount' => $amount,
                'type' => $type,
                'source' => $source,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );

        return $wpdb->insert_id;
    }

    /**
     * Update transaction status
     *
     * @since    3.0.0
     * @param    int       $transaction_id    Transaction ID
     * @param    string    $status           New status
     * @param    string    $hash             Transaction hash
     */
    private function update_transaction_status($transaction_id, $status, $hash = null) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_token_transactions';
        
        $update_data = array('status' => $status);
        $format = array('%s');
        
        if ($hash) {
            $update_data['transaction_hash'] = $hash;
            $format[] = '%s';
        }

        $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $transaction_id),
            $format,
            array('%d')
        );
    }

    /**
     * Execute blockchain transaction
     *
     * @since    3.0.0
     * @param    int       $user_id    User ID
     * @param    int       $amount     Amount
     * @param    string    $operation  Operation type
     * @return   array                 Result array
     */
    private function execute_blockchain_transaction($user_id, $amount, $operation) {
        // Simulate blockchain transaction - replace with actual Solana integration
        $solana_settings = $this->get_solana_settings();
        
        if (!$solana_settings['enabled']) {
            return array('success' => false, 'error' => 'Blockchain disabled');
        }

        // Mock transaction for now - implement actual Solana SDK calls
        $mock_hash = 'vortex_' . time() . '_' . $user_id . '_' . $amount;
        
        // In production, this would make actual RPC calls to Solana
        return array(
            'success' => true,
            'hash' => $mock_hash,
            'operation' => $operation
        );
    }

    /**
     * Get Solana settings
     *
     * @since    3.0.0
     * @return   array    Solana configuration
     */
    private function get_solana_settings() {
        return array(
            'enabled' => get_option('vortex_blockchain_enabled', false),
            'rpc_url' => get_option('vortex_solana_rpc_url', $this->rpc_url),
            'token_mint' => get_option('vortex_tola_token_mint', ''),
            'program_id' => get_option('vortex_solana_program_id', '')
        );
    }

    /**
     * Schedule blockchain retry
     *
     * @since    3.0.0
     * @param    int    $transaction_id    Transaction ID
     */
    private function schedule_blockchain_retry($transaction_id) {
        wp_schedule_single_event(time() + 300, 'vortex_retry_blockchain_transaction', array($transaction_id));
    }

    /**
     * Get user transaction history
     *
     * @since    3.0.0
     * @param    int    $user_id    User ID
     * @param    int    $limit      Number of transactions to retrieve
     * @return   array              Transaction history
     */
    public function get_transaction_history($user_id, $limit = 50) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_token_transactions';
        
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

        return $results;
    }

    /**
     * AJAX handler for getting balance
     *
     * @since    3.0.0
     */
    public function ajax_get_balance() {
        check_ajax_referer('wp_rest', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die('Unauthorized');
        }

        $balance = $this->get_balance($user_id);
        
        wp_send_json_success(array(
            'balance' => $balance,
            'formatted' => number_format($balance) . ' TOLA'
        ));
    }

    /**
     * AJAX handler for token transfer
     *
     * @since    3.0.0
     */
    public function ajax_transfer_tokens() {
        check_ajax_referer('wp_rest', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die('Unauthorized');
        }

        $amount = intval($_POST['amount']);
        $recipient = sanitize_text_field($_POST['recipient']);
        
        if ($amount <= 0) {
            wp_send_json_error('Invalid amount');
        }

        $current_balance = $this->get_balance($user_id);
        if ($current_balance < $amount) {
            wp_send_json_error('Insufficient balance');
        }

        // Process transfer
        $success = $this->transfer_tokens($user_id, $recipient, $amount);
        
        if ($success) {
            wp_send_json_success(array(
                'message' => 'Transfer completed',
                'new_balance' => $this->get_balance($user_id)
            ));
        } else {
            wp_send_json_error('Transfer failed');
        }
    }

    /**
     * Transfer tokens between users
     *
     * @since    3.0.0
     * @param    int       $from_user_id    Sender user ID
     * @param    string    $to_identifier   Recipient identifier (user ID or wallet address)
     * @param    int       $amount          Amount to transfer
     * @return   bool                       Success status
     */
    public function transfer_tokens($from_user_id, $to_identifier, $amount) {
        global $wpdb;

        try {
            $wpdb->query('START TRANSACTION');

            // Debit from sender
            if (!$this->debit_tokens($from_user_id, $amount)) {
                throw new Exception('Insufficient balance');
            }

            // Determine recipient
            $to_user_id = is_numeric($to_identifier) ? intval($to_identifier) : $this->get_user_by_wallet($to_identifier);
            
            if (!$to_user_id) {
                throw new Exception('Invalid recipient');
            }

            // Credit to recipient
            $this->credit_tokens($to_user_id, $amount);

            // Create transfer records
            $this->create_transaction_record($from_user_id, $amount, 'transfer_out', 'user_transfer');
            $this->create_transaction_record($to_user_id, $amount, 'transfer_in', 'user_transfer');

            $wpdb->query('COMMIT');
            return true;

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log("VORTEX Transfer Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user ID by wallet address
     *
     * @since    3.0.0
     * @param    string    $wallet_address    Wallet address
     * @return   int|null                     User ID or null
     */
    private function get_user_by_wallet($wallet_address) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_wallets';
        
        $user_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT user_id FROM $table_name WHERE wallet_address = %s AND is_active = 1",
                $wallet_address
            )
        );

        return $user_id ? intval($user_id) : null;
    }

    /**
     * Create or get user wallet
     *
     * @since    3.0.0
     * @param    int    $user_id    User ID
     * @return   array              Wallet information
     */
    public function get_or_create_wallet($user_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vortex_wallets';
        
        // Check if wallet exists
        $existing_wallet = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d AND wallet_type = 'solana' AND is_active = 1",
                $user_id
            ),
            ARRAY_A
        );

        if ($existing_wallet) {
            return $existing_wallet;
        }

        // Create new wallet
        $wallet_address = $this->generate_wallet_address();
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'wallet_address' => $wallet_address,
                'wallet_type' => 'solana',
                'is_active' => 1,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%d', '%s')
        );

        return array(
            'id' => $wpdb->insert_id,
            'user_id' => $user_id,
            'wallet_address' => $wallet_address,
            'wallet_type' => 'solana',
            'is_active' => 1
        );
    }

    /**
     * Generate a mock wallet address
     *
     * @since    3.0.0
     * @return   string    Generated wallet address
     */
    private function generate_wallet_address() {
        // Generate a mock Solana wallet address
        // In production, this would use actual Solana key generation
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz123456789';
        $address = '';
        for ($i = 0; $i < 44; $i++) {
            $address .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $address;
    }
} 