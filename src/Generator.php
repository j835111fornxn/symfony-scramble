<?php

namespace Dedoc\Scramble;

use Closure;
use Dedoc\Scramble\Attributes\ExcludeAllRoutesFromDocs;
use Dedoc\Scramble\Attributes\ExcludeRouteFromDocs;
use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\Exceptions\RouteAware;
use Dedoc\Scramble\OpenApiVisitor\SchemaEnforceVisitor;
use Dedoc\Scramble\Support\ContainerUtils;
use Dedoc\Scramble\Support\Generator\Components;
use Dedoc\Scramble\Support\Generator\InfoObject;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Path;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Server;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\Generator\UniqueNameOptions;
use Dedoc\Scramble\Support\Generator\UniqueNamesOptionsCollection;
use Dedoc\Scramble\Support\OperationBuilder;
use Dedoc\Scramble\Support\Collection;
use Dedoc\Scramble\Support\RouteAdapter;
use Dedoc\Scramble\Support\RouteInfo;
use Dedoc\Scramble\Support\ServerFactory;
use Dedoc\Scramble\Support\SymfonyRouteManager;
use InvalidArgumentException;
use Symfony\Component\String\Slugger\AsciiSlugger;
use ReflectionException;
use ReflectionMethod;
use Throwable;

class Generator
{
    public array $exceptions = [];

    protected bool $throwExceptions = true;

    public function __construct(
        private OperationBuilder $operationBuilder,
        private SymfonyRouteManager $routeManager,
        private Infer $infer
    ) {}

    public function setThrowExceptions(bool $throwExceptions): static
    {
        $this->throwExceptions = $throwExceptions;

        return $this;
    }

    public function __invoke(?GeneratorConfig $config = null)
    {
        $config ??= Scramble::getGeneratorConfig(Scramble::DEFAULT_API);

        $openApi = $this->makeOpenApi($config);
        $context = new OpenApiContext($openApi, $config);
        $typeTransformer = $this->buildTypeTransformer($context);

        $this->getRoutes($config)
            ->map(function ($route, int $index) use ($openApi, $config, $typeTransformer) {
                try {
                    $operation = $this->routeToOperation($openApi, $route, $config, $typeTransformer);

                    if ($route->getAction('uses') instanceof Closure) {
                        $operation->setAttribute('isClosure', true);
                    }

                    $operation->setAttribute('index', $index);

                    return $operation;
                } catch (Throwable $e) {
                    if ($e instanceof RouteAware) {
                        $e->setRoute($route);
                    }

                    if (config('app.debug', false)) {
                        $method = $route->methods()[0];
                        $action = $route->getAction('uses');
                        if ($action instanceof Closure) {
                            $action = '{closure}';
                        }

                        dump("Error when analyzing route '$method $route->uri' ($action): {$e->getMessage()} – " . ($e->getFile() . ' on line ' . $e->getLine()));
                        logger()->error("Error when analyzing route '$method $route->uri' ($action): {$e->getMessage()} – " . ($e->getFile() . ' on line ' . $e->getLine()));
                    }

                    throw $e;
                }
            })
            ->filter()
            ->sortBy($this->createOperationsSorter())
            ->each(fn(Operation $operation) => $openApi->addPath(
                Path::make(
                    '/' . ltrim(
                        preg_replace('/^' . preg_quote($config->get('api_path', 'api'), '/') . '/', '', $operation->path),
                        '/'
                    )
                )->addOperation($operation)
            ));

        $this->setUniqueOperationId($openApi);

        $this->moveSameAlternativeServersToPath($openApi);

        foreach ($config->documentTransformers->all() as $openApiTransformer) {
            $openApiTransformer = is_callable($openApiTransformer)
                ? $openApiTransformer
                : ContainerUtils::makeContextable($openApiTransformer, [
                    TypeTransformer::class => $typeTransformer,
                ]);

            if (is_callable($openApiTransformer)) {
                $openApiTransformer($openApi, $context);

                continue;
            }

            if ($openApiTransformer instanceof DocumentTransformer) {
                $openApiTransformer->handle($openApi, $context);

                continue;
            }

            // @phpstan-ignore deadCode.unreachable
            throw new InvalidArgumentException('(callable(OpenApi, OpenApiContext): void)|DocumentTransformer type for document transformer expected, received ' . $openApiTransformer::class);
        }

        return $openApi->toArray();
    }

    private function createOperationsSorter(): array
    {
        $defaultSortValue = fn(Operation $o) => $o->tags[0] ?? null;

        return [
            fn(Operation $a, Operation $b) => $a->getAttribute('groupWeight', INF) <=> $b->getAttribute('groupWeight', INF),
            fn(Operation $a, Operation $b) => $a->getAttribute('weight', INF) <=> $b->getAttribute('weight', INF), // @todo manual endpoint sorting
            fn(Operation $a, Operation $b) => $defaultSortValue($a) <=> $defaultSortValue($b),
            fn(Operation $a, Operation $b) => $a->getAttribute('index', INF) <=> $b->getAttribute('index', INF),
        ];
    }

    private function makeOpenApi(GeneratorConfig $config)
    {
        $openApi = OpenApi::make('3.1.0')
            ->setComponents(new Components)
            ->setInfo(
                InfoObject::make($config->get('ui.title', $default = config('app.name')) ?: $default)
                    ->setVersion($config->get('info.version', '0.0.1'))
                    ->setDescription($config->get('info.description', ''))
            );

        [$defaultProtocol] = explode('://', url('/'));
        $servers = $config->get('servers') ?: [
            '' => ($domain = $config->get('api_domain'))
                ? $defaultProtocol . '://' . $domain . '/' . $config->get('api_path', 'api')
                : $config->get('api_path', 'api'),
        ];
        foreach ($servers as $description => $url) {
            $openApi->addServer(
                (new ServerFactory($config->serverVariables->all()))->make(url($url ?: '/'), $description)
            );
        }

        return $openApi;
    }

    private function getRoutes(GeneratorConfig $config): Collection
    {
        // Get documented routes from SymfonyRouteManager
        // This already includes @only-docs filtering and exclusion checking
        $routes = collect($this->routeManager->getDocumentedRoutes());

        // Filter routes named 'scramble.*' (internal documentation routes)
        $routes = $routes->filter(function ($route) {
            $name = $route->getName();
            return !$name || !str_starts_with($name, 'scramble.');
        });

        // Apply custom route filters from config
        if ($customFilter = $config->routes()) {
            $routes = $routes->filter($customFilter);
        }

        return $routes->values();
    }

    private function buildTypeTransformer(OpenApiContext $context): TypeTransformer
    {
        // TypeTransformer constructor signature: __construct(Infer $infer, OpenApiContext $context)
        return new TypeTransformer($this->infer, $context);
    }

    private function routeToOperation(OpenApi $openApi, RouteAdapter $route, GeneratorConfig $config, TypeTransformer $typeTransformer)
    {
        $routeInfo = new RouteInfo($route, $this->infer);

        $operation = $this->operationBuilder->build($routeInfo, $openApi, $config, $typeTransformer);

        $this->ensureSchemaTypes($route, $operation);

        return $operation;
    }

    private function ensureSchemaTypes(RouteAdapter $route, Operation $operation): void
    {
        if (! Scramble::getSchemaValidator()->hasRules()) {
            return;
        }

        [$traverser, $visitor] = $this->createSchemaEnforceTraverser($route);

        $traverser->traverse($operation, ['', 'paths', $operation->path, $operation->method]);
        $references = $visitor->popReferences();

        /** @var Reference $ref */
        foreach ($references as $ref) {
            if ($resolvedType = $ref->resolve()) {
                $traverser->traverse($resolvedType, ['', 'components', $ref->referenceType, $ref->getUniqueName()]);
            }
        }
    }

    private function createSchemaEnforceTraverser(RouteAdapter $route)
    {
        $traverser = new OpenApiTraverser([$visitor = new SchemaEnforceVisitor($route, $this->throwExceptions, $this->exceptions)]);

        return [$traverser, $visitor];
    }

    private function moveSameAlternativeServersToPath(OpenApi $openApi)
    {
        foreach (collect($openApi->paths)->groupBy('path') as $pathsGroup) {
            if ($pathsGroup->isEmpty()) {
                continue;
            }

            $operations = collect($pathsGroup->pluck('operations')->flatten());

            $operationsHaveSameAlternativeServers = $operations->count()
                && $operations->every(fn(Operation $o) => count($o->servers))
                && $operations->unique(function (Operation $o) {
                    return collect($o->servers)->map(fn(Server $s) => $s->url)->join('.');
                })->count() === 1;

            if (! $operationsHaveSameAlternativeServers) {
                continue;
            }

            $pathsGroup->every->servers($operations->first()->servers);

            foreach ($operations as $operation) {
                $operation->servers([]);
            }
        }
    }

    private function setUniqueOperationId(OpenApi $openApi)
    {
        $names = new UniqueNamesOptionsCollection;

        $this->foreachOperation($openApi, function (Operation $operation) use ($names) {
            if ($operation->operationId) {
                return;
            }

            $names->push($operation->getAttribute('operationId')); // @phpstan-ignore argument.type
        });

        $this->foreachOperation($openApi, function (Operation $operation, $index) use ($names) {
            if ($operation->operationId) {
                return;
            }

            $name = $operation->getAttribute('operationId');
            if (! $name instanceof UniqueNameOptions) {
                return;
            }

            if (! $name->eloquent && $operation->getAttribute('isClosure')) {
                return;
            }

            $operation->setOperationId($names->getUniqueName($name, function (string $fallback) use ($index) {
                return "{$fallback}_{$index}";
            }));
        });
    }

    private function foreachOperation(OpenApi $openApi, callable $callback)
    {
        foreach (collect($openApi->paths)->groupBy('path') as $pathsGroup) {
            if ($pathsGroup->isEmpty()) {
                continue;
            }

            $operations = collect($pathsGroup->pluck('operations')->flatten());

            foreach ($operations as $index => $operation) {
                $callback($operation, $index);
            }
        }
    }
}
