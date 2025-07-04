# VORTEX AI Marketplace - SVG to PNG Converter
# PowerShell script to convert SVG files to high-quality PNG images

Write-Host "🎨 VORTEX AI Marketplace - SVG to PNG Converter" -ForegroundColor Cyan
Write-Host "=================================================" -ForegroundColor Cyan

$currentDir = Get-Location
$svgFiles = @("architecture.svg", "vortex-mindmap.svg")
$outputDir = "png-exports"

# Create output directory
if (!(Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir
    Write-Host "✅ Created output directory: $outputDir" -ForegroundColor Green
}

Write-Host "`n🔍 Checking for available conversion tools..." -ForegroundColor Yellow

# Method 1: Try Inkscape (most professional)
$inkscapeExe = Get-Command inkscape -ErrorAction SilentlyContinue
if ($inkscapeExe) {
    Write-Host "✅ Inkscape found: $($inkscapeExe.Source)" -ForegroundColor Green
    
    foreach ($svgFile in $svgFiles) {
        if (Test-Path $svgFile) {
            $outputFile = "$outputDir\$($svgFile -replace '\.svg$', '.png')"
            Write-Host "🔄 Converting $svgFile to PNG (Inkscape)..." -ForegroundColor Yellow
            
            # High-quality conversion with Inkscape
            & inkscape --export-type=png --export-dpi=300 --export-filename="$outputFile" "$svgFile"
            
            if (Test-Path $outputFile) {
                Write-Host "✅ Successfully created: $outputFile" -ForegroundColor Green
            } else {
                Write-Host "❌ Failed to create: $outputFile" -ForegroundColor Red
            }
        } else {
            Write-Host "⚠️  SVG file not found: $svgFile" -ForegroundColor Yellow
        }
    }
} else {
    Write-Host "❌ Inkscape not found" -ForegroundColor Red
}

# Method 2: Try rsvg-convert (ImageMagick alternative)
$rsvgExe = Get-Command rsvg-convert -ErrorAction SilentlyContinue
if ($rsvgExe) {
    Write-Host "✅ rsvg-convert found: $($rsvgExe.Source)" -ForegroundColor Green
    
    foreach ($svgFile in $svgFiles) {
        if (Test-Path $svgFile) {
            $outputFile = "$outputDir\$($svgFile -replace '\.svg$', '-rsvg.png')"
            Write-Host "🔄 Converting $svgFile to PNG (rsvg-convert)..." -ForegroundColor Yellow
            
            # High-quality conversion with rsvg-convert
            & rsvg-convert --dpi-x=300 --dpi-y=300 --format=png --output="$outputFile" "$svgFile"
            
            if (Test-Path $outputFile) {
                Write-Host "✅ Successfully created: $outputFile" -ForegroundColor Green
            } else {
                Write-Host "❌ Failed to create: $outputFile" -ForegroundColor Red
            }
        }
    }
} else {
    Write-Host "❌ rsvg-convert not found" -ForegroundColor Red
}

# Method 3: Try ImageMagick
$magickExe = Get-Command magick -ErrorAction SilentlyContinue
if ($magickExe) {
    Write-Host "✅ ImageMagick found: $($magickExe.Source)" -ForegroundColor Green
    
    foreach ($svgFile in $svgFiles) {
        if (Test-Path $svgFile) {
            $outputFile = "$outputDir\$($svgFile -replace '\.svg$', '-imagemagick.png')"
            Write-Host "🔄 Converting $svgFile to PNG (ImageMagick)..." -ForegroundColor Yellow
            
            # High-quality conversion with ImageMagick
            & magick -density 300 "$svgFile" "$outputFile"
            
            if (Test-Path $outputFile) {
                Write-Host "✅ Successfully created: $outputFile" -ForegroundColor Green
            } else {
                Write-Host "❌ Failed to create: $outputFile" -ForegroundColor Red
            }
        }
    }
} else {
    Write-Host "❌ ImageMagick not found" -ForegroundColor Red
}

# Method 4: Browser-based conversion instructions
if (!$inkscapeExe -and !$rsvgExe -and !$magickExe) {
    Write-Host "`n⚠️  No command-line conversion tools found!" -ForegroundColor Yellow
    Write-Host "`n📋 Alternative conversion methods:" -ForegroundColor Cyan
    Write-Host "1. Open svg-to-png-viewer.html in your browser" -ForegroundColor White
    Write-Host "2. Take screenshots of the diagrams" -ForegroundColor White
    Write-Host "3. Use online converters:" -ForegroundColor White
    Write-Host "   • https://convertio.co/svg-png/" -ForegroundColor Gray
    Write-Host "   • https://cloudconvert.com/svg-to-png" -ForegroundColor Gray
    Write-Host "   • https://www.freeconvert.com/svg-to-png" -ForegroundColor Gray
    
    Write-Host "`n💡 To install conversion tools:" -ForegroundColor Cyan
    Write-Host "Inkscape: https://inkscape.org/release/" -ForegroundColor Gray
    Write-Host "ImageMagick: https://imagemagick.org/script/download.php#windows" -ForegroundColor Gray
}

Write-Host "`n🎯 Recommended PNG settings for presentations:" -ForegroundColor Cyan
Write-Host "• Resolution: 300 DPI minimum" -ForegroundColor White
Write-Host "• Width: 1200px+ for architecture diagram" -ForegroundColor White
Write-Host "• Width: 1200px+ for mindmap" -ForegroundColor White
Write-Host "• Format: PNG with transparent background" -ForegroundColor White
Write-Host "• Compression: Lossless PNG" -ForegroundColor White

Write-Host "`n✅ SVG to PNG conversion script completed!" -ForegroundColor Green 