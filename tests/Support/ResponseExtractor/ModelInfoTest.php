<?php

namespace Dedoc\Scramble\Tests\Support\ResponseExtractor;

use Dedoc\Scramble\Support\ResponseExtractor\ModelInfo;
use Dedoc\Scramble\Tests\SymfonyTestCase;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;

final class ModelInfoTest extends SymfonyTestCase
{
    #[Test]
    public function handlesModelWithoutUpdatedAtColumn(): void
    {
        $modelInfo = new ModelInfo(UserModelWithoutUpdatedAt::class);

        $modelInfo->handle();

        $this->expectNotToPerformAssertions();
    }
}

class UserModelWithoutUpdatedAt extends Model
{
    protected $table = 'users';

    const UPDATED_AT = null;
}
