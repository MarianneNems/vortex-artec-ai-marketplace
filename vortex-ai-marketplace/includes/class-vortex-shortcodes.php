<?php
/**
 * Shortcodes for the VORTEX AI Marketplace.
 *
 * @since      2.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */
class Vortex_Shortcodes {

    /**
     * Register all shortcodes.
     *
     * @since    2.0.0
     */
    public function register_shortcodes() {
        add_shortcode('vortex_signup', array($this, 'signup_shortcode'));
        add_shortcode('vortex_generate', array($this, 'generate_shortcode'));
        add_shortcode('vortex_gallery', array($this, 'gallery_shortcode'));
        add_shortcode('vortex_milestones', array($this, 'milestones_shortcode'));
    }

    /**
     * Artist Journey signup shortcode.
     *
     * @since    2.0.0
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function signup_shortcode($atts) {
        $atts = shortcode_atts(array(
            'layout' => 'default',
            'show_plans' => 'true',
            'redirect_url' => '',
        ), $atts);

        if (!is_user_logged_in()) {
            return '<div class="vortex-error">Please log in to access the Artist Journey.</div>';
        }

        ob_start();
        ?>
        <div class="vortex-signup-container" data-layout="<?php echo esc_attr($atts['layout']); ?>">
            <div class="vortex-signup-header">
                <h2><?php _e('Welcome to Your Artist Journey', 'vortex-ai-marketplace'); ?></h2>
                <p><?php _e('Begin your creative journey with AI-powered tools and blockchain technology.', 'vortex-ai-marketplace'); ?></p>
            </div>

            <?php if ($atts['show_plans'] === 'true'): ?>
            <div class="vortex-plans-section">
                <h3><?php _e('Choose Your Plan', 'vortex-ai-marketplace'); ?></h3>
                <div class="vortex-plans-grid" id="vortexPlansGrid">
                    <div class="vortex-loading"><?php _e('Loading plans...', 'vortex-ai-marketplace'); ?></div>
                </div>
            </div>
            <?php endif; ?>

            <div class="vortex-signup-steps">
                <div class="vortex-step" data-step="role-quiz">
                    <h4><?php _e('1. Discover Your Role', 'vortex-ai-marketplace'); ?></h4>
                    <p><?php _e('Take our AI-powered quiz to find your perfect role in the art ecosystem.', 'vortex-ai-marketplace'); ?></p>
                    <button class="vortex-btn vortex-btn-primary" id="startRoleQuiz">
                        <?php _e('Start Role Quiz', 'vortex-ai-marketplace'); ?>
                    </button>
                </div>

                <div class="vortex-step" data-step="wallet-connect">
                    <h4><?php _e('2. Connect Your Wallet', 'vortex-ai-marketplace'); ?></h4>
                    <p><?php _e('Connect your Solana wallet to start earning TOLA tokens.', 'vortex-ai-marketplace'); ?></p>
                    <button class="vortex-btn vortex-btn-secondary" id="connectWallet">
                        <?php _e('Connect Wallet', 'vortex-ai-marketplace'); ?>
                    </button>
                </div>

                <div class="vortex-step" data-step="seed-art">
                    <h4><?php _e('3. Upload Seed Art', 'vortex-ai-marketplace'); ?></h4>
                    <p><?php _e('Upload your initial artwork to train our AI on your unique style.', 'vortex-ai-marketplace'); ?></p>
                    <div class="vortex-upload-area" id="seedArtUpload">
                        <p><?php _e('Drag & drop your artwork here or click to browse', 'vortex-ai-marketplace'); ?></p>
                        <input type="file" id="seedArtFile" accept="image/*" style="display: none;">
                    </div>
                </div>
            </div>

            <div class="vortex-signup-footer">
                <button class="vortex-btn vortex-btn-success" id="completeSignup" disabled>
                    <?php _e('Complete Artist Journey Setup', 'vortex-ai-marketplace'); ?>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AI generation interface shortcode.
     *
     * @since    2.0.0
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function generate_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'full',
            'show_history' => 'true',
            'max_dimensions' => '1024x1024',
        ), $atts);

        if (!is_user_logged_in()) {
            return '<div class="vortex-error">Please log in to access AI generation.</div>';
        }

        ob_start();
        ?>
        <div class="vortex-generate-container" data-style="<?php echo esc_attr($atts['style']); ?>">
            <div class="vortex-generate-header">
                <h2><?php _e('AI Art Generation Studio', 'vortex-ai-marketplace'); ?></h2>
                <p><?php _e('Create stunning artwork with HURAII AI powered by your subscription plan.', 'vortex-ai-marketplace'); ?></p>
            </div>

            <div class="vortex-generate-form">
                <div class="vortex-form-group">
                    <label for="artPrompt"><?php _e('Describe Your Vision', 'vortex-ai-marketplace'); ?></label>
                    <textarea id="artPrompt" placeholder="<?php _e('A serene landscape with mountains and a lake at sunset...', 'vortex-ai-marketplace'); ?>" rows="4"></textarea>
                </div>

                <div class="vortex-form-row">
                    <div class="vortex-form-group">
                        <label for="artStyle"><?php _e('Art Style', 'vortex-ai-marketplace'); ?></label>
                        <select id="artStyle">
                            <option value="realistic"><?php _e('Realistic', 'vortex-ai-marketplace'); ?></option>
                            <option value="abstract"><?php _e('Abstract', 'vortex-ai-marketplace'); ?></option>
                            <option value="impressionist"><?php _e('Impressionist', 'vortex-ai-marketplace'); ?></option>
                            <option value="digital"><?php _e('Digital Art', 'vortex-ai-marketplace'); ?></option>
                        </select>
                    </div>

                    <div class="vortex-form-group">
                        <label for="artDimensions"><?php _e('Dimensions', 'vortex-ai-marketplace'); ?></label>
                        <select id="artDimensions">
                            <option value="512x512">512 x 512</option>
                            <option value="1024x1024" selected>1024 x 1024</option>
                            <option value="1024x1792">1024 x 1792 (Portrait)</option>
                            <option value="1792x1024">1792 x 1024 (Landscape)</option>
                        </select>
                    </div>
                </div>

                <div class="vortex-generate-actions">
                    <button class="vortex-btn vortex-btn-primary" id="generateArt">
                        <?php _e('Generate Artwork', 'vortex-ai-marketplace'); ?>
                    </button>
                    <div class="vortex-generation-limits" id="generationLimits">
                        <span><?php _e('Generations remaining this month:', 'vortex-ai-marketplace'); ?> <strong id="remainingGens">--</strong></span>
                    </div>
                </div>
            </div>

            <div class="vortex-generate-results" id="generationResults">
                <!-- Results will be populated via JavaScript -->
            </div>

            <?php if ($atts['show_history'] === 'true'): ?>
            <div class="vortex-generate-history">
                <h3><?php _e('Recent Generations', 'vortex-ai-marketplace'); ?></h3>
                <div class="vortex-history-grid" id="generationHistory">
                    <div class="vortex-loading"><?php _e('Loading history...', 'vortex-ai-marketplace'); ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * User gallery shortcode.
     *
     * @since    2.0.0
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function gallery_shortcode($atts) {
        $atts = shortcode_atts(array(
            'columns' => '3',
            'show_filters' => 'true',
            'per_page' => '12',
        ), $atts);

        ob_start();
        ?>
        <div class="vortex-gallery-container" data-columns="<?php echo esc_attr($atts['columns']); ?>">
            <div class="vortex-gallery-header">
                <h2><?php _e('Your Art Gallery', 'vortex-ai-marketplace'); ?></h2>
                
                <?php if ($atts['show_filters'] === 'true'): ?>
                <div class="vortex-gallery-filters">
                    <select id="galleryFilter">
                        <option value="all"><?php _e('All Artwork', 'vortex-ai-marketplace'); ?></option>
                        <option value="generated"><?php _e('AI Generated', 'vortex-ai-marketplace'); ?></option>
                        <option value="uploaded"><?php _e('Uploaded', 'vortex-ai-marketplace'); ?></option>
                        <option value="collections"><?php _e('Collections', 'vortex-ai-marketplace'); ?></option>
                    </select>
                    
                    <button class="vortex-btn vortex-btn-secondary" id="createCollection">
                        <?php _e('Create Collection', 'vortex-ai-marketplace'); ?>
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <div class="vortex-gallery-grid" id="galleryGrid">
                <div class="vortex-loading"><?php _e('Loading gallery...', 'vortex-ai-marketplace'); ?></div>
            </div>

            <div class="vortex-gallery-pagination" id="galleryPagination">
                <!-- Pagination will be populated via JavaScript -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * User milestones shortcode.
     *
     * @since    2.0.0
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function milestones_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_progress' => 'true',
            'show_rewards' => 'true',
            'layout' => 'timeline',
        ), $atts);

        if (!is_user_logged_in()) {
            return '<div class="vortex-error">Please log in to view your milestones.</div>';
        }

        ob_start();
        ?>
        <div class="vortex-milestones-container" data-layout="<?php echo esc_attr($atts['layout']); ?>">
            <div class="vortex-milestones-header">
                <h2><?php _e('Your Artist Journey Milestones', 'vortex-ai-marketplace'); ?></h2>
                
                <?php if ($atts['show_progress'] === 'true'): ?>
                <div class="vortex-progress-summary">
                    <div class="vortex-progress-item">
                        <span class="vortex-progress-label"><?php _e('Overall Progress', 'vortex-ai-marketplace'); ?></span>
                        <div class="vortex-progress-bar">
                            <div class="vortex-progress-fill" data-progress="0" id="overallProgress"></div>
                        </div>
                        <span class="vortex-progress-text" id="overallProgressText">0%</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="vortex-milestones-timeline" id="milestonesTimeline">
                <div class="vortex-loading"><?php _e('Loading milestones...', 'vortex-ai-marketplace'); ?></div>
            </div>

            <?php if ($atts['show_rewards'] === 'true'): ?>
            <div class="vortex-rewards-section">
                <h3><?php _e('Milestone Rewards', 'vortex-ai-marketplace'); ?></h3>
                <div class="vortex-rewards-grid" id="milestonesRewards">
                    <div class="vortex-loading"><?php _e('Loading rewards...', 'vortex-ai-marketplace'); ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
} 