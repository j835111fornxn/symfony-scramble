# 實作任務

## 1. 準備和分析
- [ ] 1.1 審查所有測試檔案以識別 Pest 特定模式
- [ ] 1.2 建立自訂期望值和斷言的清單
- [ ] 1.3 記錄快照測試的位置和模式
- [ ] 1.4 審查 `tests/Pest.php` 中的測試輔助函數

## 2. 更新測試基礎設施
- [ ] 2.1 建立基礎 PHPUnit 測試類別（如果轉換 `tests/Pest.php`）
- [ ] 2.2 將自訂 Pest 期望值轉換為 PHPUnit 自訂斷言
  - [ ] 2.2.1 轉換 `toBeSameJson` 期望值
  - [ ] 2.2.2 轉換 `toHaveType` 期望值
- [ ] 2.3 更新測試輔助函數
  - [ ] 2.3.1 更新 `getTestSourceCode()` 以適配 PHPUnit
  - [ ] 2.3.2 更新 `analyzeFile()` 輔助函數
  - [ ] 2.3.3 更新 `analyzeClass()` 輔助函數
  - [ ] 2.3.4 更新 `generateForRoute()` 輔助函數
- [ ] 2.4 確保 `SymfonyTestCase` 與 PHPUnit 相容

## 3. 轉換測試檔案（批次 1：核心測試）
- [ ] 3.1 轉換 `tests/InferTypesTest.php`
- [ ] 3.2 轉換 `tests/ComplexInferTypesTest.php`
- [ ] 3.3 轉換 `tests/TypeToSchemaTransformerTest.php`
- [ ] 3.4 轉換 `tests/OpenApiTraverserTest.php`
- [ ] 3.5 轉換 `tests/OpenApiBuildersTest.php`

## 4. 轉換測試檔案（批次 2：功能測試）
- [ ] 4.1 轉換 `tests/ValidationRulesDocumentingTest.php`
- [ ] 4.2 轉換 `tests/ResponseDocumentingTest.php`
- [ ] 4.3 轉換 `tests/ResponsesInferTypesTest.php`
- [ ] 4.4 轉換 `tests/ErrorsResponsesTest.php`
- [ ] 4.5 轉換 `tests/ParametersSerializationTest.php`
- [ ] 4.6 轉換 `tests/ResourceCollectionResponseTest.php`

## 5. 轉換測試檔案（批次 3：元件測試）
- [ ] 5.1 轉換 `tests/Attributes/` 中的所有測試
- [ ] 5.2 轉換 `tests/Console/` 中的所有測試
- [ ] 5.3 轉換 `tests/DocumentTransformers/` 中的所有測試
- [ ] 5.4 轉換 `tests/EventSubscriber/` 中的所有測試
- [ ] 5.5 轉換 `tests/Generator/` 中的所有測試

## 6. 轉換測試檔案（批次 4：擴充測試）
- [ ] 6.1 轉換 `tests/Infer/` 中的所有測試
- [ ] 6.2 轉換 `tests/InferExtensions/` 中的所有測試
- [ ] 6.3 轉換 `tests/PhpDoc/` 中的所有測試
- [ ] 6.4 轉換 `tests/Reflection/` 中的所有測試
- [ ] 6.5 轉換 `tests/Support/` 中的所有測試

## 7. 處理快照測試
- [ ] 7.1 研究 PHPUnit 快照測試替代方案
- [ ] 7.2 轉換或替換 Spatie 快照測試
- [ ] 7.3 驗證快照測試行為與 Pest 實作匹配
- [ ] 7.4 如果需要，更新 `tests/__snapshots__/`

## 8. 更新配置
- [ ] 8.1 從 `composer.json` 依賴項中移除 Pest
- [ ] 8.2 從 `composer.json` 中移除 Pest 外掛配置
- [ ] 8.3 更新 `phpunit.xml.dist` 配置
- [ ] 8.4 更新 `composer.json` 中的測試腳本
  - [ ] 8.4.1 將 `test` 腳本改為使用 `phpunit`
  - [ ] 8.4.2 更新 `test-coverage` 腳本
- [ ] 8.5 如果完全轉換，移除 `tests/Pest.php`

## 9. 驗證和測試
- [ ] 9.1 使用 PHPUnit 執行所有測試並驗證通過
- [ ] 9.2 比較測試覆蓋率報告（轉換前與轉換後）
- [ ] 9.3 驗證快照測試產生相同的結果
- [ ] 9.4 測試所有自訂斷言正常運作
- [ ] 9.5 執行 PHPStan 分析以捕捉任何類型問題

## 10. CI/CD 和文件更新
- [ ] 10.1 更新 GitHub Actions 工作流程（如果存在）
- [ ] 10.2 更新任何本地開發腳本
- [ ] 10.3 更新 README.md 測試說明
- [ ] 10.4 更新 MIGRATION.md 的測試變更
- [ ] 10.5 將遷移注意事項加入 CHANGELOG.md

## 11. 清理
- [ ] 11.1 移除不再需要的 Laravel 測試存根
- [ ] 11.2 移除任何剩餘的 Pest 特定檔案
- [ ] 11.3 清理未使用的匯入和依賴項
- [ ] 11.4 執行 `composer update` 以清理鎖定檔案

## 驗證檢查清單
完成所有任務後：
- [ ] 所有測試通過：`vendor/bin/phpunit`
- [ ] 覆蓋率報告產生：`vendor/bin/phpunit --coverage-html build/coverage`
- [ ] `composer.lock` 中沒有 Pest 依賴
- [ ] CI/CD 管線通過（如果適用）
- [ ] `openspec validate migrate-tests-to-phpunit --strict` 通過
