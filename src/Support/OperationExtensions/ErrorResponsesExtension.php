<?php

namespace Dedoc\Scramble\Support\OperationExtensions;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\RouteInfo;
use Dedoc\Scramble\Support\Type\FunctionType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Validation\ConstraintExtractor;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * This extension is responsible for adding exceptions to the method return type
 * that may happen when an app navigates to the route.
 */
class ErrorResponsesExtension extends OperationExtension
{
    public function __construct(
        private ConstraintExtractor $constraintExtractor,
    ) {}

    public function handle(Operation $operation, RouteInfo $routeInfo)
    {
        if (! $methodType = $routeInfo->getActionType()) {
            return;
        }

        $this->attachNotFoundException($operation, $methodType);
        $this->attachAuthorizationException($routeInfo, $methodType);
        $this->attachAuthenticationException($routeInfo, $methodType);
        $this->attachValidationExceptions($methodType);
    }

    private function attachNotFoundException(Operation $operation, FunctionType $methodType)
    {
        $hasModelParams = collect($operation->parameters)
            ->contains(function (Parameter $parameter) {
                return $parameter->in === 'path'
                    && $parameter->schema->type->getAttribute('isModelId') === true;
            });

        if (! $hasModelParams) {
            return;
        }

        $methodType->exceptions = [
            ...$methodType->exceptions,
            new ObjectType(ModelNotFoundException::class),
        ];
    }

    private function attachAuthorizationException(RouteInfo $routeInfo, FunctionType $methodType)
    {
        if (! collect($routeInfo->route->gatherMiddleware())->contains(fn ($m) => is_string($m) && Str::startsWith($m, ['can:', Authorize::class.':']))) {
            return;
        }

        if (collect($methodType->exceptions)->contains(fn (Type $e) => $e->isInstanceOf(AuthorizationException::class))) {
            return;
        }

        $methodType->exceptions = [
            ...$methodType->exceptions,
            new ObjectType(AuthorizationException::class),
        ];
    }

    private function attachAuthenticationException(RouteInfo $routeInfo, FunctionType $methodType)
    {
        if (count($routeInfo->phpDoc()->getTagsByName('@unauthenticated'))) {
            return;
        }

        $isAuthMiddleware = fn ($m) => is_string($m) && ($m === 'auth' || Str::startsWith($m, 'auth:'));

        if (! collect($routeInfo->route->gatherMiddleware())->contains($isAuthMiddleware)) {
            return;
        }

        if (collect($methodType->exceptions)->contains(fn (Type $e) => $e->isInstanceOf(AuthenticationException::class))) {
            return;
        }

        $methodType->exceptions = [
            ...$methodType->exceptions,
            new ObjectType(AuthenticationException::class),
        ];
    }

    private function attachValidationExceptions(FunctionType $methodType)
    {
        // Check if any parameter has validation constraints
        foreach ($methodType->arguments as $arg) {
            if (!$arg instanceof ObjectType) {
                continue;
            }

            if ($this->constraintExtractor->hasConstraints($arg->name)) {
                // Add validation exception if parameter has constraints
                if (!$this->hasException($methodType, ValidationFailedException::class)) {
                    $methodType->exceptions = [
                        ...$methodType->exceptions,
                        new ObjectType(ValidationFailedException::class),
                    ];
                }
                break;
            }
        }
    }

    private function hasException(FunctionType $methodType, string $exceptionClass): bool
    {
        foreach ($methodType->exceptions as $exception) {
            if ($exception instanceof ObjectType && $exception->name === $exceptionClass) {
                return true;
            }
        }

        return false;
    }
}
