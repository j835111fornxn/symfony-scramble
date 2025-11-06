<?php

namespace Dedoc\Scramble\Tests\Support\TypeToSchemaExtensions;

use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Infer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\Components;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\StreamedResponseToSchema;
use Dedoc\Scramble\Tests\SymfonyTestCase;

final class StreamedResponseToSchemaTest extends SymfonyTestCase
{
    private Components $components;
    private OpenApiContext $context;
    private TypeTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->components = new Components;
        $this->context = new OpenApiContext((new OpenApi('3.1.0'))->setComponents($this->components), new GeneratorConfig);
        $this->transformer = new TypeTransformer($this->get(Infer::class), $this->context, [
            StreamedResponseToSchema::class,
        ]);
    }

    public function testTransformsJsonInferredTypeToResponse(): void
    {
        $type = getStatementType("response()->streamJson(['foo' => 'bar'])");

        $response = $this->transformer->toResponse($type);

        $this->assertSame([
            'description' => '',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => ['foo' => ['type' => 'string', 'enum' => ['bar']]],
                        'required' => ['foo'],
                    ],
                ],
            ],
            'headers' => [
                'Transfer-Encoding' => [
                    'required' => true,
                    'schema' => ['type' => 'string', 'enum' => ['chunked']],
                ],
            ],
        ], $response->toArray());
    }

    public function testTransformsSseInferredTypeToResponse(): void
    {
        $type = getStatementType('response()->eventStream(fn () => [])');

        $response = $this->transformer->toResponse($type);

        $this->assertJsonStringEqualsJsonString(json_encode([
            'description' => 'A server-sent events (SSE) streamed response. `</stream>` update will be sent to the event stream when the stream is complete.',
            'content' => [
                'text/event-stream' => [
                    'schema' => [
                        'type' => 'object',
                        'examples' => [
                            "event: update\ndata: {data}\n\nevent: update\ndata: </stream>\n\n",
                        ],
                        'properties' => [
                            'event' => ['type' => 'string', 'example' => 'update'],
                            'data' => (object) [],
                        ],
                        'required' => ['event', 'data'],
                    ],
                ],
            ],
            'headers' => [
                'Transfer-Encoding' => [
                    'required' => true,
                    'schema' => ['type' => 'string', 'enum' => ['chunked']],
                ],
            ],
        ]), json_encode($response->toArray()));
    }

    public function testTransformsSseWithoutStringEndEventToResponse(): void
    {
        $type = getStatementType("response()->eventStream(fn () => [], endStreamWith: new \Illuminate\Http\StreamedEvent(event: 'end', data: 'real'))");

        $response = $this->transformer->toResponse($type);

        $this->assertJsonStringEqualsJsonString(json_encode([
            'description' => 'A server-sent events (SSE) streamed response.',
            'content' => [
                'text/event-stream' => [
                    'schema' => [
                        'type' => 'object',
                        'examples' => [
                            "event: update\ndata: {data}\n\n",
                        ],
                        'properties' => [
                            'event' => ['type' => 'string', 'example' => 'update'],
                            'data' => (object) [],
                        ],
                        'required' => ['event', 'data'],
                    ],
                ],
            ],
            'headers' => [
                'Transfer-Encoding' => [
                    'required' => true,
                    'schema' => ['type' => 'string', 'enum' => ['chunked']],
                ],
            ],
        ]), json_encode($response->toArray()));
    }

    public function testTransformsPlainStreamedTypeToResponse(): void
    {
        $type = getStatementType('response()->stream(fn () => f())');

        $response = $this->transformer->toResponse($type);

        $this->assertJsonStringEqualsJsonString(json_encode([
            'description' => '',
            'content' => [
                'text/html' => [
                    'schema' => [
                        'type' => 'string',
                    ],
                ],
            ],
            'headers' => [
                'Transfer-Encoding' => [
                    'required' => true,
                    'schema' => ['type' => 'string', 'enum' => ['chunked']],
                ],
            ],
        ]), json_encode($response->toArray()));
    }
}
