<?php

namespace Dedoc\Scramble\Tests\DocumentTransformers;

use Dedoc\Scramble\Tests\Fixtures\Laravel\Models\SampleUserModel;
use Dedoc\Scramble\Tests\Support\AnalysisHelpers;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CleanupUnusedResponseReferencesTransformerTest extends TestCase
{
    use AnalysisHelpers;

    #[Test]
    public function doesntCauseFailureOfResponseSerializationWhenReferenceIsRemoved(): void
    {
        $openApiDocument = $this->generateForRoute(fn () => Route::get('test/{user}', CleanupUnusedResponseReferencesTransformerTest_ControllerA::class));

        $responses = $openApiDocument['paths']['/test/{user}']['get']['responses'];
        $this->assertArrayHasKey(200, $responses);
        $this->assertArrayHasKey(404, $responses);
    }
}

class CleanupUnusedResponseReferencesTransformerTest_ControllerA
{
    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function __invoke(SampleUserModel $user) {}
}
