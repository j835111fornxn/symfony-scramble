<?php

namespace Dedoc\Scramble\Tests\Infer;

use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class ClassConstFetchTypesTest extends SymfonyTestCase
{
    #[Test]
    #[DataProvider('inferClassConstFetchTypesProvider')]
    public function infersClassConstFetchTypes(string $statement, string $expectedType): void
    {
        $this->assertSame($expectedType, $this->getStatementType($statement)->toString());
    }

    public static function inferClassConstFetchTypesProvider(): array
    {
        return [
            ['$var::class', 'string'],
            ['(new SomeType)::class', 'class-string<SomeType>'],
            ['SomeType::class', 'class-string<SomeType>'],
            ['Enum_ClassConstFetchTypesTest::FOO', 'Enum_ClassConstFetchTypesTest::FOO'],
        ];
    }
}

enum Enum_ClassConstFetchTypesTest: string
{
    case FOO = 'foo';
    case BAR = 'bar';
}
