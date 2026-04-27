-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 27, 2026 at 05:51 AM
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
-- Database: `thesis_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_head_profiles`
--

CREATE TABLE `academic_head_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_head_profiles`
--

INSERT INTO `academic_head_profiles` (`id`, `user_id`, `employee_id`, `created_at`, `updated_at`) VALUES
(1, 2, '02000000002', '2026-04-08 10:56:31', '2026-04-08 10:56:31');

-- --------------------------------------------------------

--
-- Table structure for table `academic_settings`
--

CREATE TABLE `academic_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `academic_year` varchar(255) NOT NULL,
  `semester` enum('1st Semester','2nd Semester') NOT NULL,
  `exam_period` enum('Prelim','Midterm','Prefinals','Finals') NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_settings`
--

INSERT INTO `academic_settings` (`id`, `academic_year`, `semester`, `exam_period`, `created_at`, `updated_at`) VALUES
(1, '2025-2026', '1st Semester', 'Prelim', '2026-04-10 10:12:28', '2026-04-10 10:12:28');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cashier_profiles`
--

CREATE TABLE `cashier_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cashier_profiles`
--

INSERT INTO `cashier_profiles` (`id`, `user_id`, `employee_id`, `created_at`, `updated_at`) VALUES
(1, 4, '02000000002', '2026-04-08 11:08:43', '2026-04-08 11:08:43');

-- --------------------------------------------------------

--
-- Table structure for table `exam_attendances`
--

CREATE TABLE `exam_attendances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `section_exam_schedule_slot_id` bigint(20) UNSIGNED NOT NULL,
  `student_profile_id` bigint(20) UNSIGNED NOT NULL,
  `exam_permit_id` bigint(20) UNSIGNED DEFAULT NULL,
  `logged_by` bigint(20) UNSIGNED DEFAULT NULL,
  `logged_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_matrices`
--

CREATE TABLE `exam_matrices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` tinyint(3) UNSIGNED NOT NULL,
  `exam_period` varchar(30) NOT NULL,
  `program_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `uploaded_at` timestamp NULL DEFAULT NULL,
  `uploaded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_matrix_slots`
--

CREATE TABLE `exam_matrix_slots` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `exam_matrix_id` bigint(20) UNSIGNED NOT NULL,
  `slot_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_fixed` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_matrix_slot_subjects`
--

CREATE TABLE `exam_matrix_slot_subjects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `exam_matrix_slot_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_permits`
--

CREATE TABLE `exam_permits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_profile_id` bigint(20) UNSIGNED NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` tinyint(3) UNSIGNED NOT NULL,
  `exam_period` varchar(30) NOT NULL,
  `qr_token` varchar(191) NOT NULL,
  `generated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exam_permits`
--

INSERT INTO `exam_permits` (`id`, `student_profile_id`, `academic_year`, `semester`, `exam_period`, `qr_token`, `generated_by`, `generated_at`, `revoked_at`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-2026', 1, 'Prelim', '621ebd2e-dd36-4077-8944-8fbd6c8b6e61', 4, '2026-04-24 09:19:07', '2026-04-24 09:24:00', 0, '2026-04-20 08:44:16', '2026-04-24 09:24:00'),
(2, 2, '2025-2026', 1, 'Prelim', 'a2582d74-beb6-4690-8d51-7b3994626257', 4, '2026-04-21 08:36:06', '2026-04-21 09:05:49', 0, '2026-04-20 09:42:37', '2026-04-21 09:05:49');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_01_01_000001_create_programs_table', 1),
(5, '2026_01_01_000002_create_subjects_table', 1),
(6, '2026_01_01_000003_create_program_subjects_table', 1),
(7, '2026_01_01_000004_create_subject_requisites_table', 1),
(8, '2026_01_01_000005_create_sections_table', 1),
(9, '2026_01_01_000006_create_student_profiles_table', 1),
(10, '2026_01_01_000007_create_proctor_profiles_table', 1),
(11, '2026_01_01_000008_create_staff_profiles_table', 1),
(12, '2026_01_01_000009_create_student_subjects_table', 1),
(13, '2026_01_01_000010_create_rooms_table', 1),
(14, '2026_01_01_000011_create_academic_settings_table', 1),
(15, '2026_04_08_183349_add_employee_id_to_users_table', 2),
(16, '2026_04_09_000101_create_exam_matrices_table', 3),
(17, '2026_04_09_000102_create_exam_matrix_slots_table', 3),
(18, '2026_04_09_000103_create_section_exam_schedules_table', 3),
(19, '2026_04_09_000104_create_section_exam_schedule_slots_table', 3),
(20, '2026_04_09_000105_create_section_exam_schedule_slot_proctors_table', 4),
(21, '2026_04_09_000105_add_course_serial_number_to_subjects_table', 5),
(22, '2026_04_09_000106_create_subject_exam_references_table', 5),
(23, '2026_04_10_000201_create_exam_matrix_slot_subjects_table', 6),
(24, '2026_04_10_000202_backfill_exam_matrix_slot_subjects_and_drop_subject_id', 6),
(25, '2026_04_11_000203_make_exam_matrix_program_nullable', 6),
(26, '2026_04_11_000301_add_upload_fields_to_exam_matrices_table', 7),
(27, '2026_04_21_000201_create_exam_permits_table', 8),
(28, '2026_04_21_000202_create_exam_attendances_table', 8);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proctor_profiles`
--

CREATE TABLE `proctor_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` varchar(255) NOT NULL,
  `department` enum('IT','Tourism and Hospitality','General Education','Business and Management','Arts and Sciences','Senior High') NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `proctor_profiles`
--

INSERT INTO `proctor_profiles` (`id`, `user_id`, `employee_id`, `department`, `created_at`, `updated_at`) VALUES
(1, 3, '02000246810', 'IT', '2026-04-08 11:07:49', '2026-04-08 11:07:49'),
(2, 6, '02000481216', 'IT', '2026-04-10 19:34:04', '2026-04-10 19:34:04');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `name`, `code`, `created_at`, `updated_at`) VALUES
(1, 'Bachelor of Science in Computer Science', 'BSCS', '2026-04-08 11:06:24', '2026-04-08 11:06:24');

-- --------------------------------------------------------

--
-- Table structure for table `program_subjects`
--

CREATE TABLE `program_subjects` (
  `program_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `year_level` tinyint(3) UNSIGNED NOT NULL,
  `semester` tinyint(3) UNSIGNED NOT NULL COMMENT '1 or 2'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `program_subjects`
--

INSERT INTO `program_subjects` (`program_id`, `subject_id`, `year_level`, `semester`) VALUES
(1, 4, 1, 1),
(1, 5, 3, 1),
(1, 6, 1, 1),
(1, 7, 1, 1),
(1, 8, 1, 1),
(1, 9, 1, 1),
(1, 10, 1, 1),
(1, 11, 1, 1),
(1, 12, 1, 1),
(1, 13, 1, 2),
(1, 14, 1, 2),
(1, 15, 1, 2),
(1, 16, 1, 2),
(1, 17, 1, 2),
(1, 18, 1, 2),
(1, 19, 1, 2),
(1, 20, 1, 2),
(1, 21, 2, 1),
(1, 22, 2, 1),
(1, 23, 2, 1),
(1, 24, 2, 1),
(1, 25, 2, 1),
(1, 26, 2, 1),
(1, 27, 2, 1),
(1, 28, 2, 1),
(1, 29, 2, 2),
(1, 30, 2, 2),
(1, 31, 2, 2),
(1, 32, 2, 2),
(1, 33, 2, 2),
(1, 34, 2, 2),
(1, 35, 2, 2),
(1, 36, 2, 2),
(1, 37, 2, 2),
(1, 38, 3, 1),
(1, 39, 3, 1),
(1, 40, 3, 1),
(1, 41, 3, 1),
(1, 42, 3, 1),
(1, 43, 3, 1),
(1, 44, 3, 2),
(1, 45, 3, 2),
(1, 46, 3, 2),
(1, 47, 3, 2),
(1, 48, 3, 2),
(1, 49, 3, 2);

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 50,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `name`, `capacity`, `is_available`, `created_at`, `updated_at`) VALUES
(1, 'Room 101A', 30, 1, '2026-04-08 21:55:13', '2026-04-08 21:55:13'),
(2, 'Room 102A', 30, 1, '2026-04-11 07:56:38', '2026-04-11 07:56:38'),
(3, 'Room 201A', 20, 1, '2026-04-11 07:58:37', '2026-04-11 07:58:37'),
(4, 'AVR', 35, 1, '2026-04-11 07:59:06', '2026-04-11 07:59:06'),
(5, 'Room 203A', 35, 1, '2026-04-11 07:59:20', '2026-04-11 07:59:20'),
(6, 'Room 301A', 35, 1, '2026-04-11 07:59:35', '2026-04-11 07:59:35'),
(7, 'Room 302A', 35, 1, '2026-04-11 07:59:59', '2026-04-11 07:59:59'),
(8, 'Room 303A', 35, 1, '2026-04-11 08:00:19', '2026-04-11 08:00:19'),
(9, 'Room 304A', 35, 1, '2026-04-11 08:00:34', '2026-04-11 08:00:34'),
(10, 'Room 201B', 30, 1, '2026-04-11 08:00:47', '2026-04-11 08:00:47'),
(11, 'Room 202B', 40, 1, '2026-04-11 08:01:00', '2026-04-11 08:01:00'),
(12, 'Room 203B', 25, 1, '2026-04-11 08:01:17', '2026-04-11 08:01:17'),
(13, 'Room 204B', 25, 1, '2026-04-11 08:01:30', '2026-04-11 08:01:30'),
(14, 'Room 205B', 25, 1, '2026-04-11 08:01:44', '2026-04-11 08:01:44');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `program_id` bigint(20) UNSIGNED NOT NULL,
  `year_level` tinyint(3) UNSIGNED NOT NULL,
  `section_code` varchar(255) NOT NULL,
  `proctor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `program_id`, `year_level`, `section_code`, `proctor_id`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 'BSCS301A', 3, '2026-04-08 11:17:14', '2026-04-08 22:15:45'),
(2, 1, 1, 'BSCS101A', 6, '2026-04-10 19:31:13', '2026-04-10 19:34:04'),
(3, 1, 2, 'BSCS201A', NULL, '2026-04-11 07:57:56', '2026-04-11 07:57:56'),
(4, 1, 4, 'BSCS401A', NULL, '2026-04-11 07:58:08', '2026-04-11 07:58:08');

-- --------------------------------------------------------

--
-- Table structure for table `section_exam_schedules`
--

CREATE TABLE `section_exam_schedules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `exam_matrix_id` bigint(20) UNSIGNED NOT NULL,
  `section_id` bigint(20) UNSIGNED NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` tinyint(3) UNSIGNED NOT NULL,
  `exam_period` varchar(30) NOT NULL,
  `program_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `published_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `section_exam_schedule_slots`
--

CREATE TABLE `section_exam_schedule_slots` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `section_exam_schedule_id` bigint(20) UNSIGNED NOT NULL,
  `exam_matrix_slot_id` bigint(20) UNSIGNED DEFAULT NULL,
  `slot_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_fixed` tinyint(1) NOT NULL DEFAULT 0,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_manual_assignment` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `section_exam_schedule_slot_proctors`
--

CREATE TABLE `section_exam_schedule_slot_proctors` (
  `section_exam_schedule_slot_id` bigint(20) UNSIGNED NOT NULL,
  `proctor_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('7ljMiAfamzNiphP1Aflw4OL7kRrK2Jh1UwIx3xOt', 4, '192.168.8.35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiQkh3YTJjZDBET3dXMXdFMENJZ2t5alRoSGY1VmE5bjMyaE8zU0pIdSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDk6Imh0dHA6Ly8xOTIuMTY4LjguMzU6ODAwMC9jYXNoaWVyL3N0dWRlbnQtcGF5bWVudHMiO3M6NToicm91dGUiO3M6MzA6ImNhc2hpZXIuc3R1ZGVudC1wYXltZW50cy5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjQ7fQ==', 1777051492),
('7p4IUwOFnErHwoSy2OI7OllKdPnPN2aMtSbYrNIM', 5, '192.168.8.35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiTEU2QmxhQ255Q2pqcnZKanl0Ulh2WlhtbWxIN0xJWDNTTHlzdnBSRSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDE6Imh0dHA6Ly8xOTIuMTY4LjguMzU6ODAwMC9zdHVkZW50L3N1YmplY3RzIjtzOjU6InJvdXRlIjtzOjIyOiJzdHVkZW50LnN1YmplY3RzLmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NTt9', 1777051496),
('DsgDm0R6uCMbmBUBn9Q4AVe09aNf6uJFyHm2FnCa', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWGpJdURPZkJlYnR0d3NzR3FEdlBwbFJPZ1NvczZoQzZ6eG1hcjk3RCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1777049921),
('HDFELvcKIytFrJLuinfoNrx93eVDh06eMQHAHCQ4', 3, '192.168.8.35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiVFpsOWJGdUM0cHFSeFNqVWp6TE9GZWp0bXZNZDRXbkZjMllmd3VERSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDI6Imh0dHA6Ly8xOTIuMTY4LjguMzU6ODAwMC9wcm9jdG9yL3NjaGVkdWxlcyI7czo1OiJyb3V0ZSI7czoyMzoicHJvY3Rvci5zY2hlZHVsZXMuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTozO30=', 1777051448),
('v4QBMRZKfKX9CuHFDKdGdFjAz1TsrFLnRxDAHpK9', 2, '192.168.8.35', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiWDJNc2dxeXBLTEU3MGptQk4wQ2ZNbE5TTVQwVXRPUnE4ZjB1UUxlOCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTg6Imh0dHA6Ly8xOTIuMTY4LjguMzU6ODAwMC9hY2FkZW1pYy1oZWFkL2dlbmVyYWwtZXhhbS1tYXRyaXgiO3M6NToicm91dGUiO3M6Mzk6ImFjYWRlbWljLWhlYWQuZ2VuZXJhbC1leGFtLW1hdHJpeC5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI7fQ==', 1777051482),
('vHXVKIQPOfxcyPxtewmxYFlgi7UEXqQVxhQYLLkk', 3, '192.168.8.34', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/147.0.7727.99 Mobile/15E148 Safari/604.1', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiWDNBSlR2aGJTVFFLUDk1Q0g4SHVxaWxUVGhpUnl6TjVyeHJLc2pLbyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDA6Imh0dHA6Ly8xOTIuMTY4LjguMzU6ODAwMC9wcm9jdG9yL3NjYW5uZXIiO3M6NToicm91dGUiO3M6MjA6InByb2N0b3Iuc2Nhbm5lci5zaG93Ijt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Mzt9', 1777051040);

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` varchar(255) NOT NULL,
  `program_id` bigint(20) UNSIGNED NOT NULL,
  `year_level` tinyint(3) UNSIGNED NOT NULL,
  `section_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_profiles`
--

INSERT INTO `student_profiles` (`id`, `user_id`, `student_id`, `program_id`, `year_level`, `section_id`, `created_at`, `updated_at`) VALUES
(1, 5, '02000348098', 1, 3, 1, '2026-04-08 11:19:44', '2026-04-08 11:19:44'),
(2, 7, '02000987654', 1, 1, 2, '2026-04-10 19:35:30', '2026-04-10 19:35:30'),
(3, 8, '02000101010', 1, 3, 1, '2026-04-20 07:32:11', '2026-04-20 07:32:11');

-- --------------------------------------------------------

--
-- Table structure for table `student_subjects`
--

CREATE TABLE `student_subjects` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) NOT NULL,
  `course_serial_number` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `units` tinyint(3) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `code`, `course_serial_number`, `name`, `units`, `created_at`, `updated_at`) VALUES
(4, 'CITE1004', 'IT2221', 'Introduction to Computing', 3, '2026-04-09 08:50:37', '2026-04-09 08:50:37'),
(5, 'INTE1007', 'IT1807', 'Quantitative Methods', 3, '2026-04-10 10:37:42', '2026-04-10 10:37:42'),
(6, 'CITE1003', 'IT1708', 'Computer Programming 1', 3, '2026-04-10 22:13:48', '2026-04-10 22:13:48'),
(7, 'GEDC1002', 'GE1714', 'The Contemporary World', 3, '2026-04-10 22:15:29', '2026-04-10 22:15:29'),
(8, 'STIC1002', 'GE2202', 'Euthenics 1', 1, '2026-04-10 22:17:40', '2026-04-10 22:17:40'),
(9, 'GEDC1005', 'GE1707', 'Mathematics in the Modern World', 3, '2026-04-10 22:19:10', '2026-04-10 22:19:10'),
(10, 'NSTP1008', 'GE1801', 'National Service Training Program 1', 3, '2026-04-10 22:22:16', '2026-04-10 22:22:16'),
(11, 'PHED1005', 'GE2201', 'P.E./Pathfit 1: Movement Competency Training', 2, '2026-04-10 22:25:09', '2026-04-10 22:25:09'),
(12, 'GEDC1008', 'GE2204', 'Understanding the Self', 3, '2026-04-10 22:27:01', '2026-04-10 22:27:01'),
(13, 'CITE1006', 'IT1712', 'Computer Programming 2', 3, '2026-04-10 22:52:16', '2026-04-10 22:52:16'),
(14, 'COSC1002', 'IT1713', 'Discrete Structures 1 (Discrete Mathematics)', 3, '2026-04-10 22:54:37', '2026-04-10 22:54:37'),
(15, 'GEDC1010', 'GE1701', 'Art Appreciation', 3, '2026-04-10 22:55:59', '2026-04-10 22:55:59'),
(16, 'NSTP1010', 'GE1806', 'National Service Training Program 2', 3, '2026-04-10 22:57:55', '2026-04-10 22:57:55'),
(17, 'PHED1006', 'GE2302', 'P.E./Pathfit 2: Exercise-based Fitness Activities', 2, '2026-04-10 23:00:05', '2026-04-10 23:00:05'),
(18, 'GEDC1016', 'GE2203', 'Purposive Communication', 3, '2026-04-10 23:01:38', '2026-04-10 23:01:38'),
(19, 'GEDC1013', 'GE1713', 'Science, Technology, and Society', 3, '2026-04-10 23:02:43', '2026-04-10 23:02:43'),
(20, 'COSC1048', 'GE1703', 'College Calculus', 3, '2026-04-10 23:03:44', '2026-04-10 23:03:44'),
(21, 'COSC1003', 'IT1815', 'Data Structures and Algorithms', 3, '2026-04-10 23:06:16', '2026-04-10 23:06:16'),
(22, 'COSC1006', 'IT2313', 'Discrete Structures 2', 3, '2026-04-10 23:07:29', '2026-04-10 23:07:29'),
(23, 'GEDC1003', 'BM2313', 'The Entrepreneurial Mind', 3, '2026-04-10 23:11:28', '2026-04-10 23:11:28'),
(24, 'PHED1007', 'GE2303', 'P.E./Pathfit 3: Individual-Dual Sports', 3, '2026-04-10 23:13:31', '2026-04-10 23:13:31'),
(25, 'GEDC1006', 'GE1712', 'Readings in Philippine History', 3, '2026-04-10 23:14:35', '2026-04-10 23:14:35'),
(26, 'COSC1001', 'IT2311', 'Principles of Communication', 3, '2026-04-10 23:15:28', '2026-04-10 23:15:28'),
(27, 'GEDC1014', 'GE1804', 'Rizal\'s Life and Works', 3, '2026-04-10 23:16:33', '2026-04-10 23:16:33'),
(28, 'CITE1010', 'IT1907', 'Computer Programming 3', 3, '2026-04-10 23:18:36', '2026-04-10 23:18:36'),
(29, 'COSC1009', 'IT1809', 'Design and Analysis of Algorithms', 3, '2026-04-10 23:20:56', '2026-04-10 23:20:56'),
(30, 'CITE1011', 'IT1924', 'Information Management', 3, '2026-04-10 23:22:27', '2026-04-10 23:22:27'),
(31, 'GEDC1041', 'GE2101', 'Philippine Popular Culture', 3, '2026-04-10 23:23:44', '2026-04-10 23:23:44'),
(32, 'GEDC1009', 'GE2412', 'Ethics', 3, '2026-04-10 23:25:02', '2026-04-10 23:25:02'),
(33, 'PHED1008', 'GE2401', 'P.E./Pathfit 4: Team Sports', 2, '2026-04-10 23:26:39', '2026-04-10 23:26:39'),
(34, 'COSC1007', 'IT1906', 'Human-Computer Interaction', 3, '2026-04-10 23:29:45', '2026-04-10 23:29:45'),
(35, 'GEDC1045', 'GE1904', 'Great Books', 3, '2026-04-10 23:30:32', '2026-04-10 23:30:32'),
(36, 'COSC1012', 'IT2301', 'Fundamentals of Web Programming', 3, '2026-04-10 23:33:29', '2026-04-10 23:33:29'),
(37, 'INTE1023', 'IT2219', 'Computer Systems Architecture', 3, '2026-04-10 23:34:42', '2026-04-10 23:34:42'),
(38, 'COSC1014', 'IT2005', 'Theory of Computations with Automata', 3, '2026-04-11 07:26:16', '2026-04-11 07:26:16'),
(39, 'INSY1010', 'IT2501', 'Information Assurance and Security (Cybersecurity Fundamentals)', 3, '2026-04-11 07:29:03', '2026-04-11 07:29:03'),
(40, 'COSC1030', 'IT2116', 'Intermediate Web Programming', 3, '2026-04-11 07:30:35', '2026-04-11 07:30:35'),
(41, 'COSC1028', 'IT2206', 'Artificial Intelligence', 3, '2026-04-11 07:33:09', '2026-04-11 07:33:09'),
(42, 'CITE1008', 'IT1814', 'Application Development and Emerging Technologies', 3, '2026-04-11 07:35:51', '2026-04-11 07:35:51'),
(43, 'COSC1021', 'IT2004', 'Software Engineering 1', 3, '2026-04-11 07:37:23', '2026-04-11 07:37:23'),
(44, 'COSC1016', 'IT2032', 'Modelling and Simulation', 3, '2026-04-11 07:41:14', '2026-04-11 07:41:14'),
(45, 'CITE1013', 'IT2120', 'Computer Organization', 3, '2026-04-11 07:42:23', '2026-04-11 07:42:23'),
(46, 'COSC1025', 'IT2038', 'Software Engineering 2', 3, '2026-04-11 07:45:10', '2026-04-11 07:45:10'),
(47, 'COSC1042', 'IT2012', 'Game Programming', 3, '2026-04-11 07:46:12', '2026-04-11 07:46:12'),
(48, 'COSC1020', 'IT2409', 'Programming Languages', 3, '2026-04-11 07:47:37', '2026-04-11 07:47:37'),
(49, 'COSC1032', 'IT2207', 'Advanced Web Programming', 3, '2026-04-11 07:49:33', '2026-04-11 07:49:33');

-- --------------------------------------------------------

--
-- Table structure for table `subject_corequisites`
--

CREATE TABLE `subject_corequisites` (
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `corequisite_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subject_exam_references`
--

CREATE TABLE `subject_exam_references` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` tinyint(3) UNSIGNED NOT NULL,
  `exam_period` varchar(20) NOT NULL,
  `exam_reference_number` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subject_exam_references`
--

INSERT INTO `subject_exam_references` (`id`, `subject_id`, `academic_year`, `semester`, `exam_period`, `exam_reference_number`, `created_at`, `updated_at`) VALUES
(1, 39, '2025-2026', 1, 'Prelim', '10000013579', '2026-04-24 09:16:56', '2026-04-24 09:16:56');

-- --------------------------------------------------------

--
-- Table structure for table `subject_prerequisites`
--

CREATE TABLE `subject_prerequisites` (
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `prerequisite_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subject_prerequisites`
--

INSERT INTO `subject_prerequisites` (`subject_id`, `prerequisite_id`) VALUES
(13, 6),
(17, 11),
(21, 13),
(22, 14),
(24, 11),
(24, 17),
(28, 13),
(29, 13),
(30, 21),
(33, 11),
(33, 17),
(34, 4),
(36, 13),
(38, 29),
(39, 30),
(40, 36),
(41, 34),
(42, 13),
(43, 30),
(44, 38),
(46, 43),
(47, 28),
(48, 28),
(49, 40);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `employee_id` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','proctor','cashier','academic_head','admin') NOT NULL,
  `status` enum('pending','active','deactivated','archived') NOT NULL DEFAULT 'pending',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `employee_id`, `email`, `password`, `role`, `status`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Avelino', 'Layco', '02000000001', 'layco@system.com', '$2y$12$yZV./dEl7gAetfhDvGNpmuR.PWl.NRR65LztvxLEUWwD/au3R5IE.', 'admin', 'active', NULL, '2026-04-08 10:24:27', '2026-04-08 10:34:18'),
(2, 'Jayvee', 'Bautista', NULL, 'bautista@system.com', '$2y$12$IXKVZnO5DEyZvQIuPhT8o.Wx4j1m5Ia2p5.gcN.gLExnyKNOFXSCK', 'academic_head', 'active', NULL, '2026-04-08 10:56:31', '2026-04-08 10:56:31'),
(3, 'Joel', 'Pascua', NULL, 'pascua@system.com', '$2y$12$OLzdegg9Djdi.LH.yKqVpevq3JWUaN3J8dl3xvf/a3YzZVf4dXJHK', 'proctor', 'active', NULL, '2026-04-08 11:07:49', '2026-04-08 11:07:49'),
(4, 'Cherryl', 'Portacio', NULL, 'portacio@system.com', '$2y$12$8YPLRcpL0M7HN3qJHuZ3r.ckeP9RQFbj9VFPCxcm0/ONWIo8Q8dJy', 'cashier', 'active', NULL, '2026-04-08 11:08:43', '2026-04-08 11:08:43'),
(5, 'Jazer', 'Sanil', NULL, 'sanil@system.com', '$2y$12$Ms2RVsLeuVS/PhoKlYldSOrSOZo.U9BRT9koWGd95hbbKShzSxG3C', 'student', 'active', NULL, '2026-04-08 11:19:44', '2026-04-08 11:20:08'),
(6, 'Steven', 'Cristobal', NULL, 'cristobal@system.com', '$2y$12$WsIe3EoVF1/rQbjKy3eKK.jyZE8WtlIF3PbRFkslgBmF40bmqnNQa', 'proctor', 'active', NULL, '2026-04-10 19:34:04', '2026-04-11 08:11:07'),
(7, 'Stephen', 'Madayag', NULL, 'madayag@system.com', '$2y$12$HNFnoD0E9WCEP89jHSUcn.KL2Bl6EEF1Bagu6MYWyOoj8UVe7.OWm', 'student', 'active', NULL, '2026-04-10 19:35:30', '2026-04-10 19:36:43'),
(8, 'Airish John', 'Felix', NULL, 'felix@system.com', '$2y$12$37E1Pe0rahCP5bxgdu5Ube3TjC3bmh3VeAyYSRmPtKEujO/v5.VtO', 'student', 'active', NULL, '2026-04-20 07:32:11', '2026-04-20 09:00:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_head_profiles`
--
ALTER TABLE `academic_head_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `academic_head_profiles_user_id_unique` (`user_id`),
  ADD UNIQUE KEY `academic_head_profiles_employee_id_unique` (`employee_id`);

--
-- Indexes for table `academic_settings`
--
ALTER TABLE `academic_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `cashier_profiles`
--
ALTER TABLE `cashier_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cashier_profiles_user_id_unique` (`user_id`),
  ADD UNIQUE KEY `cashier_profiles_employee_id_unique` (`employee_id`);

--
-- Indexes for table `exam_attendances`
--
ALTER TABLE `exam_attendances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `exam_attendance_slot_student_unique` (`section_exam_schedule_slot_id`,`student_profile_id`),
  ADD KEY `exam_attendance_permit_fk` (`exam_permit_id`),
  ADD KEY `exam_attendance_logged_by_fk` (`logged_by`),
  ADD KEY `exam_attendance_student_logged_idx` (`student_profile_id`,`logged_at`);

--
-- Indexes for table `exam_matrices`
--
ALTER TABLE `exam_matrices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `exam_matrices_context_unique` (`academic_year`,`semester`,`exam_period`,`program_id`),
  ADD KEY `exam_matrices_created_by_foreign` (`created_by`),
  ADD KEY `exam_matrices_program_id_exam_period_index` (`program_id`,`exam_period`),
  ADD KEY `exam_matrices_uploaded_by_foreign` (`uploaded_by`),
  ADD KEY `exam_matrices_upload_scope_idx` (`academic_year`,`semester`,`exam_period`,`status`);

--
-- Indexes for table `exam_matrix_slots`
--
ALTER TABLE `exam_matrix_slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `exam_matrix_slots_unique` (`exam_matrix_id`,`slot_date`,`start_time`,`end_time`),
  ADD KEY `exam_matrix_slots_exam_matrix_id_is_fixed_index` (`exam_matrix_id`,`is_fixed`);

--
-- Indexes for table `exam_matrix_slot_subjects`
--
ALTER TABLE `exam_matrix_slot_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `exam_matrix_slot_subject_unique` (`exam_matrix_slot_id`,`subject_id`),
  ADD KEY `exam_matrix_slot_subjects_subject_id_foreign` (`subject_id`),
  ADD KEY `exam_matrix_slot_subject_order_index` (`exam_matrix_slot_id`,`sort_order`);

--
-- Indexes for table `exam_permits`
--
ALTER TABLE `exam_permits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `exam_permits_context_unique` (`student_profile_id`,`academic_year`,`semester`,`exam_period`),
  ADD UNIQUE KEY `exam_permits_qr_token_unique` (`qr_token`),
  ADD KEY `exam_permits_generated_by_fk` (`generated_by`),
  ADD KEY `exam_permits_period_active_idx` (`academic_year`,`semester`,`exam_period`,`is_active`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `proctor_profiles`
--
ALTER TABLE `proctor_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `proctor_profiles_user_id_unique` (`user_id`),
  ADD UNIQUE KEY `proctor_profiles_employee_id_unique` (`employee_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `programs_code_unique` (`code`);

--
-- Indexes for table `program_subjects`
--
ALTER TABLE `program_subjects`
  ADD PRIMARY KEY (`program_id`,`subject_id`),
  ADD KEY `program_subjects_subject_id_foreign` (`subject_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rooms_name_unique` (`name`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sections_program_id_year_level_section_code_unique` (`program_id`,`year_level`,`section_code`),
  ADD KEY `sections_proctor_id_foreign` (`proctor_id`);

--
-- Indexes for table `section_exam_schedules`
--
ALTER TABLE `section_exam_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `section_exam_schedule_context_unique` (`section_id`,`academic_year`,`semester`,`exam_period`),
  ADD KEY `section_exam_schedules_exam_matrix_id_foreign` (`exam_matrix_id`),
  ADD KEY `section_exam_schedules_published_by_foreign` (`published_by`),
  ADD KEY `section_exam_schedules_created_by_foreign` (`created_by`),
  ADD KEY `section_exam_schedules_publish_scope_idx` (`program_id`,`academic_year`,`semester`,`exam_period`,`status`);

--
-- Indexes for table `section_exam_schedule_slots`
--
ALTER TABLE `section_exam_schedule_slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `section_exam_slots_schedule_unique` (`section_exam_schedule_id`,`slot_date`,`start_time`,`end_time`),
  ADD UNIQUE KEY `section_exam_slots_room_unique` (`room_id`,`slot_date`,`start_time`,`end_time`),
  ADD KEY `section_exam_schedule_slots_exam_matrix_slot_id_foreign` (`exam_matrix_slot_id`),
  ADD KEY `section_exam_schedule_slots_slot_date_start_time_end_time_index` (`slot_date`,`start_time`,`end_time`),
  ADD KEY `section_exam_schedule_slots_subject_id_room_id_index` (`subject_id`,`room_id`);

--
-- Indexes for table `section_exam_schedule_slot_proctors`
--
ALTER TABLE `section_exam_schedule_slot_proctors`
  ADD PRIMARY KEY (`section_exam_schedule_slot_id`,`proctor_id`),
  ADD KEY `sched_slot_proctors_proctor_idx` (`proctor_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_profiles_user_id_unique` (`user_id`),
  ADD UNIQUE KEY `student_profiles_student_id_unique` (`student_id`),
  ADD KEY `student_profiles_program_id_foreign` (`program_id`),
  ADD KEY `student_profiles_section_id_foreign` (`section_id`);

--
-- Indexes for table `student_subjects`
--
ALTER TABLE `student_subjects`
  ADD PRIMARY KEY (`user_id`,`subject_id`),
  ADD KEY `student_subjects_subject_id_foreign` (`subject_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subjects_code_unique` (`code`),
  ADD UNIQUE KEY `subjects_course_serial_number_unique` (`course_serial_number`);

--
-- Indexes for table `subject_corequisites`
--
ALTER TABLE `subject_corequisites`
  ADD PRIMARY KEY (`subject_id`,`corequisite_id`),
  ADD KEY `subject_corequisites_corequisite_id_foreign` (`corequisite_id`);

--
-- Indexes for table `subject_exam_references`
--
ALTER TABLE `subject_exam_references`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_exam_refs_subject_scope_unique` (`subject_id`,`academic_year`,`semester`,`exam_period`),
  ADD UNIQUE KEY `subject_exam_refs_value_scope_unique` (`academic_year`,`semester`,`exam_period`,`exam_reference_number`),
  ADD KEY `subject_exam_refs_period_idx` (`academic_year`,`semester`,`exam_period`);

--
-- Indexes for table `subject_prerequisites`
--
ALTER TABLE `subject_prerequisites`
  ADD PRIMARY KEY (`subject_id`,`prerequisite_id`),
  ADD KEY `subject_prerequisites_prerequisite_id_foreign` (`prerequisite_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_employee_id_unique` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_head_profiles`
--
ALTER TABLE `academic_head_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `academic_settings`
--
ALTER TABLE `academic_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cashier_profiles`
--
ALTER TABLE `cashier_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `exam_attendances`
--
ALTER TABLE `exam_attendances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `exam_matrices`
--
ALTER TABLE `exam_matrices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `exam_matrix_slots`
--
ALTER TABLE `exam_matrix_slots`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=365;

--
-- AUTO_INCREMENT for table `exam_matrix_slot_subjects`
--
ALTER TABLE `exam_matrix_slot_subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `exam_permits`
--
ALTER TABLE `exam_permits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `proctor_profiles`
--
ALTER TABLE `proctor_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `section_exam_schedules`
--
ALTER TABLE `section_exam_schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `section_exam_schedule_slots`
--
ALTER TABLE `section_exam_schedule_slots`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=505;

--
-- AUTO_INCREMENT for table `student_profiles`
--
ALTER TABLE `student_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `subject_exam_references`
--
ALTER TABLE `subject_exam_references`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_head_profiles`
--
ALTER TABLE `academic_head_profiles`
  ADD CONSTRAINT `academic_head_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cashier_profiles`
--
ALTER TABLE `cashier_profiles`
  ADD CONSTRAINT `cashier_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_attendances`
--
ALTER TABLE `exam_attendances`
  ADD CONSTRAINT `exam_attendance_logged_by_fk` FOREIGN KEY (`logged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `exam_attendance_permit_fk` FOREIGN KEY (`exam_permit_id`) REFERENCES `exam_permits` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `exam_attendance_slot_fk` FOREIGN KEY (`section_exam_schedule_slot_id`) REFERENCES `section_exam_schedule_slots` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_attendance_student_fk` FOREIGN KEY (`student_profile_id`) REFERENCES `student_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_matrices`
--
ALTER TABLE `exam_matrices`
  ADD CONSTRAINT `exam_matrices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `exam_matrices_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `exam_matrices_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `exam_matrix_slots`
--
ALTER TABLE `exam_matrix_slots`
  ADD CONSTRAINT `exam_matrix_slots_exam_matrix_id_foreign` FOREIGN KEY (`exam_matrix_id`) REFERENCES `exam_matrices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_matrix_slot_subjects`
--
ALTER TABLE `exam_matrix_slot_subjects`
  ADD CONSTRAINT `exam_matrix_slot_subjects_exam_matrix_slot_id_foreign` FOREIGN KEY (`exam_matrix_slot_id`) REFERENCES `exam_matrix_slots` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_matrix_slot_subjects_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_permits`
--
ALTER TABLE `exam_permits`
  ADD CONSTRAINT `exam_permits_generated_by_fk` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `exam_permits_student_fk` FOREIGN KEY (`student_profile_id`) REFERENCES `student_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `proctor_profiles`
--
ALTER TABLE `proctor_profiles`
  ADD CONSTRAINT `proctor_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `program_subjects`
--
ALTER TABLE `program_subjects`
  ADD CONSTRAINT `program_subjects_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `program_subjects_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `sections_proctor_id_foreign` FOREIGN KEY (`proctor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sections_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `section_exam_schedules`
--
ALTER TABLE `section_exam_schedules`
  ADD CONSTRAINT `section_exam_schedules_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `section_exam_schedules_exam_matrix_id_foreign` FOREIGN KEY (`exam_matrix_id`) REFERENCES `exam_matrices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `section_exam_schedules_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `section_exam_schedules_published_by_foreign` FOREIGN KEY (`published_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `section_exam_schedules_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `section_exam_schedule_slots`
--
ALTER TABLE `section_exam_schedule_slots`
  ADD CONSTRAINT `section_exam_schedule_slots_exam_matrix_slot_id_foreign` FOREIGN KEY (`exam_matrix_slot_id`) REFERENCES `exam_matrix_slots` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `section_exam_schedule_slots_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `section_exam_schedule_slots_section_exam_schedule_id_foreign` FOREIGN KEY (`section_exam_schedule_id`) REFERENCES `section_exam_schedules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `section_exam_schedule_slots_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `section_exam_schedule_slot_proctors`
--
ALTER TABLE `section_exam_schedule_slot_proctors`
  ADD CONSTRAINT `sched_slot_proctors_slot_fk` FOREIGN KEY (`section_exam_schedule_slot_id`) REFERENCES `section_exam_schedule_slots` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sched_slot_proctors_user_fk` FOREIGN KEY (`proctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD CONSTRAINT `student_profiles_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`),
  ADD CONSTRAINT `student_profiles_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `student_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_subjects`
--
ALTER TABLE `student_subjects`
  ADD CONSTRAINT `student_subjects_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_subjects_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subject_corequisites`
--
ALTER TABLE `subject_corequisites`
  ADD CONSTRAINT `subject_corequisites_corequisite_id_foreign` FOREIGN KEY (`corequisite_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_corequisites_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subject_exam_references`
--
ALTER TABLE `subject_exam_references`
  ADD CONSTRAINT `subject_exam_references_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subject_prerequisites`
--
ALTER TABLE `subject_prerequisites`
  ADD CONSTRAINT `subject_prerequisites_prerequisite_id_foreign` FOREIGN KEY (`prerequisite_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_prerequisites_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
