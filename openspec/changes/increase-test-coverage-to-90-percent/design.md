# Design: Increase Test Coverage to 90%

## Architecture Overview
This change focuses on systematically adding tests across the entire codebase to achieve 90% coverage while maintaining the existing test architecture based on PHPUnit and Symfony testing infrastructure.

## Testing Strategy

### 1. Test Organization
Tests will be organized to mirror the source structure:
```
src/
  ├── Attributes/         → tests/Attributes/
  ├── Configuration/      → tests/Configuration/
  ├── DependencyInjection/ → tests/DependencyInjection/
  ├── DocumentTransformers/ → tests/DocumentTransformers/
  ├── Event/              → tests/Event/
  ├── EventSubscriber/    → tests/EventSubscriber/ (exists)
  ├── Exceptions/         → tests/Exceptions/
  ├── Extensions/         → tests/Extensions/
  ├── Http/               → tests/Http/
  ├── Infer/              → tests/Infer/ (exists)
  ├── OpenApiVisitor/     → tests/OpenApiVisitor/
  ├── PhpDoc/             → tests/PhpDoc/ (exists)
  ├── Reflection/         → tests/Reflection/
  └── Support/            → tests/Support/ (exists)
```

### 2. Test Types and Coverage Target

#### Unit Tests (70% of total coverage)
**Target: Individual classes and methods**
- All attribute classes (simple, DTO-like)
- Configuration classes
- Event classes
- Exception classes
- Utility classes in Support/
- Individual transformers and extensions

**Characteristics:**
- Fast execution
- No external dependencies
- Use mocks/stubs for dependencies
- Test individual methods in isolation

#### Integration Tests (25% of total coverage)
**Target: Component interactions**
- Generator with DocumentTransformers
- DependencyInjection with service resolution
- Extension system with TypeToSchema and OperationExtension
- Route reflection with SymfonyRouteManager
- Complete OpenAPI generation pipeline

**Characteristics:**
- Use real Symfony container
- Boot test kernel
- Test multiple components together
- Verify end-to-end workflows

#### Edge Case Tests (5% of total coverage)
**Target: Error conditions and boundaries**
- Invalid configurations
- Missing dependencies
- Malformed input
- Exception scenarios
- Null/empty cases

### 3. Coverage Metrics

#### Primary Metric: Line Coverage (≥90%)
- Measures which lines of code are executed during tests
- Industry standard for coverage measurement
- Balanced between effort and value

#### Secondary Metrics (tracked but not enforced):
- Branch Coverage (target: ≥80%)
  - Measures coverage of conditional branches (if/else)
- Method Coverage (target: ≥95%)
  - Measures which methods are called

#### Exclusions:
- Simple getters/setters (may be excluded if trivial)
- Deprecated code (document why)
- Generated code (if any)
- Debug/development-only code

### 4. Testing Patterns

#### For Attributes (DTO Classes)
```php
final class EndpointAttributeTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_all_parameters(): void
    {
        $attribute = new Endpoint(
            summary: 'Test endpoint',
            description: 'Test description',
            tags: ['api', 'v1']
        );

        $this->assertSame('Test endpoint', $attribute->summary);
        $this->assertSame('Test description', $attribute->description);
        $this->assertSame(['api', 'v1'], $attribute->tags);
    }

    #[Test]
    public function it_uses_defaults_when_optional_parameters_are_omitted(): void
    {
        $attribute = new Endpoint(summary: 'Test');

        $this->assertNull($attribute->description);
        $this->assertSame([], $attribute->tags);
    }
}
```

#### For Service Classes
```php
final class ScrambleExtensionTest extends SymfonyTestCase
{
    #[Test]
    public function it_registers_core_services(): void
    {
        $container = static::getContainer();

        $this->assertTrue($container->has(Generator::class));
        $this->assertTrue($container->has(Infer::class));
        $this->assertTrue($container->has(TypeTransformer::class));
    }

    #[Test]
    public function it_processes_configuration_correctly(): void
    {
        // Use test configuration
        $config = [
            'api_path' => '/api/docs',
            'api_domain' => null,
        ];

        // Verify configuration is processed
        $generatorConfig = $container->get(GeneratorConfig::class);
        $this->assertSame('/api/docs', $generatorConfig->apiPath);
    }
}
```

#### For Transformers/Extensions
```php
final class AddDocumentTagsTest extends TestCase
{
    private AddDocumentTags $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new AddDocumentTags(['api', 'v1']);
    }

    #[Test]
    public function it_adds_tags_to_openapi_document(): void
    {
        $openApi = new OpenApi('3.1.0');

        $result = $this->transformer->transform($openApi);

        $this->assertCount(2, $result->tags);
        $this->assertSame('api', $result->tags[0]->name);
        $this->assertSame('v1', $result->tags[1]->name);
    }

    #[Test]
    public function it_does_not_duplicate_existing_tags(): void
    {
        $openApi = new OpenApi('3.1.0');
        $openApi->tags = [new Tag('api', 'Existing tag')];

        $result = $this->transformer->transform($openApi);

        $this->assertCount(2, $result->tags);
        // Verify 'api' is not duplicated
    }
}
```

#### For Integration Tests
```php
final class OpenApiGenerationIntegrationTest extends SymfonyTestCase
{
    #[Test]
    public function it_generates_openapi_documentation_for_symfony_controller(): void
    {
        $generator = static::getContainer()->get(Generator::class);

        // Use a real test controller
        $openApi = $generator->generate();

        $this->assertInstanceOf(OpenApi::class, $openApi);
        $this->assertSame('3.1.0', $openApi->openapi);
        $this->assertNotEmpty($openApi->paths);

        // Verify specific endpoints are documented
        $this->assertArrayHasKey('/api/test', $openApi->paths);
    }
}
```

### 5. Coverage Collection

#### PHPUnit Configuration
Update `phpunit.xml.dist`:
```xml
<coverage>
    <report>
        <html outputDirectory="build/coverage"/>
        <clover outputFile="build/coverage/clover.xml"/>
        <text outputFile="php://stdout" showUncoveredFiles="true"/>
    </report>
</coverage>
```

#### Coverage Driver
- Primary: **pcov** (faster, designed for coverage)
- Fallback: **xdebug** (if pcov not available)

Install pcov:
```bash
sudo apt-get install php8.3-pcov
# or
pecl install pcov
```

Enable coverage in PHPUnit:
```ini
; php.ini or phpunit.php
pcov.enabled = 1
pcov.directory = ./src
```

#### Running Coverage
```bash
# Generate HTML coverage report
composer test-coverage

# Generate coverage with specific format
php vendor/bin/phpunit --coverage-html build/coverage
php vendor/bin/phpunit --coverage-text
php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
```

### 6. CI/CD Integration

#### GitHub Actions (if applicable)
```yaml
- name: Run tests with coverage
  run: composer test-coverage

- name: Check coverage threshold
  run: |
    php vendor/bin/coverage-check build/coverage/clover.xml 90
```

#### Coverage Badge
Add to README.md:
```markdown
![Coverage](https://img.shields.io/badge/coverage-90%25-brightgreen)
```

### 7. Dealing with Hard-to-Test Code

#### Strategy 1: Refactor for Testability
- Extract dependencies to constructor parameters
- Use dependency injection
- Separate concerns (business logic from infrastructure)

#### Strategy 2: Use Test Doubles
- Mock external dependencies (filesystem, network)
- Stub collaborators
- Use fakes for complex dependencies

#### Strategy 3: Integration Tests for Complex Flows
- When unit testing is too difficult, use integration tests
- Accept slower test execution for better confidence

#### Strategy 4: Document Why Code is Not Tested
- Add `@codeCoverageIgnore` annotation with justification
- Document in comments why certain code can't be easily tested
- Keep excluded code to minimum

### 8. Maintenance Strategy

#### Enforce Coverage in CI
- Fail build if coverage drops below 90%
- Use tools like `coverage-check` or PHPUnit's `--coverage-text --colors=never | grep -E '^  Lines:' | grep -oE '[0-9]+\.[0-9]+' | head -1`

#### Review Coverage in PRs
- Require coverage report in PR description
- Review coverage diff (new code should maintain 90%+)

#### Periodic Coverage Audits
- Monthly review of uncovered code
- Identify and prioritize coverage gaps
- Update tests as code evolves

## Technical Decisions

### Decision 1: Use PHPUnit Native Coverage
**Rationale:** Already integrated, well-supported, no additional dependencies

**Alternatives Considered:**
- Codecov/Coveralls: External services, adds complexity
- PHPUnit Bridge coverage: Similar to native, no significant benefit

### Decision 2: Target 90% Line Coverage
**Rationale:** Industry standard, achievable without excessive effort, provides good confidence

**Trade-offs:**
- 100% coverage: Diminishing returns, too much effort
- 80% coverage: Too low for critical library
- 90% coverage: Sweet spot

### Decision 3: Prioritize Critical Paths First
**Rationale:** Maximize value delivered early, allows phased rollout

**Phases:**
1. Core generation logic (highest value)
2. DI and extensions (enables functionality)
3. Attributes and configuration (user-facing)
4. Error handling and edge cases (completeness)

### Decision 4: Use Snapshot Testing for Complex Output
**Rationale:** OpenAPI schemas are complex JSON structures, snapshots make tests maintainable

**Library:** `spatie/phpunit-snapshot-assertions` (already included)

## Open Issues and Questions

1. **Should we exclude simple DTOs from coverage requirements?**
   - Pros: Reduces busywork for trivial code
   - Cons: Makes rules more complex
   - Decision: Include them, tests are simple and fast

2. **How to handle generated/auto-loaded code?**
   - Use `@codeCoverageIgnore` annotation
   - Document in separate file

3. **Should we add mutation testing in the future?**
   - Mutation testing can validate test quality
   - Consider adding with Infection PHP after reaching 90%
   - Out of scope for this change

4. **How to handle flaky tests?**
   - Investigate and fix root cause
   - Use retry mechanisms only as last resort
   - Document known flaky tests and why

## Success Metrics
- Line coverage ≥90% across entire codebase
- All 354 source files have associated test coverage
- All 11 untested directories have test coverage
- Test suite execution time <2 minutes (to maintain fast feedback)
- Zero Pest dependencies remain
- Coverage reporting integrated into CI/CD

## Implementation Timeline
- Week 1: Foundation and migration (9 tasks)
- Week 2-3: Core components (9 tasks)
- Week 4: Configuration & Attributes (17 tasks)
- Week 5: Events, Exceptions, HTTP (10 tasks)
- Week 6: Reflection, Visitors, Integration (15 tasks)
- **Total: 60 tasks over 6 weeks**
