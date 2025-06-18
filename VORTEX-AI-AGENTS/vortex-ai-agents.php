<?php
/*
Plugin Name: VORTEX AI AGENTS
Plugin URI: https://vortex.com
Description: AI-powered art market analysis and creative guidance system
Version: 1.0.0
Author: Marianne Nems (aka Mariana Villard)
Author URI: https://marianne-nems.com
License: GPLv2 or later
Text Domain: vortex-ai
*/

if (!defined('ABSPATH')) exit;

// Plugin Constants
define('VORTEX_VERSION', '1.0.0');
define('VORTEX_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VORTEX_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VORTEX_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-autoloader.php';

// Initialize Plugin
function vortex_init() {
    \ = new Vortex\AI\VortexPlugin();
    \->initialize();
}
add_action('plugins_loaded', 'vortex_init');

// Activation Hook
register_activation_hook(__FILE__, function() {
    require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-activator.php';
    Vortex\AI\VortexActivator::activate();
});

// Deactivation Hook
register_deactivation_hook(__FILE__, function() {
    require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-deactivator.php';
    Vortex\AI\VortexDeactivator::deactivate();
});
