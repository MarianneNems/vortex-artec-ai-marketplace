<?php
/**
 * HURAII Seed-Art Parameters
 *
 * Defines the parameters and configuration for Marianne Nems' Seed-Art Technique
 * to be used in the HURAII AI art generation process.
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage AI_Processing
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_HURAII_Seed_Art_Parameters Class
 * 
 * Core class for managing Seed-Art parameters for the HURAII AI art generation system.
 *
 * @since 1.0.0
 */
class VORTEX_HURAII_Seed_Art_Parameters {
    /**
     * Instance of this class.
     *
     * @since 1.0.0
     * @var object
     */
    protected static $instance = null;
    
    /**
     * Sacred geometry parameters
     *
     * @since 1.0.0
     * @var array
     */
    private $sacred_geometry_params = array(
        'golden_ratio' => 1.618033988749895,
        'fibonacci_sequence' => array(1, 1, 2, 3, 5, 8, 13, 21, 34, 55),
        'patterns' => array(
            'vesica_piscis',
            'flower_of_life',
            'metatrons_cube',
            'sri_yantra',
            'seed_of_life'
        ),
        'weight' => 0.85, // Importance weight for generation
        'enhancers' => array(
            'golden ratio',
            'sacred proportions',
            'geometric harmony',
            'divine proportion',
            'harmonic structure'
        )
    );
    
    /**
     * Color weight parameters
     *
     * @since 1.0.0
     * @var array
     */
    private $color_weight_params = array(
        'harmony_types' => array(
            'complementary' => 180,
            'triadic' => 120,
            'tetradic' => 90,
            'analogous' => 30,
            'split_complementary' => array(150, 210),
            'monochromatic' => 0
        ),
        'weight' => 0.92, // Importance weight for generation
        'enhancers' => array(
            'balanced color palette',
            'harmonious color distribution',
            'emotional color resonance',
            'vibrant color harmony',
            'chromatic balance'
        )
    );
    
    /**
     * Light and shadow parameters
     *
     * @since 1.0.0
     * @var array
     */
    private $light_shadow_params = array(
        'lighting_types' => array(
            'dramatic',
            'soft',
            'ambient',
            'directional',
            'rim',
            'volumetric'
        ),
        'contrast_levels' => array(
            'high' => 0.8,
            'medium' => 0.5,
            'low' => 0.3
        ),
        'weight' => 0.88, // Importance weight for generation
        'enhancers' => array(
            'dramatic lighting',
            'balanced shadows',
            'volumetric light',
            'dynamic contrast',
            'atmospheric illumination'
        )
    );
    
    /**
     * Texture parameters
     *
     * @since 1.0.0
     * @var array
     */
    private $texture_params = array(
        'texture_types' => array(
            'smooth',
            'rough',
            'granular',
            'metallic',
            'organic',
            'fabric',
            'liquid',
            'crystalline'
        ),
        'detail_levels' => array(
            'high' => 0.9,
            'medium' => 0.6,
            'low' => 0.3
        ),
        'weight' => 0.78, // Importance weight for generation
        'enhancers' => array(
            'rich texture',
            'detailed surface',
            'tactile quality',
            'textural depth',
            'material richness'
        )
    );
    
    /**
     * Perspective parameters
     *
     * @since 1.0.0
     * @var array
     */
    private $perspective_params = array(
        'perspective_types' => array(
            'one_point',
            'two_point',
            'three_point',
            'isometric',
            'fish_eye',
            'aerial'
        ),
        'depth_levels' => array(
            'deep' => 0.9,
            'moderate' => 0.6,
            'shallow' => 0.3
        ),
        'weight' => 0.82, // Importance weight for generation
        'enhancers' => array(
            'dimensional depth',
            'correct perspective',
            'spatial harmony',
            'depth perception',
            'visual distance'
        )
    );
    
    /**
     * Movement and layering parameters
     *
     * @since 1.0.0
     * @var array
     */
    private $movement_layering_params = array(
        'movement_types' => array(
            'flowing',
            'radial',
            'spiral',
            'zigzag',
            'rhythmic',
            'chaotic'
        ),
        'layer_complexity' => array(
            'high' => 7,
            'medium' => 5,
            'low' => 3
        ),
        'weight' => 0.75, // Importance weight for generation
        'enhancers' => array(
            'dynamic composition',
            'layered elements',
            'visual flow',
            'rhythmic arrangement',
            'harmonious movement'
        )
    );
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Initialize any dynamic parameters
        $this->initialize_dynamic_parameters();
    }
    
    /**
     * Get instance of this class.
     *
     * @since 1.0.0
     * @return VORTEX_HURAII_Seed_Art_Parameters
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize any dynamic parameters from database or user settings
     *
     * @since 1.0.0
     * @return void
     */
    private function initialize_dynamic_parameters() {
        // Get user preferences if available
        $user_id = get_current_user_id();
        if ($user_id > 0) {
            $user_preferences = get_user_meta($user_id, 'vortex_seed_art_preferences', true);
            
            if (!empty($user_preferences)) {
                // Apply user preferences to parameter weights
                foreach ($user_preferences as $param_key => $value) {
                    $property = $param_key . '_params';
                    if (property_exists($this, $property) && isset($this->$property['weight'])) {
                        $this->$property['weight'] = floatval($value);
                    }
                }
            }
        }
    }
    
    /**
     * Get all Seed-Art parameters
     *
     * @since 1.0.0
     * @return array All Seed-Art parameters
     */
    public function get_all_parameters() {
        return array(
            'sacred_geometry' => $this->sacred_geometry_params,
            'color_weight' => $this->color_weight_params,
            'light_shadow' => $this->light_shadow_params,
            'texture' => $this->texture_params,
            'perspective' => $this->perspective_params,
            'movement_layering' => $this->movement_layering_params
        );
    }
    
    /**
     * Get enhancers for prompt augmentation
     *
     * @since 1.0.0
     * @return array Enhancers for each parameter
     */
    public function get_enhancers() {
        return array(
            'sacred_geometry' => $this->sacred_geometry_params['enhancers'],
            'color_weight' => $this->color_weight_params['enhancers'],
            'light_shadow' => $this->light_shadow_params['enhancers'],
            'texture' => $this->texture_params['enhancers'],
            'perspective' => $this->perspective_params['enhancers'],
            'movement_layering' => $this->movement_layering_params['enhancers']
        );
    }
    
    /**
     * Get parameter weights
     *
     * @since 1.0.0
     * @return array Weights for each parameter
     */
    public function get_weights() {
        return array(
            'sacred_geometry' => $this->sacred_geometry_params['weight'],
            'color_weight' => $this->color_weight_params['weight'],
            'light_shadow' => $this->light_shadow_params['weight'],
            'texture' => $this->texture_params['weight'],
            'perspective' => $this->perspective_params['weight'],
            'movement_layering' => $this->movement_layering_params['weight']
        );
    }
    
    /**
     * Generate seed art configuration for artwork generation
     *
     * @since 1.0.0
     * @param array $user_artwork Analysis of user's seed artwork
     * @param array $generation_params Generation parameters
     * @return array Seed art configuration for generation
     */
    public function generate_seed_art_config($user_artwork, $generation_params) {
        $config = array();
        
        // Start with default parameters
        $all_params = $this->get_all_parameters();
        
        // If we have user artwork analysis, adjust parameters based on their style
        if (!empty($user_artwork)) {
            foreach ($all_params as $param_key => $param_value) {
                if (isset($user_artwork[$param_key])) {
                    $config[$param_key] = $this->blend_parameters(
                        $param_value,
                        $user_artwork[$param_key],
                        isset($generation_params['style_influence']) ? $generation_params['style_influence'] : 0.7
                    );
                } else {
                    $config[$param_key] = $param_value;
                }
            }
        } else {
            $config = $all_params;
        }
        
        // Apply any specific generation parameters
        if (!empty($generation_params['seed_art_params'])) {
            foreach ($generation_params['seed_art_params'] as $key => $value) {
                if (isset($config[$key])) {
                    $config[$key] = array_merge($config[$key], $value);
                }
            }
        }
        
        return $config;
    }
    
    /**
     * Blend default parameters with user's style
     *
     * @since 1.0.0
     * @param array $default_params Default parameters
     * @param array $user_params User's style parameters
     * @param float $influence_factor How much the user's style influences (0-1)
     * @return array Blended parameters
     */
    private function blend_parameters($default_params, $user_params, $influence_factor) {
        $result = $default_params;
        
        // Only blend numeric values and simple arrays
        foreach ($user_params as $key => $value) {
            if (isset($default_params[$key])) {
                if (is_numeric($value) && is_numeric($default_params[$key])) {
                    // Blend numeric values
                    $result[$key] = $default_params[$key] * (1 - $influence_factor) + $value * $influence_factor;
                } elseif (is_array($value) && is_array($default_params[$key]) && !is_assoc($default_params[$key])) {
                    // For simple arrays, prefer user values based on influence factor
                    $result[$key] = $influence_factor >= 0.5 ? $value : $default_params[$key];
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Enhance a generation prompt with Seed-Art principles
     *
     * @since 1.0.0
     * @param string $prompt Original prompt
     * @param array $seed_art_config Seed art configuration
     * @return string Enhanced prompt
     */
    public function enhance_prompt($prompt, $seed_art_config = array()) {
        // Use default enhancers if no config provided
        $enhancers = $this->get_enhancers();
        $weights = $this->get_weights();
        
        // If we have a specific config, use those enhancers
        if (!empty($seed_art_config)) {
            foreach ($enhancers as $key => $value) {
                if (isset($seed_art_config[$key]['enhancers'])) {
                    $enhancers[$key] = $seed_art_config[$key]['enhancers'];
                }
                if (isset($seed_art_config[$key]['weight'])) {
                    $weights[$key] = $seed_art_config[$key]['weight'];
                }
            }
        }
        
        // Sort parameters by weight to prioritize more important ones
        arsort($weights);
        
        // Only add enhancements if they're not already in the prompt
        foreach ($weights as $component => $weight) {
            // Skip if weight is too low
            if ($weight < 0.5) {
                continue;
            }
            
            $component_enhancers = $enhancers[$component];
            $enhancer_added = false;
            
            // Check if any enhancer is already in the prompt
            foreach ($component_enhancers as $enhancer) {
                if (stripos($prompt, $enhancer) !== false) {
                    $enhancer_added = true;
                    break;
                }
            }
            
            // Check if component name is in the prompt
            if (!$enhancer_added && stripos($prompt, str_replace('_', ' ', $component)) === false) {
                $selected_enhancer = $component_enhancers[array_rand($component_enhancers)];
                $prompt .= ", with " . $selected_enhancer;
            }
        }
        
        return $prompt;
    }
}

/**
 * Helper function to check if array is associative
 *
 * @param array $array Array to check
 * @return bool True if associative
 */
function is_assoc($array) {
    if (!is_array($array)) return false;
    return array_keys($array) !== range(0, count($array) - 1);
} 