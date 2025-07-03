<?php
/**
 * RunPod Vault Configuration for VORTEX AI Marketplace
 * 
 * @package VortexAIMarketplace
 * @version 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

return [
    'api_key'           => getenv('RUNPOD_API_KEY'),
    'vault_id'          => getenv('RUNPOD_VAULT_ID') ?: 'your-vault-id-here',
    'base_url'          => 'https://api.runpod.ai/v2',
    'timeout'           => 30,
    'retry_attempts'    => 3,
    'storage_endpoint'  => '/vault/files',
    'inference_endpoint'=> '/inference',
    'max_file_size'     => 104857600, // 100MB
    'allowed_types'     => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
    'encryption'        => true,
    'metadata_tracking' => true
]; 