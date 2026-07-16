-- Pump Management Module schema for fresh installations.
-- The main fuelops_schema.sql already contains this normalized structure.

CREATE TABLE IF NOT EXISTS `fuel_types` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `short_name` VARCHAR(30) NOT NULL,
  `unit` VARCHAR(20) NOT NULL DEFAULT 'litre',
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fuel_types_name` (`name`),
  UNIQUE KEY `uq_fuel_types_short` (`short_name`),
  KEY `idx_fuel_types_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pumps` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `station_id` BIGINT UNSIGNED NULL,
  `pump_code` VARCHAR(40) NOT NULL,
  `pump_name` VARCHAR(150) NOT NULL,
  `fuel_type_id` BIGINT UNSIGNED NOT NULL,
  `current_meter_reading` DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  `manufacturer` VARCHAR(120) NULL,
  `model` VARCHAR(120) NULL,
  `serial_number` VARCHAR(120) NULL,
  `installation_date` DATE NULL,
  `status` ENUM('active','inactive','under_maintenance','faulty') NOT NULL DEFAULT 'active',
  `notes` TEXT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pumps_code` (`pump_code`),
  UNIQUE KEY `uq_pumps_serial_number` (`serial_number`),
  KEY `idx_pumps_fuel_type` (`fuel_type_id`),
  KEY `idx_pumps_status` (`status`),
  KEY `idx_pumps_manufacturer` (`manufacturer`),
  KEY `idx_pumps_installation_date` (`installation_date`),
  CONSTRAINT `fk_pumps_fuel_type` FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_pumps_meter_nonnegative` CHECK (`current_meter_reading` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
