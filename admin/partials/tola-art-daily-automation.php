<?php
/**
 * Admin interface for TOLA-ART Daily Automation
 * Displays royalty structure: First sale (5% creator + 95% artists), Second+ sales (5% creator + 15% artists + 80% owner/reseller)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get automation instance
$automation = Vortex_TOLA_Art_Daily_Automation::get_instance();

// Get recent daily art data
global $wpdb;
$daily_art_table = $wpdb->prefix . 'vortex_daily_art';
$royalty_table = $wpdb->prefix . 'vortex_royalty_distribution';

// Get statistics
$recent_art = $wpdb->get_results(
    "SELECT * FROM {$daily_art_table} ORDER BY date DESC LIMIT 10"
);

$total_generated = $wpdb->get_var("SELECT COUNT(*) FROM {$daily_art_table}");
$total_sales = $wpdb->get_var("SELECT SUM(total_sales) FROM {$daily_art_table}");
$total_royalties = $wpdb->get_var("SELECT SUM(royalties_distributed) FROM {$daily_art_table}");

// Get royalty distributions
$recent_royalties = $wpdb->get_results(
    "SELECT rd.*, da.date, da.artwork_id 
     FROM {$royalty_table} rd 
     JOIN {$daily_art_table} da ON rd.daily_art_id = da.id 
     ORDER BY rd.created_at DESC LIMIT 20"
);
?>

<div class="wrap">
    <h1>TOLA-ART Daily Automation</h1>
    
    <!-- Royalty Structure Card -->
    <div class="card" style="max-width: none; margin-bottom: 20px;">
        <h2 class="title">📊 Royalty Distribution Structure</h2>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
            
            <!-- First Sale Structure -->
            <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; border-left: 4px solid #4caf50;">
                <h3 style="margin-top: 0; color: #2e7d32;">🎯 First Sale (Primary Market)</h3>
                <div style="font-size: 14px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span><strong>Creator (Marianne Nems):</strong></span>
                        <span style="background: #4caf50; color: white; padding: 2px 8px; border-radius: 12px; font-weight: bold;">5%</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span><strong>Participating Artists:</strong></span>
                        <span style="background: #2196f3; color: white; padding: 2px 8px; border-radius: 12px; font-weight: bold;">95%</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span><strong>Marketplace Fee:</strong></span>
                        <span style="background: #9e9e9e; color: white; padding: 2px 8px; border-radius: 12px; font-weight: bold;">0%</span>
                    </div>
                    <hr style="margin: 10px 0;">
                    <div style="display: flex; justify-content: space-between; font-weight: bold;">
                        <span>Total:</span>
                        <span>100%</span>
                    </div>
                </div>
            </div>
            
            <!-- Resale Structure -->
            <div style="background: #fff3e0; padding: 15px; border-radius: 8px; border-left: 4px solid #ff9800;">
                <h3 style="margin-top: 0; color: #f57c00;">🔄 Resale (Secondary Market)</h3>
                <div style="font-size: 14px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span><strong>Creator (Marianne Nems):</strong></span>
                        <span style="background: #4caf50; color: white; padding: 2px 8px; border-radius: 12px; font-weight: bold;">5%</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span><strong>Participating Artists:</strong></span>
                        <span style="background: #2196f3; color: white; padding: 2px 8px; border-radius: 12px; font-weight: bold;">15%</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span><strong>Owner/Reseller:</strong></span>
                        <span style="background: #ff9800; color: white; padding: 2px 8px; border-radius: 12px; font-weight: bold;">80%</span>
                    </div>
                    <hr style="margin: 10px 0;">
                    <div style="display: flex; justify-content: space-between; font-weight: bold;">
                        <span>Total:</span>
                        <span>100%</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 15px; padding: 10px; background: #f5f5f5; border-radius: 5px; font-size: 13px; color: #666;">
            <strong>📋 Key Features:</strong>
            • Artists receive maximum benefit on first sales (95% share)<br>
            • Creator always receives consistent 5% royalty on all sales<br>
            • No marketplace fees on any sales - 100% goes to participants<br>
            • Automated smart contract distribution via TOLA token<br>
            • Blockchain-verified transparency and immutable records
        </div>
    </div>

    <!-- Statistics Dashboard -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
        <div class="card" style="text-align: center; padding: 20px;">
            <h3 style="margin: 0; color: #2196f3; font-size: 32px;"><?php echo number_format($total_generated); ?></h3>
            <p style="margin: 5px 0 0 0; color: #666;">Total Artworks Generated</p>
        </div>
        <div class="card" style="text-align: center; padding: 20px;">
            <h3 style="margin: 0; color: #4caf50; font-size: 32px;"><?php echo number_format($total_sales, 2); ?></h3>
            <p style="margin: 5px 0 0 0; color: #666;">Total Sales (TOLA)</p>
        </div>
        <div class="card" style="text-align: center; padding: 20px;">
            <h3 style="margin: 0; color: #ff9800; font-size: 32px;"><?php echo number_format($total_royalties, 2); ?></h3>
            <p style="margin: 5px 0 0 0; color: #666;">Royalties Distributed (TOLA)</p>
        </div>
        <div class="card" style="text-align: center; padding: 20px;">
            <h3 style="margin: 0; color: #9c27b0; font-size: 32px;"><?php echo wp_get_current_user()->display_name === 'admin' ? 'ACTIVE' : 'RUNNING'; ?></h3>
            <p style="margin: 5px 0 0 0; color: #666;">Automation Status</p>
        </div>
    </div>

    <!-- Actions -->
    <div class="card" style="margin-bottom: 25px;">
        <h2 class="title">⚡ Actions</h2>
        <p>
            <button type="button" class="button button-primary" onclick="triggerDailyArt()">
                🎨 Generate Today's Art Manually
            </button>
            <button type="button" class="button" onclick="refreshStats()">
                🔄 Refresh Statistics
            </button>
            <button type="button" class="button" onclick="exportRoyaltyReport()">
                📊 Export Royalty Report
            </button>
            <button type="button" class="button button-secondary" onclick="testSmartContract()">
                🔗 Test Smart Contract
            </button>
        </p>
    </div>

    <!-- Recent Daily Art -->
    <div class="card">
        <h2 class="title">🎨 Recent Daily Art</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col">Date</th>
                    <th scope="col">Status</th>
                    <th scope="col">Artwork ID</th>
                    <th scope="col">Smart Contract</th>
                    <th scope="col">Total Sales</th>
                    <th scope="col">Participating Artists</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_art)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                            No daily art generated yet. The automation will run daily at midnight.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_art as $art): ?>
                        <tr>
                            <td><strong><?php echo date('M j, Y', strtotime($art->date)); ?></strong></td>
                            <td>
                                <?php
                                $status_colors = array(
                                    'pending' => '#ff9800',
                                    'generating' => '#2196f3',
                                    'completed' => '#4caf50',
                                    'failed' => '#f44336',
                                    'listed' => '#9c27b0'
                                );
                                $color = $status_colors[$art->generation_status] ?? '#666';
                                ?>
                                <span style="background: <?php echo $color; ?>; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                                    <?php echo strtoupper($art->generation_status); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($art->artwork_id): ?>
                                    <a href="<?php echo get_edit_post_link($art->artwork_id); ?>" target="_blank">
                                        #<?php echo $art->artwork_id; ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #999;">Not created</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($art->smart_contract_address): ?>
                                    <code style="font-size: 11px;"><?php echo substr($art->smart_contract_address, 0, 10); ?>...</code>
                                <?php else: ?>
                                    <span style="color: #999;">Not deployed</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo number_format($art->total_sales, 2); ?></strong> TOLA
                            </td>
                            <td>
                                <span style="background: #e3f2fd; color: #1976d2; padding: 2px 6px; border-radius: 10px; font-size: 12px;">
                                    <?php echo $art->participating_artists_count; ?> artists
                                </span>
                            </td>
                            <td>
                                <button type="button" class="button button-small" onclick="viewArtDetails(<?php echo $art->id; ?>)">
                                    👁️ View
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($recent_royalties)): ?>
    <!-- Recent Royalty Distributions -->
    <div class="card" style="margin-top: 25px;">
        <h2 class="title">💰 Recent Royalty Distributions</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col">Date</th>
                    <th scope="col">Sale Type</th>
                    <th scope="col">Sale Amount</th>
                    <th scope="col">Creator (5%)</th>
                    <th scope="col">Artists</th>
                    <th scope="col">Owner</th>
                    <th scope="col">Status</th>
                    <th scope="col">TX Hash</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_royalties as $royalty): ?>
                    <tr>
                        <td><?php echo date('M j, Y', strtotime($royalty->date)); ?></td>
                        <td>
                            <?php if (isset($royalty->sale_type) && $royalty->sale_type === 'resale'): ?>
                                <span style="background: #ff9800; color: white; padding: 2px 6px; border-radius: 10px; font-size: 11px;">RESALE</span>
                            <?php else: ?>
                                <span style="background: #4caf50; color: white; padding: 2px 6px; border-radius: 10px; font-size: 11px;">FIRST SALE</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo number_format($royalty->sale_amount, 2); ?></strong> TOLA</td>
                        <td><?php echo number_format($royalty->creator_royalty, 2); ?> TOLA</td>
                        <td>
                            <?php echo number_format($royalty->artist_pool, 2); ?> TOLA
                            <small style="color: #666;">
                                (<?php echo isset($royalty->sale_type) && $royalty->sale_type === 'resale' ? '15%' : '95%'; ?>)
                            </small>
                        </td>
                        <td>
                            <?php if (isset($royalty->owner_amount) && $royalty->owner_amount > 0): ?>
                                <?php echo number_format($royalty->owner_amount, 2); ?> TOLA
                                <small style="color: #666;">(80%)</small>
                            <?php else: ?>
                                <span style="color: #999;">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $status_colors = array(
                                'pending' => '#ff9800',
                                'processing' => '#2196f3',
                                'completed' => '#4caf50',
                                'failed' => '#f44336'
                            );
                            $color = $status_colors[$royalty->distribution_status] ?? '#666';
                            ?>
                            <span style="background: <?php echo $color; ?>; color: white; padding: 2px 6px; border-radius: 10px; font-size: 11px;">
                                <?php echo strtoupper($royalty->distribution_status); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($royalty->distribution_transaction_hash): ?>
                                <code style="font-size: 10px;"><?php echo substr($royalty->distribution_transaction_hash, 0, 8); ?>...</code>
                            <?php else: ?>
                                <span style="color: #999;">Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
function triggerDailyArt() {
    if (confirm('Generate today\'s TOLA-ART manually? This will create the daily artwork if it doesn\'t exist.')) {
        jQuery.post(ajaxurl, {
            action: 'vortex_trigger_daily_art',
            nonce: '<?php echo wp_create_nonce('vortex_daily_art_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                alert('✅ Daily art generation started! Check back in a few minutes.');
                location.reload();
            } else {
                alert('❌ Error: ' + response.data);
            }
        });
    }
}

function refreshStats() {
    jQuery.post(ajaxurl, {
        action: 'vortex_get_daily_art_stats',
        nonce: '<?php echo wp_create_nonce('vortex_daily_art_nonce'); ?>'
    }, function(response) {
        if (response.success) {
            location.reload();
        }
    });
}

function exportRoyaltyReport() {
    window.open('<?php echo admin_url('admin-ajax.php?action=vortex_export_royalty_report'); ?>', '_blank');
}

function testSmartContract() {
    alert('🧪 Smart contract test will be implemented. This will verify the dual royalty structure is working correctly.');
}

function viewArtDetails(dailyArtId) {
    // This would open a modal or redirect to detailed view
    alert('📊 Detailed view for daily art ID: ' + dailyArtId + ' will be implemented.');
}
</script>

<style>
.card {
    background: white;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.card .title {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 15px 0;
    color: #1d2327;
}

.wp-list-table th {
    font-weight: 600;
    background: #f6f7f7;
}

.wp-list-table td, .wp-list-table th {
    padding: 12px 8px;
    border-bottom: 1px solid #e1e1e1;
}

code {
    background: #f1f1f1;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}
</style> 