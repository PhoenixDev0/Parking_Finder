<?php
/**
 * Fichier d'exemple de configuration générale de l'application
 * Copiez ce fichier sous le nom 'config.php' et adaptez les valeurs à votre environnement.
 */

// Paramètres de l'application
define('APP_NAME', 'Parking Finder'); // Nom de l'application
define('APP_VERSION', '1.0.0'); // Version sémantique de l'application
define('APP_URL', 'http://localhost/Parking_Finder'); // URL de base de l'application (adapter selon le dossier htdocs)

// Paramètres de la base de données
define('DB_HOST', 'localhost'); // Hôte de la base de données (XAMPP : localhost)
define('DB_NAME', 'parking_finder'); // Nom de la base de données
define('DB_USER', 'root'); // Utilisateur MySQL (défaut XAMPP : root)
define('DB_PASS', ''); // Mot de passe MySQL (défaut XAMPP : vide)
define('DB_CHARSET', 'utf8mb4'); // Encodage des caractères

// Configuration de la session
define('SESSION_LIFETIME', 60 * 60 * 24 * 30); // Durée de vie du cookie 'remember me' (30 jours)
define('SESSION_NAME', 'parking_finder_session'); // Nom du cookie de session

// Configuration de la sécurité
define('CSRF_TOKEN_NAME', 'csrf_token'); // Nom du token CSRF
define('PASSWORD_MIN_LENGTH', 4); // Longueur minimale du mot de passe

// Configuration de la carte
define('DEFAULT_LAT', 48.8566); // Latitude initiale (Paris)
define('DEFAULT_LNG', 2.3522); // Longitude initiale (Paris)
define('DEFAULT_ZOOM', 15); // Zoom initial
define('DEFAULT_RADIUS', 1000); // Rayon de recherche par défaut (en mètres)
define('MAX_RADIUS', 15000); // Rayon maximal de recherche (en mètres)

// APIs externes
define('OVERPASS_API', 'https://overpass-api.de/api/interpreter'); // API Overpass (OpenStreetMap)
define('NOMINATIM_API', 'https://nominatim.openstreetmap.org'); // API Nominatim (Géocodage)
define('OSRM_API', 'https://router.project-osrm.org'); // API OSRM (Itinéraires)

// Paramètres de messagerie
define('CONTACT_EMAIL', 'contact@parking-finder.local'); // Email de réception des messages

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

// Gestion des erreurs (en développement)
error_reporting(E_ALL);
ini_set('display_errors', 1);
