-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 03, 2026 at 07:32 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `siagakampung_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `community_tasks`
--

CREATE TABLE `community_tasks` (
  `id` int(11) NOT NULL,
  `family_id` int(11) NOT NULL,
  `assigned_to` int(11) NOT NULL,
  `task_title` varchar(150) NOT NULL,
  `task_description` text DEFAULT NULL,
  `status` enum('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community_tasks`
--

INSERT INTO `community_tasks` (`id`, `family_id`, `assigned_to`, `task_title`, `task_description`, `status`, `created_at`, `completed_at`) VALUES
(1, 1, 3, 'Verifikasi Kesiapsiagaan Keluarga (SIMULASI)', 'Periksa kelengkapan lima indikator kesiapsiagaan keluarga.', 'in_progress', '2026-11-03 03:15:32', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `evacuation_plans`
--

CREATE TABLE `evacuation_plans` (
  `id` int(11) NOT NULL,
  `neighborhood` varchar(50) NOT NULL,
  `hazard_type` varchar(50) NOT NULL,
  `primary_route` text NOT NULL,
  `alternative_route` text NOT NULL,
  `assembly_point` varchar(150) NOT NULL,
  `coordinator_contact` varchar(25) NOT NULL,
  `validation_status` enum('draft','verified') NOT NULL DEFAULT 'draft',
  `verified_at` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evacuation_plans`
--

INSERT INTO `evacuation_plans` (`id`, `neighborhood`, `hazard_type`, `primary_route`, `alternative_route`, `assembly_point`, `coordinator_contact`, `validation_status`, `verified_at`, `created_by`, `created_at`) VALUES
(1, 'RT 02 / RW 05', 'Banjir (SIMULASI)', 'Rute utama simulasi. Wajib diverifikasi koordinator.', 'Rute alternatif simulasi. Wajib diverifikasi koordinator.', 'Titik kumpul simulasi, belum terverifikasi.', '0000000000', 'draft', NULL, 2, '2026-11-03 03:15:32'),
(2, '02', 'Banjir', 'Jalan utama menuju balai desa', 'Jalan belakang menuju lapangan', 'Lapangan desa', '081234567890', 'verified', '2026-11-03 13:20:14', 1, '2026-11-03 03:54:38');

-- --------------------------------------------------------

--
-- Table structure for table `families`
--

CREATE TABLE `families` (
  `id` int(11) NOT NULL,
  `family_code` varchar(20) NOT NULL,
  `family_name` varchar(100) NOT NULL,
  `neighborhood` varchar(50) NOT NULL,
  `member_count` int(11) NOT NULL,
  `emergency_contact` varchar(25) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `families`
--

INSERT INTO `families` (`id`, `family_code`, `family_name`, `neighborhood`, `member_count`, `emergency_contact`, `created_at`) VALUES
(1, 'SK-2026-00125', 'Keluarga Bapak Ahmad', 'RT 02 / RW 05', 4, '0000000000', '2026-11-03 03:15:32'),
(2, 'SK-2026-31493', 'Faiz', '02', 12, '081397991115', '2026-11-03 03:43:12'),
(3, 'SK-2026-66840', 'Syauqi', '03', 3, '081212345678', '2026-11-03 04:12:57');

-- --------------------------------------------------------

--
-- Table structure for table `readiness_checklists`
--

CREATE TABLE `readiness_checklists` (
  `id` int(11) NOT NULL,
  `family_id` int(11) NOT NULL,
  `emergency_bag` tinyint(1) NOT NULL DEFAULT 0,
  `emergency_contacts` tinyint(1) NOT NULL DEFAULT 0,
  `first_aid_kit` tinyint(1) NOT NULL DEFAULT 0,
  `important_documents` tinyint(1) NOT NULL DEFAULT 0,
  `assembly_point` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `readiness_checklists`
--

INSERT INTO `readiness_checklists` (`id`, `family_id`, `emergency_bag`, `emergency_contacts`, `first_aid_kit`, `important_documents`, `assembly_point`, `updated_at`) VALUES
(1, 1, 1, 1, 0, 1, 0, '2026-11-03 03:15:32'),
(2, 2, 1, 1, 1, 1, 1, '2026-11-03 04:46:00'),
(3, 3, 0, 0, 0, 0, 0, '2026-11-03 04:12:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','coordinator','volunteer') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Administrator', 'admin@siagakampung.test', '$2y$12$lHHAtGa7ZLEFf1I71T25JOvPJSn8WJkiiYc522xewgMua8wIMhOHS', 'admin', '2026-11-03 03:15:32'),
(2, 'Koordinator Kampung', 'koordinator@siagakampung.test', '$2y$12$lHHAtGa7ZLEFf1I71T25JOvPJSn8WJkiiYc522xewgMua8wIMhOHS', 'coordinator', '2026-11-03 03:15:32'),
(3, 'Relawan Kampung', 'relawan@siagakampung.test', '$2y$12$lHHAtGa7ZLEFf1I71T25JOvPJSn8WJkiiYc522xewgMua8wIMhOHS', 'volunteer', '2026-11-03 03:15:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `community_tasks`
--
ALTER TABLE `community_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_task_status` (`status`),
  ADD KEY `idx_task_assigned` (`assigned_to`),
  ADD KEY `idx_task_family` (`family_id`);

--
-- Indexes for table `evacuation_plans`
--
ALTER TABLE `evacuation_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_evacuation_neighborhood` (`neighborhood`),
  ADD KEY `idx_evacuation_status` (`validation_status`),
  ADD KEY `fk_evacuation_user` (`created_by`);

--
-- Indexes for table `families`
--
ALTER TABLE `families`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `family_code` (`family_code`),
  ADD KEY `idx_family_neighborhood` (`neighborhood`);

--
-- Indexes for table `readiness_checklists`
--
ALTER TABLE `readiness_checklists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `family_id` (`family_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `community_tasks`
--
ALTER TABLE `community_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `evacuation_plans`
--
ALTER TABLE `evacuation_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `families`
--
ALTER TABLE `families`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `readiness_checklists`
--
ALTER TABLE `readiness_checklists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `community_tasks`
--
ALTER TABLE `community_tasks`
  ADD CONSTRAINT `fk_task_family` FOREIGN KEY (`family_id`) REFERENCES `families` (`id`),
  ADD CONSTRAINT `fk_task_user` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`);

--
-- Constraints for table `evacuation_plans`
--
ALTER TABLE `evacuation_plans`
  ADD CONSTRAINT `fk_evacuation_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `readiness_checklists`
--
ALTER TABLE `readiness_checklists`
  ADD CONSTRAINT `fk_checklist_family` FOREIGN KEY (`family_id`) REFERENCES `families` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
