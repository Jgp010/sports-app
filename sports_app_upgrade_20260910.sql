-- 體育賽事 App 既有資料庫升級檔（2026-09-10）
-- 新增：賽事項目、一般/早鳥費用、報名單位、報名金額快照。
-- 僅供已匯入舊版 sports_app_mysql.sql 的資料庫使用，請先備份。

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET time_zone = '+08:00';

CREATE TABLE `event_items` (
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

ALTER TABLE `event_registrations`
  ADD COLUMN `organization` VARCHAR(150) NULL AFTER `contact_phone`,
  ADD COLUMN `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `organization`;

CREATE TABLE `event_registration_items` (
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

-- 為既有賽事建立可編輯的預設項目，避免升級後無法報名。
INSERT INTO `event_items`
(`event_id`,`name`,`registration_fee`,`sort_order`,`is_active`,`created_at`,`updated_at`)
SELECT `id`,'一般項目',0,0,1,'2026-09-10 12:00:00','2026-09-10 12:00:00'
FROM `events` WHERE `deleted_at` IS NULL;

-- 將既有報名連結到該賽事的預設項目，成交價保留為 0。
INSERT INTO `event_registration_items`
(`event_registration_id`,`event_item_id`,`unit_price`,`created_at`)
SELECT registrations.`id`, MIN(items.`id`), 0, '2026-09-10 12:00:00'
FROM `event_registrations` registrations
JOIN `event_items` items ON items.`event_id`=registrations.`event_id`
GROUP BY registrations.`id`;

INSERT INTO `migrations` (`migration`,`batch`)
SELECT '2026_09_10_000010_add_event_items_and_registration_organization', COALESCE((SELECT MAX(`batch`) FROM `migrations`),0)+1
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` WHERE `migration`='2026_09_10_000010_add_event_items_and_registration_organization'
);
