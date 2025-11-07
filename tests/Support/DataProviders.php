<?php

namespace Dedoc\Scramble\Tests\Support;

/**
 * Common data providers for tests.
 */
class DataProviders
{
    public static function extendableTemplateTypes(): array
    {
        return [
            'int with TA is int' => ['int', 'TA', 'TA is int'],
            'bool with TA' => ['bool', 'TA', 'TA is boolean'],
            'float with TA' => ['float', 'TA', 'TA is float'],
            'empty with TA' => ['', 'TA', 'TA'],
            'string with TA' => ['string', 'TA', 'TA is string'],
            'SomeClass with TA' => ['SomeClass', 'TA', 'TA is SomeClass'],
            'callable with TA' => ['callable', 'TA', 'TA is callable'],
        ];
    }
}
