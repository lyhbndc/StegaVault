-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 05, 2026 at 04:28 PM
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
-- Table structure for table `activity_log_admin`
--

CREATE TABLE `activity_log_admin` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log_admin`
--

INSERT INTO `activity_log_admin` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 3, 'file_downloaded', 'Downloaded file: news card.png by Test', NULL, '2026-02-28 09:29:36'),
(2, 3, 'file_downloaded', 'Downloaded file: news card.png by Test', NULL, '2026-02-28 09:31:28'),
(3, 3, 'file_downloaded', 'Downloaded file: picture.png by Test', NULL, '2026-02-28 09:31:49'),
(4, 3, 'file_deleted', 'Deleted file: picture.png by Test', NULL, '2026-02-28 09:43:51'),
(5, 3, 'file_downloaded', 'Downloaded file: picture.png by Test', NULL, '2026-02-28 09:44:24'),
(6, 3, 'file_downloaded', 'Downloaded file: OwlOps.png by Test', NULL, '2026-03-02 07:39:39'),
(7, 3, 'file_downloaded', 'Downloaded file: picture.png by Test', NULL, '2026-03-02 07:50:45'),
(8, 3, 'login_success', 'Successful login', '::1', '2026-03-02 08:21:45'),
(9, 3, 'file_downloaded', 'Downloaded file: OwlOps (2).png by Test', NULL, '2026-03-02 08:33:09'),
(10, 3, 'file_downloaded', 'Downloaded file: OwlOps (1).png by Test', NULL, '2026-03-02 08:35:18'),
(11, 3, 'file_downloaded', 'Downloaded file: wow.png by Test', NULL, '2026-03-02 08:40:45'),
(12, 3, 'file_downloaded', 'Downloaded file: 1763747336_logo_bb52f953-4d39-40a4-a3fb-78d1c4c9f74c.png by Test', NULL, '2026-03-02 08:46:43'),
(13, 3, 'login_success', 'Successful login', '::1', '2026-03-02 15:01:16'),
(14, 3, 'user_created', 'Created new user: Berlin (adrienneberlindelacruz9@gmail.com) with role: employee', NULL, '2026-03-02 15:33:38'),
(15, 3, 'user_created', 'Created new user: asdfasdf (angcuteko213@gmail.com) with role: employee', NULL, '2026-03-02 15:34:39'),
(16, 3, 'user_deleted', 'Deleted user: Admin User (admin@pgmn.inc)', NULL, '2026-03-02 15:36:39'),
(17, 3, 'user_deleted', 'Deleted user: Action Admin (actionadmin@pgmn.inc)', NULL, '2026-03-02 15:36:50'),
(18, 3, 'analysis_report_exported', 'Forensic analysis report export success: SV-FORENSIC-518E1C78.pdf | Evidence: wow_watermarked.png | Integrity: tampered', '::1', '2026-03-02 15:54:39'),
(19, 3, 'analysis_report_exported', 'Forensic analysis report export success: SV-FORENSIC-518E1C78.pdf | Evidence: wow_watermarked.png | Integrity: tampered', '::1', '2026-03-02 15:56:40'),
(20, 3, 'analysis_report_exported', 'Forensic analysis report export success: SV-FORENSIC-2B18DE5A.pdf | Evidence: ASDASD.png | Integrity: valid', '::1', '2026-03-02 15:56:55'),
(21, 3, 'login_success', 'Successful login', '::1', '2026-03-03 07:14:08'),
(22, 3, 'login_success', 'Successful login', '::1', '2026-03-04 07:08:29'),
(23, 3, 'file_downloaded', 'Downloaded file: StegaVault_System_Report (4).pdf by Test', NULL, '2026-03-04 07:14:35'),
(24, 3, 'file_downloaded', 'Downloaded file: StegaVault_System_Report (4).pdf by Test', NULL, '2026-03-04 07:24:05'),
(25, 3, 'file_downloaded', 'Downloaded file: StegaVault_System_Report (4).pdf by Test', NULL, '2026-03-04 07:25:37'),
(26, 3, 'login_success', 'Successful login', '::1', '2026-03-04 08:14:17'),
(27, 3, 'file_downloaded', 'Downloaded file: wow.png by Test', NULL, '2026-03-04 08:14:23'),
(28, 3, 'login_success', 'Successful login', '::1', '2026-03-05 05:37:08'),
(29, 3, 'login_success', 'Successful login', '::1', '2026-03-05 06:01:01'),
(30, 3, 'login_failed', 'Failed login attempt', '::1', '2026-03-05 07:11:51'),
(31, 3, 'login_failed', 'Failed login attempt', '::1', '2026-03-05 07:11:55'),
(32, 3, 'login_failed', 'Failed login attempt', '::1', '2026-03-05 07:11:56'),
(33, 3, 'account_locked', 'Account locked after 3 failed login attempts', '::1', '2026-03-05 07:11:56'),
(34, 3, 'login_success', 'Successful login', '::1', '2026-03-05 07:14:46'),
(64, 3, 'login_success', 'Successful login', '::1', '2026-03-05 15:14:26'),
(65, 3, 'user_deleted', 'Deleted user: Berlin Dela Cruz (adrienneberlindelacruz9@gmail.com)', '::1', '2026-03-05 15:21:03'),
(66, 3, 'user_created', 'Created new user: linlin (adrienneberlindelacruz9@gmail.com) with role: collaborator', '::1', '2026-03-05 15:21:32');

-- --------------------------------------------------------

--
-- Table structure for table `activity_log_collaborator`
--

CREATE TABLE `activity_log_collaborator` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log_collaborator`
--

INSERT INTO `activity_log_collaborator` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 23, 'account_activated', 'Account activated for user: Berlinda (adrienneberlindelacruz9@gmail.com)', '::1', '2026-03-05 15:21:45');

-- --------------------------------------------------------

--
-- Table structure for table `activity_log_employee`
--

CREATE TABLE `activity_log_employee` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log_employee`
--

INSERT INTO `activity_log_employee` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 20, 'file_downloaded', 'Downloaded file: picture.png by Kuznets Zachary C. Calleja', NULL, '2026-02-28 09:39:52'),
(2, 20, 'file_downloaded', 'Downloaded file: picture.png by Kuznets Zachary C. Calleja', NULL, '2026-02-28 09:44:05'),
(3, 20, 'file_downloaded', 'Downloaded file: picture.png by Kuznets Zachary C. Calleja', NULL, '2026-03-01 05:57:21'),
(4, 20, 'login_success', 'Successful login', '::1', '2026-03-04 07:12:07'),
(5, 20, 'file_downloaded', 'Downloaded file: picture.png by Kuznets Zachary C. Calleja', NULL, '2026-03-04 07:27:38'),
(6, 20, 'file_downloaded', 'Downloaded file: OwlOps.png by Kuznets Zachary C. Calleja', NULL, '2026-03-04 07:29:19'),
(7, 20, 'login_success', 'Successful login', '::1', '2026-03-04 08:12:56'),
(8, 20, 'login_success', 'Successful login', '::1', '2026-03-05 06:01:58'),
(9, 20, 'login_success', 'Successful login', '::1', '2026-03-05 07:20:03'),
(10, 20, 'login_success', 'Successful login', '::1', '2026-03-05 07:20:18'),
(11, 20, 'login_success', 'Successful login', '::1', '2026-03-05 07:22:04'),
(16, 20, 'login_success', 'Successful login', '::1', '2026-03-05 15:15:23');

-- --------------------------------------------------------

--
-- Table structure for table `activity_log_legacy_archive`
--

CREATE TABLE `activity_log_legacy_archive` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log_legacy_archive`
--

INSERT INTO `activity_log_legacy_archive` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 3, 'file_downloaded', 'Downloaded file: news card.png by Test', NULL, '2026-02-28 09:29:36'),
(2, 3, 'file_downloaded', 'Downloaded file: news card.png by Test', NULL, '2026-02-28 09:31:28'),
(3, 3, 'file_downloaded', 'Downloaded file: picture.png by Test', NULL, '2026-02-28 09:31:49'),
(4, 20, 'file_downloaded', 'Downloaded file: picture.png by Kuznets Zachary C. Calleja', NULL, '2026-02-28 09:39:52'),
(5, 3, 'file_deleted', 'Deleted file: picture.png by Test', NULL, '2026-02-28 09:43:51'),
(6, 20, 'file_downloaded', 'Downloaded file: picture.png by Kuznets Zachary C. Calleja', NULL, '2026-02-28 09:44:05'),
(7, 3, 'file_downloaded', 'Downloaded file: picture.png by Test', NULL, '2026-02-28 09:44:24'),
(8, 20, 'file_downloaded', 'Downloaded file: picture.png by Kuznets Zachary C. Calleja', NULL, '2026-03-01 05:57:21'),
(9, 3, 'file_downloaded', 'Downloaded file: OwlOps.png by Test', NULL, '2026-03-02 07:39:39'),
(10, 3, 'file_downloaded', 'Downloaded file: picture.png by Test', NULL, '2026-03-02 07:50:45'),
(11, 3, 'login_success', 'Successful login', '::1', '2026-03-02 08:21:45'),
(12, 3, 'file_downloaded', 'Downloaded file: OwlOps (2).png by Test', NULL, '2026-03-02 08:33:09'),
(13, 3, 'file_downloaded', 'Downloaded file: OwlOps (1).png by Test', NULL, '2026-03-02 08:35:18'),
(14, 3, 'file_downloaded', 'Downloaded file: wow.png by Test', NULL, '2026-03-02 08:40:45'),
(15, 3, 'file_downloaded', 'Downloaded file: 1763747336_logo_bb52f953-4d39-40a4-a3fb-78d1c4c9f74c.png by Test', NULL, '2026-03-02 08:46:43'),
(16, 3, 'login_success', 'Successful login', '::1', '2026-03-02 15:01:16'),
(17, 3, 'user_created', 'Created new user: Berlin (adrienneberlindelacruz9@gmail.com) with role: employee', NULL, '2026-03-02 15:33:38'),
(18, 3, 'user_created', 'Created new user: asdfasdf (angcuteko213@gmail.com) with role: employee', NULL, '2026-03-02 15:34:39'),
(19, 3, 'user_deleted', 'Deleted user: Admin User (admin@pgmn.inc)', NULL, '2026-03-02 15:36:39'),
(20, 3, 'user_deleted', 'Deleted user: Action Admin (actionadmin@pgmn.inc)', NULL, '2026-03-02 15:36:50'),
(21, 3, 'analysis_report_exported', 'Forensic analysis report export success: SV-FORENSIC-518E1C78.pdf | Evidence: wow_watermarked.png | Integrity: tampered', '::1', '2026-03-02 15:54:39'),
(22, 3, 'analysis_report_exported', 'Forensic analysis report export success: SV-FORENSIC-518E1C78.pdf | Evidence: wow_watermarked.png | Integrity: tampered', '::1', '2026-03-02 15:56:40'),
(23, 3, 'analysis_report_exported', 'Forensic analysis report export success: SV-FORENSIC-2B18DE5A.pdf | Evidence: ASDASD.png | Integrity: valid', '::1', '2026-03-02 15:56:55'),
(24, 3, 'login_success', 'Successful login', '::1', '2026-03-03 07:14:08'),
(25, 3, 'login_success', 'Successful login', '::1', '2026-03-04 07:08:29'),
(26, 20, 'login_success', 'Successful login', '::1', '2026-03-04 07:12:07'),
(27, 3, 'file_downloaded', 'Downloaded file: StegaVault_System_Report (4).pdf by Test', NULL, '2026-03-04 07:14:35'),
(28, 3, 'file_downloaded', 'Downloaded file: StegaVault_System_Report (4).pdf by Test', NULL, '2026-03-04 07:24:05'),
(29, 3, 'file_downloaded', 'Downloaded file: StegaVault_System_Report (4).pdf by Test', NULL, '2026-03-04 07:25:37'),
(30, 20, 'file_downloaded', 'Downloaded file: picture.png by Kuznets Zachary C. Calleja', NULL, '2026-03-04 07:27:38'),
(31, 20, 'file_downloaded', 'Downloaded file: OwlOps.png by Kuznets Zachary C. Calleja', NULL, '2026-03-04 07:29:19'),
(32, 20, 'login_success', 'Successful login', '::1', '2026-03-04 08:12:56'),
(33, 3, 'login_success', 'Successful login', '::1', '2026-03-04 08:14:17'),
(34, 3, 'file_downloaded', 'Downloaded file: wow.png by Test', NULL, '2026-03-04 08:14:23'),
(35, 3, 'login_success', 'Successful login', '::1', '2026-03-05 05:37:08'),
(36, 3, 'login_success', 'Successful login', '::1', '2026-03-05 06:01:01'),
(37, 20, 'login_success', 'Successful login', '::1', '2026-03-05 06:01:58'),
(38, 3, 'login_failed', 'Failed login attempt', '::1', '2026-03-05 07:11:51'),
(39, 3, 'login_failed', 'Failed login attempt', '::1', '2026-03-05 07:11:55'),
(40, 3, 'login_failed', 'Failed login attempt', '::1', '2026-03-05 07:11:56'),
(41, 3, 'account_locked', 'Account locked after 3 failed login attempts', '::1', '2026-03-05 07:11:56'),
(42, 3, 'login_success', 'Successful login', '::1', '2026-03-05 07:14:46'),
(43, 20, 'login_success', 'Successful login', '::1', '2026-03-05 07:20:03'),
(44, 20, 'login_success', 'Successful login', '::1', '2026-03-05 07:20:18'),
(45, 20, 'login_success', 'Successful login', '::1', '2026-03-05 07:22:04');

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
(5, 3, 33, 'enc_69a2b8e02b0f99.56852081.png', 'picture.png', 'uploads/enc_69a2b8e02b0f99.56852081.png', 153992, 'image/png', 1, NULL, '2026-02-28 09:44:00', 5, NULL),
(6, 3, 33, 'enc_69a3d616b5f906.58942326.png', 'news card.png', 'uploads/enc_69a3d616b5f906.58942326.png', 1530894, 'image/png', 0, NULL, '2026-03-01 06:00:54', 1, NULL),
(7, 3, NULL, 'enc_69a53eb927fd56.01704522.png', 'OwlOps.png', 'uploads/enc_69a53eb927fd56.01704522.png', 37995, 'image/png', 1, NULL, '2026-03-02 07:39:37', 1, NULL),
(8, 3, NULL, 'enc_69a54b42e2aed3.73294769.png', 'OwlOps (2).png', 'uploads/enc_69a54b42e2aed3.73294769.png', 37995, 'image/png', 1, NULL, '2026-03-02 08:33:06', 1, NULL),
(9, 3, NULL, 'enc_69a54bc4990f45.16085630.png', 'OwlOps (1).png', 'uploads/enc_69a54bc4990f45.16085630.png', 37995, 'image/png', 1, NULL, '2026-03-02 08:35:16', 1, NULL),
(10, 3, NULL, 'enc_69a54d0867a363.26670987.png', 'wow.png', 'uploads/enc_69a54d0867a363.26670987.png', 507705, 'image/png', 1, NULL, '2026-03-02 08:40:40', 1, NULL),
(11, 3, NULL, 'enc_69a54e70e0b951.97216991.png', '1763747336_logo_bb52f953-4d39-40a4-a3fb-78d1c4c9f74c.png', 'uploads/enc_69a54e70e0b951.97216991.png', 165480, 'image/png', 1, NULL, '2026-03-02 08:46:40', 1, NULL),
(12, 20, 33, 'enc_69a7dbcb6d6ad3.45124116.pdf', 'StegaVault_System_Report (4).pdf', 'uploads/enc_69a7dbcb6d6ad3.45124116.pdf', 3262381, 'application/pdf', 1, NULL, '2026-03-04 07:14:19', 3, NULL),
(13, 3, 33, 'enc_69a7df47786309.19839435.png', 'OwlOps.png', 'uploads/enc_69a7df47786309.19839435.png', 37995, 'image/png', 1, NULL, '2026-03-04 07:29:11', 1, NULL),
(14, 20, 33, 'enc_69a7e9c8c1c7f1.36881951.png', 'wow.png', 'uploads/enc_69a7e9c8c1c7f1.36881951.png', 507705, 'image/png', 1, NULL, '2026-03-04 08:14:00', 1, NULL),
(15, 3, 33, 'enc_69a91cdca04942.81191902.png', 'BEACON - 10.png', 'uploads/enc_69a91cdca04942.81191902.png', 2966878, 'image/png', 1, NULL, '2026-03-05 06:04:12', 0, NULL),
(16, 3, 33, 'enc_69a91e7fd0e219.17304588.png', 'Copy of IMG_1548.png', 'uploads/enc_69a91e7fd0e219.17304588.png', 7884165, 'image/png', 1, NULL, '2026-03-05 06:11:11', 0, NULL),
(17, 3, 33, 'enc_69a99df0167476.73735359.pdf', 'SV-FORENSIC-518E1C78.pdf', 'uploads/enc_69a99df0167476.73735359.pdf', 85635, 'application/pdf', 1, NULL, '2026-03-05 15:14:56', 0, NULL);

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
-- Table structure for table `super_admins`
--

CREATE TABLE `super_admins` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `super_admins`
--

INSERT INTO `super_admins` (`id`, `email`, `password_hash`, `name`, `created_at`, `updated_at`) VALUES
(1, 'superadmin@test.com', '$2y$10$npO5FGjPf4mgPPiGFCzzpOhrwzQGtuojyUOEYTXHZw/9tHNescdCy', 'Super Admin', '2026-02-26 11:25:58', '2026-02-26 11:55:39');

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
(19, 'maroon5', 'maroon@collab.com', '$2y$10$ZLByemuUdbo2eJhK2ZStauOZMLF1.p8HSTi6N75IVHa9oZltMRWD.', NULL, 0, 'Maroon 5', 'collaborator', 'active', '2026-02-27 00:00:00', '1985770d81293d50c692ecdfb7fc75ab968ff3b14262c6913e132406fd127291', NULL, NULL, '2026-02-25 21:44:52', NULL, NULL, 0),
(20, 'coycoy', 'kuznets.calleja@gmail.com', '$2y$10$uSSKPcurOB1Qtd7yAOBAxeLO97Fc9hkOQMGjIJxsfWWPKffaUWsP6', 'QDY73VQNSRZST6DU', 1, 'Kuznets Zachary C. Calleja', 'employee', 'active', NULL, NULL, NULL, NULL, '2026-02-26 09:54:57', NULL, NULL, 1),
(22, 'asdfasdf', 'angcuteko213@gmail.com', '$2y$10$rjtpFXDeDfNnfxvd55SJAuhAYxpJpPsqSR0aiswOVVPyI4msWRCaK', NULL, 0, 'FSADASF', 'employee', 'pending_activation', NULL, '1431952326e6ea04e46e61818a1838f5f22b6abe099440941c2b1578a1659090', NULL, NULL, '2026-03-02 15:34:35', NULL, NULL, 0),
(23, 'linlin', 'adrienneberlindelacruz9@gmail.com', '$2y$10$YDMiLzC.6u8Ym9ngwwbkFO7eYA.Q7z/k5TkxBjBglg6jc7.Hyi.cO', NULL, 0, 'Berlinda', 'collaborator', 'active', NULL, NULL, NULL, NULL, '2026-03-05 15:21:28', NULL, NULL, 1);

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
  `expected_hash` varchar(64) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `verification_count` int(11) DEFAULT 0,
  `last_verified` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `watermark_crypto_log`
--

INSERT INTO `watermark_crypto_log` (`id`, `watermark_id`, `file_id`, `user_id`, `signature`, `key_id`, `nonce`, `timestamp`, `ip_address`, `expected_hash`, `verified`, `verification_count`, `last_verified`, `created_at`) VALUES
(1, 'd46d84fb8474f2716f1d0252d0a06e45', 2, 3, 'd4800433193ef6ebd3225b4959c3032c8c67ee925cb877bacad0e0b23929fde3', 'e57d5a8fd2561f978206f2acb96703c487f7082dfdd395451b5b6940a7a28a1c', '5bff135ca8bc0940f9bbe25309fedead', 1772271088, '::1', NULL, 0, 0, NULL, '2026-02-28 09:31:28'),
(2, '16c48d53f1b308c3e26137db234e35a9', 5, 20, '7815748e99d12ee743fbedb2a682f20df68bc25f5a5edb89f18017d17fcf640a', 'a52e1aa34f52a635b8429bf9aa03f6684a43f71aa17d768c335aeda8c7335f5f', 'e6ebc9cfaff97ac0b5dbdb3969f0d6e7', 1772271845, '::1', NULL, 1, 2, '2026-02-28 09:48:45', '2026-02-28 09:44:05'),
(3, '2e52880b0593d2dff1e31ae3f1eeffde', 5, 3, 'e3a66a7c0cc1c322a3ed3bc9d89a8f7e1faafe77b8569964ba6a41ac7b836c3f', 'e57d5a8fd2561f978206f2acb96703c487f7082dfdd395451b5b6940a7a28a1c', '1fc5771b373b7036068401beb123252c', 1772271864, '::1', NULL, 0, 0, NULL, '2026-02-28 09:44:24'),
(4, '7ae6b536241ebd4912031e9544042ec3', 5, 20, '31cb67c619d0978e4dcc120615638a21d65f3e5cf3f014f6669d07f95bfbc708', 'a52e1aa34f52a635b8429bf9aa03f6684a43f71aa17d768c335aeda8c7335f5f', '6424e506421d3fa038fb802dd6186e9a', 1772344641, '::1', NULL, 0, 0, NULL, '2026-03-01 05:57:21'),
(5, 'fde59697ae1acacbd68e82a3b54de000', 7, 3, '03cd7f2920f18d2a10b5c41ec74f413047b2cb8c83c716e7596a1780ac0141e7', 'e57d5a8fd2561f978206f2acb96703c487f7082dfdd395451b5b6940a7a28a1c', '982bbe7e41a4b00a31c7af3955e29764', 1772437179, '::1', NULL, 1, 5, '2026-03-02 08:27:03', '2026-03-02 07:39:39'),
(6, '0da896977f9e2f1e821e41017faaa02a', 5, 3, 'e6e587bef71061a831e25718e1d01ae0047733f65902cfbd4bec6392aa8ee354', 'e57d5a8fd2561f978206f2acb96703c487f7082dfdd395451b5b6940a7a28a1c', '9307d93dd93bc2ddb162317b866d0cca', 1772437845, '::1', NULL, 1, 5, '2026-03-02 15:09:50', '2026-03-02 07:50:46'),
(7, 'a53282c5f0a0e68779b72a3d7b16fcbf', 8, 3, 'b19887bd824b9f8da71dcfda17c68df8a4c481c1c7c0cae10ca52832b00f1343', 'e57d5a8fd2561f978206f2acb96703c487f7082dfdd395451b5b6940a7a28a1c', '284c920cd9a9490e50dce3e87a0b5988', 1772440389, '::1', NULL, 1, 2, '2026-03-02 16:02:55', '2026-03-02 08:33:09'),
(8, 'cf1c036d29b2bf5928f22726a03f6f3a', 9, 3, '9a57bef107ea3051796949d32e21a3ff21bebb5032649b73e6f639c4bd52b413', 'e57d5a8fd2561f978206f2acb96703c487f7082dfdd395451b5b6940a7a28a1c', '48de0c9c41aed2833fb607cc1a84a8a2', 1772440518, '::1', NULL, 1, 17, '2026-03-04 07:28:47', '2026-03-02 08:35:18'),
(9, '77f991a7080e6e453f39b8df0205d090', 10, 3, '64c7009f15117edcd8581850f9711362da2224ce794787ecd1d431620cf2f4bd', 'e57d5a8fd2561f978206f2acb96703c487f7082dfdd395451b5b6940a7a28a1c', '9f60232f605bdd27f737fd5041a906fd', 1772440845, '::1', NULL, 1, 24, '2026-03-04 07:27:11', '2026-03-02 08:40:45'),
(10, 'da10de81f097460ca93736b7157dc98e', 11, 3, '6b0d7c643cdcb5778bf222928463c9a1e0bba242ee8683941950f68ccd7d07f4', 'e57d5a8fd2561f978206f2acb96703c487f7082dfdd395451b5b6940a7a28a1c', 'a7e1bf51fa6ad726ad869d0c7c175712', 1772441203, '::1', NULL, 1, 1, '2026-03-02 08:46:48', '2026-03-02 08:46:43'),
(11, '8d14e3452712ba1f7635e8f3ef08f96b', 12, 3, '31e000d2e0bc2cdb8cc6ed0b00aa20b05e301e8a45e9a08199168b78a931c87e', 'e57d5a8fd2561f978206f2acb96703c487f7082dfdd395451b5b6940a7a28a1c', '647a3e1621e7fda49927fe6f54e92861', 1772609045, '::1', NULL, 1, 1, '2026-03-04 07:24:14', '2026-03-04 07:24:05'),
(12, 'd76abc04714d86a86fbaefacdf0d1d56', 12, 3, '94bc07ce18fa924f89a415e67405cd5e7643aab48b1892aabedb578828efd98a', 'e57d5a8fd2561f978206f2acb96703c487f7082dfdd395451b5b6940a7a28a1c', '560a8c2d2b884e004cf99c010bc4a458', 1772609137, '::1', NULL, 1, 2, '2026-03-04 07:33:50', '2026-03-04 07:25:37'),
(13, '535d82f7808693b0ae939085239f7cc1', 5, 20, 'bf2c8125869c25a736b478e517c342cc623670a6c3c189e1a4165493244a3344', 'a52e1aa34f52a635b8429bf9aa03f6684a43f71aa17d768c335aeda8c7335f5f', 'fa1ab5831ae571cee668e3ffdb9dfade', 1772609258, '::1', NULL, 1, 1, '2026-03-04 07:27:45', '2026-03-04 07:27:38'),
(14, '1f8bb993d47ba6af97564d429621f47b', 13, 20, '9b754e714ae31a810e1f1d9fafe25a84b1a7ed3b8b5a3c4b21ded81aa7b1c4d5', 'a52e1aa34f52a635b8429bf9aa03f6684a43f71aa17d768c335aeda8c7335f5f', 'fe7c8652de19143a75a31317ac452ced', 1772609359, '::1', NULL, 1, 2, '2026-03-04 07:33:59', '2026-03-04 07:29:19'),
(15, '96ff48f7409561c501f32693186e43d6', 14, 3, 'f376ca79f8bb34b706785f8cfd19c5cec4db47b4c567a6d5022da3b6004fb638', 'e57d5a8fd2561f978206f2acb96703c487f7082dfdd395451b5b6940a7a28a1c', 'ef12e9fa0a3ddadc7202cdcd66de6eed', 1772612062, '::1', NULL, 1, 1, '2026-03-04 08:15:06', '2026-03-04 08:14:23');

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
(2, 5, 20, '16c48d53f1b308c3e26137db234e35a9', 'uploads/watermarked/wm_20_5_1772271845.png', '2026-02-28 09:44:05', 3, '2026-03-04 07:27:38', 1, '7815748e99d12ee743fbedb2a682f20df68bc25f5a5edb89f18017d17fcf640a'),
(3, 5, 3, '2e52880b0593d2dff1e31ae3f1eeffde', 'uploads/watermarked/wm_3_5_1772271864.png', '2026-02-28 09:44:24', 2, '2026-03-02 07:50:46', 1, 'e3a66a7c0cc1c322a3ed3bc9d89a8f7e1faafe77b8569964ba6a41ac7b836c3f'),
(4, 7, 3, 'fde59697ae1acacbd68e82a3b54de000', 'uploads/watermarked/wm_3_7_1772437179.png', '2026-03-02 07:39:39', 1, '2026-03-02 07:39:39', 1, '03cd7f2920f18d2a10b5c41ec74f413047b2cb8c83c716e7596a1780ac0141e7'),
(5, 8, 3, 'a53282c5f0a0e68779b72a3d7b16fcbf', 'uploads/watermarked/wm_3_8_1772440389.png', '2026-03-02 08:33:09', 1, '2026-03-02 08:33:09', 1, 'b19887bd824b9f8da71dcfda17c68df8a4c481c1c7c0cae10ca52832b00f1343'),
(6, 9, 3, 'cf1c036d29b2bf5928f22726a03f6f3a', 'uploads/watermarked/wm_3_9_1772440518.png', '2026-03-02 08:35:18', 1, '2026-03-02 08:35:18', 1, '9a57bef107ea3051796949d32e21a3ff21bebb5032649b73e6f639c4bd52b413'),
(7, 10, 3, '77f991a7080e6e453f39b8df0205d090', 'uploads/watermarked/wm_3_10_1772440845.png', '2026-03-02 08:40:45', 1, '2026-03-02 08:40:45', 1, '64c7009f15117edcd8581850f9711362da2224ce794787ecd1d431620cf2f4bd'),
(8, 11, 3, 'da10de81f097460ca93736b7157dc98e', 'uploads/watermarked/wm_3_11_1772441203.png', '2026-03-02 08:46:43', 1, '2026-03-02 08:46:43', 1, '6b0d7c643cdcb5778bf222928463c9a1e0bba242ee8683941950f68ccd7d07f4'),
(9, 12, 3, '8d14e3452712ba1f7635e8f3ef08f96b', 'uploads/watermarked/wm_doc_3_12_1772609045.pdf', '2026-03-04 07:24:05', 2, '2026-03-04 07:25:37', 1, '31e000d2e0bc2cdb8cc6ed0b00aa20b05e301e8a45e9a08199168b78a931c87e'),
(10, 13, 20, '1f8bb993d47ba6af97564d429621f47b', 'uploads/watermarked/wm_20_13_1772609359.png', '2026-03-04 07:29:19', 1, '2026-03-04 07:29:19', 1, '9b754e714ae31a810e1f1d9fafe25a84b1a7ed3b8b5a3c4b21ded81aa7b1c4d5'),
(11, 14, 3, '96ff48f7409561c501f32693186e43d6', 'uploads/watermarked/wm_3_14_1772612062.png', '2026-03-04 08:14:23', 1, '2026-03-04 08:14:23', 1, 'f376ca79f8bb34b706785f8cfd19c5cec4db47b4c567a6d5022da3b6004fb638');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log_admin`
--
ALTER TABLE `activity_log_admin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `activity_log_collaborator`
--
ALTER TABLE `activity_log_collaborator`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `activity_log_employee`
--
ALTER TABLE `activity_log_employee`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `activity_log_legacy_archive`
--
ALTER TABLE `activity_log_legacy_archive`
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
-- Indexes for table `super_admins`
--
ALTER TABLE `super_admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

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
-- AUTO_INCREMENT for table `activity_log_admin`
--
ALTER TABLE `activity_log_admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `activity_log_collaborator`
--
ALTER TABLE `activity_log_collaborator`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `activity_log_employee`
--
ALTER TABLE `activity_log_employee`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `activity_log_legacy_archive`
--
ALTER TABLE `activity_log_legacy_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

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
-- AUTO_INCREMENT for table `super_admins`
--
ALTER TABLE `super_admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `user_verification_logs`
--
ALTER TABLE `user_verification_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `watermark_crypto_log`
--
ALTER TABLE `watermark_crypto_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `watermark_mappings`
--
ALTER TABLE `watermark_mappings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log_admin`
--
ALTER TABLE `activity_log_admin`
  ADD CONSTRAINT `fk_activity_log_admin_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_log_collaborator`
--
ALTER TABLE `activity_log_collaborator`
  ADD CONSTRAINT `fk_activity_log_collaborator_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_log_employee`
--
ALTER TABLE `activity_log_employee`
  ADD CONSTRAINT `fk_activity_log_employee_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
