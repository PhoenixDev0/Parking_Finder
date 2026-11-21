<?php
class ProfileController {
    private $user; //Accès aux utilisateurs
    private $favorite; //Accès aux favoris

    //Initialisation des objets
    public function __construct() {
        $this->user = new User();
        $this->favorite = new Favorite();
        
        //Vérification de la connexion
        if (!isLoggedIn()) {
            header('Location: ' . APP_URL . '?page=auth');
            exit;
        }
    }

    //Affichage de la page de profil
    public function index(): void {
        $user = getCurrentUser();
        $csrfToken = generateCSRFToken(); //Génération d'un token CSRF
        
        //Chargement des favoris
        $favorites = [];
        if ($user && !empty($user['id'])) {
            $favorites = $this->favorite->getByUserId($user['id']);
        }
        
        require_once __DIR__ . '/../views/profile/index.php';
    }

    //Mise à jour de l'email
    public function updateEmail(): void {
        header('Content-Type: application/json');
        
        //On autorise uniquement la méthode POST pour les modifications de données
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        //Vérification des privilèges de l'utilisateur (les invités ne peuvent pas modifier leurs données)
        $user = getCurrentUser();
        if (!$user || !empty($user['is_guest'])) {
            echo json_encode(['success' => false, 'message' => 'Action non autorisée pour les invités']);
            return;
        }

        //Récupération des données
        $data = json_decode(file_get_contents('php://input'), true);
        
        //Validation du token CSRF
        if (!isset($data['csrf_token']) || !validateCSRFToken($data['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
            return;
        }

        $email = trim($data['email'] ?? '');

        //Validation du format de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Format d\'adresse email invalide']);
            return;
        }

        //Vérification de l'existence de l'email
        if ($this->user->emailExists($email, $user['id'])) {
            echo json_encode(['success' => false, 'message' => 'Cette adresse email est déjà utilisée']);
            return;
        }

        //Mise à jour de l'email
        if ($this->user->updateEmail($user['id'], $email)) {
            echo json_encode(['success' => true, 'message' => 'Email mis à jour avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
        }
    }

    //Mise à jour du mot de passe
    public function updatePassword(): void {
        header('Content-Type: application/json');
        
        //On autorise uniquement la méthode POST pour les modifications de données
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        //Vérification des privilèges de l'utilisateur (les invités ne peuvent pas modifier leurs données)
        $user = getCurrentUser();
        if (!$user || !empty($user['is_guest'])) {
            echo json_encode(['success' => false, 'message' => 'Action non autorisée pour les invités']);
            return;
        }

        //Récupération des données
        $data = json_decode(file_get_contents('php://input'), true);
        
        //Validation du token CSRF
        if (!isset($data['csrf_token']) || !validateCSRFToken($data['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
            return;
        }

        //Récupération des données
        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        //Vérification du mot de passe actuel
        $fullUser = $this->user->findByEmail($user['email']);
        if (!$fullUser || !$this->user->verifyPassword($currentPassword, $fullUser['password'])) {
            echo json_encode(['success' => false, 'message' => 'Mot de passe actuel incorrect']);
            return;
        }

        //Validation de la longueur du nouveau mot de passe
        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            echo json_encode(['success' => false, 'message' => 'Le nouveau mot de passe doit contenir au moins ' . PASSWORD_MIN_LENGTH . ' caractères']);
            return;
        }

        //Vérification de la confirmation du mot de passe
        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'Les nouveaux mots de passe ne correspondent pas']);
            return;
        }

        //Mise à jour et hash du nouveau mot de passe
        if ($this->user->updatePassword($user['id'], $newPassword)) {
            echo json_encode(['success' => true, 'message' => 'Mot de passe mis à jour avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
        }
    }
}
