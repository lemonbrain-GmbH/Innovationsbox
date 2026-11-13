# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-11-13

### Added

- Initial open-source release on GitHub
- Five DDD-aligned packages: `inno_frontend`, `inno_shared`, `inno_project_idea`, `inno_project_space`, `inno_moderation`, `inno_notification`
- Hexagonal architecture (Ports & Adapters) per domain package
- Event-driven inter-package communication via Symfony EventDispatcher
- Component library following Atomic Design (25 atoms, 26 molecules, 9 organisms, 2 templates)
- Storybook integration with PHP story server
- Design token system (`tokens.json` to SCSS)
- Full CI pipeline: lint, type-check, test (80% coverage), security scans, E2E
- Docker-based E2E test environment
- OpenAPI specification with Swagger UI
- User account deactivation (self-service and moderator-initiated)
- Notification badge system with real-time counts
