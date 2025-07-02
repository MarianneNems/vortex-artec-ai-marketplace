/**
 * Jest setup file for VORTEX AI Marketplace tests
 */

import '@testing-library/jest-dom';

// Mock WordPress globals
global.wp = {
  ajax: {
    post: jest.fn(),
    send: jest.fn()
  },
  api: {
    models: {},
    collections: {}
  },
  data: {
    select: jest.fn(),
    dispatch: jest.fn()
  }
};

global.jQuery = jest.fn(() => ({
  ajax: jest.fn(),
  on: jest.fn(),
  off: jest.fn(),
  ready: jest.fn(),
  find: jest.fn(),
  val: jest.fn(),
  text: jest.fn(),
  html: jest.fn(),
  hide: jest.fn(),
  show: jest.fn(),
  fadeIn: jest.fn(),
  fadeOut: jest.fn(),
  addClass: jest.fn(),
  removeClass: jest.fn(),
  toggleClass: jest.fn(),
  attr: jest.fn(),
  data: jest.fn(),
  prop: jest.fn(),
  css: jest.fn(),
  each: jest.fn(),
  length: 0
}));

global.$ = global.jQuery;

// Mock VORTEX globals
global.vortexAjax = {
  ajaxUrl: '/wp-admin/admin-ajax.php',
  restUrl: '/wp-json/vortex/v1/',
  nonce: 'test-nonce',
  currentUserId: 1,
  isUserLoggedIn: true,
  woocommerceEnabled: true
};

global.VortexQuizOptimizer = {
  nonce: 'test-quiz-nonce'
};

global.vortexConfig = {
  apiUrl: 'https://test-api.vortexartec.com',
  version: '3.0.0',
  debug: true
};

// Mock console methods for cleaner test output
global.console = {
  ...console,
  log: jest.fn(),
  debug: jest.fn(),
  info: jest.fn(),
  warn: jest.fn(),
  error: jest.fn()
};

// Mock localStorage
const localStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn()
};
global.localStorage = localStorageMock;

// Mock sessionStorage
const sessionStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn()
};
global.sessionStorage = sessionStorageMock;

// Mock fetch API
global.fetch = jest.fn(() =>
  Promise.resolve({
    ok: true,
    json: () => Promise.resolve({}),
    text: () => Promise.resolve(''),
    status: 200,
    statusText: 'OK'
  })
);

// Mock Web3 and blockchain
global.Web3 = jest.fn(() => ({
  eth: {
    getAccounts: jest.fn(() => Promise.resolve([])),
    getBalance: jest.fn(() => Promise.resolve('0')),
    sendTransaction: jest.fn(() => Promise.resolve({ transactionHash: '0xtest' }))
  },
  utils: {
    toWei: jest.fn(),
    fromWei: jest.fn(),
    isAddress: jest.fn(() => true)
  }
}));

global.ethereum = {
  request: jest.fn(),
  isMetaMask: true,
  selectedAddress: '0x1234567890123456789012345678901234567890'
};

// Mock Chart.js
global.Chart = jest.fn(() => ({
  update: jest.fn(),
  destroy: jest.fn(),
  resize: jest.fn()
}));

// Mock IntersectionObserver
global.IntersectionObserver = jest.fn(() => ({
  observe: jest.fn(),
  unobserve: jest.fn(),
  disconnect: jest.fn()
}));

// Mock ResizeObserver
global.ResizeObserver = jest.fn(() => ({
  observe: jest.fn(),
  unobserve: jest.fn(),
  disconnect: jest.fn()
}));

// Mock URL and URLSearchParams
global.URL = class URL {
  constructor(url) {
    this.href = url;
    this.origin = 'https://test.example.com';
    this.pathname = '/test';
    this.search = '';
    this.hash = '';
  }
};

global.URLSearchParams = class URLSearchParams {
  constructor(params = '') {
    this.params = new Map();
    if (params) {
      // Basic implementation for testing
      params.split('&').forEach(param => {
        const [key, value] = param.split('=');
        if (key) {
          this.params.set(decodeURIComponent(key), decodeURIComponent(value || ''));
        }
      });
    }
  }
  
  get(key) {
    return this.params.get(key);
  }
  
  set(key, value) {
    this.params.set(key, value);
  }
  
  has(key) {
    return this.params.has(key);
  }
  
  toString() {
    const params = [];
    this.params.forEach((value, key) => {
      params.push(`${encodeURIComponent(key)}=${encodeURIComponent(value)}`);
    });
    return params.join('&');
  }
};

// Setup and teardown helpers
beforeEach(() => {
  // Clear all mocks before each test
  jest.clearAllMocks();
  
  // Reset localStorage and sessionStorage
  localStorageMock.getItem.mockClear();
  localStorageMock.setItem.mockClear();
  localStorageMock.removeItem.mockClear();
  localStorageMock.clear.mockClear();
  
  sessionStorageMock.getItem.mockClear();
  sessionStorageMock.setItem.mockClear();
  sessionStorageMock.removeItem.mockClear();
  sessionStorageMock.clear.mockClear();
  
  // Reset fetch mock
  fetch.mockClear();
});

afterEach(() => {
  // Clean up any timers
  jest.clearAllTimers();
  jest.useRealTimers();
}); 