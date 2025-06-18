<?php
/**
 * HURAII Real-time Extension
 *
 * Extends HURAII with real-time communication capabilities for the orchestration system
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * HURAII Real-time Extension Class
 */
class VORTEX_HURAII_RT_Extension {
    /**
     * The single instance of this class
     */
    private static $instance = null;

    /**
     * Reference to the main HURAII instance
     */
    private $huraii = null;

    /**
     * Cached external context data from other agents
     */
    private $external_context = array();

    /**
     * Get instance - Singleton pattern
     *
     * @return VORTEX_HURAII_RT_Extension
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Get reference to the main HURAII instance
        if (class_exists('VORTEX_HURAII')) {
            $this->huraii = VORTEX_HURAII::get_instance();
        }

        // Setup hooks
        $this->setup_hooks();
    }

    /**
     * Setup hooks
     */
    private function setup_hooks() {
        // Hook into HURAII's initialization
        add_action('vortex_huraii_initialized', array($this, 'extend_huraii'), 10, 1);
        
        // Add enhancement filters
        add_filter('vortex_huraii_pre_creative_feedback', array($this, 'enhance_creative_feedback_with_external_data'), 10, 2);
        add_filter('vortex_huraii_pre_market_analysis', array($this, 'enhance_market_analysis_with_external_data'), 10, 2);
        
        // Add insight sharing
        add_action('vortex_huraii_creative_insight_generated', array($this, 'share_creative_insight'), 10, 3);
        add_action('vortex_huraii_market_insight_generated', array($this, 'share_market_insight'), 10, 3);
        
        // Add real-time collaboration integration
        add_action('vortex_collaboration_canvas_updated', array($this, 'analyze_canvas_update'), 10, 2);
    }

    /**
     * Extend HURAII with real-time methods
     *
     * @param VORTEX_HURAII $huraii HURAII instance
     */
    public function extend_huraii($huraii) {
        // Replace our reference with the passed instance
        $this->huraii = $huraii;
        
        // Add methods to HURAII if they don't already exist
        if (!method_exists($huraii, 'process_cross_agent_insight')) {
            // Use a closure to add the method to the HURAII instance
            $self = $this;
            $huraii->process_cross_agent_insight = function($insight) use ($self) {
                return $self->process_cross_agent_insight($insight);
            };
        }
        
        if (!method_exists($huraii, 'update_external_context')) {
            $self = $this;
            $huraii->update_external_context = function($context) use ($self) {
                return $self->update_external_context($context);
            };
        }
        
        if (!method_exists($huraii, 'provide_realtime_creative_feedback')) {
            $self = $this;
            $huraii->provide_realtime_creative_feedback = function($artwork_data) use ($self) {
                return $self->provide_realtime_creative_feedback($artwork_data);
            };
        }
        
        // Announce HURAII to the orchestrator
        $this->announce_to_orchestrator();
    }

    /**
     * Announce HURAII to the orchestrator
     */
    private function announce_to_orchestrator() {
        // Trigger the agent interaction action for the orchestrator
        do_action('vortex_agent_interaction', 'huraii', 'initialize', array(
            'version' => VORTEX_VERSION,
            'capabilities' => array(
                'creative_feedback' => true,
                'style_analysis' => true,
                'market_trends' => true,
                'realtime_feedback' => true,
                'collaboration_support' => true,
                'cross_agent_communication' => true
            )
        ));
    }

    /**
     * Process insight from another agent
     *
     * @param array $insight Insight data
     * @return bool Success
     */
    public function process_cross_agent_insight($insight) {
        if (empty($insight['source_agent']) || empty($insight['insight_type']) || empty($insight['data'])) {
            return false;
        }
        
        // Store in external context
        $source = $insight['source_agent'];
        $type = $insight['insight_type'];
        $data = $insight['data'];
        
        if (!isset($this->external_context[$source])) {
            $this->external_context[$source] = array();
        }
        
        if (!isset($this->external_context[$source]['insights'])) {
            $this->external_context[$source]['insights'] = array();
        }
        
        // Add to insights cache with timestamp
        $this->external_context[$source]['insights'][$type] = array(
            'data' => $data,
            'timestamp' => time()
        );
        
        // Log the insight reception
        $this->log_cross_agent_activity('received_insight', $source, $type, $data);
        
        // Process specific types of insights
        if (strpos($type, 'gallery_') === 0 || strpos($type, 'winners_') === 0) {
            // For example, adjust HURAII's internal style preferences based on gallery insights
            if (method_exists($this->huraii, 'update_style_preferences_from_external_data')) {
                $this->huraii->update_style_preferences_from_external_data($source, $type, $data);
                return true;
            }
        }
        
        return false;
    }

    /**
     * Update external context from another agent
     *
     * @param array $context Context data
     * @return bool Success
     */
    public function update_external_context($context) {
        if (empty($context['source_agent']) || empty($context['context_type']) || empty($context['data'])) {
            return false;
        }
        
        // Store in external context
        $source = $context['source_agent'];
        $type = $context['context_type'];
        $data = $context['data'];
        
        if (!isset($this->external_context[$source])) {
            $this->external_context[$source] = array();
        }
        
        if (!isset($this->external_context[$source]['context'])) {
            $this->external_context[$source]['context'] = array();
        }
        
        // Add to context cache with timestamp
        $this->external_context[$source]['context'][$type] = array(
            'data' => $data,
            'timestamp' => time()
        );
        
        // Log the context update
        $this->log_cross_agent_activity('received_context', $source, $type, $data);
        
        return true;
    }

    /**
     * Enhance creative feedback with external data
     *
     * @param array $feedback_data Current feedback data
     * @param array $args Feedback arguments
     * @return array Modified feedback data
     */
    public function enhance_creative_feedback_with_external_data($feedback_data, $args) {
        // Check if we have external context from other agents
        if (empty($this->external_context)) {
            return $feedback_data;
        }
        
        // Add a special section for external agent influences
        if (!isset($feedback_data['external_influences'])) {
            $feedback_data['external_influences'] = array();
        }
        
        // Process insights from each agent
        foreach ($this->external_context as $agent => $data) {
            if (isset($data['insights'])) {
                foreach ($data['insights'] as $type => $insight) {
                    // Only use recent insights (less than 48 hours old)
                    if (time() - $insight['timestamp'] > 172800) {
                        continue;
                    }
                    
                    // Add specific modifications based on insight type
                    switch ($type) {
                        case 'gallery_trend_analysis':
                            // Adjust style recommendations based on gallery trends
                            if (isset($insight['data']['trending_styles'])) {
                                foreach ($insight['data']['trending_styles'] as $style => $score) {
                                    if (!isset($feedback_data['style_recommendations'])) {
                                        $feedback_data['style_recommendations'] = array();
                                    }
                                    
                                    // Add or adjust style recommendation
                                    if (isset($feedback_data['style_recommendations'][$style])) {
                                        $feedback_data['style_recommendations'][$style]['score'] += $score * 0.3; // 30% influence
                                    } else {
                                        $feedback_data['style_recommendations'][$style] = array(
                                            'name' => $style,
                                            'score' => $score * 0.3,
                                            'reason' => __('This style is currently trending in the gallery.', 'vortex-ai-marketplace')
                                        );
                                    }
                                    
                                    // Record the influence
                                    $feedback_data['external_influences'][] = array(
                                        'agent' => $agent,
                                        'type' => 'style_recommendation',
                                        'subject' => $style,
                                        'score' => $score * 0.3
                                    );
                                }
                            }
                            break;
                            
                        case 'winners_analysis':
                            // Adjust technique recommendations based on winning artworks
                            if (isset($insight['data']['winning_techniques'])) {
                                foreach ($insight['data']['winning_techniques'] as $technique => $score) {
                                    if (!isset($feedback_data['technique_recommendations'])) {
                                        $feedback_data['technique_recommendations'] = array();
                                    }
                                    
                                    // Add or adjust technique recommendation
                                    if (isset($feedback_data['technique_recommendations'][$technique])) {
                                        $feedback_data['technique_recommendations'][$technique]['score'] += $score * 0.35; // 35% influence
                                    } else {
                                        $feedback_data['technique_recommendations'][$technique] = array(
                                            'name' => $technique,
                                            'score' => $score * 0.35,
                                            'reason' => __('This technique has been successful in winning artworks.', 'vortex-ai-marketplace')
                                        );
                                    }
                                    
                                    // Record the influence
                                    $feedback_data['external_influences'][] = array(
                                        'agent' => $agent,
                                        'type' => 'technique_recommendation',
                                        'subject' => $technique,
                                        'score' => $score * 0.35
                                    );
                                }
                            }
                            break;
                    }
                }
            }
        }
        
        // Trigger an action to indicate this data has been enhanced with external insights
        do_action('vortex_huraii_enhanced_creative_feedback', $feedback_data, $this->external_context);
        
        return $feedback_data;
    }

    /**
     * Enhance market analysis with external data
     *
     * @param array $analysis_data Current analysis data
     * @param array $args Analysis arguments
     * @return array Modified analysis data
     */
    public function enhance_market_analysis_with_external_data($analysis_data, $args) {
        // Check if we have external context from other agents
        if (empty($this->external_context)) {
            return $analysis_data;
        }
        
        // Add a special section for external agent influences
        if (!isset($analysis_data['external_influences'])) {
            $analysis_data['external_influences'] = array();
        }
        
        // Process insights from each agent
        foreach ($this->external_context as $agent => $data) {
            if (isset($data['insights'])) {
                foreach ($data['insights'] as $type => $insight) {
                    // Only use recent insights (less than 7 days old for market analysis)
                    if (time() - $insight['timestamp'] > 604800) {
                        continue;
                    }
                    
                    // Add specific modifications based on insight type
                    switch ($type) {
                        case 'collector_preferences':
                            // Adjust market demand predictions based on collector preferences
                            if (isset($insight['data']['category_preferences'])) {
                                foreach ($insight['data']['category_preferences'] as $category => $score) {
                                    if (!isset($analysis_data['market_demand'])) {
                                        $analysis_data['market_demand'] = array();
                                    }
                                    
                                    // Add or adjust market demand
                                    if (isset($analysis_data['market_demand'][$category])) {
                                        $analysis_data['market_demand'][$category]['score'] += $score * 0.4; // 40% influence
                                    } else {
                                        $analysis_data['market_demand'][$category] = array(
                                            'category' => $category,
                                            'score' => $score * 0.4,
                                            'trend' => ($score > 0.7) ? 'rising' : (($score < 0.3) ? 'falling' : 'stable'),
                                            'reason' => __('Based on collector preferences.', 'vortex-ai-marketplace')
                                        );
                                    }
                                    
                                    // Record the influence
                                    $analysis_data['external_influences'][] = array(
                                        'agent' => $agent,
                                        'type' => 'market_demand',
                                        'subject' => $category,
                                        'score' => $score * 0.4
                                    );
                                }
                            }
                            break;
                    }
                }
            }
        }
        
        // Trigger an action to indicate this data has been enhanced with external insights
        do_action('vortex_huraii_enhanced_market_analysis', $analysis_data, $this->external_context);
        
        return $analysis_data;
    }

    /**
     * Share creative insight with other agents via the orchestrator
     *
     * @param string $insight_type Type of insight
     * @param array $data Insight data
     * @param array $context Context information
     */
    public function share_creative_insight($insight_type, $data, $context) {
        // Trigger the insight sharing action for the orchestrator
        do_action('vortex_agent_insight_generated', 'huraii', 'creative_' . $insight_type, $data);
        
        // Log the insight sharing
        $this->log_cross_agent_activity('shared_insight', 'orchestrator', 'creative_' . $insight_type, $data);
    }

    /**
     * Share market insight with other agents via the orchestrator
     *
     * @param string $insight_type Type of insight
     * @param array $data Insight data
     * @param array $context Context information
     */
    public function share_market_insight($insight_type, $data, $context) {
        // Trigger the insight sharing action for the orchestrator
        do_action('vortex_agent_insight_generated', 'huraii', 'market_' . $insight_type, $data);
        
        // Log the insight sharing
        $this->log_cross_agent_activity('shared_insight', 'orchestrator', 'market_' . $insight_type, $data);
    }

    /**
     * Analyze canvas update from real-time collaboration
     *
     * @param array $update Update data
     * @param string $session_id Session ID
     */
    public function analyze_canvas_update($update, $session_id) {
        // Only analyze every 10th update to avoid overloading
        static $update_counter = 0;
        $update_counter++;
        
        if ($update_counter % 10 !== 0) {
            return;
        }
        
        // If the update contains a canvas_state, analyze it
        if (isset($update['canvas_state']) && $this->huraii && method_exists($this->huraii, 'analyze_artwork_image')) {
            // Process the canvas state to an image format HURAII can analyze
            $image_data = $this->extract_image_from_canvas_state($update['canvas_state']);
            
            if ($image_data) {
                // Get feedback from HURAII
                $feedback = $this->huraii->analyze_artwork_image($image_data, array(
                    'context' => 'realtime_collaboration',
                    'session_id' => $session_id
                ));
                
                if ($feedback) {
                    // Send the feedback to the collaboration session
                    do_action('vortex_collaboration_ai_feedback', $session_id, 'huraii', $feedback);
                    
                    // Also share with other agents via the orchestrator
                    do_action('vortex_agent_insight_generated', 'huraii', 'collaboration_feedback', array(
                        'session_id' => $session_id,
                        'feedback' => $feedback
                    ));
                }
            }
        }
    }

    /**
     * Extract image data from canvas state
     *
     * @param array $canvas_state Canvas state
     * @return string|false Image data URL or false on failure
     */
    private function extract_image_from_canvas_state($canvas_state) {
        if (empty($canvas_state) || empty($canvas_state['layers'])) {
            return false;
        }
        
        // For simple implementation, just return the data from the first visible layer
        foreach ($canvas_state['layers'] as $layer) {
            if ($layer['visible'] && !empty($layer['data'])) {
                return $layer['data'];
            }
        }
        
        return false;
    }

    /**
     * Provide real-time creative feedback for in-progress artwork
     *
     * @param array $artwork_data Artwork data
     * @return array Feedback data
     */
    public function provide_realtime_creative_feedback($artwork_data) {
        if (!$this->huraii || empty($artwork_data)) {
            return array();
        }
        
        // Basic feedback structure
        $feedback = array(
            'timestamp' => time(),
            'suggestions' => array(),
            'elements' => array(),
            'composition_analysis' => array(),
            'technical_suggestions' => array()
        );
        
        // If HURAII has an analysis method, use it
        if (method_exists($this->huraii, 'analyze_artwork_elements')) {
            $elements = $this->huraii->analyze_artwork_elements($artwork_data);
            if ($elements) {
                $feedback['elements'] = $elements;
            }
        }
        
        // Add composition analysis
        if (method_exists($this->huraii, 'analyze_composition')) {
            $composition = $this->huraii->analyze_composition($artwork_data);
            if ($composition) {
                $feedback['composition_analysis'] = $composition;
            }
        }
        
        // Generate suggestions
        $feedback['suggestions'] = array(
            array(
                'type' => 'general',
                'text' => __('Consider adjusting the balance of elements to improve visual flow.', 'vortex-ai-marketplace'),
                'confidence' => 0.85
            ),
            array(
                'type' => 'technical',
                'text' => __('Try increasing contrast in the focal area to draw more attention.', 'vortex-ai-marketplace'),
                'confidence' => 0.78
            ),
            array(
                'type' => 'style',
                'text' => __('Your current style has elements that are trending in similar artwork.', 'vortex-ai-marketplace'),
                'confidence' => 0.92
            )
        );
        
        // Add technical suggestions
        $feedback['technical_suggestions'] = array(
            array(
                'aspect' => 'color',
                'suggestion' => __('Consider a warmer color palette for more visual impact.', 'vortex-ai-marketplace'),
                'importance' => 'medium'
            ),
            array(
                'aspect' => 'composition',
                'suggestion' => __('The rule of thirds could be applied more effectively here.', 'vortex-ai-marketplace'),
                'importance' => 'high'
            )
        );
        
        // Enhance with external agent context if available
        if (!empty($this->external_context)) {
            foreach ($this->external_context as $agent => $data) {
                if (isset($data['insights']['creative_trends'])) {
                    $insight = $data['insights']['creative_trends'];
                    
                    // Only use recent insights
                    if (time() - $insight['timestamp'] > 86400) {
                        continue;
                    }
                    
                    // Add a suggestion based on the external insight
                    $feedback['suggestions'][] = array(
                        'type' => 'market',
                        'text' => sprintf(
                            __('Based on recent market trends, %s suggests %s could enhance market appeal.', 'vortex-ai-marketplace'),
                            $agent,
                            isset($insight['data']['top_suggestion']) ? $insight['data']['top_suggestion'] : __('adjusting your style slightly', 'vortex-ai-marketplace')
                        ),
                        'confidence' => 0.7,
                        'source_agent' => $agent
                    );
                }
            }
        }
        
        return $feedback;
    }

    /**
     * Log cross-agent activity
     *
     * @param string $activity Activity type
     * @param string $agent_name Agent name
     * @param string $data_type Data type
     * @param array $data Activity data
     */
    private function log_cross_agent_activity($activity, $agent_name, $data_type, $data) {
        // If HURAII has a logging method, use that
        if ($this->huraii && method_exists($this->huraii, 'log_activity')) {
            $this->huraii->log_activity('cross_agent_' . $activity, array(
                'agent' => $agent_name,
                'data_type' => $data_type,
                'timestamp' => current_time('mysql')
            ));
        }
        
        // Also log to WordPress debug log if enabled
        if (defined('WP_DEBUG') && WP_DEBUG === true && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG === true) {
            error_log(sprintf(
                'HURAII RT Extension: %s from %s - Type: %s',
                $activity,
                $agent_name,
                $data_type
            ));
        }
    }
}

// Initialize HURAII RT Extension
function vortex_initialize_huraii_rt_extension() {
    return VORTEX_HURAII_RT_Extension::get_instance();
}
add_action('plugins_loaded', 'vortex_initialize_huraii_rt_extension', 20); // After HURAII is loaded 