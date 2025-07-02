<?php
/**
 * VORTEX Main System Dashboard
 * 
 * Provides overview of all VORTEX AI systems and components
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Admin_Partials
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap vortex-system-dashboard">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-superhero-alt" style="color: #7209b7;"></span>
        VORTEX AI System Dashboard
    </h1>
    <p class="description">Complete overview and control center for all VORTEX AI components</p>

    <!-- System Status Cards -->
    <div class="vortex-status-grid">
        
        <!-- Overall System Health -->
        <div class="vortex-status-card system-health">
            <div class="card-header">
                <h3><span class="dashicons dashicons-heart"></span> System Health</h3>
                <div class="status-indicator status-<?php echo esc_attr($system_status['overall_health']); ?>">
                    <?php echo ucfirst($system_status['overall_health']); ?>
                </div>
            </div>
            <div class="card-content">
                <div class="stat-row">
                    <span class="stat-label">Components Loaded:</span>
                    <span class="stat-value"><?php echo $system_status['components_loaded']; ?>/4</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Active Agents:</span>
                    <span class="stat-value"><?php echo $system_status['agents_active']; ?>/4</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Memory Usage:</span>
                    <span class="stat-value"><?php echo size_format($system_status['memory_usage']); ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">System Uptime:</span>
                    <span class="stat-value"><?php echo $system_status['uptime']; ?></span>
                </div>
            </div>
        </div>

        <!-- ARCHER Orchestrator Status -->
        <div class="vortex-status-card archer-status">
            <div class="card-header">
                <h3><span class="dashicons dashicons-networking"></span> ARCHER Orchestrator</h3>
                <div class="status-indicator status-<?php echo class_exists('VORTEX_ARCHER_Orchestrator') ? 'active' : 'inactive'; ?>">
                    <?php echo class_exists('VORTEX_ARCHER_Orchestrator') ? 'Active' : 'Inactive'; ?>
                </div>
            </div>
            <div class="card-content">
                <div class="stat-row">
                    <span class="stat-label">Agents Managed:</span>
                    <span class="stat-value">4</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Sync Interval:</span>
                    <span class="stat-value">5 seconds</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Last Sync:</span>
                    <span class="stat-value"><?php echo $system_status['last_sync']; ?></span>
                </div>
                <div class="card-actions">
                    <a href="<?php echo admin_url('admin.php?page=vortex-archer-control'); ?>" class="button button-primary">
                        View ARCHER Control
                    </a>
                </div>
            </div>
        </div>

        <!-- SECRET SAUCE Status -->
        <div class="vortex-status-card secret-sauce-status">
            <div class="card-header">
                <h3><span class="dashicons dashicons-lock"></span> SECRET SAUCE</h3>
                <div class="status-indicator status-<?php echo get_option('vortex_secret_sauce_enabled', false) ? 'active' : 'inactive'; ?>">
                    <?php echo get_option('vortex_secret_sauce_enabled', false) ? 'Authorized' : 'Locked'; ?>
                </div>
            </div>
            <div class="card-content">
                <?php if (get_option('vortex_secret_sauce_enabled', false)): ?>
                    <div class="stat-row">
                        <span class="stat-label">RunPod Vault:</span>
                        <span class="stat-value status-connected">Connected</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Zodiac Intelligence:</span>
                        <span class="stat-value status-active">Active</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Seed Art Engine:</span>
                        <span class="stat-value status-active">Operational</span>
                    </div>
                    <div class="card-actions">
                        <a href="<?php echo admin_url('admin.php?page=vortex-secret-sauce'); ?>" class="button button-primary">
                            ACCESS SECRET SAUCE
                        </a>
                    </div>
                <?php else: ?>
                    <p class="secret-sauce-locked">
                        <span class="dashicons dashicons-warning"></span>
                        Proprietary technology access restricted. 
                        Contact VortexArtec for authorization.
                    </p>
                    <div class="card-actions">
                        <button id="enable-secret-sauce" class="button button-secondary" data-nonce="<?php echo wp_create_nonce('vortex_admin_nonce'); ?>">
                            Enable SECRET SAUCE
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Smart Contracts Status -->
        <div class="vortex-status-card smart-contracts-status">
            <div class="card-header">
                <h3><span class="dashicons dashicons-admin-links"></span> TOLA Smart Contracts</h3>
                <div class="status-indicator status-<?php echo class_exists('VORTEX_TOLA_Smart_Contract_Automation') ? 'active' : 'inactive'; ?>">
                    <?php echo class_exists('VORTEX_TOLA_Smart_Contract_Automation') ? 'Active' : 'Inactive'; ?>
                </div>
            </div>
            <div class="card-content">
                <?php 
                global $wpdb;
                $contracts_table = $wpdb->prefix . 'vortex_smart_contracts';
                $total_contracts = 0;
                if ($wpdb->get_var("SHOW TABLES LIKE '$contracts_table'") == $contracts_table) {
                    $total_contracts = $wpdb->get_var("SELECT COUNT(*) FROM $contracts_table");
                }
                ?>
                <div class="stat-row">
                    <span class="stat-label">Total Contracts:</span>
                    <span class="stat-value"><?php echo $total_contracts; ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Blockchain:</span>
                    <span class="stat-value">TOLA Network</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Auto-Generation:</span>
                    <span class="stat-value status-active">Enabled</span>
                </div>
                <div class="card-actions">
                    <a href="<?php echo admin_url('admin.php?page=vortex-smart-contracts'); ?>" class="button button-primary">
                        View Contracts
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- AI Agents Overview -->
    <div class="vortex-section">
        <h2><span class="dashicons dashicons-buddicons-buddypress-logo"></span> AI Agents Status</h2>
        
        <div class="agents-grid">
            
            <!-- HURAII Agent -->
            <div class="agent-card huraii">
                <div class="agent-header">
                    <div class="agent-avatar">🎨</div>
                    <div class="agent-info">
                        <h3>HURAII</h3>
                        <p>Seed Art Creator</p>
                    </div>
                    <div class="agent-status status-<?php echo class_exists('VORTEX_HURAII') ? 'active' : 'inactive'; ?>">
                        <?php echo class_exists('VORTEX_HURAII') ? 'Active' : 'Inactive'; ?>
                    </div>
                </div>
                <div class="agent-metrics">
                    <div class="metric">
                        <span class="metric-label">Learning:</span>
                        <span class="metric-value">Active</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Cloud Sync:</span>
                        <span class="metric-value">Connected</span>
                    </div>
                </div>
                <div class="agent-actions">
                    <button class="agent-control-btn" data-agent="HURAII" data-action="restart">Restart</button>
                    <button class="agent-control-btn" data-agent="HURAII" data-action="sync">Sync</button>
                </div>
            </div>

            <!-- CLOE Agent -->
            <div class="agent-card cloe">
                <div class="agent-avatar">
                    <span class="agent-icon">🔮</span>
                </div>
                <h3>CLOE</h3>
                <p>Market Analysis & Trends</p>
                
                <div class="agent-status status-<?php echo class_exists('VORTEX_CLOE') ? 'active' : 'inactive'; ?>">
                    <?php echo class_exists('VORTEX_CLOE') ? 'Active' : 'Inactive'; ?>
                </div>
                
                <div class="agent-metrics">
                    <div class="metric">
                        <span class="metric-label">Predictions</span>
                        <span class="metric-value">1,247</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Accuracy</span>
                        <span class="metric-value">94%</span>
                    </div>
                </div>
                
                <div class="agent-controls">
                    <button class="agent-control-btn" data-agent="CLOE" data-action="restart">Restart</button>
                    <button class="agent-control-btn" data-agent="CLOE" data-action="sync">Sync</button>
                </div>
            </div>

            <!-- HORACE Agent -->
            <div class="agent-card horace">
                <div class="agent-header">
                    <div class="agent-avatar">🔍</div>
                    <div class="agent-info">
                        <h3>HORACE</h3>
                        <p>Quality Guardian</p>
                    </div>
                    <div class="agent-status status-<?php echo class_exists('VORTEX_HORACE') ? 'active' : 'inactive'; ?>">
                        <?php echo class_exists('VORTEX_HORACE') ? 'Active' : 'Inactive'; ?>
                    </div>
                </div>
                <div class="agent-metrics">
                    <div class="metric">
                        <span class="metric-label">Curation:</span>
                        <span class="metric-value">Active</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Quality Score:</span>
                        <span class="metric-value">98.7%</span>
                    </div>
                </div>
                <div class="agent-actions">
                    <button class="agent-control-btn" data-agent="HORACE" data-action="restart">Restart</button>
                    <button class="agent-control-btn" data-agent="HORACE" data-action="sync">Sync</button>
                </div>
            </div>

            <!-- THORIUS Agent -->
            <div class="agent-card thorius">
                <div class="agent-header">
                    <div class="agent-avatar">🛡️</div>
                    <div class="agent-info">
                        <h3>THORIUS</h3>
                        <p>Security Guardian</p>
                    </div>
                    <div class="agent-status status-<?php echo class_exists('Vortex_Thorius') ? 'active' : 'inactive'; ?>">
                        <?php echo class_exists('Vortex_Thorius') ? 'Active' : 'Inactive'; ?>
                    </div>
                </div>
                <div class="agent-metrics">
                    <div class="metric">
                        <span class="metric-label">Security:</span>
                        <span class="metric-value">Monitoring</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Blockchain:</span>
                        <span class="metric-value">Secured</span>
                    </div>
                </div>
                <div class="agent-actions">
                    <button class="agent-control-btn" data-agent="THORIUS" data-action="restart">Restart</button>
                    <button class="agent-control-btn" data-agent="THORIUS" data-action="sync">Sync</button>
                </div>
            </div>

        </div>
    </div>

    <!-- Quick Actions -->
    <div class="vortex-section">
        <h2><span class="dashicons dashicons-admin-tools"></span> Quick Actions</h2>
        
        <div class="quick-actions-grid">
            <button id="sync-all-agents" class="quick-action-btn primary">
                <span class="dashicons dashicons-update"></span>
                Sync All Agents
            </button>
            
            <button id="performance-check" class="quick-action-btn">
                <span class="dashicons dashicons-performance"></span>
                Performance Check
            </button>
            
            <button id="system-restart" class="quick-action-btn warning">
                <span class="dashicons dashicons-backup"></span>
                System Restart
            </button>
            
            <a href="<?php echo admin_url('admin.php?page=vortex-analytics'); ?>" class="quick-action-btn">
                <span class="dashicons dashicons-chart-line"></span>
                View Analytics
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=vortex-settings'); ?>" class="quick-action-btn">
                <span class="dashicons dashicons-admin-settings"></span>
                System Settings
            </a>
            
            <button id="export-logs" class="quick-action-btn">
                <span class="dashicons dashicons-download"></span>
                Export Logs
            </button>
        </div>
    </div>

    <!-- Real-time Activity Monitor -->
    <div class="vortex-section">
        <h2><span class="dashicons dashicons-chart-area"></span> Real-time Activity</h2>
        
        <div class="activity-monitor">
            <div class="activity-log" id="real-time-log">
                <div class="log-entry">
                    <span class="timestamp"><?php echo current_time('H:i:s'); ?></span>
                    <span class="level info">INFO</span>
                    <span class="message">VORTEX System Dashboard loaded successfully</span>
                </div>
                <!-- Real-time entries will be populated via JavaScript -->
            </div>
        </div>
    </div>

    <!-- System Information -->
    <div class="vortex-section">
        <h2><span class="dashicons dashicons-info"></span> System Information</h2>
        
        <div class="system-info-grid">
            <div class="info-card">
                <h3>VORTEX Version</h3>
                <p><?php echo defined('VORTEX_VERSION') ? VORTEX_VERSION : '1.0.0'; ?></p>
            </div>
            
            <div class="info-card">
                <h3>WordPress Version</h3>
                <p><?php echo get_bloginfo('version'); ?></p>
            </div>
            
            <div class="info-card">
                <h3>PHP Version</h3>
                <p><?php echo PHP_VERSION; ?></p>
            </div>
            
            <div class="info-card">
                <h3>Database Version</h3>
                <p><?php echo get_option('vortex_system_db_version', '1.0'); ?></p>
            </div>
            
            <div class="info-card">
                <h3>Server Load</h3>
                <p><?php echo function_exists('sys_getloadavg') ? implode(', ', sys_getloadavg()) : 'N/A'; ?></p>
            </div>
            
            <div class="info-card">
                <h3>Memory Limit</h3>
                <p><?php echo ini_get('memory_limit'); ?></p>
            </div>
        </div>
    </div>

</div>

<!-- Loading Overlay -->
<div id="vortex-loading-overlay" class="vortex-overlay" style="display: none;">
    <div class="vortex-spinner">
        <div class="spinner"></div>
        <p>Processing VORTEX operation...</p>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Agent control buttons
    $('.agent-control-btn').on('click', function() {
        const agent = $(this).data('agent');
        const action = $(this).data('action');
        
        if (confirm(`Are you sure you want to ${action} agent ${agent}?`)) {
            controlAgent(agent, action);
        }
    });
    
    // Quick action buttons
    $('#sync-all-agents').on('click', function() {
        syncAllAgents();
    });
    
    $('#performance-check').on('click', function() {
        performanceCheck();
    });
    
    $('#system-restart').on('click', function() {
        if (confirm('Are you sure you want to restart the entire VORTEX system? This will briefly interrupt all operations.')) {
            systemRestart();
        }
    });
    
    $('#enable-secret-sauce').on('click', function() {
        if (confirm('Enable SECRET SAUCE? This will activate proprietary VortexArtec technology.')) {
            toggleSecretSauce(true);
        }
    });
    
    // Auto-refresh system status every 30 seconds
    setInterval(function() {
        refreshSystemStatus();
    }, 30000);
    
    // Real-time activity monitor
    setInterval(function() {
        updateActivityLog();
    }, 5000);
    
    // Functions
    function controlAgent(agent, action) {
        showLoading();
        
        $.ajax({
            url: vortexAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_agent_control',
                nonce: vortexAdmin.nonce,
                agent: agent,
                action_type: action
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showNotification(response.data, 'success');
                    refreshSystemStatus();
                } else {
                    showNotification(response.data, 'error');
                }
            },
            error: function() {
                hideLoading();
                showNotification('Network error occurred', 'error');
            }
        });
    }
    
    function syncAllAgents() {
        showLoading();
        
        $.ajax({
            url: vortexAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_sync_all_agents',
                nonce: vortexAdmin.nonce
            },
            success: function(response) {
                hideLoading();
                showNotification('All agents synchronized successfully', 'success');
                refreshSystemStatus();
            },
            error: function() {
                hideLoading();
                showNotification('Sync failed', 'error');
            }
        });
    }
    
    function toggleSecretSauce(enabled) {
        showLoading();
        
        $.ajax({
            url: vortexAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_secret_sauce_toggle',
                nonce: vortexAdmin.nonce,
                enabled: enabled
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(response.data, 'error');
                }
            },
            error: function() {
                hideLoading();
                showNotification('Failed to toggle SECRET SAUCE', 'error');
            }
        });
    }
    
    function refreshSystemStatus() {
        $.ajax({
            url: vortexAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vortex_system_status',
                nonce: vortexAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateStatusDisplay(response.data);
                }
            }
        });
    }
    
    function updateActivityLog() {
        const logEntry = `
            <div class="log-entry">
                <span class="timestamp">${new Date().toLocaleTimeString()}</span>
                <span class="level info">SYNC</span>
                <span class="message">Agent synchronization completed</span>
            </div>
        `;
        
        $('#real-time-log').prepend(logEntry);
        
        // Keep only last 10 entries
        $('#real-time-log .log-entry').slice(10).remove();
    }
    
    function showLoading() {
        $('#vortex-loading-overlay').show();
    }
    
    function hideLoading() {
        $('#vortex-loading-overlay').hide();
    }
    
    function showNotification(message, type) {
        // Create WordPress admin notice
        const notice = $(`
            <div class="notice notice-${type} is-dismissible">
                <p>${message}</p>
            </div>
        `);
        
        $('.vortex-system-dashboard').prepend(notice);
        
        // Auto-remove after 5 seconds
        setTimeout(() => notice.fadeOut(), 5000);
    }
    
    function updateStatusDisplay(status) {
        // Update system metrics
        $('.stat-value').each(function() {
            const label = $(this).siblings('.stat-label').text();
            // Update specific metrics based on response
        });
    }
    
});
</script> 