-- Employee onboarding and first-login profile completion.
-- Run once against an existing FuelOps/Umachi database.

CREATE TABLE IF NOT EXISTS `employee_sequences` (
  `sequence_name` VARCHAR(50) NOT NULL,
  `last_number` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`sequence_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `employee_sequences` (`sequence_name`, `last_number`)
SELECT 'employee', COALESCE(MAX(CAST(SUBSTRING(`employee_code`, 8) AS UNSIGNED)), 0)
FROM `employees`
WHERE `employee_code` REGEXP '^UMACHI-[0-9]+$'
ON DUPLICATE KEY UPDATE `last_number` = GREATEST(`last_number`, VALUES(`last_number`));

ALTER TABLE `employees`
  MODIFY `phone` VARCHAR(30) NULL,
  MODIFY `employment_type` ENUM('full_time','part_time','contract','casual','intern') NOT NULL DEFAULT 'full_time',
  MODIFY `employment_status` ENUM('active','probation','suspended','resigned','inactive','on_leave','terminated') NOT NULL DEFAULT 'active',
  ADD COLUMN `profile_completed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `photo_path`,
  ADD COLUMN `profile_completed_at` DATETIME NULL AFTER `profile_completed`,
  ADD COLUMN `state` VARCHAR(100) NULL AFTER `address`,
  ADD COLUMN `local_government_area` VARCHAR(120) NULL AFTER `state`,
  ADD COLUMN `nationality` VARCHAR(100) NULL AFTER `local_government_area`,
  ADD COLUMN `national_id` VARCHAR(100) NULL AFTER `nationality`,
  ADD COLUMN `drivers_license` VARCHAR(100) NULL AFTER `national_id`;

-- Existing employees are not forced through onboarding retroactively.
UPDATE `employees`
SET `profile_completed` = 1,
    `profile_completed_at` = COALESCE(`profile_completed_at`, `updated_at`)
WHERE `date_of_birth` IS NOT NULL
  AND `phone` IS NOT NULL
  AND `address` IS NOT NULL;
