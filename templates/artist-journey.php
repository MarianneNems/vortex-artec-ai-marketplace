<?php
/**
 * VortexArtec Artist Journey Frontend Template
 * 
 * Template Name: Artist Journey
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

get_header();

$artist_journey = VORTEX_Artist_Journey::get_instance();
$current_user_id = get_current_user_id();
$progress = array();

if ($current_user_id) {
    // Get user's journey progress
    $progress = $artist_journey->calculate_journey_progress($current_user_id);
}
?>

<div class="vortex-artist-journey">
    <div class="container">
        
        <?php if (!is_user_logged_in()): ?>
        <!-- Landing Page for Non-Logged Users -->
        <section class="journey-landing">
            <div class="hero-section">
                <h1>Welcome to VortexArtec Artist Journey</h1>
                <p class="lead">Transform your artistic vision into a thriving NFT business</p>
                
                <div class="plan-selection">
                    <h2>Choose Your Plan</h2>
                    <div class="plans-grid">
                        <?php foreach ($artist_journey->get_subscription_plans() as $plan_key => $plan): ?>
                        <div class="plan-card" data-plan="<?php echo esc_attr($plan_key); ?>">
                            <h3><?php echo esc_html($plan['name']); ?></h3>
                            <div class="price">
                                <span class="usd">$<?php echo number_format($plan['price_usd'], 2); ?>/month</span>
                                <span class="tola"><?php echo number_format($plan['price_tola'], 2); ?> TOLA</span>
                            </div>
                            <ul class="features">
                                <?php foreach ($plan['features'] as $feature): ?>
                                <li><?php echo esc_html(str_replace('_', ' ', $feature)); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button class="btn select-plan" data-plan="<?php echo esc_attr($plan_key); ?>">
                                Select Plan
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
        
        <?php else: ?>
        <!-- Journey Progress for Logged Users -->
        <section class="journey-progress">
            <div class="progress-header">
                <h1>Your Artist Journey</h1>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $progress['progress_percentage']; ?>%"></div>
                </div>
                <span class="progress-text"><?php echo $progress['progress_percentage']; ?>% Complete</span>
            </div>
            
            <!-- Journey Steps -->
            <div class="journey-steps">
                
                <!-- Step 1: Plan Selection -->
                <div class="step <?php echo $progress['steps']['plan_selection'] ? 'completed' : 'active'; ?>" data-step="plan_selection">
                    <div class="step-icon">
                        <i class="icon-plan"></i>
                    </div>
                    <div class="step-content">
                        <h3>Plan Selection</h3>
                        <p>Choose your subscription plan and connect your wallet</p>
                        <?php if (!$progress['steps']['plan_selection']): ?>
                        <button class="btn btn-primary" id="select-plan-btn">Select Plan</button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Step 2: Wallet Connection -->
                <div class="step <?php echo $progress['steps']['wallet_connection'] ? 'completed' : ($progress['steps']['plan_selection'] ? 'active' : 'disabled'); ?>" data-step="wallet_connection">
                    <div class="step-icon">
                        <i class="icon-wallet"></i>
                    </div>
                    <div class="step-content">
                        <h3>Wallet Connection</h3>
                        <p>Connect your Solana wallet for NFT transactions</p>
                        <?php if ($progress['steps']['plan_selection'] && !$progress['steps']['wallet_connection']): ?>
                        <button class="btn btn-primary" id="connect-wallet-btn">Connect Wallet</button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Step 3: Role & Expertise Quiz -->
                <div class="step <?php echo $progress['steps']['role_quiz_completed'] ? 'completed' : ($progress['steps']['subscription_active'] ? 'active' : 'disabled'); ?>" data-step="role_quiz">
                    <div class="step-icon">
                        <i class="icon-quiz"></i>
                    </div>
                    <div class="step-content">
                        <h3>Role & Expertise Quiz</h3>
                        <p>Tell us about your artistic background and interests</p>
                        <?php if ($progress['steps']['subscription_active'] && !$progress['steps']['role_quiz_completed']): ?>
                        <button class="btn btn-primary" id="start-quiz-btn">Start Quiz</button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Step 4: Terms Agreement -->
                <div class="step <?php echo $progress['steps']['terms_agreement'] ? 'completed' : ($progress['steps']['role_quiz_completed'] ? 'active' : 'disabled'); ?>" data-step="terms_agreement">
                    <div class="step-icon">
                        <i class="icon-contract"></i>
                    </div>
                    <div class="step-content">
                        <h3>Terms Agreement</h3>
                        <p>Digital signature for seed artwork terms</p>
                        <?php if ($progress['steps']['role_quiz_completed'] && !$progress['steps']['terms_agreement']): ?>
                        <button class="btn btn-primary" id="sign-terms-btn">Sign Agreement</button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Step 5: Seed Artwork Upload -->
                <div class="step <?php echo $progress['steps']['seed_artwork_uploaded'] ? 'completed' : ($progress['steps']['terms_agreement'] ? 'active' : 'disabled'); ?>" data-step="seed_upload">
                    <div class="step-icon">
                        <i class="icon-upload"></i>
                    </div>
                    <div class="step-content">
                        <h3>Seed Artwork Upload</h3>
                        <p>Upload your initial artworks to get started</p>
                        <?php if ($progress['steps']['terms_agreement'] && !$progress['steps']['seed_artwork_uploaded']): ?>
                        <button class="btn btn-primary" id="upload-artwork-btn">Upload Artwork</button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Step 6: Horas Business Quiz (Pro Only) -->
                <?php 
                $selected_plan = get_user_meta($current_user_id, 'vortex_selected_plan', true);
                if ($selected_plan === 'pro'): 
                ?>
                <div class="step <?php echo $progress['steps']['horas_quiz_completed'] ? 'completed' : ($progress['steps']['seed_artwork_uploaded'] ? 'active' : 'disabled'); ?>" data-step="horas_quiz">
                    <div class="step-icon">
                        <i class="icon-business"></i>
                    </div>
                    <div class="step-content">
                        <h3>Horas Business Quiz</h3>
                        <p>Required for Pro plan: Create your business roadmap</p>
                        <?php if ($progress['steps']['seed_artwork_uploaded'] && !$progress['steps']['horas_quiz_completed']): ?>
                        <button class="btn btn-primary" id="start-horas-btn">Start Business Quiz</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
            
            <!-- Journey Complete -->
            <?php if ($progress['journey_complete']): ?>
            <div class="journey-complete">
                <h2>🎉 Journey Complete!</h2>
                <p>Welcome to the VortexArtec marketplace. Start creating and selling your NFTs!</p>
                <div class="dashboard-access">
                    <a href="/artist-dashboard" class="btn btn-success">Access Dashboard</a>
                    <a href="/marketplace" class="btn btn-outline">Browse Marketplace</a>
                </div>
            </div>
            <?php endif; ?>
            
        </section>
        
        <!-- Chloe AI Integration -->
        <?php if ($progress['journey_complete']): ?>
        <section class="chloe-ai-section">
            <h2>Chloe AI Recommendations</h2>
            <div class="ai-features">
                
                <div class="trend-inspiration">
                    <h3>Trend Inspiration</h3>
                    <div id="trend-cards" class="trend-cards">
                        <!-- Populated via AJAX -->
                    </div>
                    <button class="btn btn-outline" id="get-inspiration">Get New Inspiration</button>
                </div>
                
                <div class="collector-matches">
                    <h3>Collector Matches</h3>
                    <div id="collector-matches" class="collector-list">
                        <!-- Populated via AJAX -->
                    </div>
                    <button class="btn btn-outline" id="find-collectors">Find Collectors</button>
                </div>
                
            </div>
        </section>
        
        <!-- Milestone Calendar -->
        <?php if ($selected_plan === 'pro'): ?>
        <section class="milestone-calendar">
            <h2>Your Business Milestones</h2>
            <div id="milestone-calendar"></div>
        </section>
        <?php endif; ?>
        
        <?php endif; ?>
        
        <!-- TOLA Balance & Rewards -->
        <section class="tola-section">
            <div class="tola-balance">
                <h3>TOLA Balance</h3>
                <div class="balance-amount">
                    <?php echo number_format(get_user_meta($current_user_id, 'vortex_tola_balance', true) ?: 0, 2); ?> TOLA
                </div>
            </div>
            
            <div class="rewards-info">
                <h4>Earn TOLA Tokens</h4>
                <ul>
                    <li>+5 TOLA for uploading artwork</li>
                    <li>+2 TOLA for artwork downloads</li>
                    <li>+10% of sale price for selling artwork</li>
                    <li>+15 TOLA for completing milestones</li>
                    <li>+10 TOLA for daily art collaboration</li>
                </ul>
            </div>
        </section>
        
        <?php endif; ?>
        
    </div>
</div>

<!-- Modals for Journey Steps -->
<div id="plan-selection-modal" class="modal">
    <div class="modal-content">
        <h3>Complete Plan Selection</h3>
        <div class="plan-form">
            <!-- Plan selection and payment form -->
        </div>
    </div>
</div>

<div id="wallet-connection-modal" class="modal">
    <div class="modal-content">
        <h3>Connect Your Solana Wallet</h3>
        <div class="wallet-options">
            <button class="wallet-btn" data-wallet="phantom">
                <img src="/images/phantom-wallet.png" alt="Phantom">
                Phantom
            </button>
            <button class="wallet-btn" data-wallet="solflare">
                <img src="/images/solflare-wallet.png" alt="Solflare">
                Solflare
            </button>
        </div>
    </div>
</div>

<div id="role-quiz-modal" class="modal">
    <div class="modal-content">
        <h3>Tell Us About Yourself</h3>
        <form id="role-quiz-form">
            <div class="form-group">
                <label>I am primarily a:</label>
                <div class="radio-group">
                    <label><input type="radio" name="role" value="artist"> Artist</label>
                    <label><input type="radio" name="role" value="collector"> Collector</label>
                </div>
            </div>
            
            <div class="form-group">
                <label>Expertise Level:</label>
                <select name="expertise_level">
                    <option value="beginner">Beginner</option>
                    <option value="intermediate">Intermediate</option>
                    <option value="advanced">Advanced</option>
                    <option value="professional">Professional</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Art Category:</label>
                <select name="category">
                    <option value="digital_art">Digital Art</option>
                    <option value="photography">Photography</option>
                    <option value="illustration">Illustration</option>
                    <option value="3d_art">3D Art</option>
                    <option value="abstract">Abstract</option>
                    <option value="conceptual">Conceptual</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Complete Quiz</button>
        </form>
    </div>
</div>

<div id="horas-quiz-modal" class="modal">
    <div class="modal-content">
        <h3>Horas Business Quiz</h3>
        <form id="horas-quiz-form">
            <div class="form-group">
                <label>Business Idea:</label>
                <textarea name="business_idea" rows="4" placeholder="Describe your artistic business vision..."></textarea>
            </div>
            
            <div class="milestones-section">
                <h4>Roadmap Milestones</h4>
                <div id="milestones-container">
                    <div class="milestone-item">
                        <input type="text" name="milestones[0][title]" placeholder="Milestone title">
                        <textarea name="milestones[0][description]" placeholder="Description"></textarea>
                        <input type="date" name="milestones[0][target_date]">
                    </div>
                </div>
                <button type="button" id="add-milestone">Add Milestone</button>
            </div>
            
            <button type="submit" class="btn btn-primary">Generate Business Plan</button>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    
    // Journey step handlers
    $('#select-plan-btn').on('click', function() {
        $('#plan-selection-modal').show();
    });
    
    $('#connect-wallet-btn').on('click', function() {
        $('#wallet-connection-modal').show();
    });
    
    $('#start-quiz-btn').on('click', function() {
        $('#role-quiz-modal').show();
    });
    
    $('#start-horas-btn').on('click', function() {
        $('#horas-quiz-modal').show();
    });
    
    // Chloe AI integration
    $('#get-inspiration').on('click', function() {
        $.ajax({
            url: vortex_ajax.url,
            type: 'POST',
            data: {
                action: 'vortex_get_chloe_inspiration',
                nonce: vortex_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderTrendCards(response.data);
                }
            }
        });
    });
    
    $('#find-collectors').on('click', function() {
        $.ajax({
            url: vortex_ajax.url,
            type: 'POST',
            data: {
                action: 'vortex_get_collector_matches',
                nonce: vortex_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderCollectorMatches(response.data.matches);
                }
            }
        });
    });
    
    // Form submissions
    $('#role-quiz-form').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: vortex_ajax.url,
            type: 'POST',
            data: {
                action: 'vortex_role_expertise_quiz',
                nonce: vortex_ajax.nonce,
                role: $('input[name="role"]:checked').val(),
                expertise_level: $('select[name="expertise_level"]').val(),
                category: $('select[name="category"]').val()
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });
    
    // Helper functions
    function renderTrendCards(trends) {
        const container = $('#trend-cards');
        container.empty();
        
        trends.trends.forEach(function(trend) {
            const card = $(`
                <div class="trend-card">
                    <h4>${trend.title}</h4>
                    <p>${trend.description}</p>
                    <div class="confidence">Confidence: ${Math.round(trend.confidence * 100)}%</div>
                </div>
            `);
            container.append(card);
        });
    }
    
    function renderCollectorMatches(matches) {
        const container = $('#collector-matches');
        container.empty();
        
        matches.forEach(function(match) {
            const item = $(`
                <div class="collector-item">
                    <h4>${match.name}</h4>
                    <div class="match-score">Match: ${Math.round(match.match_score * 100)}%</div>
                    <div class="budget">Budget: ${match.budget_range}</div>
                    <button class="btn btn-sm contact-collector" data-id="${match.collector_id}">
                        Contact
                    </button>
                </div>
            `);
            container.append(item);
        });
    }
    
});
</script>

<style>
.vortex-artist-journey {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.journey-progress {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 30px;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
    margin: 20px 0;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #007bff, #28a745);
    transition: width 0.5s ease;
}

.journey-steps {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.step {
    display: flex;
    align-items: center;
    padding: 20px;
    background: white;
    border-radius: 8px;
    border-left: 4px solid #dee2e6;
}

.step.active {
    border-left-color: #007bff;
    box-shadow: 0 2px 8px rgba(0,123,255,0.15);
}

.step.completed {
    border-left-color: #28a745;
    background: #f8fff9;
}

.step.disabled {
    opacity: 0.6;
}

.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.plan-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    transition: all 0.3s ease;
}

.plan-card:hover {
    border-color: #007bff;
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,123,255,0.15);
}

.chloe-ai-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    padding: 40px;
    margin: 30px 0;
}

.trend-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.trend-card {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    border-radius: 8px;
    padding: 20px;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
}

.modal-content {
    background: white;
    margin: 10% auto;
    padding: 30px;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
    transform: translateY(-1px);
}
</style>

<?php get_footer(); ?> 