<?php

namespace Dedoc\Scramble\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber to restrict access to Scramble documentation pages.
 *
 * Replaces Laravel middleware RestrictedDocsAccess with Symfony event system.
 * Checks environment and optionally custom authorization callback.
 */
class DocumentationAccessSubscriber implements EventSubscriberInterface
{
    private const DOCS_ROUTE_PREFIX = '/docs/api';

    /** @var callable|null */
    private $authorizationCallback;

    public function __construct(
        private readonly string $environment,
        ?callable $authorizationCallback = null,
    ) {
        $this->authorizationCallback = $authorizationCallback;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    /**
     * Check access control for documentation routes.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Only check documentation routes
        if (!$this->isDocumentationRoute($request->getPathInfo())) {
            return;
        }

        // Allow access in local/dev environment
        if ($this->environment === 'dev' || $this->environment === 'local') {
            return;
        }

        // Check authorization using custom callback if provided
        if ($this->authorizationCallback !== null) {
            $isAuthorized = call_user_func($this->authorizationCallback, $request);
            if ($isAuthorized) {
                return;
            }
        }

        // Deny access
        throw new AccessDeniedHttpException(
            'Access to API documentation is restricted.'
        );
    }

    /**
     * Check if the given path is a documentation route.
     */
    private function isDocumentationRoute(string $path): bool
    {
        // Check if path starts with documentation route prefix
        // This will match routes defined in bundle configuration
        return str_starts_with($path, self::DOCS_ROUTE_PREFIX);
    }
}
