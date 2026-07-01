-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 01, 2026 at 03:33 PM
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
-- Database: `etms`
--

-- --------------------------------------------------------

--
-- Table structure for table `assigned_tasks`
--

CREATE TABLE `assigned_tasks` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `assigned_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assigned_tasks`
--

INSERT INTO `assigned_tasks` (`id`, `task_id`, `employee_id`, `assigned_date`, `status`, `assigned_at`) VALUES
(1, 2, 10, '2026-02-15', 'Completed', '2026-02-15 19:34:10'),
(2, 4, 6, '2026-02-15', 'In Progress', '2026-02-15 21:29:26'),
(3, 3, 12, '2026-02-15', 'In Progress', '2026-02-15 21:58:59'),
(4, 1, 6, '2026-02-15', 'Completed', '2026-02-15 22:16:43'),
(5, 5, 9, '2026-02-15', 'In Progress', '2026-02-15 22:16:43'),
(7, 6, 8, '2026-02-15', 'In Progress', '2026-02-15 22:40:21'),
(8, 5, 10, '2026-02-16', 'Not Started', '2026-02-16 09:24:19');

-- --------------------------------------------------------

--
-- Table structure for table `evaluations`
--

CREATE TABLE `evaluations` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `rating` int(2) DEFAULT NULL,
  `suggested_rating` int(2) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `task_id` int(11) NOT NULL,
  `evaluated_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluations`
--

INSERT INTO `evaluations` (`id`, `employee_id`, `rating`, `suggested_rating`, `comments`, `task_id`, `evaluated_by`, `created_at`) VALUES
(2, 6, 10, 10, 'good', 1, 4, '2026-02-15 20:51:08'),
(3, 10, 7, 7, 'good', 2, 7, '2026-02-15 20:56:49'),
(4, 6, 7, 7, 'good', 4, 4, '2026-02-15 21:50:29'),
(5, 10, 10, 10, 'good', 2, 7, '2026-02-15 21:51:19'),
(6, 8, 5, NULL, '', 6, 3, '2026-02-15 23:21:44'),
(7, 9, 6, NULL, '', 5, 7, '2026-02-15 23:22:44');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `priority` varchar(50) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Not Started',
  `progress` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `assigned_to` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `deadline`, `priority`, `status`, `progress`, `created_at`, `assigned_to`, `updated_at`) VALUES
(1, 'Review Website Copy', 'Proofread the \'About Us\' page for typos and brand voice alignment.', '2026-02-15', 'Medium', 'Completed', 100, '2026-02-15 21:27:27', 6, '2026-02-15 22:32:00'),
(2, 'Draft Q2 Marketing Strategy', 'Outline key campaign initiatives and budget allocation for April-June', '2026-02-16', 'High', 'Completed', 100, '2026-02-15 21:50:57', 10, '2026-02-15 22:32:00'),
(3, 'Update CRM Database', 'Import the new leads list from the March conference into Salesforce.', '2026-02-15', 'High', 'In Progress', 28, '2026-02-15 22:26:58', 12, '2026-02-15 23:12:52'),
(4, 'Send Client Welcome Email', 'Use the onboarding template v2 in the shared drive for Client X.', '2026-02-16', 'Medium', 'In Progress', 48, '2026-02-15 21:32:34', 6, '2026-02-15 22:32:00'),
(5, 'Create Invoice for Project A', 'Generate invoice for 50% milestone payment and email to client.', '2026-02-22', 'Low', 'In Progress', 50, '2026-02-15 22:26:58', 10, '2026-02-16 09:24:19'),
(6, 'Submit Expense Report', 'Compile receipts for March travel and submit via Concur.', '2026-02-18', 'Medium', 'In Progress', 29, '2026-02-15 22:40:04', 8, '2026-02-15 23:12:28'),
(7, 'Design Newsletter Banner', 'Create 1200x600 px banner for the April company newsletter.', '2026-02-15', 'High', 'Not Started', NULL, '2026-02-15 23:24:11', 11, '2026-02-15 23:27:47');

-- --------------------------------------------------------

--
-- Table structure for table `tasks_backup`
--

CREATE TABLE `tasks_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `priority` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `progress` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `assigned_to` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks_backup`
--

INSERT INTO `tasks_backup` (`id`, `title`, `description`, `deadline`, `priority`, `status`, `progress`, `created_at`, `assigned_to`, `updated_at`) VALUES
(1, 'Review Website Copy', 'Proofread the \'About Us\' page for typos and brand voice alignment.', '2026-02-15', 'Medium', 'Completed', 100, '2026-02-15 21:27:27', 6, '2026-02-15 16:47:00'),
(2, 'Draft Q2 Marketing Strategy', 'Outline key campaign initiatives and budget allocation for April-June', '2026-02-16', 'High', 'Completed', 100, '2026-02-15 21:50:57', 10, '2026-02-15 16:47:00'),
(3, 'Update CRM Database', 'Import the new leads list from the March conference into Salesforce.', '2026-02-15', 'High', 'Not Started', 0, '2026-02-15 22:26:58', 12, '2026-02-15 16:47:00'),
(4, 'Send Client Welcome Email', 'Use the onboarding template v2 in the shared drive for Client X.', '2026-02-16', 'Medium', 'In Progress', 48, '2026-02-15 21:32:34', 6, '2026-02-15 16:47:00'),
(5, 'Create Invoice for Project A', 'Generate invoice for 50% milestone payment and email to client.', '2026-02-22', 'Low', 'Not Started', 0, '2026-02-15 22:26:58', 9, '2026-02-15 16:47:00');

-- --------------------------------------------------------

--
-- Table structure for table `task_progress`
--

CREATE TABLE `task_progress` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `assigned_to` int(11) NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `progress` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_progress`
--

INSERT INTO `task_progress` (`id`, `task_id`, `assigned_to`, `status`, `progress`, `updated_at`, `created_at`) VALUES
(1, 1, 6, 'Completed', 100, '2026-02-15 15:32:20', '2026-02-15 15:46:48'),
(2, 2, 10, 'In Progress', 57, '2026-02-15 15:32:20', '2026-02-15 15:46:48'),
(3, 2, 10, 'In Progress', 40, '2026-02-15 15:32:20', '2026-02-15 15:46:48'),
(4, 2, 10, 'In Progress', 40, '2026-02-15 15:32:20', '2026-02-15 15:46:48'),
(5, 2, 10, 'In Progress', 40, '2026-02-15 15:32:20', '2026-02-15 15:46:48'),
(6, 2, 10, 'In Progress', 57, '2026-02-15 15:32:20', '2026-02-15 15:46:48'),
(7, 4, 6, 'In Progress', 48, '2026-02-15 15:47:34', '2026-02-15 15:47:34'),
(8, 2, 10, 'Completed', 100, '2026-02-15 16:05:57', '2026-02-15 16:05:57'),
(9, 6, 8, 'In Progress', 29, '2026-02-15 17:27:28', '2026-02-15 17:27:28'),
(10, 3, 12, 'In Progress', 28, '2026-02-15 17:27:52', '2026-02-15 17:27:52'),
(11, 5, 9, 'In Progress', 50, '2026-02-15 17:37:22', '2026-02-15 17:37:22'),
(12, 1, 6, 'Completed', 100, '2026-02-16 03:16:10', '2026-02-16 03:16:10');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('employee','admin','super_admin') NOT NULL DEFAULT 'employee',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `fullname`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES
(3, 'sagar', 'Sagar Shrestha', 'sagar@gmail.com', '$2y$10$/4ttPekLxZSjIlCoy7/ivuegFYwPO7uuaILXSS/EalQUtcE5/9AuK', 'super_admin', '2025-12-01 20:00:15', '2026-02-15 15:26:01'),
(4, 'kremisha', 'Kremisha Oli', 'kremisha@gmail.com', '$2y$10$flEfHZYiY8faL3HDKkUcS.NT5NnIo91o.wsDsP8OTHBOcwQSKxosu', 'admin', '2025-12-01 20:27:46', '2026-07-01 12:37:12'),
(6, 'aayushi', 'Aayushi Neupane', 'aayushi@gmail.com', '$2y$10$0Y/tdYGwTwmcnHZ4WWkd7OsCrZzuIhJrhAfkd1fYv1p3trAS6S6Ba', 'employee', '2026-02-15 19:15:56', '2026-02-15 15:26:01'),
(7, 'bhabana', 'Bhabana Dangi', 'bhabana@gmail.com', '$2y$10$vXv471IloBoPZMoAoMBoGuzusdkGsv4mg0wVcIlUjIyBcDjK3suG2', 'admin', '2026-02-15 19:16:38', '2026-02-15 15:26:01'),
(8, 'Dipesh', 'Dipesh Kadariya', 'dipesh@gmail.com', '$2y$10$.pUqxBO6VhUStt8swB/LGedCFTCnroQD/CPkorcGhREZuJOQ/4kN.', 'employee', '2026-02-15 19:17:55', '2026-02-15 15:26:01'),
(9, 'shreeti', 'Shreeti Gautam', 'shreeti@gmail.com', '$2y$10$XMfatCNoIaWx5paRuGTReu5MDLLCbE/VwhiTQdkHop4kHWxgPbKHu', 'employee', '2026-02-15 19:18:24', '2026-02-15 15:26:01'),
(10, 'bidha', 'Bidha Poudel', 'bidha@gmail.com', '$2y$10$GEvriv8w30EBwkrzDOGD4.EzMzXEMUwsRJyWk.BNorsIMEHGNl.F2', 'employee', '2026-02-15 19:19:10', '2026-02-15 15:26:01'),
(11, 'pratik', 'Pratik Kumal', 'pratik@gmail.com', '$2y$10$0GRtnWWCXkaNRqBE2eR/7eF06I3WOFIUd2WTbOovNo3W6q4zBtpci', 'employee', '2026-02-15 19:19:38', '2026-02-15 15:26:01'),
(12, 'sophia', 'Sophia Sharma', 'sophia@gmail.com', '$2y$10$osdv54XgJ/ncTsyDZ.476OqY6HiOw6sqUCkYXTLrMdf5EzmoxkrJC', 'employee', '2026-02-15 19:20:07', '2026-02-15 15:26:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assigned_tasks`
--
ALTER TABLE `assigned_tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee` (`employee_id`),
  ADD KEY `idx_task` (`task_id`),
  ADD KEY `idx_evaluated_by` (`evaluated_by`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `task_progress`
--
ALTER TABLE `task_progress`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assigned_tasks`
--
ALTER TABLE `assigned_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `evaluations`
--
ALTER TABLE `evaluations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `task_progress`
--
ALTER TABLE `task_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
