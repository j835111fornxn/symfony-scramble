<?php

namespace Dedoc\Scramble\Tests\Support\InferExtensions;

use Dedoc\Scramble\Infer\Scope\GlobalScope;
use Dedoc\Scramble\Infer\Services\ReferenceTypeResolver;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Reference\MethodCallReferenceType;
use Dedoc\Scramble\Tests\Files\SamplePostModel;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class EloquentBuilderExtensionTest extends SymfonyTestCase
{
    #[Test]
    #[DataProvider('scopeMethodsProvider')]
    public function forwardsCallToScope(string $method, string $expectedType): void
    {
        $type = ReferenceTypeResolver::getInstance()
            ->resolve(
                new GlobalScope,
                new MethodCallReferenceType(
                    new Generic(Builder::class, [new ObjectType(SamplePostModel::class)]),
                    $method,
                    [],
                )
            );

        $this->assertSame($expectedType, $type->toString());
    }

    public static function scopeMethodsProvider(): array
    {
        return [
            ['approved', Builder::class.'<'.SamplePostModel::class.'>'],
            ['approvedTypedParam', Builder::class.'<'.SamplePostModel::class.'>'],
        ];
    }
}
