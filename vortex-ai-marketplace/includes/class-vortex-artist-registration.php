<?php
/**
 * Artist Registration and Assessment System
 *
 * @package VORTEX_AI_Marketplace
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vortex_Artist_Registration {
    
    private static $instance = null;
    private $assessment_table;
    private $education_tiers_table;
    
    /**
     * Educational Tiers Configuration
     */
    private $education_tiers = array(
        'artist_starter' => array(
            'name' => 'Artist Starter',
            'price' => 29,
            'currency' => 'USD',
            'workshop_hours' => 50,
            'membership_id' => 9205,
            'purchase_url' => '/wp-admin/admin-ajax.php?action=wcfm_choose_membership&membership=9205&method=by_url',
            'features' => array(
                'Basic AI generation access',
                'Community forum access',
                'Basic workshops',
                'Standard support'
            ),
            'tola_masterwork_access' => false,
            'role' => 'artist_starter'
        ),
        'artist_pro' => array(
            'name' => 'Artist Pro',
            'price' => 59,
            'currency' => 'USD',
            'workshop_hours' => 200,
            'membership_id' => 2305,
            'purchase_url' => '/wp-admin/admin-ajax.php?action=wcfm_choose_membership&membership=2305&method=by_url',
            'features' => array(
                'Advanced AI generation',
                'HORACE business quiz access',
                'Advanced workshops',
                'Priority support'
            ),
            'tola_masterwork_access' => false,
            'role' => 'artist_pro'
        ),
        'artist_studio' => array(
            'name' => 'Artist Studio',
            'price' => 99,
            'currency' => 'USD',
            'workshop_hours' => 'unlimited',
            'membership_id' => 2293,
            'purchase_url' => '/wp-admin/admin-ajax.php?action=wcfm_choose_membership&membership=2293&method=by_url',
            'features' => array(
                'Unlimited AI generation',
                'Full HORACE access',
                'All workshops',
                'VIP support',
                'Portfolio review'
            ),
            'tola_masterwork_access' => true,
            'role' => 'artist_studio'
        ),
        'part_time_student' => array(
            'name' => 'Part-time Student',
            'price' => 459,
            'currency' => 'USD',
            'workshop_hours' => 72,
            'membership_id' => 21813,
            'purchase_url' => '/wp-admin/admin-ajax.php?action=wcfm_choose_membership&membership=21813&method=by_url',
            'features' => array(
                'Basic workshops',
                'Pro Artist certification',
                'Standard support (4x30 min/month)',
                'Live discussions with mentor'
            ),
            'tola_masterwork_access' => false,
            'role' => 'artist_student'
        ),
        'full_time_student' => array(
            'name' => 'Full Time Student', 
            'price' => 729,
            'currency' => 'USD',
            'workshop_hours' => 120,
            'membership_id' => 21814,
            'purchase_url' => '/wp-admin/admin-ajax.php?action=wcfm_choose_membership&membership=21814&method=by_url',
            'features' => array(
                'Basic & advanced workshops',
                'Pro Artist certification',
                '1-on-1 support (4x45 min/month)',
                'Live discussions with mentor',
                'Participate in TOLA Masterworks'
            ),
            'tola_masterwork_access' => true,
            'role' => 'artist_pro'
        ),
        'masters' => array(
            'name' => 'Masters',
            'price' => 1089,
            'currency' => 'USD', 
            'workshop_hours' => 180,
            'membership_id' => 21815,
            'purchase_url' => '/wp-admin/admin-ajax.php?action=wcfm_choose_membership&membership=21815&method=by_url',
            'features' => array(
                'All workshops available',
                '1:1 Support (4x45 min/month)',
                'Master classes',
                'Pro Artist certification',
                'VIP mentorship',
                'Portfolio review',
                'TOLA Masterworks co-creation',
                'Masters Certification'
            ),
            'tola_masterwork_access' => true,
            'role' => 'artist_masters'
        )
    );
    
    /**
     * Workshop Bundle Options
     */
    private $workshop_bundles = array(
        'single_workshop' => array(
            'name' => 'Single Workshop',
            'price' => 29,
            'sessions' => 1,
            'membership_id' => 21818,
            'purchase_url' => '/wp-admin/admin-ajax.php?action=wcfm_choose_membership&membership=21818&method=by_url',
            'description' => '45-minute individual workshop session',
            'value_proposition' => 'Perfect for trying out our workshop system'
        ),
        'workshop_bundle_3' => array(
            'name' => 'Workshop Bundle (Buy 2 Get 1 Free)',
            'price' => 58,
            'sessions' => 3,
            'membership_id' => 21817,
            'purchase_url' => '/wp-admin/admin-ajax.php?action=wcfm_choose_membership&membership=21817&method=by_url',
            'description' => '3 workshop sessions for the price of 2',
            'value_proposition' => 'Save $29 with this popular bundle'
        ),
        'workshop_bundle_10' => array(
            'name' => 'Workshop Bundle (Buy 8 Get 10)',
            'price' => 232,
            'sessions' => 10,
            'membership_id' => 21816,
            'purchase_url' => '/wp-admin/admin-ajax.php?action=wcfm_choose_membership&membership=21816&method=by_url',
            'description' => '10 workshop sessions - pay for 8, get 2 free',
            'value_proposition' => 'Best value - save $58 on workshop sessions'
        )
    );

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->assessment_table = $wpdb->prefix . 'vortex_artist_assessments';
        $this->education_tiers_table = $wpdb->prefix . 'vortex_education_tiers';
        
        $this->init_hooks();
        $this->create_tables();
    }

    private function init_hooks() {
        // Registration hooks
        add_action('wp_ajax_vortex_submit_artist_assessment', array($this, 'handle_assessment_submission'));
        add_action('wp_ajax_nopriv_vortex_submit_artist_assessment', array($this, 'handle_assessment_submission'));
        
        // WooCommerce integration
        add_action('woocommerce_order_status_completed', array($this, 'handle_education_purchase'));
        add_action('woocommerce_payment_complete', array($this, 'handle_education_purchase'));
        
        // WCFM Membership integration
        add_action('wcfm_membership_activated', array($this, 'handle_wcfm_membership_activation'), 10, 2);
        add_action('wcfm_membership_cancelled', array($this, 'handle_wcfm_membership_cancellation'), 10, 2);
        
        // User role management
        add_action('vortex_education_tier_activated', array($this, 'assign_user_role'), 10, 2);
        
        // Shortcode registration
        add_shortcode('vortex_artist_registration', array($this, 'render_registration_form'));
        add_shortcode('vortex_education_tiers', array($this, 'render_education_tiers'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Artist assessments table
        $assessment_sql = "CREATE TABLE IF NOT EXISTS {$this->assessment_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            education_level varchar(50) NOT NULL,
            self_taught_years int DEFAULT NULL,
            total_years_creating int NOT NULL,
            primary_style varchar(50) NOT NULL,
            primary_style_other varchar(100) DEFAULT NULL,
            mediums longtext NOT NULL,
            mediums_other varchar(100) DEFAULT NULL,
            exhibition_count varchar(20) NOT NULL,
            exhibition_types longtext DEFAULT NULL,
            price_range varchar(30) NOT NULL,
            seed_commitment tinyint(1) DEFAULT 0,
            selected_tier varchar(30) NOT NULL,
            assessment_score int UNSIGNED DEFAULT 0,
            recommendations longtext DEFAULT NULL,
            status enum('pending','completed','approved','rejected') DEFAULT 'pending',
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_assessment (user_id),
            KEY education_level (education_level),
            KEY selected_tier (selected_tier),
            KEY status (status)
        ) $charset_collate;";
        
        // Education tier enrollments
        $tiers_sql = "CREATE TABLE IF NOT EXISTS {$this->education_tiers_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            tier_slug varchar(30) NOT NULL,
            tier_name varchar(100) NOT NULL,
            price decimal(10,2) NOT NULL,
            workshop_hours int NOT NULL,
            enrollment_date date NOT NULL,
            expiry_date date DEFAULT NULL,
            payment_status enum('pending','completed','failed','cancelled') DEFAULT 'pending',
            woocommerce_order_id bigint(20) UNSIGNED DEFAULT NULL,
            features longtext DEFAULT NULL,
            is_active tinyint(1) DEFAULT 0,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY tier_slug (tier_slug),
            KEY enrollment_date (enrollment_date),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($assessment_sql);
        dbDelta($tiers_sql);
    }

    /**
     * Render artist registration form
     */
    public function render_registration_form($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Artist Registration & Assessment',
            'subtitle' => 'Help us understand your artistic journey'
        ), $atts);
        
        ob_start();
        ?>
        <div class="vortex-artist-registration" id="vortex-artist-registration">
            <h2><?php echo esc_html($atts['title']); ?></h2>
            <p class="subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
            
            <form id="artist-assessment-form" class="vortex-form">
                <?php wp_nonce_field('vortex_artist_assessment', 'vortex_assessment_nonce'); ?>
                
                <!-- 1. Education & Experience -->
                <div class="form-section">
                    <h3>1. Education & Experience</h3>
                    
                    <div class="form-group">
                        <label>1.1 Highest Art Education (choose one)</label>
                        <div class="radio-group">
                            <label><input type="radio" name="education_level" value="high_school"> High School</label>
                            <label><input type="radio" name="education_level" value="bachelor"> Bachelor's Degree in Art</label>
                            <label><input type="radio" name="education_level" value="master"> Master's Degree in Art</label>
                            <label><input type="radio" name="education_level" value="doctorate"> Doctorate in Art</label>
                            <label><input type="radio" name="education_level" value="self_taught"> Self-taught</label>
                        </div>
                        
                        <div class="conditional-field" data-show-when="education_level=self_taught">
                            <label>If Self-taught, Years Practicing:</label>
                            <input type="number" name="self_taught_years" min="0" max="50" placeholder="Years">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>1.2 Total Years Creating Art:</label>
                        <input type="number" name="total_years_creating" min="0" max="80" required placeholder="Years">
                    </div>
                </div>

                <!-- 2. Style & Medium -->
                <div class="form-section">
                    <h3>2. Style & Medium</h3>
                    
                    <div class="form-group">
                        <label>2.1 Primary Artistic Style (choose one)</label>
                        <div class="radio-group">
                            <label><input type="radio" name="primary_style" value="abstract"> Abstract</label>
                            <label><input type="radio" name="primary_style" value="realistic"> Realistic</label>
                            <label><input type="radio" name="primary_style" value="impressionist"> Impressionist</label>
                            <label><input type="radio" name="primary_style" value="expressionist"> Expressionist</label>
                            <label><input type="radio" name="primary_style" value="surrealist"> Surrealist</label>
                            <label><input type="radio" name="primary_style" value="minimalist"> Minimalist</label>
                            <label><input type="radio" name="primary_style" value="pop_art"> Pop Art</label>
                            <label><input type="radio" name="primary_style" value="digital_art"> Digital Art</label>
                            <label><input type="radio" name="primary_style" value="conceptual"> Conceptual</label>
                            <label><input type="radio" name="primary_style" value="other"> Other</label>
                        </div>
                        
                        <div class="conditional-field" data-show-when="primary_style=other">
                            <input type="text" name="primary_style_other" placeholder="Please specify">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>2.2 Mediums Used (select all that apply)</label>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="mediums[]" value="oil_painting"> Oil Painting</label>
                            <label><input type="checkbox" name="mediums[]" value="acrylic"> Acrylic</label>
                            <label><input type="checkbox" name="mediums[]" value="watercolor"> Watercolor</label>
                            <label><input type="checkbox" name="mediums[]" value="photography"> Photography</label>
                            <label><input type="checkbox" name="mediums[]" value="digital_illustration"> Digital Illustration</label>
                            <label><input type="checkbox" name="mediums[]" value="mixed_media"> Mixed Media</label>
                            <label><input type="checkbox" name="mediums[]" value="sculpture"> Sculpture</label>
                            <label><input type="checkbox" name="mediums[]" value="installation"> Installation</label>
                            <label><input type="checkbox" name="mediums[]" value="vr_xr"> VR / XR</label>
                            <label><input type="checkbox" name="mediums[]" value="multidisciplinary"> Multidisciplinary</label>
                            <label><input type="checkbox" name="mediums[]" value="choreography"> Choreography</label>
                            <label><input type="checkbox" name="mediums[]" value="music"> Music</label>
                            <label><input type="checkbox" name="mediums[]" value="film"> Film</label>
                            <label><input type="checkbox" name="mediums[]" value="other"> Other</label>
                        </div>
                        
                        <div class="conditional-field" data-show-when="mediums=other">
                            <input type="text" name="mediums_other" placeholder="Please specify other mediums">
                        </div>
                    </div>
                </div>

                <!-- 3. Exhibitions & Professional History -->
                <div class="form-section">
                    <h3>3. Exhibitions & Professional History</h3>
                    
                    <div class="form-group">
                        <label>3.1 Number of Exhibitions</label>
                        <div class="radio-group">
                            <label><input type="radio" name="exhibition_count" value="none"> None</label>
                            <label><input type="radio" name="exhibition_count" value="1-5"> 1–5</label>
                            <label><input type="radio" name="exhibition_count" value="6-15"> 6–15</label>
                            <label><input type="radio" name="exhibition_count" value="16-30"> 16–30</label>
                            <label><input type="radio" name="exhibition_count" value="30+"> 30+</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>3.2 Professional Exhibitions (choose all that apply)</label>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="exhibition_types[]" value="solo_gallery"> Solo gallery shows</label>
                            <label><input type="checkbox" name="exhibition_types[]" value="group_exhibitions"> Group exhibitions</label>
                            <label><input type="checkbox" name="exhibition_types[]" value="curated_online"> Curated online platforms</label>
                            <label><input type="checkbox" name="exhibition_types[]" value="not_exhibited"> Not yet exhibited professionally</label>
                        </div>
                    </div>
                </div>

                <!-- 4. Market & Pricing -->
                <div class="form-section">
                    <h3>4. Market & Pricing</h3>
                    
                    <div class="form-group">
                        <label>4.1 Typical Price Range per Artwork</label>
                        <div class="radio-group">
                            <label><input type="radio" name="price_range" value="under_500"> Under $500</label>
                            <label><input type="radio" name="price_range" value="500-2000"> $500–$2,000</label>
                            <label><input type="radio" name="price_range" value="2000-15000"> $2,000–$15,000</label>
                            <label><input type="radio" name="price_range" value="15000-50000"> $15,000–$50,000</label>
                            <label><input type="radio" name="price_range" value="over_50000"> Over $50,000</label>
                            <label><input type="radio" name="price_range" value="na"> N/A - Not yet selling</label>
                        </div>
                    </div>
                </div>

                <!-- 5. Seed Art Commitment -->
                <div class="form-section">
                    <h3>5. Seed Art Commitment</h3>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="seed_commitment" value="1" required>
                            I agree to upload two hand-crafted "seed" artworks weekly.
                            <small>(Missing uploads may pause my artist privileges until I resume.)</small>
                        </label>
                    </div>
                </div>

                <!-- 6. Education Package Selection -->
                <div class="form-section">
                    <h3>6. Online Education Package Selection</h3>
                    <p>Choose one tier (Pricing per month - cancel anytime)</p>
                    
                    <div class="education-tiers">
                        <?php echo $this->render_education_tier_options(); ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Submit Assessment</button>
                    <div class="loading" style="display:none;">Processing...</div>
                </div>
            </form>
        </div>

        <style>
        .vortex-artist-registration { max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .form-section h3 { margin-top: 0; color: #333; }
        .form-group { margin-bottom: 20px; }
        .radio-group, .checkbox-group { display: flex; flex-direction: column; gap: 10px; }
        .radio-group label, .checkbox-group label { display: flex; align-items: center; gap: 8px; }
        .conditional-field { margin-top: 10px; display: none; }
        .conditional-field.show { display: block; }
        .education-tiers { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .tier-card { border: 2px solid #ddd; border-radius: 12px; padding: 20px; text-align: center; transition: all 0.3s ease; }
        .tier-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .tier-card.selected { border-color: #007cba; background: #f0f8ff; }
        .tier-price { font-size: 2em; font-weight: bold; color: #007cba; }
        .tier-features { list-style: none; padding: 0; margin: 15px 0; }
        .tier-features li { padding: 5px 0; }
        .masterwork-access { background: linear-gradient(135deg, #007cba, #005a87); color: white; padding: 8px 16px; border-radius: 20px; display: inline-block; margin: 10px 0; font-size: 0.9em; }
        
        .workshop-section { margin-top: 40px; padding-top: 30px; border-top: 2px solid #e0e0e0; }
        .workshop-section h4 { text-align: center; color: #007cba; margin-bottom: 20px; font-size: 1.3em; }
        .workshop-bundles { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .workshop-bundle { background: linear-gradient(145deg, #f8f9fa, #e9ecef); border: 2px solid #28a745; }
        .workshop-bundle:hover { border-color: #007cba; }
        .workshop-bundle.selected { border-color: #007cba; background: linear-gradient(145deg, #f0f8ff, #e3f2fd); }
        .bundle-description { margin: 15px 0; }
        .value-prop { background: #d4edda; color: #155724; padding: 8px 12px; border-radius: 6px; font-weight: bold; font-size: 0.9em; margin-top: 10px; }
        
        .btn { padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; }
        .btn-primary { background: #007cba; color: white; }
        .btn-primary:hover { background: #005a87; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Handle conditional fields
            $('input[name="education_level"]').change(function() {
                $('.conditional-field[data-show-when*="education_level"]').removeClass('show');
                if ($(this).val() === 'self_taught') {
                    $('.conditional-field[data-show-when="education_level=self_taught"]').addClass('show');
                }
            });
            
            $('input[name="primary_style"]').change(function() {
                $('.conditional-field[data-show-when*="primary_style"]').removeClass('show');
                if ($(this).val() === 'other') {
                    $('.conditional-field[data-show-when="primary_style=other"]').addClass('show');
                }
            });
            
            $('input[name="mediums[]"]').change(function() {
                var hasOther = $('input[name="mediums[]"]:checked').filter('[value="other"]').length > 0;
                $('.conditional-field[data-show-when="mediums=other"]').toggleClass('show', hasOther);
            });
            
            // Handle tier selection
            $('.tier-card').click(function() {
                $('.tier-card').removeClass('selected');
                $(this).addClass('selected');
                $(this).find('input[name="selected_tier"]').prop('checked', true);
                
                // Update button text based on selection type
                var isWorkshopBundle = $(this).hasClass('workshop-bundle');
                var tierName = $(this).find('h4').text();
                var submitButton = $('#artist-assessment-form button[type="submit"]');
                
                if (isWorkshopBundle) {
                    submitButton.text('Purchase ' + tierName);
                } else {
                    submitButton.text('Complete Assessment & Subscribe');
                }
            });
            
            // Form submission
            $('#artist-assessment-form').submit(function(e) {
                e.preventDefault();
                
                $('.loading').show();
                $('button[type="submit"]').prop('disabled', true);
                
                // Get selected tier purchase URL
                var selectedTier = $('.tier-card.selected').data('purchase-url');
                var formData = $(this).serialize() + '&action=vortex_submit_artist_assessment';
                
                if (selectedTier) {
                    formData += '&purchase_url=' + encodeURIComponent(selectedTier);
                }
                
                $.ajax({
                    url: vortexAjax.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            // Show success message and redirect to purchase URL
                            alert('Assessment completed! Redirecting to payment...');
                            window.location.href = response.data.redirect_url;
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    },
                    complete: function() {
                        $('.loading').hide();
                        $('button[type="submit"]').prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Render education tier options
     */
    private function render_education_tier_options() {
        ob_start();
        
        foreach ($this->education_tiers as $slug => $tier) {
            ?>
            <div class="tier-card" data-tier="<?php echo esc_attr($slug); ?>" data-purchase-url="<?php echo esc_attr($tier['purchase_url']); ?>">
                <input type="radio" name="selected_tier" value="<?php echo esc_attr($slug); ?>" style="display:none;">
                <h4><?php echo esc_html($tier['name']); ?></h4>
                <div class="tier-price">$<?php echo number_format($tier['price']); ?>/month</div>
                <p><strong><?php echo is_numeric($tier['workshop_hours']) ? $tier['workshop_hours'] : $tier['workshop_hours']; ?> workshop hours</strong></p>
                
                <ul class="tier-features">
                    <?php foreach ($tier['features'] as $feature): ?>
                        <li><?php echo esc_html($feature); ?></li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if ($tier['tola_masterwork_access']): ?>
                    <div class="masterwork-access">✨ <strong>TOLA Masterwork Access</strong></div>
                <?php endif; ?>
                
                <small>Auto-converts to TOLA in your wallet</small>
                <div class="membership-id" style="display:none;"><?php echo $tier['membership_id']; ?></div>
            </div>
            <?php
        }
        
        // Workshop bundle options
        echo '<div class="workshop-section"><h4>Workshop Bundles (Pay-as-you-go)</h4><div class="workshop-bundles">';
        foreach ($this->workshop_bundles as $bundle_slug => $bundle) {
            ?>
            <div class="tier-card workshop-bundle" data-tier="<?php echo esc_attr($bundle_slug); ?>" data-purchase-url="<?php echo esc_attr($bundle['purchase_url']); ?>">
                <input type="radio" name="selected_tier" value="<?php echo esc_attr($bundle_slug); ?>" style="display:none;">
                <h4><?php echo esc_html($bundle['name']); ?></h4>
                <div class="tier-price">$<?php echo number_format($bundle['price']); ?></div>
                <p><strong><?php echo $bundle['sessions']; ?> workshop session<?php echo $bundle['sessions'] > 1 ? 's' : ''; ?></strong></p>
                
                <div class="bundle-description">
                    <p><?php echo esc_html($bundle['description']); ?></p>
                    <div class="value-prop"><?php echo esc_html($bundle['value_proposition']); ?></div>
                </div>
                
                <small>45 minutes per workshop • Flexible scheduling</small>
                <div class="membership-id" style="display:none;"><?php echo $bundle['membership_id']; ?></div>
            </div>
            <?php
        }
        echo '</div></div>';
        ?>
        
        return ob_get_clean();
    }

    /**
     * Handle assessment form submission
     */
    public function handle_assessment_submission() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['vortex_assessment_nonce'], 'vortex_artist_assessment')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Security verification failed')));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_die(json_encode(array('success' => false, 'message' => 'You must be logged in to submit assessment')));
        }
        
        $user_id = get_current_user_id();
        
        // Collect and sanitize form data
        $assessment_data = array(
            'user_id' => $user_id,
            'education_level' => sanitize_text_field($_POST['education_level']),
            'self_taught_years' => isset($_POST['self_taught_years']) ? intval($_POST['self_taught_years']) : null,
            'total_years_creating' => intval($_POST['total_years_creating']),
            'primary_style' => sanitize_text_field($_POST['primary_style']),
            'primary_style_other' => isset($_POST['primary_style_other']) ? sanitize_text_field($_POST['primary_style_other']) : null,
            'mediums' => isset($_POST['mediums']) ? json_encode(array_map('sanitize_text_field', $_POST['mediums'])) : '',
            'mediums_other' => isset($_POST['mediums_other']) ? sanitize_text_field($_POST['mediums_other']) : null,
            'exhibition_count' => sanitize_text_field($_POST['exhibition_count']),
            'exhibition_types' => isset($_POST['exhibition_types']) ? json_encode(array_map('sanitize_text_field', $_POST['exhibition_types'])) : null,
            'price_range' => sanitize_text_field($_POST['price_range']),
            'seed_commitment' => isset($_POST['seed_commitment']) ? 1 : 0,
            'selected_tier' => sanitize_text_field($_POST['selected_tier']),
            'assessment_score' => $this->calculate_assessment_score($_POST),
            'status' => 'completed'
        );
        
        // Save assessment to database
        global $wpdb;
        $result = $wpdb->replace($this->assessment_table, $assessment_data);
        
        if ($result) {
            // Generate recommendations based on assessment
            $recommendations = $this->generate_recommendations($assessment_data);
            
            // Update user meta with assessment completion
            update_user_meta($user_id, 'vortex_assessment_completed', true);
            update_user_meta($user_id, 'vortex_assessment_date', current_time('mysql'));
            update_user_meta($user_id, 'vortex_selected_tier', $assessment_data['selected_tier']);
            
            // Use purchase URL from frontend or fallback to tier method
            $redirect_url = isset($_POST['purchase_url']) ? 
                           home_url(sanitize_text_field($_POST['purchase_url'])) : 
                           $this->get_tier_purchase_url($assessment_data['selected_tier']);
            
            wp_die(json_encode(array(
                'success' => true,
                'message' => 'Assessment completed successfully',
                'data' => array(
                    'recommendations' => $recommendations,
                    'redirect_url' => $redirect_url
                )
            )));
        } else {
            wp_die(json_encode(array('success' => false, 'message' => 'Failed to save assessment')));
        }
    }

    /**
     * Calculate assessment score based on responses
     */
    private function calculate_assessment_score($data) {
        $score = 0;
        
        // Education level scoring
        $education_scores = array(
            'doctorate' => 50,
            'master' => 40,
            'bachelor' => 30,
            'high_school' => 20,
            'self_taught' => 15
        );
        $score += isset($education_scores[$data['education_level']]) ? $education_scores[$data['education_level']] : 0;
        
        // Experience scoring
        $years = intval($data['total_years_creating']);
        if ($years >= 20) $score += 30;
        elseif ($years >= 10) $score += 25;
        elseif ($years >= 5) $score += 20;
        elseif ($years >= 2) $score += 15;
        else $score += 10;
        
        // Exhibition scoring
        $exhibition_scores = array(
            '30+' => 25,
            '16-30' => 20,
            '6-15' => 15,
            '1-5' => 10,
            'none' => 0
        );
        $score += isset($exhibition_scores[$data['exhibition_count']]) ? $exhibition_scores[$data['exhibition_count']] : 0;
        
        // Pricing scoring
        $price_scores = array(
            'over_50000' => 20,
            '15000-50000' => 18,
            '2000-15000' => 15,
            '500-2000' => 12,
            'under_500' => 8,
            'na' => 5
        );
        $score += isset($price_scores[$data['price_range']]) ? $price_scores[$data['price_range']] : 0;
        
        return min($score, 100); // Cap at 100
    }

    /**
     * Generate personalized recommendations
     */
    private function generate_recommendations($assessment_data) {
        $recommendations = array();
        $score = $assessment_data['assessment_score'];
        $selected_tier = $assessment_data['selected_tier'];
        
        // Score-based recommendations
        if ($score >= 80) {
            $recommendations[] = "Your experience level suggests you'd benefit most from our Masters tier.";
            $recommended_tier = 'masters';
        } elseif ($score >= 60) {
            $recommendations[] = "Your background indicates the Full Time Student tier would be ideal.";
            $recommended_tier = 'full_time_student';
        } else {
            $recommendations[] = "Starting with Part-time Student would provide a solid foundation.";
            $recommended_tier = 'part_time_student';
        }
        
        // Compare selected vs recommended
        if ($selected_tier !== $recommended_tier) {
            $recommendations[] = "Note: Based on your experience, we recommend considering the " . 
                                $this->education_tiers[$recommended_tier]['name'] . " tier.";
        }
        
        // TOLA Masterwork eligibility
        if (in_array($selected_tier, ['full_time_student', 'masters'])) {
            $recommendations[] = "Congratulations! Your selected tier includes TOLA Masterwork participation.";
        }
        
        return $recommendations;
    }

    /**
     * Get purchase URL for selected tier
     */
    private function get_tier_purchase_url($tier_slug) {
        if (isset($this->education_tiers[$tier_slug]['purchase_url'])) {
            return home_url($this->education_tiers[$tier_slug]['purchase_url']);
        }
        
        return home_url('/vortex-education-tiers/');
    }



    /**
     * Handle education tier purchase completion
     */
    public function handle_education_purchase($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            
            if (!$product) {
                continue;
            }
            
            $education_tier = $product->get_meta('_vortex_education_tier');
            
            if ($education_tier && isset($this->education_tiers[$education_tier])) {
                $this->process_tier_enrollment($order, $item, $education_tier);
            }
        }
    }

    /**
     * Handle WCFM membership activation
     */
    public function handle_wcfm_membership_activation($user_id, $membership_id) {
        // Map membership IDs to tier slugs (education tiers)
        $membership_tier_map = array();
        foreach ($this->education_tiers as $tier_slug => $tier_data) {
            if (isset($tier_data['membership_id'])) {
                $membership_tier_map[$tier_data['membership_id']] = array(
                    'slug' => $tier_slug,
                    'type' => 'education_tier'
                );
            }
        }
        
        // Map membership IDs to workshop bundles
        foreach ($this->workshop_bundles as $bundle_slug => $bundle_data) {
            if (isset($bundle_data['membership_id'])) {
                $membership_tier_map[$bundle_data['membership_id']] = array(
                    'slug' => $bundle_slug,
                    'type' => 'workshop_bundle'
                );
            }
        }
        
        if (isset($membership_tier_map[$membership_id])) {
            $mapping = $membership_tier_map[$membership_id];
            
            if ($mapping['type'] === 'education_tier') {
                $this->process_wcfm_tier_enrollment($user_id, $mapping['slug'], $membership_id);
            } elseif ($mapping['type'] === 'workshop_bundle') {
                $this->process_wcfm_workshop_purchase($user_id, $mapping['slug'], $membership_id);
            }
        }
    }

    /**
     * Handle WCFM membership cancellation
     */
    public function handle_wcfm_membership_cancellation($user_id, $membership_id) {
        // Map membership IDs to tier slugs (education tiers)
        $membership_tier_map = array();
        foreach ($this->education_tiers as $tier_slug => $tier_data) {
            if (isset($tier_data['membership_id'])) {
                $membership_tier_map[$tier_data['membership_id']] = array(
                    'slug' => $tier_slug,
                    'type' => 'education_tier'
                );
            }
        }
        
        // Map membership IDs to workshop bundles
        foreach ($this->workshop_bundles as $bundle_slug => $bundle_data) {
            if (isset($bundle_data['membership_id'])) {
                $membership_tier_map[$bundle_data['membership_id']] = array(
                    'slug' => $bundle_slug,
                    'type' => 'workshop_bundle'
                );
            }
        }
        
        if (isset($membership_tier_map[$membership_id])) {
            $mapping = $membership_tier_map[$membership_id];
            
            if ($mapping['type'] === 'education_tier') {
                $this->process_wcfm_tier_cancellation($user_id, $mapping['slug']);
            }
            // Workshop bundles are one-time purchases, no cancellation needed
        }
    }

    /**
     * Process WCFM tier enrollment
     */
    private function process_wcfm_tier_enrollment($user_id, $tier_slug, $membership_id) {
        global $wpdb;
        
        if (!isset($this->education_tiers[$tier_slug])) {
            return;
        }
        
        $tier = $this->education_tiers[$tier_slug];
        
        // Create enrollment record
        $enrollment_data = array(
            'user_id' => $user_id,
            'tier_slug' => $tier_slug,
            'tier_name' => $tier['name'],
            'price' => $tier['price'],
            'workshop_hours' => is_numeric($tier['workshop_hours']) ? $tier['workshop_hours'] : 0,
            'enrollment_date' => current_time('Y-m-d'),
            'expiry_date' => date('Y-m-d', strtotime('+1 month')),
            'payment_status' => 'completed',
            'woocommerce_order_id' => null, // WCFM doesn't use WC orders
            'features' => json_encode($tier['features']),
            'is_active' => 1
        );
        
        $wpdb->insert($this->education_tiers_table, $enrollment_data);
        
        // Assign user role
        $this->assign_user_role($user_id, $tier_slug);
        
        // Update user meta
        update_user_meta($user_id, 'vortex_education_tier', $tier_slug);
        update_user_meta($user_id, 'vortex_education_active', true);
        update_user_meta($user_id, 'vortex_workshop_hours_remaining', $tier['workshop_hours']);
        update_user_meta($user_id, 'vortex_wcfm_membership_id', $membership_id);
        
        // Credit TOLA tokens equivalent to USD paid
        if (class_exists('Vortex_AI_Marketplace_Wallet')) {
            $wallet = Vortex_AI_Marketplace_Wallet::get_instance();
            $wallet->credit_tokens($user_id, $tier['price'], 'Education tier purchase: ' . $tier['name']);
        }
        
        // Trigger role assignment action
        do_action('vortex_education_tier_activated', $user_id, $tier_slug);
        
        // Send welcome email
        $this->send_enrollment_email($user_id, $tier);
        
        error_log("VORTEX: Processed WCFM tier enrollment for user {$user_id}, tier: {$tier['name']}, membership: {$membership_id}");
    }

    /**
     * Process WCFM tier cancellation
     */
    private function process_wcfm_tier_cancellation($user_id, $tier_slug) {
        global $wpdb;
        
        // Deactivate enrollment
        $wpdb->update(
            $this->education_tiers_table,
            array('is_active' => 0),
            array('user_id' => $user_id, 'tier_slug' => $tier_slug),
            array('%d'),
            array('%d', '%s')
        );
        
        // Update user meta
        update_user_meta($user_id, 'vortex_education_active', false);
        delete_user_meta($user_id, 'vortex_wcfm_membership_id');
        
        // Remove TOLA Masterwork access if applicable
        if (isset($this->education_tiers[$tier_slug]) && 
            $this->education_tiers[$tier_slug]['tola_masterwork_access']) {
            delete_user_meta($user_id, 'vortex_tola_masterwork_access');
        }
        
        error_log("VORTEX: Processed WCFM tier cancellation for user {$user_id}, tier: {$tier_slug}");
    }

    /**
     * Process WCFM workshop bundle purchase
     */
    private function process_wcfm_workshop_purchase($user_id, $bundle_slug, $membership_id) {
        global $wpdb;
        
        if (!isset($this->workshop_bundles[$bundle_slug])) {
            return;
        }
        
        $bundle = $this->workshop_bundles[$bundle_slug];
        
        // Create workshop purchase record
        $workshop_table = $wpdb->prefix . 'vortex_workshop_purchases';
        $purchase_data = array(
            'user_id' => $user_id,
            'bundle_slug' => $bundle_slug,
            'bundle_name' => $bundle['name'],
            'price_paid' => $bundle['price'],
            'sessions_purchased' => $bundle['sessions'],
            'sessions_remaining' => $bundle['sessions'],
            'membership_id' => $membership_id,
            'purchase_date' => current_time('Y-m-d'),
            'status' => 'active'
        );
        
        // Create table if it doesn't exist
        $this->create_workshop_purchases_table();
        
        $wpdb->insert($workshop_table, $purchase_data);
        
        // Update user meta with workshop sessions
        $current_sessions = (int) get_user_meta($user_id, 'vortex_workshop_sessions_remaining', true);
        $new_sessions = $current_sessions + $bundle['sessions'];
        update_user_meta($user_id, 'vortex_workshop_sessions_remaining', $new_sessions);
        
        // Credit TOLA tokens equivalent to USD paid
        if (class_exists('Vortex_AI_Marketplace_Wallet')) {
            $wallet = Vortex_AI_Marketplace_Wallet::get_instance();
            $wallet->credit_tokens($user_id, $bundle['price'], 'Workshop bundle purchase: ' . $bundle['name']);
        }
        
        // Send purchase confirmation email
        $this->send_workshop_purchase_email($user_id, $bundle, $new_sessions);
        
        error_log("VORTEX: Processed workshop bundle purchase for user {$user_id}, bundle: {$bundle['name']}, sessions: {$bundle['sessions']}");
    }

    /**
     * Create workshop purchases table
     */
    private function create_workshop_purchases_table() {
        global $wpdb;
        
        $workshop_table = $wpdb->prefix . 'vortex_workshop_purchases';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$workshop_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            bundle_slug varchar(30) NOT NULL,
            bundle_name varchar(100) NOT NULL,
            price_paid decimal(10,2) NOT NULL,
            sessions_purchased int UNSIGNED NOT NULL,
            sessions_remaining int UNSIGNED NOT NULL,
            membership_id bigint(20) UNSIGNED NOT NULL,
            purchase_date date NOT NULL,
            status enum('active','completed','cancelled') DEFAULT 'active',
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY bundle_slug (bundle_slug),
            KEY purchase_date (purchase_date),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Send workshop purchase confirmation email
     */
    private function send_workshop_purchase_email($user_id, $bundle, $total_sessions) {
        $user = get_user_by('ID', $user_id);
        
        if (!$user) {
            return;
        }
        
        $subject = 'Workshop Bundle Purchase Confirmed - ' . $bundle['name'];
        
        $message = "
        <h2>Workshop Bundle Purchase Confirmation</h2>
        <p>Dear {$user->display_name},</p>
        
        <p>Thank you for purchasing the <strong>{$bundle['name']}</strong>!</p>
        
        <h3>📚 Your Purchase Details:</h3>
        <ul>
            <li><strong>Bundle:</strong> {$bundle['name']}</li>
            <li><strong>Sessions Included:</strong> {$bundle['sessions']} workshop sessions</li>
            <li><strong>Amount Paid:</strong> \${$bundle['price']}</li>
            <li><strong>TOLA Credited:</strong> {$bundle['price']} TOLA tokens</li>
        </ul>
        
        <h3>🎯 What's Next:</h3>
        <ul>
            <li><strong>Total Available Sessions:</strong> {$total_sessions} workshops</li>
            <li><strong>Session Duration:</strong> 45 minutes each</li>
            <li><strong>Scheduling:</strong> Book sessions through your dashboard</li>
            <li><strong>Flexible Access:</strong> Use sessions at your own pace</li>
        </ul>
        
        <div style='background: #f0f8ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>
            <h4>💡 Workshop Benefits:</h4>
            <p>{$bundle['description']}</p>
            <p><em>{$bundle['value_proposition']}</em></p>
        </div>
        
        <p><strong>Ready to start learning?</strong></p>
        <p><a href='" . home_url('/dashboard/workshops/') . "' style='background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;'>Access Your Workshops</a></p>
        
        <p>Your TOLA wallet has been credited with {$bundle['price']} tokens equivalent to your purchase amount.</p>
        
        <p>Best regards,<br>The VORTEX ARTEC Team</p>
        ";
        
        wp_mail($user->user_email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
    }

    /**
     * Process tier enrollment
     */
    private function process_tier_enrollment($order, $item, $tier_slug) {
        global $wpdb;
        
        $user_id = $order->get_user_id();
        $tier = $this->education_tiers[$tier_slug];
        
        // Create enrollment record
        $enrollment_data = array(
            'user_id' => $user_id,
            'tier_slug' => $tier_slug,
            'tier_name' => $tier['name'],
            'price' => $tier['price'],
            'workshop_hours' => $tier['workshop_hours'],
            'enrollment_date' => current_time('Y-m-d'),
            'expiry_date' => date('Y-m-d', strtotime('+1 month')),
            'payment_status' => 'completed',
            'woocommerce_order_id' => $order->get_id(),
            'features' => json_encode($tier['features']),
            'is_active' => 1
        );
        
        $wpdb->insert($this->education_tiers_table, $enrollment_data);
        
        // Assign user role
        $this->assign_user_role($user_id, $tier_slug);
        
        // Update user meta
        update_user_meta($user_id, 'vortex_education_tier', $tier_slug);
        update_user_meta($user_id, 'vortex_education_active', true);
        update_user_meta($user_id, 'vortex_workshop_hours_remaining', $tier['workshop_hours']);
        
        // Credit TOLA tokens equivalent to USD paid
        $wallet = Vortex_AI_Marketplace_Wallet::get_instance();
        $wallet->credit_tokens($user_id, $tier['price'], 'Education tier purchase: ' . $tier['name']);
        
        // Trigger role assignment action
        do_action('vortex_education_tier_activated', $user_id, $tier_slug);
        
        // Send welcome email
        $this->send_enrollment_email($user_id, $tier);
        
        error_log("VORTEX: Processed tier enrollment for user {$user_id}, tier: {$tier['name']}");
    }

    /**
     * Assign user role based on tier
     */
    public function assign_user_role($user_id, $tier_slug) {
        $user = get_user_by('ID', $user_id);
        
        if (!$user || !isset($this->education_tiers[$tier_slug])) {
            return false;
        }
        
        $new_role = $this->education_tiers[$tier_slug]['role'];
        
        // Remove existing artist roles
        $user->remove_role('artist_student');
        $user->remove_role('artist_pro');
        $user->remove_role('artist_masters');
        
        // Add new role
        $user->add_role($new_role);
        
        // Special permissions for Masters tier
        if ($tier_slug === 'masters') {
            update_user_meta($user_id, 'vortex_masters_certification', true);
            update_user_meta($user_id, 'vortex_vip_mentorship', true);
        }
        
        // TOLA Masterwork access
        if ($this->education_tiers[$tier_slug]['tola_masterwork_access']) {
            update_user_meta($user_id, 'vortex_tola_masterwork_access', true);
            
            // Add to participating artists for future masterworks
            $automation = Vortex_TOLA_Art_Daily_Automation::get_instance();
            $automation->add_artist_to_participation($user_id);
        }
        
        return true;
    }

    /**
     * Send enrollment welcome email
     */
    private function send_enrollment_email($user_id, $tier) {
        $user = get_user_by('ID', $user_id);
        
        if (!$user) {
            return;
        }
        
        $subject = 'Welcome to VORTEX ' . $tier['name'] . ' Education!';
        
        $message = "
        <h2>Welcome to VORTEX Education!</h2>
        <p>Dear {$user->display_name},</p>
        
        <p>Congratulations on enrolling in our <strong>{$tier['name']}</strong> education tier!</p>
        
        <h3>Your Tier Benefits:</h3>
        <ul>";
        
        foreach ($tier['features'] as $feature) {
            $message .= "<li>{$feature}</li>";
        }
        
        $message .= "</ul>
        
        <p><strong>Next Steps:</strong></p>
        <ol>
            <li>Log into your dashboard to access your first workshops</li>
            <li>Upload your first seed artworks to complete your profile</li>
            <li>Schedule your mentor sessions</li>";
        
        if ($tier['tola_masterwork_access']) {
            $message .= "<li>Join the TOLA Masterwork community for collaborative creation</li>";
        }
        
        $message .= "</ol>
        
        <p>Your TOLA wallet has been credited with {$tier['price']} TOLA tokens equivalent to your USD payment.</p>
        
        <p>Ready to begin your artistic journey? <a href='" . home_url('/dashboard/') . "'>Access Your Dashboard</a></p>
        
        <p>Best regards,<br>The VORTEX ARTEC Team</p>
        ";
        
        wp_mail($user->user_email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
    }

    /**
     * Get tier product ID
     */
    private function get_tier_product_id($tier_slug) {
        return wc_get_product_id_by_sku('vortex_education_' . $tier_slug);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'vortex-ai',
            'Artist Assessments',
            'Artist Assessments',
            'manage_options',
            'vortex-assessments',
            array($this, 'admin_page')
        );
    }

    /**
     * Admin page for viewing assessments
     */
    public function admin_page() {
        global $wpdb;
        
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'assessments';
        
        echo '<div class="wrap">';
        echo '<h1>Artist Registration Management</h1>';
        
        // Tab navigation
        echo '<h2 class="nav-tab-wrapper">';
        echo '<a href="?page=vortex-assessments&tab=assessments" class="nav-tab ' . ($active_tab == 'assessments' ? 'nav-tab-active' : '') . '">Assessments</a>';
        echo '<a href="?page=vortex-assessments&tab=enrollments" class="nav-tab ' . ($active_tab == 'enrollments' ? 'nav-tab-active' : '') . '">Tier Enrollments</a>';
        echo '<a href="?page=vortex-assessments&tab=workshops" class="nav-tab ' . ($active_tab == 'workshops' ? 'nav-tab-active' : '') . '">Workshop Purchases</a>';
        echo '</h2>';
        
        switch ($active_tab) {
            case 'enrollments':
                $this->display_enrollments_tab();
                break;
            case 'workshops':
                $this->display_workshops_tab();
                break;
            default:
                $this->display_assessments_tab();
                break;
        }
        
        echo '</div>';
    }
    
    /**
     * Display assessments tab
     */
    private function display_assessments_tab() {
        global $wpdb;
        
        $assessments = $wpdb->get_results("
            SELECT a.*, u.display_name, u.user_email 
            FROM {$this->assessment_table} a 
            JOIN {$wpdb->users} u ON a.user_id = u.ID 
            ORDER BY a.created_at DESC 
            LIMIT 100
        ");
        
        echo '<h2>Artist Assessments</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Artist</th><th>Selected Tier</th><th>Score</th><th>Status</th><th>Date</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($assessments as $assessment) {
            $tier_name = isset($this->education_tiers[$assessment->selected_tier]) ? 
                        $this->education_tiers[$assessment->selected_tier]['name'] : 
                        (isset($this->workshop_bundles[$assessment->selected_tier]) ? 
                         $this->workshop_bundles[$assessment->selected_tier]['name'] : 
                         $assessment->selected_tier);
            
            echo '<tr>';
            echo '<td>' . esc_html($assessment->display_name) . '<br><small>' . esc_html($assessment->user_email) . '</small></td>';
            echo '<td>' . esc_html($tier_name) . '</td>';
            echo '<td>' . $assessment->assessment_score . '/100</td>';
            echo '<td>' . ucfirst($assessment->status) . '</td>';
            echo '<td>' . date('M j, Y', strtotime($assessment->created_at)) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * Display enrollments tab
     */
    private function display_enrollments_tab() {
        global $wpdb;
        
        $enrollments = $wpdb->get_results("
            SELECT e.*, u.display_name, u.user_email 
            FROM {$this->education_tiers_table} e 
            JOIN {$wpdb->users} u ON e.user_id = u.ID 
            ORDER BY e.created_at DESC 
            LIMIT 100
        ");
        
        echo '<h2>Education Tier Enrollments</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Artist</th><th>Tier</th><th>Price</th><th>Status</th><th>Enrolled</th><th>Expires</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($enrollments as $enrollment) {
            $status_color = $enrollment->is_active ? 'green' : 'red';
            $status_text = $enrollment->is_active ? 'Active' : 'Inactive';
            
            echo '<tr>';
            echo '<td>' . esc_html($enrollment->display_name) . '<br><small>' . esc_html($enrollment->user_email) . '</small></td>';
            echo '<td>' . esc_html($enrollment->tier_name) . '</td>';
            echo '<td>$' . number_format($enrollment->price, 2) . '</td>';
            echo '<td><span style="color: ' . $status_color . ';">' . $status_text . '</span></td>';
            echo '<td>' . date('M j, Y', strtotime($enrollment->enrollment_date)) . '</td>';
            echo '<td>' . ($enrollment->expiry_date ? date('M j, Y', strtotime($enrollment->expiry_date)) : 'N/A') . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * Display workshops tab
     */
    private function display_workshops_tab() {
        global $wpdb;
        
        $workshop_table = $wpdb->prefix . 'vortex_workshop_purchases';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$workshop_table}'") == $workshop_table;
        
        if (!$table_exists) {
            echo '<h2>Workshop Purchases</h2>';
            echo '<p>No workshop purchases found. The workshop purchases table will be created when the first purchase is made.</p>';
            return;
        }
        
        $purchases = $wpdb->get_results("
            SELECT w.*, u.display_name, u.user_email 
            FROM {$workshop_table} w 
            JOIN {$wpdb->users} u ON w.user_id = u.ID 
            ORDER BY w.created_at DESC 
            LIMIT 100
        ");
        
        echo '<h2>Workshop Bundle Purchases</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Artist</th><th>Bundle</th><th>Price</th><th>Sessions</th><th>Remaining</th><th>Status</th><th>Purchase Date</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($purchases as $purchase) {
            $status_color = $purchase->status == 'active' ? 'green' : ($purchase->status == 'completed' ? 'blue' : 'red');
            
            echo '<tr>';
            echo '<td>' . esc_html($purchase->display_name) . '<br><small>' . esc_html($purchase->user_email) . '</small></td>';
            echo '<td>' . esc_html($purchase->bundle_name) . '</td>';
            echo '<td>$' . number_format($purchase->price_paid, 2) . '</td>';
            echo '<td>' . $purchase->sessions_purchased . '</td>';
            echo '<td>' . $purchase->sessions_remaining . '</td>';
            echo '<td><span style="color: ' . $status_color . ';">' . ucfirst($purchase->status) . '</span></td>';
            echo '<td>' . date('M j, Y', strtotime($purchase->purchase_date)) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
} 