-- Add first-login password-change and lockout fields to an existing installation.
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 AFTER `account_status`,
    ADD COLUMN IF NOT EXISTS `failed_attempts` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `must_change_password`,
    ADD COLUMN IF NOT EXISTS `locked_until` DATETIME NULL AFTER `failed_attempts`;
