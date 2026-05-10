-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 10, 2026 at 10:03 AM
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
-- Database: `sddproject`
--

-- --------------------------------------------------------

--
-- Table structure for table `admincheck`
--

CREATE TABLE `admincheck` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admincheck`
--

INSERT INTO `admincheck` (`id`, `username`, `password`) VALUES
(1, 'admin', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

CREATE TABLE `candidates` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `newaccountregistration`
--

CREATE TABLE `newaccountregistration` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `retype_password` varchar(255) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(15) NOT NULL,
  `voters_id_number` varchar(50) NOT NULL,
  `status` enum('pending','active','suspend') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `newaccountregistration`
--

INSERT INTO `newaccountregistration` (`id`, `username`, `email`, `password`, `retype_password`, `date_of_birth`, `gender`, `address`, `phone`, `voters_id_number`, `status`, `created_at`) VALUES
(1, 'bimochan', '2004sumanmishra@gmail.com', 'bimochan', 'bimochan', '2003-11-21', 'male', 'tarakeswar', '9848444526', '123456', 'active', '2025-06-08 15:05:06'),
(2, 'test1', 'test!@gmail.com', 'pass123', 'pass123', '2222-02-22', 'male', 'tarakeswar', '9848444526', '69', 'active', '2025-06-09 01:28:04'),
(3, 'test2', 'test2@gmail.com', 'test2', 'test2', '2000-01-02', 'male', 'tarakeswar', '9848444526', '98', 'active', '2025-06-09 01:34:19'),
(4, 'bimo', 'bimo12@gmail.com', 'bimo', 'bimo', '2003-11-21', 'male', 'dsadasd', '9841266144', '12345678', 'active', '2025-06-09 02:50:49'),
(5, 'susi', '20044sumanmishra@gmail.com', '123456', '123456', '2000-01-01', 'male', 'tarakeswar', '9848444526', '12134', 'pending', '2026-01-09 13:10:40'),
(6, 'suman', '2004sumanmishra@gmail.com', '123456', '123456', '2002-01-10', 'male', 'tarakeswar', '9848444526', '203340', 'pending', '2026-05-10 07:47:27');

-- --------------------------------------------------------

--
-- Table structure for table `voting_system`
--

CREATE TABLE `voting_system` (
  `candidate_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `candidate_name` varchar(100) NOT NULL,
  `voted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `voting_system`
--

INSERT INTO `voting_system` (`candidate_id`, `username`, `candidate_name`, `voted_at`) VALUES
(1, 'bimochan', 'Person B', '2025-06-08 15:05:41'),
(2, 'test1', '', '2025-06-09 01:28:49'),
(3, 'bimo', '', '2025-06-09 02:51:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admincheck`
--
ALTER TABLE `admincheck`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `newaccountregistration`
--
ALTER TABLE `newaccountregistration`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `voters_id_number` (`voters_id_number`);

--
-- Indexes for table `voting_system`
--
ALTER TABLE `voting_system`
  ADD PRIMARY KEY (`candidate_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admincheck`
--
ALTER TABLE `admincheck`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `candidates`
--
ALTER TABLE `candidates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `newaccountregistration`
--
ALTER TABLE `newaccountregistration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `voting_system`
--
ALTER TABLE `voting_system`
  MODIFY `candidate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
