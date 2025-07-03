#
# VORTEX AI Marketplace - Windows PowerShell Deployment Script
# 
# This script automates the deployment process on Windows
# Usage: .\deploy\deploy-windows.ps1
#

param(
    [string]$WordPressPath = "C:\xampp\htdocs",
    [string]$PluginZip = "release\vortex-ai-marketplace-v1.0.0.zip"
)

Write-Host "🚀 Starting VORTEX AI Marketplace deployment..." -ForegroundColor Green

# 1. Check prerequisites
Write-Host "1. Checking prerequisites..." -ForegroundColor Cyan

if (-not (Test-Path $PluginZip)) {
    Write-Host "❌ Plugin ZIP not found: $PluginZip" -ForegroundColor Red
    exit 1
}

if (-not (Test-Path $WordPressPath)) {
    Write-Host "❌ WordPress path not found: $WordPressPath" -ForegroundColor Red
    exit 1
}

Write-Host "✅ Prerequisites checked" -ForegroundColor Green

# 2. Set environment variables
Write-Host "2. Setting up environment..." -ForegroundColor Cyan
& ".\deploy\setup-env-windows.ps1"

# 3. Extract and copy plugin files
Write-Host "3. Installing plugin..." -ForegroundColor Cyan

$PluginDir = "$WordPressPath\wp-content\plugins\vortex-ai-marketplace"

# Remove existing plugin directory if it exists
if (Test-Path $PluginDir) {
    Write-Host "   Removing existing plugin directory..." -ForegroundColor Yellow
    Remove-Item -Recurse -Force $PluginDir
}

# Create plugin directory
New-Item -ItemType Directory -Path $PluginDir -Force | Out-Null

# Extract ZIP to plugin directory
Write-Host "   Extracting plugin files..." -ForegroundColor Yellow
Expand-Archive -Path $PluginZip -DestinationPath $PluginDir -Force

Write-Host "✅ Plugin installed" -ForegroundColor Green

# 4. Create upload directories
Write-Host "4. Creating upload directories..." -ForegroundColor Cyan

$UploadDirs = @(
    "$WordPressPath\wp-content\uploads\vortex-ai",
    "$WordPressPath\wp-content\uploads\vortex-ai\seed-art",
    "$WordPressPath\wp-content\uploads\vortex-ai\generated",
    "$WordPressPath\wp-content\uploads\vortex-ai\temp"
)

foreach ($dir in $UploadDirs) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Host "   Created: $dir" -ForegroundColor Yellow
    }
}

Write-Host "✅ Upload directories created" -ForegroundColor Green

# 5. Verify configuration files
Write-Host "5. Verifying configuration files..." -ForegroundColor Cyan

$ConfigFiles = @(
    "$PluginDir\aws-config.php",
    "$PluginDir\runpod-config.php",
    "$PluginDir\vortex-ai-marketplace.php"
)

foreach ($file in $ConfigFiles) {
    if (Test-Path $file) {
        Write-Host "✅ Found: $(Split-Path $file -Leaf)" -ForegroundColor Green
    } else {
        Write-Host "❌ Missing: $(Split-Path $file -Leaf)" -ForegroundColor Red
    }
}

# 6. Set file permissions (Windows equivalent)
Write-Host "6. Setting file permissions..." -ForegroundColor Cyan

# Give full control to current user and IIS_IUSRS if available
try {
    $acl = Get-Acl $PluginDir
    $currentUser = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
    $accessRule = New-Object System.Security.AccessControl.FileSystemAccessRule($currentUser, "FullControl", "ContainerInherit,ObjectInherit", "None", "Allow")
    $acl.SetAccessRule($accessRule)
    Set-Acl -Path $PluginDir -AclObject $acl
    Write-Host "✅ Permissions set for current user" -ForegroundColor Green
} catch {
    Write-Host "⚠️  Could not set permissions: $($_.Exception.Message)" -ForegroundColor Yellow
}

# 7. WordPress CLI commands (if wp-cli is available)
Write-Host "7. WordPress configuration..." -ForegroundColor Cyan

if (Get-Command wp -ErrorAction SilentlyContinue) {
    Write-Host "   WP-CLI found, configuring WordPress..." -ForegroundColor Yellow
    
    # Navigate to WordPress directory
    Push-Location $WordPressPath
    
    try {
        # Activate plugin
        & wp plugin activate vortex-ai-marketplace
        Write-Host "✅ Plugin activated" -ForegroundColor Green
        
        # Set options
        & wp option update vortex_ai_aws_config_path 'wp-content/plugins/vortex-ai-marketplace/aws-config.php'
        & wp option update vortex_ai_runpod_config_path 'wp-content/plugins/vortex-ai-marketplace/runpod-config.php'
        & wp option update vortex_plugin_version '1.0.1'
        & wp option update vortex_installation_date (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
        & wp option update vortex_environment 'production'
        
        Write-Host "✅ WordPress options configured" -ForegroundColor Green
        
        # Schedule cron events
        & wp cron event schedule vortex_daily_masterwork now daily
        & wp cron event schedule vortex_daily_milestone_reminder now daily
        & wp cron event schedule vortex_daily_admin_analysis now daily
        
        Write-Host "✅ Cron events scheduled" -ForegroundColor Green
        
    } catch {
        Write-Host "⚠️  WP-CLI error: $($_.Exception.Message)" -ForegroundColor Yellow
    } finally {
        Pop-Location
    }
} else {
    Write-Host "⚠️  WP-CLI not found. Manual WordPress configuration required:" -ForegroundColor Yellow
    Write-Host "   1. Log into WordPress admin"
    Write-Host "   2. Navigate to Plugins"
    Write-Host "   3. Activate 'VORTEX AI Marketplace'"
    Write-Host "   4. Configure plugin settings"
}

# 8. Run smoke tests
Write-Host "8. Running smoke tests..." -ForegroundColor Cyan

$SmokeTestScript = "$PluginDir\tests\staging-smoke.sh"
if (Test-Path $SmokeTestScript) {
    if (Get-Command bash -ErrorAction SilentlyContinue) {
        try {
            & bash $SmokeTestScript
            Write-Host "✅ Smoke tests completed" -ForegroundColor Green
        } catch {
            Write-Host "⚠️  Smoke test error: $($_.Exception.Message)" -ForegroundColor Yellow
        }
    } else {
        Write-Host "⚠️  Bash not available. Skipping smoke tests." -ForegroundColor Yellow
        Write-Host "   Run manually: bash $SmokeTestScript"
    }
} else {
    Write-Host "⚠️  Smoke test script not found" -ForegroundColor Yellow
}

# 9. Final verification
Write-Host "9. Final verification..." -ForegroundColor Cyan

$VerifyChecks = @{
    "Plugin directory exists" = (Test-Path $PluginDir)
    "Main plugin file exists" = (Test-Path "$PluginDir\vortex-ai-marketplace.php")
    "AWS config exists" = (Test-Path "$PluginDir\aws-config.php")
    "RunPod config exists" = (Test-Path "$PluginDir\runpod-config.php")
    "Upload directory exists" = (Test-Path "$WordPressPath\wp-content\uploads\vortex-ai")
}

foreach ($check in $VerifyChecks.GetEnumerator()) {
    if ($check.Value) {
        Write-Host "✅ $($check.Key)" -ForegroundColor Green
    } else {
        Write-Host "❌ $($check.Key)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "🎉 VORTEX AI Marketplace deployment complete!" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Update configuration files with real API keys"
Write-Host "2. Test WordPress admin panel access"
Write-Host "3. Verify plugin functionality"
Write-Host "4. Configure SSL certificates for production"
Write-Host "" 