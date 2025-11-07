<?php

namespace Dedoc\Scramble\Tests\Infer\Definition;

use Dedoc\Scramble\Infer\Reflector\MethodReflector;
use Dedoc\Scramble\Tests\TestUtils;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FunctionLikeAstDefinitionTest extends TestCase
{
    #[Test]
    public function it_prefers_return_declaration_type_if_inferred_is_not_compatible(): void
    {
        $def = TestUtils::buildAstFunctionDefinition(
            MethodReflector::make(Foo_FunctionLikeAstDefinitionTest::class, 'foo'),
        );

        $this->assertSame('int', $def->getReturnType()->toString());
    }

    #[Test]
    public function it_prefers_return_phpdoc_type_if_inferred_is_not_compatible(): void
    {
        $def = TestUtils::buildAstFunctionDefinition(
            MethodReflector::make(Foo_FunctionLikeAstDefinitionTest::class, 'bar'),
        );

        $this->assertSame('int', $def->getReturnType()->toString());
    }

    #[Test]
    public function it_prefers_scramble_return_type_even_if_inferred_is_concrete(): void
    {
        $def = TestUtils::buildAstFunctionDefinition(
            MethodReflector::make(Foo_FunctionLikeAstDefinitionTest::class, 'baz'),
        );

        $this->assertSame('int', $def->getReturnType()->toString());
    }
}

class Foo_FunctionLikeAstDefinitionTest
{
    public function foo(): int
    {
        return unk();
    }

    /**
     * @return int
     */
    public function bar()
    {
        return unk();
    }

    /**
     * @scramble-return int
     */
    public function baz()
    {
        return 42;
    }
}
