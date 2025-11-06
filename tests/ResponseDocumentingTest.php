<?php

namespace Dedoc\Scramble\Tests;

use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

final class ResponseDocumentingTest extends TestCase
{
    use MatchesSnapshots;

    #[Test]
    public function responseNoContentCallSupport(): void
    {
        \Illuminate\Support\Facades\Route::get('api/test', [Foo_Test::class, 'index']);

        \Dedoc\Scramble\Scramble::routes(fn (Route $r) => $r->uri === 'api/test');
        $openApiDocument = app()->make(\Dedoc\Scramble\Generator::class)();

        $this->assertMatchesSnapshot($openApiDocument);
    }

    #[Test]
    public function responseJsonCallSupportWithPhpdocHelp(): void
    {
        \Illuminate\Support\Facades\Route::get('api/test', [Foo_TestTwo::class, 'index']);

        \Dedoc\Scramble\Scramble::routes(fn (Route $r) => $r->uri === 'api/test');
        $openApiDocument = app()->make(\Dedoc\Scramble\Generator::class)();

        $this->assertMatchesSnapshot($openApiDocument);
    }

    #[Test]
    public function multipleResponsesSupport(): void
    {
        $openApiDocument = generateForRoute(fn () => \Illuminate\Support\Facades\Route::get('api/test', [Foo_TestThree::class, 'index']));

        $this->assertMatchesSnapshot($openApiDocument);
    }

    #[Test]
    public function manuallyAnnotatedResponsesSupport(): void
    {
        $openApiDocument = generateForRoute(fn () => \Illuminate\Support\Facades\Route::get('api/test', [Foo_TestFour::class, 'index']));

        $this->assertMatchesSnapshot($openApiDocument);
    }

    #[Test]
    public function manuallyAnnotatedResponsesResourcesSupport(): void
    {
        $openApiDocument = generateForRoute(fn () => \Illuminate\Support\Facades\Route::get('api/test', [Foo_TestFive::class, 'index']));

        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'data' => ['$ref' => '#/components/schemas/Foo_TestFiveResource'],
            ],
            'required' => ['data'],
        ], $openApiDocument['paths']['/test']['get']['responses'][200]['content']['application/json']['schema']);
    }

    #[Test]
    public function automatedResponseStatusCodeInferenceWhenUsingResponseSetStatusCodeMethod(): void
    {
        $openApiDocument = generateForRoute(fn () => \Illuminate\Support\Facades\Route::get('api/test', [Foo_TestSix::class, 'single']));

        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'data' => [
                    '$ref' => '#/components/schemas/Foo_TestFiveResource',
                ],
            ],
            'required' => ['data'],
        ], $openApiDocument['paths']['/test']['get']['responses'][201]['content']['application/json']['schema']);
    }

    #[Test]
    public function automatedResponseStatusCodeInferenceWhenUsingCollectionResponseSetStatusCodeMethod(): void
    {
        $openApiDocument = generateForRoute(fn () => \Illuminate\Support\Facades\Route::get('api/test', [Foo_TestSix::class, 'collection']));

        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'data' => [
                    'type' => 'array',
                    'items' => ['$ref' => '#/components/schemas/Foo_TestFiveResource'],
                ],
            ],
            'required' => [
                0 => 'data',
            ],
        ], $openApiDocument['paths']['/test']['get']['responses'][201]['content']['application/json']['schema']);
    }

    #[Test]
    public function doesNotWrapResourcesWhenResourceIsWrapped(): void
    {
        $openApiDocument = generateForRoute(fn () => \Illuminate\Support\Facades\Route::get('api/test', [Foo_TestSeven::class, 'index']));

        $this->assertSame(
            ['$ref' => '#/components/schemas/Foo_TestSevenResource'],
            $openApiDocument['paths']['/test']['get']['responses'][200]['content']['application/json']['schema']
        );
        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'data' => [
                    'type' => 'object',
                    'properties' => ['foo' => ['type' => 'string']],
                    'required' => ['foo'],
                ],
            ],
            'required' => ['data'],
            'title' => 'Foo_TestSevenResource',
        ], $openApiDocument['components']['schemas']['Foo_TestSevenResource']);
    }

    #[Test]
    public function doesNotWrapResourcesWhenResourceIsPassedToJsonResponseExplicitly(): void
    {
        $openApiDocument = generateForRoute(fn () => \Illuminate\Support\Facades\Route::get('api/test', Foo_TestEight::class));

        $this->assertSame(
            ['$ref' => '#/components/schemas/Foo_TestEightResource'],
            $openApiDocument['paths']['/test']['get']['responses'][200]['content']['application/json']['schema']
        );
        $this->assertSame([
            'type' => 'object',
            'properties' => ['foo' => ['type' => 'string']],
            'required' => ['foo'],
            'title' => 'Foo_TestEightResource',
        ], $openApiDocument['components']['schemas']['Foo_TestEightResource']);
    }
}

class Foo_Test
{
    public function index()
    {
        return response()->noContent();
    }
}

class Foo_TestTwo
{
    public function index()
    {
        return response()->json([
            /** @var array{msg: string, code: int} */
            'error' => $var,
        ], 500);
    }
}

class Foo_TestThree
{
    public function index()
    {
        try {
            something_some();
        } catch (\Throwable $e) {
            return response()->json([
                /** @var array{msg: string, code: int} */
                'error' => $var,
            ], 500);
        }
        if ($foo) {
            return response()->json(['foo' => 'one']);
        }

        return response()->json(['foo' => 'bar']);
    }
}

class Foo_TestFour
{
    public function index()
    {
        if ($foo) {
            /**
             * Advanced comment.
             *
             * With more description.
             *
             * @status 201
             *
             * @body array{foo: string}
             */
            return response()->json(['foo' => 'one']);
        }

        // Simple comment.
        return response()->json(['foo' => 'bar']);
    }
}

class Foo_TestFive
{
    public function index()
    {
        /**
         * @body Foo_TestFiveResource
         */
        return response()->json(['foo' => 'bar']);
    }
}

class Foo_TestFiveResource extends \Illuminate\Http\Resources\Json\JsonResource
{
    public static $wrap = 'data';

    public function toArray(\Illuminate\Http\Request $request)
    {
        return [
            'foo' => $this->id,
        ];
    }
}

class Foo_TestSix
{
    public function single()
    {
        return (new Foo_TestFiveResource)->response()->setStatusCode(201);
    }

    public function collection()
    {
        return Foo_TestFiveResource::collection()->response()->setStatusCode(201);
    }
}

class Foo_TestSevenResource extends \Illuminate\Http\Resources\Json\JsonResource
{
    public static $wrap = 'data';

    public function toArray(\Illuminate\Http\Request $request)
    {
        return [
            'data' => [
                'foo' => $this->id,
            ],
        ];
    }
}

class Foo_TestSeven
{
    public function index()
    {
        return new Foo_TestSevenResource(unknown());
    }
}

class Foo_TestEightResource extends \Illuminate\Http\Resources\Json\JsonResource
{
    public static $wrap = 'data';

    public function toArray(\Illuminate\Http\Request $request)
    {
        return [
            'foo' => $this->id,
        ];
    }
}

class Foo_TestEight
{
    public function __invoke()
    {
        return response()->json(new Foo_TestEightResource(unknown()));
    }
}
