-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 23, 2026 at 07:22 PM
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
  `phone_number` varchar(15) NOT NULL,
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

INSERT INTO `user` (`user_id`, `user_name`, `user_email`, `phone_number`, `user_password`, `user_role`, `user_gender`, `user_age`, `user_weight`, `user_height`, `user_activity_level`, `user_bmi`, `user_bmr`, `user_tdee`, `created_at`, `updated_at`, `user_goal`, `user_target_calories`, `user_bmi_category`) VALUES
(3, 'admin67', 'admin67@gmail.com', '', '$2y$10$ZpQL/7ukzqsqIpiwwYhxuOY57xRy3X0cgwTta4vE9Q5mAfcGWhQ1G', 'admin', 'male', 30, 65.00, 175.00, 1.725, 21.22, 1598.75, 2757.84, '2026-10-22 10:33:20', '2026-10-22 10:34:02', NULL, NULL, 'น้ำหนักปกติ'),
(4, 'danaiphak', 'danaiphak@gmail.com', '', '$2y$10$EchSahwtPp6xVw1v.3aT3eYvD6c3E6H2kRATnIYHKu7nuDF.Vmtze', 'user', 'male', 21, 61.00, 167.00, 1.725, 21.87, 1553.75, 2680.22, '2026-10-22 10:34:33', '2026-10-22 14:59:12', NULL, NULL, 'น้ำหนักปกติ'),
(5, 'alice', 'alice@gmail.com', '', '$2y$10$quhW8Hf.pWgCuZKI6z6zLeamLz7QYtBinT/7rGrppv2Y4s2QPMn92', 'user', 'female', 20, 50.00, 158.00, 1.550, 20.03, 1226.50, 1901.08, '2026-10-22 15:03:43', '2026-10-22 15:04:11', NULL, NULL, 'น้ำหนักปกติ'),
(6, 'paratakorn', 'paratakorn@gmail.com', '', '$2y$10$n16DUGGAB9mfBzHTr3MnZOBrU9WzzS3ieyfLuE61Y0qTWB2/KAcCG', 'user', 'male', 20, 60.00, 180.00, 1.200, 18.52, 1630.00, 1956.00, '2026-10-22 19:14:41', '2026-10-22 19:15:23', NULL, NULL, 'น้ำหนักปกติ'),
(7, '6767', 'sattawut@gmail.com', '', '$2y$10$DxiEi6NC2vBYsTQklhfGne9hmjFZDkhT8hlnVdP7.rqIj7uuwRX7q', 'user', 'male', 20, 50.00, 170.00, 1.200, 17.30, 1467.50, 1761.00, '2026-10-23 02:53:39', '2026-10-23 02:58:40', NULL, NULL, 'น้ำหนักน้อยกว่าเกณฑ์'),
(8, 'bmpha', 'bmphatthadon2549@icloud.com', '', '$2y$10$.yOViSyD7CRWT7AvtVIAzOH69oHTEUkV0401PsLaMLi34XncQxzlC', 'user', 'male', 20, 60.00, 178.00, 1.550, 18.94, 1617.50, 2507.13, '2026-10-23 03:09:04', '2026-10-23 03:19:05', NULL, NULL, 'น้ำหนักปกติ'),
(9, 'newbietest', 'newbietest@gmail.com', '', '$2y$10$c0VDC8MnKuR1Mkj9DoW5T.dng8gAV1n1BRwK/8Y8murS.t30ck8O6', 'user', 'male', 45, 60.00, 168.00, 1.725, 21.26, 1430.00, 2466.75, '2026-10-23 03:16:42', '2026-10-23 03:24:55', NULL, NULL, 'น้ำหนักปกติ'),
(12, 'dewdekdee', 'dewdekdee@gmail.com', '0981234567', '$2y$10$ni6hsiF.SDO2Fh/zG6nWD.oKnxnIjrxcq4pgFn7Ju0xmvx/GpQinC', 'user', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-23 04:11:13', NULL, NULL, NULL, NULL),
(13, 'satawut', 'satawut@gmail.com', '0975632167', '$2y$10$1xGdFsbF8G1cr41K9LiNTujrOoEyuUrafr0tz2R9jZDkAA0tocX3m', 'user', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-23 04:19:26', NULL, NULL, NULL, NULL);

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
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
