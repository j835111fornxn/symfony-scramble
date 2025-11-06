<?php

namespace Dedoc\Scramble\Support\InferExtensions;

use Dedoc\Scramble\Infer\Extensions\Event\FunctionCallEvent;
use Dedoc\Scramble\Infer\Extensions\Event\MethodCallEvent;
use Dedoc\Scramble\Infer\Extensions\ExpressionTypeInferExtension;
use Dedoc\Scramble\Infer\Extensions\FunctionReturnTypeExtension;
use Dedoc\Scramble\Infer\Extensions\MethodReturnTypeExtension;
use Dedoc\Scramble\Infer\Scope\Scope;
use Dedoc\Scramble\Support\Type\ArrayType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\Literal\LiteralIntegerType;
use Dedoc\Scramble\Support\Type\Literal\LiteralStringType;
use Dedoc\Scramble\Support\Type\NullType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\TypeHelper;
use PhpParser\Node\Expr;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResponseFactoryTypeInfer implements ExpressionTypeInferExtension, FunctionReturnTypeExtension, MethodReturnTypeExtension
{
    public function shouldHandle(ObjectType|string $callee): bool
    {
        // This extension is kept for backward compatibility but mainly disabled
        // In Symfony, responses are typically created directly via constructors
        return false;
    }

    public function getFunctionReturnType(FunctionCallEvent $event): ?Type
    {
        // No longer used in Symfony - responses are created directly
        return null;
    }

    public function getMethodReturnType(MethodCallEvent $event): ?Type
    {
        // No longer used in Symfony - responses are created directly
        return null;
    }

    public function getType(Expr $node, Scope $scope): ?Type
    {
        // Handle Symfony Response and JsonResponse constructors
        if (
            $node instanceof Expr\New_
            && (
                $scope->getType($node)->isInstanceOf(JsonResponse::class)
                || $scope->getType($node)->isInstanceOf(Response::class)
            )
        ) {
            /** @var ObjectType $nodeType */
            $nodeType = $scope->getType($node);

            $contentName = $nodeType->isInstanceOf(JsonResponse::class) ? 'data' : 'content';
            $contentDefaultType = $nodeType->isInstanceOf(JsonResponse::class)
                ? new ArrayType
                : new LiteralStringType('');

            return new Generic($nodeType->name, [
                TypeHelper::getArgType($scope, $node->args, [$contentName, 0], $contentDefaultType),
                TypeHelper::getArgType($scope, $node->args, ['status', 1], new LiteralIntegerType(200)),
                TypeHelper::getArgType($scope, $node->args, ['headers', 2], new ArrayType),
            ]);
        }

        return null;
    }
}
