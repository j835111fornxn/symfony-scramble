<?php

namespace Dedoc\Scramble\Tests;

use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ParametersSerializationTest extends TestCase
{
    #[Test]
    public function checksReturnTypeOfParamWhenStyleAndExplodeSpecified(): void
    {
        $type = new StringType;
        $type->enum(['products', 'categories', 'condition']);

        $parameter = new Parameter('includes', 'query');
        $parameter->setSchema(Schema::fromType($type));
        $parameter->setExplode(false);
        $parameter->setStyle('form');

        $this->assertSame([
            'name' => 'includes',
            'in' => 'query',
            'style' => 'form',
            'schema' => [
                'type' => 'string',
                'enum' => [
                    'products',
                    'categories',
                    'condition',
                ],
            ],
            'explode' => false,
        ], $parameter->toArray());
    }

    #[Test]
    public function checksReturnTypeOfParamWhenStyleAndExplodeNotSpecified(): void
    {
        $type = new StringType;
        $type->enum(['products', 'categories', 'condition']);

        $parameter = new Parameter('includes', 'query');
        $parameter->setSchema(Schema::fromType($type));

        $this->assertSame([
            'name' => 'includes',
            'in' => 'query',
            'schema' => [
                'type' => 'string',
                'enum' => [
                    'products',
                    'categories',
                    'condition',
                ],
            ],
        ], $parameter->toArray());
    }
}
