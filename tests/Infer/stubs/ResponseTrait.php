<?php

namespace Dedoc\Scramble\Tests\Infer\stubs;

use Symfony\Component\HttpFoundation\Response;

trait ResponseTrait
{
    public function foo()
    {
        return Response::HTTP_CONTINUE;
    }
}
