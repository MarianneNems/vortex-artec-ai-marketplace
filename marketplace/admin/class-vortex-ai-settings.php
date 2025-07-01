<?php
/**
 * AI Agents Settings Class
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin
 * @link       https://aimarketplace.vortex.com
 * @since      1.0.0
 */

/**
 * The AI Agent settings class.
 */
class Vortex_AI_Settings {

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add the settings page to the admin menu.
     */
    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=vortex_artwork',
            'AI Agents Settings',
            'AI Agents',
            'manage_options',
            'vortex_ai_settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Display the settings page content.
     */
    public function display_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('vortex_ai_settings');
                do_settings_sections('vortex_ai_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register settings and fields.
     */
    public function register_settings() {
        register_setting('vortex_ai_settings', 'vortex_ai_api_key');
        register_setting('vortex_ai_settings', 'vortex_ai_api_url');
        register_setting('vortex_ai_settings', 'vortex_ai_agents', array($this, 'sanitize_agents'));
        
        add_settings_section(
            'vortex_ai_api_section',
            'API Settings',
            array($this, 'api_section_callback'),
            'vortex_ai_settings'
        );
        
        add_settings_field(
            'vortex_ai_api_key',
            'API Key',
            array($this, 'api_key_callback'),
            'vortex_ai_settings',
            'vortex_ai_api_section'
        );
        
        add_settings_field(
            'vortex_ai_api_url',
            'API URL',
            array($this, 'api_url_callback'),
            'vortex_ai_settings',
            'vortex_ai_api_section'
        );
        
        add_settings_section(
            'vortex_ai_agents_section',
            'AI Agents Configuration',
            array($this, 'agents_section_callback'),
            'vortex_ai_settings'
        );
        
        add_settings_field(
            'vortex_ai_agents',
            'AI Agents',
            array($this, 'agents_callback'),
            'vortex_ai_settings',
            'vortex_ai_agents_section'
        );
    }
    
    /**
     * API section description.
     */
    public function api_section_callback() {
        echo '<p>Configure the AI API settings.</p>';
    }
    
    /**
     * API key field callback.
     */
    public function api_key_callback() {
        $api_key = get_option('vortex_ai_api_key');
        echo '<input type="password" id="vortex_ai_api_key" name="vortex_ai_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
    }
    
    /**
     * API URL field callback.
     */
    public function api_url_callback() {
        $api_url = get_option('vortex_ai_api_url', 'https://api.openai.com/v1/chat/completions');
        echo '<input type="url" id="vortex_ai_api_url" name="vortex_ai_api_url" value="' . esc_attr($api_url) . '" class="regular-text">';
    }
    
    /**
     * Agents section description.
     */
    public function agents_section_callback() {
        echo '<p>Configure the AI agents available on the marketplace.</p>';
    }
    
    /**
     * Agents field callback.
     */
    public function agents_callback() {
        $agents = get_option('vortex_ai_agents', array(
            array(
                'id' => 'artwork_advisor',
                'name' => 'Artwork Advisor',
                'icon' => 'palette',
                'description' => 'Get advice about creating and promoting your artwork',
                'enabled' => true
            ),
            array(
                'id' => 'marketplace_guide',
                'name' => 'Marketplace Guide',
                'icon' => 'store',
                'description' => 'Learn how to navigate and use the marketplace',
                'enabled' => true
            ),
            array(
                'id' => 'prompt_engineer',
                'name' => 'Prompt Engineer',
                'icon' => 'edit',
                'description' => 'Help with crafting effective AI art prompts',
                'enabled' => true
            ),
            array(
                'id' => 'community_assistant',
                'name' => 'Community Assistant',
                'icon' => 'groups',
                'description' => 'Get information about community events and forums',
                'enabled' => true
            ),
            array(
                'id' => 'technical_support',
                'name' => 'Technical Support',
                'icon' => 'support',
                'description' => 'Help with technical issues and questions',
                'enabled' => true
            )
        ));
        
        echo '<div id="vortex-ai-agents-container">';
        
        foreach ($agents as $index => $agent) {
            echo '<div class="vortex-agent-item" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; background-color: #f9f9f9;">';
            echo '<input type="hidden" name="vortex_ai_agents[' . $index . '][id]" value="' . esc_attr($agent['id']) . '">';
            
            echo '<div style="margin-bottom: 10px;">';
            echo '<label style="display: block; font-weight: bold;">Name:</label>';
            echo '<input type="text" name="vortex_ai_agents[' . $index . '][name]" value="' . esc_attr($agent['name']) . '" class="regular-text">';
            echo '</div>';
            
            echo '<div style="margin-bottom: 10px;">';
            echo '<label style="display: block; font-weight: bold;">Icon:</label>';
            echo '<input type="text" name="vortex_ai_agents[' . $index . '][icon]" value="' . esc_attr($agent['icon']) . '" class="regular-text">';
            echo '<span class="description">Enter a Material icon name</span>';
            echo '</div>';
            
            echo '<div style="margin-bottom: 10px;">';
            echo '<label style="display: block; font-weight: bold;">Description:</label>';
            echo '<textarea name="vortex_ai_agents[' . $index . '][description]" rows="2" class="large-text">' . esc_textarea($agent['description']) . '</textarea>';
            echo '</div>';
            
            echo '<div>';
            echo '<label>';
            echo '<input type="checkbox" name="vortex_ai_agents[' . $index . '][enabled]" value="1" ' . checked(isset($agent['enabled']) && $agent['enabled'], true, false) . '>';
            echo ' Enabled';
            echo '</label>';
            echo '</div>';
            
            echo '</div>';
        }
        
        echo '<button type="button" id="add-new-agent" class="button">Add New Agent</button>';
        echo '</div>';
        
        // JavaScript for adding new agents
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#add-new-agent').on('click', function() {
                var index = $('.vortex-agent-item').length;
                var newAgent = $('<div class="vortex-agent-item" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; background-color: #f9f9f9;"></div>');
                
                newAgent.append('<input type="hidden" name="vortex_ai_agents[' + index + '][id]" value="custom_agent_' + index + '">');
                
                newAgent.append('<div style="margin-bottom: 10px;"><label style="display: block; font-weight: bold;">Name:</label><input type="text" name="vortex_ai_agents[' + index + '][name]" value="" class="regular-text"></div>');
                
                newAgent.append('<div style="margin-bottom: 10px;"><label style="display: block; font-weight: bold;">Icon:</label><input type="text" name="vortex_ai_agents[' + index + '][icon]" value="assistant" class="regular-text"><span class="description">Enter a Material icon name</span></div>');
                
                newAgent.append('<div style="margin-bottom: 10px;"><label style="display: block; font-weight: bold;">Description:</label><textarea name="vortex_ai_agents[' + index + '][description]" rows="2" class="large-text"></textarea></div>');
                
                newAgent.append('<div><label><input type="checkbox" name="vortex_ai_agents[' + index + '][enabled]" value="1" checked> Enabled</label></div>');
                
                $('#vortex-ai-agents-container').find('#add-new-agent').before(newAgent);
            });
        });
        </script>
        <?php
    }
    
    /**
     * Sanitize the agents data.
     */
    public function sanitize_agents($agents) {
        if (!is_array($agents)) {
            return array();
        }
        
        $sanitized_agents = array();
        
        foreach ($agents as $agent) {
            if (empty($agent['name'])) {
                continue;
            }
            
            $sanitized_agents[] = array(
                'id' => sanitize_key(!empty($agent['id']) ? $agent['id'] : 'agent_' . wp_rand()),
                'name' => sanitize_text_field($agent['name']),
                'icon' => sanitize_text_field($agent['icon']),
                'description' => sanitize_textarea_field($agent['description']),
                'enabled' => isset($agent['enabled']) ? (bool) $agent['enabled'] : false
            );
        }
        
        return $sanitized_agents;
    }
}

// Initialize the settings
new Vortex_AI_Settings(); 