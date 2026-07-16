-- FuelOps Filling Station Staff & Activity Management System
-- Complete normalized MySQL schema for backend development
-- Target: MySQL 8.0+

SET NAMES utf8mb4;
SET time_zone = '+01:00';
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `umachi` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `umachi`;

DROP TABLE IF EXISTS `activity_logs`, `system_settings`, `announcement_reads`, `announcement_audiences`, `announcement_attachments`, `announcements`, `leave_request_actions`, `leave_request_attachments`, `leave_requests`, `leave_balances`, `leave_approval_steps`, `leave_approval_workflows`, `leave_types`, `fuel_inventory_movements`, `fuel_sales`, `fuel_delivery_items`, `fuel_deliveries`, `fuel_inventory_levels`, `fuel_tanks`, `fuel_price_history`, `fuel_prices`, `fuel_suppliers`, `pump_meter_readings`, `pump_allocations`, `pumps`, `fuel_types`, `roster_assignments`, `attendance_adjustments`, `attendance_records`, `attendance_settings`, `shifts`, `password_reset_tokens`, `login_attempts`, `user_sessions`, `remember_tokens`, `user_permissions`, `role_permissions`, `user_roles`, `permissions`, `roles`, `employee_documents`, `employee_bank_accounts`, `employee_emergency_contacts`, `users`, `employees`, `employee_sequences`, `job_titles`, `departments`;

CREATE TABLE `departments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_departments_name` (`name`), KEY `idx_departments_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_titles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `department_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_job_titles_department_name` (`department_id`,`name`), KEY `idx_job_titles_status` (`status`),
  CONSTRAINT `fk_job_titles_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `employee_sequences` (
  `sequence_name` VARCHAR(50) NOT NULL,
  `last_number` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`sequence_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `employees` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_code` VARCHAR(30) NOT NULL,
  `first_name` VARCHAR(80) NOT NULL,
  `last_name` VARCHAR(80) NOT NULL,
  `other_names` VARCHAR(120) NULL,
  `gender` ENUM('male','female','other') NULL,
  `date_of_birth` DATE NULL,
  `marital_status` ENUM('single','married','divorced','widowed') NULL,
  `phone` VARCHAR(30) NULL,
  `email` VARCHAR(150) NULL,
  `address` TEXT NULL,
  `state` VARCHAR(100) NULL,
  `local_government_area` VARCHAR(120) NULL,
  `nationality` VARCHAR(100) NULL,
  `national_id` VARCHAR(100) NULL,
  `drivers_license` VARCHAR(100) NULL,
  `department_id` BIGINT UNSIGNED NULL,
  `job_title_id` BIGINT UNSIGNED NULL,
  `supervisor_id` BIGINT UNSIGNED NULL,
  `employment_type` ENUM('full_time','part_time','contract','casual','intern') NOT NULL DEFAULT 'full_time',
  `employment_status` ENUM('active','probation','suspended','resigned','inactive','on_leave','terminated') NOT NULL DEFAULT 'active',
  `date_joined` DATE NULL,
  `salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `allowance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `photo_path` VARCHAR(255) NULL,
  `profile_completed` TINYINT(1) NOT NULL DEFAULT 0,
  `profile_completed_at` DATETIME NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `deleted_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_employees_employee_code` (`employee_code`), UNIQUE KEY `uq_employees_email` (`email`),
  KEY `idx_employees_name` (`last_name`,`first_name`), KEY `idx_employees_department` (`department_id`), KEY `idx_employees_job_title` (`job_title_id`), KEY `idx_employees_supervisor` (`supervisor_id`), KEY `idx_employees_status` (`employment_status`),
  CONSTRAINT `fk_employees_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_employees_job_title` FOREIGN KEY (`job_title_id`) REFERENCES `job_titles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_employees_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` BIGINT UNSIGNED NULL,
  `username` VARCHAR(80) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `account_status` ENUM('active','inactive','locked','pending') NOT NULL DEFAULT 'active',
    `must_change_password` TINYINT(1) NOT NULL DEFAULT 0,
  `failed_attempts` INT UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` DATETIME NULL,
`two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `two_factor_secret` VARCHAR(255) NULL,
  `last_login_at` DATETIME NULL,
  `last_password_change_at` DATETIME NULL,
  `email_verified_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_users_employee` (`employee_id`), UNIQUE KEY `uq_users_username` (`username`), UNIQUE KEY `uq_users_email` (`email`), KEY `idx_users_status` (`account_status`), KEY `idx_users_lockout` (`locked_until`),
  CONSTRAINT `fk_users_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employees_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_employees_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_employees_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

CREATE TABLE `employee_emergency_contacts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `employee_id` BIGINT UNSIGNED NOT NULL, `contact_name` VARCHAR(150) NOT NULL, `relationship` VARCHAR(80) NULL, `phone` VARCHAR(30) NOT NULL, `email` VARCHAR(150) NULL, `address` TEXT NULL, `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `idx_employee_contacts_employee` (`employee_id`), CONSTRAINT `fk_employee_contacts_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `employee_bank_accounts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `employee_id` BIGINT UNSIGNED NOT NULL, `bank_name` VARCHAR(120) NOT NULL, `account_name` VARCHAR(150) NOT NULL, `account_number` VARCHAR(30) NOT NULL, `is_primary` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_employee_bank_account` (`employee_id`,`account_number`), CONSTRAINT `fk_employee_bank_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `employee_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `employee_id` BIGINT UNSIGNED NOT NULL, `document_type` VARCHAR(100) NOT NULL, `document_title` VARCHAR(180) NOT NULL, `file_path` VARCHAR(255) NOT NULL, `uploaded_by` BIGINT UNSIGNED NULL, `expires_on` DATE NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `idx_employee_documents_employee` (`employee_id`), KEY `idx_employee_documents_type` (`document_type`),
  CONSTRAINT `fk_employee_documents_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_employee_documents_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `name` VARCHAR(80) NOT NULL, `slug` VARCHAR(90) NOT NULL, `description` TEXT NULL, `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `module` VARCHAR(80) NOT NULL, `name` VARCHAR(120) NOT NULL, `slug` VARCHAR(150) NOT NULL, `description` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_permissions_slug` (`slug`), KEY `idx_permissions_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_roles` (`user_id` BIGINT UNSIGNED NOT NULL, `role_id` BIGINT UNSIGNED NOT NULL, `assigned_by` BIGINT UNSIGNED NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`user_id`,`role_id`), KEY `idx_user_roles_role` (`role_id`), CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE, CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE, CONSTRAINT `fk_user_roles_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `role_permissions` (`role_id` BIGINT UNSIGNED NOT NULL, `permission_id` BIGINT UNSIGNED NOT NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`role_id`,`permission_id`), KEY `idx_role_permissions_permission` (`permission_id`), CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE, CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `user_permissions` (`user_id` BIGINT UNSIGNED NOT NULL, `permission_id` BIGINT UNSIGNED NOT NULL, `effect` ENUM('allow','deny') NOT NULL DEFAULT 'allow', `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`user_id`,`permission_id`), CONSTRAINT `fk_user_permissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE, CONSTRAINT `fk_user_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `remember_tokens` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `user_id` BIGINT UNSIGNED NOT NULL, `selector` VARCHAR(64) NOT NULL, `token_hash` VARCHAR(255) NOT NULL, `expires_at` DATETIME NOT NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), UNIQUE KEY `uq_remember_tokens_selector` (`selector`), KEY `idx_remember_tokens_user` (`user_id`), CONSTRAINT `fk_remember_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `user_sessions` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `user_id` BIGINT UNSIGNED NOT NULL, `session_token_hash` VARCHAR(255) NOT NULL, `ip_address` VARCHAR(45) NULL, `user_agent` VARCHAR(500) NULL, `browser` VARCHAR(120) NULL, `operating_system` VARCHAR(120) NULL, `device_type` VARCHAR(80) NULL, `last_activity_at` DATETIME NULL, `expires_at` DATETIME NOT NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), UNIQUE KEY `uq_user_sessions_token` (`session_token_hash`), KEY `idx_user_sessions_user` (`user_id`), KEY `idx_user_sessions_expires` (`expires_at`), CONSTRAINT `fk_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `login_attempts` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `username` VARCHAR(150) NOT NULL, `user_id` BIGINT UNSIGNED NULL, `ip_address` VARCHAR(45) NULL, `user_agent` VARCHAR(500) NULL, `status` ENUM('success','failed') NOT NULL, `failure_reason` VARCHAR(180) NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), KEY `idx_login_attempts_username` (`username`), KEY `idx_login_attempts_user` (`user_id`), KEY `idx_login_attempts_ip_created` (`ip_address`,`created_at`), CONSTRAINT `fk_login_attempts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `password_reset_tokens` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `user_id` BIGINT UNSIGNED NOT NULL, `token_hash` VARCHAR(255) NOT NULL, `expires_at` DATETIME NOT NULL, `used_at` DATETIME NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), UNIQUE KEY `uq_password_reset_token` (`token_hash`), KEY `idx_password_reset_user` (`user_id`), CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `shifts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `name` VARCHAR(100) NOT NULL, `start_time` TIME NOT NULL, `end_time` TIME NOT NULL, `max_employees` INT UNSIGNED NULL, `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_shifts_name` (`name`), KEY `idx_shifts_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `attendance_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `shift_id` BIGINT UNSIGNED NULL, `clock_in_time` TIME NOT NULL, `clock_out_time` TIME NOT NULL, `grace_period_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 0, `late_threshold_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 0, `overtime_start_time` TIME NULL, `max_overtime_hours` DECIMAL(5,2) NULL, `auto_clock_out` TINYINT(1) NOT NULL DEFAULT 0, `photo_required` TINYINT(1) NOT NULL DEFAULT 0, `face_verification_required` TINYINT(1) NOT NULL DEFAULT 0, `gps_verification_required` TINYINT(1) NOT NULL DEFAULT 0, `early_clock_in_allowed` TINYINT(1) NOT NULL DEFAULT 1, `manual_adjustment_allowed` TINYINT(1) NOT NULL DEFAULT 1, `approval_required_role` VARCHAR(80) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), KEY `idx_attendance_settings_shift` (`shift_id`), CONSTRAINT `fk_attendance_settings_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `attendance_records` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `employee_id` BIGINT UNSIGNED NOT NULL, `shift_id` BIGINT UNSIGNED NULL, `attendance_date` DATE NOT NULL, `clock_in_at` DATETIME NULL, `clock_out_at` DATETIME NULL, `clock_in_photo_path` VARCHAR(255) NULL, `clock_out_photo_path` VARCHAR(255) NULL, `clock_in_latitude` DECIMAL(10,7) NULL, `clock_in_longitude` DECIMAL(10,7) NULL, `clock_out_latitude` DECIMAL(10,7) NULL, `clock_out_longitude` DECIMAL(10,7) NULL, `status` ENUM('present','absent','late','on_leave','off_duty') NOT NULL DEFAULT 'present', `remarks` TEXT NULL, `approved_by` BIGINT UNSIGNED NULL, `approved_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_attendance_employee_date_shift` (`employee_id`,`attendance_date`,`shift_id`), KEY `idx_attendance_date_status` (`attendance_date`,`status`), KEY `idx_attendance_shift` (`shift_id`), KEY `idx_attendance_approved_by` (`approved_by`),
  CONSTRAINT `fk_attendance_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_attendance_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_attendance_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_attendance_clock_order` CHECK (`clock_out_at` IS NULL OR `clock_in_at` IS NULL OR `clock_out_at` >= `clock_in_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `attendance_adjustments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `attendance_id` BIGINT UNSIGNED NOT NULL, `requested_by` BIGINT UNSIGNED NULL, `approved_by` BIGINT UNSIGNED NULL, `old_clock_in_at` DATETIME NULL, `old_clock_out_at` DATETIME NULL, `new_clock_in_at` DATETIME NULL, `new_clock_out_at` DATETIME NULL, `reason` TEXT NOT NULL, `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), KEY `idx_attendance_adjustments_attendance` (`attendance_id`),
  CONSTRAINT `fk_attendance_adjustments_attendance` FOREIGN KEY (`attendance_id`) REFERENCES `attendance_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_attendance_adjustments_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_attendance_adjustments_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fuel_types` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `name` VARCHAR(100) NOT NULL, `short_name` VARCHAR(30) NOT NULL, `unit` VARCHAR(20) NOT NULL DEFAULT 'litre', `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_fuel_types_name` (`name`), UNIQUE KEY `uq_fuel_types_short` (`short_name`), KEY `idx_fuel_types_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pumps` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `pump_code` VARCHAR(40) NOT NULL, `pump_name` VARCHAR(150) NOT NULL, `fuel_type_id` BIGINT UNSIGNED NOT NULL, `current_meter_reading` DECIMAL(14,3) NOT NULL DEFAULT 0.000, `manufacturer` VARCHAR(120) NULL, `model` VARCHAR(120) NULL, `serial_number` VARCHAR(120) NULL, `installation_date` DATE NULL, `status` ENUM('active','inactive','under_maintenance','faulty') NOT NULL DEFAULT 'active', `notes` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_pumps_code` (`pump_code`), UNIQUE KEY `uq_pumps_serial_number` (`serial_number`), KEY `idx_pumps_fuel_type` (`fuel_type_id`), KEY `idx_pumps_status` (`status`),
  CONSTRAINT `fk_pumps_fuel_type` FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_pumps_meter_nonnegative` CHECK (`current_meter_reading` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roster_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `roster_date` DATE NOT NULL, `employee_id` BIGINT UNSIGNED NOT NULL, `shift_id` BIGINT UNSIGNED NOT NULL, `pump_id` BIGINT UNSIGNED NULL, `supervisor_id` BIGINT UNSIGNED NULL, `reporting_time` TIME NOT NULL, `closing_time` TIME NOT NULL, `status` ENUM('scheduled','active','completed','off_duty','on_leave','cancelled') NOT NULL DEFAULT 'scheduled', `notes` TEXT NULL, `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_roster_employee_shift_date` (`roster_date`,`employee_id`,`shift_id`), UNIQUE KEY `uq_roster_pump_shift_date` (`roster_date`,`pump_id`,`shift_id`), KEY `idx_roster_date_status` (`roster_date`,`status`), KEY `idx_roster_employee` (`employee_id`), KEY `idx_roster_supervisor` (`supervisor_id`),
  CONSTRAINT `fk_roster_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_roster_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_roster_pump` FOREIGN KEY (`pump_id`) REFERENCES `pumps` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_roster_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_roster_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pump_allocations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `roster_assignment_id` BIGINT UNSIGNED NULL, `allocation_date` DATE NOT NULL, `employee_id` BIGINT UNSIGNED NOT NULL, `pump_id` BIGINT UNSIGNED NOT NULL, `shift_id` BIGINT UNSIGNED NOT NULL, `supervisor_id` BIGINT UNSIGNED NULL, `status` ENUM('assigned','completed','cancelled') NOT NULL DEFAULT 'assigned',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_pump_allocation_pump_shift_date` (`allocation_date`,`pump_id`,`shift_id`), KEY `idx_pump_allocations_employee` (`employee_id`), KEY `idx_pump_allocations_roster` (`roster_assignment_id`),
  CONSTRAINT `fk_pump_allocations_roster` FOREIGN KEY (`roster_assignment_id`) REFERENCES `roster_assignments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pump_allocations_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pump_allocations_pump` FOREIGN KEY (`pump_id`) REFERENCES `pumps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pump_allocations_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_pump_allocations_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pump_meter_readings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `pump_id` BIGINT UNSIGNED NOT NULL, `employee_id` BIGINT UNSIGNED NULL, `reading_at` DATETIME NOT NULL, `meter_reading` DECIMAL(14,3) NOT NULL, `reading_type` ENUM('opening','closing','inspection','adjustment') NOT NULL, `remarks` TEXT NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), KEY `idx_pump_meter_pump_date` (`pump_id`,`reading_at`), KEY `idx_pump_meter_employee` (`employee_id`),
  CONSTRAINT `fk_pump_meter_pump` FOREIGN KEY (`pump_id`) REFERENCES `pumps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pump_meter_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_pump_meter_nonnegative` CHECK (`meter_reading` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fuel_prices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `fuel_type_id` BIGINT UNSIGNED NOT NULL, `price_per_litre` DECIMAL(12,2) NOT NULL, `effective_from` DATETIME NOT NULL, `effective_to` DATETIME NULL, `status` ENUM('scheduled','active','expired') NOT NULL DEFAULT 'scheduled', `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), KEY `idx_fuel_prices_fuel_effective` (`fuel_type_id`,`effective_from`,`effective_to`), KEY `idx_fuel_prices_status` (`status`),
  CONSTRAINT `fk_fuel_prices_fuel_type` FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fuel_prices_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_fuel_prices_positive` CHECK (`price_per_litre` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fuel_price_history` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `fuel_type_id` BIGINT UNSIGNED NOT NULL, `old_price` DECIMAL(12,2) NULL, `new_price` DECIMAL(12,2) NOT NULL, `effective_from` DATETIME NOT NULL, `changed_by` BIGINT UNSIGNED NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), KEY `idx_fuel_price_history_fuel` (`fuel_type_id`,`created_at`),
  CONSTRAINT `fk_fuel_price_history_fuel_type` FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fuel_price_history_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fuel_suppliers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `name` VARCHAR(180) NOT NULL, `phone` VARCHAR(30) NULL, `email` VARCHAR(150) NULL, `address` TEXT NULL, `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_fuel_suppliers_name` (`name`), KEY `idx_fuel_suppliers_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fuel_tanks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `fuel_type_id` BIGINT UNSIGNED NOT NULL, `tank_code` VARCHAR(60) NOT NULL, `tank_name` VARCHAR(150) NOT NULL, `capacity_litres` DECIMAL(14,3) NOT NULL, `minimum_stock_litres` DECIMAL(14,3) NOT NULL DEFAULT 0.000, `status` ENUM('active','inactive','maintenance') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_fuel_tanks_code` (`tank_code`), KEY `idx_fuel_tanks_fuel` (`fuel_type_id`),
  CONSTRAINT `fk_fuel_tanks_fuel_type` FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_fuel_tanks_capacity` CHECK (`capacity_litres` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fuel_inventory_levels` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `fuel_type_id` BIGINT UNSIGNED NOT NULL, `tank_id` BIGINT UNSIGNED NULL, `current_stock_litres` DECIMAL(14,3) NOT NULL DEFAULT 0.000, `minimum_stock_litres` DECIMAL(14,3) NOT NULL DEFAULT 0.000, `last_delivery_at` DATETIME NULL, `last_updated_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_inventory_level_fuel_tank` (`fuel_type_id`,`tank_id`), KEY `idx_inventory_levels_tank` (`tank_id`),
  CONSTRAINT `fk_inventory_levels_fuel_type` FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_levels_tank` FOREIGN KEY (`tank_id`) REFERENCES `fuel_tanks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_inventory_stock_nonnegative` CHECK (`current_stock_litres` >= 0 AND `minimum_stock_litres` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fuel_deliveries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `delivery_code` VARCHAR(60) NOT NULL, `supplier_id` BIGINT UNSIGNED NOT NULL, `delivery_datetime` DATETIME NOT NULL, `tanker_number` VARCHAR(80) NOT NULL, `invoice_number` VARCHAR(100) NOT NULL, `received_by` BIGINT UNSIGNED NULL, `remarks` TEXT NULL, `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_fuel_deliveries_code` (`delivery_code`), UNIQUE KEY `uq_fuel_deliveries_invoice` (`invoice_number`), KEY `idx_fuel_deliveries_supplier` (`supplier_id`), KEY `idx_fuel_deliveries_datetime` (`delivery_datetime`),
  CONSTRAINT `fk_fuel_deliveries_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `fuel_suppliers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fuel_deliveries_received_by` FOREIGN KEY (`received_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fuel_deliveries_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fuel_delivery_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `fuel_delivery_id` BIGINT UNSIGNED NOT NULL, `fuel_type_id` BIGINT UNSIGNED NOT NULL, `tank_id` BIGINT UNSIGNED NULL, `quantity_litres` DECIMAL(14,3) NOT NULL, `cost_per_litre` DECIMAL(12,2) NOT NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), KEY `idx_delivery_items_delivery` (`fuel_delivery_id`), KEY `idx_delivery_items_fuel` (`fuel_type_id`),
  CONSTRAINT `fk_delivery_items_delivery` FOREIGN KEY (`fuel_delivery_id`) REFERENCES `fuel_deliveries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_delivery_items_fuel_type` FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_delivery_items_tank` FOREIGN KEY (`tank_id`) REFERENCES `fuel_tanks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_delivery_items_positive` CHECK (`quantity_litres` > 0 AND `cost_per_litre` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fuel_sales` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `sale_code` VARCHAR(60) NOT NULL, `sale_date` DATE NOT NULL, `employee_id` BIGINT UNSIGNED NOT NULL, `shift_id` BIGINT UNSIGNED NULL, `roster_assignment_id` BIGINT UNSIGNED NULL, `pump_id` BIGINT UNSIGNED NOT NULL, `fuel_type_id` BIGINT UNSIGNED NOT NULL, `attendance_id` BIGINT UNSIGNED NULL, `opening_meter` DECIMAL(14,3) NOT NULL, `closing_meter` DECIMAL(14,3) NOT NULL, `litres_sold` DECIMAL(14,3) NOT NULL, `unit_price` DECIMAL(12,2) NOT NULL, `total_amount` DECIMAL(14,2) NOT NULL, `submitted_at` DATETIME NULL, `verified_by` BIGINT UNSIGNED NULL, `verified_at` DATETIME NULL, `rejection_reason` TEXT NULL, `status` ENUM('pending','verified','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_fuel_sales_code` (`sale_code`), KEY `idx_fuel_sales_date_status` (`sale_date`,`status`), KEY `idx_fuel_sales_employee` (`employee_id`), KEY `idx_fuel_sales_pump` (`pump_id`), KEY `idx_fuel_sales_fuel_type` (`fuel_type_id`), KEY `idx_fuel_sales_roster` (`roster_assignment_id`),
  CONSTRAINT `fk_fuel_sales_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fuel_sales_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fuel_sales_roster` FOREIGN KEY (`roster_assignment_id`) REFERENCES `roster_assignments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fuel_sales_pump` FOREIGN KEY (`pump_id`) REFERENCES `pumps` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fuel_sales_fuel_type` FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_fuel_sales_attendance` FOREIGN KEY (`attendance_id`) REFERENCES `attendance_records` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_fuel_sales_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_fuel_sales_meter_order` CHECK (`closing_meter` >= `opening_meter`), CONSTRAINT `chk_fuel_sales_amounts` CHECK (`litres_sold` >= 0 AND `unit_price` >= 0 AND `total_amount` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fuel_inventory_movements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `fuel_type_id` BIGINT UNSIGNED NOT NULL, `tank_id` BIGINT UNSIGNED NULL, `movement_type` ENUM('opening_balance','delivery','sale','adjustment','loss') NOT NULL, `quantity_litres` DECIMAL(14,3) NOT NULL, `unit_cost` DECIMAL(12,2) NULL, `movement_datetime` DATETIME NOT NULL, `fuel_delivery_item_id` BIGINT UNSIGNED NULL, `fuel_sale_id` BIGINT UNSIGNED NULL, `reference` VARCHAR(120) NULL, `remarks` TEXT NULL, `created_by` BIGINT UNSIGNED NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), KEY `idx_inventory_movements_fuel_date` (`fuel_type_id`,`movement_datetime`), KEY `idx_inventory_movements_tank` (`tank_id`), KEY `idx_inventory_movements_delivery_item` (`fuel_delivery_item_id`), KEY `idx_inventory_movements_sale` (`fuel_sale_id`),
  CONSTRAINT `fk_inventory_movements_fuel_type` FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_movements_tank` FOREIGN KEY (`tank_id`) REFERENCES `fuel_tanks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_movements_delivery_item` FOREIGN KEY (`fuel_delivery_item_id`) REFERENCES `fuel_delivery_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_movements_sale` FOREIGN KEY (`fuel_sale_id`) REFERENCES `fuel_sales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_movements_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_inventory_movements_quantity` CHECK (`quantity_litres` <> 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `leave_types` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `name` VARCHAR(100) NOT NULL, `description` TEXT NULL, `max_days_per_year` SMALLINT UNSIGNED NOT NULL DEFAULT 0, `is_paid` TINYINT(1) NOT NULL DEFAULT 1, `requires_attachment` TINYINT(1) NOT NULL DEFAULT 0, `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_leave_types_name` (`name`), KEY `idx_leave_types_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `leave_approval_workflows` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `name` VARCHAR(120) NOT NULL, `slug` VARCHAR(120) NOT NULL, `description` TEXT NULL, `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_leave_workflows_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `leave_approval_steps` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `workflow_id` BIGINT UNSIGNED NOT NULL, `step_order` SMALLINT UNSIGNED NOT NULL, `approver_role_id` BIGINT UNSIGNED NULL, `approver_job_title_id` BIGINT UNSIGNED NULL, `label` VARCHAR(120) NOT NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_leave_steps_workflow_order` (`workflow_id`,`step_order`),
  CONSTRAINT `fk_leave_steps_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `leave_approval_workflows` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_leave_steps_role` FOREIGN KEY (`approver_role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_leave_steps_job_title` FOREIGN KEY (`approver_job_title_id`) REFERENCES `job_titles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `leave_balances` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `employee_id` BIGINT UNSIGNED NOT NULL, `leave_type_id` BIGINT UNSIGNED NOT NULL, `year` SMALLINT UNSIGNED NOT NULL, `entitled_days` DECIMAL(6,2) NOT NULL DEFAULT 0.00, `used_days` DECIMAL(6,2) NOT NULL DEFAULT 0.00, `carried_forward_days` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_leave_balances_employee_type_year` (`employee_id`,`leave_type_id`,`year`), KEY `idx_leave_balances_type` (`leave_type_id`),
  CONSTRAINT `fk_leave_balances_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_leave_balances_type` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_leave_balances_nonnegative` CHECK (`entitled_days` >= 0 AND `used_days` >= 0 AND `carried_forward_days` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `leave_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `request_code` VARCHAR(60) NOT NULL, `employee_id` BIGINT UNSIGNED NOT NULL, `leave_type_id` BIGINT UNSIGNED NOT NULL, `workflow_id` BIGINT UNSIGNED NULL, `reason` TEXT NOT NULL, `start_date` DATE NOT NULL, `end_date` DATE NOT NULL, `total_days` DECIMAL(6,2) NOT NULL, `applied_at` DATETIME NOT NULL, `current_stage` VARCHAR(120) NULL, `status` ENUM('pending','forwarded','approved','rejected','cancelled') NOT NULL DEFAULT 'pending', `final_approved_by` BIGINT UNSIGNED NULL, `final_approved_at` DATETIME NULL, `notes` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_leave_requests_code` (`request_code`), KEY `idx_leave_requests_employee` (`employee_id`), KEY `idx_leave_requests_type` (`leave_type_id`), KEY `idx_leave_requests_status` (`status`,`applied_at`),
  CONSTRAINT `fk_leave_requests_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_leave_requests_type` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_leave_requests_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `leave_approval_workflows` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_leave_requests_final_approver` FOREIGN KEY (`final_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_leave_request_dates` CHECK (`end_date` >= `start_date` AND `total_days` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `leave_request_attachments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `leave_request_id` BIGINT UNSIGNED NOT NULL, `file_path` VARCHAR(255) NOT NULL, `original_name` VARCHAR(180) NULL, `uploaded_by` BIGINT UNSIGNED NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), KEY `idx_leave_attachments_request` (`leave_request_id`),
  CONSTRAINT `fk_leave_attachments_request` FOREIGN KEY (`leave_request_id`) REFERENCES `leave_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_leave_attachments_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `leave_request_actions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `leave_request_id` BIGINT UNSIGNED NOT NULL, `step_id` BIGINT UNSIGNED NULL, `actor_user_id` BIGINT UNSIGNED NULL, `action` ENUM('submitted','forwarded','approved','rejected','cancelled','commented') NOT NULL, `from_status` VARCHAR(40) NULL, `to_status` VARCHAR(40) NULL, `comments` TEXT NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), KEY `idx_leave_actions_request` (`leave_request_id`,`created_at`), KEY `idx_leave_actions_actor` (`actor_user_id`),
  CONSTRAINT `fk_leave_actions_request` FOREIGN KEY (`leave_request_id`) REFERENCES `leave_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_leave_actions_step` FOREIGN KEY (`step_id`) REFERENCES `leave_approval_steps` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_leave_actions_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `announcements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `title` VARCHAR(180) NOT NULL, `slug` VARCHAR(200) NOT NULL, `category` VARCHAR(100) NOT NULL, `content` MEDIUMTEXT NOT NULL, `priority` ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium', `status` ENUM('draft','published','scheduled','expired','archived') NOT NULL DEFAULT 'draft', `publish_at` DATETIME NULL, `expires_at` DATETIME NULL, `is_pinned` TINYINT(1) NOT NULL DEFAULT 0, `created_by` BIGINT UNSIGNED NULL, `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_announcements_slug` (`slug`), KEY `idx_announcements_status_publish` (`status`,`publish_at`), KEY `idx_announcements_category` (`category`),
  CONSTRAINT `fk_announcements_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_announcements_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `announcement_audiences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `announcement_id` BIGINT UNSIGNED NOT NULL, `audience_type` ENUM('everyone','role','department','job_title','employee') NOT NULL, `role_id` BIGINT UNSIGNED NULL, `department_id` BIGINT UNSIGNED NULL, `job_title_id` BIGINT UNSIGNED NULL, `employee_id` BIGINT UNSIGNED NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), KEY `idx_announcement_audiences_announcement` (`announcement_id`), KEY `idx_announcement_audiences_role` (`role_id`), KEY `idx_announcement_audiences_department` (`department_id`),
  CONSTRAINT `fk_announcement_audiences_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_announcement_audiences_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_announcement_audiences_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_announcement_audiences_job_title` FOREIGN KEY (`job_title_id`) REFERENCES `job_titles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_announcement_audiences_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `announcement_attachments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `announcement_id` BIGINT UNSIGNED NOT NULL, `file_path` VARCHAR(255) NOT NULL, `original_name` VARCHAR(180) NULL, `mime_type` VARCHAR(120) NULL, `file_size` BIGINT UNSIGNED NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), KEY `idx_announcement_attachments_announcement` (`announcement_id`), CONSTRAINT `fk_announcement_attachments_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `announcement_reads` (
  `announcement_id` BIGINT UNSIGNED NOT NULL, `employee_id` BIGINT UNSIGNED NOT NULL, `read_at` DATETIME NULL, `acknowledged_at` DATETIME NULL,
  PRIMARY KEY (`announcement_id`,`employee_id`), KEY `idx_announcement_reads_employee` (`employee_id`),
  CONSTRAINT `fk_announcement_reads_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_announcement_reads_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `system_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `setting_group` VARCHAR(80) NOT NULL, `setting_key` VARCHAR(120) NOT NULL, `setting_value` JSON NULL, `value_type` ENUM('string','number','boolean','json','time','date') NOT NULL DEFAULT 'string', `is_public` TINYINT(1) NOT NULL DEFAULT 0, `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_system_settings_group_key` (`setting_group`,`setting_key`), KEY `idx_system_settings_updated_by` (`updated_by`),
  CONSTRAINT `fk_system_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activity_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `log_code` VARCHAR(60) NULL, `user_id` BIGINT UNSIGNED NULL, `employee_id` BIGINT UNSIGNED NULL, `activity_type` VARCHAR(100) NOT NULL, `module` VARCHAR(100) NOT NULL, `activity` VARCHAR(255) NOT NULL, `entity_type` VARCHAR(120) NULL, `entity_id` BIGINT UNSIGNED NULL, `old_value` JSON NULL, `new_value` JSON NULL, `ip_address` VARCHAR(45) NULL, `browser` VARCHAR(120) NULL, `operating_system` VARCHAR(120) NULL, `device_type` VARCHAR(80) NULL, `status` ENUM('success','failed','warning','information') NOT NULL DEFAULT 'information', `notes` TEXT NULL, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_activity_logs_code` (`log_code`), KEY `idx_activity_logs_user` (`user_id`), KEY `idx_activity_logs_employee` (`employee_id`), KEY `idx_activity_logs_module_type` (`module`,`activity_type`), KEY `idx_activity_logs_created` (`created_at`), KEY `idx_activity_logs_status` (`status`),
  CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_activity_logs_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;



