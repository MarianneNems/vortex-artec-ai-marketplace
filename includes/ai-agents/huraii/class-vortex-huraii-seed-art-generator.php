<?php
/**
 * HURAII Seed-Art Generator
 *
 * Implements the generation process using Marianne Nems' Seed-Art Technique.
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage AI_Processing
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_HURAII_Seed_Art_Generator Class
 * 
 * Implements the generation process using the Seed-Art technique.
 *
 * @since 1.0.0
 */
class VORTEX_HURAII_Seed_Art_Generator {
    /**
     * Instance of this class.
     *
     * @since 1.0.0
     * @var object
     */
    protected static $instance = null;
    
    /**
     * Parameters instance
     *
     * @since 1.0.0
     * @var VORTEX_HURAII_Seed_Art_Parameters
     */
    private $parameters;
    
    /**
     * Integration instance
     *
     * @since 1.0.0
     * @var VORTEX_HURAII_Seed_Art_Integration
     */
    private $integration;
    
    /**
     * Model loader instance
     *
     * @since 1.0.0
     * @var VORTEX_Model_Loader
     */
    private $model_loader;
    
    /**
     * Default generation parameters
     *
     * @since 1.0.0
     * @var array
     */
    private $default_params = array(
        'width' => 1024,
        'height' => 1024,
        'steps' => 50,
        'cfg_scale' => 7.5,
        'seed' => -1,
        'sampler' => 'k_euler_ancestral',
        'seed_art_enabled' => true,
        'layer_optimization' => true,
        'style_influence' => 0.7
    );
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Get required instances
        $this->parameters = VORTEX_HURAII_Seed_Art_Parameters::get_instance();
        $this->integration = VORTEX_HURAII_Seed_Art_Integration::get_instance();
        $this->model_loader = VORTEX_Model_Loader::get_instance();
        
        // Setup hooks
        $this->setup_hooks();
    }
    
    /**
     * Get instance of this class.
     *
     * @since 1.0.0
     * @return VORTEX_HURAII_Seed_Art_Generator
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Setup hooks
     *
     * @since 1.0.0
     * @return void
     */
    private function setup_hooks() {
        // AJAX handlers
        add_action('wp_ajax_vortex_generate_with_seed_art', array($this, 'ajax_generate_with_seed_art'));
        
        // Filter for HURAII generation
        add_filter('vortex_huraii_generation_params', array($this, 'prepare_generation_params'), 10, 2);
    }
    
    /**
     * Generate artwork using Seed-Art technique
     *
     * @since 1.0.0
     * @param string $prompt User prompt
     * @param array $settings Generation settings
     * @param int $user_id User ID
     * @return array|WP_Error Generation result or error
     */
    public function generate_artwork($prompt, $settings = array(), $user_id = 0) {
        try {
            // Get current user if not provided
            if ($user_id <= 0) {
                $user_id = get_current_user_id();
            }
            
            // Merge with default parameters
            $settings = wp_parse_args($settings, $this->default_params);
            
            // Ensure Seed-Art is enabled
            $settings['seed_art_enabled'] = true;
            
            // Apply Seed-Art parameters to settings
            $settings = $this->integration->apply_seed_art_to_generation($settings, $user_id);
            
            // Enhance prompt with Seed-Art principles
            $enhanced_prompt = $this->integration->enhance_prompt_with_seed_art($prompt, $settings);
            
            // Start generation timer
            $start_time = microtime(true);
            
            // Generate initial image using appropriate model
            $generation_result = $this->model_loader->run_inference('sd-v2-1', array(
                'prompt' => $enhanced_prompt,
                'negative_prompt' => isset($settings['negative_prompt']) ? $settings['negative_prompt'] : '',
                'width' => $settings['width'],
                'height' => $settings['height'],
                'steps' => $settings['steps'],
                'cfg_scale' => $settings['cfg_scale'],
                'seed' => $settings['seed'] > 0 ? $settings['seed'] : rand(1, 2147483647),
                'sampler' => $settings['sampler'],
                'seed_art_enabled' => true
            ));
            
            if (is_wp_error($generation_result)) {
                return $generation_result;
            }
            
            // Calculate generation time
            $generation_time = round(microtime(true) - $start_time, 2);
            
            // Estimate layer count
            $huraii = VORTEX_HURAII::get_instance();
            $estimated_layers = $huraii->analyze_layer_count($generation_result['image_data']);
            
            // Apply post-processing if needed
            if (!empty($settings['post_processing'])) {
                $generation_result = $this->apply_post_processing($generation_result, $settings['post_processing']);
            }
            
            // Prepare result
            $result = array(
                'success' => true,
                'image_url' => $generation_result['image_url'],
                'image_id' => $generation_result['image_id'],
                'image_data' => $generation_result['image_data'],
                'original_prompt' => $prompt,
                'enhanced_prompt' => $enhanced_prompt,
                'generation_params' => $settings,
                'seed_used' => $generation_result['seed'],
                'generation_time' => $generation_time,
                'estimated_layers' => $estimated_layers
            );
            
            // Generate Seed Art analysis
            $result['seed_art_analysis'] = $this->integration->analyze_generated_artwork(
                $result, 
                $settings
            )['seed_art_analysis'];
            
            // Log successful generation
            $this->log_generation($result, $user_id);
            
            return $result;
        } catch (Exception $e) {
            return new WP_Error('generation_failed', $e->getMessage());
        }
    }
    
    /**
     * Apply post-processing to generated image
     *
     * @since 1.0.0
     * @param array $generation_result Generation result
     * @param array $processing_options Processing options
     * @return array Modified generation result
     */
    private function apply_post_processing($generation_result, $processing_options) {
        // Get image processor
        $img_processor = VORTEX_Img2Img::get_instance();
        
        // Apply each processing option
        foreach ($processing_options as $process => $options) {
            switch ($process) {
                case 'upscale':
                    $scale_factor = isset($options['scale']) ? floatval($options['scale']) : 2.0;
                    $generation_result = $img_processor->upscale_image(
                        $generation_result['image_data'], 
                        $scale_factor, 
                        $options
                    );
                    break;
                    
                case 'style_transfer':
                    $style = isset($options['style']) ? $options['style'] : 'enhance';
                    $strength = isset($options['strength']) ? floatval($options['strength']) : 0.75;
                    $generation_result = $img_processor->apply_style(
                        $generation_result['image_data'], 
                        $style, 
                        $strength
                    );
                    break;
                    
                case 'color_adjust':
                    $generation_result = $img_processor->adjust_colors(
                        $generation_result['image_data'], 
                        $options
                    );
                    break;
                    
                case 'sharpen':
                    $amount = isset($options['amount']) ? floatval($options['amount']) : 0.5;
                    $generation_result = $img_processor->sharpen_image(
                        $generation_result['image_data'], 
                        $amount
                    );
                    break;
            }
        }
        
        return $generation_result;
    }
    
    /**
     * Log generation for analytics and learning
     *
     * @since 1.0.0
     * @param array $result Generation result
     * @param int $user_id User ID
     * @return void
     */
    private function log_generation($result, $user_id) {
        // Log to user's generation history
        $history = get_user_meta($user_id, 'vortex_huraii_history', true);
        if (!is_array($history)) {
            $history = array();
        }
        
        // Add to history (limited to last 50 generations)
        $history_entry = array(
            'timestamp' => current_time('timestamp'),
            'prompt' => $result['original_prompt'],
            'enhanced_prompt' => $result['enhanced_prompt'],
            'image_url' => $result['image_url'],
            'image_id' => $result['image_id'],
            'seed' => $result['seed_used'],
            'generation_time' => $result['generation_time']
        );
        
        array_unshift($history, $history_entry);
        $history = array_slice($history, 0, 50);
        
        update_user_meta($user_id, 'vortex_huraii_history', $history);
        
        // Log for AI learning
        if (class_exists('VORTEX_AI_Learning')) {
            $learning = VORTEX_AI_Learning::get_instance();
            $learning->log_generation_data('HURAII', array(
                'user_id' => $user_id,
                'prompt' => $result['original_prompt'],
                'enhanced_prompt' => $result['enhanced_prompt'],
                'seed_art_enabled' => true,
                'seed_art_analysis' => $result['seed_art_analysis'],
                'generation_params' => $result['generation_params'],
                'success' => true
            ));
        }
    }
    
    /**
     * AJAX handler for generating artwork with Seed-Art
     *
     * @since 1.0.0
     * @return void
     */
    public function ajax_generate_with_seed_art() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_seed_art_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'vortex-ai-marketplace')));
            return;
        }
        
        // Get user ID
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('User not logged in', 'vortex-ai-marketplace')));
            return;
        }
        
        // Get prompt and settings
        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
        if (empty($prompt)) {
            wp_send_json_error(array('message' => __('Prompt is required', 'vortex-ai-marketplace')));
            return;
        }
        
        // Parse settings
        $settings = array();
        
        // Image dimensions
        if (isset($_POST['width']) && is_numeric($_POST['width'])) {
            $settings['width'] = intval($_POST['width']);
        }
        if (isset($_POST['height']) && is_numeric($_POST['height'])) {
            $settings['height'] = intval($_POST['height']);
        }
        
        // Generation parameters
        if (isset($_POST['steps']) && is_numeric($_POST['steps'])) {
            $settings['steps'] = intval($_POST['steps']);
        }
        if (isset($_POST['cfg_scale']) && is_numeric($_POST['cfg_scale'])) {
            $settings['cfg_scale'] = floatval($_POST['cfg_scale']);
        }
        if (isset($_POST['seed']) && is_numeric($_POST['seed'])) {
            $settings['seed'] = intval($_POST['seed']);
        }
        if (isset($_POST['sampler'])) {
            $settings['sampler'] = sanitize_text_field($_POST['sampler']);
        }
        
        // Seed-Art specific parameters
        if (isset($_POST['style_influence']) && is_numeric($_POST['style_influence'])) {
            $settings['style_influence'] = floatval($_POST['style_influence']) / 100; // Convert from percentage
        }
        if (isset($_POST['negative_prompt'])) {
            $settings['negative_prompt'] = sanitize_textarea_field($_POST['negative_prompt']);
        }
        
        // Generate artwork
        $result = $this->generate_artwork($prompt, $settings, $user_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        // Format analysis for display
        $formatted_analysis = $this->format_analysis_for_display($result['seed_art_analysis']);
        
        // Send success response
        wp_send_json_success(array(
            'image_url' => $result['image_url'],
            'prompt' => $result['original_prompt'],
            'enhanced_prompt' => $result['enhanced_prompt'],
            'seed' => $result['seed_used'],
            'generation_time' => $result['generation_time'],
            'analysis' => $formatted_analysis
        ));
    }
    
    /**
     * Format analysis for display
     *
     * @since 1.0.0
     * @param array $analysis Raw analysis data
     * @return array Formatted analysis
     */
    private function format_analysis_for_display($analysis) {
        $formatted = array();
        
        // Format each component
        foreach ($analysis as $component => $data) {
            if ($component === 'error') {
                $formatted['error'] = $data;
                continue;
            }
            
            switch ($component) {
                case 'sacred_geometry':
                    $formatted['Sacred Geometry'] = $this->format_sacred_geometry($data);
                    break;
                    
                case 'color_weight':
                    $formatted['Color Weight'] = $this->format_color_weight($data);
                    break;
                    
                case 'light_shadow':
                    $formatted['Light & Shadow'] = $this->format_light_shadow($data);
                    break;
                    
                case 'texture':
                    $formatted['Texture'] = $this->format_texture($data);
                    break;
                    
                case 'perspective':
                    $formatted['Perspective'] = $this->format_perspective($data);
                    break;
                    
                case 'movement_layering':
                    $formatted['Movement & Layering'] = $this->format_movement_layering($data);
                    break;
                    
                case 'layer_analysis':
                    $formatted['Layer Analysis'] = $this->format_layer_analysis($data);
                    break;
                    
                case 'efficiency':
                    $formatted['Efficiency'] = $this->format_efficiency($data);
                    break;
            }
        }
        
        return $formatted;
    }
    
    /**
     * Format sacred geometry analysis
     *
     * @since 1.0.0
     * @param array $data Sacred geometry data
     * @return array Formatted data
     */
    private function format_sacred_geometry($data) {
        $formatted = array();
        
        if (isset($data['detected_patterns'])) {
            $formatted['Detected Patterns'] = implode(', ', $data['detected_patterns']);
        }
        
        if (isset($data['golden_ratio_alignment'])) {
            $formatted['Golden Ratio Alignment'] = $this->format_percentage($data['golden_ratio_alignment']);
        }
        
        if (isset($data['symmetry_score'])) {
            $formatted['Symmetry Score'] = $this->format_percentage($data['symmetry_score']);
        }
        
        if (isset($data['harmonic_proportions'])) {
            $formatted['Harmonic Proportions'] = $this->format_percentage($data['harmonic_proportions']);
        }
        
        return $formatted;
    }
    
    /**
     * Format color weight analysis
     *
     * @since 1.0.0
     * @param array $data Color weight data
     * @return array Formatted data
     */
    private function format_color_weight($data) {
        $formatted = array();
        
        if (isset($data['dominant_colors'])) {
            $formatted['Dominant Colors'] = implode(', ', $data['dominant_colors']);
        }
        
        if (isset($data['harmony_type'])) {
            $formatted['Harmony Type'] = ucfirst(str_replace('_', ' ', $data['harmony_type']));
        }
        
        if (isset($data['balance_score'])) {
            $formatted['Balance Score'] = $this->format_percentage($data['balance_score']);
        }
        
        if (isset($data['emotional_impact'])) {
            $formatted['Emotional Impact'] = ucfirst($data['emotional_impact']);
        }
        
        return $formatted;
    }
    
    /**
     * Format light and shadow analysis
     *
     * @since 1.0.0
     * @param array $data Light and shadow data
     * @return array Formatted data
     */
    private function format_light_shadow($data) {
        $formatted = array();
        
        if (isset($data['light_direction'])) {
            $formatted['Light Direction'] = ucfirst($data['light_direction']);
        }
        
        if (isset($data['contrast_level'])) {
            $formatted['Contrast Level'] = ucfirst($data['contrast_level']);
        }
        
        if (isset($data['volumetric_quality'])) {
            $formatted['Volumetric Quality'] = $this->format_percentage($data['volumetric_quality']);
        }
        
        if (isset($data['shadow_depth'])) {
            $formatted['Shadow Depth'] = ucfirst($data['shadow_depth']);
        }
        
        return $formatted;
    }
    
    /**
     * Format texture analysis
     *
     * @since 1.0.0
     * @param array $data Texture data
     * @return array Formatted data
     */
    private function format_texture($data) {
        $formatted = array();
        
        if (isset($data['texture_type'])) {
            $formatted['Texture Type'] = ucfirst($data['texture_type']);
        }
        
        if (isset($data['detail_level'])) {
            $formatted['Detail Level'] = ucfirst($data['detail_level']);
        }
        
        if (isset($data['tactile_quality'])) {
            $formatted['Tactile Quality'] = $this->format_percentage($data['tactile_quality']);
        }
        
        if (isset($data['material_simulation'])) {
            $formatted['Material Simulation'] = ucfirst($data['material_simulation']);
        }
        
        return $formatted;
    }
    
    /**
     * Format perspective analysis
     *
     * @since 1.0.0
     * @param array $data Perspective data
     * @return array Formatted data
     */
    private function format_perspective($data) {
        $formatted = array();
        
        if (isset($data['perspective_type'])) {
            $formatted['Perspective Type'] = ucfirst(str_replace('_', ' ', $data['perspective_type']));
        }
        
        if (isset($data['depth_perception'])) {
            $formatted['Depth Perception'] = $this->format_percentage($data['depth_perception']);
        }
        
        if (isset($data['spatial_coherence'])) {
            $formatted['Spatial Coherence'] = $this->format_percentage($data['spatial_coherence']);
        }
        
        if (isset($data['viewpoint'])) {
            $formatted['Viewpoint'] = ucfirst($data['viewpoint']);
        }
        
        return $formatted;
    }
    
    /**
     * Format movement and layering analysis
     *
     * @since 1.0.0
     * @param array $data Movement and layering data
     * @return array Formatted data
     */
    private function format_movement_layering($data) {
        $formatted = array();
        
        if (isset($data['movement_type'])) {
            $formatted['Movement Type'] = ucfirst($data['movement_type']);
        }
        
        if (isset($data['compositional_flow'])) {
            $formatted['Compositional Flow'] = ucfirst($data['compositional_flow']);
        }
        
        if (isset($data['rhythm_score'])) {
            $formatted['Rhythm Score'] = $this->format_percentage($data['rhythm_score']);
        }
        
        if (isset($data['layering_complexity'])) {
            $formatted['Layering Complexity'] = ucfirst($data['layering_complexity']);
        }
        
        return $formatted;
    }
    
    /**
     * Format layer analysis
     *
     * @since 1.0.0
     * @param array $data Layer analysis data
     * @return array Formatted data
     */
    private function format_layer_analysis($data) {
        $formatted = array();
        
        if (isset($data['layer_count'])) {
            $formatted['Layer Count'] = $data['layer_count'];
        }
        
        if (isset($data['transparency_analysis'])) {
            $formatted['Transparency Analysis'] = $data['transparency_analysis'];
        }
        
        return $formatted;
    }
    
    /**
     * Format efficiency analysis
     *
     * @since 1.0.0
     * @param array $data Efficiency data
     * @return array Formatted data
     */
    private function format_efficiency($data) {
        $formatted = array();
        
        if (isset($data['efficiency_analysis'])) {
            $formatted['Efficiency Analysis'] = $data['efficiency_analysis'];
        }
        
        if (isset($data['time_estimate'])) {
            $formatted['Time Estimate'] = $data['time_estimate'];
        }
        
        if (isset($data['optimization_advice'])) {
            $formatted['Optimization Advice'] = $data['optimization_advice'];
        }
        
        return $formatted;
    }
    
    /**
     * Format percentage value
     *
     * @since 1.0.0
     * @param float $value Value to format
     * @return string Formatted percentage
     */
    private function format_percentage($value) {
        return round($value * 100) . '%';
    }
    
    /**
     * Prepare generation parameters
     *
     * @since 1.0.0
     * @param array $params Original parameters
     * @param int $user_id User ID
     * @return array Modified parameters
     */
    public function prepare_generation_params($params, $user_id) {
        // Only modify if Seed-Art is enabled
        if (isset($params['seed_art_enabled']) && $params['seed_art_enabled']) {
            // Apply Seed-Art parameters
            $params = $this->integration->apply_seed_art_to_generation($params, $user_id);
        }
        
        return $params;
    }
} 