# Tasks: Increase Test Coverage to 90%

## Phase 1: Foundation & Migration
- [x] 1.1 Configure PHPUnit coverage reporting in `phpunit.xml.dist`
  - [x] Enable coverage collection
  - [x] Configure source paths
  - [x] Set coverage format (HTML, Clover, text)
  - [x] Fix invalid `processUncoveredFiles` attribute
- [x] 1.2 Install and configure coverage driver (xdebug or pcov)
  - [x] Confirmed pcov is installed and working
- [ ] 1.3 Run baseline coverage report and document current metrics
  - ⚠️ Blocked: Test suite has issues preventing full execution
- [x] 1.4 Migrate `tests/Infer/Definition/FunctionLikeAstDefinitionTest.php` from Pest to PHPUnit
- [x] 1.5 Migrate `tests/Infer/DefinitionBuilders/FunctionLikeAstDefinitionBuilderTest.php` from Pest to PHPUnit
- [x] 1.6 Migrate `tests/Infer/DefinitionBuilders/FunctionLikeDeclarationAstDefinitionBuilderTest.php` from Pest to PHPUnit
- [x] 1.7 Migrate `tests/Infer/DefinitionBuilders/FunctionLikeReflectionDefinitionBuilderTest.php` from Pest to PHPUnit
- [x] 1.8 Create `JsonResource` stub for testing (tests/Stubs/JsonResource.php)
- [ ] 1.9 Verify all tests pass after migration
  - ⚠️ Blocked: Existing tests have issues from incomplete Symfony migration
- [ ] 1.10 Remove any remaining Pest dependencies and configuration

**Note:** Phase 1 partially completed. Test migration successful but blocked by pre-existing test suite issues requiring resolution of Laravel→Symfony migration artifacts.

## Phase 2: Core Components Testing
- [ ] 2.1 Add tests for `src/Generator.php` (core documentation generator)
- [ ] 2.2 Add tests for `src/DependencyInjection/ScrambleExtension.php`
- [ ] 2.3 Add tests for `src/DependencyInjection/Compiler/ScrambleExtensionPass.php`
- [ ] 2.4 Add tests for `src/DependencyInjection/Configuration.php`
- [ ] 2.5 Add tests for `src/ScrambleBundle.php`
- [ ] 2.6 Add tests for `src/DocumentTransformers/AddDocumentTags.php`
- [ ] 2.7 Add tests for `src/DocumentTransformers/CleanupUnusedResponseReferencesTransformer.php`
- [ ] 2.8 Add tests for extension system (`src/Extensions/`)
  - [ ] ExceptionToResponseExtension
  - [ ] OperationExtension
  - [ ] TypeToSchemaExtension
- [ ] 2.9 Run coverage report - target: ≥50%

## Phase 3: Configuration & Attributes
- [ ] 3.1 Add tests for all attribute classes in `src/Attributes/`
  - [ ] BodyParameter
  - [ ] CookieParameter
  - [ ] Endpoint
  - [ ] Example
  - [ ] ExcludeAllRoutesFromDocs
  - [ ] ExcludeRouteFromDocs
  - [ ] Group
  - [ ] Header
  - [ ] HeaderParameter
  - [ ] MissingValue
  - [ ] Parameter
  - [ ] PathParameter
  - [ ] QueryParameter
  - [ ] Response
  - [ ] SchemaName
- [ ] 3.2 Add tests for configuration classes in `src/Configuration/`
  - [ ] DocumentTransformers
  - [ ] GeneratorConfigCollection
  - [ ] InferConfig
  - [ ] OperationTransformers
  - [ ] ParametersExtractors
  - [ ] ServerVariables
- [ ] 3.3 Run coverage report - target: ≥65%

## Phase 4: Events, Exceptions & HTTP
- [ ] 4.1 Add tests for event classes in `src/Event/`
  - [ ] GenerationCompleteEvent
  - [ ] GenerationStartEvent
  - [ ] OperationGeneratedEvent
- [ ] 4.2 Add tests for event subscribers in `src/EventSubscriber/`
  - [ ] DocumentationAccessSubscriber (if not already tested)
  - [ ] ExceptionEventSubscriber (if not already tested)
- [ ] 4.3 Add tests for exception classes in `src/Exceptions/`
  - [ ] InvalidSchema
  - [ ] OpenApiReferenceTargetNotFoundException
  - [ ] RouteAwareTrait
- [ ] 4.4 Add tests for HTTP controller in `src/Http/`
  - [ ] DocumentationController
- [ ] 4.5 Run coverage report - target: ≥75%

## Phase 5: Reflection & Visitors
- [ ] 5.1 Add tests for reflection classes in `src/Reflection/`
  - [ ] ReflectionRoute
  - [ ] SymfonyReflectionRoute
- [ ] 5.2 Add tests for OpenAPI visitors in `src/OpenApiVisitor/`
  - [ ] SchemaEnforceVisitor
- [ ] 5.3 Add tests for base visitor classes
  - [ ] AbstractOpenApiVisitor
  - [ ] OpenApiVisitor
  - [ ] OpenApiTraverser (expand existing)
- [ ] 5.4 Run coverage report - target: ≥85%

## Phase 6: Integration & Completion
- [ ] 6.1 Add integration tests for complete documentation generation workflow
  - [ ] Generate docs for sample Symfony controller
  - [ ] Verify OpenAPI schema structure
  - [ ] Test with Doctrine entities
  - [ ] Test with validation constraints
- [ ] 6.2 Add integration tests for route exclusion
- [ ] 6.3 Add integration tests for custom extensions
- [ ] 6.4 Fill any remaining coverage gaps identified in coverage report
- [ ] 6.5 Run final coverage report - target: ≥90%
- [ ] 6.6 Verify all tests pass
- [ ] 6.7 Document testing patterns and guidelines in `docs/testing-guide.md`
- [ ] 6.8 Update `README.md` with testing and coverage information
- [ ] 6.9 Update `testing` spec with new coverage requirements

## Validation
- [ ] All tests pass: `composer test`
- [ ] Coverage report shows ≥90%: `composer test-coverage`
- [ ] No Pest dependencies remain in composer.json
- [ ] PHPUnit configuration is properly set up
- [ ] Testing documentation is complete

## Notes
- Prioritize business-critical code paths first
- Focus on meaningful tests that verify behavior, not just achieve metrics
- Use snapshot testing for complex OpenAPI schema outputs
- Leverage existing test utilities (SymfonyTestCase, test helpers)
- Document any bugs discovered during test writing
