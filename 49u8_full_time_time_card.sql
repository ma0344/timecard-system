-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- ホスト: mysql8008.in.shared-server.net:13654
-- 生成日時: 2025 年 10 月 22 日 10:51
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
(76, 78, '2025-05-19 12:00:00', 0, '2025-05-19 13:00:00', 0, '2025-09-02 11:34:32');

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
  `expire_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `paid_leave_logs`
--

CREATE TABLE `paid_leave_logs` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `paid_leave_id` int DEFAULT NULL,
  `used_date` date NOT NULL,
  `used_hours` float NOT NULL,
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `settings`
--

INSERT INTO `settings` (`id`, `period_start`, `period_end`, `rounding_type`, `rounding_unit`, `work_hours`, `work_minutes`, `legal_hours_28`, `legal_hours_29`, `legal_hours_30`, `legal_hours_31`, `updated_at`) VALUES
(1, 16, 15, 'ceil', 15, 8, 0, 160, 165, 171, 177, '2025-09-02 10:45:18');

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
(78, 2, '2025-05-19', '2025-05-19 09:00:00', 0, '2025-05-19 18:00:00', 0, 0, '2025-09-02 11:34:32');

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
  `contract_hours_per_day` float NOT NULL DEFAULT '8',
  `full_time` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- テーブルのデータのダンプ `user_detail`
--

INSERT INTO `user_detail` (`user_id`, `use_vehicle`, `contract_hours_per_day`, `full_time`, `created_at`) VALUES
(2, 1, 8, 1, '2025-06-11 17:56:26'),
(3, 1, 7.5, 1, '2025-06-18 10:55:32'),
(5, 0, 8, 1, '2025-06-18 13:06:36'),
(6, 1, 8, 1, '2025-10-01 10:16:13');

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
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `attendance_statuses`
--
ALTER TABLE `attendance_statuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attendance_statuses_code_unique` (`code`);

--
-- テーブルのインデックス `breaks`
--
ALTER TABLE `breaks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `timecard_id` (`timecard_id`);

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
  ADD KEY `paid_leave_id` (`paid_leave_id`);

--
-- テーブルのインデックス `paid_leave_types`
--
ALTER TABLE `paid_leave_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `paid_leave_types_code_unique` (`code`),
  ADD KEY `paid_leave_types_default_unit_id_index` (`default_unit_id`);

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
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `attendance_statuses`
--
ALTER TABLE `attendance_statuses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- テーブルの AUTO_INCREMENT `breaks`
--
ALTER TABLE `breaks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `paid_leave_logs`
--
ALTER TABLE `paid_leave_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `paid_leave_types`
--
ALTER TABLE `paid_leave_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- テーブルの AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  ADD CONSTRAINT `paid_leave_logs_ibfk_2` FOREIGN KEY (`paid_leave_id`) REFERENCES `paid_leaves` (`id`);

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
  ADD CONSTRAINT `fk_user_leave_settings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_leave_settings_default_unit` FOREIGN KEY (`default_unit_id`) REFERENCES `leave_units` (`id`),
  ADD CONSTRAINT `fk_user_leave_settings_default_type` FOREIGN KEY (`default_paid_leave_type_id`) REFERENCES `paid_leave_types` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
