# Change: Migrate from Laravel to Symfony Framework

## Why

This project currently depends on Laravel framework for its core integration patterns, including service provider registration, routing, middleware, validation, Eloquent ORM, and JSON resources. To support Symfony-based applications and adopt Symfony's architectural conventions and coding styles, we need to replace all Laravel-specific integrations with their Symfony equivalents.

The migration will enable the library to generate OpenAPI documentation for Symfony applications using native Symfony patterns (services, routing, events, validation constraints, Doctrine ORM, serialization).

## What Changes

- **BREAKING**: Replace Laravel service provider with Symfony bundle
- **BREAKING**: Replace Laravel routing integration with Symfony routing component
- **BREAKING**: Replace Laravel middleware with Symfony event listeners/subscribers
- **BREAKING**: Replace Laravel FormRequest validation with Symfony validation constraints
- **BREAKING**: Replace Eloquent model inference with Doctrine entity support
- **BREAKING**: Replace Laravel JsonResource with Symfony serializer integration
- **BREAKING**: Replace Blade views with Twig templates
- **BREAKING**: Replace Laravel helper functions (Arr, Str) with Symfony components
- **BREAKING**: Update package dependencies to use Symfony packages instead of illuminate/* packages
- **BREAKING**: Migrate Orchestra Testbench to Symfony's testing framework
- Update code style to follow Symfony conventions (PSR-4 autoloading, service configuration, event naming)
- Update configuration files to use Symfony config format (YAML/XML)
- Replace Laravel-specific exception handling with Symfony exception listeners
- Update documentation and examples to reflect Symfony usage patterns

## Impact

### Affected Specs
- `service-integration` - New capability for Symfony bundle registration and dependency injection
- `routing` - Modified to use Symfony Router instead of Laravel Route
- `middleware` - Modified to use Symfony event system instead of Laravel middleware
- `validation` - Modified to use Symfony Validator instead of Laravel validation
- `views` - Modified to use Twig instead of Blade
- `type-inference` - Modified to infer types from Doctrine entities, Symfony forms, and serializer metadata

### Affected Code
- `composer.json` - Replace Laravel dependencies with Symfony equivalents
- `src/ScrambleServiceProvider.php` - Convert to Symfony bundle class
- `config/scramble.php` - Convert to Symfony configuration format
- `routes/web.php` - Convert to Symfony routing format
- `src/Http/Middleware/*` - Convert to Symfony event listeners
- `resources/views/docs.blade.php` - Convert to Twig template
- `src/Support/InferExtensions/*Extension.php` - Update all Laravel-specific type inference extensions
- `src/Support/TypeToSchemaExtensions/*` - Replace Eloquent/JsonResource support with Doctrine/Serializer
- `src/Support/ExceptionToResponseExtensions/*` - Adapt to Symfony exception hierarchy
- `src/Reflection/ReflectionRoute.php` - Adapt to Symfony Route objects
- `src/Support/OperationExtensions/ErrorResponsesExtension.php` - Replace FormRequest with Symfony constraints
- `tests/*` - Convert from Orchestra Testbench to Symfony test framework
- All files using `Illuminate\*` imports - Replace with appropriate Symfony components

### Migration Path
This is a major breaking change. Users will need to:
1. Update their `composer.json` to require the new Symfony-compatible version
2. Replace Laravel service provider registration with Symfony bundle configuration
3. Update any custom extensions to use Symfony components instead of Laravel
4. Review generated documentation as schema generation patterns may differ

## Compatibility
- **Minimum Symfony version**: 6.4 or 7.0+
- **PHP version**: Keep current requirement (^8.1)
- **No backward compatibility**: This is a complete framework migration
