-- 後台帳號管理升級檔（2026-09-10）
-- 新增獨立 username，後台帳號不再綁定 Email。匯入前請先備份資料庫。

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET time_zone = '+08:00';

ALTER TABLE `users`
  ADD COLUMN `username` VARCHAR(60) NULL AFTER `id`,
  MODIFY COLUMN `email` VARCHAR(255) NULL,
  ADD UNIQUE KEY `users_username_unique` (`username`);

-- 第一個系統管理員預設使用 admin，其餘後台帳號使用 role_id。
SET @first_admin_id := (SELECT MIN(`id`) FROM `users` WHERE `role`='admin');
UPDATE `users`
SET `username` = CASE
  WHEN `role`='admin' AND `id`=@first_admin_id THEN 'admin'
  ELSE CONCAT(`role`,'_',`id`)
END
WHERE `role` IN ('admin','editor') AND `username` IS NULL;

-- 後台帳號不保留 Email；一般會員 Email 不受影響。
UPDATE `users` SET `email`=NULL WHERE `role` IN ('admin','editor');

INSERT INTO `migrations` (`migration`,`batch`)
SELECT '2026_09_10_000011_add_username_for_backend_accounts', COALESCE((SELECT MAX(`batch`) FROM `migrations`),0)+1
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` WHERE `migration`='2026_09_10_000011_add_username_for_backend_accounts'
);
