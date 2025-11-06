<?php

namespace Dedoc\Scramble\Console\Commands;

use Dedoc\Scramble\Generator;
use Dedoc\Scramble\Scramble;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'scramble:export',
    description: 'Export the OpenAPI document to a JSON file.'
)]
class ExportDocumentation extends Command
{
    public function __construct(private readonly Generator $generator)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'The path to save the exported JSON file')
            ->addOption('api', null, InputOption::VALUE_OPTIONAL, 'The API to export a documentation for', 'default');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $api = $input->getOption('api');
        $path = $input->getOption('path');

        $config = Scramble::getGeneratorConfig($api);

        $specification = json_encode($this->generator->__invoke($config), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        /** @var string $filename */
        $filename = $path ?: $config->get('export_path') ?? 'api'.($api === 'default' ? '' : "-$api").'.json';

        file_put_contents($filename, $specification);

        $io->success("OpenAPI document exported to {$filename}.");

        return Command::SUCCESS;
    }
}
