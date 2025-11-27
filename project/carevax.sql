-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 27, 2025 at 12:06 AM
-- Server version: 8.3.0
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `carevax`
--

-- --------------------------------------------------------

--
-- Table structure for table `children`
--

DROP TABLE IF EXISTS `children`;
CREATE TABLE IF NOT EXISTS `children` (
  `id` int NOT NULL AUTO_INCREMENT,
  `healthworker_id` int DEFAULT NULL,
  `parent_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `parent_phone` varchar(20) NOT NULL,
  `dob` date NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `mother_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `mother_id` (`mother_id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `children`
--

INSERT INTO `children` (`id`, `healthworker_id`, `parent_id`, `name`, `parent_phone`, `dob`, `gender`, `created_at`, `mother_id`) VALUES
(9, NULL, 0, 'grace asiimwe', '', '2025-10-11', 'Male', '2025-10-08 12:55:21', 1),
(18, NULL, 0, 'Binomugisha Ben', '', '2025-11-24', 'Male', '2025-11-25 15:42:15', 3),
(13, NULL, 0, 'justice', '', '2025-10-02', 'Male', '2025-10-21 13:56:37', 1),
(14, NULL, 0, 'israel', '', '2025-10-17', 'Male', '2025-10-21 14:12:28', 1),
(19, NULL, 0, 'Ampa tim', '', '2025-11-27', 'Male', '2025-11-25 15:49:04', 4);

-- --------------------------------------------------------

--
-- Table structure for table `followups`
--

DROP TABLE IF EXISTS `followups`;
CREATE TABLE IF NOT EXISTS `followups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vht_id` int NOT NULL,
  `household_id` int NOT NULL,
  `task` varchar(255) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('Pending','Completed') DEFAULT 'Pending',
  PRIMARY KEY (`id`),
  KEY `vht_id` (`vht_id`),
  KEY `household_id` (`household_id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `followups`
--

INSERT INTO `followups` (`id`, `vht_id`, `household_id`, `task`, `due_date`, `status`) VALUES
(1, 1, 1, 'asdfgcvhbn', '2025-10-30', 'Pending'),
(2, 1, 1, 'asdfgcvhbn', '2025-10-30', 'Pending'),
(3, 1, 1, 'asdfgcvhbn', '2025-10-30', 'Pending'),
(4, 1, 1, 'asdfgcvhbn', '2025-10-30', 'Pending'),
(5, 1, 1, 'asdfgcvhbn', '2025-10-30', 'Pending'),
(6, 1, 1, 'asdfgcvhbn', '2025-10-30', 'Pending'),
(7, 1, 1, 'asdfgcvhbn', '2025-10-30', 'Pending'),
(8, 1, 2, 'dgfhjkll/.', '2025-10-31', 'Pending'),
(9, 1, 2, 'dgfhjkll/.', '2025-10-31', 'Pending'),
(10, 1, 1, 'wasdgfchj', '2025-10-31', 'Pending'),
(11, 1, 1, 'vhjk', '2025-11-26', 'Pending'),
(12, 1, 1, 'vhjk', '2025-11-26', ''),
(13, 1, 1, 'vhjk', '2025-11-26', 'Pending'),
(14, 1, 1, 'ZCvavwe', '2025-11-27', 'Pending'),
(15, 1, 1, 'go check the house hold', '2025-11-27', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `households`
--

DROP TABLE IF EXISTS `households`;
CREATE TABLE IF NOT EXISTS `households` (
  `id` int NOT NULL AUTO_INCREMENT,
  `household_name` varchar(100) DEFAULT NULL,
  `village` varchar(100) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `registered_by` varchar(100) DEFAULT NULL,
  `date_registered` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `vht_id` int DEFAULT NULL,
  `num_children` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_vht` (`vht_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `households`
--

INSERT INTO `households` (`id`, `household_name`, `village`, `contact`, `registered_by`, `date_registered`, `vht_id`, `num_children`) VALUES
(1, 'Nabirye Family', 'Kawempe', NULL, NULL, '2025-10-27 20:53:38', 1, 4),
(2, 'Mukasa Family', 'Entebbe', NULL, NULL, '2025-10-27 20:53:38', 2, 3);

-- --------------------------------------------------------

--
-- Table structure for table `immunization_history`
--

DROP TABLE IF EXISTS `immunization_history`;
CREATE TABLE IF NOT EXISTS `immunization_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `child_id` int NOT NULL,
  `vaccine_id` int NOT NULL,
  `date_given` date NOT NULL,
  `status` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `child_id` (`child_id`),
  KEY `vaccine_id` (`vaccine_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mothers`
--

DROP TABLE IF EXISTS `mothers`;
CREATE TABLE IF NOT EXISTS `mothers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `household_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `mothers`
--

INSERT INTO `mothers` (`id`, `name`, `phone`, `email`, `household_id`) VALUES
(1, 'mary', '0777381554', 'kingjossy36@gmail.com\r\n', 0),
(2, 'mert', '0702779663', 'kingjossy36@gmail.com', 0),
(3, 'josh may', '0777361554', 'g@gmail.com', 0),
(4, 'henry ASIIMWE', '0773541900', 'henry@gmail.com', 0);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parent_id` int NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `code` varchar(6) NOT NULL,
  `method` enum('email','sms') NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `email`, `code`, `method`, `expires_at`) VALUES
(6, 0, 'kingjossy36@gmail.com', '617590', 'email', '2025-10-05 09:04:02'),
(7, 16, '', '221771', 'email', '2025-10-05 09:13:19'),
(8, 16, '', '648196', 'email', '2025-10-05 10:08:11'),
(9, 16, '', '799021', 'email', '2025-10-05 10:08:33'),
(10, 16, '', '159585', 'email', '2025-10-05 10:11:00'),
(11, 16, '', '217833', 'email', '2025-10-05 10:14:05'),
(12, 16, '', '360740', 'email', '2025-10-05 10:15:19'),
(13, 16, '', '563478', 'email', '2025-10-05 10:16:55'),
(14, 16, '', '387786', 'email', '2025-10-05 10:28:26'),
(15, 16, '', '900742', 'email', '2025-10-05 10:33:44'),
(16, 16, '', '116795', 'email', '2025-10-05 10:34:36'),
(17, 16, '', '359795', 'email', '2025-10-05 10:36:31'),
(18, 16, '', '509057', 'email', '2025-10-05 10:45:51'),
(19, 16, '', '826231', 'email', '2025-10-05 10:46:22'),
(20, 16, '', '248976', 'email', '2025-10-05 10:46:39'),
(21, 16, '', '178298', 'email', '2025-10-05 10:57:08'),
(22, 21, '', '990457', 'email', '2025-10-15 12:18:19'),
(23, 21, '', '331315', 'email', '2025-10-15 12:09:04'),
(24, 25, '', '934650', 'email', '2025-10-15 12:43:37'),
(25, 25, '', '412100', 'email', '2025-10-15 12:46:01'),
(26, 25, '', '284112', 'email', '2025-10-19 15:26:30'),
(27, 25, '', '322214', 'email', '2025-11-04 19:53:56'),
(28, 25, '', '630305', 'sms', '2025-11-04 20:14:10'),
(29, 25, '', '694658', 'sms', '2025-11-04 20:17:43'),
(30, 25, '', '680366', 'sms', '2025-11-04 20:18:10'),
(31, 25, '', '942407', 'sms', '2025-11-04 20:21:34'),
(32, 25, '', '528298', 'email', '2025-11-04 20:22:53'),
(33, 25, '', '740368', 'email', '2025-11-04 20:23:01'),
(34, 25, '', '224127', 'email', '2025-11-05 10:59:59'),
(35, 25, '', '158577', 'email', '2025-11-05 11:01:49'),
(36, 25, '', '672595', 'email', '2025-11-05 11:04:30'),
(37, 25, '', '779065', 'email', '2025-11-05 11:07:43'),
(38, 25, '', '553635', 'email', '2025-11-05 11:08:56'),
(39, 25, '', '596022', 'email', '2025-11-05 11:13:36'),
(40, 25, '', '638682', 'email', '2025-11-05 11:14:15');

-- --------------------------------------------------------

--
-- Table structure for table `patient_vaccines`
--

DROP TABLE IF EXISTS `patient_vaccines`;
CREATE TABLE IF NOT EXISTS `patient_vaccines` (
  `id` int NOT NULL AUTO_INCREMENT,
  `child_id` int NOT NULL,
  `vaccine_id` int NOT NULL,
  `date_scheduled` date DEFAULT NULL,
  `date_given` date DEFAULT NULL,
  `status` enum('upcoming','completed','missed') DEFAULT 'upcoming',
  `reminder_sent_sms` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `child_id` (`child_id`),
  KEY `vaccine_id` (`vaccine_id`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `patient_vaccines`
--

INSERT INTO `patient_vaccines` (`id`, `child_id`, `vaccine_id`, `date_scheduled`, `date_given`, `status`, `reminder_sent_sms`) VALUES
(7, 3, 5, '2025-10-11', '2025-10-10', 'upcoming', 0),
(10, 1, 7, '2025-10-09', '2025-10-11', 'completed', 0),
(9, 1, 7, '2025-10-11', '2025-10-11', 'completed', 0),
(8, 1, 2, '2025-10-17', '2025-10-09', 'missed', 0),
(5, 1, 6, '2025-10-17', '2025-11-26', 'completed', 0),
(6, 1, 4, '2025-10-08', '2025-10-09', 'completed', 0),
(11, 5, 8, '2025-10-25', '2025-10-24', 'upcoming', 0),
(12, 6, 3, '2025-10-01', '2025-10-23', 'missed', 0),
(13, 6, 3, '2025-10-01', '2025-10-23', 'missed', 0),
(14, 7, 4, NULL, NULL, 'upcoming', 0),
(15, 7, 6, NULL, '2025-10-10', 'upcoming', 0),
(16, 8, 2, NULL, '2025-10-17', 'upcoming', 0),
(17, 9, 6, '2025-10-09', '2025-10-09', 'missed', 0),
(18, 11, 8, '2025-10-16', '2025-10-09', 'missed', 0),
(19, 9, 16, '2025-10-23', '2025-10-24', 'completed', 0),
(20, 13, 7, NULL, '2025-10-16', 'upcoming', 0),
(21, 14, 16, '2025-11-19', '2025-10-24', 'completed', 0),
(22, 9, 16, '2025-10-16', '2025-10-31', 'completed', 0),
(23, 18, 16, NULL, '2025-11-28', 'upcoming', 0),
(24, 19, 3, '2025-11-28', '2025-11-26', 'upcoming', 0);

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

DROP TABLE IF EXISTS `reminders`;
CREATE TABLE IF NOT EXISTS `reminders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `vaccine_name` varchar(255) NOT NULL,
  `scheduled_date` date NOT NULL,
  `reminder_sent_sms` tinyint(1) DEFAULT '0',
  `reminder_sent_whatsapp` tinyint(1) DEFAULT '0',
  `reminder_sent_inapp` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_reminders`
--

DROP TABLE IF EXISTS `sms_reminders`;
CREATE TABLE IF NOT EXISTS `sms_reminders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `child_id` int DEFAULT NULL,
  `vaccine_id` int DEFAULT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `message` text,
  `date_sent` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_config`
--

DROP TABLE IF EXISTS `system_config`;
CREATE TABLE IF NOT EXISTS `system_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sms_api_url` varchar(255) DEFAULT NULL,
  `sms_api_key` varchar(255) DEFAULT NULL,
  `sms_sender_id` varchar(50) DEFAULT NULL,
  `reminder_days_before` int DEFAULT '3',
  `reminder_days_after` int DEFAULT '7',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` tinyint NOT NULL,
  `system_name` varchar(255) DEFAULT 'CareVax',
  `logo` varchar(255) DEFAULT '',
  `theme` enum('light','dark') DEFAULT 'light',
  `timezone` varchar(100) DEFAULT 'Africa/Kampala',
  `language` varchar(50) DEFAULT 'en',
  `sms_api_key` text,
  `sms_sender` varchar(100) DEFAULT NULL,
  `email_sender` varchar(100) DEFAULT NULL,
  `reminder_before_days` int DEFAULT '3',
  `reminder_on_due` tinyint(1) DEFAULT '1',
  `reminder_after_days` int DEFAULT '7',
  `password_min_length` int DEFAULT '8',
  `password_require_numbers` tinyint(1) DEFAULT '1',
  `password_require_special` tinyint(1) DEFAULT '0',
  `two_factor_enabled` tinyint(1) DEFAULT '0',
  `session_timeout_minutes` int DEFAULT '30',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `system_name`, `logo`, `theme`, `timezone`, `language`, `sms_api_key`, `sms_sender`, `email_sender`, `reminder_before_days`, `reminder_on_due`, `reminder_after_days`, `password_min_length`, `password_require_numbers`, `password_require_special`, `two_factor_enabled`, `session_timeout_minutes`) VALUES
(1, 'CareVax', '', 'dark', 'Africa/Kampala', 'en', '', '', '', 3, 1, 7, 8, 1, 0, 0, 30);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `email_notifications` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=MyISAM AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `phone_number`, `email`, `username`, `phone`, `password`, `role`, `created_at`, `email_notifications`) VALUES
(27, 'AINOMUGISHA  king josssy', NULL, 'kingjossy37@gmail.com', 'king', '077268259', '$2y$10$VT4eg/6b/schBKXj0SYkQeGeUIQNferQxP0slxDpB9pY9Dd8.jYg6', 'healthworker', '2025-11-25 15:38:34', 1),
(28, 'AINOMUGISHA KING JOSSSY', NULL, 'kingjossy36@gmail.com', 'JOSSY', '0702779664', '$2y$10$3iP4AUlFWP1r6qSTyIMTneqoZpqq35LXtpw3h2DykOXZsCQFttJkW', 'healthworker', '2025-11-25 15:46:34', 1),
(29, 'Dave', NULL, 'd@gmail.com', 'Dave', '0702996773', '$2y$10$rtcXXnMHYlV4b/eERbff1uJ2hCQqUU.3jnsak5ewqlwvtjGVmEiRq', 'admin', '2025-11-25 15:52:17', 1);

-- --------------------------------------------------------

--
-- Table structure for table `vaccinations`
--

DROP TABLE IF EXISTS `vaccinations`;
CREATE TABLE IF NOT EXISTS `vaccinations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `healthworker_id` int DEFAULT NULL,
  `patient_id` int NOT NULL,
  `vaccine_name` varchar(100) NOT NULL,
  `date_given` date DEFAULT NULL,
  `status` enum('Done','Pending') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vaccines`
--

DROP TABLE IF EXISTS `vaccines`;
CREATE TABLE IF NOT EXISTS `vaccines` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `quantity` int NOT NULL DEFAULT '0',
  `manufacturer` varchar(255) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `recommended_age` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `vaccines`
--

INSERT INTO `vaccines` (`id`, `name`, `description`, `quantity`, `manufacturer`, `expiration_date`, `recommended_age`) VALUES
(1, 'BCG', NULL, 0, NULL, NULL, NULL),
(2, 'Polio (OPV)', NULL, 0, NULL, NULL, NULL),
(3, 'DPT', NULL, 0, NULL, NULL, NULL),
(4, 'Hepatitis B', '', 56, 'bridget', '2025-10-09', NULL),
(5, 'Measles', NULL, 0, NULL, NULL, NULL),
(6, 'MMR', '', 4, '', '0000-00-00', NULL),
(7, 'Rotavirus', '', 23, 'WHO', '2025-11-26', NULL),
(8, 'Pneumococcal', NULL, 0, NULL, NULL, NULL),
(9, 'Yellow Fever', NULL, 0, NULL, NULL, NULL),
(16, 'corona', 'virus', 12, 'FGSAR', '2025-10-25', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vhts`
--

DROP TABLE IF EXISTS `vhts`;
CREATE TABLE IF NOT EXISTS `vhts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vht_name` varchar(100) NOT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `village` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `vhts`
--

INSERT INTO `vhts` (`id`, `vht_name`, `contact`, `village`) VALUES
(1, 'John Okello', '0702779663', 'Kampala'),
(2, 'Sarah Namusoke', '0756123456', 'Entebbe');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
