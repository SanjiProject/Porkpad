-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 04, 2025 at 08:02 AM
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
-- Database: `porkpad`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_settings`
--

CREATE TABLE `admin_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_settings`
--

INSERT INTO `admin_settings` (`setting_key`, `setting_value`, `description`, `updated_at`, `updated_by`) VALUES
('allow_anonymous_pastes', '1', 'Allow anonymous users to create pastes', '2025-09-03 05:29:58', NULL),
('default_expiration', 'never', 'Default paste expiration', '2025-09-03 05:29:58', NULL),
('maintenance_mode', '0', 'Enable maintenance mode', '2025-09-03 05:29:58', NULL),
('max_paste_size', '10485760', 'Maximum paste size in bytes', '2025-09-03 05:29:58', NULL),
('require_captcha', '1', 'Require captcha for anonymous users', '2025-09-03 05:29:58', NULL),
('site_name', 'PorkPad', 'Site name displayed in header', '2025-09-03 05:29:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `captcha_sessions`
--

CREATE TABLE `captcha_sessions` (
  `session_id` varchar(128) NOT NULL,
  `question` varchar(100) NOT NULL,
  `answer` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `attempts` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `captcha_sessions`
--

INSERT INTO `captcha_sessions` (`session_id`, `question`, `answer`, `created_at`, `expires_at`, `attempts`) VALUES
('ivma6lkai9016ol80cltaet3es', '6 - 2', 4, '2025-09-03 12:09:24', '2025-09-03 06:44:23', 0);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(0, 'None', 'No specific category', '2025-09-03 05:29:53'),
(4, 'Cybersecurity', 'Security related content', '2025-09-03 05:29:53'),
(5, 'Cryptocurrency', 'Crypto and blockchain content', '2025-09-03 05:29:53'),
(6, 'Movies', 'Movie reviews and discussions', '2025-09-03 05:29:53'),
(7, 'Fixit', 'Repair and troubleshooting guides', '2025-09-03 05:29:53'),
(8, 'Food', 'Recipes and food-related content', '2025-09-03 05:29:53'),
(9, 'Gaming', 'Gaming content and discussions', '2025-09-03 05:29:53'),
(10, 'Haiku', 'Poetry and haiku', '2025-09-03 05:29:53'),
(11, 'Help', 'Help requests and assistance', '2025-09-03 05:29:53'),
(12, 'History', 'Historical content and discussions', '2025-09-03 05:29:53'),
(13, 'Housing', 'Real estate and housing content', '2025-09-03 05:29:53'),
(14, 'Jokes', 'Humor and entertainment', '2025-09-03 05:29:53'),
(15, 'Legal', 'Legal advice and discussions', '2025-09-03 05:29:53'),
(16, 'Money', 'Financial advice and discussions', '2025-09-03 05:29:53'),
(17, 'Music', 'Music related content', '2025-09-03 05:29:53'),
(18, 'Pets', 'Pet care and animal content', '2025-09-03 05:29:53'),
(19, 'Photo', 'Photography and image content', '2025-09-03 05:29:53'),
(20, 'Science', 'Scientific content and discussions', '2025-09-03 05:29:53'),
(21, 'Software', 'Software development and tech', '2025-09-03 05:29:53'),
(22, 'Spirit', 'Spiritual and philosophical content', '2025-09-03 05:29:53'),
(23, 'Sports', 'Sports content and discussions', '2025-09-03 05:29:53'),
(24, 'Travel', 'Travel guides and experiences', '2025-09-03 05:29:53'),
(25, 'TV', 'Television shows and reviews', '2025-09-03 05:29:53'),
(26, 'Writing', 'Creative writing and literature', '2025-09-03 05:29:53'),
(27, 'Source Code', 'Programming code and snippets', '2025-09-03 05:29:53');

-- --------------------------------------------------------

--
-- Table structure for table `pastes`
--

CREATE TABLE `pastes` (
  `id` varchar(8) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL DEFAULT 'Untitled',
  `content` longtext NOT NULL,
  `language` varchar(50) DEFAULT 'text',
  `category_id` int(11) DEFAULT 0,
  `is_private` tinyint(1) DEFAULT 0,
  `password` varchar(255) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `views` int(11) DEFAULT 0,
  `author_ip` varchar(45) DEFAULT NULL,
  `can_edit` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paste_revisions`
--

CREATE TABLE `paste_revisions` (
  `id` int(11) NOT NULL,
  `paste_id` varchar(8) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `language` varchar(50) DEFAULT 'text',
  `category_id` int(11) DEFAULT 0,
  `revision_number` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paste_stats`
--

CREATE TABLE `paste_stats` (
  `id` int(11) NOT NULL,
  `paste_id` varchar(8) DEFAULT NULL,
  `view_date` date DEFAULT NULL,
  `views` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_settings`
--
ALTER TABLE `admin_settings`
  ADD PRIMARY KEY (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `captcha_sessions`
--
ALTER TABLE `captcha_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pastes`
--
ALTER TABLE `pastes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_is_private` (`is_private`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `paste_revisions`
--
ALTER TABLE `paste_revisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_paste_id` (`paste_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `paste_stats`
--
ALTER TABLE `paste_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_paste_date` (`paste_id`,`view_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_is_admin` (`is_admin`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `paste_revisions`
--
ALTER TABLE `paste_revisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `paste_stats`
--
ALTER TABLE `paste_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_settings`
--
ALTER TABLE `admin_settings`
  ADD CONSTRAINT `admin_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pastes`
--
ALTER TABLE `pastes`
  ADD CONSTRAINT `fk_paste_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `paste_revisions`
--
ALTER TABLE `paste_revisions`
  ADD CONSTRAINT `paste_revisions_ibfk_1` FOREIGN KEY (`paste_id`) REFERENCES `pastes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `paste_revisions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `paste_stats`
--
ALTER TABLE `paste_stats`
  ADD CONSTRAINT `paste_stats_ibfk_1` FOREIGN KEY (`paste_id`) REFERENCES `pastes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
