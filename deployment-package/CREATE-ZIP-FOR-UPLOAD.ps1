# 🌟 VORTEX Artec Deployment Package Creator
# This script creates a ZIP file ready for upload to your website

Write-Host "🌟 Creating VORTEX Artec Deployment Package..." -ForegroundColor Cyan

# Set paths
$deploymentPath = Get-Location
$zipFileName = "vortex-artec-integration-deployment.zip"
$zipPath = Join-Path $deploymentPath $zipFileName

# Remove existing ZIP if it exists
if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
    Write-Host "✅ Removed existing ZIP file" -ForegroundColor Green
}

# Create ZIP file
try {
    Compress-Archive -Path "vortex-artec-integration" -DestinationPath $zipPath -Force
    Write-Host "✅ ZIP file created successfully!" -ForegroundColor Green
    Write-Host "📁 File location: $zipPath" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "🚀 READY TO DEPLOY!" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "NEXT STEPS:" -ForegroundColor White
    Write-Host "1. Upload the ZIP file to your website" -ForegroundColor Gray
    Write-Host "2. Extract it in /wp-content/plugins/" -ForegroundColor Gray
    Write-Host "3. Activate the plugin in WordPress Admin" -ForegroundColor Gray
    Write-Host "4. Visit your website to see the transformation!" -ForegroundColor Gray
    Write-Host ""
    Write-Host "🌟 Your VORTEX ecosystem awaits!" -ForegroundColor Magenta
}
catch {
    Write-Host "❌ Error creating ZIP file: $($_.Exception.Message)" -ForegroundColor Red
}

# Keep window open
Read-Host "Press Enter to continue..." 