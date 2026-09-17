# 體育賽事與報名 App

本工作區包含可運作的 Laravel API／管理後台、Kotlin Android 用戶端與 SwiftUI iOS 用戶端，依照 `體育賽事消息App_系統規劃書_v1.0.docx` 實作。

## 專案結構

- `backend/`：Laravel 12、會員／賽事／報名 API、管理後台、SQLite 本機資料庫與 PHPUnit 測試。
- `android-app/`：Kotlin + Android SDK 原生 App，提供消息、賽事、會員、報名與個人紀錄。
- `ios-app/`：SwiftUI 原生 iOS App，與 Android 共用消息、會員、賽事及報名 API。
- `openapi.yaml`：Android、iOS 與第三方串接共用的 API 契約。
- `artifacts/`：可安裝的 Debug APK（建置驗證後產生）。

## 快速啟動後端

目前 Windows PHP 需要在 CLI 暫時載入 SQLite 擴充：

```powershell
cd backend
php -d extension=pdo_sqlite -d extension=sqlite3 artisan migrate:fresh --seed
php -d extension=pdo_sqlite -d extension=sqlite3 artisan serve --host=0.0.0.0 --port=8000
```

管理後台：`http://127.0.0.1:8000/admin`

正式網址：`https://purple-oyster-440244.hostingersite.com/`；正式管理後台位於 `/admin`，API 位於 `/api/v1/`。

若使用 MySQL／MariaDB，可將根目錄的 `sports_app_mysql.sql` 匯入一個空白資料庫：

```powershell
mysql -u your_user -p your_database < sports_app_mysql.sql
```

匯入後請在 `backend/.env` 設定 `DB_CONNECTION=mysql` 與連線資訊。SQL 已包含 Laravel migration 紀錄，不需再次建立資料表。

本機示範帳號：

- Email：`admin@example.com`
- Password：`ChangeMe123!`

App 示範會員：`member@example.com`／`Member123!`

此帳密只供本機開發，部署前必須透過 `.env` 的 `ADMIN_EMAIL`、`ADMIN_PASSWORD` 更換，並重新執行 seeder 或建立正式管理員。

公開 API：

- `GET /api/v1/news`
- `GET /api/v1/news/{slug}`
- `GET /api/v1/sports`
- `GET /api/v1/events`、`GET /api/v1/events/{slug}`
- `POST /api/v1/auth/register`、`POST /api/v1/auth/login`
- `GET|PUT /api/v1/me`
- `POST /api/v1/events/{slug}/registrations`
- `GET /api/v1/me/registrations`
- `GET /api/v1/registrations/{registration_no}`
- `DELETE /api/v1/registrations/{id}`
- `GET /api/v1/app-config?platform=android`
- `GET /api/v1/health`

## Android

Android 目前預設連到 `https://purple-oyster-440244.hostingersite.com/api/v1/`。若要改用本機模擬器測試，請在 `android-app/app/build.gradle.kts` 將 `API_BASE_URL` 暫時改為 `http://10.0.2.2:8000/api/v1/`。

未登入會員點擊報名時會導向登入／註冊頁；登入後可維護基本個資，並查詢或取消自己的報名。

## 時間與 SSO

- Laravel 固定使用 `Asia/Taipei`，所有建立、更新、報名及取消時間由程式端寫入，不依賴資料庫預設時間；MySQL/MariaDB 連線時區同步設為 `+08:00`。
- API 日期時間使用 ISO 8601 並帶 `+08:00`。
- 會員表預留 `sso_provider`、`sso_subject`、`sso_credential`；憑證透過 Laravel encrypted cast 加密保存。

```powershell
cd android-app
$env:JAVA_HOME='C:\Program Files\Android\Android Studio\jbr'
$env:ANDROID_HOME='C:\Users\User\AppData\Local\Android\Sdk'
.\gradlew.bat :app:assembleDebug
```

## 測試

```powershell
cd backend
php -d extension=pdo_sqlite -d extension=sqlite3 vendor\phpunit\phpunit\phpunit
```

## 正式部署注意事項

- 後端正式環境建議切換 MySQL/MariaDB，設定 `DB_CONNECTION=mysql` 及連線資訊。
- 設定 `APP_ENV=production`、`APP_DEBUG=false`、正式 `APP_URL` 與 HTTPS。
- 執行 `php artisan storage:link`，設定 Web Server、排程器與每日備份。
- Cron 每分鐘執行 `php artisan schedule:run`，讓排程消息準時轉為 published。
- Android 正式版必須使用 HTTPS API，並建立 release signing config。
- Laravel 12 是因目前 PHP 8.2 的相容選擇；升級 PHP 8.3 後應規劃升級 Laravel 13。

## iOS

iOS 專案位於 `ios-app/SportsApp.xcodeproj`，採用 SwiftUI、URLSession、Codable 與 Keychain，不需要另一套後端或第三方套件。請在 macOS 使用 Xcode 16 開啟專案，設定 Apple Developer Team 及正式 Bundle Identifier 後執行。詳細步驟請參考 `ios-app/README.md`。
