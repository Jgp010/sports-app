# 體育賽事 iOS App

原生 SwiftUI 用戶端，與 Android App 共用 Laravel API。

## 開發環境

- macOS
- Xcode 16 或更新版本
- iOS 16.0 或更新版本
- 不使用第三方套件

## 開啟專案

1. 將 `ios-app` 資料夾複製到 Mac。
2. 使用 Xcode 開啟 `SportsApp.xcodeproj`。
3. 選擇 `SportsApp` Target → Signing & Capabilities。
4. 選擇自己的 Apple Developer Team。
5. 將 Bundle Identifier `com.example.sportsapp` 改成實際且唯一的識別碼。
6. 選擇模擬器或已連接的 iPhone 後執行。

正式 API 已設定為：

```text
https://purple-oyster-440244.hostingersite.com/api/v1/
```

設定位置：`SportsApp/Core/APIClient.swift`

## 已完成內容

- 最新消息列表與詳情、封面圖片
- 賽事列表、賽事詳情與多項目費用
- 會員註冊、登入及登出
- Token 儲存於 iOS Keychain
- 未登入報名時導向登入／註冊
- 多項目報名、單位、聯絡人及緊急聯絡資料
- 會員基本資料修改
- 性別選擇及生日日期選擇
- 我的報名紀錄、中文狀態、詳情及取消確認
- 台灣時間與新台幣整數金額顯示
- API 安全錯誤訊息及欄位驗證錯誤解析

## 上架前設定

- 補上正式的 1024 × 1024 App Icon。
- 確認 Apple Developer Team、Bundle Identifier 及簽章憑證。
- 在實機測試登入、報名、取消及圖片顯示。
- 調整版本號 `MARKETING_VERSION` 與建置編號 `CURRENT_PROJECT_VERSION`。
- 使用 Xcode Archive 產生 TestFlight／App Store Connect 發布版本。

Windows 無法執行 Xcode、iOS Simulator、簽章或產出可安裝的 `.ipa`，最後編譯與簽署必須在 macOS 上完成。

## Codemagic 自動測試

根目錄的 `codemagic.yaml` 已提供兩個不需 Apple 簽章的工作流程：

- `ios-simulator-smoke`：在 iPhone Simulator 啟動 App，驗證三個主頁籤與未登入會員畫面，並保留測試結果及截圖。
- `ios-simulator-build`：只檢查 SwiftUI 專案能否成功編譯，並輸出未簽章 `.app`。

`ios-simulator-smoke` 會在 Git push 或 pull request 時自動執行。測試程式位於 `SportsAppUITests/SportsAppUITests.swift`。
