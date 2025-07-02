# VORTEX AI Marketplace - WordPress Plugin

![Version](https://img.shields.io/badge/version-3.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)
![License](https://img.shields.io/badge/license-Proprietary-red.svg)
![WooCommerce](https://img.shields.io/badge/WooCommerce-5.0%2B-purple.svg)

**The complete AI-powered marketplace solution for WordPress with WooCommerce integration, blockchain technology, and automated artist royalty distribution.**

---

## 🚀 Features

### 🤖 AI-Powered Generation
- **HURAII Integration**: Advanced AI art generation via GPU-powered backend
- **Daily TOLA Masterworks**: Automated daily art creation at 00:00 UTC
- **Custom Prompts**: User-defined AI generation with advanced parameters
- **Quality Control**: Automated content filtering and verification

### 💰 WooCommerce Integration  
- **Seamless Commerce**: Full WooCommerce integration for marketplace sales
- **Subscription Tiers**: Artist Starter ($29), Pro ($59), Studio ($99)
- **TOLA Token Purchases**: Virtual products for token pack sales
- **Automated Billing**: Recurring subscriptions with automatic role assignment

### 🎨 Artist Journey Management
- **Role Assessment**: Interactive quiz for artist skill evaluation
- **HORACE Business Quiz**: Advanced business strategy assessment (Pro+)
- **Seed Art Upload**: Drag-and-drop artwork submission with AWS S3 storage
- **Milestone Tracking**: Achievement system with progress indicators

### 🔗 Blockchain & NFT
- **Solana Integration**: Smart contract deployment for NFT minting
- **TOLA Token System**: 1:1 USD utility token with transparent fees
- **Royalty Distribution**: Automated payments to participating artists
- **Immutable Provenance**: Blockchain verification for all artworks

### 🏆 Gamification System
- **5 Achievement Tiers**: From "First Steps" to "Visionary Leader"
- **Token Rewards**: 1 TOLA for uploads, 1 for generations, 5 for sales
- **Event Tracking**: Comprehensive analytics and progress monitoring
- **Leaderboards**: Community recognition and ranking systems

---

## 📋 Requirements

- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher  
- **MySQL:** 5.7 or higher
- **WooCommerce:** 5.0 or higher
- **Memory:** 512MB+ recommended
- **HTTPS:** Required for secure operations

### Optional Services
- **AWS S3:** For file storage (local fallback available)
- **External AI Server:** For custom generation endpoints
- **Blockchain RPC:** For Solana network integration

---

## 🛠 Installation

### Automatic Installation
1. Download the plugin ZIP file
2. Go to **Plugins > Add New** in WordPress admin
3. Click **Upload Plugin** and select the ZIP file
4. Click **Install Now** and then **Activate**
5. Follow the setup wizard in **VORTEX AI > Dashboard**

### Manual Installation
```bash
# Extract to WordPress plugins directory
cd /path/to/wordpress/wp-content/plugins/
unzip vortex-ai-marketplace.zip

# Set proper permissions
chmod -R 755 vortex-ai-marketplace/
chown -R www-data:www-data vortex-ai-marketplace/
```

### Initial Configuration
1. **Navigate to VORTEX AI Settings**
2. **Configure AWS S3** (optional - has local fallback)
3. **Set AI Server Endpoint** (optional - includes mock responses)
4. **Add Blockchain Credentials** (optional - mock integration included)
5. **Create WooCommerce Products** (automated on first activation)

---

## 🏗 Architecture

### Core Components
```
vortex-ai-marketplace/
├── vortex-ai-marketplace.php      # Main plugin file
├── includes/                      # Core functionality
│   ├── class-vortex-ai-marketplace.php
│   ├── class-vortex-subscriptions.php
│   ├── class-vortex-ai-marketplace-wallet.php
│   ├── class-vortex-artist-journey-shortcodes.php
│   ├── class-vortex-seed-art-manager.php
│   ├── class-vortex-gamification.php
│   ├── class-vortex-tola-art-daily-automation.php
│   └── api/
│       └── class-vortex-ai-api.php
├── admin/                         # Admin interface
│   └── class-vortex-settings.php
├── public/                        # Frontend assets
├── templates/                     # Template files
└── tests/                        # PHPUnit tests
```

### Database Schema
- **vortex_transactions**: TOLA token transaction history
- **vortex_wallets**: User wallet management and balances  
- **vortex_seed_artworks**: Artist uploaded seed art tracking
- **vortex_gamification_events**: Achievement and reward tracking
- **vortex_daily_art**: Daily TOLA Masterwork generation records
- **vortex_artist_participation**: Royalty distribution participants
- **vortex_royalty_distribution**: Sale proceeds allocation tracking

---

## 🔌 API Endpoints

### Public REST API
```php
// AI Generation
POST /wp-json/vortex/v1/generate
{
    "prompt": "string",
    "style": "string",
    "dimensions": "1024x1024"
}

// Seed Art Upload
POST /wp-json/vortex/v1/upload-seed
{
    "file": "binary",
    "title": "string", 
    "description": "string"
}

// TOLA Balance
GET /wp-json/vortex/v1/balance/{user_id}

// User Gallery
GET /wp-json/vortex/v1/gallery/{user_id}
```

### Authentication
All API endpoints require:
- **WordPress nonce** verification
- **User authentication** for protected endpoints
- **Permission checks** based on subscription tier

---

## 🎨 Shortcodes

### Artist Journey
```php
[vortex_role_quiz]           // Interactive role assessment
[vortex_horas_quiz]          // Business strategy quiz (Pro+)
[vortex_seed_upload]         // Drag-and-drop file uploader
[vortex_milestones]          // Achievement progress display
```

### Marketplace
```php
[vortex_ai_generator]        // AI generation interface
[vortex_token_balance]       // User TOLA balance display
[vortex_purchase_history]    // Transaction history
[vortex_daily_masterwork]    // Today's featured artwork
```

---

## 🔧 Configuration

### Environment Variables
```php
// AWS S3 Configuration
define('VORTEX_AWS_ACCESS_KEY', 'your-access-key');
define('VORTEX_AWS_SECRET_KEY', 'your-secret-key');
define('VORTEX_AWS_BUCKET', 'your-bucket-name');
define('VORTEX_AWS_REGION', 'us-east-1');

// AI Server Configuration  
define('VORTEX_AI_ENDPOINT', 'https://your-ai-server.com/api');
define('VORTEX_AI_API_KEY', 'your-api-key');

// Blockchain Configuration
define('VORTEX_SOLANA_RPC', 'https://api.mainnet-beta.solana.com');
define('VORTEX_WALLET_PRIVATE_KEY', 'your-wallet-key');
```

### WordPress Options
```php
// Plugin settings stored in WordPress options table
get_option('vortex_ai_settings');        // General plugin settings
get_option('vortex_aws_settings');       // AWS S3 configuration  
get_option('vortex_blockchain_settings'); // Blockchain credentials
get_option('vortex_fee_structure');      // Marketplace fee settings
```

---

## 🧪 Testing

### PHPUnit Tests
```bash
# Install dependencies
composer install

# Run all tests
vendor/bin/phpunit

# Run specific test suite
vendor/bin/phpunit tests/test-woocommerce-integration.php
vendor/bin/phpunit tests/test-api-endpoints.php
```

### Manual Testing
1. **WooCommerce Integration**: Test subscription purchases and role assignment
2. **API Endpoints**: Verify AI generation and file upload functionality  
3. **Shortcodes**: Ensure proper rendering and AJAX functionality
4. **Gamification**: Test achievement unlocking and token rewards
5. **Daily Automation**: Verify TOLA Masterwork generation and sales

### Test Coverage
- ✅ WooCommerce subscription processing
- ✅ TOLA token transaction handling
- ✅ AI API endpoint responses  
- ✅ File upload validation
- ✅ User permission checking
- ✅ Database operations
- ✅ Shortcode rendering

---

## 🚦 Deployment

### Production Checklist
- [ ] **SSL Certificate** installed and configured
- [ ] **WooCommerce** properly configured with payment gateways
- [ ] **AWS S3** credentials configured (or local storage prepared)
- [ ] **AI Server** endpoint configured (or using mock responses)
- [ ] **Database backups** scheduled
- [ ] **File permissions** properly set (755/644)
- [ ] **Error logging** enabled for debugging

### Performance Optimization
- **Object Caching**: Enable Redis or Memcached
- **Image Optimization**: Configure WebP support
- **CDN Integration**: For static asset delivery
- **Database Indexing**: Verify custom table indexes
- **Cron Jobs**: Monitor WordPress cron performance

---

## 🤝 Contributing

### Development Setup
```bash
# Clone repository
git clone https://github.com/vortexartec/vortex-ai-marketplace.git
cd vortex-ai-marketplace

# Install dependencies
composer install
npm install

# Set up development environment
cp .env.example .env
# Configure your local settings

# Run development server
npm run dev
```

### Coding Standards
- **PSR-4** autoloading for PHP classes
- **WordPress Coding Standards** for all PHP code
- **ESLint** configuration for JavaScript
- **PHPUnit** for automated testing
- **JSDoc** for JavaScript documentation

### Pull Request Process
1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request with detailed description

---

## 📚 Documentation

### Developer Resources
- **[API Reference](docs/api-reference.md)**: Complete API documentation
- **[Database Schema](docs/database-schema.md)**: Table structures and relationships
- **[Hooks & Filters](docs/hooks-filters.md)**: WordPress customization points
- **[Deployment Guide](docs/deployment.md)**: Production setup instructions

### User Manuals
- **[Artist Guide](docs/user/artist-guide.md)**: Getting started as an artist
- **[Admin Manual](docs/user/admin-manual.md)**: Site administrator instructions
- **[Troubleshooting](docs/user/troubleshooting.md)**: Common issues and solutions

---

## 🐛 Support & Issues

### Bug Reports
Please report bugs via [GitHub Issues](https://github.com/vortexartec/vortex-ai-marketplace/issues) with:
- **WordPress version** and environment details
- **Steps to reproduce** the issue  
- **Expected vs actual behavior**
- **Screenshots** or error logs if applicable

### Feature Requests
Submit feature requests through GitHub Issues with:
- **Use case description** and business justification
- **Proposed implementation** approach
- **Impact assessment** on existing functionality

### Community Support
- **Discord Server**: [Join our community](https://discord.gg/vortexartec)
- **Documentation**: Comprehensive guides and tutorials
- **Email Support**: info@vortexartec.com for technical issues

---

## 🔒 Security

### Reporting Vulnerabilities
Report security issues privately to: **info@vortexartec.com**

Please include:
- **Detailed description** of the vulnerability
- **Steps to reproduce** the issue
- **Potential impact** assessment
- **Suggested mitigation** if available

### Security Measures
- **Nonce verification** for all AJAX requests
- **Input sanitization** and validation
- **SQL injection prevention** via prepared statements  
- **XSS protection** through proper output escaping
- **File upload security** with type and size validation
- **User permission checks** for all operations

---

## 📄 License

This project is licensed under a **Proprietary License**.

```
VORTEX AI Marketplace Plugin
Copyright (C) 2024 Mariana Villard. All rights reserved.

This software is proprietary and confidential. Unauthorized copying,
modification, distribution, or reverse engineering is strictly prohibited.
All algorithms, AI integrations, and automation systems are trade secrets
and intellectual property of Mariana Villard.

See LICENSE file for complete terms and conditions.
```

---

## 👏 Acknowledgments

- **WordPress Community**: For the amazing ecosystem and standards
- **WooCommerce Team**: For robust e-commerce integration capabilities
- **AI Research Community**: For advancing generative art technologies  
- **Blockchain Developers**: For decentralized infrastructure innovations
- **Beta Testers**: For invaluable feedback and bug reports

---

## 📞 Contact

**VORTEX ARTEC**  
1000 Fifth Street, Suite 200T4  
Miami Beach, Florida 33139 USA

- **Website**: [https://vortexartec.com](https://vortexartec.com)
- **Email**: info@vortexartec.com
- **Phone**: +1.786.696.8031  
- **GitHub**: [@vortexartec](https://github.com/vortexartec)

---

*© 2024 Mariana Villard. All rights reserved. "VORTEX," "TOLA," "HURAII," and associated logos are trademarks of Mariana Villard.* 