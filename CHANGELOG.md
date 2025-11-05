# Changelog

All notable changes to `scramble` will be documented in this file.

## [2.0.0] - 2025-11-05

### Major Release: Symfony Port

This is a complete rewrite of Scramble to work with Symfony instead of Laravel, while maintaining the same powerful automatic API documentation generation capabilities.

### Added

- **Symfony Bundle Support**: New `ScrambleBundle` for Symfony integration
- **Symfony DI Integration**: Full dependency injection container support
- **Symfony Router Adapter**: `RouteAdapter` and `SymfonyRouteManager` for route handling
- **Symfony Event System**: Integration with Symfony EventDispatcher
- **Symfony Testing Infrastructure**: New `SymfonyTestCase` base class
- **Doctrine ORM Support**: Automatic entity documentation generation
- **Symfony Validation**: Support for Symfony Validator constraints
- **Custom Collection Classes**: Framework-agnostic helper classes
  - `Collection` class with 32 Laravel-compatible methods
  - `Str` class with 33 string manipulation methods
  - `Arr` class with 18 array helper methods
  - `Stringable` class with 25 chainable methods
- **Global Helper Functions**: `collect()`, `tap()`, `app()`, `config()`, `logger()`, `url()`, `class_basename()`
- **Symfony HTTP Exceptions**: Proper exception handling with Symfony's HTTP kernel exceptions
- **Optional Laravel Support**: Eloquent models and Laravel responses work when packages are installed

### Changed

- **BREAKING**: Replaced `ScrambleServiceProvider` with `ScrambleBundle`
- **BREAKING**: Configuration now uses YAML format (`config/packages/scramble.yaml`)
- **BREAKING**: Route system now uses Symfony routing instead of Laravel routing
- **BREAKING**: Eloquent models replaced with Doctrine entities as primary ORM
- **BREAKING**: Middleware detection replaced with Symfony security attribute detection
- **BREAKING**: Form Requests replaced with Symfony Validator constraints
- Updated all core dependencies to Symfony 6.4+ / 7.0+
- Type system completely refactored for better type inference
- Exception handling adapted to Symfony HTTP exceptions

### Improved

- **Type Safety**: All PHPStan errors resolved, full static analysis support
- **Modularity**: Better separation of concerns using Symfony DI
- **Extensibility**: Cleaner extension points via Symfony's compiler passes
- **Performance**: Optimized service loading and caching
- **Testing**: Comprehensive test infrastructure with Symfony KernelTestCase

### Migration

See [MIGRATION.md](MIGRATION.md) for detailed migration guide from Laravel version.

### Technical Details

- 52 commits transforming the codebase
- 624 files modified (+47,572 / -43,367 lines)
- Complete replacement of all Illuminate packages
- Maintained backward compatibility where possible
- Optional support for Laravel-specific features

### Removed

- Laravel-specific middleware support (use Symfony security)
- Laravel Passport/Sanctum integration (use Symfony Security)
- Orchestra Testbench (replaced with Symfony testing tools)
- Direct Laravel package dependencies

---
