# Design Document: Laravel to Symfony Migration

## Context

Scramble is currently built as a Laravel package that automatically generates OpenAPI documentation by analyzing Laravel routes, controllers, FormRequests, Eloquent models, and JsonResources. The project heavily integrates with Laravel's service container, routing system, middleware pipeline, validation framework, and ORM.

The migration to Symfony requires replacing these integrations while maintaining the core functionality: automatic OpenAPI documentation generation through static analysis and type inference.

### Current Laravel Dependencies
- `illuminate/contracts` - Service container contracts, routing, foundation
- `illuminate/routing` - Route registration and resolution
- `illuminate/http` - Request/Response handling, JsonResource
- `illuminate/database` - Eloquent ORM
- `illuminate/validation` - FormRequest and validation rules
- `illuminate/view` - Blade templating
- `illuminate/support` - Helper classes (Arr, Str, Collection, Facades)
- `spatie/laravel-package-tools` - Package service provider tooling
- `orchestra/testbench` - Testing framework

### Symfony Equivalents
- Symfony DependencyInjection component - Service container
- Symfony Routing component - Route registration and matching
- Symfony HttpFoundation/HttpKernel - Request/Response handling
- Doctrine ORM - Entity mapping and database abstraction
- Symfony Validator component - Constraint validation
- Twig - Templating engine
- Symfony String/PropertyAccess - Utility components
- Symfony Bundle system - Package integration
- Symfony FrameworkBundle test utilities - Testing framework

## Goals / Non-Goals

### Goals
- Migrate all Laravel-specific code to Symfony equivalents
- Maintain existing OpenAPI generation capabilities
- Adopt Symfony best practices and conventions
- Support Symfony 6.4+ and 7.x
- Preserve extensibility through Symfony's event system
- Enable documentation generation for Symfony routes, controllers, entities, forms, and serializer metadata

### Non-Goals
- Maintaining backward compatibility with Laravel
- Supporting both frameworks simultaneously
- Changing the core OpenAPI generation algorithm
- Modifying the user-facing documentation UI (Stoplight Elements remains)
- Supporting Symfony versions below 6.4

## Decisions

### Decision 1: Bundle Architecture
**What**: Create a `ScrambleBundle` class extending Symfony's `Bundle` base class instead of Laravel's `PackageServiceProvider`.

**Why**: 
- Symfony bundles are the standard way to integrate third-party packages
- Bundles provide lifecycle hooks (build, boot) for registering services and configuration
- Follows Symfony ecosystem conventions

**Implementation**:
```php
class ScrambleBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        // Register compiler passes for extension discovery
        $container->addCompilerPass(new ScrambleExtensionPass());
    }
    
    public function boot(): void
    {
        // Register routes if configuration allows
    }
}
```

### Decision 2: Routing Integration
**What**: Replace Laravel Route facade with Symfony Router service.

**Why**:
- Symfony uses RouteCollection and Route objects instead of facade pattern
- Routes are typically defined in YAML/XML/attributes, not PHP files
- Need to support Symfony route attributes on controllers

**Implementation**:
- Parse routes from `RouterInterface::getRouteCollection()`
- Support PHP attributes (`#[Route('/api/users', methods: ['GET'])]`)
- Extract route metadata using Symfony's reflection utilities

### Decision 3: Middleware to Event System
**What**: Replace Laravel middleware classes with Symfony event subscribers.

**Why**:
- Symfony uses event-driven architecture instead of middleware pipeline
- Events like `kernel.request`, `kernel.response`, `kernel.exception` provide equivalent hooks
- More flexible and decoupled than middleware

**Implementation**:
- `RestrictedDocsAccess` middleware → `RequestEvent` subscriber checking gate/security
- Use Symfony Security component for access control

### Decision 4: Validation Strategy
**What**: Replace FormRequest analysis with Symfony validation constraint extraction.

**Why**:
- Symfony uses Constraint classes and annotations instead of rules arrays
- Validator metadata can be extracted from entities, DTOs, and forms
- Need to support both attribute-based and YAML/XML validator configurations

**Implementation**:
- Use `ValidatorInterface::getMetadataFor()` to extract constraints
- Convert Symfony constraints to OpenAPI parameter schemas
- Support custom constraint to schema converters

### Decision 5: ORM Integration
**What**: Replace Eloquent model inference with Doctrine entity metadata extraction.

**Why**:
- Doctrine is the standard Symfony ORM
- Entity metadata (field types, associations, nullability) available via `ClassMetadataFactory`
- Different patterns: entities vs models, repositories vs query builder

**Implementation**:
- Use Doctrine `EntityManagerInterface` to get metadata
- Infer types from `@ORM\Column`, `@ORM\ManyToOne`, etc. attributes
- Support Doctrine types (datetime, json, decimal, etc.)

### Decision 6: Serialization
**What**: Replace JsonResource with Symfony Serializer component integration.

**Why**:
- Symfony serializer handles normalization/denormalization
- Supports groups, context, custom normalizers
- More powerful than simple JsonResource toArray()

**Implementation**:
- Analyze `SerializerInterface` and normalizer metadata
- Support serialization groups in OpenAPI responses
- Infer response schemas from normalizer context

### Decision 7: View Layer
**What**: Convert Blade template to Twig.

**Why**:
- Twig is Symfony's default templating engine
- More secure (auto-escaping), more powerful (inheritance, filters)
- Better IDE support in Symfony projects

**Implementation**:
- Simple conversion: `{{ $var }}` → `{{ var }}`
- `@if` → `{% if %}`
- Keep same HTML/JS structure (Stoplight Elements)

### Decision 8: Configuration Format
**What**: Support YAML/XML configuration following Symfony conventions, alongside PHP config.

**Why**:
- Symfony projects typically use YAML or XML for configuration
- Bundle extension classes define configuration trees
- Provide better validation and IDE autocomplete

**Implementation**:
```yaml
# config/packages/scramble.yaml
scramble:
    api_path: /api
    api_domain: ~
    export_path: api.json
    info:
        version: '1.0.0'
        description: 'API Documentation'
    ui:
        theme: light
        layout: responsive
```

### Decision 9: Testing Framework
**What**: Replace Orchestra Testbench with Symfony's KernelTestCase and WebTestCase.

**Why**:
- Orchestra is Laravel-specific
- Symfony provides kernel booting and service access in tests
- WebTestCase for HTTP testing with test client

**Implementation**:
- Create test kernel that loads ScrambleBundle
- Use `static::bootKernel()` in test setup
- Use `static::getContainer()` to access services

### Decision 10: Dependency Injection Pattern
**What**: Use constructor injection and service configuration instead of Laravel's app() helper and facades.

**Why**:
- Symfony strongly favors explicit constructor injection
- Services defined in YAML/XML with autowiring
- No global helpers or facades in Symfony ecosystem

**Implementation**:
- Define all services in `Resources/config/services.yaml`
- Enable autowiring and autoconfiguration
- Tag extension services for auto-discovery

## Alternatives Considered

### Alternative 1: Multi-Framework Support
**Considered**: Supporting both Laravel and Symfony through adapter pattern.

**Rejected**: 
- Doubles maintenance burden
- Complex abstraction layer needed
- Framework-specific idioms don't translate well
- User confusion about which features work where

### Alternative 2: Framework-Agnostic Core
**Considered**: Extract framework-agnostic OpenAPI generation core, with separate Laravel/Symfony adapters.

**Rejected**:
- Requires significant refactoring of existing codebase
- Type inference is deeply tied to framework patterns
- Overkill for a project focused on Symfony now
- Can be considered in future if supporting more frameworks

### Alternative 3: Keep Laravel Helpers
**Considered**: Continue using `Illuminate\Support\Arr` and `Illuminate\Support\Str` utilities.

**Rejected**:
- Adds unnecessary Laravel dependencies
- Symfony has equivalent utilities (String component, array functions)
- Goes against goal of full Symfony adoption

## Risks / Trade-offs

### Risk 1: Type Inference Accuracy
**Risk**: Symfony's looser typing (vs Laravel's conventions) may reduce inference quality.

**Mitigation**: 
- Leverage PHP 8.1+ typed properties and return types
- Use Symfony's metadata systems (Validator, Serializer, Doctrine)
- Provide clear extension points for custom type hints

### Risk 2: Breaking Change Impact
**Risk**: Existing users on Laravel cannot upgrade without major refactoring.

**Mitigation**:
- Clearly document breaking changes and migration path
- Consider maintaining a Laravel-compatible branch for critical bugs
- Provide migration guide with code examples
- Communicate change well in advance

### Risk 3: Learning Curve
**Risk**: Contributors familiar with Laravel need to learn Symfony patterns.

**Mitigation**:
- Update CONTRIBUTING.md with Symfony conventions
- Provide examples of common patterns
- Link to Symfony best practices documentation

### Risk 4: Testing Coverage
**Risk**: Migration may introduce bugs due to behavior differences.

**Mitigation**:
- Ensure comprehensive test coverage before migration
- Create parallel tests during migration
- Test against real Symfony applications
- Beta period with early adopter feedback

### Risk 5: Doctrine vs Eloquent Differences
**Risk**: Doctrine's unit of work pattern differs significantly from Eloquent's active record.

**Mitigation**:
- Focus on metadata extraction, not ORM behavior
- Support both entities and plain DTOs
- Document limitations clearly

## Migration Plan

### Phase 1: Dependencies (Week 1)
- [ ] Update composer.json with Symfony packages
- [ ] Remove Laravel packages
- [ ] Resolve any conflicts
- [ ] Ensure PHP 8.1+ compatibility

### Phase 2: Core Services (Week 2-3)
- [ ] Create ScrambleBundle class
- [ ] Create bundle extension for configuration
- [ ] Set up service container configuration
- [ ] Migrate core services (Infer, Generator, TypeTransformer)

### Phase 3: Routing Integration (Week 3-4)
- [ ] Implement Symfony route collection parsing
- [ ] Support route attributes
- [ ] Extract controller metadata
- [ ] Update ReflectionRoute for Symfony

### Phase 4: Type Inference (Week 4-6)
- [ ] Doctrine entity metadata extraction
- [ ] Symfony validator constraint inference
- [ ] Symfony serializer integration
- [ ] Replace Eloquent/JsonResource extensions
- [ ] Update exception handling

### Phase 5: UI and Configuration (Week 6-7)
- [ ] Convert Blade to Twig
- [ ] Implement YAML/XML config support
- [ ] Create event subscribers for access control
- [ ] Update command classes to use Symfony Console

### Phase 6: Testing (Week 7-8)
- [ ] Migrate tests to Symfony test framework
- [ ] Create test kernel
- [ ] Ensure feature parity with Laravel version
- [ ] Add integration tests with real Symfony app

### Phase 7: Documentation (Week 9)
- [ ] Update README with Symfony installation
- [ ] Create migration guide from Laravel version
- [ ] Update all code examples
- [ ] Document new extension patterns

### Rollback Plan
If critical issues arise:
1. Maintain `v1.x` branch with Laravel support for 6 months
2. Tag new Symfony version as `v2.0.0`
3. Provide clear versioning in documentation
4. Accept critical bug fixes for v1.x during transition period

## Open Questions

1. **Q**: Should we support Symfony 5.4 LTS or only 6.4+?
   **A**: Pending - need to check feature availability in 5.4 vs 6.4

2. **Q**: How to handle API Platform integration (another popular Symfony API framework)?
   **A**: Pending - consider dedicated extension or built-in support

3. **Q**: Should configuration support all three formats (YAML, XML, PHP) or just YAML?
   **A**: Pending - likely YAML primary, PHP fallback

4. **Q**: How to handle Symfony UX/Turbo/HTMX patterns?
   **A**: Out of scope for initial migration - these are UI patterns, not API documentation

5. **Q**: Should we auto-register routes in dev environment or require explicit configuration?
   **A**: Pending - gather community feedback on preferred approach
