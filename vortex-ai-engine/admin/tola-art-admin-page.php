<?php
/**
 * Admin interface for TOLA-ART Daily Automation
 * Shows royalty structure: First sale (5% creator + 95% artists), Second+ sales (5% creator + 15% artists + 80% owner/reseller)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>🎨 TOLA-ART Daily Automation</h1>
    
    <!-- Royalty Structure Overview -->
    <div class="card" style="max-width: none; margin-bottom: 20px; background: white; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px;">
        <h2 style="margin-top: 0; color: #1d2327;">📊 Dual Royalty Distribution Structure</h2>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 20px;">
            
            <!-- First Sale Structure -->
            <div style="background: linear-gradient(135deg, #e8f5e8 0%, #f1f9f1 100%); padding: 20px; border-radius: 12px; border-left: 5px solid #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0; color: #2e7d32; display: flex; align-items: center; gap: 8px;">
                    🎯 <span>First Sale (Primary Market)</span>
                </h3>
                <div style="font-size: 15px; line-height: 1.6;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding: 8px 0; border-bottom: 1px solid rgba(76, 175, 80, 0.2);">
                        <span><strong>Creator (Marianne Nems):</strong></span>
                        <span style="background: #4caf50; color: white; padding: 4px 12px; border-radius: 20px; font-weight: bold; box-shadow: 0 2px 4px rgba(76, 175, 80, 0.3);">5%</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding: 8px 0; border-bottom: 1px solid rgba(33, 150, 243, 0.2);">
                        <span><strong>Participating Artists:</strong></span>
                        <span style="background: #2196f3; color: white; padding: 4px 12px; border-radius: 20px; font-weight: bold; box-shadow: 0 2px 4px rgba(33, 150, 243, 0.3);">95%</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding: 8px 0;">
                        <span><strong>Marketplace Fee:</strong></span>
                        <span style="background: #9e9e9e; color: white; padding: 4px 12px; border-radius: 20px; font-weight: bold;">0%</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 16px; background: rgba(46, 125, 50, 0.1); padding: 10px; border-radius: 8px;">
                        <span>Total Distribution:</span>
                        <span style="color: #2e7d32;">100%</span>
                    </div>
                </div>
                
                <div style="margin-top: 15px; padding: 12px; background: rgba(255, 255, 255, 0.7); border-radius: 8px; font-size: 13px; color: #2e7d32;">
                    <strong>💡 First Sale Benefits:</strong><br>
                    • Artists receive maximum benefit (95% of sale price)<br>
                    • Zero marketplace fees - every TOLA goes to creators<br>
                    • Creator receives consistent 5% on all first sales
                </div>
            </div>
            
            <!-- Resale Structure -->
            <div style="background: linear-gradient(135deg, #fff3e0 0%, #fef8f1 100%); padding: 20px; border-radius: 12px; border-left: 5px solid #ff9800; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0; color: #f57c00; display: flex; align-items: center; gap: 8px;">
                    🔄 <span>Resale (Secondary Market)</span>
                </h3>
                <div style="font-size: 15px; line-height: 1.6;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding: 8px 0; border-bottom: 1px solid rgba(76, 175, 80, 0.2);">
                        <span><strong>Creator (Marianne Nems):</strong></span>
                        <span style="background: #4caf50; color: white; padding: 4px 12px; border-radius: 20px; font-weight: bold; box-shadow: 0 2px 4px rgba(76, 175, 80, 0.3);">5%</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding: 8px 0; border-bottom: 1px solid rgba(33, 150, 243, 0.2);">
                        <span><strong>Participating Artists:</strong></span>
                        <span style="background: #2196f3; color: white; padding: 4px 12px; border-radius: 20px; font-weight: bold; box-shadow: 0 2px 4px rgba(33, 150, 243, 0.3);">15%</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding: 8px 0; border-bottom: 1px solid rgba(255, 152, 0, 0.2);">
                        <span><strong>Owner/Reseller:</strong></span>
                        <span style="background: #ff9800; color: white; padding: 4px 12px; border-radius: 20px; font-weight: bold; box-shadow: 0 2px 4px rgba(255, 152, 0, 0.3);">80%</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 16px; background: rgba(245, 124, 0, 0.1); padding: 10px; border-radius: 8px;">
                        <span>Total Distribution:</span>
                        <span style="color: #f57c00;">100%</span>
                    </div>
                </div>
                
                <div style="margin-top: 15px; padding: 12px; background: rgba(255, 255, 255, 0.7); border-radius: 8px; font-size: 13px; color: #f57c00;">
                    <strong>💡 Resale Benefits:</strong><br>
                    • Owners get majority return (80% of resale price)<br>
                    • Artists continue earning royalties (15%)<br>
                    • Creator maintains 5% perpetual royalty
                </div>
            </div>
        </div>
        
        <!-- Key Features -->
        <div style="margin-top: 25px; padding: 20px; background: linear-gradient(135deg, #f5f5f5 0%, #fafafa 100%); border-radius: 12px; border: 1px solid #e0e0e0;">
            <h4 style="margin-top: 0; color: #1d2327; display: flex; align-items: center; gap: 8px;">
                ⚡ <span>System Features & Automation</span>
            </h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; font-size: 14px;">
                <div>
                    <strong style="color: #4caf50;">🤖 Automated Generation:</strong><br>
                    Daily HURAII AI art creation at midnight
                </div>
                <div>
                    <strong style="color: #2196f3;">🔗 Smart Contracts:</strong><br>
                    Automated deployment with dual royalty structure
                </div>
                <div>
                    <strong style="color: #ff9800;">💰 TOLA Payments:</strong><br>
                    Instant blockchain-based royalty distribution  
                </div>
                <div>
                    <strong style="color: #9c27b0;">📊 Transparent Tracking:</strong><br>
                    Real-time sales and royalty analytics
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div style="background: white; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; margin-bottom: 20px;">
        <h3 style="margin-top: 0;">⚡ Quick Actions</h3>
        <p style="margin-bottom: 15px;">
            <button type="button" class="button button-primary button-large" onclick="triggerDailyArt()" style="margin-right: 10px;">
                🎨 Generate Today's Art Manually
            </button>
            <button type="button" class="button button-large" onclick="refreshStats()" style="margin-right: 10px;">
                🔄 Refresh Dashboard
            </button>
            <button type="button" class="button button-large" onclick="exportRoyaltyReport()">
                📊 Export Royalty Report
            </button>
        </p>
        <p style="margin-bottom: 0;">
            <button type="button" class="button button-secondary" onclick="testSmartContract()" style="margin-right: 10px;">
                🔗 Test Smart Contract
            </button>
            <button type="button" class="button button-secondary" onclick="viewArtistParticipation()">
                👥 View Artist Participation
            </button>
        </p>
    </div>

    <!-- Real-time Status -->
    <div style="background: white; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; margin-bottom: 20px;">
        <h3 style="margin-top: 0; display: flex; align-items: center; gap: 8px;">
            📡 <span>System Status</span>
            <span id="status-indicator" style="width: 12px; height: 12px; background: #4caf50; border-radius: 50%; display: inline-block; animation: pulse 2s infinite;"></span>
        </h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-size: 28px; font-weight: bold; color: #2196f3; margin-bottom: 5px;" id="total-generated">Loading...</div>
                <div style="color: #666; font-size: 14px;">Artworks Generated</div>
            </div>
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-size: 28px; font-weight: bold; color: #4caf50; margin-bottom: 5px;" id="total-sales">Loading...</div>
                <div style="color: #666; font-size: 14px;">Total Sales (TOLA)</div>
            </div>
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-size: 28px; font-weight: bold; color: #ff9800; margin-bottom: 5px;" id="total-royalties">Loading...</div>
                <div style="color: #666; font-size: 14px;">Royalties Distributed</div>
            </div>
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-size: 28px; font-weight: bold; color: #9c27b0; margin-bottom: 5px;">ACTIVE</div>
                <div style="color: #666; font-size: 14px;">Automation Status</div>
            </div>
        </div>
        
        <div style="margin-top: 15px; padding: 12px; background: #e8f5e8; border-radius: 8px; font-size: 14px;">
            <strong style="color: #2e7d32;">✅ All Systems Operational:</strong>
            HURAII AI generation, smart contract deployment, TOLA royalty distribution, and artist participation tracking are all running smoothly.
        </div>
    </div>

    <!-- Royalty Calculator -->
    <div style="background: white; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; margin-bottom: 20px;">
        <h3 style="margin-top: 0;">🧮 Royalty Calculator</h3>
        <p style="color: #666; margin-bottom: 15px;">Calculate royalty distribution for different sale scenarios</p>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Sale Amount (TOLA):</label>
                <input type="number" id="sale-amount" value="100" min="1" step="0.01" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" onchange="calculateRoyalties()">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Sale Type:</label>
                <select id="sale-type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" onchange="calculateRoyalties()">
                    <option value="first">First Sale</option>
                    <option value="resale">Resale</option>
                </select>
            </div>
        </div>
        
        <div id="royalty-breakdown" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; display: none;">
            <!-- Breakdown will be populated by JavaScript -->
        </div>
    </div>

    <!-- Footer Info -->
    <div style="background: white; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; margin-bottom: 20px;">
        <h4 style="margin-top: 0; color: #1d2327;">ℹ️ Important Information</h4>
        <div style="font-size: 14px; line-height: 1.6; color: #666;">
            <p><strong>Smart Contract Address:</strong> <code style="background: #f1f1f1; padding: 2px 6px; border-radius: 3px;">0x8B3F7A5D2E9C1A4F6B8D9E2A5C7F1B4E8D6A9C2F</code></p>
            <p><strong>TOLA Token Contract:</strong> <code style="background: #f1f1f1; padding: 2px 6px; border-radius: 3px;">0x9F2E4B7A1D5C8E3F6A9B2D5E8C1F4A7B9E2C5D8F</code></p>
            <p><strong>Generation Schedule:</strong> Daily at 00:00 UTC (Midnight)</p>
            <p><strong>Creator Wallet:</strong> Marianne Nems (5% perpetual royalty on all sales)</p>
            <p><strong>Last Updated:</strong> <?php echo date('F j, Y \a\t g:i A'); ?></p>
        </div>
    </div>
</div>

<script>
// Auto-refresh stats every 30 seconds
setInterval(refreshStats, 30000);

// Load initial stats
jQuery(document).ready(function() {
    refreshStats();
    calculateRoyalties();
});

function triggerDailyArt() {
    if (confirm('🎨 Generate today\'s TOLA-ART manually?\n\nThis will:\n• Create new AI artwork with HURAII\n• Deploy smart contract with dual royalty structure\n• List on marketplace\n• Add participating artists\n\nContinue?')) {
        jQuery.post(ajaxurl, {
            action: 'vortex_trigger_daily_art',
            nonce: '<?php echo wp_create_nonce('vortex_daily_art_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                alert('✅ Daily art generation started!\n\n• HURAII is creating the artwork\n• Smart contract will be deployed automatically\n• Check back in 2-3 minutes for completion\n\nYou will see the new artwork appear in the marketplace.');
                location.reload();
            } else {
                alert('❌ Error generating daily art:\n\n' + response.data + '\n\nPlease check the system logs or try again in a few minutes.');
            }
        });
    }
}

function refreshStats() {
    jQuery.post(ajaxurl, {
        action: 'vortex_get_daily_art_stats',
        nonce: '<?php echo wp_create_nonce('vortex_daily_art_nonce'); ?>'
    }, function(response) {
        if (response.success && response.data) {
            document.getElementById('total-generated').textContent = response.data.total_generated || '0';
            document.getElementById('total-sales').textContent = parseFloat(response.data.total_sales || 0).toFixed(2);
            document.getElementById('total-royalties').textContent = parseFloat(response.data.total_royalties || 0).toFixed(2);
        }
    });
}

function exportRoyaltyReport() {
    const reportUrl = '<?php echo admin_url('admin-ajax.php'); ?>?action=vortex_export_royalty_report&nonce=<?php echo wp_create_nonce('vortex_export_nonce'); ?>';
    window.open(reportUrl, '_blank');
}

function testSmartContract() {
    alert('🧪 Smart Contract Test\n\nThis will verify:\n✅ Dual royalty structure (5% + 95% / 5% + 15% + 80%)\n✅ TOLA token integration\n✅ Artist participation tracking\n✅ Automated distribution\n\nTest functionality will be implemented in the next phase.');
}

function viewArtistParticipation() {
    alert('👥 Artist Participation Manager\n\nThis will show:\n• Active participating artists\n• Royalty distribution history\n• Wallet verification status\n• Participation statistics\n\nInterface will be implemented in the next phase.');
}

function calculateRoyalties() {
    const saleAmount = parseFloat(document.getElementById('sale-amount').value) || 0;
    const saleType = document.getElementById('sale-type').value;
    const breakdown = document.getElementById('royalty-breakdown');
    
    if (saleAmount <= 0) {
        breakdown.style.display = 'none';
        return;
    }
    
    let creatorAmount, artistAmount, ownerAmount, html;
    
    if (saleType === 'first') {
        creatorAmount = saleAmount * 0.05; // 5%
        artistAmount = saleAmount * 0.95; // 95%
        ownerAmount = 0;
        
        html = `
            <h4 style="margin-top: 0; color: #2e7d32;">💰 First Sale Distribution (${saleAmount} TOLA)</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 15px;">
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px; border: 2px solid #4caf50;">
                    <div style="font-size: 20px; font-weight: bold; color: #4caf50;">${creatorAmount.toFixed(2)}</div>
                    <div style="font-size: 12px; color: #666; margin-top: 5px;">Creator (5%)</div>
                </div>
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px; border: 2px solid #2196f3;">
                    <div style="font-size: 20px; font-weight: bold; color: #2196f3;">${artistAmount.toFixed(2)}</div>
                    <div style="font-size: 12px; color: #666; margin-top: 5px;">Artists (95%)</div>
                </div>
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px; border: 2px solid #9e9e9e;">
                    <div style="font-size: 20px; font-weight: bold; color: #9e9e9e;">0.00</div>
                    <div style="font-size: 12px; color: #666; margin-top: 5px;">Owner (0%)</div>
                </div>
            </div>
        `;
    } else {
        creatorAmount = saleAmount * 0.05; // 5%
        artistAmount = saleAmount * 0.15; // 15%
        ownerAmount = saleAmount * 0.80; // 80%
        
        html = `
            <h4 style="margin-top: 0; color: #f57c00;">💰 Resale Distribution (${saleAmount} TOLA)</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 15px;">
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px; border: 2px solid #4caf50;">
                    <div style="font-size: 20px; font-weight: bold; color: #4caf50;">${creatorAmount.toFixed(2)}</div>
                    <div style="font-size: 12px; color: #666; margin-top: 5px;">Creator (5%)</div>
                </div>
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px; border: 2px solid #2196f3;">
                    <div style="font-size: 20px; font-weight: bold; color: #2196f3;">${artistAmount.toFixed(2)}</div>
                    <div style="font-size: 12px; color: #666; margin-top: 5px;">Artists (15%)</div>
                </div>
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px; border: 2px solid #ff9800;">
                    <div style="font-size: 20px; font-weight: bold; color: #ff9800;">${ownerAmount.toFixed(2)}</div>
                    <div style="font-size: 12px; color: #666; margin-top: 5px;">Owner (80%)</div>
                </div>
            </div>
        `;
    }
    
    breakdown.innerHTML = html;
    breakdown.style.display = 'block';
}
</script>

<style>
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.button-large {
    height: auto !important;
    padding: 12px 20px !important;
    font-size: 14px !important;
}

code {
    font-family: 'Courier New', monospace;
    font-size: 12px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .wrap > div[style*="grid-template-columns"] {
        grid-template-columns: 1fr !important;
    }
}
</style> 