<?php
/**
 * Contrôleur gérant l'authentification des utilisateurs (connexion, inscription, invité, déconnexion).
 */
class AuthController {
    private $user;
    private $session;

    public function __construct() {
        $this->user = new User();
        $this->session = new Session();
        
        $this->checkPersistentLogin();
    }

    /**
     * Vérifie la présence d'un cookie de session persistante et reconnecte l'utilisateur si valide.
     */
    private function checkPersistentLogin(): void {
        if (isLoggedIn()) {
            return;
        }

        if (isset($_COOKIE['remember_token'])) {
            try {
                $session = $this->session->findByToken($_COOKIE['remember_token']);
                if ($session) {
                    if ($session['is_guest']) {
                        $_SESSION['is_guest'] = true;
                    } else {
                        $_SESSION['user_id'] = $session['user_id'];
                    }
                    $this->session->extend($_COOKIE['remember_token']);
                } else {
                    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
                }
            } catch (Exception $e) {
                setcookie('remember_token', '', time() - 3600, '/', '', false, true);
                error_log("Persistent login check failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Affiche la vue d'authentification (ou redirige vers la carte si déjà connecté).
     */
    public function index(): void {
        if (isLoggedIn()) {
            header('Location: ' . APP_URL . '?page=map');
            exit;
        }
        
        $csrfToken = generateCSRFToken();
        require_once __DIR__ . '/../views/auth/index.php';
    }

    /**
     * Traite la requête AJAX de connexion.
     */
    public function login(): void {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['csrf_token']) || !validateCSRFToken($data['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
            return;
        }

        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Format d\'adresse email invalide']);
            return;
        }

        $user = $this->user->findByEmail($email);
        if (!$user || !$this->user->verifyPassword($password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect']);
            return;
        }

        $_SESSION['user_id'] = $user['id'];
        
        // Création du jeton de session persistante (30 jours)
        $token = $this->session->create($user['id'], false);
        setcookie('remember_token', $token, time() + SESSION_LIFETIME, '/', '', false, true);

        echo json_encode(['success' => true, 'redirect' => APP_URL . '?page=map']);
    }

    /**
     * Traite la requête AJAX d'inscription d'un nouvel utilisateur.
     */
    public function register(): void {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['csrf_token']) || !validateCSRFToken($data['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
            return;
        }

        $firstName = trim($data['first_name'] ?? '');
        $lastName = trim($data['last_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        // Validation du prénom
        if (mb_strlen($firstName) < 3) {
            echo json_encode(['success' => false, 'message' => 'Le prénom doit contenir au moins 3 caractères']);
            return;
        }
        if (!preg_match('/^[\p{L}\s\-\']+$/u', $firstName)) {
            echo json_encode(['success' => false, 'message' => 'Le prénom ne doit contenir que des lettres, espaces, tirets ou apostrophes']);
            return;
        }

        // Validation du nom
        if (mb_strlen($lastName) < 3) {
            echo json_encode(['success' => false, 'message' => 'Le nom doit contenir au moins 3 caractères']);
            return;
        }
        if (!preg_match('/^[\p{L}\s\-\']+$/u', $lastName)) {
            echo json_encode(['success' => false, 'message' => 'Le nom ne doit contenir que des lettres, espaces, tirets ou apostrophes']);
            return;
        }

        // Validation de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Format d\'adresse email invalide']);
            return;
        }

        if ($this->user->emailExists($email)) {
            echo json_encode(['success' => false, 'message' => 'Cette adresse email est déjà utilisée']);
            return;
        }

        // Validation du mot de passe
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins ' . PASSWORD_MIN_LENGTH . ' caractères']);
            return;
        }

        if ($password !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'Les mots de passe ne correspondent pas']);
            return;
        }

        // Création du compte et initialisation de la session
        $userId = $this->user->create($email, $password, $firstName, $lastName);
        $_SESSION['user_id'] = $userId;
        
        $token = $this->session->create($userId, false);
        setcookie('remember_token', $token, time() + SESSION_LIFETIME, '/', '', false, true);

        echo json_encode(['success' => true, 'redirect' => APP_URL . '?page=map']);
    }

    /**
     * Active le mode invité sans compte persistant.
     */
    public function guest(): void {
        header('Content-Type: application/json');
        $_SESSION['is_guest'] = true;
        echo json_encode(['success' => true, 'redirect' => APP_URL . '?page=map']);
    }

    /**
     * Déconnecte l'utilisateur, détruit la session et supprime les cookies associés.
     */
    public function logout(): void {
        if (isset($_COOKIE['remember_token'])) {
            $this->session->delete($_COOKIE['remember_token']);
            setcookie('remember_token', '', time() - 3600, '/');
        }

        session_destroy();
        header('Location: ' . APP_URL . '?page=auth');
        exit;
    }
}
