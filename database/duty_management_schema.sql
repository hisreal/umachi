-- Duty Management Module schema for fresh installations.
-- Existing installations are upgraded safely by App\Models\DutyManagement::ensureSchema().

CREATE TABLE IF NOT EXISTS `duty_rosters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `roster_name` VARCHAR(150) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `status` ENUM('Draft','Published','Archived') NOT NULL DEFAULT 'Draft',
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_duty_rosters_name_live` (`roster_name`),
  KEY `idx_duty_rosters_status` (`status`),
  KEY `idx_duty_rosters_dates` (`start_date`, `end_date`),
  CONSTRAINT `fk_duty_rosters_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_duty_rosters_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `duty_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `roster_id` BIGINT UNSIGNED NOT NULL,
  `employee_id` BIGINT UNSIGNED NOT NULL,
  `pump_id` BIGINT UNSIGNED NOT NULL,
  `fuel_type` ENUM('Petrol','Diesel','Gas') NOT NULL,
  `shift_id` BIGINT UNSIGNED NOT NULL,
  `assignment_date` DATE NOT NULL,
  `remarks` TEXT NULL,
  `status` ENUM('Assigned','Completed','Cancelled') NOT NULL DEFAULT 'Assigned',
  `legacy_roster_assignment_id` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_duty_assignments_roster` (`roster_id`),
  KEY `idx_duty_assignments_employee_date` (`employee_id`, `assignment_date`),
  KEY `idx_duty_assignments_pump_date` (`pump_id`, `assignment_date`),
  KEY `idx_duty_assignments_shift_date` (`shift_id`, `assignment_date`),
  KEY `idx_duty_assignments_status` (`status`),
  CONSTRAINT `fk_duty_assignments_roster` FOREIGN KEY (`roster_id`) REFERENCES `duty_rosters` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_duty_assignments_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_duty_assignments_pump` FOREIGN KEY (`pump_id`) REFERENCES `pumps` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_duty_assignments_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_duty_assignments_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
