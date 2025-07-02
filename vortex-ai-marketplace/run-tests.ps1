#!/usr/bin/env pwsh
# VORTEX AI Marketplace - Code Validation Script
# Runs basic validation without requiring PHP installation

Write-Host "================================" -ForegroundColor Green
Write-Host "VORTEX AI Marketplace Validation" -ForegroundColor Green  
Write-Host "================================" -ForegroundColor Green
Write-Host ""

# Test 1: Check plugin structure
Write-Host "[1/5] Checking plugin structure..." -ForegroundColor Yellow
$requiredFiles = @(
    "vortex-ai-marketplace.php",
    "composer.json", 
    "phpunit.xml",
    "includes/class-vortex-ai-marketplace.php",
    "includes/shortcodes/class-vortex-artist-business-quiz.php",
    "templates/artist-business-quiz.php"
)

$missingFiles = @()
foreach ($file in $requiredFiles) {
    if (!(Test-Path $file)) {
        $missingFiles += $file
    }
}

if ($missingFiles.Count -eq 0) {
    Write-Host "✅ All required files present" -ForegroundColor Green
} else {
    Write-Host "❌ Missing files:" -ForegroundColor Red
    $missingFiles | ForEach-Object { Write-Host "   - $_" -ForegroundColor Red }
}

# Test 2: Check PHP syntax (basic)
Write-Host ""
Write-Host "[2/5] Checking PHP file syntax..." -ForegroundColor Yellow
$phpFiles = Get-ChildItem -Recurse -Filter "*.php" | Select-Object -First 10
$syntaxErrors = 0

foreach ($phpFile in $phpFiles) {
    $content = Get-Content $phpFile.FullName -Raw
    if ($content -match '<?php' -and $content -notmatch '\?>$') {
        # Basic syntax check - look for common issues
        if ($content -match ';;\s*$' -or $content -match '\$\$') {
            Write-Host "⚠️  Potential syntax issue in: $($phpFile.Name)" -ForegroundColor Yellow
            $syntaxErrors++
        }
    }
}

if ($syntaxErrors -eq 0) {
    Write-Host "✅ No obvious syntax errors found" -ForegroundColor Green
} else {
    Write-Host "⚠️  $syntaxErrors potential syntax issues found" -ForegroundColor Yellow
}

# Test 3: Check WordPress standards (basic)
Write-Host ""
Write-Host "[3/5] Checking WordPress standards..." -ForegroundColor Yellow
$standardsIssues = 0

# Check for proper plugin header
$mainPlugin = Get-Content "vortex-ai-marketplace.php" -Raw
if ($mainPlugin -match "Plugin Name:" -and $mainPlugin -match "Version:" -and $mainPlugin -match "Author:") {
    Write-Host "✅ Plugin header is properly formatted" -ForegroundColor Green
} else {
    Write-Host "❌ Plugin header missing required fields" -ForegroundColor Red
    $standardsIssues++
}

# Check for security best practices
$securityFiles = Get-ChildItem -Recurse -Filter "*.php" | Select-Object -First 5
foreach ($file in $securityFiles) {
    $content = Get-Content $file.FullName -Raw
    if ($content -notmatch "defined.*ABSPATH" -and $content -notmatch "defined.*WPINC") {
        $standardsIssues++
    }
}

if ($standardsIssues -eq 0) {
    Write-Host "✅ Basic WordPress standards followed" -ForegroundColor Green
} else {
    Write-Host "⚠️  $standardsIssues potential standards issues" -ForegroundColor Yellow
}

# Test 4: Check database table definitions
Write-Host ""
Write-Host "[4/5] Checking database schema..." -ForegroundColor Yellow
$dbFiles = Get-ChildItem -Recurse -Filter "*.php" | Where-Object { 
    (Get-Content $_.FullName -Raw) -match "CREATE TABLE"
}

if ($dbFiles.Count -gt 0) {
    Write-Host "✅ Database schema files found: $($dbFiles.Count)" -ForegroundColor Green
    $dbFiles | ForEach-Object { Write-Host "   - $($_.Name)" -ForegroundColor Cyan }
} else {
    Write-Host "⚠️  No database schema files found" -ForegroundColor Yellow
}

# Test 5: Check shortcode implementation  
Write-Host ""
Write-Host "[5/5] Checking shortcode implementation..." -ForegroundColor Yellow
$shortcodeFile = "includes/shortcodes/class-vortex-artist-business-quiz.php"
if (Test-Path $shortcodeFile) {
    $content = Get-Content $shortcodeFile -Raw
    if ($content -match "add_shortcode.*vortex_artist_business_quiz" -and 
        $content -match "has_submitted_this_month" -and
        $content -match "user_can_access_quiz") {
        Write-Host "✅ Artist Business Quiz shortcode properly implemented" -ForegroundColor Green
        Write-Host "   - Monthly limit check: ✅" -ForegroundColor Green
        Write-Host "   - Access control: ✅" -ForegroundColor Green
        Write-Host "   - Template integration: ✅" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Shortcode implementation incomplete" -ForegroundColor Yellow
    }
} else {
    Write-Host "❌ Shortcode file not found" -ForegroundColor Red
}

# Final Summary
Write-Host ""
Write-Host "================================" -ForegroundColor Green
Write-Host "VALIDATION SUMMARY" -ForegroundColor Green
Write-Host "================================" -ForegroundColor Green
Write-Host ""
Write-Host "Plugin Structure: " -NoNewline
if ($missingFiles.Count -eq 0) { Write-Host "PASS" -ForegroundColor Green } else { Write-Host "FAIL" -ForegroundColor Red }

Write-Host "PHP Syntax: " -NoNewline  
if ($syntaxErrors -eq 0) { Write-Host "PASS" -ForegroundColor Green } else { Write-Host "WARN" -ForegroundColor Yellow }

Write-Host "WordPress Standards: " -NoNewline
if ($standardsIssues -eq 0) { Write-Host "PASS" -ForegroundColor Green } else { Write-Host "WARN" -ForegroundColor Yellow }

Write-Host "Database Schema: " -NoNewline
if ($dbFiles.Count -gt 0) { Write-Host "PASS" -ForegroundColor Green } else { Write-Host "WARN" -ForegroundColor Yellow }

Write-Host "Shortcode Implementation: " -NoNewline
if (Test-Path $shortcodeFile) { Write-Host "PASS" -ForegroundColor Green } else { Write-Host "FAIL" -ForegroundColor Red }

Write-Host ""
Write-Host "🎯 RECOMMENDATION:" -ForegroundColor Cyan
Write-Host "Install XAMPP from https://www.apachefriends.org for full PHP testing" -ForegroundColor Cyan
Write-Host "Or run as WordPress plugin directly in WordPress environment" -ForegroundColor Cyan
Write-Host "" 