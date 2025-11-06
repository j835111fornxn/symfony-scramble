<?php

namespace Dedoc\Scramble\Tests;

use Dedoc\Scramble\Infer\Definition\ClassDefinition;
use Dedoc\Scramble\Infer\Definition\FunctionLikeAstDefinition;
use Dedoc\Scramble\Infer\DefinitionBuilders\FunctionLikeAstDefinitionBuilder;
use Dedoc\Scramble\Infer\Reflector\MethodReflector;
use Dedoc\Scramble\Infer\Scope\Index;
use Dedoc\Scramble\Infer\Services\FileNameResolver;
use Dedoc\Scramble\PhpDoc\PhpDocTypeHelper;
use Dedoc\Scramble\Support\PhpDoc;
use Dedoc\Scramble\Support\Type\Type;
use PHPStan\PhpDocParser\Parser\TokenIterator;

class TestUtils
{
    public static function parseType(string $type): Type
    {
        [$lexer, $_, $typeParser] = PhpDoc::getTokenizerAndParser();

        $tokens = new TokenIterator($lexer->tokenize($type));

        $phpDocType = $typeParser->parse($tokens);

        return PhpDocTypeHelper::toType($phpDocType);
    }

    public static function buildAstFunctionDefinition(MethodReflector $reflector, ?ClassDefinition $classDefinition = null): FunctionLikeAstDefinition
    {
        // Get container from SymfonyTestCase
        try {
            $container = \Dedoc\Scramble\Tests\SymfonyTestCase::getTestContainer();
            $index = $container->has(Index::class) ? $container->get(Index::class) : new Index;
        } catch (\Throwable $e) {
            $index = new Index;
        }

        $definition = (new FunctionLikeAstDefinitionBuilder(
            $reflector->name,
            $reflector->getAstNode(),
            $index,
            new FileNameResolver($reflector->getClassReflector()->getNameContext()),
            $classDefinition ?: new ClassDefinition($reflector->getClassReflector()->getReflection()->name)
        ))->build();

        $definition->definingClassName = $reflector->getClassReflector()->getReflection()->name;

        return $definition;
    }
}
