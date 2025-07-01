/**
 * Class for handling AI agents in the Vortex AI Marketplace.
 *
 * This class manages AI agents, their display, and interaction handling.
 *
 * @link       https://aimarketplace.vortex-it.com/
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_AI_Agent_Handler {

    /**
     * Array of available AI agents.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $agents    The available AI agents.
     */
    private $agents;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->initialize_agents();
        $this->register_hooks();
    }

    /**
     * Register the necessary hooks for the AI agent functionality.
     *
     * @since    1.0.0
     */
    public function register_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_vortex_agent_message', array($this, 'handle_agent_message'));
        add_action('wp_ajax_nopriv_vortex_agent_message', array($this, 'handle_agent_message'));
        add_action('wp_ajax_vortex_analyze_quiz_responses', array($this, 'handle_quiz_analysis'));
        add_action('wp_ajax_nopriv_vortex_analyze_quiz_responses', array($this, 'handle_quiz_analysis'));
        add_shortcode('vortex_ai_agents', array($this, 'display_agents'));
    }

    /**
     * Enqueue the necessary CSS and JavaScript for the AI agents.
     *
     * @since    1.0.0
     */
    public function enqueue_assets() {
        // Enqueue Font Awesome for agent icons
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
        
        // Enqueue custom CSS
        wp_enqueue_style('vortex-ai-agents-styles', plugin_dir_url(__FILE__) . '../public/css/vortex-ai-agents.css', array(), '1.0.0');
        
        // Enqueue JavaScript
        wp_enqueue_script('vortex-ai-agents-js', plugin_dir_url(__FILE__) . '../public/js/vortex-ai-agents.js', array('jquery'), '1.0.0', true);
        
        // Localize script with AJAX URL and nonce
        wp_localize_script('vortex-ai-agents-js', 'vortexAiAgents', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_ai_agents_nonce')
        ));
    }

    /**
     * Initialize the available AI agents.
     *
     * @since    1.0.0
     */
    public function initialize_agents() {
        // Default agents
        $this->agents = array(
            'artwork_advisor' => array(
                'id' => 'artwork_advisor',
                'name' => 'Artwork Advisor',
                'icon' => 'fas fa-palette',
                'description' => 'Get advice on artwork creation, style, and techniques.',
                'greeting' => 'Hello! I\'m your Artwork Advisor. I can help you with tips on creating art, choosing styles, and improving your techniques. What can I assist you with today?'
            ),
            'marketplace_guide' => array(
                'id' => 'marketplace_guide',
                'name' => 'Marketplace Guide',
                'icon' => 'fas fa-store',
                'description' => 'Learn how to navigate and use the marketplace effectively.',
                'greeting' => 'Welcome to the Vortex AI Marketplace! I\'m your guide to help you navigate our platform. Whether you\'re buying, selling, or just browsing, I can help. What would you like to know?'
            ),
            'prompt_engineer' => array(
                'id' => 'prompt_engineer',
                'name' => 'Prompt Engineer',
                'icon' => 'fas fa-magic',
                'description' => 'Get help crafting effective prompts for AI art generation.',
                'greeting' => 'Hi there! I\'m your Prompt Engineering Assistant. I can help you craft effective prompts for AI art generation to get the best results. What kind of art are you trying to create?'
            ),
            'community_assistant' => array(
                'id' => 'community_assistant',
                'name' => 'Community Assistant',
                'icon' => 'fas fa-users',
                'description' => 'Get information about community events, forums, and collaboration opportunities.',
                'greeting' => 'Hello! I\'m your Community Assistant. I can help you connect with other artists, find events, and discover collaboration opportunities in our vibrant community. How can I assist you today?'
            ),
            'technical_support' => array(
                'id' => 'technical_support',
                'name' => 'Technical Support',
                'icon' => 'fas fa-cogs',
                'description' => 'Get help with technical issues related to the marketplace.',
                'greeting' => 'Hello! I\'m your Technical Support Assistant. I can help troubleshoot issues you might be experiencing with the marketplace. What seems to be the problem?'
            ),
            'artist_qualifier' => array(
                'id' => 'artist_qualifier',
                'name' => 'Artist Qualifier',
                'icon' => 'fas fa-user-check',
                'description' => 'Take a qualification quiz to assess your artist tier and get personalized recommendations.',
                'greeting' => 'Hello! I\'m the Artist Qualifier. I can help assess your experience, style, and commitment to determine your artist tier on our platform. Would you like to take the artist qualification quiz?'
            )
        );

        // Apply filters to allow for customization
        $this->agents = apply_filters( 'vortex_ai_agents', $this->agents );
    }

    /**
     * Get all available AI agents.
     *
     * @since    1.0.0
     * @return   array    The available AI agents.
     */
    public function get_agents() {
        return $this->agents;
    }

    /**
     * Get a specific AI agent by ID.
     *
     * @since    1.0.0
     * @param    string    $agent_id    The ID of the agent to retrieve.
     * @return   array|null             The agent data or null if not found.
     */
    public function get_agent($agent_id) {
        return isset($this->agents[$agent_id]) ? $this->agents[$agent_id] : null;
    }

    /**
     * Display the AI agents.
     *
     * @since    1.0.0
     * @param    array     $atts       Shortcode attributes.
     * @return   string                 The HTML output for the agents.
     */
    public function display_agents($atts = array()) {
        // Buffer output
        ob_start();
        
        // Include the template
        include plugin_dir_path(__FILE__) . '../public/partials/vortex-ai-agents-display.php';
        
        // Return the buffered output
        return ob_get_clean();
    }

    /**
     * Handle AI agent message AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_agent_message() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_ai_agents_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            exit;
        }

        // Check if agent ID and message are provided
        if (!isset($_POST['agent_id']) || !isset($_POST['message'])) {
            wp_send_json_error(array('message' => 'Missing required parameters'));
            exit;
        }

        $agent_id = sanitize_text_field($_POST['agent_id']);
        $message = sanitize_textarea_field($_POST['message']);

        // Check if agent exists
        $agent = $this->get_agent($agent_id);
        if (!$agent) {
            wp_send_json_error(array('message' => 'Agent not found'));
            exit;
        }

        // Generate response
        $response = $this->generate_agent_response($agent_id, $message);

        // Send response
        wp_send_json_success(array(
            'agent_id' => $agent_id,
            'message' => $response
        ));
        exit;
    }

    /**
     * Handle artist quiz analysis AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_quiz_analysis() {
        // Check nonce for security
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'vortex_analyze_quiz_responses')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Get quiz responses
        $education = isset($_POST['education']) ? sanitize_text_field($_POST['education']) : '';
        $self_taught_years = isset($_POST['self_taught_years']) ? intval($_POST['self_taught_years']) : 0;
        $style = isset($_POST['style']) ? sanitize_text_field($_POST['style']) : '';
        $exhibitions = isset($_POST['exhibitions']) ? sanitize_text_field($_POST['exhibitions']) : '';
        $price_range = isset($_POST['price_range']) ? sanitize_text_field($_POST['price_range']) : '';
        $seed_commitment = isset($_POST['seed_art_commitment']) ? (bool)$_POST['seed_art_commitment'] : false;
        
        // Initialize score and feedback
        $score = 0;
        $feedback = '';
        $tier = 'standard';
        
        // Seed art commitment is mandatory
        if (!$seed_commitment) {
            $feedback = __('Commitment to regular seed art uploads is required to participate as an artist in our marketplace. Please agree to this commitment.', 'vortex-ai-marketplace');
            wp_send_json_error(array(
                'message' => $feedback
            ));
            exit;
        }
        
        // Get responses from the artist qualifier agent
        $responses = $this->analyze_quiz_with_ai(array(
            'education' => $education,
            'self_taught_years' => $self_taught_years,
            'style' => $style,
            'exhibitions' => $exhibitions,
            'price_range' => $price_range
        ));
        
        // Return the results
        wp_send_json_success($responses);
        exit;
    }

    /**
     * Analyze quiz responses using AI.
     *
     * @since    1.0.0
     * @param    array     $responses   The quiz responses.
     * @return   array                  Analysis results.
     */
    private function analyze_quiz_with_ai($responses) {
        // Initialize tier and feedback
        $score = 0;
        $tier = 'standard';
        
        // Evaluate education
        if ($responses['education'] === 'formal_degree') {
            $score += 3;
        } elseif ($responses['education'] === 'formal_courses') {
            $score += 2;
        } elseif ($responses['education'] === 'self_taught' && $responses['self_taught_years'] >= 5) {
            $score += 2;
        } elseif ($responses['education'] === 'self_taught' && $responses['self_taught_years'] >= 2) {
            $score += 1;
        }
        
        // Evaluate exhibitions
        if ($responses['exhibitions'] === 'gallery_featured') {
            $score += 3;
        } elseif ($responses['exhibitions'] === 'group_exhibitions') {
            $score += 2;
        } elseif ($responses['exhibitions'] === 'online_curated') {
            $score += 1;
        }
        
        // Evaluate price range
        if ($responses['price_range'] === 'premium') {
            $score += 3;
        } elseif ($responses['price_range'] === 'mid_range') {
            $score += 2;
        } elseif ($responses['price_range'] === 'entry_level') {
            $score += 1;
        }
        
        // Generate personalized feedback based on responses
        $feedback = $this->generate_personalized_quiz_feedback($responses, $score);
        
        // Determine tier based on score
        if ($score >= 7) {
            $tier = 'premium';
        } elseif ($score >= 4) {
            $tier = 'advanced';
        } else {
            $tier = 'standard';
        }
        
        // Add seed art commitment reminder
        $feedback .= ' ' . __('Remember, as part of your artist agreement, you\'ve committed to uploading at least two hand-crafted seed artworks weekly.', 'vortex-ai-marketplace');
        
        return array(
            'tier' => $tier,
            'feedback' => $feedback,
            'score' => $score
        );
    }

    /**
     * Generate personalized feedback for quiz responses.
     *
     * @since    1.0.0
     * @param    array     $responses   The quiz responses.
     * @param    int       $score       The calculated score.
     * @return   string                 Personalized feedback.
     */
    private function generate_personalized_quiz_feedback($responses, $score) {
        // Base feedback based on score tiers
        if ($score >= 7) {
            $feedback = __('Your qualifications and experience suggest you would be an excellent addition to our premium artist tier. ', 'vortex-ai-marketplace');
        } elseif ($score >= 4) {
            $feedback = __('Your experience and background qualify you for our advanced artist tier. ', 'vortex-ai-marketplace');
        } else {
            $feedback = __('Welcome to our artist community! We\'re excited to help you grow your artistic career. ', 'vortex-ai-marketplace');
        }
        
        // Add education-specific feedback
        if ($responses['education'] === 'formal_degree') {
            $feedback .= __('Your formal art education provides a strong foundation for creating quality artwork. ', 'vortex-ai-marketplace');
        } elseif ($responses['education'] === 'formal_courses') {
            $feedback .= __('Your structured art courses have helped develop your technical skills. ', 'vortex-ai-marketplace');
        } elseif ($responses['education'] === 'self_taught' && $responses['self_taught_years'] >= 5) {
            $feedback .= __('Your years of self-directed learning demonstrate dedication to your craft. ', 'vortex-ai-marketplace');
        } elseif ($responses['education'] === 'self_taught') {
            $feedback .= __('Self-taught artists bring fresh perspectives to our marketplace. ', 'vortex-ai-marketplace');
        }
        
        // Add exhibition-specific feedback
        if ($responses['exhibitions'] === 'gallery_featured') {
            $feedback .= __('Your gallery exhibition experience indicates professional recognition of your work. ', 'vortex-ai-marketplace');
        } elseif ($responses['exhibitions'] === 'group_exhibitions') {
            $feedback .= __('Participating in group exhibitions shows your engagement with the art community. ', 'vortex-ai-marketplace');
        } elseif ($responses['exhibitions'] === 'online_curated') {
            $feedback .= __('Your presence in curated online galleries demonstrates digital art market awareness. ', 'vortex-ai-marketplace');
        }
        
        // Add style-specific encouragement
        if (!empty($responses['style'])) {
            $feedback .= sprintf(__('Your focus on %s brings valuable diversity to our marketplace. ', 'vortex-ai-marketplace'), $responses['style']);
        }
        
        return $feedback;
    }

    /**
     * Generate a response from an AI agent based on the user's message.
     *
     * @param string $agent_id The ID of the agent.
     * @param string $message  The user's message.
     * @return string          The agent's response.
     */
    public function generate_agent_response( $agent_id, $message ) {
        // Convert message to lowercase for easier matching
        $message_lower = strtolower( $message );
        
        // Check if this is the Artist Qualifier agent
        if ( $agent_id === 'artist_qualifier' ) {
            return $this->handle_artist_qualifier_response( $message_lower );
        }
        
        // Predefined responses for other agents
        $responses = array(
            'artwork_advisor' => array(
                'technique' => 'To improve your technique, try practicing regularly with focused exercises. Start with basic skills and gradually move to more complex techniques. Reference tutorials and courses specific to your medium.',
                'style' => 'Developing your unique style takes time. Study artists you admire, understand their techniques, then experiment with your own variations. Your style will emerge naturally as you create more work.',
                'portfolio' => 'A strong portfolio shows variety in your skills while maintaining a cohesive style. Include your best 10-15 pieces, and update regularly as your skills improve.'
            ),
            'marketplace_guide' => array(
                'price' => 'When pricing your artwork, consider factors like size, medium, complexity, time invested, your experience level, and market demand. Research what similar artists charge, and don\'t undervalue your work.',
                'sell' => 'To increase sales, ensure high-quality presentation with good photos and descriptions, engage with potential buyers through comments, share your process, and promote your work on social media.',
                'reviews' => 'Positive reviews build trust. After a sale, follow up with buyers to ensure satisfaction. Respond professionally to all reviews, even negative ones, which shows your commitment to customer service.'
            ),
            'prompt_engineer' => array(
                'prompt' => 'Effective AI art prompts are specific and descriptive. Include subject, style, mood, lighting, color palette, and composition. For example, instead of "sunset," try "vibrant orange and purple sunset over mountain landscape, dramatic clouds, golden light, cinematic, ultra detailed."',
                'style' => 'To specify style, reference art movements (impressionism, cubism), artists (in the style of Monet), media (watercolor, oil painting), or aesthetics (cyberpunk, cottagecore). Be specific about the look you want.',
                'technical' => 'Some AI art platforms allow technical parameters like aspect ratio, seed numbers (for reproducibility), guidance scale (creativity vs. prompt adherence), and sampling methods that affect output quality.'
            ),
            'community_assistant' => array(
                'event' => 'We host regular community events including monthly themed challenges, live critique sessions every Friday, virtual gallery openings for featured artists, and seasonal art competitions with prizes.',
                'collaboration' => 'To find collaboration partners, check our community forum\'s collaboration board, join our Discord server\'s #find-a-partner channel, or participate in our quarterly collaborative projects.',
                'newsletter' => 'The community newsletter goes out every Monday with featured artworks, upcoming events, challenge announcements, marketplace tips, and spotlights on community members. Subscribe through your account settings.'
            ),
            'technical_support' => array(
                'upload' => 'To upload artwork, go to your Dashboard > Create New Listing. You can upload images up to 50MB in JPG, PNG, or TIFF formats. For optimal quality, use 300dpi images with accurate color profiles.',
                'account' => 'You can update your account settings in the Dashboard > Account Settings page. From there, you can change your profile picture, bio, payment information, notification preferences, and privacy settings.',
                'mobile' => 'Yes, our site is fully responsive for mobile devices. However, for uploading artwork and managing your shop, we recommend using a desktop for the best experience and access to all features.'
            )
        );
        
        // Check for keywords in the message and return appropriate response
        if (isset($responses[$agent_id])) {
            foreach ($responses[$agent_id] as $keyword => $response) {
                if (strpos($message_lower, $keyword) !== false) {
                    return $response;
                }
            }
        }
        
        // Default response if no keyword matches
        return "I'm sorry, I don't have specific information about that. Can you ask me something else about the Vortex AI Marketplace?";
    }

    /**
     * Handle responses specific to the Artist Qualifier agent.
     *
     * @param string $message_lower The user's message in lowercase.
     * @return string               The agent's response.
     */
    public function handle_artist_qualifier_response( $message_lower ) {
        // Response for qualification quiz request
        if ( strpos( $message_lower, 'quiz' ) !== false || 
             strpos( $message_lower, 'qualification' ) !== false || 
             strpos( $message_lower, 'assess' ) !== false ) {
            
            return '<div class="vortex-quiz-container">
                <h3>Artist Qualification Quiz</h3>
                <form id="vortex-artist-quiz-form">
                    <div class="quiz-question">
                        <label for="education">What is your highest level of art education?</label>
                        <select name="education" id="education" required>
                            <option value="">Select an option</option>
                            <option value="formal_degree">Art degree (BFA, MFA, etc.)</option>
                            <option value="formal_courses">Art courses or workshops</option>
                            <option value="self_taught">Self-taught</option>
                        </select>
                    </div>
                    
                    <div class="quiz-question self-taught-years" style="display:none;">
                        <label for="self_taught_years">How many years have you been creating art?</label>
                        <input type="number" name="self_taught_years" id="self_taught_years" min="0" max="70">
                    </div>
                    
                    <div class="quiz-question">
                        <label for="style">How would you describe your artistic style?</label>
                        <select name="style" id="style" required>
                            <option value="">Select an option</option>
                            <option value="realism">Realism</option>
                            <option value="abstract">Abstract</option>
                            <option value="digital">Digital art</option>
                            <option value="mixed_media">Mixed media</option>
                            <option value="illustration">Illustration</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="quiz-question">
                        <label for="exhibitions">Have you exhibited your artwork professionally?</label>
                        <select name="exhibitions" id="exhibitions" required>
                            <option value="">Select an option</option>
                            <option value="gallery_featured">Yes, in galleries</option>
                            <option value="group_exhibitions">Yes, group exhibitions</option>
                            <option value="online_curated">Yes, curated online platforms</option>
                            <option value="none">No, I haven\'t exhibited professionally</option>
                        </select>
                    </div>
                    
                    <div class="quiz-question">
                        <label for="price_range">What is your typical price range for original artwork?</label>
                        <select name="price_range" id="price_range" required>
                            <option value="">Select an option</option>
                            <option value="entry_level">Under $500</option>
                            <option value="mid_range">$500-$2,000</option>
                            <option value="premium">Over $2,000</option>
                        </select>
                    </div>
                    
                    <div class="quiz-question">
                        <label for="seed_art_commitment">
                            <input type="checkbox" name="seed_art_commitment" id="seed_art_commitment" required>
                            I agree to upload at least 2 hand-crafted artworks weekly as seed art
                        </label>
                    </div>
                    
                    <div class="quiz-submit">
                        <button type="submit" class="vortex-quiz-submit">Submit Quiz</button>
                    </div>
                </form>
            </div>' . wp_nonce_field('vortex_analyze_quiz_responses', 'quiz_security', true, false);
        }
        
        // Response for general greeting or help request
        if ( strpos( $message_lower, 'hello' ) !== false || 
             strpos( $message_lower, 'hi' ) !== false || 
             strpos( $message_lower, 'hey' ) !== false || 
             strpos( $message_lower, 'help' ) !== false ) {
            return "Hello! I'm the Artist Qualifier assistant. I can help assess your artistic experience and qualifications to determine your tier on our platform. Would you like to take our qualification quiz? Just ask me about the 'qualification quiz' to get started.";
        }
        
        // Response for questions about tiers
        if ( strpos( $message_lower, 'tier' ) !== false || 
             strpos( $message_lower, 'level' ) !== false || 
             strpos( $message_lower, 'rank' ) !== false ) {
            return "Our platform has three artist tiers: Standard, Advanced, and Premium. Each tier offers different benefits and exposure on the marketplace. Your tier is determined based on your experience, style, exhibition history, and commitment to creating seed art. Take the qualification quiz to discover your tier!";
        }
        
        // Response for questions about seed art
        if ( strpos( $message_lower, 'seed' ) !== false || 
             strpos( $message_lower, 'seed art' ) !== false || 
             strpos( $message_lower, 'commitment' ) !== false ) {
            return "Seed art refers to original, hand-crafted artwork that you create and upload to our platform. All artists must commit to uploading at least two pieces of seed art per week to maintain their artist status. This contributes to our vibrant ecosystem and helps ensure the quality and diversity of AI-generated art on our platform.";
        }
        
        // Default response
        return "I'm here to help assess your qualifications as an artist on our platform. You can ask me about the qualification quiz, artist tiers, or seed art requirements. If you're ready to determine your tier, just ask me about the 'qualification quiz'.";
    }
} 