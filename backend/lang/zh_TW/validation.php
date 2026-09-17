<?php

return [
    'required' => ':attribute為必填欄位。',
    'required_with' => '填寫相關欄位時，:attribute也必須填寫。',
    'string' => ':attribute必須是文字。',
    'integer' => ':attribute必須是整數。',
    'numeric' => ':attribute必須是數字。',
    'boolean' => ':attribute格式不正確。',
    'array' => ':attribute格式不正確。',
    'date' => ':attribute必須是有效日期。',
    'email' => ':attribute必須是有效的電子郵件地址。',
    'min' => [
        'array' => ':attribute至少需要 :min 項。',
        'numeric' => ':attribute不可小於 :min。',
        'string' => ':attribute至少需要 :min 個字元。',
    ],
    'max' => [
        'array' => ':attribute最多只能有 :max 項。',
        'numeric' => ':attribute不可大於 :max。',
        'string' => ':attribute不可超過 :max 個字元。',
    ],
    'after' => ':attribute必須晚於 :date。',
    'before_or_equal' => ':attribute必須早於或等於 :date。',
    'distinct' => ':attribute不可重複。',
    'exists' => '所選的:attribute不存在。',
    'unique' => ':attribute已被使用。',
    'attributes' => [
        'event_start_at' => '賽事開始時間',
        'event_end_at' => '賽事結束時間',
        'registration_open_at' => '報名開放時間',
        'registration_close_at' => '報名截止時間',
        'title' => '賽事名稱',
        'venue' => '地點',
        'description' => '賽事說明',
    ],
];
