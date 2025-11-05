# 設計文件：Laravel 到 Symfony 遷移

## 背景

Scramble 目前是作為 Laravel 套件構建的，透過分析 Laravel 路由、控制器、FormRequests、Eloquent 模型和 JsonResources 自動生成 OpenAPI 文件。該專案與 Laravel 的服務容器、路由系統、中介層管道、驗證框架和 ORM 深度整合。

遷移到 Symfony 需要替換這些整合，同時保持核心功能：透過靜態分析和類型推斷自動生成 OpenAPI 文件。

### 當前 Laravel 依賴項
- `illuminate/contracts` - 服務容器契約、路由、基礎
- `illuminate/routing` - 路由註冊和解析
- `illuminate/http` - Request/Response 處理、JsonResource
- `illuminate/database` - Eloquent ORM
- `illuminate/validation` - FormRequest 和驗證規則
- `illuminate/view` - Blade 模板
- `illuminate/support` - 輔助類別（Arr、Str、Collection、Facades）
- `spatie/laravel-package-tools` - 套件服務提供者工具
- `orchestra/testbench` - 測試框架

### Symfony 對應項
- Symfony DependencyInjection 元件 - 服務容器
- Symfony Routing 元件 - 路由註冊和匹配
- Symfony HttpFoundation/HttpKernel - Request/Response 處理
- Doctrine ORM - 實體映射和資料庫抽象
- Symfony Validator 元件 - 約束驗證
- Twig - 模板引擎
- Symfony String/PropertyAccess - 工具元件
- Symfony Bundle 系統 - 套件整合
- Symfony FrameworkBundle 測試工具 - 測試框架

## 目標 / 非目標

### 目標
- 將所有 Laravel 特定程式碼遷移至 Symfony 對應項
- 維持現有的 OpenAPI 生成能力
- 採用 Symfony 最佳實踐和慣例
- 支援 Symfony 6.4+ 和 7.x
- 透過 Symfony 的事件系統保留可擴展性
- 為 Symfony 路由、控制器、實體、表單和序列化器元數據啟用文件生成

### 非目標
- 維持與 Laravel 的向後相容性
- 同時支援兩個框架
- 變更核心 OpenAPI 生成演算法
- 修改面向使用者的文件 UI（保留 Stoplight Elements）
- 支援 6.4 以下的 Symfony 版本

## 決策

### 決策 1：Bundle 架構
**內容**：建立一個 `ScrambleBundle` 類別，繼承 Symfony 的 `Bundle` 基礎類別，而非 Laravel 的 `PackageServiceProvider`。

**原因**： 
- Symfony Bundle 是整合第三方套件的標準方式
- Bundle 提供生命週期鉤子（build、boot）用於註冊服務和配置
- 遵循 Symfony 生態系統慣例

**實作**：
```php
class ScrambleBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        // 註冊編譯器 pass 以發現擴充
        $container->addCompilerPass(new ScrambleExtensionPass());
    }
    
    public function boot(): void
    {
        // 如果配置允許，則註冊路由
    }
}
```

### 決策 2：路由整合
**內容**：將 Laravel Route Facade 替換為 Symfony Router 服務。

**原因**：
- Symfony 使用 RouteCollection 和 Route 物件，而非 facade 模式
- 路由通常在 YAML/XML/屬性中定義，而非 PHP 檔案
- 需要支援控制器上的 Symfony 路由屬性

**實作**：
- 從 `RouterInterface::getRouteCollection()` 解析路由
- 支援 PHP 屬性（`#[Route('/api/users', methods: ['GET'])]`）
- 使用 Symfony 的反射工具提取路由元數據

### 決策 3：中介層到事件系統
**內容**：將 Laravel 中介層類別替換為 Symfony 事件訂閱器。

**原因**：
- Symfony 使用事件驅動架構，而非中介層管道
- `kernel.request`、`kernel.response`、`kernel.exception` 等事件提供等效的鉤子
- 比中介層更靈活和解耦

**實作**：
- `RestrictedDocsAccess` 中介層 → `RequestEvent` 訂閱器檢查權限/安全性
- 使用 Symfony Security 元件進行存取控制

### 決策 4：驗證策略
**內容**：將 FormRequest 分析替換為 Symfony 驗證約束提取。

**原因**：
- Symfony 使用 Constraint 類別和註解，而非規則陣列
- 可以從實體、DTO 和表單中提取驗證器元數據
- 需要支援基於屬性和 YAML/XML 的驗證器配置

**實作**：
- 使用 `ValidatorInterface::getMetadataFor()` 提取約束
- 將 Symfony 約束轉換為 OpenAPI 參數模式
- 支援自訂約束到模式的轉換器

### 決策 5：ORM 整合
**內容**：將 Eloquent 模型推斷替換為 Doctrine 實體元數據提取。

**原因**：
- Doctrine 是標準的 Symfony ORM
- 實體元數據（欄位類型、關聯、可空性）可透過 `ClassMetadataFactory` 獲得
- 不同的模式：實體 vs 模型、儲存庫 vs 查詢建構器

**實作**：
- 使用 Doctrine `EntityManagerInterface` 獲取元數據
- 從 `@ORM\Column`、`@ORM\ManyToOne` 等屬性推斷類型
- 支援 Doctrine 類型（datetime、json、decimal 等）

### 決策 6：序列化
**內容**：將 JsonResource 替換為 Symfony Serializer 元件整合。

**原因**：
- Symfony 序列化器處理正規化/反正規化
- 支援群組、上下文、自訂正規化器
- 比簡單的 JsonResource toArray() 更強大

**實作**：
- 分析 `SerializerInterface` 和正規化器元數據
- 在 OpenAPI 回應中支援序列化群組
- 從正規化器上下文推斷回應模式

### 決策 7：視圖層
**內容**：將 Blade 模板轉換為 Twig。

**原因**：
- Twig 是 Symfony 的預設模板引擎
- 更安全（自動轉義）、更強大（繼承、過濾器）
- 在 Symfony 專案中有更好的 IDE 支援

**實作**：
- 簡單轉換：`{{ $var }}` → `{{ var }}`
- `@if` → `{% if %}`
- 保持相同的 HTML/JS 結構（Stoplight Elements）

### 決策 8：配置格式
**內容**：遵循 Symfony 慣例支援 YAML/XML 配置，同時保留 PHP 配置。

**原因**：
- Symfony 專案通常使用 YAML 或 XML 進行配置
- Bundle 擴充類別定義配置樹
- 提供更好的驗證和 IDE 自動完成

**實作**：
```yaml
# config/packages/scramble.yaml
scramble:
    api_path: /api
    api_domain: ~
    export_path: api.json
    info:
        version: '1.0.0'
        description: 'API Documentation'
    ui:
        theme: light
        layout: responsive
```

### 決策 9：測試框架
**內容**：將 Orchestra Testbench 替換為 Symfony 的 KernelTestCase 和 WebTestCase。

**原因**：
- Orchestra 是 Laravel 特定的
- Symfony 在測試中提供核心啟動和服務存取
- WebTestCase 用於使用測試客戶端進行 HTTP 測試

**實作**：
- 建立載入 ScrambleBundle 的測試核心
- 在測試設定中使用 `static::bootKernel()`
- 使用 `static::getContainer()` 存取服務

### 決策 10：依賴注入模式
**內容**：使用建構函式注入和服務配置，而非 Laravel 的 app() 輔助函式和 Facades。

**原因**：
- Symfony 強烈偏好明確的建構函式注入
- 服務在 YAML/XML 中定義，帶有自動裝配
- Symfony 生態系統中沒有全域輔助函式或 Facades

**實作**：
- 在 `Resources/config/services.yaml` 中定義所有服務
- 啟用自動裝配和自動配置
- 為擴充服務添加標籤以進行自動發現

## 考慮的替代方案

### 替代方案 1：多框架支援
**考慮**：透過適配器模式同時支援 Laravel 和 Symfony。

**拒絕**： 
- 維護負擔加倍
- 需要複雜的抽象層
- 框架特定的習語不能很好地轉換
- 使用者對哪些功能在哪裡工作會感到困惑

### 替代方案 2：框架無關核心
**考慮**：提取框架無關的 OpenAPI 生成核心，配備單獨的 Laravel/Symfony 適配器。

**拒絕**：
- 需要對現有程式碼庫進行重大重構
- 類型推斷與框架模式深度綁定
- 對於現在專注於 Symfony 的專案來說過於複雜
- 如果將來支援更多框架，可以考慮

### 替代方案 3：保留 Laravel 輔助函式
**考慮**：繼續使用 `Illuminate\Support\Arr` 和 `Illuminate\Support\Str` 工具。

**拒絕**：
- 增加不必要的 Laravel 依賴項
- Symfony 有等效的工具（String 元件、陣列函式）
- 違背了完全採用 Symfony 的目標

## 風險 / 權衡

### 風險 1：類型推斷準確性
**風險**：Symfony 的較寬鬆類型（相對於 Laravel 的慣例）可能會降低推斷品質。

**緩解**： 
- 利用 PHP 8.1+ 類型屬性和回傳類型
- 使用 Symfony 的元數據系統（Validator、Serializer、Doctrine）
- 為自訂類型提示提供清晰的擴充點

### 風險 2：破壞性變更影響
**風險**：Laravel 上的現有使用者無法在不進行重大重構的情況下升級。

**緩解**：
- 清楚記錄破壞性變更和遷移路徑
- 考慮為關鍵錯誤維護 Laravel 相容分支
- 提供包含程式碼範例的遷移指南
- 提前充分溝通變更

### 風險 3：學習曲線
**風險**：熟悉 Laravel 的貢獻者需要學習 Symfony 模式。

**緩解**：
- 使用 Symfony 慣例更新 CONTRIBUTING.md
- 提供常見模式的範例
- 連結到 Symfony 最佳實踐文件

### 風險 4：測試覆蓋率
**風險**：由於行為差異，遷移可能會引入錯誤。

**緩解**：
- 在遷移前確保全面的測試覆蓋率
- 在遷移期間建立平行測試
- 針對實際的 Symfony 應用程式進行測試
- 透過早期採用者回饋進行測試期

### 風險 5：Doctrine vs Eloquent 差異
**風險**：Doctrine 的工作單元模式與 Eloquent 的活動記錄有顯著不同。

**緩解**：
- 專注於元數據提取，而非 ORM 行為
- 同時支援實體和純 DTO
- 清楚記錄限制

## 遷移計劃

### 階段 1：依賴項（第 1 週）
- [ ] 使用 Symfony 套件更新 composer.json
- [ ] 移除 Laravel 套件
- [ ] 解決任何衝突
- [ ] 確保 PHP 8.1+ 相容性

### 階段 2：核心服務（第 2-3 週）
- [ ] 建立 ScrambleBundle 類別
- [ ] 為配置建立 bundle 擴充
- [ ] 設定服務容器配置
- [ ] 遷移核心服務（Infer、Generator、TypeTransformer）

### 階段 3：路由整合（第 3-4 週）
- [ ] 實作 Symfony 路由集合解析
- [ ] 支援路由屬性
- [ ] 提取控制器元數據
- [ ] 為 Symfony 更新 ReflectionRoute

### 階段 4：類型推斷（第 4-6 週）
- [ ] Doctrine 實體元數據提取
- [ ] Symfony 驗證器約束推斷
- [ ] Symfony 序列化器整合
- [ ] 替換 Eloquent/JsonResource 擴充
- [ ] 更新例外處理

### 階段 5：UI 和配置（第 6-7 週）
- [ ] 將 Blade 轉換為 Twig
- [ ] 實作 YAML/XML 配置支援
- [ ] 為存取控制建立事件訂閱器
- [ ] 更新命令類別以使用 Symfony Console

### 階段 6：測試（第 7-8 週）
- [ ] 將測試遷移至 Symfony 測試框架
- [ ] 建立測試核心
- [ ] 確保與 Laravel 版本的功能對等
- [ ] 使用實際 Symfony 應用程式新增整合測試

### 階段 7：文件（第 9 週）
- [ ] 使用 Symfony 安裝更新 README
- [ ] 從 Laravel 版本建立遷移指南
- [ ] 更新所有程式碼範例
- [ ] 記錄新的擴充模式

### 回退計劃
如果出現關鍵問題：
1. 維護 `v1.x` 分支，提供 6 個月的 Laravel 支援
2. 將新的 Symfony 版本標記為 `v2.0.0`
3. 在文件中提供清晰的版本控制
4. 在過渡期接受 v1.x 的關鍵錯誤修復

## 開放問題

1. **問**：我們應該支援 Symfony 5.4 LTS 還是只支援 6.4+？
   **答**：待定 - 需要檢查 5.4 vs 6.4 的功能可用性

2. **問**：如何處理 API Platform 整合（另一個流行的 Symfony API 框架）？
   **答**：待定 - 考慮專用擴充或內建支援

3. **問**：配置應該支援所有三種格式（YAML、XML、PHP）還是只支援 YAML？
   **答**：待定 - 可能主要是 YAML，PHP 作為後備

4. **問**：如何處理 Symfony UX/Turbo/HTMX 模式？
   **答**：初始遷移範圍之外 - 這些是 UI 模式，而非 API 文件

5. **問**：我們應該在開發環境中自動註冊路由還是需要明確配置？
   **答**：待定 - 收集社群對首選方法的回饋
