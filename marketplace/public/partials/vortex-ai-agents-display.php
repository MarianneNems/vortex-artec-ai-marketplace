<?php
/**
 * Template for displaying AI agents
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get available agents from the shortcode attributes or use defaults
$agent_ids = !empty($atts['agents']) ? explode(',', $atts['agents']) : array();
$columns = isset($atts['columns']) ? intval($atts['columns']) : 3;
$title = isset($atts['title']) ? sanitize_text_field($atts['title']) : __('AI Assistants', 'vortex-ai-marketplace');
$description = isset($atts['description']) ? sanitize_text_field($atts['description']) : __('Get help from our AI assistants. Click on an assistant to start a conversation.', 'vortex-ai-marketplace');

// Check if any agents are configured
$agents = array();

// Default agents if none configured
if (empty($agents)) {
    $agents = array(
        'artwork_advisor' => array(
            'id' => 'artwork_advisor',
            'name' => __('Artwork Advisor', 'vortex-ai-marketplace'),
            'icon' => 'palette',
            'description' => __('Get personalized advice on your portfolio and selling strategies.', 'vortex-ai-marketplace'),
            'greeting' => __('Hi there! I\'m your Artwork Advisor. I can help you optimize your portfolio and give advice on selling your artwork. What would you like to know?', 'vortex-ai-marketplace')
        ),
        'marketplace_guide' => array(
            'id' => 'marketplace_guide',
            'name' => __('Marketplace Guide', 'vortex-ai-marketplace'),
            'icon' => 'shopping-cart',
            'description' => __('Learn how to navigate the marketplace and find the right artwork.', 'vortex-ai-marketplace'),
            'greeting' => __('Welcome! I\'m the Marketplace Guide. I can help you navigate the marketplace, understand prompts, and find the right artwork. How can I assist you today?', 'vortex-ai-marketplace')
        ),
        'prompt_engineer' => array(
            'id' => 'prompt_engineer',
            'name' => __('Prompt Engineer', 'vortex-ai-marketplace'),
            'icon' => 'wand-magic-sparkles',
            'description' => __('Get help crafting effective prompts for AI art generation.', 'vortex-ai-marketplace'),
            'greeting' => __('Hello! I\'m your Prompt Engineer assistant. I can help you craft effective prompts for AI art generation. What kind of artwork are you looking to create?', 'vortex-ai-marketplace')
        ),
        'community_assistant' => array(
            'id' => 'community_assistant',
            'name' => __('Community Assistant', 'vortex-ai-marketplace'),
            'icon' => 'users',
            'description' => __('Discover events, challenges, and ways to connect with other artists.', 'vortex-ai-marketplace'),
            'greeting' => __('Hi! I\'m the Community Assistant. I can tell you about events, challenges, and ways to connect with other artists in our community. What are you interested in?', 'vortex-ai-marketplace')
        ),
        'technical_support' => array(
            'id' => 'technical_support',
            'name' => __('Technical Support', 'vortex-ai-marketplace'),
            'icon' => 'wrench',
            'description' => __('Get help with technical issues related to the marketplace.', 'vortex-ai-marketplace'),
            'greeting' => __('Welcome to technical support. I can help with issues related to uploads, marketplace features, or general technical questions. What can I help you with today?', 'vortex-ai-marketplace')
        )
    );
}

// Filter agents based on shortcode attributes if specified
if (!empty($agent_ids)) {
    $filtered_agents = array();
    foreach ($agent_ids as $agent_id) {
        if (isset($agents[$agent_id])) {
            $filtered_agents[$agent_id] = $agents[$agent_id];
        }
    }
    $agents = $filtered_agents;
}
?>

<div class="vortex-ai-agents-container">
    <?php if (!empty($title) || !empty($description)): ?>
    <div class="vortex-ai-agents-header">
        <?php if (!empty($title)): ?>
            <h2 class="vortex-ai-agents-title"><?php echo esc_html($title); ?></h2>
        <?php endif; ?>
        
        <?php if (!empty($description)): ?>
            <p class="vortex-ai-agents-description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="vortex-agent-cards" style="<?php echo esc_attr("grid-template-columns: repeat({$columns}, 1fr);"); ?>">
        <?php foreach ($agents as $agent): ?>
            <div class="vortex-agent-card" data-agent-id="<?php echo esc_attr($agent['id']); ?>" data-agent-name="<?php echo esc_attr($agent['name']); ?>">
                <div class="vortex-agent-icon">
                    <i class="fa-solid fa-<?php echo esc_attr($agent['icon']); ?>"></i>
                </div>
                <h3 class="vortex-agent-name"><?php echo esc_html($agent['name']); ?></h3>
                <p class="vortex-agent-description"><?php echo esc_html($agent['description']); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Chat Modal -->
<div class="vortex-chat-modal-overlay">
    <div class="vortex-chat-modal">
        <div class="vortex-chat-modal-header">
            <div class="vortex-chat-modal-title"></div>
            <button class="vortex-chat-modal-close">&times;</button>
        </div>
        <div class="vortex-chat-messages"></div>
        <div class="vortex-chat-input-container">
            <textarea class="vortex-chat-input" placeholder="<?php esc_attr_e('Type your message here...', 'vortex-ai-marketplace'); ?>"></textarea>
            <button class="vortex-chat-send-btn">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
    // Pass agent data to JavaScript
    var vortexAgentData = <?php echo json_encode($agents); ?>;
</script> 