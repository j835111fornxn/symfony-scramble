<?php

namespace Dedoc\Scramble\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Handles exceptions thrown during API request processing and converts them to appropriate JSON responses.
 * This provides a consistent error response format for API endpoints.
 * 
 * Note: This is separate from ExceptionToResponseExtensions which are used for static analysis
 * to generate OpenAPI documentation.
 */
class ExceptionEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ?LoggerInterface $logger = null,
        private bool $debug = false
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            // Listen to exception events with lower priority so other exception handlers can run first
            KernelEvents::EXCEPTION => ['onKernelException', -100],
        ];
    }

    /**
     * Handle exceptions and convert them to JSON responses for API requests.
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Only process exceptions for API requests
        if (! $this->shouldHandleException($request->getPathInfo(), $request->headers->get('Accept', ''))) {
            return;
        }

        $response = $this->createJsonResponseFromException($exception);
        $event->setResponse($response);

        // Log the exception handling
        $this->logger?->debug('Exception converted to JSON response', [
            'exception' => get_class($exception),
            'status_code' => $response->getStatusCode(),
        ]);
    }

    /**
     * Determine if this exception should be handled by this subscriber.
     */
    private function shouldHandleException(string $pathInfo, string $acceptHeader): bool
    {
        // Handle API routes (configurable via path prefix)
        // Or requests that explicitly want JSON
        return str_starts_with($pathInfo, '/api/') ||
            str_contains($acceptHeader, 'application/json');
    }

    /**
     * Convert an exception to a JSON response.
     */
    private function createJsonResponseFromException(\Throwable $exception): JsonResponse
    {
        $statusCode = $this->getStatusCode($exception);
        $body = $this->getResponseBody($exception);

        return new JsonResponse($body, $statusCode);
    }

    /**
     * Get the HTTP status code for an exception.
     */
    private function getStatusCode(\Throwable $exception): int
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof ValidationFailedException) {
            return Response::HTTP_UNPROCESSABLE_ENTITY;
        }

        // Check for Symfony Security exceptions if the component is installed
        $exceptionClass = get_class($exception);

        if (
            $exceptionClass === 'Symfony\Component\Security\Core\Exception\AuthenticationException' ||
            is_subclass_of($exceptionClass, 'Symfony\Component\Security\Core\Exception\AuthenticationException')
        ) {
            return Response::HTTP_UNAUTHORIZED;
        }

        if (
            $exceptionClass === 'Symfony\Component\Security\Core\Exception\AccessDeniedException' ||
            is_subclass_of($exceptionClass, 'Symfony\Component\Security\Core\Exception\AccessDeniedException')
        ) {
            return Response::HTTP_FORBIDDEN;
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    /**
     * Get the response body for an exception.
     */
    private function getResponseBody(\Throwable $exception): array
    {
        $body = [
            'message' => $exception->getMessage(),
        ];

        // Add validation errors for validation failures
        if ($exception instanceof ValidationFailedException) {
            $body['errors'] = $this->formatValidationErrors($exception);
        }

        // Add debug information in debug mode
        if ($this->debug) {
            $body['exception'] = get_class($exception);
            $body['file'] = $exception->getFile();
            $body['line'] = $exception->getLine();
            $body['trace'] = $exception->getTraceAsString();
        }

        return $body;
    }

    /**
     * Format validation errors from ValidationFailedException.
     */
    private function formatValidationErrors(ValidationFailedException $exception): array
    {
        $violations = $exception->getViolations();
        $errors = [];

        foreach ($violations as $violation) {
            $propertyPath = $violation->getPropertyPath();
            $errors[$propertyPath][] = $violation->getMessage();
        }

        return $errors;
    }
}
