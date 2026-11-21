<?php
/**
 * Contrôleur gérant les points de terminaison de l'API REST interne,
 * les proxys vers les services tiers, le cache fichier et les exports.
 */
class ApiController {
    private $favorite;

    public function __construct() {
        $this->favorite = new Favorite();
    }

    /**
     * Point d'entrée par défaut de l'API.
     */
    public function index(): void {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Action non spécifiée']);
    }

    /**
     * Ajoute un parking aux favoris de l'utilisateur connecté.
     */
    public function addFavorite(): void {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        $user = getCurrentUser();
        if (!$user || !empty($user['is_guest'])) {
            echo json_encode(['success' => false, 'message' => 'Connectez-vous pour ajouter des favoris']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['csrf_token']) || !validateCSRFToken($data['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
            return;
        }

        $parkingId = $data['parking_id'] ?? '';
        $name = $data['name'] ?? 'Parking';
        $lat = floatval($data['lat'] ?? 0);
        $lng = floatval($data['lng'] ?? 0);

        if (empty($parkingId) || $lat == 0 || $lng == 0) {
            echo json_encode(['success' => false, 'message' => 'Données de parking invalides']);
            return;
        }

        if ($this->favorite->add($user['id'], $parkingId, $name, $lat, $lng)) {
            echo json_encode(['success' => true, 'message' => 'Parking ajouté aux favoris']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ce parking est déjà dans vos favoris']);
        }
    }

    /**
     * Supprime un parking des favoris de l'utilisateur.
     */
    public function removeFavorite(): void {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }

        $user = getCurrentUser();
        if (!$user || !empty($user['is_guest'])) {
            echo json_encode(['success' => false, 'message' => 'Action non autorisée']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['csrf_token']) || !validateCSRFToken($data['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
            return;
        }

        $parkingId = $data['parking_id'] ?? '';

        if ($this->favorite->remove($user['id'], $parkingId)) {
            echo json_encode(['success' => true, 'message' => 'Parking retiré des favoris']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
        }
    }

    /**
     * Retourne la liste des favoris de l'utilisateur connecté en JSON.
     */
    public function getFavorites(): void {
        header('Content-Type: application/json');
        
        $user = getCurrentUser();
        if (!$user || !empty($user['is_guest'])) {
            echo json_encode(['success' => true, 'favorites' => []]);
            return;
        }

        $favorites = $this->favorite->getByUserId($user['id']);
        echo json_encode(['success' => true, 'favorites' => $favorites]);
    }

    /**
     * Vérifie si un parking spécifique fait partie des favoris de l'utilisateur.
     */
    public function isFavorite(): void {
        header('Content-Type: application/json');
        
        $user = getCurrentUser();
        if (!$user || !empty($user['is_guest'])) {
            echo json_encode(['success' => true, 'isFavorite' => false]);
            return;
        }

        $parkingId = $_GET['parking_id'] ?? '';
        $isFav = $this->favorite->isFavorite($user['id'], $parkingId);
        echo json_encode(['success' => true, 'isFavorite' => $isFav]);
    }
    
    /**
     * Proxy HTTP vers les APIs temps réel des collectivités (Metz, Londres).
     * Évite les blocages CORS et sécurise les requêtes sortantes.
     */
    public function parkingProxy(): void {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        
        $city = $_GET['city'] ?? '';
        
        // Liste blanche des APIs autorisées (protection contre les failles SSRF)
        $apis = [
            'metz' => 'https://maps.eurometropolemetz.eu/ows?service=WFS&version=2.0.0&request=GetFeature&typeName=public:pub_tsp_sta&srsName=EPSG:4326&outputFormat=json',
            'london' => 'https://api.tfl.gov.uk/Occupancy/CarPark'
        ];
        
        if (!isset($apis[$city])) {
            echo json_encode(['error' => 'Invalid city', 'available' => array_keys($apis)]);
            return;
        }
        
        $url = $apis[$city];
        
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
        
        if ($error) {
            echo json_encode(['error' => 'API request failed', 'details' => $error]);
            return;
        }
        
        if ($httpCode !== 200) {
            echo json_encode(['error' => 'API error', 'http_code' => $httpCode]);
            return;
        }
        
        echo $response;
    }
    
    /**
     * Proxy vers l'API Overpass (OpenStreetMap) avec système de cache disque (TTL de 10 minutes).
     */
    public function overpassProxy(): void {
        header('Content-Type: application/json');
        
        $lat = floatval($_GET['lat'] ?? 0);
        $lng = floatval($_GET['lng'] ?? 0);
        $radius = intval($_GET['radius'] ?? 1000);
        
        if ($lat == 0 || $lng == 0) {
            echo json_encode(['error' => 'Invalid coordinates']);
            return;
        }
        
        // Clé de cache indexée sur les coordonnées arrondies et le rayon
        $cacheLat = round($lat, 2);
        $cacheLng = round($lng, 2);
        $cacheKey = "overpass_{$cacheLat}_{$cacheLng}_{$radius}";
        $cacheFile = __DIR__ . '/../cache/' . $cacheKey . '.json';
        $cacheTTL = 600; // Durée de validité du cache : 10 minutes
        
        // Utilisation du cache existant si non expiré
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
            echo file_get_contents($cacheFile);
            return;
        }
        
        // Requête Overpass QL optimisée (nœuds et surfaces uniquement)
        $query = "[out:json][timeout:35];
            (
                node[\"amenity\"=\"parking\"](around:{$radius},{$lat},{$lng});
                way[\"amenity\"=\"parking\"](around:{$radius},{$lat},{$lng});
            );
            out center;";
        
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
        
        // Mécanisme de secours : en cas d'erreur de l'API distante, on tente d'exploiter un ancien cache
        if ($error || $httpCode !== 200) {
            if (file_exists($cacheFile)) {
                echo file_get_contents($cacheFile);
                return;
            }
            echo json_encode(['elements' => [], 'error' => 'Overpass API unavailable']);
            return;
        }
        
        // Validation de la réponse JSON avant mise en cache
        $decoded = json_decode($response, true);
        if ($decoded === null) {
            if (file_exists($cacheFile)) {
                echo file_get_contents($cacheFile);
                return;
            }
            echo json_encode(['elements' => [], 'error' => 'Invalid API response']);
            return;
        }
        
        // Enregistrement du résultat en cache
        file_put_contents($cacheFile, $response);
        
        echo $response;
    }
    
    /**
     * Traite le formulaire de contact utilisateur.
     */
    public function sendContact(): void {
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
        
        $subject = trim($data['subject'] ?? '');
        $message = trim($data['message'] ?? '');
        
        if (empty($subject) || strlen($subject) < 3) {
            echo json_encode(['success' => false, 'message' => 'Le sujet doit contenir au moins 3 caractères']);
            return;
        }
        
        if (empty($message) || strlen($message) < 10) {
            echo json_encode(['success' => false, 'message' => 'Le message doit contenir au moins 10 caractères']);
            return;
        }
        
        $user = getCurrentUser();
        $userEmail = $user && !empty($user['email']) ? $user['email'] : 'Invité (non connecté)';
        $userId = $user && !empty($user['id']) ? $user['id'] : 'N/A';
        
        $to = defined('CONTACT_EMAIL') ? CONTACT_EMAIL : 'contact@parking-finder.local';
        $emailSubject = '[Parking Finder] ' . $subject;
        $emailBody = "Nouveau message de contact:\n\n";
        $emailBody .= "De: $userEmail (ID: $userId)\n";
        $emailBody .= "Sujet: $subject\n";
        $emailBody .= "Date: " . date('d/m/Y H:i:s') . "\n\n";
        $emailBody .= "Message:\n";
        $emailBody .= "----------------------------------------\n";
        $emailBody .= $message . "\n";
        $emailBody .= "----------------------------------------\n";
        
        $headers = "From: noreply@parking-finder.com\r\n";
        $headers .= "Reply-To: $userEmail\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        @mail($to, $emailSubject, $emailBody, $headers);
        
        // Journalisation locale du message
        $logFile = __DIR__ . '/../cache/contact_log.txt';
        $logEntry = date('Y-m-d H:i:s') . " | $userEmail | $subject\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.'
        ]);
    }
    
    /**
     * Exporte les favoris de l'utilisateur au format CSV ou JSON.
     */
    public function exportFavorites(): void {
        $user = getCurrentUser();
        if (!$user || !empty($user['is_guest'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Connectez-vous pour exporter vos favoris']);
            return;
        }
        
        $format = strtolower($_GET['format'] ?? 'json');
        
        $favorite = new Favorite();
        $favorites = $favorite->getByUserId($user['id']);
        
        if (empty($favorites)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Aucun favori à exporter']);
            return;
        }
        
        $filename = 'parking_finder_favoris_' . date('Y-m-d');
        
        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // Insertion du BOM UTF-8 pour la compatibilité avec Microsoft Excel
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($output, ['ID Parking', 'Nom', 'Latitude', 'Longitude', 'Lien Google Maps', 'Date d\'ajout']);
            
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
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.json"');
            
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
            
            echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }
}
