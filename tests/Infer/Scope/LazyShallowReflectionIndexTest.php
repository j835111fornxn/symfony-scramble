<?php

namespace Dedoc\Scramble\Tests\Infer\Scope;

use Dedoc\Scramble\Infer\Scope\LazyShallowReflectionIndex;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\Test;

final class LazyShallowReflectionIndexTest extends SymfonyTestCase
{
    #[Test]
    public function buildsReflectionBasedDefinitionUponRequest(): void
    {
        $index = new LazyShallowReflectionIndex;

        $definition = $index->getClass(Foo_LazyShallowReflectionIndexTest::class);

        $this->assertSame(Foo_LazyShallowReflectionIndexTest::class, $definition->getData()->name);
        $this->assertCount(0, $definition->getData()->methods);
    }

    #[Test]
    public function buildsMethodDefinitionUponRequest(): void
    {
        $index = new LazyShallowReflectionIndex;

        $methodDefinition = $index->getClass(Foo_LazyShallowReflectionIndexTest::class)->getMethod('foo');

        $this->assertSame('int', $methodDefinition->type->getReturnType()->toString());
    }
}

class Foo_LazyShallowReflectionIndexTest
{
    public int $foo = 42;

    public function foo(): int {}
}
