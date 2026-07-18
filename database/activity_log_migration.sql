CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `log_code` VARCHAR(60) NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `employee_id` BIGINT UNSIGNED NULL,
  `employee_name` VARCHAR(200) NULL,
  `role` VARCHAR(80) NULL,
  `activity_type` VARCHAR(100) NOT NULL,
  `action` VARCHAR(120) NULL,
  `module` VARCHAR(100) NOT NULL,
  `activity` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `entity_type` VARCHAR(120) NULL,
  `entity_id` BIGINT UNSIGNED NULL,
  `old_value` JSON NULL,
  `new_value` JSON NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(500) NULL,
  `browser` VARCHAR(120) NULL,
  `operating_system` VARCHAR(120) NULL,
  `device_type` VARCHAR(80) NULL,
  `request_method` VARCHAR(10) NULL,
  `request_url` VARCHAR(500) NULL,
  `status` ENUM('success','failed','warning','information') NOT NULL DEFAULT 'information',
  `notes` TEXT NULL,
  `archived_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_activity_logs_code` (`log_code`),
  KEY `idx_activity_logs_user` (`user_id`),
  KEY `idx_activity_logs_employee` (`employee_id`),
  KEY `idx_activity_logs_role` (`role`),
  KEY `idx_activity_logs_module` (`module`),
  KEY `idx_activity_logs_action` (`action`),
  KEY `idx_activity_logs_status` (`status`),
  KEY `idx_activity_logs_created` (`created_at`),
  KEY `idx_activity_logs_archived` (`archived_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `activity_logs`
  ADD COLUMN IF NOT EXISTS `employee_name` VARCHAR(200) NULL AFTER `employee_id`,
  ADD COLUMN IF NOT EXISTS `role` VARCHAR(80) NULL AFTER `employee_name`,
  ADD COLUMN IF NOT EXISTS `action` VARCHAR(120) NULL AFTER `activity_type`,
  ADD COLUMN IF NOT EXISTS `description` TEXT NULL AFTER `activity`,
  ADD COLUMN IF NOT EXISTS `user_agent` VARCHAR(500) NULL AFTER `ip_address`,
  ADD COLUMN IF NOT EXISTS `request_method` VARCHAR(10) NULL AFTER `device_type`,
  ADD COLUMN IF NOT EXISTS `request_url` VARCHAR(500) NULL AFTER `request_method`,
  ADD COLUMN IF NOT EXISTS `archived_at` TIMESTAMP NULL DEFAULT NULL AFTER `notes`;

CREATE INDEX IF NOT EXISTS `idx_activity_logs_role` ON `activity_logs` (`role`);
CREATE INDEX IF NOT EXISTS `idx_activity_logs_module` ON `activity_logs` (`module`);
CREATE INDEX IF NOT EXISTS `idx_activity_logs_action` ON `activity_logs` (`action`);
CREATE INDEX IF NOT EXISTS `idx_activity_logs_archived` ON `activity_logs` (`archived_at`);

UPDATE `activity_logs`
SET `action` = COALESCE(`action`, `activity_type`),
    `description` = COALESCE(`description`, `activity`)
WHERE `action` IS NULL OR `description` IS NULL;
