<?php
class MapController {
    public function __construct() {
        //Vérification que l'utilisateur est connecté
        if (!isLoggedIn()) {
            header('Location: ' . APP_URL . '?page=auth');
            exit;
        }
    }

    //Affichage de la page de la carte
    public function index(): void {
        $user = getCurrentUser(); //Récupération de l'utilisateur connecté
        $csrfToken = generateCSRFToken(); //génération d'un token CSRF
        
        //Récupération des favoris de l'utilisateur
        $favorites = [];
        if ($user && !empty($user['id'])) {
            $favoriteModel = new Favorite();
            $favorites = $favoriteModel->getByUserId($user['id']);
        }
        
        require_once __DIR__ . '/../views/map/index.php';
    }
}
