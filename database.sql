-- Nom de la base de donnée : "parking_finder"
-- moteur de base de données : "InnoDB"

--1. Création de la base de données

-- Création de la base de données si elle n'existe pas
-- Utilisation du jeu de caractère "utf8mb4" pour supporter les caractères spéciaux et emojis
CREATE DATABASE IF NOT EXISTS parking_finder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE parking_finder;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des sessions
CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL, --peut être NULL pour permettre le mode invité
    token VARCHAR(255) NOT NULL UNIQUE, --jeton d'authentification unique (sécurité)
    is_guest TINYINT(1) DEFAULT 0,
    ip_address VARCHAR(45), --adresse IP de l'utilisateur (pour éviter le vol de sessions)
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE --Si un utilisateur est supprimer, toutes ses sessions sont supprimées aussi
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des favoris
CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    parking_id VARCHAR(255) NOT NULL, --Identifiant provenant de l'API externe (type VARCHAR car le format n'est pas garantit numérique par l'API)
    parking_name VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, --Suppression des favoris si l'utilisateur est supprimé
    UNIQUE KEY unique_user_parking (user_id, parking_id) --empêche un utilisateur d'ajouter deux fois le même parking
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



--2. Indexation pour accélérer les requêtes
CREATE INDEX idx_sessions_token ON sessions(token);
CREATE INDEX idx_sessions_expires ON sessions(expires_at);
CREATE INDEX idx_favorites_user ON favorites(user_id);
