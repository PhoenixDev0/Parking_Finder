<?php
class ApiController {
    private $favorite;

    public function __construct() {
        $this->favorite = new Favorite();
    }

    //Point d'entrée de l'API par défaut
    public function index(): void {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Action non spécifiée']);
    }

    //Ajout d'un parking aux favoris
    public function addFavorite(): void {
        header('Content-Type: application/json');
        
        //Vérification de la méthode : POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        //Vérification de l'utilisateur
        $user = getCurrentUser();
        if (!$user || !empty($user['is_guest'])) {
            echo json_encode(['success' => false, 'message' => 'Connectez-vous pour ajouter des favoris']);
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
        $parkingId = $data['parking_id'] ?? '';
        $name = $data['name'] ?? 'Parking';
        $lat = floatval($data['lat'] ?? 0);
        $lng = floatval($data['lng'] ?? 0);

        //Validation des données géographiques
        if (empty($parkingId) || $lat == 0 || $lng == 0) {
            echo json_encode(['success' => false, 'message' => 'Données de parking invalides']);
            return;
        }

        //Ajout du parking aux favoris
        if ($this->favorite->add($user['id'], $parkingId, $name, $lat, $lng)) {
            echo json_encode(['success' => true, 'message' => 'Parking ajouté aux favoris']);
        } else {
            //en cas de doublon
            echo json_encode(['success' => false, 'message' => 'Ce parking est déjà dans vos favoris']);
        }
    }

    //Suppression d'un parking des favoris
    public function removeFavorite(): void {
        header('Content-Type: application/json');
        
        //Vérification de la méthode : POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        //Vérification de l'utilisateur
        $user = getCurrentUser();
        if (!$user || !empty($user['is_guest'])) {
            echo json_encode(['success' => false, 'message' => 'Action non autorisée']);
            return;
        }

        //Récupération des données
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validation du token CSRF
        if (!isset($data['csrf_token']) || !validateCSRFToken($data['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
            return;
        }

        //Récupération de l'identifiant du parking
        $parkingId = $data['parking_id'] ?? '';

        //Suppression du parking des favoris
        if ($this->favorite->remove($user['id'], $parkingId)) {
            echo json_encode(['success' => true, 'message' => 'Parking retiré des favoris']);
        } else {
            //en cas d'erreur
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
        }
    }

    //Récupération des favoris
    public function getFavorites(): void {
        header('Content-Type: application/json');
        
        //Vérification de l'utilisateur
        $user = getCurrentUser();
        if (!$user || !empty($user['is_guest'])) {
            echo json_encode(['success' => true, 'favorites' => []]);
            return;
        }

        //Récupération des favoris
        $favorites = $this->favorite->getByUserId($user['id']);
        echo json_encode(['success' => true, 'favorites' => $favorites]);
    }

    //Vérification si un parking est dans les favoris
    public function isFavorite(): void {
        header('Content-Type: application/json');
        
        //Vérification de l'utilisateur
        $user = getCurrentUser();
        if (!$user || !empty($user['is_guest'])) {
            echo json_encode(['success' => true, 'isFavorite' => false]);
            return;
        }

        //Récupération de l'identifiant du parking
        $parkingId = $_GET['parking_id'] ?? '';
        
        //Vérification si le parking est dans les favoris
        $isFav = $this->favorite->isFavorite($user['id'], $parkingId);
        echo json_encode(['success' => true, 'isFavorite' => $isFav]);
    }
    
    //Proxy pour les APIs externes (pour éviter les problèmes de CORS)
    public function parkingProxy(): void {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        
        $city = $_GET['city'] ?? '';
        
        //whitelist des villes configurées
        $apis = [
            'metz' => 'https://maps.eurometropolemetz.eu/ows?service=WFS&version=2.0.0&request=GetFeature&typeName=public:pub_tsp_sta&srsName=EPSG:4326&outputFormat=json',
            'london' => 'https://api.tfl.gov.uk/Occupancy/CarPark'
        ];
        
        if (!isset($apis[$city])) {
            echo json_encode(['error' => 'Invalid city', 'available' => array_keys($apis)]);
            return;
        }
        
        $url = $apis[$city];
        
        //Initialisation de la requête cURL
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: ParkingFinder/1.0'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        //Gestion des erreurs
        if ($error) {
            echo json_encode(['error' => 'API request failed', 'details' => $error]);
            return;
        }
        
        if ($httpCode !== 200) {
            echo json_encode(['error' => 'API error', 'http_code' => $httpCode]);
            return;
        }
        
        //Retour de la réponse de l'API
        echo $response;
    }
    
    //Proxy pour l'API Overpass avec mise en cache (pour augmenter la vitesse de l'app)
    public function overpassProxy(): void {
        header('Content-Type: application/json');
        
        $lat = floatval($_GET['lat'] ?? 0);
        $lng = floatval($_GET['lng'] ?? 0);
        $radius = intval($_GET['radius'] ?? 1000);
        
        if ($lat == 0 || $lng == 0) {
            echo json_encode(['error' => 'Invalid coordinates']);
            return;
        }
        
        //Clé de cache (arrondit à 2 décimales)
        $cacheLat = round($lat, 2);
        $cacheLng = round($lng, 2);
        $cacheKey = "overpass_{$cacheLat}_{$cacheLng}_{$radius}";
        $cacheFile = __DIR__ . '/../cache/' . $cacheKey . '.json';
        $cacheTTL = 600; //stocké dans le cache pendant 10 minutes
        
        //Vérification de la présence d'un fichier de cache
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
            //Lecture du fichier de cache
            echo file_get_contents($cacheFile);
            return;
        }
        
        //Construction de la requête Overpass (optimisée sans relation pour réduire la latence)
        $query = "[out:json][timeout:35];
            (
                node[\"amenity\"=\"parking\"](around:{$radius},{$lat},{$lng});
                way[\"amenity\"=\"parking\"](around:{$radius},{$lat},{$lng});
            );
            out center;";
        
        //Appel de l'API Overpass et exécution de la requête
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => defined('OVERPASS_API') ? OVERPASS_API : 'https://overpass-api.de/api/interpreter',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'data=' . urlencode($query),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: ParkingFinder/1.0'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        //Repli : Si l'API échue, on essaie de se servir du vieux cache
        if ($error || $httpCode !== 200) {
            if (file_exists($cacheFile)) {
                echo file_get_contents($cacheFile);
                return;
            }
            echo json_encode(['elements' => [], 'error' => 'Overpass API unavailable']);
            return;
        }
        
        //Validation de la réponse JSON
        $decoded = json_decode($response, true);
        if ($decoded === null) {
            if (file_exists($cacheFile)) {
                echo file_get_contents($cacheFile);
                return;
            }
            echo json_encode(['elements' => [], 'error' => 'Invalid API response']);
            return;
        }
        
        //Sauvegarde dans le cache
        file_put_contents($cacheFile, $response);
        
        echo $response;
    }
    
    //Gestion du formulaire de contact
    public function sendContact(): void {
        header('Content-Type: application/json');
        
        //Vérification de la méthode : POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        //Validation du token CSRF
        if (!isset($data['csrf_token']) || !validateCSRFToken($data['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
            return;
        }
        
        //Récupération des données
        $subject = trim($data['subject'] ?? '');
        $message = trim($data['message'] ?? '');
        
        //Validation des données
        if (empty($subject) || strlen($subject) < 3) {
            echo json_encode(['success' => false, 'message' => 'Le sujet doit contenir au moins 3 caractères']);
            return;
        }
        
        if (empty($message) || strlen($message) < 10) {
            echo json_encode(['success' => false, 'message' => 'Le message doit contenir au moins 10 caractères']);
            return;
        }
        
        //Récupération des informations de l'utilisateur
        $user = getCurrentUser();
        $userEmail = $user && !empty($user['email']) ? $user['email'] : 'Invité (non connecté)';
        $userId = $user && !empty($user['id']) ? $user['id'] : 'N/A';
        
        //Préparation du mail
        $to = defined('CONTACT_EMAIL') ? CONTACT_EMAIL : 'contact@parking-finder.local'; //Adresse mail de destination
        $emailSubject = '[Parking Finder] ' . $subject;
        $emailBody = "Nouveau message de contact:\n\n";
        $emailBody .= "De: $userEmail (ID: $userId)\n";
        $emailBody .= "Sujet: $subject\n";
        $emailBody .= "Date: " . date('d/m/Y H:i:s') . "\n\n";
        $emailBody .= "Message:\n";
        $emailBody .= "----------------------------------------\n";
        $emailBody .= $message . "\n";
        $emailBody .= "----------------------------------------\n";
        
        //En-tête du mail
        $headers = "From: noreply@parking-finder.com\r\n";
        $headers .= "Reply-To: $userEmail\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        //Envoi du mail
        $mailSent = @mail($to, $emailSubject, $emailBody, $headers);
        
        //Sauvegarde du message dans un fichier de log
        $logFile = __DIR__ . '/../cache/contact_log.txt';
        $logEntry = date('Y-m-d H:i:s') . " | $userEmail | $subject\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        
        //Retourne un message de confirmation
        echo json_encode([
            'success' => true, 
            'message' => 'Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.'
        ]);
    }
    
    //Gestion de l'export des favoris
    public function exportFavorites(): void {
        $user = getCurrentUser();
        //Vérification de l'authentification
        if (!$user || !empty($user['is_guest'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Connectez-vous pour exporter vos favoris']);
            return;
        }
        
        //Récupération du format
        $format = strtolower($_GET['format'] ?? 'json');
        
        //Récupération des favoris
        $favorite = new Favorite();
        $favorites = $favorite->getByUserId($user['id']);
        
        //Vérification de la présence de favoris
        if (empty($favorites)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Aucun favori à exporter']);
            return;
        }
        
        //Création du fichier
        $filename = 'parking_finder_favoris_' . date('Y-m-d');
        
        if ($format === 'csv') {
            //Configuration de l'export CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            //Gestion de l'encodage Excel
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            //En-tête du fichier
            fputcsv($output, ['ID Parking', 'Nom', 'Latitude', 'Longitude', 'Lien Google Maps', 'Date d\'ajout']);
            
            //Ajout des données
            foreach ($favorites as $fav) {
                $parkingName = $fav['parking_name'] ?? $fav['name'] ?? 'Parking';
                $googleMapsUrl = "https://www.google.com/maps/search/?api=1&query={$fav['latitude']},{$fav['longitude']}";
                fputcsv($output, [
                    $fav['parking_id'],
                    $parkingName,
                    $fav['latitude'],
                    $fav['longitude'],
                    $googleMapsUrl,
                    $fav['created_at']
                ]);
            }
            
            fclose($output);
        } else {
            //Configuration de l'export JSON
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.json"');
            
            //Structuration des données
            $exportData = [
                'export_date' => date('Y-m-d H:i:s'),
                'total_favorites' => count($favorites),
                'favorites' => array_map(function($fav) {
                    return [
                        'parking_id' => $fav['parking_id'],
                        'name' => $fav['parking_name'] ?? $fav['name'] ?? 'Parking',
                        'coordinates' => [
                            'latitude' => floatval($fav['latitude']),
                            'longitude' => floatval($fav['longitude'])
                        ],
                        'google_maps_url' => "https://www.google.com/maps/search/?api=1&query={$fav['latitude']},{$fav['longitude']}",
                        'added_at' => $fav['created_at']
                    ];
                }, $favorites)
            ];
            
            //Retourne les données
            echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }
}
