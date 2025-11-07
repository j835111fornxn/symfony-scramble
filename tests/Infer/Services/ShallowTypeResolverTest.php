<?php

namespace Dedoc\Scramble\Tests\Infer\Services;

use Dedoc\Scramble\Infer\Scope\GlobalScope;
use Dedoc\Scramble\Infer\Scope\LazyShallowReflectionIndex;
use Dedoc\Scramble\Infer\Services\ShallowTypeResolver;
use Dedoc\Scramble\Support\Type\CallableStringType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Reference\CallableCallReferenceType;
use Dedoc\Scramble\Support\Type\Reference\MethodCallReferenceType;
use Dedoc\Scramble\Support\Type\Reference\NewCallReferenceType;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use PHPUnit\Framework\Attributes\Test;

class ShallowTypeResolverTest extends SymfonyTestCase
{
    private LazyShallowReflectionIndex $index;
    private GlobalScope $scope;

    protected function setUp(): void
    {
        parent::setUp();
        $this->index = new LazyShallowReflectionIndex;
        $this->scope = new GlobalScope;
    }

    #[Test]
    public function handles_callable_call_reference_type(): void
    {
        $type = (new ShallowTypeResolver($this->index))->resolve($this->scope, new CallableCallReferenceType(
            new CallableStringType(__NAMESPACE__.'\\fn_ShallowTypeResolverTest'),
            []
        ));
        $this->assertEquals('int', $type->toString());
    }

    #[Test]
    public function handles_new_reference_type(): void
    {
        $type = (new ShallowTypeResolver($this->index))->resolve($this->scope, new NewCallReferenceType(
            Foo_ShallowTypeResolverTest::class,
            []
        ));
        $this->assertEquals(Foo_ShallowTypeResolverTest::class, $type->toString());
    }

    #[Test]
    public function handles_self_return_annotation(): void
    {
        $type = (new ShallowTypeResolver($this->index))->resolve($this->scope, new MethodCallReferenceType(
            new ObjectType(Foo_ShallowTypeResolverTest::class),
            'returnsSelf',
            []
        ));
        $this->assertEquals(Bar_ShallowTypeResolverTest::class, $type->toString());
    }

    #[Test]
    public function handles_static_return_annotation(): void
    {
        $type = (new ShallowTypeResolver($this->index))->resolve($this->scope, new MethodCallReferenceType(
            new ObjectType(Foo_ShallowTypeResolverTest::class),
            'returnsStatic',
            []
        ));
        $this->assertEquals(Foo_ShallowTypeResolverTest::class, $type->toString());
    }

    #[Test]
    public function handles_parent_return_annotation(): void
    {
        $type = (new ShallowTypeResolver($this->index))->resolve($this->scope, new MethodCallReferenceType(
            new ObjectType(Foo_ShallowTypeResolverTest::class),
            'returnsParent',
            []
        ));
        $this->assertEquals(Bar_ShallowTypeResolverTest::class, $type->toString());
    }
}

class Foo_ShallowTypeResolverTest extends Bar_ShallowTypeResolverTest
{
    public function returnsParent(): parent {}
}
class Bar_ShallowTypeResolverTest
{
    public function returnsSelf(): self {}

    public function returnsStatic(): static {}
}
function fn_ShallowTypeResolverTest(): int {}
