# 變更提案：從 Laravel 遷移至 Symfony 框架

## 為什麼

本專案目前依賴 Laravel 框架的核心整合模式，包括服務提供者註冊、路由、中介層、驗證、Eloquent ORM 和 JSON 資源。為了支援基於 Symfony 的應用程式並採用 Symfony 的架構慣例和編碼風格，我們需要將所有 Laravel 特定的整合替換為對應的 Symfony 元件。

這次遷移將使函式庫能夠使用原生 Symfony 模式（服務、路由、事件、驗證約束、Doctrine ORM、序列化）為 Symfony 應用程式生成 OpenAPI 文件。

## 有哪些變更

- **重大變更**：將 Laravel 服務提供者替換為 Symfony Bundle
- **重大變更**：將 Laravel 路由整合替換為 Symfony 路由元件
- **重大變更**：將 Laravel 中介層替換為 Symfony 事件監聽器/訂閱器
- **重大變更**：將 Laravel FormRequest 驗證替換為 Symfony 驗證約束
- **重大變更**：將 Eloquent 模型推斷替換為 Doctrine 實體支援
- **重大變更**：將 Laravel JsonResource 替換為 Symfony 序列化器整合
- **重大變更**：將 Blade 視圖替換為 Twig 模板
- **重大變更**：將 Laravel 輔助函式（Arr、Str）替換為 Symfony 元件
- **重大變更**：更新套件依賴項，使用 Symfony 套件而非 illuminate/* 套件
- **重大變更**：將 Orchestra Testbench 遷移至 Symfony 測試框架
- 更新程式碼風格以遵循 Symfony 慣例（PSR-4 自動載入、服務配置、事件命名）
- 更新配置檔案以使用 Symfony 配置格式（YAML/XML）
- 將 Laravel 特定的例外處理替換為 Symfony 例外監聽器
- 更新文件和範例以反映 Symfony 使用模式

## 影響範圍

### 受影響的規格
- `service-integration` - 新功能：Symfony Bundle 註冊和依賴注入
- `routing` - 修改為使用 Symfony Router 而非 Laravel Route
- `middleware` - 修改為使用 Symfony 事件系統而非 Laravel 中介層
- `validation` - 修改為使用 Symfony Validator 而非 Laravel 驗證
- `views` - 修改為使用 Twig 而非 Blade
- `type-inference` - 修改為從 Doctrine 實體、Symfony 表單和序列化器元數據推斷類型

### 受影響的程式碼
- `composer.json` - 將 Laravel 依賴項替換為 Symfony 對應項
- `src/ScrambleServiceProvider.php` - 轉換為 Symfony Bundle 類別
- `config/scramble.php` - 轉換為 Symfony 配置格式
- `routes/web.php` - 轉換為 Symfony 路由格式
- `src/Http/Middleware/*` - 轉換為 Symfony 事件監聽器
- `resources/views/docs.blade.php` - 轉換為 Twig 模板
- `src/Support/InferExtensions/*Extension.php` - 更新所有 Laravel 特定的類型推斷擴充
- `src/Support/TypeToSchemaExtensions/*` - 將 Eloquent/JsonResource 支援替換為 Doctrine/Serializer
- `src/Support/ExceptionToResponseExtensions/*` - 適配 Symfony 例外階層
- `src/Reflection/ReflectionRoute.php` - 適配 Symfony Route 物件
- `src/Support/OperationExtensions/ErrorResponsesExtension.php` - 將 FormRequest 替換為 Symfony 約束
- `tests/*` - 從 Orchestra Testbench 轉換為 Symfony 測試框架
- 所有使用 `Illuminate\*` 匯入的檔案 - 替換為適當的 Symfony 元件

### 遷移路徑
這是一個重大的破壞性變更。使用者需要：
1. 更新 `composer.json` 以要求新的 Symfony 相容版本
2. 將 Laravel 服務提供者註冊替換為 Symfony Bundle 配置
3. 更新任何自訂擴充以使用 Symfony 元件而非 Laravel
4. 檢視生成的文件，因為模式生成模式可能有所不同

## 相容性
- **最低 Symfony 版本**：6.4 或 7.0+
- **PHP 版本**：保持當前要求（^8.1）
- **無向後相容性**：這是一次完整的框架遷移
