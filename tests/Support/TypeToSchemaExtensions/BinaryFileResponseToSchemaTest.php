<?php

namespace Dedoc\Scramble\Tests\Support\TypeToSchemaExtensions;

use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Infer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\Components;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\Type\ArrayType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\Literal\LiteralIntegerType;
use Dedoc\Scramble\Support\Type\Literal\LiteralStringType;
use Dedoc\Scramble\Support\Type\UnknownType;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\BinaryFileResponseToSchema;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BinaryFileResponseToSchemaTest extends SymfonyTestCase
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
            BinaryFileResponseToSchema::class,
        ]);
    }

    #[Test]
    public function transforms_basic_inferred_type_to_response(): void
    {
        // $type = getStatementType("response()->download(base_path('/tmp/wow.txt'))");
        $type = (new Generic(BinaryFileResponse::class, [
            new UnknownType,
            new LiteralIntegerType(200),
            new ArrayType,
            new LiteralStringType('attachment'),
        ]))->mergeAttributes([
            'mimeType' => 'text/plain',
            'contentDisposition' => 'attachment; filename=wow.txt',
        ]);

        $response = $this->transformer->toResponse($type);

        $this->assertArrayHasKey('Content-Disposition', $response->headers);
        $this->assertEquals('attachment; filename=wow.txt', $response->headers['Content-Disposition']->example);
        $this->assertArrayHasKey('text/plain', $response->content);
        $this->assertEquals(['type' => 'string'], $response->getContent('text/plain')->toArray());
    }
}
