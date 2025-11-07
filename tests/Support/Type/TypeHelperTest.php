<?php

namespace Dedoc\Scramble\Tests\Support\Type;

use Dedoc\Scramble\Support\Type\TypeHelper;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use PhpParser\Node;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class TypeHelperTest extends SymfonyTestCase
{
    #[Test]
    #[DataProvider('valueProvider')]
    public function create_type_from_value($value, string $expectedType): void
    {
        $type = TypeHelper::createTypeFromValue($value);

        $this->assertEquals($expectedType, $type->toString());
    }

    public static function valueProvider(): array
    {
        return [
            [1, 'int(1)'],
            ['foo', 'string(foo)'],
            [[1, 2, 3], 'list{int(1), int(2), int(3)}'],
        ];
    }

    #[Test]
    public function create_type_from_enum_value(): void
    {
        $type = TypeHelper::createTypeFromValue([
            Foo_TypeHelperTest::Foo,
            Foo_TypeHelperTest::Bar,
        ]);

        $this->assertEquals('list{Dedoc\Scramble\Tests\Support\Type\Foo_TypeHelperTest::Foo, Dedoc\Scramble\Tests\Support\Type\Foo_TypeHelperTest::Bar}', $type->toString());
    }

    #[Test]
    #[DataProvider('typeNodeProvider')]
    public function create_type_from_type_node($node, string $expectedType): void
    {
        $type = TypeHelper::createTypeFromTypeNode($node);

        $this->assertEquals($expectedType, $type->toString());
    }

    public static function typeNodeProvider(): array
    {
        return [
            [new Node\Identifier('int'), 'int'],
            [new Node\Identifier('string'), 'string'],
            [new Node\Identifier('bool'), 'boolean'],
            [new Node\Identifier('true'), 'boolean(true)'],
            [new Node\Identifier('false'), 'boolean(false)'],
            [new Node\Identifier('float'), 'float'],
            [new Node\Identifier('array'), 'array<mixed>'],
            [new Node\Identifier('null'), 'null'],
            [new Node\Name('App\\Models\\User'), 'App\\Models\\User'],
            [new Node\NullableType(new Node\Identifier('string')), 'null|string'],
            [new Node\UnionType([
                new Node\Identifier('int'),
                new Node\Identifier('string'),
                new Node\Identifier('null'),
            ]), 'int|string|null'],
        ];
    }
}

enum Foo_TypeHelperTest: string
{
    case Foo = 'f';
    case Bar = 'b';
}
