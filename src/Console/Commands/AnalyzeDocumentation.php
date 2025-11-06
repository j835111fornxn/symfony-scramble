<?php

namespace Dedoc\Scramble\Console\Commands;

use Dedoc\Scramble\Console\Commands\Components\TermsOfContentItem;
use Dedoc\Scramble\Exceptions\ConsoleRenderable;
use Dedoc\Scramble\Exceptions\RouteAware;
use Dedoc\Scramble\Generator;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Collection;
use Dedoc\Scramble\Support\RouteAdapter;
use Dedoc\Scramble\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'scramble:analyze',
    description: 'Analyzes the documentation generation process to surface any issues.'
)]
class AnalyzeDocumentation extends Command
{
    public function __construct(private readonly Generator $generator)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('api', null, InputOption::VALUE_OPTIONAL, 'The API to analyze', 'default');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $api = $input->getOption('api');

        $this->generator->setThrowExceptions(false);
        $this->generator->__invoke(Scramble::getGeneratorConfig($api));

        $i = 1;
        $this->groupExceptions($this->generator->exceptions)->each(function (Collection $exceptions, string $group) use (&$i, $output) {
            $this->renderExceptionsGroup($exceptions, $group, $i, $output);
        });

        if (count($this->generator->exceptions)) {
            $io->error('[ERROR] Found ' . count($this->generator->exceptions) . ' errors.');

            return Command::FAILURE;
        }

        $io->success('Everything is fine! Documentation is generated without any errors 🍻');

        return Command::SUCCESS;
    }

    /**
     * @return Collection<string, Collection<int, Throwable>>
     */
    private function groupExceptions(array $exceptions): Collection
    {
        return collect($exceptions)
            ->groupBy(fn($e) => $e instanceof RouteAware ? $this->getRouteKey($e->getRoute()) : '');
    }

    /**
     * @param  Collection<int, Throwable>  $exceptions
     */
    private function renderExceptionsGroup(Collection $exceptions, string $group, int &$i, OutputInterface $output): void
    {
        // when route key is set, then the exceptions in the group are route aware.
        if ($group) {
            $this->renderRouteExceptionsGroupLine($exceptions, $output);
        }

        $exceptions->each(function ($exception) use (&$i, $output) {
            $this->renderException($exception, $i, $output);
            $i++;
            $output->writeln('');
        });
    }

    private function getRouteKey(?RouteAdapter $route): string
    {
        if (! $route) {
            return '';
        }

        $methods = $route->getMethods();
        $method = $methods[0] ?? 'GET';
        $action = $route->getActionName();

        return "$method.$action";
    }

    /**
     * @param  Collection<int, RouteAware>  $exceptions
     */
    private function renderRouteExceptionsGroupLine(Collection $exceptions, OutputInterface $output): void
    {
        $firstException = $exceptions->first();
        $route = $firstException->getRoute();

        $methods = $route->getMethods();
        $method = $methods[0] ?? 'GET';
        $count = $exceptions->count();
        $errorsMessage = $count . ' ' . ($count === 1 ? 'error' : 'errors');

        $tocComponent = new TermsOfContentItem(
            right: '<options=bold;fg=' . $this->getHttpMethodColor($method) . '>' . $method . "</> " . $route->getPath() . " <fg=red>$errorsMessage</>",
            left: $this->getRouteAction($route),
        );

        $tocComponent->render($output);

        $output->writeln('');
    }

    private function getHttpMethodColor(string $method): string
    {
        return match ($method) {
            'POST', 'PUT' => 'blue',
            'DELETE' => 'red',
            default => 'yellow',
        };
    }

    public function getRouteAction(?RouteAdapter $route): ?string
    {
        if (! $route) {
            return null;
        }

        $action = $route->getActionName();

        if (! $action || ! str_contains($action, '@')) {
            return null;
        }

        $parts = explode('@', $action);
        if (count($parts) !== 2 || ! method_exists($parts[0], $parts[1])) {
            return null;
        }

        [$class, $method] = $parts;

        $eloquentClassName = Str::replace(['App\Http\Controllers\\', 'App\Http\\'], '', $class);

        return "<fg=gray>{$eloquentClassName}@{$method}</>";
    }

    private function renderException(Throwable $exception, int $i, OutputInterface $output): void
    {
        $message = Str::replace('Dedoc\Scramble\Support\Generator\Types\\', '', property_exists($exception, 'originalMessage') ? $exception->originalMessage : $exception->getMessage()); // @phpstan-ignore property.notFound

        $output->writeln("<options=bold>$i. {$message}</>");

        if ($exception instanceof ConsoleRenderable) {
            $exception->renderInConsole($output);
        }
    }
}
