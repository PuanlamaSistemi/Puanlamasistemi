-- phpMyAdmin SQL Dump
-- version 5.2.0
-- Vize Projesi İçin Veritabanı (8. Hafta)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `puanlama_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `puanlama_db`;

--
-- Tablo `projects` (Projeler)
--
CREATE TABLE `projects` (
  `id` varchar(50) NOT NULL PRIMARY KEY,
  `name` varchar(255) NOT NULL,
  `description` text,
  `team_members` text,
  `video_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- MOCK DATA (Vize sunumunda göstermek için)
INSERT INTO `projects` (`id`, `name`, `description`, `team_members`, `video_url`) VALUES
('P-101', 'Akıllı Çöp Kutusu', 'Bu proje, sensörler yardımıyla geri dönüştürülebilir atıkları ayırt eder.', 'Ahmet Yılmaz, Ayşe Kaya', 'https://www.w3schools.com/html/mov_bbb.mp4'),
('P-102', 'Yapay Zeka Destekli Randevu Sistemi', 'Hastanelerde doktor yoğunluğunu algılayan online rezervasyon app_i.', 'Mehmet Demir', NULL);

--
-- Tablo `users` (Kullanıcılar)
--
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('jury','student') NOT NULL DEFAULT 'student',
  `project_id` varchar(50) DEFAULT NULL,
  `profile_title` varchar(100) DEFAULT NULL,
  `profile_about` text DEFAULT NULL,
  KEY `fk_project` (`project_id`),
  CONSTRAINT `fk_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- MOCK DATA
INSERT INTO `users` (`email`, `password`, `role`, `project_id`) VALUES
('juri@universite.edu.tr', '123456', 'jury', NULL),
('ogrenci@universite.edu.tr', '123456', 'student', 'P-101');

--
-- Tablo `votes` (Oylar)
--
CREATE TABLE `votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `project_id` varchar(50) NOT NULL,
  `design_score` tinyint(4) NOT NULL,
  `tech_score` tinyint(4) NOT NULL,
  `presentation_score` tinyint(4) NOT NULL,
  `innovation_score` tinyint(4) NOT NULL,
  `comment` text,
  `voter_session` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  KEY `fk_vote_project` (`project_id`),
  CONSTRAINT `fk_vote_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;
