# Symfony-Scramble 遷移計劃
## Laravel Scramble 到 Symfony 框架適配詳細計劃

**文件版本**: 1.0  
**建立日期**: 2025-11-04  
**專案**: symfony-scramble  
**目標**: 將 Laravel Scramble API 文件生成器適配到 Symfony 框架

---

## 一、專案概述

### 1.1 原始專案分析
**Laravel Scramble** 是一個為 Laravel 專案自動生成 OpenAPI 3.1.0 格式 API 文件的工具。主要特點：

- **無需手動註解**: 透過程式碼分析自動生成文件
- **類型推斷**: 使用 PHP Parser 和靜態分析推斷參數和返回類型
- **Laravel 深度整合**: 依賴 Laravel 的路由、服務提供者、中介層等核心組件
- **可擴展架構**: 支援自定義擴展和轉換器

### 1.2 核心架構元件

```
src/
├── ScrambleServiceProvider.php    # Laravel 服務提供者 (核心整合點)
├── Scramble.php                   # 主要 API 類
├── Generator.php                  # 文件生成器
├── GeneratorConfig.php            # 配置管理
├── OpenApiTraverser.php          # OpenAPI 結構遍歷
├── Attributes/                    # PHP 屬性定義
├── Configuration/                 # 配置類
├── Http/Middleware/              # Laravel 中介層
├── Infer/                        # 類型推斷系統
├── Support/                      # 支援類和擴展
│   ├── InferExtensions/          # Laravel 特定推斷擴展
│   ├── TypeToSchemaExtensions/   # 類型到 Schema 轉換
│   └── ExceptionToResponseExtensions/ # 異常處理
└── ...
```

### 1.3 Laravel 依賴統計

- **Illuminate 命名空間使用**: 227 處
- **主要依賴組件**:
  - `Illuminate\Routing\Route` - 路由系統
  - `Illuminate\Routing\Router` - 路由器
  - `Illuminate\Support\Facades\*` - Facade 模式
  - `Illuminate\Http\Request` - 請求對象
  - `Illuminate\Database\Eloquent\*` - ORM 系統
  - `Illuminate\Validation\*` - 驗證系統
  - `Illuminate\Auth\*` - 認證系統
  - Spatie Laravel Package Tools - Laravel 包開發工具

---

## 二、遷移策略

### 2.1 遷移方式選擇

**建議採用：雙框架支援策略**

**理由**:
1. 保留原有 Laravel 支援，避免破壞現有用戶
2. 逐步添加 Symfony 支援，降低風險
3. 共享核心類型推斷和 OpenAPI 生成邏輯
4. 便於維護和測試

**替代方案**（不建議）:
- 完全重寫：風險高，工作量大
- 分叉項目：維護成本高
- 僅 Symfony：放棄現有用戶群

### 2.2 架構設計

```
symfony-scramble/
├── src/
│   ├── Core/                     # 框架無關的核心邏輯 (新)
│   │   ├── Generator/            # 文件生成核心
│   │   ├── Analyzer/             # 代碼分析核心
│   │   ├── TypeInference/        # 類型推斷核心
│   │   └── OpenApi/              # OpenAPI 對象模型
│   ├── Laravel/                  # Laravel 適配器 (遷移現有代碼)
│   │   ├── ScrambleServiceProvider.php
│   │   ├── LaravelRouteExtractor.php
│   │   ├── LaravelTypeExtensions/
│   │   └── Middleware/
│   ├── Symfony/                  # Symfony 適配器 (新)
│   │   ├── ScrambleBundle.php
│   │   ├── SymfonyRouteExtractor.php
│   │   ├── SymfonyTypeExtensions/
│   │   ├── EventSubscriber/
│   │   └── DependencyInjection/
│   ├── Abstractions/             # 抽象接口層 (新)
│   │   ├── RouteExtractorInterface.php
│   │   ├── RequestExtractorInterface.php
│   │   ├── ResponseExtractorInterface.php
│   │   └── SecurityExtractorInterface.php
│   └── (現有其他目錄...)
├── config/
│   ├── scramble.php              # Laravel 配置
│   └── packages/
│       └── scramble.yaml         # Symfony 配置
└── tests/
    ├── Core/                     # 核心測試
    ├── Laravel/                  # Laravel 特定測試
    └── Symfony/                  # Symfony 特定測試
```

---

## 三、詳細實施計劃

### 階段一：核心抽象層建立 (第 1-2 週)

#### 3.1 創建框架抽象接口

**目標**: 定義框架無關的接口，使核心邏輯可在兩個框架間共享

**需要創建的接口**:

1. **RouteExtractorInterface**
```php
namespace Dedoc\Scramble\Abstractions;

interface RouteExtractorInterface
{
    /**
     * 提取所有 API 路由
     * @return RouteInfo[]
     */
    public function extractRoutes(array $config): array;
    
    /**
     * 獲取路由的 HTTP 方法
     */
    public function getMethods(object $route): array;
    
    /**
     * 獲取路由 URI
     */
    public function getUri(object $route): string;
    
    /**
     * 獲取路由處理器信息
     */
    public function getHandler(object $route): HandlerInfo;
}
```

2. **RequestExtractorInterface**
```php
namespace Dedoc\Scramble\Abstractions;

interface RequestExtractorInterface
{
    /**
     * 提取請求參數定義
     */
    public function extractParameters(HandlerInfo $handler): ParameterCollection;
    
    /**
     * 提取請求體定義
     */
    public function extractRequestBody(HandlerInfo $handler): ?RequestBodySchema;
}
```

3. **ResponseExtractorInterface**
```php
namespace Dedoc\Scramble\Abstractions;

interface ResponseExtractorInterface
{
    /**
     * 提取響應定義
     */
    public function extractResponses(HandlerInfo $handler): ResponseCollection;
}
```

4. **SecurityExtractorInterface**
```php
namespace Dedoc\Scramble\Abstractions;

interface SecurityExtractorInterface
{
    /**
     * 提取安全需求（認證、授權）
     */
    public function extractSecurityRequirements(object $route): array;
}
```

#### 3.2 重構核心邏輯為框架無關

**需要重構的類**:

1. **Generator.php** → **Core/Generator/DocumentGenerator.php**
   - 移除 Laravel Route 直接依賴
   - 使用 RouteExtractorInterface
   - 保留 OpenAPI 生成邏輯

2. **OpenApiTraverser.php** → **Core/OpenApi/Traverser.php**
   - 已基本框架無關，僅需少量調整

3. **Infer/** → **Core/TypeInference/**
   - 類型推斷系統基本框架無關
   - 僅框架特定擴展需分離

### 階段二：Laravel 適配器實現 (第 3-4 週)

#### 3.3 將現有 Laravel 代碼遷移到適配器層

**主要任務**:

1. **創建 Laravel 路由提取器**
```php
namespace Dedoc\Scramble\Laravel;

use Dedoc\Scramble\Abstractions\RouteExtractorInterface;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

class LaravelRouteExtractor implements RouteExtractorInterface
{
    public function extractRoutes(array $config): array
    {
        $routes = RouteFacade::getRoutes();
        // 使用現有的路由過濾邏輯
        return $this->filterApiRoutes($routes, $config);
    }
    
    // 實現其他接口方法...
}
```

2. **保留 Laravel 特定擴展**
   - `src/Support/InferExtensions/` → `src/Laravel/TypeExtensions/`
     - EloquentBuilderExtension
     - ModelExtension
     - JsonResourceExtension
     - RequestExtension
     - 等等...

3. **保留 Laravel 中介層和服務提供者**
   - `src/Http/Middleware/` → `src/Laravel/Middleware/`
   - `ScrambleServiceProvider.php` → `src/Laravel/ScrambleServiceProvider.php`

### 階段三：Symfony 適配器實現 (第 5-8 週)

#### 3.4 創建 Symfony Bundle

**目標**: 創建符合 Symfony 標準的 Bundle

1. **Bundle 類**
```php
namespace Dedoc\Scramble\Symfony;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ScrambleBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
```

2. **DependencyInjection 配置**
```php
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
        
        // 註冊服務
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yaml');
        
        // 設置參數
        $container->setParameter('scramble.config', $config);
    }
}
```

#### 3.5 實現 Symfony 路由提取器

```php
namespace Dedoc\Scramble\Symfony;

use Dedoc\Scramble\Abstractions\RouteExtractorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Route;

class SymfonyRouteExtractor implements RouteExtractorInterface
{
    public function __construct(
        private RouterInterface $router
    ) {}
    
    public function extractRoutes(array $config): array
    {
        $collection = $this->router->getRouteCollection();
        $routes = [];
        
        foreach ($collection as $name => $route) {
            if ($this->shouldIncludeRoute($route, $config)) {
                $routes[] = $this->createRouteInfo($name, $route);
            }
        }
        
        return $routes;
    }
    
    private function shouldIncludeRoute(Route $route, array $config): bool
    {
        $path = $route->getPath();
        $apiPath = $config['api_path'] ?? '/api';
        
        // 檢查路徑前綴
        if (!str_starts_with($path, $apiPath)) {
            return false;
        }
        
        // 檢查路由名稱排除規則
        // 檢查控制器排除規則
        
        return true;
    }
    
    public function getMethods(object $route): array
    {
        return $route->getMethods();
    }
    
    public function getUri(object $route): string
    {
        return $route->getPath();
    }
    
    public function getHandler(object $route): HandlerInfo
    {
        $defaults = $route->getDefaults();
        $controller = $defaults['_controller'] ?? null;
        
        if (is_string($controller) && str_contains($controller, '::')) {
            [$class, $method] = explode('::', $controller, 2);
            return new HandlerInfo($class, $method);
        }
        
        // 處理其他控制器格式
        return HandlerInfo::fromCallable($controller);
    }
}
```

#### 3.6 實現 Symfony 類型擴展

**需要創建的 Symfony 特定擴展**:

1. **DoctrineEntityExtension** (類似 Laravel 的 ModelExtension)
```php
namespace Dedoc\Scramble\Symfony\TypeExtensions;

use Dedoc\Scramble\Infer\Extensions\InferExtension;
use Doctrine\ORM\Mapping\Entity;

class DoctrineEntityExtension implements InferExtension
{
    public function shouldHandle(Type $type): bool
    {
        if (!$type instanceof ObjectType) {
            return false;
        }
        
        $reflection = new \ReflectionClass($type->name);
        return !empty($reflection->getAttributes(Entity::class));
    }
    
    public function handle(Type $type): Type
    {
        // 從 Doctrine metadata 推斷屬性類型
        // 類似 Laravel ModelExtension 的邏輯
    }
}
```

2. **SymfonyRequestExtension** (類似 Laravel RequestExtension)
```php
namespace Dedoc\Scramble\Symfony\TypeExtensions;

use Symfony\Component\HttpFoundation\Request;

class SymfonyRequestExtension implements InferExtension
{
    // 處理 Symfony Request 對象
    // 支援 $request->query, $request->request, $request->headers 等
}
```

3. **SerializerExtension** (類似 Laravel JsonResource)
```php
namespace Dedoc\Scramble\Symfony\TypeExtensions;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SerializerExtension implements InferExtension
{
    // 處理 Symfony Serializer 序列化的對象
}
```

4. **ValidatorExtension** (類似 Laravel Validation)
```php
namespace Dedoc\Scramble\Symfony\TypeExtensions;

use Symfony\Component\Validator\Constraint;

class ValidatorExtension implements InferExtension
{
    // 從 Symfony Validator 註解提取驗證規則
}
```

#### 3.7 實現 Symfony 異常處理

```php
namespace Dedoc\Scramble\Symfony\ExceptionExtensions;

use Dedoc\Scramble\Extensions\ExceptionToResponseExtension;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class SymfonyHttpExceptionExtension implements ExceptionToResponseExtension
{
    public function shouldHandle(string $exceptionClass): bool
    {
        return is_subclass_of($exceptionClass, HttpException::class);
    }
    
    public function toResponse(Type $type): Response
    {
        // 轉換 Symfony HTTP 異常為 OpenAPI 響應
    }
}
```

#### 3.8 實現 Symfony 控制器

```php
namespace Dedoc\Scramble\Symfony\Controller;

use Dedoc\Scramble\Core\Generator\DocumentGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ScrambleController extends AbstractController
{
    public function __construct(
        private DocumentGenerator $generator,
        private array $config
    ) {}
    
    #[Route('/docs/api', name: 'scramble_docs_ui', methods: ['GET'])]
    public function docsUi(): Response
    {
        $spec = $this->generator->generate($this->config);
        
        return $this->render('@Scramble/docs.html.twig', [
            'spec' => $spec,
            'config' => $this->config,
        ]);
    }
    
    #[Route('/docs/api.json', name: 'scramble_docs_json', methods: ['GET'])]
    public function docsJson(): JsonResponse
    {
        $spec = $this->generator->generate($this->config);
        
        return new JsonResponse($spec, 200, [], JSON_PRETTY_PRINT);
    }
}
```

#### 3.9 創建 Symfony 配置文件

**config/packages/scramble.yaml**:
```yaml
scramble:
    # API 路徑前綴
    api_path: '/api'
    
    # API 域名
    api_domain: null
    
    # OpenAPI 規範導出路徑
    export_path: 'api.json'
    
    # API 信息
    info:
        version: '1.0.0'
        title: 'API Documentation'
        description: 'Auto-generated API documentation'
    
    # UI 配置
    ui:
        title: null
        theme: 'light'  # light, dark, system
        hide_try_it: false
        hide_schemas: false
        layout: 'responsive'  # sidebar, responsive, stacked
    
    # 伺服器配置
    servers: null
    
    # 中介層
    middleware: []
    
    # 擴展
    extensions: []
    
    # 安全配置
    security:
        enabled: true
        voter: 'scramble.security.voter'
    
    # 路由過濾
    routes:
        include_patterns: ['^/api']
        exclude_patterns: []
        exclude_controllers: []
```

**services.yaml**:
```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
    
    # 核心服務
    Dedoc\Scramble\Core\Generator\DocumentGenerator:
        arguments:
            $routeExtractor: '@Dedoc\Scramble\Symfony\SymfonyRouteExtractor'
    
    # 路由提取器
    Dedoc\Scramble\Symfony\SymfonyRouteExtractor:
        arguments:
            $router: '@router'
    
    # 控制器
    Dedoc\Scramble\Symfony\Controller\ScrambleController:
        arguments:
            $generator: '@Dedoc\Scramble\Core\Generator\DocumentGenerator'
            $config: '%scramble.config%'
        tags: ['controller.service_arguments']
    
    # 類型推斷
    Dedoc\Scramble\Core\TypeInference\Infer: ~
    
    # Symfony 特定擴展
    Dedoc\Scramble\Symfony\TypeExtensions\DoctrineEntityExtension: ~
    Dedoc\Scramble\Symfony\TypeExtensions\SymfonyRequestExtension: ~
    Dedoc\Scramble\Symfony\TypeExtensions\SerializerExtension: ~
    Dedoc\Scramble\Symfony\TypeExtensions\ValidatorExtension: ~
```

### 階段四：屬性和註解支援 (第 9 週)

#### 3.10 Symfony 特定屬性

創建 Symfony 風格的 PHP 屬性：

```php
namespace Dedoc\Scramble\Symfony\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class ApiEndpoint
{
    public function __construct(
        public ?string $summary = null,
        public ?string $description = null,
        public ?array $tags = null,
    ) {}
}

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ApiResponse
{
    public function __construct(
        public int $status,
        public string $description,
        public ?string $content = null,
    ) {}
}

#[Attribute(Attribute::TARGET_PARAMETER)]
class ApiParameter
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?string $type = null,
        public bool $required = false,
    ) {}
}
```

### 階段五：測試和文件 (第 10-12 週)

#### 3.11 測試策略

1. **核心測試**（框架無關）
   - TypeInference 測試
   - OpenAPI 生成測試
   - Schema 驗證測試

2. **Laravel 測試**（保留現有測試）
   - 路由提取測試
   - Eloquent 類型推斷測試
   - JsonResource 測試

3. **Symfony 測試**（新增）
   - Symfony 路由提取測試
   - Doctrine 實體類型推斷測試
   - Serializer 測試
   - 整合測試

**測試範例**:
```php
namespace Dedoc\Scramble\Tests\Symfony;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SymfonyRouteExtractorTest extends WebTestCase
{
    public function testExtractsSymfonyRoutes(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();
        
        $extractor = $container->get(SymfonyRouteExtractor::class);
        $routes = $extractor->extractRoutes([
            'api_path' => '/api'
        ]);
        
        $this->assertNotEmpty($routes);
        $this->assertContainsOnlyInstancesOf(RouteInfo::class, $routes);
    }
}
```

#### 3.12 文件更新

1. **README.md**
   - 添加 Symfony 安裝說明
   - 更新使用範例
   - 說明雙框架支援

2. **新增文件**
   - `docs/symfony-installation.md` - Symfony 安裝指南
   - `docs/symfony-configuration.md` - Symfony 配置指南
   - `docs/symfony-examples.md` - Symfony 使用範例
   - `docs/symfony-migration-guide.md` - 從 Laravel 遷移指南

---

## 四、技術挑戰與解決方案

### 4.1 路由系統差異

**挑戰**:
- Laravel: `Illuminate\Routing\Route`，簡單的閉包和控制器方法
- Symfony: `Symfony\Component\Routing\Route`，更複雜的控制器格式

**解決方案**:
- 創建統一的 `RouteInfo` 抽象
- 使用適配器模式處理差異
- 在路由提取器中規範化控制器格式

### 4.2 依賴注入差異

**挑戰**:
- Laravel: 服務容器（Service Container）
- Symfony: 依賴注入容器（DI Container）

**解決方案**:
- 核心邏輯不直接使用容器
- 在適配器層處理依賴解析
- 使用接口而非具體實現

### 4.3 ORM 差異

**挑戰**:
- Laravel: Eloquent ORM
- Symfony: Doctrine ORM

**解決方案**:
- 創建框架特定的模型類型擴展
- Eloquent → `ModelExtension`
- Doctrine → `DoctrineEntityExtension`
- 兩者返回相同的抽象類型表示

### 4.4 請求驗證差異

**挑戰**:
- Laravel: FormRequest 類，驗證規則數組
- Symfony: Validator 組件，註解/屬性

**解決方案**:
- Laravel: 從 FormRequest 的 `rules()` 方法提取
- Symfony: 從實體或 DTO 的 Validator 約束提取
- 統一轉換為 OpenAPI Schema

### 4.5 資源序列化差異

**挑戰**:
- Laravel: JsonResource，`toArray()` 方法
- Symfony: Serializer 組件，註解配置

**解決方案**:
- Laravel: 分析 JsonResource 的 `toArray()` 方法
- Symfony: 解析 Serializer 配置（Groups, MaxDepth 等）
- 統一轉換為 Schema 對象

### 4.6 中介層/事件監聽器

**挑戰**:
- Laravel: 中介層（Middleware）
- Symfony: 事件訂閱器（EventSubscriber）

**解決方案**:
- Laravel: 保留現有中介層
- Symfony: 創建事件訂閱器
- 兩者實現相同的安全控制邏輯

---

## 五、組件對應關係

### 5.1 核心組件映射

| Laravel 組件 | Symfony 對應組件 | 抽象接口 |
|-------------|-----------------|---------|
| `Illuminate\Routing\Route` | `Symfony\Component\Routing\Route` | `RouteExtractorInterface` |
| `Illuminate\Http\Request` | `Symfony\Component\HttpFoundation\Request` | `RequestExtractorInterface` |
| `Illuminate\Http\Response` | `Symfony\Component\HttpFoundation\Response` | `ResponseExtractorInterface` |
| `Illuminate\Database\Eloquent\Model` | `Doctrine\ORM\Entity` | `ModelTypeExtension` |
| `Illuminate\Http\Resources\Json\JsonResource` | `Symfony\Component\Serializer\Normalizer` | `SerializerTypeExtension` |
| `Illuminate\Validation\Validator` | `Symfony\Component\Validator\Validator` | `ValidatorExtension` |
| Laravel Service Provider | Symfony Bundle | - |
| Laravel Middleware | Symfony EventSubscriber | - |

### 5.2 例外處理映射

| Laravel 異常 | Symfony 異常 | OpenAPI 響應 |
|-------------|-------------|------------|
| `ValidationException` | `Symfony\Component\Validator\Exception\ValidationFailedException` | 422 Unprocessable Entity |
| `AuthenticationException` | `Symfony\Component\Security\Core\Exception\AuthenticationException` | 401 Unauthorized |
| `AuthorizationException` | `Symfony\Component\Security\Core\Exception\AccessDeniedException` | 403 Forbidden |
| `ModelNotFoundException` | `Doctrine\ORM\EntityNotFoundException` | 404 Not Found |
| `HttpException` | `Symfony\Component\HttpKernel\Exception\HttpException` | 對應狀態碼 |

---

## 六、配置和使用差異

### 6.1 安裝

**Laravel**:
```bash
composer require dedoc/scramble
```

**Symfony**:
```bash
composer require dedoc/scramble
# 啟用 Bundle（Symfony Flex 自動處理）
# 或手動添加到 config/bundles.php
```

### 6.2 配置

**Laravel** (`config/scramble.php`):
```php
return [
    'api_path' => 'api',
    'middleware' => ['web', RestrictedDocsAccess::class],
    // ...
];
```

**Symfony** (`config/packages/scramble.yaml`):
```yaml
scramble:
    api_path: '/api'
    middleware: []
    security:
        enabled: true
```

### 6.3 路由註冊

**Laravel**:
```php
// 自動註冊（Service Provider）
// 或手動：
Scramble::registerUiRoute('/docs/api');
```

**Symfony**:
```php
// 自動註冊（Bundle 配置）
// 或在 routes.yaml 中：
scramble_docs_ui:
    path: /docs/api
    controller: Dedoc\Scramble\Symfony\Controller\ScrambleController::docsUi
```

---

## 七、實施時程

### 時程總覽（12 週）

| 階段 | 週次 | 主要任務 | 交付物 |
|-----|-----|---------|-------|
| 階段一 | 1-2 | 核心抽象層建立 | 抽象接口、核心類重構 |
| 階段二 | 3-4 | Laravel 適配器 | Laravel 代碼遷移、向後兼容 |
| 階段三 | 5-8 | Symfony 適配器 | Symfony Bundle、類型擴展 |
| 階段四 | 9 | 屬性和註解 | Symfony 屬性、文件增強 |
| 階段五 | 10-12 | 測試和文件 | 完整測試套件、用戶文件 |

### 詳細時程

**第 1 週**: 設計和實現抽象接口
- 設計 RouteExtractorInterface 等接口
- 創建 HandlerInfo、RouteInfo 等統一數據結構
- 建立 Core 命名空間結構

**第 2 週**: 重構核心邏輯
- 重構 Generator.php 移除 Laravel 直接依賴
- 重構 OpenApiTraverser.php
- 將 Infer 系統移至 Core

**第 3 週**: Laravel 適配器 - 路由和請求
- 實現 LaravelRouteExtractor
- 實現 LaravelRequestExtractor
- 遷移路由相關代碼

**第 4 週**: Laravel 適配器 - 類型和驗證
- 遷移 InferExtensions 到 Laravel/TypeExtensions
- 遷移 TypeToSchemaExtensions
- 確保向後兼容

**第 5 週**: Symfony Bundle 基礎
- 創建 ScrambleBundle
- 實現 DependencyInjection
- 創建服務配置

**第 6 週**: Symfony 路由提取
- 實現 SymfonyRouteExtractor
- 實現 SymfonyRequestExtractor
- 實現 SymfonyResponseExtractor

**第 7 週**: Symfony 類型擴展（1）
- DoctrineEntityExtension
- SymfonyRequestExtension
- ValidatorExtension

**第 8 週**: Symfony 類型擴展（2）
- SerializerExtension
- 異常處理擴展
- 控制器實現

**第 9 週**: 屬性和增強
- Symfony 屬性定義
- 註解支援
- 文件增強功能

**第 10 週**: 核心和 Laravel 測試
- 核心邏輯測試
- Laravel 適配器測試
- 向後兼容性測試

**第 11 週**: Symfony 測試
- Symfony 適配器測試
- Doctrine 整合測試
- 端到端測試

**第 12 週**: 文件和發布準備
- 用戶文件撰寫
- 範例應用
- 發布準備

---

## 八、風險評估與應對

### 8.1 技術風險

| 風險 | 可能性 | 影響 | 應對措施 |
|-----|-------|-----|---------|
| 核心重構破壞 Laravel 功能 | 中 | 高 | 完整的回歸測試、逐步重構 |
| Symfony 類型推斷複雜度高 | 高 | 中 | 先實現基礎功能、逐步增強 |
| Doctrine 元數據提取困難 | 中 | 中 | 參考成功案例、社群諮詢 |
| 效能問題 | 低 | 中 | 效能測試、快取優化 |

### 8.2 專案風險

| 風險 | 可能性 | 影響 | 應對措施 |
|-----|-------|-----|---------|
| 時程延誤 | 中 | 中 | 階段性交付、優先級排序 |
| 資源不足 | 低 | 高 | 提前規劃、社群貢獻 |
| 需求變更 | 中 | 中 | 敏捷開發、定期審查 |
| 測試覆蓋不足 | 中 | 高 | TDD、持續整合 |

---

## 九、成功標準

### 9.1 功能標準

- ✅ Symfony 應用可以安裝和配置 Scramble
- ✅ 自動發現和文件化 Symfony API 路由
- ✅ 支援 Doctrine 實體類型推斷
- ✅ 支援 Symfony Validator 驗證規則提取
- ✅ 支援 Symfony Serializer 序列化配置
- ✅ 生成正確的 OpenAPI 3.1.0 文件
- ✅ UI 正確顯示 Symfony API 文件
- ✅ 向後兼容 Laravel 使用者

### 9.2 品質標準

- ✅ 測試覆蓋率 > 80%
- ✅ 所有核心功能有單元測試
- ✅ 每個適配器有整合測試
- ✅ 通過 PHPStan level 5 分析
- ✅ 符合 PSR-12 編碼標準

### 9.3 文件標準

- ✅ 完整的 Symfony 安裝指南
- ✅ Symfony 配置選項文件
- ✅ Symfony 使用範例
- ✅ 從 Laravel 遷移指南
- ✅ API 參考文件

---

## 十、未來擴展

### 10.1 短期（6 個月內）

- 支援更多 Symfony 特性（Security voters、FOSRestBundle 等）
- 效能優化和快取機制
- 更豐富的 Doctrine 支援（關聯、繼承等）
- API 版本控制支援

### 10.2 長期（1 年內）

- 其他 PHP 框架支援（Slim、Lumen 等）
- GraphQL 支援
- 自動生成測試案例
- API 監控和分析

---

## 十一、結論

本計劃提供了將 Laravel Scramble 適配到 Symfony 框架的完整路徑。通過：

1. **抽象層設計**: 將框架特定邏輯與核心邏輯分離
2. **適配器模式**: 為每個框架提供專門的適配器
3. **雙框架支援**: 保持 Laravel 支援同時添加 Symfony 支援
4. **階段性實施**: 降低風險，便於測試和驗證

這個策略確保了：
- 現有 Laravel 用戶不受影響
- Symfony 用戶獲得完整功能
- 代碼維護性和可擴展性
- 未來支援更多框架的可能性

預計 12 週完成基礎實施，之後持續優化和擴展功能。
