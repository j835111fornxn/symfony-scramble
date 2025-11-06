<?php

namespace Tests\EventSubscriber;

use Dedoc\Scramble\EventSubscriber\ExceptionEventSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ExceptionEventSubscriberTest extends TestCase
{
    private ExceptionEventSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new ExceptionEventSubscriber(logger: null, debug: false);
    }

    /** @test */
    public function it_handles_api_route_exceptions(): void
    {
        $request = Request::create('/api/users', 'GET');
        $exception = new NotFoundHttpException('User not found');

        $event = $this->createExceptionEvent($request, $exception);
        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertSame('User not found', $content['message']);
    }

    /** @test */
    public function it_handles_json_accept_header(): void
    {
        $request = Request::create('/some/path', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $exception = new \RuntimeException('Something went wrong');

        $event = $this->createExceptionEvent($request, $exception);
        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(500, $response->getStatusCode());
    }

    /** @test */
    public function it_ignores_non_api_routes(): void
    {
        $request = Request::create('/web/page', 'GET');
        $exception = new \RuntimeException('Error');

        $event = $this->createExceptionEvent($request, $exception);
        $this->subscriber->onKernelException($event);

        // Should not set a response for non-API routes
        $this->assertNull($event->getResponse());
    }

    /** @test */
    public function it_formats_validation_exceptions(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('Email is required', null, [], null, 'email', null),
            new ConstraintViolation('Name is too short', null, [], null, 'name', null),
        ]);

        $exception = new ValidationFailedException('value', $violations);
        $request = Request::create('/api/users', 'POST');

        $event = $this->createExceptionEvent($request, $exception);
        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();

        $this->assertSame(422, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $content);
        $this->assertArrayHasKey('email', $content['errors']);
        $this->assertArrayHasKey('name', $content['errors']);
    }

    /** @test */
    public function it_includes_debug_info_when_debug_is_enabled(): void
    {
        $subscriber = new ExceptionEventSubscriber(logger: null, debug: true);
        $request = Request::create('/api/test', 'GET');
        $exception = new \RuntimeException('Test error');

        $event = $this->createExceptionEvent($request, $exception);
        $subscriber->onKernelException($event);

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('exception', $content);
        $this->assertArrayHasKey('file', $content);
        $this->assertArrayHasKey('line', $content);
        $this->assertArrayHasKey('trace', $content);
    }

    /** @test */
    public function it_returns_correct_status_codes_for_http_exceptions(): void
    {
        $exceptions = [
            [new NotFoundHttpException('Not found'), 404],
            [new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Bad request'), 400],
            [new \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException('auth', 'Unauthorized'), 401],
        ];

        foreach ($exceptions as [$exception, $expectedStatus]) {
            $request = Request::create('/api/test', 'GET');
            $event = $this->createExceptionEvent($request, $exception);

            $this->subscriber->onKernelException($event);

            $response = $event->getResponse();
            $this->assertSame($expectedStatus, $response->getStatusCode());
        }
    }

    private function createExceptionEvent(Request $request, \Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );
    }
}
