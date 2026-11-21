<?php
/**
 * Contrôleur gérant l'espace profil utilisateur et les paramètres de compte.
 */
class ProfileController {
    private $user;
    private $favorite;

    public function __construct() {
        $this->user = new User();
        $this->favorite = new Favorite();
        
        if (!isLoggedIn()) {
            header('Location: ' . APP_URL . '?page=auth');
            exit;
        }
    }

    /**
     * Affiche la vue du profil utilisateur avec la liste de ses favoris.
     */
    public function index(): void {
        $user = getCurrentUser();
        $csrfToken = generateCSRFToken();
        
        $favorites = [];
        if ($user && !empty($user['id'])) {
            $favorites = $this->favorite->getByUserId($user['id']);
        }
        
        require_once __DIR__ . '/../views/profile/index.php';
    }

    /**
     * Traite la mise à jour de l'adresse email de l'utilisateur.
     */
    public function updateEmail(): void {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        $user = getCurrentUser();
        if (!$user || !empty($user['is_guest'])) {
            echo json_encode(['success' => false, 'message' => 'Action non autorisée pour les invités']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['csrf_token']) || !validateCSRFToken($data['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
            return;
        }

        $email = trim($data['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Format d\'adresse email invalide']);
            return;
        }

        if ($this->user->emailExists($email, $user['id'])) {
            echo json_encode(['success' => false, 'message' => 'Cette adresse email est déjà utilisée']);
            return;
        }

        if ($this->user->updateEmail($user['id'], $email)) {
            echo json_encode(['success' => true, 'message' => 'Email mis à jour avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
        }
    }

    /**
     * Traite le changement de mot de passe de l'utilisateur.
     */
    public function updatePassword(): void {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        $user = getCurrentUser();
        if (!$user || !empty($user['is_guest'])) {
            echo json_encode(['success' => false, 'message' => 'Action non autorisée pour les invités']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['csrf_token']) || !validateCSRFToken($data['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
            return;
        }

        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        $fullUser = $this->user->findByEmail($user['email']);
        if (!$fullUser || !$this->user->verifyPassword($currentPassword, $fullUser['password'])) {
            echo json_encode(['success' => false, 'message' => 'Mot de passe actuel incorrect']);
            return;
        }

        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            echo json_encode(['success' => false, 'message' => 'Le nouveau mot de passe doit contenir au moins ' . PASSWORD_MIN_LENGTH . ' caractères']);
            return;
        }

        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'Les nouveaux mots de passe ne correspondent pas']);
            return;
        }

        if ($this->user->updatePassword($user['id'], $newPassword)) {
            echo json_encode(['success' => true, 'message' => 'Mot de passe mis à jour avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
        }
    }
}
