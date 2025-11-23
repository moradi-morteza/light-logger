# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial project structure
- PHP Swoole-based Logger Server with HTTP/2 and WebSocket support
- Health check endpoint for Kubernetes/load balancer integration
- Coroutine-enabled non-blocking I/O
- Basic WebSocket client connection handling
- Environment configuration support
- Console logging with ANSI colors

### Planned
- Log querying API endpoints
- Vue.js Logger Panel dashboard
- Authentication and authorization
- Redis pub/sub for command distribution
- Client SDKs (PHP, Node.js, Python)
- Docker Compose configuration
- Kubernetes Helm charts

## [0.1.0] - 2024-XX-XX

### Added
- Initial release (coming soon)

---

## Version History

| Version | Date | Description |
|---------|------|-------------|
| 0.1.0 | TBD | Initial release |

## How to Update This File

When making changes, add an entry under `[Unreleased]` in the appropriate section:

- **Added** - New features
- **Changed** - Changes in existing functionality
- **Deprecated** - Soon-to-be removed features
- **Removed** - Removed features
- **Fixed** - Bug fixes
- **Security** - Vulnerability fixes

When releasing a new version:
1. Replace `[Unreleased]` with the version number and date
2. Add a new `[Unreleased]` section at the top
3. Update the Version History table
