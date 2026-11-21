<?php
/**
 *Point d'entrée unique de l'application
 *Utilisation du patron de conception "Front Controller"
*/



//1. Configuration de l'environnement :

//Démarrage de la session
session_start();

//chargement des fichiers de configuration et de la base de données
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

//Chargement des classes (autoload)
spl_autoload_register(function ($class) {
    //Définition des chemins possibles pour les classes
    $paths = [
        __DIR__ . '/controllers/' . $class . '.php',
        __DIR__ . '/models/' . $class . '.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});




//2. Traitement de la requête :

//Récupération de la page demandée (via méthode GET)
$page = isset($_GET['page']) ? $_GET['page'] : 'auth';
$action = isset($_GET['action']) ? $_GET['action'] : 'index';




//3. Sécurité :

//Génération d'un token CSRF (protection contre les attaques CSRF)
function generateCSRFToken(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

//Validation du token CSRF (retourne vrai si le token est valide)
function validateCSRFToken(string $token): bool {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

//Vérification de la connexion de l'utilisateur
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) || isset($_SESSION['is_guest']);
}

//Récupération de l'utilisateur connecté
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    
    //Gestion du mode invité
    if (isset($_SESSION['is_guest']) && $_SESSION['is_guest']) {
        return ['id' => null, 'email' => 'Invité', 'is_guest' => true];
    }
    
    //Hydratation de l'utilisateur depuis la base de données
    if (isset($_SESSION['user_id'])) {
        $user = new User();
        return $user->findById($_SESSION['user_id']);
    }
    
    return null;
}



//4. Routage :

try {
    //Whitelist des routes autorisées (protection contre les attaques XSS)
    switch ($page) {
        case 'auth':
            $controller = new AuthController();
            break;
        case 'map':
            //Redirection vers la page d'authentification si l'utilisateur n'est pas connecté
            if (!isLoggedIn()) {
                header('Location: ' . APP_URL . '?page=auth');
                exit;
            }
            $controller = new MapController();
            break;
        case 'profile':
            if (!isLoggedIn()) {
                header('Location: ' . APP_URL . '?page=auth');
                exit;
            }
            $controller = new ProfileController();
            break;
        case 'api':
            $controller = new ApiController();
            break;
        default:
            //Redirection vers la page d'authentification si la route n'est pas autorisée
            header('Location: ' . APP_URL . '?page=auth');
            exit;
    }
    
    //Appel de la méthode de l'action demandée
    if (method_exists($controller, $action)) {
        $controller->$action();
    } else {
        $controller->index();
    }
} catch (Exception $e) {
    //Gestion des exceptions
    error_log("Application error: " . $e->getMessage());
    http_response_code(500);
    echo "Une erreur est survenue.";
}
