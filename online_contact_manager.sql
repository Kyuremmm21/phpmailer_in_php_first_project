-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 15, 2025 at 02:53 AM
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
-- Database: `online_contact_manager`
--

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `teacher_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `due_date` datetime NOT NULL,
  `status` enum('pending','completed','overdue') NOT NULL DEFAULT 'pending',
  `submission_file` varchar(255) DEFAULT NULL,
  `submission_date` datetime DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `grade` decimal(5,2) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `title`, `description`, `teacher_id`, `user_id`, `due_date`, `status`, `submission_file`, `submission_date`, `feedback`, `grade`, `is_deleted`, `created_at`, `updated_at`) VALUES
(1, 'book cover', 'yygfyf', 1, 5, '2025-05-12 00:00:00', 'completed', '6822004f1e855.pdf', '2025-05-12 22:06:07', 'greatwork', 90.00, 0, '2025-05-12 12:01:37', '2025-05-14 09:09:38');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `user_id`, `teacher_id`, `name`, `phone`, `email`, `category`, `notes`, `is_deleted`, `created_at`, `updated_at`) VALUES
(2, 1, 1, 'kenneth', '09276183649', 'gumalogk7@gmail.com', NULL, NULL, 0, '2025-05-12 13:51:52', '2025-05-12 13:51:52'),
(3, 5, NULL, 'miss u', '09946864344', 'gumalogk7@gmail.com', NULL, NULL, 0, '2025-05-12 14:03:07', '2025-05-12 14:05:32'),
(4, 5, NULL, 'kenneth-210673', '09946864344', 'langtosample6@gmail.com', NULL, NULL, 1, '2025-05-14 08:45:55', '2025-05-14 08:48:25'),
(5, 1, 1, 'kenneth-210673', '09946864344', 'langtosample6@gmail.com', NULL, NULL, 1, '2025-05-14 08:49:01', '2025-05-14 08:49:04');

-- --------------------------------------------------------

--
-- Table structure for table `contact_history`
--

CREATE TABLE `contact_history` (
  `id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`id`, `user_id`, `action`, `ip_address`, `user_agent`, `timestamp`, `details`) VALUES
(28, 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-12 13:01:30', NULL),
(29, 2, 'Created new teacher account: langtosample6@gmail.com', NULL, NULL, '2025-05-12 13:03:32', NULL),
(30, 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-12 13:50:10', NULL),
(31, 1, 'Added new contact: kenneth', NULL, NULL, '2025-05-12 13:51:52', NULL),
(32, 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-12 13:59:34', NULL),
(33, 5, 'Added new contact: miss u', NULL, NULL, '2025-05-12 14:03:07', NULL),
(34, 5, 'Updated contact: miss u', NULL, NULL, '2025-05-12 14:05:24', NULL),
(35, 5, 'Updated contact: miss u', NULL, NULL, '2025-05-12 14:05:32', NULL),
(36, 5, 'Submitted assignment: book cover', NULL, NULL, '2025-05-12 14:06:07', NULL),
(37, 5, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-12 14:06:15', NULL),
(38, 1, 'Graded assignment: book cover', NULL, NULL, '2025-05-12 14:06:51', NULL),
(39, 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-12 14:07:27', NULL),
(40, 2, 'User deactivated (ID: 6)', NULL, NULL, '2025-05-12 14:49:33', NULL),
(41, 2, 'User activated (ID: 6)', NULL, NULL, '2025-05-12 14:49:34', NULL),
(42, 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-12 15:33:54', NULL),
(43, 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-13 08:52:34', NULL),
(44, 5, 'Added new contact: kenneth-210673', NULL, NULL, '2025-05-14 08:45:55', NULL),
(45, 5, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-14 08:48:48', NULL),
(46, 1, 'Added new contact: kenneth-210673', NULL, NULL, '2025-05-14 08:49:01', NULL),
(47, 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-14 08:51:32', NULL),
(48, 2, 'User deactivated (ID: 6)', NULL, NULL, '2025-05-14 08:52:06', NULL),
(49, 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-14 08:55:49', NULL),
(50, 5, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-14 08:57:40', NULL),
(51, 5, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-14 08:59:27', NULL),
(52, 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-14 09:01:53', NULL),
(53, 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-14 09:02:28', NULL),
(54, 5, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36 Edg/136.0.0.0', '2025-05-14 09:07:12', NULL),
(55, 2, 'edit_assignment', NULL, NULL, '2025-05-14 09:09:16', NULL),
(56, 2, 'edit_assignment', NULL, NULL, '2025-05-14 09:09:38', NULL),
(57, 2, 'Created new teacher account: langtosample6@gmail.com', NULL, NULL, '2025-05-14 09:31:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_submissions`
--

CREATE TABLE `teacher_submissions` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `submission_title` varchar(255) NOT NULL,
  `submission_content` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','user') NOT NULL DEFAULT 'user',
  `verification_code` varchar(6) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `login_attempts` int(11) NOT NULL DEFAULT 0,
  `password_reset_token` varchar(64) DEFAULT NULL,
  `password_reset_expires` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `verification_code`, `is_verified`, `status`, `last_login`, `login_attempts`, `password_reset_token`, `password_reset_expires`, `created_at`) VALUES
(1, 'kenneth', 'gumalogk7@gmail.com', '$2y$10$kyZKhS.xQx8upYCjD0hl7.ct9BVV3/91qj/AIlWus/iDMHKaEgU.i', 'teacher', '669926', 1, 1, '2025-05-14 09:05:53', 0, NULL, NULL, '2025-05-04 11:30:25'),
(2, 'kenneth', 'aebhrian@gmail.com', '$2y$10$nktzy/JxxKCBdrBZvfILkOVVE1WW.YiZSwPjgjOq0rZKd.qXrtMym', 'admin', '234036', 1, 1, '2025-05-14 09:30:43', 0, NULL, NULL, '2025-05-04 11:56:35'),
(5, 'miss u', 'gumalogk@gmail.com', '$2y$10$86FikvT6rA..WOnzcZBqqO/uiHdKHWtRPwe5Vzabz/0ITC9biSDm6', 'user', NULL, 1, 1, '2025-05-14 09:12:13', 0, NULL, NULL, '2025-05-12 11:33:49'),
(9, 'mamamo', 'langtosample6@gmail.com', '$2y$10$MrSumlMClj1PsrT9AKmyZuIZvgl7D8AgTbXWSfo0J7Wir5IXbvvAq', 'user', NULL, 1, 1, NULL, 0, NULL, NULL, '2025-05-14 09:57:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `due_date` (`due_date`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `category` (`category`);

--
-- Indexes for table `contact_history`
--
ALTER TABLE `contact_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contact_id` (`contact_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `timestamp` (`timestamp`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `last_activity` (`last_activity`);

--
-- Indexes for table `teacher_submissions`
--
ALTER TABLE `teacher_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role` (`role`),
  ADD KEY `status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contact_history`
--
ALTER TABLE `contact_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_submissions`
--
ALTER TABLE `teacher_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contacts`
--
ALTER TABLE `contacts`
  ADD CONSTRAINT `contacts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contacts_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `contact_history`
--
ALTER TABLE `contact_history`
  ADD CONSTRAINT `contact_history_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contact_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_submissions`
--
ALTER TABLE `teacher_submissions`
  ADD CONSTRAINT `teacher_submissions_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
