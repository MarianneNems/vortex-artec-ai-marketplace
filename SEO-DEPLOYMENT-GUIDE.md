# VORTEX ARTEC SEO System Deployment Guide

## Overview

This guide will help you deploy the complete VORTEX ARTEC SEO system, which includes:

1. **Metadata Store** (`metadata.json`) - Centralized SEO metadata for all pages
2. **Express API Server** (`server.js`) - Serves metadata and generates sitemaps
3. **WordPress SEO Plugin** - Injects dynamic SEO tags and integrates with VORTEX
4. **Automated sitemap generation** - Dynamic XML sitemaps from WordPress and metadata

## Prerequisites

- Node.js 16+ installed
- WordPress 5.0+ with VORTEX AI Marketplace plugin active
- Server access to deploy Express application
- Basic knowledge of WordPress plugin installation

## Step 1: Install Dependencies

```bash
# Install Node.js dependencies
npm install

# Or install specific packages if needed
npm install express cors axios
npm install --save-dev concurrently nodemon
```

## Step 2: Configure the Metadata API Server

1. **Review and customize `metadata.json`** with your specific pages:
   ```json
   {
     "/": {
       "title": "VORTEX ARTEC | Where Art Awakens AI - Revolutionary Art Marketplace",
       "description": "Discover VORTEX ARTEC—the immersive art platform powered by AI agents THORIUS, HURAII, and CLOE.",
       "ogImage": "/assets/images/VORTEX_ROUND_BLACK.png",
       "keywords": "AI art, blockchain art, NFT marketplace, TOLA token",
       "canonical": "https://www.vortexartec.com/"
     }
   }
   ```

2. **Update server configuration** in `server.js`:
   ```javascript
   // Update the WordPress API URL if different
   const wpResponse = await axios.get('https://www.vortexartec.com/wp-json/wp/v2/pages');
   
   // Update domain in sitemap generation
   xml += '    <loc>https://www.vortexartec.com' + path + '</loc>\n';
   ```

3. **Set environment variables**:
   ```bash
   # Create .env file
   PORT=3001
   WORDPRESS_API_URL=https://www.vortexartec.com/wp-json/wp/v2
   CORS_ORIGIN=https://www.vortexartec.com
   ```

## Step 3: Deploy the Express Server

### Option A: Local Development
```bash
# Start the server
npm run server

# Or with auto-restart
npm run dev
```

### Option B: Production Deployment

#### Using PM2 (Recommended)
```bash
# Install PM2 globally
npm install -g pm2

# Start the application
pm2 start server.js --name "vortex-seo-api"

# Save PM2 configuration
pm2 save
pm2 startup
```

#### Using Docker
```dockerfile
# Create Dockerfile
FROM node:18-alpine
WORKDIR /app
COPY package*.json ./
RUN npm ci --only=production
COPY . .
EXPOSE 3001
CMD ["node", "server.js"]
```

```bash
# Build and run
docker build -t vortex-seo-api .
docker run -d -p 3001:3001 --name vortex-seo vortex-seo-api
```

#### Using Nginx Reverse Proxy
```nginx
# /etc/nginx/sites-available/vortex-seo
server {
    listen 80;
    server_name api.vortexartec.com;
    
    location / {
        proxy_pass http://localhost:3001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }
}
```

## Step 4: Install WordPress SEO Plugin

1. **Upload plugin files** to your WordPress installation:
   ```
   wp-content/plugins/vortex-seo/
   ├── vortex-seo-plugin.php
   ├── includes/
   │   └── class-vortex-seo-manager.php
   └── README.md
   ```

2. **Activate the plugin** in WordPress admin:
   - Go to Plugins > Installed Plugins
   - Find "VORTEX SEO Manager"
   - Click "Activate"

3. **Configure plugin settings**:
   - Go to Settings > VORTEX SEO
   - Set "Metadata API URL" to your deployed server: `https://api.vortexartec.com/api/page-meta`
   - Set cache duration (default: 3600 seconds)
   - Test API connection

## Step 5: Update WordPress Theme

If you have a custom theme, ensure it supports the SEO system:

```php
// In your theme's functions.php
function theme_seo_support() {
    // Remove default title tag if VORTEX SEO is active
    if (class_exists('Vortex_SEO_Manager')) {
        remove_theme_support('title-tag');
    }
}
add_action('after_setup_theme', 'theme_seo_support');

// Optional: Add custom SEO hooks
function theme_custom_seo_data($meta, $path) {
    // Modify SEO data based on theme requirements
    if (is_home()) {
        $meta['title'] = get_bloginfo('name') . ' | ' . get_bloginfo('description');
    }
    return $meta;
}
add_filter('vortex_seo_meta', 'theme_custom_seo_data', 10, 2);
```

## Step 6: Configure DNS and SSL

1. **Set up subdomain** for API:
   ```
   api.vortexartec.com -> Your server IP
   ```

2. **Install SSL certificate**:
   ```bash
   # Using Let's Encrypt
   sudo certbot --nginx -d api.vortexartec.com
   ```

## Step 7: Testing and Validation

### Test API Endpoints
```bash
# Test metadata API
curl "https://api.vortexartec.com/api/page-meta?path=/"

# Test sitemap
curl "https://api.vortexartec.com/sitemap.xml"

# Test health check
curl "https://api.vortexartec.com/health"
```

### Test WordPress Integration
1. Visit your website and view page source
2. Check for proper SEO tags:
   ```html
   <title>VORTEX ARTEC | Where Art Awakens AI - Revolutionary Art Marketplace</title>
   <meta name="description" content="...">
   <meta property="og:title" content="...">
   ```

### SEO Validation Tools
- Google Search Console
- Facebook Sharing Debugger
- Twitter Card Validator
- Schema.org Validator

## Step 8: Performance Optimization

### Enable Caching
```javascript
// In server.js, add Redis caching
const redis = require('redis');
const client = redis.createClient();

app.get('/api/page-meta', async (req, res) => {
    const path = req.query.path || '/';
    const cacheKey = `seo:${path}`;
    
    try {
        const cached = await client.get(cacheKey);
        if (cached) {
            return res.json(JSON.parse(cached));
        }
        
        const metadata = getMetadata(path);
        await client.setex(cacheKey, 3600, JSON.stringify(metadata));
        res.json(metadata);
    } catch (error) {
        res.json(getMetadata(path));
    }
});
```

### CDN Configuration
```javascript
// Add cache headers
app.use((req, res, next) => {
    if (req.path.includes('/api/page-meta')) {
        res.set('Cache-Control', 'public, max-age=3600');
    }
    next();
});
```

## Step 9: Monitoring and Maintenance

### Health Monitoring
```bash
# Set up monitoring with PM2
pm2 install pm2-server-monit

# Or use external monitoring
curl -f https://api.vortexartec.com/health || exit 1
```

### Log Management
```javascript
// In server.js
const winston = require('winston');

const logger = winston.createLogger({
    level: 'info',
    format: winston.format.json(),
    transports: [
        new winston.transports.File({ filename: 'error.log', level: 'error' }),
        new winston.transports.File({ filename: 'combined.log' })
    ]
});
```

### Automated Updates
```bash
# Create update script
#!/bin/bash
cd /path/to/vortex-seo
git pull origin main
npm install
pm2 restart vortex-seo-api
```

## Troubleshooting

### Common Issues

1. **API Connection Failed**
   - Check server is running: `pm2 status`
   - Verify firewall allows port 3001
   - Check DNS resolution: `nslookup api.vortexartec.com`

2. **SEO Tags Not Appearing**
   - Verify plugin is activated
   - Check WordPress error log
   - Test API manually: `curl "https://api.vortexartec.com/api/page-meta?path=/"`

3. **Sitemap Issues**
   - Check WordPress REST API: `/wp-json/wp/v2/pages`
   - Verify metadata.json format
   - Test sitemap generation: `curl "https://api.vortexartec.com/sitemap.xml"`

### Debug Mode
```php
// Add to wp-config.php for debugging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Enable VORTEX SEO debug
define('VORTEX_SEO_DEBUG', true);
```

## Security Considerations

1. **API Rate Limiting**
   ```javascript
   const rateLimit = require('express-rate-limit');
   
   const limiter = rateLimit({
       windowMs: 15 * 60 * 1000, // 15 minutes
       max: 100 // limit each IP to 100 requests per windowMs
   });
   
   app.use('/api/', limiter);
   ```

2. **Input Validation**
   ```javascript
   app.get('/api/page-meta', (req, res) => {
       const path = req.query.path || '/';
       
       // Validate path
       if (!/^\/[\w\-\/]*$/.test(path)) {
           return res.status(400).json({ error: 'Invalid path' });
       }
       
       // Continue with processing...
   });
   ```

3. **CORS Configuration**
   ```javascript
   app.use(cors({
       origin: ['https://www.vortexartec.com', 'https://vortexartec.com'],
       credentials: true
   }));
   ```

## Backup and Recovery

### Database Backups
```bash
# Backup WordPress database
mysqldump -u username -p database_name > backup.sql

# Backup metadata
cp metadata.json metadata.json.backup
```

### Configuration Backups
```bash
# Backup server configuration
tar -czf vortex-seo-backup.tar.gz server.js metadata.json package.json
```

## Support and Maintenance

- **Documentation**: Keep this guide updated with any changes
- **Version Control**: Use Git tags for releases
- **Testing**: Set up automated testing for API endpoints
- **Monitoring**: Implement uptime monitoring and alerting

For additional support, contact the VORTEX ARTEC development team or refer to the main project documentation. 