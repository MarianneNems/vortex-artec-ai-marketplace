# Contributing to VORTEX AI Marketplace

We're excited that you're interested in contributing to the VORTEX AI Marketplace! This guide will help you get started with contributing to our WordPress plugin that bridges AI, art, and blockchain technology.

## 🚀 Getting Started

### Prerequisites

- PHP 7.4 or higher
- Node.js 18 or higher
- WordPress 5.0 or higher
- Composer
- Git

### Development Setup

1. **Fork the repository** on GitHub
2. **Clone your fork** locally:
   ```bash
   git clone https://github.com/YOUR-USERNAME/vortex-artec-ai-marketplace.git
   cd vortex-artec-ai-marketplace
   ```

3. **Install dependencies**:
   ```bash
   composer install
   npm install
   ```

4. **Set up your development environment**:
   ```bash
   cp .env.example .env
   # Configure your local settings
   ```

5. **Run the development build**:
   ```bash
   npm run dev
   ```

## 🛠️ Development Workflow

### Branch Naming

- `feature/feature-name` - New features
- `bugfix/bug-description` - Bug fixes
- `hotfix/critical-fix` - Critical fixes
- `docs/documentation-update` - Documentation updates

### Code Standards

#### PHP Code Standards
- Follow **WordPress Coding Standards**
- Use **PSR-4** autoloading
- Document all public methods with PHPDoc
- Use type hints where possible

#### JavaScript Standards
- Follow **ESLint** configuration
- Use modern ES6+ syntax
- Write JSDoc comments for functions
- Use meaningful variable names

#### CSS Standards
- Follow **BEM** methodology
- Use CSS custom properties
- Write mobile-first responsive styles
- Maintain consistent naming conventions

### Testing Requirements

#### PHP Tests
```bash
# Run all PHP tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/test-api-endpoints.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/
```

#### JavaScript Tests
```bash
# Run JavaScript tests
npm test

# Run E2E tests
npx cypress run
```

#### Code Quality
```bash
# PHP Code Standards
vendor/bin/phpcs --standard=WordPress

# JavaScript linting
npm run lint

# Fix auto-fixable issues
npm run lint:fix
```

## 📝 Contribution Process

### 1. Create an Issue

Before starting work, please create an issue to discuss:
- **Bug reports**: Include steps to reproduce, expected vs actual behavior
- **Feature requests**: Describe the use case and proposed solution
- **Questions**: Ask for clarification on existing functionality

### 2. Submit a Pull Request

1. **Create a new branch** from `develop`:
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes** following our coding standards

3. **Test your changes**:
   ```bash
   npm run test
   vendor/bin/phpunit
   ```

4. **Commit your changes**:
   ```bash
   git add .
   git commit -m "feat: add new AI generation endpoint"
   ```

5. **Push to your fork**:
   ```bash
   git push origin feature/your-feature-name
   ```

6. **Create a Pull Request** on GitHub

### Pull Request Guidelines

- **Clear title** describing the change
- **Detailed description** explaining what and why
- **Reference related issues** using `#issue-number`
- **Include screenshots** for UI changes
- **Update documentation** if needed
- **Add tests** for new functionality

### Commit Message Format

We use conventional commits:

```
type(scope): description

[optional body]

[optional footer]
```

Types:
- `feat`: New features
- `fix`: Bug fixes
- `docs`: Documentation changes
- `style`: Code formatting changes
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

Examples:
```
feat(api): add artwork generation endpoint
fix(blockchain): resolve wallet connection timeout
docs(readme): update installation instructions
```

## 🎯 Areas for Contribution

### High Priority
- 🐛 **Bug fixes** - Check our [Issues](https://github.com/MarianneNems/vortex-artec-ai-marketplace/issues)
- 📝 **Documentation** - Improve user and developer guides
- 🧪 **Test coverage** - Add unit and integration tests
- 🔒 **Security** - Identify and fix security vulnerabilities

### Feature Development
- 🤖 **AI integrations** - New AI art generation models
- 🔗 **Blockchain features** - Smart contract improvements
- 🎨 **UI/UX enhancements** - Better user experience
- 📊 **Analytics** - Advanced reporting and metrics

### Documentation
- 📚 **API documentation** - Comprehensive endpoint documentation
- 🎓 **Tutorials** - Step-by-step guides for users
- 🔧 **Developer guides** - Technical implementation guides
- 📖 **Code examples** - Practical usage examples

## 🌟 Recognition

Contributors are recognized in:
- **README.md** - Contributors section
- **Release notes** - Contribution highlights
- **Discord community** - Contributor role
- **Annual report** - Top contributor recognition

## 🤝 Community Guidelines

### Code of Conduct
- Be respectful and inclusive
- Welcome newcomers and help them learn
- Focus on constructive feedback
- Collaborate openly and transparently

### Getting Help
- **Discord**: Join our [developer community](https://discord.gg/vortexartec)
- **Issues**: Ask questions in GitHub issues
- **Email**: Contact maintainers at info@vortexartec.com

## 🔒 Security

### Reporting Security Issues
- **Email**: info@vortexartec.com
- **Do not** create public issues for security vulnerabilities
- **Include** detailed reproduction steps
- **Expect** acknowledgment within 48 hours

## 📄 License

By contributing to VORTEX AI Marketplace, you agree that your contributions will be licensed under the same license as the project.

## 🙏 Thank You

Every contribution, no matter how small, helps make VORTEX AI Marketplace better. Thank you for being part of our community!

---

*For more information, visit our [Documentation](https://www.vortexartec.com/docs) or reach out to our [Community](https://discord.gg/vortexartec).* 