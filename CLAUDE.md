# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 專案概述

這是 `ajay0524/filament-logger` 的維護版本，一個適用於 Filament v4 的活動記錄器套件，支援 Laravel 12。

**核心功能**：
- 記錄 Filament Resource 事件（建立、更新、刪除）
- 記錄使用者登入事件
- 記錄通知事件（發送、失敗）
- 記錄 Model 事件（需手動註冊）
- 支援自訂事件記錄

**技術依賴**：
- PHP: ^8.2
- Laravel: ^12.0
- Filament: ^4.0
- Spatie Laravel ActivityLog: ^4.5

## 常用開發命令

### 測試
```bash
# 執行所有測試（使用 Pest）
composer run test
# 或
vendor/bin/pest

# 執行測試並生成覆蓋率報告
composer run test-coverage
# 或
vendor/bin/pest --coverage
```

### 靜態分析
```bash
# 執行 PHPStan 靜態分析
composer run analyse
# 或
vendor/bin/phpstan analyse
```

### 套件安裝（在 Laravel 專案中）
```bash
# 執行套件安裝命令
php artisan filament-logger:install

# 發布翻譯檔案
php artisan vendor:publish --tag="filament-logger-translations"

# 發布設定檔案
php artisan vendor:publish --provider="AJAY0524\FilamentLogger\FilamentLoggerServiceProvider" --tag="filament-logger-config"
```

## 核心架構

### 目錄結構
```
src/
├── Loggers/                     # 記錄器實作
│   ├── AbstractModelLogger.php # 基礎記錄器（所有記錄器的父類別）
│   ├── ResourceLogger.php      # Filament Resource 事件記錄
│   ├── ModelLogger.php         # 一般 Model 事件記錄
│   ├── AccessLogger.php        # 登入事件記錄
│   └── NotificationLogger.php  # 通知事件記錄
├── Resources/
│   ├── ActivityResource.php    # Filament Resource（活動日誌管理介面）
│   ├── ActivityResource/
│   │   ├── Pages/             # 列表頁、檢視頁
│   │   ├── Schemas/           # Form Schema 定義
│   │   └── Tables/            # Table 定義
│   └── ...
└── FilamentLoggerServiceProvider.php  # 服務提供者
```

### 記錄器架構

**Logger 繼承關係**：
```
AbstractModelLogger (抽象基礎類別)
├── ResourceLogger  # 監聽 Filament Resource 的 Model 事件
├── ModelLogger     # 監聽手動註冊的 Model 事件
├── AccessLogger    # 監聽 Login 事件
└── NotificationLogger  # 監聽 NotificationSent/Failed 事件
```

**AbstractModelLogger 提供的核心方法**：
- `log()` - 記錄活動的核心方法
- `created()` - 記錄建立事件
- `updated()` - 記錄更新事件（自動過濾 remember_token）
- `deleted()` - 記錄刪除事件
- `getLoggableAttributes()` - 處理可記錄的屬性（尊重 Model 的 visible/hidden）

### 自動註冊機制

**ServiceProvider 啟動流程**：
1. `bootingPackage()` - 註冊事件監聽器
   - 登入事件 → AccessLogger
   - 通知事件 → NotificationLogger

2. `packageBooted()` - 註冊 Model Observer
   - 自動掃描所有 Filament Panel 的 Resources → ResourceLogger
   - 手動註冊的 Models（config 中的 `models.register`）→ ModelLogger

### 設定檔結構（config/filament-logger.php）

**重要設定項**：
- `activity_resource` - ActivityResource 類別路徑（可自訂）
- `scoped_to_tenant` - 是否限制在租戶範圍內（多租戶支援）
- `resources.enabled` - 是否啟用 Resource 事件記錄
- `resources.exclude` - 排除特定 Resource 不記錄
- `models.register` - 手動註冊要記錄的 Model
- `custom` - 自訂記錄類型（log_name 和 color）

### Filament v4 架構要點

**Schema vs Form**：
- Filament v4 使用 `Schemas\Schema` 而非 `Forms\Form`
- Schema 配置在 `ActivityResource\Schemas\ActivityForm` 中

**Table 配置**：
- 表格定義在 `ActivityResource\Tables\ActivityTable` 中
- 使用靜態方法 `configure(Table $table)` 進行配置
- 動態生成過濾器選項（log_name, subject_type）

**Resource Pages**：
- 只有 `ListActivities` 和 `ViewActivity` 兩個頁面
- 活動日誌為唯讀，不提供編輯功能

## 開發規範

### 添加新的 Logger

1. 繼承 `AbstractModelLogger`
2. 實作 `getLogName()` 方法
3. 在 config 中註冊（如適用）
4. 在 ServiceProvider 中註冊事件監聽或 Observer

**範例**：
```php
class CustomLogger extends AbstractModelLogger
{
    protected function getLogName(): string
    {
        return 'Custom';
    }

    public function handle(CustomEvent $event): void
    {
        $this->log(
            model: $event->model,
            event: 'custom_action',
            description: 'Custom action performed',
            attributes: $event->data
        );
    }
}
```

### 自訂 ActivityResource

如需覆寫預設的 ActivityResource：
1. 建立自己的 Resource 類別繼承或複製 `ActivityResource`
2. 在 config 中更新 `activity_resource` 指向新類別
3. 在 Panel 中註冊新的 Resource

### 翻譯檔案

翻譯檔案位於 `resources/lang/{locale}/filament-logger.php`：
- 支援多語言（包含繁體中文 `zh_TW`）
- 包含 Resource 標籤、表格欄位、頁面標題等

## 測試結構

**測試框架**：Pest (而非 PHPUnit 原生語法)

**設定檔**：`phpunit.xml.dist`
- 測試套件位於 `tests/` 目錄
- 覆蓋率報告輸出至 `build/coverage/`

## 與 Spatie ActivityLog 的整合

**Model 解析**：
- 使用 Spatie 的 `ActivitylogServiceProvider::determineActivityModel()`
- 預設為 `Spatie\Activitylog\Models\Activity`
- 可在 `config/activitylog.php` 中自訂 Model

**記錄屬性**：
- 使用 `ActivityLogger` 的 Fluent API
- 支援 `properties` 儲存額外資料（old/attributes）
- 支援 `causer`（操作者）和 `subject`（操作對象）關聯

## 重要提醒

1. **不要直接修改 Migration**：此套件依賴 Spatie ActivityLog 的 migration，由 `filament-logger:install` 自動發布

2. **Observer 註冊時機**：在 `packageBooted()` 階段註冊，確保所有 Filament Panel 已載入

3. **remember_token 過濾**：`updated()` 方法會自動忽略只有 `remember_token` 變更的更新事件

4. **Visible/Hidden 尊重**：記錄屬性時會自動尊重 Model 的 `$visible` 和 `$hidden` 設定

5. **多租戶支援**：透過 `scoped_to_tenant` 設定控制，Resource 會自動套用租戶範圍
