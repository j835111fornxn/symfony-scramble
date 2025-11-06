<?php

namespace Dedoc\Scramble\Tests\Support\OperationExtensions;

use Dedoc\Scramble\Tests\SymfonyTestCase;
use Illuminate\Support\Facades\Route as RouteFacade;
use PHPUnit\Framework\Attributes\Test;

class ResponseExtensionTest extends SymfonyTestCase
{
    #[Test]
    public function extracts_response_from_response_tag(): void
    {
        $openApiDocument = generateForRoute(function () {
            return RouteFacade::get('api/test', [Foo_ResponseExtensionTest_Controller::class, 'foo']);
        });

        $schema = $openApiDocument['paths']['/test']['get']['responses'][200]['content']['application/json']['schema'];
        $this->assertEquals('object', $schema['type']);
        $this->assertEquals('string', $schema['properties']['foo']['type']);
        $this->assertEquals(['bar'], $schema['properties']['foo']['enum']);
    }

    #[Test]
    public function ignores_annotation_when_return_node_is_manually_annotated(): void
    {
        $openApiDocument = generateForRoute(fn () => RouteFacade::get('api/test', [Foo_ResponseExtensionAnnotationTest__Controller::class, 'foo']));

        $schema = $openApiDocument['paths']['/test']['get']['responses'][200]['content']['application/json']['schema'];
        $this->assertEquals('object', $schema['type']);
        $this->assertEquals('string', $schema['properties']['foo']['type']);
    }

    #[Test]
    public function combines_responses_with_different_content_types(): void
    {
        $openApiDocument = generateForRoute(fn () => RouteFacade::get('api/test', MultipleMimes_ResponseExtensionTest_Controller::class));

        $response = $openApiDocument['paths']['/test']['get']['responses'][200];
        $this->assertNotNull($response);
        $this->assertArrayHasKey('Content-Disposition', $response['headers']);
        $this->assertEquals([
            'application/pdf' => ['schema' => ['type' => 'string', 'format' => 'binary']],
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'foo' => ['type' => 'string', 'enum' => ['bar']],
                    ],
                    'required' => ['foo'],
                ],
            ],
        ], $response['content']);
    }
}

class Foo_ResponseExtensionTest_Controller
{
    /**
     * @response array{"foo": "bar"}
     */
    public function foo()
    {
        return 42;
    }
}

class Foo_ResponseExtensionAnnotationTest__Controller
{
    public function foo(): int
    {
        /**
         * @body array{"foo": "bar"}
         */
        return unknown();
    }
}

class MultipleMimes_ResponseExtensionTest_Controller
{
    public function __invoke()
    {
        if (foobar()) {
            return ['foo' => 'bar'];
        }

        return response()->download('data.pdf');
    }
}
