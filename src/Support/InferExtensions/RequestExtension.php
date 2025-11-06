<?php

namespace Dedoc\Scramble\Support\InferExtensions;

use Dedoc\Scramble\Infer\Extensions\Event\MethodCallEvent;
use Dedoc\Scramble\Infer\Extensions\MethodReturnTypeExtension;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class RequestExtension implements MethodReturnTypeExtension
{
    public function __construct(
        private ?string $userClass = null
    ) {}

    public function shouldHandle(ObjectType $type): bool
    {
        return $type->isInstanceOf(Request::class);
    }

    public function getMethodReturnType(MethodCallEvent $event): ?Type
    {
        return match ($event->getName()) {
            'getUser' => new ObjectType($this->getUserClass()),
            default => null,
        };
    }

    protected function getUserClass(): string
    {
        // Use injected user class if provided
        if ($this->userClass && class_exists($this->userClass)) {
            return $this->userClass;
        }

        // Try common user class locations
        $possibleClasses = [
            'App\\Entity\\User',
            'App\\Models\\User',
            'App\\User',
        ];

        foreach ($possibleClasses as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        // Fallback to Symfony's UserInterface
        return 'Symfony\\Component\\Security\\Core\\User\\UserInterface';
    }
}
