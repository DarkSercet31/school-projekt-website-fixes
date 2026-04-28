-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 20, 2026 at 02:50 PM
-- Server version: 8.2.0
-- PHP Version: 8.3.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `weather_station_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `collection`
--

CREATE TABLE `collection` (
  `pk_collection` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text,
  `fk_user_creates` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collection`
--

INSERT INTO `collection` (`pk_collection`, `name`, `description`, `fk_user_creates`) VALUES
(5, 'a', 'a', 'test');

-- --------------------------------------------------------

--
-- Table structure for table `contains`
--

CREATE TABLE `contains` (
  `pkfk_measurement` int NOT NULL,
  `pkfk_collection` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contains`
--

INSERT INTO `contains` (`pkfk_measurement`, `pkfk_collection`) VALUES
(71, 5),
(72, 5),
(73, 5),
(74, 5),
(75, 5),
(76, 5),
(77, 5),
(78, 5),
(79, 5),
(80, 5);

-- --------------------------------------------------------

--
-- Table structure for table `hasaccess`
--

CREATE TABLE `hasaccess` (
  `pkfk_collection` int NOT NULL,
  `pkfk_user` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `isfriend`
--

CREATE TABLE `isfriend` (
  `pkfk_user_user` varchar(50) NOT NULL,
  `pkfk_user_friend` varchar(50) NOT NULL,
  `status` enum('pending','accepted','declined') NOT NULL DEFAULT 'pending',
  `requested_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `isfriend`
--

INSERT INTO `isfriend` (`pkfk_user_user`, `pkfk_user_friend`, `status`, `requested_at`) VALUES
('Anes', 'sarah', 'pending', '2026-01-25 15:16:46'),
('max', 'sarah', 'pending', '2026-01-25 15:16:46'),
('samir', 'test', 'accepted', '2026-01-26 19:06:47'),
('test', 'admin', 'pending', '2026-01-25 15:26:02'),
('test', 'ilhan', 'pending', '2026-01-26 18:28:03'),
('test', 'Luka', 'pending', '2026-01-25 15:28:43'),
('test', 'max', 'pending', '2026-01-25 15:21:57'),
('test', 'samir', 'accepted', '2026-01-26 19:07:10');

-- --------------------------------------------------------

--
-- Table structure for table `measurement`
--

CREATE TABLE `measurement` (
  `pk_measurement` int NOT NULL,
  `temperature` decimal(5,2) DEFAULT NULL,
  `humidity` decimal(5,2) DEFAULT NULL,
  `pressure` decimal(6,2) DEFAULT NULL,
  `light` decimal(6,2) DEFAULT NULL,
  `gas` decimal(6,2) DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL,
  `fk_station_records` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `measurement`
--

INSERT INTO `measurement` (`pk_measurement`, `temperature`, `humidity`, `pressure`, `light`, `gas`, `timestamp`, `fk_station_records`) VALUES
(71, 21.50, 45.00, 1013.25, 250.00, 420.00, '2025-12-24 08:00:00', 'SN-1001'),
(72, 21.80, 44.00, 1013.30, 450.00, 415.00, '2025-12-24 09:00:00', 'SN-1001'),
(73, 22.10, 43.00, 1013.28, 680.00, 410.00, '2025-12-24 10:00:00', 'SN-1001'),
(74, 22.30, 42.00, 1013.25, 720.00, 405.00, '2025-12-24 11:00:00', 'SN-1001'),
(75, 22.50, 41.00, 1013.20, 750.00, 400.00, '2025-12-24 12:00:00', 'SN-1001'),
(76, 22.40, 42.00, 1013.18, 740.00, 408.00, '2025-12-24 13:00:00', 'SN-1001'),
(77, 22.20, 43.00, 1013.22, 680.00, 412.00, '2025-12-24 14:00:00', 'SN-1001'),
(78, 21.90, 44.00, 1013.25, 500.00, 418.00, '2025-12-24 15:00:00', 'SN-1001'),
(79, 21.60, 45.00, 1013.28, 300.00, 422.00, '2025-12-24 16:00:00', 'SN-1001'),
(80, 21.20, 47.00, 1013.30, 150.00, 428.00, '2025-12-24 17:00:00', 'SN-1001'),
(82, 19.80, 51.00, 1013.30, 50.00, 445.00, '2025-12-24 09:00:00', 'SN-1002'),
(83, 20.20, 50.00, 1013.28, 200.00, 440.00, '2025-12-24 10:00:00', 'SN-1002'),
(84, 20.50, 49.00, 1013.25, 300.00, 435.00, '2025-12-24 11:00:00', 'SN-1002'),
(85, 20.80, 48.00, 1013.20, 350.00, 430.00, '2025-12-24 12:00:00', 'SN-1002'),
(86, 21.00, 47.00, 1013.18, 400.00, 428.00, '2025-12-24 13:00:00', 'SN-1002'),
(87, 20.90, 48.00, 1013.22, 380.00, 432.00, '2025-12-24 14:00:00', 'SN-1002'),
(88, 20.60, 49.00, 1013.25, 250.00, 438.00, '2025-12-24 15:00:00', 'SN-1002'),
(89, 20.20, 50.00, 1013.28, 100.00, 443.00, '2025-12-24 16:00:00', 'SN-1002'),
(90, 19.80, 52.00, 1013.30, 20.00, 450.00, '2025-12-24 17:00:00', 'SN-1002'),
(91, 23.20, 40.00, 1013.25, 600.00, 380.00, '2025-12-24 08:00:00', 'SN-1003'),
(92, 23.50, 39.00, 1013.30, 700.00, 375.00, '2025-12-24 09:00:00', 'SN-1003'),
(93, 24.10, 38.00, 1013.28, 750.00, 370.00, '2025-12-24 10:00:00', 'SN-1003'),
(94, 24.50, 36.00, 1013.25, 800.00, 365.00, '2025-12-24 11:00:00', 'SN-1003'),
(95, 24.80, 35.00, 1013.20, 820.00, 360.00, '2025-12-24 12:00:00', 'SN-1003'),
(96, 24.60, 36.00, 1013.18, 810.00, 362.00, '2025-12-24 13:00:00', 'SN-1003'),
(97, 24.20, 37.00, 1013.22, 770.00, 368.00, '2025-12-24 14:00:00', 'SN-1003'),
(98, 23.80, 39.00, 1013.25, 650.00, 378.00, '2025-12-24 15:00:00', 'SN-1003'),
(99, 23.40, 40.00, 1013.28, 500.00, 385.00, '2025-12-24 16:00:00', 'SN-1003'),
(100, 23.00, 41.00, 1013.30, 400.00, 390.00, '2025-12-24 17:00:00', 'SN-1003'),
(101, 20.80, 44.00, 1013.50, 110.00, 295.00, '2026-01-02 22:34:10', 'SN-1001'),
(102, 21.20, 45.00, 1013.40, 115.00, 298.00, '2026-01-03 09:34:10', 'SN-1001'),
(103, 21.90, 47.00, 1013.10, 130.00, 305.00, '2026-01-03 18:34:10', 'SN-1001'),
(104, 19.50, 42.00, 1014.20, 90.00, 280.00, '2025-12-28 21:34:34', 'SN-1001'),
(105, 20.00, 43.00, 1014.00, 95.00, 285.00, '2025-12-30 21:34:34', 'SN-1001'),
(106, 20.30, 44.00, 1013.80, 100.00, 290.00, '2026-01-01 21:34:34', 'SN-1001');

-- --------------------------------------------------------

--
-- Table structure for table `station`
--

CREATE TABLE `station` (
  `pk_serialNumber` varchar(50) NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text,
  `fk_user_owns` varchar(50) DEFAULT NULL,
  `status` enum('Active','Paused') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `station`
--

INSERT INTO `station` (`pk_serialNumber`, `name`, `description`, `fk_user_owns`, `status`) VALUES
('SN-1001', 'Wohnzimmer', 'Sensor im Wohnzimmer', 'Anes', 'Paused'),
('SN-1002', 'Schlafzimmer', 'Sensor im Schlafzimmer', 'Anes', 'Active'),
('SN-1003', 'Küche', 'Sensor in der Küche', 'Anes', 'Active'),
('SN-1004', 'Büro', 'Sensor im Homeoffice', 'test', 'Paused'),
('SN-1006', 'hause', '1', 'Luka', 'Active'),
('SN-1067', 'SN-1067', '', 'Luka20083', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `pk_username` varchar(50) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(50) NOT NULL,
  `role` enum('User','Admin') NOT NULL DEFAULT 'User',
  `code` int DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'notverified',
  `mustChangePassword` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`pk_username`, `firstName`, `lastName`, `password`, `email`, `role`, `code`, `status`, `mustChangePassword`) VALUES
('admin', 'Admin', 'User', '$2y$10$SjJudTh3V3MyZzAwdFdhLuWj.YxVJ.hLN8OGqXxG9LrVB5Yty7TRe', 'admin@example.com', 'Admin', NULL, 'verified', 0),
('max', 'Max', 'Mustermann', '$2y$10$SjJudTh3V3MyZzAwdFdhLuWj.YxVJ.hLN8OGqXxG9LrVB5Yty7TRe', 'max@example.com', 'User', NULL, 'notverified', 0),
('sarah', 'Sarah', 'Schmidt', '$2y$10$SjJudTh3V3MyZzAwdFdhLuWj.YxVJ.hLN8OGqXxG9LrVB5Yty7TRe', 'sarah@example.com', 'User', NULL, 'notverified', 0),
('test', 't', 'A', '$2y$10$HYSywWZsB/pt.ElbpfvTUeCbhPp1qSwU5raErBXGtABPxL1QbiMIa', 'A1@gmail.com', 'Admin', NULL, 'verified', 0),
('test1', 'a', '1', '$2y$10$/ApIXer6O4KNKVnV7GjUUukApCYwSQyWYo4dUqRryOjrKpPlrRfmq', 'test1@gmail.com', 'User', NULL, 'verified', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `collection`
--
ALTER TABLE `collection`
  ADD PRIMARY KEY (`pk_collection`),
  ADD KEY `fk_collection_user` (`fk_user_creates`);

--
-- Indexes for table `contains`
--
ALTER TABLE `contains`
  ADD PRIMARY KEY (`pkfk_measurement`,`pkfk_collection`),
  ADD KEY `fk_contains_collection` (`pkfk_collection`);

--
-- Indexes for table `hasaccess`
--
ALTER TABLE `hasaccess`
  ADD PRIMARY KEY (`pkfk_collection`,`pkfk_user`),
  ADD KEY `fk_hasAccess_user` (`pkfk_user`);

--
-- Indexes for table `isfriend`
--
ALTER TABLE `isfriend`
  ADD PRIMARY KEY (`pkfk_user_user`,`pkfk_user_friend`),
  ADD KEY `fk_isFriend_friend` (`pkfk_user_friend`);

--
-- Indexes for table `measurement`
--
ALTER TABLE `measurement`
  ADD PRIMARY KEY (`pk_measurement`),
  ADD KEY `fk_measurement_station` (`fk_station_records`);

--
-- Indexes for table `station`
--
ALTER TABLE `station`
  ADD PRIMARY KEY (`pk_serialNumber`),
  ADD KEY `fk_station_user` (`fk_user_owns`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`pk_username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `collection`
--
ALTER TABLE `collection`
  MODIFY `pk_collection` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `measurement`
--
ALTER TABLE `measurement`
  MODIFY `pk_measurement` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `collection`
--
ALTER TABLE `collection`
  ADD CONSTRAINT `fk_collection_user` FOREIGN KEY (`fk_user_creates`) REFERENCES `user` (`pk_username`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `contains`
--
ALTER TABLE `contains`
  ADD CONSTRAINT `fk_contains_collection` FOREIGN KEY (`pkfk_collection`) REFERENCES `collection` (`pk_collection`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_contains_measurement` FOREIGN KEY (`pkfk_measurement`) REFERENCES `measurement` (`pk_measurement`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `hasaccess`
--
ALTER TABLE `hasaccess`
  ADD CONSTRAINT `fk_hasAccess_collection` FOREIGN KEY (`pkfk_collection`) REFERENCES `collection` (`pk_collection`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hasAccess_user` FOREIGN KEY (`pkfk_user`) REFERENCES `user` (`pk_username`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
