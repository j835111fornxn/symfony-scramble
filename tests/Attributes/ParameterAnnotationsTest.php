<?php

namespace Dedoc\Scramble\Tests\Attributes;

use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Example;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\Parameter;
use Dedoc\Scramble\Attributes\PathParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;

final class ParameterAnnotationsTest extends SymfonyTestCase
{
    #[Test]
    public function bodyParametersAttachesInfoToInferredParams(): void
    {
        $openApi = $this->generateForRoute(fn (Router $r) => $r->post('api/test', BodyParameterController_ParameterAnnotationsTest::class));

        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'The name of the company',
                ],
            ],
            'required' => ['name'],
        ], $openApi['paths']['/test']['post']['requestBody']['content']['application/json']['schema']);
    }

    #[Test]
    public function retrievesParametersFromParameterAnnotations(): void
    {
        $openApi = $this->generateForRoute(fn (Router $r) => $r->get('api/test', ParameterController_ParameterAnnotationsTest::class));

        $this->assertSame([
            'name' => 'per_page',
            'in' => 'query',
            'schema' => [
                'type' => 'integer',
                'default' => 15,
            ],
        ], $openApi['paths']['/test']['get']['parameters'][0]);
    }

    #[Test]
    public function supportsPathParametersAttributes(): void
    {
        $openApi = $this->generateForRoute(fn (Router $r) => $r->get('api/test/{testId}', ParameterController_PathParameterTest::class));

        $this->assertSame([
            'name' => 'testId',
            'in' => 'path',
            'required' => true,
            'description' => 'Nice test ID',
            'schema' => [
                'type' => 'string',
            ],
        ], $openApi['paths']['/test/{testId}']['get']['parameters'][0]);
    }

    #[Test]
    public function supportsSimpleExampleForParameterAnnotations(): void
    {
        $openApi = $this->generateForRoute(fn (Router $r) => $r->get('api/test', ParameterSimpleExampleController_ParameterAnnotationsTest::class));

        $this->assertSame([
            'name' => 'per_page',
            'in' => 'query',
            'schema' => [
                'type' => 'integer',
                'default' => 15,
            ],
            'example' => 10,
        ], $openApi['paths']['/test']['get']['parameters'][0]);
    }

    #[Test]
    public function allowsAnnotatingParametersWithTheSameNames(): void
    {
        $openApi = $this->generateForRoute(fn (Router $r) => $r->get('api/test', SameNameParametersController_ParameterAnnotationsTest::class));

        $parameters = $openApi['paths']['/test']['get']['parameters'];
        $this->assertCount(2, $parameters);
        $this->assertSame('per_page', $parameters[0]['name']);
        $this->assertSame('per_page', $parameters[1]['name']);
        $this->assertSame('query', $parameters[0]['in']);
        $this->assertSame('header', $parameters[1]['in']);
    }

    #[Test]
    public function allowsDefiningParametersWithTheSameNamesAsInferredInDifferentLocations(): void
    {
        $openApi = $this->generateForRoute(fn (Router $r) => $r->get('api/test/{test}', SameNameParametersAsInferredController_ParameterAnnotationsTest::class));

        $parameters = $openApi['paths']['/test/{test}']['get']['parameters'];
        $this->assertCount(2, $parameters);
        $this->assertSame('test', $parameters[0]['name']);
        $this->assertSame('test', $parameters[1]['name']);
        $this->assertSame('path', $parameters[0]['in']);
        $this->assertSame('query', $parameters[1]['in']);
    }

    #[Test]
    public function supportsComplexExamplesForParameterAnnotations(): void
    {
        $openApi = $this->generateForRoute(fn (Router $r) => $r->get('api/test', ParameterComplexExampleController_ParameterAnnotationsTest::class));

        $this->assertSame([
            'name' => 'per_page',
            'in' => 'query',
            'schema' => [
                'type' => 'integer',
                'default' => 15,
            ],
            'examples' => [
                'max' => [
                    'value' => 99,
                    'summary' => 'Max amount of stuff',
                    'description' => 'Really big item',
                ],
            ],
        ], $openApi['paths']['/test']['get']['parameters'][0]);
    }

    #[Test]
    public function mergesParameterDataWithTheDataInferredFromParameterAnnotations(): void
    {
        $openApi = $this->generateForRoute(fn (Router $r) => $r->get('api/test', ParameterOverridingController_ParameterAnnotationsTest::class));

        $this->assertSame([
            'name' => 'per_page',
            'in' => 'query',
            'schema' => [
                'type' => 'integer',
                'default' => 15,
            ],
        ], $openApi['paths']['/test']['get']['parameters'][0]);
    }

    #[Test]
    public function supportsSubclassParameterAnnotations(): void
    {
        $openApi = $this->generateForRoute(fn (Router $r) => $r->get('api/test', QueryParameterController_ParameterAnnotationsTest::class));

        $this->assertSame([
            'name' => 'per_page',
            'in' => 'header',
            'schema' => [
                'type' => 'integer',
                'default' => 15,
            ],
        ], $openApi['paths']['/test']['get']['parameters'][0]);
    }

    #[Test]
    public function supportsSubclassAnnotationsOnClosureRoutes(): void
    {
        $openApi = $this->generateForRoute(Route::get(
            'api/test',
            #[HeaderParameter('X-Retry-After', type: 'int')]
            function () {}
        ));

        $this->assertSame([
            'name' => 'X-Retry-After',
            'in' => 'header',
            'schema' => [
                'type' => 'integer',
            ],
        ], $openApi['paths']['/test']['get']['parameters'][0]);
    }
}

class BodyParameterController_ParameterAnnotationsTest
{
    #[BodyParameter('name', 'The name of the company')]
    public function __invoke(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string'],
        ]);
    }
}

class ParameterController_ParameterAnnotationsTest
{
    #[Parameter('query', 'per_page', type: 'int', default: 15)]
    public function __invoke() {}
}

class ParameterController_PathParameterTest
{
    #[PathParameter('testId', 'Nice test ID')]
    public function __invoke(string $testId) {}
}

class ParameterSimpleExampleController_ParameterAnnotationsTest
{
    #[Parameter('query', 'per_page', type: 'int', default: 15, example: 10)]
    public function __invoke() {}
}

class SameNameParametersController_ParameterAnnotationsTest
{
    #[QueryParameter('per_page')]
    #[HeaderParameter('per_page')]
    public function __invoke() {}
}

class SameNameParametersAsInferredController_ParameterAnnotationsTest
{
    #[QueryParameter('test')]
    public function __invoke(string $test) {}
}

class ParameterComplexExampleController_ParameterAnnotationsTest
{
    #[Parameter('query', 'per_page', type: 'int', default: 15, examples: ['max' => new Example(99, 'Max amount of stuff', 'Really big item')])]
    public function __invoke() {}
}

class ParameterOverridingController_ParameterAnnotationsTest
{
    #[Parameter('query', 'per_page', default: 15)]
    public function __invoke(Request $request)
    {
        $request->validate(['per_page' => 'int']);
    }
}

class QueryParameterController_ParameterAnnotationsTest
{
    #[HeaderParameter('per_page', type: 'int', default: 15)]
    public function __invoke() {}
}
