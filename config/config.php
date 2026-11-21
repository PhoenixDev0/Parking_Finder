<?php
/**
 *Fichier de configuration général de l'application
*/

//Paramètres de l'application
define('APP_NAME', 'Parking Finder'); //Nom de l'application
define('APP_VERSION', '1.0.0'); //Version sémentique de l'application
define('APP_URL', 'http://localhost/Parking_Finder'); //URL de l'application (XAMPP)

//Paramètres de la base de données
define('DB_HOST', 'localhost'); //Hôte de la base de données (XAMPP)
define('DB_NAME', 'parking_finder'); //Nom de la base de données (XAMPP)
define('DB_USER', 'root'); //Utilisateur de la base de données (XAMPP)
define('DB_PASS', ''); //Mot de passe de la base de données (XAMPP)
define('DB_CHARSET', 'utf8mb4'); //Encodage de la base de données (XAMPP)

//Configuration de la session
define('SESSION_LIFETIME', 60 * 60 * 24 * 30); //Durée de vie des cookies de persistance définis à 30 jours
define('SESSION_NAME', 'parking_finder_session'); //Nom du cookie de session

//Configuration de la sécurité
define('CSRF_TOKEN_NAME', 'csrf_token'); //Nom du token CSRF
define('PASSWORD_MIN_LENGTH', 4); //Longueur minimum du mot de passe à 4 caractères

//Configuration de la carte
define('DEFAULT_LAT', 48.8566); //Latitude de Paris centre
define('DEFAULT_LNG', 2.3522); //Longitude
define('DEFAULT_ZOOM', 15); //Niveau de zoom initial
//Rayon de recherche des parkings
define('DEFAULT_RADIUS', 1000); //Rayons par défaut : 1km
define('MAX_RADIUS', 15000); //Rayon maximum : 15km

//APIs externes
define('OVERPASS_API', 'https://overpass-api.de/api/interpreter'); //Overpass (pour récupérer les parkings)
define('NOMINATIM_API', 'https://nominatim.openstreetmap.org'); //Nominatim (pour récupérer les coordonnées)
define('OSRM_API', 'https://router.project-osrm.org'); //OSRM (pour récupérer les itinéraires)

//Paramètres de messagerie
define('CONTACT_EMAIL', 'contact@parking-finder.local'); //Email de réception du formulaire de contact

//Définition du fuseau horaire de l'application
date_default_timezone_set('Europe/Paris');

//Gestion des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

