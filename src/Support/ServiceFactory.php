<?php

namespace Dedoc\Scramble\Support;

use Dedoc\Scramble\Infer\Definition\FunctionLikeDefinition;
use Dedoc\Scramble\Infer\Scope\Index;
use Dedoc\Scramble\Infer\Scope\LazyShallowReflectionIndex;
use Dedoc\Scramble\Support\InferExtensions\ShallowFunctionDefinition;
use Dedoc\Scramble\Support\Type\FunctionType;
use Dedoc\Scramble\Support\Type\TemplateType;
use Dedoc\Scramble\Support\Type\VoidType;

/**
 * Factory class for creating complex services that were previously defined
 * in the Laravel service provider with closure-based factories.
 */
class ServiceFactory
{
    /**
     * Create LazyShallowReflectionIndex with predefined function definitions.
     *
     * Abort helpers are handled in the extension and these definitions are needed
     * to avoid leaking the annotated exceptions to the caller's definitions.
     */
    public static function createLazyShallowReflectionIndex(): LazyShallowReflectionIndex
    {
        return new LazyShallowReflectionIndex(
            functions: [
                'abort' => $abortType = new FunctionLikeDefinition(type: new FunctionType('abort', returnType: new VoidType)),
                'abort_if' => $abortType,
                'abort_unless' => $abortType,
                'throw_if' => $throwType = new FunctionLikeDefinition(type: new FunctionType('throw_if', returnType: new VoidType)),
                'throw_unless' => $throwType,
            ]
        );
    }

    /**
     * Create Index with class definitions from dictionaries and tap function definition.
     */
    public static function createIndex(): Index
    {
        $index = new Index;

        // Load class definitions from dictionary
        $dictionaryPath = dirname(__DIR__, 2).'/dictionaries/classMap.php';
        foreach ((require $dictionaryPath) ?: [] as $className => $serializedClassDefinition) {
            $index->classesDefinitions[$className] = unserialize($serializedClassDefinition);
        }

        // Define tap function with template type
        $templates = [$tValue = new TemplateType('TValue')];
        $functionType = new FunctionType(
            name: 'tap',
            arguments: [
                'value' => $tValue,
            ],
            returnType: $tValue,
        );
        $functionType->templates = $templates;

        $index->functionsDefinitions['tap'] = new ShallowFunctionDefinition(
            type: $functionType
        );

        return $index;
    }
}
