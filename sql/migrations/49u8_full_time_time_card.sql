-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- ホスト: mysql8008.in.shared-server.net:13654
-- 生成日時: 2025 年 11 月 02 日 13:38
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

--
-- テーブルのデータのダンプ `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `actor_user_id`, `target_user_id`, `action`, `details`, `created_at`) VALUES
(1, 2, 2, 'paid_leave_use_create', '{\"reason\": \"年休\", \"event_id\": \"1\", \"used_date\": \"2025-09-10\", \"used_hours\": \"7\"}', '2025-10-27 18:35:22'),
(2, 2, 2, 'paid_leave_use_create', '{\"reason\": \"\", \"event_id\": \"2\", \"used_date\": \"2025-09-17\", \"used_hours\": \"7\"}', '2025-10-27 18:37:36'),
(3, 2, 2, 'paid_leave_use_create', '{\"reason\": \"\", \"event_id\": \"3\", \"used_date\": \"2025-09-16\", \"used_hours\": \"3.5\"}', '2025-10-27 18:38:19'),
(4, 2, 2, 'paid_leave_use_create', '{\"reason\": \"\", \"event_id\": \"4\", \"used_date\": \"2025-09-23\", \"used_hours\": \"7\"}', '2025-10-27 18:38:26'),
(5, 2, 2, 'paid_leave_use_create', '{\"reason\": \"\", \"event_id\": \"5\", \"used_date\": \"2025-09-03\", \"used_hours\": \"7\"}', '2025-10-27 18:39:24'),
(6, 2, 2, 'paid_leave_use_event_delete', '{\"event_id\": \"5\"}', '2025-10-27 18:40:12'),
(7, 2, 2, 'paid_leave_use_create', '{\"reason\": \"\", \"event_id\": \"6\", \"used_date\": \"2025-09-03\", \"used_hours\": \"7\"}', '2025-10-27 18:41:22'),
(8, 2, 2, 'paid_leave_use_event_delete', '{\"event_id\": \"6\"}', '2025-10-27 18:42:54'),
(9, 2, 2, 'paid_leave_use_create', '{\"reason\": \"\", \"event_id\": \"7\", \"used_date\": \"2025-08-06\", \"used_hours\": \"7\"}', '2025-10-27 18:46:00'),
(10, 2, 2, 'paid_leave_use_create', '{\"reason\": \"\", \"event_id\": \"8\", \"used_date\": \"2025-08-15\", \"used_hours\": \"7\"}', '2025-10-27 18:54:43'),
(11, 2, 2, 'paid_leave_use_event_delete', '{\"event_id\": \"1\"}', '2025-10-27 18:57:19'),
(12, 2, 2, 'paid_leave_use_event_delete', '{\"event_id\": \"8\"}', '2025-10-27 18:57:25'),
(13, 2, 2, 'paid_leave_use_create', '{\"reason\": \"\", \"event_id\": \"9\", \"used_date\": \"2025-10-06\", \"used_hours\": \"7\"}', '2025-10-27 19:07:47'),
(14, 2, 2, 'paid_leave_use_event_delete', '{\"event_id\": \"3\"}', '2025-10-27 19:26:07'),
(15, 2, 2, 'paid_leave_use_create', '{\"reason\": \"\", \"event_id\": \"10\", \"used_date\": \"2025-10-07\", \"used_hours\": \"3.5\"}', '2025-10-27 19:45:37'),
(16, 2, 2, 'paid_leave_use_event_delete', '{\"event_id\": \"7\"}', '2025-10-27 19:47:41'),
(17, 2, 2, 'paid_leave_use_event_delete', '{\"event_id\": \"10\"}', '2025-10-27 19:48:30'),
(18, 2, 2, 'paid_leave_use_event_delete', '{\"event_id\": \"9\"}', '2025-10-27 19:48:32'),
(19, 2, 2, 'paid_leave_use_event_delete', '{\"event_id\": \"2\"}', '2025-10-27 19:48:38'),
(20, 2, 2, 'paid_leave_use_event_delete', '{\"event_id\": \"4\"}', '2025-10-27 19:48:39'),
(21, 2, 2, 'paid_leave_use_create', '{\"reason\": \"\", \"event_id\": \"11\", \"used_date\": \"2025-08-01\", \"used_hours\": \"24\"}', '2025-10-27 19:49:34'),
(22, 2, 2, 'paid_leave_use_create', '{\"reason\": \"\", \"event_id\": \"12\", \"used_date\": \"2025-09-01\", \"used_hours\": \"24\"}', '2025-10-27 19:49:46'),
(23, 2, 2, 'paid_leave_use_event_delete', '{\"event_id\": \"11\"}', '2025-11-02 13:00:34'),
(24, 2, 2, 'paid_leave_use_event_delete', '{\"event_id\": \"12\"}', '2025-11-02 13:00:38');

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
(14, 7, '2025-06-09 12:15:59', 0, '2025-06-09 13:15:48', 0, '2025-06-12 11:39:24'),
(33, 5, '2025-06-11 10:40:46', 0, '2025-06-11 11:15:20', 0, '2025-06-13 10:43:40'),
(34, 5, '2025-06-11 13:24:46', 0, '2025-06-11 14:05:02', 0, '2025-06-13 10:43:41'),
(54, 29, '2025-06-14 11:30:00', 0, '2025-06-14 12:30:00', 0, '2025-06-16 12:41:11'),
(56, 32, '2025-06-16 12:52:46', 0, '2025-06-16 14:57:30', 0, '2025-06-17 12:44:47'),
(60, 18, '2025-06-08 10:33:38', 0, '2025-06-08 11:01:14', 0, '2025-06-19 12:38:25'),
(61, 18, '2025-06-08 15:51:46', 0, '2025-06-08 16:24:19', 0, '2025-06-19 12:38:25'),
(69, 70, '2025-07-02 12:00:00', 1, '2025-07-02 13:00:00', 1, '2025-07-02 11:48:55'),
(70, 70, '2025-07-02 14:00:00', 1, '2025-07-02 14:30:00', 1, '2025-07-02 11:49:41'),
(71, 71, '2025-07-09 12:00:00', 1, '2025-07-09 13:00:00', 1, '2025-07-09 11:40:48'),
(73, 72, '2025-07-30 12:00:00', 0, '2025-07-30 13:00:00', 0, '2025-08-20 10:14:38'),
(74, 74, '2025-08-08 12:00:00', 0, '2025-08-08 13:00:00', 0, '2025-08-20 10:15:03'),
(75, 77, '2025-05-16 12:00:00', 0, '2025-05-16 13:00:00', 0, '2025-09-02 11:34:00'),
(76, 78, '2025-05-19 12:00:00', 0, '2025-05-19 13:00:00', 0, '2025-09-02 11:34:32'),
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
(96, 118, '2025-07-03 12:00:00', 1, '2025-07-03 13:00:00', 1, '2025-10-29 11:41:45'),
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
(114, 156, '2025-10-27 12:00:00', 1, '2025-10-27 13:00:00', 1, '2025-10-29 11:41:45');

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
(14, 6, '2025-10-01', 12, '0.00', '2027-09-30'),
(16, 2, '2015-12-04', 80, '0.00', '2017-12-03'),
(17, 2, '2016-12-04', 80, '0.00', '2018-12-03'),
(18, 2, '2017-12-04', 88, '0.00', '2019-12-03'),
(19, 2, '2018-12-04', 96, '0.00', '2020-12-03'),
(20, 2, '2019-12-04', 112, '0.00', '2021-12-03'),
(21, 2, '2020-12-04', 128, '0.00', '2022-12-03'),
(22, 2, '2021-12-04', 144, '0.00', '2023-12-03'),
(23, 2, '2022-12-04', 160, '0.00', '2024-12-03'),
(24, 2, '2023-12-04', 160, '0.00', '2025-12-03'),
(25, 2, '2024-12-04', 160, '0.00', '2026-12-03');

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
(1, 16, 15, 'ceil', 15, 8, 0, 160, 165, 171, 177, 24, '{\"fulltime\": [10, 11, 12, 14, 16, 18, 20], \"parttime\": {\"1d\": [1, 2, 2, 2, 3, 3, 3], \"2d\": [3, 4, 4, 5, 6, 6, 7], \"3d\": [5, 6, 6, 7, 9, 10, 11], \"4d\": [7, 8, 9, 10, 12, 13, 15]}, \"milestones\": [\"6m\", \"1y6m\", \"2y6m\", \"3y6m\", \"4y6m\", \"5y6m\", \"6y6m+\"]}', '2025-11-01 11:11:00');

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
(5, 2, '2025-06-11', '2025-06-11 09:00:00', 0, '2025-06-11 18:00:00', 0, 0, '2025-06-11 19:02:33'),
(7, 2, '2025-06-09', '2025-06-09 08:55:50', 0, '2025-06-09 18:13:18', 0, 0, '2025-06-12 11:14:51'),
(18, 2, '2025-06-08', '2025-06-08 08:54:00', 0, '2025-06-08 19:00:00', 0, 0, '2025-06-12 11:35:01'),
(21, 2, '2025-06-10', '2025-06-10 09:00:00', 1, '2025-06-10 18:00:00', 1, 0, '2025-06-13 17:36:36'),
(29, 2, '2025-06-14', '2025-06-14 09:00:00', 1, '2025-06-14 17:20:00', 0, 0, '2025-06-14 12:32:14'),
(30, 2, '2025-06-07', '2025-06-07 09:00:00', 0, '2025-06-07 18:00:00', 0, 0, '2025-06-15 19:13:47'),
(31, 2, '2025-06-13', '2025-06-13 09:00:00', 0, '2025-06-13 17:00:00', 0, 0, '2025-06-15 19:14:14'),
(32, 2, '2025-06-16', '2025-06-16 12:40:00', 0, '2025-06-16 18:00:00', 0, 0, '2025-06-16 12:40:42'),
(37, 2, '2025-06-05', '2025-06-05 09:00:00', 0, '2025-06-05 17:00:00', 0, 0, '2025-06-16 15:01:05'),
(38, 2, '2025-06-06', '2025-06-06 09:00:00', 0, '2025-06-06 18:00:00', 0, 0, '2025-06-16 15:01:31'),
(47, 2, '2025-06-18', '2025-06-18 14:11:00', 0, '2025-06-18 18:00:00', 0, 0, '2025-06-18 14:11:33'),
(48, 2, '2025-06-19', '2025-06-19 09:00:00', 0, '2025-06-19 16:58:44', 0, 0, '2025-06-19 13:05:15'),
(70, 2, '2025-07-02', '2025-07-02 09:00:00', 1, '2025-07-02 18:00:00', 1, 0, '2025-07-02 11:48:44'),
(71, 2, '2025-07-09', '2025-07-09 09:00:00', 1, '2025-07-09 18:00:00', 1, 0, '2025-07-09 11:32:45'),
(72, 2, '2025-07-30', '2025-07-30 09:00:00', 1, '2025-07-30 18:00:00', 0, 0, '2025-07-30 09:11:49'),
(73, 2, '2025-06-20', '2025-06-20 09:00:00', 0, '2025-06-20 18:00:00', 0, 0, '2025-07-30 10:09:00'),
(74, 2, '2025-08-08', '2025-08-08 09:00:00', 1, '2025-08-08 18:00:00', 0, 0, '2025-08-08 09:37:55'),
(75, 2, '2025-08-27', '2025-08-27 09:00:00', 1, '2025-08-27 13:00:00', 0, 0, '2025-08-27 10:06:53'),
(76, 2, '2025-09-02', '2025-09-02 09:00:00', 1, NULL, 0, 0, '2025-09-02 11:04:58'),
(77, 2, '2025-05-16', '2025-05-16 09:00:00', 0, '2025-05-16 18:00:00', 0, 0, '2025-09-02 11:33:42'),
(78, 2, '2025-05-19', '2025-05-19 09:00:00', 0, '2025-05-19 18:00:00', 0, 0, '2025-09-02 11:34:32'),
(80, 5, '2025-10-22', '2025-10-22 09:00:00', 0, '2025-10-22 18:00:00', 0, 0, '2025-10-22 13:00:11'),
(81, 5, '2025-10-21', '2025-10-21 09:00:00', 0, '2025-10-21 15:00:00', 0, 0, '2025-10-22 13:46:51'),
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
(157, 6, '2025-10-30', '2025-10-30 12:30:00', 1, '2025-10-30 15:00:00', 1, 0, '2025-10-29 11:41:45');

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
(3, 'admin', '$2y$10$s3OtiJxrKHiMKomJkXd5p.8tAcWfOmOhBKyMqd/jZfvWqth6kqnPW', 'admin', 1, 0, NULL, NULL, '2025-06-18 10:55:32'),
(5, '小橋加英子', '$2y$10$nIHmpcrg4b3K46KJlaCBtO1zRcQWYuTVx2K5KoZF1EeHfJZ7AMspC', 'admin', 1, 0, NULL, NULL, '2025-06-18 13:06:36'),
(6, 'ma', '$2y$10$WqUXccMwN7ubKzcNv7z4l.UrMasNb.nt59WxaT/mqWbkFat7gw7Ja', 'user', 1, 0, NULL, NULL, '2025-10-01 10:16:13');

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

--
-- ダンプしたテーブルのインデックス
--

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
  ADD KEY `timecard_id` (`timecard_id`);

--
-- テーブルのインデックス `leave_expire_runs`
--
ALTER TABLE `leave_expire_runs`
  ADD PRIMARY KEY (`run_date`);

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
  ADD KEY `user_id` (`user_id`);

--
-- テーブルのインデックス `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=140;

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
-- テーブルの AUTO_INCREMENT `paid_leaves`
--
ALTER TABLE `paid_leaves`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=158;

--
-- テーブルの AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `breaks`
--
ALTER TABLE `breaks`
  ADD CONSTRAINT `breaks_ibfk_1` FOREIGN KEY (`timecard_id`) REFERENCES `timecards` (`id`);

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
