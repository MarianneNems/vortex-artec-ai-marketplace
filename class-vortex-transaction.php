<?php
/**
 * Transaction Processing Enhancement for TOLA Enforcement
 */

// Add this to the existing VORTEX_Transaction class

/**
 * Validate currency type to enforce TOLA
 *
 * @since 1.0.0
 * @param array $data Transaction data
 * @return array|WP_Error Validated data or error
 */
private function validate_currency($data) {
    // If currency type is not set, default to TOLA
    if (!isset($data['currency_type'])) {
        $data['currency_type'] = 'tola_credit';
    }
    
    // Apply filter to enforce TOLA (will be enforced by validator)
    $data['currency_type'] = apply_filters('vortex_transaction_currency', $data['currency_type'], $data);
    
    // If it's not TOLA, reject the transaction
    if ($data['currency_type'] !== 'tola_credit') {
        return new WP_Error(
            'invalid_currency',
            __('Only TOLA tokens can be used for transactions in the VORTEX marketplace', 'vortex')
        );
    }
    
    return $data;
}

/**
 * Create a new transaction
 *
 * @since 1.0.0
 * @param array $transaction_data Transaction data
 * @return int|WP_Error Transaction ID or error
 */
public function create($transaction_data) {
    global $wpdb;
    
    // Start transaction for atomicity
    $wpdb->query('START TRANSACTION');
    
    try {
        // Validate transaction data
        $validation = $this->validate_transaction_data($transaction_data);
        if (is_wp_error($validation)) {
            $wpdb->query('ROLLBACK');
            return $validation;
        }
        
        // Validate and enforce TOLA as currency
        $currency_validation = $this->validate_currency($transaction_data);
        if (is_wp_error($currency_validation)) {
            $wpdb->query('ROLLBACK');
            return $currency_validation;
        }
        $transaction_data = $currency_validation;
        
        // Run the transaction through the validator
        $valid = apply_filters('vortex_pre_process_transaction', true, $transaction_data);
        if (is_wp_error($valid)) {
            $wpdb->query('ROLLBACK');
            return $valid;
        }
        
        // Prepare transaction data for database insertion
        $table_name = $wpdb->prefix . 'vortex_transactions';
        
        $insert_data = array(
            'type' => sanitize_text_field($transaction_data['type']),
            'from_user_id' => intval($transaction_data['from_user_id'] ?? 0),
            'to_user_id' => intval($transaction_data['to_user_id'] ?? 0),
            'amount' => floatval($transaction_data['amount']),
            'currency_type' => sanitize_text_field($transaction_data['currency_type']),
            'status' => 'pending',
            'transaction_hash' => $this->generate_transaction_hash($transaction_data),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'metadata' => maybe_serialize($transaction_data['metadata'] ?? array())
        );
        
        // Insert transaction record
        $result = $wpdb->insert($table_name, $insert_data);
        
        if ($result === false) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('db_error', __('Failed to create transaction record', 'vortex'));
        }
        
        $transaction_id = $wpdb->insert_id;
        
        // Update transaction status to completed if validation passes
        $update_result = $wpdb->update(
            $table_name,
            array(
                'status' => 'completed',
                'updated_at' => current_time('mysql')
            ),
            array('id' => $transaction_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($update_result === false) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('db_error', __('Failed to update transaction status', 'vortex'));
        }
        
        // Commit the transaction
        $wpdb->query('COMMIT');
        
        // Fire action for successful transaction creation
        do_action('vortex_transaction_created', $transaction_id, $transaction_data);
        
        return $transaction_id;
        
    } catch (Exception $e) {
        // Rollback on any exception
        $wpdb->query('ROLLBACK');
        error_log('Transaction creation failed: ' . $e->getMessage());
        return new WP_Error('transaction_error', __('Transaction creation failed', 'vortex'));
    }
}

/**
 * Generate a unique transaction hash
 *
 * @param array $transaction_data Transaction data
 * @return string Transaction hash
 */
private function generate_transaction_hash($transaction_data) {
    $hash_data = array(
        $transaction_data['type'],
        $transaction_data['from_user_id'] ?? 0,
        $transaction_data['to_user_id'] ?? 0,
        $transaction_data['amount'],
        $transaction_data['currency_type'],
        time(),
        wp_generate_uuid4()
    );
    
    return hash('sha256', implode('|', $hash_data));
}

/**
 * Validate transaction data
 *
 * @param array $data Transaction data
 * @return array|WP_Error Validated data or error
 */
private function validate_transaction_data($data) {
    $required_fields = array('type', 'amount', 'currency_type');
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return new WP_Error('missing_field', sprintf(__('Required field %s is missing', 'vortex'), $field));
        }
    }
    
    // Validate amount is positive
    if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
        return new WP_Error('invalid_amount', __('Transaction amount must be positive', 'vortex'));
    }
    
    // Validate transaction type
    $allowed_types = array('purchase', 'sale', 'transfer', 'royalty_payment', 'commission');
    if (!in_array($data['type'], $allowed_types)) {
        return new WP_Error('invalid_type', __('Invalid transaction type', 'vortex'));
    }
    
    return $data;
} 