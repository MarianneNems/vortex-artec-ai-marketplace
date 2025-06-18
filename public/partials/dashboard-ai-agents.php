<?php
/**
 * AI Agents Dashboard Panel
 *
 * Displays AI agents available in the VORTEX AI Marketplace.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get user ID and role
$user_id = get_current_user_id();
$user = get_userdata($user_id);
$user_roles = $user->roles;
$is_artist = in_array('vortex_artist', $user_roles);

// Get AI agents
$agents = array(
    'artwork_advisor' => array(
        'id' => 'artwork_advisor',
        'name' => 'Artwork Advisor',
        'icon' => 'palette',
        'description' => 'Get personalized advice on your portfolio and selling strategies.'
    ),
    'marketplace_guide' => array(
        'id' => 'marketplace_guide',
        'name' => 'Marketplace Guide',
        'icon' => 'shopping-cart',
        'description' => 'Learn how to navigate the marketplace and find the right artwork.'
    ),
    'prompt_engineer' => array(
        'id' => 'prompt_engineer',
        'name' => 'Prompt Engineer',
        'icon' => 'wand-magic-sparkles',
        'description' => 'Get help crafting effective prompts for AI art generation.'
    ),
    'community_assistant' => array(
        'id' => 'community_assistant',
        'name' => 'Community Assistant',
        'icon' => 'users',
        'description' => 'Discover events, challenges, and ways to connect with other artists.'
    ),
    'technical_support' => array(
        'id' => 'technical_support',
        'name' => 'Technical Support',
        'icon' => 'wrench',
        'description' => 'Get help with technical issues related to the marketplace.'
    )
);

// If AI Agents class exists, get agents from there
if (class_exists('Vortex_AI_Agents')) {
    $ai_agents_instance = new Vortex_AI_Agents();
    if (method_exists($ai_agents_instance, 'get_agents')) {
        $agents = $ai_agents_instance->get_agents();
    }
}
?>

<div class="vortex-ai-agents-panel">
    <div class="vortex-panel-header">
        <h2><?php esc_html_e('Meet Your AI Agents', 'vortex-ai-marketplace'); ?></h2>
        <p><?php esc_html_e('Your team of AI assistants, each with unique specialties to support your creative journey.', 'vortex-ai-marketplace'); ?></p>
    </div>
    
    <div class="vortex-agent-cards" style="grid-template-columns: repeat(4, 1fr);">
        <?php foreach ($agents as $agent_id => $agent) : ?>
            <div class="vortex-agent-card" 
                 data-agent-id="<?php echo esc_attr($agent_id); ?>"
                 data-agent-name="<?php echo esc_attr($agent['name']); ?>">
                <div class="vortex-agent-icon">
                    <i class="fa-solid fa-<?php echo esc_attr($agent['icon']); ?>"></i>
                </div>
                <h3 class="vortex-agent-name"><?php echo esc_html($agent['name']); ?></h3>
                <p class="vortex-agent-description"><?php echo esc_html($agent['description']); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if ($is_artist): ?>
    <!-- Artwork Swap Game (Exclusive to Artists) -->
    <div class="vortex-swap-game-promo">
        <div class="vortex-swap-game-info">
            <h3><?php esc_html_e('Artwork Swap Game', 'vortex-ai-marketplace'); ?></h3>
            <p><?php esc_html_e('Trade your art with other artists in real-time! Upload at least 2 artworks weekly to maintain swap privileges.', 'vortex-ai-marketplace'); ?></p>
        </div>
        <div class="vortex-swap-game-actions">
            <a href="<?php echo esc_url(home_url('/swap-artwork/')); ?>" class="vortex-button vortex-button-primary vortex-swap-button">
                <span class="vortex-swap-icon"></span>
                <?php esc_html_e('Swap Artwork', 'vortex-ai-marketplace'); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Chat Modal -->
<div class="vortex-agent-chat-modal">
    <div class="vortex-agent-chat-header">
        <div class="vortex-agent-chat-name"></div>
        <div class="vortex-agent-chat-close">
            <i class="fa-solid fa-times"></i>
        </div>
    </div>
    <div class="vortex-agent-chat-messages"></div>
    <div class="vortex-agent-chat-input">
        <textarea placeholder="Type your message here..."></textarea>
        <button class="vortex-agent-chat-send">
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </div>
</div>

<script>
    // Pass AJAX URL and security nonce to script
    var vortexAIAgents = {
        ajaxurl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
        security: '<?php echo wp_create_nonce('vortex_ai_agent_security'); ?>'
    };
</script> 