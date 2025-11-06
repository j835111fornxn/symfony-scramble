<?php

namespace Dedoc\Scramble\Tests\Support;

use Dedoc\Scramble\Infer;
use Dedoc\Scramble\Infer\DefinitionBuilders\FunctionLikeAstDefinitionBuilder;
use Dedoc\Scramble\Infer\Scope\Index;
use Dedoc\Scramble\Infer\Scope\NodeTypesResolver;
use Dedoc\Scramble\Infer\Scope\Scope;
use Dedoc\Scramble\Infer\Scope\ScopeContext;
use Dedoc\Scramble\Infer\Services\FileNameResolver;
use Dedoc\Scramble\Infer\Services\FileParser;
use Dedoc\Scramble\Infer\Services\ReferenceTypeResolver;
use Dedoc\Scramble\Infer\TypeInferer;
use Dedoc\Scramble\Infer\Visitors\PhpDocResolver;
use Dedoc\Scramble\Support\Type\Type;
use PhpParser\ErrorHandler\Throwing;
use PhpParser\NameContext;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PHPUnit\Framework\Assert;

trait TypeInferenceAssertions
{
    protected function assertSameJson(mixed $expected, mixed $actual, string $message = ''): void
    {
        $expectedJson = json_encode($expected, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $actualJson = json_encode($actual, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        Assert::assertSame($expectedJson, $actualJson, $message);
    }

    protected function assertTypeEquals(string $expected, Type $actual, string $message = ''): void
    {
        Assert::assertSame($expected, $actual->toString(), $message);
    }

    protected function assertHasType(string|callable $expectedType, callable $callback): void
    {
        $code = $this->getCallerCode($callback);

        // Try to get container from SymfonyTestCase if available
        try {
            $container = \Dedoc\Scramble\Tests\SymfonyTestCase::getTestContainer();
            $index = $container->has(Index::class) ? $container->get(Index::class) : new Index;
        } catch (\Throwable $e) {
            $index = new Index;
        }

        $traverser = new NodeTraverser;
        $traverser->addVisitor($nameResolver = new NameResolver);
        $traverser->addVisitor(new PhpDocResolver(
            $nameResolver = new FileNameResolver($nameResolver->getNameContext()),
        ));
        $traverser->addVisitor(new TypeInferer(
            $index,
            $nameResolver,
            $scope = new Scope($index, new NodeTypesResolver, new ScopeContext, $nameResolver),
            Infer\Context::getInstance()->extensionsBroker->extensions,
        ));
        $traverser->traverse(
            $fileAst = FileParser::getInstance()->parseContent($code)->getStatements(),
        );

        /** @var \PhpParser\Node\Expr\FuncCall $node */
        $node = (new \PhpParser\NodeFinder)->findFirst($fileAst, fn($n) => $n instanceof \PhpParser\Node\Expr\FuncCall && $n->name instanceof \PhpParser\Node\Name && $n->name->toString() === 'expect');

        $actualType = ReferenceTypeResolver::getInstance()->resolve(
            $scope,
            $incompleteType = ($scope->getType($node->args[0]->value)),
        );

        if (is_string($expectedType)) {
            Assert::assertSame($expectedType, $actualType->toString());
        } else {
            Assert::assertTrue($expectedType($actualType));
        }
    }

    private function getCallerCode(callable $callback): string
    {
        $reflection = new \ReflectionFunction($callback);

        $file = $reflection->getFileName();
        $startLine = $reflection->getStartLine();
        $endLine = $reflection->getEndLine();

        $fileContent = file_get_contents($file);
        $lines = explode("\n", $fileContent);

        $code = array_slice($lines, $startLine - 1, $endLine - $startLine + 1);
        $codeString = implode("\n", $code);

        return '<?php' . "\n\n" . $codeString;
    }
}
