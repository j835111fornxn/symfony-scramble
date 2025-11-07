<?php

namespace Dedoc\Scramble\Tests\Support\Type;

use Dedoc\Scramble\Tests\SymfonyTestCase;
use Dedoc\Scramble\Tests\TestUtils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class TypeWidenerTest extends SymfonyTestCase
{
    #[Test]
    #[DataProvider('typesWideningProvider')]
    public function typesWidening(string $type, string $expectedType): void
    {
        $type = TestUtils::parseType($type);

        $this->assertSame($expectedType, $type->widen()->toString());
    }

    public static function typesWideningProvider(): array
    {
        return [
            ['true|false', 'boolean'],
            ['true|false|true', 'boolean'],
            ['int|42', 'int'],
            ['42|69', 'int(42)|int(69)'],
            ['string|"wow"', 'string'],
        ];
    }
}
