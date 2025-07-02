# XAMPP Setup Guide for VORTEX AI Marketplace Development

## 🚀 Quick Installation (5 minutes)

### Step 1: Download XAMPP
1. **Go to**: https://www.apachefriends.org/download.html
2. **Download**: XAMPP for Windows (PHP 8.2.x version)
3. **File**: `xampp-windows-x64-8.2.12-0-VS16-installer.exe` (or latest)

### Step 2: Install XAMPP
1. **Run installer** as Administrator (right-click → "Run as administrator")
2. **Installation Path**: Keep default `C:\xampp`
3. **Components**: Select at minimum:
   - ✅ Apache
   - ✅ MySQL  
   - ✅ PHP
   - ✅ phpMyAdmin
4. **Click**: Install and wait for completion

### Step 3: Add PHP to System PATH
1. **Open**: Windows Settings → System → Advanced System Settings
2. **Click**: Environment Variables
3. **Under System Variables**: Find and select "Path" → Click "Edit"
4. **Click**: "New" and add: `C:\xampp\php`
5. **Click**: OK to save all dialogs

### Step 4: Verify Installation
Open **new** PowerShell window and test:
```powershell
# Test PHP
php --version
# Should show: PHP 8.2.x

# Test Composer (if not installed, see Step 5)
composer --version
```

### Step 5: Install Composer (if needed)
1. **Download**: https://getcomposer.org/Composer-Setup.exe
2. **Run installer** - it will auto-detect your PHP installation
3. **Verify**: Open new PowerShell → `composer --version`

## 🧪 Testing Your VORTEX Plugin

Once XAMPP is installed, you can run full tests:

### Install Dependencies
```powershell
cd C:\Users\mvill\Documents\marketplace\vortex-ai-marketplace
composer install
```

### Run PHP Code Standards Check
```powershell
# Install PHPCS first
composer global require "squizlabs/php_codesniffer=*"

# Add to PATH: C:\Users\[USERNAME]\AppData\Roaming\Composer\vendor\bin

# Run WordPress standards check
phpcs --standard=WordPress includes/ admin/ public/
```

### Run PHPUnit Tests
```powershell
vendor/bin/phpunit --configuration phpunit.xml
```

### Run Custom Validation
```powershell
php -f run-tests-with-php.php
```

## 🎯 WordPress Development Setup

### Option A: Local WordPress Installation
1. **Start XAMPP**: Open XAMPP Control Panel → Start Apache & MySQL
2. **Download WordPress**: https://wordpress.org/download/
3. **Extract to**: `C:\xampp\htdocs\wordpress`
4. **Access**: http://localhost/wordpress
5. **Install Plugin**: Copy `vortex-ai-marketplace` to `wp-content/plugins/`

### Option B: Plugin Development Only
Just use PHP/Composer for code validation and testing without full WordPress.

## 🔧 Troubleshooting

### PHP Not Found After Installation
- **Restart PowerShell/Command Prompt** after adding to PATH
- **Verify PATH**: `echo $env:PATH` should contain `C:\xampp\php`

### Permission Issues
- **Run PowerShell as Administrator** for installations
- **Check Antivirus**: May block XAMPP installation

### Composer Issues
- **Manual Install**: Download `composer.phar` to project folder
- **Run with**: `php composer.phar install`

## ⚡ Quick Commands Reference

```powershell
# Navigate to project
cd C:\Users\mvill\Documents\marketplace\vortex-ai-marketplace

# Install dependencies
composer install

# Check syntax
php -l includes/class-vortex-ai-marketplace.php

# Run WordPress standards
phpcs --standard=WordPress includes/

# Run tests
vendor/bin/phpunit

# Start XAMPP services
# Open XAMPP Control Panel manually
```

## 🎉 Success Indicators

You'll know everything is working when:
- ✅ `php --version` shows PHP 8.2.x
- ✅ `composer --version` shows Composer version
- ✅ `composer install` runs without errors
- ✅ `phpcs` and `phpunit` commands work
- ✅ No more "command not found" errors

## 📞 Need Help?

If you encounter issues:
1. **Restart your computer** after installation
2. **Try running PowerShell as Administrator**
3. **Check Windows Defender/Antivirus** isn't blocking XAMPP
4. **Manual PHP test**: Try `C:\xampp\php\php.exe --version` directly 