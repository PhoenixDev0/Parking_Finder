<?php
/**
 * Contrôleur gérant la vue principale de la carte interactive.
 */
class MapController {
    public function __construct() {
        if (!isLoggedIn()) {
            header('Location: ' . APP_URL . '?page=auth');
            exit;
        }
    }

    /**
     * Prépare les données de contexte et affiche l'interface cartographique.
     */
    public function index(): void {
        $user = getCurrentUser();
        $csrfToken = generateCSRFToken();
        
        $favorites = [];
        if ($user && !empty($user['id'])) {
            $favoriteModel = new Favorite();
            $favorites = $favoriteModel->getByUserId($user['id']);
        }
        
        require_once __DIR__ . '/../views/map/index.php';
    }
}
