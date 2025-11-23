# Contributing to Light Logger

First off, thank you for considering contributing to Light Logger! It's people like you that make Light Logger such a great tool.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [How Can I Contribute?](#how-can-i-contribute)
- [Development Setup](#development-setup)
- [Style Guidelines](#style-guidelines)
- [Commit Messages](#commit-messages)
- [Pull Request Process](#pull-request-process)

## Code of Conduct

This project and everyone participating in it is governed by our [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code.

## Getting Started

- Make sure you have a [GitHub account](https://github.com/signup)
- Fork the repository on GitHub
- Clone your fork locally
- Set up the development environment (see below)

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues to avoid duplicates. When you create a bug report, include as many details as possible:

- **Use a clear and descriptive title**
- **Describe the exact steps to reproduce the problem**
- **Provide specific examples** (code snippets, configuration files)
- **Describe the behavior you observed and what you expected**
- **Include logs and error messages**
- **Specify your environment** (PHP version, Swoole version, OS, etc.)

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion:

- **Use a clear and descriptive title**
- **Provide a detailed description of the proposed functionality**
- **Explain why this enhancement would be useful**
- **List any alternative solutions you've considered**

### Pull Requests

1. Fork the repo and create your branch from `main`
2. If you've added code that should be tested, add tests
3. Ensure the test suite passes
4. Make sure your code follows the style guidelines
5. Issue your pull request

## Development Setup

### Prerequisites

- PHP 8.3+
- Swoole extension 6.0+
- Composer
- Node.js 18+ (for panel development)

### Server Setup

```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/light-logger.git
cd light-logger

# Install server dependencies
cd server
composer install

# Copy environment file
cp .env.example .env

# Run tests
composer test

# Start development server
composer start
```

### Panel Setup

```bash
cd panel
npm install
npm run dev
```

## Style Guidelines

### PHP Code Style

We use [PHP-CS-Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer) for code formatting:

```bash
# Check code style
composer fix -- --dry-run

# Fix code style automatically
composer fix
```

Key conventions:
- PSR-12 coding standard
- Type declarations for all parameters and return types
- Meaningful variable and method names
- Document complex logic with comments

### JavaScript/Vue Code Style

- Use ESLint and Prettier
- Follow Vue.js style guide
- Use TypeScript where possible

### Documentation

- Use clear, concise language
- Include code examples where helpful
- Keep README and docs up to date

## Commit Messages

We follow the [Conventional Commits](https://www.conventionalcommits.org/) specification:

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

- `feat`: A new feature
- `fix`: A bug fix
- `docs`: Documentation only changes
- `style`: Code style changes (formatting, missing semicolons, etc.)
- `refactor`: Code change that neither fixes a bug nor adds a feature
- `perf`: Performance improvement
- `test`: Adding missing tests
- `chore`: Changes to the build process or auxiliary tools

### Examples

```
feat(server): add log aggregation endpoint

fix(websocket): handle client disconnection gracefully

docs(readme): update installation instructions
```

## Pull Request Process

1. **Update documentation** - If your changes require it, update the README or other docs

2. **Add tests** - New features should include tests; bug fixes should include a test that fails without the fix

3. **Follow the template** - Fill out the PR template completely

4. **One feature per PR** - Keep pull requests focused on a single feature or fix

5. **Rebase if needed** - Keep your branch up to date with main:
   ```bash
   git fetch upstream
   git rebase upstream/main
   ```

6. **Wait for review** - A maintainer will review your PR and may request changes

7. **Address feedback** - Make requested changes and push new commits

## Questions?

Feel free to open an issue with your question or reach out to the maintainers.

Thank you for contributing!
