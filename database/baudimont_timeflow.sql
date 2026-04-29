-- ================================================================
-- BASE DE DONNÉES : baudimont_timeflow
-- Compatible MySQL 5.7+ / MariaDB 10.3+ (XAMPP)
--
-- INSTALLATION :
--   1. Ouvrez phpMyAdmin → http://localhost/phpmyadmin
--   2. Cliquez sur "Importer" dans la barre du haut
--   3. Sélectionnez ce fichier et cliquez "Exécuter"
--   OU via la ligne de commande :
--      mysql -u root -p < baudimont_timeflow.sql
-- ================================================================

-- Création / sélection de la base
CREATE DATABASE IF NOT EXISTS `baudimont_timeflow`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `baudimont_timeflow`;

-- ----------------------------------------------------------------
-- Table : users
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`            VARCHAR(36)  NOT NULL,
  `full_name`     VARCHAR(120) NOT NULL,
  `email`         VARCHAR(180) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `team_id`       ENUM('dsi','tech','restauration','entretien') NOT NULL,
  `role`          ENUM('member','admin') NOT NULL DEFAULT 'member',
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- Table : punch_records
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `punch_records` (
  `id`             VARCHAR(36)  NOT NULL,
  `user_id`        VARCHAR(36)  NOT NULL,
  `user_full_name` VARCHAR(120) NOT NULL,
  `team_id`        ENUM('dsi','tech','restauration','entretien') NOT NULL,
  `kind`           ENUM('in','break_out','break_in','out') NOT NULL,
  `location`       ENUM('onsite','remote') NOT NULL DEFAULT 'onsite',
  `at_time`        DATETIME     NOT NULL,
  `validated`      TINYINT(1)   NOT NULL DEFAULT 0,
  `late`           TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id`   (`user_id`),
  KEY `idx_team_id`   (`team_id`),
  KEY `idx_at_time`   (`at_time`),
  CONSTRAINT `fk_punch_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- Table : team_codes  (codes d'accès équipes)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `team_codes` (
  `team_id`     ENUM('dsi','tech','restauration','entretien') NOT NULL,
  `member_code` VARCHAR(80)  NOT NULL,
  `admin_code`  VARCHAR(80)  NOT NULL,
  PRIMARY KEY (`team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- Données de démo — Utilisateurs
-- Mot de passe pour tous : demo1234
-- Hash bcrypt généré avec password_hash('demo1234', PASSWORD_BCRYPT)
-- ----------------------------------------------------------------
INSERT IGNORE INTO `users` (`id`, `full_name`, `email`, `password_hash`, `team_id`, `role`, `created_at`) VALUES
('u1', 'Marie Dupont',   'marie@baudimont.fr',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dsi',          'member', NOW()),
('u2', 'Pierre Martin',  'pierre@baudimont.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dsi',          'member', NOW()),
('u3', 'Sophie Laurent', 'sophie@baudimont.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tech',         'member', NOW()),
('u4', 'Karim Belkacem', 'karim@baudimont.fr',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'restauration', 'member', NOW()),
('u5', 'Aïcha Ndiaye',   'aicha@baudimont.fr',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'entretien',    'member', NOW()),
('u6', 'Admin DSI',      'admin@baudimont.fr',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dsi',          'admin',  NOW());

-- ----------------------------------------------------------------
-- Données de démo — Codes équipes
-- ----------------------------------------------------------------
INSERT IGNORE INTO `team_codes` (`team_id`, `member_code`, `admin_code`) VALUES
('dsi',          'DSI-2024-XK9',          'ADMIN-DSI-2026-PRIV'),
('tech',         'Tech-2024-YM7',         'ADMIN-TECH-2026-PRIV'),
('restauration', 'Restauration-2024-ZP5', 'ADMIN-REST-2026-PRIV'),
('entretien',    'Entretien-2026-WQ3',    'ADMIN-ENT-2026-PRIV');

-- ----------------------------------------------------------------
-- Données de démo — Pointages d'aujourd'hui
-- ----------------------------------------------------------------
INSERT IGNORE INTO `punch_records` (`id`, `user_id`, `user_full_name`, `team_id`, `kind`, `location`, `at_time`, `validated`, `late`) VALUES
('p1', 'u1', 'Marie Dupont',   'dsi',  'in', 'onsite', CONCAT(CURDATE(), ' 08:55:00'), 1, 0),
('p2', 'u2', 'Pierre Martin',  'dsi',  'in', 'remote', CONCAT(CURDATE(), ' 09:12:00'), 0, 1),
('p3', 'u3', 'Sophie Laurent', 'tech', 'in', 'onsite', CONCAT(CURDATE(), ' 08:58:00'), 1, 0);

-- ----------------------------------------------------------------
-- Vue pratique : derniers pointages par utilisateur aujourd'hui
-- ----------------------------------------------------------------
CREATE OR REPLACE VIEW `v_today_punches` AS
SELECT
  p.*,
  u.full_name,
  u.email
FROM punch_records p
JOIN users u ON u.id = p.user_id
WHERE DATE(p.at_time) = CURDATE()
ORDER BY p.at_time DESC;
