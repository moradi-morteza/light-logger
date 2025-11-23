# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 0.x.x   | :white_check_mark: |

## Reporting a Vulnerability

We take the security of Light Logger seriously. If you believe you have found a security vulnerability, please report it to us as described below.

### Please do NOT:

- Open a public GitHub issue for security vulnerabilities
- Disclose the vulnerability publicly before it has been addressed

### Please DO:

1. **Email us directly** at [moradiemails@gmail.com](mailto:moradiemails@gmail.com) with:
   - A description of the vulnerability
   - Steps to reproduce the issue
   - Potential impact of the vulnerability
   - Any possible mitigations you've identified

2. **Allow time for response** - We will acknowledge your email within 48 hours and provide a more detailed response within 7 days, indicating the next steps in handling your report.

3. **Work with us** - We may ask for additional information or guidance during our investigation.

## What to Expect

- **Acknowledgment**: Within 48 hours of your report
- **Initial assessment**: Within 7 days
- **Resolution timeline**: Depends on severity, typically 30-90 days
- **Credit**: We will credit reporters who follow responsible disclosure

## Scope

The following are in scope for security reports:

- Light Logger Server (PHP/Swoole)
- Light Logger Panel (Vue.js)
- Official Docker images
- Official client SDKs

## Out of Scope

- Third-party dependencies (report these to their maintainers)
- Social engineering attacks
- Physical attacks
- Issues in environments not matching our requirements

## Security Best Practices

When deploying Light Logger:

1. Always use HTTPS/WSS in production
2. Keep PHP, Swoole, and dependencies up to date
3. Use strong authentication for Elasticsearch and Redis
4. Configure proper firewall rules
5. Regularly rotate credentials
6. Monitor logs for suspicious activity

Thank you for helping keep Light Logger and its users safe!
