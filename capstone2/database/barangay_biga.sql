-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 27, 2025 at 01:34 PM
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
-- Database: `barangay_biga`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `module` varchar(50) NOT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('success','failed','pending') DEFAULT 'success',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `module`, `action`, `description`, `status`, `ip_address`, `created_at`) VALUES
(15, 8, '', 'registration', 'New user registration', 'success', '::1', '2025-06-14 03:23:12'),
(16, 8, '', 'login', 'User logged in successfully', 'success', '::1', '2025-06-14 03:23:30'),
(17, 8, '', 'profile_picture_update', 'Profile picture updated', 'success', '::1', '2025-06-14 03:23:41'),
(18, 8, '', 'profile_picture_updated', 'Updated profile picture', 'success', '::1', '2025-06-14 03:23:41'),
(19, 9, '', 'registration', 'New user registration', 'success', '::1', '2025-06-14 03:24:46'),
(20, 9, '', 'login', 'User logged in successfully', 'success', '::1', '2025-06-14 03:25:03'),
(21, 9, '', 'logout', 'User logged out', 'success', '::1', '2025-06-14 03:30:30'),
(22, 9, '', 'logout', 'User logged out', 'success', '::1', '2025-06-14 03:30:30'),
(23, 9, '', 'login', 'User logged in successfully', 'success', '::1', '2025-06-14 03:30:35'),
(24, 9, '', 'profile_picture_update', 'Profile picture updated', 'success', '::1', '2025-06-14 03:33:11'),
(25, 9, '', 'profile_picture_updated', 'Updated profile picture', 'success', '::1', '2025-06-14 03:33:11'),
(26, 9, '', 'profile_updated', 'Updated profile information', 'success', '::1', '2025-06-14 03:33:31'),
(27, 9, '', 'appointment_created', 'Created new appointment #1', 'success', '::1', '2025-06-14 03:46:32'),
(28, 8, '', 'appointment_updated', 'Updated appointment #1 status to approved', 'success', '::1', '2025-06-14 03:47:00'),
(29, 9, '', 'appointment_created', 'Created new appointment #2', 'success', '::1', '2025-06-14 03:56:19'),
(30, 8, '', 'appointment_updated', 'Updated appointment #2 status to approved', 'success', '::1', '2025-06-14 03:56:29'),
(31, 9, '', 'appointment_created', 'Created new appointment #3', 'success', '::1', '2025-06-14 03:57:29'),
(32, 8, '', 'appointment_updated', 'Updated appointment #3 status to approved', 'success', '::1', '2025-06-14 03:57:54'),
(33, 9, '', 'appointment_created', 'Created new appointment #4', 'success', '::1', '2025-06-14 04:00:16'),
(34, 8, '', 'appointment_updated', 'Updated appointment #4 status to approved', 'success', '::1', '2025-06-14 04:00:20'),
(35, 9, '', 'profile_updated', 'Updated profile information', 'success', '::1', '2025-06-14 04:06:40'),
(39, 9, '', 'logout', 'User logged out', 'success', '::1', '2025-06-14 04:54:45'),
(40, 9, '', 'logout', 'User logged out', 'success', '::1', '2025-06-14 04:54:45'),
(41, 13, '', 'registration', 'New user registration', 'success', '::1', '2025-06-14 04:55:43'),
(42, 9, '', 'login', 'User logged in successfully', 'success', '::1', '2025-06-14 04:56:00'),
(43, 14, '', 'registration', 'New user registration', 'success', '::1', '2025-06-14 04:59:14'),
(45, 8, '', 'announcement_created', 'Created announcement: Barangay Assembly Day', 'success', '::1', '2025-06-14 05:43:51'),
(46, 9, '', 'logout', 'User logged out', 'success', '::1', '2025-06-14 07:27:07'),
(47, 9, '', 'logout', 'User logged out', 'success', '::1', '2025-06-14 07:27:07'),
(48, 9, '', 'login', 'User logged in successfully', 'success', '::1', '2025-06-14 07:27:12'),
(49, 8, '', 'login', 'User logged in successfully', 'success', '::1', '2025-06-17 21:49:25'),
(50, 8, '', 'logout', 'User logged out', 'success', '::1', '2025-06-17 21:49:38'),
(51, 8, '', 'logout', 'User logged out', 'success', '::1', '2025-06-17 21:49:38'),
(52, 9, '', 'login', 'User logged in successfully', 'success', '::1', '2025-06-17 21:49:42'),
(53, 8, '', 'login', 'User logged in successfully', 'success', '::1', '2025-06-21 04:09:56'),
(54, 8, '', 'announcement_updated', 'Updated announcement: Barangay Assembly Dayy', 'success', '::1', '2025-06-21 04:10:12'),
(55, 16, '', 'registration', 'New user registration', 'success', '::1', '2025-06-21 04:11:15'),
(56, 8, '', 'logout', 'User logged out', 'success', '::1', '2025-06-21 04:11:54'),
(57, 8, '', 'logout', 'User logged out', 'success', '::1', '2025-06-21 04:11:54'),
(58, 16, '', 'login', 'User logged in successfully', 'success', '::1', '2025-06-21 04:11:56'),
(59, 16, '', 'profile_picture_update', 'Profile picture updated', 'success', '::1', '2025-06-21 04:12:10'),
(60, 16, '', 'profile_picture_updated', 'Updated profile picture', 'success', '::1', '2025-06-21 04:12:10'),
(61, 16, '', 'logout', 'User logged out', 'success', '::1', '2025-06-21 04:15:31'),
(62, 16, '', 'logout', 'User logged out', 'success', '::1', '2025-06-21 04:15:31'),
(63, 8, '', 'login', 'User logged in successfully', 'success', '::1', '2025-06-21 04:15:41'),
(64, 17, '', 'registration', 'New user registration', 'success', '::1', '2025-06-21 04:17:19'),
(65, 8, '', 'logout', 'User logged out', 'success', '::1', '2025-06-21 04:41:57'),
(66, 8, '', 'logout', 'User logged out', 'success', '::1', '2025-06-21 04:41:57'),
(67, 9, '', 'login', 'User logged in successfully', 'success', '::1', '2025-06-21 04:42:18'),
(68, 9, '', 'profile_updated', 'Updated profile information', 'success', '::1', '2025-06-21 05:09:37'),
(69, 9, '', 'profile_updated', 'Updated profile information', 'success', '::1', '2025-06-21 05:09:40'),
(70, 8, '', 'login', 'User logged in successfully', 'success', '::1', '2025-06-21 06:03:29'),
(71, 8, '', 'announcement_created', 'Created announcement: Medical Mission', 'success', '::1', '2025-06-21 06:18:02'),
(72, 9, '', 'document_requested', 'Requested document #1', 'success', '::1', '2025-06-21 06:29:42'),
(73, 8, '', 'document_request_updated', 'Updated document request #1 status to processing', 'success', '::1', '2025-06-21 06:30:08'),
(74, 9, '', 'document_requested', 'Requested document #2', 'success', '::1', '2025-06-21 06:32:52'),
(75, 8, '', 'document_request_updated', 'Updated document request #2 status to processing', 'success', '::1', '2025-06-21 06:32:57'),
(76, 9, '', 'document_requested', 'Requested document #3', 'success', '::1', '2025-06-21 06:33:42'),
(77, 9, '', 'document_requested', 'Requested document #4', 'success', '::1', '2025-06-21 06:34:04'),
(78, 9, '', 'document_requested', 'Requested document #5', 'success', '::1', '2025-06-21 06:34:26'),
(79, 9, '', 'document_requested', 'Requested document #6', 'success', '::1', '2025-06-21 06:44:54'),
(80, 9, '', 'document_requested', 'Requested document #7', 'success', '::1', '2025-06-21 06:45:02'),
(81, 9, '', 'document_requested', 'Requested document #8', 'success', '::1', '2025-06-21 06:47:15'),
(82, 8, '', 'document_status_update', 'Updated document request #8 to Processing', 'success', '::1', '2025-06-21 07:07:16'),
(83, 8, '', 'document_status_update', 'Updated document request #8 to Cancelled', 'success', '::1', '2025-06-21 07:07:17'),
(84, 8, '', 'document_status_update', 'Updated document request #8 to Ready for Pickup', 'success', '::1', '2025-06-21 07:07:24'),
(85, 8, '', 'document_status_update', 'Updated document request #8 to Processing', 'success', '::1', '2025-06-21 07:13:56'),
(86, 8, '', 'document_status_update', 'Updated document request #7 to Ready for Pickup', 'success', '::1', '2025-06-21 07:14:13'),
(87, 8, '', 'document_status_update', 'Updated document request #8 to Processing', 'success', NULL, '2025-06-21 07:46:22'),
(88, 8, '', 'document_status_update', 'Updated document request #8 to Ready for Pickup', 'success', NULL, '2025-06-21 07:46:27'),
(89, 8, '', 'document_status_update', 'Updated document request #8 to Completed', 'success', NULL, '2025-06-21 07:46:46'),
(90, 8, '', 'document_status_update', 'Updated document request #8 to Completed', 'success', NULL, '2025-06-21 07:46:56'),
(91, 9, '', 'appointment_created', 'Created new appointment #6', 'success', NULL, '2025-06-21 07:58:01'),
(92, 8, '', 'document_status_update', 'Updated document request #8 to Processing', 'success', NULL, '2025-06-21 08:18:07'),
(93, 8, '', 'appointment_status_update', 'Updated appointment #6 to Processing', 'success', NULL, '2025-06-21 08:18:45'),
(94, 8, '', 'appointment_status_update', 'Updated appointment #5 to Processing', 'success', NULL, '2025-06-21 08:40:13'),
(95, 9, '', 'appointment_created', 'Created new appointment #7', 'success', NULL, '2025-06-22 00:15:53'),
(96, 8, '', 'appointment_status_update', 'Updated appointment #7 to Approved', 'success', NULL, '2025-06-22 02:00:49'),
(97, 8, '', 'appointment_status_update', 'Updated appointment #4 to Declined', 'success', NULL, '2025-06-22 02:00:51'),
(98, 8, '', 'appointment_status_update', 'Updated appointment #6 to Completed', 'success', NULL, '2025-06-22 02:00:53'),
(99, 9, '', 'appointment_created', 'Created new appointment #8', 'success', NULL, '2025-06-22 02:02:41'),
(100, 8, '', 'document_status_update', 'Updated document request #8 to Approved', 'success', NULL, '2025-06-22 07:05:21'),
(101, 8, '', 'document_status_update', 'Updated document request #8 to Declined', 'success', NULL, '2025-06-22 07:05:22'),
(102, 8, '', 'document_status_update', 'Updated document request #8 to Pending', 'success', NULL, '2025-06-22 07:05:42'),
(103, 8, '', 'document_status_update', 'Updated document request #9 to Approved', 'success', NULL, '2025-06-22 10:14:24'),
(104, 9, '', 'document_requested', 'Requested document #10', 'success', NULL, '2025-06-22 10:15:34'),
(105, 9, '', 'logout', 'User logged out', 'success', NULL, '2025-06-24 12:42:22'),
(106, 8, '', 'logout', 'User logged out', 'success', NULL, '2025-06-24 12:43:02'),
(107, 8, '', 'logout', 'User logged out', 'success', NULL, '2025-06-24 12:43:05'),
(108, 8, '', 'logout', 'User logged out', 'success', NULL, '2025-06-24 12:45:40'),
(109, 8, '', 'logout', 'User logged out', 'success', NULL, '2025-06-25 13:46:01'),
(110, 9, '', 'logout', 'User logged out', 'success', NULL, '2025-06-25 13:53:46'),
(111, 8, '', 'logout', 'User logged out', 'success', NULL, '2025-06-27 11:04:38');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `type` enum('health','general','event') NOT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `image` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `title`, `content`, `type`, `status`, `is_public`, `image`, `created_by`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'Barangay Assembly Dayy', 'QWE', '', 'published', 1, 'ann_684d0c171cbe7.png', 8, 1, '2025-06-14 05:43:51', '2025-06-21 04:10:12'),
(3, 'Medical Mission', '📢 BARANGAY BIGA ANNOUNCEMENT 📢\r\n\r\nFREE MEDICAL MISSION FOR ALL RESIDENTS!\r\n\r\nWe are pleased to announce that Barangay Biga, in partnership with local health professionals and volunteer doctors, will be holding a Free Medical Mission on:\r\n\r\n🗓 July 20, 2025 (Sunday)\r\n🕗 8:00 AM to 3:00 PM\r\n📍 Barangay Biga Covered Court\r\n\r\nServices Offered:\r\n✅ Free Medical Consultation\r\n✅ Blood Pressure &amp; Sugar Check\r\n✅ Free Medicines (Limited Supply)\r\n✅ Basic Laboratory Tests\r\n✅ Dental Check-up\r\n✅ Health Education &amp; Counseling\r\n\r\n📌 Bring your Barangay ID or any valid ID for verification.\r\n📌 First come, first served basis.\r\n📌 Open to all residents of Barangay Biga.\r\n\r\nLet’s take this opportunity to prioritize our health and well-being. See you there, mga Ka-Barangay! 💚\r\n\r\nFor more info, contact the Barangay Health Center or message our official Facebook page.\r\n\r\n#SerbisyongTapatSaBayan\r\n#BarangayBigaCares\r\n#MedicalMission2025', '', 'published', 1, 'ann_68564e9a3853e.png', 8, 1, '2025-06-21 06:18:02', '2025-06-21 06:18:02');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_comments`
--

CREATE TABLE `announcement_comments` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement_comments`
--

INSERT INTO `announcement_comments` (`id`, `announcement_id`, `user_id`, `parent_id`, `comment`, `created_at`) VALUES
(1, 2, 9, NULL, 'qwewqe', '2025-06-14 07:32:02'),
(2, 2, 9, NULL, 'hahahaha ha', '2025-06-14 07:33:56'),
(3, 2, 8, 2, 'QWEQWE', '2025-06-14 07:38:54'),
(4, 2, 8, 1, 'QWEQE', '2025-06-14 07:39:49'),
(5, 2, 16, NULL, 'solid', '2025-06-21 04:12:21'),
(6, 2, 9, NULL, 'qwe', '2025-06-21 05:23:29'),
(7, 2, 9, NULL, 'qwe', '2025-06-21 05:23:35'),
(8, 2, 9, NULL, 'qew', '2025-06-21 05:23:36'),
(9, 2, 9, NULL, 'new', '2025-06-21 05:23:40'),
(10, 2, 9, NULL, 'testing', '2025-06-21 06:03:15'),
(11, 2, 8, 10, 'haha', '2025-06-21 06:12:32'),
(12, 3, 9, NULL, 'qweqe', '2025-06-22 10:26:05'),
(13, 3, 9, NULL, 'hahahha', '2025-06-22 10:26:08'),
(14, 3, 8, 13, 'lol', '2025-06-22 10:26:20'),
(15, 3, 9, NULL, 'w', '2025-06-22 10:46:21'),
(16, 3, 9, NULL, 'qweeqw', '2025-06-24 11:16:11'),
(17, 3, 8, 16, 'TEST', '2025-06-24 12:53:05'),
(18, 3, 9, NULL, 'Test', '2025-06-27 11:17:38'),
(19, 3, 9, NULL, 'lala haha', '2025-06-27 11:17:45'),
(20, 3, 9, NULL, 'test june 27 2025 7:17 pm', '2025-06-27 11:18:01'),
(21, 2, 9, NULL, 'lol', '2025-06-27 11:19:12'),
(22, 3, 9, NULL, 'yow', '2025-06-27 11:24:12'),
(23, 2, 9, NULL, 'we', '2025-06-27 11:31:13'),
(24, 3, 9, NULL, 'test', '2025-06-27 11:33:53');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_likes`
--

CREATE TABLE `announcement_likes` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement_likes`
--

INSERT INTO `announcement_likes` (`id`, `announcement_id`, `user_id`, `created_at`) VALUES
(16, 3, 9, '2025-06-24 11:02:53'),
(18, 2, 9, '2025-06-24 11:12:28');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `appointment_type` enum('healthcare','official') NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `end_time` time DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `purpose` text NOT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `user_id`, `assigned_to`, `appointment_type`, `appointment_date`, `appointment_time`, `end_time`, `status`, `purpose`, `cancellation_reason`, `created_at`, `updated_at`) VALUES
(1, 9, NULL, '', '2025-06-16', '01:49:00', NULL, '', 'for my school purposes', NULL, '2025-06-14 03:46:32', '2025-06-14 03:47:00'),
(2, 9, NULL, '', '2025-06-16', '11:58:00', NULL, '', '[Meeting with: SA KANYA] SA KANYA', NULL, '2025-06-14 03:56:19', '2025-06-14 03:56:29'),
(3, 9, NULL, '', '2025-06-16', '11:59:00', NULL, '', 'we', NULL, '2025-06-14 03:57:29', '2025-06-14 03:57:54'),
(4, 9, NULL, '', '2025-07-17', '13:01:00', NULL, '', 'qweqwe', NULL, '2025-06-14 04:00:16', '2025-06-22 02:00:51'),
(5, 9, NULL, '', '2025-06-24', '18:56:00', NULL, '', 'Testing', NULL, '2025-06-21 07:54:57', '2025-06-21 08:40:13'),
(6, 9, NULL, '', '2025-06-26', '03:01:00', NULL, 'completed', '[Meeting with: EDward] testing', NULL, '2025-06-21 07:58:01', '2025-06-22 02:00:53'),
(7, 9, NULL, '', '2025-06-25', '23:17:00', NULL, '', '[Meeting with: EDward] Testt', NULL, '2025-06-22 00:15:53', '2025-06-22 02:00:49'),
(8, 9, NULL, '', '2025-06-23', '10:04:00', NULL, 'pending', 'qweewq', NULL, '2025-06-22 02:02:41', '2025-06-22 02:02:41');

-- --------------------------------------------------------

--
-- Table structure for table `document_requests`
--

CREATE TABLE `document_requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `document_type` enum('Barangay Clearance','Certificate of Residency','Certificate of Indigency','Barangay ID','Certificate of House Ownership','Construction Clearance','Business Clearance','Endorsement Letter for Business','Barangay Blotter') NOT NULL,
  `purpose` text NOT NULL,
  `status` enum('pending','in_progress','for_pickup','completed','cancelled') DEFAULT 'pending',
  `cost` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('unpaid','paid','partial') DEFAULT 'unpaid',
  `payment_date` datetime DEFAULT NULL,
  `validity_period` int(11) DEFAULT NULL,
  `pickup_instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_requests`
--

INSERT INTO `document_requests` (`request_id`, `user_id`, `document_type`, `purpose`, `status`, `cost`, `payment_status`, `payment_date`, `validity_period`, `pickup_instructions`, `created_at`, `updated_at`) VALUES
(1, 9, '', 'Need lang', '', 20.00, 'unpaid', NULL, NULL, 'later', '2025-06-21 06:29:42', '2025-06-21 06:30:08'),
(2, 9, '', 'wqe', '', 20.00, 'unpaid', NULL, NULL, 'qew', '2025-06-21 06:32:52', '2025-06-21 06:32:57'),
(3, 9, '', 'qweewq', 'pending', 20.00, 'unpaid', NULL, NULL, 'eqw', '2025-06-21 06:33:42', '2025-06-21 06:33:42'),
(4, 9, '', 'qweewq', 'pending', 20.00, 'unpaid', NULL, NULL, 'eqw', '2025-06-21 06:34:04', '2025-06-21 06:34:04'),
(5, 9, '', 'qweewq', 'pending', 20.00, 'unpaid', NULL, NULL, 'eqw', '2025-06-21 06:34:26', '2025-06-21 06:34:26'),
(6, 9, '', 'qewqwe', 'pending', 20.00, 'unpaid', NULL, NULL, 'ewqwqe', '2025-06-21 06:44:54', '2025-06-21 06:44:54'),
(7, 9, '', 'qewqwe', '', 20.00, 'unpaid', NULL, NULL, 'ewqwqe', '2025-06-21 06:45:02', '2025-06-21 07:14:13'),
(8, 9, '', 'qewqwe', 'pending', 20.00, 'unpaid', NULL, NULL, 'ewqwqe', '2025-06-21 06:47:15', '2025-06-22 07:05:42'),
(9, 9, 'Certificate of Residency', 'test', '', 40.00, 'unpaid', NULL, NULL, 'test', '2025-06-22 10:13:04', '2025-06-22 10:14:24'),
(10, 9, 'Certificate of Residency', 'Tea', 'pending', 40.00, 'unpaid', NULL, NULL, 'weqe', '2025-06-22 10:15:34', '2025-06-22 10:15:34');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL,
  `location` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `record_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `diagnosis` text NOT NULL,
  `prescription` text DEFAULT NULL,
  `attending_physician` varchar(100) DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `next_checkup_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `record_date` date NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','resident') NOT NULL,
  `is_senior_citizen` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `profile_picture` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_code` varchar(6) DEFAULT NULL,
  `reset_code_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `role`, `is_senior_citizen`, `is_active`, `profile_picture`, `last_login`, `failed_login_attempts`, `created_at`, `updated_at`, `reset_code`, `reset_code_expires`) VALUES
(8, 'brixadmin', '$2y$10$Jnjgbk2AlINQ7cL1q2Gf1O4OIZgi3jz9j.3eYKpg2eOxgjsgeHs/O', 'brixshopee@gmail.com', 'admin', 0, 1, '/capstone2/uploads/profile_pictures/684ceb3dadbda_468a8ca4-8e5e-4931-85bc-888855292d37.jpeg', NULL, 0, '2025-06-14 03:23:12', '2025-06-14 03:23:41', NULL, NULL),
(9, 'brixuser', '$2y$10$oDgrGB72sKM4yEqf3AN0w.bKnUOW1SluvNCGSZOiXgJfF/D.Mmw0a', 'sesebrixligon@gmail.com', 'resident', 1, 1, '/capstone2/uploads/profile_pictures/684ced77c4a2c_IMG_2558 - Copy.JPG', NULL, 0, '2025-06-14 03:24:46', '2025-06-21 05:09:40', NULL, NULL),
(13, 'sigelot123', '$2y$10$wNVwCR85F7l/pE6P1xi85ev0vEfWH9Bux7uMz3AemJ6DTTc8EqarC', 'cowiki8800@ethsms.com', 'resident', 0, 1, NULL, NULL, 0, '2025-06-14 04:55:43', '2025-06-14 04:55:43', NULL, NULL),
(14, 'lukesese', '$2y$10$sVnNathgcsugv9B6ER91KO3L.eQDEI5oySiRsJgAgFIgbnF2wvmUa', 'pewahib137@ethsms.com', 'resident', 0, 1, NULL, NULL, 0, '2025-06-14 04:59:14', '2025-06-15 03:53:02', NULL, NULL),
(16, 'brixtest', '$2y$10$AbZ77Iyfr6C069nOQ.vLkeFzM4RWGCRQqE5MzUKlhcvFBIpJ.aEiq', 'brixtest@gmail.com', 'resident', 0, 1, '/capstone2/uploads/profile_pictures/6856311a50053_Screenshot 2025-06-05 211259.png', NULL, 0, '2025-06-21 04:11:15', '2025-06-21 04:12:10', NULL, NULL),
(17, 'newtry', '$2y$10$//mAg9cBGAgdGBqVvyt0.u8SwZXYx6dHISWq2ptmESKs4GV8B4uiq', 'yigites571@finfave.com', 'admin', 0, 1, NULL, NULL, 0, '2025-06-21 04:17:19', '2025-06-21 04:17:19', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_details`
--

CREATE TABLE `user_details` (
  `detail_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `birth_date` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `civil_status` enum('Single','Married','Widowed','Separated','Divorced') NOT NULL,
  `nationality` varchar(50) DEFAULT 'Filipino',
  `occupation` varchar(100) DEFAULT NULL,
  `voter_status` tinyint(1) DEFAULT 0,
  `address` text NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `emergency_number` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_details`
--

INSERT INTO `user_details` (`detail_id`, `user_id`, `first_name`, `last_name`, `middle_name`, `birth_date`, `gender`, `civil_status`, `nationality`, `occupation`, `voter_status`, `address`, `contact_number`, `emergency_contact`, `emergency_number`, `created_at`, `updated_at`) VALUES
(8, 8, 'Brix', 'Sese', NULL, '2004-02-27', 'Other', 'Single', 'Filipino', NULL, 0, 'blk 60 lot 17 purok 3 , barangay biga , tanza ,cavite', NULL, NULL, NULL, '2025-06-14 11:23:12', '2025-06-14 11:23:12'),
(9, 9, 'Brix', 'Sese', '', '1948-01-06', '', '', 'Filipino', '', 1, 'blk 60 lot 17 purok 3 , barangay biga , tanza ,cavite', '09364112314', NULL, NULL, '2025-06-14 11:24:46', '2025-06-21 13:09:40'),
(10, 13, 'Brix', 'Sese', NULL, '2004-06-03', 'Other', 'Single', 'Filipino', NULL, 0, 'blk 60 lot 17 purok 3 , barangay biga , tanza ,cavite', NULL, NULL, NULL, '2025-06-14 12:55:43', '2025-06-14 12:55:43'),
(11, 14, 'luke', 'sese', NULL, '2025-06-18', 'Other', 'Single', 'Filipino', NULL, 0, '', NULL, NULL, NULL, '2025-06-14 12:59:14', '2025-06-14 12:59:14'),
(13, 16, 'brix', 'test', NULL, '2000-06-29', 'Other', 'Single', 'Filipino', NULL, 0, '', NULL, NULL, NULL, '2025-06-21 12:11:15', '2025-06-21 12:11:15'),
(14, 17, 'new', 'try', NULL, '2025-06-20', 'Other', 'Single', 'Filipino', NULL, 0, '', NULL, NULL, NULL, '2025-06-21 12:17:19', '2025-06-21 12:17:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`);

--
-- Indexes for table `announcement_comments`
--
ALTER TABLE `announcement_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `announcement_id` (`announcement_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `announcement_likes`
--
ALTER TABLE `announcement_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `announcement_id` (`announcement_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `document_requests`
--
ALTER TABLE `document_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_details`
--
ALTER TABLE `user_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `announcement_comments`
--
ALTER TABLE `announcement_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `announcement_likes`
--
ALTER TABLE `announcement_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `document_requests`
--
ALTER TABLE `document_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `user_details`
--
ALTER TABLE `user_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `announcement_comments`
--
ALTER TABLE `announcement_comments`
  ADD CONSTRAINT `announcement_comments_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`announcement_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcement_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcement_comments_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `announcement_comments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `announcement_likes`
--
ALTER TABLE `announcement_likes`
  ADD CONSTRAINT `announcement_likes_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`announcement_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcement_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `document_requests`
--
ALTER TABLE `document_requests`
  ADD CONSTRAINT `document_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medical_records_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_details`
--
ALTER TABLE `user_details`
  ADD CONSTRAINT `user_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
