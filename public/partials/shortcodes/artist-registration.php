<?php
/**
 * Template for artist registration shortcode
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Default shortcode attributes
$atts = isset($atts) ? $atts : array(
    'redirect' => '',
    'class' => ''
);

// Check if user is already logged in
if (is_user_logged_in()) {
    $user = wp_get_current_user();
    $is_artist = in_array('vortex_artist', (array) $user->roles);
    
    if ($is_artist) {
        ?>
        <div class="vortex-already-registered">
            <p><?php printf(esc_html__('You are already registered as an artist: %s.', 'vortex-ai-marketplace'), esc_html($user->display_name)); ?></p>
            
            <div class="vortex-user-actions">
                <a href="<?php echo esc_url(home_url('/artist-dashboard/')); ?>" class="vortex-button">
                    <?php esc_html_e('Go to Artist Dashboard', 'vortex-ai-marketplace'); ?>
                </a>
                
                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="vortex-button vortex-button-secondary">
                    <?php esc_html_e('Log Out', 'vortex-ai-marketplace'); ?>
                </a>
            </div>
        </div>
        <?php
    } else {
        // User is logged in but not an artist, show form to upgrade to artist
        ?>
        <div class="vortex-artist-upgrade">
            <h2><?php esc_html_e('Become an Artist', 'vortex-ai-marketplace'); ?></h2>
            <p><?php printf(esc_html__('You are currently logged in as %s. Complete the form below to register as an artist.', 'vortex-ai-marketplace'), esc_html($user->display_name)); ?></p>
            <?php
            // Process artist registration form submission for existing user
            artist_upgrade_form($user, $atts);
            ?>
        </div>
        <?php
    }
    return;
}

// Process registration form submission
$registration_error = '';
$registration_success = false;

if (isset($_POST['vortex_register_artist']) && isset($_POST['vortex_artist_registration_nonce']) && 
    wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vortex_artist_registration_nonce'])), 'vortex_artist_registration')) {
    
    $username = isset($_POST['vortex_username']) ? sanitize_user(wp_unslash($_POST['vortex_username'])) : '';
    $email = isset($_POST['vortex_email']) ? sanitize_email(wp_unslash($_POST['vortex_email'])) : '';
    $password = isset($_POST['vortex_password']) ? $_POST['vortex_password'] : '';
    $confirm_password = isset($_POST['vortex_confirm_password']) ? $_POST['vortex_confirm_password'] : '';
    $terms = isset($_POST['vortex_terms']) ? $_POST['vortex_terms'] : '';
    
    // Artist specific fields
    $bio = isset($_POST['vortex_artist_bio']) ? sanitize_textarea_field(wp_unslash($_POST['vortex_artist_bio'])) : '';
    $specialties = isset($_POST['vortex_artist_specialties']) ? sanitize_text_field(wp_unslash($_POST['vortex_artist_specialties'])) : '';
    $wallet_address = isset($_POST['vortex_artist_wallet_address']) ? sanitize_text_field(wp_unslash($_POST['vortex_artist_wallet_address'])) : '';
    $website = isset($_POST['vortex_artist_website']) ? esc_url_raw(wp_unslash($_POST['vortex_artist_website'])) : '';
    
    // Artist quiz data
    $education = isset($_POST['vortex_artist_education']) ? sanitize_text_field(wp_unslash($_POST['vortex_artist_education'])) : '';
    $self_taught_years = isset($_POST['vortex_artist_self_taught_years']) ? intval($_POST['vortex_artist_self_taught_years']) : 0;
    $artistic_style = isset($_POST['vortex_artist_style']) ? sanitize_text_field(wp_unslash($_POST['vortex_artist_style'])) : '';
    $exhibitions = isset($_POST['vortex_artist_exhibitions']) ? sanitize_text_field(wp_unslash($_POST['vortex_artist_exhibitions'])) : '';
    $price_range = isset($_POST['vortex_artist_price_range']) ? sanitize_text_field(wp_unslash($_POST['vortex_artist_price_range'])) : '';
    $seed_art_commitment = isset($_POST['vortex_artist_seed_art_commitment']) ? 1 : 0;
    
    // Education package
    $education_package = isset($_POST['vortex_education_package']) ? sanitize_text_field(wp_unslash($_POST['vortex_education_package'])) : 'standard';
    
    // Social media links
    $social_media = array(
        'twitter' => isset($_POST['vortex_artist_social_media']['twitter']) ? esc_url_raw(wp_unslash($_POST['vortex_artist_social_media']['twitter'])) : '',
        'instagram' => isset($_POST['vortex_artist_social_media']['instagram']) ? esc_url_raw(wp_unslash($_POST['vortex_artist_social_media']['instagram'])) : '',
        'facebook' => isset($_POST['vortex_artist_social_media']['facebook']) ? esc_url_raw(wp_unslash($_POST['vortex_artist_social_media']['facebook'])) : '',
        'deviantart' => isset($_POST['vortex_artist_social_media']['deviantart']) ? esc_url_raw(wp_unslash($_POST['vortex_artist_social_media']['deviantart'])) : '',
        'behance' => isset($_POST['vortex_artist_social_media']['behance']) ? esc_url_raw(wp_unslash($_POST['vortex_artist_social_media']['behance'])) : '',
    );
    
    // Validate inputs
    if (empty($username)) {
        $registration_error = __('Username is required.', 'vortex-ai-marketplace');
    } elseif (empty($email)) {
        $registration_error = __('Email address is required.', 'vortex-ai-marketplace');
    } elseif (!is_email($email)) {
        $registration_error = __('Invalid email address.', 'vortex-ai-marketplace');
    } elseif (empty($password)) {
        $registration_error = __('Password is required.', 'vortex-ai-marketplace');
    } elseif ($password !== $confirm_password) {
        $registration_error = __('Passwords do not match.', 'vortex-ai-marketplace');
    } elseif (empty($terms)) {
        $registration_error = __('You must agree to the terms and conditions.', 'vortex-ai-marketplace');
    } elseif (empty($specialties)) {
        $registration_error = __('Please enter your artistic specialties.', 'vortex-ai-marketplace');
    } elseif (empty($seed_art_commitment)) {
        $registration_error = __('You must agree to the seed artwork upload commitment to register as an artist.', 'vortex-ai-marketplace');
    } else {
        // Create user
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            $registration_error = $user_id->get_error_message();
        } else {
            // Set user role to artist
            $user = new WP_User($user_id);
            $user->set_role('vortex_artist');
            
            // Save artist profile data
            update_user_meta($user_id, '_vortex_artist_bio', $bio);
            update_user_meta($user_id, '_vortex_artist_specialties', $specialties);
            update_user_meta($user_id, '_vortex_artist_wallet_address', $wallet_address);
            update_user_meta($user_id, '_vortex_artist_website', $website);
            update_user_meta($user_id, '_vortex_artist_social_media', $social_media);
            update_user_meta($user_id, 'vortex_register_as_artist', true);
            
            // Save artist quiz data
            update_user_meta($user_id, '_vortex_artist_education', $education);
            if ($education === 'self_taught' && !empty($self_taught_years)) {
                update_user_meta($user_id, '_vortex_artist_self_taught_years', $self_taught_years);
            }
            update_user_meta($user_id, '_vortex_artist_style', $artistic_style);
            update_user_meta($user_id, '_vortex_artist_exhibitions', $exhibitions);
            update_user_meta($user_id, '_vortex_artist_price_range', $price_range);
            update_user_meta($user_id, '_vortex_artist_seed_commitment', $seed_art_commitment);
            
            // Set initial seed art status
            update_user_meta($user_id, '_vortex_artist_seed_status', 'active');
            update_user_meta($user_id, '_vortex_artist_last_seed_upload', current_time('mysql'));
            update_user_meta($user_id, '_vortex_artist_seed_uploads_due', 2);
            
            // Save education package
            update_user_meta($user_id, 'vortex_education_package', $education_package);
            update_user_meta($user_id, 'vortex_workshop_hours_used', 0);
            
            // Set a flag that we need to create an artist profile post
            update_user_meta($user_id, '_vortex_create_artist_profile', true);
            
            // Auto login
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
            
            // Create artist profile
            if (class_exists('Vortex_Artists')) {
                $artists = new Vortex_Artists(VORTEX_PLUGIN_NAME, VORTEX_VERSION);
                $profile_id = $artists->create_or_update_artist_profile($user_id);
                
                if ($profile_id) {
                    // Update the profile with additional details
                    if (!empty($bio)) {
                        wp_update_post(array(
                            'ID' => $profile_id,
                            'post_content' => $bio
                        ));
                    }
                    
                    if (!empty($specialties)) {
                        update_post_meta($profile_id, '_vortex_artist_specialties', $specialties);
                    }
                    
                    if (!empty($wallet_address)) {
                        update_post_meta($profile_id, '_vortex_artist_wallet_address', $wallet_address);
                    }
                    
                    // Set social media links
                    update_post_meta($profile_id, '_vortex_artist_social_links', $social_media);
                }
            }
            
            $registration_success = true;
            
            // Redirect after registration if specified
            if (!empty($atts['redirect'])) {
                wp_redirect(esc_url($atts['redirect']));
                exit;
            }
        }
    }
}

/**
 * Display the form for upgrading an existing user to an artist
 *
 * @param WP_User $user The current user object
 * @param array $atts Shortcode attributes
 */
function artist_upgrade_form($user, $atts = array()) {
    // Process form submission for upgrade
    $upgrade_error = '';
    $upgrade_success = false;
    
    if (isset($_POST['vortex_upgrade_artist']) && isset($_POST['vortex_artist_upgrade_nonce']) && 
        wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vortex_artist_upgrade_nonce'])), 'vortex_artist_upgrade')) {
        
        // Artist specific fields
        $bio = isset($_POST['vortex_artist_bio']) ? sanitize_textarea_field(wp_unslash($_POST['vortex_artist_bio'])) : '';
        $specialties = isset($_POST['vortex_artist_specialties']) ? sanitize_text_field(wp_unslash($_POST['vortex_artist_specialties'])) : '';
        $wallet_address = isset($_POST['vortex_artist_wallet_address']) ? sanitize_text_field(wp_unslash($_POST['vortex_artist_wallet_address'])) : '';
        $website = isset($_POST['vortex_artist_website']) ? esc_url_raw(wp_unslash($_POST['vortex_artist_website'])) : '';
        
        // Artist quiz data
        $education = isset($_POST['vortex_artist_education']) ? sanitize_text_field(wp_unslash($_POST['vortex_artist_education'])) : '';
        $self_taught_years = isset($_POST['vortex_artist_self_taught_years']) ? intval($_POST['vortex_artist_self_taught_years']) : 0;
        $artistic_style = isset($_POST['vortex_artist_style']) ? sanitize_text_field(wp_unslash($_POST['vortex_artist_style'])) : '';
        $exhibitions = isset($_POST['vortex_artist_exhibitions']) ? sanitize_text_field(wp_unslash($_POST['vortex_artist_exhibitions'])) : '';
        $price_range = isset($_POST['vortex_artist_price_range']) ? sanitize_text_field(wp_unslash($_POST['vortex_artist_price_range'])) : '';
        $seed_art_commitment = isset($_POST['vortex_artist_seed_art_commitment']) ? 1 : 0;
        
        // Education package
        $education_package = isset($_POST['vortex_education_package']) ? sanitize_text_field(wp_unslash($_POST['vortex_education_package'])) : 'standard';
        
        // Social media links
        $social_media = array(
            'twitter' => isset($_POST['vortex_artist_social_media']['twitter']) ? esc_url_raw(wp_unslash($_POST['vortex_artist_social_media']['twitter'])) : '',
            'instagram' => isset($_POST['vortex_artist_social_media']['instagram']) ? esc_url_raw(wp_unslash($_POST['vortex_artist_social_media']['instagram'])) : '',
            'facebook' => isset($_POST['vortex_artist_social_media']['facebook']) ? esc_url_raw(wp_unslash($_POST['vortex_artist_social_media']['facebook'])) : '',
            'deviantart' => isset($_POST['vortex_artist_social_media']['deviantart']) ? esc_url_raw(wp_unslash($_POST['vortex_artist_social_media']['deviantart'])) : '',
            'behance' => isset($_POST['vortex_artist_social_media']['behance']) ? esc_url_raw(wp_unslash($_POST['vortex_artist_social_media']['behance'])) : '',
        );
        
        // Validate inputs
        if (empty($specialties)) {
            $upgrade_error = __('Please enter your artistic specialties.', 'vortex-ai-marketplace');
        } elseif (empty($seed_art_commitment)) {
            $upgrade_error = __('You must agree to the seed artwork upload commitment to become an artist.', 'vortex-ai-marketplace');
        } else {
            // Update user role to artist
            $user->set_role('vortex_artist');
            
            // Save artist profile data
            update_user_meta($user->ID, '_vortex_artist_bio', $bio);
            update_user_meta($user->ID, '_vortex_artist_specialties', $specialties);
            update_user_meta($user->ID, '_vortex_artist_wallet_address', $wallet_address);
            update_user_meta($user->ID, '_vortex_artist_website', $website);
            update_user_meta($user->ID, '_vortex_artist_social_media', $social_media);
            update_user_meta($user->ID, 'vortex_register_as_artist', true);
            
            // Save artist quiz data
            update_user_meta($user->ID, '_vortex_artist_education', $education);
            if ($education === 'self_taught' && !empty($self_taught_years)) {
                update_user_meta($user->ID, '_vortex_artist_self_taught_years', $self_taught_years);
            }
            update_user_meta($user->ID, '_vortex_artist_style', $artistic_style);
            update_user_meta($user->ID, '_vortex_artist_exhibitions', $exhibitions);
            update_user_meta($user->ID, '_vortex_artist_price_range', $price_range);
            update_user_meta($user->ID, '_vortex_artist_seed_commitment', $seed_art_commitment);
            
            // Set initial seed art status
            update_user_meta($user->ID, '_vortex_artist_seed_status', 'active');
            update_user_meta($user->ID, '_vortex_artist_last_seed_upload', current_time('mysql'));
            update_user_meta($user->ID, '_vortex_artist_seed_uploads_due', 2);
            
            // Save education package
            update_user_meta($user->ID, 'vortex_education_package', $education_package);
            update_user_meta($user->ID, 'vortex_workshop_hours_used', 0);
            
            // Create artist profile
            if (class_exists('Vortex_Artists')) {
                $artists = new Vortex_Artists(VORTEX_PLUGIN_NAME, VORTEX_VERSION);
                $profile_id = $artists->create_or_update_artist_profile($user->ID);
                
                if ($profile_id) {
                    // Update the profile with additional details
                    if (!empty($bio)) {
                        wp_update_post(array(
                            'ID' => $profile_id,
                            'post_content' => $bio
                        ));
                    }
                    
                    if (!empty($specialties)) {
                        update_post_meta($profile_id, '_vortex_artist_specialties', $specialties);
                    }
                    
                    if (!empty($wallet_address)) {
                        update_post_meta($profile_id, '_vortex_artist_wallet_address', $wallet_address);
                    }
                    
                    // Set social media links
                    update_post_meta($profile_id, '_vortex_artist_social_links', $social_media);
                }
            }
            
            $upgrade_success = true;
            
            // Redirect after upgrade if specified
            if (!empty($atts['redirect'])) {
                wp_redirect(esc_url($atts['redirect']));
                exit;
            }
        }
    }
    
    // Display upgrade form
    ?>
    <div class="vortex-registration-form-container vortex-artist-form">
        <?php if ($upgrade_success): ?>
            <div class="vortex-registration-success">
                <h3><?php esc_html_e('Upgrade Successful!', 'vortex-ai-marketplace'); ?></h3>
                <p><?php esc_html_e('Your account has been upgraded to an artist account.', 'vortex-ai-marketplace'); ?></p>
                
                <div class="vortex-next-steps">
                    <p><?php esc_html_e('Next Steps:', 'vortex-ai-marketplace'); ?></p>
                    <ol>
                        <li>
                            <a href="<?php echo esc_url(home_url('/artist-dashboard/')); ?>">
                                <?php esc_html_e('Go to your Artist Dashboard', 'vortex-ai-marketplace'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/submit-artwork/')); ?>">
                                <?php esc_html_e('Submit your first artwork', 'vortex-ai-marketplace'); ?>
                            </a>
                        </li>
                    </ol>
                </div>
            </div>
        <?php else: ?>
            <?php if (!empty($upgrade_error)): ?>
                <div class="vortex-registration-error">
                    <?php echo esc_html($upgrade_error); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" class="vortex-registration-form">
                <?php wp_nonce_field('vortex_artist_upgrade', 'vortex_artist_upgrade_nonce'); ?>
                
                <h3><?php esc_html_e('Artist Qualification Quiz', 'vortex-ai-marketplace'); ?></h3>
                
                <div class="form-group">
                    <label for="education"><?php _e('What is your art education level?', 'vortex-ai-marketplace'); ?></label>
                    <select name="vortex_artist_education" id="education" required>
                        <option value=""><?php esc_html_e('Select your education level', 'vortex-ai-marketplace'); ?></option>
                        <option value="high_school"><?php esc_html_e('High School', 'vortex-ai-marketplace'); ?></option>
                        <option value="bachelor"><?php esc_html_e('Bachelor\'s Degree in Art', 'vortex-ai-marketplace'); ?></option>
                        <option value="masters"><?php esc_html_e('Master\'s Degree in Art', 'vortex-ai-marketplace'); ?></option>
                        <option value="doctorate"><?php esc_html_e('Doctorate in Art', 'vortex-ai-marketplace'); ?></option>
                        <option value="self_taught"><?php esc_html_e('Self-taught', 'vortex-ai-marketplace'); ?></option>
                    </select>
                </div>
                
                <div class="form-group self-taught-years" style="display: none;">
                    <label for="self_taught_years"><?php esc_html_e('If self-taught, how many years have you been practicing?', 'vortex-ai-marketplace'); ?></label>
                    <input type="number" id="self_taught_years" name="vortex_artist_self_taught_years" min="0" max="100">
                </div>
                
                <div class="form-group">
                    <label for="style"><?php _e('What is your primary artistic style?', 'vortex-ai-marketplace'); ?></label>
                    <select name="vortex_artist_style" id="style" required>
                        <option value=""><?php esc_html_e('Select your primary style', 'vortex-ai-marketplace'); ?></option>
                        <option value="abstract"><?php esc_html_e('Abstract', 'vortex-ai-marketplace'); ?></option>
                        <option value="realistic"><?php esc_html_e('Realistic', 'vortex-ai-marketplace'); ?></option>
                        <option value="impressionist"><?php esc_html_e('Impressionist', 'vortex-ai-marketplace'); ?></option>
                        <option value="expressionist"><?php esc_html_e('Expressionist', 'vortex-ai-marketplace'); ?></option>
                        <option value="surrealist"><?php esc_html_e('Surrealist', 'vortex-ai-marketplace'); ?></option>
                        <option value="minimalist"><?php esc_html_e('Minimalist', 'vortex-ai-marketplace'); ?></option>
                        <option value="pop_art"><?php esc_html_e('Pop Art', 'vortex-ai-marketplace'); ?></option>
                        <option value="digital"><?php esc_html_e('Digital Art', 'vortex-ai-marketplace'); ?></option>
                        <option value="conceptual"><?php esc_html_e('Conceptual', 'vortex-ai-marketplace'); ?></option>
                        <option value="other"><?php esc_html_e('Other', 'vortex-ai-marketplace'); ?></option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="exhibitions"><?php _e('How many exhibitions have you participated in?', 'vortex-ai-marketplace'); ?></label>
                    <select name="vortex_artist_exhibitions" id="exhibitions" required>
                        <option value=""><?php esc_html_e('Select exhibition experience', 'vortex-ai-marketplace'); ?></option>
                        <option value="none"><?php esc_html_e('None', 'vortex-ai-marketplace'); ?></option>
                        <option value="1-5"><?php esc_html_e('1-5 exhibitions', 'vortex-ai-marketplace'); ?></option>
                        <option value="6-15"><?php esc_html_e('6-15 exhibitions', 'vortex-ai-marketplace'); ?></option>
                        <option value="16-30"><?php esc_html_e('16-30 exhibitions', 'vortex-ai-marketplace'); ?></option>
                        <option value="30+"><?php esc_html_e('30+ exhibitions', 'vortex-ai-marketplace'); ?></option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="price_range"><?php _e('What is the typical price range of your artwork?', 'vortex-ai-marketplace'); ?></label>
                    <select name="vortex_artist_price_range" id="price_range" required>
                        <option value=""><?php esc_html_e('Select price range', 'vortex-ai-marketplace'); ?></option>
                        <option value="0-15000"><?php esc_html_e('$0 - $15,000', 'vortex-ai-marketplace'); ?></option>
                        <option value="15000-50000"><?php esc_html_e('$15,000 - $50,000', 'vortex-ai-marketplace'); ?></option>
                        <option value="50000+"><?php esc_html_e('$50,000+', 'vortex-ai-marketplace'); ?></option>
                    </select>
                </div>
                
                <div class="form-group vortex-seed-commitment">
                    <input type="checkbox" id="seed_art_commitment" name="vortex_artist_seed_art_commitment" value="1" required>
                    <label for="seed_art_commitment">
                        <?php esc_html_e('I agree to upload two hand-crafted artworks ("seed artwork") weekly, which will help define and refine my artistic style on the platform. I understand that failing to meet this commitment may result in losing artist privileges temporarily until I resume regular uploads.', 'vortex-ai-marketplace'); ?> <span class="required">*</span>
                    </label>
                </div>
                
                <div class="vortex-seed-commitment-notice">
                    <p class="vortex-notice">
                        <span class="dashicons dashicons-info-outline"></span>
                        <?php esc_html_e('Note: Regular seed artwork uploads are required to maintain artist status. If you do not upload two seed artworks weekly, your account will revert to a standard member role until you resume regular uploads. You will still have access to your account and libraries, but not to artist-exclusive features.', 'vortex-ai-marketplace'); ?>
                    </p>
                </div>
                
                <h3><?php esc_html_e('Artist Education Package', 'vortex-ai-marketplace'); ?></h3>
                
                <div class="vortex-education-package">
                    <p class="vortex-education-intro">
                        <?php esc_html_e('Choose an education package to access artist workshops and earn certification as a Pro Artist. All packages include 1 semester of workshop access.', 'vortex-ai-marketplace'); ?>
                    </p>
                    
                    <div class="vortex-education-options">
                        <div class="vortex-education-option">
                            <input type="radio" id="upgrade_education_standard" name="vortex_education_package" value="standard" checked>
                            <label for="upgrade_education_standard" class="vortex-education-label">
                                <div class="vortex-education-title">
                                    <span class="vortex-education-name"><?php esc_html_e('Standard', 'vortex-ai-marketplace'); ?></span>
                                    <span class="vortex-education-price">500 TOLA</span>
                                </div>
                                <div class="vortex-education-details">
                                    <span class="vortex-education-hours">72 <?php esc_html_e('workshop hours', 'vortex-ai-marketplace'); ?></span>
                                    <ul class="vortex-education-features">
                                        <li><?php esc_html_e('Access to all basic workshops', 'vortex-ai-marketplace'); ?></li>
                                        <li><?php esc_html_e('Pro Artist certification upon completion', 'vortex-ai-marketplace'); ?></li>
                                        <li><?php esc_html_e('Standard support', 'vortex-ai-marketplace'); ?></li>
                                    </ul>
                                </div>
                            </label>
                        </div>
                        
                        <div class="vortex-education-option">
                            <input type="radio" id="upgrade_education_premium" name="vortex_education_package" value="premium">
                            <label for="upgrade_education_premium" class="vortex-education-label">
                                <div class="vortex-education-title">
                                    <span class="vortex-education-name"><?php esc_html_e('Premium', 'vortex-ai-marketplace'); ?></span>
                                    <span class="vortex-education-price">800 TOLA</span>
                                </div>
                                <div class="vortex-education-details">
                                    <span class="vortex-education-hours">120 <?php esc_html_e('workshop hours', 'vortex-ai-marketplace'); ?></span>
                                    <ul class="vortex-education-features">
                                        <li><?php esc_html_e('Access to all basic and advanced workshops', 'vortex-ai-marketplace'); ?></li>
                                        <li><?php esc_html_e('Pro Artist certification upon completion', 'vortex-ai-marketplace'); ?></li>
                                        <li><?php esc_html_e('Premium support with 1-on-1 sessions', 'vortex-ai-marketplace'); ?></li>
                                    </ul>
                                </div>
                            </label>
                        </div>
                        
                        <div class="vortex-education-option">
                            <input type="radio" id="upgrade_education_professional" name="vortex_education_package" value="professional">
                            <label for="upgrade_education_professional" class="vortex-education-label">
                                <div class="vortex-education-title">
                                    <span class="vortex-education-name"><?php esc_html_e('Professional', 'vortex-ai-marketplace'); ?></span>
                                    <span class="vortex-education-price">1200 TOLA</span>
                                </div>
                                <div class="vortex-education-details">
                                    <span class="vortex-education-hours">180 <?php esc_html_e('workshop hours', 'vortex-ai-marketplace'); ?></span>
                                    <ul class="vortex-education-features">
                                        <li><?php esc_html_e('Access to all workshops including master classes', 'vortex-ai-marketplace'); ?></li>
                                        <li><?php esc_html_e('Pro Artist certification upon completion', 'vortex-ai-marketplace'); ?></li>
                                        <li><?php esc_html_e('VIP support with dedicated mentor', 'vortex-ai-marketplace'); ?></li>
                                        <li><?php esc_html_e('Portfolio review by industry experts', 'vortex-ai-marketplace'); ?></li>
                                    </ul>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <p class="vortex-education-payment-info">
                        <span class="dashicons dashicons-info-outline"></span>
                        <?php esc_html_e('Payment in TOLA tokens will be processed after registration. Your wallet address must have sufficient TOLA balance.', 'vortex-ai-marketplace'); ?>
                    </p>
                </div>
                
                <h3><?php esc_html_e('Artist Profile', 'vortex-ai-marketplace'); ?></h3>
                
                <?php // Display artist fields ?>
                <?php include(dirname(__FILE__) . '/partials/artist-profile-fields.php'); ?>
                
                <div class="vortex-form-field vortex-terms-field">
                    <input type="checkbox" 
                           id="terms" 
                           name="terms" 
                           value="1" 
                           required>
                    <label for="terms">
                        <?php 
                        printf(
                            esc_html__('I agree to the %1$sArtist Terms of Service%2$s and %3$sPrivacy Policy%4$s', 'vortex-ai-marketplace'),
                            '<a href="' . esc_url(home_url('/artist-terms-of-service/')) . '" target="_blank">',
                            '</a>',
                            '<a href="' . esc_url(home_url('/privacy-policy/')) . '" target="_blank">',
                            '</a>'
                        ); 
                        ?> <span class="required">*</span>
                    </label>
                </div>
                
                <div class="vortex-form-submit">
                    <button type="submit" name="vortex_upgrade_artist" class="vortex-button vortex-button-primary">
                        <?php esc_html_e('Upgrade to Artist', 'vortex-ai-marketplace'); ?>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    <?php
}
?>

<div class="vortex-registration-form-container vortex-artist-form">
    <?php if ($registration_success): ?>
        <div class="vortex-registration-success">
            <h3><?php esc_html_e('Artist Registration Successful!', 'vortex-ai-marketplace'); ?></h3>
            <p><?php esc_html_e('Welcome to Vortex AI Marketplace! Your artist account has been created.', 'vortex-ai-marketplace'); ?></p>
            
            <div class="vortex-next-steps">
                <p><?php esc_html_e('Next Steps:', 'vortex-ai-marketplace'); ?></p>
                <ol>
                    <li>
                        <a href="<?php echo esc_url(home_url('/artist-dashboard/')); ?>">
                            <?php esc_html_e('Go to your Artist Dashboard', 'vortex-ai-marketplace'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(home_url('/submit-artwork/')); ?>">
                            <?php esc_html_e('Submit your first artwork', 'vortex-ai-marketplace'); ?>
                        </a>
                    </li>
                </ol>
            </div>
        </div>
    <?php else: ?>
        <h2><?php esc_html_e('Register as an Artist', 'vortex-ai-marketplace'); ?></h2>
        <p class="vortex-registration-intro">
            <?php esc_html_e('Join Vortex AI Marketplace as an artist to sell your artwork and reach new collectors.', 'vortex-ai-marketplace'); ?>
        </p>
        
        <?php if (!empty($registration_error)): ?>
            <div class="vortex-registration-error">
                <?php echo esc_html($registration_error); ?>
            </div>
        <?php endif; ?>
        
        <form id="vortex-artist-qualification-quiz" class="vortex-form">
            <?php wp_nonce_field('vortex_quiz_nonce', 'quiz_security'); ?>
            
            <h3><?php _e('Artist Qualification Quiz', 'vortex-ai-marketplace'); ?></h3>
            
            <div class="form-group">
                <label for="education"><?php _e('What is your art education level?', 'vortex-ai-marketplace'); ?></label>
                <select name="education" id="education" required>
                    <option value=""><?php esc_html_e('Select your education level', 'vortex-ai-marketplace'); ?></option>
                    <option value="high_school" <?php selected(isset($_POST['vortex_artist_education']) && $_POST['vortex_artist_education'] === 'high_school'); ?>><?php esc_html_e('High School', 'vortex-ai-marketplace'); ?></option>
                    <option value="bachelor" <?php selected(isset($_POST['vortex_artist_education']) && $_POST['vortex_artist_education'] === 'bachelor'); ?>><?php esc_html_e('Bachelor\'s Degree in Art', 'vortex-ai-marketplace'); ?></option>
                    <option value="masters" <?php selected(isset($_POST['vortex_artist_education']) && $_POST['vortex_artist_education'] === 'masters'); ?>><?php esc_html_e('Master\'s Degree in Art', 'vortex-ai-marketplace'); ?></option>
                    <option value="doctorate" <?php selected(isset($_POST['vortex_artist_education']) && $_POST['vortex_artist_education'] === 'doctorate'); ?>><?php esc_html_e('Doctorate in Art', 'vortex-ai-marketplace'); ?></option>
                    <option value="self_taught" <?php selected(isset($_POST['vortex_artist_education']) && $_POST['vortex_artist_education'] === 'self_taught'); ?>><?php esc_html_e('Self-taught', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
            
            <div class="form-group self-taught-years" style="display: none;">
                <label for="self_taught_years"><?php esc_html_e('If self-taught, how many years have you been practicing?', 'vortex-ai-marketplace'); ?></label>
                <input type="number" 
                       id="self_taught_years" 
                       name="self_taught_years" 
                       value="<?php echo isset($_POST['vortex_artist_self_taught_years']) ? esc_attr(intval($_POST['vortex_artist_self_taught_years'])) : ''; ?>" 
                       min="0" 
                       max="100">
            </div>
            
            <div class="form-group">
                <label for="style"><?php _e('What is your primary artistic style?', 'vortex-ai-marketplace'); ?></label>
                <select name="style" id="style" required>
                    <option value=""><?php esc_html_e('Select your primary style', 'vortex-ai-marketplace'); ?></option>
                    <option value="abstract" <?php selected(isset($_POST['vortex_artist_style']) && $_POST['vortex_artist_style'] === 'abstract'); ?>><?php esc_html_e('Abstract', 'vortex-ai-marketplace'); ?></option>
                    <option value="realistic" <?php selected(isset($_POST['vortex_artist_style']) && $_POST['vortex_artist_style'] === 'realistic'); ?>><?php esc_html_e('Realistic', 'vortex-ai-marketplace'); ?></option>
                    <option value="impressionist" <?php selected(isset($_POST['vortex_artist_style']) && $_POST['vortex_artist_style'] === 'impressionist'); ?>><?php esc_html_e('Impressionist', 'vortex-ai-marketplace'); ?></option>
                    <option value="expressionist" <?php selected(isset($_POST['vortex_artist_style']) && $_POST['vortex_artist_style'] === 'expressionist'); ?>><?php esc_html_e('Expressionist', 'vortex-ai-marketplace'); ?></option>
                    <option value="surrealist" <?php selected(isset($_POST['vortex_artist_style']) && $_POST['vortex_artist_style'] === 'surrealist'); ?>><?php esc_html_e('Surrealist', 'vortex-ai-marketplace'); ?></option>
                    <option value="minimalist" <?php selected(isset($_POST['vortex_artist_style']) && $_POST['vortex_artist_style'] === 'minimalist'); ?>><?php esc_html_e('Minimalist', 'vortex-ai-marketplace'); ?></option>
                    <option value="pop_art" <?php selected(isset($_POST['vortex_artist_style']) && $_POST['vortex_artist_style'] === 'pop_art'); ?>><?php esc_html_e('Pop Art', 'vortex-ai-marketplace'); ?></option>
                    <option value="digital" <?php selected(isset($_POST['vortex_artist_style']) && $_POST['vortex_artist_style'] === 'digital'); ?>><?php esc_html_e('Digital Art', 'vortex-ai-marketplace'); ?></option>
                    <option value="conceptual" <?php selected(isset($_POST['vortex_artist_style']) && $_POST['vortex_artist_style'] === 'conceptual'); ?>><?php esc_html_e('Conceptual', 'vortex-ai-marketplace'); ?></option>
                    <option value="other" <?php selected(isset($_POST['vortex_artist_style']) && $_POST['vortex_artist_style'] === 'other'); ?>><?php esc_html_e('Other', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="exhibitions"><?php _e('How many exhibitions have you participated in?', 'vortex-ai-marketplace'); ?></label>
                <select name="exhibitions" id="exhibitions" required>
                    <option value=""><?php esc_html_e('Select exhibition experience', 'vortex-ai-marketplace'); ?></option>
                    <option value="none" <?php selected(isset($_POST['vortex_artist_exhibitions']) && $_POST['vortex_artist_exhibitions'] === 'none'); ?>><?php esc_html_e('None', 'vortex-ai-marketplace'); ?></option>
                    <option value="1-5" <?php selected(isset($_POST['vortex_artist_exhibitions']) && $_POST['vortex_artist_exhibitions'] === '1-5'); ?>><?php esc_html_e('1-5 exhibitions', 'vortex-ai-marketplace'); ?></option>
                    <option value="6-15" <?php selected(isset($_POST['vortex_artist_exhibitions']) && $_POST['vortex_artist_exhibitions'] === '6-15'); ?>><?php esc_html_e('6-15 exhibitions', 'vortex-ai-marketplace'); ?></option>
                    <option value="16-30" <?php selected(isset($_POST['vortex_artist_exhibitions']) && $_POST['vortex_artist_exhibitions'] === '16-30'); ?>><?php esc_html_e('16-30 exhibitions', 'vortex-ai-marketplace'); ?></option>
                    <option value="30+" <?php selected(isset($_POST['vortex_artist_exhibitions']) && $_POST['vortex_artist_exhibitions'] === '30+'); ?>><?php esc_html_e('30+ exhibitions', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="price_range"><?php _e('What is the typical price range of your artwork?', 'vortex-ai-marketplace'); ?></label>
                <select name="price_range" id="price_range" required>
                    <option value=""><?php esc_html_e('Select price range', 'vortex-ai-marketplace'); ?></option>
                    <option value="0-15000" <?php selected(isset($_POST['vortex_artist_price_range']) && $_POST['vortex_artist_price_range'] === '0-15000'); ?>><?php esc_html_e('$0 - $15,000', 'vortex-ai-marketplace'); ?></option>
                    <option value="15000-50000" <?php selected(isset($_POST['vortex_artist_price_range']) && $_POST['vortex_artist_price_range'] === '15000-50000'); ?>><?php esc_html_e('$15,000 - $50,000', 'vortex-ai-marketplace'); ?></option>
                    <option value="50000+" <?php selected(isset($_POST['vortex_artist_price_range']) && $_POST['vortex_artist_price_range'] === '50000+'); ?>><?php esc_html_e('$50,000+', 'vortex-ai-marketplace'); ?></option>
                </select>
            </div>
            
            <div class="form-group vortex-seed-commitment">
                <input type="checkbox" 
                       id="seed_art_commitment" 
                       name="seed_art_commitment" 
                       value="1" 
                       required>
                <label for="seed_art_commitment">
                    <?php esc_html_e('I agree to upload two hand-crafted artworks ("seed artwork") weekly, which will help define and refine my artistic style on the platform. I understand that failing to meet this commitment may result in losing artist privileges temporarily until I resume regular uploads.', 'vortex-ai-marketplace'); ?> <span class="required">*</span>
                </label>
            </div>
            
            <div class="vortex-seed-commitment-notice">
                <p class="vortex-notice">
                    <span class="dashicons dashicons-info-outline"></span>
                    <?php esc_html_e('Note: Regular seed artwork uploads are required to maintain artist status. If you do not upload two seed artworks weekly, your account will revert to a standard member role until you resume regular uploads. You will still have access to your account and libraries, but not to artist-exclusive features.', 'vortex-ai-marketplace'); ?>
                </p>
            </div>
            
            <h3><?php esc_html_e('Artist Education Package', 'vortex-ai-marketplace'); ?></h3>
            
            <div class="vortex-education-package">
                <p class="vortex-education-intro">
                    <?php esc_html_e('Choose an education package to access artist workshops and earn certification as a Pro Artist. All packages include 1 semester of workshop access.', 'vortex-ai-marketplace'); ?>
                </p>
                
                <div class="vortex-education-options">
                    <div class="vortex-education-option">
                        <input type="radio" id="education_standard" name="vortex_education_package" value="standard" checked>
                        <label for="education_standard" class="vortex-education-label">
                            <div class="vortex-education-title">
                                <span class="vortex-education-name"><?php esc_html_e('Standard', 'vortex-ai-marketplace'); ?></span>
                                <span class="vortex-education-price">500 TOLA</span>
                            </div>
                            <div class="vortex-education-details">
                                <span class="vortex-education-hours">72 <?php esc_html_e('workshop hours', 'vortex-ai-marketplace'); ?></span>
                                <ul class="vortex-education-features">
                                    <li><?php esc_html_e('Access to all basic workshops', 'vortex-ai-marketplace'); ?></li>
                                    <li><?php esc_html_e('Pro Artist certification upon completion', 'vortex-ai-marketplace'); ?></li>
                                    <li><?php esc_html_e('Standard support', 'vortex-ai-marketplace'); ?></li>
                                </ul>
                            </div>
                        </label>
                    </div>
                    
                    <div class="vortex-education-option">
                        <input type="radio" id="education_premium" name="vortex_education_package" value="premium">
                        <label for="education_premium" class="vortex-education-label">
                            <div class="vortex-education-title">
                                <span class="vortex-education-name"><?php esc_html_e('Premium', 'vortex-ai-marketplace'); ?></span>
                                <span class="vortex-education-price">800 TOLA</span>
                            </div>
                            <div class="vortex-education-details">
                                <span class="vortex-education-hours">120 <?php esc_html_e('workshop hours', 'vortex-ai-marketplace'); ?></span>
                                <ul class="vortex-education-features">
                                    <li><?php esc_html_e('Access to all basic and advanced workshops', 'vortex-ai-marketplace'); ?></li>
                                    <li><?php esc_html_e('Pro Artist certification upon completion', 'vortex-ai-marketplace'); ?></li>
                                    <li><?php esc_html_e('Premium support with 1-on-1 sessions', 'vortex-ai-marketplace'); ?></li>
                                </ul>
                            </div>
                        </label>
                    </div>
                    
                    <div class="vortex-education-option">
                        <input type="radio" id="education_professional" name="vortex_education_package" value="professional">
                        <label for="education_professional" class="vortex-education-label">
                            <div class="vortex-education-title">
                                <span class="vortex-education-name"><?php esc_html_e('Professional', 'vortex-ai-marketplace'); ?></span>
                                <span class="vortex-education-price">1200 TOLA</span>
                            </div>
                            <div class="vortex-education-details">
                                <span class="vortex-education-hours">180 <?php esc_html_e('workshop hours', 'vortex-ai-marketplace'); ?></span>
                                <ul class="vortex-education-features">
                                    <li><?php esc_html_e('Access to all workshops including master classes', 'vortex-ai-marketplace'); ?></li>
                                    <li><?php esc_html_e('Pro Artist certification upon completion', 'vortex-ai-marketplace'); ?></li>
                                    <li><?php esc_html_e('VIP support with dedicated mentor', 'vortex-ai-marketplace'); ?></li>
                                    <li><?php esc_html_e('Portfolio review by industry experts', 'vortex-ai-marketplace'); ?></li>
                                </ul>
                            </div>
                        </label>
                    </div>
                </div>
                
                <p class="vortex-education-payment-info">
                    <span class="dashicons dashicons-info-outline"></span>
                    <?php esc_html_e('Payment in TOLA tokens will be processed after registration. Your wallet address must have sufficient TOLA balance.', 'vortex-ai-marketplace'); ?>
                </p>
            </div>
            
            <h3><?php esc_html_e('Artist Profile', 'vortex-ai-marketplace'); ?></h3>
            
            <?php // Include artist fields partial ?>
            <?php include(dirname(__FILE__) . '/partials/artist-profile-fields.php'); ?>
            
            <div class="vortex-form-field vortex-terms-field">
                <input type="checkbox" 
                       id="terms" 
                       name="terms" 
                       value="1" 
                       required>
                <label for="terms">
                    <?php 
                    printf(
                        esc_html__('I agree to the %1$sArtist Terms of Service%2$s and %3$sPrivacy Policy%4$s', 'vortex-ai-marketplace'),
                        '<a href="' . esc_url(home_url('/artist-terms-of-service/')) . '" target="_blank">',
                        '</a>',
                        '<a href="' . esc_url(home_url('/privacy-policy/')) . '" target="_blank">',
                        '</a>'
                    ); 
                    ?> <span class="required">*</span>
                </label>
            </div>
            
            <div class="vortex-form-submit">
                <button type="submit" name="vortex_register_artist" class="vortex-button vortex-button-primary">
                    <?php esc_html_e('Register as Artist', 'vortex-ai-marketplace'); ?>
                </button>
            </div>
        </form>
        
        <div class="vortex-login-link">
            <p>
                <?php 
                printf(
                    esc_html__('Already have an account? %sLog In%s', 'vortex-ai-marketplace'),
                    '<a href="' . esc_url(wp_login_url()) . '">',
                    '</a>'
                ); 
                ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<style>
.vortex-registration-form-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 30px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.vortex-artist-form h3 {
    margin-top: 30px;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    color: #333;
}

.vortex-registration-form-container h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
}

.vortex-registration-intro {
    margin-bottom: 25px;
    color: #666;
}

.vortex-registration-error {
    padding: 10px 15px;
    margin-bottom: 20px;
    background-color: #ffebee;
    border-left: 4px solid #f44336;
    color: #d32f2f;
}

.vortex-registration-success {
    padding: 20px;
    margin-bottom: 20px;
    background-color: #e8f5e9;
    border-left: 4px solid #4caf50;
    color: #2e7d32;
}

.vortex-form-field {
    margin-bottom: 20px;
}

.vortex-form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.vortex-form-field input[type="text"],
.vortex-form-field input[type="email"],
.vortex-form-field input[type="password"],
.vortex-form-field input[type="url"],
.vortex-form-field textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.vortex-form-field textarea {
    min-height: 120px;
}

.vortex-terms-field {
    display: flex;
    align-items: flex-start;
}

.vortex-terms-field input {
    margin-top: 5px;
    margin-right: 10px;
}

.vortex-button {
    display: inline-block;
    padding: 10px 20px;
    background-color: #2271b1;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    font-weight: 500;
}

.vortex-button:hover {
    background-color: #135e96;
    color: white;
}

.vortex-button-secondary {
    background-color: #f0f0f1;
    color: #2c3338;
}

.vortex-button-secondary:hover {
    background-color: #e5e5e5;
    color: #2c3338;
}

.vortex-login-link {
    margin-top: 20px;
    text-align: center;
}

.required {
    color: #f44336;
}

.vortex-next-steps {
    margin-top: 20px;
}

.vortex-next-steps ol {
    margin-left: 20px;
}

.vortex-next-steps li {
    margin-bottom: 10px;
}

.vortex-user-actions {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

.vortex-social-fields {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

@media (max-width: 768px) {
    .vortex-social-fields {
        grid-template-columns: 1fr;
    }
}

/* Artist Quiz Styles */
.vortex-quiz-intro {
    margin-bottom: 20px;
    padding: 15px;
    background-color: #f8f9fa;
    border-left: 4px solid #4e85c5;
    font-style: italic;
}

.vortex-seed-commitment {
    margin-top: 30px;
}

.vortex-seed-commitment label {
    font-weight: 600;
}

.vortex-seed-commitment-notice {
    margin-top: 10px;
    margin-bottom: 30px;
}

.vortex-notice {
    padding: 15px;
    background-color: #fff8e1;
    border-left: 4px solid #ffc107;
    font-size: 0.9em;
}

.vortex-notice .dashicons {
    color: #ffc107;
    margin-right: 8px;
    vertical-align: middle;
}
</style>

<script type="text/javascript">
(function($) {
    $(document).ready(function() {
        // Function to toggle self-taught years field
        function toggleSelfTaughtYears() {
            var educationValue = $('#education').val();
            if (educationValue === 'self_taught') {
                $('.self-taught-years').show();
                $('#self_taught_years').prop('required', true);
            } else {
                $('.self-taught-years').hide();
                $('#self_taught_years').prop('required', false);
            }
        }
        
        // Initial check
        toggleSelfTaughtYears();
        
        // Event listener for education select change
        $('#education').on('change', toggleSelfTaughtYears);
        
        // Seed art commitment confirmation
        $('#seed_art_commitment').on('change', function() {
            if ($(this).is(':checked')) {
                // Show confirmation dialog
                if (!confirm('<?php echo esc_js(__("Please confirm that you understand the seed artwork commitment: You agree to upload two hand-crafted artworks weekly to maintain your artist status. If you don't meet this requirement, your account will revert to a standard member role until you resume regular uploads. Do you agree to these terms?", "vortex-ai-marketplace")); ?>')) {
                    // If user cancels, uncheck the box
                    $(this).prop('checked', false);
                }
            }
        });
        
        // Education package selection highlight - for both upgrade and registration forms
        $('.vortex-education-option input[type="radio"]').on('change', function() {
            // Remove selected class from all options
            $('.vortex-education-option label').removeClass('selected');
            
            // Add selected class to the chosen option
            $(this).closest('.vortex-education-option').find('label').addClass('selected');
        });
    });
})(jQuery);
</script> 