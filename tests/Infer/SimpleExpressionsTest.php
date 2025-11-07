<?php

namespace Dedoc\Scramble\Tests\Infer;

use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class SimpleExpressionsTest extends SymfonyTestCase
{
    #[Test]
    #[DataProvider('simpleTypesProvider')]
    public function infersSimpleTypes(string $statement, string $expectedType): void
    {
        $this->assertSame($expectedType, $this->getStatementType($statement)->toString());
    }

    public static function simpleTypesProvider(): array
    {
        return [
            ['null', 'null'],
            ['true', 'boolean(true)'],
            ['false', 'boolean(false)'],
            ['1', 'int(1)'],
            ['"foo"', 'string(foo)'],
            ['157.50', 'float(157.5)'],
        ];
    }

    #[Test]
    #[DataProvider('booleanOperationsProvider')]
    public function infersBooleanOperations(string $statement, string $expectedType): void
    {
        $this->assertSame($expectedType, $this->getStatementType($statement)->toString());
    }

    public static function booleanOperationsProvider(): array
    {
        return [
            ['! $some', 'boolean'],
            ['!! $some', 'boolean'],
            ['$a > $b', 'boolean'],
            ['$a >= $b', 'boolean'],
            ['$a < $b', 'boolean'],
            ['$a <= $b', 'boolean'],
            ['$a != $b', 'boolean'],
            ['$a !== $b', 'boolean'],
            ['$a == $b', 'boolean'],
            ['$a === $b', 'boolean'],
        ];
    }

    #[Test]
    #[DataProvider('dynamicStaticFetchProvider')]
    public function doesntFailOnDynamicStaticFetch(string $statement, string $expectedType): void
    {
        $this->assertSame($expectedType, $this->getStatementType($statement)->toString());
    }

    public static function dynamicStaticFetchProvider(): array
    {
        return [
            ['Something::{$v}', 'unknown'],
        ];
    }

    // @todo
    // casts test (int, float, bool, string)
    // array with literals test (int, float, bool, string)
}