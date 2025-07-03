<?php
/**
 * AWS S3 Configuration for VORTEX AI Marketplace
 * 
 * @package VortexAIMarketplace
 * @version 1.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

return [
    'key'    => getenv('AWS_ACCESS_KEY_ID'),
    'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
    'region' => 'us-east-1',
    'bucket' => 'vortexartec.com-client-art',
    'endpoint' => null,
    'use_path_style_endpoint' => false,
    'signature_version' => 'v4',
    'cors_enabled' => true,
    'max_file_size' => 52428800, // 50MB
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf']
]; 