-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 26, 2025 at 12:28 PM
-- Server version: 10.11.10-MariaDB
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u520834156_dbBagoVetIMS`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action`, `timestamp`) VALUES
(1, 1, 'Viewed Activity Logs', '2025-07-09 19:25:51'),
(2, 1, 'Viewed Activity Logs', '2025-07-09 19:28:40'),
(3, 1, 'Viewed Activity Logs', '2025-07-09 19:28:49'),
(4, 1, 'Viewed Activity Logs', '2025-07-09 19:34:42'),
(5, NULL, 'Logged in', '2025-07-09 19:34:43'),
(6, 1, 'Updated profile information', '2025-07-09 19:34:43'),
(7, 1, 'Uploaded new profile photo', '2025-07-09 19:34:43'),
(8, 1, 'Added new livestock entry', '2025-07-09 19:34:43'),
(9, 1, 'Deleted poultry record ID #', '2025-07-09 19:34:43'),
(10, 1, 'Viewed pharmaceuticals page', '2025-07-09 19:34:43'),
(11, 1, 'Viewed Activity Logs', '2025-07-09 19:36:40'),
(12, 1, 'Added new Livestock - chicken (20 units)', '2025-07-09 19:45:27'),
(13, 1, 'Added new Poultry - chicken (10 units)', '2025-07-09 19:55:33'),
(14, 1, 'Viewed Activity Logs', '2025-07-10 02:28:38'),
(15, 1, 'Viewed Activity Logs', '2025-07-10 02:29:28'),
(16, 1, 'Viewed Activity Logs', '2025-07-14 13:25:35'),
(17, 1, 'Viewed Activity Logs', '2025-07-14 13:26:35'),
(18, 1, 'Viewed Activity Logs', '2025-07-14 17:28:41'),
(19, 1, 'Viewed Activity Logs', '2025-07-16 13:37:59'),
(20, 1, 'Viewed Activity Logs', '2025-07-17 15:40:28'),
(21, 1, 'Viewed Activity Logs', '2025-07-17 21:00:15'),
(22, 1, 'Viewed Activity Logs', '2025-07-17 21:24:36'),
(23, 1, 'Viewed Activity Logs', '2025-07-17 21:25:50'),
(24, 1, 'Viewed Activity Logs', '2025-07-17 21:25:57'),
(25, 1, 'Viewed Activity Logs', '2025-07-17 21:27:04'),
(26, 1, 'Viewed Activity Logs', '2025-07-17 21:27:15'),
(27, 1, 'Viewed Activity Logs', '2025-07-17 21:30:00'),
(28, 1, 'Viewed Activity Logs', '2025-07-17 21:30:07'),
(29, 1, 'Viewed Activity Logs', '2025-07-17 21:30:26'),
(30, 1, 'Viewed Activity Logs', '2025-07-17 21:31:33'),
(31, 1, 'Viewed Activity Logs', '2025-07-17 21:32:15'),
(32, 1, 'Viewed Activity Logs', '2025-07-17 21:33:48'),
(33, 1, 'Viewed Activity Logs', '2025-07-17 21:33:48'),
(34, 1, 'Viewed Activity Logs', '2025-07-17 21:33:49'),
(35, 1, 'Viewed Activity Logs', '2025-07-17 21:33:49'),
(36, 1, 'Viewed Activity Logs', '2025-07-17 21:34:06'),
(37, 1, 'Viewed Activity Logs', '2025-07-17 21:34:06'),
(38, 1, 'Viewed Activity Logs', '2025-07-17 21:34:13'),
(39, 1, 'Viewed Activity Logs', '2025-07-17 21:34:13'),
(40, 1, 'Viewed Activity Logs', '2025-07-17 21:34:13'),
(41, 1, 'Viewed Activity Logs', '2025-07-17 21:34:13'),
(42, 1, 'Viewed Activity Logs', '2025-07-17 21:34:13'),
(43, 1, 'Viewed Activity Logs', '2025-07-17 21:34:13'),
(44, 1, 'Viewed Activity Logs', '2025-07-17 21:34:14'),
(45, 1, 'Viewed Activity Logs', '2025-07-17 21:34:14'),
(46, 1, 'Viewed Activity Logs', '2025-07-17 21:34:16'),
(47, 1, 'Viewed Activity Logs', '2025-07-17 21:34:48'),
(48, 1, 'Viewed Activity Logs', '2025-07-17 21:34:48'),
(49, 1, 'Viewed Activity Logs', '2025-07-17 21:34:49'),
(50, 1, 'Viewed Activity Logs', '2025-07-17 21:34:49'),
(51, 1, 'Viewed Activity Logs', '2025-07-17 21:34:49'),
(52, 1, 'Viewed Activity Logs', '2025-07-17 21:34:49'),
(53, 1, 'Viewed Activity Logs', '2025-07-17 21:35:30'),
(54, 1, 'Viewed Activity Logs', '2025-07-17 21:35:30'),
(55, 1, 'Viewed Activity Logs', '2025-07-17 21:37:13'),
(56, 1, 'Viewed Activity Logs', '2025-07-17 21:37:14'),
(57, 1, 'Viewed Activity Logs', '2025-07-17 21:38:28'),
(58, 1, 'Viewed Activity Logs', '2025-07-17 21:38:29'),
(59, 1, 'Viewed Activity Logs', '2025-07-17 21:38:43'),
(60, 1, 'Viewed Activity Logs', '2025-07-17 21:38:43'),
(61, 1, 'Viewed Activity Logs', '2025-07-17 21:40:01'),
(62, 1, 'Viewed Activity Logs', '2025-07-18 04:02:43'),
(63, 1, 'Viewed Activity Logs', '2025-07-18 04:24:26'),
(64, 1, 'Viewed Activity Logs', '2025-07-18 08:15:01'),
(65, 1, 'Viewed Activity Logs', '2025-07-18 08:26:15'),
(66, 1, 'Viewed Activity Logs', '2025-07-18 08:36:51'),
(67, 1, 'Viewed Activity Logs', '2025-07-18 08:39:08'),
(68, 1, 'Viewed Activity Logs', '2025-07-18 08:40:08'),
(69, 1, 'Viewed Activity Logs', '2025-07-18 08:40:35'),
(70, 1, 'Viewed Activity Logs', '2025-07-18 08:43:21'),
(71, 1, 'Viewed Activity Logs', '2025-07-22 05:33:36'),
(72, 1, 'Viewed Activity Logs', '2025-07-22 05:47:36'),
(73, 1, 'Viewed Activity Logs', '2025-07-22 07:09:48'),
(74, 1, 'Viewed Activity Logs', '2025-07-22 17:50:00'),
(75, 1, 'Viewed Activity Logs', '2025-07-22 17:50:01'),
(76, 1, 'Viewed Activity Logs', '2025-07-22 18:07:05'),
(77, 1, 'Viewed Activity Logs', '2025-07-22 18:20:12'),
(78, 1, 'Viewed Activity Logs', '2025-07-22 18:20:13'),
(79, 1, 'Viewed Activity Logs', '2025-07-22 19:23:07'),
(80, 1, 'Viewed Activity Logs', '2025-07-28 13:44:11'),
(81, 1, 'Viewed Activity Logs', '2025-07-31 01:43:53'),
(82, 1, 'Viewed Activity Logs', '2025-07-31 01:44:11'),
(83, 1, 'Viewed Activity Logs', '2025-07-31 01:44:25'),
(84, 1, 'Viewed Activity Logs', '2025-07-31 02:14:48'),
(85, 1, 'Viewed Activity Logs', '2025-07-31 06:40:58'),
(86, 1, 'Viewed Activity Logs', '2025-07-31 06:52:42'),
(87, 1, 'Viewed Activity Logs', '2025-07-31 08:40:22'),
(88, 1, 'Viewed Activity Logs', '2025-08-10 17:12:18'),
(89, 1, 'Viewed Activity Logs', '2025-08-10 17:19:37'),
(90, 1, 'Viewed Activity Logs', '2025-08-10 17:19:49'),
(91, 1, 'Viewed Activity Logs', '2025-08-10 17:21:45'),
(92, 1, 'Viewed Activity Logs', '2025-08-10 18:22:59'),
(93, 1, 'Viewed Activity Logs', '2025-08-11 05:39:32'),
(94, 1, 'Viewed Activity Logs', '2025-08-11 05:54:07'),
(95, 1, 'Viewed Activity Logs', '2025-08-11 05:54:11'),
(96, 1, 'Viewed Activity Logs', '2025-08-11 06:21:58'),
(97, 1, 'Viewed Activity Logs', '2025-08-11 06:22:38'),
(98, 1, 'Viewed Activity Logs', '2025-08-11 06:38:58'),
(99, 1, 'Viewed Activity Logs', '2025-08-11 06:39:33'),
(100, 1, 'Viewed Activity Logs', '2025-08-11 17:49:29'),
(101, 1, 'Viewed Activity Logs', '2025-08-12 16:40:34'),
(102, 1, 'Viewed Activity Logs', '2025-08-12 17:01:27'),
(103, 1, 'Viewed Activity Logs', '2025-08-12 17:01:30'),
(104, 1, 'Viewed Activity Logs', '2025-08-12 17:01:40'),
(105, 1, 'Viewed Activity Logs', '2025-08-12 17:04:19'),
(106, 1, 'Viewed Activity Logs', '2025-08-12 17:04:21'),
(107, 1, 'Viewed Activity Logs', '2025-08-12 17:05:49'),
(108, 1, 'Viewed Activity Logs', '2025-08-12 17:33:10'),
(109, 1, 'Viewed Activity Logs', '2025-08-13 00:14:15'),
(110, 1, 'Viewed Activity Logs', '2025-08-13 00:15:29'),
(111, 1, 'Viewed Activity Logs', '2025-08-13 03:12:01'),
(112, 1, 'Viewed Activity Logs', '2025-08-13 04:14:06'),
(113, 1, 'Viewed Activity Logs', '2025-08-14 00:57:23'),
(114, 1, 'Viewed Activity Logs', '2025-08-14 02:28:16'),
(115, 1, 'Viewed Activity Logs', '2025-08-19 15:49:57'),
(116, 1, 'Viewed Activity Logs', '2025-08-19 15:59:29'),
(117, 1, 'Viewed Activity Logs', '2025-08-19 16:00:35'),
(118, 1, 'Viewed Activity Logs', '2025-08-19 16:00:55'),
(119, 1, 'Viewed Activity Logs', '2025-08-19 16:01:43'),
(120, 1, 'Viewed Activity Logs', '2025-08-19 16:02:04'),
(121, 1, 'Viewed Activity Logs', '2025-08-19 16:32:29'),
(122, 1, 'Viewed Activity Logs', '2025-08-19 17:05:59'),
(123, 1, 'Viewed Activity Logs', '2025-08-19 18:12:50'),
(124, 1, 'Viewed Activity Logs', '2025-08-19 18:34:10'),
(125, 1, 'Viewed Activity Logs', '2025-08-19 18:34:34'),
(126, 1, 'Viewed Activity Logs', '2025-08-19 19:27:21'),
(127, 1, 'Viewed Activity Logs', '2025-08-20 19:24:08'),
(128, 1, 'Viewed Activity Logs', '2025-08-20 19:24:27'),
(129, 1, 'Viewed Activity Logs', '2025-08-20 22:29:02'),
(130, 1, 'Viewed Activity Logs', '2025-08-20 23:04:14'),
(131, 1, 'Viewed Activity Logs', '2025-08-24 12:14:11');

-- --------------------------------------------------------

--
-- Table structure for table `animal_photos`
--

CREATE TABLE `animal_photos` (
  `photo_id` int(11) NOT NULL,
  `animal_id` int(11) DEFAULT NULL,
  `photo_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `animal_photos`
--

INSERT INTO `animal_photos` (`photo_id`, `animal_id`, `photo_path`, `status`, `reviewed_by`, `reviewed_at`, `rejection_reason`, `uploaded_at`) VALUES
(3, 7, 'uploads/animal_photos/689b6b35a7db3_camera_photo_1755015986715.jpg', 'Pending', NULL, NULL, NULL, '2025-08-12 16:26:29'),
(4, 7, 'uploads/animal_photos/689b6d0c00258_camera_photo_1755016457469.jpg', 'Pending', NULL, NULL, NULL, '2025-08-12 16:34:20'),
(5, 7, 'uploads/animal_photos/689c0aa4c2f69_camera_photo_1755056795321.jpg', 'Pending', NULL, NULL, NULL, '2025-08-13 03:46:44'),
(6, 7, 'uploads/animal_photos/689c17197329b_camera_photo_1755059990278.jpg', 'Pending', NULL, NULL, NULL, '2025-08-13 04:39:53'),
(7, 8, 'uploads/animal_photos/689c3bcd45f1c_camera_photo_1755069385463.jpg', 'Pending', NULL, NULL, NULL, '2025-08-13 07:16:29'),
(8, 8, 'uploads/animal_photos/689c3bcd46374_camera_photo_1755069385854.jpg', 'Pending', NULL, NULL, NULL, '2025-08-13 07:16:29'),
(9, 8, 'uploads/animal_photos/689c3bcd4662b_camera_photo_1755069386095.jpg', 'Pending', NULL, NULL, NULL, '2025-08-13 07:16:29'),
(10, 7, 'uploads/animal_photos/689c3bf73114f_camera_photo_1755069427051.jpg', 'Pending', NULL, NULL, NULL, '2025-08-13 07:17:11'),
(11, 7, 'uploads/animal_photos/689c3bf73152d_camera_photo_1755069427232.jpg', 'Pending', NULL, NULL, NULL, '2025-08-13 07:17:11'),
(12, 7, 'uploads/animal_photos/689c518d42d4a_camera_photo_1755074953653.jpg', 'Pending', NULL, NULL, NULL, '2025-08-13 08:49:17'),
(13, 16, 'uploads/animal_photos/68a87bedbe36f_camera_photo_1755872233144.jpg', 'Approved', 1, '2025-08-23 18:59:26', '', '2025-08-22 14:17:17'),
(14, 18, 'uploads/animal_photos/68ac8f04e8266_camera_photo_1756139266325.jpg', 'Approved', 1, '2025-08-26 07:59:39', NULL, '2025-08-25 16:27:48'),
(15, 18, 'uploads/animal_photos/68ac944515710_camera_photo_1756140609918.jpg', 'Approved', 1, '2025-08-26 07:59:47', NULL, '2025-08-25 16:50:13'),
(16, 7, 'uploads/animal_photos/68ad6199a95bb_camera_photo_1756193173471.jpg', 'Pending', NULL, NULL, NULL, '2025-08-26 07:26:17'),
(17, 20, 'uploads/animal_photos/68ad70d3b5ea7_camera_photo_1756197071440.jpg', 'Rejected', 1, '2025-08-26 08:32:31', 'not acceptable', '2025-08-26 08:31:15'),
(18, 20, 'uploads/animal_photos/68ad714b93ed4_camera_photo_1756197192817.jpg', 'Rejected', 1, '2025-08-26 08:40:34', 'not acceptable', '2025-08-26 08:33:15'),
(19, 20, 'uploads/animal_photos/68ad74784dfbb_camera_photo_1756198005801.jpg', 'Rejected', 1, '2025-08-26 08:47:36', 'idk', '2025-08-26 08:46:48'),
(20, 20, 'uploads/animal_photos/68ad778c7b63a_camera_photo_1756198794692.jpg', 'Rejected', 1, '2025-08-26 09:00:21', 'wala lang', '2025-08-26 08:59:56'),
(21, 20, 'uploads/animal_photos/68ad810ca5642_camera_photo_1756201226912.jpg', 'Approved', 1, '2025-08-26 09:40:52', NULL, '2025-08-26 09:40:28'),
(22, 21, 'uploads/animal_photos/68ad82eb0311d_camera_photo_1756201705243.jpg', 'Approved', 1, '2025-08-26 09:48:50', NULL, '2025-08-26 09:48:27'),
(23, 22, 'uploads/animal_photos/68ad856416b94_camera_photo_1756202338257.jpg', 'Approved', 1, '2025-08-26 09:59:20', NULL, '2025-08-26 09:59:00'),
(24, 23, 'uploads/animal_photos/68ad8e28573bb_camera_photo_1756204583121.jpg', 'Approved', 1, '2025-08-26 10:36:44', NULL, '2025-08-26 10:36:24');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `client_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `contact_number` varchar(15) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `barangay` enum('Abuanan','Alianza','Atipuluan','Bacong-Montilla','Bagroy','Balingasag','Binubuhan','Busay','Calumangan','Caridad','Don Jorge L. Araneta','Dulao','Ilijan','Lag-Asan','Ma-ao','Mailum','Malingin','Napoles','Pacol','Poblacion','Sagasa','Tabunan','Taloc','Sampinit') NOT NULL,
  `transfer_date` date DEFAULT NULL,
  `type` enum('Disseminated','Owned','Pharmaceuticals') NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Complied','Pending') DEFAULT 'Pending',
  `role` set('client') NOT NULL DEFAULT 'client'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`client_id`, `full_name`, `contact_number`, `address`, `barangay`, `transfer_date`, `type`, `latitude`, `longitude`, `username`, `password`, `profile_photo`, `created_at`, `status`, `role`) VALUES
(1, 'Client Name', '09852653412', 'barangay lag asan', '', NULL, 'Disseminated', 0.0008724, -0.0005294, 'client', '$2y$10$YBZboo1.2o/pP9XbitjmMuzYP6fdh4jNL8R0jr1KzoOqsqB6zs1Ka', 'uploads/profile_pictures/profile_1_1752092600.png', '2025-07-09 14:29:24', 'Complied', 'client'),
(4, 'Rodrigazo Ma. Moniza', '09672341549', 'mainuswagon I', '', NULL, 'Disseminated', NULL, NULL, 'mon', '$2y$10$a.avxRE6Tf6PIhYr9F00A.Po67ZFj/z5mGMpdL69NoTtYRdWyhKpS', 'uploads/profile_pictures/profile_4_1752700209.png', '2025-07-16 13:23:21', 'Pending', 'client'),
(5, 'jin', '09284726354', NULL, '', '2025-07-28', 'Disseminated', 10.5373000, 122.8370000, 'jin', '$2y$10$ZWXlqUIIAGL//0.LMuU2XOQLUMtV/.nFwJVvKyr5WFW/75cNxJKsi', NULL, '2025-07-28 15:22:51', 'Pending', 'client'),
(6, 'jeeen', '092384759281', NULL, '', '2025-07-28', 'Disseminated', 10.5373000, 122.8370000, 'jeen', '$2y$10$WghMbW3RALpv1/j/6W1Y/uq2VsGVjEDG/3sVX4vC6TTVq4Hhxlv2u', NULL, '2025-07-28 15:23:27', 'Pending', 'client'),
(7, 'jen', '09283762574', NULL, '', '2025-07-31', 'Disseminated', 10.5373000, 122.8370000, 'jeeeen', '$2y$10$VHvExZy/tPs5/nh4.p5PoeCtvtVzJCIF.B33OO8sgIWLdbG88ddeO', NULL, '2025-07-31 01:40:46', 'Pending', 'client'),
(8, 'testing', '92362236', 'lag-asan', '', '2025-07-31', 'Disseminated', 10.5288068, 122.8393152, 'testing', '$2y$10$EjxnOI31fEWWqqGM21r.8u1OWw.lYqvhb1Rf.jZzJCpOnB5X0caYW', NULL, '2025-07-31 01:51:35', 'Pending', 'client'),
(9, 'anthony', NULL, NULL, '', NULL, 'Disseminated', NULL, NULL, '', '', NULL, '2025-07-31 08:30:22', 'Pending', 'client'),
(10, 'jason delos reyes', '09876543543', 'lag-asan', '', NULL, 'Disseminated', NULL, NULL, 'jason', '$2y$10$PcBeQNrMVnM/S5FqhkVwL.sl9e0R2z4EoQlZ8RJWSWARPvc2scnwS', NULL, '2025-07-31 08:53:03', 'Pending', 'client'),
(11, 'anthony', '9949795091', 'bago', '', NULL, 'Disseminated', NULL, NULL, 'anthony', '$2y$10$3HwEnN2vzqA4EepAjs0hKuBpykGWwub5hv2U/2sW3k/jEkEGB2QFy', NULL, '2025-07-31 08:54:52', 'Pending', 'client'),
(12, 'vivien ', '09284756372', 'Atipuluan', '', NULL, 'Disseminated', NULL, NULL, 'viv', '$2y$10$vxXNOcO6AKDNRLj1zTnLWe.3B8KD9RUNZGRMeDyUpaGIshrRxE.Mq', NULL, '2025-08-19 19:57:17', 'Pending', 'client'),
(13, 'vivi', '09876543549', 'Lag-Asan', '', NULL, 'Disseminated', NULL, NULL, 'vivi', '$2y$10$n4admqyl6rYshn5VhP/Ur.dncVpVYIZat0Gn8muD.QoSjRyUtjyk2', NULL, '2025-08-20 19:45:42', 'Pending', 'client'),
(14, 'test lang', '09182746532', 'Balingasag', '', NULL, 'Disseminated', NULL, NULL, 'testtttttttttt', '$2y$10$VcFIC7YzVB.q5MEehY/ZgOlcFg8itDJDOp0hjqZkZpjJ91cS2zbLu', NULL, '2025-08-20 20:49:19', 'Pending', 'client'),
(15, 'Jenny Rose', '0977827612', 'Busay', 'Abuanan', NULL, 'Disseminated', NULL, NULL, 'rose', '$2y$10$BYudw6iFdwB0rYAJ1.UbXusK1KjVYIK9/MV8S/FHURWf9QhIAhZJC', NULL, '2025-08-22 14:15:54', 'Pending', 'client'),
(16, 'clariza garcia', '09873647567', 'Dulao', 'Abuanan', NULL, 'Disseminated', NULL, NULL, 'clariza', '$2y$10$wF3CUrsu2b/512ZcYcF23O6xayZvzCpr4ID.nmKZ/2uBhUCowOP1S', NULL, '2025-08-24 16:52:07', 'Pending', 'client'),
(17, 'test lang', '09182736453', 'Abuanan', 'Abuanan', NULL, 'Disseminated', NULL, NULL, 'tests', '$2y$10$2qGsST62sRe93ghkmL5GgOG1uOWZYmeRbkdSwQ2JAr7ptmlAkByJ.', NULL, '2025-08-26 08:30:07', 'Pending', 'client'),
(18, 'sonzaida patricio', '09182938475', 'Bacong-Montilla', 'Abuanan', NULL, 'Disseminated', NULL, NULL, 'son', '$2y$10$Q.pKM/DvSj4Z98eLqSeo5u7vXeLZroOGlMcrqW6o/tJje/abZli1.', NULL, '2025-08-26 09:47:39', 'Pending', 'client'),
(19, 'jenny mission', '09876543543', 'Balingasag', 'Abuanan', NULL, 'Disseminated', NULL, NULL, 'bem', '$2y$10$nHIViMtsJtNOjbGiP4Q14.JKWiIt6YSjX1njTv0H0upd06KQGZ2A2', NULL, '2025-08-26 09:53:54', NULL, 'client'),
(20, 'ambot', '09787635422', 'Binubuhan', 'Abuanan', NULL, 'Disseminated', NULL, NULL, 'ambot', '$2y$10$1liNxn3Y8QSnaejbVLgElu8zqDo6O92ckUci7CLX.C.BvNPeX1Bmq', NULL, '2025-08-26 09:57:03', 'Complied', 'client'),
(21, 'weh', '9949795091', 'Abuanan', 'Abuanan', NULL, 'Disseminated', NULL, NULL, 'weh', '$2y$10$AiZ23zOpHWNr664O0NsN3uAG9L8mq9QhGHL4pZp7oeUKxDBTyFlti', NULL, '2025-08-26 10:35:17', 'Complied', 'client');

-- --------------------------------------------------------

--
-- Table structure for table `livestock_poultry`
--

CREATE TABLE `livestock_poultry` (
  `animal_id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `animal_type` enum('Livestock','Poultry','Both') NOT NULL,
  `species` varchar(50) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `weight` float DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `health_status` varchar(100) DEFAULT NULL,
  `last_vaccination` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `livestock_poultry`
--

INSERT INTO `livestock_poultry` (`animal_id`, `client_id`, `age`, `animal_type`, `species`, `quantity`, `created_at`, `updated_at`, `weight`, `source`, `health_status`, `last_vaccination`) VALUES
(1, 1, NULL, 'Livestock', 'cow', 1, '2025-07-09 19:45:27', '2025-07-09 19:54:58', 55, 'Disseminated', 'Healthy', '2025-07-02'),
(2, 1, NULL, 'Poultry', 'chicken', 10, '2025-07-09 19:55:33', '2025-07-09 19:55:33', 0, 'Owned', 'Healthy', NULL),
(3, 4, NULL, 'Livestock', 'cow', 1, '2025-07-16 22:18:47', '2025-08-11 20:53:20', 55, 'Owned', 'Healthy', '2025-06-02'),
(4, 5, NULL, '', 'cattle', 1, '2025-07-27 16:00:00', '2025-07-28 15:22:51', NULL, NULL, NULL, NULL),
(5, 6, NULL, '', 'cow', 3, '2025-07-27 16:00:00', '2025-07-28 15:23:27', NULL, NULL, NULL, NULL),
(6, 7, NULL, '', 'carabao', 5, '2025-07-30 16:00:00', '2025-07-31 01:40:46', NULL, NULL, NULL, NULL),
(7, 8, NULL, 'Livestock', 'cattle', 1, '2025-07-31 02:01:52', '2025-08-11 20:53:11', 65, 'Owned', 'Healthy', '2025-07-24'),
(8, 8, NULL, 'Poultry', 'Chicken', 1, '2025-07-31 08:44:51', '2025-08-13 06:52:07', 0, 'Owned', 'Healthy', NULL),
(10, 11, NULL, 'Livestock', 'Water Buffalo', 4, '2025-08-13 07:55:21', '2025-08-13 07:55:21', 79, 'Disseminated', 'Healthy', NULL),
(12, 6, NULL, 'Poultry', 'Chicken', 40, '2025-08-13 07:58:46', '2025-08-13 07:58:46', 0, 'Disseminated', 'Healthy', NULL),
(13, 6, NULL, 'Poultry', 'Chicken', 4, '2025-08-13 08:02:04', '2025-08-13 08:02:04', 0, 'Disseminated', 'Healthy', NULL),
(14, 10, NULL, 'Livestock', 'Cattle', 1, '2025-08-13 08:04:38', '2025-08-13 08:04:38', 79, 'Owned', 'Healthy', '2025-08-13'),
(15, 6, NULL, 'Poultry', 'Chicken', 5, '2025-08-13 08:05:22', '2025-08-13 08:05:22', 0, 'Owned', 'Healthy', '2025-07-30'),
(16, 15, NULL, 'Livestock', 'Carabao', 1, '2025-08-22 14:16:54', '2025-08-22 14:16:54', 32, 'Owned', 'Healthy', '2025-07-27'),
(17, 11, NULL, 'Livestock', 'Cattle', 1, '2025-08-23 17:13:57', '2025-08-23 17:13:57', 22, 'Owned', 'Healthy', '2025-08-18'),
(18, 16, NULL, 'Livestock', 'cattle', 1, '2025-08-24 16:53:45', '2025-08-24 16:53:45', 57, 'Disseminated', 'Healthy', '2025-08-24'),
(19, 16, NULL, 'Poultry', 'Chicken', 5, '2025-08-25 14:14:46', '2025-08-25 14:14:46', 0, 'Disseminated', 'Healthy', '2025-08-24'),
(20, 17, NULL, 'Livestock', 'cattle', 1, '2025-08-26 08:30:52', '2025-08-26 08:30:52', 98, 'Disseminated', 'Healthy', '2025-08-25'),
(21, 18, NULL, 'Livestock', 'cow', 1, '2025-08-26 09:48:14', '2025-08-26 09:48:14', 65, 'Owned', 'Healthy', '2025-08-25'),
(22, 20, NULL, 'Livestock', 'cow', 1, '2025-08-26 09:57:43', '2025-08-26 09:57:43', 89, 'Disseminated', 'Healthy', '2025-08-25'),
(23, 21, NULL, 'Livestock', 'cattle', 1, '2025-08-26 10:35:56', '2025-08-26 10:35:56', 65, 'Owned', 'Healthy', '2025-08-25'),
(24, 21, NULL, 'Livestock', 'cow', 1, '2025-08-26 11:35:28', '2025-08-26 11:35:28', 67, 'Owned', 'Healthy', '2025-08-25'),
(25, 21, NULL, 'Livestock', 'cattle', 1, '2025-08-26 11:35:55', '2025-08-26 11:35:55', 78, 'Owned', 'Healthy', '2025-08-25');

-- --------------------------------------------------------

--
-- Table structure for table `livestock_served`
--

CREATE TABLE `livestock_served` (
  `served_id` int(11) NOT NULL,
  `prescription_id` int(11) DEFAULT NULL,
  `total_ml` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Read','Unread') DEFAULT 'Unread',
  `client_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `message`, `timestamp`, `status`, `client_id`) VALUES
(1, 1, 'Low stock alert: \'Ivermectin\' added with only 50 units.', '2025-07-08 20:17:49', 'Read', NULL),
(2, 7, 'Your transaction for medicine has been approved.', '2025-07-31 08:23:39', 'Unread', NULL),
(3, 7, 'Your transaction for medicine has been approved.', '2025-07-31 08:23:41', 'Unread', NULL),
(4, 9, 'Your transaction for medicine has been issued.', '2025-07-31 08:30:22', 'Unread', NULL),
(6, 1, 'testing uploaded new photos for cattle (Livestock)', '2025-08-12 16:26:29', 'Read', NULL),
(7, 1, 'testing uploaded new photos for cattle (Livestock)', '2025-08-12 16:34:20', 'Read', NULL),
(11, 1, 'testing uploaded new photos for cattle (Livestock)', '2025-08-13 03:46:44', 'Read', NULL),
(12, 1, 'testing uploaded new photos for cattle (Livestock)', '2025-08-13 04:39:53', 'Read', NULL),
(15, 1, 'testing uploaded new photos for Chicken (Poultry)', '2025-08-13 07:16:29', 'Read', NULL),
(16, 1, 'testing uploaded new photos for cattle (Livestock)', '2025-08-13 07:17:11', 'Read', NULL),
(18, 1, 'testing uploaded new photos for cattle (Livestock)', '2025-08-13 08:49:17', 'Read', NULL),
(19, 1, 'Jenny Rose uploaded new photos for Carabao (Livestock)', '2025-08-22 14:17:17', 'Unread', NULL),
(20, 1, 'clariza garcia uploaded new photos for cattle (Livestock)', '2025-08-25 16:27:48', 'Unread', NULL),
(21, 1, 'clariza garcia uploaded new photos for cattle (Livestock)', '2025-08-25 16:50:13', 'Unread', NULL),
(22, 1, 'testing uploaded new photos for cattle (Livestock)', '2025-08-26 07:26:17', 'Unread', NULL),
(23, 1, 'test lang uploaded new photos for cattle (Livestock)', '2025-08-26 08:31:15', 'Unread', NULL),
(24, 1, 'test lang uploaded new photos for cattle (Livestock)', '2025-08-26 08:33:15', 'Unread', NULL),
(25, 1, 'test lang uploaded new photos for cattle (Livestock)', '2025-08-26 08:46:48', 'Unread', NULL),
(26, 1, 'test lang uploaded new photos for cattle (Livestock)', '2025-08-26 08:59:56', 'Unread', NULL),
(27, NULL, 'Your animal photo has been rejected. Reason: wala lang', '2025-08-26 09:00:21', 'Read', 17),
(28, NULL, 'Your pharmaceutical request for Amoxicillin has been approved by test', '2025-08-26 09:01:40', 'Read', 17),
(29, 1, 'test lang uploaded new photos for cattle (Livestock)', '2025-08-26 09:40:28', 'Unread', NULL),
(30, NULL, 'Your animal photo has been approved by the administrator.', '2025-08-26 09:40:52', 'Unread', 17),
(31, 1, 'sonzaida patricio uploaded new photos for cow (Livestock)', '2025-08-26 09:48:27', 'Unread', NULL),
(32, NULL, 'Your animal photo has been approved by the administrator.', '2025-08-26 09:48:50', 'Unread', 18),
(33, 1, 'ambot uploaded new photos for cow (Livestock)', '2025-08-26 09:59:00', 'Unread', NULL),
(34, NULL, 'Your animal photo has been approved by the administrator.', '2025-08-26 09:59:20', 'Unread', 20),
(35, 1, 'weh uploaded new photos for cattle (Livestock)', '2025-08-26 10:36:24', 'Unread', NULL),
(36, NULL, 'Your animal photo has been approved by the administrator.', '2025-08-26 10:36:44', 'Unread', 21);

-- --------------------------------------------------------

--
-- Table structure for table `pharmaceuticals`
--

CREATE TABLE `pharmaceuticals` (
  `pharma_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `category` varchar(100) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pharmaceuticals`
--

INSERT INTO `pharmaceuticals` (`pharma_id`, `name`, `description`, `stock`, `category`, `unit`, `expiry_date`, `created_at`, `updated_at`) VALUES
(1, 'Hog Colera', 'Classical Swine Fever', 1, 'Vaccines', 'Vi', '2025-10-29', '2025-07-08 13:44:24', '2025-07-17 16:02:12'),
(2, 'Amoxicillin', 'Treats bacterial infections in animals (e.g., respiratory, skin, urinary).', 66, 'Antibiotics', 'Capsules', '2025-10-20', '2025-07-08 15:09:31', '2025-07-17 16:02:12'),
(3, 'Rabies Vaccine', 'Prevents rabies infection; required for many pets and livestock.', 48, 'Vaccine', 'Vials', '2025-11-21', '2025-07-08 15:11:12', '2025-07-17 16:02:12'),
(5, 'Ivermectin', 'Used to control internal and external parasites like worms and mites.', 100, 'Antiparasitic', 'Sachets', '2025-11-08', '2025-07-08 20:15:55', '2025-07-17 16:02:12'),
(9, 'Felbendezole', 'Treats roundworms, hookworms, whipworms, lungworms, and certain tapeworms', 100, 'Dewormer', 'Tablets', '2025-10-23', '2025-08-12 17:21:20', '2025-08-12 17:21:20'),
(10, 'vit', NULL, 500, 'Antiparasitic', 'Vials', '2025-10-24', '2025-08-23 17:11:51', '2025-08-23 17:11:51');

-- --------------------------------------------------------

--
-- Table structure for table `pharmaceutical_requests`
--

CREATE TABLE `pharmaceutical_requests` (
  `request_id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `req_type` enum('Livestock','Poultry') DEFAULT NULL,
  `species` varchar(100) DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `estimated_weight` double DEFAULT NULL,
  `pharma_id` int(11) DEFAULT NULL,
  `poultry_quantity` int(11) DEFAULT NULL,
  `weight` double DEFAULT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Approved','Issued') DEFAULT 'Pending',
  `quantity` int(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pharmaceutical_requests`
--

INSERT INTO `pharmaceutical_requests` (`request_id`, `client_id`, `req_type`, `species`, `symptoms`, `estimated_weight`, `pharma_id`, `poultry_quantity`, `weight`, `request_date`, `status`, `quantity`) VALUES
(1, 4, NULL, NULL, NULL, NULL, NULL, 3, 45, '2025-07-16 20:15:03', 'Pending', 0),
(2, 4, NULL, NULL, NULL, NULL, 1, 3, 43, '2025-07-16 20:15:29', 'Approved', 0),
(3, 4, NULL, NULL, NULL, NULL, 5, 2, 30, '2025-07-16 20:17:48', 'Approved', 0),
(4, 4, NULL, NULL, NULL, NULL, 2, 5, 55, '2025-07-16 20:30:40', 'Approved', 0),
(5, 8, NULL, NULL, NULL, NULL, 1, 4, NULL, '2025-07-31 01:52:19', 'Approved', 0),
(6, 8, NULL, NULL, NULL, NULL, 1, 50, NULL, '2025-08-13 03:47:27', 'Approved', 0),
(7, 8, NULL, NULL, NULL, NULL, 1, 70, 77, '2025-08-13 03:57:08', 'Pending', 0),
(8, 8, NULL, NULL, NULL, NULL, 3, 2, NULL, '2025-08-13 04:11:40', 'Approved', 0),
(9, 17, NULL, NULL, NULL, NULL, 1, 45, 65, '2025-08-26 08:50:29', 'Approved', 0),
(10, 17, NULL, NULL, NULL, NULL, 2, 6, 54, '2025-08-26 09:01:18', 'Approved', 0);

-- --------------------------------------------------------

--
-- Table structure for table `pharmaceutical_request_items`
--

CREATE TABLE `pharmaceutical_request_items` (
  `item_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `pharma_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `report_type` varchar(100) DEFAULT NULL,
  `date_generated` date DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `pharma_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `barangay` varchar(255) NOT NULL,
  `status` enum('Pending','Approved') DEFAULT 'Approved',
  `request_date` date NOT NULL DEFAULT current_timestamp(),
  `issued_date` timestamp NULL DEFAULT NULL,
  `type` enum('Livestock','Poultry','Pharmaceutical') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `client_id`, `user_id`, `pharma_id`, `quantity`, `barangay`, `status`, `request_date`, `issued_date`, `type`) VALUES
(1, 4, 1, 1, 3, '', 'Approved', '2025-07-16', '2025-07-16 21:02:08', NULL),
(2, 1, NULL, 2, 60, '', 'Pending', '2025-07-16', '0000-00-00 00:00:00', NULL),
(3, 7, NULL, 5, 6, '', 'Approved', '2025-07-31', '2025-07-31 00:00:00', NULL),
(4, 7, NULL, 5, 6, '', 'Approved', '2025-07-31', '2025-07-31 00:00:00', NULL),
(6, 1, NULL, 2, 70, 'Alianza', 'Pending', '2025-08-13', '2025-08-13 00:00:00', NULL),
(7, 1, NULL, 9, 99, 'Atipuluan', 'Pending', '2025-08-13', '2025-08-13 00:00:00', NULL),
(8, 10, NULL, 5, 10, 'Alianza', 'Approved', '2025-08-13', '2025-08-13 00:00:00', NULL),
(9, 8, 1, 1, 50, '', 'Approved', '2025-08-13', '2025-08-13 03:47:42', NULL),
(10, 8, 1, 1, 4, '', 'Approved', '2025-08-13', '2025-08-13 03:57:38', NULL),
(11, 8, 1, 3, 2, '', 'Approved', '2025-08-13', '2025-08-13 04:12:16', NULL),
(12, 1, NULL, 2, 23, 'Alianza', 'Approved', '2025-08-13', '0000-00-00 00:00:00', NULL),
(13, 10, NULL, 3, 50, 'Binubuhan', 'Approved', '2025-08-13', '2025-08-13 00:00:00', NULL),
(14, 4, 1, 2, 5, '', 'Approved', '2025-08-13', '2025-08-13 07:45:18', NULL),
(15, 17, 1, 1, 45, '', 'Approved', '2025-08-26', '2025-08-26 08:51:16', NULL),
(16, 17, 1, 2, 6, '', 'Approved', '2025-08-26', '2025-08-26 09:01:40', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `contact_number` int(15) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` varchar(500) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `role` enum('staff','admin','client') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `contact_number`, `username`, `password`, `address`, `profile_photo`, `role`, `created_at`, `status`) VALUES
(1, 'test', 921134152, 'test', '$2y$10$AGqecrzQAc2XFeIwaZrGg.jEcZ5aEBEGKf9SZ.j6Pkrtfy/RD2XTi', '', 'uploads/profile_pictures/profile_1_1755017915.png', 'admin', '2025-05-02 10:09:18', 'Active'),
(7, 'jennn mission', 2147483647, 'jen', '$2y$10$lgC4I/fOyyuQ3F0UQh2wleCKm3AdADFrKbenFy1rVExsKWgerIWQO', '', 'uploads/profile_photos/profile_7_1752705795.png', 'staff', '2025-07-16 22:41:29', 'Active'),
(8, 'jenny ', 2147483647, 'jenny', '$2y$10$ziKeLo.nskVlZFapQ06RlefO0lSxRjWBlBTfBN6h8KPmkpz0hz5aC', '', NULL, 'staff', '2025-07-17 15:57:16', 'Active'),
(9, 'test 7', 2147483647, 'test7', '$2y$10$yiftON7aGOmRYbTk5HD4Heyx2CTobxO8xvCRWvKHJZ99oZHaq42VC', '', NULL, 'staff', '2025-07-17 16:48:46', 'Active'),
(13, 'vivien patricio', 2147483647, 'vivien', '$2y$10$rcJ4MuhZZQ8/EC8f9S/PjuvB7Dwc3vNIIg3ZkCzChr3zjs/O4UF52', '', NULL, 'staff', '2025-08-22 15:03:44', 'Active'),
(14, 'lovely shane', 2147483647, 'lovely', '$2y$10$qCgTdqxFCgfXro6wsjf/XeA5.unPeTdj6di19.kO4o6io8D/kjiAa', '', NULL, 'staff', '2025-08-24 18:06:05', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `vet_prescription`
--

CREATE TABLE `vet_prescription` (
  `prescription_id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `animal_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `animal_photos`
--
ALTER TABLE `animal_photos`
  ADD PRIMARY KEY (`photo_id`),
  ADD KEY `fk_animal_photos_reviewed_by` (`reviewed_by`),
  ADD KEY `animal_id` (`animal_id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`client_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `livestock_poultry`
--
ALTER TABLE `livestock_poultry`
  ADD PRIMARY KEY (`animal_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `livestock_served`
--
ALTER TABLE `livestock_served`
  ADD PRIMARY KEY (`served_id`),
  ADD KEY `prescription_id` (`prescription_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `fk_user_id` (`user_id`),
  ADD KEY `fk_client_id` (`client_id`);

--
-- Indexes for table `pharmaceuticals`
--
ALTER TABLE `pharmaceuticals`
  ADD PRIMARY KEY (`pharma_id`);

--
-- Indexes for table `pharmaceutical_requests`
--
ALTER TABLE `pharmaceutical_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `pharma_id` (`pharma_id`);

--
-- Indexes for table `pharmaceutical_request_items`
--
ALTER TABLE `pharmaceutical_request_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `fk_pr_items_pharma` (`pharma_id`),
  ADD KEY `fk_pr_items_user` (`created_by`),
  ADD KEY `idx_pr_items_request` (`request_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `pharma_id` (`pharma_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `vet_prescription`
--
ALTER TABLE `vet_prescription`
  ADD PRIMARY KEY (`prescription_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `animal_id` (`animal_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

--
-- AUTO_INCREMENT for table `animal_photos`
--
ALTER TABLE `animal_photos`
  MODIFY `photo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `client_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `livestock_poultry`
--
ALTER TABLE `livestock_poultry`
  MODIFY `animal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `livestock_served`
--
ALTER TABLE `livestock_served`
  MODIFY `served_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `pharmaceuticals`
--
ALTER TABLE `pharmaceuticals`
  MODIFY `pharma_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `pharmaceutical_requests`
--
ALTER TABLE `pharmaceutical_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `pharmaceutical_request_items`
--
ALTER TABLE `pharmaceutical_request_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `vet_prescription`
--
ALTER TABLE `vet_prescription`
  MODIFY `prescription_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `animal_photos`
--
ALTER TABLE `animal_photos`
  ADD CONSTRAINT `fk_animal_photos_animal_id` FOREIGN KEY (`animal_id`) REFERENCES `livestock_poultry` (`animal_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_animal_photos_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `livestock_poultry`
--
ALTER TABLE `livestock_poultry`
  ADD CONSTRAINT `livestock_poultry_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`);

--
-- Constraints for table `livestock_served`
--
ALTER TABLE `livestock_served`
  ADD CONSTRAINT `livestock_served_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `vet_prescription` (`prescription_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_client_id` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pharmaceutical_requests`
--
ALTER TABLE `pharmaceutical_requests`
  ADD CONSTRAINT `pharmaceutical_requests_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`),
  ADD CONSTRAINT `pharmaceutical_requests_ibfk_2` FOREIGN KEY (`pharma_id`) REFERENCES `pharmaceuticals` (`pharma_id`);

--
-- Constraints for table `pharmaceutical_request_items`
--
ALTER TABLE `pharmaceutical_request_items`
  ADD CONSTRAINT `fk_pr_items_pharma` FOREIGN KEY (`pharma_id`) REFERENCES `pharmaceuticals` (`pharma_id`),
  ADD CONSTRAINT `fk_pr_items_request` FOREIGN KEY (`request_id`) REFERENCES `pharmaceutical_requests` (`request_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pr_items_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`),
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`pharma_id`) REFERENCES `pharmaceuticals` (`pharma_id`);

--
-- Constraints for table `vet_prescription`
--
ALTER TABLE `vet_prescription`
  ADD CONSTRAINT `vet_prescription_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`),
  ADD CONSTRAINT `vet_prescription_ibfk_2` FOREIGN KEY (`animal_id`) REFERENCES `livestock_poultry` (`animal_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
