-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 01, 2026 at 07:07 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `stegavault`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 3, 'file_downloaded', 'Downloaded file: news card.png by Test', NULL, '2026-02-28 09:29:36'),
(2, 3, 'file_downloaded', 'Downloaded file: news card.png by Test', NULL, '2026-02-28 09:31:28'),
(3, 3, 'file_downloaded', 'Downloaded file: picture.png by Test', NULL, '2026-02-28 09:31:49'),
(4, 20, 'file_downloaded', 'Downloaded file: picture.png by Kuznets Zachary C. Calleja', NULL, '2026-02-28 09:39:52'),
(5, 3, 'file_deleted', 'Deleted file: picture.png by Test', NULL, '2026-02-28 09:43:51'),
(6, 20, 'file_downloaded', 'Downloaded file: picture.png by Kuznets Zachary C. Calleja', NULL, '2026-02-28 09:44:05'),
(7, 3, 'file_downloaded', 'Downloaded file: picture.png by Test', NULL, '2026-02-28 09:44:24'),
(8, 20, 'file_downloaded', 'Downloaded file: picture.png by Kuznets Zachary C. Calleja', NULL, '2026-03-01 05:57:21');

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `watermarked` tinyint(1) DEFAULT 0,
  `watermark_id` varchar(100) DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `download_count` int(11) DEFAULT 0,
  `folder_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `files`
--

INSERT INTO `files` (`id`, `user_id`, `project_id`, `filename`, `original_name`, `file_path`, `file_size`, `mime_type`, `watermarked`, `watermark_id`, `upload_date`, `download_count`, `folder_id`) VALUES
(2, 3, NULL, 'enc_69a2b5ee7fcd08.81408438.png', 'news card.png', 'uploads/enc_69a2b5ee7fcd08.81408438.png', 1530894, 'image/png', 1, NULL, '2026-02-28 09:31:26', 1, NULL),
(3, 3, NULL, 'enc_69a2b6031eef08.40571011.png', 'picture.png', 'uploads/enc_69a2b6031eef08.40571011.png', 153992, 'image/png', 0, NULL, '2026-02-28 09:31:47', 1, NULL),
(5, 3, 33, 'enc_69a2b8e02b0f99.56852081.png', 'picture.png', 'uploads/enc_69a2b8e02b0f99.56852081.png', 153992, 'image/png', 1, NULL, '2026-02-28 09:44:00', 3, NULL),
(6, 3, 33, 'enc_69a3d616b5f906.58942326.png', 'news card.png', 'uploads/enc_69a3d616b5f906.58942326.png', 1530894, 'image/png', 0, NULL, '2026-03-01 06:00:54', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `mfa_recovery_codes`
--

CREATE TABLE `mfa_recovery_codes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mfa_recovery_codes`
--

INSERT INTO `mfa_recovery_codes` (`id`, `user_id`, `code`, `used`, `used_at`, `created_at`) VALUES
(1, 20, 'D5905DDA-4B13FE49', 0, NULL, '2026-03-01 05:52:46'),
(2, 20, '814DCAAA-C9BE860B', 0, NULL, '2026-03-01 05:52:46'),
(3, 20, 'CE355641-40FC23B8', 0, NULL, '2026-03-01 05:52:46'),
(4, 20, 'D900D23A-20BF12EA', 0, NULL, '2026-03-01 05:52:46'),
(5, 20, 'B85615C5-B5AF5C47', 0, NULL, '2026-03-01 05:52:46'),
(6, 20, '2DDFF80C-86031833', 0, NULL, '2026-03-01 05:52:46'),
(7, 20, '76E29DDC-47B57CEF', 0, NULL, '2026-03-01 05:52:46'),
(8, 20, '41773494-D782A194', 0, NULL, '2026-03-01 05:52:46'),
(9, 20, '0616C10D-99942CCE', 0, NULL, '2026-03-01 05:52:46'),
(10, 20, '21F99616-5F0B54E1', 0, NULL, '2026-03-01 05:52:46');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#6366f1',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','archived','completed') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `name`, `description`, `color`, `created_by`, `created_at`, `updated_at`, `status`) VALUES
(16, 'Q4 Marketing Campaign', 'asdasd', '#8b5cf6', 3, '2025-12-22 03:37:16', '2025-12-22 03:37:16', 'active'),
(30, 'PGMN Files', 'all confidentials', '#ec4899', 3, '2026-02-22 16:16:28', '2026-02-25 15:57:30', 'active'),
(31, 'February', 'Interns', '#ec4899', 14, '2026-02-25 21:47:40', '2026-02-25 21:47:40', 'active'),
(33, 'Test', 'For Testing', '#6366f1', 3, '2026-02-26 10:39:25', '2026-02-26 10:39:25', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `project_folders`
--

CREATE TABLE `project_folders` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_folders`
--

INSERT INTO `project_folders` (`id`, `project_id`, `name`, `created_by`, `created_at`, `parent_id`) VALUES
(2, 16, 'Test Files', 3, '2026-02-22 14:53:55', NULL),
(4, 16, 'Sub Docs', 4, '2026-02-22 15:23:21', 2),
(9, 16, 'New', 4, '2026-02-22 16:42:47', 2),
(10, 30, 'Contracts Employees', 14, '2026-02-24 11:44:06', NULL),
(11, 30, 'asa', 4, '2026-02-24 11:53:45', 10),
(12, 30, 'Evidence', 14, '2026-02-25 15:58:13', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `project_members`
--

CREATE TABLE `project_members` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(20) DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_members`
--

INSERT INTO `project_members` (`id`, `project_id`, `user_id`, `role`, `joined_at`) VALUES
(8, 16, 3, 'owner', '2025-12-22 03:37:16'),
(9, 16, 4, 'member', '2025-12-22 03:37:16'),
(16, 30, 3, 'owner', '2026-02-22 16:16:28'),
(17, 30, 4, 'member', '2026-02-22 16:16:28'),
(18, 30, 13, 'member', '2026-02-25 11:54:30'),
(19, 31, 14, 'owner', '2026-02-25 21:47:40'),
(20, 31, 19, 'member', '2026-02-25 21:47:40'),
(24, 33, 3, 'owner', '2026-02-26 10:39:25'),
(25, 33, 20, 'member', '2026-02-26 10:39:25'),
(26, 33, 19, 'member', '2026-02-26 10:39:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `mfa_secret` varchar(255) DEFAULT NULL,
  `is_mfa_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(100) NOT NULL,
  `role` varchar(20) DEFAULT 'admin',
  `status` enum('active','pending_activation','disabled','expired') DEFAULT 'pending_activation',
  `expiration_date` datetime DEFAULT NULL,
  `activation_token` varchar(64) DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verification_token` varchar(64) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `mfa_secret`, `is_mfa_enabled`, `name`, `role`, `status`, `expiration_date`, `activation_token`, `reset_token`, `reset_expires`, `created_at`, `verification_token`, `token_expiry`, `is_verified`) VALUES
(3, 'admin', 'admin@test.com', '$2y$10$4vig.N45yMqL4MeQ.01iue1/OmlKncGP8iDRf40BpI6iXSHbUj/KG', NULL, 0, 'Test', 'admin', 'active', NULL, NULL, 'efc8fee3ae92d52e9d5b9c0fb99ce7d4a7c30c210f41f6c06cd517025e368128', '2026-02-26 06:28:17', '2025-12-21 03:44:15', NULL, NULL, 1),
(4, 'lele', 'lele@gmail.com', '$2y$10$TP72KrZmP5MddCjvaMyvsOPMmX3SKDdoE7sfdDVr1p4Ge7rJf7gY.', NULL, 0, 'Lele', 'employee', 'active', NULL, NULL, '7e80b75990cdbfcd498c77739b5617456fb245727bed75977f4387131c188c66', '2026-02-26 07:04:21', '2025-12-21 08:28:02', NULL, NULL, 1),
(11, NULL, 'testadmin@stegavault.com', '$2y$10$lmiGfX2rkEYzYO26uG763..wEQjHy1RlsEu.IrS7myW1CUdeeUgt.', NULL, 0, 'Test Admin', 'admin', 'active', NULL, NULL, NULL, NULL, '2026-02-22 16:22:24', NULL, NULL, 0),
(12, NULL, 'admin@stegavault.com', '$2y$10$IDcfmwQQzwYMpTVbudgEEukQMBM7psxJGpUNyN6VjOU.BDu4g/OAm', NULL, 0, 'Admin User', 'admin', 'active', NULL, NULL, NULL, NULL, '2026-02-22 16:48:14', NULL, NULL, 0),
(13, 'aaliyah', 'maeracreation@gmail.com', '$2y$10$iBoy1r0TaX7WQi0jnuk8dufHrWsp67T/X45Uilzn.BnjBnEiw71gu', NULL, 0, 'Aaliyah Bondoc', 'employee', 'active', '2027-03-01 00:00:00', '5fb87e797491af536a7be6a6a56589379c58bbbbec0356d5dfca7a66507ae59b', NULL, NULL, '2026-02-22 17:16:29', NULL, NULL, 0),
(14, NULL, 'aaliyah@pgmn.com', '$2y$10$LgVe9/xiR3lqxRu4ENOhCePdmSOUV0G2nLjbyNyjFuwHaMf9Cv5da', NULL, 0, 'Aaliyah Bondoc', 'admin', 'active', NULL, NULL, NULL, NULL, '2026-02-24 11:43:37', NULL, NULL, 0),
(15, NULL, 'testadmin@pgmn.inc', '$2y$10$oscOI0ozWv4HeUNcvBMOBeKt011kNnlmXONhBQFTKI88CTvxyR53u', NULL, 0, 'Test Admin', 'admin', 'active', NULL, NULL, NULL, NULL, '2026-02-25 19:41:10', NULL, NULL, 0),
(16, 'employee', 'employee@pgmn.inc', '$2y$10$XxppPCzq9nF/hfFmqQKJpO3sPHk1tYijCMbw7nttcKyFISqgcDyRC', NULL, 0, 'Employee User', 'employee', 'active', NULL, 'b86682231c0950b643d502cbf32f66839fed2be3ab2135e4656c9567e0919daa', NULL, NULL, '2026-02-25 19:43:15', NULL, NULL, 0),
(17, NULL, 'actionadmin@pgmn.inc', '$2y$10$jPOxhpptA4XO68q6LGTw1O7l2b3h/tMIhxYOPekUBFDIFXZIZ4YtS', NULL, 0, 'Action Admin', 'admin', 'active', NULL, NULL, NULL, NULL, '2026-02-25 19:51:12', NULL, NULL, 0),
(18, NULL, 'admin@pgmn.inc', '$2y$10$KIQh/MAtV1qxS07JgC.v2eh/ExbZwTzRDTvscRjZFUco35rp4tTp.', NULL, 0, 'Admin User', 'admin', 'pending_activation', NULL, NULL, NULL, NULL, '2026-02-25 20:47:46', NULL, NULL, 0),
(19, 'maroon5', 'maroon@collab.com', '$2y$10$ZLByemuUdbo2eJhK2ZStauOZMLF1.p8HSTi6N75IVHa9oZltMRWD.', NULL, 0, 'Maroon 5', 'collaborator', 'active', '2026-02-27 00:00:00', '1985770d81293d50c692ecdfb7fc75ab968ff3b14262c6913e132406fd127291', NULL, NULL, '2026-02-25 21:44:52', NULL, NULL, 0),
(20, 'coycoy', 'kuznets.calleja@gmail.com', '$2y$10$uSSKPcurOB1Qtd7yAOBAxeLO97Fc9hkOQMGjIJxsfWWPKffaUWsP6', 'QDY73VQNSRZST6DU', 1, 'Kuznets Zachary C. Calleja', 'employee', 'active', NULL, NULL, NULL, NULL, '2026-02-26 09:54:57', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_verification_logs`
--

CREATE TABLE `user_verification_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `verification_token` varchar(64) NOT NULL,
  `verification_status` enum('sent','verified','expired') DEFAULT 'sent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `watermark_crypto_log`
--

CREATE TABLE `watermark_crypto_log` (
  `id` int(11) NOT NULL,
  `watermark_id` varchar(100) NOT NULL,
  `file_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `signature` varchar(255) NOT NULL,
  `key_id` varchar(64) NOT NULL,
  `nonce` varchar(64) NOT NULL,
  `timestamp` bigint(20) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `verification_count` int(11) DEFAULT 0,
  `last_verified` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `watermark_crypto_log`
--

INSERT INTO `watermark_crypto_log` (`id`, `watermark_id`, `file_id`, `user_id`, `signature`, `key_id`, `nonce`, `timestamp`, `ip_address`, `verified`, `verification_count`, `last_verified`, `created_at`) VALUES
(1, 'd46d84fb8474f2716f1d0252d0a06e45', 2, 3, 'd4800433193ef6ebd3225b4959c3032c8c67ee925cb877bacad0e0b23929fde3', 'e57d5a8fd2561f978206f2acb96703c487f7082dfdd395451b5b6940a7a28a1c', '5bff135ca8bc0940f9bbe25309fedead', 1772271088, '::1', 0, 0, NULL, '2026-02-28 09:31:28'),
(2, '16c48d53f1b308c3e26137db234e35a9', 5, 20, '7815748e99d12ee743fbedb2a682f20df68bc25f5a5edb89f18017d17fcf640a', 'a52e1aa34f52a635b8429bf9aa03f6684a43f71aa17d768c335aeda8c7335f5f', 'e6ebc9cfaff97ac0b5dbdb3969f0d6e7', 1772271845, '::1', 1, 2, '2026-02-28 09:48:45', '2026-02-28 09:44:05'),
(3, '2e52880b0593d2dff1e31ae3f1eeffde', 5, 3, 'e3a66a7c0cc1c322a3ed3bc9d89a8f7e1faafe77b8569964ba6a41ac7b836c3f', 'e57d5a8fd2561f978206f2acb96703c487f7082dfdd395451b5b6940a7a28a1c', '1fc5771b373b7036068401beb123252c', 1772271864, '::1', 0, 0, NULL, '2026-02-28 09:44:24'),
(4, '7ae6b536241ebd4912031e9544042ec3', 5, 20, '31cb67c619d0978e4dcc120615638a21d65f3e5cf3f014f6669d07f95bfbc708', 'a52e1aa34f52a635b8429bf9aa03f6684a43f71aa17d768c335aeda8c7335f5f', '6424e506421d3fa038fb802dd6186e9a', 1772344641, '::1', 0, 0, NULL, '2026-03-01 05:57:21');

-- --------------------------------------------------------

--
-- Table structure for table `watermark_mappings`
--

CREATE TABLE `watermark_mappings` (
  `id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `watermark_id` varchar(100) NOT NULL,
  `watermarked_path` varchar(500) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `download_count` int(11) DEFAULT 0,
  `last_download` timestamp NULL DEFAULT NULL,
  `crypto_enabled` tinyint(1) DEFAULT 0,
  `signature` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `watermark_mappings`
--

INSERT INTO `watermark_mappings` (`id`, `file_id`, `user_id`, `watermark_id`, `watermarked_path`, `generated_at`, `download_count`, `last_download`, `crypto_enabled`, `signature`) VALUES
(1, 2, 3, 'd46d84fb8474f2716f1d0252d0a06e45', 'uploads/watermarked/wm_3_2_1772271088.png', '2026-02-28 09:31:28', 1, '2026-02-28 09:31:28', 1, 'd4800433193ef6ebd3225b4959c3032c8c67ee925cb877bacad0e0b23929fde3'),
(2, 5, 20, '16c48d53f1b308c3e26137db234e35a9', 'uploads/watermarked/wm_20_5_1772271845.png', '2026-02-28 09:44:05', 2, '2026-03-01 05:57:21', 1, '7815748e99d12ee743fbedb2a682f20df68bc25f5a5edb89f18017d17fcf640a'),
(3, 5, 3, '2e52880b0593d2dff1e31ae3f1eeffde', 'uploads/watermarked/wm_3_5_1772271864.png', '2026-02-28 09:44:24', 1, '2026-02-28 09:44:24', 1, 'e3a66a7c0cc1c322a3ed3bc9d89a8f7e1faafe77b8569964ba6a41ac7b836c3f');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_files_project` (`project_id`),
  ADD KEY `files_folder_fk` (`folder_id`);

--
-- Indexes for table `mfa_recovery_codes`
--
ALTER TABLE `mfa_recovery_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_code` (`code`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `project_folders`
--
ALTER TABLE `project_folders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `pf_parent_fk` (`parent_id`);

--
-- Indexes for table `project_members`
--
ALTER TABLE `project_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_verification_token` (`verification_token`),
  ADD KEY `idx_token_expiry` (`token_expiry`),
  ADD KEY `idx_is_verified` (`is_verified`);

--
-- Indexes for table `user_verification_logs`
--
ALTER TABLE `user_verification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_token` (`verification_token`);

--
-- Indexes for table `watermark_crypto_log`
--
ALTER TABLE `watermark_crypto_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_signature` (`signature`),
  ADD KEY `idx_watermark_id` (`watermark_id`),
  ADD KEY `idx_file_user` (`file_id`,`user_id`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- Indexes for table `watermark_mappings`
--
ALTER TABLE `watermark_mappings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `watermark_id` (`watermark_id`),
  ADD KEY `file_id` (`file_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_signature` (`signature`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `mfa_recovery_codes`
--
ALTER TABLE `mfa_recovery_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `project_folders`
--
ALTER TABLE `project_folders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `project_members`
--
ALTER TABLE `project_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `user_verification_logs`
--
ALTER TABLE `user_verification_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `watermark_crypto_log`
--
ALTER TABLE `watermark_crypto_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `watermark_mappings`
--
ALTER TABLE `watermark_mappings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `files_folder_fk` FOREIGN KEY (`folder_id`) REFERENCES `project_folders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `files_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `mfa_recovery_codes`
--
ALTER TABLE `mfa_recovery_codes`
  ADD CONSTRAINT `mfa_recovery_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_folders`
--
ALTER TABLE `project_folders`
  ADD CONSTRAINT `pf_parent_fk` FOREIGN KEY (`parent_id`) REFERENCES `project_folders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pf_project_fk` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pf_user_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_verification_logs`
--
ALTER TABLE `user_verification_logs`
  ADD CONSTRAINT `user_verification_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `watermark_crypto_log`
--
ALTER TABLE `watermark_crypto_log`
  ADD CONSTRAINT `watermark_crypto_log_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `watermark_crypto_log_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `watermark_mappings`
--
ALTER TABLE `watermark_mappings`
  ADD CONSTRAINT `watermark_mappings_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `watermark_mappings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
