# Symfony-Scramble 實施指南
## 詳細實施步驟與代碼範例

**文件版本**: 1.0  
**建立日期**: 2025-11-04

---

## 一、開發環境準備

### 1.1 必要工具

```bash
# PHP 8.1+
php --version

# Composer
composer --version

# Git
git --version
```

### 1.2 開發依賴

```json
{
    "require": {
        "php": "^8.1",
        "nikic/php-parser": "^5.0",
        "phpstan/phpdoc-parser": "^1.0|^2.0",
        "symfony/http-kernel": "^6.0|^7.0",
        "symfony/routing": "^6.0|^7.0",
        "symfony/dependency-injection": "^6.0|^7.0",
        "symfony/config": "^6.0|^7.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0|^11.0",
        "symfony/framework-bundle": "^6.0|^7.0",
        "doctrine/orm": "^2.0|^3.0",
        "phpstan/phpstan": "^1.0"
    }
}
```

### 1.3 目錄結構初始化

```bash
cd /path/to/symfony-scramble

# 創建新目錄結構
mkdir -p src/Core/{Generator,TypeInference,OpenApi}
mkdir -p src/Abstractions
mkdir -p src/Laravel/{TypeExtensions,Middleware}
mkdir -p src/Symfony/{TypeExtensions,EventSubscriber,DependencyInjection,Controller,Resources/config}
mkdir -p tests/{Core,Laravel,Symfony}
mkdir -p docs/Plan

# 創建測試配置
mkdir -p tests/Fixtures/{Laravel,Symfony}
```

---

## 二、階段一：核心抽象層（第 1-2 週）

### 2.1 步驟 1: 創建基礎接口

#### 文件: `src/Abstractions/RouteExtractorInterface.php`

```php
<?php

namespace Dedoc\Scramble\Abstractions;

interface RouteExtractorInterface
{
    /**
     * 提取所有 API 路由
     * 
     * @param array $config 配置數組
     * @return RouteInfo[] 路由信息數組
     */
    public function extractRoutes(array $config): array;
    
    /**
     * 獲取路由的 HTTP 方法
     * 
     * @param object $route 框架特定的路由對象
     * @return string[] HTTP 方法數組
     */
    public function getMethods(object $route): array;
    
    /**
     * 獲取路由 URI
     * 
     * @param object $route 框架特定的路由對象
     * @return string URI 路徑
     */
    public function getUri(object $route): string;
    
    /**
     * 獲取路由處理器信息
     * 
     * @param object $route 框架特定的路由對象
     * @return HandlerInfo 處理器信息
     */
    public function getHandler(object $route): HandlerInfo;
    
    /**
     * 獲取路由中介層
     * 
     * @param object $route 框架特定的路由對象
     * @return array 中介層類名數組
     */
    public function getMiddleware(object $route): array;
    
    /**
     * 獲取路由元數據
     * 
     * @param object $route 框架特定的路由對象
     * @return array 元數據數組
     */
    public function getMetadata(object $route): array;
}
```

#### 文件: `src/Abstractions/HandlerInfo.php`

```php
<?php

namespace Dedoc\Scramble\Abstractions;

/**
 * 處理器信息統一表示
 */
class HandlerInfo
{
    public function __construct(
        public readonly ?string $className,
        public readonly ?string $methodName,
        public readonly ?callable $closure = null,
        public readonly array $parameters = [],
    ) {}
    
    public static function fromClassMethod(string $class, string $method): self
    {
        return new self(
            className: $class,
            methodName: $method,
        );
    }
    
    public static function fromClosure(callable $closure): self
    {
        return new self(
            className: null,
            methodName: null,
            closure: $closure,
        );
    }
    
    public static function fromCallable(mixed $callable): self
    {
        if (is_string($callable) && str_contains($callable, '::')) {
            [$class, $method] = explode('::', $callable, 2);
            return self::fromClassMethod($class, $method);
        }
        
        if (is_array($callable) && count($callable) === 2) {
            [$object, $method] = $callable;
            $class = is_object($object) ? get_class($object) : $object;
            return self::fromClassMethod($class, $method);
        }
        
        if (is_callable($callable)) {
            return self::fromClosure($callable);
        }
        
        throw new \InvalidArgumentException('Invalid callable provided');
    }
    
    public function isClosure(): bool
    {
        return $this->closure !== null;
    }
    
    public function isClassMethod(): bool
    {
        return $this->className !== null && $this->methodName !== null;
    }
    
    public function getFullyQualifiedName(): string
    {
        if ($this->isClosure()) {
            return '{closure}';
        }
        
        return "{$this->className}::{$this->methodName}";
    }
    
    public function getReflection(): \ReflectionFunctionAbstract
    {
        if ($this->isClosure()) {
            return new \ReflectionFunction($this->closure);
        }
        
        if ($this->isClassMethod()) {
            return new \ReflectionMethod($this->className, $this->methodName);
        }
        
        throw new \RuntimeException('Cannot get reflection for handler');
    }
}
```

#### 文件: `src/Abstractions/RouteInfo.php`

```php
<?php

namespace Dedoc\Scramble\Abstractions;

/**
 * 框架無關的路由信息
 */
class RouteInfo
{
    public function __construct(
        public readonly string $uri,
        public readonly array $methods,
        public readonly HandlerInfo $handler,
        public readonly array $middleware = [],
        public readonly array $metadata = [],
        public readonly ?object $originalRoute = null,
    ) {}
    
    /**
     * 獲取主要 HTTP 方法（通常是第一個）
     */
    public function getMethod(): string
    {
        return $this->methods[0] ?? 'GET';
    }
    
    /**
     * 檢查是否有特定中介層
     */
    public function hasMiddleware(string $middleware): bool
    {
        return in_array($middleware, $this->middleware, true);
    }
    
    /**
     * 獲取元數據值
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }
    
    /**
     * 獲取路由名稱
     */
    public function getName(): ?string
    {
        return $this->metadata['name'] ?? null;
    }
    
    /**
     * 生成唯一識別符
     */
    public function getIdentifier(): string
    {
        return md5($this->getMethod() . ':' . $this->uri);
    }
}
```

### 2.2 步驟 2: 創建參數相關類

#### 文件: `src/Abstractions/Parameter.php`

```php
<?php

namespace Dedoc\Scramble\Abstractions;

/**
 * API 參數定義
 */
class Parameter
{
    public function __construct(
        public readonly string $name,
        public readonly string $in, // 'path', 'query', 'header', 'cookie'
        public readonly string $type = 'string',
        public readonly bool $required = false,
        public readonly ?string $description = null,
        public readonly mixed $default = null,
        public readonly ?string $format = null,
        public readonly ?array $enum = null,
        public readonly ?array $example = null,
        public readonly array $validation = [],
    ) {}
    
    /**
     * 轉換為 OpenAPI 數組格式
     */
    public function toOpenApiArray(): array
    {
        $schema = [
            'type' => $this->type,
        ];
        
        if ($this->format !== null) {
            $schema['format'] = $this->format;
        }
        
        if ($this->enum !== null) {
            $schema['enum'] = $this->enum;
        }
        
        if ($this->default !== null) {
            $schema['default'] = $this->default;
        }
        
        if ($this->example !== null) {
            $schema['example'] = $this->example;
        }
        
        // 添加驗證約束到 schema
        foreach ($this->validation as $rule => $value) {
            match($rule) {
                'min' => $schema['minimum'] = $value,
                'max' => $schema['maximum'] = $value,
                'minLength' => $schema['minLength'] = $value,
                'maxLength' => $schema['maxLength'] = $value,
                'pattern' => $schema['pattern'] = $value,
                default => null,
            };
        }
        
        $parameter = [
            'name' => $this->name,
            'in' => $this->in,
            'required' => $this->required,
            'schema' => $schema,
        ];
        
        if ($this->description !== null) {
            $parameter['description'] = $this->description;
        }
        
        return $parameter;
    }
    
    /**
     * 創建路徑參數
     */
    public static function path(
        string $name,
        string $type = 'string',
        ?string $description = null
    ): self {
        return new self(
            name: $name,
            in: 'path',
            type: $type,
            required: true, // 路徑參數總是必需的
            description: $description,
        );
    }
    
    /**
     * 創建查詢參數
     */
    public static function query(
        string $name,
        string $type = 'string',
        bool $required = false,
        ?string $description = null,
        mixed $default = null
    ): self {
        return new self(
            name: $name,
            in: 'query',
            type: $type,
            required: $required,
            description: $description,
            default: $default,
        );
    }
    
    /**
     * 創建請求頭參數
     */
    public static function header(
        string $name,
        string $type = 'string',
        bool $required = false,
        ?string $description = null
    ): self {
        return new self(
            name: $name,
            in: 'header',
            type: $type,
            required: $required,
            description: $description,
        );
    }
}
```

#### 文件: `src/Abstractions/ParameterCollection.php`

```php
<?php

namespace Dedoc\Scramble\Abstractions;

use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * 參數集合
 */
class ParameterCollection implements IteratorAggregate, Countable
{
    /** @var Parameter[] */
    private array $parameters = [];
    
    /**
     * 添加參數
     */
    public function add(Parameter $parameter): self
    {
        $this->parameters[$parameter->name] = $parameter;
        return $this;
    }
    
    /**
     * 批量添加參數
     */
    public function addMany(array $parameters): self
    {
        foreach ($parameters as $parameter) {
            $this->add($parameter);
        }
        return $this;
    }
    
    /**
     * 獲取參數
     */
    public function get(string $name): ?Parameter
    {
        return $this->parameters[$name] ?? null;
    }
    
    /**
     * 檢查是否存在參數
     */
    public function has(string $name): bool
    {
        return isset($this->parameters[$name]);
    }
    
    /**
     * 移除參數
     */
    public function remove(string $name): self
    {
        unset($this->parameters[$name]);
        return $this;
    }
    
    /**
     * 獲取所有參數
     */
    public function all(): array
    {
        return $this->parameters;
    }
    
    /**
     * 過濾參數
     */
    public function filter(callable $callback): self
    {
        $collection = new self();
        foreach ($this->parameters as $parameter) {
            if ($callback($parameter)) {
                $collection->add($parameter);
            }
        }
        return $collection;
    }
    
    /**
     * 獲取必需參數
     */
    public function required(): self
    {
        return $this->filter(fn(Parameter $p) => $p->required);
    }
    
    /**
     * 獲取可選參數
     */
    public function optional(): self
    {
        return $this->filter(fn(Parameter $p) => !$p->required);
    }
    
    /**
     * 按位置分組
     */
    public function groupByIn(): array
    {
        $groups = [];
        foreach ($this->parameters as $parameter) {
            $groups[$parameter->in][] = $parameter;
        }
        return $groups;
    }
    
    /**
     * 轉換為 OpenAPI 數組
     */
    public function toOpenApiArray(): array
    {
        return array_map(
            fn(Parameter $p) => $p->toOpenApiArray(),
            array_values($this->parameters)
        );
    }
    
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->parameters);
    }
    
    public function count(): int
    {
        return count($this->parameters);
    }
    
    /**
     * 合併其他集合
     */
    public function merge(ParameterCollection $other): self
    {
        $collection = clone $this;
        foreach ($other->all() as $parameter) {
            $collection->add($parameter);
        }
        return $collection;
    }
}
```

### 2.3 步驟 3: 測試抽象層

#### 文件: `tests/Core/Abstractions/HandlerInfoTest.php`

```php
<?php

namespace Dedoc\Scramble\Tests\Core\Abstractions;

use Dedoc\Scramble\Abstractions\HandlerInfo;
use PHPUnit\Framework\TestCase;

class HandlerInfoTest extends TestCase
{
    public function testCreateFromClassMethod(): void
    {
        $handler = HandlerInfo::fromClassMethod('App\Controller\UserController', 'index');
        
        $this->assertTrue($handler->isClassMethod());
        $this->assertFalse($handler->isClosure());
        $this->assertEquals('App\Controller\UserController', $handler->className);
        $this->assertEquals('index', $handler->methodName);
        $this->assertEquals('App\Controller\UserController::index', $handler->getFullyQualifiedName());
    }
    
    public function testCreateFromClosure(): void
    {
        $closure = fn() => 'test';
        $handler = HandlerInfo::fromClosure($closure);
        
        $this->assertTrue($handler->isClosure());
        $this->assertFalse($handler->isClassMethod());
        $this->assertNull($handler->className);
        $this->assertNull($handler->methodName);
        $this->assertEquals('{closure}', $handler->getFullyQualifiedName());
    }
    
    public function testCreateFromCallableString(): void
    {
        $handler = HandlerInfo::fromCallable('App\Controller\UserController::index');
        
        $this->assertTrue($handler->isClassMethod());
        $this->assertEquals('App\Controller\UserController', $handler->className);
        $this->assertEquals('index', $handler->methodName);
    }
    
    public function testCreateFromCallableArray(): void
    {
        $handler = HandlerInfo::fromCallable([self::class, 'testCreateFromCallableArray']);
        
        $this->assertTrue($handler->isClassMethod());
        $this->assertEquals(self::class, $handler->className);
        $this->assertEquals('testCreateFromCallableArray', $handler->methodName);
    }
}
```

---

## 三、階段二：Laravel 適配器（第 3-4 週）

### 3.1 步驟 4: 實現 Laravel 路由提取器

#### 文件: `src/Laravel/LaravelRouteExtractor.php`

```php
<?php

namespace Dedoc\Scramble\Laravel;

use Dedoc\Scramble\Abstractions\RouteExtractorInterface;
use Dedoc\Scramble\Abstractions\RouteInfo;
use Dedoc\Scramble\Abstractions\HandlerInfo;
use Dedoc\Scramble\Attributes\ExcludeRouteFromDocs;
use Dedoc\Scramble\Attributes\ExcludeAllRoutesFromDocs;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use ReflectionClass;

class LaravelRouteExtractor implements RouteExtractorInterface
{
    public function extractRoutes(array $config): array
    {
        $apiPath = $config['api_path'] ?? 'api';
        $apiDomain = $config['api_domain'] ?? null;
        
        $routes = RouteFacade::getRoutes()->getRoutes();
        
        $result = [];
        foreach ($routes as $route) {
            if ($this->shouldIncludeRoute($route, $apiPath, $apiDomain)) {
                $result[] = $this->createRouteInfo($route);
            }
        }
        
        return $result;
    }
    
    private function shouldIncludeRoute(Route $route, string $apiPath, ?string $apiDomain): bool
    {
        // 檢查域名
        if ($apiDomain !== null && $route->getDomain() !== $apiDomain) {
            return false;
        }
        
        // 檢查路徑前綴
        $uri = $route->uri();
        if (!str_starts_with($uri, $apiPath)) {
            return false;
        }
        
        // 檢查路由名稱是否排除
        $name = $route->getName();
        if ($name && str_starts_with($name, 'scramble.')) {
            return false;
        }
        
        // 檢查屬性排除
        if ($this->hasExcludeAttribute($route)) {
            return false;
        }
        
        return true;
    }
    
    private function hasExcludeAttribute(Route $route): bool
    {
        $action = $route->getAction('uses');
        
        if (!is_string($action)) {
            return false;
        }
        
        [$class, $method] = explode('@', $action) + [null, null];
        
        if (!$class || !$method) {
            return false;
        }
        
        try {
            $reflection = new ReflectionClass($class);
            
            // 檢查類級別排除
            if (!empty($reflection->getAttributes(ExcludeAllRoutesFromDocs::class))) {
                return true;
            }
            
            // 檢查方法級別排除
            $methodReflection = $reflection->getMethod($method);
            if (!empty($methodReflection->getAttributes(ExcludeRouteFromDocs::class))) {
                return true;
            }
        } catch (\ReflectionException $e) {
            return false;
        }
        
        return false;
    }
    
    private function createRouteInfo(Route $route): RouteInfo
    {
        return new RouteInfo(
            uri: $this->getUri($route),
            methods: $this->getMethods($route),
            handler: $this->getHandler($route),
            middleware: $this->getMiddleware($route),
            metadata: $this->getMetadata($route),
            originalRoute: $route,
        );
    }
    
    public function getMethods(object $route): array
    {
        /** @var Route $route */
        return $route->methods();
    }
    
    public function getUri(object $route): string
    {
        /** @var Route $route */
        return $route->uri();
    }
    
    public function getHandler(object $route): HandlerInfo
    {
        /** @var Route $route */
        $action = $route->getAction('uses');
        
        if ($action instanceof \Closure) {
            return HandlerInfo::fromClosure($action);
        }
        
        if (is_string($action) && str_contains($action, '@')) {
            [$class, $method] = explode('@', $action);
            return HandlerInfo::fromClassMethod($class, $method);
        }
        
        // Laravel 也支援 invokable controllers
        if (is_string($action)) {
            return HandlerInfo::fromClassMethod($action, '__invoke');
        }
        
        throw new \RuntimeException('Unsupported handler type: ' . get_debug_type($action));
    }
    
    public function getMiddleware(object $route): array
    {
        /** @var Route $route */
        return $route->gatherMiddleware();
    }
    
    public function getMetadata(object $route): array
    {
        /** @var Route $route */
        return [
            'name' => $route->getName(),
            'wheres' => $route->wheres,
            'domain' => $route->getDomain(),
            'prefix' => $route->getPrefix(),
        ];
    }
}
```

### 3.2 步驟 5: 測試 Laravel 適配器

#### 文件: `tests/Laravel/LaravelRouteExtractorTest.php`

```php
<?php

namespace Dedoc\Scramble\Tests\Laravel;

use Dedoc\Scramble\Laravel\LaravelRouteExtractor;
use Dedoc\Scramble\Abstractions\RouteInfo;
use Dedoc\Scramble\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class LaravelRouteExtractorTest extends TestCase
{
    public function testExtractsLaravelRoutes(): void
    {
        // 設置測試路由
        Route::get('/api/users', [TestController::class, 'index'])->name('api.users.index');
        Route::post('/api/users', [TestController::class, 'store'])->name('api.users.store');
        Route::get('/web/home', [TestController::class, 'home'])->name('web.home');
        
        $extractor = new LaravelRouteExtractor();
        $routes = $extractor->extractRoutes([
            'api_path' => 'api',
        ]);
        
        // 應該只提取 /api 路由
        $this->assertCount(2, $routes);
        
        foreach ($routes as $route) {
            $this->assertInstanceOf(RouteInfo::class, $route);
            $this->assertStringStartsWith('api/', $route->uri);
        }
    }
    
    public function testExcludesRoutesWithExcludeAttribute(): void
    {
        Route::get('/api/public', [TestController::class, 'public']);
        Route::get('/api/excluded', [TestController::class, 'excluded']);
        
        $extractor = new LaravelRouteExtractor();
        $routes = $extractor->extractRoutes([
            'api_path' => 'api',
        ]);
        
        // excluded 方法有 ExcludeRouteFromDocs 屬性，應該被排除
        $uris = array_map(fn($r) => $r->uri, $routes);
        $this->assertContains('api/public', $uris);
        $this->assertNotContains('api/excluded', $uris);
    }
}

class TestController
{
    public function index() {}
    public function store() {}
    public function home() {}
    public function public() {}
    
    #[ExcludeRouteFromDocs]
    public function excluded() {}
}
```

---

## 四、階段三：Symfony 適配器（第 5-8 週）

### 4.1 步驟 6: 創建 Symfony Bundle

#### 文件: `src/Symfony/ScrambleBundle.php`

```php
<?php

namespace Dedoc\Scramble\Symfony;

use Dedoc\Scramble\Symfony\DependencyInjection\ScrambleExtension;
use Dedoc\Scramble\Symfony\DependencyInjection\Compiler\ExtensionRegistrationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ScrambleBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
    
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        
        // 添加編譯器通道以註冊擴展
        $container->addCompilerPass(new ExtensionRegistrationPass());
    }
    
    public function getContainerExtension(): ScrambleExtension
    {
        if (null === $this->extension) {
            $this->extension = new ScrambleExtension();
        }
        
        return $this->extension;
    }
}
```

#### 文件: `src/Symfony/DependencyInjection/Configuration.php`

```php
<?php

namespace Dedoc\Scramble\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('scramble');
        $rootNode = $treeBuilder->getRootNode();
        
        $rootNode
            ->children()
                ->scalarNode('api_path')
                    ->defaultValue('/api')
                    ->info('API 路徑前綴')
                ->end()
                ->scalarNode('api_domain')
                    ->defaultNull()
                    ->info('API 域名')
                ->end()
                ->scalarNode('export_path')
                    ->defaultValue('api.json')
                    ->info('OpenAPI 規範導出路徑')
                ->end()
                ->arrayNode('info')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('version')
                            ->defaultValue('1.0.0')
                        ->end()
                        ->scalarNode('title')
                            ->defaultValue('API Documentation')
                        ->end()
                        ->scalarNode('description')
                            ->defaultValue('')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('ui')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('title')
                            ->defaultNull()
                        ->end()
                        ->enumNode('theme')
                            ->values(['light', 'dark', 'system'])
                            ->defaultValue('light')
                        ->end()
                        ->booleanNode('hide_try_it')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('hide_schemas')
                            ->defaultFalse()
                        ->end()
                        ->enumNode('layout')
                            ->values(['sidebar', 'responsive', 'stacked'])
                            ->defaultValue('responsive')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('servers')
                    ->scalarPrototype()->end()
                    ->defaultNull()
                ->end()
                ->arrayNode('routes')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('include_patterns')
                            ->scalarPrototype()->end()
                            ->defaultValue(['^/api'])
                        ->end()
                        ->arrayNode('exclude_patterns')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end()
                        ->arrayNode('exclude_controllers')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('security')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                        ->end()
                        ->scalarNode('voter')
                            ->defaultValue('scramble.security.voter')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('extensions')
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                ->end()
            ->end()
        ;
        
        return $treeBuilder;
    }
}
```

#### 文件: `src/Symfony/DependencyInjection/ScrambleExtension.php`

```php
<?php

namespace Dedoc\Scramble\Symfony\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class ScrambleExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        
        // 設置配置參數
        $container->setParameter('scramble.config', $config);
        $container->setParameter('scramble.api_path', $config['api_path']);
        $container->setParameter('scramble.export_path', $config['export_path']);
        
        // 載入服務定義
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yaml');
    }
    
    public function getAlias(): string
    {
        return 'scramble';
    }
}
```

### 4.2 步驟 7: 實現 Symfony 路由提取器

#### 文件: `src/Symfony/SymfonyRouteExtractor.php`

```php
<?php

namespace Dedoc\Scramble\Symfony;

use Dedoc\Scramble\Abstractions\RouteExtractorInterface;
use Dedoc\Scramble\Abstractions\RouteInfo;
use Dedoc\Scramble\Abstractions\HandlerInfo;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Route;

class SymfonyRouteExtractor implements RouteExtractorInterface
{
    public function __construct(
        private RouterInterface $router
    ) {}
    
    public function extractRoutes(array $config): array
    {
        $apiPath = $config['api_path'] ?? '/api';
        $includePatterns = $config['routes']['include_patterns'] ?? ['^/api'];
        $excludePatterns = $config['routes']['exclude_patterns'] ?? [];
        $excludeControllers = $config['routes']['exclude_controllers'] ?? [];
        
        $collection = $this->router->getRouteCollection();
        
        $result = [];
        foreach ($collection as $name => $route) {
            if ($this->shouldIncludeRoute(
                $route,
                $name,
                $includePatterns,
                $excludePatterns,
                $excludeControllers
            )) {
                $result[] = $this->createRouteInfo($name, $route);
            }
        }
        
        return $result;
    }
    
    private function shouldIncludeRoute(
        Route $route,
        string $name,
        array $includePatterns,
        array $excludePatterns,
        array $excludeControllers
    ): bool {
        $path = $route->getPath();
        
        // 檢查包含模式
        $included = false;
        foreach ($includePatterns as $pattern) {
            if (preg_match("#{$pattern}#", $path)) {
                $included = true;
                break;
            }
        }
        
        if (!$included) {
            return false;
        }
        
        // 檢查排除模式
        foreach ($excludePatterns as $pattern) {
            if (preg_match("#{$pattern}#", $path)) {
                return false;
            }
        }
        
        // 檢查排除的路由名稱
        if (str_starts_with($name, 'scramble_')) {
            return false;
        }
        
        // 檢查排除的控制器
        $defaults = $route->getDefaults();
        $controller = $defaults['_controller'] ?? null;
        
        if ($controller) {
            foreach ($excludeControllers as $excludedController) {
                if (str_contains($controller, $excludedController)) {
                    return false;
                }
            }
        }
        
        // 檢查路由選項中的排除標記
        if ($route->getOption('scramble_exclude')) {
            return false;
        }
        
        return true;
    }
    
    private function createRouteInfo(string $name, Route $route): RouteInfo
    {
        return new RouteInfo(
            uri: $this->getUri($route),
            methods: $this->getMethods($route),
            handler: $this->getHandler($route),
            middleware: $this->getMiddleware($route),
            metadata: $this->getMetadata($route, $name),
            originalRoute: $route,
        );
    }
    
    public function getMethods(object $route): array
    {
        /** @var Route $route */
        $methods = $route->getMethods();
        return !empty($methods) ? $methods : ['GET'];
    }
    
    public function getUri(object $route): string
    {
        /** @var Route $route */
        return $route->getPath();
    }
    
    public function getHandler(object $route): HandlerInfo
    {
        /** @var Route $route */
        $defaults = $route->getDefaults();
        $controller = $defaults['_controller'] ?? null;
        
        if ($controller === null) {
            throw new \RuntimeException('Route has no controller defined');
        }
        
        // Symfony 控制器格式: "App\Controller\ApiController::getUserAction"
        if (is_string($controller) && str_contains($controller, '::')) {
            [$class, $method] = explode('::', $controller, 2);
            return HandlerInfo::fromClassMethod($class, $method);
        }
        
        // Invokable controller: "App\Controller\ApiController"
        if (is_string($controller) && class_exists($controller)) {
            return HandlerInfo::fromClassMethod($controller, '__invoke');
        }
        
        // Callable
        if (is_callable($controller)) {
            return HandlerInfo::fromCallable($controller);
        }
        
        throw new \RuntimeException(
            'Unsupported controller type: ' . get_debug_type($controller)
        );
    }
    
    public function getMiddleware(object $route): array
    {
        // Symfony 使用事件系統，不是中介層
        // 可以從路由選項中獲取相關信息
        /** @var Route $route */
        return [];
    }
    
    public function getMetadata(object $route, string $name): array
    {
        /** @var Route $route */
        return [
            'name' => $name,
            'requirements' => $route->getRequirements(),
            'options' => $route->getOptions(),
            'schemes' => $route->getSchemes(),
            'host' => $route->getHost(),
            'defaults' => $route->getDefaults(),
        ];
    }
}
```

### 4.3 步驟 8: 創建服務配置

#### 文件: `src/Symfony/Resources/config/services.yaml`

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: false

    # 路由提取器
    Dedoc\Scramble\Symfony\SymfonyRouteExtractor:
        arguments:
            $router: '@router'

    Dedoc\Scramble\Abstractions\RouteExtractorInterface:
        alias: Dedoc\Scramble\Symfony\SymfonyRouteExtractor

    # 請求提取器（待實現）
    Dedoc\Scramble\Symfony\SymfonyRequestExtractor: ~

    Dedoc\Scramble\Abstractions\RequestExtractorInterface:
        alias: Dedoc\Scramble\Symfony\SymfonyRequestExtractor

    # 響應提取器（待實現）
    Dedoc\Scramble\Symfony\SymfonyResponseExtractor: ~

    Dedoc\Scramble\Abstractions\ResponseExtractorInterface:
        alias: Dedoc\Scramble\Symfony\SymfonyResponseExtractor

    # 安全提取器（待實現）
    Dedoc\Scramble\Symfony\SymfonySecurityExtractor: ~

    Dedoc\Scramble\Abstractions\SecurityExtractorInterface:
        alias: Dedoc\Scramble\Symfony\SymfonySecurityExtractor

    # 核心生成器
    Dedoc\Scramble\Core\Generator\DocumentGenerator:
        arguments:
            $routeExtractor: '@Dedoc\Scramble\Abstractions\RouteExtractorInterface'
            $requestExtractor: '@Dedoc\Scramble\Abstractions\RequestExtractorInterface'
            $responseExtractor: '@Dedoc\Scramble\Abstractions\ResponseExtractorInterface'
            $securityExtractor: '@Dedoc\Scramble\Abstractions\SecurityExtractorInterface'

    # 類型推斷
    Dedoc\Scramble\Core\TypeInference\TypeInferenceEngine: ~

    # 控制器
    Dedoc\Scramble\Symfony\Controller\ScrambleController:
        arguments:
            $generator: '@Dedoc\Scramble\Core\Generator\DocumentGenerator'
            $config: '%scramble.config%'
        tags: ['controller.service_arguments']
        public: true

    # Symfony 特定類型擴展
    Dedoc\Scramble\Symfony\TypeExtensions\DoctrineEntityExtension:
        tags: ['scramble.type_extension']

    Dedoc\Scramble\Symfony\TypeExtensions\SymfonyRequestExtension:
        tags: ['scramble.type_extension']

    Dedoc\Scramble\Symfony\TypeExtensions\SerializerExtension:
        tags: ['scramble.type_extension']

    Dedoc\Scramble\Symfony\TypeExtensions\ValidatorExtension:
        tags: ['scramble.type_extension']
```

---

## 五、測試執行

### 5.1 執行 PHPUnit 測試

```bash
# 執行所有測試
vendor/bin/phpunit

# 執行特定目錄的測試
vendor/bin/phpunit tests/Core
vendor/bin/phpunit tests/Laravel
vendor/bin/phpunit tests/Symfony

# 執行特定測試文件
vendor/bin/phpunit tests/Core/Abstractions/HandlerInfoTest.php

# 帶覆蓋率報告
vendor/bin/phpunit --coverage-html coverage
```

### 5.2 執行 PHPStan 靜態分析

```bash
# 執行 PHPStan
vendor/bin/phpstan analyse

# 生成基準線（首次運行）
vendor/bin/phpstan analyse --generate-baseline

# 指定級別
vendor/bin/phpstan analyse --level=5
```

---

## 六、Git 工作流程

### 6.1 分支策略

```bash
# 主分支
main - 穩定版本

# 開發分支
develop - 開發中的功能

# 功能分支
feature/abstraction-layer - 抽象層
feature/laravel-adapter - Laravel 適配器
feature/symfony-adapter - Symfony 適配器
feature/symfony-bundle - Symfony Bundle

# 修復分支
fix/bug-description
```

### 6.2 提交訊息規範

```bash
# 格式
<type>(<scope>): <subject>

# 類型
feat: 新功能
fix: 修復
refactor: 重構
test: 測試
docs: 文件
style: 格式
chore: 雜項

# 範例
feat(abstraction): add RouteExtractorInterface
feat(laravel): implement LaravelRouteExtractor
feat(symfony): implement SymfonyRouteExtractor
test(core): add HandlerInfo tests
docs(plan): update implementation guide
```

---

## 七、檢查清單

### 7.1 階段一完成檢查

- [ ] 所有抽象接口已定義
- [ ] HandlerInfo、RouteInfo 等數據結構已實現
- [ ] ParameterCollection 已實現並測試
- [ ] 單元測試覆蓋率 > 80%
- [ ] PHPStan level 5 無錯誤

### 7.2 階段二完成檢查

- [ ] LaravelRouteExtractor 已實現
- [ ] 支援所有 Laravel 路由格式
- [ ] 屬性排除功能正常
- [ ] Laravel 整合測試通過
- [ ] 向後兼容性測試通過

### 7.3 階段三完成檢查

- [ ] ScrambleBundle 已創建
- [ ] Configuration 已定義
- [ ] SymfonyRouteExtractor 已實現
- [ ] 服務配置正確
- [ ] Symfony 整合測試通過
- [ ] 控制器正確渲染文件

---

## 八、總結

本實施指南提供了：

1. **詳細的代碼範例** - 每個關鍵組件都有完整實現
2. **測試範例** - 確保代碼品質
3. **配置範例** - Symfony Bundle 完整配置
4. **開發流程** - 從開發到測試的完整流程

按照本指南步驟執行，可以確保：
- ✅ 代碼品質高
- ✅ 測試覆蓋完整
- ✅ 架構清晰可維護
- ✅ 符合最佳實踐

下一步應該開始實際編碼，從抽象層開始逐步實現。
