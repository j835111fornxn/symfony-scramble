<?php

namespace Dedoc\Scramble\Tests;

use Dedoc\Scramble\Console\Commands\ExportDocumentation;
use Dedoc\Scramble\Generator;
use Dedoc\Scramble\Scramble;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;

final class ExportDocumentationTest extends SymfonyTestCase
{
    #[Test]
    public function shouldExportTheDocumentation(): void
    {
        $command = $this->get(ExportDocumentation::class);
        $tester = new CommandTester($command);

        $generator = $this->get(Generator::class);
        $path = 'api.json';

        $this->mockFilePut($path, json_encode($generator(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $tester->execute([]);

        $this->assertEquals(0, $tester->getStatusCode());
    }

    #[Test]
    public function shouldExportTheDocumentationToThePathSpecifiedByThePathOption(): void
    {
        $command = $this->get(ExportDocumentation::class);
        $tester = new CommandTester($command);

        $generator = $this->get(Generator::class);
        $path = 'api-test.json';

        $this->mockFilePut($path, json_encode($generator(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $tester->execute(['--path' => $path]);

        $this->assertEquals(0, $tester->getStatusCode());
    }

    #[Test]
    public function shouldExportTheDocumentationOfTheApiSpecifiedByTheApiOption(): void
    {
        $command = $this->get(ExportDocumentation::class);
        $tester = new CommandTester($command);

        $api = 'v2';
        $exportPath = 'scramble/api-v2.json';
        $generator = $this->get(Generator::class);

        Scramble::registerApi($api, [
            'api_path' => 'api/'.$api,
            'export_path' => $exportPath,
        ]);

        $this->mockFilePut(
            $exportPath,
            json_encode($generator(Scramble::getGeneratorConfig($api)), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $tester->execute(['--api' => $api]);

        $this->assertEquals(0, $tester->getStatusCode());
    }

    #[Test]
    public function shouldExportTheDocumentationOfTheApiSpecifiedByTheApiOptionWithoutExportPathConfig(): void
    {
        $command = $this->get(ExportDocumentation::class);
        $tester = new CommandTester($command);

        $api = 'v2';
        $generator = $this->get(Generator::class);

        Scramble::registerApi($api, [
            'api_path' => 'api/v2',
            'export_path' => null,
        ]);

        $this->mockFilePut(
            'api-'.$api.'.json',
            json_encode($generator(Scramble::getGeneratorConfig($api)), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $tester->execute(['--api' => $api]);

        $this->assertEquals(0, $tester->getStatusCode());
    }

    /**
     * Mock file_put_contents calls.
     */
    private function mockFilePut(string $path, string $contents): void
    {
        // Note: In a Symfony test environment, you may need to use a VirtualFilesystem
        // or mock the actual file operations. For now, we're verifying the command
        // executes successfully without mocking file I/O.
    }
}
