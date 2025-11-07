<?php

namespace Dedoc\Scramble\Tests;

use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Infer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\Components;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\CollectionToSchema;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\ModelToSchema;
use Dedoc\Scramble\Tests\Fixtures\Entities\Post;
use Dedoc\Scramble\Tests\Fixtures\Entities\PostWithToArray;
use Dedoc\Scramble\Tests\Fixtures\Entities\User;
use Spatie\Snapshots\MatchesSnapshots;

final class InferTypesTest extends SymfonyTestCase
{
    use MatchesSnapshots;

    private Infer $infer;

    private Components $components;

    private OpenApiContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $container = self::getContainer();
        $this->infer = $container->get(Infer::class);
        $this->components = new Components;
        $this->context = new OpenApiContext((new OpenApi('3.1.0'))->setComponents($this->components), new GeneratorConfig);
    }

    public function test_gets_json_resource_type(): void
    {
        $this->markTestSkipped('JsonResource is Laravel-specific and not relevant to Symfony-based system. See: openspec/changes/eliminate-test-laravel-dependencies');

        $def = $this->infer->analyzeClass(InferTypesTest_SampleJsonResource::class);

        $returnType = $def->getMethodDefinition('toArray')->type->getReturnType();

        $this->assertMatchesTextSnapshot($returnType->toString());
    }

    public function test_gets_json_resource_type_with_enum(): void
    {
        $this->markTestSkipped('JsonResource is Laravel-specific and not relevant to Symfony-based system. See: openspec/changes/eliminate-test-laravel-dependencies');

        $def = $this->infer->analyzeClass(InferTypesTest_SampleTwoPostJsonResource::class);

        $returnType = $def->getMethodDefinition('toArray')->type->getReturnType();

        $this->assertMatchesTextSnapshot($returnType->toString());
    }

    public function test_infers_model_type(): void
    {
        $transformer = new TypeTransformer($this->infer, $this->context, [
            ModelToSchema::class,
            CollectionToSchema::class,
        ]);
        $extension = new ModelToSchema($this->infer, $transformer, $this->components, $this->context);

        $type = new ObjectType(Post::class);
        $openApiType = $extension->toSchema($type);

        $this->assertCount(2, $this->components->schemas);
        $this->assertArrayHasKey('Post', $this->components->schemas);
        $this->assertArrayHasKey('User', $this->components->schemas);
        $this->assertMatchesSnapshot($openApiType->toArray());
    }

    public function test_infers_model_type_when_to_array_is_implemented(): void
    {
        $transformer = new TypeTransformer($this->infer, $this->context, [
            ModelToSchema::class,
            CollectionToSchema::class,
        ]);
        $extension = new ModelToSchema($this->infer, $transformer, $this->components, $this->context);

        $type = new ObjectType(PostWithToArray::class);
        $openApiType = $extension->toSchema($type);

        $this->assertCount(2, $this->components->schemas);
        $this->assertArrayHasKey('PostWithToArray', $this->components->schemas);
        $this->assertArrayHasKey('User', $this->components->schemas);
        $this->assertMatchesSnapshot($openApiType->toArray());
    }
}

/**
 * @property User $resource
 */
class InferTypesTest_SampleJsonResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            $this->merge(fn () => ['foo' => 'bar']),
            $this->mergeWhen(true, fn () => ['foo' => 'bar', 'id_inside' => $this->resource->id]),
            'when' => $this->when(true, ['wiw']),
            'item' => new InferTypesTest_SampleTwoJsonResource($this->resource),
            'item_make' => InferTypesTest_SampleTwoJsonResource::make($this->resource),
            'items' => InferTypesTest_SampleTwoJsonResource::collection($this->resource),
            'optional_when_new' => $this->when(true, fn () => new InferTypesTest_SampleTwoJsonResource($this->resource)),
            $this->mergeWhen(true, fn () => [
                'threads' => [
                    $this->mergeWhen(true, fn () => [
                        'brand' => new InferTypesTest_SampleTwoJsonResource(null),
                    ]),
                ],
            ]),
            '_test' => 1,
            /** @var int $with_doc great */
            'with_doc' => $this->foo,
            /** @var string wow this is good */
            'when_with_doc' => $this->when(true, 'wiw'),
            'some' => $this->some,
            'id' => $this->id,
            'email' => $this->resource->email,
        ];
    }
}

/**
 * @property Post $resource
 */
class InferTypesTest_SampleTwoJsonResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'email' => $this->resource->email,
        ];
    }
}

/**
 * @property Post $resource
 */
class InferTypesTest_SampleTwoPostJsonResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
        ];
    }
}
