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
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Tests\Utils\AnalysisResult;
use PhpParser\ErrorHandler\Throwing;
use PhpParser\NameContext;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use Symfony\Component\Routing\Route;

trait AnalysisHelpers
{
    protected function analyzeFile(
        string $code,
        array $extensions = [],
    ): AnalysisResult {
        if ($code[0] === '/') {
            $code = file_get_contents($code);
        }

        if (count($extensions)) {
            Infer\Context::configure(
                new Infer\Extensions\ExtensionsBroker($extensions),
            );
        }

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
            new Scope($index, new NodeTypesResolver, new ScopeContext, $nameResolver),
            Infer\Context::getInstance()->extensionsBroker->extensions,
        ));
        $traverser->traverse(
            $fileAst = FileParser::getInstance()->parseContent($code)->getStatements(),
        );

        $classLikeNames = array_map(
            fn(\PhpParser\Node\Stmt\ClassLike $cl) => $cl->name?->name,
            (new \PhpParser\NodeFinder)->find(
                $fileAst,
                fn($n) => $n instanceof \PhpParser\Node\Stmt\ClassLike,
            ),
        );

        foreach ($index->classesDefinitions as $classDefinition) {
            if (! in_array($classDefinition->name, $classLikeNames)) {
                continue;
            }
            foreach ($classDefinition->methods as $name => $methodDefinition) {
                $node = (new \PhpParser\NodeFinder)->findFirst(
                    $fileAst,
                    fn($n) => $n instanceof \PhpParser\Node\Stmt\ClassMethod && $n->name->name === $name,
                );

                if (! $node) {
                    continue;
                }

                $classDefinition->methods[$name] = (new FunctionLikeAstDefinitionBuilder(
                    $methodDefinition->type->name,
                    $node,
                    $index,
                    new FileNameResolver(new NameContext(new Throwing)),
                    $classDefinition,
                ))->build();
            }
        }

        // Should this be here? Index must be global?
        $this->resolveReferences($index, new ReferenceTypeResolver($index));

        return new AnalysisResult($index);
    }

    protected function analyzeClass(string $className, array $extensions = []): AnalysisResult
    {
        Infer\Context::configure(
            new Infer\Extensions\ExtensionsBroker($extensions),
        );
        // Try to get container from SymfonyTestCase if available
        try {
            $container = \Dedoc\Scramble\Tests\SymfonyTestCase::getTestContainer();
            $infer = $container->has(Infer::class) ? $container->get(Infer::class) : new Infer(new Index, new \Dedoc\Scramble\Configuration\InferConfig);
        } catch (\Throwable $e) {
            $infer = new Infer(new Index, new \Dedoc\Scramble\Configuration\InferConfig);
        }

        $infer->analyzeClass($className);

        return new AnalysisResult($infer->index);
    }

    protected function resolveReferences(Index $index, ReferenceTypeResolver $referenceResolver): void
    {
        foreach ($index->functionsDefinitions as $functionDefinition) {
            $fnScope = new Scope(
                $index,
                new NodeTypesResolver,
                new ScopeContext(functionDefinition: $functionDefinition),
                new FileNameResolver(new NameContext(new Throwing)),
            );
            FunctionLikeAstDefinitionBuilder::resolveFunctionReturnReferences($fnScope, $functionDefinition);
        }

        foreach ($index->classesDefinitions as $classDefinition) {
            foreach ($classDefinition->methods as $name => $methodDefinition) {
                $methodScope = new Scope(
                    $index,
                    new NodeTypesResolver,
                    new ScopeContext($classDefinition, $methodDefinition),
                    new FileNameResolver(new NameContext(new Throwing)),
                );
                FunctionLikeAstDefinitionBuilder::resolveFunctionReturnReferences($methodScope, $methodDefinition);
            }
        }
    }

    protected function getStatementType(string $statement, array $extensions = []): ?Type
    {
        return $this->analyzeFile('<?php', $extensions)->getExpressionType($statement);
    }

    protected function generateForRoute($param)
    {
        // Get container from SymfonyTestCase
        try {
            $container = \Dedoc\Scramble\Tests\SymfonyTestCase::getTestContainer();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Container not available for route generation: ' . $e->getMessage());
        }

        // For Symfony: route should be passed directly or created via callback
        if ($param instanceof Route) {
            $route = $param;
        } elseif (is_callable($param)) {
            $route = $param($container->get('router'));
        } else {
            throw new \InvalidArgumentException('Parameter must be a Route or callable');
        }

        $config = Scramble::configure()
            ->useConfig($container->getParameter('scramble.config') ?? [])
            ->routes(fn(Route $r) => $r->getPath() === $route->getPath());

        return $container->get(\Dedoc\Scramble\Generator::class)($config);
    }
}
