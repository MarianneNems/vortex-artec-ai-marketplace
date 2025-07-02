<?php
/**
 * Wallet API handler for TOLA blockchain wallet management.
 *
 * @since      2.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/api
 */
class Vortex_Wallet_API {

    private $namespace;

    public function __construct() {
        $this->namespace = 'vortex/v1';
    }

    public function register_routes() {
        register_rest_route($this->namespace, '/wallet/connect', array(
            'methods' => 'POST',
            'callback' => array($this, 'connect_wallet'),
            'permission_callback' => array($this, 'check_user_permission'),
        ));

        register_rest_route($this->namespace, '/wallet/balance', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_balance'),
            'permission_callback' => array($this, 'check_user_permission'),
        ));

        register_rest_route($this->namespace, '/wallet/transactions', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_transactions'),
            'permission_callback' => array($this, 'check_user_permission'),
        ));
    }

    public function connect_wallet($request) {
        $wallet_address = $request->get_param('wallet_address');
        $wallet_type = $request->get_param('wallet_type'); // phantom, solflare, etc.
        
        if (empty($wallet_address)) {
            return new WP_Error('missing_wallet', 'Wallet address is required', array('status' => 400));
        }

        $user_id = get_current_user_id();
        update_user_meta($user_id, 'vortex_wallet_address', $wallet_address);
        update_user_meta($user_id, 'vortex_wallet_type', $wallet_type);
        update_user_meta($user_id, 'vortex_wallet_connected', current_time('mysql'));

        return new WP_REST_Response(array(
            'success' => true,
            'wallet_address' => $wallet_address,
            'wallet_type' => $wallet_type,
            'message' => 'Wallet connected successfully',
        ), 200);
    }

    public function get_balance($request) {
        $user_id = get_current_user_id();
        $wallet_address = get_user_meta($user_id, 'vortex_wallet_address', true);
        
        if (empty($wallet_address)) {
            return new WP_Error('no_wallet', 'No wallet connected', array('status' => 400));
        }

        // Mock balance for demonstration - in real implementation, query Solana blockchain
        $balances = array(
            'SOL' => 1.5,
            'TOLA' => 150.75,
            'USD_EQUIVALENT' => 150.75, // 1:1 conversion
        );

        return new WP_REST_Response(array(
            'success' => true,
            'wallet_address' => $wallet_address,
            'balances' => $balances,
            'last_updated' => current_time('mysql'),
        ), 200);
    }

    public function get_transactions($request) {
        $user_id = get_current_user_id();
        $limit = $request->get_param('limit') ?: 10;

        // Mock transactions for demonstration
        $transactions = array(
            array(
                'id' => 'tx_001',
                'type' => 'purchase',
                'amount' => -25.00,
                'currency' => 'TOLA',
                'description' => 'NFT Purchase - Digital Sunset',
                'timestamp' => current_time('mysql'),
                'status' => 'completed',
            ),
            array(
                'id' => 'tx_002',
                'type' => 'sale',
                'amount' => 50.00,
                'currency' => 'TOLA',
                'description' => 'NFT Sale - Abstract Dreams',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'status' => 'completed',
            ),
        );

        return new WP_REST_Response(array(
            'success' => true,
            'transactions' => array_slice($transactions, 0, $limit),
            'total_count' => count($transactions),
        ), 200);
    }

    public function check_user_permission($request) {
        return is_user_logged_in();
    }
} 