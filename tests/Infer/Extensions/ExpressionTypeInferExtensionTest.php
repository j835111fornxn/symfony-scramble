<?php

namespace Dedoc\Scramble\Tests\Infer\Extensions;

use Dedoc\Scramble\Infer\Extensions\ExpressionTypeInferExtension;
use Dedoc\Scramble\Infer\Scope\Scope;
use Dedoc\Scramble\Support\Type\Literal\LiteralStringType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use PhpParser\Node\Expr;
use PHPUnit\Framework\Attributes\Test;

final class ExpressionTypeInferExtensionTest extends SymfonyTestCase
{
    #[Test]
    public function usesExpressionTypeInferExtension(): void
    {
        $extension = new class implements ExpressionTypeInferExtension
        {
            public function getType(Expr $node, Scope $scope): ?Type
            {
                if ($node instanceof Expr\MethodCall && $node->name->toString() === 'callWow') {
                    return new LiteralStringType('wow');
                }

                return null;
            }
        };

        $type = $this->analyzeClass(ExpressionTypeInferExtensionTest_Test::class, [$extension])
            ->getClassDefinition(ExpressionTypeInferExtensionTest_Test::class)
            ->getMethodDefinition('foo')
            ->type->getReturnType();

        $this->assertSame('string(wow)', $type->toString());
    }
}

class ExpressionTypeInferExtensionTest_Test
{
    public function foo()
    {
        return $this->callWow();
    }
}
