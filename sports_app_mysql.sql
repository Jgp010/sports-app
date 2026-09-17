-- 體育賽事與報名 App
-- MySQL 8.0 / MariaDB 10.6+ 完整資料庫架構
-- 請先建立並選取目標資料庫，再匯入本檔案。
-- 本檔不會 DROP 既有資料表；建議匯入空白資料庫。
-- 所有業務時間由 Laravel（Asia/Taipei）寫入，資料表不使用 CURRENT_TIMESTAMP 自動值。

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET time_zone = '+08:00';
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `migrations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` VARCHAR(255) NOT NULL,
  `batch` INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(60) NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NULL,
  `phone` VARCHAR(30) NULL,
  `birth_date` DATE NULL,
  `gender` VARCHAR(20) NULL,
  `address` VARCHAR(255) NULL,
  `sso_provider` VARCHAR(60) NULL,
  `sso_subject` VARCHAR(191) NULL,
  `sso_credential` TEXT NULL,
  `email_verified_at` TIMESTAMP NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(255) NOT NULL DEFAULT 'editor',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` TIMESTAMP NULL,
  `remember_token` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_sso_provider_subject_unique` (`sso_provider`,`sso_subject`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` VARCHAR(255) NOT NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache` (
  `key` VARCHAR(255) NOT NULL,
  `value` MEDIUMTEXT NOT NULL,
  `expiration` INT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` VARCHAR(255) NOT NULL,
  `owner` VARCHAR(255) NOT NULL,
  `expiration` INT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL,
  `reserved_at` INT UNSIGNED NULL,
  `available_at` INT UNSIGNED NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `total_jobs` INT NOT NULL,
  `pending_jobs` INT NOT NULL,
  `failed_jobs` INT NOT NULL,
  `failed_job_ids` LONGTEXT NOT NULL,
  `options` MEDIUMTEXT NULL,
  `cancelled_at` INT NULL,
  `created_at` INT NOT NULL,
  `finished_at` INT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(255) NOT NULL,
  `connection` TEXT NOT NULL,
  `queue` TEXT NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sports` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(60) NOT NULL,
  `slug` VARCHAR(80) NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sports_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `news_posts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sport_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(120) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `summary` VARCHAR(300) NOT NULL,
  `content` LONGTEXT NOT NULL,
  `cover_path` VARCHAR(255) NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `event_start_at` TIMESTAMP NULL,
  `venue` VARCHAR(160) NULL,
  `published_at` TIMESTAMP NULL,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `news_posts_slug_unique` (`slug`),
  KEY `news_posts_published_at_index` (`published_at`),
  KEY `news_posts_status_published_at_index` (`status`,`published_at`),
  KEY `news_posts_sport_id_published_at_index` (`sport_id`,`published_at`),
  CONSTRAINT `news_posts_sport_id_foreign` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `news_posts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `news_posts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `action` VARCHAR(80) NOT NULL,
  `entity_type` VARCHAR(100) NULL,
  `entity_id` BIGINT UNSIGNED NULL,
  `before_json` JSON NULL,
  `after_json` JSON NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_audit_logs_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  CONSTRAINT `admin_audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `api_access_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(80) NOT NULL DEFAULT 'android',
  `token_hash` VARCHAR(64) NOT NULL,
  `last_used_at` TIMESTAMP NULL,
  `expires_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_access_tokens_token_hash_unique` (`token_hash`),
  KEY `api_access_tokens_user_id_foreign` (`user_id`),
  CONSTRAINT `api_access_tokens_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sport_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(180) NOT NULL,
  `description` TEXT NOT NULL,
  `venue` VARCHAR(180) NOT NULL,
  `event_start_at` TIMESTAMP NOT NULL,
  `event_end_at` TIMESTAMP NULL,
  `registration_open_at` TIMESTAMP NOT NULL,
  `registration_close_at` TIMESTAMP NOT NULL,
  `capacity` INT UNSIGNED NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `created_by` BIGINT UNSIGNED NOT NULL,
  `updated_by` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `events_slug_unique` (`slug`),
  KEY `events_status_event_start_at_index` (`status`,`event_start_at`),
  KEY `events_sport_id_foreign` (`sport_id`),
  KEY `events_created_by_foreign` (`created_by`),
  KEY `events_updated_by_foreign` (`updated_by`),
  CONSTRAINT `events_sport_id_fk` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `events_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `events_updated_by_fk` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `event_registrations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `registration_no` VARCHAR(30) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'registered',
  `contact_phone` VARCHAR(30) NOT NULL,
  `organization` VARCHAR(150) NULL,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `emergency_contact_name` VARCHAR(80) NULL,
  `emergency_contact_phone` VARCHAR(30) NULL,
  `notes` TEXT NULL,
  `admin_notes` TEXT NULL,
  `registered_at` TIMESTAMP NOT NULL,
  `cancelled_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_registrations_registration_no_unique` (`registration_no`),
  UNIQUE KEY `event_registrations_event_id_user_id_unique` (`event_id`,`user_id`),
  KEY `event_registrations_event_id_status_index` (`event_id`,`status`),
  KEY `event_registrations_user_id_foreign` (`user_id`),
  CONSTRAINT `event_registrations_event_id_fk` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `event_registrations_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `event_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `description` VARCHAR(500) NULL,
  `registration_fee` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `early_bird_fee` DECIMAL(10,2) NULL,
  `early_bird_ends_at` TIMESTAMP NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_items_event_id_name_unique` (`event_id`,`name`),
  KEY `event_items_event_id_is_active_sort_order_index` (`event_id`,`is_active`,`sort_order`),
  CONSTRAINT `event_items_event_id_fk` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `event_registration_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_registration_id` BIGINT UNSIGNED NOT NULL,
  `event_item_id` BIGINT UNSIGNED NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `created_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `registration_item_unique` (`event_registration_id`,`event_item_id`),
  KEY `event_registration_items_event_item_id_foreign` (`event_item_id`),
  CONSTRAINT `event_registration_items_registration_fk` FOREIGN KEY (`event_registration_id`) REFERENCES `event_registrations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_registration_items_item_fk` FOREIGN KEY (`event_item_id`) REFERENCES `event_items` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Laravel migration history：匯入後執行 php artisan migrate 不會重建以上資料表。
INSERT IGNORE INTO `migrations` (`migration`,`batch`) VALUES
('0001_01_01_000000_create_users_table',1),
('0001_01_01_000001_create_cache_table',1),
('0001_01_01_000002_create_jobs_table',1),
('2026_08_25_000003_create_sports_table',1),
('2026_08_25_000004_create_news_posts_table',1),
('2026_08_25_000005_create_admin_audit_logs_table',1),
('2026_09_08_000006_add_member_fields_to_users_table',2),
('2026_09_08_000007_create_api_access_tokens_table',2),
('2026_09_08_000008_create_events_table',2),
('2026_09_08_000009_create_event_registrations_table',2),
('2026_09_10_000010_add_event_items_and_registration_organization',3),
('2026_09_10_000011_add_username_for_backend_accounts',4);

-- 示範帳號（正式環境匯入後請立即更換密碼）。
-- 管理員：admin@example.com / ChangeMe123!
-- 會員：member@example.com / Member123!
INSERT IGNORE INTO `users`
(`id`,`username`,`name`,`email`,`phone`,`password`,`role`,`is_active`,`created_at`,`updated_at`) VALUES
(1,'admin','系統管理員',NULL,NULL,'$2y$10$fwMDueoDvQVv2zX1BNJWfO9eqP0Ebwsv5JhXpF.NfEcxiKULEMqrO','admin',1,'2026-09-08 13:00:00','2026-09-08 13:00:00'),
(2,NULL,'示範會員','member@example.com','0912345678','$2y$10$rq0U0mmaFQMDx1mRCQJ4NelrF0e/y/JtHY92Y.Z3y5F8iWFpTkw8e','member',1,'2026-09-08 13:00:00','2026-09-08 13:00:00');

INSERT IGNORE INTO `sports` (`id`,`name`,`slug`,`sort_order`,`is_active`,`created_at`,`updated_at`) VALUES

(1,'12月tricking','tricking',10,1,'2026-09-08 13:00:00','2026-01-08 13:00:00');

INSERT IGNORE INTO `news_posts`
(`id`,`sport_id`,`title`,`slug`,`summary`,`content`,`status`,`published_at`,`created_by`,`updated_by`,`created_at`,`updated_at`) VALUES
(1,1,'體育賽事消息 App 正式啟動','welcome-to-sports-desk','這是一筆可供 Android App 與 API 測試的示範消息。','歡迎使用體育賽事與報名 App。管理員可在後台管理消息、賽事、會員與報名資料。','published','2026-09-08 13:00:00',1,1,'2026-09-08 13:00:00','2026-09-08 13:00:00');

INSERT IGNORE INTO `events`
(`id`,`sport_id`,`title`,`slug`,`description`,`venue`,`event_start_at`,`event_end_at`,`registration_open_at`,`registration_close_at`,`capacity`,`status`,`created_by`,`updated_by`,`created_at`,`updated_at`) VALUES
(1,1,'台北城市籃球交流賽','taipei-basketball-cup','提供 App 賽事列表與報名流程測試使用的示範賽事。','台北市立體育館','2026-09-22 09:00:00','2026-09-22 17:00:00','2026-09-07 13:00:00','2026-09-18 13:00:00',100,'published',1,1,'2026-09-08 13:00:00','2026-09-08 13:00:00');

INSERT IGNORE INTO `event_items`
(`id`,`event_id`,`name`,`description`,`registration_fee`,`early_bird_fee`,`early_bird_ends_at`,`sort_order`,`is_active`,`created_at`,`updated_at`) VALUES
(1,1,'一般組','示範報名項目',800.00,650.00,'2026-09-12 23:59:59',0,1,'2026-09-10 12:00:00','2026-09-10 12:00:00');
