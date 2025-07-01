# 🚀 **VortexArtec Website Deployment Guide**

## 📋 **Complete Deployment to www.vortexartec.com**

### **🎯 Deployment Overview**
Your VortexArtec marketplace is now ready for production deployment with:
- ✅ **RunPod AI Integration**: Connected to https://4416007023f09466f6.gradio.live
- ✅ **HURAII AI Agent**: Complete automation system
- ✅ **Blockchain Integration**: TOLA tokens and smart contracts
- ✅ **Complete User Journey**: From prompt to NFT to marketplace
- ✅ **GitHub Repository**: https://github.com/MarianneNems/vortex-artec-ai-marketplace

---

## 🛠️ **Pre-Deployment Requirements**

### **Server Requirements**
- **PHP**: 8.0 or higher
- **MySQL**: 5.7 or higher
- **WordPress**: 6.0 or higher
- **Memory**: 512MB minimum (2GB recommended)
- **Storage**: 10GB minimum (50GB recommended)
- **SSL Certificate**: Required for blockchain features

### **Required Extensions**
```bash
# PHP Extensions needed
php-curl
php-gd
php-mbstring
php-xml
php-zip
php-mysql
php-imagick
```

---

## 📁 **Step 1: Download & Prepare Files**

### **Option A: Download from GitHub**
```bash
# Clone the repository
git clone https://github.com/MarianneNems/vortex-artec-ai-marketplace.git
cd vortex-artec-ai-marketplace
```

### **Option B: Download ZIP**
1. Go to: https://github.com/MarianneNems/vortex-artec-ai-marketplace
2. Click **"Code"** → **"Download ZIP"**
3. Extract to your local folder

### **Files to Upload**
```
Your WordPress Installation/
├── wp-content/
│   └── plugins/
│       └── vortex-ai-marketplace/          # ← UPLOAD ENTIRE FOLDER
│           ├── admin/
│           ├── includes/
│           ├── public/
│           ├── assets/
│           ├── blocks/
│           ├── contracts/
│           ├── deployment-package/
│           ├── docs/
│           └── vortex-ai-marketplace.php
└── wp-content/
    └── themes/
        └── your-theme/                      # ← OPTIONAL: Custom theme files
```

---

## 🌐 **Step 2: WordPress Installation Methods**

### **Method A: cPanel File Manager (Recommended)**

#### **Step 2A.1: Access cPanel**
1. Log into your hosting cPanel
2. Navigate to **"File Manager"**
3. Go to **"public_html"** (or your domain folder)

#### **Step 2A.2: Upload Plugin**
```bash
# Navigate to the WordPress plugins directory
cd public_html/wp-content/plugins/

# Create the plugin directory
mkdir vortex-ai-marketplace

# Upload all files to this directory
```

1. **Upload Method 1 - ZIP Upload**:
   - Create ZIP of entire marketplace folder
   - Upload ZIP to `/wp-content/plugins/`
   - Extract ZIP in cPanel File Manager

2. **Upload Method 2 - Direct Upload**:
   - Select all files in marketplace folder
   - Upload to `/wp-content/plugins/vortex-ai-marketplace/`

#### **Step 2A.3: Set Permissions**
```bash
# Set correct permissions
chmod 755 vortex-ai-marketplace/
chmod 644 vortex-ai-marketplace/*.php
chmod 755 vortex-ai-marketplace/assets/
chmod -R 644 vortex-ai-marketplace/assets/*
```

### **Method B: FTP Upload**

#### **Step 2B.1: FTP Connection**
```bash
# Connect via FTP client (FileZilla, WinSCP, etc.)
Host: your-domain.com
Username: your-ftp-username
Password: your-ftp-password
Port: 21 (or 22 for SFTP)
```

#### **Step 2B.2: Navigate & Upload**
```bash
# Navigate to WordPress root
cd /public_html/wp-content/plugins/

# Create directory
mkdir vortex-ai-marketplace

# Upload all marketplace files
# (Drag & drop in FTP client)
```

### **Method C: SSH/Terminal Upload**

#### **Step 2C.1: SSH Connection**
```bash
# Connect to your server
ssh username@your-domain.com

# Navigate to WordPress
cd /var/www/html/wp-content/plugins/
# OR
cd /home/username/public_html/wp-content/plugins/
```

#### **Step 2C.2: Upload via Git**
```bash
# Clone directly on server
git clone https://github.com/MarianneNems/vortex-artec-ai-marketplace.git vortex-ai-marketplace

# Set ownership (replace 'www-data' with your web server user)
chown -R www-data:www-data vortex-ai-marketplace/
chmod -R 755 vortex-ai-marketplace/
```

---

## ⚙️ **Step 3: WordPress Configuration**

### **Step 3.1: Plugin Activation**
1. Log into **WordPress Admin** → `/wp-admin/`
2. Go to **"Plugins"** → **"Installed Plugins"**
3. Find **"VortexArtec AI Marketplace"**
4. Click **"Activate"**

### **Step 3.2: Initial Setup Wizard**
After activation, you'll see:
```
🎨 VortexArtec Setup Wizard
┌─────────────────────────────────┐
│ 1. Database Setup               │
│ 2. RunPod Configuration        │
│ 3. Blockchain Settings         │
│ 4. AWS S3 Configuration        │
│ 5. TOLA Token Setup            │
└─────────────────────────────────┘
```

### **Step 3.3: RunPod Configuration**
1. Go to **WordPress Admin** → **"VortexArtec"** → **"RunPod Settings"**
2. Configure:
   ```
   Primary Server URL: https://4416007023f09466f6.gradio.live
   Timeout: 120 seconds
   Max Retries: 3
   Model: sd_xl_base_1.0.safetensors
   Default Steps: 30
   CFG Scale: 7.5
   Sampler: DPM++ 2M Karras
   ```
3. Click **"Test Connection"** to verify
4. Save settings

---

## 🔐 **Step 4: Environment Configuration**

### **Step 4.1: WordPress wp-config.php**
Add these constants to your `wp-config.php`:

```php
// VortexArtec Configuration
define('VORTEX_RUNPOD_URL', 'https://4416007023f09466f6.gradio.live');
define('VORTEX_AWS_REGION', 'us-east-2');
define('VORTEX_S3_BUCKET', 'vortexartec.com-client-art');

// Security Keys (generate new ones)
define('VORTEX_API_SECRET', 'your-unique-api-secret-key');
define('VORTEX_ENCRYPTION_KEY', 'your-encryption-key');

// Debug (set to false in production)
define('VORTEX_DEBUG', true);
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### **Step 4.2: Environment Variables (.env)**
Create `.env` file in plugin root:
```bash
# RunPod Configuration
RUNPOD_PRIMARY_URL=https://4416007023f09466f6.gradio.live
RUNPOD_TIMEOUT=120
RUNPOD_MAX_RETRIES=3

# AWS Configuration
AWS_ACCESS_KEY_ID=your-aws-access-key
AWS_SECRET_ACCESS_KEY=your-aws-secret-key
AWS_REGION=us-east-2
AWS_S3_BUCKET=vortexartec.com-client-art

# Blockchain Configuration
ETHEREUM_NETWORK=mainnet
TOLA_CONTRACT_ADDRESS=0x...
PRIVATE_KEY=your-private-key

# API Keys
OPENAI_API_KEY=your-openai-key
HURAII_API_KEY=your-huraii-key
```

---

## 🗄️ **Step 5: Database Setup**

### **Automatic Database Creation**
The plugin will automatically create these tables:
```sql
wp_vortex_artists
wp_vortex_artworks
wp_vortex_collections
wp_vortex_transactions
wp_vortex_huraii_generations
wp_vortex_user_profiles
wp_vortex_marketplace_listings
wp_vortex_daily_challenges
```

### **Manual Database Setup (if needed)**
```bash
# Run the setup script
cd deployment-package/
chmod +x setup-runpod-integration.sh
./setup-runpod-integration.sh
```

---

## 🎨 **Step 6: Frontend Configuration**

### **Step 6.1: Theme Integration**
If using a custom theme, add to your `functions.php`:

```php
// Enqueue VortexArtec styles and scripts
function vortex_theme_integration() {
    if (class_exists('Vortex_AI_Marketplace')) {
        wp_enqueue_style('vortex-frontend');
        wp_enqueue_script('vortex-frontend');
    }
}
add_action('wp_enqueue_scripts', 'vortex_theme_integration');

// Add VortexArtec menu
function vortex_add_menu() {
    wp_nav_menu(array(
        'theme_location' => 'vortex-main-menu',
        'menu_class' => 'vortex-navigation'
    ));
}
```

### **Step 6.2: Create Essential Pages**
Create these WordPress pages:

1. **Homepage**: Marketplace showcase
2. **AI Studio**: `/ai-studio/` - Art generation interface
3. **Gallery**: `/gallery/` - User galleries
4. **Marketplace**: `/marketplace/` - NFT marketplace
5. **Profile**: `/profile/` - User profiles
6. **TOLA-ART**: `/tola-art/` - Daily competition

### **Step 6.3: Configure Permalinks**
1. Go to **WordPress Admin** → **"Settings"** → **"Permalinks"**
2. Select **"Post name"** structure
3. Click **"Save Changes"**

---

## 🔧 **Step 7: Advanced Configuration**

### **Step 7.1: SSL Certificate**
Ensure SSL is installed and working:
```bash
# Test SSL
curl -I https://www.vortexartec.com

# Should return HTTP/1.1 200 OK
```

### **Step 7.2: CDN Configuration**
For better performance, configure CloudFlare:
1. Add domain to CloudFlare
2. Enable **"Full SSL"** mode
3. Configure caching rules for images
4. Enable Rocket Loader for JavaScript

### **Step 7.3: Backup Configuration**
Set up automated backups:
```bash
# Daily database backup
0 2 * * * /usr/local/bin/wp db export /backups/vortex-$(date +\%Y\%m\%d).sql

# Weekly full backup
0 1 * * 0 tar -czf /backups/vortex-full-$(date +\%Y\%m\%d).tar.gz /var/www/html/
```

---

## 🧪 **Step 8: Testing & Verification**

### **Step 8.1: Functionality Tests**
Run these tests to verify everything works:

1. **Plugin Activation Test**
   ```bash
   ✅ Plugin activates without errors
   ✅ Database tables created
   ✅ Admin menus appear
   ```

2. **RunPod Connection Test**
   ```bash
   ✅ Go to VortexArtec → RunPod Settings
   ✅ Click "Test Connection"
   ✅ Should show "Connection Successful"
   ```

3. **AI Generation Test**
   ```bash
   ✅ Go to AI Studio page
   ✅ Enter test prompt: "beautiful sunset over mountains"
   ✅ Generate image successfully
   ✅ Image appears in WordPress media library
   ```

4. **Blockchain Integration Test**
   ```bash
   ✅ Connect MetaMask wallet
   ✅ TOLA token balance displays
   ✅ NFT creation works
   ```

### **Step 8.2: Performance Tests**
```bash
# Page load speed test
curl -w "%{time_total}\n" -o /dev/null -s https://www.vortexartec.com

# Should be under 3 seconds

# Memory usage test
wp eval "echo memory_get_peak_usage(true) / 1024 / 1024 . ' MB';"

# Should be under 128MB
```

---

## 🚀 **Step 9: Go Live Checklist**

### **Pre-Launch Checklist**
- [ ] ✅ Plugin activated and configured
- [ ] ✅ RunPod server connected and tested
- [ ] ✅ SSL certificate installed
- [ ] ✅ Database optimized
- [ ] ✅ Backup system configured
- [ ] ✅ CDN configured (optional)
- [ ] ✅ Error monitoring setup
- [ ] ✅ Analytics configured

### **Launch Day Tasks**
- [ ] 🌐 Update DNS if needed
- [ ] 📧 Configure email settings
- [ ] 🔍 Submit to search engines
- [ ] 📱 Test mobile responsiveness
- [ ] 🎨 Create first TOLA-ART competition
- [ ] 👥 Invite beta users

### **Post-Launch Monitoring**
- [ ] 📊 Monitor error logs
- [ ] 🎯 Track user engagement
- [ ] 💰 Monitor TOLA token metrics
- [ ] 🔧 Performance optimization
- [ ] 📈 SEO optimization

---

## 🆘 **Troubleshooting Guide**

### **Common Issues & Solutions**

#### **Issue: Plugin Won't Activate**
```bash
# Check PHP error logs
tail -f /var/log/php_errors.log

# Common solutions:
1. Increase PHP memory limit to 512MB
2. Check file permissions (755 for directories, 644 for files)
3. Verify all files uploaded correctly
```

#### **Issue: RunPod Connection Failed**
```bash
# Test direct connection
curl -I https://4416007023f09466f6.gradio.live/sdapi/v1/options

# Solutions:
1. Check server firewall settings
2. Verify RunPod server is running
3. Update server URL if changed
```

#### **Issue: Images Not Generating**
```bash
# Check logs
wp log tail --lines=50

# Common solutions:
1. Verify TOLA token balance
2. Check PHP timeout settings
3. Increase WordPress memory limit
```

#### **Issue: Database Errors**
```bash
# Check database connection
wp db check

# Repair database if needed
wp db repair
```

---

## 📞 **Support & Maintenance**

### **Getting Help**
- **GitHub Issues**: https://github.com/MarianneNems/vortex-artec-ai-marketplace/issues
- **Documentation**: `/docs/` folder in repository
- **Error Logs**: Check WordPress debug.log

### **Regular Maintenance**
```bash
# Weekly tasks
1. Update WordPress core
2. Update plugin if new version available
3. Check error logs
4. Monitor server performance
5. Backup database

# Monthly tasks
1. Security scan
2. Performance optimization
3. User feedback review
4. Feature updates
```

---

## 🎉 **Success! Your VortexArtec Marketplace is Live!**

### **Live URLs**
- **Main Site**: https://www.vortexartec.com
- **AI Studio**: https://www.vortexartec.com/ai-studio/
- **Marketplace**: https://www.vortexartec.com/marketplace/
- **Admin**: https://www.vortexartec.com/wp-admin/

### **Next Steps**
1. 🎨 Create your first artwork using HURAII
2. 💰 Set up TOLA token distribution
3. 🏆 Launch first TOLA-ART competition
4. 👥 Invite artists and collectors
5. 📱 Share on social media

**Your AI-powered art marketplace is now ready to revolutionize digital art creation and trading!** 🚀🎨 