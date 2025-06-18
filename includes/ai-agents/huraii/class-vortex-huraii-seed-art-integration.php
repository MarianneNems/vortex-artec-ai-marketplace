<?php
/**
 * HURAII Seed-Art Integration
 *
 * Integrates Marianne Nems' Seed-Art Technique into the HURAII AI art generation process.
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage AI_Processing
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_HURAII_Seed_Art_Integration Class
 * 
 * Integrates Seed-Art parameters into the HURAII generation process.
 *
 * @since 1.0.0
 */
class VORTEX_HURAII_Seed_Art_Integration {
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
     * User seed artwork cache
     *
     * @since 1.0.0
     * @var array
     */
    private $user_seed_cache = array();
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Get the parameters instance
        $this->parameters = VORTEX_HURAII_Seed_Art_Parameters::get_instance();
        
        // Setup hooks
        $this->setup_hooks();
    }
    
    /**
     * Get instance of this class.
     *
     * @since 1.0.0
     * @return VORTEX_HURAII_Seed_Art_Integration
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
        // Filter for integrating with HURAII generation
        add_filter('vortex_huraii_pre_generation', array($this, 'apply_seed_art_to_generation'), 10, 2);
        add_filter('vortex_huraii_prompt_enhancement', array($this, 'enhance_prompt_with_seed_art'), 10, 2);
        add_filter('vortex_huraii_post_generation', array($this, 'analyze_generated_artwork'), 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_vortex_get_user_seed_art', array($this, 'ajax_get_user_seed_art'));
        add_action('wp_ajax_vortex_update_seed_art_preferences', array($this, 'ajax_update_seed_art_preferences'));
    }
    
    /**
     * Apply Seed-Art parameters to generation settings
     *
     * @since 1.0.0
     * @param array $generation_settings Generation settings
     * @param int $user_id User ID
     * @return array Modified generation settings
     */
    public function apply_seed_art_to_generation($generation_settings, $user_id) {
        // Skip if Seed-Art is disabled
        if (isset($generation_settings['seed_art_enabled']) && !$generation_settings['seed_art_enabled']) {
            return $generation_settings;
        }
        
        // Get user's seed artwork analysis
        $user_artwork = $this->get_user_seed_artwork_analysis($user_id);
        
        // Generate Seed-Art configuration
        $seed_art_config = $this->parameters->generate_seed_art_config($user_artwork, $generation_settings);
        
        // Add Seed-Art configuration to generation settings
        $generation_settings['seed_art_config'] = $seed_art_config;
        
        // Apply specific model adjustments based on Seed-Art parameters
        $generation_settings = $this->apply_model_adjustments($generation_settings, $seed_art_config);
        
        return $generation_settings;
    }
    
    /**
     * Enhance prompt with Seed-Art principles
     *
     * @since 1.0.0
     * @param string $prompt Original prompt
     * @param array $generation_settings Generation settings
     * @return string Enhanced prompt
     */
    public function enhance_prompt_with_seed_art($prompt, $generation_settings) {
        // Skip if Seed-Art is disabled
        if (isset($generation_settings['seed_art_enabled']) && !$generation_settings['seed_art_enabled']) {
            return $prompt;
        }
        
        // Get Seed-Art configuration
        $seed_art_config = isset($generation_settings['seed_art_config']) ? 
            $generation_settings['seed_art_config'] : array();
        
        // Enhance the prompt
        $enhanced_prompt = $this->parameters->enhance_prompt($prompt, $seed_art_config);
        
        return $enhanced_prompt;
    }
    
    /**
     * Analyze generated artwork using Seed-Art principles
     *
     * @since 1.0.0
     * @param array $result Generation result
     * @param array $generation_settings Generation settings
     * @return array Modified result with analysis
     */
    public function analyze_generated_artwork($result, $generation_settings) {
        // Skip if Seed-Art is disabled or analysis is disabled
        if (
            (isset($generation_settings['seed_art_enabled']) && !$generation_settings['seed_art_enabled']) ||
            (isset($generation_settings['skip_analysis']) && $generation_settings['skip_analysis'])
        ) {
            return $result;
        }
        
        // Get image data from result
        $image_data = isset($result['image_data']) ? $result['image_data'] : null;
        
        if (!$image_data) {
            return $result;
        }
        
        // Perform Seed-Art analysis
        $analysis = $this->perform_seed_art_analysis($image_data, $generation_settings);
        
        // Add analysis to result
        $result['seed_art_analysis'] = $analysis;
        
        return $result;
    }
    
    /**
     * Get user's seed artwork analysis
     *
     * @since 1.0.0
     * @param int $user_id User ID
     * @return array User's seed artwork analysis
     */
    public function get_user_seed_artwork_analysis($user_id) {
        // Check cache first
        if (isset($this->user_seed_cache[$user_id])) {
            return $this->user_seed_cache[$user_id];
        }
        
        // Get from database
        global $wpdb;
        $artwork_table = $wpdb->prefix . 'vortex_artwork';
        
        $seed_artworks = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $artwork_table WHERE user_id = %d AND is_seed = 1 ORDER BY upload_date DESC LIMIT 5",
                $user_id
            )
        );
        
        if (empty($seed_artworks)) {
            return array();
        }
        
        // Combine analysis from all seed artworks
        $combined_analysis = array();
        
        foreach ($seed_artworks as $artwork) {
            $artwork_analysis = get_post_meta($artwork->id, 'vortex_seed_art_analysis', true);
            
            if (!empty($artwork_analysis)) {
                foreach ($artwork_analysis as $component => $values) {
                    if (!isset($combined_analysis[$component])) {
                        $combined_analysis[$component] = $values;
                    } else {
                        // Merge numeric values with averaging
                        foreach ($values as $key => $value) {
                            if (is_numeric($value) && isset($combined_analysis[$component][$key])) {
                                $combined_analysis[$component][$key] = 
                                    ($combined_analysis[$component][$key] + $value) / 2;
                            } elseif (!isset($combined_analysis[$component][$key])) {
                                $combined_analysis[$component][$key] = $value;
                            }
                        }
                    }
                }
            }
        }
        
        // Cache the result
        $this->user_seed_cache[$user_id] = $combined_analysis;
        
        return $combined_analysis;
    }
    
    /**
     * Apply model adjustments based on Seed-Art parameters
     *
     * @since 1.0.0
     * @param array $generation_settings Generation settings
     * @param array $seed_art_config Seed-Art configuration
     * @return array Modified generation settings
     */
    private function apply_model_adjustments($generation_settings, $seed_art_config) {
        // Adjust guidance scale based on sacred geometry weight
        if (isset($seed_art_config['sacred_geometry']['weight'])) {
            $geometry_weight = $seed_art_config['sacred_geometry']['weight'];
            // Higher sacred geometry weight = higher guidance scale for more precise geometry
            $generation_settings['cfg_scale'] = min(12, max(5, 7 + ($geometry_weight - 0.5) * 10));
        }
        
        // Adjust sampling steps based on texture detail
        if (isset($seed_art_config['texture']['detail_levels'])) {
            $detail_level = $this->get_highest_detail_level($seed_art_config['texture']['detail_levels']);
            // Higher detail = more sampling steps
            $generation_settings['steps'] = min(100, max(30, 40 + $detail_level * 60));
        }
        
        // Adjust sampler based on light and shadow parameters
        if (isset($seed_art_config['light_shadow']['weight']) && $seed_art_config['light_shadow']['weight'] > 0.8) {
            // For high light/shadow emphasis, use samplers that preserve contrast
            $generation_settings['sampler'] = 'k_euler_ancestral';
        }
        
        return $generation_settings;
    }
    
    /**
     * Get highest detail level from texture parameters
     *
     * @since 1.0.0
     * @param array $detail_levels Detail levels array
     * @return float Highest detail level value
     */
    private function get_highest_detail_level($detail_levels) {
        $highest = 0;
        
        foreach ($detail_levels as $level => $value) {
            $highest = max($highest, (float)$value);
        }
        
        return $highest;
    }
    
    /**
     * Perform Seed-Art analysis on generated artwork
     *
     * @since 1.0.0
     * @param string $image_data Base64 encoded image data
     * @param array $generation_settings Generation settings
     * @return array Analysis results
     */
    private function perform_seed_art_analysis($image_data, $generation_settings) {
        // Get the seed art analyzer model
        $model_loader = VORTEX_Model_Loader::get_instance();
        
        try {
            $analyzer_result = $model_loader->run_inference('seed-art-analyzer', array(
                'image_data' => $image_data,
                'analyze_components' => array(
                    'sacred_geometry',
                    'color_weight', 
                    'light_shadow',
                    'texture',
                    'perspective',
                    'artwork_size',
                    'movement_layering'
                )
            ));
            
            if (is_wp_error($analyzer_result)) {
                return array(
                    'error' => $analyzer_result->get_error_message()
                );
            }
            
            // Add layer analysis if enabled
            if (!empty($generation_settings['layer_analysis_enabled'])) {
                $huraii = VORTEX_HURAII::get_instance();
                $layer_count = $huraii->analyze_layer_count($image_data);
                $analyzer_result['components']['layer_analysis'] = array(
                    'layer_count' => $layer_count,
                    'transparency_analysis' => $huraii->analyze_transparency($image_data)
                );
            }
            
            // Add efficiency analysis if enabled
            if (!empty($generation_settings['efficiency_enabled'])) {
                $huraii = VORTEX_HURAII::get_instance();
                $efficiency = $huraii->analyze_efficiency($image_data, $generation_settings);
                $analyzer_result['components']['efficiency'] = $efficiency;
            }
            
            return $analyzer_result['components'];
            
        } catch (Exception $e) {
            return array(
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * AJAX handler for getting user's seed artwork
     *
     * @since 1.0.0
     * @return void
     */
    public function ajax_get_user_seed_art() {
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
        
        // Get seed artwork
        global $wpdb;
        $artwork_table = $wpdb->prefix . 'vortex_artwork';
        
        $seed_artworks = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, title, file_path, upload_date FROM $artwork_table WHERE user_id = %d AND is_seed = 1 ORDER BY upload_date DESC",
                $user_id
            )
        );
        
        // Format for response
        $formatted_artworks = array();
        foreach ($seed_artworks as $artwork) {
            $analysis = get_post_meta($artwork->id, 'vortex_seed_art_analysis', true);
            
            $formatted_artworks[] = array(
                'id' => $artwork->id,
                'title' => $artwork->title,
                'image_url' => $artwork->file_path,
                'upload_date' => $artwork->upload_date,
                'has_analysis' => !empty($analysis)
            );
        }
        
        wp_send_json_success(array(
            'seed_artworks' => $formatted_artworks,
            'count' => count($seed_artworks)
        ));
    }
    
    /**
     * AJAX handler for updating Seed-Art preferences
     *
     * @since 1.0.0
     * @return void
     */
    public function ajax_update_seed_art_preferences() {
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
        
        // Get preferences
        $preferences = isset($_POST['preferences']) ? $_POST['preferences'] : array();
        
        // Validate preferences
        $valid_components = array(
            'sacred_geometry',
            'color_weight',
            'light_shadow',
            'texture',
            'perspective',
            'movement_layering'
        );
        
        $validated_prefs = array();
        
        foreach ($preferences as $component => $value) {
            if (in_array($component, $valid_components)) {
                $validated_prefs[$component] = min(1.0, max(0.0, floatval($value)));
            }
        }
        
        // Save preferences
        update_user_meta($user_id, 'vortex_seed_art_preferences', $validated_prefs);
        
        wp_send_json_success(array(
            'message' => __('Preferences updated successfully', 'vortex-ai-marketplace'),
            'preferences' => $validated_prefs
        ));
    }
} 