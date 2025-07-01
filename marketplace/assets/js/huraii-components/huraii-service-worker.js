/**
 * HURAII Service Worker
 * Handles caching for image generation results and optimization
 */

const CACHE_NAME = 'vortex-huraii-cache-v1';
const RUNTIME_CACHE = 'vortex-huraii-runtime';

// Resources to precache
const PRECACHE_RESOURCES = [
  '/assets/js/vortex-huraii.js',
  '/assets/js/huraii-components/huraii-core.js',
  '/assets/js/huraii-components/huraii-ui.js',
  '/assets/js/huraii-components/huraii-api.js',
  '/assets/js/huraii-components/huraii-learning.js',
  '/assets/css/vortex-huraii.css',
  '/assets/images/huraii-logo.png',
  '/assets/images/placeholder.jpg'
];

// Install event - precache static resources
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(PRECACHE_RESOURCES))
      .then(() => self.skipWaiting())
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  const currentCaches = [CACHE_NAME, RUNTIME_CACHE];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return cacheNames.filter(cacheName => !currentCaches.includes(cacheName));
    }).then(cachesToDelete => {
      return Promise.all(cachesToDelete.map(cacheToDelete => {
        return caches.delete(cacheToDelete);
      }));
    }).then(() => self.clients.claim())
  );
});

// Fetch event - network-first strategy for API, cache-first for static assets
self.addEventListener('fetch', event => {
  // Skip non-GET requests
  if (event.request.method !== 'GET') return;
  
  // Skip cross-origin requests
  if (!event.request.url.startsWith(self.location.origin)) return;
  
  // Special handling for image generation API
  if (event.request.url.includes('vortex_huraii_generate')) {
    return event.respondWith(networkFirstStrategy(event.request));
  }
  
  // Handle generated image files
  if (event.request.url.includes('/uploads/vortex-huraii/')) {
    return event.respondWith(cacheFirstStrategy(event.request));
  }
  
  // Default strategy for other resources
  event.respondWith(cacheFirstStrategy(event.request));
});

// Cache-first strategy
async function cacheFirstStrategy(request) {
  const cachedResponse = await caches.match(request);
  
  if (cachedResponse) {
    return cachedResponse;
  }
  
  try {
    const networkResponse = await fetch(request);
    
    // Don't cache non-successful responses
    if (!networkResponse || networkResponse.status !== 200) {
      return networkResponse;
    }
    
    // Clone the response before caching
    const responseToCache = networkResponse.clone();
    
    // Cache the fetched response
    caches.open(RUNTIME_CACHE).then(cache => {
      cache.put(request, responseToCache);
    });
    
    return networkResponse;
  } catch (error) {
    // Fallback for network failure - return default placeholder for images
    if (request.destination === 'image') {
      return caches.match('/assets/images/placeholder.jpg');
    }
    
    throw error;
  }
}

// Network-first strategy
async function networkFirstStrategy(request) {
  try {
    const networkResponse = await fetch(request);
    
    // Don't cache non-successful responses
    if (!networkResponse || networkResponse.status !== 200) {
      return networkResponse;
    }
    
    // Clone the response before caching
    const responseToCache = networkResponse.clone();
    
    // Cache the fetched response
    caches.open(RUNTIME_CACHE).then(cache => {
      cache.put(request, responseToCache);
    });
    
    return networkResponse;
  } catch (error) {
    // Try to get from cache if network fails
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    throw error;
  }
}

// Handle message events from the application
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'CLEAR_IMAGES_CACHE') {
    event.waitUntil(
      caches.open(RUNTIME_CACHE).then(cache => {
        return cache.keys().then(requests => {
          return Promise.all(
            requests
              .filter(request => request.url.includes('/uploads/vortex-huraii/'))
              .map(request => cache.delete(request))
          );
        });
      })
    );
  }
}); 