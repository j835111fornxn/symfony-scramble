<?php

namespace Dedoc\Scramble\Tests\PhpDoc;

use Dedoc\Scramble\PhpDoc\PhpDocTypeHelper;
use Dedoc\Scramble\Support\PhpDoc;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PhpDocTypeHelperTest extends TestCase
{
    #[Test]
    #[DataProvider('phpDocTypesProvider')]
    public function parsesPhpDocIntoTypeCorrectly(string $phpDocType, string $expectedTypeString): void
    {
        $this->assertSame(
            $expectedTypeString,
            $this->getPhpTypeFromDoc($phpDocType)->toString()
        );
    }

    public static function phpDocTypesProvider(): array
    {
        return [
            ['/** @var Foo */', 'Foo'],
            ['/** @var Foo<Bar, Baz> */', 'Foo<Bar, Baz>'],
        ];
    }

    #[Test]
    #[DataProvider('tupleTypesProvider')]
    public function parsesTuple(string $phpDocType, string $expectedTypeString): void
    {
        $this->assertSame(
            $expectedTypeString,
            $this->getPhpTypeFromDoc($phpDocType)->toString()
        );
    }

    public static function tupleTypesProvider(): array
    {
        return [
            ['/** @var array{float, float} */', 'list{float, float}'],
        ];
    }

    #[Test]
    #[DataProvider('listTypesProvider')]
    public function parsesList(string $phpDocType, string $expectedTypeString): void
    {
        $this->assertSame(
            $expectedTypeString,
            $this->getPhpTypeFromDoc($phpDocType)->toString()
        );
    }

    public static function listTypesProvider(): array
    {
        return [
            ['/** @var list<float> */', 'array<float>'],
        ];
    }

    #[Test]
    #[DataProvider('integerTypesProvider')]
    public function parsesIntegers(string $phpDocType, string $expectedTypeString): void
    {
        $this->assertSame(
            $expectedTypeString,
            $this->getPhpTypeFromDoc($phpDocType)->toString()
        );
    }

    public static function integerTypesProvider(): array
    {
        return [
            ['/** @var int */', 'int'],
            ['/** @var integer */', 'int'],
            ['/** @var positive-int */', 'int<1, max>'],
            ['/** @var negative-int */', 'int<min, -1>'],
            ['/** @var non-positive-int */', 'int<min, 0>'],
            ['/** @var non-negative-int */', 'int<0, max>'],
            ['/** @var non-zero-int */', 'int'],
            ['/** @var int<10, 11> */', 'int<10, 11>'],
            ['/** @var int<10, max> */', 'int<10, max>'],
            ['/** @var int<min, 10> */', 'int<min, 10>'],
            ['/** @var int<max, 10> */', 'int'],
            ['/** @var int<10, min> */', 'int'],
        ];
    }

    private function getPhpTypeFromDoc(string $phpDoc)
    {
        $docNode = PhpDoc::parse($phpDoc);
        $varNode = $docNode->getVarTagValues()[0];

        return PhpDocTypeHelper::toType($varNode->type);
    }
}
