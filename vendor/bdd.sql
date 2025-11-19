-- ================================
--   BASE DE DONNÉES : blog_db
-- ================================

CREATE DATABASE IF NOT EXISTS `blog_db`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE `blog_db`;

-- ================================
-- 1. SUPPRESSION DES TABLES
-- ================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `Commentaires`;
DROP TABLE IF EXISTS `Article_Tag`;
DROP TABLE IF EXISTS `Tags`;
DROP TABLE IF EXISTS `Articles`;
DROP TABLE IF EXISTS `Role_Permission`;
DROP TABLE IF EXISTS `Role_User`;
DROP TABLE IF EXISTS `Permissions`;
DROP TABLE IF EXISTS `Roles`;
DROP TABLE IF EXISTS `Utilisateurs`;

SET FOREIGN_KEY_CHECKS = 1;

-- ================================
-- 2. CREATION DES TABLES
-- ================================

-- Table Utilisateurs
CREATE TABLE `Utilisateurs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom_utilisateur` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `mot_de_passe` CHAR(60) NOT NULL, -- Hash bcrypt/argon2
    `est_actif` BOOLEAN DEFAULT TRUE,
    `date_inscription` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table Roles
CREATE TABLE `Roles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom_role` VARCHAR(50) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table Permissions
CREATE TABLE `Permissions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom_permission` VARCHAR(100) NOT NULL UNIQUE,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table pivot Role_User (un utilisateur a plusieurs rôles)
CREATE TABLE `Role_User` (
    `role_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `user_id`),
    FOREIGN KEY (`role_id`) REFERENCES `Roles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `Utilisateurs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table pivot Role_Permission
CREATE TABLE `Role_Permission` (
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    FOREIGN KEY (`role_id`) REFERENCES `Roles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `Permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table Articles
CREATE TABLE `Articles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `utilisateur_id` INT UNSIGNED NOT NULL,
    `titre` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `contenu` TEXT NOT NULL,
    `image_une` VARCHAR(255) NULL,
    `statut` ENUM('Brouillon', 'Publié', 'Archivé') DEFAULT 'Brouillon',
    `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `date_mise_a_jour` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`utilisateur_id`) REFERENCES `Utilisateurs`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table Tags
CREATE TABLE `Tags` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom_tag` VARCHAR(50) NOT NULL UNIQUE,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table pivot Article_Tag
CREATE TABLE `Article_Tag` (
    `article_id` INT UNSIGNED NOT NULL,
    `tag_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`article_id`, `tag_id`),
    FOREIGN KEY (`article_id`) REFERENCES `Articles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tag_id`) REFERENCES `Tags`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table Commentaires
CREATE TABLE `Commentaires` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `article_id` INT UNSIGNED NOT NULL,
    `nom_auteur` VARCHAR(100) NOT NULL,
    `email_auteur` VARCHAR(100) NULL,
    `contenu` TEXT NOT NULL,
    `statut` ENUM('En attente', 'Approuvé', 'Rejeté') DEFAULT 'En attente',
    `date_commentaire` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`article_id`) REFERENCES `Articles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================
-- 3. INSERTIONS DE DONNÉES
-- ================================

-- Mot de passe hashé (exemple)
SET @HASHED_PASSWORD =
'$2y$10$Q7iR7/h7Gq6yRzW2gP0pT.0.1oQ5t4T8W0y5fG8E7C8zM7/V2C9a';

-- Utilisateurs
INSERT INTO `Utilisateurs` (`id`, `nom_utilisateur`, `email`, `mot_de_passe`) VALUES
(1, 'AdminVTT', 'admin@vtt.com', @HASHED_PASSWORD),
(2, 'EditeurTrail', 'editeur@vtt.com', @HASHED_PASSWORD),
(3, 'ContributeurRando', 'contributeur@vtt.com', @HASHED_PASSWORD);

-- Rôles
INSERT INTO `Roles` (`id`, `nom_role`, `description`) VALUES
(1, 'Administrateur', 'Accès complet au tableau de bord et gestion des utilisateurs.'),
(2, 'Éditeur', 'Créer, modifier et publier des articles.'),
(3, 'Contributeur', 'Créer et modifier ses articles en brouillon.');

-- Permissions
INSERT INTO `Permissions` (`id`, `nom_permission`) VALUES
(1, 'admin_access'),
(2, 'article_creer'),
(3, 'article_editer_tous'),
(4, 'article_publier'),
(5, 'article_supprimer'),
(6, 'commentaire_gerer'),
(7, 'utilisateur_gerer'),
(8, 'tag_gerer');

-- Rôle → Permissions
-- Admin (toutes)
INSERT INTO `Role_Permission` (`role_id`, `permission_id`) VALUES
(1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8);

-- Éditeur
INSERT INTO `Role_Permission` (`role_id`, `permission_id`) VALUES
(2,1),(2,2),(2,3),(2,4),(2,6),(2,8);

-- Contributeur
INSERT INTO `Role_Permission` (`role_id`, `permission_id`) VALUES (3,2);

-- Utilisateur → Rôle
INSERT INTO `Role_User` (`user_id`, `role_id`) VALUES
(1,1),        -- AdminVTT → Administrateur
(2,2),(2,3),  -- EditeurTrail → Éditeur + Contributeur
(3,3);        -- ContributeurRando → Contributeur

-- Tags
INSERT INTO `Tags` (`id`, `nom_tag`, `slug`) VALUES
(1,'Traces GPS','traces-gps'),
(2,'Enduro','enduro'),
(3,'XC','xc'),
(4,'Suspension','suspension'),
(5,'Nutrition','nutrition'),
(6,'Entraînement','entrainement'),
(7,'Hydratation','hydratation');

-- Articles
INSERT INTO `Articles` (`id`, `utilisateur_id`, `titre`, `slug`, `contenu`, `statut`) VALUES
(1, 1, 'Top 5 des Traces VTT Enduro en Rhône-Alpes', 'top-5-traces-vtt-enduro-rhone-alpes',
 '# Introduction
Découvrez les descentes mythiques...
## La piste de l\'Écureuil
Une trace rapide...',
 'Publié'),

(2, 2, 'Réglage de la suspension : le SAG parfait pour le XC', 'reglage-suspension-sag-xc',
 'Le **SAG** est crucial en XC...',
 'Publié'),

(3, 3, 'Gérer l\'Hydratation sur une longue sortie VTT', 'gerer-hydratation-longue-sortie',
 'Au-delà de 3h, l\'eau seule ne suffit...',
 'Brouillon');

-- Article → Tag
INSERT INTO `Article_Tag` (`article_id`, `tag_id`) VALUES
(1,1),(1,2),(1,4),
(2,3),(2,4),
(3,5),(3,7);

-- Commentaires
INSERT INTO `Commentaires` (`article_id`, `nom_auteur`, `email_auteur`, `contenu`, `statut`) VALUES
(1,'Nicolas Rider','nic@trail.fr','Super article !','Approuvé'),
(1,'Anonyme',NULL,'Trop de monde...','En attente'),
(2,'ProXC','pro@xc.com','J’utilise le même SAG.','Approuvé');
