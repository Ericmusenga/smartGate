-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 12, 2025 at 12:28 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gate_management_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `computer_lending`
--

CREATE TABLE `computer_lending` (
  `id` int(11) NOT NULL,
  `lender_id` int(11) DEFAULT NULL,
  `borrower_id` int(11) DEFAULT NULL,
  `borrower_reg_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `lend_date` datetime DEFAULT NULL,
  `return_date` datetime DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `computer_lending`
--

INSERT INTO `computer_lending` (`id`, `lender_id`, `borrower_id`, `borrower_reg_number`, `notes`, `lend_date`, `return_date`, `status`, `created_at`) VALUES
(10, 71, 70, 'UR/CE/2021/004', 'testing', '2025-07-11 13:08:59', NULL, 'Accepted', '2025-07-11 11:08:59');

-- --------------------------------------------------------

--
-- Table structure for table `computer_placement`
--

CREATE TABLE `computer_placement` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `brower_id` int(11) NOT NULL,
  `computer_status` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `computer_return`
--

CREATE TABLE `computer_return` (
  `id` int(100) NOT NULL,
  `lender_id` int(100) NOT NULL,
  `borrower_id` int(100) NOT NULL,
  `notes` varchar(100) NOT NULL,
  `create_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `computer_return`
--

INSERT INTO `computer_return` (`id`, `lender_id`, `borrower_id`, `notes`, `create_at`) VALUES
(2, 43, 44, 'test', '2025-07-10 19:53:42'),
(3, 43, 44, 'test', '2025-07-10 20:56:22'),
(4, 71, 69, 'Return My computer', '2025-07-11 12:46:22');

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

CREATE TABLE `devices` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device_type` enum('laptop','tablet','phone','other') NOT NULL,
  `device_name` varchar(100) NOT NULL,
  `serial_number` varchar(100) NOT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_registered` tinyint(1) DEFAULT 1,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `devices`
--

INSERT INTO `devices` (`id`, `owner_id`, `user_id`, `device_type`, `device_name`, `serial_number`, `brand`, `model`, `color`, `description`, `is_registered`, `registration_date`, `updated_at`) VALUES
(1, 1, 1, 'laptop', 'Test Laptop', 'SN001', 'Dell', 'XPS 13', 'Silver', 'Test device for lending', 1, '2025-07-05 09:09:25', '2025-07-05 09:09:25');

-- --------------------------------------------------------

--
-- Stand-in structure for view `device_summary`
-- (See below for the actual view)
--
CREATE TABLE `device_summary` (
`id` int(11)
,`device_name` varchar(100)
,`device_type` enum('laptop','tablet','phone','other')
,`serial_number` varchar(100)
,`brand` varchar(50)
,`model` varchar(50)
,`color` varchar(30)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`username` varchar(50)
,`role_name` varchar(50)
,`registration_date` timestamp
,`is_registered` tinyint(1)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `entry_exit_summary`
-- (See below for the actual view)
--
CREATE TABLE `entry_exit_summary` (
);

-- --------------------------------------------------------

--
-- Table structure for table `entry_student`
--

CREATE TABLE `entry_student` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `entry_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `entry_gate` varchar(50) DEFAULT 'Main Gate',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `entry_student`
--

INSERT INTO `entry_student` (`id`, `student_id`, `entry_time`, `entry_gate`, `notes`, `created_at`) VALUES
(1, 43, '2025-07-11 07:45:26', 'Main Gate', 'testing', '2025-07-11 07:45:26');

-- --------------------------------------------------------

--
-- Table structure for table `entry_visitor`
--

CREATE TABLE `entry_visitor` (
  `id` int(11) NOT NULL,
  `visitor_id` int(11) NOT NULL,
  `entry_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `entry_gate` varchar(50) DEFAULT 'Main Gate',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `purpose` varchar(200) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `equipment` varchar(100) DEFAULT NULL,
  `entry_date` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `entry_visitor`
--

INSERT INTO `entry_visitor` (`id`, `visitor_id`, `entry_time`, `entry_gate`, `notes`, `created_at`, `purpose`, `department`, `equipment`, `entry_date`) VALUES
(6, 3, '2025-07-11 14:36:00', 'Main Gate', 'testing', '2025-07-11 14:36:00', 'Training', 'IT', 'Laptop', '2025-07-11'),
(7, 1, '2025-07-11 21:19:59', 'Main Gate', 'test', '2025-07-11 21:19:59', 'exam', 'Administration', 'Array', '2025-07-11');

-- --------------------------------------------------------

--
-- Table structure for table `exit_student`
--

CREATE TABLE `exit_student` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `exit_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `exit_gate` varchar(50) DEFAULT 'Main Gate',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exit_student`
--

INSERT INTO `exit_student` (`id`, `student_id`, `exit_time`, `exit_gate`, `notes`, `created_at`) VALUES
(1, 44, '2025-07-11 07:22:30', 'Main Gate', 'testing', '2025-07-11 07:22:30'),
(2, 43, '2025-07-11 07:22:30', 'Main Gate', 'test', '2025-07-11 07:22:30');

-- --------------------------------------------------------

--
-- Table structure for table `exit_visitor`
--

CREATE TABLE `exit_visitor` (
  `id` int(11) NOT NULL,
  `visitor_id` int(11) NOT NULL,
  `exit_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `exit_gate` varchar(50) DEFAULT 'Main Gate',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exit_visitor`
--

INSERT INTO `exit_visitor` (`id`, `visitor_id`, `exit_time`, `exit_gate`, `notes`, `created_at`) VALUES
(1, 3, '2025-07-11 21:19:26', 'Main Gate', 'test', '2025-07-11 21:19:26'),
(2, 1, '2025-07-11 22:24:24', 'Main Gate', 'yufhg', '2025-07-11 22:24:24');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `report_type` enum('daily','weekly','monthly','custom') NOT NULL,
  `report_name` varchar(100) NOT NULL,
  `generated_by` int(11) NOT NULL,
  `report_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`report_data`)),
  `file_path` varchar(255) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rfid_cards`
--

CREATE TABLE `rfid_cards` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `card_number` varchar(50) NOT NULL,
  `card_type` enum('student_id','library_card','other') DEFAULT 'student_id',
  `is_active` tinyint(1) DEFAULT 1,
  `issued_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `rfid_summary`
-- (See below for the actual view)
--
CREATE TABLE `rfid_summary` (
`id` int(11)
,`card_number` varchar(50)
,`card_type` enum('student_id','library_card','other')
,`is_active` tinyint(1)
,`issued_date` timestamp
,`expiry_date` date
,`registration_number` varchar(20)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`email` varchar(100)
,`department` varchar(100)
,`program` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `role_description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `role_description`, `permissions`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'System Administrator - Full access to all features', '{\"users\": \"all\", \"devices\": \"all\", \"logs\": \"all\", \"reports\": \"all\", \"settings\": \"all\", \"students\": \"all\", \"security\": \"all\", \"rfid\": \"all\"}', 1, '2025-07-04 14:52:46', '2025-07-04 14:52:46'),
(2, 'security', 'Security Officer - Can manage entry/exit logs and view devices', '{\"users\": \"view\", \"devices\": \"view\", \"logs\": \"all\", \"reports\": \"view\", \"students\": \"view\", \"rfid\": \"view\"}', 1, '2025-07-04 14:52:46', '2025-07-04 14:52:46'),
(4, 'student', 'Student - Can register devices and view own logs', '{\"users\": \"own\", \"devices\": \"own\", \"logs\": \"own\", \"reports\": \"own\"}', 1, '2025-07-04 14:52:46', '2025-07-04 14:52:46');

-- --------------------------------------------------------

--
-- Table structure for table `security_officers`
--

CREATE TABLE `security_officers` (
  `id` int(11) NOT NULL,
  `security_code` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `security_officers`
--

INSERT INTO `security_officers` (`id`, `security_code`, `first_name`, `last_name`, `email`, `phone`, `is_active`, `created_at`, `updated_at`) VALUES
(3, 'SEC003', 'David', 'Miller', 'david.miller@security.ur.ac.rw', '+250788123461', 1, '2025-07-04 14:52:46', '2025-07-04 14:52:46'),
(4, 'sec0012', 'Anisia', 'Mutesi', 'anisia123@gmail.com', '0786544543', 1, '2025-07-05 12:28:16', '2025-07-05 12:28:16'),
(5, 'SEC001', 'John', 'Doe', 'john.doe@example.com', '1234567890', 1, '2025-07-05 17:18:45', '2025-07-05 17:18:45'),
(8, 'SEC002', 'Jane', 'Smith', 'jane.smith@example.com', '0987654321', 1, '2025-07-05 17:20:30', '2025-07-05 17:20:30'),
(9, 'sec004', 'Patrick', 'Iradukunda', 'admin@gmail.com', '0722270247', 1, '2025-07-07 09:35:37', '2025-07-07 09:35:37'),
(10, 'sec0013', 'Iraguha', 'Yves', 'irayves@gmail.com', '0722270248', 1, '2025-07-11 09:46:45', '2025-07-11 09:46:45');

-- --------------------------------------------------------

--
-- Table structure for table `security_shifts`
--

CREATE TABLE `security_shifts` (
  `id` int(11) NOT NULL,
  `security_officer_id` int(11) NOT NULL,
  `gate_number` int(11) NOT NULL,
  `shift_start` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `shift_end` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `security_summary`
-- (See below for the actual view)
--
CREATE TABLE `security_summary` (
`id` int(11)
,`security_code` varchar(20)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`email` varchar(100)
,`phone` varchar(20)
,`is_active` tinyint(1)
,`is_first_login` tinyint(1)
,`last_login` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'site_name', 'Gate Management System - UR Rukara Campus', 'Website name', '2025-07-04 14:52:46'),
(2, 'max_devices_per_user', '3', 'Maximum number of devices a user can register', '2025-07-04 14:52:46'),
(3, 'session_timeout', '3600', 'Session timeout in seconds', '2025-07-04 14:52:46'),
(4, 'enable_notifications', 'true', 'Enable email notifications', '2025-07-04 14:52:46'),
(5, 'maintenance_mode', 'false', 'Enable maintenance mode', '2025-07-04 14:52:46'),
(6, 'gate_count', '2', 'Number of gates in the system', '2025-07-04 14:52:46'),
(7, 'auto_logout_time', '1800', 'Auto logout time in seconds', '2025-07-04 14:52:46'),
(8, 'enable_device_photos', 'true', 'Enable device photo uploads', '2025-07-04 14:52:46'),
(9, 'max_photo_size', '5242880', 'Maximum photo size in bytes (5MB)', '2025-07-04 14:52:46'),
(10, 'force_password_change', 'true', 'Force password change on first login', '2025-07-04 14:52:46'),
(11, 'password_expiry_days', '90', 'Password expiry in days', '2025-07-04 14:52:46'),
(12, 'enable_rfid', 'true', 'Enable RFID card functionality', '2025-07-04 14:52:46'),
(13, 'rfid_timeout', '30', 'RFID card timeout in seconds', '2025-07-04 14:52:46');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `registration_number` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(100) NOT NULL,
  `program` varchar(100) NOT NULL,
  `year_of_study` int(11) NOT NULL,
  `Student_card_number` varchar(255) NOT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `emergency_phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `serial_number` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `registration_number`, `first_name`, `last_name`, `email`, `phone`, `department`, `program`, `year_of_study`, `Student_card_number`, `gender`, `date_of_birth`, `address`, `emergency_contact`, `emergency_phone`, `is_active`, `created_at`, `updated_at`, `serial_number`) VALUES
(67, 'UR/CE/2021/001', 'Eric', 'MUSENGIMANA', 'eric@ur.ac.rw', '788000001', 'Languages and Humanities', 'Bachelor of Education in English', 3, 'SC0000012025', 'male', '2000-01-01', 'Nyagatare', 'Jean', '0788000001', 1, '2025-07-11 09:30:00', '2025-07-11 09:30:00', 'LAPTOP001'),
(68, 'UR/CE/2021/002', 'Alice', 'KAMIKAZI', 'alice@ur.ac.rw', '788000003', 'Foundations, Management and Curriculum Studies', 'Bachelor of Education in Educational Planning', 2, 'SC0000022025', 'female', '2001-02-02', 'Kigali', 'Mutesi', '0788000003', 1, '2025-07-11 09:30:00', '2025-07-11 09:30:00', 'LAPTOP002'),
(69, 'UR/CE/2021/003', 'John', 'NTAKIRUTIMANA', 'john@ur.ac.rw', '788000004', 'Science and Mathematics Education', 'Bachelor of Education in Physics and Mathematics', 3, 'SC0000032025', 'male', '1999-03-03', 'Rwamagana', 'Karenzi', '0788000005', 1, '2025-07-11 09:30:01', '2025-07-11 09:30:01', 'LAPTOP003'),
(70, 'UR/CE/2021/004', 'Grace', 'UWASE', 'grace@ur.ac.rw', '788000006', 'Social Sciences Education', 'Bachelor of Education in History and Geography', 1, 'SC0000042025', 'female', '2002-04-04', 'Butare', 'Claire', '0788000007', 1, '2025-07-11 09:30:01', '2025-07-11 09:30:01', 'LAPTOP004'),
(71, 'UR/CE/2021/005', 'David', 'MUGISHA', 'david@ur.ac.rw', '788000007', 'Inclusive and Special Needs Education', 'Bachelor of Education in Special Needs', 2, 'SC0000052025', 'male', '2000-05-05', 'Kigali', 'Grace', '0788000006', 1, '2025-07-11 09:30:01', '2025-07-11 09:30:01', 'LAPTOP005');

-- --------------------------------------------------------

--
-- Stand-in structure for view `student_summary`
-- (See below for the actual view)
--
CREATE TABLE `student_summary` (
`id` int(11)
,`registration_number` varchar(20)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`email` varchar(100)
,`phone` varchar(20)
,`department` varchar(100)
,`program` varchar(100)
,`year_of_study` int(11)
,`gender` enum('male','female','other')
,`is_active` tinyint(1)
,`is_first_login` tinyint(1)
,`last_login` timestamp
,`device_count` bigint(21)
,`rfid_card_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `security_officer_id` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `year_of_study` int(11) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `emergency_phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_first_login` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `first_name`, `last_name`, `role_id`, `student_id`, `security_officer_id`, `phone`, `department`, `program`, `year_of_study`, `gender`, `date_of_birth`, `address`, `emergency_contact`, `emergency_phone`, `is_active`, `is_first_login`, `last_login`, `password_changed_at`, `created_at`, `updated_at`) VALUES
(1, 'admin', '123456', 'admin@ur.ac.rw', 'System', 'Administrator', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2025-07-11 21:28:47', NULL, '2025-07-04 14:52:46', '2025-07-11 21:28:47'),
(7, 'SEC003', '123456', 'david.miller@security.ur.ac.rw', 'David', 'Miller', 2, NULL, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2025-07-09 18:09:17', NULL, '2025-07-04 14:52:46', '2025-07-09 18:09:17'),
(9, 'sec0012', '12345678', 'anisia123@gmail.com', 'Anisia', 'Mutesi', 2, NULL, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2025-07-08 10:05:53', NULL, '2025-07-05 12:28:16', '2025-07-11 05:20:31'),
(10, 'SEC001', '123456', 'john.doe@security.com', 'John', 'Doe', 2, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, '2025-07-11 22:23:22', NULL, '2025-07-05 17:22:10', '2025-07-11 22:23:22'),
(69, 'UR/CE/2021/001', '$2y$10$pAqqvXTs9sYHV.Lv8wueIOqR3EvktrnVbVD06uG0MKFoyXEZ/9.yO', 'eric@ur.ac.rw', 'Eric', 'MUSENGIMANA', 4, 67, NULL, '788000001', 'Languages and Humanities', 'Bachelor of Education in English', 3, 'male', '2000-01-01', 'Nyagatare', 'Jean', '0788000001', 1, 0, '2025-07-11 10:43:52', NULL, '2025-07-11 09:30:00', '2025-07-11 10:43:52'),
(70, 'UR/CE/2021/002', '$2y$10$N8NHrq1EvTXH7bjPkJr3BOfpMJPdQ16cXGWaLSW5GtVw3zopMg8Xa', 'alice@ur.ac.rw', 'Alice', 'KAMIKAZI', 4, 68, NULL, '788000003', 'Foundations, Management and Curriculum Studies', 'Bachelor of Education in Educational Planning', 2, 'female', '2001-02-02', 'Kigali', 'Mutesi', '0788000003', 1, 0, '2025-07-11 11:09:38', NULL, '2025-07-11 09:30:00', '2025-07-11 11:10:00'),
(71, 'UR/CE/2021/003', '$2y$10$.jSYo2u2KJOGhqpP7bbNsOic4Nwqihlcbu1be4b6o1VY2W8hi6f4K', 'john@ur.ac.rw', 'John', 'NTAKIRUTIMANA', 4, 69, NULL, '788000004', 'Science and Mathematics Education', 'Bachelor of Education in Physics and Mathematics', 3, 'male', '1999-03-03', 'Rwamagana', 'Karenzi', '0788000005', 1, 0, '2025-07-11 11:12:41', NULL, '2025-07-11 09:30:01', '2025-07-11 11:12:41'),
(72, 'UR/CE/2021/004', '$2y$10$Vqvqka1UxcVZOJhnOJUVdeC6fsjaIxnZzQxBfV.p5bfNdwKeDPaIu', 'grace@ur.ac.rw', 'Grace', 'UWASE', 4, 70, NULL, '788000006', 'Social Sciences Education', 'Bachelor of Education in History and Geography', 1, 'female', '2002-04-04', 'Butare', 'Claire', '0788000007', 1, 1, NULL, NULL, '2025-07-11 09:30:01', '2025-07-11 09:30:01'),
(73, 'UR/CE/2021/005', '$2y$10$7oZsd7rxqcMdNpD7CGlAO.nheWbrQfQIDmstQvLIwCMYhnFPv/ZT6', 'david@ur.ac.rw', 'David', 'MUGISHA', 4, 71, NULL, '788000007', 'Inclusive and Special Needs Education', 'Bachelor of Education in Special Needs', 2, 'male', '2000-05-05', 'Kigali', 'Grace', '0788000006', 1, 1, NULL, NULL, '2025-07-11 09:30:01', '2025-07-11 09:30:01'),
(74, 'sec0013', '$2y$10$kyAMG4e0hKOdjo4Zhh0z1OBq17ocn/9A02.UfrJO6AerN4IFDPoy2', 'irayves@gmail.com', 'Iraguha', 'Yves', 2, NULL, 10, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, '2025-07-11 09:46:45', '2025-07-11 09:46:45');

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_summary`
-- (See below for the actual view)
--
CREATE TABLE `user_summary` (
`id` int(11)
,`username` varchar(50)
,`email` varchar(100)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`role_name` varchar(50)
,`department` varchar(100)
,`program` varchar(100)
,`year_of_study` int(11)
,`is_active` tinyint(1)
,`is_first_login` tinyint(1)
,`last_login` timestamp
,`device_count` bigint(21)
,`created_at` timestamp
,`user_type` varchar(8)
);

-- --------------------------------------------------------

--
-- Table structure for table `vistor`
--

CREATE TABLE `vistor` (
  `id` int(11) NOT NULL,
  `visitor_name` varchar(255) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) NOT NULL,
  `department` varchar(100) NOT NULL,
  `person_to_visit` varchar(255) DEFAULT NULL,
  `purpose` text NOT NULL,
  `equipment_brought` text NOT NULL,
  `other_equipment_details` varchar(255) DEFAULT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vistor`
--

INSERT INTO `vistor` (`id`, `visitor_name`, `id_number`, `email`, `telephone`, `department`, `person_to_visit`, `purpose`, `equipment_brought`, `other_equipment_details`, `registration_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Mucyo Emmanuel', '1123456787654322', 'test@gmail.com', '078-654-4578', 'Academic Affairs', 'Meeting', 'Maintenance', 'Laptop, Tablet', '', '2025-07-08 21:39:18', 'exited', '2025-07-08 21:39:18', '2025-07-11 13:37:57'),
(3, 'Niyigaba Claude', '1123456787654323', 'cla@gmail.com', '072227054', 'ICT Department', 'Academic afairs', 'Exam', 'Laptop', '', '2025-07-11 13:33:57', 'inside', '2025-07-11 13:33:57', '2025-07-11 13:38:46');

-- --------------------------------------------------------

--
-- Structure for view `device_summary`
--
DROP TABLE IF EXISTS `device_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `device_summary`  AS SELECT `d`.`id` AS `id`, `d`.`device_name` AS `device_name`, `d`.`device_type` AS `device_type`, `d`.`serial_number` AS `serial_number`, `d`.`brand` AS `brand`, `d`.`model` AS `model`, `d`.`color` AS `color`, `u`.`first_name` AS `first_name`, `u`.`last_name` AS `last_name`, `u`.`username` AS `username`, `r`.`role_name` AS `role_name`, `d`.`registration_date` AS `registration_date`, `d`.`is_registered` AS `is_registered` FROM ((`devices` `d` join `users` `u` on(`d`.`user_id` = `u`.`id`)) join `roles` `r` on(`u`.`role_id` = `r`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `entry_exit_summary`
--
DROP TABLE IF EXISTS `entry_exit_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `entry_exit_summary`  AS SELECT `eel`.`id` AS `id`, `u`.`first_name` AS `first_name`, `u`.`last_name` AS `last_name`, `u`.`username` AS `username`, `d`.`device_name` AS `device_name`, `d`.`device_type` AS `device_type`, `rf`.`card_number` AS `rfid_card`, `eel`.`entry_time` AS `entry_time`, `eel`.`exit_time` AS `exit_time`, `eel`.`gate_number` AS `gate_number`, `eel`.`entry_method` AS `entry_method`, `eel`.`status` AS `status`, `eel`.`created_at` AS `created_at` FROM (((`entry_exit_logs` `eel` left join `users` `u` on(`eel`.`user_id` = `u`.`id`)) left join `devices` `d` on(`eel`.`device_id` = `d`.`id`)) left join `rfid_cards` `rf` on(`eel`.`rfid_card_id` = `rf`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `rfid_summary`
--
DROP TABLE IF EXISTS `rfid_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `rfid_summary`  AS SELECT `rf`.`id` AS `id`, `rf`.`card_number` AS `card_number`, `rf`.`card_type` AS `card_type`, `rf`.`is_active` AS `is_active`, `rf`.`issued_date` AS `issued_date`, `rf`.`expiry_date` AS `expiry_date`, `s`.`registration_number` AS `registration_number`, `s`.`first_name` AS `first_name`, `s`.`last_name` AS `last_name`, `s`.`email` AS `email`, `s`.`department` AS `department`, `s`.`program` AS `program` FROM (`rfid_cards` `rf` join `students` `s` on(`rf`.`student_id` = `s`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `security_summary`
--
DROP TABLE IF EXISTS `security_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `security_summary`  AS SELECT `so`.`id` AS `id`, `so`.`security_code` AS `security_code`, `so`.`first_name` AS `first_name`, `so`.`last_name` AS `last_name`, `so`.`email` AS `email`, `so`.`phone` AS `phone`, `so`.`is_active` AS `is_active`, `u`.`is_first_login` AS `is_first_login`, `u`.`last_login` AS `last_login` FROM (`security_officers` `so` left join `users` `u` on(`so`.`id` = `u`.`security_officer_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `student_summary`
--
DROP TABLE IF EXISTS `student_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `student_summary`  AS SELECT `s`.`id` AS `id`, `s`.`registration_number` AS `registration_number`, `s`.`first_name` AS `first_name`, `s`.`last_name` AS `last_name`, `s`.`email` AS `email`, `s`.`phone` AS `phone`, `s`.`department` AS `department`, `s`.`program` AS `program`, `s`.`year_of_study` AS `year_of_study`, `s`.`gender` AS `gender`, `s`.`is_active` AS `is_active`, `u`.`is_first_login` AS `is_first_login`, `u`.`last_login` AS `last_login`, count(`d`.`id`) AS `device_count`, count(`rf`.`id`) AS `rfid_card_count` FROM (((`students` `s` left join `users` `u` on(`s`.`id` = `u`.`student_id`)) left join `devices` `d` on(`u`.`id` = `d`.`user_id` and `d`.`is_registered` = 1)) left join `rfid_cards` `rf` on(`s`.`id` = `rf`.`student_id` and `rf`.`is_active` = 1)) GROUP BY `s`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `user_summary`
--
DROP TABLE IF EXISTS `user_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_summary`  AS SELECT `u`.`id` AS `id`, `u`.`username` AS `username`, `u`.`email` AS `email`, `u`.`first_name` AS `first_name`, `u`.`last_name` AS `last_name`, `r`.`role_name` AS `role_name`, `u`.`department` AS `department`, `u`.`program` AS `program`, `u`.`year_of_study` AS `year_of_study`, `u`.`is_active` AS `is_active`, `u`.`is_first_login` AS `is_first_login`, `u`.`last_login` AS `last_login`, count(`d`.`id`) AS `device_count`, `u`.`created_at` AS `created_at`, CASE WHEN `u`.`student_id` is not null THEN 'student' WHEN `u`.`security_officer_id` is not null THEN 'security' ELSE 'admin' END AS `user_type` FROM ((`users` `u` left join `roles` `r` on(`u`.`role_id` = `r`.`id`)) left join `devices` `d` on(`u`.`id` = `d`.`user_id` and `d`.`is_registered` = 1)) GROUP BY `u`.`id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `computer_lending`
--
ALTER TABLE `computer_lending`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `computer_placement`
--
ALTER TABLE `computer_placement`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `computer_return`
--
ALTER TABLE `computer_return`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serial_number` (`serial_number`),
  ADD KEY `idx_devices_user_id` (`user_id`),
  ADD KEY `idx_devices_serial_number` (`serial_number`),
  ADD KEY `idx_devices_device_type` (`device_type`),
  ADD KEY `fk_devices_owner_id` (`owner_id`);

--
-- Indexes for table `entry_student`
--
ALTER TABLE `entry_student`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `entry_visitor`
--
ALTER TABLE `entry_visitor`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exit_student`
--
ALTER TABLE `exit_student`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exit_visitor`
--
ALTER TABLE `exit_visitor`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reports_generated_by` (`generated_by`),
  ADD KEY `idx_reports_report_type` (`report_type`);

--
-- Indexes for table `rfid_cards`
--
ALTER TABLE `rfid_cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `card_number` (`card_number`),
  ADD KEY `idx_rfid_cards_card_number` (`card_number`),
  ADD KEY `idx_rfid_cards_student_id` (`student_id`),
  ADD KEY `idx_rfid_cards_is_active` (`is_active`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`),
  ADD KEY `idx_roles_role_name` (`role_name`);

--
-- Indexes for table `security_officers`
--
ALTER TABLE `security_officers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `security_code` (`security_code`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_security_officers_security_code` (`security_code`),
  ADD KEY `idx_security_officers_email` (`email`);

--
-- Indexes for table `security_shifts`
--
ALTER TABLE `security_shifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_security_shifts_officer_id` (`security_officer_id`),
  ADD KEY `idx_security_shifts_active` (`is_active`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `registration_number` (`registration_number`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_students_registration_number` (`registration_number`),
  ADD KEY `idx_students_email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_username` (`username`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_role_id` (`role_id`),
  ADD KEY `idx_users_student_id` (`student_id`),
  ADD KEY `idx_users_security_officer_id` (`security_officer_id`),
  ADD KEY `idx_users_is_first_login` (`is_first_login`);

--
-- Indexes for table `vistor`
--
ALTER TABLE `vistor`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `computer_lending`
--
ALTER TABLE `computer_lending`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `computer_placement`
--
ALTER TABLE `computer_placement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `computer_return`
--
ALTER TABLE `computer_return`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `devices`
--
ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `entry_student`
--
ALTER TABLE `entry_student`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `entry_visitor`
--
ALTER TABLE `entry_visitor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `exit_student`
--
ALTER TABLE `exit_student`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `exit_visitor`
--
ALTER TABLE `exit_visitor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rfid_cards`
--
ALTER TABLE `rfid_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `security_officers`
--
ALTER TABLE `security_officers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `security_shifts`
--
ALTER TABLE `security_shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `vistor`
--
ALTER TABLE `vistor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `devices`
--
ALTER TABLE `devices`
  ADD CONSTRAINT `devices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_devices_owner_id` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rfid_cards`
--
ALTER TABLE `rfid_cards`
  ADD CONSTRAINT `rfid_cards_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `security_shifts`
--
ALTER TABLE `security_shifts`
  ADD CONSTRAINT `security_shifts_ibfk_1` FOREIGN KEY (`security_officer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `users_ibfk_3` FOREIGN KEY (`security_officer_id`) REFERENCES `security_officers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
