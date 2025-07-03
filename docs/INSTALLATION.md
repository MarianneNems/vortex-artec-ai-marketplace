# VORTEX AI Marketplace - Installation Guide

## 📋 System Requirements

### WordPress Requirements
- **WordPress:** 5.8 or higher
- **PHP:** 8.1 or higher (minimum 7.4)
- **MySQL:** 5.7 or higher / MariaDB 10.3+
- **Memory:** 512MB minimum (1GB+ recommended)
- **Disk Space:** 500MB+ for plugin and assets
- **SSL Certificate:** Required for blockchain integration

### Server Requirements
- **Web Server:** Apache/Nginx with mod_rewrite
- **cURL Extension:** Required for API calls
- **GD Extension:** Required for image processing
- **OpenSSL:** Required for blockchain transactions
- **JSON Support:** Required for API responses

### Optional Dependencies
- **WooCommerce:** 5.0+ (for enhanced e-commerce features)
- **Elementor:** Latest version (for widget support)
- **Docker:** For containerized AI server deployment
- **Node.js:** 18+ (for frontend development)
- **Python:** 3.9+ (for AI server components)

---

## 🚀 Installation Methods

### Method 1: WordPress Plugin Installation (Recommended)

#### Step 1: Download and Upload
1. Download the `vortex-ai-marketplace.zip` file
2. Log in to your WordPress admin dashboard
3. Navigate to **Plugins → Add New**
4. Click **Upload Plugin**
5. Select the ZIP file and click **Install Now**
6. Click **Activate Plugin**

#### Step 2: Database Setup
The plugin automatically creates the following tables on activation:
- `wp_vortex_artworks`
- `wp_vortex_artists`
- `wp_vortex_artwork_stats`
- `wp_vortex_artist_stats`
- `wp_vortex_token_transactions`
- `wp_vortex_metrics`
- `wp_vortex_rankings`
- `wp_vortex_language_preferences`
- `wp_vortex_agent_logs`
- `wp_vortex_learning_metrics`
- `wp_vortex_performance_logs`

### Method 2: Manual Installation

#### Step 1: Extract Files
```bash
# Extract to WordPress plugins directory
cd /path/to/wordpress/wp-content/plugins/
unzip vortex-ai-marketplace.zip

# Set proper permissions
chmod -R 755 vortex-ai-marketplace/
chown -R www-data:www-data vortex-ai-marketplace/
```

#### Step 2: Install Dependencies
```bash
# Navigate to plugin directory
cd vortex-ai-marketplace/

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node.js dependencies (if developing)
npm ci
```

#### Step 3: Activate Plugin
1. Go to WordPress admin → **Plugins**
2. Find "VORTEX AI Marketplace"
3. Click **Activate**

---

## ⚙️ Configuration

### Step 1: Basic Configuration

1. Navigate to **VORTEX AI** in your WordPress admin menu
2. Go to **Settings** tab
3. Configure the following basic settings:

#### General Settings
- **Plugin Mode:** Production/Development
- **Debug Mode:** Enable for troubleshooting
- **Cache Duration:** 3600 seconds (recommended)
- **API Rate Limit:** 100 requests per minute

#### AI Agent Settings
- **THORIUS:** Enable/disable ethical concierge
- **HURAII:** Enable/disable artistic AI
- **CLOE:** Enable/disable curation engine
- **Business Strategist:** Enable/disable financial guidance

### Step 2: Environment Variables

#### Option A: wp-config.php Configuration
Add these constants to your `wp-config.php` file:

```php
// API Configuration
define('VORTEX_BASE_URL', 'https://api.vortexmarketplace.io');
define('VORTEX_API_RATE_LIMIT', 100);

// External API Keys
define('VORTEX_OPENAI_API_KEY', 'your-openai-api-key');
define('VORTEX_STABILITY_API_KEY', 'your-stability-ai-key');
define('VORTEX_HUGGINGFACE_API_KEY', 'your-huggingface-key');

// Blockchain Configuration
define('VORTEX_BLOCKCHAIN_PROVIDER', 'solana');
define('VORTEX_BLOCKCHAIN_NETWORK', 'mainnet-beta');
define('VORTEX_BLOCKCHAIN_RPC_URL', 'https://api.mainnet-beta.solana.com');
define('VORTEX_TOLA_TOKEN_MINT', 'your-tola-token-mint-address');

// AWS Configuration (Optional)
define('AWS_ACCESS_KEY_ID', 'your-aws-access-key');
define('AWS_SECRET_ACCESS_KEY', 'your-aws-secret-key');
define('AWS_S3_BUCKET', 'your-s3-bucket-name');
define('AWS_REGION', 'us-east-1');

// Security
define('VORTEX_ENCRYPTION_KEY', 'your-256-bit-encryption-key');
define('VORTEX_JWT_SECRET', 'your-jwt-secret-key');
```

#### Option B: Environment File (.env)
Create a `.env` file in the plugin root directory:

```bash
# API Configuration
VORTEX_BASE_URL=https://api.vortexmarketplace.io
VORTEX_API_RATE_LIMIT=100

# External API Keys
VORTEX_OPENAI_API_KEY=your-openai-api-key
VORTEX_STABILITY_API_KEY=your-stability-ai-key
VORTEX_HUGGINGFACE_API_KEY=your-huggingface-key

# Blockchain Configuration
VORTEX_BLOCKCHAIN_PROVIDER=solana
VORTEX_BLOCKCHAIN_NETWORK=mainnet-beta
VORTEX_BLOCKCHAIN_RPC_URL=https://api.mainnet-beta.solana.com
VORTEX_TOLA_TOKEN_MINT=your-tola-token-mint-address

# AWS Configuration (Optional)
AWS_ACCESS_KEY_ID=your-aws-access-key
AWS_SECRET_ACCESS_KEY=your-aws-secret-key
AWS_S3_BUCKET=your-s3-bucket-name
AWS_REGION=us-east-1

# Security
VORTEX_ENCRYPTION_KEY=your-256-bit-encryption-key
VORTEX_JWT_SECRET=your-jwt-secret-key
```

### Step 3: API Keys Setup

#### Required API Keys
1. **OpenAI API Key** - For THORIUS and agent functionality
2. **Stability.ai API Key** - For HURAII image generation
3. **Solana RPC URL** - For blockchain integration

#### Optional API Keys
1. **Hugging Face API Key** - For additional AI models
2. **AWS Credentials** - For S3 file storage
3. **Anthropic API Key** - For Claude integration

#### How to Obtain API Keys

**OpenAI:**
1. Visit https://platform.openai.com/
2. Create an account or log in
3. Generate an API key from the API section
4. Set usage limits and billing

**Stability.ai:**
1. Visit https://platform.stability.ai/
2. Create an account and verify email
3. Generate API key from dashboard
4. Add credits to your account

**Solana RPC:**
- Use public RPC: `https://api.mainnet-beta.solana.com`
- Or use premium services like Alchemy, QuickNode

### Step 4: Blockchain Configuration

#### Solana Wallet Setup
1. Install a Solana wallet (Phantom, Solflare)
2. Generate or import a wallet keypair
3. Add the public key to plugin settings
4. Fund wallet with SOL for transaction fees

#### TOLA Token Configuration
1. Obtain TOLA token mint address
2. Configure token decimals (usually 9 for SPL tokens)
3. Set up token account for transactions
4. Configure royalty distribution settings

---

## 🛠️ Advanced Setup

### AI Server Deployment

#### Option A: Docker Deployment (Recommended)

1. **Build Docker Image**
```bash
# From project root
docker build -t vortex-ai-server .
```

2. **Run Container**
```bash
docker run -d -p 8000:8000 \
  -e AWS_ACCESS_KEY_ID=your_key \
  -e AWS_SECRET_ACCESS_KEY=your_secret \
  -e SOLANA_RPC_URL=your_solana_rpc \
  -e OPENAI_API_KEY=your_openai_key \
  --name vortex-ai vortex-ai-server
```

3. **Configure Plugin**
Set AI server URL in plugin settings: `http://localhost:8000`

#### Option B: Manual Python Server

1. **Install Python Dependencies**
```bash
cd server/
pip install -r requirements.txt
```

2. **Start AI Server**
```bash
uvicorn server.main:app --host 0.0.0.0 --port 8000
```

3. **Configure Plugin**
Set AI server URL in plugin settings: `http://localhost:8000`

### Database Optimization

#### MySQL Configuration
Add these settings to your `my.cnf` file:

```ini
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_file_per_table = 1
query_cache_size = 128M
query_cache_type = 1
max_connections = 300
```

#### WordPress Database Optimization
```php
// Add to wp-config.php
define('WP_MEMORY_LIMIT', '512M');
define('WP_MAX_MEMORY_LIMIT', '1024M');
define('WP_CACHE', true);
define('COMPRESS_CSS', true);
define('COMPRESS_SCRIPTS', true);
define('CONCATENATE_SCRIPTS', true);
```

### Performance Optimization

#### Caching Configuration
```php
// Redis Cache (if available)
define('WP_REDIS_HOST', 'localhost');
define('WP_REDIS_PORT', 6379);
define('WP_REDIS_DATABASE', 0);

// Object Cache
define('WP_CACHE_KEY_SALT', 'vortex-ai-marketplace');
```

#### CDN Configuration
Configure CDN for static assets:
1. Upload images to AWS S3/CloudFront
2. Configure image optimization
3. Enable gzip compression
4. Set up proper caching headers

---

## 🔧 Frontend Integration

### Theme Integration

Add to your theme's `functions.php`:

```php
// Enqueue VORTEX styles and scripts
function vortex_theme_integration() {
    if (class_exists('Vortex_AI_Marketplace')) {
        wp_enqueue_style('vortex-frontend');
        wp_enqueue_script('vortex-frontend');
    }
}
add_action('wp_enqueue_scripts', 'vortex_theme_integration');

// Add VORTEX menu
function vortex_add_menu() {
    register_nav_menus(array(
        'vortex-main-menu' => __('VORTEX Main Menu', 'vortex-ai-marketplace'),
        'vortex-footer-menu' => __('VORTEX Footer Menu', 'vortex-ai-marketplace')
    ));
}
add_action('after_setup_theme', 'vortex_add_menu');
```

### Page Creation

Create these essential pages:

1. **Marketplace** - Main marketplace page
2. **Artist Dashboard** - Artist control panel
3. **Collector Dashboard** - Collector interface
4. **Art Generator** - AI art creation tool
5. **TOLA Wallet** - Token management
6. **Community** - User interaction hub

### Shortcode Usage

```html
<!-- THORIUS Chat Interface -->
[vortex_thorius_chat]

<!-- HURAII Art Generator -->
[vortex_huraii_generator]

<!-- Artist Dashboard -->
[vortex_artist_dashboard]

<!-- Collector Dashboard -->
[vortex_collector_dashboard]

<!-- Marketplace Grid -->
[vortex_marketplace_grid]

<!-- TOLA Balance Display -->
[vortex_tola_balance]
```

---

## 🔐 Security Configuration

### API Security

1. **Rate Limiting**
```php
// Configure rate limits
define('VORTEX_API_RATE_LIMIT', 100); // requests per minute
define('VORTEX_API_BURST_LIMIT', 10); // burst requests
```

2. **Authentication**
```php
// JWT Configuration
define('VORTEX_JWT_SECRET', 'your-secure-jwt-secret');
define('VORTEX_JWT_EXPIRATION', 3600); // 1 hour
```

3. **CORS Configuration**
```php
// Allowed origins
define('VORTEX_ALLOWED_ORIGINS', 'https://yourdomain.com,https://app.yourdomain.com');
```

### Blockchain Security

1. **Wallet Security**
- Use hardware wallets for production
- Store private keys securely
- Enable multi-signature wallets
- Regular security audits

2. **Smart Contract Security**
- Audit all smart contracts
- Use trusted contract templates
- Implement proper access controls
- Monitor contract activity

---

## 📊 Monitoring and Analytics

### Performance Monitoring

1. **Database Monitoring**
```php
// Enable query logging
define('SAVEQUERIES', true);

// Monitor slow queries
define('VORTEX_SLOW_QUERY_THRESHOLD', 2.0); // seconds
```

2. **API Monitoring**
```php
// Enable API logging
define('VORTEX_API_LOGGING', true);

// Log slow API calls
define('VORTEX_API_SLOW_THRESHOLD', 5.0); // seconds
```

### Error Tracking

1. **WordPress Error Logging**
```php
// Enable error logging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

2. **VORTEX Error Tracking**
```php
// Enable VORTEX debugging
define('VORTEX_DEBUG', true);
define('VORTEX_LOG_LEVEL', 'info'); // debug, info, warning, error
```

---

## 🧪 Testing

### Unit Testing

```bash
# Run PHP unit tests
composer test

# Run specific test suite
vendor/bin/phpunit tests/unit/

# Run integration tests
vendor/bin/phpunit tests/integration/
```

### Frontend Testing

```bash
# Run JavaScript tests
npm test

# Run e2e tests
npm run test:e2e

# Run performance tests
npm run test:performance
```

### API Testing

```bash
# Test API endpoints
curl -X POST http://localhost/wp-json/vortex/v1/thorius/query \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{"query": "Test query"}'
```

---

## 🚨 Troubleshooting

### Common Issues

#### Database Connection Issues
```bash
# Check database credentials
wp db check

# Repair database tables
wp db repair
```

#### API Connection Errors
1. Verify API keys are correct
2. Check firewall settings
3. Test network connectivity
4. Review error logs

#### Memory Issues
```php
// Increase memory limit
ini_set('memory_limit', '1024M');

// Optimize database queries
// Use proper indexing
// Enable query caching
```

#### Permission Issues
```bash
# Set proper file permissions
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Set ownership
chown -R www-data:www-data .
```

### Debug Mode

Enable debug mode for troubleshooting:

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('VORTEX_DEBUG', true);
```

### Log Locations

- **WordPress Logs:** `/wp-content/debug.log`
- **VORTEX Logs:** `/wp-content/plugins/vortex-ai-marketplace/logs/`
- **Server Logs:** `/var/log/apache2/error.log` or `/var/log/nginx/error.log`

---

## 🎯 Post-Installation Checklist

### Required Steps
- [ ] Plugin activated successfully
- [ ] Database tables created
- [ ] API keys configured
- [ ] Blockchain connection tested
- [ ] AI agents responding
- [ ] Frontend loading correctly

### Optional Steps
- [ ] CDN configured
- [ ] Caching enabled
- [ ] Monitoring tools installed
- [ ] Backup solution implemented
- [ ] Security hardening completed

### Verification Tests
- [ ] Create test artwork
- [ ] Process test transaction
- [ ] Verify AI agent responses
- [ ] Check blockchain integration
- [ ] Test API endpoints

---

## 📞 Support

### Documentation
- **User Guide:** `www.vortexartec.com/docs/user-guides`
- **API Reference:** `www.vortexartec.com/docs/api-documentation`
- **Developer Guide:** `www.vortexartec.com/docs/developer-guide`

### Community Support
- **GitHub Issues:** https://github.com/MarianneNems/VORTEX-AI-MARKETPLACE/issues
- **Discord Server:** https://discord.gg/vortexai
- **Community Forum:** https://community.vortexartec.com

### Professional Support
- **Email:** info@vortexartec.com
- **Priority Support:** Available for enterprise customers
- **Custom Development:** Available on request

---

## 🔄 Updates and Maintenance

### Plugin Updates
- Regular updates released monthly
- Security patches as needed
- Breaking changes documented
- Migration guides provided

### Maintenance Tasks
- **Weekly:** Check logs and performance
- **Monthly:** Update API keys and certificates
- **Quarterly:** Security audit and optimization
- **Annually:** Full system review and upgrades

---

*This installation guide is maintained by the VORTEX team. For the latest updates, visit: www.vortexartec.com/docs*
