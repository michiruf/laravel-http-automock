# Changelog

All notable changes to `laravel-http-automock` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [0.2-rc7] - 2026-03-04

### Added
- Independent dispatcher for automock (fixes `Event::fake()` disabling automock)
- AI-generated documentation

### Changed
- Enhanced default config

## [0.2-rc6] - 2026-03-04

### Added
- Check whether automock is generally enabled

### Fixed
- Format prevented request exception with newlines for readability

### Changed
- Dependency bumps

## [0.2-rc5] - 2025-05-05

### Added
- Shared mock directory support
- Directory naming prettification
- CI/CD pipeline with GitHub Actions (test matrix, Larastan, Pint)

## [0.2-rc4] - 2025-03-10

### Fixed
- Preventing real requests when mock file exists and renew is specified
- Request URL resolver type error

### Changed
- `preventAutoRenew` and `filters` now configurable via command args

## [0.2-rc3] - 2025-02-25

### Added
- Convenience mixin for query parameters

### Fixed
- Wrong return value in response validation
- Directory not found when pruning without existing directory

### Changed
- Construct request like stub handler to include request data

## [0.2-rc2] - 2025-02-24

### Added
- Mock validation option
- Exception for prevented requests
- Allow HTTP fakes when preventing requests

### Changed
- Disabling auto renew also disables pruning

## [0.2-rc1] - 2025-02-22

### Added
- Stack file name resolver
- Callable file name resolver
- Separate file name resolution service
- Option to configure mocking HTTP fakes
- Option to prevent automatic renewing
- Prune mocks functionality
- Test command args support (Pest plugin for automock args)

### Changed
- Separated options from automock
- Enhanced filename resolvers (request body, header, request resolver)

## [0.1] - 2025-02-09

### Added
- Core automock functionality for Laravel HTTP client
- File-based mock storage with configurable directory
- Pretty print JSON option
- Configurable URL filters
- PSR message serialization (request/response with headers)
- Filename resolution system with URL, count, and hash resolvers
- Fluent API for configuration
- Pest test support
