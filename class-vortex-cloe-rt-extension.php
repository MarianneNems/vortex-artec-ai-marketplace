<?php
/**
 * CLOE Real-time Extension
 *
 * Extends CLOE with real-time communication capabilities for the orchestration system
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CLOE Real-time Extension Class
 */
class VORTEX_CLOE_RT_Extension {
    /**
     * The single instance of this class
     */
    private static $instance = null;

    /**
     * Reference to the main CLOE instance
     */
    private $cloe = null;

    /**
     * Cached external context data from other agents
     */
    private $external_context = array();

    /**
     * Get instance - Singleton pattern
     *
     * @return VORTEX_CLOE_RT_Extension
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
        // Get reference to the main CLOE instance
        if (class_exists('VORTEX_CLOE')) {
            $this->cloe = VORTEX_CLOE::get_instance();
        }

        // Setup hooks
        $this->setup_hooks();
    }

    /**
     * Setup hooks
     */
    private function setup_hooks() {
        // Hook into CLOE's initialization
        add_action('vortex_cloe_initialized', array($this, 'extend_cloe'), 10, 1);
        
        // Add filter for gallery shuffle enhancement with external context
        add_filter('vortex_cloe_pre_gallery_shuffle', array($this, 'enhance_gallery_shuffle_with_external_data'), 10, 2);
        
        // Add filter for daily winners selection with external context
        add_filter('vortex_cloe_pre_daily_winners', array($this, 'enhance_daily_winners_with_external_data'), 10, 2);
        
        // Add insight sharing
        add_action('vortex_cloe_gallery_insight_generated', array($this, 'share_gallery_insight'), 10, 3);
        add_action('vortex_cloe_winners_insight_generated', array($this, 'share_winners_insight'), 10, 3);
    }

    /**
     * Extend CLOE with real-time methods
     *
     * @param VORTEX_CLOE $cloe CLOE instance
     */
    public function extend_cloe($cloe) {
        // Replace our reference with the passed instance
        $this->cloe = $cloe;
        
        // Add methods to CLOE if they don't already exist
        if (!method_exists($cloe, 'process_cross_agent_insight')) {
            // Use a closure to add the method to the CLOE instance
            $self = $this;
            $cloe->process_cross_agent_insight = function($insight) use ($self) {
                return $self->process_cross_agent_insight($insight);
            };
        }
        
        if (!method_exists($cloe, 'update_external_context')) {
            $self = $this;
            $cloe->update_external_context = function($context) use ($self) {
                return $self->update_external_context($context);
            };
        }
        
        // Announce CLOE to the orchestrator
        $this->announce_to_orchestrator();
    }

    /**
     * Announce CLOE to the orchestrator
     */
    private function announce_to_orchestrator() {
        // Trigger the agent interaction action for the orchestrator
        do_action('vortex_agent_interaction', 'cloe', 'initialize', array(
            'version' => VORTEX_VERSION,
            'capabilities' => array(
                'gallery_shuffle' => true,
                'daily_winners' => true,
                'trend_analysis' => true,
                'user_preferences' => true,
                'artwork_metadata' => true,
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
        
        // Trigger CLOE to process this insight if it relates to gallery or winners
        if (in_array($type, array('artist_trends', 'market_trends', 'user_engagement', 'collector_preferences'))) {
            if (method_exists($this->cloe, 'analyze_external_trend_data')) {
                $this->cloe->analyze_external_trend_data($source, $type, $data);
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
     * Enhance gallery shuffle with external data
     *
     * @param array $shuffle_data Current shuffle data
     * @param array $args Shuffle arguments
     * @return array Modified shuffle data
     */
    public function enhance_gallery_shuffle_with_external_data($shuffle_data, $args) {
        // Check if we have external context from other agents
        if (empty($this->external_context)) {
            return $shuffle_data;
        }
        
        // Add a special section for external agent influences
        if (!isset($shuffle_data['external_influences'])) {
            $shuffle_data['external_influences'] = array();
        }
        
        // Process insights from each agent
        foreach ($this->external_context as $agent => $data) {
            if (isset($data['insights'])) {
                foreach ($data['insights'] as $type => $insight) {
                    // Only use recent insights (less than 24 hours old)
                    if (time() - $insight['timestamp'] > 86400) {
                        continue;
                    }
                    
                    // Add specific modifications based on insight type
                    switch ($type) {
                        case 'artist_trends':
                            // Boost artists trending according to other agents
                            if (isset($insight['data']['trending_artists'])) {
                                foreach ($insight['data']['trending_artists'] as $artist_id => $score) {
                                    if (!isset($shuffle_data['artist_boosts'])) {
                                        $shuffle_data['artist_boosts'] = array();
                                    }
                                    
                                    // Add or increase the boost for this artist
                                    if (isset($shuffle_data['artist_boosts'][$artist_id])) {
                                        $shuffle_data['artist_boosts'][$artist_id] += $score * 0.5; // Reduce external influence
                                    } else {
                                        $shuffle_data['artist_boosts'][$artist_id] = $score * 0.5;
                                    }
                                    
                                    // Record the influence
                                    $shuffle_data['external_influences'][] = array(
                                        'agent' => $agent,
                                        'type' => 'artist_boost',
                                        'subject' => $artist_id,
                                        'score' => $score * 0.5
                                    );
                                }
                            }
                            break;
                            
                        case 'market_trends':
                            // Adjust category weights based on market insights
                            if (isset($insight['data']['category_trends'])) {
                                foreach ($insight['data']['category_trends'] as $category => $score) {
                                    if (!isset($shuffle_data['category_weights'])) {
                                        $shuffle_data['category_weights'] = array();
                                    }
                                    
                                    // Add or adjust category weight
                                    if (isset($shuffle_data['category_weights'][$category])) {
                                        $shuffle_data['category_weights'][$category] *= (1 + ($score * 0.3)); // 30% influence
                                    } else {
                                        $shuffle_data['category_weights'][$category] = 1 + ($score * 0.3);
                                    }
                                    
                                    // Record the influence
                                    $shuffle_data['external_influences'][] = array(
                                        'agent' => $agent,
                                        'type' => 'category_weight',
                                        'subject' => $category,
                                        'score' => $score * 0.3
                                    );
                                }
                            }
                            break;
                            
                        case 'user_engagement':
                            // Boost artwork with high engagement
                            if (isset($insight['data']['artwork_engagement'])) {
                                foreach ($insight['data']['artwork_engagement'] as $artwork_id => $score) {
                                    if (!isset($shuffle_data['artwork_boosts'])) {
                                        $shuffle_data['artwork_boosts'] = array();
                                    }
                                    
                                    // Add or increase the boost for this artwork
                                    if (isset($shuffle_data['artwork_boosts'][$artwork_id])) {
                                        $shuffle_data['artwork_boosts'][$artwork_id] += $score * 0.4; // 40% influence
                                    } else {
                                        $shuffle_data['artwork_boosts'][$artwork_id] = $score * 0.4;
                                    }
                                    
                                    // Record the influence
                                    $shuffle_data['external_influences'][] = array(
                                        'agent' => $agent,
                                        'type' => 'artwork_boost',
                                        'subject' => $artwork_id,
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
        do_action('vortex_cloe_enhanced_gallery_shuffle', $shuffle_data, $this->external_context);
        
        return $shuffle_data;
    }

    /**
     * Enhance daily winners selection with external data
     *
     * @param array $winners_data Current winners data
     * @param array $args Winners selection arguments
     * @return array Modified winners data
     */
    public function enhance_daily_winners_with_external_data($winners_data, $args) {
        // Check if we have external context from other agents
        if (empty($this->external_context)) {
            return $winners_data;
        }
        
        // Add a special section for external agent influences
        if (!isset($winners_data['external_influences'])) {
            $winners_data['external_influences'] = array();
        }
        
        // Process insights from each agent
        foreach ($this->external_context as $agent => $data) {
            if (isset($data['insights'])) {
                foreach ($data['insights'] as $type => $insight) {
                    // Only use recent insights (less than 48 hours old for winners)
                    if (time() - $insight['timestamp'] > 172800) {
                        continue;
                    }
                    
                    // Add specific modifications based on insight type
                    switch ($type) {
                        case 'collector_preferences':
                            // Adjust scoring based on collector preferences
                            if (isset($insight['data']['artwork_preferences'])) {
                                foreach ($insight['data']['artwork_preferences'] as $artwork_id => $score) {
                                    if (!isset($winners_data['artwork_scores'])) {
                                        $winners_data['artwork_scores'] = array();
                                    }
                                    
                                    // Add or adjust artwork score
                                    if (isset($winners_data['artwork_scores'][$artwork_id])) {
                                        $winners_data['artwork_scores'][$artwork_id] += $score * 0.3; // 30% influence
                                    } else {
                                        $winners_data['artwork_scores'][$artwork_id] = $score * 0.3;
                                    }
                                    
                                    // Record the influence
                                    $winners_data['external_influences'][] = array(
                                        'agent' => $agent,
                                        'type' => 'artwork_score',
                                        'subject' => $artwork_id,
                                        'score' => $score * 0.3
                                    );
                                }
                            }
                            break;
                            
                        case 'market_trends':
                            // Adjust category weights based on market insights
                            if (isset($insight['data']['trending_styles'])) {
                                foreach ($insight['data']['trending_styles'] as $style => $score) {
                                    if (!isset($winners_data['style_weights'])) {
                                        $winners_data['style_weights'] = array();
                                    }
                                    
                                    // Add or adjust style weight
                                    if (isset($winners_data['style_weights'][$style])) {
                                        $winners_data['style_weights'][$style] += $score * 0.25; // 25% influence
                                    } else {
                                        $winners_data['style_weights'][$style] = $score * 0.25;
                                    }
                                    
                                    // Record the influence
                                    $winners_data['external_influences'][] = array(
                                        'agent' => $agent,
                                        'type' => 'style_weight',
                                        'subject' => $style,
                                        'score' => $score * 0.25
                                    );
                                }
                            }
                            break;
                    }
                }
            }
        }
        
        // Trigger an action to indicate this data has been enhanced with external insights
        do_action('vortex_cloe_enhanced_daily_winners', $winners_data, $this->external_context);
        
        return $winners_data;
    }

    /**
     * Share gallery insight with other agents via the orchestrator
     *
     * @param string $insight_type Type of insight
     * @param array $data Insight data
     * @param array $context Context information
     */
    public function share_gallery_insight($insight_type, $data, $context) {
        // Trigger the insight sharing action for the orchestrator
        do_action('vortex_agent_insight_generated', 'cloe', 'gallery_' . $insight_type, $data);
        
        // Log the insight sharing
        $this->log_cross_agent_activity('shared_insight', 'orchestrator', 'gallery_' . $insight_type, $data);
    }

    /**
     * Share winners insight with other agents via the orchestrator
     *
     * @param string $insight_type Type of insight
     * @param array $data Insight data
     * @param array $context Context information
     */
    public function share_winners_insight($insight_type, $data, $context) {
        // Trigger the insight sharing action for the orchestrator
        do_action('vortex_agent_insight_generated', 'cloe', 'winners_' . $insight_type, $data);
        
        // Log the insight sharing
        $this->log_cross_agent_activity('shared_insight', 'orchestrator', 'winners_' . $insight_type, $data);
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
        // If CLOE has a logging method, use that
        if ($this->cloe && method_exists($this->cloe, 'log_activity')) {
            $this->cloe->log_activity('cross_agent_' . $activity, array(
                'agent' => $agent_name,
                'data_type' => $data_type,
                'timestamp' => current_time('mysql')
            ));
        }
        
        // Also log to WordPress debug log if enabled
        if (defined('WP_DEBUG') && WP_DEBUG === true && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG === true) {
            error_log(sprintf(
                'CLOE RT Extension: %s from %s - Type: %s',
                $activity,
                $agent_name,
                $data_type
            ));
        }
    }
}

// Initialize CLOE RT Extension
function vortex_initialize_cloe_rt_extension() {
    return VORTEX_CLOE_RT_Extension::get_instance();
}
add_action('plugins_loaded', 'vortex_initialize_cloe_rt_extension', 20); // After CLOE is loaded 