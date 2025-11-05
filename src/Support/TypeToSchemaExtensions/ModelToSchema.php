<?php

namespace Dedoc\Scramble\Support\TypeToSchemaExtensions;

use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Infer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\ClassBasedReference;
use Dedoc\Scramble\Support\Generator\Components;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;

class ModelToSchema extends TypeToSchemaExtension
{
    private const ELOQUENT_MODEL_CLASS = 'Illuminate\\Database\\Eloquent\\Model';

    public function __construct(
        Infer $infer,
        TypeTransformer $openApiTransformer,
        Components $components,
        protected OpenApiContext $openApiContext
    ) {
        parent::__construct($infer, $openApiTransformer, $components);
    }

    public function shouldHandle(Type $type)
    {
        // Only handle if Laravel's Eloquent Model class exists (optional dependency)
        if (! class_exists(self::ELOQUENT_MODEL_CLASS)) {
            return false;
        }

        return $type instanceof ObjectType
            && $type->isInstanceOf(self::ELOQUENT_MODEL_CLASS)
            && $type->name !== self::ELOQUENT_MODEL_CLASS;
    }

    /**
     * @param  ObjectType  $type
     */
    public function toSchema(Type $type)
    {
        $this->infer->analyzeClass($type->name);

        $toArrayReturnType = $type->getMethodReturnType('toArray');

        return $this->openApiTransformer->transform($toArrayReturnType);
    }

    /**
     * @param  ObjectType  $type
     */
    public function toResponse(Type $type)
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
