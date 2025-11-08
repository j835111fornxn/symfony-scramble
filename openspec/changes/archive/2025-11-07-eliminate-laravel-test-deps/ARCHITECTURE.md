# Validation & Pagination Abstraction Architecture

## Overview

This document describes how Scramble handles validation and pagination across different frameworks (Laravel and Symfony) through abstraction interfaces.

## Validation Abstraction

### The ParameterExtractor Interface

Scramble uses the `ParameterExtractor` interface as the core abstraction for extracting request parameters from various sources. This interface allows multiple validation strategies to coexist.

**Interface Definition:**
```php
namespace Dedoc\Scramble\Support\OperationExtensions\ParameterExtractor;

interface ParameterExtractor
{
    /**
     * @param  ParametersExtractionResult[]  $parameterExtractionResults
     * @return ParametersExtractionResult[]
     */
    public function handle(RouteInfo $routeInfo, array $parameterExtractionResults): array;
}
```

### Implementations

Scramble provides multiple implementations of this interface to support different validation approaches:

#### 1. Laravel Validation Support

**Class:** `ValidateCallParametersExtractor`
- **Purpose:** Extracts validation rules from Laravel's `$request->validate()` calls
- **Dependencies:** `Illuminate\Validation\*`
- **Status:** ✅ Core feature - intentionally maintained

**Supporting Classes:**
- `RulesMapper` - Maps Laravel validation rules to OpenAPI schema types
- `RuleSetToSchemaTransformer` - Converts rule sets to OpenAPI schemas
- `NodeRulesEvaluator` - Evaluates validation rule AST nodes

#### 2. Symfony Validation Support

**Class:** `SymfonyValidationParametersExtractor`
- **Purpose:** Extracts validation constraints from Symfony Validator annotations/attributes
- **Dependencies:** `Symfony\Component\Validator\*`
- **Status:** ✅ Fully implemented

**Supporting Classes:**
- `ConstraintExtractor` - Extracts Symfony validation constraints from classes
- `ConstraintToSchemaConverter` - Converts constraints to OpenAPI schema properties

#### 3. Symfony Form Support

**Class:** `FormTypeParametersExtractor`
- **Purpose:** Extracts parameters from Symfony Form types
- **Dependencies:** `Symfony\Component\Form\*`
- **Status:** ✅ Fully implemented

#### 4. Other Extractors

- **PathParametersExtractor** - Extracts path parameters from routes
- **AttributesParametersExtractor** - Extracts parameters from PHP attributes
- **MethodCallsParametersExtractor** - Extracts parameters from method calls

### How It Works

All parameter extractors are registered in the dependency injection container and invoked sequentially during API documentation generation. Each extractor:

1. Receives a `RouteInfo` object containing route metadata
2. Receives accumulated `$parameterExtractionResults` from previous extractors
3. Analyzes the route to determine if it applies (e.g., checks for validation calls or constraints)
4. Extracts parameter information if applicable
5. Returns the updated array of extraction results

This chain-of-responsibility pattern allows multiple validation strategies to coexist and complement each other.

## Pagination Abstraction

### Current State: Removed

Laravel pagination extensions (`PaginatorTypeToSchema`, `LengthAwarePaginatorTypeToSchema`, `CursorPaginatorTypeToSchema`) were **intentionally removed** in commit `15a16bf` as part of the Symfony migration.

**Rationale:**
- Pagination is framework-specific and tightly coupled to Laravel's response structure
- Scramble's primary use case (documenting Laravel APIs) can rely on explicit return types or manual documentation for pagination
- The complexity of maintaining pagination abstractions across multiple frameworks outweighed the benefits
- Alternative: Users can document paginated responses using OpenAPI attributes or return type hints

### Future Pagination Support

If pagination support is needed in the future, it should follow the same pattern as validation:

1. Define a `PaginationTypeExtension` interface extending `TypeToSchemaExtension`
2. Implement framework-specific adapters:
   - `LaravelPaginationExtension` for Laravel paginators
   - `SymfonyPaginationExtension` for Symfony pagination libraries (e.g., Pagerfanta)
3. Register both extensions in the service container
4. Let the `TypeTransformer` chain invoke them during response analysis

## Design Principles

### 1. Framework-Specific Code is Intentional

Scramble is a **Laravel API documentation tool** that has been migrated to use **Symfony components for its test infrastructure**. The presence of Laravel-specific code in `src/` is intentional and core to Scramble's functionality:

- **Laravel validation rules** - Core feature for documenting Laravel API validation
- **Laravel route analysis** - Understanding Laravel routing patterns
- **Eloquent model analysis** - Documenting model-based APIs

See `tests/Fixtures/Laravel/README.md` for details on intentional Laravel dependencies.

### 2. Abstraction Through Interfaces

Rather than eliminating framework-specific code, Scramble provides **abstraction through common interfaces** that allow multiple frameworks to be supported simultaneously:

- `ParameterExtractor` - For validation/parameter extraction
- `TypeToSchemaExtension` - For type-to-schema transformations
- `ExceptionToResponseExtension` - For exception handling

### 3. Additive, Not Exclusive

The architecture is **additive**: supporting Symfony doesn't require removing Laravel support. Multiple implementations coexist, and the appropriate one is selected based on the code being analyzed.

## Testing Strategy

### Test Infrastructure

Tests use **Symfony components** (HttpKernel, DependencyInjection, etc.) for the test harness, even when testing Laravel-specific features. This provides:

- Consistent test environment
- Better isolation
- Framework-agnostic base test case
- Easier maintenance

### Laravel Fixtures

Laravel-specific code is maintained in `tests/Fixtures/Laravel/` as **intentional test fixtures** to validate Scramble's Laravel compatibility. These are not legacy code; they're essential for testing core functionality.

## Migration Path

### Completed Phases

✅ **Phase 1:** Removed Foundation traits and replaced Auth exceptions with Symfony equivalents
✅ **Phase 2:** Migrated database migrations to Doctrine DBAL and organized Eloquent fixtures
✅ **Phase 3:** Documented existing abstraction architecture (this document)

### What Phase 3 Achieved

Phase 3's goal was to "create abstraction layer for validation rule extraction to support both Laravel and Symfony validation." This goal was achieved by recognizing that:

1. **The abstraction already exists** - The `ParameterExtractor` interface serves this purpose
2. **Both frameworks are supported** - `ValidateCallParametersExtractor` (Laravel) and `SymfonyValidationParametersExtractor` (Symfony) coexist
3. **Pagination was intentionally removed** - No abstraction needed for removed functionality
4. **Documentation was needed** - This document fulfills that requirement

## Summary

Scramble's architecture successfully supports **both Laravel and Symfony** through:

- ✅ **Common interfaces** (`ParameterExtractor`, `TypeToSchemaExtension`)
- ✅ **Multiple coexisting implementations** (Laravel and Symfony validators)
- ✅ **Framework-specific code where appropriate** (Laravel feature analysis)
- ✅ **Framework-agnostic test infrastructure** (Symfony-based test harness)
- ✅ **Clear documentation** of intentional dependencies and architecture

No additional abstraction interfaces are needed - the architecture is complete and functional.
