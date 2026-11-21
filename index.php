<?php
// Point d'entrée unique de l'application.

session_start();

// Chargement des fichiers de configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Chargement automatique des classes (contrôleurs et modèles)
spl_autoload_register(function ($class) {
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

// Récupération des paramètres de routage
$page = isset($_GET['page']) ? $_GET['page'] : 'auth';
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

// Fonctions de sécurité et de gestion de session
function generateCSRFToken(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function validateCSRFToken(string $token): bool {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) || isset($_SESSION['is_guest']);
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    
    // Gestion du compte invité
    if (isset($_SESSION['is_guest']) && $_SESSION['is_guest']) {
        return ['id' => null, 'email' => 'Invité', 'is_guest' => true];
    }
    
    // Récupération des données utilisateur en base
    if (isset($_SESSION['user_id'])) {
        $user = new User();
        return $user->findById($_SESSION['user_id']);
    }
    
    return null;
}

// Routage et exécution de l'action demandée
try {
    switch ($page) {
        case 'auth':
            $controller = new AuthController();
            break;
        case 'map':
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
            header('Location: ' . APP_URL . '?page=auth');
            exit;
    }
    
    if (method_exists($controller, $action)) {
        $controller->$action();
    } else {
        $controller->index();
    }
} catch (Exception $e) {
    error_log("Application error: " . $e->getMessage());
    http_response_code(500);
    echo "Une erreur est survenue.";
}
