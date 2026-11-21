<?php
/**
 * Modèle de configuration générale de l'application.
 * Copier ce fichier vers 'config.php' et adapter les valeurs à l'environnement cible.
 */

// Paramètres de l'application
define('APP_NAME', 'Parking Finder');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/Parking_Finder');

// Paramètres de connexion MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'parking_finder');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Paramètres de session
define('SESSION_LIFETIME', 60 * 60 * 24 * 30);
define('SESSION_NAME', 'parking_finder_session');

// Sécurité
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 4);

// Paramètres cartographiques
define('DEFAULT_LAT', 48.8566);
define('DEFAULT_LNG', 2.3522);
define('DEFAULT_ZOOM', 15);
define('DEFAULT_RADIUS', 1000);
define('MAX_RADIUS', 15000);

// Endpoints des APIs externes
define('OVERPASS_API', 'https://overpass-api.de/api/interpreter');
define('NOMINATIM_API', 'https://nominatim.openstreetmap.org');
define('OSRM_API', 'https://router.project-osrm.org');

// Messagerie
define('CONTACT_EMAIL', 'contact@parking-finder.local');

// Environnement et fuseau horaire
date_default_timezone_set('Europe/Paris');
error_reporting(E_ALL);
ini_set('display_errors', 1);
