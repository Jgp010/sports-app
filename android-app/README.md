# Sports News Android

原生 Kotlin + Android SDK 用戶端，最低 Android 8.0（API 26），編譯 SDK 36。

目前不使用 AndroidX 或第三方網路套件，HTTP、JSON 與圖片下載均使用 Android／Java 標準 API。畫面包含最新消息、賽事列表／詳情、會員登入／註冊、基本個資、賽事報名及我的報名紀錄。

未登入點擊報名會導向會員登入。登入 Token 目前儲存在 App 私有 SharedPreferences；正式上架前可再升級為 Android Keystore 加密方案。

正式畫面不顯示底層 HTTP、JSON 或例外內容；連線、逾時、服務未部署及資料格式異常均轉換為一般使用者可理解的訊息。

API 位址在 `app/build.gradle.kts` 的 `API_BASE_URL`，目前設定為 Hostinger 正式網址。Android 模擬器若需存取本機 Laravel，可改用 `10.0.2.2`。

## 正式簽章與安裝警告

`debug` APK 使用 Android Debug 憑證，只適合開發測試。正式發布前請建立自己的長期 release keystore，將 `keystore.properties.example` 複製為 `keystore.properties` 並填入資料，再執行 `gradlew assembleRelease`。金鑰與密碼已排除於版本控制。

即使 APK 已正式簽章，從瀏覽器或通訊軟體直接側載時，Android 仍會依系統政策要求使用者允許「安裝未知應用程式」，部分 Play Protect 裝置也會掃描未經商店發行的新 App。要避免此類來源警告，應使用 Google Play 正式版、封閉測試或內部測試軌道發行。
