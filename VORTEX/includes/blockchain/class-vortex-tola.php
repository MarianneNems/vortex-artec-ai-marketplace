<?php
/**
 * Solana Integration
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/blockchain
 * @author     Marianne Nems
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Solana Integration Class.
 *
 * This class integrates native Solana functionality with the WordPress plugin.
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/blockchain
 * @author     Marianne Nems
 */
class Vortex_TOLA {

    /**
     * The RPC endpoint URL.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $rpc_url    The Solana RPC URL.
     */
    private $rpc_url;

    /**
     * Token decimals.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $token_decimals    The token decimals (9 for SOL).
     */
    private $token_decimals;

    /**
     * Solana network.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $network    The Solana network (mainnet-beta, testnet, devnet).
     */
    private $network;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->load_dependencies();
        $this->setup_solana_details();
        $this->define_hooks();
    }

    /**
     * Load the required dependencies for this class.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        // Load dependencies for Solana PHP integration
        require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'vendor/autoload.php';
    }

    /**
     * Set up the Solana details from settings.
     *
     * @since    1.0.0
     * @access   private
     */
    private function setup_solana_details() {
        $this->rpc_url = get_option('vortex_solana_rpc_url', 'https://api.mainnet-beta.solana.com');
        $this->network = get_option('vortex_solana_network', 'mainnet-beta');
        $this->token_decimals = get_option('vortex_solana_decimals', 9); // Default to 9 decimals for SOL
    }

    /**
     * Define the hooks for the Solana integration.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_hooks() {
        // Enqueue scripts for Solana integration
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Ajax handlers for token operations
        add_action('wp_ajax_vortex_get_solana_balance', array($this, 'ajax_get_solana_balance'));
        add_action('wp_ajax_vortex_process_transaction', array($this, 'ajax_process_transaction'));
        add_action('wp_ajax_vortex_disconnect_wallet', array($this, 'ajax_disconnect_wallet'));
        
        // Shortcodes
        add_shortcode('vortex_solana_balance', array($this, 'shortcode_solana_balance'));
        add_shortcode('vortex_solana_wallet', array($this, 'shortcode_solana_wallet'));
        
        // Add cron job to update pending transactions
        add_action('vortex_update_pending_transactions', array($this, 'update_pending_transactions'));
        
        // Schedule the cron if not already scheduled
        if (!wp_next_scheduled('vortex_update_pending_transactions')) {
            wp_schedule_event(time(), 'hourly', 'vortex_update_pending_transactions');
        }
    }

    /**
     * Enqueue scripts for the Solana integration.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Enqueue Solana Web3.js
        wp_enqueue_script('solana-web3', 'https://unpkg.com/@solana/web3.js@latest/lib/index.iife.min.js', array(), '1.0.0', false);
        
        // Enqueue wallet adapter
        wp_enqueue_script('solana-wallet-adapter', 'https://unpkg.com/@solana/wallet-adapter-wallets@latest/lib/index.iife.min.js', array('solana-web3'), '1.0.0', false);
        
        // Enqueue our custom script
        wp_enqueue_script('vortex-solana', VORTEX_AI_MARKETPLACE_PLUGIN_URL . 'public/js/vortex-solana.js', array('jquery', 'solana-web3', 'solana-wallet-adapter'), VORTEX_AI_MARKETPLACE_VERSION, false);
        
        // Pass variables to script
        wp_localize_script('vortex-solana', 'vortexSolana', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'rpcUrl' => $this->rpc_url,
            'network' => $this->network,
            'nonce' => wp_create_nonce('vortex_solana_nonce')
        ));
    }

    /**
     * Ajax handler for getting Solana balance.
     *
     * @since    1.0.0
     */
    public function ajax_get_solana_balance() {
        // Verify nonce
        check_ajax_referer('vortex_solana_nonce', 'nonce');
        
        // Rate limiting
        $user_id = get_current_user_id();
        $rate_limit_key = 'vortex_rate_limit_balance_' . $user_id;
        $last_request = get_transient($rate_limit_key);
        
        if ($last_request !== false && (time() - $last_request) < 5) { // 5 second cooldown
            wp_send_json_error(array('message' => 'Please wait before making another request'));
            return;
        }
        
        // Set rate limit
        set_transient($rate_limit_key, time(), 60); // Keep for 1 minute
        
        // Get parameters
        $wallet_address = isset($_POST['wallet_address']) ? sanitize_text_field($_POST['wallet_address']) : '';
        
        if (empty($wallet_address)) {
            wp_send_json_error(array('message' => 'Wallet address is required'));
            return;
        }
        
        // Get the balance
        $balance = $this->get_solana_balance($wallet_address);
        
        wp_send_json_success(array(
            'balance' => $balance,
            'formatted_balance' => number_format($balance, $this->token_decimals > 4 ? 4 : $this->token_decimals) . ' SOL'
        ));
    }

    /**
     * Ajax handler for processing a Solana transaction.
     *
     * @since    1.0.0
     */
    public function ajax_process_transaction() {
        // Verify nonce
        check_ajax_referer('vortex_solana_nonce', 'nonce');
        
        // Get parameters
        $from_address = isset($_POST['from_address']) ? sanitize_text_field($_POST['from_address']) : '';
        $to_address = isset($_POST['to_address']) ? sanitize_text_field($_POST['to_address']) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $transaction_data = isset($_POST['transaction_data']) ? $_POST['transaction_data'] : array();
        
        if (empty($from_address) || empty($to_address) || $amount <= 0) {
            wp_send_json_error(array('message' => 'Invalid transaction parameters'));
            return;
        }
        
        // Record the transaction
        $transaction_id = $this->record_transaction($from_address, $to_address, $amount, $transaction_data);
        
        wp_send_json_success(array(
            'transaction_id' => $transaction_id,
            'message' => 'Transaction processed successfully'
        ));
    }

    /**
     * Get Solana balance for a wallet address.
     *
     * @since    1.0.0
     * @param    string    $wallet_address    The wallet address.
     * @return   float     The SOL balance.
     */
    public function get_solana_balance($wallet_address) {
        try {
            // Validate Solana wallet address format (base58)
            if (!preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $wallet_address)) {
                error_log('Invalid Solana address format: ' . $wallet_address);
                return 0;
            }
            
            // Prepare the RPC request
            $data = array(
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'getBalance',
                'params' => array($wallet_address)
            );
            
            $response = $this->send_solana_rpc_request($data);
            
            if (isset($response['result']['value'])) {
                // Convert from lamports to SOL
                $balance = $response['result']['value'] / pow(10, $this->token_decimals);
                return $balance;
            } else {
                error_log('Error fetching Solana balance: ' . json_encode($response));
                return $this->get_cached_balance($wallet_address);
            }
        } catch (Exception $e) {
            error_log('Exception in get_solana_balance: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return $this->get_cached_balance($wallet_address);
        }
    }

    /**
     * Send an RPC request to the Solana network.
     *
     * @since    1.0.0
     * @param    array     $data    The request data.
     * @return   array     The response.
     */
    private function send_solana_rpc_request($data) {
        $ch = curl_init($this->rpc_url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $response = curl_exec($ch);
        $err = curl_error($ch);
        
        curl_close($ch);
        
        if ($err) {
            error_log('cURL Error: ' . $err);
            return array('error' => $err);
        }
        
        return json_decode($response, true);
    }

    /**
     * Get cached balance for a wallet address.
     *
     * @since    1.0.0
     * @param    string    $wallet_address    The wallet address.
     * @return   float     The cached SOL balance.
     */
    private function get_cached_balance($wallet_address) {
        $current_user_id = get_current_user_id();
        if ($current_user_id) {
            $user_wallet = get_user_meta($current_user_id, 'vortex_wallet_address', true);
            if ($user_wallet === $wallet_address) {
                return floatval(get_user_meta($current_user_id, 'vortex_solana_balance', true));
            }
        }
        
        // Check cache
        $cached_balance = get_transient('vortex_solana_balance_' . $wallet_address);
        if ($cached_balance !== false) {
            return floatval($cached_balance);
        }
        
        return 0;
    }

    /**
     * Record a transaction in the database.
     *
     * @since    1.0.0
     * @param    string    $from_address      The sender address.
     * @param    string    $to_address        The recipient address.
     * @param    float     $amount            The transaction amount.
     * @param    array     $transaction_data  Additional transaction data.
     * @return   string    The transaction ID.
     */
    public function record_transaction($from_address, $to_address, $amount, $transaction_data) {
        global $wpdb;
        
        // Table name for transactions
        $table_name = $wpdb->prefix . 'vortex_transactions';
        
        // Generate transaction ID
        $transaction_id = isset($transaction_data['signature']) ? $transaction_data['signature'] : 'tx_' . uniqid();
        
        // Transaction status (pending by default if it's a real blockchain tx)
        $status = isset($transaction_data['signature']) ? 'pending' : 'completed';
        
        // Insert transaction
        $wpdb->insert(
            $table_name,
            array(
                'transaction_id' => $transaction_id,
                'from_address' => $from_address,
                'to_address' => $to_address,
                'amount' => $amount,
                'transaction_data' => json_encode($transaction_data),
                'status' => $status,
                'blockchain_tx_hash' => isset($transaction_data['signature']) ? $transaction_data['signature'] : '',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s')
        );
        
        // If this is a mock transaction, update user balances immediately
        if ($status === 'completed') {
            $this->update_user_balances($from_address, $to_address, $amount);
        }
        
        return $transaction_id;
    }

    /**
     * Update user balances after a transaction.
     *
     * @since    1.0.0
     * @param    string    $from_address    The sender address.
     * @param    string    $to_address      The recipient address.
     * @param    float     $amount          The transaction amount.
     */
    private function update_user_balances($from_address, $to_address, $amount) {
        // Find users by wallet address
        $users = get_users(array(
            'meta_key' => 'vortex_wallet_address',
            'meta_value' => array($from_address, $to_address),
            'meta_compare' => 'IN'
        ));
        
        foreach ($users as $user) {
            $user_wallet = get_user_meta($user->ID, 'vortex_wallet_address', true);
            $current_balance = floatval(get_user_meta($user->ID, 'vortex_solana_balance', true));
            
            if ($user_wallet === $from_address) {
                // Deduct from sender
                update_user_meta($user->ID, 'vortex_solana_balance', max(0, $current_balance - $amount));
            } elseif ($user_wallet === $to_address) {
                // Add to recipient
                update_user_meta($user->ID, 'vortex_solana_balance', $current_balance + $amount);
            }
        }
    }

    /**
     * Shortcode for displaying Solana balance.
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes.
     * @return   string    The shortcode output.
     */
    public function shortcode_solana_balance($atts) {
        $atts = shortcode_atts(array(
            'address' => '',
            'show_label' => 'true'
        ), $atts, 'vortex_solana_balance');
        
        $address = $atts['address'];
        $show_label = filter_var($atts['show_label'], FILTER_VALIDATE_BOOLEAN);
        
        // If no address provided, use current user's address
        if (empty($address)) {
            $current_user_id = get_current_user_id();
            if ($current_user_id) {
                $address = get_user_meta($current_user_id, 'vortex_wallet_address', true);
            }
        }
        
        // If still no address, return a message
        if (empty($address)) {
            return '<div class="vortex-solana-balance-error">No wallet address available</div>';
        }
        
        // Get balance
        $balance = $this->get_solana_balance($address);
        $formatted_balance = number_format($balance, 4) . ' SOL';
        
        // Build output
        $output = '<div class="vortex-solana-balance">';
        if ($show_label) {
            $output .= '<span class="vortex-solana-balance-label">SOL Balance: </span>';
        }
        $output .= '<span class="vortex-solana-balance-amount">' . esc_html($formatted_balance) . '</span>';
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Shortcode for displaying Solana wallet interface.
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes.
     * @return   string    The shortcode output.
     */
    public function shortcode_solana_wallet($atts) {
        $atts = shortcode_atts(array(
            'show_transactions' => 'true',
            'show_send' => 'true'
        ), $atts, 'vortex_solana_wallet');
        
        $show_transactions = filter_var($atts['show_transactions'], FILTER_VALIDATE_BOOLEAN);
        $show_send = filter_var($atts['show_send'], FILTER_VALIDATE_BOOLEAN);
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="vortex-solana-wallet-error">Please log in to access your Solana wallet</div>';
        }
        
        // Get current user data
        $current_user_id = get_current_user_id();
        $wallet_address = get_user_meta($current_user_id, 'vortex_wallet_address', true);
        $balance = $this->get_solana_balance($wallet_address);
        $formatted_balance = number_format($balance, 4) . ' SOL';
        
        // Start building output
        ob_start();
        ?>
        <div class="vortex-solana-wallet">
            <div class="vortex-solana-wallet-header">
                <h3><?php _e('Your Solana Wallet', 'vortex-ai-marketplace'); ?></h3>
                
                <?php if (empty($wallet_address)): ?>
                    <div class="vortex-solana-wallet-connect">
                        <p><?php _e('Connect your wallet to manage your SOL tokens', 'vortex-ai-marketplace'); ?></p>
                        <button class="vortex-connect-wallet-button"><?php _e('Connect Wallet', 'vortex-ai-marketplace'); ?></button>
                    </div>
                <?php else: ?>
                    <div class="vortex-solana-wallet-info">
                        <div class="vortex-solana-wallet-address">
                            <span class="label"><?php _e('Wallet Address:', 'vortex-ai-marketplace'); ?></span>
                            <span class="value"><?php echo esc_html(substr($wallet_address, 0, 6) . '...' . substr($wallet_address, -4)); ?></span>
                            <button class="vortex-copy-address-button" data-address="<?php echo esc_attr($wallet_address); ?>"><?php _e('Copy', 'vortex-ai-marketplace'); ?></button>
                            <button class="vortex-disconnect-wallet-button"><?php _e('Disconnect', 'vortex-ai-marketplace'); ?></button>
                        </div>
                        <div class="vortex-solana-wallet-balance">
                            <span class="label"><?php _e('Balance:', 'vortex-ai-marketplace'); ?></span>
                            <span class="value"><?php echo esc_html($formatted_balance); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($wallet_address) && $show_send): ?>
                <div class="vortex-solana-wallet-send">
                    <h4><?php _e('Send SOL', 'vortex-ai-marketplace'); ?></h4>
                    <form class="vortex-solana-send-form">
                        <div class="form-group">
                            <label for="recipient_address"><?php _e('Recipient Address', 'vortex-ai-marketplace'); ?></label>
                            <input type="text" id="recipient_address" name="recipient_address" required>
                        </div>
                        <div class="form-group">
                            <label for="amount"><?php _e('Amount (SOL)', 'vortex-ai-marketplace'); ?></label>
                            <input type="number" id="amount" name="amount" min="0.000001" step="0.000001" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="vortex-solana-send-button"><?php _e('Send', 'vortex-ai-marketplace'); ?></button>
                        </div>
                        <div class="vortex-solana-send-result"></div>
                    </form>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($wallet_address) && $show_transactions): ?>
                <div class="vortex-solana-wallet-transactions">
                    <h4><?php _e('Recent Transactions', 'vortex-ai-marketplace'); ?></h4>
                    <?php 
                    $transactions = $this->get_user_transactions($current_user_id, 5);
                    if (!empty($transactions)): 
                    ?>
                        <table class="vortex-transaction-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Date', 'vortex-ai-marketplace'); ?></th>
                                    <th><?php _e('Type', 'vortex-ai-marketplace'); ?></th>
                                    <th><?php _e('Amount', 'vortex-ai-marketplace'); ?></th>
                                    <th><?php _e('Status', 'vortex-ai-marketplace'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo esc_html($transaction->created_at); ?></td>
                                        <td><?php echo esc_html($transaction->type); ?></td>
                                        <td><?php echo esc_html(number_format($transaction->amount, 4) . ' SOL'); ?></td>
                                        <td><?php echo esc_html($transaction->status); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p><?php _e('No transactions found', 'vortex-ai-marketplace'); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get user transactions.
     *
     * @since    1.0.0
     * @param    int       $user_id    The user ID.
     * @param    int       $limit      Maximum number of transactions to return.
     * @return   array     The user transactions.
     */
    public function get_user_transactions($user_id, $limit = 10) {
        global $wpdb;
        
        // Get user wallet address
        $wallet_address = get_user_meta($user_id, 'vortex_wallet_address', true);
        
        if (empty($wallet_address)) {
            return array();
        }
        
        // Table name for transactions
        $table_name = $wpdb->prefix . 'vortex_transactions';
        
        // Get transactions where user is sender or recipient
        $transactions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *, 
                CASE 
                    WHEN from_address = %s THEN 'Sent' 
                    WHEN to_address = %s THEN 'Received' 
                END as type
                FROM {$table_name}
                WHERE from_address = %s OR to_address = %s
                ORDER BY created_at DESC
                LIMIT %d",
                $wallet_address, $wallet_address, $wallet_address, $wallet_address, $limit
            )
        );
        
        return $transactions;
    }

    /**
     * Verify transaction status on the Solana blockchain.
     *
     * @since    1.0.0
     * @param    string    $signature    The transaction signature.
     * @return   string    The transaction status.
     */
    public function verify_transaction_status($signature) {
        try {
            // Prepare the RPC request
            $data = array(
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'getSignatureStatuses',
                'params' => array(
                    array($signature),
                    array('searchTransactionHistory' => true)
                )
            );
            
            $response = $this->send_solana_rpc_request($data);
            
            if (isset($response['result']['value'][0])) {
                $status_info = $response['result']['value'][0];
                
                if ($status_info === null) {
                    return 'pending';
                }
                
                if (isset($status_info['err']) && $status_info['err'] === null) {
                    return 'completed';
                } else {
                    return 'failed';
                }
            } else {
                error_log('Error verifying Solana transaction: ' . json_encode($response));
                return 'unknown';
            }
        } catch (Exception $e) {
            error_log('Exception in verify_transaction_status: ' . $e->getMessage());
            return 'unknown';
        }
    }

    /**
     * Update status of pending transactions.
     *
     * @since    1.0.0
     */
    public function update_pending_transactions() {
        global $wpdb;
        
        // Table name for transactions
        $table_name = $wpdb->prefix . 'vortex_transactions';
        
        // Get all pending transactions
        $pending_transactions = $wpdb->get_results(
            "SELECT * FROM {$table_name} WHERE status = 'pending' AND blockchain_tx_hash != ''"
        );
        
        foreach ($pending_transactions as $transaction) {
            $status = $this->verify_transaction_status($transaction->blockchain_tx_hash);
            
            if ($status !== 'pending') {
                // Update transaction status
                $wpdb->update(
                    $table_name,
                    array('status' => $status),
                    array('transaction_id' => $transaction->transaction_id),
                    array('%s'),
                    array('%s')
                );
                
                // If completed, update user balances
                if ($status === 'completed') {
                    $this->update_user_balances(
                        $transaction->from_address,
                        $transaction->to_address,
                        $transaction->amount
                    );
                }
            }
        }
    }

    /**
     * Create or update plugin database tables.
     *
     * @since    1.0.0
     */
    public function create_database_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_transactions';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            transaction_id varchar(88) NOT NULL,
            from_address varchar(44) NOT NULL,
            to_address varchar(44) NOT NULL,
            amount decimal(18,9) NOT NULL,
            transaction_data text NOT NULL,
            status varchar(20) NOT NULL,
            blockchain_tx_hash varchar(88) NOT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            KEY transaction_id (transaction_id),
            KEY from_address (from_address),
            KEY to_address (to_address),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Ajax handler for disconnecting a wallet.
     *
     * @since    1.0.0
     */
    public function ajax_disconnect_wallet() {
        // Verify nonce
        check_ajax_referer('vortex_solana_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not logged in'));
            return;
        }
        
        // Remove wallet address
        delete_user_meta($user_id, 'vortex_wallet_address');
        
        wp_send_json_success(array('message' => 'Wallet disconnected successfully'));
    }
} 