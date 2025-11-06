<?php

namespace Dedoc\Scramble\Tests\Generator;

use Illuminate\Support\Facades\Route as RouteFacade;
use PHPUnit\Framework\Attributes\Test;
use Dedoc\Scramble\Tests\SymfonyTestCase;

final class ManualResponseDocumentationTest extends SymfonyTestCase
{
    #[Test]
    public function documentsAResponseEvenWhenReturnTypeIsTakenFromAnAnnotation(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return RouteFacade::get('api/test', [ManualResponseDocumentation_Test::class, 'a']);
        });

        $this->assertArrayHasKey('description', $openApiDocument['paths']['/test']['get']['responses'][200]);
        $this->assertSame('Wow.', $openApiDocument['paths']['/test']['get']['responses'][200]['description']);

        $this->assertArrayHasKey('content', $openApiDocument['paths']['/test']['get']['responses'][200]);
        $this->assertArrayHasKey('application/json', $openApiDocument['paths']['/test']['get']['responses'][200]['content']);
        $this->assertArrayHasKey('schema', $openApiDocument['paths']['/test']['get']['responses'][200]['content']['application/json']);
        $this->assertArrayHasKey('properties', $openApiDocument['paths']['/test']['get']['responses'][200]['content']['application/json']['schema']);
        $this->assertArrayHasKey('id', $openApiDocument['paths']['/test']['get']['responses'][200]['content']['application/json']['schema']['properties']);
        $this->assertArrayHasKey('type', $openApiDocument['paths']['/test']['get']['responses'][200]['content']['application/json']['schema']['properties']['id']);
        $this->assertSame('integer', $openApiDocument['paths']['/test']['get']['responses'][200]['content']['application/json']['schema']['properties']['id']['type']);
    }

    #[Test]
    public function doesntUseCommentsFromServiceClasses(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return RouteFacade::get('api/test', ServiceResponseDocumentation_Test::class);
        });

        $this->assertArrayHasKey('description', $openApiDocument['paths']['/test']['get']['responses'][200]);
        $this->assertSame('', $openApiDocument['paths']['/test']['get']['responses'][200]['description']);
    }
}

class ManualResponseDocumentation_Test extends \Illuminate\Routing\Controller
{
    public function a(): Illuminate\Http\Resources\Json\JsonResource
    {
        /**
         * Wow.
         *
         * @body array{id: int}
         */
        return $this->unknown_fn();
    }
}

class ServiceResponseDocumentation_Test extends \Illuminate\Routing\Controller
{
    public function __invoke()
    {
        return (new Foo_ManualResponseDocumentationTest)->foo();
    }
}

class Foo_ManualResponseDocumentationTest
{
    public function foo()
    {
        /**
         * Wow.
         */
        return $this->unknown_fn();
    }
}
