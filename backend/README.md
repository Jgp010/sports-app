# Sports News Backend

Laravel 12 REST API 與內容管理後台。完整啟動方式、測試帳號與部署說明請參考工作區根目錄的 `README.md`。

核心功能：

- 管理員 Session 登入、CSRF 與登入節流
- 賽事消息 CRUD、圖片上傳、草稿／排程／發布／封存
- 運動類別管理與操作稽核紀錄
- 賽事 CRUD、報名期間與名額控制
- 全會員查閱／編輯與 SSO 預留加密欄位
- 報名資料查閱／編輯與狀態管理
- App 會員 Token 驗證、個資與自己的報名紀錄 API
- 版本化公開 API、分頁、分類與關鍵字篩選
- 排程發布命令 `news:publish-scheduled`
- SQLite 本機環境與 MySQL/MariaDB 正式環境支援
- 全系統使用 `Asia/Taipei`，時間由 Laravel 程式端寫入
