<?php
class AuthController {
    private $user; //Modèle de l'utilisateur
    private $session; //Modèle de la session

    //Initialisation des modèles
    public function __construct() {
        $this->user = new User();
        $this->session = new Session();
        
        //restauration de la session (si elle existe)
        $this->checkPersistentLogin();
    }

    //Vérification de la session persistante
    private function checkPersistentLogin(): void {
        //Si l'utilisateur est déjà connecté, on ne vérifie pas le cookie
        if (isLoggedIn()) {
            return;
        }

        //Vérification du cookie client
        if (isset($_COOKIE['remember_token'])) {
            try {
                //recherche de la session dans la base de données
                $session = $this->session->findByToken($_COOKIE['remember_token']);
                if ($session) {
                    //Si la session est trouvée, on la charge dans la session
                    if ($session['is_guest']) {
                        $_SESSION['is_guest'] = true;
                    } else {
                        $_SESSION['user_id'] = $session['user_id'];
                    }
                    //Extension de la session
                    $this->session->extend($_COOKIE['remember_token']);
                } else {
                    //Si le jeton est invalide, on le supprime
                    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
                }
            } catch (Exception $e) {
                //Gestion des erreurs
                setcookie('remember_token', '', time() - 3600, '/', '', false, true);
                error_log("Persistent login check failed: " . $e->getMessage());
            }
        }
    }

    //Affichage de la page d'authentification
    public function index(): void {
        //redirection vers la page de la carte si l'utilisateur est déjà connecté
        if (isLoggedIn()) {
            header('Location: ' . APP_URL . '?page=map');
            exit;
        }
        
        //génération d'un token CSRF pour les formulaires
        $csrfToken = generateCSRFToken();
        require_once __DIR__ . '/../views/auth/index.php';
    }

    //traitement de la connexion
    public function login(): void {
        header('Content-Type: application/json');
        
        //Restriction de méthode : on autorise que la méthode POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        //Lecture des données envoyées
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validation du token CSRF
        if (!isset($data['csrf_token']) || !validateCSRFToken($data['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
            return;
        }

        //Nettoyage des données
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        // Validation du format de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Format d\'adresse email invalide']);
            return;
        }

        //Recherche de l'utilisateur
        $user = $this->user->findByEmail($email);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect']);
            return;
        }

        // Vérification du mot de passe
        if (!$this->user->verifyPassword($password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect']);
            return;
        }

        //Si l'utilisateur est trouvé et le mot de passe est correct, on créer une session
        $_SESSION['user_id'] = $user['id'];
        
        // Création d'un token de session persistante
        $token = $this->session->create($user['id'], false);
        //Configuration du cookie de sécurité
        setcookie('remember_token', $token, time() + SESSION_LIFETIME, '/', '', false, true);

        echo json_encode(['success' => true, 'redirect' => APP_URL . '?page=map']);
    }

    //Gestion de l'inscription
    public function register(): void {
        header('Content-Type: application/json');
        
        //Restriction de méthode : on autorise que la méthode POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        //Lecture des données envoyées
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validation du token CSRF
        if (!isset($data['csrf_token']) || !validateCSRFToken($data['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
            return;
        }

        //Nettoyage des données
        $firstName = trim($data['first_name'] ?? '');
        $lastName = trim($data['last_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        // Validation du prénom (minimum 3 caractères)
        if (mb_strlen($firstName) < 3) {
            echo json_encode(['success' => false, 'message' => 'Le prénom doit contenir au moins 3 caractères']);
            return;
        }

        // Validation des caractères du prénom (lettres, espaces, tirets et apostrophes uniquement)
        if (!preg_match('/^[\p{L}\s\-\']+$/u', $firstName)) {
            echo json_encode(['success' => false, 'message' => 'Le prénom ne doit contenir que des lettres, espaces, tirets ou apostrophes']);
            return;
        }

        // Validation du nom (minimum 3 caractères)
        if (mb_strlen($lastName) < 3) {
            echo json_encode(['success' => false, 'message' => 'Le nom doit contenir au moins 3 caractères']);
            return;
        }

        // Validation des caractères du nom (lettres, espaces, tirets et apostrophes uniquement)
        if (!preg_match('/^[\p{L}\s\-\']+$/u', $lastName)) {
            echo json_encode(['success' => false, 'message' => 'Le nom ne doit contenir que des lettres, espaces, tirets ou apostrophes']);
            return;
        }

        // Validation du format de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Format d\'adresse email invalide']);
            return;
        }

        // Vérification de l'existence de l'email
        if ($this->user->emailExists($email)) {
            echo json_encode(['success' => false, 'message' => 'Cette adresse email est déjà utilisée']);
            return;
        }

        // Vérification de la longueur du mot de passe
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins ' . PASSWORD_MIN_LENGTH . ' caractères']);
            return;
        }

        // Vérification de la confirmation du mot de passe
        if ($password !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'Les mots de passe ne correspondent pas']);
            return;
        }

        // Création de l'utilisateur avec prénom et nom
        $userId = $this->user->create($email, $password, $firstName, $lastName);
        
        // Auto-login après l'inscription
        $_SESSION['user_id'] = $userId;
        
        // Création d'un token de session persistante
        $token = $this->session->create($userId, false);
        setcookie('remember_token', $token, time() + SESSION_LIFETIME, '/', '', false, true);

        echo json_encode(['success' => true, 'redirect' => APP_URL . '?page=map']);
    }

    //Gestion du mode invité
    public function guest(): void {
        header('Content-Type: application/json');
        
        //Création d'une session invité
        $_SESSION['is_guest'] = true;
        
        echo json_encode(['success' => true, 'redirect' => APP_URL . '?page=map']);
    }

    //Gestion de la déconnexion
    public function logout(): void {
        // Suppression du jeton de session
        if (isset($_COOKIE['remember_token'])) {
            $this->session->delete($_COOKIE['remember_token']);
            //Suppression du cookie
            setcookie('remember_token', '', time() - 3600, '/');
        }

        // Destruction de la session
        session_destroy();

        //Redirection vers la page d'authentification
        header('Location: ' . APP_URL . '?page=auth');
        exit;
    }
}
