# 變更：將測試框架從 Pest 遷移到 PHPUnit

## 為什麼

本專案目前使用 Pest 作為測試框架，這是一個最初為 Laravel 設計的框架。作為遷移到 Symfony 的一部分，我們需要轉換到原生的 PHPUnit，原因如下：

1. **框架對齊**：PHPUnit 是 Symfony 生態系統的標準測試框架，提供與 Symfony 測試工具和慣例更好的整合。
2. **簡化依賴**：移除在 Symfony 優先的程式碼庫中不再需要的 Laravel 特定測試依賴（Pest、Spatie Snapshots）。
3. **改善可維護性**：使用業界標準的 PHPUnit 語法，這在 PHP 社群中被更廣泛理解和支援。
4. **增強 IDE 支援**：在現代 IDE 中，PHPUnit 的類別基礎方法提供更好的自動完成、導航和除錯支援。

## 變更內容

- 將所有 Pest 測試檔案轉換為 PHPUnit 測試類別
  - 將 `it()` 和 `test()` 函數轉換為 PHPUnit 測試方法
  - 將 `beforeEach()` 轉換為 `setUp()` 方法
  - 將 Pest `expect()` 斷言替換為 PHPUnit 斷言
  - 移除 `uses()` trait 宣告並轉換為類別基礎的測試結構
- 更新測試工具和輔助函數
  - 轉換 `getTestSourceCode()` 輔助函數以適配 PHPUnit 反射
  - 將自訂 Pest 期望值遷移到 PHPUnit 自訂斷言
  - 更新 `analyzeFile()`、`analyzeClass()` 和其他測試輔助函數
- 更新配置檔案
  - **破壞性變更**：從 `composer.json` 移除 Pest 配置
  - 更新 `phpunit.xml.dist` 以僅使用 PHPUnit 執行
  - 移除 Pest 外掛參照
- 更新文件
  - 更新 README 和文件中的測試範例
  - 更新 CI/CD 管線腳本以使用 PHPUnit

## 影響

- **受影響的規格**：`testing`（建立新規格）
- **受影響的程式碼**： 
  - `tests/` 目錄中的所有測試檔案（約 50+ 個檔案）
  - `tests/Pest.php` → 將被移除或轉換為基礎測試類別
  - `tests/SymfonyTestCase.php` → 可能需要更新以整合 PHPUnit
  - `composer.json` → 依賴項變更
  - `.github/workflows/` → CI 腳本更新（如果存在）
- **破壞性變更**： 
  - **破壞性變更**：測試執行命令從 `vendor/bin/pest` 變更為 `vendor/bin/phpunit`
  - **破壞性變更**：Pest 外掛和依賴項將被移除
  - 自訂 Pest 外掛（例如 snapshots）需要 PHPUnit 等效實作
- **遷移路徑**： 
  - 所有測試將在單一變更中轉換
  - 快照測試在轉換後可能需要手動驗證
  - CI/CD 中的測試執行需要同時更新

## 依賴關係

此變更建立在現有的 `migrate-to-symfony` 變更之上，應作為 Symfony 遷移工作的一部分完成。

## 風險

1. **快照測試**：Pest 的快照外掛可能與 PHPUnit 替代方案有不同的行為
2. **測試覆蓋率**：確保所有測試模式（資料提供者、期望值、設定/清理）都正確轉換
3. **隱藏依賴**：某些測試可能依賴於不立即顯而易見的 Pest 特定行為

## 成功標準

- 所有測試使用 PHPUnit 通過
- `composer.json` 中不再有 Pest 依賴
- 測試覆蓋率保持在當前水準或更高
- CI/CD 管線成功使用 PHPUnit 執行測試
- 所有自訂斷言和輔助函數正常運作
