-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 18, 2025 at 01:29 PM
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
-- Database: `bcvoims`
--

-- --------------------------------------------------------

--
-- Table structure for table `pharmaceutical_requests`
--

CREATE TABLE `pharmaceutical_requests` (
  `request_id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `category` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `pharma_id` int(11) DEFAULT NULL,
  `poultry_quantity` int(11) DEFAULT NULL,
  `weight` double DEFAULT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Approved','Issued') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pharmaceutical_requests`
--

INSERT INTO `pharmaceutical_requests` (`request_id`, `client_id`, `category`, `name`, `pharma_id`, `poultry_quantity`, `weight`, `request_date`, `status`) VALUES
(10, 3, 'Vaccine', 'Viton', NULL, 1, 32, '2025-05-05 18:16:49', 'Pending'),
(11, 3, 'Vaccine', 'Viton', NULL, 1, 32, '2025-05-05 18:17:04', 'Pending'),
(13, 3, 'Vaccine', 'Viton', NULL, 1, 32, '2025-05-05 18:19:09', 'Pending'),
(15, 3, 'Vaccine', 'Viton', NULL, 1, 32, '2025-05-05 18:20:23', 'Pending'),
(16, 3, 'Dewormer', 'Albendezole', NULL, 20, 10, '2025-05-05 18:21:31', 'Pending'),
(17, 3, 'Dewormer', 'Albendezole', NULL, 20, 10, '2025-05-05 18:22:39', 'Pending'),
(18, 3, 'Dewormer', 'Albendezole', NULL, 3, 32, '2025-05-05 18:56:45', 'Pending'),
(19, 3, 'Vaccine', 'Albendezole', NULL, 6, 32, '2025-05-05 18:57:09', 'Pending'),
(20, 3, 'Vaccine', 'Albendezole', NULL, 6, 32, '2025-05-05 19:06:30', 'Pending'),
(27, 3, 'Hog Cholera', 'Hog Coloera', NULL, 1, 32, '2025-05-18 09:53:50', 'Pending');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pharmaceutical_requests`
--
ALTER TABLE `pharmaceutical_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `pharma_id` (`pharma_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pharmaceutical_requests`
--
ALTER TABLE `pharmaceutical_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pharmaceutical_requests`
--
ALTER TABLE `pharmaceutical_requests`
  ADD CONSTRAINT `pharmaceutical_requests_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`),
  ADD CONSTRAINT `pharmaceutical_requests_ibfk_2` FOREIGN KEY (`pharma_id`) REFERENCES `pharmaceuticals` (`pharma_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
