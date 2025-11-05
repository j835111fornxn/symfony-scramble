<?php

namespace Dedoc\Scramble\Support;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

class ContainerUtils
{
    /**
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return T
     */
    public static function makeContextable(string $class, array $contextfulBindings = [])
    {
        $container = app();

        $reflectionClass = new ReflectionClass($class);

        $parameters = $reflectionClass->getConstructor()?->getParameters() ?? [];

        $contextfulArguments = collect($parameters)
            ->mapWithKeys(function (ReflectionParameter $p) use ($contextfulBindings) {
                $parameterClass = $p->getType() instanceof ReflectionNamedType
                    ? $p->getType()->getName()
                    : null;

                return $parameterClass && isset($contextfulBindings[$parameterClass]) ? [
                    $p->name => $contextfulBindings[$parameterClass],
                ] : [];
            })
            ->all();

        // Symfony's container doesn't support makeWith pattern like Laravel
        // For now, create instances directly with constructor arguments
        if (empty($contextfulArguments)) {
            return $container->get($class);
        }

        // For classes with contextful bindings, instantiate directly
        // First try to get dependencies from container for non-contextful parameters
        $allArguments = [];
        foreach ($parameters as $param) {
            $paramType = $param->getType();
            $paramTypeName = $paramType instanceof ReflectionNamedType
                ? $paramType->getName()
                : null;

            if ($paramTypeName && isset($contextfulBindings[$paramTypeName])) {
                $allArguments[] = $contextfulBindings[$paramTypeName];
            } elseif ($paramTypeName && $paramType instanceof ReflectionNamedType && ! $paramType->isBuiltin() && $container->has($paramTypeName)) {
                $allArguments[] = $container->get($paramTypeName);
            } elseif ($param->isOptional()) {
                $allArguments[] = $param->getDefaultValue();
            }
        }

        return new $class(...$allArguments);
    }
}
