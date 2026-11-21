<?php
/**
 *Gestionnaire de connexion à la base de donnée
 *Utilisation du patron "Singleton"
*/

//Inclusion du fichier de configuration globale
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null; //Instance unique de la classe
    private $pdo; //Objet PDO pour la connexion

    private function __construct() {
        try {
            //Connexion à la base de données
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            //Configuration des options de connexion PDO
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, //Mode d'erreur
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, //Mode de récupération des données
                PDO::ATTR_EMULATE_PREPARES => false, //Désactivation des requêtes préparées simulées
            ];
            //Initialisation de la connexion PDO
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            //Gestion des erreurs de connexion
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Connexion à la base de données impossible.");
        }
    }

    //Récupération de l'instance unique de la classe
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    //Récupération de la connexion PDO
    public function getConnection(): PDO {
        return $this->pdo;
    }

    //Protection contre la clonage de l'instance
    private function __clone() {}

    //Protection contre la désérialisation de l'instance pour empêcher la réinitialisation de l'instance
    public function __wakeup() {
        throw new Exception("La désérialisation de l'instance est interdite.");
    }
}
