<?php
/**
 * Template for collector dashboard
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
        <p><?php esc_html_e('You need to be registered as a collector to view this dashboard.', 'vortex-ai-marketplace'); ?></p>
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

// Check if minimum TOLA requirement is met
$min_tola_required = 50; // Set your minimum TOLA requirement
$has_min_tola = $tola_balance >= $min_tola_required;
?>

<div class="vortex-collector-dashboard">
    <div class="vortex-dashboard-header">
        <h2><?php esc_html_e('Collector Dashboard', 'vortex-ai-marketplace'); ?></h2>
        <p class="vortex-welcome-message"><?php printf(esc_html__('Welcome back, %s!', 'vortex-ai-marketplace'), esc_html($user->display_name)); ?></p>
    </div>
    
    <?php if ($subscription_status !== 'active'): ?>
    <div class="vortex-subscription-notice">
        <div class="vortex-notice vortex-warning">
            <h3><?php esc_html_e('Subscription Required', 'vortex-ai-marketplace'); ?></h3>
            <p><?php esc_html_e('You need an active collector subscription to access all marketplace features.', 'vortex-ai-marketplace'); ?></p>
            <a href="<?php echo esc_url(home_url('/collector-subscription/')); ?>" class="vortex-button"><?php esc_html_e('Subscribe Now - $399/year', 'vortex-ai-marketplace'); ?></a>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="vortex-dashboard-row">
        <div class="vortex-dashboard-column vortex-wallet-status">
            <h3><?php esc_html_e('Wallet Status', 'vortex-ai-marketplace'); ?></h3>
            
            <?php if ($wallet_connected): ?>
                <div class="vortex-wallet-connected">
                    <span class="vortex-status-icon connected"><i class="fas fa-check-circle"></i></span>
                    <span class="vortex-status-text"><?php esc_html_e('Wallet Connected', 'vortex-ai-marketplace'); ?></span>
                </div>
                <div class="vortex-wallet-address">
                    <span class="vortex-address-label"><?php esc_html_e('Address:', 'vortex-ai-marketplace'); ?></span>
                    <span class="vortex-address-value"><?php echo esc_html(substr($wallet_address, 0, 6) . '...' . substr($wallet_address, -4)); ?></span>
                </div>
            <?php else: ?>
                <div class="vortex-wallet-disconnected">
                    <span class="vortex-status-icon disconnected"><i class="fas fa-times-circle"></i></span>
                    <span class="vortex-status-text"><?php esc_html_e('Wallet Not Connected', 'vortex-ai-marketplace'); ?></span>
                </div>
                <a href="<?php echo esc_url(home_url('/connect-wallet/')); ?>" class="vortex-button vortex-button-small"><?php esc_html_e('Connect Wallet', 'vortex-ai-marketplace'); ?></a>
            <?php endif; ?>
            
            <div class="vortex-tola-balance">
                <h4><?php esc_html_e('TOLA Balance', 'vortex-ai-marketplace'); ?></h4>
                <div class="vortex-balance-amount"><?php echo esc_html($tola_balance); ?> TOLA</div>
                
                <?php if (!$has_min_tola): ?>
                    <div class="vortex-balance-warning">
                        <p><?php printf(esc_html__('Minimum %d TOLA required for marketplace activities.', 'vortex-ai-marketplace'), $min_tola_required); ?></p>
                    </div>
                <?php endif; ?>
                
                <a href="<?php echo esc_url(home_url('/purchase-tola/')); ?>" class="vortex-button vortex-button-small"><?php esc_html_e('Purchase TOLA', 'vortex-ai-marketplace'); ?></a>
            </div>
        </div>
        
        <div class="vortex-dashboard-column vortex-ai-agents">
            <h3><?php esc_html_e('AI Agents', 'vortex-ai-marketplace'); ?></h3>
            
            <div class="vortex-agent-tabs">
                <div class="vortex-agent-tab" data-agent="cloe">
                    <div class="vortex-agent-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="vortex-agent-name"><?php esc_html_e('CLOE', 'vortex-ai-marketplace'); ?></div>
                </div>
                
                <div class="vortex-agent-tab" data-agent="thorius">
                    <div class="vortex-agent-icon"><i class="fas fa-coins"></i></div>
                    <div class="vortex-agent-name"><?php esc_html_e('Thorius', 'vortex-ai-marketplace'); ?></div>
                </div>
                
                <div class="vortex-agent-tab" data-agent="strategist">
                    <div class="vortex-agent-icon"><i class="fas fa-lightbulb"></i></div>
                    <div class="vortex-agent-name"><?php esc_html_e('Business Strategist', 'vortex-ai-marketplace'); ?></div>
                </div>
            </div>
            
            <div class="vortex-agent-content">
                <div class="vortex-agent-placeholder">
                    <p><?php esc_html_e('Select an AI agent to interact with', 'vortex-ai-marketplace'); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="vortex-dashboard-row">
        <div class="vortex-dashboard-column vortex-recent-activity">
            <h3><?php esc_html_e('Recent Activity', 'vortex-ai-marketplace'); ?></h3>
            
            <div class="vortex-activity-list">
                <?php 
                // Get collector's recent activity
                $activities = array(); // This would normally be populated from a function
                
                if (empty($activities)): 
                ?>
                    <div class="vortex-no-activity">
                        <p><?php esc_html_e('No recent activity to display.', 'vortex-ai-marketplace'); ?></p>
                    </div>
                <?php else: ?>
                    <ul class="vortex-activity-items">
                        <?php foreach ($activities as $activity): ?>
                            <li class="vortex-activity-item">
                                <span class="vortex-activity-date"><?php echo esc_html($activity['date']); ?></span>
                                <span class="vortex-activity-type"><?php echo esc_html($activity['type']); ?></span>
                                <span class="vortex-activity-description"><?php echo esc_html($activity['description']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="vortex-dashboard-column vortex-marketplace">
            <h3><?php esc_html_e('Artists Marketplace', 'vortex-ai-marketplace'); ?></h3>
            
            <div class="vortex-artists-preview">
                <p><?php esc_html_e('Discover and connect with artists on the marketplace.', 'vortex-ai-marketplace'); ?></p>
                <a href="<?php echo esc_url(home_url('/marketplace/')); ?>" class="vortex-button"><?php esc_html_e('Browse Marketplace', 'vortex-ai-marketplace'); ?></a>
            </div>
        </div>
    </div>
</div>

<style>
.vortex-collector-dashboard {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    color: #333;
}

.vortex-dashboard-header {
    margin-bottom: 30px;
    border-bottom: 1px solid #eee;
    padding-bottom: 20px;
}

.vortex-dashboard-header h2 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 28px;
    color: #333;
}

.vortex-welcome-message {
    font-size: 16px;
    color: #666;
    margin-bottom: 0;
}

.vortex-dashboard-row {
    display: flex;
    gap: 30px;
    margin-bottom: 30px;
}

.vortex-dashboard-column {
    flex: 1;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 25px;
}

.vortex-dashboard-column h3 {
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 20px;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.vortex-wallet-connected,
.vortex-wallet-disconnected {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.vortex-status-icon {
    font-size: 20px;
    margin-right: 10px;
}

.vortex-status-icon.connected {
    color: #4caf50;
}

.vortex-status-icon.disconnected {
    color: #f44336;
}

.vortex-wallet-address {
    background: #f5f5f5;
    padding: 8px 12px;
    border-radius: 4px;
    margin-bottom: 20px;
    font-family: monospace;
}

.vortex-tola-balance {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px dashed #ddd;
}

.vortex-tola-balance h4 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 16px;
}

.vortex-balance-amount {
    font-size: 24px;
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
}

.vortex-balance-warning {
    background-color: #fff8e1;
    border-left: 4px solid #ffc107;
    padding: 10px;
    margin-bottom: 15px;
    font-size: 14px;
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
}

.vortex-button:hover {
    background-color: #2e59d9;
    color: #fff;
}

.vortex-button-small {
    padding: 8px 15px;
    font-size: 14px;
}

.vortex-subscription-notice {
    margin-bottom: 30px;
}

.vortex-notice {
    padding: 20px;
    border-radius: 6px;
    background-color: #f8f9fc;
    border-left: 4px solid #4e73df;
}

.vortex-warning {
    background-color: #fff8e1;
    border-left-color: #ffc107;
}

.vortex-warning h3 {
    margin-top: 0;
    color: #856404;
    border-bottom: none;
    padding-bottom: 0;
}

.vortex-agent-tabs {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}

.vortex-agent-tab {
    flex: 1;
    background-color: #f8f9fc;
    border-radius: 6px;
    padding: 15px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.vortex-agent-tab:hover {
    background-color: #eaecf4;
    transform: translateY(-3px);
}

.vortex-agent-icon {
    font-size: 24px;
    margin-bottom: 10px;
    color: #4e73df;
}

.vortex-agent-name {
    font-weight: 500;
}

.vortex-agent-content {
    background-color: #f8f9fc;
    border-radius: 6px;
    padding: 20px;
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.vortex-no-activity {
    text-align: center;
    padding: 30px;
    color: #666;
}

.vortex-activity-items {
    list-style: none;
    padding: 0;
    margin: 0;
}

.vortex-activity-item {
    padding: 12px 0;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
}

.vortex-activity-date {
    font-size: 12px;
    color: #666;
    width: 80px;
}

.vortex-activity-type {
    background-color: #e8eaf6;
    color: #3949ab;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 12px;
    margin-right: 10px;
}

.vortex-activity-description {
    flex-grow: 1;
}

.vortex-artists-preview {
    text-align: center;
    padding: 30px 0;
}

@media (max-width: 768px) {
    .vortex-dashboard-row {
        flex-direction: column;
    }
    
    .vortex-dashboard-column {
        margin-bottom: 20px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle agent tab clicks
    $('.vortex-agent-tab').on('click', function() {
        const agent = $(this).data('agent');
        $('.vortex-agent-tab').removeClass('active');
        $(this).addClass('active');
        
        // For demo purposes, just show a placeholder message
        $('.vortex-agent-content').html(
            `<div class="vortex-agent-chat">
                <p>Loading ${agent.charAt(0).toUpperCase() + agent.slice(1)} interface...</p>
            </div>`
        );
        
        // In a real implementation, you'd load the agent chat interface via AJAX
    });
});
</script> 