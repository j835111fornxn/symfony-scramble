# Symfony-Scramble 技術架構文件
## 雙框架支援技術架構設計

**文件版本**: 1.0  
**建立日期**: 2025-11-04

---

## 一、系統架構概覽

### 1.1 架構圖

```
┌─────────────────────────────────────────────────────────────────────┐
│                        使用者應用程式                                 │
│                  (Laravel App / Symfony App)                         │
└────────────────────┬────────────────────┬────────────────────────────┘
                     │                    │
         ┌───────────▼───────────┐   ┌───▼────────────────────┐
         │  Laravel Adapter      │   │  Symfony Adapter       │
         │  ─────────────────    │   │  ─────────────────     │
         │  - Service Provider   │   │  - Bundle              │
         │  - Route Extractor    │   │  - Route Extractor     │
         │  - Type Extensions    │   │  - Type Extensions     │
         │  - Middleware         │   │  - Event Subscribers   │
         └───────────┬───────────┘   └───┬────────────────────┘
                     │                    │
                     └──────────┬─────────┘
                                │
                    ┌───────────▼────────────┐
                    │   Abstraction Layer    │
                    │   ─────────────────    │
                    │   - Interfaces         │
                    │   - Data Structures    │
                    │   - Contracts          │
                    └───────────┬────────────┘
                                │
                ┌───────────────┼───────────────┐
                │               │               │
    ┌───────────▼──────┐  ┌────▼────────┐  ┌──▼──────────────┐
    │  Core Generator  │  │  Type Infer │  │  OpenAPI Model  │
    │  ──────────────  │  │  ──────────  │  │  ──────────────  │
    │  - Doc Builder   │  │  - Parser    │  │  - Schema        │
    │  - Traverser     │  │  - Analyzer  │  │  - Operation     │
    │  - Transformer   │  │  - Resolver  │  │  - Response      │
    └──────────────────┘  └─────────────┘  └─────────────────┘
```

### 1.2 分層說明

**第一層：適配器層 (Adapter Layer)**
- 負責與特定框架整合
- 處理框架特定的數據結構
- 提供框架專屬的功能擴展

**第二層：抽象層 (Abstraction Layer)**
- 定義框架無關的接口
- 提供統一的數據結構
- 確保不同適配器的互操作性

**第三層：核心層 (Core Layer)**
- 實現核心業務邏輯
- 類型推斷引擎
- OpenAPI 文件生成
- 完全框架獨立

---

## 二、關鍵接口定義

### 2.1 RouteExtractorInterface

```php
<?php

namespace Dedoc\Scramble\Abstractions;

/**
 * 路由提取器接口
 * 
 * 負責從框架的路由系統中提取 API 路由信息
 */
interface RouteExtractorInterface
{
    /**
     * 提取所有符合條件的 API 路由
     * 
     * @param array $config 配置數組，包含過濾條件
     * @return RouteInfo[] 路由信息數組
     */
    public function extractRoutes(array $config): array;
    
    /**
     * 獲取路由支援的 HTTP 方法
     * 
     * @param object $route 框架特定的路由對象
     * @return string[] HTTP 方法數組 ['GET', 'POST', ...]
     */
    public function getMethods(object $route): array;
    
    /**
     * 獲取路由的 URI 路徑
     * 
     * @param object $route 框架特定的路由對象
     * @return string URI 路徑，例如 "/api/users/{id}"
     */
    public function getUri(object $route): string;
    
    /**
     * 獲取路由的處理器信息
     * 
     * @param object $route 框架特定的路由對象
     * @return HandlerInfo 處理器信息（控制器類名、方法名等）
     */
    public function getHandler(object $route): HandlerInfo;
    
    /**
     * 獲取路由的中介層/事件監聽器
     * 
     * @param object $route 框架特定的路由對象
     * @return array 中介層類名數組
     */
    public function getMiddleware(object $route): array;
    
    /**
     * 獲取路由的元數據
     * 
     * @param object $route 框架特定的路由對象
     * @return array 元數據數組（名稱、描述等）
     */
    public function getMetadata(object $route): array;
}
```

### 2.2 RequestExtractorInterface

```php
<?php

namespace Dedoc\Scramble\Abstractions;

/**
 * 請求提取器接口
 * 
 * 負責從處理器中提取請求參數、請求體等信息
 */
interface RequestExtractorInterface
{
    /**
     * 提取路徑參數
     * 
     * @param HandlerInfo $handler 處理器信息
     * @return ParameterCollection 參數集合
     */
    public function extractPathParameters(HandlerInfo $handler): ParameterCollection;
    
    /**
     * 提取查詢參數
     * 
     * @param HandlerInfo $handler 處理器信息
     * @return ParameterCollection 參數集合
     */
    public function extractQueryParameters(HandlerInfo $handler): ParameterCollection;
    
    /**
     * 提取請求頭參數
     * 
     * @param HandlerInfo $handler 處理器信息
     * @return ParameterCollection 參數集合
     */
    public function extractHeaderParameters(HandlerInfo $handler): ParameterCollection;
    
    /**
     * 提取請求體 Schema
     * 
     * @param HandlerInfo $handler 處理器信息
     * @return RequestBodySchema|null 請求體 Schema
     */
    public function extractRequestBody(HandlerInfo $handler): ?RequestBodySchema;
    
    /**
     * 提取驗證規則
     * 
     * @param HandlerInfo $handler 處理器信息
     * @return ValidationRules 驗證規則
     */
    public function extractValidationRules(HandlerInfo $handler): ValidationRules;
}
```

### 2.3 ResponseExtractorInterface

```php
<?php

namespace Dedoc\Scramble\Abstractions;

/**
 * 響應提取器接口
 * 
 * 負責從處理器中提取響應信息
 */
interface ResponseExtractorInterface
{
    /**
     * 提取所有可能的響應
     * 
     * @param HandlerInfo $handler 處理器信息
     * @return ResponseCollection 響應集合
     */
    public function extractResponses(HandlerInfo $handler): ResponseCollection;
    
    /**
     * 提取成功響應
     * 
     * @param HandlerInfo $handler 處理器信息
     * @return Response|null 成功響應
     */
    public function extractSuccessResponse(HandlerInfo $handler): ?Response;
    
    /**
     * 提取錯誤響應
     * 
     * @param HandlerInfo $handler 處理器信息
     * @return Response[] 錯誤響應數組
     */
    public function extractErrorResponses(HandlerInfo $handler): array;
}
```

### 2.4 SecurityExtractorInterface

```php
<?php

namespace Dedoc\Scramble\Abstractions;

/**
 * 安全提取器接口
 * 
 * 負責提取認證、授權等安全相關信息
 */
interface SecurityExtractorInterface
{
    /**
     * 提取安全需求
     * 
     * @param object $route 框架特定的路由對象
     * @return SecurityRequirement[] 安全需求數組
     */
    public function extractSecurityRequirements(object $route): array;
    
    /**
     * 提取認證方式
     * 
     * @param object $route 框架特定的路由對象
     * @return string[] 認證方式數組 ['bearer', 'apiKey', ...]
     */
    public function extractAuthenticationMethods(object $route): array;
    
    /**
     * 提取授權範圍
     * 
     * @param object $route 框架特定的路由對象
     * @return string[] 授權範圍數組
     */
    public function extractScopes(object $route): array;
}
```

---

## 三、統一數據結構

### 3.1 RouteInfo

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
    
    public function getMethod(): string
    {
        return $this->methods[0] ?? 'GET';
    }
    
    public function hasMiddleware(string $middleware): bool
    {
        return in_array($middleware, $this->middleware);
    }
    
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }
}
```

### 3.2 HandlerInfo

```php
<?php

namespace Dedoc\Scramble\Abstractions;

/**
 * 處理器信息（控制器方法、閉包等）
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
        return new self($class, $method);
    }
    
    public static function fromClosure(callable $closure): self
    {
        return new self(null, null, $closure);
    }
    
    public static function fromCallable(mixed $callable): self
    {
        if (is_string($callable) && str_contains($callable, '::')) {
            [$class, $method] = explode('::', $callable, 2);
            return self::fromClassMethod($class, $method);
        }
        
        if (is_callable($callable)) {
            return self::fromClosure($callable);
        }
        
        throw new \InvalidArgumentException('Invalid callable');
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
}
```

### 3.3 ParameterCollection

```php
<?php

namespace Dedoc\Scramble\Abstractions;

use ArrayIterator;
use IteratorAggregate;

/**
 * 參數集合
 */
class ParameterCollection implements IteratorAggregate
{
    /** @var Parameter[] */
    private array $parameters = [];
    
    public function add(Parameter $parameter): self
    {
        $this->parameters[$parameter->name] = $parameter;
        return $this;
    }
    
    public function get(string $name): ?Parameter
    {
        return $this->parameters[$name] ?? null;
    }
    
    public function has(string $name): bool
    {
        return isset($this->parameters[$name]);
    }
    
    public function all(): array
    {
        return $this->parameters;
    }
    
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->parameters);
    }
    
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
    
    public function required(): self
    {
        return $this->filter(fn($p) => $p->required);
    }
}
```

### 3.4 Parameter

```php
<?php

namespace Dedoc\Scramble\Abstractions;

/**
 * 參數定義
 */
class Parameter
{
    public function __construct(
        public readonly string $name,
        public readonly string $in, // 'path', 'query', 'header', 'cookie'
        public readonly string $type,
        public readonly bool $required = false,
        public readonly ?string $description = null,
        public readonly mixed $default = null,
        public readonly ?string $format = null,
        public readonly ?array $enum = null,
        public readonly array $validation = [],
    ) {}
    
    public function toOpenApiArray(): array
    {
        $schema = [
            'type' => $this->type,
        ];
        
        if ($this->format) {
            $schema['format'] = $this->format;
        }
        
        if ($this->enum) {
            $schema['enum'] = $this->enum;
        }
        
        if ($this->default !== null) {
            $schema['default'] = $this->default;
        }
        
        $parameter = [
            'name' => $this->name,
            'in' => $this->in,
            'required' => $this->required,
            'schema' => $schema,
        ];
        
        if ($this->description) {
            $parameter['description'] = $this->description;
        }
        
        return $parameter;
    }
}
```

### 3.5 ResponseCollection

```php
<?php

namespace Dedoc\Scramble\Abstractions;

/**
 * 響應集合
 */
class ResponseCollection
{
    /** @var Response[] */
    private array $responses = [];
    
    public function add(Response $response): self
    {
        $this->responses[$response->statusCode] = $response;
        return $this;
    }
    
    public function get(int $statusCode): ?Response
    {
        return $this->responses[$statusCode] ?? null;
    }
    
    public function all(): array
    {
        return $this->responses;
    }
    
    public function toOpenApiArray(): array
    {
        $responses = [];
        foreach ($this->responses as $statusCode => $response) {
            $responses[(string)$statusCode] = $response->toOpenApiArray();
        }
        return $responses;
    }
}
```

### 3.6 Response

```php
<?php

namespace Dedoc\Scramble\Abstractions;

/**
 * 響應定義
 */
class Response
{
    public function __construct(
        public readonly int $statusCode,
        public readonly string $description,
        public readonly ?Schema $schema = null,
        public readonly ?string $contentType = 'application/json',
        public readonly array $headers = [],
    ) {}
    
    public function toOpenApiArray(): array
    {
        $response = [
            'description' => $this->description,
        ];
        
        if ($this->schema) {
            $response['content'] = [
                $this->contentType => [
                    'schema' => $this->schema->toOpenApiArray(),
                ],
            ];
        }
        
        if (!empty($this->headers)) {
            $response['headers'] = $this->headers;
        }
        
        return $response;
    }
}
```

---

## 四、核心組件設計

### 4.1 DocumentGenerator

```php
<?php

namespace Dedoc\Scramble\Core\Generator;

use Dedoc\Scramble\Abstractions\RouteExtractorInterface;
use Dedoc\Scramble\Abstractions\RequestExtractorInterface;
use Dedoc\Scramble\Abstractions\ResponseExtractorInterface;
use Dedoc\Scramble\Abstractions\SecurityExtractorInterface;

/**
 * 文件生成器核心
 * 
 * 負責協調各個提取器，生成完整的 OpenAPI 文件
 */
class DocumentGenerator
{
    public function __construct(
        private RouteExtractorInterface $routeExtractor,
        private RequestExtractorInterface $requestExtractor,
        private ResponseExtractorInterface $responseExtractor,
        private SecurityExtractorInterface $securityExtractor,
    ) {}
    
    /**
     * 生成 OpenAPI 文件
     */
    public function generate(array $config): array
    {
        // 1. 提取路由
        $routes = $this->routeExtractor->extractRoutes($config);
        
        // 2. 初始化 OpenAPI 文件結構
        $openApi = $this->initializeDocument($config);
        
        // 3. 處理每個路由
        foreach ($routes as $routeInfo) {
            $operation = $this->buildOperation($routeInfo);
            $this->addOperationToDocument($openApi, $routeInfo, $operation);
        }
        
        // 4. 應用文件轉換器
        $openApi = $this->applyDocumentTransformers($openApi, $config);
        
        return $openApi;
    }
    
    private function buildOperation(RouteInfo $routeInfo): array
    {
        $handler = $routeInfo->handler;
        
        // 提取參數
        $parameters = [
            ...$this->requestExtractor->extractPathParameters($handler)->all(),
            ...$this->requestExtractor->extractQueryParameters($handler)->all(),
            ...$this->requestExtractor->extractHeaderParameters($handler)->all(),
        ];
        
        // 提取請求體
        $requestBody = $this->requestExtractor->extractRequestBody($handler);
        
        // 提取響應
        $responses = $this->responseExtractor->extractResponses($handler);
        
        // 提取安全需求
        $security = $this->securityExtractor->extractSecurityRequirements(
            $routeInfo->originalRoute
        );
        
        // 構建 operation 對象
        return [
            'operationId' => $this->generateOperationId($routeInfo),
            'summary' => $this->extractSummary($routeInfo),
            'description' => $this->extractDescription($routeInfo),
            'tags' => $this->extractTags($routeInfo),
            'parameters' => array_map(fn($p) => $p->toOpenApiArray(), $parameters),
            'requestBody' => $requestBody?->toOpenApiArray(),
            'responses' => $responses->toOpenApiArray(),
            'security' => $security,
        ];
    }
    
    private function initializeDocument(array $config): array
    {
        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => $config['info']['title'] ?? 'API Documentation',
                'version' => $config['info']['version'] ?? '1.0.0',
                'description' => $config['info']['description'] ?? '',
            ],
            'servers' => $this->buildServers($config),
            'paths' => [],
            'components' => [
                'schemas' => [],
                'securitySchemes' => [],
            ],
        ];
    }
}
```

### 4.2 TypeInferenceEngine

```php
<?php

namespace Dedoc\Scramble\Core\TypeInference;

/**
 * 類型推斷引擎
 * 
 * 負責分析 PHP 代碼，推斷變量類型
 */
class TypeInferenceEngine
{
    private array $extensions = [];
    
    public function registerExtension(TypeInferenceExtension $extension): void
    {
        $this->extensions[] = $extension;
    }
    
    /**
     * 分析函數或方法，推斷參數和返回類型
     */
    public function analyzeFunction(string $className, string $methodName): FunctionAnalysis
    {
        // 1. 獲取反射信息
        $reflection = new \ReflectionMethod($className, $methodName);
        
        // 2. 解析 PHP 代碼
        $ast = $this->parseMethod($reflection);
        
        // 3. 分析類型
        $analysis = new FunctionAnalysis($className, $methodName);
        
        // 4. 應用擴展
        foreach ($this->extensions as $extension) {
            if ($extension->shouldHandle($reflection)) {
                $extension->analyze($reflection, $ast, $analysis);
            }
        }
        
        return $analysis;
    }
    
    /**
     * 推斷表達式類型
     */
    public function inferExpressionType($expression, Scope $scope): Type
    {
        // 實現類型推斷邏輯
    }
}
```

---

## 五、Laravel 適配器實現

### 5.1 LaravelRouteExtractor

```php
<?php

namespace Dedoc\Scramble\Laravel;

use Dedoc\Scramble\Abstractions\RouteExtractorInterface;
use Dedoc\Scramble\Abstractions\RouteInfo;
use Dedoc\Scramble\Abstractions\HandlerInfo;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

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
        if ($apiDomain && $route->getDomain() !== $apiDomain) {
            return false;
        }
        
        // 檢查路徑前綴
        $uri = $route->uri();
        if (!str_starts_with($uri, $apiPath)) {
            return false;
        }
        
        // 檢查排除屬性
        if ($this->hasExcludeAttribute($route)) {
            return false;
        }
        
        return true;
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
        return $route->methods();
    }
    
    public function getUri(object $route): string
    {
        return $route->uri();
    }
    
    public function getHandler(object $route): HandlerInfo
    {
        $action = $route->getAction('uses');
        
        if ($action instanceof \Closure) {
            return HandlerInfo::fromClosure($action);
        }
        
        if (is_string($action)) {
            [$class, $method] = explode('@', $action);
            return HandlerInfo::fromClassMethod($class, $method);
        }
        
        throw new \RuntimeException('Unsupported handler type');
    }
    
    public function getMiddleware(object $route): array
    {
        return $route->gatherMiddleware();
    }
    
    public function getMetadata(object $route): array
    {
        return [
            'name' => $route->getName(),
            'wheres' => $route->wheres,
        ];
    }
}
```

### 5.2 Laravel 類型擴展範例

```php
<?php

namespace Dedoc\Scramble\Laravel\TypeExtensions;

use Dedoc\Scramble\Core\TypeInference\TypeInferenceExtension;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent Model 類型擴展
 */
class EloquentModelExtension implements TypeInferenceExtension
{
    public function shouldHandle(\ReflectionClass $reflection): bool
    {
        return $reflection->isSubclassOf(Model::class);
    }
    
    public function analyze(
        \ReflectionMethod $method,
        $ast,
        FunctionAnalysis $analysis
    ): void {
        $modelClass = $method->getDeclaringClass()->getName();
        
        // 從模型獲取屬性信息
        /** @var Model $model */
        $model = new $modelClass;
        
        // 獲取資料庫欄位
        $table = $model->getTable();
        $columns = $this->getTableColumns($table);
        
        // 分析關聯
        $relations = $this->analyzeRelations($model);
        
        // 更新分析結果
        $analysis->addModelInfo([
            'table' => $table,
            'columns' => $columns,
            'relations' => $relations,
            'casts' => $model->getCasts(),
            'hidden' => $model->getHidden(),
            'fillable' => $model->getFillable(),
        ]);
    }
}
```

---

## 六、Symfony 適配器實現

### 6.1 SymfonyRouteExtractor

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
        
        $collection = $this->router->getRouteCollection();
        
        $result = [];
        foreach ($collection as $name => $route) {
            if ($this->shouldIncludeRoute($route, $apiPath)) {
                $result[] = $this->createRouteInfo($name, $route);
            }
        }
        
        return $result;
    }
    
    private function shouldIncludeRoute(Route $route, string $apiPath): bool
    {
        $path = $route->getPath();
        
        // 檢查路徑前綴
        if (!str_starts_with($path, $apiPath)) {
            return false;
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
            middleware: [], // Symfony 使用事件系統，不是中介層
            metadata: [
                'name' => $name,
                'requirements' => $route->getRequirements(),
                'options' => $route->getOptions(),
            ],
            originalRoute: $route,
        );
    }
    
    public function getMethods(object $route): array
    {
        $methods = $route->getMethods();
        return !empty($methods) ? $methods : ['GET'];
    }
    
    public function getUri(object $route): string
    {
        return $route->getPath();
    }
    
    public function getHandler(object $route): HandlerInfo
    {
        $defaults = $route->getDefaults();
        $controller = $defaults['_controller'] ?? null;
        
        if (is_string($controller)) {
            // 格式: "App\Controller\ApiController::getUserAction"
            if (str_contains($controller, '::')) {
                [$class, $method] = explode('::', $controller, 2);
                return HandlerInfo::fromClassMethod($class, $method);
            }
            
            // 格式: "App\Controller\ApiController" (invoke)
            return HandlerInfo::fromClassMethod($controller, '__invoke');
        }
        
        if (is_callable($controller)) {
            return HandlerInfo::fromClosure($controller);
        }
        
        throw new \RuntimeException('Unsupported controller type');
    }
    
    public function getMiddleware(object $route): array
    {
        // Symfony 不使用中介層概念
        return [];
    }
    
    public function getMetadata(object $route): array
    {
        return [
            'requirements' => $route->getRequirements(),
            'options' => $route->getOptions(),
            'schemes' => $route->getSchemes(),
            'host' => $route->getHost(),
        ];
    }
}
```

### 6.2 Symfony 類型擴展範例

```php
<?php

namespace Dedoc\Scramble\Symfony\TypeExtensions;

use Dedoc\Scramble\Core\TypeInference\TypeInferenceExtension;
use Doctrine\ORM\Mapping\Entity;

/**
 * Doctrine Entity 類型擴展
 */
class DoctrineEntityExtension implements TypeInferenceExtension
{
    public function shouldHandle(\ReflectionClass $reflection): bool
    {
        $attributes = $reflection->getAttributes(Entity::class);
        return !empty($attributes);
    }
    
    public function analyze(
        \ReflectionMethod $method,
        $ast,
        FunctionAnalysis $analysis
    ): void {
        $entityClass = $method->getDeclaringClass()->getName();
        
        // 從 Doctrine metadata 獲取信息
        $metadata = $this->entityManager
            ->getClassMetadata($entityClass);
        
        // 獲取欄位映射
        $fieldMappings = $metadata->fieldMappings;
        
        // 獲取關聯映射
        $associationMappings = $metadata->associationMappings;
        
        // 更新分析結果
        $analysis->addEntityInfo([
            'table' => $metadata->getTableName(),
            'fields' => $fieldMappings,
            'associations' => $associationMappings,
            'identifier' => $metadata->getIdentifier(),
        ]);
    }
}
```

---

## 七、配置系統

### 7.1 統一配置接口

```php
<?php

namespace Dedoc\Scramble\Abstractions;

/**
 * 配置接口
 */
interface ConfigurationInterface
{
    /**
     * 獲取配置值
     */
    public function get(string $key, mixed $default = null): mixed;
    
    /**
     * 設置配置值
     */
    public function set(string $key, mixed $value): void;
    
    /**
     * 合併配置
     */
    public function merge(array $config): void;
    
    /**
     * 獲取所有配置
     */
    public function all(): array;
}
```

### 7.2 Laravel 配置適配器

```php
<?php

namespace Dedoc\Scramble\Laravel;

use Dedoc\Scramble\Abstractions\ConfigurationInterface;

class LaravelConfiguration implements ConfigurationInterface
{
    public function __construct(
        private array $config
    ) {}
    
    public static function fromLaravelConfig(): self
    {
        return new self(config('scramble', []));
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }
    
    public function set(string $key, mixed $value): void
    {
        data_set($this->config, $key, $value);
    }
    
    public function merge(array $config): void
    {
        $this->config = array_merge_recursive($this->config, $config);
    }
    
    public function all(): array
    {
        return $this->config;
    }
}
```

### 7.3 Symfony 配置適配器

```php
<?php

namespace Dedoc\Scramble\Symfony;

use Dedoc\Scramble\Abstractions\ConfigurationInterface;

class SymfonyConfiguration implements ConfigurationInterface
{
    public function __construct(
        private array $config
    ) {}
    
    public static function fromSymfonyConfig(array $config): self
    {
        return new self($config);
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $current = &$this->config;
        
        foreach ($keys as $k) {
            if (!isset($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }
        
        $current = $value;
    }
    
    public function merge(array $config): void
    {
        $this->config = array_merge_recursive($this->config, $config);
    }
    
    public function all(): array
    {
        return $this->config;
    }
}
```

---

## 八、擴展系統

### 8.1 擴展註冊機制

```php
<?php

namespace Dedoc\Scramble\Core;

/**
 * 擴展管理器
 */
class ExtensionManager
{
    private array $typeInferenceExtensions = [];
    private array $typeToSchemaExtensions = [];
    private array $operationTransformers = [];
    private array $documentTransformers = [];
    
    public function registerTypeInferenceExtension(string $class): void
    {
        $this->typeInferenceExtensions[] = $class;
    }
    
    public function registerTypeToSchemaExtension(string $class): void
    {
        $this->typeToSchemaExtensions[] = $class;
    }
    
    public function registerOperationTransformer(string $class): void
    {
        $this->operationTransformers[] = $class;
    }
    
    public function registerDocumentTransformer(string $class): void
    {
        $this->documentTransformers[] = $class;
    }
    
    public function getTypeInferenceExtensions(): array
    {
        return $this->typeInferenceExtensions;
    }
    
    public function getTypeToSchemaExtensions(): array
    {
        return $this->typeToSchemaExtensions;
    }
    
    public function getOperationTransformers(): array
    {
        return $this->operationTransformers;
    }
    
    public function getDocumentTransformers(): array
    {
        return $this->documentTransformers;
    }
}
```

### 8.2 Laravel 擴展註冊

```php
<?php

// 在 Laravel 中通過配置文件註冊
// config/scramble.php

return [
    'extensions' => [
        // 類型推斷擴展
        \App\Scramble\Extensions\CustomTypeInferenceExtension::class,
        
        // 類型到 Schema 擴展
        \App\Scramble\Extensions\CustomTypeToSchemaExtension::class,
        
        // 操作轉換器
        \App\Scramble\Extensions\CustomOperationTransformer::class,
    ],
];

// 或通過程式碼註冊
use Dedoc\Scramble\Scramble;

Scramble::registerExtension(CustomExtension::class);
```

### 8.3 Symfony 擴展註冊

```yaml
# config/packages/scramble.yaml

scramble:
    extensions:
        # 類型推斷擴展
        - App\Scramble\Extensions\CustomTypeInferenceExtension
        
        # 類型到 Schema 擴展
        - App\Scramble\Extensions\CustomTypeToSchemaExtension
        
        # 操作轉換器
        - App\Scramble\Extensions\CustomOperationTransformer
```

```php
<?php

// 或通過 CompilerPass 註冊
namespace App\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ScrambleExtensionPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $manager = $container->getDefinition(ExtensionManager::class);
        
        // 找到所有標記為 scramble.extension 的服務
        $extensions = $container->findTaggedServiceIds('scramble.extension');
        
        foreach ($extensions as $id => $tags) {
            $manager->addMethodCall('registerExtension', [$id]);
        }
    }
}
```

---

## 九、測試策略

### 9.1 測試層次

```
測試金字塔
    ┌─────────────────┐
    │   E2E Tests     │  少量端到端測試
    │   (Symfony/     │
    │    Laravel App) │
    ├─────────────────┤
    │ Integration     │  中等數量整合測試
    │    Tests        │
    │ (Adapter Layer) │
    ├─────────────────┤
    │   Unit Tests    │  大量單元測試
    │  (Core Layer)   │
    └─────────────────┘
```

### 9.2 核心層測試

```php
<?php

namespace Dedoc\Scramble\Tests\Core;

use PHPUnit\Framework\TestCase;
use Dedoc\Scramble\Abstractions\RouteInfo;
use Dedoc\Scramble\Abstractions\HandlerInfo;
use Dedoc\Scramble\Core\Generator\DocumentGenerator;

class DocumentGeneratorTest extends TestCase
{
    public function testGeneratesOpenApiDocument(): void
    {
        $routeExtractor = $this->createMock(RouteExtractorInterface::class);
        $routeExtractor->method('extractRoutes')
            ->willReturn([
                new RouteInfo(
                    '/api/users',
                    ['GET'],
                    HandlerInfo::fromClassMethod(UserController::class, 'index')
                ),
            ]);
        
        $generator = new DocumentGenerator(
            $routeExtractor,
            $this->createMock(RequestExtractorInterface::class),
            $this->createMock(ResponseExtractorInterface::class),
            $this->createMock(SecurityExtractorInterface::class),
        );
        
        $document = $generator->generate([
            'api_path' => '/api',
        ]);
        
        $this->assertArrayHasKey('openapi', $document);
        $this->assertEquals('3.1.0', $document['openapi']);
        $this->assertArrayHasKey('paths', $document);
    }
}
```

### 9.3 適配器層測試

```php
<?php

namespace Dedoc\Scramble\Tests\Symfony;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Dedoc\Scramble\Symfony\SymfonyRouteExtractor;

class SymfonyRouteExtractorTest extends KernelTestCase
{
    public function testExtractsSymfonyRoutes(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        
        $extractor = $container->get(SymfonyRouteExtractor::class);
        $routes = $extractor->extractRoutes([
            'api_path' => '/api',
        ]);
        
        $this->assertIsArray($routes);
        $this->assertNotEmpty($routes);
        
        foreach ($routes as $route) {
            $this->assertInstanceOf(RouteInfo::class, $route);
            $this->assertStringStartsWith('/api', $route->uri);
        }
    }
}
```

---

## 十、效能優化

### 10.1 快取策略

```php
<?php

namespace Dedoc\Scramble\Core\Cache;

/**
 * 文件快取管理器
 */
class DocumentCache
{
    public function __construct(
        private CacheInterface $cache,
        private int $ttl = 3600
    ) {}
    
    public function get(string $api, array $config): ?array
    {
        $key = $this->getCacheKey($api, $config);
        
        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }
        
        return null;
    }
    
    public function set(string $api, array $config, array $document): void
    {
        $key = $this->getCacheKey($api, $config);
        $this->cache->set($key, $document, $this->ttl);
    }
    
    public function clear(string $api): void
    {
        $pattern = "scramble:document:{$api}:*";
        $this->cache->deletePattern($pattern);
    }
    
    private function getCacheKey(string $api, array $config): string
    {
        $hash = md5(serialize($config));
        return "scramble:document:{$api}:{$hash}";
    }
}
```

### 10.2 延遲加載

```php
<?php

namespace Dedoc\Scramble\Core;

/**
 * 延遲類型分析
 */
class LazyTypeAnalyzer
{
    private array $analyzed = [];
    
    public function analyzeType(string $className): TypeInfo
    {
        if (isset($this->analyzed[$className])) {
            return $this->analyzed[$className];
        }
        
        $info = $this->doAnalyze($className);
        $this->analyzed[$className] = $info;
        
        return $info;
    }
}
```

---

## 十一、總結

本技術架構文件詳細說明了 Symfony-Scramble 的設計：

1. **分層架構**: 清晰的適配器、抽象、核心三層設計
2. **統一接口**: 框架無關的抽象層確保可擴展性
3. **雙框架支援**: 同時支援 Laravel 和 Symfony
4. **可擴展性**: 完善的擴展系統支援自定義功能
5. **可測試性**: 分層架構便於單元測試和整合測試
6. **效能優化**: 快取和延遲加載確保良好效能

該架構設計確保了：
- ✅ 代碼重用性高
- ✅ 維護成本低
- ✅ 易於擴展
- ✅ 框架切換成本低
- ✅ 未來支援更多框架的可能性
