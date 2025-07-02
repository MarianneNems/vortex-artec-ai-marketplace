# VORTEX AI Marketplace - Build System Documentation

## 🚀 Quick Start

### Prerequisites
- **Node.js**: >=18.0.0
- **npm**: >=8.0.0
- **PHP**: >=8.1
- **Composer**: Latest version

### Installation

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

## 📦 Available Scripts

### Development
```bash
# Start development server with hot reload
npm run dev

# Build for development (unminified)
npm run build:dev

# Serve with webpack dev server
npm run serve
```

### Production
```bash
# Build for production (minified and optimized)
npm run build

# Clean build directories
npm run clean
```

### CSS/SCSS
```bash
# Compile SCSS to CSS (compressed)
npm run sass:build

# Watch SCSS files for changes
npm run sass:watch
```

### Code Quality
```bash
# Lint JavaScript files
npm run lint

# Lint and fix JavaScript files
npm run lint:fix

# Format code with Prettier
npm run format
```

### Testing
```bash
# Run all tests
npm test

# Run tests in watch mode
npm run test:watch

# Generate test coverage report
npm run test:coverage
```

### Analysis
```bash
# Analyze bundle size
npm run analyze
```

## 🏗️ Build System Architecture

### Entry Points
The build system is configured with multiple entry points for different parts of the plugin:

- **Frontend Scripts**:
  - `vortex-marketplace.js` - Main marketplace functionality
  - `vortex-tola.js` - TOLA blockchain integration
  - `quiz-optimizer.js` - Quiz optimization system
  - `quiz-enhanced.js` - Enhanced cosmic quiz features
  - `ai-terminal.js` - AI terminal interface
  - `huraii-components/index.js` - HURAII AI components

- **Admin Scripts**:
  - `vortex-admin.js` - Admin dashboard functionality
  - `thorius-admin.js` - THORIUS admin features

- **Blockchain Components**:
  - `blockchain/wallet-connect.js` - Wallet connection
  - `blockchain/tola-integration.js` - TOLA integration

### Output Structure
```
dist/
├── js/
│   ├── [name].[contenthash].js
│   └── [name].[contenthash].chunk.js
├── css/
│   ├── [name].[contenthash].css
│   └── [name].[contenthash].chunk.css
├── images/
│   └── [name].[hash][ext]
└── fonts/
    └── [name].[hash][ext]
```

## 🔧 Configuration Files

### Webpack (`webpack.config.js`)
- **Development**: Hot reload, source maps, unminified output
- **Production**: Minification, optimization, code splitting
- **Aliases**: `@` for public/js, `@admin` for admin/js, etc.

### Babel (`babel.config.js`)
- **Presets**: `@babel/preset-env` for ES6+ transpilation
- **Plugins**: Class properties, dynamic imports
- **Targets**: Modern browsers + IE11 support

### ESLint (`.eslintrc.js`)
- **WordPress Standards**: WordPress globals and conventions
- **VORTEX Globals**: Plugin-specific global variables
- **Security Rules**: No eval, no implied eval, etc.

### Prettier (`.prettierrc`)
- **Code Formatting**: Consistent style across all files
- **Integration**: Works with ESLint for code quality

### Jest Testing
- **Environment**: jsdom for DOM testing
- **Mocks**: WordPress, Web3, VORTEX globals
- **Coverage**: Comprehensive test coverage reporting

## 🎯 Development Workflow

### 1. Start Development
```bash
# Terminal 1: Start webpack dev server
npm run dev

# Terminal 2: Watch SCSS changes
npm run sass:watch

# Terminal 3: Run tests in watch mode (optional)
npm run test:watch
```

### 2. Code Quality Checks
```bash
# Before committing
npm run lint:fix
npm run format
npm test
```

### 3. Production Build
```bash
# Clean previous builds
npm run clean

# Build for production
npm run build

# Analyze bundle (optional)
npm run analyze
```

## 📁 Directory Structure

```
vortex-ai-marketplace/
├── public/
│   ├── js/                     # Frontend JavaScript
│   │   ├── components/         # Reusable components
│   │   ├── utils/             # Utility functions
│   │   ├── ai-terminal.js     # AI terminal
│   │   ├── quiz-enhanced.js   # Cosmic quiz
│   │   └── vortex-tola.js     # TOLA integration
│   ├── css/                   # Compiled CSS
│   └── scss/                  # SCSS source files
├── admin/
│   ├── js/                    # Admin JavaScript
│   └── css/                   # Admin CSS
├── tests/
│   └── js/                    # JavaScript tests
├── dist/                      # Build output
├── node_modules/              # Node dependencies
├── vendor/                    # PHP dependencies
├── webpack.config.js          # Webpack configuration
├── babel.config.js            # Babel configuration
├── .eslintrc.js              # ESLint configuration
├── .prettierrc               # Prettier configuration
└── package.json              # Node.js dependencies
```

## 🔄 Asset Loading

### Production Assets
In production, the plugin automatically loads minified assets from the `dist/` directory:

```php
wp_enqueue_script(
    'vortex-tola-prod',
    plugin_dir_url(__FILE__) . 'dist/js/vortex-tola.[hash].js',
    array('jquery', 'wp-api'),
    null,
    true
);
```

### Development Assets
In development, assets are served from the webpack dev server or source directories.

## 🧪 Testing

### Test Structure
```
tests/js/
├── setup.js                  # Jest setup and mocks
├── vortex-tola.test.js       # TOLA functionality tests
├── quiz-optimizer.test.js    # Quiz optimization tests
└── components/               # Component tests
```

### Mocked Globals
- **WordPress**: `wp`, `jQuery`, `$`, `ajaxurl`
- **VORTEX**: `vortexAjax`, `VortexQuizOptimizer`, `vortexConfig`
- **Web3**: `Web3`, `ethereum`, wallet functions
- **Chart.js**: `Chart` constructor and methods

## 🚀 Deployment

### Manual Build
```bash
# 1. Install dependencies
npm ci --production=false
composer install --no-dev --optimize-autoloader

# 2. Build assets
npm run build

# 3. Run tests
npm test

# 4. Package for deployment
# (Copy dist/ folder and exclude dev files)
```

### CI/CD Integration
The build system integrates with GitHub Actions:

```yaml
- name: Install Node dependencies
  run: npm ci

- name: Build assets
  run: npm run build

- name: Run JavaScript tests
  run: npm test

- name: Lint JavaScript
  run: npm run lint
```

## 🔍 Troubleshooting

### Common Issues

1. **Build Errors**
   ```bash
   # Clear cache and reinstall
   npm run clean
   rm -rf node_modules package-lock.json
   npm install
   ```

2. **SCSS Compilation Issues**
   ```bash
   # Check SCSS syntax
   npm run sass:build
   ```

3. **Test Failures**
   ```bash
   # Run specific test
   npm test -- --testNamePattern="TOLA"
   
   # Debug test
   npm test -- --verbose
   ```

4. **Linting Errors**
   ```bash
   # Auto-fix most issues
   npm run lint:fix
   
   # Check specific file
   npx eslint public/js/vortex-tola.js
   ```

## 📚 Additional Resources

- [Webpack Documentation](https://webpack.js.org/)
- [Babel Documentation](https://babeljs.io/)
- [Jest Testing Framework](https://jestjs.io/)
- [ESLint Rules](https://eslint.org/docs/rules/)
- [Prettier Configuration](https://prettier.io/docs/en/configuration.html)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Install dependencies: `npm install`
4. Make changes following the linting rules
5. Run tests: `npm test`
6. Build for production: `npm run build`
7. Submit a pull request

---

**Need help?** Check the [XAMPP-SETUP-GUIDE.md](XAMPP-SETUP-GUIDE.md) for development environment setup. 