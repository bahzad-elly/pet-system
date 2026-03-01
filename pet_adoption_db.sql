-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 01, 2026 at 04:54 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pet_adoption_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `adopters`
--

CREATE TABLE `adopters` (
  `adopterId` int(11) NOT NULL,
  `fname` varchar(50) NOT NULL,
  `lname` varchar(50) NOT NULL,
  `DoB` date DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `preference` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `adoption`
--

CREATE TABLE `adoption` (
  `adoption_id` int(11) NOT NULL,
  `adopter_id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL,
  `adoptiondate` date NOT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `animals`
--

CREATE TABLE `animals` (
  `animal_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `gender` enum('Male','Female','Unknown') DEFAULT 'Unknown',
  `species` varchar(50) NOT NULL,
  `breed` varchar(50) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `status` enum('Available','Adopted','Pending','Medical Care') DEFAULT 'Available',
  `photo` varchar(255) DEFAULT NULL,
  `intake_id` int(11) DEFAULT NULL,
  `dateadded` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `animals`
--

INSERT INTO `animals` (`animal_id`, `name`, `gender`, `species`, `breed`, `age`, `status`, `photo`, `intake_id`, `dateadded`) VALUES
(3, 'max', 'Male', 'dog', 'wekk', 3, 'Pending', '1772313548_garmian-logo.png', NULL, '2026-02-28 21:19:08'),
(4, 'susu', 'Female', 'Dogs', 'nunu', 5, 'Adopted', NULL, NULL, '2026-02-28 21:47:39');

-- --------------------------------------------------------

--
-- Table structure for table `animal_vaccination`
--

CREATE TABLE `animal_vaccination` (
  `av_id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL,
  `vtype_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `nextDate` date DEFAULT NULL,
  `userId` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `intake_source`
--

CREATE TABLE `intake_source` (
  `iid` int(11) NOT NULL,
  `sname` varchar(100) NOT NULL,
  `stype` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_record`
--

CREATE TABLE `medical_record` (
  `record_id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL,
  `treatment` text NOT NULL,
  `treatedBy` varchar(100) DEFAULT NULL,
  `visit_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `diagnoses` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `uid` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(64) NOT NULL,
  `role` enum('admin','staff') DEFAULT 'staff',
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`uid`, `username`, `password`, `role`, `fullname`, `email`, `created_at`, `updated_at`) VALUES
(3, 'admin', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'admin', 'Test Admin', 'admin@shelter.com', '2026-02-28 21:15:49', '2026-02-28 21:15:49');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `logId` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `actiontype` varchar(50) NOT NULL,
  `targettable` varchar(50) DEFAULT NULL,
  `targetid` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activity_log`
--

INSERT INTO `user_activity_log` (`logId`, `uid`, `actiontype`, `targettable`, `targetid`, `details`, `created_at`) VALUES
(3, 3, 'Create', 'animals', 3, 'Registered new animal', '2026-02-28 21:19:08'),
(4, 3, 'Create', 'animals', 4, 'Registered new animal', '2026-02-28 21:47:39');

-- --------------------------------------------------------

--
-- Table structure for table `vaccination_types`
--

CREATE TABLE `vaccination_types` (
  `vtype_id` int(11) NOT NULL,
  `vaccine_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `frequency_months` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccination_types`
--

INSERT INTO `vaccination_types` (`vtype_id`, `vaccine_name`, `description`, `frequency_months`) VALUES
(5, 'Rabies', 'Standard rabies vaccine', 12),
(6, 'FVRCP', 'Feline viral rhinotracheitis, calicivirus and panleukopenia', 12),
(7, 'DHPP', 'Canine Distemper, Hepatitis, Parainfluenza, and Parvovirus', 12);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `adopters`
--
ALTER TABLE `adopters`
  ADD PRIMARY KEY (`adopterId`);

--
-- Indexes for table `adoption`
--
ALTER TABLE `adoption`
  ADD PRIMARY KEY (`adoption_id`),
  ADD UNIQUE KEY `unique_animal_adoption` (`animal_id`),
  ADD KEY `adopter_id` (`adopter_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `animals`
--
ALTER TABLE `animals`
  ADD PRIMARY KEY (`animal_id`),
  ADD KEY `intake_id` (`intake_id`);

--
-- Indexes for table `animal_vaccination`
--
ALTER TABLE `animal_vaccination`
  ADD PRIMARY KEY (`av_id`),
  ADD KEY `animal_id` (`animal_id`),
  ADD KEY `vtype_id` (`vtype_id`),
  ADD KEY `userId` (`userId`);

--
-- Indexes for table `intake_source`
--
ALTER TABLE `intake_source`
  ADD PRIMARY KEY (`iid`);

--
-- Indexes for table `medical_record`
--
ALTER TABLE `medical_record`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `animal_id` (`animal_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`logId`),
  ADD KEY `uid` (`uid`);

--
-- Indexes for table `vaccination_types`
--
ALTER TABLE `vaccination_types`
  ADD PRIMARY KEY (`vtype_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `adopters`
--
ALTER TABLE `adopters`
  MODIFY `adopterId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `adoption`
--
ALTER TABLE `adoption`
  MODIFY `adoption_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `animals`
--
ALTER TABLE `animals`
  MODIFY `animal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `animal_vaccination`
--
ALTER TABLE `animal_vaccination`
  MODIFY `av_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `intake_source`
--
ALTER TABLE `intake_source`
  MODIFY `iid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `medical_record`
--
ALTER TABLE `medical_record`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `logId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vaccination_types`
--
ALTER TABLE `vaccination_types`
  MODIFY `vtype_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `adoption`
--
ALTER TABLE `adoption`
  ADD CONSTRAINT `adoption_ibfk_1` FOREIGN KEY (`adopter_id`) REFERENCES `adopters` (`adopterId`) ON UPDATE CASCADE,
  ADD CONSTRAINT `adoption_ibfk_2` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`animal_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `adoption_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `animals`
--
ALTER TABLE `animals`
  ADD CONSTRAINT `animals_ibfk_1` FOREIGN KEY (`intake_id`) REFERENCES `intake_source` (`iid`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `animal_vaccination`
--
ALTER TABLE `animal_vaccination`
  ADD CONSTRAINT `animal_vaccination_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`animal_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `animal_vaccination_ibfk_2` FOREIGN KEY (`vtype_id`) REFERENCES `vaccination_types` (`vtype_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `animal_vaccination_ibfk_3` FOREIGN KEY (`userId`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `medical_record`
--
ALTER TABLE `medical_record`
  ADD CONSTRAINT `medical_record_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`animal_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD CONSTRAINT `user_activity_log_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
