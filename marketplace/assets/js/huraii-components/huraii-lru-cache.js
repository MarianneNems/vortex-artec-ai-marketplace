/**
 * HURAII LRU Cache
 * A sophisticated Least Recently Used (LRU) cache implementation for HURAII
 * 
 * Features:
 * - Configurable max size and TTL
 * - Parameterized TTL based on item type
 * - Automatic garbage collection
 * - Optional persistent storage integration
 * - Cache statistics tracking
 */
(function(global, $) {
  'use strict';
  
  /**
   * LRU Cache Node (internal)
   * Used for the doubly linked list implementation
   */
  class LRUNode {
    constructor(key, value, ttl = null) {
      this.key = key;
      this.value = value;
      this.prev = null;
      this.next = null;
      this.timestamp = Date.now();
      this.ttl = ttl; // Time to live in milliseconds
      this.type = null; // Optional item type for type-specific TTL
    }
    
    /**
     * Check if the item has expired
     * @returns {boolean} True if expired
     */
    isExpired() {
      if (!this.ttl) return false;
      return (Date.now() - this.timestamp) > this.ttl;
    }
  }
  
  /**
   * HURAII LRU Cache
   */
  const LRUCache = {
    name: 'lruCache',
    core: null,
    
    // Default configuration
    config: {
      maxSize: 500,           // Maximum number of items to store
      defaultTTL: 5 * 60000,  // Default TTL: 5 minutes
      gcInterval: 60000,      // Garbage collection interval: 1 minute
      persistToDisk: false,   // Whether to persist cache to disk
      storageKey: 'huraii_cache', // Local storage key
      typeTTLMap: {          // TTL by item type in milliseconds
        models: 30 * 60000,   // Models: 30 minutes
        presets: 60 * 60000,  // Presets: 1 hour
        thumbnails: 24 * 3600000, // Thumbnails: 24 hours
        temporary: 60000      // Temporary data: 1 minute
      }
    },
    
    // Internal properties
    _head: null,       // Head of linked list (most recently used)
    _tail: null,       // Tail of linked list (least recently used)
    _size: 0,          // Current cache size
    _items: null,      // Map of key -> node
    _stats: null,      // Cache statistics
    _gcTimer: null,    // Garbage collection timer
    
    /**
     * Initialize the cache
     * @param {Object} core Core component
     * @param {Object} config Configuration
     * @returns {Object} This component
     */
    init: function(core, config = {}) {
      this.core = core;
      
      // Merge configs
      this.config = {...this.config, ...config};
      
      // Initialize data structures
      this._items = new Map();
      this._head = null;
      this._tail = null;
      this._size = 0;
      
      // Initialize statistics
      this._stats = {
        hits: 0,
        misses: 0,
        sets: 0,
        evictions: 0,
        expirations: 0,
        lastGcRun: null,
        itemsByType: {}
      };
      
      // Load from persistent storage if enabled
      if (this.config.persistToDisk) {
        this._loadFromStorage();
      }
      
      // Start garbage collection
      this._startGarbageCollection();
      
      // Register with core
      core.registerComponent(this.name, this);
      
      return this;
    },
    
    /**
     * Get an item from the cache
     * @param {string} key Cache key
     * @returns {*} Cached value or undefined if not found
     */
    get: function(key) {
      const node = this._items.get(key);
      
      // Check if item exists and is not expired
      if (node) {
        if (node.isExpired()) {
          // Remove expired item
          this.remove(key);
          this._stats.expirations++;
          this._stats.misses++;
          
          // Emit cache event
          this.core.emit('cache_expired', {
            key: key,
            timestamp: Date.now()
          });
          
          return undefined;
        }
        
        // Move to front (most recently used)
        this._moveToFront(node);
        
        // Update stats
        this._stats.hits++;
        
        // Update timestamp
        node.timestamp = Date.now();
        
        // Emit cache event
        this.core.emit('cache_hit', {
          key: key,
          type: node.type,
          timestamp: Date.now()
        });
        
        return node.value;
      }
      
      // Item not found
      this._stats.misses++;
      
      // Emit cache event
      this.core.emit('cache_miss', {
        key: key,
        timestamp: Date.now()
      });
      
      return undefined;
    },
    
    /**
     * Set an item in the cache
     * @param {string} key Cache key
     * @param {*} value Value to cache
     * @param {Object} options Cache options
     * @returns {Object} This cache instance
     */
    set: function(key, value, options = {}) {
      // Handle options
      const ttl = options.ttl || 
                 (options.type && this.config.typeTTLMap[options.type]) || 
                 this.config.defaultTTL;
      
      // Create new node
      const node = new LRUNode(key, value, ttl);
      node.type = options.type || null;
      
      // Check if key already exists
      if (this._items.has(key)) {
        // Remove existing node
        this.remove(key);
      } else if (this._size >= this.config.maxSize) {
        // Remove least recently used item if cache is full
        this._removeLRU();
      }
      
      // Add to cache
      this._items.set(key, node);
      this._addToFront(node);
      this._size++;
      
      // Update stats
      this._stats.sets++;
      if (node.type) {
        this._stats.itemsByType[node.type] = (this._stats.itemsByType[node.type] || 0) + 1;
      }
      
      // Emit cache event
      this.core.emit('cache_set', {
        key: key,
        type: node.type,
        timestamp: Date.now(),
        ttl: ttl
      });
      
      // Persist to storage if enabled
      if (this.config.persistToDisk && options.persist !== false) {
        this._saveToStorage();
      }
      
      return this;
    },
    
    /**
     * Remove an item from the cache
     * @param {string} key Cache key
     * @returns {boolean} True if item was removed
     */
    remove: function(key) {
      const node = this._items.get(key);
      
      if (!node) {
        return false;
      }
      
      // Remove from linked list
      this._removeNode(node);
      
      // Remove from map
      this._items.delete(key);
      this._size--;
      
      // Update stats
      if (node.type && this._stats.itemsByType[node.type]) {
        this._stats.itemsByType[node.type]--;
      }
      
      // Emit cache event
      this.core.emit('cache_remove', {
        key: key,
        type: node.type,
        timestamp: Date.now()
      });
      
      return true;
    },
    
    /**
     * Check if key exists in cache
     * @param {string} key Cache key
     * @returns {boolean} True if key exists and is not expired
     */
    has: function(key) {
      const node = this._items.get(key);
      
      if (!node) {
        return false;
      }
      
      if (node.isExpired()) {
        this.remove(key);
        return false;
      }
      
      return true;
    },
    
    /**
     * Clear all items from the cache
     */
    clear: function() {
      this._items.clear();
      this._head = null;
      this._tail = null;
      this._size = 0;
      
      // Reset type stats
      this._stats.itemsByType = {};
      
      // Emit cache event
      this.core.emit('cache_clear', {
        timestamp: Date.now()
      });
      
      // Clear persistent storage if enabled
      if (this.config.persistToDisk) {
        this._clearStorage();
      }
    },
    
    /**
     * Get cache statistics
     * @returns {Object} Cache statistics
     */
    getStats: function() {
      const hitRatio = (this._stats.hits + this._stats.misses) > 0 
        ? this._stats.hits / (this._stats.hits + this._stats.misses)
        : 0;
        
      return {
        ...this._stats,
        size: this._size,
        maxSize: this.config.maxSize,
        hitRatio: hitRatio,
        usageRatio: this._size / this.config.maxSize,
        timestamp: Date.now()
      };
    },
    
    /**
     * Run garbage collection
     * @returns {number} Number of items removed
     */
    runGarbageCollection: function() {
      let removed = 0;
      
      // Check all items for expiration
      for (const [key, node] of this._items.entries()) {
        if (node.isExpired()) {
          this.remove(key);
          removed++;
          this._stats.expirations++;
        }
      }
      
      // Update stats
      this._stats.lastGcRun = Date.now();
      
      // Emit cache event
      this.core.emit('cache_gc_run', {
        itemsRemoved: removed,
        timestamp: Date.now()
      });
      
      return removed;
    },
    
    /**
     * Move a node to the front of the list (most recently used)
     * @param {Object} node Node to move
     * @private
     */
    _moveToFront: function(node) {
      if (node === this._head) {
        return; // Already at front
      }
      
      // Remove from current position
      this._removeNode(node);
      
      // Add to front
      this._addToFront(node);
    },
    
    /**
     * Add a node to the front of the list
     * @param {Object} node Node to add
     * @private
     */
    _addToFront: function(node) {
      if (!this._head) {
        // Empty list
        this._head = node;
        this._tail = node;
        node.prev = null;
        node.next = null;
      } else {
        // Add to front
        node.next = this._head;
        node.prev = null;
        this._head.prev = node;
        this._head = node;
      }
    },
    
    /**
     * Remove a node from the linked list
     * @param {Object} node Node to remove
     * @private
     */
    _removeNode: function(node) {
      if (node.prev) {
        node.prev.next = node.next;
      } else {
        // Node is head
        this._head = node.next;
      }
      
      if (node.next) {
        node.next.prev = node.prev;
      } else {
        // Node is tail
        this._tail = node.prev;
      }
    },
    
    /**
     * Remove the least recently used item
     * @private
     */
    _removeLRU: function() {
      if (!this._tail) {
        return; // Empty cache
      }
      
      const key = this._tail.key;
      
      // Update stats
      this._stats.evictions++;
      if (this._tail.type && this._stats.itemsByType[this._tail.type]) {
        this._stats.itemsByType[this._tail.type]--;
      }
      
      // Emit cache event
      this.core.emit('cache_eviction', {
        key: key,
        type: this._tail.type,
        timestamp: Date.now()
      });
      
      // Remove from map
      this._items.delete(key);
      
      // Remove from linked list
      this._tail = this._tail.prev;
      if (this._tail) {
        this._tail.next = null;
      } else {
        // Cache is now empty
        this._head = null;
      }
      
      this._size--;
    },
    
    /**
     * Start garbage collection timer
     * @private
     */
    _startGarbageCollection: function() {
      // Clear existing timer if any
      if (this._gcTimer) {
        clearInterval(this._gcTimer);
      }
      
      // Start new timer
      this._gcTimer = setInterval(() => {
        this.runGarbageCollection();
      }, this.config.gcInterval);
    },
    
    /**
     * Save cache to local storage
     * @private
     */
    _saveToStorage: function() {
      if (!this.config.persistToDisk || !window.localStorage) {
        return;
      }
      
      try {
        // Only save persistent items
        const persistentItems = {};
        
        for (const [key, node] of this._items.entries()) {
          // Skip temporary items
          if (node.type === 'temporary') continue;
          
          persistentItems[key] = {
            value: node.value,
            timestamp: node.timestamp,
            ttl: node.ttl,
            type: node.type
          };
        }
        
        localStorage.setItem(this.config.storageKey, JSON.stringify(persistentItems));
      } catch (e) {
        console.error('HURAII Cache: Error saving to localStorage', e);
      }
    },
    
    /**
     * Load cache from local storage
     * @private
     */
    _loadFromStorage: function() {
      if (!this.config.persistToDisk || !window.localStorage) {
        return;
      }
      
      try {
        const data = localStorage.getItem(this.config.storageKey);
        
        if (!data) return;
        
        const items = JSON.parse(data);
        
        // Add items to cache
        for (const [key, item] of Object.entries(items)) {
          // Skip if already expired
          if (item.ttl && (Date.now() - item.timestamp) > item.ttl) {
            continue;
          }
          
          this.set(key, item.value, {
            ttl: item.ttl,
            type: item.type,
            persist: false // Avoid writing back immediately
          });
          
          // Restore original timestamp
          const node = this._items.get(key);
          if (node) {
            node.timestamp = item.timestamp;
          }
        }
      } catch (e) {
        console.error('HURAII Cache: Error loading from localStorage', e);
      }
    },
    
    /**
     * Clear cache from local storage
     * @private
     */
    _clearStorage: function() {
      if (!this.config.persistToDisk || !window.localStorage) {
        return;
      }
      
      try {
        localStorage.removeItem(this.config.storageKey);
      } catch (e) {
        console.error('HURAII Cache: Error clearing localStorage', e);
      }
    }
  };
  
  // Register with HURAII when loaded
  if (global.HURAII) {
    global.HURAII.registerComponent('lruCache', LRUCache);
  } else {
    // Wait for HURAII to be defined
    document.addEventListener('DOMContentLoaded', () => {
      if (global.HURAII) {
        global.HURAII.registerComponent('lruCache', LRUCache);
      } else {
        console.error('HURAII core module not found. LRU Cache module initialization failed.');
      }
    });
  }
  
})(window, jQuery); 