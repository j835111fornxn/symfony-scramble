# 實作任務清單

## 1. 專案設定和依賴項

- [ ] 1.1 更新 composer.json 以要求 Symfony 套件（^6.4|^7.0）
  - [ ] 新增 symfony/dependency-injection
  - [ ] 新增 symfony/config
  - [ ] 新增 symfony/http-kernel
  - [ ] 新增 symfony/http-foundation
  - [ ] 新增 symfony/routing
  - [ ] 新增 symfony/event-dispatcher
  - [ ] 新增 symfony/validator
  - [ ] 新增 symfony/serializer
  - [ ] 新增 symfony/twig-bundle
  - [ ] 新增 symfony/console
  - [ ] 新增 doctrine/orm
  - [ ] 新增 doctrine/doctrine-bundle
- [ ] 1.2 從 composer.json 移除 Laravel 依賴項
  - [ ] 移除 illuminate/contracts
  - [ ] 移除 illuminate/routing
  - [ ] 移除 illuminate/http
  - [ ] 移除 illuminate/database
  - [ ] 移除 illuminate/validation
  - [ ] 移除 illuminate/view
  - [ ] 移除 illuminate/support
  - [ ] 移除 spatie/laravel-package-tools
- [ ] 1.3 更新測試的開發依賴項
  - [ ] 移除 orchestra/testbench
  - [ ] 新增 symfony/test-pack 或 symfony/phpunit-bridge
  - [ ] 新增 symfony/browser-kit 用於功能測試
- [ ] 1.4 如需要，更新最低 PHP 版本約束
- [ ] 1.5 執行 composer update 並解決任何衝突

## 2. Bundle 建立

- [ ] 2.1 建立 `src/ScrambleBundle.php`，繼承 Symfony\Component\HttpKernel\Bundle\Bundle
- [ ] 2.2 實作 `build()` 方法以註冊編譯器 pass
- [ ] 2.3 實作 `boot()` 方法用於路由註冊
- [ ] 2.4 建立 `src/DependencyInjection/ScrambleExtension.php` 用於配置
- [ ] 2.5 建立 `src/DependencyInjection/Configuration.php` 用於配置樹
- [ ] 2.6 建立 `src/DependencyInjection/Compiler/ScrambleExtensionPass.php` 用於擴充發現
- [ ] 2.7 建立 `Resources/config/services.yaml` 用於服務定義
- [ ] 2.8 配置服務自動裝配和自動配置
- [ ] 2.9 為擴充定義服務標籤（scramble.infer_extension、scramble.type_to_schema_extension 等）

## 3. 配置遷移

- [ ] 3.1 將 config/scramble.php 轉換為 Resources/config/packages/scramble.yaml
- [ ] 3.2 更新 Configuration.php 以定義配置樹
- [ ] 3.3 為複雜設定建立配置驗證器
- [ ] 3.4 同時支援 YAML 和 PHP 配置格式
- [ ] 3.5 更新 ScrambleExtension 以處理和正規化配置
- [ ] 3.6 使用無效值測試配置驗證

## 4. 路由整合

- [ ] 4.1 更新 Scramble.php 以使用 Symfony Router 而非 Route facade
- [ ] 4.2 建立服務從 RouterInterface::getRouteCollection() 檢索路由
- [ ] 4.3 根據 api_path 和 api_domain 配置實作路由過濾
- [ ] 4.4 更新 ReflectionRoute.php 以使用 Symfony\Component\Routing\Route
- [ ] 4.5 新增對控制器上路由屬性（#[Route]）的支援
- [ ] 4.6 從路由預設值實作控制器方法解析
- [ ] 4.7 新增對可呼叫控制器的支援
- [ ] 4.8 提取路由參數要求並對應到 OpenAPI 約束
- [ ] 4.9 處理帶有預設值的可選路由參數
- [ ] 4.10 更新路由解析的測試

## 5. 服務提供者到 Bundle 遷移

- [ ] 5.1 移除 ScrambleServiceProvider.php
- [ ] 5.2 將單例註冊移至 services.yaml
- [ ] 5.3 將服務綁定（when/needs/give）移至服務配置
- [ ] 5.4 將基於 facade 的註冊（RouteFacade）轉換為服務注入
- [ ] 5.5 更新所有 app() 呼叫以使用依賴注入
- [ ] 5.6 移除任何剩餘的 Laravel 容器綁定
- [ ] 5.7 更新擴充註冊以使用服務標籤
- [ ] 5.8 測試服務解析和依賴注入

## 6. 中介層到事件系統遷移

- [ ] 6.1 移除 src/Http/Middleware/RestrictedDocsAccess.php
- [ ] 6.2 建立 src/EventSubscriber/DocumentationAccessSubscriber.php
- [ ] 6.3 實作 onKernelRequest() 以檢查存取控制
- [ ] 6.4 與 Symfony Security 元件整合以進行授權
- [ ] 6.5 支援基於角色的存取控制（ROLE_ADMIN 等）
- [ ] 6.6 支援基於環境的存取控制（僅限開發環境等）
- [ ] 6.7 為預生成鉤子建立事件（scramble.generation.start）
- [ ] 6.8 為後生成鉤子建立事件（scramble.generation.complete）
- [ ] 6.9 為操作生成建立事件（scramble.operation.generated）
- [ ] 6.10 為擴充開發者記錄事件系統

## 7. 視圖層遷移

- [ ] 7.1 從 resources/views/docs.blade.php 建立 templates/docs.html.twig
- [ ] 7.2 將 Blade 語法轉換為 Twig 語法
  - [ ] {{ $var }} → {{ var }}
  - [ ] @if/@endif → {% if %}/{% endif %}
  - [ ] @json() → |json_encode|raw 過濾器
- [ ] 7.3 使用 @Scramble 命名空間配置 Twig 載入器
- [ ] 7.4 更新模板變數傳遞
- [ ] 7.5 使用範例資料測試模板渲染
- [ ] 7.6 記錄模板覆蓋過程

## 8. 驗證整合

- [ ] 8.1 移除基於 FormRequest 的驗證推斷
- [ ] 8.2 建立 DoctrineMetadataExtractor 服務用於實體元數據
- [ ] 8.3 建立 ConstraintExtractor 服務用於 Symfony Validator 約束
- [ ] 8.4 為常見約束實作約束到模式的轉換器：
  - [ ] NotBlank → 必填屬性
  - [ ] Length → minLength/maxLength
  - [ ] Range → minimum/maximum
  - [ ] Email → format: email
  - [ ] Regex → pattern
  - [ ] Count → minItems/maxItems
  - [ ] Choice → enum
- [ ] 8.5 新增對驗證群組的支援
- [ ] 8.6 新增對 Form 類型作為請求主體模式的支援
- [ ] 8.7 實作巢狀表單類型處理
- [ ] 8.8 更新驗證推斷的測試

## 9. ORM 遷移（Eloquent 到 Doctrine）

- [ ] 9.1 移除所有 Eloquent 特定的擴充
- [ ] 9.2 建立 DoctrineEntityExtension 用於實體類型推斷
- [ ] 9.3 實作欄位類型對應（Doctrine 類型 → OpenAPI 類型）
- [ ] 9.4 實作關聯處理（ManyToOne、OneToMany、ManyToMany）
- [ ] 9.5 從元數據提取欄位可空性
- [ ] 9.6 處理自訂 Doctrine 類型
- [ ] 9.7 更新 ModelExtension 以使用 Doctrine 實體
- [ ] 9.8 移除 EloquentBuilderExtension
- [ ] 9.9 如需要，建立 DoctrineRepositoryExtension
- [ ] 9.10 更新 Doctrine 實體推斷的測試

## 10. 序列化整合

- [ ] 10.1 移除 JsonResource 特定的擴充
- [ ] 10.2 建立 SymfonySerializerExtension 用於回應推斷
- [ ] 10.3 實作序列化群組支援
- [ ] 10.4 處理 SerializedName 屬性
- [ ] 10.5 處理 Ignore 屬性
- [ ] 10.6 在可能的情況下支援自訂正規化器推斷
- [ ] 10.7 為 Symfony 回應更新 ResourceResponseTypeToSchema
- [ ] 10.8 更新或完全替換 JsonResourceTypeToSchema
- [ ] 10.9 移除 PaginatedResourceResponseTypeToSchema（Laravel 特定）
- [ ] 10.10 測試基於序列化的模式生成

## 11. 例外處理遷移

- [ ] 11.1 為 Symfony ValidationFailedException 更新 ValidationExceptionToResponseExtension
- [ ] 11.2 為 Symfony AuthenticationException 更新 AuthenticationExceptionToResponseExtension
- [ ] 11.3 為 Symfony AccessDeniedException 更新 AuthorizationExceptionToResponseExtension
- [ ] 11.4 為 Symfony HttpException 更新 HttpExceptionToResponseExtension
- [ ] 11.5 為 Symfony NotFoundHttpException 更新 NotFoundExceptionToResponseExtension
- [ ] 11.6 新增例外事件訂閱器用於錯誤處理
- [ ] 11.7 測試例外到回應的轉換

## 12. 輔助函式替換

- [ ] 12.1 將所有 Illuminate\Support\Arr 的使用替換為原生 PHP 陣列函式或 Symfony ArrayUtil
- [ ] 12.2 將所有 Illuminate\Support\Str 的使用替換為 Symfony String 元件
- [ ] 12.3 將所有 Illuminate\Support\Collection 的使用替換為 Doctrine\Common\Collections 或陣列
- [ ] 12.4 移除 app() 呼叫並使用依賴注入
- [ ] 12.5 移除 config() 呼叫並注入配置
- [ ] 12.6 移除 view() 呼叫並注入 Twig 環境
- [ ] 12.7 移除 response() 呼叫並回傳 Symfony Response 物件
- [ ] 12.8 更新整個程式碼庫中的所有輔助函式使用

## 13. 推斷擴充遷移

- [ ] 13.1 為 Symfony 回應更新 ResponseMethodReturnTypeExtension
- [ ] 13.2 移除或調整 JsonResourceExtension
- [ ] 13.3 移除 ResourceResponseMethodReturnTypeExtension（Laravel 特定）
- [ ] 13.4 為 Symfony JsonResponse 更新 JsonResponseMethodReturnTypeExtension
- [ ] 13.5 將 ModelExtension 替換為基於實體的擴充
- [ ] 13.6 將 EloquentBuilderExtension 替換為儲存庫擴充
- [ ] 13.7 為 Symfony Request 更新 RequestExtension
- [ ] 13.8 移除 JsonResource 相關的定義擴充
- [ ] 13.9 移除 PaginateMethodsReturnTypeExtension（Laravel 特定）
- [ ] 13.10 如需要，更新 ArrayMergeReturnTypeExtension
- [ ] 13.11 為 Symfony 拋出模式更新 abort 輔助函式擴充
- [ ] 13.12 測試所有類型推斷擴充

## 14. 類型到模式擴充遷移

- [ ] 14.1 保留 EnumToSchema（PHP enum 的工作方式相同）
- [ ] 14.2 移除或替換 JsonResourceTypeToSchema
- [ ] 14.3 將 ModelToSchema 替換為基於實體的模式生成器
- [ ] 14.4 為 Doctrine 集合更新 CollectionToSchema
- [ ] 14.5 移除 EloquentCollectionToSchema
- [ ] 14.6 移除 ResourceCollectionTypeToSchema（Laravel 特定）
- [ ] 14.7 移除分頁器相關模式（Laravel 特定）或為 Symfony 分頁進行調整
- [ ] 14.8 為 Symfony Response 更新 ResponseTypeToSchema
- [ ] 14.9 保留 BinaryFileResponseToSchema（可能原樣工作）
- [ ] 14.10 為 Symfony StreamedResponse 更新 StreamedResponseToSchema
- [ ] 14.11 移除 ResourceResponseTypeToSchema 和 PaginatedResourceResponseTypeToSchema
- [ ] 14.12 保留 VoidTypeToSchema
- [ ] 14.13 測試所有類型的模式生成

## 15. 主控台命令遷移

- [ ] 15.1 更新 AnalyzeDocumentation 命令以繼承 Symfony Command
- [ ] 15.2 更新 ExportDocumentation 命令以繼承 Symfony Command
- [ ] 15.3 移除 Laravel 特定的命令設定
- [ ] 15.4 在 services.yaml 中使用命令標籤註冊命令
- [ ] 15.5 更新命令 I/O 以使用 Symfony Console Style
- [ ] 15.6 在 Symfony 主控台中測試命令

## 16. 測試框架遷移

- [ ] 16.1 移除繼承 Orchestra\Testbench 的 tests/TestCase.php
- [ ] 16.2 建立繼承 Symfony KernelTestCase 的 tests/ScrambleKernelTestCase.php
- [ ] 16.3 建立載入 ScrambleBundle 的測試核心
- [ ] 16.4 建立測試應用程式配置
- [ ] 16.5 更新所有測試類別以使用新的測試案例
- [ ] 16.6 將 $this->app 替換為 static::getContainer()
- [ ] 16.7 為 Symfony 替換路由註冊模式
- [ ] 16.8 更新測試固件（控制器、實體等）
- [ ] 16.9 為 Symfony 模式更新測試斷言
- [ ] 16.10 確保所有測試在 Symfony 中通過

## 17. 路由註冊

- [ ] 17.1 移除 routes/web.php
- [ ] 17.2 為文件路由建立 Resources/config/routes.yaml
- [ ] 17.3 在 bundle boot() 方法中實作路由註冊
- [ ] 17.4 根據配置支援動態路由註冊
- [ ] 17.5 為 UI 和 JSON 規格端點建立控制器
- [ ] 17.6 測試路由註冊和存取

## 18. 文件更新

- [ ] 18.1 使用 Symfony 安裝說明更新 README.md
- [ ] 18.2 建立從 Laravel 到 Symfony 版本的 MIGRATION.md 指南
- [ ] 18.3 記錄 bundle 配置選項
- [ ] 18.4 記錄事件系統和擴充點
- [ ] 18.5 更新所有程式碼範例以使用 Symfony 模式
- [ ] 18.6 記錄 Doctrine 實體使用
- [ ] 18.7 記錄 Form 類型使用
- [ ] 18.8 記錄 Symfony Validator 約束支援
- [ ] 18.9 更新擴充開發指南
- [ ] 18.10 為常見問題建立疑難排解部分

## 19. 程式碼品質和標準

- [ ] 19.1 為 Symfony 標準更新 .php-cs-fixer 配置
- [ ] 19.2 為 Symfony 更新 PHPStan 規則
- [ ] 19.3 執行 PHP CS Fixer 並修復程式碼風格問題
- [ ] 19.4 執行 PHPStan 並修復類型問題
- [ ] 19.5 檢視並更新 PHPDoc 註解
- [ ] 19.6 確保 PSR-4 自動載入正確

## 20. 整合測試

- [ ] 20.1 建立範例 Symfony 應用程式用於整合測試
- [ ] 20.2 測試 bundle 安裝和配置
- [ ] 20.3 測試範例 API 的文件生成
- [ ] 20.4 測試路由屬性檢測
- [ ] 20.5 測試 Doctrine 實體推斷
- [ ] 20.6 測試 Symfony Validator 約束推斷
- [ ] 20.7 測試 Form 類型推斷
- [ ] 20.8 測試序列化群組支援
- [ ] 20.9 測試事件系統和自訂
- [ ] 20.10 測試存取控制
- [ ] 20.11 測試錯誤處理
- [ ] 20.12 驗證 OpenAPI 規格正確性

## 21. 發布準備

- [ ] 21.1 使用破壞性變更更新 CHANGELOG.md
- [ ] 21.2 標記版本 2.0.0（破壞性變更的主要版本）
- [ ] 21.3 建立包含遷移說明的 GitHub 發布
- [ ] 21.4 在 composer.json 中更新套件關鍵字
- [ ] 21.5 為 Symfony 更新套件描述
- [ ] 21.6 考慮為錯誤修復建立 laravel-legacy 分支
- [ ] 21.7 在相關管道宣布遷移
- [ ] 21.8 監控問題並收集回饋
