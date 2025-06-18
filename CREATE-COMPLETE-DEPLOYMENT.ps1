# 🌟 VORTEX AI Marketplace - Complete Deployment Package Creator
# This script creates a comprehensive deployment package for vortexartec.com

Write-Host "🌟 Creating COMPLETE VORTEX AI Marketplace Deployment Package..." -ForegroundColor Cyan
Write-Host "📦 This will include ALL features and components" -ForegroundColor Yellow
Write-Host ""

# Set paths
$marketplacePath = Get-Location
$deploymentName = "vortex-complete-deployment"
$zipFileName = "$deploymentName.zip"
$zipPath = Join-Path $marketplacePath $zipFileName
$tempDir = Join-Path $marketplacePath $deploymentName

# Remove existing files if they exist
if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
    Write-Host "✅ Removed existing ZIP file" -ForegroundColor Green
}

if (Test-Path $tempDir) {
    Remove-Item $tempDir -Recurse -Force
    Write-Host "✅ Removed existing temp directory" -ForegroundColor Green
}

# Create temporary directory
New-Item -ItemType Directory -Path $tempDir -Force | Out-Null
Write-Host "📁 Created temporary directory: $tempDir" -ForegroundColor Gray

# Define core files and directories to include
$coreFiles = @(
    "vortex-ai-marketplace.php",
    "LICENSE",
    "README.md"
)

$coreDirectories = @(
    "includes",
    "admin", 
    "public",
    "assets",
    "blockchain",
    "api",
    "database",
    "templates",
    "languages",
    "css",
    "js",
    "blocks",
    "contracts",
    "widgets"
)

Write-Host "📋 Copying core files..." -ForegroundColor Cyan

# Copy core files
foreach ($file in $coreFiles) {
    if (Test-Path $file) {
        Copy-Item $file -Destination $tempDir -Force
        Write-Host "  ✅ $file" -ForegroundColor Green
    } else {
        Write-Host "  ⚠️  $file (not found)" -ForegroundColor Yellow
    }
}

# Copy directories
foreach ($dir in $coreDirectories) {
    if (Test-Path $dir) {
        Copy-Item $dir -Destination $tempDir -Recurse -Force
        Write-Host "  ✅ $dir/" -ForegroundColor Green
    } else {
        Write-Host "  ⚠️  $dir/ (not found)" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "📝 Creating deployment documentation..." -ForegroundColor Cyan

# Create deployment instructions
$deploymentInstructions = @"
# 🚀 VORTEX AI Marketplace - Complete Deployment

## 📦 Package Contents
This package contains the complete VORTEX AI Marketplace system with:
- ✅ All 4 AI Agents (THORIUS, HURAII, CLOE, Business Strategist)
- ✅ Complete blockchain integration (TOLA token, Solana)
- ✅ Advanced marketplace features
- ✅ Sacred geometry system
- ✅ Career projects and collaboration tools
- ✅ DAO and gamification features
- ✅ Analytics and metrics system

## 🚀 Quick Deployment Steps

### 1. Backup Your Website
- Export WordPress database
- Download all website files
- **This is CRITICAL - always backup first!**

### 2. Upload Plugin
**Method A: WordPress Admin**
1. Go to `https://vortexartec.com/wp-admin`
2. Plugins → Add New → Upload Plugin
3. Upload this ZIP file
4. Install and Activate

**Method B: FTP/cPanel**
1. Extract this ZIP file
2. Upload folder to `/wp-content/plugins/`
3. Activate in WordPress Admin

### 3. Configure API Keys
Add to your `wp-config.php`:
```php
// AI Agent API Keys
define('VORTEX_OPENAI_API_KEY', 'your-openai-key');
define('VORTEX_STABILITY_API_KEY', 'your-stability-ai-key');
define('VORTEX_ANTHROPIC_API_KEY', 'your-anthropic-key');

// Blockchain Configuration  
define('VORTEX_SOLANA_NETWORK', 'mainnet-beta');
define('VORTEX_TOLA_TOKEN_ADDRESS', 'your-token-address');
```

### 4. Verify Deployment
Check these features work:
- ✅ New VORTEX menus appear
- ✅ AI agents respond in dashboard
- ✅ Wallet connection works
- ✅ Sacred geometry applied
- ✅ All existing content preserved

## 📞 Support
- Check error logs: `/wp-content/debug.log`
- Database repair: VORTEX AI → Tools → Database Repair
- Rollback: Deactivate plugin and restore backup

## 🎉 Success!
Your vortexartec.com is now the complete VORTEX ecosystem!

Generated: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
"@

$deploymentInstructions | Out-File -FilePath (Join-Path $tempDir "DEPLOYMENT-INSTRUCTIONS.md") -Encoding UTF8

# Create a simple plugin info file
$pluginInfo = @"
<?php
/**
 * Plugin Name: VORTEX AI Marketplace - Complete System
 * Plugin URI: https://vortexartec.com/marketplace
 * Description: Complete VORTEX AI Marketplace with 4 AI agents, blockchain integration, sacred geometry, and advanced marketplace features.
 * Version: 1.0.0
 * Author: Marianne Nems
 * Author URI: https://vortexartec.com
 * License: GPL-2.0+
 * Text Domain: vortex-ai-marketplace
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include the main plugin file
require_once plugin_dir_path(__FILE__) . 'vortex-ai-marketplace.php';
"@

$pluginInfo | Out-File -FilePath (Join-Path $tempDir "vortex-complete-marketplace.php") -Encoding UTF8

Write-Host "✅ Created deployment documentation" -ForegroundColor Green
Write-Host ""

# Create the ZIP file
Write-Host "🗜️  Creating ZIP file..." -ForegroundColor Cyan
try {
    Compress-Archive -Path "$tempDir\*" -DestinationPath $zipPath -Force
    Write-Host "✅ ZIP file created successfully!" -ForegroundColor Green
    Write-Host "📁 File location: $zipPath" -ForegroundColor Yellow
    
    # Get file size
    $fileSize = (Get-Item $zipPath).Length
    $fileSizeMB = [math]::Round($fileSize / 1MB, 2)
    Write-Host "📏 File size: $fileSizeMB MB" -ForegroundColor Gray
    
} catch {
    Write-Host "❌ Error creating ZIP file: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Clean up temporary directory
Remove-Item $tempDir -Recurse -Force
Write-Host "🧹 Cleaned up temporary files" -ForegroundColor Gray

Write-Host ""
Write-Host "🎉 COMPLETE DEPLOYMENT PACKAGE READY!" -ForegroundColor Green
Write-Host ""
Write-Host "📦 Package: $zipFileName" -ForegroundColor White
Write-Host "📏 Size: $fileSizeMB MB" -ForegroundColor White
Write-Host "🌟 Features: Complete VORTEX AI Marketplace System" -ForegroundColor White
Write-Host ""
Write-Host "🚀 READY TO DEPLOY TO VORTEXARTEC.COM!" -ForegroundColor Cyan
Write-Host ""
Write-Host "NEXT STEPS:" -ForegroundColor White
Write-Host "1. 💾 Backup your website (CRITICAL!)" -ForegroundColor Red
Write-Host "2. 📤 Upload $zipFileName to WordPress" -ForegroundColor Yellow
Write-Host "3. ⚡ Activate the plugin" -ForegroundColor Yellow
Write-Host "4. 🔑 Configure API keys in wp-config.php" -ForegroundColor Yellow
Write-Host "5. 🎯 Test all features" -ForegroundColor Yellow
Write-Host "6. 🌟 Launch your enhanced website!" -ForegroundColor Green
Write-Host ""
Write-Host "🎭 Your VORTEX AI ecosystem awaits!" -ForegroundColor Magenta

# Keep window open
Read-Host "Press Enter to continue..." 