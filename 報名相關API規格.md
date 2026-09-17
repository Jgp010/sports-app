# 體育賽事 App－報名相關 API 規格

更新日期：2026-09-15  
API 版本：v1

## 基本設定

正式環境 Base URL：

```text
https://purple-oyster-440244.hostingersite.com/api/v1
```

所有請求建議包含：

```http
Accept: application/json
Content-Type: application/json; charset=utf-8
```

需要會員登入的 API 必須額外包含：

```http
Authorization: Bearer {會員登入取得的 token}
```

所有日期時間皆使用台灣時區 `Asia/Taipei`，以 ISO 8601 格式回傳，例如：

```text
2026-10-20T09:00:00+08:00
```

所有金額皆為新台幣「元」，API 使用 JSON 整數格式，例如 `800`，不回傳小數或數字字串。用戶端應使用整數型別保存與計算。

---

## 1. 取得賽事列表

```http
GET /events?per_page=30
```

不需要登入。

### Query Parameters

| 參數 | 必填 | 說明 |
|---|---:|---|
| `per_page` | 否 | 每頁筆數，預設 30，最小 1、最大 50 |

### 成功回應

HTTP Status：`200 OK`

```json
{
  "success": true,
  "data": [
    {
      "id": 10,
      "slug": "gymnastics-2026",
      "title": "2026 體操錦標賽",
      "description": "賽事說明",
      "venue": "台北體育館",
      "sport": {
        "id": 3,
        "name": "體操",
        "slug": "gymnastics"
      },
      "event_start_at": "2026-10-20T09:00:00+08:00",
      "event_end_at": "2026-10-20T17:00:00+08:00",
      "registration_open_at": "2026-09-15T00:00:00+08:00",
      "registration_close_at": "2026-10-10T23:59:00+08:00",
      "capacity": 100,
      "registered_count": 20,
      "registration_open": true,
      "status": "published",
      "items": [
        {
          "id": 31,
          "name": "跳馬",
          "description": "男子及女子跳馬",
          "registration_fee": 1000,
          "early_bird_fee": 800,
          "early_bird_ends_at": "2026-09-30T23:59:00+08:00",
          "current_fee": 800,
          "early_bird_active": true
        },
        {
          "id": 32,
          "name": "彈翻床",
          "description": null,
          "registration_fee": 1200,
          "early_bird_fee": null,
          "early_bird_ends_at": null,
          "current_fee": 1200,
          "early_bird_active": false
        }
      ]
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 30,
    "total": 1,
    "last_page": 1
  },
  "error": null
}
```

### 重要欄位

| 欄位 | 說明 |
|---|---|
| `registration_open` | 目前是否可報名，會同時判斷發布狀態、報名期間與名額 |
| `registered_count` | 狀態為 `registered` 的有效報名筆數 |
| `capacity` | 賽事名額；`null` 表示不限名額 |
| `items` | 目前開放報名的賽事項目 |
| `current_fee` | 伺服器根據目前時間計算出的適用金額 |
| `early_bird_active` | 目前是否適用早鳥價格 |

---

## 2. 取得單一賽事詳情

```http
GET /events/{event_slug}
```

範例：

```http
GET /events/gymnastics-2026
```

不需要登入。

### 成功回應

HTTP Status：`200 OK`

```json
{
  "success": true,
  "data": {
    "id": 10,
    "slug": "gymnastics-2026",
    "title": "2026 體操錦標賽",
    "description": "賽事說明",
    "venue": "台北體育館",
    "sport": {
      "id": 3,
      "name": "體操",
      "slug": "gymnastics"
    },
    "event_start_at": "2026-10-20T09:00:00+08:00",
    "event_end_at": "2026-10-20T17:00:00+08:00",
    "registration_open_at": "2026-09-15T00:00:00+08:00",
    "registration_close_at": "2026-10-10T23:59:00+08:00",
    "capacity": 100,
    "registered_count": 20,
    "registration_open": true,
    "status": "published",
    "items": [
      {
        "id": 31,
        "name": "跳馬",
        "description": "男子及女子跳馬",
        "registration_fee": 1000,
        "early_bird_fee": 800,
        "early_bird_ends_at": "2026-09-30T23:59:00+08:00",
        "current_fee": 800,
        "early_bird_active": true
      }
    ]
  },
  "meta": null,
  "error": null
}
```

找不到賽事或賽事未發布時回傳 `404 Not Found`：

```json
{
  "success": false,
  "data": null,
  "meta": null,
  "error": {
    "code": "EVENT_NOT_FOUND",
    "message": "查無賽事資料。"
  }
}
```

---

## 3. 會員註冊

```http
POST /auth/register
```

此 API 不需要登入，也不需要傳送 Token。註冊成功後會直接回傳一組新的會員 Token。

### Request Body

```json
{
  "name": "王小明",
  "email": "user@example.com",
  "password": "password123",
  "phone": "0912345678"
}
```

### 欄位規格

| 欄位 | 型別 | 必填 | 規則 |
|---|---|---:|---|
| `name` | string | 是 | 最多 80 個字元 |
| `email` | string | 是 | 有效的 Email 格式、最多 190 個字元且不可重複 |
| `password` | string | 是 | 至少 8 個字元 |
| `phone` | string / null | 否 | 最多 30 個字元 |

### 成功回應

HTTP Status：`201 Created`

```json
{
  "success": true,
  "data": {
    "token": "產生的會員登入憑證",
    "token_type": "Bearer",
    "expires_at": "2026-10-15T12:00:00+08:00",
    "user": {
      "id": 25,
      "name": "王小明",
      "email": "user@example.com",
      "phone": "0912345678",
      "birth_date": null,
      "gender": null,
      "address": null,
      "sso_provider": null,
      "has_sso_credential": false
    }
  },
  "meta": null,
  "error": null
}
```

Token 有效期限為建立後 30 天，後續需要登入的 API 使用：

```http
Authorization: Bearer {token}
```

### 驗證失敗

Email 已被註冊、Email 格式錯誤、密碼不足 8 個字元或缺少必填欄位時，回傳 HTTP Status `422 Unprocessable Content`：

```json
{
  "success": false,
  "data": null,
  "meta": null,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "輸入資料驗證失敗。",
    "details": {
      "email": [
        "email已被使用。"
      ]
    }
  }
}
```

---

## 4. 會員登入並取得 Token

```http
POST /auth/login
```

### Request Body

```json
{
  "email": "member@example.com",
  "password": "Member123!"
}
```

### 成功回應

HTTP Status：`200 OK`

```json
{
  "success": true,
  "data": {
    "token": "會員存取Token",
    "token_type": "Bearer",
    "expires_at": "2026-10-14T12:00:00+08:00",
    "user": {
      "id": 2,
      "name": "王小明",
      "email": "member@example.com",
      "phone": "0912345678",
      "birth_date": "2000-01-01",
      "gender": "male",
      "address": "台北市",
      "sso_provider": null,
      "has_sso_credential": false
    }
  },
  "meta": null,
  "error": null
}
```

Token 有效期目前為 30 天。

後台管理帳號與 App 會員帳號為不同登入機制；此 API 只接受會員 Email 與密碼。

---

## 5. 送出賽事報名

```http
POST /events/{event_slug}/registrations
Authorization: Bearer {token}
```

### Request Body

```json
{
  "contact_phone": "0912345678",
  "organization": "台北體操協會",
  "emergency_contact_name": "王大明",
  "emergency_contact_phone": "0922333444",
  "item_ids": [31, 32],
  "notes": "素食"
}
```

### 欄位規格

| 欄位 | 型別 | 必填 | 限制 |
|---|---|---:|---|
| `contact_phone` | string | 是 | 最多 30 字 |
| `organization` | string | 是 | 報名單位，最多 150 字 |
| `emergency_contact_name` | string | 是 | 最多 80 字 |
| `emergency_contact_phone` | string | 是 | 最多 30 字 |
| `item_ids` | integer[] | 是 | 至少一個、不可重複，且必須屬於該賽事並處於啟用狀態 |
| `notes` | string/null | 否 | 最多 1000 字 |

### 成功回應

HTTP Status：`201 Created`

```json
{
  "success": true,
  "data": {
    "id": 105,
    "registration_no": "R20260914123000AB12",
    "status": "registered",
    "contact_phone": "0912345678",
    "emergency_contact_name": "王大明",
    "organization": "台北體操協會",
    "total_amount": 2000,
    "emergency_contact_phone": "0922333444",
    "notes": "素食",
    "registered_at": "2026-09-14T12:30:00+08:00",
    "cancelled_at": null,
    "items": [
      {
        "id": 31,
        "name": "跳馬",
        "unit_price": 800
      },
      {
        "id": 32,
        "name": "彈翻床",
        "unit_price": 1200
      }
    ],
    "event": {
      "id": 10,
      "slug": "gymnastics-2026",
      "title": "2026 體操錦標賽",
      "description": "賽事說明",
      "venue": "台北體育館",
      "sport": {
        "id": 3,
        "name": "體操",
        "slug": "gymnastics"
      },
      "event_start_at": "2026-10-20T09:00:00+08:00",
      "event_end_at": "2026-10-20T17:00:00+08:00",
      "registration_open_at": "2026-09-15T00:00:00+08:00",
      "registration_close_at": "2026-10-10T23:59:00+08:00",
      "capacity": 100,
      "registered_count": 21,
      "registration_open": true,
      "status": "published",
      "items": [
        {
          "id": 31,
          "name": "跳馬",
          "description": "男子及女子跳馬",
          "registration_fee": 1000,
          "early_bird_fee": 800,
          "early_bird_ends_at": "2026-09-30T23:59:00+08:00",
          "current_fee": 800,
          "early_bird_active": true
        }
      ]
    }
  },
  "meta": null,
  "error": null
}
```

`items[].unit_price` 為報名當下的成交金額快照。管理員之後修改賽事項目價格，不會改變既有報名資料。

伺服器會在寫入資料庫前重新確認：

1. 賽事已發布。
2. 目前時間位於報名期間內。
3. 賽事尚有名額。
4. 所有 `item_ids` 都屬於該賽事且仍開放報名。
5. 會員尚未報名過該賽事。
6. 早鳥價格由伺服器時間決定，不接受用戶端自行傳入價格。

---

## 6. 查詢自己的報名紀錄

```http
GET /me/registrations
Authorization: Bearer {token}
```

### 成功回應

HTTP Status：`200 OK`

```json
{
  "success": true,
  "data": [
    {
      "id": 105,
      "registration_no": "R20260914123000AB12",
      "status": "registered",
      "contact_phone": "0912345678",
      "emergency_contact_name": "王大明",
      "organization": "台北體操協會",
      "total_amount": 2000,
      "emergency_contact_phone": "0922333444",
      "notes": "素食",
      "registered_at": "2026-09-14T12:30:00+08:00",
      "cancelled_at": null,
      "items": [
        {
          "id": 31,
          "name": "跳馬",
          "unit_price": 800
        }
      ],
      "event": {
        "id": 10,
        "slug": "gymnastics-2026",
        "title": "2026 體操錦標賽",
        "description": "賽事說明",
        "venue": "台北體育館",
        "sport": {
          "id": 3,
          "name": "體操",
          "slug": "gymnastics"
        },
        "event_start_at": "2026-10-20T09:00:00+08:00",
        "event_end_at": "2026-10-20T17:00:00+08:00",
        "registration_open_at": "2026-09-15T00:00:00+08:00",
        "registration_close_at": "2026-10-10T23:59:00+08:00",
        "capacity": 100,
        "registered_count": 20,
        "registration_open": true,
        "status": "published",
        "items": []
      }
    }
  ],
  "meta": null,
  "error": null
}
```

報名紀錄依 `registered_at` 由新到舊排列。

### 依報名編號查詢單筆紀錄

```http
GET /registrations/{registration_no}
```

範例：

```http
GET /registrations/R20260914123000AB12
```

成功時 HTTP Status 為 `200 OK`，`data` 為單筆報名資料，欄位格式與上述列表中的單筆資料相同。

此端點不需要登入，也不需要傳送 Token。持有完整報名編號的人即可查詢該筆報名資料，因此用戶端不可公開或依序產生報名編號。

若報名編號不存在，回傳：

```json
{
  "success": false,
  "data": null,
  "meta": null,
  "error": {
    "code": "REGISTRATION_NOT_FOUND",
    "message": "查無報名資料。"
  }
}
```

HTTP Status：`404 Not Found`

### 報名狀態

| API 值 | 中文顯示 |
|---|---|
| `registered` | 已報名 |
| `cancelled` | 已取消 |
| `attended` | 已出席 |

---

## 7. 取消自己的報名

```http
DELETE /registrations/{registration_id}
Authorization: Bearer {token}
```

範例：

```http
DELETE /registrations/105
```

不需要 Request Body。

### 成功回應

HTTP Status：`200 OK`

```json
{
  "success": true,
  "data": null,
  "meta": null,
  "error": null
}
```

### 取消限制

- 只能取消目前登入會員自己的報名。
- 報名狀態必須是 `registered`。
- 賽事開始後不能取消。
- 成功取消後，狀態改為 `cancelled` 並寫入 `cancelled_at`。
- 目前一位會員對同一賽事只能存在一筆報名，因此取消後不能重新報名同一賽事。

---

## 錯誤回應

### 未登入或 Token 無效

HTTP Status：`401 Unauthorized`

```json
{
  "success": false,
  "data": null,
  "meta": null,
  "error": {
    "code": "UNAUTHENTICATED",
    "message": "請先登入會員。"
  }
}
```

可能原因包括：

- 沒有提供 Bearer Token。
- Token 不存在或已撤銷。
- Token 已過期。
- 會員帳號已停用。
- Token 所屬帳號不是 App 會員。

### 重複報名

HTTP Status：`409 Conflict`

```json
{
  "success": false,
  "data": null,
  "meta": null,
  "error": {
    "code": "ALREADY_REGISTERED",
    "message": "您已報名此賽事。"
  }
}
```

### 不在報名期間或名額已滿

HTTP Status：`422 Unprocessable Content`

```json
{
  "success": false,
  "data": null,
  "meta": null,
  "error": {
    "code": "UNPROCESSABLE_CONTENT",
    "message": "目前不在報名期間或名額已滿。"
  }
}
```

### 賽事項目無效

HTTP Status：`422 Unprocessable Content`

```json
{
  "success": false,
  "data": null,
  "meta": null,
  "error": {
    "code": "UNPROCESSABLE_CONTENT",
    "message": "請重新選擇有效的賽事項目。"
  }
}
```

### 欄位驗證失敗

HTTP Status：`422 Unprocessable Content`

```json
{
  "success": false,
  "data": null,
  "meta": null,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "輸入資料驗證失敗。",
    "details": {
      "organization": [
        "organization為必填欄位。"
      ]
    }
  }
}
```

### 非本人的報名或資料不存在

HTTP Status：`404 Not Found`

```json
{
  "success": false,
  "data": null,
  "meta": null,
  "error": {
    "code": "REGISTRATION_NOT_FOUND",
    "message": "查無報名資料。"
  }
}
```

### 報名狀態無法取消

HTTP Status：`422 Unprocessable Content`

```json
{
  "success": false,
  "data": null,
  "meta": null,
  "error": {
    "code": "UNPROCESSABLE_CONTENT",
    "message": "此報名無法取消。"
  }
}
```

### 賽事已開始

HTTP Status：`422 Unprocessable Content`

```json
{
  "success": false,
  "data": null,
  "meta": null,
  "error": {
    "code": "UNPROCESSABLE_CONTENT",
    "message": "賽事已開始，無法取消。"
  }
}
```

### 系統內部錯誤

HTTP Status：`500 Internal Server Error`

```json
{
  "success": false,
  "data": null,
  "meta": null,
  "error": {
    "code": "SERVER_ERROR",
    "message": "系統忙碌中，請稍後再試。"
  }
}
```

API 不會將 Laravel 例外名稱、SQL、伺服器檔案路徑或程式堆疊回傳給用戶端；完整內容只會記錄在後端 Log。

---

## cURL 範例

### 會員註冊

```bash
curl -X POST "https://purple-oyster-440244.hostingersite.com/api/v1/auth/register" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "王小明",
    "email": "user@example.com",
    "password": "password123",
    "phone": "0912345678"
  }'
```

### 取得賽事詳情

```bash
curl "https://purple-oyster-440244.hostingersite.com/api/v1/events/gymnastics-2026" \
  -H "Accept: application/json"
```

### 送出報名

```bash
curl -X POST "https://purple-oyster-440244.hostingersite.com/api/v1/events/gymnastics-2026/registrations" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "contact_phone": "0912345678",
    "organization": "台北體操協會",
    "emergency_contact_name": "王大明",
    "emergency_contact_phone": "0922333444",
    "item_ids": [31, 32],
    "notes": "素食"
  }'
```

### 查詢自己的報名紀錄

```bash
curl "https://purple-oyster-440244.hostingersite.com/api/v1/me/registrations" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 取消報名

```bash
curl -X DELETE "https://purple-oyster-440244.hostingersite.com/api/v1/registrations/105" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 流量限制

- 一般 API：每分鐘最多 60 次請求。
- 登入及註冊 API：每分鐘最多 10 次請求。

超過限制時會回傳 HTTP `429 Too Many Requests`：

```json
{
  "success": false,
  "data": null,
  "meta": null,
  "error": {
    "code": "TOO_MANY_REQUESTS",
    "message": "請求次數過多，請稍後再試。"
  }
}
```
