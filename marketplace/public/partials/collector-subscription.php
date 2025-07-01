<?php
/**
 * Template for collector subscription
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get current user
$user = wp_get_current_user();
$user_id = $user->ID;

// Check if user is a collector
$is_collector = in_array('vortex_collector', (array) $user->roles);

if (!$is_collector) {
    ?>
    <div class="vortex-notice">
        <p><?php esc_html_e('You need to be registered as a collector to subscribe.', 'vortex-ai-marketplace'); ?></p>
        <p><a href="<?php echo esc_url(home_url('/collector-registration/')); ?>" class="vortex-button"><?php esc_html_e('Register as Collector', 'vortex-ai-marketplace'); ?></a></p>
    </div>
    <?php
    return;
}

// Get collector subscription status
$subscription_status = get_user_meta($user_id, '_vortex_collector_subscription_status', true);
$subscription_expiry = get_user_meta($user_id, '_vortex_collector_subscription_expiry', true);

// Get wallet status
$wallet_address = get_user_meta($user_id, '_vortex_collector_wallet_address', true);
$wallet_connected = !empty($wallet_address);

// Get TOLA balance
$tola_balance = get_user_meta($user_id, 'vortex_tola_balance', true);
if (empty($tola_balance)) {
    $tola_balance = 0;
}

// For subscription purchase, we need the wallet to be connected
if (!$wallet_connected) {
    ?>
    <div class="vortex-wallet-required">
        <div class="vortex-notice vortex-warning">
            <h3><?php esc_html_e('Wallet Connection Required', 'vortex-ai-marketplace'); ?></h3>
            <p><?php esc_html_e('You need to connect your wallet before purchasing a subscription.', 'vortex-ai-marketplace'); ?></p>
            <a href="<?php echo esc_url(home_url('/connect-wallet/')); ?>" class="vortex-button"><?php esc_html_e('Connect Wallet', 'vortex-ai-marketplace'); ?></a>
        </div>
    </div>
    <?php
}

// TOLA amount needed for the subscription
$subscription_price_tola = 399; // This would typically be fetched from plugin settings

// Check if user has enough TOLA
$has_enough_tola = ($tola_balance >= $subscription_price_tola);

// Process subscription form submission
$subscription_error = '';
$subscription_success = false;

if (isset($_POST['subscribe_collector']) && isset($_POST['subscription_nonce']) && 
    wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['subscription_nonce'])), 'collector_subscription')) {
    
    // Make sure wallet is connected
    if (!$wallet_connected) {
        $subscription_error = __('Wallet connection is required for subscription purchase.', 'vortex-ai-marketplace');
    }
    // Check TOLA balance
    elseif (!$has_enough_tola) {
        $subscription_error = sprintf(__('You need at least %d TOLA for this subscription. Please purchase more TOLA.', 'vortex-ai-marketplace'), $subscription_price_tola);
    }
    else {
        // Process subscription purchase
        
        // Deduct TOLA from balance
        update_user_meta($user_id, 'vortex_tola_balance', $tola_balance - $subscription_price_tola);
        
        // Set subscription status
        update_user_meta($user_id, '_vortex_collector_subscription_status', 'active');
        
        // Set subscription expiry (1 year from now)
        $expiry_date = date('Y-m-d H:i:s', strtotime('+1 year'));
        update_user_meta($user_id, '_vortex_collector_subscription_expiry', $expiry_date);
        
        // Record transaction
        // In a real implementation, this would be handled by a dedicated transaction processor
        
        $subscription_success = true;
    }
}
?>

<div class="vortex-subscription-container">
    <h2><?php esc_html_e('Collector Subscription', 'vortex-ai-marketplace'); ?></h2>
    
    <?php if ($subscription_success): ?>
        <div class="vortex-subscription-success">
            <div class="vortex-notice vortex-success">
                <h3><?php esc_html_e('Subscription Successful!', 'vortex-ai-marketplace'); ?></h3>
                <p><?php esc_html_e('Your collector subscription is now active. You can access all marketplace features.', 'vortex-ai-marketplace'); ?></p>
                
                <div class="vortex-subscription-details">
                    <div class="vortex-detail-row">
                        <span class="vortex-detail-label"><?php esc_html_e('Status:', 'vortex-ai-marketplace'); ?></span>
                        <span class="vortex-detail-value vortex-active"><?php esc_html_e('Active', 'vortex-ai-marketplace'); ?></span>
                    </div>
                    <div class="vortex-detail-row">
                        <span class="vortex-detail-label"><?php esc_html_e('Expiry Date:', 'vortex-ai-marketplace'); ?></span>
                        <span class="vortex-detail-value"><?php echo esc_html(date('F j, Y', strtotime($expiry_date))); ?></span>
                    </div>
                </div>
                
                <div class="vortex-next-steps">
                    <p><?php esc_html_e('Next Steps:', 'vortex-ai-marketplace'); ?></p>
                    <ul>
                        <li><a href="<?php echo esc_url(home_url('/collector-dashboard/')); ?>"><?php esc_html_e('Go to your Dashboard', 'vortex-ai-marketplace'); ?></a></li>
                        <li><a href="<?php echo esc_url(home_url('/marketplace/')); ?>"><?php esc_html_e('Browse the Marketplace', 'vortex-ai-marketplace'); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    <?php elseif ($subscription_status === 'active'): ?>
        <div class="vortex-already-subscribed">
            <div class="vortex-notice vortex-info">
                <h3><?php esc_html_e('Active Subscription', 'vortex-ai-marketplace'); ?></h3>
                <p><?php esc_html_e('You already have an active collector subscription.', 'vortex-ai-marketplace'); ?></p>
                
                <div class="vortex-subscription-details">
                    <div class="vortex-detail-row">
                        <span class="vortex-detail-label"><?php esc_html_e('Status:', 'vortex-ai-marketplace'); ?></span>
                        <span class="vortex-detail-value vortex-active"><?php esc_html_e('Active', 'vortex-ai-marketplace'); ?></span>
                    </div>
                    <?php if (!empty($subscription_expiry)): ?>
                    <div class="vortex-detail-row">
                        <span class="vortex-detail-label"><?php esc_html_e('Expiry Date:', 'vortex-ai-marketplace'); ?></span>
                        <span class="vortex-detail-value"><?php echo esc_html(date('F j, Y', strtotime($subscription_expiry))); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="vortex-next-steps">
                    <a href="<?php echo esc_url(home_url('/collector-dashboard/')); ?>" class="vortex-button"><?php esc_html_e('Go to Dashboard', 'vortex-ai-marketplace'); ?></a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php if (!empty($subscription_error)): ?>
            <div class="vortex-subscription-error">
                <div class="vortex-notice vortex-error">
                    <?php echo esc_html($subscription_error); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="vortex-subscription-plan">
            <div class="vortex-plan-header">
                <h3><?php esc_html_e('Collector Annual Plan', 'vortex-ai-marketplace'); ?></h3>
                <div class="vortex-plan-price">
                    <span class="vortex-price"><?php echo esc_html($subscription_price_tola); ?></span>
                    <span class="vortex-currency">TOLA</span>
                    <span class="vortex-period"><?php esc_html_e('per year', 'vortex-ai-marketplace'); ?></span>
                </div>
            </div>
            
            <div class="vortex-plan-benefits">
                <h4><?php esc_html_e('Plan Benefits', 'vortex-ai-marketplace'); ?></h4>
                <ul>
                    <li><?php esc_html_e('Full access to all marketplace artists', 'vortex-ai-marketplace'); ?></li>
                    <li><?php esc_html_e('Access to CLOE market analysis AI', 'vortex-ai-marketplace'); ?></li>
                    <li><?php esc_html_e('Thorius blockchain advisor access', 'vortex-ai-marketplace'); ?></li>
                    <li><?php esc_html_e('Business strategist AI for collection recommendations', 'vortex-ai-marketplace'); ?></li>
                    <li><?php esc_html_e('Private messaging with artists', 'vortex-ai-marketplace'); ?></li>
                    <li><?php esc_html_e('Video call consultations with artists', 'vortex-ai-marketplace'); ?></li>
                    <li><?php esc_html_e('First access to new artwork releases', 'vortex-ai-marketplace'); ?></li>
                </ul>
            </div>
            
            <div class="vortex-wallet-status">
                <h4><?php esc_html_e('Wallet Status', 'vortex-ai-marketplace'); ?></h4>
                
                <?php if ($wallet_connected): ?>
                    <div class="vortex-wallet-info">
                        <div class="vortex-wallet-connected">
                            <span class="vortex-status-icon connected"><i class="fas fa-check-circle"></i></span>
                            <span class="vortex-status-text"><?php esc_html_e('Wallet Connected', 'vortex-ai-marketplace'); ?></span>
                        </div>
                        
                        <div class="vortex-tola-balance">
                            <div class="vortex-balance-row">
                                <span class="vortex-balance-label"><?php esc_html_e('Current Balance:', 'vortex-ai-marketplace'); ?></span>
                                <span class="vortex-balance-value"><?php echo esc_html($tola_balance); ?> TOLA</span>
                            </div>
                            <div class="vortex-balance-row">
                                <span class="vortex-balance-label"><?php esc_html_e('Required for Subscription:', 'vortex-ai-marketplace'); ?></span>
                                <span class="vortex-balance-value"><?php echo esc_html($subscription_price_tola); ?> TOLA</span>
                            </div>
                        </div>
                        
                        <?php if (!$has_enough_tola): ?>
                            <div class="vortex-insufficient-balance">
                                <p><?php esc_html_e('You need more TOLA to purchase this subscription.', 'vortex-ai-marketplace'); ?></p>
                                <a href="<?php echo esc_url(home_url('/purchase-tola/')); ?>" class="vortex-button"><?php esc_html_e('Purchase TOLA', 'vortex-ai-marketplace'); ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="vortex-wallet-info">
                        <div class="vortex-wallet-disconnected">
                            <span class="vortex-status-icon disconnected"><i class="fas fa-times-circle"></i></span>
                            <span class="vortex-status-text"><?php esc_html_e('Wallet Not Connected', 'vortex-ai-marketplace'); ?></span>
                        </div>
                        <a href="<?php echo esc_url(home_url('/connect-wallet/')); ?>" class="vortex-button"><?php esc_html_e('Connect Wallet', 'vortex-ai-marketplace'); ?></a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="vortex-subscription-action">
                <form method="post" class="vortex-subscription-form">
                    <?php wp_nonce_field('collector_subscription', 'subscription_nonce'); ?>
                    
                    <button type="submit" 
                            name="subscribe_collector" 
                            class="vortex-button vortex-button-large"
                            <?php echo (!$wallet_connected || !$has_enough_tola) ? 'disabled' : ''; ?>>
                        <?php esc_html_e('Subscribe Now', 'vortex-ai-marketplace'); ?>
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.vortex-subscription-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 30px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    color: #333;
}

.vortex-subscription-container h2 {
    margin-top: 0;
    margin-bottom: 30px;
    font-size: 28px;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 15px;
}

.vortex-notice {
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 30px;
    background-color: #f8f9fc;
    border-left: 5px solid #4e73df;
}

.vortex-notice h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 20px;
}

.vortex-warning {
    background-color: #fff8e1;
    border-left-color: #ffc107;
}

.vortex-warning h3 {
    color: #856404;
}

.vortex-success {
    background-color: #e8f5e9;
    border-left-color: #4caf50;
}

.vortex-success h3 {
    color: #2e7d32;
}

.vortex-info {
    background-color: #e8f4fd;
    border-left-color: #2196f3;
}

.vortex-info h3 {
    color: #0d47a1;
}

.vortex-error {
    background-color: #ffebee;
    border-left-color: #f44336;
}

.vortex-subscription-details {
    margin-top: 20px;
    background-color: rgba(255, 255, 255, 0.5);
    padding: 15px;
    border-radius: 4px;
}

.vortex-detail-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.vortex-detail-row:last-child {
    border-bottom: none;
}

.vortex-detail-label {
    font-weight: 500;
}

.vortex-detail-value.vortex-active {
    color: #4caf50;
    font-weight: 600;
}

.vortex-next-steps {
    margin-top: 25px;
}

.vortex-next-steps ul {
    margin: 15px 0;
    padding-left: 20px;
}

.vortex-next-steps li {
    margin-bottom: 10px;
}

.vortex-button {
    display: inline-block;
    background-color: #4e73df;
    color: #fff;
    padding: 10px 20px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    transition: background-color 0.3s;
    border: none;
    cursor: pointer;
}

.vortex-button:hover {
    background-color: #2e59d9;
    color: #fff;
}

.vortex-button-large {
    padding: 15px 30px;
    font-size: 18px;
}

.vortex-button:disabled {
    background-color: #c3c3c3;
    cursor: not-allowed;
}

.vortex-button:disabled:hover {
    background-color: #c3c3c3;
}

.vortex-subscription-plan {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.vortex-plan-header {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    color: white;
    padding: 30px;
    text-align: center;
}

.vortex-plan-header h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 24px;
}

.vortex-plan-price {
    font-size: 18px;
}

.vortex-price {
    font-size: 36px;
    font-weight: 700;
}

.vortex-currency {
    font-size: 18px;
    margin-left: 5px;
}

.vortex-period {
    opacity: 0.8;
    margin-left: 5px;
}

.vortex-plan-benefits {
    padding: 30px;
    border-bottom: 1px solid #eee;
}

.vortex-plan-benefits h4 {
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 18px;
    color: #333;
}

.vortex-plan-benefits ul {
    padding-left: 20px;
    margin: 0;
}

.vortex-plan-benefits li {
    margin-bottom: 10px;
    position: relative;
}

.vortex-wallet-status {
    padding: 30px;
    border-bottom: 1px solid #eee;
}

.vortex-wallet-status h4 {
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 18px;
    color: #333;
}

.vortex-wallet-connected,
.vortex-wallet-disconnected {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.vortex-status-icon {
    margin-right: 10px;
    font-size: 18px;
}

.vortex-status-icon.connected {
    color: #4caf50;
}

.vortex-status-icon.disconnected {
    color: #f44336;
}

.vortex-tola-balance {
    margin-top: 20px;
    margin-bottom: 20px;
    background-color: #f5f7fa;
    padding: 15px;
    border-radius: 4px;
}

.vortex-balance-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
}

.vortex-balance-label {
    font-weight: 500;
}

.vortex-insufficient-balance {
    margin-top: 15px;
    background-color: #fff8e1;
    padding: 15px;
    border-radius: 4px;
    border-left: 3px solid #ffc107;
}

.vortex-subscription-action {
    padding: 30px;
    text-align: center;
}

@media (max-width: 768px) {
    .vortex-subscription-container {
        padding: 15px;
    }
}
</style> 