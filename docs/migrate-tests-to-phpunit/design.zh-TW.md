# 設計：將測試框架從 Pest 遷移到 PHPUnit

## 背景

Scramble 正在從 Laravel 套件轉換為 Symfony bundle。測試套件目前使用 Pest，這是一個面向 Laravel 的測試框架，在 PHPUnit 之上提供了函數式測試 API。作為 Symfony 遷移的一部分，我們需要採用 PHPUnit 的原生類別基礎方法，這是 Symfony 生態系統的標準。

**目前狀態：**
- 約 50+ 個測試檔案使用 Pest 的函數式 API（`it()`、`test()`、`expect()`）
- 自訂 Pest 期望值（`toBeSameJson`、`toHaveType`）
- Pest 設定檔案（`tests/Pest.php`）包含輔助函數和全域配置
- 使用 Spatie 的 Pest 外掛進行快照測試
- 混合單元測試、整合測試和功能測試

**限制條件：**
- 必須維持 100% 測試覆蓋率
- 不能破壞現有的測試功能
- 應將對開發工作流程的干擾降到最低
- 必須保留快照測試行為

## 目標 / 非目標

**目標：**
- 將所有 Pest 測試轉換為 PHPUnit 原生語法
- 維持或改善測試可讀性和可維護性
- 移除所有 Laravel 特定的測試依賴項
- 保留所有測試功能和覆蓋率
- 確保 CI/CD 相容性

**非目標：**
- 重寫測試邏輯或改變測試覆蓋率（可單獨進行）
- 優化測試效能（可單獨進行）
- 新增新的測試案例（與遷移分開）
- 改變測試組織或結構

## 決策

### 決策 1：轉換策略 - 一次性完成 vs 漸進式

**選擇：一次性完成（單一 PR）**

**理由：**
- Pest 和 PHPUnit 無法在測試執行中乾淨地共存
- 執行混合測試需要複雜的配置
- 單一原子變更提供乾淨的 git 歷史記錄
- 更容易進行程式碼審查，因為所有模式都可以一起看到

**考慮的替代方案：**
- 漸進式（逐目錄）：需要同時維護兩個框架、複雜配置和多個 PR
- 保留 Pest：不可行，因為它以 Laravel 為中心，在 Symfony 生態系統中不符合慣例

### 決策 2：基礎測試類別結構

**選擇：保留 `SymfonyTestCase` 作為基礎，將輔助函數移至 traits**

**理由：**
- `SymfonyTestCase` 已經提供 Symfony 整合
- 基於 trait 的輔助函數允許選擇性包含
- 清晰的關注點分離

**結構：**
```php
abstract class SymfonyTestCase extends TestCase
{
    use CreatesApplication;
    
    protected static function bootKernel(): KernelInterface { ... }
    protected static function getContainer(): ContainerInterface { ... }
}

trait TypeInferenceAssertions
{
    protected function assertTypeEquals(string $expected, Type $actual) { ... }
    protected function assertSameJson(mixed $expected, mixed $actual) { ... }
}

trait AnalysisHelpers
{
    protected function analyzeFile(string $code): AnalysisResult { ... }
    protected function analyzeClass(string $className): AnalysisResult { ... }
}
```

### 決策 3：自訂斷言轉換

**Pest 自訂期望值 → PHPUnit 自訂斷言**

| Pest | PHPUnit 方法 |
|------|-------------|
| `expect($x)->toBeSameJson($y)` | `$this->assertSameJson($x, $y)` |
| `expect($x)->toHaveType($type)` | `$this->assertTypeEquals($type, $x)` |

**理由：**
- PHPUnit 自訂斷言是類別方法，而不是擴充
- 在 IDE 中更明確且更容易發現
- 更好的類型安全性和自動完成

### 決策 4：快照測試

**選擇：使用 PHPUnit 快照斷言函式庫或內聯快照**

**選項：**
1. 使用 `spatie/phpunit-snapshot-assertions`（他們的 Pest 外掛的 PHPUnit 版本）
2. 測試檔案中的內聯快照（對於小型快照）
3. 手動基於檔案的快照

**理由：**
- Spatie 提供 PHPUnit 版本，遷移所需最少
- 快照格式應保持相容
- 可在實作期間進行評估

### 決策 5：測試方法命名

**選擇：使用 PHPUnit 慣例與描述性名稱**

```php
// Pest
it('supports present rule', function () { ... });

// PHPUnit
public function testSupportsPresentRule(): void { ... }
// 或使用屬性
#[Test]
public function supportsPresentRule(): void { ... }
```

**理由：**
- 遵循 Symfony/PHPUnit 慣例
- 更好的 IDE 支援和導航
- 從方法名稱清楚看出測試意圖

### 決策 6：資料提供者

**Pest：**
```php
it('transforms simple types', function ($type, $openApiArrayed) {
    // ...
})->with('simpleTypes');
```

**PHPUnit：**
```php
#[DataProvider('simpleTypesProvider')]
public function testTransformsSimpleTypes($type, $openApiArrayed): void {
    // ...
}

public static function simpleTypesProvider(): array {
    return [ /* ... */ ];
}
```

**理由：**
- PHPUnit 資料提供者是靜態方法
- 更明確且類型安全
- 對複雜資料情境更好

## 實作模式

### 逐步轉換流程

**對於每個測試檔案：**

1. **類別宣告**
   ```php
   // 之前（Pest）
   uses(SymfonyTestCase::class)->in(__DIR__);
   
   // 之後（PHPUnit）
   final class ValidationRulesDocumentingTest extends SymfonyTestCase
   {
   ```

2. **設定/清理**
   ```php
   // 之前
   beforeEach(function () {
       $this->transformer = new TypeToSchemaTransformer();
   });
   
   // 之後
   protected function setUp(): void
   {
       parent::setUp();
       $this->transformer = new TypeToSchemaTransformer();
   }
   ```

3. **測試方法**
   ```php
   // 之前
   it('supports present rule', function () {
       $result = analyzeFile(__DIR__.'/Stubs/ValidationRulesStub.php');
       expect($result)->toBeSomeValue();
   });
   
   // 之後
   public function testSupportsPresentRule(): void
   {
       $result = $this->analyzeFile(__DIR__.'/Stubs/ValidationRulesStub.php');
       $this->assertSomeValue($result);
   }
   ```

4. **斷言對應**
   ```php
   // Pest → PHPUnit
   expect($x)->toBe($y)           → $this->assertSame($y, $x)
   expect($x)->toEqual($y)        → $this->assertEquals($y, $x)
   expect($x)->toBeTrue()         → $this->assertTrue($x)
   expect($x)->toBeFalse()        → $this->assertFalse($x)
   expect($x)->toBeNull()         → $this->assertNull($x)
   expect($x)->toBeArray()        → $this->assertIsArray($x)
   expect($x)->toBeString()       → $this->assertIsString($x)
   expect($x)->toContain($y)      → $this->assertContains($y, $x)
   expect($x)->toHaveCount($n)    → $this->assertCount($n, $x)
   ```

## 風險 / 權衡

### 風險 1：測試行為差異
- **風險**：Pest 和 PHPUnit 執行之間的細微差異可能導致測試行為不同
- **緩解措施**：在轉換前後執行全面的測試套件比較，檢查覆蓋率報告
- **影響**：中等

### 風險 2：快照測試相容性
- **風險**：Pest 和 PHPUnit 外掛之間的快照格式或產生可能不同
- **緩解措施**：手動驗證快照測試，必要時重新產生
- **影響**：低至中等

### 風險 3：自訂期望值邏輯錯誤
- **風險**：自訂 `toHaveType` 期望值具有複雜邏輯，在轉換期間可能會出錯
- **緩解措施**：為自訂斷言新增專用單元測試，徹底手動測試
- **影響**：中等

### 風險 4：CI/CD 管線問題
- **風險**：CI/CD 配置可能需要不立即顯而易見的更新
- **緩解措施**：首先在單獨的分支中測試 CI/CD 變更
- **影響**：低

### 權衡：詳細度 vs 明確性
- Pest 的函數式 API 更簡潔
- PHPUnit 的類別基礎方法更詳細但更明確
- **決策**：接受增加的詳細度以獲得更好的 IDE 支援和 Symfony 生態系統對齊

## 遷移計畫

### 階段 1：基礎設施（第 1 週）
1. 更新基礎測試類別和 traits
2. 轉換測試輔助函數和工具
3. 實作自訂斷言
4. 隔離測試基礎設施變更

### 階段 2：測試轉換（第 1-2 週）
1. 轉換核心推論測試（最高複雜度）
2. 轉換功能測試（中等複雜度）
3. 轉換元件測試（較低複雜度）
4. 轉換擴充測試（較低複雜度）

### 階段 3：驗證（第 2 週）
1. 執行完整測試套件
2. 比較覆蓋率報告
3. 驗證快照測試
4. 更新 CI/CD

### 階段 4：清理（第 2 週）
1. 移除 Pest 依賴項
2. 更新文件
3. 最終驗證

### 回滾計畫
如果發現關鍵問題：
1. 還原遷移提交
2. 在單獨的分支中修復已識別的問題
3. 重新嘗試遷移

## 未解決的問題

1. **問**：我們應該使用 PHPUnit 10 還是 11？
   - **答**：使用 PHPUnit 11（已在 composer.json 中作為 `^11.5.3`），這是最新的穩定版本

2. **問**：如何處理依賴於 Pest 的 `$this` 閉包綁定的測試？
   - **答**：轉換為測試類別中的實例變數，在方法中透過 `$this` 存取

3. **問**：我們應該保留 `dataset()` 定義還是內聯它們？
   - **答**：轉換為靜態資料提供者方法以獲得更好的類型安全性

4. **問**：使用 Pest 的 `arch()` 測試怎麼辦？
   - **答**：目前程式碼庫中未找到，不適用

## 參考資料

- [PHPUnit 文件](https://docs.phpunit.de/en/11.5/)
- [Symfony 測試指南](https://symfony.com/doc/current/testing.html)
- [PHPUnit 遷移指南](https://docs.phpunit.de/en/11.5/installation.html)
- [Spatie PHPUnit 快照斷言](https://github.com/spatie/phpunit-snapshot-assertions)
