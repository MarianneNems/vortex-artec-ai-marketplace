<?php
/**
 * 💎 VORTEX ARTIST SWAPPING MARKETPLACE - "SWAPPING GEM"
 * 
 * Artist-to-Artist Image Trading Platform with Smart Contract Integration
 * Every image swap is secured by TOLA blockchain smart contracts
 * 
 * Copyright © 2024 VORTEX AI AGENTS. ALL RIGHTS RESERVED.
 * This handles the marketplace for artist image swapping and trading
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Artist_Swapping_Marketplace
 * @copyright 2024 VORTEX AI AGENTS
 * @license PROPRIETARY - ALL RIGHTS RESERVED
 * @version 1.0.0-SWAPPING-GEM
 */

// 🛡️ PROTECTION: Prevent direct access
if (!defined('ABSPATH')) {
    wp_die('🔒 VORTEX ARTIST SWAPPING MARKETPLACE - UNAUTHORIZED ACCESS DENIED');
}

class VORTEX_Artist_Swapping_Marketplace {
    
    private static $instance = null;
    
    /**
     * 💎 SWAPPING GEM CONFIGURATION
     * Core marketplace settings and rules
     */
    private $swapping_gem_config = array(
        'marketplace_name' => 'VORTEX Swapping Gem',
        'enabled' => true,
        'minimum_artist_level' => 2,
        'swap_fee_percentage' => 2.5,
        'platform_commission' => 1.0,
        'reputation_bonus_multiplier' => 1.2,
        'daily_swap_limit' => 5,
        'weekly_swap_limit' => 20,
        'monthly_swap_limit' => 50,
        'escrow_duration_hours' => 24,
        'dispute_resolution_period_days' => 7
    );
    
    /**
     * 🎨 SWAP CATEGORIES AND TYPES
     * Different ways artists can exchange their artwork
     */
    private $swap_categories = array(
        'zodiac_harmony' => array(
            'name' => 'Zodiac Harmony Swap',
            'description' => 'Exchange artworks with complementary zodiac energies',
            'bonus_multiplier' => 1.3,
            'matching_algorithm' => 'zodiac_compatibility',
            'rarity_boost' => true,
            'fee_discount' => 10 // 10% discount
        ),
        'elemental_exchange' => array(
            'name' => 'Elemental Energy Exchange',
            'description' => 'Trade artworks based on elemental affinities (Fire, Earth, Air, Water)',
            'bonus_multiplier' => 1.2,
            'matching_algorithm' => 'elemental_compatibility',
            'rarity_boost' => false,
            'fee_discount' => 5
        ),
        'style_synthesis' => array(
            'name' => 'Artistic Style Synthesis',
            'description' => 'Swap artworks to complement or contrast artistic styles',
            'bonus_multiplier' => 1.1,
            'matching_algorithm' => 'style_analysis',
            'rarity_boost' => false,
            'fee_discount' => 0
        ),
        'rarity_resonance' => array(
            'name' => 'Rarity Resonance Swap',
            'description' => 'Exchange rare and legendary artworks of similar value',
            'bonus_multiplier' => 1.5,
            'matching_algorithm' => 'rarity_matching',
            'rarity_boost' => true,
            'fee_discount' => 15,
            'minimum_rarity' => 'rare'
        ),
        'collaborative_creation' => array(
            'name' => 'Collaborative Creation Exchange',
            'description' => 'Trade individual pieces to create collaborative collections',
            'bonus_multiplier' => 1.4,
            'matching_algorithm' => 'collaboration_potential',
            'rarity_boost' => true,
            'fee_discount' => 12,
            'creates_new_artwork' => true
        ),
        'seasonal_special' => array(
            'name' => 'Seasonal Special Swap',
            'description' => 'Limited-time swaps based on astrological seasons and events',
            'bonus_multiplier' => 1.6,
            'matching_algorithm' => 'seasonal_alignment',
            'rarity_boost' => true,
            'fee_discount' => 20,
            'time_limited' => true
        )
    );
    
    /**
     * 🏆 ARTIST REPUTATION SYSTEM
     * Trust and experience levels for marketplace participation
     */
    private $reputation_system = array(
        'novice' => array(
            'min_score' => 0,
            'max_score' => 100,
            'swap_limit_daily' => 2,
            'swap_limit_weekly' => 8,
            'escrow_required' => true,
            'fee_multiplier' => 1.0,
            'benefits' => array('basic_swapping', 'community_access')
        ),
        'apprentice' => array(
            'min_score' => 101,
            'max_score' => 300,
            'swap_limit_daily' => 3,
            'swap_limit_weekly' => 12,
            'escrow_required' => true,
            'fee_multiplier' => 0.9,
            'benefits' => array('zodiac_swapping', 'elemental_swapping', 'priority_matching')
        ),
        'artisan' => array(
            'min_score' => 301,
            'max_score' => 600,
            'swap_limit_daily' => 5,
            'swap_limit_weekly' => 20,
            'escrow_required' => false,
            'fee_multiplier' => 0.8,
            'benefits' => array('all_swap_types', 'reduced_fees', 'instant_swapping', 'marketplace_insights')
        ),
        'master' => array(
            'min_score' => 601,
            'max_score' => 1000,
            'swap_limit_daily' => 8,
            'swap_limit_weekly' => 30,
            'escrow_required' => false,
            'fee_multiplier' => 0.7,
            'benefits' => array('all_features', 'vip_support', 'exclusive_events', 'mentorship_opportunities')
        ),
        'grandmaster' => array(
            'min_score' => 1001,
            'max_score' => 9999,
            'swap_limit_daily' => 15,
            'swap_limit_weekly' => 50,
            'escrow_required' => false,
            'fee_multiplier' => 0.5,
            'benefits' => array('unlimited_features', 'marketplace_governance', 'revenue_sharing', 'exclusive_collections')
        )
    );
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - Initialize swapping marketplace
     */
    private function __construct() {
        $this->setup_marketplace_hooks();
        $this->initialize_swap_matching_engine();
        $this->setup_reputation_system();
        $this->setup_ajax_endpoints();
        $this->initialize_marketplace_ui();
        
        // Schedule background tasks
        $this->schedule_marketplace_tasks();
        
        $this->log_marketplace('💎 Artist Swapping Marketplace (Swapping Gem) initialized', 'info');
    }
    
    /**
     * 🎯 SETUP MARKETPLACE HOOKS
     */
    private function setup_marketplace_hooks() {
        // Core marketplace hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_marketplace_assets'));
        add_action('init', array($this, 'register_marketplace_post_types'));
        add_filter('query_vars', array($this, 'add_marketplace_query_vars'));
        add_action('parse_request', array($this, 'handle_marketplace_routes'));
        
        // Integration with image generation and contracts
        add_action('vortex_smart_contract_created', array($this, 'on_smart_contract_created'), 10, 2);
        add_action('vortex_image_enabled_for_swapping', array($this, 'add_to_marketplace'), 10, 2);
        
        // Reputation and achievement hooks
        add_action('vortex_swap_completed', array($this, 'update_artist_reputation'), 10, 2);
        add_action('vortex_successful_swap', array($this, 'award_swap_achievements'), 10, 2);
    }
    
    /**
     * 🎨 MARKETPLACE HOMEPAGE - DISPLAY SWAPPABLE ARTWORKS
     */
    public function display_marketplace_homepage() {
        $user_id = get_current_user_id();
        $user_reputation = $this->get_artist_reputation($user_id);
        
        // Get featured swappable artworks
        $featured_artworks = $this->get_featured_swappable_artworks();
        
        // Get personalized recommendations
        $recommended_swaps = $this->get_personalized_swap_recommendations($user_id);
        
        // Get user's own swappable artworks
        $my_artworks = $this->get_artist_swappable_artworks($user_id);
        
        ob_start();
        ?>
        <div class="vortex-swapping-gem-marketplace">
            <!-- Header Section -->
            <div class="marketplace-header">
                <h1>💎 VORTEX Swapping Gem Marketplace</h1>
                <p>Artist-to-Artist Image Trading with Smart Contract Security</p>
                
                <div class="user-stats">
                    <div class="reputation-badge">
                        <span class="reputation-level"><?php echo esc_html($user_reputation['level']); ?></span>
                        <span class="reputation-score"><?php echo esc_html($user_reputation['score']); ?> points</span>
                    </div>
                    <div class="swap-limits">
                        <span>Daily Swaps: <?php echo esc_html($user_reputation['swaps_today']); ?>/<?php echo esc_html($user_reputation['daily_limit']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Navigation Tabs -->
            <div class="marketplace-nav">
                <ul class="nav-tabs">
                    <li><a href="#featured" class="active">Featured Artworks</a></li>
                    <li><a href="#recommended">Recommended for You</a></li>
                    <li><a href="#my-artworks">My Artworks</a></li>
                    <li><a href="#swap-history">Swap History</a></li>
                    <li><a href="#create-swap">Create Swap</a></li>
                </ul>
            </div>
            
            <!-- Featured Artworks Section -->
            <div id="featured" class="marketplace-section active">
                <h2>🌟 Featured Artworks Available for Swapping</h2>
                <div class="artworks-grid">
                    <?php foreach ($featured_artworks as $artwork): ?>
                        <div class="artwork-card" data-artwork-id="<?php echo esc_attr($artwork['id']); ?>">
                            <div class="artwork-image">
                                <img src="<?php echo esc_url($artwork['image_url']); ?>" alt="<?php echo esc_attr($artwork['title']); ?>">
                                <div class="artwork-overlay">
                                    <div class="zodiac-sign"><?php echo esc_html($artwork['zodiac_sign']); ?></div>
                                    <div class="rarity-badge <?php echo esc_attr($artwork['rarity']); ?>"><?php echo esc_html($artwork['rarity']); ?></div>
                                </div>
                            </div>
                            
                            <div class="artwork-info">
                                <h3><?php echo esc_html($artwork['title']); ?></h3>
                                <p class="artist-name">by <?php echo esc_html($artwork['artist_name']); ?></p>
                                <p class="artwork-description"><?php echo esc_html($artwork['description']); ?></p>
                                
                                <div class="artwork-stats">
                                    <span class="creation-date"><?php echo esc_html($artwork['created_date']); ?></span>
                                    <span class="swap-count"><?php echo esc_html($artwork['swap_count']); ?> swaps</span>
                                </div>
                                
                                <div class="smart-contract-info">
                                    <span class="contract-badge">Smart Contract Secured</span>
                                    <a href="<?php echo esc_url($artwork['contract_explorer_url']); ?>" target="_blank" class="view-contract">View Contract</a>
                                </div>
                                
                                <div class="swap-actions">
                                    <button class="btn-propose-swap" data-artwork-id="<?php echo esc_attr($artwork['id']); ?>">
                                        💎 Propose Swap
                                    </button>
                                    <button class="btn-view-details" data-artwork-id="<?php echo esc_attr($artwork['id']); ?>">
                                        View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Recommended Swaps Section -->
            <div id="recommended" class="marketplace-section">
                <h2>🎯 Recommended Swaps for You</h2>
                <div class="recommendations-container">
                    <?php foreach ($recommended_swaps as $recommendation): ?>
                        <div class="recommendation-card">
                            <div class="recommendation-header">
                                <h3><?php echo esc_html($recommendation['category']); ?> Match</h3>
                                <div class="compatibility-score">
                                    <span><?php echo esc_html($recommendation['compatibility_percentage']); ?>% Compatible</span>
                                </div>
                            </div>
                            
                            <div class="swap-preview">
                                <div class="my-artwork">
                                    <img src="<?php echo esc_url($recommendation['my_artwork']['image_url']); ?>" alt="My Artwork">
                                    <p>Your: <?php echo esc_html($recommendation['my_artwork']['title']); ?></p>
                                </div>
                                
                                <div class="swap-arrow">⟷</div>
                                
                                <div class="target-artwork">
                                    <img src="<?php echo esc_url($recommendation['target_artwork']['image_url']); ?>" alt="Target Artwork">
                                    <p>Their: <?php echo esc_html($recommendation['target_artwork']['title']); ?></p>
                                </div>
                            </div>
                            
                            <div class="recommendation-details">
                                <p><strong>Why this match:</strong> <?php echo esc_html($recommendation['reason']); ?></p>
                                <p><strong>Potential bonus:</strong> <?php echo esc_html($recommendation['bonus_multiplier']); ?>x reputation</p>
                            </div>
                            
                            <button class="btn-start-recommended-swap" data-recommendation-id="<?php echo esc_attr($recommendation['id']); ?>">
                                Start This Swap
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- My Artworks Section -->
            <div id="my-artworks" class="marketplace-section">
                <h2>🎨 My Artworks Available for Swapping</h2>
                <div class="my-artworks-grid">
                    <?php foreach ($my_artworks as $artwork): ?>
                        <div class="my-artwork-card">
                            <img src="<?php echo esc_url($artwork['image_url']); ?>" alt="<?php echo esc_attr($artwork['title']); ?>">
                            <div class="artwork-info">
                                <h3><?php echo esc_html($artwork['title']); ?></h3>
                                <div class="artwork-stats">
                                    <span class="zodiac"><?php echo esc_html($artwork['zodiac_sign']); ?></span>
                                    <span class="rarity <?php echo esc_attr($artwork['rarity']); ?>"><?php echo esc_html($artwork['rarity']); ?></span>
                                    <span class="interest-count"><?php echo esc_html($artwork['interest_count']); ?> interested</span>
                                </div>
                                
                                <div class="swap-status">
                                    <?php if ($artwork['active_swaps'] > 0): ?>
                                        <span class="status active"><?php echo esc_html($artwork['active_swaps']); ?> active swap(s)</span>
                                    <?php else: ?>
                                        <span class="status available">Available for swapping</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="actions">
                                    <button class="btn-find-matches" data-artwork-id="<?php echo esc_attr($artwork['id']); ?>">
                                        Find Matches
                                    </button>
                                    <button class="btn-edit-listing" data-artwork-id="<?php echo esc_attr($artwork['id']); ?>">
                                        Edit Listing
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Swap Creation Modal -->
            <div id="swap-creation-modal" class="modal">
                <div class="modal-content">
                    <h2>💎 Create New Swap Proposal</h2>
                    
                    <form id="swap-creation-form">
                        <div class="form-section">
                            <label>Select Your Artwork:</label>
                            <select name="my_artwork_id" required>
                                <option value="">Choose artwork to trade...</option>
                                <?php foreach ($my_artworks as $artwork): ?>
                                    <option value="<?php echo esc_attr($artwork['id']); ?>">
                                        <?php echo esc_html($artwork['title']); ?> (<?php echo esc_html($artwork['zodiac_sign']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-section">
                            <label>Target Artwork ID:</label>
                            <input type="number" name="target_artwork_id" placeholder="Enter artwork ID you want" required>
                        </div>
                        
                        <div class="form-section">
                            <label>Swap Category:</label>
                            <select name="swap_category" required>
                                <?php foreach ($this->swap_categories as $category_id => $category): ?>
                                    <option value="<?php echo esc_attr($category_id); ?>">
                                        <?php echo esc_html($category['name']); ?> (<?php echo esc_html($category['bonus_multiplier']); ?>x bonus)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-section">
                            <label>Personal Message (Optional):</label>
                            <textarea name="swap_message" placeholder="Tell the artist why you'd like to swap..."></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-create-swap">Create Swap Proposal</button>
                            <button type="button" class="btn-cancel">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <style>
        .vortex-swapping-gem-marketplace {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .marketplace-header {
            text-align: center;
            margin-bottom: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 15px;
            color: white;
        }
        
        .marketplace-header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5em;
        }
        
        .user-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
        }
        
        .reputation-badge {
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 25px;
            backdrop-filter: blur(10px);
        }
        
        .artworks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .artwork-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .artwork-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        
        .artwork-image {
            position: relative;
            height: 200px;
            overflow: hidden;
        }
        
        .artwork-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .artwork-overlay {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 10px;
        }
        
        .zodiac-sign, .rarity-badge {
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
        }
        
        .rarity-badge.common { background: #95a5a6; }
        .rarity-badge.uncommon { background: #27ae60; }
        .rarity-badge.rare { background: #3498db; }
        .rarity-badge.epic { background: #9b59b6; }
        .rarity-badge.legendary { background: #f39c12; }
        
        .artwork-info {
            padding: 20px;
        }
        
        .swap-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-propose-swap, .btn-view-details {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-propose-swap {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-view-details {
            background: #ecf0f1;
            color: #2c3e50;
        }
        
        .btn-propose-swap:hover, .btn-view-details:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 80%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .form-section {
            margin-bottom: 20px;
        }
        
        .form-section label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .form-section input, .form-section select, .form-section textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .form-section textarea {
            height: 80px;
            resize: vertical;
        }
        
        .nav-tabs {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 20px 0;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .nav-tabs li {
            margin-right: 20px;
        }
        
        .nav-tabs a {
            display: block;
            padding: 10px 20px;
            text-decoration: none;
            color: #7f8c8d;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .nav-tabs a.active, .nav-tabs a:hover {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        .marketplace-section {
            display: none;
        }
        
        .marketplace-section.active {
            display: block;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('.nav-tabs a').click(function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                $('.nav-tabs a').removeClass('active');
                $(this).addClass('active');
                
                $('.marketplace-section').removeClass('active');
                $(target).addClass('active');
            });
            
            // Propose swap
            $('.btn-propose-swap').click(function() {
                var artworkId = $(this).data('artwork-id');
                $('#swap-creation-modal').show();
                $('#swap-creation-form input[name="target_artwork_id"]').val(artworkId);
            });
            
            // Create swap form submission
            $('#swap-creation-form').submit(function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'vortex_initiate_swap');
                formData.append('nonce', vortex_ajax.nonce);
                
                $.ajax({
                    url: vortex_ajax.ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Swap proposal created successfully!');
                            $('#swap-creation-modal').hide();
                            location.reload();
                        } else {
                            alert('❌ Error: ' + response.data);
                        }
                    }
                });
            });
            
            // Close modal
            $('.btn-cancel').click(function() {
                $('#swap-creation-modal').hide();
            });
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * 🎯 GET PERSONALIZED SWAP RECOMMENDATIONS
     * AI-powered matching based on zodiac, style, and preferences
     */
    private function get_personalized_swap_recommendations($user_id) {
        $user_artworks = $this->get_artist_swappable_artworks($user_id);
        $user_profile = $this->get_artist_profile($user_id);
        $recommendations = array();
        
        foreach ($user_artworks as $my_artwork) {
            // Find compatible artworks for each of user's pieces
            $compatible_artworks = $this->find_compatible_artworks($my_artwork, $user_profile);
            
            foreach ($compatible_artworks as $compatible_artwork) {
                $compatibility_score = $this->calculate_compatibility_score($my_artwork, $compatible_artwork);
                
                if ($compatibility_score >= 70) { // Only show high compatibility matches
                    $recommendations[] = array(
                        'id' => uniqid('rec_'),
                        'my_artwork' => $my_artwork,
                        'target_artwork' => $compatible_artwork,
                        'compatibility_percentage' => $compatibility_score,
                        'category' => $this->determine_match_category($my_artwork, $compatible_artwork),
                        'reason' => $this->generate_match_reason($my_artwork, $compatible_artwork),
                        'bonus_multiplier' => $this->calculate_bonus_multiplier($my_artwork, $compatible_artwork)
                    );
                }
            }
        }
        
        // Sort by compatibility score
        usort($recommendations, function($a, $b) {
            return $b['compatibility_percentage'] - $a['compatibility_percentage'];
        });
        
        return array_slice($recommendations, 0, 10); // Top 10 recommendations
    }
    
    /**
     * 🎨 ADD IMAGE TO MARKETPLACE
     * Called when an image is enabled for swapping
     */
    public function add_to_marketplace($image_id, $contract_address) {
        try {
            $image_data = get_post($image_id);
            $artist_id = $image_data->post_author;
            
            // Get zodiac and metadata
            $zodiac_data = get_post_meta($image_id, 'vortex_zodiac_data', true);
            $generation_metadata = get_post_meta($image_id, 'vortex_generation_metadata', true);
            
            // Determine rarity
            $rarity = $this->calculate_artwork_rarity($image_data, $zodiac_data, $generation_metadata);
            
            // Create marketplace listing
            $marketplace_data = array(
                'image_id' => $image_id,
                'artist_id' => $artist_id,
                'contract_address' => $contract_address,
                'title' => $image_data->post_title,
                'description' => $image_data->post_content,
                'zodiac_sign' => $zodiac_data['sign'] ?? 'unknown',
                'zodiac_element' => $zodiac_data['element'] ?? 'unknown',
                'rarity' => $rarity,
                'artistic_style' => $generation_metadata['style'] ?? 'unknown',
                'creation_date' => $image_data->post_date,
                'swap_enabled' => true,
                'featured' => ($rarity === 'legendary' || $rarity === 'epic'),
                'marketplace_added' => current_time('mysql')
            );
            
            // Store in marketplace database
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'vortex_marketplace_listings',
                $marketplace_data,
                array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s')
            );
            
            // Update search index
            $this->update_marketplace_search_index($image_id, $marketplace_data);
            
            $this->log_marketplace("💎 Image {$image_id} added to marketplace with contract {$contract_address}", 'info');
            
            // Trigger marketplace events
            do_action('vortex_marketplace_listing_added', $image_id, $marketplace_data);
            
        } catch (Exception $e) {
            $this->log_marketplace("❌ Failed to add image {$image_id} to marketplace: {$e->getMessage()}", 'error');
        }
    }
    
    /**
     * 💫 CALCULATE ARTWORK RARITY
     * Determine rarity based on zodiac, generation quality, and uniqueness
     */
    private function calculate_artwork_rarity($image_data, $zodiac_data, $generation_metadata) {
        $rarity_score = 0;
        
        // Base rarity from generation quality
        $quality_score = $generation_metadata['quality_score'] ?? 50;
        $rarity_score += $quality_score;
        
        // Zodiac rarity bonuses
        $zodiac_rarities = array(
            'aries' => 10, 'taurus' => 15, 'gemini' => 12, 'cancer' => 18,
            'leo' => 8, 'virgo' => 20, 'libra' => 14, 'scorpio' => 25,
            'sagittarius' => 11, 'capricorn' => 16, 'aquarius' => 22, 'pisces' => 19
        );
        
        $zodiac_sign = $zodiac_data['sign'] ?? 'unknown';
        $rarity_score += $zodiac_rarities[$zodiac_sign] ?? 10;
        
        // Uniqueness bonus
        $uniqueness_score = $generation_metadata['uniqueness_score'] ?? 50;
        $rarity_score += $uniqueness_score * 0.5;
        
        // Artistic complexity bonus
        $complexity = $generation_metadata['complexity'] ?? 'medium';
        $complexity_bonuses = array('simple' => 5, 'medium' => 10, 'complex' => 20, 'ultra' => 35);
        $rarity_score += $complexity_bonuses[$complexity] ?? 10;
        
        // Determine rarity tier
        if ($rarity_score >= 140) return 'legendary';
        if ($rarity_score >= 110) return 'epic';
        if ($rarity_score >= 80) return 'rare';
        if ($rarity_score >= 50) return 'uncommon';
        return 'common';
    }
    
    /**
     * 🎯 SETUP AJAX ENDPOINTS
     */
    private function setup_ajax_endpoints() {
        add_action('wp_ajax_vortex_get_marketplace_data', array($this, 'ajax_get_marketplace_data'));
        add_action('wp_ajax_vortex_search_artworks', array($this, 'ajax_search_artworks'));
        add_action('wp_ajax_vortex_get_swap_recommendations', array($this, 'ajax_get_swap_recommendations'));
        add_action('wp_ajax_vortex_update_listing_preferences', array($this, 'ajax_update_listing_preferences'));
        add_action('wp_ajax_vortex_get_artist_reputation', array($this, 'ajax_get_artist_reputation'));
    }
    
    // Helper methods and additional functionality
    
    private function get_featured_swappable_artworks($limit = 12) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT ml.*, p.post_title, p.post_content, u.display_name as artist_name
            FROM {$wpdb->prefix}vortex_marketplace_listings ml
            JOIN {$wpdb->posts} p ON ml.image_id = p.ID
            JOIN {$wpdb->users} u ON ml.artist_id = u.ID
            WHERE ml.swap_enabled = 1 
            AND (ml.featured = 1 OR ml.rarity IN ('epic', 'legendary'))
            ORDER BY ml.marketplace_added DESC
            LIMIT %d
        ", $limit), ARRAY_A);
        
        $featured_artworks = array();
        foreach ($results as $result) {
            $featured_artworks[] = array(
                'id' => $result['image_id'],
                'title' => $result['post_title'],
                'description' => $result['post_content'],
                'artist_name' => $result['artist_name'],
                'artist_id' => $result['artist_id'],
                'zodiac_sign' => $result['zodiac_sign'],
                'rarity' => $result['rarity'],
                'image_url' => wp_get_attachment_url($result['image_id']),
                'created_date' => date('M j, Y', strtotime($result['creation_date'])),
                'swap_count' => $this->get_artwork_swap_count($result['image_id']),
                'contract_explorer_url' => $this->get_contract_explorer_url($result['contract_address'])
            );
        }
        
        return $featured_artworks;
    }
    
    private function get_artist_reputation($artist_id) {
        $reputation_score = get_user_meta($artist_id, 'vortex_reputation_score', true) ?: 0;
        
        foreach ($this->reputation_system as $level => $config) {
            if ($reputation_score >= $config['min_score'] && $reputation_score <= $config['max_score']) {
                return array(
                    'score' => $reputation_score,
                    'level' => $level,
                    'daily_limit' => $config['swap_limit_daily'],
                    'weekly_limit' => $config['swap_limit_weekly'],
                    'swaps_today' => $this->get_artist_swaps_today($artist_id),
                    'benefits' => $config['benefits']
                );
            }
        }
        
        return array(
            'score' => $reputation_score,
            'level' => 'novice',
            'daily_limit' => 2,
            'weekly_limit' => 8,
            'swaps_today' => 0,
            'benefits' => array('basic_swapping')
        );
    }
    
    private function log_marketplace($message, $level = 'info') {
        error_log("[VORTEX_SWAPPING_MARKETPLACE] [{$level}] {$message}");
    }
    
    // Additional methods for marketplace functionality...
}

// Initialize Artist Swapping Marketplace
add_action('init', function() {
    if (get_option('vortex_swapping_marketplace_enabled', true)) {
        VORTEX_Artist_Swapping_Marketplace::get_instance();
    }
});

// Shortcode for marketplace display
add_shortcode('vortex_swapping_marketplace', function($atts) {
    $marketplace = VORTEX_Artist_Swapping_Marketplace::get_instance();
    return $marketplace->display_marketplace_homepage();
});

/**
 * 🔐 COPYRIGHT PROTECTION NOTICE
 * 
 * This swapping marketplace contains proprietary matching algorithms
 * and trading mechanisms. Unauthorized use is prohibited.
 * 
 * © 2024 VORTEX AI AGENTS - ALL RIGHTS RESERVED
 */ 