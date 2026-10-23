-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2026 at 12:52 PM
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
-- Database: `userdb_bmi&tdee`
--

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `user_email` varchar(100) NOT NULL,
  `user_password` varchar(255) NOT NULL,
  `user_role` enum('user','admin') NOT NULL DEFAULT 'user',
  `user_gender` enum('male','female') DEFAULT NULL,
  `user_age` int(11) DEFAULT NULL,
  `user_weight` decimal(5,2) DEFAULT NULL,
  `user_height` decimal(5,2) DEFAULT NULL,
  `user_activity_level` decimal(4,3) DEFAULT NULL,
  `user_bmi` decimal(4,2) DEFAULT NULL,
  `user_bmr` decimal(6,2) DEFAULT NULL,
  `user_tdee` decimal(6,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `user_goal` enum('lose','maintain','gain') DEFAULT NULL,
  `user_target_calories` decimal(6,2) DEFAULT NULL,
  `user_bmi_category` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `user_name`, `user_email`, `user_password`, `user_role`, `user_gender`, `user_age`, `user_weight`, `user_height`, `user_activity_level`, `user_bmi`, `user_bmr`, `user_tdee`, `created_at`, `updated_at`, `user_goal`, `user_target_calories`, `user_bmi_category`) VALUES
(3, 'admin67', 'admin67@gmail.com', '$2y$10$ZpQL/7ukzqsqIpiwwYhxuOY57xRy3X0cgwTta4vE9Q5mAfcGWhQ1G', 'admin', 'male', 30, 65.00, 175.00, 1.725, 21.22, 1598.75, 2757.84, '2026-10-22 10:33:20', '2026-10-22 10:34:02', NULL, NULL, 'น้ำหนักปกติ'),
(4, 'danaiphak', 'danaiphak@gmail.com', '$2y$10$EchSahwtPp6xVw1v.3aT3eYvD6c3E6H2kRATnIYHKu7nuDF.Vmtze', 'user', 'male', 21, 63.00, 167.00, 1.375, 22.59, 1573.75, 2163.91, '2026-10-22 10:34:33', '2026-10-22 10:35:03', NULL, NULL, 'น้ำหนักปกติ');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_user_email` (`user_email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
