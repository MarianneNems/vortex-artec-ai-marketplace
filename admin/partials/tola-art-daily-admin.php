<?php
/**
 * Admin interface for TOLA-ART Daily Automation
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

global $wpdb;

// Get automation instance
$automation = Vortex_TOLA_Art_Daily_Automation::get_instance();

// Get current statistics
$daily_art_table = $wpdb->prefix . 'vortex_daily_art';
$participation_table = $wpdb->prefix . 'vortex_artist_participation';
$royalty_table = $wpdb->prefix . 'vortex_royalty_distribution';

$total_generated = $wpdb->get_var("SELECT COUNT(*) FROM {$daily_art_table}");
$successful_generations = $wpdb->get_var("SELECT COUNT(*) FROM {$daily_art_table} WHERE generation_status = 'listed'");
$total_participating_artists = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$participation_table}");
$total_sales_amount = $wpdb->get_var("SELECT SUM(sale_amount) FROM {$royalty_table} WHERE distribution_status = 'completed'") ?: 0;
$total_royalties_distributed = $wpdb->get_var("SELECT SUM(creator_royalty + artist_pool) FROM {$royalty_table} WHERE distribution_status = 'completed'") ?: 0;

// Get recent generations
$recent_generations = $wpdb->get_results("
    SELECT da.*, p.meta_value as artwork_title 
    FROM {$daily_art_table} da 
    LEFT JOIN {$wpdb->postmeta} p ON da.artwork_id = p.post_id AND p.meta_key = 'post_title'
    ORDER BY da.created_at DESC 
    LIMIT 10
");

// Get today's status
$today = current_time('Y-m-d');
$today_artwork = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$daily_art_table} WHERE date = %s",
    $today
));

?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <i class="dashicons dashicons-art"></i>
        TOLA-ART Daily Automation Dashboard
    </h1>
    
    <div class="tola-art-admin-container">
        
        <!-- Header Stats -->
        <div class="tola-stats-grid">
            <div class="stat-card total-generated">
                <div class="stat-icon">
                    <i class="dashicons dashicons-images-alt2"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Generated</h3>
                    <span class="stat-number"><?php echo number_format($total_generated); ?></span>
                    <span class="stat-label">Artworks Created</span>
                </div>
            </div>
            
            <div class="stat-card successful">
                <div class="stat-icon">
                    <i class="dashicons dashicons-yes-alt"></i>
                </div>
                <div class="stat-content">
                    <h3>Successfully Listed</h3>
                    <span class="stat-number"><?php echo number_format($successful_generations); ?></span>
                    <span class="stat-label">On Marketplace</span>
                </div>
            </div>
            
            <div class="stat-card artists">
                <div class="stat-icon">
                    <i class="dashicons dashicons-groups"></i>
                </div>
                <div class="stat-content">
                    <h3>Participating Artists</h3>
                    <span class="stat-number"><?php echo number_format($total_participating_artists); ?></span>
                    <span class="stat-label">Receiving Royalties</span>
                </div>
            </div>
            
            <div class="stat-card revenue">
                <div class="stat-icon">
                    <i class="dashicons dashicons-money-alt"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Sales</h3>
                    <span class="stat-number"><?php echo number_format($total_sales_amount, 2); ?></span>
                    <span class="stat-label">TOLA Tokens</span>
                </div>
            </div>
            
            <div class="stat-card royalties">
                <div class="stat-icon">
                    <i class="dashicons dashicons-share"></i>
                </div>
                <div class="stat-content">
                    <h3>Royalties Distributed</h3>
                    <span class="stat-number"><?php echo number_format($total_royalties_distributed, 2); ?></span>
                    <span class="stat-label">TOLA Tokens</span>
                </div>
            </div>
        </div>
        
        <!-- Today's Status -->
        <div class="tola-today-status">
            <div class="panel-header">
                <h2>
                    <i class="dashicons dashicons-calendar-alt"></i>
                    Today's TOLA-ART Status (<?php echo date('F j, Y'); ?>)
                </h2>
                <div class="header-actions">
                    <button id="manual-trigger-btn" class="button button-primary">
                        <i class="dashicons dashicons-controls-play"></i>
                        Manual Trigger
                    </button>
                    <button id="refresh-status-btn" class="button">
                        <i class="dashicons dashicons-update"></i>
                        Refresh
                    </button>
                </div>
            </div>
            
            <div class="today-content">
                <?php if ($today_artwork): ?>
                    <div class="artwork-status-card status-<?php echo esc_attr($today_artwork->generation_status); ?>">
                        <div class="status-indicator">
                            <div class="status-circle <?php echo esc_attr($today_artwork->generation_status); ?>"></div>
                            <span class="status-text"><?php echo ucfirst(str_replace('_', ' ', $today_artwork->generation_status)); ?></span>
                        </div>
                        
                        <div class="artwork-details">
                            <h4>Today's Artwork</h4>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <label>Generation Date:</label>
                                    <span><?php echo esc_html($today_artwork->date); ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Status:</label>
                                    <span class="status-badge <?php echo esc_attr($today_artwork->generation_status); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $today_artwork->generation_status)); ?>
                                    </span>
                                </div>
                                <?php if ($today_artwork->artwork_id): ?>
                                <div class="detail-item">
                                    <label>Artwork ID:</label>
                                    <span>
                                        <a href="<?php echo admin_url('post.php?post=' . $today_artwork->artwork_id . '&action=edit'); ?>" target="_blank">
                                            #<?php echo esc_html($today_artwork->artwork_id); ?>
                                        </a>
                                    </span>
                                </div>
                                <?php endif; ?>
                                <?php if ($today_artwork->marketplace_listing_id): ?>
                                <div class="detail-item">
                                    <label>Marketplace Listing:</label>
                                    <span>
                                        <a href="#" class="view-listing" data-listing-id="<?php echo esc_attr($today_artwork->marketplace_listing_id); ?>">
                                            View Listing #<?php echo esc_html($today_artwork->marketplace_listing_id); ?>
                                        </a>
                                    </span>
                                </div>
                                <?php endif; ?>
                                <div class="detail-item">
                                    <label>Participating Artists:</label>
                                    <span><?php echo esc_html($today_artwork->participating_artists_count ?: 0); ?> artists</span>
                                </div>
                                <?php if ($today_artwork->smart_contract_address): ?>
                                <div class="detail-item">
                                    <label>Smart Contract:</label>
                                    <span class="contract-address">
                                        <a href="#" class="view-contract" data-address="<?php echo esc_attr($today_artwork->smart_contract_address); ?>">
                                            <?php echo esc_html(substr($today_artwork->smart_contract_address, 0, 10) . '...'); ?>
                                        </a>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($today_artwork->generation_status === 'failed'): ?>
                            <div class="error-details">
                                <h5>Error Information</h5>
                                <p>Generation failed. Check system logs for details.</p>
                                <button class="button retry-generation" data-date="<?php echo esc_attr($today_artwork->date); ?>">
                                    <i class="dashicons dashicons-controls-repeat"></i>
                                    Retry Generation
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($today_artwork->prompt): ?>
                        <div class="prompt-section">
                            <h5>Generation Prompt</h5>
                            <div class="prompt-text"><?php echo esc_html($today_artwork->prompt); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="no-artwork-today">
                        <div class="empty-state">
                            <i class="dashicons dashicons-clock"></i>
                            <h4>No artwork generated today</h4>
                            <p>The daily automation will run at 6:00 AM, or you can trigger it manually.</p>
                            <button id="generate-today-btn" class="button button-primary button-large">
                                <i class="dashicons dashicons-art"></i>
                                Generate Today's Artwork
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Navigation Tabs -->
        <div class="tola-admin-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#recent-generations" class="nav-tab nav-tab-active">Recent Generations</a>
                <a href="#artist-management" class="nav-tab">Artist Management</a>
                <a href="#royalty-tracking" class="nav-tab">Royalty Tracking</a>
                <a href="#system-settings" class="nav-tab">System Settings</a>
                <a href="#automation-logs" class="nav-tab">Automation Logs</a>
            </nav>
            
            <!-- Recent Generations Tab -->
            <div id="recent-generations" class="tab-content active">
                <div class="panel-header">
                    <h3>Recent Generations</h3>
                    <div class="table-actions">
                        <select id="status-filter">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="generating">Generating</option>
                            <option value="completed">Completed</option>
                            <option value="listed">Listed</option>
                            <option value="failed">Failed</option>
                        </select>
                        <button class="button" id="export-generations">
                            <i class="dashicons dashicons-download"></i>
                            Export CSV
                        </button>
                    </div>
                </div>
                
                <div class="generations-table-wrapper">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Artwork</th>
                                <th>Participating Artists</th>
                                <th>Sales</th>
                                <th>Royalties</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_generations)): ?>
                                <?php foreach ($recent_generations as $generation): ?>
                                <tr data-generation-id="<?php echo esc_attr($generation->id); ?>">
                                    <td>
                                        <strong><?php echo esc_html($generation->date); ?></strong>
                                        <br>
                                        <small><?php echo esc_html(date('M j, Y', strtotime($generation->created_at))); ?></small>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo esc_attr($generation->generation_status); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $generation->generation_status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($generation->artwork_id): ?>
                                            <a href="<?php echo admin_url('post.php?post=' . $generation->artwork_id . '&action=edit'); ?>" target="_blank">
                                                Artwork #<?php echo esc_html($generation->artwork_id); ?>
                                            </a>
                                            <?php if ($generation->marketplace_listing_id): ?>
                                                <br><small>Listed #<?php echo esc_html($generation->marketplace_listing_id); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not created</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="artist-count"><?php echo esc_html($generation->participating_artists_count ?: 0); ?></span>
                                        <?php if ($generation->participating_artists_count > 0): ?>
                                            <button class="button-link view-artists" data-generation-id="<?php echo esc_attr($generation->id); ?>">
                                                View Artists
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="sales-amount"><?php echo number_format($generation->total_sales, 2); ?> TOLA</span>
                                    </td>
                                    <td>
                                        <span class="royalties-amount"><?php echo number_format($generation->royalties_distributed, 2); ?> TOLA</span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="button-link view-details" data-generation-id="<?php echo esc_attr($generation->id); ?>" title="View Details">
                                                <i class="dashicons dashicons-visibility"></i>
                                            </button>
                                            <?php if ($generation->generation_status === 'failed'): ?>
                                            <button class="button-link retry-generation" data-date="<?php echo esc_attr($generation->date); ?>" title="Retry Generation">
                                                <i class="dashicons dashicons-controls-repeat"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($generation->smart_contract_address): ?>
                                            <button class="button-link view-contract" data-address="<?php echo esc_attr($generation->smart_contract_address); ?>" title="View Contract">
                                                <i class="dashicons dashicons-admin-links"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-items">
                                        <div class="empty-state">
                                            <i class="dashicons dashicons-art"></i>
                                            <p>No generations found. The daily automation will create artworks automatically.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Artist Management Tab -->
            <div id="artist-management" class="tab-content">
                <div class="panel-header">
                    <h3>Artist Participation Management</h3>
                    <div class="header-actions">
                        <button class="button button-primary" id="add-artist-btn">
                            <i class="dashicons dashicons-plus"></i>
                            Add Artist
                        </button>
                        <button class="button" id="export-artists">
                            <i class="dashicons dashicons-download"></i>
                            Export List
                        </button>
                    </div>
                </div>
                
                <div class="artist-filters">
                    <div class="filter-group">
                        <label for="artist-status-filter">Status:</label>
                        <select id="artist-status-filter">
                            <option value="">All Artists</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="artist-search">Search:</label>
                        <input type="text" id="artist-search" placeholder="Search by name or wallet...">
                    </div>
                </div>
                
                <div id="artists-table-container">
                    <!-- Artists table will be loaded via AJAX -->
                    <div class="loading-placeholder">
                        <i class="dashicons dashicons-update spin"></i>
                        Loading artist data...
                    </div>
                </div>
            </div>
            
            <!-- Royalty Tracking Tab -->
            <div id="royalty-tracking" class="tab-content">
                <div class="panel-header">
                    <h3>Royalty Distribution Tracking</h3>
                    <div class="header-actions">
                        <select id="royalty-date-range">
                            <option value="7">Last 7 days</option>
                            <option value="30">Last 30 days</option>
                            <option value="90">Last 90 days</option>
                            <option value="365">Last year</option>
                            <option value="all">All time</option>
                        </select>
                        <button class="button" id="export-royalties">
                            <i class="dashicons dashicons-download"></i>
                            Export Report
                        </button>
                    </div>
                </div>
                
                <div class="royalty-stats-grid">
                    <div class="royalty-stat-card">
                        <h4>Creator Royalties (5%)</h4>
                        <div class="stat-amount" id="creator-royalties-total">0 TOLA</div>
                        <div class="stat-recipient">Marianne Nems</div>
                    </div>
                    <div class="royalty-stat-card">
                        <h4>Artist Pool (95%)</h4>
                        <div class="stat-amount" id="artist-pool-total">0 TOLA</div>
                        <div class="stat-recipient" id="artist-pool-recipients">0 artists</div>
                    </div>
                    <div class="royalty-stat-card">
                        <h4>Total Distributed</h4>
                        <div class="stat-amount" id="total-distributed">0 TOLA</div>
                        <div class="stat-recipient" id="distribution-count">0 distributions</div>
                    </div>
                </div>
                
                <div id="royalty-chart-container">
                    <canvas id="royalty-distribution-chart" width="400" height="200"></canvas>
                </div>
                
                <div id="royalty-table-container">
                    <!-- Royalty distribution table will be loaded via AJAX -->
                </div>
            </div>
            
            <!-- System Settings Tab -->
            <div id="system-settings" class="tab-content">
                <div class="panel-header">
                    <h3>Automation System Settings</h3>
                </div>
                
                <form id="system-settings-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Daily Generation Time</th>
                            <td>
                                <input type="time" name="generation_time" value="06:00" />
                                <p class="description">Time when daily artwork generation should occur (server time)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Creator Wallet Address</th>
                            <td>
                                <input type="text" name="creator_wallet" value="<?php echo esc_attr('0x742d35Cc6634C0532925a3b8D'); ?>" class="regular-text" readonly />
                                <p class="description">Marianne Nems' wallet address (immutable)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Creator Royalty Percentage</th>
                            <td>
                                <input type="number" name="creator_royalty" value="5" min="0" max="100" step="0.1" readonly />
                                <span>%</span>
                                <p class="description">Fixed at 5% as per smart contract (immutable)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Default Artwork Price</th>
                            <td>
                                <input type="number" name="default_price" value="100" min="1" step="1" />
                                <span>TOLA</span>
                                <p class="description">Default listing price for daily artworks</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">HURAII Generation Settings</th>
                            <td>
                                <fieldset>
                                    <label>
                                        <span>Width:</span>
                                        <input type="number" name="generation_width" value="2048" min="512" max="4096" step="64" />
                                        <span>px</span>
                                    </label>
                                    <br><br>
                                    <label>
                                        <span>Height:</span>
                                        <input type="number" name="generation_height" value="2048" min="512" max="4096" step="64" />
                                        <span>px</span>
                                    </label>
                                    <br><br>
                                    <label>
                                        <span>Steps:</span>
                                        <input type="number" name="generation_steps" value="50" min="20" max="100" step="1" />
                                    </label>
                                    <br><br>
                                    <label>
                                        <span>CFG Scale:</span>
                                        <input type="number" name="generation_cfg" value="7.5" min="1" max="20" step="0.5" />
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Automation Status</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="automation_enabled" value="1" checked />
                                    Enable daily automation
                                </label>
                                <p class="description">Uncheck to disable automatic daily generation</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Notification Settings</th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="notify_generation_complete" value="1" checked />
                                        Notify when generation completes
                                    </label>
                                    <br><br>
                                    <label>
                                        <input type="checkbox" name="notify_generation_failed" value="1" checked />
                                        Notify when generation fails
                                    </label>
                                    <br><br>
                                    <label>
                                        <input type="checkbox" name="notify_royalty_distributed" value="1" checked />
                                        Notify when royalties are distributed
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <i class="dashicons dashicons-yes"></i>
                            Save Settings
                        </button>
                        <button type="button" class="button" id="reset-settings">
                            <i class="dashicons dashicons-undo"></i>
                            Reset to Defaults
                        </button>
                    </p>
                </form>
            </div>
            
            <!-- Automation Logs Tab -->
            <div id="automation-logs" class="tab-content">
                <div class="panel-header">
                    <h3>Automation System Logs</h3>
                    <div class="header-actions">
                        <select id="log-level-filter">
                            <option value="">All Levels</option>
                            <option value="info">Info</option>
                            <option value="warning">Warning</option>
                            <option value="error">Error</option>
                        </select>
                        <button class="button" id="refresh-logs">
                            <i class="dashicons dashicons-update"></i>
                            Refresh
                        </button>
                        <button class="button" id="export-logs">
                            <i class="dashicons dashicons-download"></i>
                            Export
                        </button>
                    </div>
                </div>
                
                <div id="logs-container">
                    <div class="logs-loading">
                        <i class="dashicons dashicons-update spin"></i>
                        Loading system logs...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<div id="generation-details-modal" class="tola-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Generation Details</h3>
            <span class="modal-close">&times;</span>
        </div>
        <div class="modal-body">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<div id="artist-form-modal" class="tola-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add/Edit Artist</h3>
            <span class="modal-close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="artist-form">
                <table class="form-table">
                    <tr>
                        <th><label for="artist-name">Artist Name</label></th>
                        <td><input type="text" id="artist-name" name="artist_name" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th><label for="artist-email">Email</label></th>
                        <td><input type="email" id="artist-email" name="artist_email" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th><label for="artist-wallet">Wallet Address</label></th>
                        <td><input type="text" id="artist-wallet" name="wallet_address" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th><label for="participation-weight">Participation Weight</label></th>
                        <td>
                            <input type="number" id="participation-weight" name="participation_weight" value="1.0" min="0.1" max="10" step="0.1" />
                            <p class="description">Weight factor for royalty calculations</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="artist-active">Status</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="artist-active" name="is_active" value="1" checked />
                                Active participation
                            </label>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Save Artist</button>
                    <button type="button" class="button modal-close">Cancel</button>
                </p>
            </form>
        </div>
    </div>
</div>

<style>
/* Admin Dashboard Styles */
.tola-art-admin-container {
    max-width: 1400px;
    margin: 20px 0;
}

.tola-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #fff;
}

.stat-card.total-generated .stat-icon { background: #00a0d2; }
.stat-card.successful .stat-icon { background: #46b450; }
.stat-card.artists .stat-icon { background: #f56e28; }
.stat-card.revenue .stat-icon { background: #826eb4; }
.stat-card.royalties .stat-icon { background: #dc3232; }

.stat-content h3 {
    margin: 0 0 8px 0;
    font-size: 14px;
    color: #646970;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-number {
    display: block;
    font-size: 32px;
    font-weight: 700;
    color: #1e1e1e;
    line-height: 1;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 12px;
    color: #8c8f94;
}

.tola-today-status {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    margin-bottom: 30px;
    overflow: hidden;
}

.panel-header {
    background: #f6f7f7;
    border-bottom: 1px solid #ccd0d4;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.panel-header h2, .panel-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.header-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.today-content {
    padding: 20px;
}

.artwork-status-card {
    border: 2px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    background: #fafafa;
}

.artwork-status-card.status-listed {
    border-color: #46b450;
    background: #f0f8f0;
}

.artwork-status-card.status-generating {
    border-color: #f56e28;
    background: #fef7f0;
}

.artwork-status-card.status-failed {
    border-color: #dc3232;
    background: #fdf0f0;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
}

.status-circle {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    position: relative;
}

.status-circle.pending { background: #8c8f94; }
.status-circle.generating { background: #f56e28; animation: pulse 2s infinite; }
.status-circle.completed { background: #46b450; }
.status-circle.listed { background: #00a0d2; }
.status-circle.failed { background: #dc3232; }

.status-text {
    font-weight: 600;
    text-transform: capitalize;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    margin: 16px 0;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.detail-item label {
    font-size: 12px;
    color: #646970;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.pending { background: #f0f0f1; color: #646970; }
.status-badge.generating { background: #fef7f0; color: #f56e28; }
.status-badge.completed { background: #f0f8f0; color: #46b450; }
.status-badge.listed { background: #e5f5fa; color: #00a0d2; }
.status-badge.failed { background: #fdf0f0; color: #dc3232; }

.prompt-section {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #ddd;
}

.prompt-section h5 {
    margin: 0 0 8px 0;
    color: #646970;
}

.prompt-text {
    background: #f6f7f7;
    padding: 12px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 13px;
    line-height: 1.5;
    color: #2c3338;
}

.no-artwork-today {
    padding: 40px 20px;
    text-align: center;
}

.empty-state {
    max-width: 400px;
    margin: 0 auto;
}

.empty-state i {
    font-size: 48px;
    color: #8c8f94;
    margin-bottom: 16px;
}

.empty-state h4 {
    margin: 0 0 8px 0;
    color: #1e1e1e;
}

.empty-state p {
    color: #646970;
    margin-bottom: 20px;
}

.tola-admin-tabs .nav-tab-wrapper {
    border-bottom: 1px solid #ccd0d4;
    margin-bottom: 0;
}

.tab-content {
    display: none;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-top: none;
    border-radius: 0 0 8px 8px;
}

.tab-content.active {
    display: block;
}

.generations-table-wrapper {
    padding: 20px;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.action-buttons .button-link {
    padding: 4px;
    border-radius: 4px;
    color: #646970;
    text-decoration: none;
}

.action-buttons .button-link:hover {
    background: #f0f0f1;
    color: #1e1e1e;
}

.artist-filters {
    padding: 20px;
    background: #f6f7f7;
    border-bottom: 1px solid #ccd0d4;
    display: flex;
    gap: 20px;
    align-items: center;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-group label {
    font-weight: 600;
    color: #1e1e1e;
}

.royalty-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    padding: 20px;
    background: #f6f7f7;
}

.royalty-stat-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #ccd0d4;
}

.royalty-stat-card h4 {
    margin: 0 0 12px 0;
    color: #646970;
    font-size: 14px;
}

.stat-amount {
    font-size: 24px;
    font-weight: 700;
    color: #1e1e1e;
    margin-bottom: 8px;
}

.stat-recipient {
    font-size: 12px;
    color: #8c8f94;
}

.tola-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: #fff;
    border-radius: 8px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #ccd0d4;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #646970;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    color: #dc3232;
}

.modal-body {
    padding: 20px;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.spin {
    animation: spin 1s linear infinite;
}

/* Responsive */
@media (max-width: 768px) {
    .tola-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .panel-header {
        flex-direction: column;
        gap: 16px;
        align-items: stretch;
    }
    
    .header-actions {
        justify-content: center;
    }
    
    .detail-grid {
        grid-template-columns: 1fr;
    }
    
    .artist-filters {
        flex-direction: column;
        align-items: stretch;
    }
    
    .royalty-stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        const target = $(this).attr('href');
        
        // Update active states
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });
    
    // Manual trigger
    $('#manual-trigger-btn, #generate-today-btn').on('click', function() {
        const button = $(this);
        const originalText = button.html();
        
        button.prop('disabled', true).html('<i class="dashicons dashicons-update spin"></i> Generating...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'vortex_trigger_daily_art',
                nonce: '<?php echo wp_create_nonce("tola_art_admin_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert('Request failed. Please try again.');
            },
            complete: function() {
                button.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Modal handling
    $('.modal-close').on('click', function() {
        $(this).closest('.tola-modal').hide();
    });
    
    // Click outside modal to close
    $('.tola-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Load additional data via AJAX as needed
    function loadArtistsTable() {
        $('#artists-table-container').html('<div class="loading-placeholder"><i class="dashicons dashicons-update spin"></i> Loading artist data...</div>');
        
        // AJAX call to load artists
        // Implementation would go here
    }
    
    function loadRoyaltyData() {
        // AJAX call to load royalty distribution data
        // Implementation would go here
    }
    
    function loadSystemLogs() {
        $('#logs-container').html('<div class="logs-loading"><i class="dashicons dashicons-update spin"></i> Loading system logs...</div>');
        
        // AJAX call to load logs
        // Implementation would go here
    }
    
    // Load data when tabs are activated
    $('a[href="#artist-management"]').on('click', loadArtistsTable);
    $('a[href="#royalty-tracking"]').on('click', loadRoyaltyData);
    $('a[href="#automation-logs"]').on('click', loadSystemLogs);
});
</script> 