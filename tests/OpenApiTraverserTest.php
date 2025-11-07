<?php

namespace Dedoc\Scramble\Tests;

use Dedoc\Scramble\AbstractOpenApiVisitor;
use Dedoc\Scramble\Support\Generator\InfoObject;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Path;
use PHPUnit\Framework\Attributes\Test;

final class OpenApiTraverserTest extends SymfonyTestCase
{
    #[Test]
    public function traversesOpenApiDocument(): void
    {
        $document = new OpenApi(version: '3.1.0');
        $document->setInfo(new InfoObject(title: 'app'));
        $document->addPath($path = new Path('/test'));
        $path->addOperation(new Operation('GET'));

        $visitor = new class extends AbstractOpenApiVisitor
        {
            public array $paths = [];

            public function enter($object, $path = [])
            {
                $this->paths[] = implode('.', $path);
            }
        };

        $traverser = new \Dedoc\Scramble\OpenApiTraverser([$visitor]);

        $traverser->traverse($document);

        $this->assertSame([
            '',
            'info',
            'components',
            'paths.0',
            'paths.0.operations.GET',
        ], $visitor->paths);
    }
}
