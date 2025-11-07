<?php

namespace Dedoc\Scramble\Tests\Infer\DefinitionBuilders;

use Dedoc\Scramble\Infer\Definition\ClassDefinition;
use Dedoc\Scramble\Infer\DefinitionBuilders\FunctionLikeDeclarationAstDefinitionBuilder;
use Dedoc\Scramble\Infer\Reflector\MethodReflector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FunctionLikeDeclarationAstDefinitionBuilderTest extends TestCase
{
    #[Test]
    public function it_creates_declaration_definition_from_ast(): void
    {
        $reflector = MethodReflector::make(Foo_FunctionLikeDeclarationAstDefinitionBuilderTest::class, 'foo');

        $definition = (new FunctionLikeDeclarationAstDefinitionBuilder(
            $reflector->getAstNode(),
            new ClassDefinition(Foo_FunctionLikeDeclarationAstDefinitionBuilderTest::class),
        ))->build();

        $this->assertSame('(string): int', $definition->type->toString());
        $this->assertSame('foo', $definition->type->name);
        $this->assertSame(Foo_FunctionLikeDeclarationAstDefinitionBuilderTest::class, $definition->definingClassName);
    }
}

class Foo_FunctionLikeDeclarationAstDefinitionBuilderTest
{
    public function foo(string $b = 'foo'): int {}
}
