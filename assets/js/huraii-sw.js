/**
 * HURAII Service Worker
 * Manages caching and offline functionality for HURAII image generation
 * 
 * Note: This is the main service worker that gets registered in the browser
 * It delegates most of its functionality to the huraii-service-worker.js component
 */

// Service worker version - change this to trigger update
const SW_VERSION = '1.0.0';
const CACHE_NAME = 'huraii-cache-v1';

// Import the actual service worker component from the huraii-components folder
importScripts('./huraii-components/huraii-service-worker.js');

// The actual implementation is in huraii-service-worker.js to maintain component modularity
// This file serves as a wrapper for registration and bootstrapping

self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'GET_VERSION') {
    event.ports[0].postMessage({
      version: SW_VERSION
    });
  }
}); 