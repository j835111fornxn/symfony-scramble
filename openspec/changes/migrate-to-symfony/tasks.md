# Implementation Tasks

## 1. Project Setup and Dependencies

- [x] 1.1 Update composer.json to require Symfony packages (^6.4|^7.0)
  - [x] Add symfony/dependency-injection
  - [x] Add symfony/config
  - [x] Add symfony/http-kernel
  - [x] Add symfony/http-foundation
  - [x] Add symfony/routing
  - [x] Add symfony/event-dispatcher
  - [x] Add symfony/validator
  - [x] Add symfony/serializer
  - [x] Add symfony/twig-bundle
  - [x] Add symfony/console
  - [x] Add doctrine/orm
  - [x] Add doctrine/doctrine-bundle
- [x] 1.2 Remove Laravel dependencies from composer.json
  - [x] Remove illuminate/contracts
  - [x] Remove illuminate/routing
  - [x] Remove illuminate/http
  - [x] Remove illuminate/database
  - [x] Remove illuminate/validation
  - [x] Remove illuminate/view
  - [x] Remove illuminate/support
  - [x] Remove spatie/laravel-package-tools
- [x] 1.3 Update dev dependencies for testing
  - [x] Remove orchestra/testbench
  - [x] Add symfony/test-pack or symfony/phpunit-bridge
  - [x] Add symfony/browser-kit for functional tests
- [x] 1.4 Update minimum PHP version constraints if needed
- [x] 1.5 Run composer update and resolve any conflicts

## 2. Bundle Creation

- [x] 2.1 Create `src/ScrambleBundle.php` extending Symfony\Component\HttpKernel\Bundle\Bundle
- [x] 2.2 Implement `build()` method to register compiler passes
- [x] 2.3 Implement `boot()` method for route registration
- [x] 2.4 Create `src/DependencyInjection/ScrambleExtension.php` for configuration
- [x] 2.5 Create `src/DependencyInjection/Configuration.php` for configuration tree
- [x] 2.6 Create `src/DependencyInjection/Compiler/ScrambleExtensionPass.php` for extension discovery
- [x] 2.7 Create `Resources/config/services.yaml` for service definitions
- [x] 2.8 Configure service autowiring and autoconfiguration
- [x] 2.9 Define service tags for extensions (scramble.infer_extension, scramble.type_to_schema_extension, etc.)

## 3. Configuration Migration

- [x] 3.1 Convert config/scramble.php to Resources/config/packages/scramble.yaml
- [x] 3.2 Update Configuration.php to define configuration tree
- [x] 3.3 Create configuration validators for complex settings
- [x] 3.4 Support both YAML and PHP configuration formats
- [x] 3.5 Update ScrambleExtension to process and normalize configuration
- [x] 3.6 Test configuration validation with invalid values

## 4. Routing Integration

- [x] 4.1 Update Scramble.php to work with Symfony Router instead of Route facade
- [x] 4.2 Create service to retrieve routes from RouterInterface::getRouteCollection()
- [x] 4.3 Implement route filtering based on api_path and api_domain configuration
- [x] 4.4 Update ReflectionRoute.php to work with Symfony\Component\Routing\Route
- [x] 4.5 Add support for route attributes (#[Route]) on controllers
- [x] 4.6 Implement controller method resolution from route defaults
- [x] 4.7 Add support for invokable controllers
- [x] 4.8 Extract route parameter requirements and map to OpenAPI constraints
- [x] 4.9 Handle optional route parameters with defaults
- [x] 4.10 Update tests for route parsing

## 5. Service Provider to Bundle Migration

- [x] 5.1 Remove ScrambleServiceProvider.php
- [x] 5.2 Move singleton registrations to services.yaml
- [x] 5.3 Move service bindings (when/needs/give) to service configuration
- [x] 5.4 Convert facade-based registrations (RouteFacade) to service injection
- [x] 5.5 Update all app() calls to use dependency injection
- [x] 5.6 Remove any remaining Laravel container bindings
- [x] 5.7 Update extension registration to use service tags
- [x] 5.8 Test service resolution and dependency injection

## 6. Middleware to Event System Migration

- [x] 6.1 Remove src/Http/Middleware/RestrictedDocsAccess.php
- [x] 6.2 Create src/EventSubscriber/DocumentationAccessSubscriber.php
- [x] 6.3 Implement onKernelRequest() to check access control
- [x] 6.4 Integrate with Symfony Security component for authorization
- [x] 6.5 Support role-based access control (ROLE_ADMIN, etc.)
- [x] 6.6 Support environment-based access control (dev only, etc.)
- [x] 6.7 Create event for pre-generation hook (scramble.generation.start)
- [x] 6.8 Create event for post-generation hook (scramble.generation.complete)
- [x] 6.9 Create event for operation generation (scramble.operation.generated)
- [x] 6.10 Document event system for extension developers

## 7. View Layer Migration

- [x] 7.1 Create templates/docs.html.twig from resources/views/docs.blade.php
- [x] 7.2 Convert Blade syntax to Twig syntax
  - [x] {{ $var }} → {{ var }}
  - [x] @if/@endif → {% if %}/{% endif %}
  - [x] @json() → |json_encode|raw filter
- [x] 7.3 Configure Twig loader with @Scramble namespace
- [x] 7.4 Update template variable passing
- [x] 7.5 Test template rendering with sample data
- [x] 7.6 Document template overriding process

## 8. Validation Integration

- [x] 8.1 Remove FormRequest-based validation inference
- [x] 8.2 Create DoctrineMetadataExtractor service for entity metadata
- [x] 8.3 Create ConstraintExtractor service for Symfony Validator constraints
- [x] 8.4 Implement constraint-to-schema converters for common constraints:
  - [x] NotBlank → required property
  - [x] Length → minLength/maxLength
  - [x] Range → minimum/maximum
  - [x] Email → format: email
  - [x] Regex → pattern
  - [x] Count → minItems/maxItems
  - [x] Choice → enum
- [ ] 8.5 Add support for validation groups
- [ ] 8.6 Add support for Form types as request body schemas
- [ ] 8.7 Implement nested form type handling
- [ ] 8.8 Update tests for validation inference

## 9. ORM Migration (Eloquent to Doctrine)

- [x] 9.1 Remove all Eloquent-specific extensions
- [x] 9.2 Create DoctrineEntityExtension for entity type inference
- [x] 9.3 Implement field type mapping (Doctrine types → OpenAPI types)
- [x] 9.4 Implement association handling (ManyToOne, OneToMany, ManyToMany)
- [x] 9.5 Extract field nullability from metadata
- [ ] 9.6 Handle custom Doctrine types
- [x] 9.7 Update ModelExtension to work with Doctrine entities (replaced by DoctrineEntityExtension)
- [x] 9.8 Remove EloquentBuilderExtension
- [ ] 9.9 Create DoctrineRepositoryExtension if needed
- [ ] 9.10 Update tests for Doctrine entity inference

## 10. Serialization Integration

- [ ] 10.1 Remove JsonResource-specific extensions
- [ ] 10.2 Create SymfonySerializerExtension for response inference
- [ ] 10.3 Implement serialization group support
- [ ] 10.4 Handle SerializedName attributes
- [ ] 10.5 Handle Ignore attributes
- [ ] 10.6 Support custom normalizers inference where possible
- [ ] 10.7 Update ResourceResponseTypeToSchema for Symfony responses
- [ ] 10.8 Update JsonResourceTypeToSchema or replace completely
- [ ] 10.9 Remove PaginatedResourceResponseTypeToSchema (Laravel-specific)
- [ ] 10.10 Test serialization-based schema generation

## 11. Exception Handling Migration

- [ ] 11.1 Update ValidationExceptionToResponseExtension for Symfony ValidationFailedException
- [ ] 11.2 Update AuthenticationExceptionToResponseExtension for Symfony AuthenticationException
- [ ] 11.3 Update AuthorizationExceptionToResponseExtension for Symfony AccessDeniedException
- [ ] 11.4 Update HttpExceptionToResponseExtension for Symfony HttpException
- [ ] 11.5 Update NotFoundExceptionToResponseExtension for Symfony NotFoundHttpException
- [ ] 11.6 Add exception event subscriber for error handling
- [ ] 11.7 Test exception to response conversions

## 12. Helper Function Replacement

- [ ] 12.1 Replace all uses of Illuminate\Support\Arr with native PHP array functions or Symfony ArrayUtil
- [ ] 12.2 Replace all uses of Illuminate\Support\Str with Symfony String component
- [ ] 12.3 Replace all uses of Illuminate\Support\Collection with Doctrine\Common\Collections or arrays
- [ ] 12.4 Remove app() calls and use dependency injection
- [ ] 12.5 Remove config() calls and inject configuration
- [ ] 12.6 Remove view() calls and inject Twig environment
- [ ] 12.7 Remove response() calls and return Symfony Response objects
- [ ] 12.8 Update all helper usages throughout the codebase

## 13. Infer Extensions Migration

- [ ] 13.1 Update ResponseMethodReturnTypeExtension for Symfony responses
- [ ] 13.2 Remove or adapt JsonResourceExtension
- [ ] 13.3 Remove ResourceResponseMethodReturnTypeExtension (Laravel-specific)
- [ ] 13.4 Update JsonResponseMethodReturnTypeExtension for Symfony JsonResponse
- [ ] 13.5 Replace ModelExtension with entity-based extension
- [ ] 13.6 Replace EloquentBuilderExtension with repository extension
- [ ] 13.7 Update RequestExtension for Symfony Request
- [ ] 13.8 Remove JsonResource-related definition extensions
- [ ] 13.9 Remove PaginateMethodsReturnTypeExtension (Laravel-specific)
- [ ] 13.10 Update ArrayMergeReturnTypeExtension if needed
- [ ] 13.11 Update abort helpers extension for Symfony throw patterns
- [ ] 13.12 Test all type inference extensions

## 14. Type to Schema Extensions Migration

- [ ] 14.1 Keep EnumToSchema (PHP enums work same way)
- [ ] 14.2 Remove or replace JsonResourceTypeToSchema
- [ ] 14.3 Replace ModelToSchema with entity-based schema generator
- [ ] 14.4 Update CollectionToSchema for Doctrine collections
- [ ] 14.5 Remove EloquentCollectionToSchema
- [ ] 14.6 Remove ResourceCollectionTypeToSchema (Laravel-specific)
- [ ] 14.7 Remove paginator-related schemas (Laravel-specific) or adapt for Symfony pagination
- [ ] 14.8 Update ResponseTypeToSchema for Symfony Response
- [ ] 14.9 Keep BinaryFileResponseToSchema (may work as-is)
- [ ] 14.10 Update StreamedResponseToSchema for Symfony StreamedResponse
- [ ] 14.11 Remove ResourceResponseTypeToSchema and PaginatedResourceResponseTypeToSchema
- [ ] 14.12 Keep VoidTypeToSchema
- [ ] 14.13 Test schema generation for all types

## 15. Console Commands Migration

- [ ] 15.1 Update AnalyzeDocumentation command to extend Symfony Command
- [ ] 15.2 Update ExportDocumentation command to extend Symfony Command
- [ ] 15.3 Remove Laravel-specific command setup
- [ ] 15.4 Register commands in services.yaml with command tag
- [ ] 15.5 Update command I/O to use Symfony Console Style
- [ ] 15.6 Test commands in Symfony console

## 16. Testing Framework Migration

- [ ] 16.1 Remove tests/TestCase.php extending Orchestra\Testbench
- [ ] 16.2 Create tests/ScrambleKernelTestCase.php extending Symfony KernelTestCase
- [ ] 16.3 Create test kernel that loads ScrambleBundle
- [ ] 16.4 Create test application configuration
- [ ] 16.5 Update all test classes to use new test case
- [ ] 16.6 Replace $this->app with static::getContainer()
- [ ] 16.7 Replace route registration patterns for Symfony
- [ ] 16.8 Update test fixtures (controllers, entities, etc.)
- [ ] 16.9 Update test assertions for Symfony patterns
- [ ] 16.10 Ensure all tests pass with Symfony

## 17. Route Registration

- [ ] 17.1 Remove routes/web.php
- [ ] 17.2 Create Resources/config/routes.yaml for documentation routes
- [ ] 17.3 Implement route registration in bundle boot() method
- [ ] 17.4 Support dynamic route registration based on configuration
- [ ] 17.5 Create controllers for UI and JSON spec endpoints
- [ ] 17.6 Test route registration and access

## 18. Documentation Updates

- [ ] 18.1 Update README.md with Symfony installation instructions
- [ ] 18.2 Create MIGRATION.md guide from Laravel to Symfony version
- [ ] 18.3 Document bundle configuration options
- [ ] 18.4 Document event system and extension points
- [ ] 18.5 Update all code examples to use Symfony patterns
- [ ] 18.6 Document Doctrine entity usage
- [ ] 18.7 Document Form type usage
- [ ] 18.8 Document Symfony Validator constraint support
- [ ] 18.9 Update extension development guide
- [ ] 18.10 Create troubleshooting section for common issues

## 19. Code Quality and Standards

- [ ] 19.1 Update .php-cs-fixer config for Symfony standards
- [ ] 19.2 Update PHPStan rules for Symfony
- [ ] 19.3 Run PHP CS Fixer and fix code style issues
- [ ] 19.4 Run PHPStan and fix type issues
- [ ] 19.5 Review and update PHPDoc comments
- [ ] 19.6 Ensure PSR-4 autoloading is correct

## 20. Integration Testing

- [ ] 20.1 Create sample Symfony application for integration testing
- [ ] 20.2 Test bundle installation and configuration
- [ ] 20.3 Test documentation generation for sample API
- [ ] 20.4 Test route attribute detection
- [ ] 20.5 Test Doctrine entity inference
- [ ] 20.6 Test Symfony Validator constraint inference
- [ ] 20.7 Test Form type inference
- [ ] 20.8 Test serialization group support
- [ ] 20.9 Test event system and customization
- [ ] 20.10 Test access control
- [ ] 20.11 Test error handling
- [ ] 20.12 Verify OpenAPI spec correctness

## 21. Release Preparation

- [ ] 21.1 Update CHANGELOG.md with breaking changes
- [ ] 21.2 Tag version 2.0.0 (major version for breaking changes)
- [ ] 21.3 Create GitHub release with migration notes
- [ ] 21.4 Update package keywords in composer.json
- [ ] 21.5 Update package description for Symfony
- [ ] 21.6 Consider creating a laravel-legacy branch for bug fixes
- [ ] 21.7 Announce migration on relevant channels
- [ ] 21.8 Monitor for issues and gather feedback
