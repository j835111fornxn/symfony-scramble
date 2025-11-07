<?php

namespace Dedoc\Scramble\Tests;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\LengthRequiredHttpException;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validation;

final class ErrorsResponsesTest extends SymfonyTestCase
{
    use MatchesSnapshots;

    public function testAddsValidationErrorResponse(): void
    {
        $this->addRoute('/api/test', [ErrorsResponsesTest_Controller::class, 'adds_validation_error_response']);

        Scramble::routes(fn (\Symfony\Component\Routing\Route $r) => $r->getPath() === '/api/test');
        $openApiDocument = $this->get(\Dedoc\Scramble\Generator::class)();

        $this->assertMatchesSnapshot($openApiDocument);
    }

    public function testAddsValidationErrorResponseWithFacadeMadeValidators(): void
    {
        $this->addRoute('/api/test', [ErrorsResponsesTest_Controller::class, 'adds_validation_error_response_with_facade_made_validators']);

        Scramble::routes(fn (\Symfony\Component\Routing\Route $r) => $r->getPath() === '/api/test');
        $openApiDocument = $this->get(\Dedoc\Scramble\Generator::class)();

        $this->assertMatchesSnapshot($openApiDocument);
    }

    public function testAddsErrorsResponsesWithCustomRequests(): void
    {
        $this->addRoute('/api/test', [ErrorsResponsesTest_Controller::class, 'adds_errors_with_custom_request']);

        Scramble::routes(fn (\Symfony\Component\Routing\Route $r) => $r->getPath() === '/api/test');
        $openApiDocument = $this->get(\Dedoc\Scramble\Generator::class)();

        $this->assertMatchesSnapshot($openApiDocument);
    }

    public function testDoesntAddErrorsWithCustomRequestWhenErrorsProducingMethodsAreNotDefined(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ErrorsResponsesTest_Controller::class, 'doesnt_add_errors_with_custom_request_when_errors_producing_methods_not_defined']);
        });

        $this->assertArrayHasKey(200, $openApiDocument['paths']['/test']['get']['responses']);
        $this->assertCount(1, $openApiDocument['paths']['/test']['get']['responses']);
    }

    public function testAddsAuthorizationErrorResponse(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ErrorsResponsesTest_Controller::class, 'adds_authorization_error_response']);
        });

        $this->assertMatchesSnapshot($openApiDocument);
    }

    public function testAddsAuthorizationErrorResponseForGateAuthorize(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ErrorsResponsesTest_Controller::class, 'adds_authorization_error_response_gate']);
        });

        $this->assertMatchesSnapshot($openApiDocument);
    }

    public function testAddsAuthenticationErrorResponse(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test', [ErrorsResponsesTest_Controller::class, 'adds_authorization_error_response'], ['GET'], [
                'middleware' => ['auth'],
            ]);
        });

        $this->assertArrayHasKey('components', $openApiDocument);
        $this->assertArrayHasKey('responses', $openApiDocument['components']);
        $this->assertArrayHasKey('AuthenticationException', $openApiDocument['components']['responses']);
        $this->assertEquals([
            '$ref' => '#/components/responses/AuthenticationException',
        ], $openApiDocument['paths']['/test']['get']['responses'][401]);
    }

    public function testAddsNotFoundErrorResponseWithCanDirective(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test/{user}', [ErrorsResponsesTest_Controller::class, 'adds_not_found_error_response'], ['GET'], [
                'middleware' => ['can:update,post'],
            ]);
        });

        $this->assertMatchesSnapshot($openApiDocument);
    }

    public function testAddsNotFoundErrorResponseWithAuthorizeUsing(): void
    {
        $openApiDocument = $this->generateForRoute(function () {
            return $this->addRoute('/api/test/{user}', [ErrorsResponsesTest_Controller::class, 'adds_not_found_error_response'], ['GET'], [
                'middleware' => [Authorize::using('update', 'post')],
            ]);
        });

        $this->assertMatchesSnapshot($openApiDocument);
    }

    public function testAddsValidationErrorResponseWhenDocumentedInPhpdoc(): void
    {
        $this->addRoute('/api/test', [ErrorsResponsesTest_Controller::class, 'phpdoc_exception_response']);

        Scramble::routes(fn (\Symfony\Component\Routing\Route $r) => $r->getPath() === '/api/test');
        $openApiDocument = $this->get(\Dedoc\Scramble\Generator::class)();

        $this->assertMatchesSnapshot($openApiDocument);
    }

    public function testAddsHttpErrorResponseExceptionExtendingHTTPExceptionIsThrown(): void
    {
        $openApiDocument = $this->generateForRoute(fn () => $this->addRoute('/api/test', [ErrorsResponsesTest_Controller::class, 'custom_exception_response']));

        $this->assertArrayHasKey('content', $openApiDocument['paths']['/test']['get']['responses'][409]);
        $this->assertArrayHasKey('application/json', $openApiDocument['paths']['/test']['get']['responses'][409]['content']);
        $this->assertEquals('object', $openApiDocument['paths']['/test']['get']['responses'][409]['content']['application/json']['schema']['type']);
    }

    public function testAddsHttpErrorResponseExceptionExtendingSymphonyHTTPExceptionIsThrown(): void
    {
        $openApiDocument = $this->generateForRoute(fn () => $this->addRoute('/api/test', [ErrorsResponsesTest_Controller::class, 'symfony_http_exception_response']));

        // AccessDeniedHttpException
        $this->assertArrayHasKey('content', $openApiDocument['paths']['/test']['get']['responses'][403]);
        $this->assertEquals('object', $openApiDocument['paths']['/test']['get']['responses'][403]['content']['application/json']['schema']['type']);

        // BadRequestHttpException
        $this->assertArrayHasKey('content', $openApiDocument['paths']['/test']['get']['responses'][400]);
        $this->assertEquals('object', $openApiDocument['paths']['/test']['get']['responses'][400]['content']['application/json']['schema']['type']);

        // ConflictHttpException
        $this->assertArrayHasKey('content', $openApiDocument['paths']['/test']['get']['responses'][409]);
        $this->assertEquals('object', $openApiDocument['paths']['/test']['get']['responses'][409]['content']['application/json']['schema']['type']);

        // GoneHttpException
        $this->assertArrayHasKey('content', $openApiDocument['paths']['/test']['get']['responses'][410]);
        $this->assertEquals('object', $openApiDocument['paths']['/test']['get']['responses'][410]['content']['application/json']['schema']['type']);

        // LengthRequiredHttpException
        $this->assertArrayHasKey('content', $openApiDocument['paths']['/test']['get']['responses'][411]);
        $this->assertEquals('object', $openApiDocument['paths']['/test']['get']['responses'][411]['content']['application/json']['schema']['type']);

        // LockedHttpException
        $this->assertArrayHasKey('content', $openApiDocument['paths']['/test']['get']['responses'][423]);
        $this->assertEquals('object', $openApiDocument['paths']['/test']['get']['responses'][423]['content']['application/json']['schema']['type']);

        // MethodNotAllowedHttpException
        $this->assertArrayHasKey('content', $openApiDocument['paths']['/test']['get']['responses'][405]);
        $this->assertEquals('object', $openApiDocument['paths']['/test']['get']['responses'][405]['content']['application/json']['schema']['type']);

        // NotAcceptableHttpException
        $this->assertArrayHasKey('content', $openApiDocument['paths']['/test']['get']['responses'][406]);
        $this->assertEquals('object', $openApiDocument['paths']['/test']['get']['responses'][406]['content']['application/json']['schema']['type']);

        // PreconditionFailedHttpException
        $this->assertArrayHasKey('content', $openApiDocument['paths']['/test']['get']['responses'][412]);
        $this->assertEquals('object', $openApiDocument['paths']['/test']['get']['responses'][412]['content']['application/json']['schema']['type']);

        // PreconditionRequiredHttpException
        $this->assertArrayHasKey('content', $openApiDocument['paths']['/test']['get']['responses'][428]);
        $this->assertEquals('object', $openApiDocument['paths']['/test']['get']['responses'][428]['content']['application/json']['schema']['type']);

        // ServiceUnavailableHttpException
        $this->assertArrayHasKey('content', $openApiDocument['paths']['/test']['get']['responses'][503]);
        $this->assertEquals('object', $openApiDocument['paths']['/test']['get']['responses'][503]['content']['application/json']['schema']['type']);

        // TooManyRequestsHttpException
        $this->assertArrayHasKey('content', $openApiDocument['paths']['/test']['get']['responses'][429]);
        $this->assertEquals('object', $openApiDocument['paths']['/test']['get']['responses'][429]['content']['application/json']['schema']['type']);

        // UnauthorizedHttpException
        $this->assertArrayHasKey('content', $openApiDocument['paths']['/test']['get']['responses'][401]);
        $this->assertEquals('object', $openApiDocument['paths']['/test']['get']['responses'][401]['content']['application/json']['schema']['type']);

        // UnprocessableEntityHttpException
        $this->assertArrayHasKey('content', $openApiDocument['paths']['/test']['get']['responses'][422]);
        $this->assertEquals('object', $openApiDocument['paths']['/test']['get']['responses'][422]['content']['application/json']['schema']['type']);

        // UnsupportedMediaTypeHttpException
        $this->assertArrayHasKey('content', $openApiDocument['paths']['/test']['get']['responses'][415]);
        $this->assertEquals('object', $openApiDocument['paths']['/test']['get']['responses'][415]['content']['application/json']['schema']['type']);
    }
}

class ErrorsResponsesTest_Controller
{
    public function adds_validation_error_response(Request $request)
    {
        // Symfony validation equivalent
        $validator = Validation::createValidator();
        $violations = $validator->validate($request->request->all(), new Assert\Collection([
            'foo' => new Assert\NotBlank(),
        ]));

        if (count($violations) > 0) {
            throw new ValidationFailedException(null, $violations);
        }
    }

    public function adds_validation_error_response_with_facade_made_validators(Request $request)
    {
        // Symfony validation equivalent
        $validator = Validation::createValidator();
        $violations = $validator->validate($request->request->all(), new Assert\Collection([
            'foo' => new Assert\NotBlank(),
        ]));

        if (count($violations) > 0) {
            throw new ValidationFailedException(null, $violations);
        }
    }

    public function adds_errors_with_custom_request(ErrorsResponsesTest_Controller_CustomRequest $request) {}

    public function doesnt_add_errors_with_custom_request_when_errors_producing_methods_not_defined(ErrorsResponsesTest_Controller_CustomRequestWithoutErrorCreatingMethods $request) {}

    public function adds_authorization_error_response(Request $request)
    {
        throw new AccessDeniedException('This action is unauthorized.');
    }

    public function adds_authorization_error_response_gate()
    {
        throw new AccessDeniedException('This action is unauthorized.');
    }

    public function adds_authentication_error_response(Request $request) {}

    public function adds_not_found_error_response(Request $request, UserModel_ErrorsResponsesTest $user) {}

    /**
     * @throws \Symfony\Component\Validator\Exception\ValidationFailedException
     */
    public function phpdoc_exception_response(Request $request) {}

    public function custom_exception_response(Request $request)
    {
        throw new BusinessException('The business error');
    }

    /**
     * @throws AccessDeniedHttpException|BadRequestHttpException|ConflictHttpException|GoneHttpException|LengthRequiredHttpException|LockedHttpException|MethodNotAllowedHttpException|NotAcceptableHttpException|PreconditionFailedHttpException|PreconditionRequiredHttpException|ServiceUnavailableHttpException|TooManyRequestsHttpException|UnauthorizedHttpException|UnprocessableEntityHttpException|UnsupportedMediaTypeHttpException
     */
    public function symfony_http_exception_response(Request $request) {}
}

class BusinessException extends \Symfony\Component\HttpKernel\Exception\HttpException
{
    public function __construct(string $message = '', ?\Throwable $previous = null, array $headers = [], int $code = 0)
    {
        parent::__construct(409, $message, $previous, $headers, $code);
    }
}

// Simple DTO/Entity for testing - Symfony doesn't require extending a base class
class UserModel_ErrorsResponsesTest
{
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}

// Symfony Request with validation constraints
class ErrorsResponsesTest_Controller_CustomRequest extends Request
{
    public function authorize(): bool
    {
        return something();
    }

    public function getValidationRules(): array
    {
        return [
            'foo' => [new Assert\NotBlank()],
        ];
    }
}

class ErrorsResponsesTest_Controller_CustomRequestWithoutErrorCreatingMethods extends Request {}
