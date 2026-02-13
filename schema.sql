-- CRM Stages Database Schema
-- Event-driven architecture with append-only event log

-- Companies table
CREATE TABLE IF NOT EXISTS `companies` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `current_stage` VARCHAR(50) NULL COMMENT 'Cached calculated stage',
    `stage_updated_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_stage` (`current_stage`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events table (append-only)
CREATE TABLE IF NOT EXISTS `events` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `event_type` VARCHAR(50) NOT NULL,
    `event_data` JSON NULL COMMENT 'Additional event metadata',
    `created_by` INT UNSIGNED NULL COMMENT 'User ID who created the event',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_company_created` (`company_id`, `created_at` DESC),
    INDEX `idx_company_type` (`company_id`, `event_type`),
    INDEX `idx_type_created` (`event_type`, `created_at`),
    CONSTRAINT `fk_events_company` 
        FOREIGN KEY (`company_id`) 
        REFERENCES `companies` (`id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial data for testing
INSERT INTO `companies` (`name`) VALUES 
    ('Acme Corporation'),
    ('TechStart Inc'),
    ('Global Solutions Ltd');
