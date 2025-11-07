<?php

namespace Dedoc\Scramble\Tests\Infer\DefinitionBuilders;

use Dedoc\Scramble\Infer\Definition\ClassDefinition;
use Dedoc\Scramble\Infer\Definition\FunctionLikeDefinition;
use Dedoc\Scramble\Infer\DefinitionBuilders\FunctionLikeAstDefinitionBuilder;
use Dedoc\Scramble\Infer\Reflector\MethodReflector;
use Dedoc\Scramble\Infer\Scope\Index;
use Dedoc\Scramble\Infer\Services\FileNameResolver;
use Dedoc\Scramble\Support\Collection;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\TemplateType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FunctionLikeAstDefinitionBuilderTest extends TestCase
{
    private function buildAstFunctionDefinition(MethodReflector $reflector, ?ClassDefinition $classDefinition = null): FunctionLikeDefinition
    {
        return (new FunctionLikeAstDefinitionBuilder(
            $reflector->name,
            $reflector->getAstNode(),
            app(Index::class),
            new FileNameResolver($reflector->getClassReflector()->getNameContext()),
            $classDefinition
        ))->build();
    }

    #[Test]
    public function it_respects_scramble_return_primitive_annotation(): void
    {
        $definition = $this->buildAstFunctionDefinition(
            MethodReflector::make(Foo_FunctionLikeAstDefinitionBuilderTest::class, 'foo'),
        );

        $this->assertSame('int', $definition->type->returnType->toString());
    }

    #[Test]
    public function it_respects_scramble_return_generic_annotation(): void
    {
        $definition = $this->buildAstFunctionDefinition(
            MethodReflector::make(Foo_FunctionLikeAstDefinitionBuilderTest::class, 'bar'),
        );

        $this->assertSame('Illuminate\Support\Collection<int, string>', $definition->type->returnType->toString());
    }

    #[Test]
    public function it_allows_templates_in_scramble_return_annotation(): void
    {
        $definition = $this->buildAstFunctionDefinition(
            MethodReflector::make(Bar_FunctionLikeAstDefinitionBuilderTest::class, 'templated'),
            new ClassDefinition(Bar_FunctionLikeAstDefinitionBuilderTest::class, [new TemplateType('TFoo')])
        );

        $rt = $definition->type->returnType;
        $this->assertInstanceOf(Generic::class, $rt);
        $this->assertSame(Collection::class, $rt->name);

        $t = $rt->templateTypes[1];
        $this->assertInstanceOf(TemplateType::class, $t);
        $this->assertSame('TFoo', $t->name);
    }
}

class Foo_FunctionLikeAstDefinitionBuilderTest
{
    /**
     * @scramble-return int
     */
    public function foo() {}

    /**
     * @scramble-return \Illuminate\Support\Collection<int, string>
     */
    public function bar() {}

    /**
     * @scramble-return \Illuminate\Support\Collection<int, TNonExisting>
     */
    public function fail() {}
}

class Bar_FunctionLikeAstDefinitionBuilderTest
{
    /**
     * @scramble-return \Illuminate\Support\Collection<int, TFoo>
     */
    public function templated() {}
}
