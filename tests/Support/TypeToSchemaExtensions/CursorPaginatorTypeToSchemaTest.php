<?php

namespace Dedoc\Scramble\Tests\Support\TypeToSchemaExtensions;

use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Infer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\Components;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\CursorPaginatorTypeToSchema;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\JsonResourceTypeToSchema;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\CursorPaginator;
use PHPUnit\Framework\Attributes\Test;

class CursorPaginatorTypeToSchemaTest extends SymfonyTestCase
{
    private Components $components;
    private OpenApiContext $context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->components = new Components;
        $this->context = new OpenApiContext((new OpenApi('3.1.0'))->setComponents($this->components), new GeneratorConfig);
    }

    #[Test]
    public function correctly_documents_when_annotated(): void
    {
        $type = new Generic(CursorPaginator::class, [
            new ObjectType(CursorPaginatorTypeToSchemaTest_Resource::class),
        ]);

        $transformer = new TypeTransformer($infer = $this->get(Infer::class), $this->context, [
            JsonResourceTypeToSchema::class,
            CursorPaginatorTypeToSchema::class,
        ]);
        $extension = new CursorPaginatorTypeToSchema($infer, $transformer, $this->components, $this->context);

        $this->assertTrue($extension->shouldHandle($type));
        $this->assertMatchesSnapshot($extension->toResponse($type)->toArray());
    }
}

class CursorPaginatorTypeToSchemaTest_Resource extends JsonResource
{
    public function toArray(Request $request)
    {
        return ['id' => 1];
    }
}
