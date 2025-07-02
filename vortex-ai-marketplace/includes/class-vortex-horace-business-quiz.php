<?php
/**
 * HORACE Business Strategist Quiz System
 *
 * @package VORTEX_AI_Marketplace
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vortex_HORACE_Business_Quiz {
    
    private static $instance = null;
    private $quiz_responses_table;
    
    /**
     * HORACE Business Quiz Questions
     */
    private $quiz_questions = array(
        'business_goals' => array(
            'type' => 'multiple_choice',
            'question' => 'What is your primary business goal as an artist?',
            'options' => array(
                'creative_expression' => 'Pure creative expression and artistic fulfillment',
                'supplemental_income' => 'Generate supplemental income from art',
                'full_time_career' => 'Build a full-time sustainable art career',
                'gallery_representation' => 'Achieve gallery representation and exhibitions',
                'digital_marketplace' => 'Dominate digital art marketplaces and NFT space',
                'teaching_mentoring' => 'Teach and mentor other artists while creating',
                'commercial_success' => 'Achieve significant commercial and critical success'
            )
        ),
        'target_market' => array(
            'type' => 'multiple_choice',
            'question' => 'Who is your ideal collector/customer?',
            'options' => array(
                'emerging_collectors' => 'Emerging collectors ($500-$2,000 budget)',
                'established_collectors' => 'Established collectors ($2,000-$15,000 budget)',
                'high_end_collectors' => 'High-end collectors ($15,000+ budget)',
                'corporate_clients' => 'Corporate clients and businesses',
                'interior_designers' => 'Interior designers and architects',
                'digital_natives' => 'Digital natives and crypto enthusiasts',
                'art_institutions' => 'Museums and art institutions',
                'general_public' => 'General public and art enthusiasts'
            )
        ),
        'business_experience' => array(
            'type' => 'multiple_choice',
            'question' => 'What is your current business/entrepreneurial experience?',
            'options' => array(
                'complete_beginner' => 'Complete beginner - never sold art professionally',
                'occasional_sales' => 'Occasional sales through social media or local venues',
                'regular_sales' => 'Regular sales but inconsistent income',
                'established_business' => 'Established art business with steady income',
                'multi_revenue' => 'Multiple revenue streams (sales, commissions, teaching)',
                'business_background' => 'Strong business background in other industries',
                'entrepreneur' => 'Experienced entrepreneur expanding into art'
            )
        ),
        'marketing_strategy' => array(
            'type' => 'multiple_choice',
            'question' => 'How do you currently market your artwork?',
            'options' => array(
                'no_marketing' => 'I don\'t actively market my work',
                'social_media_basic' => 'Basic social media posting (Instagram, Facebook)',
                'social_media_strategic' => 'Strategic social media with consistent branding',
                'website_portfolio' => 'Professional website and online portfolio',
                'email_marketing' => 'Email newsletters and marketing campaigns',
                'local_networking' => 'Local galleries, art fairs, and networking events',
                'digital_marketing' => 'Comprehensive digital marketing strategy',
                'professional_representation' => 'Professional gallery or agent representation'
            )
        ),
        'financial_goals' => array(
            'type' => 'multiple_choice',
            'question' => 'What are your annual income goals from art?',
            'options' => array(
                'hobby_level' => 'Under $5,000 - Art as a creative hobby',
                'supplemental' => '$5,000-$25,000 - Supplemental income',
                'part_time' => '$25,000-$50,000 - Significant part-time income',
                'full_time_modest' => '$50,000-$100,000 - Full-time modest living',
                'full_time_comfortable' => '$100,000-$250,000 - Comfortable full-time career',
                'high_earning' => '$250,000-$500,000 - High-earning professional artist',
                'top_tier' => '$500,000+ - Top-tier commercial success'
            )
        ),
        'time_commitment' => array(
            'type' => 'multiple_choice',
            'question' => 'How much time can you dedicate to business development weekly?',
            'options' => array(
                'minimal' => '1-3 hours - Minimal time for business activities',
                'casual' => '4-7 hours - Casual business development',
                'moderate' => '8-15 hours - Moderate business focus',
                'serious' => '16-25 hours - Serious business development',
                'intensive' => '26-35 hours - Intensive business approach',
                'full_time' => '36+ hours - Full-time business focus'
            )
        ),
        'biggest_challenge' => array(
            'type' => 'multiple_choice',
            'question' => 'What is your biggest business challenge as an artist?',
            'options' => array(
                'pricing_work' => 'Pricing my work appropriately',
                'finding_customers' => 'Finding and reaching potential customers',
                'marketing_visibility' => 'Marketing and gaining visibility',
                'business_skills' => 'Lack of business and entrepreneurial skills',
                'time_management' => 'Balancing creation time with business tasks',
                'financial_management' => 'Financial planning and money management',
                'competition' => 'Standing out in a competitive market',
                'technology' => 'Keeping up with digital trends and technology'
            )
        ),
        'growth_priorities' => array(
            'type' => 'multiple_choice',
            'question' => 'What is your top priority for business growth?',
            'options' => array(
                'online_presence' => 'Building a strong online presence',
                'local_market' => 'Dominating my local art market',
                'digital_innovation' => 'Leveraging digital innovation (NFTs, AI, VR)',
                'traditional_galleries' => 'Traditional gallery representation',
                'direct_sales' => 'Direct-to-collector sales and relationships',
                'passive_income' => 'Creating passive income streams',
                'brand_building' => 'Building a recognizable personal brand',
                'scaling_operations' => 'Scaling operations and systemizing business'
            )
        ),
        'investment_willingness' => array(
            'type' => 'multiple_choice',
            'question' => 'How much are you willing to invest monthly in business development?',
            'options' => array(
                'minimal' => 'Under $100 - Very limited budget',
                'conservative' => '$100-$300 - Conservative investment',
                'moderate' => '$300-$700 - Moderate investment in growth',
                'serious' => '$700-$1,500 - Serious about business growth',
                'aggressive' => '$1,500-$3,000 - Aggressive growth investment',
                'premium' => '$3,000+ - Premium investment for rapid scaling'
            )
        ),
        'collaboration_preference' => array(
            'type' => 'multiple_choice',
            'question' => 'What is your preferred approach to collaboration and community?',
            'options' => array(
                'solo_artist' => 'Prefer working independently as a solo artist',
                'small_collaborations' => 'Small collaborations with select artists',
                'community_active' => 'Active in artist communities and networks',
                'mentorship_seeking' => 'Seeking mentorship and guidance',
                'mentorship_providing' => 'Providing mentorship to emerging artists',
                'collective_projects' => 'Participating in collective art projects',
                'business_partnerships' => 'Open to business partnerships and joint ventures'
            )
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
        $this->quiz_responses_table = $wpdb->prefix . 'vortex_horace_quiz_responses';
        
        $this->init_hooks();
        $this->create_tables();
    }

    private function init_hooks() {
        // AJAX handlers
        add_action('wp_ajax_vortex_submit_horace_quiz', array($this, 'handle_quiz_submission'));
        add_action('wp_ajax_nopriv_vortex_submit_horace_quiz', array($this, 'handle_quiz_submission'));
        
        // Trigger hooks
        add_action('vortex_artist_profile_completed', array($this, 'trigger_horace_quiz'));
        add_action('vortex_seed_artwork_uploaded', array($this, 'check_quiz_trigger'), 10, 2);
        
        // Shortcode
        add_shortcode('vortex_horace_quiz', array($this, 'render_horace_quiz'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->quiz_responses_table} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            question_key varchar(50) NOT NULL,
            selected_option varchar(50) NOT NULL,
            business_score int UNSIGNED DEFAULT 0,
            quiz_completed tinyint(1) DEFAULT 0,
            recommendations longtext DEFAULT NULL,
            business_profile varchar(100) DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_question (user_id, question_key),
            KEY user_id (user_id),
            KEY business_profile (business_profile),
            KEY quiz_completed (quiz_completed)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Check if HORACE quiz should be triggered
     */
    public function check_quiz_trigger($user_id, $artwork_count) {
        // Trigger HORACE quiz after user uploads their first seed artwork
        // and has completed their artist registration
        
        $assessment_completed = get_user_meta($user_id, 'vortex_assessment_completed', true);
        $education_tier = get_user_meta($user_id, 'vortex_education_tier', true);
        $horace_completed = get_user_meta($user_id, 'vortex_horace_quiz_completed', true);
        
        // Trigger for Pro+, Studio, Full Time Student, and Masters tiers, and if not already completed
        $horace_access_tiers = ['artist_pro', 'artist_studio', 'full_time_student', 'masters'];
        
        if ($assessment_completed && 
            in_array($education_tier, $horace_access_tiers) && 
            !$horace_completed && 
            $artwork_count >= 1) {
            
            $this->trigger_horace_quiz($user_id);
        }
    }

    /**
     * Trigger HORACE quiz notification
     */
    public function trigger_horace_quiz($user_id) {
        // Set flag that HORACE is ready
        update_user_meta($user_id, 'vortex_horace_quiz_ready', true);
        
        // Send notification email
        $this->send_horace_quiz_notification($user_id);
        
        // Log the trigger
        error_log("VORTEX: HORACE business quiz triggered for user {$user_id}");
    }

    /**
     * Send HORACE quiz notification email
     */
    private function send_horace_quiz_notification($user_id) {
        $user = get_user_by('ID', $user_id);
        
        if (!$user) {
            return;
        }
        
        $subject = 'HORACE is Ready to Analyze Your Business Strategy';
        
        $message = "
        <h2>Welcome to HORACE Business Strategy Assessment</h2>
        <p>Dear {$user->display_name},</p>
        
        <p>Congratulations on completing your artist profile and uploading your first seed artwork! 🎨</p>
        
        <p><strong>HORACE</strong>, our AI business strategist, is now ready to get to know you better and help optimize your artistic career path.</p>
        
        <h3>What HORACE Will Help You With:</h3>
        <ul>
            <li>🎯 <strong>Business Goal Alignment</strong> - Clarify your artistic and financial objectives</li>
            <li>📊 <strong>Market Positioning</strong> - Identify your ideal collectors and market segment</li>
            <li>💰 <strong>Revenue Strategy</strong> - Develop sustainable income streams</li>
            <li>📈 <strong>Growth Planning</strong> - Create a personalized business development roadmap</li>
            <li>🤝 <strong>Collaboration Opportunities</strong> - Connect with the right partners and communities</li>
        </ul>
        
        <p>The business quiz takes approximately <strong>10-15 minutes</strong> and will provide you with:</p>
        <ul>
            <li>Personalized business profile analysis</li>
            <li>Customized recommendations for your tier</li>
            <li>Strategic action plan for the next 90 days</li>
            <li>Access to tier-specific business resources</li>
        </ul>
        
        <p><strong>Ready to optimize your artistic business strategy?</strong></p>
        <p><a href='" . home_url('/dashboard/?horace_quiz=start') . "' style='background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;'>Start HORACE Business Quiz</a></p>
        
        <p>Best regards,<br>HORACE - Your AI Business Strategist<br>VORTEX ARTEC Team</p>
        ";
        
        wp_mail($user->user_email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
    }

    /**
     * Render HORACE quiz shortcode
     */
    public function render_horace_quiz($atts) {
        $atts = shortcode_atts(array(
            'title' => 'HORACE Business Strategy Assessment',
            'subtitle' => 'Let HORACE analyze your business goals and create a personalized strategy'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<p>You must be logged in to access the HORACE business quiz.</p>';
        }
        
        $user_id = get_current_user_id();
        $education_tier = get_user_meta($user_id, 'vortex_education_tier', true);
        
        // Check if user has access to HORACE
        $horace_access_tiers = ['artist_pro', 'artist_studio', 'full_time_student', 'masters'];
        if (!in_array($education_tier, $horace_access_tiers)) {
            return '<p>HORACE Business Quiz is available for Artist Pro, Artist Studio, Full Time Student and Masters tier members. <a href="/education-tiers/">Upgrade your tier</a> to access this feature.</p>';
        }
        
        // Check if quiz is ready
        $horace_ready = get_user_meta($user_id, 'vortex_horace_quiz_ready', true);
        if (!$horace_ready) {
            return '<p>Complete your artist registration and upload your first seed artwork to unlock HORACE Business Quiz.</p>';
        }
        
        // Check if already completed
        $horace_completed = get_user_meta($user_id, 'vortex_horace_quiz_completed', true);
        if ($horace_completed) {
            return $this->render_quiz_results($user_id);
        }
        
        ob_start();
        ?>
        <div class="vortex-horace-quiz" id="vortex-horace-quiz">
            <div class="quiz-header">
                <h2><?php echo esc_html($atts['title']); ?></h2>
                <p class="subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
                
                <div class="horace-intro">
                    <div class="horace-avatar">🤖</div>
                    <div class="horace-message">
                        <h3>Hello! I'm HORACE, your AI Business Strategist</h3>
                        <p>I'll analyze your responses to create a personalized business strategy that aligns with your artistic goals and maximizes your potential for success.</p>
                        <p><strong>This assessment takes 10-15 minutes and will provide you with actionable insights tailored to your situation.</strong></p>
                    </div>
                </div>
            </div>
            
            <form id="horace-quiz-form" class="vortex-form">
                <?php wp_nonce_field('vortex_horace_quiz', 'vortex_horace_nonce'); ?>
                
                <div class="quiz-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 10%;"></div>
                    </div>
                    <span class="progress-text">Question 1 of <?php echo count($this->quiz_questions); ?></span>
                </div>
                
                <?php $question_index = 1; ?>
                <?php foreach ($this->quiz_questions as $key => $question): ?>
                    <div class="question-section <?php echo $question_index === 1 ? 'active' : ''; ?>" data-question="<?php echo $question_index; ?>">
                        <h3>Question <?php echo $question_index; ?></h3>
                        <h4><?php echo esc_html($question['question']); ?></h4>
                        
                        <div class="question-options">
                            <?php foreach ($question['options'] as $option_key => $option_text): ?>
                                <label class="option-label">
                                    <input type="radio" name="<?php echo $key; ?>" value="<?php echo $option_key; ?>" required>
                                    <span class="option-text"><?php echo esc_html($option_text); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="question-navigation">
                            <?php if ($question_index > 1): ?>
                                <button type="button" class="btn btn-secondary prev-question">Previous</button>
                            <?php endif; ?>
                            
                            <?php if ($question_index < count($this->quiz_questions)): ?>
                                <button type="button" class="btn btn-primary next-question">Next</button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-primary submit-quiz">Complete Assessment</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php $question_index++; ?>
                <?php endforeach; ?>
                
                <div class="loading" style="display:none;">
                    <div class="horace-thinking">
                        <div class="horace-avatar">🤖</div>
                        <p>HORACE is analyzing your responses and creating your personalized business strategy...</p>
                    </div>
                </div>
            </form>
        </div>

        <style>
        .vortex-horace-quiz { max-width: 800px; margin: 0 auto; padding: 20px; }
        .quiz-header { text-align: center; margin-bottom: 40px; }
        .horace-intro { display: flex; align-items: flex-start; gap: 20px; background: #f8f9fa; padding: 20px; border-radius: 12px; margin: 20px 0; }
        .horace-avatar { font-size: 3em; }
        .horace-message h3 { margin-top: 0; color: #007cba; }
        .quiz-progress { margin-bottom: 30px; }
        .progress-bar { background: #e0e0e0; height: 8px; border-radius: 4px; overflow: hidden; }
        .progress-fill { background: #007cba; height: 100%; transition: width 0.3s ease; }
        .progress-text { display: block; text-align: center; margin-top: 10px; font-weight: bold; }
        .question-section { display: none; }
        .question-section.active { display: block; }
        .question-section h3 { color: #007cba; margin-bottom: 10px; }
        .question-section h4 { margin-bottom: 20px; font-size: 1.2em; }
        .question-options { margin-bottom: 30px; }
        .option-label { display: block; margin-bottom: 15px; padding: 15px; border: 2px solid #e0e0e0; border-radius: 8px; cursor: pointer; transition: all 0.3s ease; }
        .option-label:hover { border-color: #007cba; background: #f0f8ff; }
        .option-label input[type="radio"] { margin-right: 12px; }
        .option-label input[type="radio"]:checked + .option-text { font-weight: bold; }
        .option-label:has(input[type="radio"]:checked) { border-color: #007cba; background: #f0f8ff; }
        .question-navigation { display: flex; gap: 15px; justify-content: space-between; }
        .btn { padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; transition: all 0.3s ease; }
        .btn-primary { background: #007cba; color: white; }
        .btn-primary:hover { background: #005a87; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #545b62; }
        .horace-thinking { text-align: center; padding: 40px; }
        .horace-thinking .horace-avatar { font-size: 4em; margin-bottom: 20px; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            let currentQuestion = 1;
            const totalQuestions = <?php echo count($this->quiz_questions); ?>;
            
            function updateProgress() {
                const progress = (currentQuestion / totalQuestions) * 100;
                $('.progress-fill').css('width', progress + '%');
                $('.progress-text').text('Question ' + currentQuestion + ' of ' + totalQuestions);
            }
            
            function showQuestion(questionNum) {
                $('.question-section').removeClass('active');
                $('.question-section[data-question="' + questionNum + '"]').addClass('active');
                updateProgress();
            }
            
            $('.next-question').click(function() {
                const currentSection = $('.question-section.active');
                const selectedOption = currentSection.find('input[type="radio"]:checked');
                
                if (selectedOption.length === 0) {
                    alert('Please select an answer before continuing.');
                    return;
                }
                
                if (currentQuestion < totalQuestions) {
                    currentQuestion++;
                    showQuestion(currentQuestion);
                }
            });
            
            $('.prev-question').click(function() {
                if (currentQuestion > 1) {
                    currentQuestion--;
                    showQuestion(currentQuestion);
                }
            });
            
            $('#horace-quiz-form').submit(function(e) {
                e.preventDefault();
                
                // Check if all questions are answered
                let allAnswered = true;
                $('.question-section').each(function() {
                    if ($(this).find('input[type="radio"]:checked').length === 0) {
                        allAnswered = false;
                    }
                });
                
                if (!allAnswered) {
                    alert('Please answer all questions before submitting.');
                    return;
                }
                
                $('.question-section').hide();
                $('.loading').show();
                
                $.ajax({
                    url: vortexAjax.ajaxUrl,
                    type: 'POST',
                    data: $(this).serialize() + '&action=vortex_submit_horace_quiz',
                    success: function(response) {
                        if (response.success) {
                            window.location.reload();
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle quiz submission
     */
    public function handle_quiz_submission() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['vortex_horace_nonce'], 'vortex_horace_quiz')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Security verification failed')));
        }
        
        if (!is_user_logged_in()) {
            wp_die(json_encode(array('success' => false, 'message' => 'You must be logged in')));
        }
        
        $user_id = get_current_user_id();
        
        // Process each answer
        global $wpdb;
        
        foreach ($this->quiz_questions as $question_key => $question_data) {
            if (isset($_POST[$question_key])) {
                $selected_option = sanitize_text_field($_POST[$question_key]);
                
                $wpdb->replace(
                    $this->quiz_responses_table,
                    array(
                        'user_id' => $user_id,
                        'question_key' => $question_key,
                        'selected_option' => $selected_option
                    ),
                    array('%d', '%s', '%s')
                );
            }
        }
        
        // Calculate business score and profile
        $business_profile = $this->calculate_business_profile($user_id);
        $business_score = $this->calculate_business_score($user_id);
        $recommendations = $this->generate_horace_recommendations($user_id, $business_profile);
        
        // Update completion status
        $wpdb->update(
            $this->quiz_responses_table,
            array(
                'quiz_completed' => 1,
                'business_score' => $business_score,
                'business_profile' => $business_profile,
                'recommendations' => json_encode($recommendations)
            ),
            array('user_id' => $user_id),
            array('%d', '%d', '%s', '%s'),
            array('%d')
        );
        
        // Update user meta
        update_user_meta($user_id, 'vortex_horace_quiz_completed', true);
        update_user_meta($user_id, 'vortex_business_profile', $business_profile);
        update_user_meta($user_id, 'vortex_business_score', $business_score);
        
        // Send completion email
        $this->send_quiz_completion_email($user_id, $business_profile, $recommendations);
        
        wp_die(json_encode(array(
            'success' => true,
            'message' => 'Quiz completed successfully',
            'data' => array(
                'business_profile' => $business_profile,
                'business_score' => $business_score
            )
        )));
    }

    /**
     * Calculate business profile based on responses
     */
    private function calculate_business_profile($user_id) {
        global $wpdb;
        
        $responses = $wpdb->get_results($wpdb->prepare(
            "SELECT question_key, selected_option FROM {$this->quiz_responses_table} WHERE user_id = %d",
            $user_id
        ), OBJECT_K);
        
        // Analyze patterns in responses to determine profile
        $business_goal = isset($responses['business_goals']) ? $responses['business_goals']->selected_option : '';
        $experience = isset($responses['business_experience']) ? $responses['business_experience']->selected_option : '';
        $financial_goals = isset($responses['financial_goals']) ? $responses['financial_goals']->selected_option : '';
        $time_commitment = isset($responses['time_commitment']) ? $responses['time_commitment']->selected_option : '';
        
        // Define business profiles
        if (in_array($business_goal, ['creative_expression']) && 
            in_array($experience, ['complete_beginner', 'occasional_sales']) &&
            in_array($financial_goals, ['hobby_level', 'supplemental'])) {
            return 'Creative Explorer';
        }
        
        if (in_array($business_goal, ['supplemental_income', 'full_time_career']) &&
            in_array($experience, ['regular_sales', 'established_business']) &&
            in_array($financial_goals, ['part_time', 'full_time_modest'])) {
            return 'Emerging Professional';
        }
        
        if (in_array($business_goal, ['gallery_representation', 'commercial_success']) &&
            in_array($experience, ['multi_revenue', 'entrepreneur']) &&
            in_array($financial_goals, ['full_time_comfortable', 'high_earning'])) {
            return 'Established Artist';
        }
        
        if (in_array($business_goal, ['digital_marketplace']) &&
            in_array($time_commitment, ['intensive', 'full_time'])) {
            return 'Digital Pioneer';
        }
        
        if (in_array($business_goal, ['teaching_mentoring']) &&
            in_array($experience, ['established_business', 'multi_revenue'])) {
            return 'Artistic Educator';
        }
        
        // Default profile
        return 'Developing Artist';
    }

    /**
     * Calculate business score
     */
    private function calculate_business_score($user_id) {
        global $wpdb;
        
        $responses = $wpdb->get_results($wpdb->prepare(
            "SELECT question_key, selected_option FROM {$this->quiz_responses_table} WHERE user_id = %d",
            $user_id
        ), OBJECT_K);
        
        $score = 0;
        
        // Scoring logic based on business readiness and potential
        $scoring_matrix = array(
            'business_experience' => array(
                'entrepreneur' => 25,
                'business_background' => 20,
                'established_business' => 18,
                'multi_revenue' => 15,
                'regular_sales' => 12,
                'occasional_sales' => 8,
                'complete_beginner' => 5
            ),
            'marketing_strategy' => array(
                'professional_representation' => 25,
                'digital_marketing' => 20,
                'email_marketing' => 18,
                'website_portfolio' => 15,
                'social_media_strategic' => 12,
                'local_networking' => 10,
                'social_media_basic' => 8,
                'no_marketing' => 0
            ),
            'time_commitment' => array(
                'full_time' => 20,
                'intensive' => 18,
                'serious' => 15,
                'moderate' => 12,
                'casual' => 8,
                'minimal' => 5
            ),
            'investment_willingness' => array(
                'premium' => 20,
                'aggressive' => 18,
                'serious' => 15,
                'moderate' => 12,
                'conservative' => 8,
                'minimal' => 5
            )
        );
        
        foreach ($scoring_matrix as $question_key => $options) {
            if (isset($responses[$question_key])) {
                $selected = $responses[$question_key]->selected_option;
                if (isset($options[$selected])) {
                    $score += $options[$selected];
                }
            }
        }
        
        return min($score, 100); // Cap at 100
    }

    /**
     * Generate HORACE recommendations
     */
    private function generate_horace_recommendations($user_id, $business_profile) {
        $education_tier = get_user_meta($user_id, 'vortex_education_tier', true);
        
        $recommendations = array(
            'profile_analysis' => "Based on your responses, you align with the '{$business_profile}' profile.",
            'immediate_actions' => array(),
            'tier_specific_resources' => array(),
            'growth_strategies' => array(),
            'success_metrics' => array()
        );
        
        // Profile-specific recommendations
        switch ($business_profile) {
            case 'Creative Explorer':
                $recommendations['immediate_actions'] = array(
                    'Focus on developing your unique artistic voice',
                    'Document your creative process for social media',
                    'Start with local art communities and events',
                    'Price your work accessibly to build confidence'
                );
                break;
                
            case 'Emerging Professional':
                $recommendations['immediate_actions'] = array(
                    'Develop a consistent pricing strategy',
                    'Create a professional portfolio website',
                    'Establish regular social media presence',
                    'Track your sales and customer data'
                );
                break;
                
            case 'Established Artist':
                $recommendations['immediate_actions'] = array(
                    'Expand into new market segments',
                    'Develop passive income streams',
                    'Consider gallery representation',
                    'Build email marketing campaigns'
                );
                break;
                
            case 'Digital Pioneer':
                $recommendations['immediate_actions'] = array(
                    'Explore NFT and blockchain opportunities',
                    'Leverage AI tools for creation and marketing',
                    'Build a strong online personal brand',
                    'Participate in TOLA Masterwork collaborations'
                );
                break;
        }
        
        // Tier-specific resources
        if ($education_tier === 'full_time_student') {
            $recommendations['tier_specific_resources'] = array(
                'Access to advanced business workshops',
                'Monthly mentor sessions for strategy guidance',
                'TOLA Masterwork collaboration opportunities',
                'Community networking events'
            );
        } elseif ($education_tier === 'masters') {
            $recommendations['tier_specific_resources'] = array(
                'VIP mentorship program access',
                'Portfolio review sessions',
                'Advanced master classes',
                'TOLA Masterwork co-creation privileges',
                'Priority placement in gallery partnerships'
            );
        }
        
        return $recommendations;
    }

    /**
     * Render quiz results
     */
    private function render_quiz_results($user_id) {
        $business_profile = get_user_meta($user_id, 'vortex_business_profile', true);
        $business_score = get_user_meta($user_id, 'vortex_business_score', true);
        
        global $wpdb;
        $recommendations_data = $wpdb->get_var($wpdb->prepare(
            "SELECT recommendations FROM {$this->quiz_responses_table} WHERE user_id = %d AND quiz_completed = 1 LIMIT 1",
            $user_id
        ));
        
        $recommendations = $recommendations_data ? json_decode($recommendations_data, true) : array();
        
        ob_start();
        ?>
        <div class="horace-results">
            <div class="results-header">
                <div class="horace-avatar">🤖</div>
                <h2>Your HORACE Business Strategy Analysis</h2>
                <p>Completed on <?php echo date('F j, Y'); ?></p>
            </div>
            
            <div class="business-profile-card">
                <h3>Your Business Profile</h3>
                <div class="profile-badge"><?php echo esc_html($business_profile); ?></div>
                <div class="business-score">
                    Business Readiness Score: <strong><?php echo $business_score; ?>/100</strong>
                </div>
            </div>
            
            <?php if (!empty($recommendations)): ?>
                <div class="recommendations-section">
                    <h3>Your Personalized Strategy</h3>
                    
                    <?php if (isset($recommendations['immediate_actions'])): ?>
                        <div class="recommendation-block">
                            <h4>Immediate Action Items</h4>
                            <ul>
                                <?php foreach ($recommendations['immediate_actions'] as $action): ?>
                                    <li><?php echo esc_html($action); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($recommendations['tier_specific_resources'])): ?>
                        <div class="recommendation-block">
                            <h4>Your Tier Benefits</h4>
                            <ul>
                                <?php foreach ($recommendations['tier_specific_resources'] as $resource): ?>
                                    <li><?php echo esc_html($resource); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="next-steps">
                <h3>Next Steps</h3>
                <div class="action-buttons">
                    <a href="/dashboard/" class="btn btn-primary">Go to Dashboard</a>
                    <a href="/workshops/" class="btn btn-secondary">Browse Workshops</a>
                    <a href="/community/" class="btn btn-secondary">Join Community</a>
                </div>
            </div>
        </div>
        
        <style>
        .horace-results { max-width: 800px; margin: 0 auto; padding: 20px; }
        .results-header { text-align: center; margin-bottom: 30px; }
        .results-header .horace-avatar { font-size: 4em; margin-bottom: 15px; }
        .business-profile-card { background: linear-gradient(135deg, #007cba, #005a87); color: white; padding: 30px; border-radius: 12px; text-align: center; margin-bottom: 30px; }
        .profile-badge { background: rgba(255,255,255,0.2); display: inline-block; padding: 10px 20px; border-radius: 20px; font-size: 1.2em; font-weight: bold; margin: 15px 0; }
        .business-score { font-size: 1.1em; }
        .recommendations-section { margin-bottom: 30px; }
        .recommendation-block { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .recommendation-block h4 { color: #007cba; margin-bottom: 15px; }
        .recommendation-block ul { margin-left: 20px; }
        .recommendation-block li { margin-bottom: 8px; }
        .next-steps { text-align: center; }
        .action-buttons { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; }
        .btn-primary { background: #007cba; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Send quiz completion email
     */
    private function send_quiz_completion_email($user_id, $business_profile, $recommendations) {
        $user = get_user_by('ID', $user_id);
        
        if (!$user) {
            return;
        }
        
        $subject = 'Your HORACE Business Strategy Analysis is Complete!';
        
        $message = "
        <h2>Your Business Strategy Analysis</h2>
        <p>Dear {$user->display_name},</p>
        
        <p>HORACE has completed the analysis of your business strategy quiz. Here are your personalized results:</p>
        
        <h3>🎯 Your Business Profile: <strong>{$business_profile}</strong></h3>
        
        <h3>🚀 Immediate Action Items:</h3>
        <ul>";
        
        if (isset($recommendations['immediate_actions'])) {
            foreach ($recommendations['immediate_actions'] as $action) {
                $message .= "<li>{$action}</li>";
            }
        }
        
        $message .= "</ul>
        
        <p>Your complete strategy analysis is available in your dashboard, including tier-specific resources and growth recommendations.</p>
        
        <p><a href='" . home_url('/dashboard/') . "' style='background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;'>View Complete Analysis</a></p>
        
        <p>HORACE will continue to provide strategic guidance as you progress through your artistic journey.</p>
        
        <p>Best regards,<br>HORACE - Your AI Business Strategist<br>VORTEX ARTEC Team</p>
        ";
        
        wp_mail($user->user_email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'vortex-ai',
            'HORACE Quiz Results',
            'HORACE Quiz',
            'manage_options',
            'vortex-horace-quiz',
            array($this, 'admin_page')
        );
    }

    /**
     * Admin page
     */
    public function admin_page() {
        global $wpdb;
        
        $completed_quizzes = $wpdb->get_results("
            SELECT DISTINCT r.user_id, u.display_name, u.user_email, r.business_profile, r.business_score, r.created_at
            FROM {$this->quiz_responses_table} r
            JOIN {$wpdb->users} u ON r.user_id = u.ID
            WHERE r.quiz_completed = 1
            ORDER BY r.created_at DESC
        ");
        
        echo '<div class="wrap">';
        echo '<h1>HORACE Business Quiz Results</h1>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Artist</th><th>Business Profile</th><th>Score</th><th>Completed</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($completed_quizzes as $quiz) {
            echo '<tr>';
            echo '<td>' . esc_html($quiz->display_name) . '<br><small>' . esc_html($quiz->user_email) . '</small></td>';
            echo '<td>' . esc_html($quiz->business_profile) . '</td>';
            echo '<td>' . $quiz->business_score . '/100</td>';
            echo '<td>' . date('M j, Y', strtotime($quiz->created_at)) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
    }
} 