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

**採用策略：完全遷移到 Symfony**

**目標**:
1. 將所有功能從 Laravel 遷移到 Symfony
2. 保持功能完整性，不減少任何現有特性
3. 採用 Symfony 編碼標準和最佳實踐
4. 使用 Symfony 測試慣例和工具

**理由**:
1. 專注於單一框架，降低維護複雜度
2. 充分利用 Symfony 生態系統和工具
3. 統一的代碼風格和測試標準
4. 更清晰的專案定位和目標用戶群

**不採用雙框架支援的原因**:
- 維護成本過高，需要同時支援兩套框架
- 測試複雜度倍增
- 代碼庫膨脹，難以保持一致性

### 2.2 架構設計

**新的 Symfony 專案架構**:

```
symfony-scramble/
├── src/
│   ├── Bundle/
│   │   └── ScrambleBundle.php           # Symfony Bundle 主類
│   ├── DependencyInjection/
│   │   ├── ScrambleExtension.php        # DI 擴展
│   │   ├── Configuration.php            # 配置定義
│   │   └── Compiler/                    # 編譯器通道
│   ├── Controller/
│   │   └── DocumentationController.php  # 文件查看控制器
│   ├── Command/
│   │   ├── GenerateCommand.php          # 生成文件命令
│   │   └── ExportCommand.php            # 導出文件命令
│   ├── Generator/
│   │   ├── DocumentGenerator.php        # 文件生成器
│   │   ├── OperationBuilder.php         # 操作建構器
│   │   └── SchemaBuilder.php            # Schema 建構器
│   ├── Extractor/
│   │   ├── RouteExtractor.php           # 路由提取器
│   │   ├── RequestExtractor.php         # 請求提取器
│   │   ├── ResponseExtractor.php        # 響應提取器
│   │   └── SecurityExtractor.php        # 安全提取器
│   ├── TypeInference/
│   │   ├── TypeInferenceEngine.php      # 類型推斷引擎
│   │   ├── TypeAnalyzer.php             # 類型分析器
│   │   └── Extension/                   # Symfony 特定擴展
│   │       ├── DoctrineEntityExtension.php
│   │       ├── SerializerExtension.php
│   │       ├── ValidatorExtension.php
│   │       └── FormTypeExtension.php
│   ├── EventSubscriber/
│   │   └── SecuritySubscriber.php       # 安全事件訂閱器
│   ├── Exception/                       # 異常類
│   ├── Model/                          # 數據模型
│   │   ├── OpenApi/                    # OpenAPI 模型
│   │   ├── RouteInfo.php
│   │   └── ParameterInfo.php
│   └── Resources/
│       ├── config/
│       │   └── services.yaml           # 服務配置
│       └── views/
│           └── documentation.html.twig # 文件視圖
├── config/
│   └── packages/
│       └── scramble.yaml                # Symfony 配置
├── tests/
│   ├── Unit/                           # 單元測試
│   ├── Integration/                    # 整合測試
│   ├── Functional/                     # 功能測試
│   └── Fixtures/                       # 測試 fixtures
├── phpunit.xml.dist                    # PHPUnit 配置
├── phpcs.xml.dist                      # PHP_CodeSniffer 配置
└── .php-cs-fixer.dist.php             # PHP-CS-Fixer 配置
```

**遵循 Symfony 最佳實踐**:
- 使用 Symfony Bundle 結構
- 依賴注入容器管理所有服務
- 事件驅動架構
- Twig 模板引擎
- Symfony 命令行工具
- PSR-12 編碼標準
- PHPUnit + Symfony 測試工具

---

## 三、詳細實施計劃

### 階段一：專案結構重組和 Bundle 建立 (第 1-2 週)

#### 3.1 創建 Symfony Bundle 基礎結構

**目標**: 建立符合 Symfony 標準的 Bundle 架構

**主要任務**:

1. **創建 ScrambleBundle 主類**
```php
namespace Dedoc\Scramble\Bundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ScrambleBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
    
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        // 註冊編譯器通道
    }
}
```

2. **設置 DependencyInjection**
   - 創建 `ScrambleExtension.php`
   - 定義 `Configuration.php`
   - 配置服務自動註冊

3. **設置專案基礎配置**
   - `composer.json` - 更新依賴為 Symfony
   - `phpunit.xml.dist` - Symfony 測試配置
   - `.php-cs-fixer.dist.php` - Symfony 編碼標準

#### 3.2 移除 Laravel 依賴

**目標**: 將所有 Laravel 特定代碼移除

**主要任務**:

1. **移除 Laravel 依賴包**
   - 移除 `illuminate/*` 依賴
   - 移除 `spatie/laravel-package-tools`
   - 添加 Symfony 依賴

2. **刪除 Laravel 特定文件**
   - 刪除 `ScrambleServiceProvider.php`
   - 刪除 `routes/web.php`
   - 刪除 `config/scramble.php` (Laravel 配置)

3. **移除 Laravel 中介層和 Facade**
   - 移除 `src/Http/Middleware/`
   - 移除所有 Facade 使用

### 階段二：核心功能遷移到 Symfony (第 3-5 週)

#### 3.3 路由提取器重寫

**目標**: 將 Laravel 路由提取改為 Symfony 路由提取

**原 Laravel 實現**:
```php
// 舊代碼 - 依賴 Laravel
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

$routes = RouteFacade::getRoutes();
```

**新 Symfony 實現**:
```php
namespace Dedoc\Scramble\Extractor;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Route;

class RouteExtractor
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
                $routes[] = $this->buildRouteInfo($name, $route);
            }
        }
        
        return $routes;
    }
    
    private function shouldIncludeRoute(Route $route, array $config): bool
    {
        // 使用 Symfony 路由匹配邏輯
        $path = $route->getPath();
        $apiPath = $config['api_path'] ?? '/api';
        
        return str_starts_with($path, $apiPath);
    }
}
```

### 階段三：類型推斷系統適配 (第 6-8 週)

#### 3.4 Symfony 特定類型擴展

**目標**: 將 Laravel 特定的類型推斷改為 Symfony 特定
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

### 4.1 路由系統遷移

**從 Laravel 遷移挑戰**:
- Laravel `Illuminate\Routing\Route` → Symfony `Symfony\Component\Routing\Route`
- 控制器格式不同（`Controller@method` vs `Controller::method`）
- 路由定義方式不同（檔案 vs 屬性/YAML）

**解決方案**:
- 重寫路由提取邏輯使用 Symfony RouterInterface
- 支援 PHP 屬性、註解和 YAML 路由定義
- 標準化控制器處理邏輯

### 4.2 依賴注入系統遷移

**從 Laravel 遷移挑戰**:
- Laravel Service Container → Symfony DI Container
- 服務註冊方式不同
- 自動裝配（autowiring）行為差異

**解決方案**:
- 使用 Symfony DependencyInjection 組件
- 在 `services.yaml` 中定義所有服務
- 啟用 Symfony 自動裝配和自動配置
- 使用構造函數注入而非 Facade

### 4.3 ORM 系統遷移

**從 Eloquent 到 Doctrine**:
- Active Record → Data Mapper 模式
- 屬性自動推斷方式不同
- 關聯定義語法不同

**解決方案**:
- 創建 `DoctrineEntityExtension` 類型擴展
- 從 Doctrine Metadata 提取屬性類型
- 支援 Doctrine 關聯（OneToMany, ManyToMany 等）
- 處理 Doctrine 代理類

### 4.4 請求驗證遷移

**從 Laravel Validation 到 Symfony Validator**:
- FormRequest 規則數組 → Constraint 屬性/註解
- 驗證錯誤格式不同

**解決方案**:
- 創建 `ValidatorExtension` 從屬性提取約束
- 支援常用約束：NotBlank, Length, Email, Choice 等
- 轉換約束為 OpenAPI 驗證規則
- 處理驗證組（validation groups）

### 4.5 資源序列化遷移

**從 JsonResource 到 Serializer**:
- JsonResource `toArray()` → Serializer Groups/Context
- 手動序列化 → 自動序列化

**解決方案**:
- 創建 `SerializerExtension` 處理序列化
- 支援 Serializer Groups
- 支援 MaxDepth 和 Circular Reference 處理
- 使用 Normalizer 接口分析輸出格式

### 4.6 安全和認證遷移

**從 Laravel Auth 到 Symfony Security**:
- Laravel Gates/Policies → Symfony Voters
- Middleware → Event Subscribers

**解決方案**:
- 使用 Symfony Security Bundle
- 創建 EventSubscriber 控制文件訪問
- 支援多種認證方式（JWT, Session, API Key）

### 4.7 測試框架遷移

**從 Laravel 測試到 Symfony 測試**:
- Laravel TestCase → Symfony WebTestCase/KernelTestCase
- 測試資料庫設置不同
- Fixtures 處理不同

**解決方案**:
- 使用 `WebTestCase` 進行功能測試
- 使用 `KernelTestCase` 進行單元測試
- 使用 Doctrine Fixtures Bundle 管理測試數據
- 遵循 Symfony 測試最佳實踐

---

## 五、Laravel 到 Symfony 組件遷移對照表

### 5.1 核心組件遷移

| 功能 | Laravel 原組件 | Symfony 新組件 | 備註 |
|-----|---------------|---------------|------|
| 路由 | `Illuminate\Routing\Route` | `Symfony\Component\Routing\Route` | 改用 RouterInterface |
| HTTP 請求 | `Illuminate\Http\Request` | `Symfony\Component\HttpFoundation\Request` | PSR-7 兼容 |
| HTTP 響應 | `Illuminate\Http\Response` | `Symfony\Component\HttpFoundation\Response` | PSR-7 兼容 |
| ORM | `Illuminate\Database\Eloquent\Model` | `Doctrine\ORM\Entity` | Data Mapper 模式 |
| 序列化 | `Illuminate\Http\Resources\Json\JsonResource` | `Symfony\Component\Serializer\Normalizer` | 基於註解/屬性 |
| 驗證 | `Illuminate\Validation\Validator` | `Symfony\Component\Validator\Validator` | 基於 Constraint |
| 服務註冊 | Service Provider | Bundle + DI Extension | 標準 Symfony 方式 |
| 中介層 | Middleware | EventSubscriber | 事件驅動 |
| 配置 | PHP 數組 | YAML/PHP | Symfony Config Component |
| 視圖 | Blade | Twig | Symfony 標準模板引擎 |

### 5.2 異常處理遷移

| 功能 | Laravel 異常 | Symfony 異常 | HTTP 狀態碼 |
|-----|-------------|-------------|-----------|
| 驗證失敗 | `ValidationException` | `ValidationFailedException` | 422 |
| 認證失敗 | `AuthenticationException` | `AuthenticationException` | 401 |
| 授權失敗 | `AuthorizationException` | `AccessDeniedException` | 403 |
| 找不到資源 | `ModelNotFoundException` | `EntityNotFoundException` | 404 |
| HTTP 錯誤 | `HttpException` | `HttpException` | 自定義 |
| 方法不允許 | `MethodNotAllowedHttpException` | `MethodNotAllowedHttpException` | 405 |

### 5.3 類型推斷擴展遷移

| 功能 | Laravel 擴展 | Symfony 擴展 | 說明 |
|-----|------------|-------------|------|
| 模型類型 | `ModelExtension` | `DoctrineEntityExtension` | Entity metadata |
| 請求類型 | `RequestExtension` | `SymfonyRequestExtension` | Request 對象屬性 |
| 響應資源 | `JsonResourceExtension` | `SerializerExtension` | Serializer groups |
| 驗證規則 | `ValidationRulesExtension` | `ValidatorExtension` | Validator constraints |
| 集合類型 | `EloquentBuilderExtension` | `DoctrineCollectionExtension` | Query builder |
| 分頁 | `PaginatorExtension` | `PaginatorExtension` | Pagerfanta 支援 |

---

## 六、Symfony 專案配置和使用

### 6.1 安裝

**Symfony 專案安裝**:
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

### 時程總覽（10 週）

| 階段 | 週次 | 主要任務 | 交付物 |
|-----|-----|---------|-------|
| 階段一 | 1-2 | 專案重組和 Bundle 建立 | Symfony Bundle、移除 Laravel 依賴 |
| 階段二 | 3-5 | 核心功能遷移 | 路由、請求、響應提取器 |
| 階段三 | 6-8 | 類型推斷和擴展 | Doctrine、Serializer、Validator 擴展 |
| 階段四 | 9 | 測試遷移 | Symfony 測試套件、PHPUnit 配置 |
| 階段五 | 10 | 文件和發布 | 文件、範例、CI/CD |

### 詳細時程

**第 1 週**: 專案結構重組
- 創建 Symfony Bundle 結構
- 更新 `composer.json`，移除 Laravel 依賴，添加 Symfony 依賴
- 建立 DependencyInjection 配置
- 創建基礎 `services.yaml`
- 設置 PHPUnit 和編碼標準配置

**第 2 週**: 移除 Laravel 組件
- 刪除 `ScrambleServiceProvider.php`
- 刪除 Laravel 路由文件
- 刪除 Laravel 中介層
- 移除所有 `Illuminate\*` 使用
- 移除 Facade 使用

**第 3 週**: Symfony 路由和請求處理
- 實現 `RouteExtractor` 使用 Symfony Router
- 實現 `RequestExtractor` 分析 Symfony Request
- 實現 `ResponseExtractor` 分析 Symfony Response
- 創建 `DocumentationController`
- 配置路由（屬性/YAML）

**第 4 週**: OpenAPI 生成器適配
- 重寫 `Generator` 類移除 Laravel 依賴
- 適配 `OperationBuilder` 使用 Symfony 組件
- 適配 `SchemaBuilder`
- 更新 OpenAPI 遍歷器

**第 5 週**: 核心類型推斷系統
- 保留並優化類型推斷引擎
- 移除 Laravel 特定類型推斷
- 創建基礎 Symfony 類型推斷
- 更新 PHPDoc 解析器

**第 6 週**: Doctrine Entity 支援
- 實現 `DoctrineEntityExtension`
- 從 Doctrine Metadata 提取屬性類型
- 支援 Entity 關聯
- 處理 Proxy 類

**第 7 週**: Serializer 和 Validator 支援
- 實現 `SerializerExtension`
- 支援 Serializer Groups 和 Context
- 實現 `ValidatorExtension`
- 從 Constraint 提取驗證規則

**第 8 週**: 異常處理和安全
- 實現 Symfony 異常轉換
- 創建 `SecuritySubscriber`
- 支援 Symfony Security 組件
- 實現訪問控制

**第 9 週**: 測試套件遷移
- 重寫測試使用 `WebTestCase` 和 `KernelTestCase`
- 創建測試 Fixtures
- 配置測試資料庫
- 達到 80% 測試覆蓋率
- 所有測試通過

**第 10 週**: 文件、CI/CD 和發布
- 更新 README.md
- 撰寫 Symfony 安裝和配置文件
- 創建範例應用
- 配置 GitHub Actions CI/CD
- 準備發布到 Packagist

---

## 八、風險評估與應對

### 8.1 技術風險

| 風險 | 可能性 | 影響 | 應對措施 |
|-----|-------|-----|---------|
| Symfony 路由提取複雜度 | 中 | 高 | 參考 Symfony Bundle 最佳實踐、逐步實現 |
| Doctrine Metadata 提取困難 | 中 | 中 | 研究 Doctrine ORM 內部機制、參考類似專案 |
| 類型推斷準確度下降 | 中 | 高 | 完整測試覆蓋、增量開發和驗證 |
| 效能問題 | 低 | 中 | Profiling、快取策略、延遲加載 |
| Serializer Groups 處理 | 中 | 中 | 深入研究 Serializer 組件、創建示例 |

### 8.2 專案風險

| 風險 | 可能性 | 影響 | 應對措施 |
|-----|-------|-----|---------|
| 時程延誤 | 中 | 中 | 階段性交付、MVP 優先、靈活調整 |
| 功能遺漏 | 中 | 高 | 詳細功能清單、逐項對照檢查 |
| 測試覆蓋不足 | 中 | 高 | TDD 開發方式、CI/CD 自動化 |
| 文件不完整 | 低 | 中 | 邊開發邊文件、最後統一審查 |
| 社群接受度 | 中 | 低 | 提供範例、詳細文件、積極回應反饋 |

---

## 九、成功標準

### 9.1 功能標準

- ✅ Symfony 應用可以通過 Composer 安裝 Scramble Bundle
- ✅ 自動發現和文件化所有 Symfony API 路由（屬性、註解、YAML）
- ✅ 完整支援 Doctrine Entity 類型推斷和關聯
- ✅ 完整支援 Symfony Validator 約束提取
- ✅ 完整支援 Symfony Serializer Groups 和配置
- ✅ 生成符合 OpenAPI 3.1.0 規範的文件
- ✅ UI 正確顯示文件（Twig 模板）
- ✅ 支援 Symfony 命令行工具導出文件
- ✅ 完全移除 Laravel 依賴，無遺留代碼
- ✅ 所有原有功能在 Symfony 中完整實現

### 9.2 品質標準

- ✅ 測試覆蓋率 > 80%
- ✅ 所有核心功能有單元測試（PHPUnit）
- ✅ 功能測試使用 Symfony WebTestCase
- ✅ 通過 PHPStan level 6 分析
- ✅ 符合 Symfony 編碼標準（PSR-12 + Symfony CS Fixer）
- ✅ 無 Symfony 棄用警告
- ✅ 所有測試在 CI/CD 中自動運行

### 9.3 文件標準

- ✅ 完整的 Symfony 安裝指南
- ✅ 詳細的配置選項說明
- ✅ API 使用範例和最佳實踐
- ✅ Symfony 屬性使用指南
- ✅ 故障排除文件
- ✅ Contributing 指南
- ✅ Changelog 記錄所有變更
- ✅ Symfony 使用範例
- ✅ 從 Laravel 遷移指南
- ✅ API 參考文件

---

## 十、未來擴展

### 10.1 短期（3-6 個月內）

- 支援更多 Symfony Bundles（FOSRestBundle、API Platform 等）
- 效能優化和智能快取機制
- 增強 Doctrine 支援（繼承、Embedded 等進階特性）
- API 版本控制支援
- 支援 Symfony UX 和 Live Components

### 10.2 長期（6-12 個月內）

- GraphQL API 文件生成支援
- 自動生成 API 測試案例
- API 使用統計和監控
- 多語言文件生成
- CLI 工具增強

---

## 十一、結論

本計劃提供了將 Laravel Scramble 完全遷移到 Symfony 框架的清晰路徑。通過：

1. **完全遷移策略**: 徹底從 Laravel 遷移到 Symfony，專注單一框架
2. **保持功能完整**: 確保所有原有功能在 Symfony 中完整實現
3. **Symfony 最佳實踐**: 採用 Symfony 標準的代碼風格和架構模式
4. **階段性實施**: 10 週分階段完成，降低風險
5. **測試驅動**: 完整的測試覆蓋確保品質

這個策略的優勢：
- ✅ 專注於 Symfony 生態系統，充分利用其優勢
- ✅ 降低維護成本，無需同時支援兩個框架
- ✅ 統一的代碼風格和測試標準
- ✅ 更清晰的專案定位和目標用戶群
- ✅ 更容易獲得 Symfony 社群支持

**重要注意事項**：
- 此為完全遷移，不保留 Laravel 支援
- 需要完整測試確保功能對等
- 建議為 Laravel 用戶提供遷移指南

預計 10 週完成遷移，之後持續優化和增強 Symfony 特定功能。
