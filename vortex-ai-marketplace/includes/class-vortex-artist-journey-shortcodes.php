<?php
/**
 * The Artist Journey shortcodes class.
 *
 * @since      3.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_Artist_Journey_Shortcodes {

    /**
     * Initialize the class and set its properties.
     *
     * @since    3.0.0
     */
    public function __construct() {
        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_ajax_vortex_submit_role_quiz', array($this, 'handle_role_quiz_submission'));
        add_action('wp_ajax_vortex_submit_horas_quiz', array($this, 'handle_horas_quiz_submission'));
        add_action('wp_ajax_vortex_upload_seed_art', array($this, 'handle_seed_art_upload'));
        add_action('wp_ajax_vortex_complete_milestone', array($this, 'handle_milestone_completion'));
        
        // Public AJAX handlers for logged-in users
        add_action('wp_ajax_nopriv_vortex_submit_role_quiz', array($this, 'redirect_to_login'));
        add_action('wp_ajax_nopriv_vortex_submit_horas_quiz', array($this, 'redirect_to_login'));
        add_action('wp_ajax_nopriv_vortex_upload_seed_art', array($this, 'redirect_to_login'));
    }

    /**
     * Register all shortcodes
     *
     * @since    3.0.0
     */
    public function register_shortcodes() {
        add_shortcode('vortex_role_quiz', array($this, 'render_role_quiz'));
        add_shortcode('vortex_horas_quiz', array($this, 'render_horas_quiz'));
        add_shortcode('vortex_seed_upload', array($this, 'render_seed_upload'));
        add_shortcode('vortex_milestones', array($this, 'render_milestones'));
        add_shortcode('vortex_artist_journey', array($this, 'render_full_journey'));
    }

    /**
     * Render role quiz shortcode
     *
     * @since    3.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string            Shortcode output
     */
    public function render_role_quiz($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Discover Your Artist Role',
            'subtitle' => 'Take our quiz to find your perfect plan'
        ), $atts);

        if (!is_user_logged_in()) {
            return '<div class="vortex-login-required"><p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to take the quiz.</p></div>';
        }

        $user_id = get_current_user_id();
        $completed_quiz = get_user_meta($user_id, 'vortex_role_quiz_completed', true);

        if ($completed_quiz) {
            $quiz_result = get_user_meta($user_id, 'vortex_role_quiz_result', true);
            return $this->render_quiz_completed($quiz_result);
        }

        ob_start();
        ?>
        <div class="vortex-role-quiz-container">
            <div class="quiz-header">
                <h2><?php echo esc_html($atts['title']); ?></h2>
                <p><?php echo esc_html($atts['subtitle']); ?></p>
            </div>
            
            <form id="vortex-role-quiz-form" class="vortex-quiz-form">
                <?php wp_nonce_field('vortex_role_quiz', 'quiz_nonce'); ?>
                
                <div class="quiz-question" data-question="1">
                    <h3>What's your primary artistic focus?</h3>
                    <div class="quiz-options">
                        <label><input type="radio" name="q1" value="digital"> Digital art and illustrations</label>
                        <label><input type="radio" name="q1" value="traditional"> Traditional art techniques</label>
                        <label><input type="radio" name="q1" value="mixed"> Mixed media and experimentation</label>
                        <label><input type="radio" name="q1" value="commercial"> Commercial and business art</label>
                    </div>
                </div>

                <div class="quiz-question" data-question="2">
                    <h3>How many hours per week do you dedicate to art?</h3>
                    <div class="quiz-options">
                        <label><input type="radio" name="q2" value="casual"> 1-5 hours (Hobby)</label>
                        <label><input type="radio" name="q2" value="regular"> 6-20 hours (Regular practice)</label>
                        <label><input type="radio" name="q2" value="serious"> 21-40 hours (Serious artist)</label>
                        <label><input type="radio" name="q2" value="professional"> 40+ hours (Professional)</label>
                    </div>
                </div>

                <div class="quiz-question" data-question="3">
                    <h3>What's your experience with AI art tools?</h3>
                    <div class="quiz-options">
                        <label><input type="radio" name="q3" value="none"> No experience</label>
                        <label><input type="radio" name="q3" value="beginner"> Some experimentation</label>
                        <label><input type="radio" name="q3" value="intermediate"> Regular use</label>
                        <label><input type="radio" name="q3" value="advanced"> Advanced techniques</label>
                    </div>
                </div>

                <div class="quiz-question" data-question="4">
                    <h3>What are your goals with VORTEX?</h3>
                    <div class="quiz-options">
                        <label><input type="radio" name="q4" value="explore"> Explore AI art capabilities</label>
                        <label><input type="radio" name="q4" value="improve"> Improve my artistic skills</label>
                        <label><input type="radio" name="q4" value="monetize"> Monetize my art</label>
                        <label><input type="radio" name="q4" value="business"> Build an art business</label>
                    </div>
                </div>

                <div class="quiz-navigation">
                    <button type="button" id="quiz-prev" class="btn-secondary" style="display:none;">Previous</button>
                    <button type="button" id="quiz-next" class="btn-primary">Next</button>
                    <button type="submit" id="quiz-submit" class="btn-primary" style="display:none;">Complete Quiz</button>
                </div>
            </form>
            
            <div id="quiz-results" class="quiz-results" style="display:none;"></div>
        </div>

        <style>
        .vortex-role-quiz-container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .quiz-question { display: none; margin: 20px 0; }
        .quiz-question.active { display: block; }
        .quiz-question:first-child { display: block; }
        .quiz-options { margin: 15px 0; }
        .quiz-options label { display: block; margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px; cursor: pointer; }
        .quiz-options label:hover { background: #f5f5f5; }
        .quiz-options input[type="radio"] { margin-right: 10px; }
        .quiz-navigation { margin: 30px 0; text-align: center; }
        .quiz-navigation button { margin: 0 10px; padding: 10px 20px; }
        .btn-primary { background: #007cba; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn-secondary { background: #666; color: white; border: none; border-radius: 5px; cursor: pointer; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            let currentQuestion = 1;
            const totalQuestions = 4;
            
            function showQuestion(n) {
                $('.quiz-question').removeClass('active');
                $('[data-question="' + n + '"]').addClass('active');
                
                $('#quiz-prev').toggle(n > 1);
                $('#quiz-next').toggle(n < totalQuestions);
                $('#quiz-submit').toggle(n === totalQuestions);
            }
            
            $('#quiz-next').click(function() {
                if (currentQuestion < totalQuestions) {
                    currentQuestion++;
                    showQuestion(currentQuestion);
                }
            });
            
            $('#quiz-prev').click(function() {
                if (currentQuestion > 1) {
                    currentQuestion--;
                    showQuestion(currentQuestion);
                }
            });
            
            $('#vortex-role-quiz-form').submit(function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: vortexAjax.ajaxUrl,
                    type: 'POST',
                    data: $(this).serialize() + '&action=vortex_submit_role_quiz',
                    success: function(response) {
                        if (response.success) {
                            $('#quiz-results').html(response.data.html).show();
                            $('#vortex-role-quiz-form').hide();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Render HORAS quiz shortcode
     *
     * @since    3.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string            Shortcode output
     */
    public function render_horas_quiz($atts) {
        $atts = shortcode_atts(array(
            'title' => 'HORACE Business Strategy Quiz'
        ), $atts);

        if (!is_user_logged_in()) {
            return '<div class="vortex-login-required"><p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to access HORACE.</p></div>';
        }

        $user_id = get_current_user_id();
        $user_plan = get_user_meta($user_id, 'vortex_plan', true);
        
        if (!in_array($user_plan, array('artist-pro', 'artist-studio'))) {
            return '<div class="vortex-upgrade-required"><p>HORACE Business Quiz requires Pro or Studio plan. <a href="/pricing">Upgrade now</a></p></div>';
        }

        ob_start();
        ?>
        <div class="vortex-horas-quiz-container">
            <div class="quiz-header">
                <h2><?php echo esc_html($atts['title']); ?></h2>
                <p>Get personalized business advice from HORACE AI</p>
            </div>
            
            <form id="vortex-horas-quiz-form" class="vortex-quiz-form">
                <?php wp_nonce_field('vortex_horas_quiz', 'horas_nonce'); ?>
                
                <div class="quiz-question">
                    <h3>What's your current business stage?</h3>
                    <select name="business_stage" required>
                        <option value="">Select stage...</option>
                        <option value="idea">Just an idea</option>
                        <option value="starting">Starting out</option>
                        <option value="growing">Growing business</option>
                        <option value="established">Established business</option>
                    </select>
                </div>

                <div class="quiz-question">
                    <h3>What's your monthly art income goal?</h3>
                    <select name="income_goal" required>
                        <option value="">Select goal...</option>
                        <option value="0-500">$0 - $500</option>
                        <option value="500-2000">$500 - $2,000</option>
                        <option value="2000-5000">$2,000 - $5,000</option>
                        <option value="5000+">$5,000+</option>
                    </select>
                </div>

                <div class="quiz-question">
                    <h3>What's your biggest business challenge?</h3>
                    <textarea name="challenge" placeholder="Describe your main challenge..." rows="4" required></textarea>
                </div>

                <button type="submit" class="btn-primary">Get HORACE Analysis</button>
            </form>
            
            <div id="horas-results" class="horas-results" style="display:none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render seed upload shortcode
     *
     * @since    3.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string            Shortcode output
     */
    public function render_seed_upload($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Upload Your Seed Artwork',
            'max_files' => '5'
        ), $atts);

        if (!is_user_logged_in()) {
            return '<div class="vortex-login-required"><p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to upload artwork.</p></div>';
        }

        ob_start();
        ?>
        <div class="vortex-seed-upload-container">
            <div class="upload-header">
                <h2><?php echo esc_html($atts['title']); ?></h2>
                <p>Upload your artwork to train AI and participate in TOLA-ART generation</p>
            </div>
            
            <div id="vortex-upload-area" class="upload-area">
                <div class="upload-zone" id="upload-zone">
                    <div class="upload-icon">📁</div>
                    <p>Drag and drop images here or <span class="upload-link">browse files</span></p>
                    <input type="file" id="seed-file-input" multiple accept="image/*" style="display:none;">
                    <div class="upload-requirements">
                        <small>• JPG, PNG, GIF supported<br>• Max <?php echo esc_attr($atts['max_files']); ?> files<br>• 10MB per file</small>
                    </div>
                </div>
            </div>
            
            <div id="upload-progress" class="upload-progress" style="display:none;">
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <div class="progress-text">Uploading...</div>
            </div>
            
            <div id="uploaded-files" class="uploaded-files"></div>
        </div>

        <style>
        .vortex-seed-upload-container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .upload-area { margin: 20px 0; }
        .upload-zone { 
            border: 2px dashed #ddd; 
            border-radius: 10px; 
            padding: 40px; 
            text-align: center; 
            transition: border-color 0.3s;
            cursor: pointer;
        }
        .upload-zone:hover, .upload-zone.drag-over { border-color: #007cba; }
        .upload-icon { font-size: 48px; margin-bottom: 10px; }
        .upload-link { color: #007cba; text-decoration: underline; cursor: pointer; }
        .progress-bar { 
            width: 100%; 
            height: 20px; 
            background: #f0f0f0; 
            border-radius: 10px; 
            overflow: hidden; 
        }
        .progress-fill { 
            height: 100%; 
            background: #007cba; 
            width: 0%; 
            transition: width 0.3s; 
        }
        .uploaded-files { margin: 20px 0; }
        .uploaded-file { 
            display: flex; 
            align-items: center; 
            margin: 10px 0; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
        }
        .file-thumbnail { width: 50px; height: 50px; object-fit: cover; margin-right: 10px; border-radius: 3px; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            const uploadZone = $('#upload-zone');
            const fileInput = $('#seed-file-input');
            const maxFiles = <?php echo intval($atts['max_files']); ?>;
            let uploadedCount = 0;
            
            // Click to browse
            uploadZone.click(() => fileInput.click());
            $('.upload-link').click((e) => {
                e.preventDefault();
                fileInput.click();
            });
            
            // Drag and drop
            uploadZone.on('dragover dragenter', function(e) {
                e.preventDefault();
                $(this).addClass('drag-over');
            });
            
            uploadZone.on('dragleave', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');
            });
            
            uploadZone.on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');
                handleFiles(e.originalEvent.dataTransfer.files);
            });
            
            fileInput.change(function() {
                handleFiles(this.files);
            });
            
            function handleFiles(files) {
                if (uploadedCount >= maxFiles) {
                    alert('Maximum ' + maxFiles + ' files allowed');
                    return;
                }
                
                Array.from(files).slice(0, maxFiles - uploadedCount).forEach(uploadFile);
            }
            
            function uploadFile(file) {
                if (!file.type.startsWith('image/')) {
                    alert('Only image files are allowed');
                    return;
                }
                
                if (file.size > 10 * 1024 * 1024) {
                    alert('File size must be less than 10MB');
                    return;
                }
                
                const formData = new FormData();
                formData.append('file', file);
                formData.append('action', 'vortex_upload_seed_art');
                formData.append('nonce', vortexAjax.nonce);
                
                $('#upload-progress').show();
                updateProgress(0);
                
                $.ajax({
                    url: vortexAjax.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    xhr: function() {
                        const xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener('progress', function(e) {
                            if (e.lengthComputable) {
                                updateProgress((e.loaded / e.total) * 100);
                            }
                        });
                        return xhr;
                    },
                    success: function(response) {
                        $('#upload-progress').hide();
                        if (response.success) {
                            addUploadedFile(response.data);
                            uploadedCount++;
                        } else {
                            alert('Upload failed: ' + response.data);
                        }
                    },
                    error: function() {
                        $('#upload-progress').hide();
                        alert('Upload failed');
                    }
                });
            }
            
            function updateProgress(percent) {
                $('.progress-fill').css('width', percent + '%');
                $('.progress-text').text('Uploading... ' + Math.round(percent) + '%');
            }
            
            function addUploadedFile(fileData) {
                const fileHtml = `
                    <div class="uploaded-file">
                        <img src="${fileData.thumbnail}" class="file-thumbnail" alt="Uploaded file">
                        <div class="file-info">
                            <strong>${fileData.filename}</strong><br>
                            <small>Uploaded to S3 • ${fileData.size}</small>
                        </div>
                        <div class="file-actions" style="margin-left: auto;">
                            <span class="success">✓ Uploaded</span>
                        </div>
                    </div>
                `;
                $('#uploaded-files').append(fileHtml);
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Render milestones shortcode
     *
     * @since    3.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string            Shortcode output
     */
    public function render_milestones($atts) {
        if (!is_user_logged_in()) {
            return '<div class="vortex-login-required"><p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to view milestones.</p></div>';
        }

        $user_id = get_current_user_id();
        $milestones = $this->get_user_milestones($user_id);

        ob_start();
        ?>
        <div class="vortex-milestones-container">
            <h2>Your Artist Journey Milestones</h2>
            
            <div class="milestones-progress">
                <?php foreach ($milestones as $milestone): ?>
                    <div class="milestone <?php echo $milestone['completed'] ? 'completed' : 'pending'; ?>">
                        <div class="milestone-icon">
                            <?php echo $milestone['completed'] ? '✅' : '⏳'; ?>
                        </div>
                        <div class="milestone-content">
                            <h3><?php echo esc_html($milestone['title']); ?></h3>
                            <p><?php echo esc_html($milestone['description']); ?></p>
                            <?php if (!$milestone['completed'] && $milestone['action_url']): ?>
                                <a href="<?php echo esc_url($milestone['action_url']); ?>" class="milestone-action">
                                    <?php echo esc_html($milestone['action_text']); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="milestone-reward">
                            <strong>+<?php echo $milestone['reward']; ?> TOLA</strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <style>
        .vortex-milestones-container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .milestone { 
            display: flex; 
            align-items: center; 
            margin: 20px 0; 
            padding: 20px; 
            border: 1px solid #ddd; 
            border-radius: 10px; 
            transition: all 0.3s;
        }
        .milestone.completed { background: #f0f8f0; border-color: #4CAF50; }
        .milestone-icon { font-size: 24px; margin-right: 20px; }
        .milestone-content { flex: 1; }
        .milestone-reward { 
            background: #007cba; 
            color: white; 
            padding: 5px 10px; 
            border-radius: 5px; 
            font-size: 12px;
        }
        .milestone-action { 
            display: inline-block; 
            margin-top: 10px; 
            padding: 8px 16px; 
            background: #007cba; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle role quiz submission
     *
     * @since    3.0.0
     */
    public function handle_role_quiz_submission() {
        check_ajax_referer('vortex_role_quiz', 'quiz_nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }

        $answers = array(
            'q1' => sanitize_text_field($_POST['q1']),
            'q2' => sanitize_text_field($_POST['q2']),
            'q3' => sanitize_text_field($_POST['q3']),
            'q4' => sanitize_text_field($_POST['q4'])
        );

        // Calculate recommended plan
        $recommended_plan = $this->calculate_recommended_plan($answers);

        // Save quiz results
        update_user_meta($user_id, 'vortex_role_quiz_completed', time());
        update_user_meta($user_id, 'vortex_role_quiz_answers', $answers);
        update_user_meta($user_id, 'vortex_role_quiz_result', $recommended_plan);

        // Award completion bonus
        $this->award_milestone_reward($user_id, 'role_quiz_completed', 5);

        $html = $this->render_quiz_result($recommended_plan, $answers);

        wp_send_json_success(array('html' => $html));
    }

    /**
     * Calculate recommended plan based on quiz answers
     *
     * @since    3.0.0
     * @param    array    $answers    Quiz answers
     * @return   string              Recommended plan
     */
    private function calculate_recommended_plan($answers) {
        $score = 0;

        // Scoring logic
        switch ($answers['q2']) {
            case 'casual': $score += 1; break;
            case 'regular': $score += 2; break;
            case 'serious': $score += 3; break;
            case 'professional': $score += 4; break;
        }

        switch ($answers['q3']) {
            case 'none': $score += 1; break;
            case 'beginner': $score += 2; break;
            case 'intermediate': $score += 3; break;
            case 'advanced': $score += 4; break;
        }

        if (in_array($answers['q4'], array('monetize', 'business'))) {
            $score += 2;
        }

        // Determine plan
        if ($score <= 4) return 'artist-starter';
        if ($score <= 7) return 'artist-pro';
        return 'artist-studio';
    }

    /**
     * Render quiz completion result
     *
     * @since    3.0.0
     * @param    string    $result    Quiz result
     * @return   string               HTML output
     */
    private function render_quiz_completed($result) {
        $plan_info = $this->get_plan_info($result);
        
        return '<div class="quiz-completed">
            <h3>Quiz Already Completed</h3>
            <p>Your recommended plan: <strong>' . $plan_info['name'] . '</strong></p>
            <a href="/pricing" class="btn-primary">View Plans</a>
        </div>';
    }

    /**
     * Render quiz result
     *
     * @since    3.0.0
     * @param    string    $plan      Recommended plan
     * @param    array     $answers   Quiz answers
     * @return   string               HTML output
     */
    private function render_quiz_result($plan, $answers) {
        $plan_info = $this->get_plan_info($plan);
        
        return '<div class="quiz-result-container">
            <div class="result-header">
                <h3>🎉 Quiz Complete!</h3>
                <p>Based on your answers, we recommend:</p>
            </div>
            
            <div class="recommended-plan">
                <h2>' . $plan_info['name'] . '</h2>
                <div class="plan-price">$' . $plan_info['price'] . '/month</div>
                <ul class="plan-features">
                    ' . implode('', array_map(function($feature) {
                        return '<li>✓ ' . $feature . '</li>';
                    }, $plan_info['features'])) . '
                </ul>
                <a href="/pricing" class="btn-primary">Get Started</a>
            </div>
            
            <div class="milestone-reward">
                <strong>🎁 +5 TOLA Bonus</strong> for completing the quiz!
            </div>
        </div>';
    }

    /**
     * Get plan information
     *
     * @since    3.0.0
     * @param    string    $plan_key    Plan key
     * @return   array                  Plan information
     */
    private function get_plan_info($plan_key) {
        $plans = array(
            'artist-starter' => array(
                'name' => 'Artist Starter',
                'price' => 29,
                'features' => array('Basic AI generation', 'Community support', 'Marketplace access')
            ),
            'artist-pro' => array(
                'name' => 'Artist Pro', 
                'price' => 59,
                'features' => array('Advanced AI tools', 'HORACE business quiz', 'Priority support')
            ),
            'artist-studio' => array(
                'name' => 'Artist Studio',
                'price' => 99,
                'features' => array('Unlimited generation', 'All AI agents', 'Studio features')
            )
        );

        return isset($plans[$plan_key]) ? $plans[$plan_key] : $plans['artist-starter'];
    }

    /**
     * Get user milestones
     *
     * @since    3.0.0
     * @param    int    $user_id    User ID
     * @return   array              Milestones array
     */
    private function get_user_milestones($user_id) {
        $completed_milestones = get_user_meta($user_id, 'vortex_completed_milestones', true) ?: array();
        
        $milestones = array(
            array(
                'title' => 'Complete Role Quiz',
                'description' => 'Discover your perfect artist plan',
                'completed' => in_array('role_quiz_completed', $completed_milestones),
                'reward' => 5,
                'action_url' => '#role-quiz',
                'action_text' => 'Take Quiz'
            ),
            array(
                'title' => 'Upload Seed Artwork',
                'description' => 'Share your art to train AI',
                'completed' => in_array('seed_upload_completed', $completed_milestones),
                'reward' => 10,
                'action_url' => '#seed-upload',
                'action_text' => 'Upload Art'
            ),
            array(
                'title' => 'Generate First AI Art',
                'description' => 'Create your first AI-generated artwork',
                'completed' => in_array('first_generation', $completed_milestones),
                'reward' => 15,
                'action_url' => '/generate',
                'action_text' => 'Generate Art'
            ),
            array(
                'title' => 'Subscribe to Plan',
                'description' => 'Upgrade to unlock full features',
                'completed' => get_user_meta($user_id, 'vortex_plan', true) ? true : false,
                'reward' => 25,
                'action_url' => '/pricing',
                'action_text' => 'View Plans'
            )
        );

        return $milestones;
    }

    /**
     * Award milestone reward
     *
     * @since    3.0.0
     * @param    int       $user_id       User ID
     * @param    string    $milestone     Milestone key
     * @param    int       $reward        TOLA reward amount
     */
    private function award_milestone_reward($user_id, $milestone, $reward) {
        $completed_milestones = get_user_meta($user_id, 'vortex_completed_milestones', true) ?: array();
        
        if (!in_array($milestone, $completed_milestones)) {
            $completed_milestones[] = $milestone;
            update_user_meta($user_id, 'vortex_completed_milestones', $completed_milestones);
            
            // Award TOLA tokens
            $wallet = new Vortex_AI_Marketplace_Wallet();
            $wallet->credit_tokens($user_id, $reward);
        }
    }

    /**
     * Handle HORAS quiz submission
     *
     * @since    3.0.0
     */
    public function handle_horas_quiz_submission() {
        check_ajax_referer('vortex_horas_quiz', 'horas_nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }

        // Process HORAS quiz and generate business advice
        // This would integrate with the actual HORACE AI system
        
        wp_send_json_success(array(
            'advice' => 'HORACE analysis will be available in the full implementation.'
        ));
    }

    /**
     * Handle seed art upload
     *
     * @since    3.0.0
     */
    public function handle_seed_art_upload() {
        check_ajax_referer('wp_rest', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }

        if (!isset($_FILES['file'])) {
            wp_send_json_error('No file uploaded');
        }

        $file = $_FILES['file'];
        
        // Validate file
        if (!getimagesize($file['tmp_name'])) {
            wp_send_json_error('Invalid image file');
        }

        // This would integrate with the actual S3 upload system
        // For now, return mock data
        wp_send_json_success(array(
            'filename' => $file['name'],
            'size' => size_format($file['size']),
            'thumbnail' => 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50"><rect width="50" height="50" fill="#ddd"/><text x="25" y="30" text-anchor="middle" font-size="12">IMG</text></svg>'),
            's3_url' => 'https://mock-s3-url.com/file.jpg'
        ));
    }

    /**
     * Redirect non-logged users to login
     *
     * @since    3.0.0
     */
    public function redirect_to_login() {
        wp_send_json_error('Please login to continue');
    }

    /**
     * Render full artist journey
     *
     * @since    3.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string            Full journey HTML
     */
    public function render_full_journey($atts) {
        $atts = shortcode_atts(array(
            'steps' => 'quiz,upload,milestones'
        ), $atts);

        $steps = explode(',', $atts['steps']);
        $output = '<div class="vortex-artist-journey">';

        foreach ($steps as $step) {
            $step = trim($step);
            switch ($step) {
                case 'quiz':
                    $output .= $this->render_role_quiz(array());
                    break;
                case 'horas':
                    $output .= $this->render_horas_quiz(array());
                    break;
                case 'upload':
                    $output .= $this->render_seed_upload(array());
                    break;
                case 'milestones':
                    $output .= $this->render_milestones(array());
                    break;
            }
        }

        $output .= '</div>';
        return $output;
    }
} 