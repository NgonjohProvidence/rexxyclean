-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 31, 2026 at 08:25 PM
-- Server version: 8.0.27
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bexxy_cleaning`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_login_attempts`
--

CREATE TABLE `admin_login_attempts` (
  `attempt_key` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempt_count` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `window_started_at` datetime NOT NULL,
  `locked_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_login_attempts`
--

INSERT INTO `admin_login_attempts` (`attempt_key`, `attempt_count`, `window_started_at`, `locked_until`) VALUES
('23bbe685ebce5e1227279175dd3619c4ce051ac048b8cffe740ef49ef73d73ab', 2, '2026-10-31 19:51:50', NULL),
('c0d8c9d7ba6c3797ee3b46bfbb3e9765eb284be29ef919cf6261d585c4a60a2c', 1, '2026-10-31 20:23:12', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `booking_requests`
--

CREATE TABLE `booking_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `service` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rooms` tinyint UNSIGNED NOT NULL,
  `estimated_hours` decimal(4,1) DEFAULT NULL,
  `cleaner_count` tinyint UNSIGNED DEFAULT NULL,
  `preferred_date` date NOT NULL,
  `arrival_time` time NOT NULL,
  `extra_services` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `estimated_total` decimal(12,2) DEFAULT NULL,
  `currency_code` char(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(254) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_address` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `internal_notes` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','contacted','confirmed','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking_requests`
--

INSERT INTO `booking_requests` (`id`, `service`, `rooms`, `estimated_hours`, `cleaner_count`, `preferred_date`, `arrival_time`, `extra_services`, `estimated_total`, `currency_code`, `full_name`, `email`, `phone`, `service_address`, `notes`, `internal_notes`, `status`, `created_at`, `updated_at`) VALUES
(2, 'Deep cleaning', 2, 1.5, 1, '2026-10-29', '10:00:00', '[\"Fridge cleaning\",\"Interior windows\"]', 82.50, 'CAD', 'Ngonjoh Providence', 'empprov001@gmail.com', '+237673263533', 'opposite, stade camtel, Bepanda', 'nothing', '', 'contacted', '2026-10-29 05:23:36', '2026-10-31 19:15:04'),
(3, 'Regular maintenance', 1, 0.5, 1, '2026-11-01', '12:00:00', '[]', 20.00, 'CAD', 'Ngonjoh Providence', 'empprov001@gmail.com', '+237673263533', 'opposite, stade camtel, Bepanda', 'ok', '', 'pending', '2026-10-31 19:01:55', '2026-10-31 19:01:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_login_attempts`
--
ALTER TABLE `admin_login_attempts`
  ADD PRIMARY KEY (`attempt_key`),
  ADD KEY `idx_admin_login_attempts_window` (`window_started_at`);

--
-- Indexes for table `booking_requests`
--
ALTER TABLE `booking_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_booking_requests_date` (`preferred_date`),
  ADD KEY `idx_booking_requests_status_created` (`status`,`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `booking_requests`
--
ALTER TABLE `booking_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
