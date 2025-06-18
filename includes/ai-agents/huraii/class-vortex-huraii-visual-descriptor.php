<?php
/**
 * HURAII Visual Descriptor
 *
 * Advanced visual analysis and description system that analyzes uploaded images 
 * using Marianne Nems' Seed-Art Technique and generates comprehensive descriptions
 * that can be used as prompts for new artwork generation.
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage AI_Processing
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_HURAII_Visual_Descriptor Class
 * 
 * Implements comprehensive visual analysis and description generation using 
 * the Seed-Art technique with external knowledge base integration.
 *
 * @since 1.0.0
 */
class VORTEX_HURAII_Visual_Descriptor {
    /**
     * Instance of this class.
     *
     * @since 1.0.0
     * @var object
     */
    protected static $instance = null;
    
    /**
     * Seed art analyzer instance
     *
     * @since 1.0.0
     * @var Vortex_HURAII_Seed_Analyzer
     */
    private $seed_analyzer;
    
    /**
     * Knowledge categories for external research
     *
     * @since 1.0.0
     * @var array
     */
    private $knowledge_categories = array(
        'art_movements' => array(
            'Renaissance', 'Baroque', 'Impressionism', 'Expressionism', 'Cubism', 
            'Surrealism', 'Abstract Expressionism', 'Pop Art', 'Minimalism',
            'Contemporary Art', 'Digital Art', 'Neo-Expressionism', 'Street Art'
        ),
        'artistic_techniques' => array(
            'Chiaroscuro', 'Sfumato', 'Impasto', 'Glazing', 'Scumbling',
            'Grisaille', 'Pointillism', 'Fauvism', 'Tenebrism', 'Alla Prima'
        ),
        'color_theories' => array(
            'Complementary Colors', 'Triadic Harmony', 'Analogous Colors',
            'Split-Complementary', 'Tetradic Colors', 'Monochromatic'
        ),
        'sacred_geometry' => array(
            'Golden Ratio', 'Fibonacci Sequence', 'Flower of Life', 'Metatrons Cube',
            'Vesica Piscis', 'Sri Yantra', 'Seed of Life', 'Tree of Life'
        ),
        'cultural_symbols' => array(
            'Mythology', 'Religious Iconography', 'Cultural Motifs', 'Archetypal Symbols',
            'Historical References', 'Literary Allusions', 'Philosophical Concepts'
        )
    );
    
    /**
     * External API endpoints for knowledge enrichment
     *
     * @since 1.0.0
     * @var array
     */
    private $knowledge_sources = array(
        'wikipedia_api' => 'https://en.wikipedia.org/api/rest_v1/page/summary/',
        'wikiart_api' => 'https://www.wikiart.org/en/api/2/',
        'metmuseum_api' => 'https://collectionapi.metmuseum.org/public/collection/v1/',
        'openai_vision' => 'https://api.openai.com/v1/chat/completions'
    );
    
    /**
     * Return an instance of this class.
     *
     * @since 1.0.0
     * @return object A single instance of this class.
     */
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->seed_analyzer = Vortex_HURAII_Seed_Analyzer::get_instance();
        $this->setup_hooks();
    }
    
    /**
     * Setup WordPress hooks
     *
     * @since 1.0.0
     */
    private function setup_hooks() {
        // AJAX endpoints for describe functionality
        add_action('wp_ajax_huraii_describe_visual', array($this, 'ajax_describe_visual'));
        add_action('wp_ajax_nopriv_huraii_describe_visual', array($this, 'ajax_describe_visual'));
        
        // Add describe command to Midjourney UI
        add_filter('vortex_midjourney_commands', array($this, 'add_describe_command'));
        
        // Enqueue scripts for visual descriptor
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue necessary scripts and styles
     *
     * @since 1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'huraii-visual-descriptor',
            VORTEX_PLUGIN_URL . 'assets/js/huraii-components/huraii-visual-descriptor.js',
            array('jquery', 'huraii-midjourney-ui'),
            VORTEX_VERSION,
            true
        );
        
        wp_localize_script('huraii-visual-descriptor', 'huraiiDescriptor', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('huraii_describe_nonce'),
            'i18n' => array(
                'analyzing' => __('Analyzing visual components...', 'vortex-ai-marketplace'),
                'researching' => __('Researching artistic elements...', 'vortex-ai-marketplace'),
                'generating' => __('Generating description...', 'vortex-ai-marketplace'),
                'complete' => __('Analysis complete!', 'vortex-ai-marketplace'),
                'error' => __('Analysis failed. Please try again.', 'vortex-ai-marketplace')
            )
        ));
    }
    
    /**
     * Add describe command to Midjourney UI commands
     *
     * @since 1.0.0
     * @param array $commands Existing commands
     * @return array Modified commands
     */
    public function add_describe_command($commands) {
        $commands[] = array(
            'name' => 'describe',
            'icon' => 'comment-alt',
            'description' => 'Generate detailed description of uploaded image using Seed-Art analysis'
        );
        return $commands;
    }
    
    /**
     * Comprehensive visual description using Seed-Art technique
     *
     * @since 1.0.0
     * @param string $image_path Path to image file
     * @param array $options Analysis options
     * @return array Comprehensive description data
     */
    public function describe_visual($image_path, $options = array()) {
        $start_time = microtime(true);
        
        // Default options
        $options = wp_parse_args($options, array(
            'include_prompts' => true,
            'research_depth' => 'comprehensive',
            'cultural_context' => true,
            'technical_analysis' => true,
            'generate_variations' => 4
        ));
        
        // Step 1: Perform Seed-Art analysis
        $seed_analysis = $this->seed_analyzer->analyze_seed_artwork($image_path);
        
        if (is_wp_error($seed_analysis)) {
            throw new Exception($seed_analysis->get_error_message());
        }
        
        // Step 2: Extract visual components
        $visual_components = $this->extract_visual_components($image_path, $seed_analysis);
        
        // Step 3: Research each component with external knowledge
        $researched_components = $this->research_visual_components($visual_components);
        
        // Step 4: Generate comprehensive description
        $comprehensive_description = $this->generate_comprehensive_description($researched_components, $seed_analysis);
        
        // Step 5: Create prompt variations for generation
        $prompt_variations = array();
        if ($options['include_prompts']) {
            $prompt_variations = $this->generate_prompt_variations($comprehensive_description, $options['generate_variations']);
        }
        
        return array(
            'primary_description' => $comprehensive_description['primary'],
            'detailed_analysis' => $comprehensive_description['detailed'],
            'cultural_context' => $comprehensive_description['cultural'],
            'seed_art_analysis' => $seed_analysis,
            'visual_components' => $researched_components,
            'prompt_variations' => $prompt_variations,
            'confidence_score' => $this->calculate_confidence_score($seed_analysis, $researched_components),
            'processing_time' => microtime(true) - $start_time
        );
    }
    
    /**
     * AJAX handler for visual description
     *
     * @since 1.0.0
     */
    public function ajax_describe_visual() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'huraii_describe_nonce')) {
            wp_die(__('Security check failed', 'vortex-ai-marketplace'));
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(__('No image uploaded or upload failed', 'vortex-ai-marketplace'));
        }
        
        // Validate file type
        $allowed_types = array('image/jpeg', 'image/png', 'image/webp', 'image/gif');
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            wp_send_json_error(__('Invalid file type. Please upload JPEG, PNG, WebP, or GIF.', 'vortex-ai-marketplace'));
        }
        
        // Move uploaded file to temp location
        $temp_file = wp_upload_bits($_FILES['image']['name'], null, file_get_contents($_FILES['image']['tmp_name']));
        
        if ($temp_file['error']) {
            wp_send_json_error($temp_file['error']);
        }
        
        try {
            // Perform comprehensive visual analysis
            $description_result = $this->describe_visual($temp_file['file']);
            
            // Clean up temp file
            @unlink($temp_file['file']);
            
            wp_send_json_success($description_result);
            
        } catch (Exception $e) {
            // Clean up temp file on error
            @unlink($temp_file['file']);
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Extract visual components from image using Seed-Art principles
     *
     * @since 1.0.0
     * @param string $image_path Path to image
     * @param array $seed_analysis Seed art analysis results
     * @return array Visual components
     */
    private function extract_visual_components($image_path, $seed_analysis) {
        // Get image metadata
        $image_info = getimagesize($image_path);
        $image_data = $this->get_image_histogram($image_path);
        
        $components = array(
            // Sacred Geometry Components
            'geometry' => array(
                'detected_patterns' => $seed_analysis['sacred_geometry'] ?? array(),
                'proportions' => $seed_analysis['proportional_systems'] ?? array(),
                'symmetry' => $seed_analysis['sacred_geometry']['symmetry'] ?? array()
            ),
            
            // Color Analysis
            'colors' => array(
                'palette' => $seed_analysis['color_harmony']['dominant_colors'] ?? array(),
                'temperature' => $seed_analysis['color_harmony']['temperature'] ?? 'neutral',
                'harmony_type' => $this->identify_harmony_type($seed_analysis['color_harmony'] ?? array()),
                'emotional_impact' => $seed_analysis['emotional_resonance'] ?? array()
            ),
            
            // Compositional Elements
            'composition' => array(
                'focal_points' => $seed_analysis['compositional_elements']['focal_points'] ?? array(),
                'balance_type' => $seed_analysis['compositional_elements']['balance'] ?? array(),
                'movement_flow' => $seed_analysis['movement_patterns'] ?? array(),
                'depth_techniques' => $seed_analysis['depth_perception'] ?? array()
            ),
            
            // Technical Aspects
            'technical' => array(
                'dimensions' => array('width' => $image_info[0], 'height' => $image_info[1]),
                'aspect_ratio' => round($image_info[0] / $image_info[1], 2),
                'texture_quality' => $seed_analysis['texture_analysis'] ?? array(),
                'detail_level' => $this->assess_detail_level($image_path)
            ),
            
            // Style Signatures
            'style' => array(
                'fingerprint' => $seed_analysis['style_fingerprint'] ?? array(),
                'unique_elements' => $seed_analysis['unique_signature'] ?? array(),
                'artistic_influences' => $this->detect_artistic_influences($seed_analysis)
            )
        );
        
        return $components;
    }
    
    /**
     * Research visual components using external knowledge sources
     *
     * @since 1.0.0
     * @param array $components Visual components to research
     * @return array Enriched components with research data
     */
    private function research_visual_components($components) {
        $researched = $components;
        
        // Research geometric patterns
        if (!empty($components['geometry']['detected_patterns'])) {
            foreach ($components['geometry']['detected_patterns'] as $pattern => $data) {
                $researched['geometry']['detected_patterns'][$pattern]['research'] = 
                    $this->research_geometric_pattern($pattern);
            }
        }
        
        // Research color harmonies
        if (!empty($components['colors']['harmony_type'])) {
            $researched['colors']['harmony_research'] = 
                $this->research_color_theory($components['colors']['harmony_type']);
        }
        
        // Research artistic influences
        if (!empty($components['style']['artistic_influences'])) {
            foreach ($components['style']['artistic_influences'] as $influence) {
                $researched['style']['influence_research'][$influence] = 
                    $this->research_art_movement($influence);
            }
        }
        
        // Research cultural symbols if detected
        $cultural_elements = $this->detect_cultural_elements($components);
        if (!empty($cultural_elements)) {
            $researched['cultural'] = array();
            foreach ($cultural_elements as $element) {
                $researched['cultural'][$element] = $this->research_cultural_symbol($element);
            }
        }
        
        return $researched;
    }
    
    /**
     * Generate comprehensive description based on analysis and research
     *
     * @since 1.0.0
     * @param array $researched_components Researched visual components
     * @param array $seed_analysis Original seed art analysis
     * @return array Comprehensive descriptions
     */
    private function generate_comprehensive_description($researched_components, $seed_analysis) {
        // Primary description (concise, prompt-friendly)
        $primary = $this->generate_primary_description($researched_components);
        
        // Detailed technical analysis
        $detailed = $this->generate_detailed_analysis($researched_components, $seed_analysis);
        
        // Cultural and historical context
        $cultural = $this->generate_cultural_context($researched_components);
        
        return array(
            'primary' => $primary,
            'detailed' => $detailed,
            'cultural' => $cultural
        );
    }
    
    /**
     * Generate primary description suitable for AI prompts
     *
     * @since 1.0.0
     * @param array $components Researched components
     * @return string Primary description
     */
    private function generate_primary_description($components) {
        $description_parts = array();
        
        // Style and medium inference
        if (!empty($components['style']['artistic_influences'])) {
            $style = implode(' and ', array_slice($components['style']['artistic_influences'], 0, 2));
            $description_parts[] = "artwork in {$style} style";
        }
        
        // Color description
        if (!empty($components['colors']['palette'])) {
            $color_desc = $this->describe_color_palette($components['colors']['palette']);
            $description_parts[] = "featuring {$color_desc}";
        }
        
        // Compositional elements
        if (!empty($components['composition']['focal_points'])) {
            $composition_desc = $this->describe_composition($components['composition']);
            $description_parts[] = "with {$composition_desc}";
        }
        
        // Sacred geometry elements
        if (!empty($components['geometry']['detected_patterns'])) {
            $geometry_desc = $this->describe_sacred_geometry($components['geometry']['detected_patterns']);
            $description_parts[] = "incorporating {$geometry_desc}";
        }
        
        // Technical quality
        $technical_desc = $this->describe_technical_quality($components['technical']);
        $description_parts[] = $technical_desc;
        
        return ucfirst(implode(', ', $description_parts)) . '.';
    }
    
    /**
     * Generate prompt variations for artwork generation
     *
     * @since 1.0.0
     * @param array $description Comprehensive description
     * @param int $count Number of variations to generate
     * @return array Prompt variations
     */
    private function generate_prompt_variations($description, $count = 4) {
        $base_prompt = $description['primary'];
        $variations = array();
        
        // Variation 1: Enhanced detail
        $variations[] = array(
            'type' => 'enhanced',
            'prompt' => $base_prompt . ' Highly detailed, museum quality, professional artwork.',
            'focus' => 'detail enhancement'
        );
        
        // Variation 2: Different medium
        $variations[] = array(
            'type' => 'medium_variant',
            'prompt' => str_replace('artwork', 'oil painting on canvas', $base_prompt),
            'focus' => 'medium exploration'
        );
        
        // Variation 3: Lighting emphasis
        $variations[] = array(
            'type' => 'lighting_focused',
            'prompt' => $base_prompt . ' Dramatic lighting, chiaroscuro technique, volumetric light.',
            'focus' => 'lighting and atmosphere'
        );
        
        // Variation 4: Cultural context
        if (!empty($description['cultural'])) {
            $cultural_elements = array_keys($description['cultural']);
            $cultural_focus = implode(', ', array_slice($cultural_elements, 0, 2));
            $variations[] = array(
                'type' => 'cultural_context',
                'prompt' => $base_prompt . " Inspired by {$cultural_focus}, rich cultural symbolism.",
                'focus' => 'cultural depth'
            );
        } else {
            // Fallback: Texture emphasis
            $variations[] = array(
                'type' => 'texture_focused',
                'prompt' => $base_prompt . ' Rich textures, tactile quality, material depth.',
                'focus' => 'texture and materials'
            );
        }
        
        return array_slice($variations, 0, $count);
    }
    
    /**
     * Research geometric pattern using external knowledge
     *
     * @since 1.0.0
     * @param string $pattern Pattern name
     * @return array Research data
     */
    private function research_geometric_pattern($pattern) {
        $cache_key = 'huraii_pattern_research_' . md5($pattern);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $research = array(
            'definition' => '',
            'cultural_significance' => '',
            'artistic_applications' => '',
            'symbolism' => ''
        );
        
        // Try Wikipedia API
        $wiki_result = $this->query_wikipedia($pattern);
        if (!is_wp_error($wiki_result)) {
            $research['definition'] = $wiki_result['extract'] ?? '';
            $research['cultural_significance'] = $this->extract_cultural_info($wiki_result);
        }
        
        // Add known information for common patterns
        $pattern_knowledge = $this->get_pattern_knowledge($pattern);
        $research = array_merge($research, $pattern_knowledge);
        
        // Cache for 24 hours
        set_transient($cache_key, $research, DAY_IN_SECONDS);
        
        return $research;
    }
    
    /**
     * Query Wikipedia API for information
     *
     * @since 1.0.0
     * @param string $term Search term
     * @return array|WP_Error Wikipedia data or error
     */
    private function query_wikipedia($term) {
        $url = $this->knowledge_sources['wikipedia_api'] . urlencode($term);
        
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'VORTEX-HURAII-Visual-Descriptor/1.0'
            )
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Failed to parse Wikipedia response');
        }
        
        return $data;
    }
    
    /**
     * Calculate confidence score for the analysis
     *
     * @since 1.0.0
     * @param array $seed_analysis Seed art analysis
     * @param array $components Visual components
     * @return float Confidence score (0-1)
     */
    private function calculate_confidence_score($seed_analysis, $components) {
        $factors = array();
        
        // Sacred geometry detection confidence
        if (!empty($seed_analysis['sacred_geometry'])) {
            $geometry_confidence = 0;
            foreach ($seed_analysis['sacred_geometry'] as $pattern => $data) {
                if (isset($data['presence'])) {
                    $geometry_confidence = max($geometry_confidence, $data['presence']);
                }
            }
            $factors['geometry'] = $geometry_confidence * 0.25;
        }
        
        // Color analysis confidence
        if (!empty($seed_analysis['color_harmony']['harmony_score'])) {
            $factors['color'] = $seed_analysis['color_harmony']['harmony_score'] * 0.20;
        }
        
        // Composition analysis confidence
        if (!empty($seed_analysis['compositional_elements'])) {
            $composition_score = count($seed_analysis['compositional_elements']) / 6; // Max 6 elements
            $factors['composition'] = min($composition_score, 1.0) * 0.25;
        }
        
        // Style fingerprint confidence
        if (!empty($seed_analysis['style_fingerprint'])) {
            $factors['style'] = 0.15; // Base confidence for having a fingerprint
        }
        
        // Research enrichment bonus
        if (!empty($components['cultural'])) {
            $factors['research'] = 0.15;
        }
        
        return min(array_sum($factors), 1.0);
    }
} 