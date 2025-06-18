const express = require('express');
const fs = require('fs');
const path = require('path');
const axios = require('axios');
const cors = require('cors');

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.static('public'));

// Load metadata
let metadata = {};
try {
  const metadataPath = path.join(__dirname, 'metadata.json');
  metadata = JSON.parse(fs.readFileSync(metadataPath, 'utf8'));
} catch (error) {
  console.error('Error loading metadata.json:', error);
  metadata = {
    "/": {
      "title": "VORTEX ARTEC | Where Art Awakens AI",
      "description": "Discover VORTEX ARTEC—the immersive art platform powered by AI, blockchain, and community.",
      "ogImage": "/assets/images/VORTEX_ROUND_BLACK.png"
    }
  };
}

// API endpoint for page metadata
app.get('/api/page-meta', (req, res) => {
  const path = req.query.path || '/';
  const pageMetadata = metadata[path] || metadata['/'];
  
  res.json({
    success: true,
    data: pageMetadata,
    path: path
  });
});

// Sitemap.xml endpoint
app.get('/sitemap.xml', async (req, res) => {
  try {
    // Get pages from WordPress REST API
    let pages = [];
    try {
      const wpResponse = await axios.get('https://www.vortexartec.com/wp-json/wp/v2/pages', {
        timeout: 5000
      });
      pages = wpResponse.data;
    } catch (wpError) {
      console.log('WordPress API not available, using static pages');
      // Fallback to static pages from metadata
      pages = Object.keys(metadata).map(path => ({
        slug: path === '/' ? '' : path.replace(/^\/|\/$/g, ''),
        modified: new Date().toISOString(),
        link: `https://www.vortexartec.com${path}`
      }));
    }

    // Build XML sitemap
    let xml = '<?xml version="1.0" encoding="UTF-8"?>\n';
    xml += '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n';
    
    // Add homepage
    xml += '  <url>\n';
    xml += '    <loc>https://www.vortexartec.com/</loc>\n';
    xml += '    <lastmod>' + new Date().toISOString().split('T')[0] + '</lastmod>\n';
    xml += '    <priority>1.0</priority>\n';
    xml += '  </url>\n';
    
    // Add other pages
    pages.forEach(page => {
      const url = page.link || `https://www.vortexartec.com/${page.slug}`;
      const lastmod = page.modified ? page.modified.split('T')[0] : new Date().toISOString().split('T')[0];
      
      xml += '  <url>\n';
      xml += '    <loc>' + url + '</loc>\n';
      xml += '    <lastmod>' + lastmod + '</lastmod>\n';
      xml += '    <priority>0.8</priority>\n';
      xml += '  </url>\n';
    });
    
    // Add metadata-defined pages that might not be in WordPress
    Object.keys(metadata).forEach(path => {
      if (path !== '/' && !pages.find(p => p.slug === path.replace(/^\/|\/$/g, ''))) {
        xml += '  <url>\n';
        xml += '    <loc>https://www.vortexartec.com' + path + '</loc>\n';
        xml += '    <lastmod>' + new Date().toISOString().split('T')[0] + '</lastmod>\n';
        xml += '    <priority>0.7</priority>\n';
        xml += '  </url>\n';
      }
    });
    
    xml += '</urlset>';
    
    res.header('Content-Type', 'application/xml');
    res.send(xml);
  } catch (error) {
    console.error('Error generating sitemap:', error);
    res.status(500).json({ error: 'Failed to generate sitemap' });
  }
});

// Robots.txt endpoint
app.get('/robots.txt', (req, res) => {
  const robots = `User-agent: *
Allow: /

# Sitemap
Sitemap: https://www.vortexartec.com/sitemap.xml

# Disallow admin areas
Disallow: /wp-admin/
Disallow: /wp-includes/
Disallow: /wp-content/plugins/
Disallow: /wp-content/themes/

# Allow specific assets
Allow: /wp-content/uploads/
Allow: /assets/
Allow: /css/
Allow: /js/`;

  res.header('Content-Type', 'text/plain');
  res.send(robots);
});

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({
    status: 'healthy',
    timestamp: new Date().toISOString(),
    metadata_pages: Object.keys(metadata).length
  });
});

// Metadata management endpoints (for dynamic updates)
app.post('/api/metadata/update', (req, res) => {
  const { path, meta } = req.body;
  
  if (!path || !meta) {
    return res.status(400).json({ error: 'Path and meta are required' });
  }
  
  metadata[path] = meta;
  
  // Save to file
  try {
    fs.writeFileSync(path.join(__dirname, 'metadata.json'), JSON.stringify(metadata, null, 2));
    res.json({ success: true, message: 'Metadata updated successfully' });
  } catch (error) {
    console.error('Error saving metadata:', error);
    res.status(500).json({ error: 'Failed to save metadata' });
  }
});

app.get('/api/metadata/all', (req, res) => {
  res.json({
    success: true,
    data: metadata
  });
});

// Start server
app.listen(PORT, () => {
  console.log(`VORTEX ARTEC SEO Server running on port ${PORT}`);
  console.log(`Metadata API: http://localhost:${PORT}/api/page-meta`);
  console.log(`Sitemap: http://localhost:${PORT}/sitemap.xml`);
  console.log(`Health check: http://localhost:${PORT}/health`);
});

module.exports = app; 