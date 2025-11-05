<?php

namespace Dedoc\Scramble\Support\TypeToSchemaExtensions;

use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Infer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Doctrine\DoctrineMetadataExtractor;
use Dedoc\Scramble\Support\Generator\ClassBasedReference;
use Dedoc\Scramble\Support\Generator\Components;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;

class DoctrineEntityToSchema extends TypeToSchemaExtension
{
    public function __construct(
        Infer $infer,
        TypeTransformer $openApiTransformer,
        Components $components,
        protected OpenApiContext $openApiContext,
        protected DoctrineMetadataExtractor $metadataExtractor,
    ) {
        parent::__construct($infer, $openApiTransformer, $components);
    }

    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ObjectType
            && $this->metadataExtractor->isEntity($type->name);
    }

    /**
     * @param  ObjectType  $type
     */
    public function toSchema(Type $type)
    {
        $this->infer->analyzeClass($type->name);

        // For Doctrine entities, we analyze the entity metadata and generate the schema
        // This is similar to how ModelToSchema worked but using Doctrine metadata instead of toArray()
        return $this->openApiTransformer->transform($type);
    }

    /**
     * @param  ObjectType  $type
     */
    public function toResponse(Type $type): Response
    {
        return Response::make(200)
            ->setDescription('`'.$this->openApiContext->references->schemas->uniqueName($type->name).'`')
            ->setContent(
                'application/json',
                Schema::fromType($this->openApiTransformer->transform($type)),
            );
    }

    public function reference(ObjectType $type)
    {
        return ClassBasedReference::create('schemas', $type->name, $this->components);
    }
}
