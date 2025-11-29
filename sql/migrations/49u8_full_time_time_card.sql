-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- ホスト: mysql8008.in.shared-server.net:13654
-- 生成日時: 2025 年 11 月 29 日 09:11
-- サーバのバージョン： 8.0.29
-- PHP のバージョン: 7.1.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- データベース: `49u8_full_time_time_card`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `app_settings`
--

CREATE TABLE `app_settings` (
  `key` varchar(191) NOT NULL,
  `value` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- テーブルのデータのダンプ `app_settings`
--

INSERT INTO `app_settings` (`key`, `value`) VALUES
('notify', '{\"enabled\":true,\"recipients\":\"ma0344@net-one.info\"}'),
('smtp', '{\"host\":\"smtp.net-one.info\",\"port\":587,\"secure\":\"tls\",\"username\":\"netone-admin@net-one.info\",\"password\":\"zRN5gMuFgws94ip\",\"from_email\":\"netone-admin@net-one.info\",\"from_name\":\"ねっとわん勤怠システム\"}');

-- --------------------------------------------------------

--
-- テーブルの構造 `attendance_period_locks`
--

CREATE TABLE `attendance_period_locks` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('locked','reopened') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'locked',
  `locked_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `locked_by` int NOT NULL,
  `reopened_at` datetime DEFAULT NULL,
  `reopened_by` int DEFAULT NULL,
  `note` text COLLATE utf8mb4_general_ci,
  `version` int NOT NULL DEFAULT '1'
) ;

--
-- テーブルのデータのダンプ `attendance_period_locks`
--

INSERT INTO `attendance_period_locks` (`id`, `user_id`, `start_date`, `end_date`, `status`, `locked_at`, `locked_by`, `reopened_at`, `reopened_by`, `note`, `version`) VALUES
(1, NULL, '2025-08-16', '2025-09-15', 'locked', '2025-11-12 12:19:21', 2, NULL, NULL, NULL, 1),
(2, NULL, '2025-07-16', '2025-08-15', 'locked', '2025-11-12 12:40:55', 2, NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- テーブルの構造 `attendance_statuses`
--

CREATE TABLE `attendance_statuses` (
  `id` int NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_paid` tinyint(1) NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `attendance_statuses`
--

INSERT INTO `attendance_statuses` (`id`, `code`, `name`, `description`, `is_paid`, `is_active`, `created_at`) VALUES
(1, 'WORK', '勤務', '通常勤務', 1, 1, '2025-10-15 04:42:08'),
(2, 'PAID_LEAVE', '有給休暇', '有給として処理する勤務区分', 1, 1, '2025-10-15 04:42:08'),
(3, 'UNPAID_LEAVE', '欠勤（無給）', '給与対象外の欠勤', 0, 1, '2025-10-15 04:42:08'),
(4, 'HOLIDAY', '公休日', '所定休日扱いの区分', 1, 1, '2025-10-15 04:42:08'),
(5, 'SICK_LEAVE', '病気休暇', '病気による欠勤', 0, 1, '2025-10-15 04:42:08');

-- --------------------------------------------------------

--
-- テーブルの構造 `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint NOT NULL,
  `actor_user_id` int NOT NULL,
  `target_user_id` int DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `details` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `breaks`
--

CREATE TABLE `breaks` (
  `id` int NOT NULL,
  `timecard_id` int NOT NULL,
  `break_start` datetime DEFAULT NULL,
  `break_start_manual` tinyint(1) DEFAULT '0',
  `break_end` datetime DEFAULT NULL,
  `break_end_manual` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- テーブルのデータのダンプ `breaks`
--

INSERT INTO `breaks` (`id`, `timecard_id`, `break_start`, `break_start_manual`, `break_end`, `break_end_manual`, `created_at`) VALUES
(77, 83, '2025-04-04 12:00:00', 1, '2025-04-04 13:00:00', 1, '2025-10-29 11:41:45'),
(78, 86, '2025-04-15 12:00:00', 1, '2025-04-15 13:00:00', 1, '2025-10-29 11:41:45'),
(79, 88, '2025-04-22 12:00:00', 1, '2025-04-22 13:00:00', 1, '2025-10-29 11:41:45'),
(80, 91, '2025-05-02 12:00:00', 1, '2025-05-02 13:00:00', 1, '2025-10-29 11:41:45'),
(81, 93, '2025-05-09 12:00:00', 1, '2025-05-09 13:00:00', 1, '2025-10-29 11:41:45'),
(82, 94, '2025-05-13 12:00:00', 1, '2025-05-13 13:00:00', 1, '2025-10-29 11:41:45'),
(83, 96, '2025-05-20 12:00:00', 1, '2025-05-20 13:00:00', 1, '2025-10-29 11:41:45'),
(84, 97, '2025-05-23 12:00:00', 1, '2025-05-23 13:00:00', 1, '2025-10-29 11:41:45'),
(85, 98, '2025-05-27 12:00:00', 1, '2025-05-27 13:00:00', 1, '2025-10-29 11:41:45'),
(86, 101, '2025-05-30 12:00:00', 1, '2025-05-30 13:00:00', 1, '2025-10-29 11:41:45'),
(87, 102, '2025-06-02 12:00:00', 1, '2025-06-02 13:00:00', 1, '2025-10-29 11:41:45'),
(88, 103, '2025-06-03 12:00:00', 1, '2025-06-03 13:00:00', 1, '2025-10-29 11:41:45'),
(89, 105, '2025-06-05 12:00:00', 1, '2025-06-05 13:00:00', 1, '2025-10-29 11:41:45'),
(90, 106, '2025-06-06 12:00:00', 1, '2025-06-06 13:00:00', 1, '2025-10-29 11:41:45'),
(91, 108, '2025-06-10 12:00:00', 1, '2025-06-10 13:00:00', 1, '2025-10-29 11:41:45'),
(92, 111, '2025-06-16 12:00:00', 1, '2025-06-16 13:00:00', 1, '2025-10-29 11:41:45'),
(93, 112, '2025-06-18 12:00:00', 1, '2025-06-18 13:00:00', 1, '2025-10-29 11:41:45'),
(94, 113, '2025-06-20 12:00:00', 1, '2025-06-20 13:00:00', 1, '2025-10-29 11:41:45'),
(95, 115, '2025-06-25 12:00:00', 1, '2025-06-25 13:00:00', 1, '2025-10-29 11:41:45'),
(97, 120, '2025-07-11 12:00:00', 1, '2025-07-11 13:00:00', 1, '2025-10-29 11:41:45'),
(98, 123, '2025-07-29 12:00:00', 1, '2025-07-29 13:00:00', 1, '2025-10-29 11:41:45'),
(99, 124, '2025-08-01 12:00:00', 1, '2025-08-01 13:00:00', 1, '2025-10-29 11:41:45'),
(100, 125, '2025-08-05 12:00:00', 1, '2025-08-05 13:00:00', 1, '2025-10-29 11:41:45'),
(101, 127, '2025-08-12 12:00:00', 1, '2025-08-12 13:00:00', 1, '2025-10-29 11:41:45'),
(102, 130, '2025-08-21 12:00:00', 1, '2025-08-21 13:00:00', 1, '2025-10-29 11:41:45'),
(103, 133, '2025-08-29 12:00:00', 1, '2025-08-29 13:00:00', 1, '2025-10-29 11:41:45'),
(104, 135, '2025-09-05 12:00:00', 1, '2025-09-05 13:00:00', 1, '2025-10-29 11:41:45'),
(105, 137, '2025-09-12 12:00:00', 1, '2025-09-12 13:00:00', 1, '2025-10-29 11:41:45'),
(106, 141, '2025-10-02 12:00:00', 1, '2025-10-02 13:00:00', 1, '2025-10-29 11:41:45'),
(107, 143, '2025-10-06 12:00:00', 1, '2025-10-06 13:00:00', 1, '2025-10-29 11:41:45'),
(108, 144, '2025-10-07 12:00:00', 1, '2025-10-07 13:00:00', 1, '2025-10-29 11:41:45'),
(109, 146, '2025-10-09 12:00:00', 1, '2025-10-09 13:00:00', 1, '2025-10-29 11:41:45'),
(110, 147, '2025-10-10 12:00:00', 1, '2025-10-10 13:00:00', 1, '2025-10-29 11:41:45'),
(111, 150, '2025-10-15 12:00:00', 1, '2025-10-15 13:00:00', 1, '2025-10-29 11:41:45'),
(112, 151, '2025-10-16 12:00:00', 1, '2025-10-16 13:00:00', 1, '2025-10-29 11:41:45'),
(113, 153, '2025-10-20 12:00:00', 1, '2025-10-20 13:00:00', 1, '2025-10-29 11:41:45'),
(114, 156, '2025-10-27 12:00:00', 1, '2025-10-27 13:00:00', 1, '2025-10-29 11:41:45'),
(172, 118, '2025-07-03 12:00:00', 0, '2025-07-03 13:00:00', 0, '2025-11-12 12:32:24');

-- --------------------------------------------------------

--
-- テーブルの構造 `day_status`
--

CREATE TABLE `day_status` (
  `id` bigint NOT NULL,
  `user_id` int NOT NULL,
  `date` date NOT NULL,
  `status` enum('work','off','am_off','pm_off','ignore') NOT NULL DEFAULT 'work',
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- ビュー用の代替構造 `day_status_effective`
-- (実際のビューを参照するには下にあります)
--
CREATE TABLE `day_status_effective` (
`date` date
,`note` mediumtext
,`source` varchar(8)
,`status` varchar(6)
,`user_id` int
);

-- --------------------------------------------------------

--
-- テーブルの構造 `day_status_overrides`
--

CREATE TABLE `day_status_overrides` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `date` date NOT NULL,
  `status` varchar(16) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `revoked_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- テーブルのデータのダンプ `day_status_overrides`
--

INSERT INTO `day_status_overrides` (`id`, `user_id`, `date`, `status`, `note`, `created_by`, `created_at`, `revoked_at`) VALUES
(99, 2, '2025-11-08', 'off_full', NULL, 2, '2025-11-12 20:11:30', NULL),
(100, 2, '2025-10-16', 'off_full', NULL, 2, '2025-11-12 20:13:01', '2025-11-16 11:42:06'),
(101, 2, '2025-10-17', 'off_full', NULL, 2, '2025-11-12 20:13:07', '2025-11-16 11:42:17'),
(102, 2, '2025-11-10', 'off_full', NULL, 2, '2025-11-12 22:55:56', NULL),
(103, 2, '2025-10-18', 'off_full', NULL, 2, '2025-11-15 16:12:32', '2025-11-16 11:50:49'),
(104, 2, '2025-10-19', 'off_full', NULL, 2, '2025-11-15 16:12:38', '2025-11-16 11:58:08'),
(105, 2, '2025-10-20', 'off_full', NULL, 2, '2025-11-15 16:12:43', '2025-11-16 11:58:10'),
(106, 2, '2025-10-21', 'off_full', NULL, 2, '2025-11-15 16:12:55', '2025-11-16 11:58:13'),
(107, 2, '2025-10-22', 'off_full', NULL, 2, '2025-11-15 16:17:41', '2025-11-16 11:58:16'),
(108, 2, '2025-10-23', 'off_full', NULL, 2, '2025-11-15 16:19:20', '2025-11-16 11:58:17'),
(109, 2, '2025-10-24', 'off_full', NULL, 2, '2025-11-15 17:01:31', '2025-11-16 11:58:19'),
(110, 2, '2025-10-25', 'off_full', NULL, 2, '2025-11-16 11:00:07', '2025-11-16 11:58:21'),
(111, 2, '2025-10-26', 'off_full', NULL, 2, '2025-11-16 11:16:43', '2025-11-16 11:58:24'),
(112, 2, '2025-10-27', 'off_full', NULL, 2, '2025-11-16 11:17:23', '2025-11-16 11:58:27'),
(113, 2, '2025-10-28', 'off_full', NULL, 2, '2025-11-16 11:17:56', '2025-11-16 11:58:29'),
(114, 2, '2025-10-29', 'off_full', NULL, 2, '2025-11-16 11:25:19', '2025-11-16 11:58:31'),
(115, 2, '2025-10-30', 'off_full', NULL, 2, '2025-11-16 11:26:07', NULL),
(116, 2, '2025-10-31', 'off_full', NULL, 2, '2025-11-16 11:27:15', NULL),
(117, 2, '2025-11-01', 'off_full', NULL, 2, '2025-11-16 11:29:41', NULL),
(118, 2, '2025-11-02', 'off_full', NULL, 2, '2025-11-16 11:31:52', NULL),
(119, 2, '2025-11-03', 'off_full', NULL, 2, '2025-11-16 11:41:37', NULL),
(120, 2, '2025-11-04', 'off_full', NULL, 2, '2025-11-16 11:41:43', '2025-11-16 11:41:55'),
(121, 2, '2025-10-16', 'off_full', NULL, 2, '2025-11-16 11:42:24', '2025-11-16 11:43:22'),
(122, 2, '2025-10-17', 'off_full', NULL, 2, '2025-11-16 11:42:27', '2025-11-16 11:48:47'),
(123, 2, '2025-10-16', 'off_full', NULL, 2, '2025-11-16 11:58:34', '2025-11-16 20:31:04'),
(124, 2, '2025-10-17', 'off_full', NULL, 2, '2025-11-16 11:58:39', '2025-11-16 20:31:08'),
(125, 2, '2025-10-18', 'off_full', NULL, 2, '2025-11-16 11:58:40', '2025-11-16 20:32:22'),
(126, 2, '2025-10-19', 'off_full', NULL, 2, '2025-11-16 11:58:41', '2025-11-16 20:34:27'),
(127, 2, '2025-10-20', 'off_full', NULL, 2, '2025-11-16 11:58:42', '2025-11-16 20:34:31'),
(128, 2, '2025-10-21', 'off_full', NULL, 2, '2025-11-16 11:58:43', NULL),
(129, 2, '2025-10-22', 'off_full', NULL, 2, '2025-11-16 11:58:44', '2025-11-26 11:54:54'),
(130, 2, '2025-10-23', 'off_full', NULL, 2, '2025-11-16 11:58:45', NULL),
(131, 2, '2025-10-24', 'off_full', NULL, 2, '2025-11-16 11:58:46', NULL),
(132, 2, '2025-10-25', 'off_full', NULL, 2, '2025-11-16 11:58:46', NULL),
(133, 2, '2025-10-26', 'off_full', NULL, 2, '2025-11-16 11:58:47', NULL),
(134, 2, '2025-10-27', 'off_full', NULL, 2, '2025-11-16 11:58:48', NULL),
(135, 2, '2025-10-28', 'off_full', NULL, 2, '2025-11-16 11:58:49', NULL),
(136, 2, '2025-10-29', 'off_full', NULL, 2, '2025-11-16 11:58:50', '2025-11-26 11:55:02'),
(137, 2, '2025-11-04', 'off_full', NULL, 2, '2025-11-16 11:58:51', NULL),
(138, 2, '2025-11-09', 'off_full', NULL, 2, '2025-11-16 11:58:53', NULL),
(139, 2, '2025-11-12', 'off_full', NULL, 2, '2025-11-16 11:58:54', '2025-11-26 11:55:43'),
(140, 2, '2025-11-13', 'off_full', NULL, 2, '2025-11-16 11:58:56', NULL),
(141, 2, '2025-11-15', 'off_full', NULL, 2, '2025-11-16 11:58:57', '2025-11-16 19:04:57'),
(142, 2, '2025-10-20', 'off_full', NULL, 2, '2025-11-16 20:35:00', NULL),
(143, 2, '2025-11-16', 'off_full', NULL, 2, '2025-11-18 19:26:45', NULL),
(144, 2, '2025-11-17', 'off_full', NULL, 2, '2025-11-18 19:26:46', NULL),
(145, 2, '2025-11-18', 'off_full', NULL, 2, '2025-11-18 19:26:51', NULL),
(146, 2, '2025-10-16', 'off_full', NULL, 2, '2025-11-18 21:37:53', '2025-11-26 11:54:48'),
(147, 2, '2025-10-17', 'off_full', NULL, 2, '2025-11-18 21:37:55', NULL),
(148, 2, '2025-10-18', 'off_full', NULL, 2, '2025-11-18 21:37:56', NULL),
(149, 2, '2025-10-19', 'off_full', NULL, 2, '2025-11-18 21:37:57', NULL),
(150, 2, '2025-11-15', 'off_full', NULL, 2, '2025-11-18 21:37:59', NULL),
(151, 2, '2025-11-20', 'off_full', NULL, 2, '2025-11-26 11:54:12', NULL),
(152, 2, '2025-11-21', 'off_full', NULL, 2, '2025-11-26 11:54:22', NULL),
(153, 2, '2025-11-22', 'off_full', NULL, 2, '2025-11-26 11:54:23', NULL),
(154, 2, '2025-11-23', 'off_full', NULL, 2, '2025-11-26 11:54:24', NULL),
(155, 2, '2025-11-24', 'off_full', NULL, 2, '2025-11-26 11:54:26', NULL),
(156, 2, '2025-11-25', 'off_full', NULL, 2, '2025-11-26 11:54:27', NULL),
(157, 2, '2025-10-16', 'off_full', NULL, 2, '2025-11-26 11:54:50', NULL),
(158, 2, '2025-11-11', 'off_full', NULL, 2, '2025-11-26 11:55:44', NULL),
(159, 2, '2025-11-14', 'off_full', NULL, 2, '2025-11-26 11:55:53', NULL),
(160, 2, '2025-11-07', 'off_full', NULL, 2, '2025-11-26 11:55:57', NULL),
(161, 2, '2025-11-06', 'off_full', NULL, 2, '2025-11-26 11:55:58', NULL),
(162, 2, '2025-09-16', 'off_full', NULL, 2, '2025-11-26 13:11:39', '2025-11-26 13:11:47'),
(163, 2, '2025-09-20', 'off_full', NULL, 2, '2025-11-26 13:26:46', NULL),
(164, 2, '2025-09-21', 'off_full', NULL, 2, '2025-11-26 13:26:47', NULL),
(165, 2, '2025-09-27', 'off_full', NULL, 2, '2025-11-26 13:26:51', NULL),
(166, 2, '2025-09-28', 'off_full', NULL, 2, '2025-11-26 13:26:53', NULL),
(167, 2, '2025-10-04', 'off_full', NULL, 2, '2025-11-26 13:26:59', NULL),
(168, 2, '2025-10-05', 'off_full', NULL, 2, '2025-11-26 13:27:01', NULL),
(169, 2, '2025-10-12', 'off_full', NULL, 2, '2025-11-26 13:27:07', NULL),
(170, 2, '2025-10-13', 'off_full', NULL, 2, '2025-11-26 13:27:08', NULL);

-- --------------------------------------------------------

--
-- テーブルの構造 `leave_expire_runs`
--

CREATE TABLE `leave_expire_runs` (
  `run_date` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `leave_expire_runs`
--

INSERT INTO `leave_expire_runs` (`run_date`, `created_at`) VALUES
('2025-10-25', '2025-10-25 17:36:37');

-- --------------------------------------------------------

--
-- テーブルの構造 `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `used_date` date NOT NULL,
  `hours` decimal(6,2) NOT NULL,
  `reason` text,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approver_user_id` int DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `decided_ip` varchar(45) DEFAULT NULL,
  `decided_user_agent` varchar(255) DEFAULT NULL,
  `approve_token` varchar(128) DEFAULT NULL,
  `approve_token_hash` char(64) DEFAULT NULL,
  `approve_token_expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- テーブルのデータのダンプ `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `user_id`, `used_date`, `hours`, `reason`, `status`, `approver_user_id`, `decided_at`, `decided_ip`, `decided_user_agent`, `approve_token`, `approve_token_hash`, `approve_token_expires_at`, `created_at`) VALUES
(5, 2, '2025-11-02', '8.00', '', 'approved', NULL, '2025-11-02 19:29:06', NULL, NULL, NULL, NULL, NULL, '2025-11-02 19:28:46'),
(6, 2, '2025-11-03', '8.00', '', 'rejected', NULL, '2025-11-02 20:13:53', NULL, NULL, NULL, NULL, NULL, '2025-11-02 20:13:33'),
(7, 2, '2025-11-07', '8.00', '', 'approved', NULL, '2025-11-02 20:15:09', NULL, NULL, NULL, NULL, NULL, '2025-11-02 20:14:40'),
(8, 2, '2025-11-02', '8.00', '', 'approved', NULL, '2025-11-02 20:27:19', NULL, NULL, NULL, NULL, NULL, '2025-11-02 20:25:34'),
(9, 2, '2025-11-05', '8.00', '', 'approved', NULL, '2025-11-02 20:32:57', NULL, NULL, NULL, NULL, NULL, '2025-11-02 20:32:52'),
(10, 2, '2025-11-03', '8.00', '', 'approved', 2, '2025-11-03 11:45:59', '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, 'd8bf7c3c04c25bc639868ecbb0c8d53bdc8ae3e6c02fbc8e4871a1d96466c3c3', NULL, '2025-11-03 09:43:27'),
(11, 2, '2025-11-05', '8.00', '', 'rejected', 2, '2025-11-03 11:46:00', '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '82f47881d605d905e90182e74aa224fd6cf75b246eb67555a4991da445695b4d', NULL, '2025-11-03 09:49:11'),
(12, 2, '2025-11-03', '8.00', '', 'rejected', NULL, '2025-11-03 11:47:32', '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '8424272298307bc6b56361b9252e8034a221e7348e82dfb2e3ea5779b11a7225', NULL, '2025-11-03 11:47:19'),
(13, 2, '2025-11-03', '8.00', '', 'approved', 2, '2025-11-03 12:36:12', '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, 'f4f252931b1803592727e45497bdb99d906396006330fb1d09cf5c774a1eecd7', NULL, '2025-11-03 11:51:38'),
(14, 2, '2025-11-03', '8.00', '', 'rejected', 2, '2025-11-03 12:36:14', '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '13f79647fa85acaf312fe3b9a1a36b6d9c5b91d856a9c99315225621d3038882', NULL, '2025-11-03 11:51:43'),
(15, 2, '2025-11-03', '8.00', '', 'rejected', 2, '2025-11-03 13:04:59', '119.243.184.174', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', NULL, 'f15446585e02b418494af9863db3744792ada01853bfa06eb924a0f3aa30a115', NULL, '2025-11-03 13:04:49'),
(16, 2, '2025-11-03', '8.00', '', 'approved', 2, '2025-11-03 13:08:57', '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '678052b3813fe7b05e4c16e02b3c942c02c9220a0c665f5b881469eea3f365bf', NULL, '2025-11-03 13:08:49'),
(17, 2, '2025-11-03', '8.00', '', 'rejected', 2, '2025-11-03 13:47:31', '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '4e067bbc89a5ada698077dfedbeaf8e8405c90a056837d01e262e5fd08f4411b', NULL, '2025-11-03 13:47:21'),
(18, 2, '2025-11-03', '8.00', '', 'approved', 2, '2025-11-03 14:08:28', '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '483ae819ea4105951842a6396d4cfc4840038edc6af2e414265dc29f5bde76f4', NULL, '2025-11-03 14:08:18'),
(19, 2, '2025-11-03', '8.00', '', 'rejected', 2, '2025-11-03 21:56:29', '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, 'ee0756fc0586014c634a4bdbc88397f4db371533fe483dabd103f20425c8aef7', NULL, '2025-11-03 21:02:59'),
(20, 2, '2025-11-03', '8.00', '', 'rejected', 2, '2025-11-03 22:21:47', '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, 'fd7fa64684a0ffaeaf0c0189c45ca97f37eed33ad1fbc97ddd7f9bf342138549', NULL, '2025-11-03 21:50:37'),
(21, 2, '2025-11-03', '8.00', '', 'rejected', 2, '2025-11-12 13:48:43', '157.147.233.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', NULL, 'eec6dfb6eca6980e15cf7a16f8c2a3b44c0cef714810979d8568d8eca6140e08', NULL, '2025-11-03 21:52:59'),
(22, 2, '2025-11-03', '8.00', '', 'rejected', 2, '2025-11-12 13:48:45', '157.147.233.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', NULL, 'b3383b19d47019f47575cd20555749648e9a68c13c4ad4180e7a2b01e8fbaf53', NULL, '2025-11-03 21:57:22'),
(23, 2, '2025-10-31', '8.00', '', 'rejected', 2, '2025-11-07 19:05:53', '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '09751221a5edc62593211c026dd35a7c440a2f8a90541dd7b122636dec395f08', NULL, '2025-11-03 22:32:33'),
(24, 2, '2025-11-05', '8.00', '', 'rejected', 2, '2025-11-09 13:20:56', '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', NULL, '320aa66ed7f470930431d42ebf52ad5720d4b89a02ec871a7e2c9c1f6d753fa8', NULL, '2025-11-05 13:09:41'),
(25, 2, '2025-11-14', '8.00', '', 'approved', 2, '2025-11-12 13:48:52', '157.147.233.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', NULL, 'c15baddf492b06b1616950697aafc988385d624906981b35640ada875147c4e4', NULL, '2025-11-12 13:48:18'),
(26, 2, '2025-11-10', '8.00', '', 'approved', 2, '2025-11-12 15:40:29', '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', NULL, 'e6fcebf0c3ac29db84f4168f7409b84737ec5b8a5cd489c62113477bbb4bf3ae', NULL, '2025-11-12 15:40:18'),
(27, 2, '2025-10-31', '8.00', '', 'approved', 2, '2025-11-12 20:14:31', '106.132.157.173', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', NULL, '48af81cd3419f3762a0a8399d701efa215d61e039333f0a8a59b9ef5f2449e00', NULL, '2025-11-12 20:14:17');

-- --------------------------------------------------------

--
-- テーブルの構造 `leave_request_audit`
--

CREATE TABLE `leave_request_audit` (
  `id` bigint NOT NULL,
  `request_id` int NOT NULL,
  `action` enum('create','open','approve','reject') NOT NULL,
  `actor_type` enum('user','admin','token','system') NOT NULL,
  `actor_id` int DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- テーブルのデータのダンプ `leave_request_audit`
--

INSERT INTO `leave_request_audit` (`id`, `request_id`, `action`, `actor_type`, `actor_id`, `ip`, `user_agent`, `created_at`) VALUES
(1, 10, 'approve', 'admin', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 11:45:59'),
(2, 11, 'reject', 'admin', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 11:46:00'),
(3, 12, 'create', 'user', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 11:47:19'),
(4, 12, 'open', 'token', NULL, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 11:47:30'),
(5, 12, 'reject', 'token', NULL, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 11:47:32'),
(6, 13, 'create', 'user', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 11:51:38'),
(7, 14, 'create', 'user', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 11:51:43'),
(8, 13, 'open', 'token', NULL, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 11:52:31'),
(9, 13, 'approve', 'admin', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 12:36:12'),
(10, 14, 'reject', 'admin', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 12:36:14'),
(11, 15, 'create', 'user', 2, '119.243.184.174', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-11-03 13:04:49'),
(12, 15, 'reject', 'admin', 2, '119.243.184.174', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-11-03 13:04:59'),
(13, 16, 'create', 'user', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 13:08:49'),
(14, 16, 'approve', 'admin', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 13:08:57'),
(15, 17, 'create', 'user', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 13:47:21'),
(16, 17, 'reject', 'admin', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 13:47:31'),
(17, 18, 'create', 'user', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 14:08:18'),
(18, 18, 'approve', 'admin', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 14:08:28'),
(19, 19, 'create', 'user', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 21:02:59'),
(20, 20, 'create', 'user', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 21:50:37'),
(21, 21, 'create', 'user', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 21:52:59'),
(22, 19, 'reject', 'admin', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 21:56:29'),
(23, 22, 'create', 'user', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 21:57:22'),
(24, 20, 'reject', 'admin', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 22:21:47'),
(25, 23, 'create', 'user', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-03 22:32:33'),
(26, 23, 'open', 'token', NULL, '157.147.233.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-05 10:03:07'),
(27, 24, 'create', 'user', 2, '157.147.233.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 13:09:41'),
(28, 24, 'open', 'token', NULL, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-06 17:09:08'),
(29, 23, 'reject', 'admin', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-07 19:05:53'),
(30, 24, 'reject', 'admin', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-09 13:20:56'),
(31, 25, 'create', 'user', 2, '157.147.233.93', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-11-12 13:48:18'),
(32, 21, 'reject', 'admin', 2, '157.147.233.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 13:48:43'),
(33, 22, 'reject', 'admin', 2, '157.147.233.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 13:48:45'),
(34, 25, 'approve', 'admin', 2, '157.147.233.93', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 13:48:52'),
(35, 26, 'create', 'user', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 15:40:18'),
(36, 26, 'approve', 'admin', 2, '119.243.184.174', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 15:40:29'),
(37, 27, 'create', 'user', 2, '106.132.157.173', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-12 20:14:17'),
(38, 27, 'approve', 'admin', 2, '106.132.157.173', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-12 20:14:31');

-- --------------------------------------------------------

--
-- テーブルの構造 `leave_statuses`
--

CREATE TABLE `leave_statuses` (
  `id` int NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `leave_statuses`
--

INSERT INTO `leave_statuses` (`id`, `code`, `name`, `description`, `is_active`, `created_at`) VALUES
(1, 'ACTIVE', '有効', '付与済みで利用可能な状態', 1, '2025-10-15 04:42:08'),
(2, 'SCHEDULED', '付与予定', '付与が確定していない状態', 1, '2025-10-15 04:42:08'),
(3, 'EXPIRED', '失効', '有効期限を過ぎた状態', 1, '2025-10-15 04:42:08');

-- --------------------------------------------------------

--
-- テーブルの構造 `leave_units`
--

CREATE TABLE `leave_units` (
  `id` int NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `minutes_per_unit` int NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `leave_units`
--

INSERT INTO `leave_units` (`id`, `code`, `name`, `description`, `minutes_per_unit`, `is_active`, `created_at`) VALUES
(1, 'DAY', '日単位', '契約上の所定労働時間を1日として扱う単位', 480, 1, '2025-10-15 04:42:08'),
(2, 'HALF_DAY', '半日単位', '半日休暇で利用する単位', 240, 1, '2025-10-15 04:42:08'),
(3, 'HOUR', '時間単位', '時間単位で利用する休暇', 60, 1, '2025-10-15 04:42:08'),
(4, 'MINUTE', '分単位', '最小単位としての1分', 1, 1, '2025-10-15 04:42:08');

-- --------------------------------------------------------

--
-- テーブルの構造 `log_types`
--

CREATE TABLE `log_types` (
  `id` int NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `effect` enum('add','subtract','informational') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'subtract',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `log_types`
--

INSERT INTO `log_types` (`id`, `code`, `name`, `description`, `effect`, `is_active`, `created_at`) VALUES
(1, 'GRANT', '付与', '有給を付与したログ', 'add', 1, '2025-10-15 04:42:08'),
(2, 'USE', '利用', '有給を消化したログ', 'subtract', 1, '2025-10-15 04:42:08'),
(3, 'EXPIRE', '失効', '期限により自動失効したログ', 'subtract', 1, '2025-10-15 04:42:08'),
(4, 'ADJUST', '調整', '手動調整や情報提供のログ', 'informational', 1, '2025-10-15 04:42:08');

-- --------------------------------------------------------

--
-- テーブルの構造 `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint NOT NULL,
  `user_id` int NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `body` text,
  `link` varchar(255) DEFAULT NULL,
  `status` enum('unread','read') NOT NULL DEFAULT 'unread',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- テーブルのデータのダンプ `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `body`, `link`, `status`, `created_at`, `read_at`) VALUES
(1, 2, 'leave_request_result', '有給申請が承認されました', '2025-11-03 8.0h 申請の決裁結果です。', '../attendance_list.html', 'read', '2025-11-03 12:36:12', '2025-11-03 12:55:47'),
(2, 2, 'leave_request_result', '有給申請が却下されました', '2025-11-03 8.0h 申請の決裁結果です。', '../attendance_list.html', 'read', '2025-11-03 12:36:14', '2025-11-03 12:55:47'),
(3, 2, 'leave_request_result', '有給申請が却下されました', '2025-11-03 8.0h 申請の決裁結果です。', '../attendance_list.html', 'read', '2025-11-03 13:04:59', '2025-11-03 13:05:23'),
(4, 2, 'leave_request_result', '有給申請が承認されました', '2025-11-03 8.0h 申請の決裁結果です。', '../attendance_list.html', 'read', '2025-11-03 13:08:57', '2025-11-03 13:09:31'),
(5, 2, 'leave_request_result', '有給申請が却下されました', '2025-11-03 8.0h 申請の決裁結果です。', '../attendance_list.html', 'read', '2025-11-03 13:47:31', '2025-11-03 13:47:45'),
(6, 2, 'leave_request_result', '有給申請が承認されました', '2025-11-03 8.0h 申請の決裁結果です。', '../attendance_list.html', 'read', '2025-11-03 14:08:28', '2025-11-03 14:14:31'),
(7, 2, 'leave_request_result', '有給申請が却下されました', '2025-11-03 8.0h 申請の決裁結果です。', '../attendance_list.html', 'read', '2025-11-03 21:56:29', '2025-11-03 22:32:45'),
(8, 2, 'leave_request_result', '有給申請が却下されました', '2025-11-03 8.0h 申請の決裁結果です。', '../attendance_list.html', 'read', '2025-11-03 22:21:47', '2025-11-03 22:32:46'),
(9, 6, 'attendance_missing_reminder', '勤務記録未入力のお願い', '11月5日の勤務記録がありません。入力をお願いします。', '../attendance_list.html', 'read', '2025-11-06 17:32:45', '2025-11-06 17:50:48'),
(10, 6, 'attendance_missing_reminder', '勤務記録未入力のお願い', '11月5日の勤務記録がありません。入力をお願いします。', './attendance_list.html', 'read', '2025-11-06 17:35:44', '2025-11-06 17:50:47'),
(11, 5, 'attendance_missing_reminder', '勤務記録未入力のお願い', '11月5日の勤務記録がありません。入力をお願いします。', './attendance_list.html', 'unread', '2025-11-06 17:45:55', NULL),
(12, 5, 'attendance_missing_reminder', '勤務記録未入力のお願い', '11月5日の勤務記録がありません。入力をお願いします。', './attendance_list.html', 'unread', '2025-11-06 17:48:27', NULL),
(13, 5, 'attendance_missing_reminder', '勤務記録未入力のお願い', '11月5日の勤務記録がありません。入力をお願いします。', './attendance_list.html', 'unread', '2025-11-06 17:49:47', NULL),
(14, 5, 'attendance_missing_reminder', '勤務記録未入力のお願い', '11月5日の勤務記録がありません。入力をお願いします。', './attendance_list.html', 'unread', '2025-11-06 17:50:31', NULL),
(15, 6, 'attendance_missing_reminder', '勤務記録未入力のお願い', '11月5日の勤務記録がありません。入力をお願いします。', './attendance_list.html', 'read', '2025-11-06 17:50:55', '2025-11-06 17:51:13'),
(16, 6, 'attendance_missing_reminder', '勤務記録未入力のお願い', '11月4日の勤務記録がありません。入力をお願いします。', './attendance_list.html', 'read', '2025-11-06 18:02:46', '2025-11-06 21:08:29'),
(17, 5, 'attendance_missing_reminder', '勤務記録未入力のお願い', '11月5日の勤務記録がありません。入力をお願いします。', './attendance_list.html', 'unread', '2025-11-06 21:01:40', NULL),
(18, 5, 'attendance_missing_reminder', '勤務記録未入力のお願い', '11月4日の勤務記録がありません。入力をお願いします。', './attendance_list.html', 'unread', '2025-11-06 21:01:40', NULL),
(19, 5, 'attendance_missing_reminder', '勤務記録未入力のお願い', '11月3日の勤務記録がありません。入力をお願いします。', './attendance_list.html', 'unread', '2025-11-06 21:01:40', NULL),
(20, 5, 'attendance_missing_reminder', '勤務記録未入力のお願い', '11月2日の勤務記録がありません。入力をお願いします。', './attendance_list.html', 'unread', '2025-11-06 21:01:40', NULL),
(21, 5, 'attendance_missing_reminder', '勤務記録未入力のお願い', '11月1日の勤務記録がありません。入力をお願いします。', './attendance_list.html', 'unread', '2025-11-06 21:01:40', NULL),
(22, 5, 'attendance_missing_reminder', '勤務記録未入力のお願い', '10月31日の勤務記録がありません。入力をお願いします。', './attendance_list.html', 'unread', '2025-11-06 21:01:41', NULL),
(23, 5, 'attendance_missing_reminder', '勤務記録未入力のお願い', '10月30日の勤務記録がありません。入力をお願いします。', './attendance_list.html', 'unread', '2025-11-06 21:01:41', NULL),
(24, 6, 'attendance_missing_reminder', '勤務記録未入力のお願い', '11月5日の勤務記録がありません。入力をお願いします。', './attendance_list.html', 'read', '2025-11-06 21:06:00', '2025-11-06 21:08:29'),
(25, 6, 'attendance_missing_reminder', '勤務記録未入力のお願い', '11月4日の勤務記録がありません。入力をお願いします。', './attendance_list.html', 'read', '2025-11-06 21:06:01', '2025-11-06 21:08:29'),
(26, 6, 'attendance_missing_reminder', '勤務記録未入力のお願い', '11月3日の勤務記録がありません。入力をお願いします。', './attendance_list.html', 'read', '2025-11-06 21:06:01', '2025-11-06 21:08:29'),
(27, 6, 'attendance_missing_reminder', '勤務記録未入力のお願い', '11月1日の勤務記録がありません。入力をお願いします。', './attendance_list.html', 'read', '2025-11-06 21:06:01', '2025-11-06 21:08:29'),
(28, 6, 'attendance_missing_reminder', '勤務記録未入力のお願い', '10月31日の勤務記録がありません。入力をお願いします。', './attendance_list.html', 'read', '2025-11-06 21:06:01', '2025-11-06 21:08:29'),
(29, 2, 'leave_request_result', '有給申請が却下されました', '2025-10-31 8.0h 申請の決裁結果です。', '../attendance_list.html', 'read', '2025-11-07 19:05:53', '2025-11-09 12:51:16'),
(30, 6, 'attendance_missing_reminder', '勤務記録未入力のお願い', '11月6日の勤務記録がありません。入力をお願いします。\n\n管理者コメント: テスト入力です', './attendance_list.html', 'read', '2025-11-07 22:03:58', '2025-11-07 22:05:49'),
(31, 6, 'attendance_missing_reminder', '勤務記録未入力のお願い', '11月1日の勤務記録がありません。入力をお願いします。\r\n\r\n管理者コメント: test message', './attendance_list.html', 'read', '2025-11-07 22:07:27', '2025-11-07 22:21:48'),
(32, 6, 'attendance_missing_reminder', '勤務記録未入力のお願い', '11月1日の勤務記録がありません。入力をお願いします。<br>管理者コメント: it\'s a test message', './attendance_list.html', 'read', '2025-11-07 22:09:07', '2025-11-07 22:21:48'),
(33, 6, 'attendance_missing_reminder', '勤務記録未入力のお願い', '11月6日の勤務記録がありません。入力をお願いします。\\n\\n管理者コメント: testtesttest', './attendance_list.html', 'read', '2025-11-07 22:40:02', '2025-11-12 10:34:57'),
(34, 6, 'attendance_missing_reminder', '勤務記録未入力のお願い', '10月31日の勤務記録がありません。入力をお願いします。\r\n\r\n管理者コメント: testtesttesttesttest', './attendance_list.html', 'read', '2025-11-07 22:41:26', '2025-11-12 10:34:57'),
(35, 2, 'leave_request_result', '有給申請が却下されました', '2025-11-05 8.0h 申請の決裁結果です。', '../attendance_list.html', 'read', '2025-11-09 13:20:56', '2025-11-11 14:25:04'),
(36, 2, 'leave_request_result', '有給申請が却下されました', '2025-11-03 8.0h 申請の決裁結果です。', '../attendance_list.html', 'read', '2025-11-12 13:48:43', '2025-11-12 14:02:15'),
(37, 2, 'leave_request_result', '有給申請が却下されました', '2025-11-03 8.0h 申請の決裁結果です。', '../attendance_list.html', 'read', '2025-11-12 13:48:45', '2025-11-12 14:02:15'),
(38, 2, 'leave_request_result', '有給申請が承認されました', '2025-11-14 8.0h 申請の決裁結果です。', '../attendance_list.html', 'read', '2025-11-12 13:48:52', '2025-11-12 14:02:15'),
(39, 2, 'leave_request_result', '有給申請が承認されました', '2025-11-10 8.0h 申請の決裁結果です。', '../attendance_list.html', 'read', '2025-11-12 15:40:29', '2025-11-12 16:14:29'),
(40, 2, 'leave_request_result', '有給申請が承認されました', '2025-10-31 8.0h 申請の決裁結果です。', '../attendance_list.html', 'read', '2025-11-12 20:14:31', '2025-11-12 20:14:45');

-- --------------------------------------------------------

--
-- テーブルの構造 `paid_leaves`
--

CREATE TABLE `paid_leaves` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `grant_date` date NOT NULL,
  `grant_hours` float NOT NULL,
  `consumed_hours_total` decimal(6,2) NOT NULL DEFAULT '0.00',
  `expire_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `paid_leaves`
--

INSERT INTO `paid_leaves` (`id`, `user_id`, `grant_date`, `grant_hours`, `consumed_hours_total`, `expire_date`) VALUES
(4, 5, '2025-10-01', 10, '0.00', '2029-10-01'),
(16, 2, '2015-12-04', 80, '0.00', '2017-12-03'),
(17, 2, '2016-12-04', 80, '0.00', '2018-12-03'),
(18, 2, '2017-12-04', 88, '0.00', '2019-12-03'),
(19, 2, '2018-12-04', 96, '0.00', '2020-12-03'),
(20, 2, '2019-12-04', 112, '0.00', '2021-12-03'),
(21, 2, '2020-12-04', 128, '0.00', '2022-12-03'),
(22, 2, '2021-12-04', 144, '0.00', '2023-12-03'),
(23, 2, '2022-12-04', 160, '0.00', '2024-12-03'),
(24, 2, '2023-12-04', 160, '0.00', '2025-12-03'),
(25, 2, '2024-12-04', 160, '0.00', '2026-12-03'),
(26, 6, '2025-10-01', 12, '0.00', '2027-09-30');

-- --------------------------------------------------------

--
-- テーブルの構造 `paid_leave_logs`
--

CREATE TABLE `paid_leave_logs` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `paid_leave_id` int DEFAULT NULL,
  `event_id` int DEFAULT NULL,
  `used_date` date NOT NULL,
  `used_hours` float NOT NULL,
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `log_type_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `paid_leave_logs`
--

INSERT INTO `paid_leave_logs` (`id`, `user_id`, `paid_leave_id`, `event_id`, `used_date`, `used_hours`, `reason`, `log_type_id`) VALUES
(27, 2, 16, NULL, '2017-12-03', 80, '失効', 3),
(29, 2, 17, NULL, '2018-12-03', 80, '失効', 3),
(30, 2, 18, NULL, '2019-12-03', 88, '失効', 3),
(31, 2, 19, NULL, '2020-12-03', 96, '失効', 3),
(32, 2, 20, NULL, '2021-12-03', 112, '失効', 3),
(33, 2, 21, NULL, '2022-12-03', 128, '失効', 3),
(34, 2, 22, NULL, '2023-12-03', 144, '失効', 3),
(35, 2, 23, NULL, '2024-12-03', 160, '失効', 3);

-- --------------------------------------------------------

--
-- テーブルの構造 `paid_leave_types`
--

CREATE TABLE `paid_leave_types` (
  `id` int NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `default_unit_id` int NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `paid_leave_types`
--

INSERT INTO `paid_leave_types` (`id`, `code`, `name`, `default_unit_id`, `description`, `is_active`, `created_at`) VALUES
(1, 'ANNUAL', '年次有給休暇', 1, '労働基準法に基づく通常の年次有給休暇', 1, '2025-10-15 04:42:08'),
(2, 'COMPENSATORY', '代休', 3, '時間単位で付与される代替休暇', 1, '2025-10-15 04:42:08'),
(3, 'SPECIAL', '特別休暇', 1, '慶弔・産前産後など各種特別休暇', 1, '2025-10-15 04:42:08');

-- --------------------------------------------------------

--
-- テーブルの構造 `paid_leave_use_events`
--

CREATE TABLE `paid_leave_use_events` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `used_date` date NOT NULL,
  `total_hours` float NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `request_rate_limit`
--

CREATE TABLE `request_rate_limit` (
  `id` bigint NOT NULL,
  `ip` varchar(45) NOT NULL,
  `endpoint` varchar(100) NOT NULL,
  `period_start` datetime NOT NULL,
  `count` int NOT NULL DEFAULT '0',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- テーブルのデータのダンプ `request_rate_limit`
--

INSERT INTO `request_rate_limit` (`id`, `ip`, `endpoint`, `period_start`, `count`) VALUES
(1, '119.243.184.174', 'leave_requests_decide_admin', '2025-11-12 15:40:29', 1),
(2, '157.147.233.93', 'leave_requests_approve_link', '2025-11-05 10:03:07', 1),
(3, '119.243.184.174', 'leave_requests_approve_link', '2025-11-06 17:09:08', 1),
(4, '119.243.184.174', 'attendance_notify_missing', '2025-11-07 22:40:02', 2),
(5, '157.147.233.93', 'leave_requests_decide_admin', '2025-11-12 13:48:43', 3),
(6, '106.132.157.173', 'leave_requests_decide_admin', '2025-11-12 20:14:31', 1);

-- --------------------------------------------------------

--
-- テーブルの構造 `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `period_start` tinyint NOT NULL,
  `period_end` tinyint NOT NULL,
  `rounding_type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `rounding_unit` tinyint NOT NULL,
  `work_hours` tinyint NOT NULL,
  `work_minutes` tinyint NOT NULL,
  `legal_hours_28` int DEFAULT '160',
  `legal_hours_29` int DEFAULT '165',
  `legal_hours_30` int DEFAULT '171',
  `legal_hours_31` int DEFAULT '177',
  `paid_leave_valid_months` int NOT NULL DEFAULT '24',
  `paid_leave_rules` json DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `settings`
--

INSERT INTO `settings` (`id`, `period_start`, `period_end`, `rounding_type`, `rounding_unit`, `work_hours`, `work_minutes`, `legal_hours_28`, `legal_hours_29`, `legal_hours_30`, `legal_hours_31`, `paid_leave_valid_months`, `paid_leave_rules`, `updated_at`) VALUES
(1, 16, 15, 'ceil', 15, 8, 0, 160, 165, 171, 177, 24, '{\"fulltime\": [10, 11, 12, 14, 16, 18, 20], \"parttime\": {\"1d\": [1, 2, 2, 2, 3, 3, 3], \"2d\": [3, 4, 4, 5, 6, 6, 7], \"3d\": [5, 6, 6, 7, 9, 10, 11], \"4d\": [7, 8, 9, 10, 12, 13, 15]}, \"milestones\": [\"6m\", \"1y6m\", \"2y6m\", \"3y6m\", \"4y6m\", \"5y6m\", \"6y6m+\"]}', '2025-11-02 16:18:35');

-- --------------------------------------------------------

--
-- テーブルの構造 `source_types`
--

CREATE TABLE `source_types` (
  `id` int NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `paid_leave_type_id` int DEFAULT NULL,
  `is_user_selectable` tinyint(1) NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `source_types`
--

INSERT INTO `source_types` (`id`, `code`, `name`, `description`, `paid_leave_type_id`, `is_user_selectable`, `is_active`, `created_at`) VALUES
(1, 'ANNUAL_GENERAL', '年次有給（通常）', '1日単位の年次有給休暇', 1, 1, 1, '2025-10-15 04:42:08'),
(2, 'ANNUAL_HALF', '年次有給（半日）', '半日単位で取得する年次有給休暇', 1, 1, 1, '2025-10-15 04:42:08'),
(3, 'COMP_TIME', '代休', '時間外労働や休日出勤の代替休暇', 2, 1, 1, '2025-10-15 04:42:08'),
(4, 'BEREAVEMENT', '慶弔休暇', '結婚・忌引などの特別休暇', 3, 0, 1, '2025-10-15 04:42:08');

-- --------------------------------------------------------

--
-- テーブルの構造 `timecards`
--

CREATE TABLE `timecards` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `work_date` date NOT NULL,
  `clock_in` datetime DEFAULT NULL,
  `clock_in_manual` tinyint(1) DEFAULT '0',
  `clock_out` datetime DEFAULT NULL,
  `clock_out_manual` tinyint(1) DEFAULT '0',
  `vehicle_distance` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- テーブルのデータのダンプ `timecards`
--

INSERT INTO `timecards` (`id`, `user_id`, `work_date`, `clock_in`, `clock_in_manual`, `clock_out`, `clock_out_manual`, `vehicle_distance`, `created_at`) VALUES
(82, 6, '2025-04-02', '2025-04-02 09:30:00', 1, '2025-04-02 12:00:00', 1, 0, '2025-10-29 11:41:45'),
(83, 6, '2025-04-04', '2025-04-04 10:00:00', 1, '2025-04-04 15:30:00', 1, 0, '2025-10-29 11:41:45'),
(84, 6, '2025-04-08', '2025-04-08 13:00:00', 1, '2025-04-08 16:00:00', 1, 0, '2025-10-29 11:41:45'),
(85, 6, '2025-04-11', '2025-04-11 09:00:00', 1, '2025-04-11 12:00:00', 1, 0, '2025-10-29 11:41:45'),
(86, 6, '2025-04-15', '2025-04-15 11:00:00', 1, '2025-04-15 16:30:00', 1, 0, '2025-10-29 11:41:45'),
(87, 6, '2025-04-18', '2025-04-18 14:00:00', 1, '2025-04-18 16:00:00', 1, 0, '2025-10-29 11:41:45'),
(88, 6, '2025-04-22', '2025-04-22 10:30:00', 1, '2025-04-22 13:30:00', 1, 0, '2025-10-29 11:41:45'),
(89, 6, '2025-04-25', '2025-04-25 12:30:00', 1, '2025-04-25 16:30:00', 1, 0, '2025-10-29 11:41:45'),
(90, 6, '2025-05-01', '2025-05-01 09:00:00', 1, '2025-05-01 12:00:00', 1, 0, '2025-10-29 11:41:45'),
(91, 6, '2025-05-02', '2025-05-02 10:00:00', 1, '2025-05-02 16:00:00', 1, 0, '2025-10-29 11:41:45'),
(92, 6, '2025-05-06', '2025-05-06 13:30:00', 1, '2025-05-06 16:00:00', 1, 0, '2025-10-29 11:41:45'),
(93, 6, '2025-05-09', '2025-05-09 11:30:00', 1, '2025-05-09 15:30:00', 1, 0, '2025-10-29 11:41:45'),
(94, 6, '2025-05-13', '2025-05-13 09:15:00', 1, '2025-05-13 12:15:00', 1, 0, '2025-10-29 11:41:45'),
(95, 6, '2025-05-16', '2025-05-16 12:45:00', 1, '2025-05-16 16:45:00', 1, 0, '2025-10-29 11:41:45'),
(96, 6, '2025-05-20', '2025-05-20 10:30:00', 1, '2025-05-20 13:00:00', 1, 0, '2025-10-29 11:41:45'),
(97, 6, '2025-05-23', '2025-05-23 11:00:00', 1, '2025-05-23 16:00:00', 1, 0, '2025-10-29 11:41:45'),
(98, 6, '2025-05-27', '2025-05-27 09:30:00', 1, '2025-05-27 14:30:00', 1, 0, '2025-10-29 11:41:45'),
(99, 6, '2025-05-28', '2025-05-28 13:00:00', 1, '2025-05-28 17:00:00', 1, 0, '2025-10-29 11:41:45'),
(100, 6, '2025-05-29', '2025-05-29 09:00:00', 1, '2025-05-29 11:30:00', 1, 0, '2025-10-29 11:41:45'),
(101, 6, '2025-05-30', '2025-05-30 10:45:00', 1, '2025-05-30 15:15:00', 1, 0, '2025-10-29 11:41:45'),
(102, 6, '2025-06-02', '2025-06-02 09:00:00', 1, '2025-06-02 12:30:00', 1, 0, '2025-10-29 11:41:45'),
(103, 6, '2025-06-03', '2025-06-03 10:00:00', 1, '2025-06-03 13:30:00', 1, 0, '2025-10-29 11:41:45'),
(104, 6, '2025-06-04', '2025-06-04 13:00:00', 1, '2025-06-04 15:00:00', 1, 0, '2025-10-29 11:41:45'),
(105, 6, '2025-06-05', '2025-06-05 11:00:00', 1, '2025-06-05 17:00:00', 1, 0, '2025-10-29 11:41:45'),
(106, 6, '2025-06-06', '2025-06-06 09:30:00', 1, '2025-06-06 12:30:00', 1, 0, '2025-10-29 11:41:45'),
(107, 6, '2025-06-09', '2025-06-09 12:30:00', 1, '2025-06-09 16:30:00', 1, 0, '2025-10-29 11:41:45'),
(108, 6, '2025-06-10', '2025-06-10 10:30:00', 1, '2025-06-10 16:30:00', 1, 0, '2025-10-29 11:41:45'),
(109, 6, '2025-06-11', '2025-06-11 09:00:00', 1, '2025-06-11 11:30:00', 1, 0, '2025-10-29 11:41:45'),
(110, 6, '2025-06-13', '2025-06-13 13:00:00', 1, '2025-06-13 17:00:00', 1, 0, '2025-10-29 11:41:45'),
(111, 6, '2025-06-16', '2025-06-16 10:00:00', 1, '2025-06-16 14:00:00', 1, 0, '2025-10-29 11:41:45'),
(112, 6, '2025-06-18', '2025-06-18 09:15:00', 1, '2025-06-18 12:15:00', 1, 0, '2025-10-29 11:41:45'),
(113, 6, '2025-06-20', '2025-06-20 11:30:00', 1, '2025-06-20 16:00:00', 1, 0, '2025-10-29 11:41:45'),
(114, 6, '2025-06-23', '2025-06-23 14:00:00', 1, '2025-06-23 16:00:00', 1, 0, '2025-10-29 11:41:45'),
(115, 6, '2025-06-25', '2025-06-25 10:45:00', 1, '2025-06-25 15:45:00', 1, 0, '2025-10-29 11:41:45'),
(116, 6, '2025-06-27', '2025-06-27 12:30:00', 1, '2025-06-27 15:30:00', 1, 0, '2025-10-29 11:41:45'),
(117, 6, '2025-07-01', '2025-07-01 09:00:00', 1, '2025-07-01 11:00:00', 1, 0, '2025-10-29 11:41:45'),
(118, 6, '2025-07-03', '2025-07-03 10:00:00', 1, '2025-07-03 14:30:00', 1, 0, '2025-10-29 11:41:45'),
(119, 6, '2025-07-08', '2025-07-08 13:30:00', 1, '2025-07-08 16:30:00', 1, 0, '2025-10-29 11:41:45'),
(120, 6, '2025-07-11', '2025-07-11 11:00:00', 1, '2025-07-11 16:30:00', 1, 0, '2025-10-29 11:41:45'),
(121, 6, '2025-07-15', '2025-07-15 09:30:00', 1, '2025-07-15 12:00:00', 1, 0, '2025-10-29 11:41:45'),
(122, 6, '2025-07-22', '2025-07-22 12:30:00', 1, '2025-07-22 17:00:00', 1, 0, '2025-10-29 11:41:45'),
(123, 6, '2025-07-29', '2025-07-29 10:30:00', 1, '2025-07-29 13:30:00', 1, 0, '2025-10-29 11:41:45'),
(124, 6, '2025-08-01', '2025-08-01 09:00:00', 1, '2025-08-01 12:30:00', 1, 0, '2025-10-29 11:41:45'),
(125, 6, '2025-08-05', '2025-08-05 10:00:00', 1, '2025-08-05 15:00:00', 1, 0, '2025-10-29 11:41:45'),
(126, 6, '2025-08-07', '2025-08-07 13:00:00', 1, '2025-08-07 15:30:00', 1, 0, '2025-10-29 11:41:45'),
(127, 6, '2025-08-12', '2025-08-12 11:00:00', 1, '2025-08-12 16:00:00', 1, 0, '2025-10-29 11:41:45'),
(128, 6, '2025-08-14', '2025-08-14 09:30:00', 1, '2025-08-14 12:00:00', 1, 0, '2025-10-29 11:41:45'),
(129, 6, '2025-08-18', '2025-08-18 12:30:00', 1, '2025-08-18 16:30:00', 1, 0, '2025-10-29 11:41:45'),
(130, 6, '2025-08-21', '2025-08-21 10:30:00', 1, '2025-08-21 14:00:00', 1, 0, '2025-10-29 11:41:45'),
(131, 6, '2025-08-25', '2025-08-25 13:00:00', 1, '2025-08-25 17:00:00', 1, 0, '2025-10-29 11:41:45'),
(132, 6, '2025-08-27', '2025-08-27 09:00:00', 1, '2025-08-27 11:30:00', 1, 0, '2025-10-29 11:41:45'),
(133, 6, '2025-08-29', '2025-08-29 11:30:00', 1, '2025-08-29 15:30:00', 1, 0, '2025-10-29 11:41:45'),
(134, 6, '2025-09-02', '2025-09-02 09:00:00', 1, '2025-09-02 11:00:00', 1, 0, '2025-10-29 11:41:45'),
(135, 6, '2025-09-05', '2025-09-05 10:00:00', 1, '2025-09-05 13:30:00', 1, 0, '2025-10-29 11:41:45'),
(136, 6, '2025-09-09', '2025-09-09 13:30:00', 1, '2025-09-09 16:30:00', 1, 0, '2025-10-29 11:41:45'),
(137, 6, '2025-09-12', '2025-09-12 11:00:00', 1, '2025-09-12 17:00:00', 1, 0, '2025-10-29 11:41:45'),
(138, 6, '2025-09-19', '2025-09-19 09:30:00', 1, '2025-09-19 12:00:00', 1, 0, '2025-10-29 11:41:45'),
(139, 6, '2025-09-24', '2025-09-24 12:45:00', 1, '2025-09-24 16:45:00', 1, 0, '2025-10-29 11:41:45'),
(140, 6, '2025-10-01', '2025-10-01 09:00:00', 1, '2025-10-01 12:00:00', 1, 0, '2025-10-29 11:41:45'),
(141, 6, '2025-10-02', '2025-10-02 10:30:00', 1, '2025-10-02 14:30:00', 1, 0, '2025-10-29 11:41:45'),
(142, 6, '2025-10-03', '2025-10-03 13:00:00', 1, '2025-10-03 17:00:00', 1, 0, '2025-10-29 11:41:45'),
(143, 6, '2025-10-06', '2025-10-06 11:00:00', 1, '2025-10-06 16:30:00', 1, 0, '2025-10-29 11:41:45'),
(144, 6, '2025-10-07', '2025-10-07 09:30:00', 1, '2025-10-07 12:30:00', 1, 0, '2025-10-29 11:41:45'),
(145, 6, '2025-10-08', '2025-10-08 12:30:00', 1, '2025-10-08 16:30:00', 1, 0, '2025-10-29 11:41:45'),
(146, 6, '2025-10-09', '2025-10-09 10:00:00', 1, '2025-10-09 13:00:00', 1, 0, '2025-10-29 11:41:45'),
(147, 6, '2025-10-10', '2025-10-10 11:30:00', 1, '2025-10-10 16:00:00', 1, 0, '2025-10-29 11:41:45'),
(148, 6, '2025-10-13', '2025-10-13 09:00:00', 1, '2025-10-13 11:30:00', 1, 0, '2025-10-29 11:41:45'),
(149, 6, '2025-10-14', '2025-10-14 13:00:00', 1, '2025-10-14 16:00:00', 1, 0, '2025-10-29 11:41:45'),
(150, 6, '2025-10-15', '2025-10-15 10:30:00', 1, '2025-10-15 15:30:00', 1, 0, '2025-10-29 11:41:45'),
(151, 6, '2025-10-16', '2025-10-16 09:00:00', 1, '2025-10-16 12:30:00', 1, 0, '2025-10-29 11:41:45'),
(152, 6, '2025-10-17', '2025-10-17 12:45:00', 1, '2025-10-17 16:45:00', 1, 0, '2025-10-29 11:41:45'),
(153, 6, '2025-10-20', '2025-10-20 11:00:00', 1, '2025-10-20 17:00:00', 1, 0, '2025-10-29 11:41:45'),
(154, 6, '2025-10-22', '2025-10-22 13:30:00', 1, '2025-10-22 16:00:00', 1, 0, '2025-10-29 11:41:45'),
(155, 6, '2025-10-24', '2025-10-24 09:30:00', 1, '2025-10-24 12:00:00', 1, 0, '2025-10-29 11:41:45'),
(156, 6, '2025-10-27', '2025-10-27 10:00:00', 1, '2025-10-27 16:00:00', 1, 0, '2025-10-29 11:41:45'),
(157, 6, '2025-10-30', '2025-10-30 12:30:00', 1, '2025-10-30 18:00:00', 0, 0, '2025-10-29 11:41:45'),
(188, 2, '2025-11-05', '2025-11-05 12:00:00', 0, '2025-11-05 15:00:00', 0, 0, '2025-11-12 23:22:35'),
(191, 2, '2025-11-19', '2025-11-19 09:15:00', 0, '2025-11-19 13:30:00', 0, 0, '2025-11-19 12:09:56'),
(193, 2, '2025-11-26', '2025-11-26 11:58:19', 0, NULL, 0, 0, '2025-11-26 11:58:19'),
(194, 2, '2025-09-16', '2025-09-16 09:00:00', 0, '2025-09-16 17:00:00', 0, 0, '2025-11-26 13:19:36'),
(195, 2, '2025-09-16', '2025-09-16 09:00:00', 0, '2025-09-16 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(196, 2, '2025-09-17', '2025-09-17 09:00:00', 0, '2025-09-17 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(197, 2, '2025-09-18', '2025-09-18 09:00:00', 0, '2025-09-18 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(198, 2, '2025-09-19', '2025-09-19 09:00:00', 0, '2025-09-19 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(199, 2, '2025-09-22', '2025-09-22 09:00:00', 0, '2025-09-22 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(200, 2, '2025-09-23', '2025-09-23 09:00:00', 0, '2025-09-23 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(201, 2, '2025-09-24', '2025-09-24 09:00:00', 0, '2025-09-24 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(202, 2, '2025-09-25', '2025-09-25 09:00:00', 0, '2025-09-25 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(203, 2, '2025-09-26', '2025-09-26 09:00:00', 0, '2025-09-26 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(204, 2, '2025-09-29', '2025-09-29 09:00:00', 0, '2025-09-29 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(205, 2, '2025-09-30', '2025-09-30 09:00:00', 0, '2025-09-30 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(206, 2, '2025-10-01', '2025-10-01 09:00:00', 0, '2025-10-01 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(207, 2, '2025-10-02', '2025-10-02 09:00:00', 0, '2025-10-02 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(208, 2, '2025-10-03', '2025-10-03 09:00:00', 0, '2025-10-03 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(209, 2, '2025-10-06', '2025-10-06 09:00:00', 0, '2025-10-06 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(210, 2, '2025-10-07', '2025-10-07 09:00:00', 0, '2025-10-07 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(211, 2, '2025-10-08', '2025-10-08 09:00:00', 0, '2025-10-08 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(212, 2, '2025-10-09', '2025-10-09 09:00:00', 0, '2025-10-09 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(213, 2, '2025-10-10', '2025-10-10 09:00:00', 0, '2025-10-10 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(214, 2, '2025-10-11', '2025-10-11 09:00:00', 0, '2025-10-11 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(215, 2, '2025-10-14', '2025-10-14 09:00:00', 0, '2025-10-14 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(216, 2, '2025-10-15', '2025-10-15 09:00:00', 0, '2025-10-15 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(217, 2, '2025-10-16', '2025-10-16 09:00:00', 0, '2025-10-16 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(218, 2, '2025-10-17', '2025-10-17 09:00:00', 0, '2025-10-17 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(219, 2, '2025-10-18', '2025-10-18 09:00:00', 0, '2025-10-18 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(220, 2, '2025-10-19', '2025-10-19 09:00:00', 0, '2025-10-19 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(221, 2, '2025-10-22', '2025-10-22 09:00:00', 0, '2025-10-22 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(222, 2, '2025-10-23', '2025-10-23 09:00:00', 0, '2025-10-23 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(223, 2, '2025-10-24', '2025-10-24 09:00:00', 0, '2025-10-24 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(224, 2, '2025-10-25', '2025-10-25 09:00:00', 0, '2025-10-25 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(225, 2, '2025-10-26', '2025-10-26 09:00:00', 0, '2025-10-26 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(226, 2, '2025-10-29', '2025-10-29 09:00:00', 0, '2025-10-29 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(227, 2, '2025-10-30', '2025-10-30 09:00:00', 0, '2025-10-30 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(228, 2, '2025-10-31', '2025-10-31 09:00:00', 0, '2025-10-31 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(229, 2, '2025-11-01', '2025-11-01 09:00:00', 0, '2025-11-01 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(230, 2, '2025-11-02', '2025-11-02 09:00:00', 0, '2025-11-02 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(231, 2, '2025-11-03', '2025-11-03 09:00:00', 0, '2025-11-03 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(232, 2, '2025-11-06', '2025-11-06 09:00:00', 0, '2025-11-06 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(233, 2, '2025-11-07', '2025-11-07 09:00:00', 0, '2025-11-07 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(234, 2, '2025-11-08', '2025-11-08 09:00:00', 0, '2025-11-08 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(235, 2, '2025-11-09', '2025-11-09 09:00:00', 0, '2025-11-09 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(236, 2, '2025-11-10', '2025-11-10 09:00:00', 0, '2025-11-10 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(237, 2, '2025-11-11', '2025-11-11 09:00:00', 0, '2025-11-11 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(238, 2, '2025-11-14', '2025-11-14 09:00:00', 0, '2025-11-14 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(239, 2, '2025-11-15', '2025-11-15 09:00:00', 0, '2025-11-15 17:00:00', 0, 0, '2025-11-26 13:25:59'),
(240, 2, '2025-08-18', '2025-08-18 09:00:00', 0, '2025-08-18 17:00:00', 0, 0, '2025-11-26 13:31:57'),
(241, 2, '2025-08-19', '2025-08-19 09:00:00', 0, '2025-08-19 17:00:00', 0, 0, '2025-11-26 13:31:57'),
(242, 2, '2025-08-20', '2025-08-20 09:00:00', 0, '2025-08-20 17:00:00', 0, 0, '2025-11-26 13:31:57'),
(243, 2, '2025-08-21', '2025-08-21 09:00:00', 0, '2025-08-21 17:00:00', 0, 0, '2025-11-26 13:31:57'),
(244, 2, '2025-08-22', '2025-08-22 09:00:00', 0, '2025-08-22 17:00:00', 0, 0, '2025-11-26 13:31:57'),
(245, 2, '2025-08-25', '2025-08-25 09:00:00', 0, '2025-08-25 17:00:00', 0, 0, '2025-11-26 13:31:57'),
(246, 2, '2025-08-26', '2025-08-26 09:00:00', 0, '2025-08-26 17:00:00', 0, 0, '2025-11-26 13:31:57'),
(247, 2, '2025-08-27', '2025-08-27 09:00:00', 0, '2025-08-27 17:00:00', 0, 0, '2025-11-26 13:31:57'),
(248, 2, '2025-08-28', '2025-08-28 09:00:00', 0, '2025-08-28 17:00:00', 0, 0, '2025-11-26 13:31:57'),
(249, 2, '2025-08-29', '2025-08-29 09:00:00', 0, '2025-08-29 17:00:00', 0, 0, '2025-11-26 13:31:57'),
(250, 2, '2025-09-01', '2025-09-01 09:00:00', 0, '2025-09-01 17:00:00', 0, 0, '2025-11-26 13:31:57'),
(251, 2, '2025-09-02', '2025-09-02 09:00:00', 0, '2025-09-02 17:00:00', 0, 0, '2025-11-26 13:31:57'),
(252, 2, '2025-09-03', '2025-09-03 09:00:00', 0, '2025-09-03 17:00:00', 0, 0, '2025-11-26 13:31:57'),
(253, 2, '2025-09-04', '2025-09-04 09:00:00', 0, '2025-09-04 17:00:00', 0, 0, '2025-11-26 13:31:57'),
(254, 2, '2025-09-05', '2025-09-05 09:00:00', 0, '2025-09-05 17:00:00', 0, 0, '2025-11-26 13:31:57'),
(255, 2, '2025-09-08', '2025-09-08 09:00:00', 0, '2025-09-08 17:00:00', 0, 0, '2025-11-26 13:31:57'),
(256, 2, '2025-09-09', '2025-09-09 09:00:00', 0, '2025-09-09 17:00:00', 0, 0, '2025-11-26 13:31:57'),
(257, 2, '2025-09-10', '2025-09-10 09:00:00', 0, '2025-09-10 17:00:00', 0, 0, '2025-11-26 13:31:57'),
(258, 2, '2025-09-11', '2025-09-11 09:00:00', 0, '2025-09-11 17:00:00', 0, 0, '2025-11-26 13:31:57'),
(259, 2, '2025-09-12', '2025-09-12 09:00:00', 0, '2025-09-12 17:00:00', 0, 0, '2025-11-26 13:31:57'),
(260, 2, '2025-09-15', '2025-09-15 09:00:00', 0, '2025-09-15 17:00:00', 0, 0, '2025-11-26 13:31:57');

-- --------------------------------------------------------

--
-- テーブルの構造 `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  `must_reset_password` tinyint(1) NOT NULL DEFAULT '0',
  `reset_token` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8_general_ci DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- テーブルのデータのダンプ `users`
--

INSERT INTO `users` (`id`, `name`, `password_hash`, `role`, `visible`, `must_reset_password`, `reset_token`, `reset_token_expires_at`, `created_at`) VALUES
(2, '山口　政佳', '$2y$10$i.GJQsCSZA59.VxKAfdeieECrDbsRxQvK0OMzpnUf/nVnZ.D6svXK', 'admin', 1, 0, NULL, NULL, '2025-06-11 17:56:26'),
(3, 'admin', '$2y$10$s3OtiJxrKHiMKomJkXd5p.8tAcWfOmOhBKyMqd/jZfvWqth6kqnPW', 'admin', 0, 0, NULL, NULL, '2025-06-18 10:55:32'),
(5, '小橋加英子', '$2y$10$nIHmpcrg4b3K46KJlaCBtO1zRcQWYuTVx2K5KoZF1EeHfJZ7AMspC', 'admin', 1, 0, NULL, NULL, '2025-06-18 13:06:36'),
(6, 'ma', '$2y$10$WqUXccMwN7ubKzcNv7z4l.UrMasNb.nt59WxaT/mqWbkFat7gw7Ja', 'user', 1, 0, NULL, NULL, '2025-10-01 10:16:13');

-- --------------------------------------------------------

--
-- テーブルの構造 `user_day_memos`
--

CREATE TABLE `user_day_memos` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `work_date` date NOT NULL,
  `memo_text` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- テーブルの構造 `user_detail`
--

CREATE TABLE `user_detail` (
  `user_id` int NOT NULL,
  `use_vehicle` tinyint(1) NOT NULL DEFAULT '1',
  `contract_hours_per_day` float DEFAULT NULL,
  `scheduled_weekly_days` tinyint DEFAULT NULL,
  `scheduled_weekly_hours` decimal(5,2) DEFAULT NULL,
  `scheduled_annual_days` smallint DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `retire_date` date DEFAULT NULL,
  `full_time` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- テーブルのデータのダンプ `user_detail`
--

INSERT INTO `user_detail` (`user_id`, `use_vehicle`, `contract_hours_per_day`, `scheduled_weekly_days`, `scheduled_weekly_hours`, `scheduled_annual_days`, `hire_date`, `retire_date`, `full_time`, `created_at`) VALUES
(2, 1, 8, NULL, NULL, NULL, '2015-07-04', NULL, 1, '2025-06-11 17:56:26'),
(3, 1, 8, NULL, NULL, NULL, NULL, NULL, 1, '2025-06-18 10:55:32'),
(5, 0, 8, NULL, NULL, NULL, NULL, NULL, 1, '2025-06-18 13:06:36'),
(6, 1, NULL, NULL, NULL, NULL, '2025-04-01', NULL, 0, '2025-10-01 10:16:13');

-- --------------------------------------------------------

--
-- テーブルの構造 `user_leave_settings`
--

CREATE TABLE `user_leave_settings` (
  `user_id` int NOT NULL,
  `default_unit_id` int DEFAULT NULL,
  `allow_half_day` tinyint(1) DEFAULT NULL,
  `allow_hourly` tinyint(1) DEFAULT NULL,
  `base_hours_per_day_override` float DEFAULT NULL,
  `carryover_months` int DEFAULT NULL,
  `carryover_max_minutes` int DEFAULT NULL,
  `negative_balance_allowed` tinyint(1) DEFAULT NULL,
  `default_paid_leave_type_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- テーブルのデータのダンプ `user_leave_settings`
--

INSERT INTO `user_leave_settings` (`user_id`, `default_unit_id`, `allow_half_day`, `allow_hourly`, `base_hours_per_day_override`, `carryover_months`, `carryover_max_minutes`, `negative_balance_allowed`, `default_paid_leave_type_id`, `created_at`) VALUES
(2, 1, 1, 1, NULL, 24, 0, 0, 1, '2025-10-28 14:18:21'),
(6, 1, 1, 1, 0, 24, 0, 0, 1, '2025-10-29 18:18:30');

-- --------------------------------------------------------

--
-- テーブルの構造 `user_leave_summary`
--

CREATE TABLE `user_leave_summary` (
  `user_id` int NOT NULL,
  `balance_hours` decimal(8,2) NOT NULL DEFAULT '0.00',
  `used_total_hours` decimal(8,2) NOT NULL DEFAULT '0.00',
  `next_expire_date` date DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `user_leave_summary`
--

INSERT INTO `user_leave_summary` (`user_id`, `balance_hours`, `used_total_hours`, `next_expire_date`) VALUES
(2, '320.00', '0.00', '2025-12-03'),
(3, '0.00', '0.00', NULL),
(5, '10.00', '0.00', '2029-10-01'),
(6, '12.00', '0.00', '2027-09-30');

-- --------------------------------------------------------

--
-- ビュー用の構造 `day_status_effective`
--
DROP TABLE IF EXISTS `day_status_effective`;

CREATE ALGORITHM=UNDEFINED DEFINER=`ZwKL084`@`%` SQL SECURITY DEFINER VIEW `day_status_effective`  AS SELECT coalesce(`o`.`user_id`,`ds`.`user_id`) AS `user_id`, coalesce(`o`.`date`,`ds`.`date`) AS `date`, coalesce((case `o`.`status` when 'off_full' then 'off' when 'off_am' then 'am_off' when 'off_pm' then 'pm_off' when 'ignore' then 'ignore' else NULL end),`ds`.`status`,'work') AS `status`, (case when (`o`.`user_id` is not null) then 'override' else 'baseline' end) AS `source`, coalesce(`o`.`note`,`ds`.`note`) AS `note` FROM (`day_status` `ds` left join (select `day_status_overrides`.`user_id` AS `user_id`,`day_status_overrides`.`date` AS `date`,substring_index(group_concat(`day_status_overrides`.`status` order by `day_status_overrides`.`id` DESC separator ','),',',1) AS `status`,substring_index(group_concat(coalesce(`day_status_overrides`.`note`,'') order by `day_status_overrides`.`id` DESC separator ','),',',1) AS `note` from `day_status_overrides` where (`day_status_overrides`.`revoked_at` is null) group by `day_status_overrides`.`user_id`,`day_status_overrides`.`date`) `o` on(((`o`.`user_id` = `ds`.`user_id`) and (`o`.`date` = `ds`.`date`)))) ;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`key`);

--
-- テーブルのインデックス `attendance_period_locks`
--
ALTER TABLE `attendance_period_locks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_apl_user_dates` (`user_id`,`start_date`,`end_date`),
  ADD KEY `idx_apl_status` (`status`);

--
-- テーブルのインデックス `attendance_statuses`
--
ALTER TABLE `attendance_statuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attendance_statuses_code_unique` (`code`);

--
-- テーブルのインデックス `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_actor_time` (`actor_user_id`,`created_at`);

--
-- テーブルのインデックス `breaks`
--
ALTER TABLE `breaks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `timecard_id` (`timecard_id`),
  ADD KEY `idx_breaks_timecard_active` (`timecard_id`);

--
-- テーブルのインデックス `day_status`
--
ALTER TABLE `day_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_date` (`user_id`,`date`),
  ADD KEY `date` (`date`),
  ADD KEY `user_id` (`user_id`);

--
-- テーブルのインデックス `day_status_overrides`
--
ALTER TABLE `day_status_overrides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_date` (`user_id`,`date`),
  ADD KEY `idx_active` (`user_id`,`date`,`revoked_at`),
  ADD KEY `fk_dso_created_by` (`created_by`);

--
-- テーブルのインデックス `leave_expire_runs`
--
ALTER TABLE `leave_expire_runs`
  ADD PRIMARY KEY (`run_date`);

--
-- テーブルのインデックス `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `approve_token` (`approve_token`),
  ADD KEY `status` (`status`),
  ADD KEY `approve_token_2` (`approve_token`),
  ADD KEY `idx_leave_requests_approve_token_hash` (`approve_token_hash`);

--
-- テーブルのインデックス `leave_request_audit`
--
ALTER TABLE `leave_request_audit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lra_request_id` (`request_id`),
  ADD KEY `idx_lra_action` (`action`),
  ADD KEY `idx_lra_actor_type` (`actor_type`);

--
-- テーブルのインデックス `leave_statuses`
--
ALTER TABLE `leave_statuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_statuses_code_unique` (`code`);

--
-- テーブルのインデックス `leave_units`
--
ALTER TABLE `leave_units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_units_code_unique` (`code`);

--
-- テーブルのインデックス `log_types`
--
ALTER TABLE `log_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `log_types_code_unique` (`code`);

--
-- テーブルのインデックス `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`);

--
-- テーブルのインデックス `paid_leaves`
--
ALTER TABLE `paid_leaves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- テーブルのインデックス `paid_leave_logs`
--
ALTER TABLE `paid_leave_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `paid_leave_id` (`paid_leave_id`),
  ADD KEY `paid_leave_logs_log_type_id_index` (`log_type_id`),
  ADD KEY `idx_paid_leave_logs_event_id` (`event_id`);

--
-- テーブルのインデックス `paid_leave_types`
--
ALTER TABLE `paid_leave_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `paid_leave_types_code_unique` (`code`),
  ADD KEY `paid_leave_types_default_unit_id_index` (`default_unit_id`);

--
-- テーブルのインデックス `paid_leave_use_events`
--
ALTER TABLE `paid_leave_use_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_plue_user_date` (`user_id`,`used_date`);

--
-- テーブルのインデックス `request_rate_limit`
--
ALTER TABLE `request_rate_limit`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_ip_ep` (`ip`,`endpoint`);

--
-- テーブルのインデックス `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `source_types`
--
ALTER TABLE `source_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `source_types_code_unique` (`code`),
  ADD KEY `source_types_paid_leave_type_id_index` (`paid_leave_type_id`);

--
-- テーブルのインデックス `timecards`
--
ALTER TABLE `timecards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_timecards_user_date_active` (`user_id`,`work_date`);

--
-- テーブルのインデックス `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `user_day_memos`
--
ALTER TABLE `user_day_memos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_date` (`user_id`,`work_date`),
  ADD KEY `idx_user` (`user_id`);

--
-- テーブルのインデックス `user_detail`
--
ALTER TABLE `user_detail`
  ADD PRIMARY KEY (`user_id`);

--
-- テーブルのインデックス `user_leave_settings`
--
ALTER TABLE `user_leave_settings`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `user_leave_settings_default_unit_id_index` (`default_unit_id`),
  ADD KEY `user_leave_settings_default_paid_leave_type_id_index` (`default_paid_leave_type_id`);

--
-- テーブルのインデックス `user_leave_summary`
--
ALTER TABLE `user_leave_summary`
  ADD PRIMARY KEY (`user_id`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `attendance_period_locks`
--
ALTER TABLE `attendance_period_locks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `attendance_statuses`
--
ALTER TABLE `attendance_statuses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- テーブルの AUTO_INCREMENT `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- テーブルの AUTO_INCREMENT `breaks`
--
ALTER TABLE `breaks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=178;

--
-- テーブルの AUTO_INCREMENT `day_status`
--
ALTER TABLE `day_status`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `day_status_overrides`
--
ALTER TABLE `day_status_overrides`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- テーブルの AUTO_INCREMENT `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- テーブルの AUTO_INCREMENT `leave_request_audit`
--
ALTER TABLE `leave_request_audit`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- テーブルの AUTO_INCREMENT `leave_statuses`
--
ALTER TABLE `leave_statuses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- テーブルの AUTO_INCREMENT `leave_units`
--
ALTER TABLE `leave_units`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- テーブルの AUTO_INCREMENT `log_types`
--
ALTER TABLE `log_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- テーブルの AUTO_INCREMENT `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- テーブルの AUTO_INCREMENT `paid_leaves`
--
ALTER TABLE `paid_leaves`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- テーブルの AUTO_INCREMENT `paid_leave_logs`
--
ALTER TABLE `paid_leave_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- テーブルの AUTO_INCREMENT `paid_leave_types`
--
ALTER TABLE `paid_leave_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- テーブルの AUTO_INCREMENT `paid_leave_use_events`
--
ALTER TABLE `paid_leave_use_events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- テーブルの AUTO_INCREMENT `request_rate_limit`
--
ALTER TABLE `request_rate_limit`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- テーブルの AUTO_INCREMENT `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- テーブルの AUTO_INCREMENT `source_types`
--
ALTER TABLE `source_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- テーブルの AUTO_INCREMENT `timecards`
--
ALTER TABLE `timecards`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=261;

--
-- テーブルの AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- テーブルの AUTO_INCREMENT `user_day_memos`
--
ALTER TABLE `user_day_memos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `breaks`
--
ALTER TABLE `breaks`
  ADD CONSTRAINT `breaks_ibfk_1` FOREIGN KEY (`timecard_id`) REFERENCES `timecards` (`id`);

--
-- テーブルの制約 `day_status_overrides`
--
ALTER TABLE `day_status_overrides`
  ADD CONSTRAINT `fk_dso_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_dso_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `paid_leaves`
--
ALTER TABLE `paid_leaves`
  ADD CONSTRAINT `paid_leaves_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- テーブルの制約 `paid_leave_logs`
--
ALTER TABLE `paid_leave_logs`
  ADD CONSTRAINT `paid_leave_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `paid_leave_logs_ibfk_2` FOREIGN KEY (`paid_leave_id`) REFERENCES `paid_leaves` (`id`),
  ADD CONSTRAINT `paid_leave_logs_log_type_id_foreign` FOREIGN KEY (`log_type_id`) REFERENCES `log_types` (`id`);

--
-- テーブルの制約 `paid_leave_types`
--
ALTER TABLE `paid_leave_types`
  ADD CONSTRAINT `paid_leave_types_default_unit_id_foreign` FOREIGN KEY (`default_unit_id`) REFERENCES `leave_units` (`id`);

--
-- テーブルの制約 `source_types`
--
ALTER TABLE `source_types`
  ADD CONSTRAINT `source_types_paid_leave_type_id_foreign` FOREIGN KEY (`paid_leave_type_id`) REFERENCES `paid_leave_types` (`id`);

--
-- テーブルの制約 `timecards`
--
ALTER TABLE `timecards`
  ADD CONSTRAINT `timecards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- テーブルの制約 `user_day_memos`
--
ALTER TABLE `user_day_memos`
  ADD CONSTRAINT `fk_user_day_memos_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `user_detail`
--
ALTER TABLE `user_detail`
  ADD CONSTRAINT `fk_user_detail_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- テーブルの制約 `user_leave_settings`
--
ALTER TABLE `user_leave_settings`
  ADD CONSTRAINT `fk_user_leave_settings_default_type` FOREIGN KEY (`default_paid_leave_type_id`) REFERENCES `paid_leave_types` (`id`),
  ADD CONSTRAINT `fk_user_leave_settings_default_unit` FOREIGN KEY (`default_unit_id`) REFERENCES `leave_units` (`id`),
  ADD CONSTRAINT `fk_user_leave_settings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- テーブルの制約 `user_leave_summary`
--
ALTER TABLE `user_leave_summary`
  ADD CONSTRAINT `fk_user_leave_summary_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
