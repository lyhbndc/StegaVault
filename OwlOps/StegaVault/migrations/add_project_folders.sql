-- Migration: Add project_folders table and folder_id to files
-- Run this in phpMyAdmin or via MySQL CLI

CREATE TABLE IF NOT EXISTS `project_folders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `pf_project_fk` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pf_user_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add folder_id column to files table (nullable)
ALTER TABLE `files`
  ADD COLUMN IF NOT EXISTS `folder_id` int(11) DEFAULT NULL,
  ADD CONSTRAINT `files_folder_fk` FOREIGN KEY (`folder_id`) REFERENCES `project_folders` (`id`) ON DELETE SET NULL;
