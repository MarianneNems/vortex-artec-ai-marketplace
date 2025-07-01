# AWS CLI Installation Script for Windows
Write-Host "🚀 Installing AWS CLI for VortexArtec Setup" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Green

# Check if AWS CLI is already installed
if (Get-Command aws -ErrorAction SilentlyContinue) {
    Write-Host "✅ AWS CLI is already installed!" -ForegroundColor Green
    aws --version
    exit 0
}

Write-Host "📥 Downloading AWS CLI v2 installer..." -ForegroundColor Blue

# Download AWS CLI v2 MSI installer
$downloadUrl = "https://awscli.amazonaws.com/AWSCLIV2.msi"
$installerPath = "$env:TEMP\AWSCLIV2.msi"

try {
    Invoke-WebRequest -Uri $downloadUrl -OutFile $installerPath -UseBasicParsing
    Write-Host "✅ Download completed: $installerPath" -ForegroundColor Green
    
    Write-Host ""
    Write-Host "🔧 Installing AWS CLI..." -ForegroundColor Yellow
    Write-Host "   This will open the installer window." -ForegroundColor Gray
    Write-Host "   Please follow the installation wizard." -ForegroundColor Gray
    
    # Run the installer
    Start-Process -FilePath "msiexec.exe" -ArgumentList "/i", $installerPath, "/qb" -Wait
    
    Write-Host ""
    Write-Host "🔄 Refreshing environment variables..." -ForegroundColor Blue
    
    # Refresh environment variables
    $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
    
    # Test installation
    Start-Sleep -Seconds 3
    
    if (Get-Command aws -ErrorAction SilentlyContinue) {
        Write-Host "✅ AWS CLI installed successfully!" -ForegroundColor Green
        aws --version
        
        Write-Host ""
        Write-Host "🔄 Next Steps:" -ForegroundColor Magenta
        Write-Host "   1. Close and reopen PowerShell (important!)" -ForegroundColor White
        Write-Host "   2. Run: aws configure --profile vortexartec" -ForegroundColor Cyan
        Write-Host "   3. Enter your AWS credentials" -ForegroundColor White
        Write-Host "   4. Run: .\Setup-VortexAWS.ps1" -ForegroundColor Cyan
        
    } else {
        Write-Host "⚠️ AWS CLI installation may need a PowerShell restart" -ForegroundColor Yellow
        Write-Host "   Please close and reopen PowerShell, then check: aws --version" -ForegroundColor Gray
    }
    
    # Clean up installer
    Remove-Item $installerPath -ErrorAction SilentlyContinue
    
} catch {
    Write-Host "❌ Error downloading AWS CLI: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
    Write-Host "📋 Manual Installation Steps:" -ForegroundColor Yellow
    Write-Host "   1. Go to: https://aws.amazon.com/cli/" -ForegroundColor White
    Write-Host "   2. Download AWS CLI v2 for Windows" -ForegroundColor White
    Write-Host "   3. Run the installer" -ForegroundColor White
    Write-Host "   4. Restart PowerShell" -ForegroundColor White
    Write-Host "   5. Return here and run: .\Setup-VortexAWS.ps1" -ForegroundColor Cyan
}

Write-Host ""
Write-Host "📞 Need Help?" -ForegroundColor White
Write-Host "   If installation fails, we can proceed with manual download." -ForegroundColor Gray 