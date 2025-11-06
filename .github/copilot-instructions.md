# Copilot Instructions for Symfony Scramble

## Project Overview

Symfony Scramble is an **automatic OpenAPI 3.1.0 documentation generator** for Symfony applications. This is a port from Laravel Scramble, migrating from Laravel's architecture to Symfony while preserving core functionality.

**Key Philosophy:** Generate API docs by analyzing code—no manual PHPDoc annotations required. Docs stay current by inferring types, routes, and schemas directly from source.

## Architecture

### Three-Tier System

1. **Type Inference (`src/Infer/`)** - PHP-Parser-based static analysis that builds type definitions from source code
2. **Type-to-Schema Transformation (`src/Support/Generator/`)** - Converts inferred PHP types to OpenAPI schemas
3. **Route Analysis (`src/Generator.php`)** - Orchestrates analysis of Symfony routes and generates documentation

### Extension System (Critical)

Scramble is highly extensible via **tagged services**. Four extension points:

```php
// Auto-tagged via _instanceof in config/services.yaml
scramble.infer_extension           // Adds type inference for methods/properties
scramble.type_to_schema_extension  // Converts custom types to OpenAPI schemas
scramble.operation_extension       // Modifies OpenAPI operations
scramble.exception_to_response_extension // Maps exceptions to response docs
```

**Pattern:** Implement interface → Symfony auto-tags → Extension automatically registered.

Example: `DoctrineEntityExtension` infers types from Doctrine metadata by implementing `PropertyTypeExtension`.

### Key Services

- **`Infer`** - Entry point for type analysis. Call `analyzeClass($fqcn)` to get `ClassDefinition`
- **`Generator`** - Main orchestrator. Analyzes routes and produces `OpenApi` document
- **`TypeTransformer`** - Converts `Type` objects to OpenAPI `Schema` objects
- **`SymfonyRouteManager`** - Filters and adapts Symfony routes for documentation
- **`Scramble`** - Facade for configuration and extension registration

## Migration Status (Laravel → Symfony)

**In Progress:** ~70% complete. Active work in `feature/migrate-to-symfony` branch.

### Already Ported
✅ Bundle registration, DI container, routing  
✅ Doctrine ORM (replaces Eloquent)  
✅ Symfony security (replaces Laravel auth middleware)  
✅ Custom `Collection`, `Str`, `Arr` helpers (replacing Illuminate)

### Still Using Laravel Code
⚠️ **30+ files** still import `Illuminate\Support\*`  
⚠️ **20+ `app()` calls** need DI refactoring  
⚠️ Exception hierarchy partially Laravel-dependent

**When editing:** Prefer Symfony patterns. Use `Dedoc\Scramble\Support\Collection` not `Illuminate\Support\Collection`.

## Development Workflows

### Running Tests

```bash
# Full suite (~150 tests)
composer test

# Specific test
vendor/bin/phpunit tests/InferTypesTest.php

# With coverage (slow!)
composer test-coverage

# Watch mode (use with entr/watchexec)
find src tests -name '*.php' | entr -c vendor/bin/phpunit
```

**Performance Note:** `SymfonyTestCase` reuses kernel container across tests. Once initialized, tests run fast. First test in suite is slowest.

### Static Analysis

```bash
# PHPStan (level 6, with baseline)
composer analyse

# Fix code style
composer format
```

**PHPStan Baseline:** `phpstan-baseline.neon` contains known issues. New code should not add errors.

### Manual Testing

After changes, verify docs at:
- UI: `http://localhost:8000/docs/api`
- JSON: `http://localhost:8000/docs/api.json`

Use `config/packages/scramble.yaml` to configure which routes to document.

## Testing Patterns

### Test Structure

Two base classes:

1. **`SymfonyTestCase`** - Most tests. Boots Symfony kernel with Scramble bundle.
2. **`TestCase`** - Pure PHP tests, no framework (rare).

### Type Inference Tests

```php
// Standard pattern in tests/InferTypesTest.php
$definition = $this->infer->analyzeClass(MyClass::class);
$returnType = $definition->getMethodDefinition('myMethod')->type->getReturnType();

$this->assertEquals('array{id: int, name: string}', $returnType->toString());
```

### Snapshot Testing

Many tests use `spatie/phpunit-snapshot-assertions`:

```php
use Spatie\Snapshots\MatchesSnapshots;

$this->assertMatchesTextSnapshot($schema->toArray());
```

**On failure:** Review `tests/__snapshots__/*.txt` diffs. Update with `-d --update-snapshots` if intentional.

### Test Helpers

- **`AnalysisHelpers` trait** - Provides `analyzeClass()`, `analyzeMethod()` shortcuts
- **`TypeInferenceAssertions` trait** - Custom assertions like `assertTypeEquals()`

## Code Conventions

### Naming

- **Classes:** PascalCase, descriptive. E.g., `DoctrineEntityExtension`, `TypeTransformer`
- **Methods:** camelCase. Verb-led for actions (`transformType`, `getMethodDefinition`)
- **Properties:** camelCase. Prefer descriptive over short (`$methodDefinition` not `$def`)

### Type System

All types inherit from `Dedoc\Scramble\Support\Type\Type`. Key types:

- `ObjectType` - Represents classes, keyed arrays, associative shapes
- `GenericType` - Parameterized types like `Collection<User>`
- `UnionType` - Multiple possible types (`string|int`)
- `VoidType` - No return value

**Pattern:** Type inference returns `Type` objects. Schema transformation consumes them.

### Error Handling

**During Analysis:** Catch exceptions implementing `RouteAware` to attach route context:

```php
try {
    $this->analyzeRoute($route);
} catch (Throwable $e) {
    if ($e instanceof RouteAware) {
        $e->setRoute($route);
    }
    throw $e;
}
```

**In Production:** `Scramble::throwOnError()` vs `Scramble::ignoreErrors()` controls behavior.

### Dependency Injection

**Never use `app()` or facades.** Constructor injection only:

```php
public function __construct(
    private Infer $infer,
    private TypeTransformer $transformer
) {}
```

Auto-wiring configured in `config/services.yaml`. Public services: `Infer`, `Generator`, `GeneratorConfigCollection`.

## OpenSpec Workflow (Proposals & Changes)

**Scramble uses OpenSpec for change management.** See `openspec/AGENTS.md` for full details.

### When to Create a Proposal

**Create `openspec/changes/[id]/` when:**
- Adding features or capabilities
- Making breaking changes
- Changing architecture/patterns
- Significant performance/security work

**Skip for:**
- Bug fixes (restore intended behavior)
- Typos, formatting, comments
- Dependency updates (non-breaking)

### Proposal Process

1. **Check existing work:** `openspec list` and `openspec list --specs`
2. **Create structure:**
   ```bash
   mkdir -p openspec/changes/add-feature-x/specs/capability
   # Write proposal.md, tasks.md, spec deltas
   ```
3. **Validate:** `openspec validate add-feature-x --strict`
4. **Get approval** before implementing
5. **After deployment:** `openspec archive add-feature-x --yes`

**Spec Format:**
```markdown
## ADDED Requirements
### Requirement: Feature Name
System SHALL do something.

#### Scenario: Success case
- **WHEN** conditions
- **THEN** expected result
```

Use `## MODIFIED Requirements` for changes (paste full requirement), `## REMOVED Requirements` for deprecations.

## Key Files Reference

- **`src/Generator.php`** - Main orchestrator. Analyzes routes → operations → OpenAPI doc
- **`src/Infer.php`** - Entry point for type analysis
- **`src/ScrambleBundle.php`** - Symfony bundle registration
- **`config/services.yaml`** - DI configuration. All extensions registered here
- **`tests/SymfonyTestCase.php`** - Test base with performance optimizations
- **`openspec/AGENTS.md`** - OpenSpec workflow documentation (read before proposals)

## Common Patterns

### Adding an Infer Extension

1. Implement `MethodReturnTypeExtension`, `PropertyTypeExtension`, or `FunctionReturnTypeExtension`
2. Add to `config/services.yaml` with tag `scramble.infer_extension`
3. Test in `tests/Infer/` or `tests/InferExtensions/`

### Adding a Type-to-Schema Extension

1. Implement `TypeToSchemaExtension` interface
2. Auto-tagged via `_instanceof` in services.yaml
3. Test in `tests/TypeToSchemaTransformerTest.php`

### Debugging Type Inference

```php
// In test or source
dump($type->toString()); // Human-readable type representation
dump($type->nodes);      // AST nodes if available
```

Use `SimpleDebugTest.php` for quick experiments without full test setup.

## Anti-Patterns to Avoid

❌ **Don't use Laravel patterns:** No facades, `app()`, `config()`, `view()` helpers  
❌ **Don't modify specs without OpenSpec:** Create proposal first for significant changes  
❌ **Don't skip validation:** Run `openspec validate --strict` before proposals  
❌ **Don't add Illuminate imports:** Use `Dedoc\Scramble\Support\*` equivalents  
❌ **Don't bypass DI:** Always use constructor injection, not service locator pattern

## Getting Started Checklist

Before making changes:
- [ ] Read relevant specs in `openspec/specs/`
- [ ] Check `openspec list` for active changes
- [ ] Review `MIGRATION.md` if touching Laravel-adjacent code
- [ ] Run `composer test` to ensure baseline
- [ ] Consider whether change needs OpenSpec proposal

## Additional Resources

- **Documentation:** [scramble.dedoc.co](https://scramble.dedoc.co)
- **Migration Guide:** `MIGRATION.md` - Laravel to Symfony changes
- **OpenSpec Instructions:** `openspec/AGENTS.md` - Change proposal workflow
- **Work Session Summary:** `WORK_SESSION_SUMMARY.md` - Recent changes log
