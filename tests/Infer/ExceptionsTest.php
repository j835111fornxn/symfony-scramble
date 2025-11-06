<?php

namespace Dedoc\Scramble\Tests\Infer;

use Dedoc\Scramble\Infer\Extensions\ExpressionExceptionExtension;
use Dedoc\Scramble\Infer\Scope\Scope;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;

/**
 * Note: type inference system does not infer other method's/functions calls exceptions.
 */
class ExceptionsTest extends SymfonyTestCase
{
    public function test_infers_manually_thrown_exceptions(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
function foo () {
    throw new \Exception();
}
EOD)->getFunctionDefinition('foo');

        $this->assertNotNull($type);
        $this->assertCount(1, $type->type->exceptions);
        $this->assertSame(Exception::class, $type->type->exceptions[0]->name);
    }

    public function test_infers_exceptions_using_expression_exception_extensions(): void
    {
        $type = $this->analyzeFile(<<<'EOD'
<?php
function foo () {
    return bar();
}
EOD, [
            new class implements ExpressionExceptionExtension
            {
                public function getException(Expr $node, Scope $scope): array
                {
                    if ($node instanceof FuncCall && $node->name->toString() === 'bar') {
                        return [new ObjectType(Exception::class)];
                    }

                    return [];
                }
            },
        ])->getFunctionDefinition('foo');

        $this->assertNotNull($type);
        $this->assertCount(1, $type->type->exceptions);
        $this->assertSame(Exception::class, $type->type->exceptions[0]->name);
    }
}
