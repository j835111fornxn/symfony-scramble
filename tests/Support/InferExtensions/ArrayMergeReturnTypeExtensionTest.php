<?php

namespace Dedoc\Scramble\Tests\Support\InferExtensions;

use Dedoc\Scramble\Support\InferExtensions\ArrayMergeReturnTypeExtension;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ArrayMergeReturnTypeExtensionTest extends SymfonyTestCase
{
    #[Test]
    public function infersArrayMergeType(): void
    {
        $type = $this->getStatementType('array_merge(["foo" => 23], ["bar" => "baz"])', [
            new ArrayMergeReturnTypeExtension,
        ]);

        $this->assertSame('array{foo: int(23), bar: string(baz)}', $type->toString());
    }

    #[Test]
    public function infersArrayMergeTypeWhenArraysHaveSameKeys(): void
    {
        $type = $this->getStatementType('array_merge(["foo" => 23], ["foo" => "baz"])', [
            new ArrayMergeReturnTypeExtension,
        ]);

        $this->assertSame('array{foo: string(baz)}', $type->toString());
    }
}
