<?php
//1. Configuration des en-têtes HTTP :

//Définition du contenu de la réponse en JSON
header('Content-Type: application/json');
//Autorisation de la requête pour toutes les origines
header('Access-Control-Allow-Origin: *');



//2. Récupération et validation des entrées :

//Récupération de la ville demandée via méthode GET
$city = $_GET['city'] ?? '';

// Vérification de la ville demandée (pour empêcher les attaques SSRF)
$apis = [
    'metz' => 'https://data.metz.fr/api/records/1.0/search/?dataset=stationnement-en-ouvrage-temps-reel&rows=100',
    'london' => 'https://api.tfl.gov.uk/Occupancy/CarPark'
];

//Vérification de l'existence de la ville demandée dans la liste
if (!isset($apis[$city])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid city', 'available_cities' => array_keys($apis)]);
    exit;
}



//3. Initialisation de la requête (cURL) :

// Sélection de l'URL de l'API correspondante
$url = $apis[$city];

// Configuration de la requête cURL
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url, //L'URL cible
    CURLOPT_RETURNTRANSFER => true, //Retourne la réponse sous forme de chaines de caractères
    CURLOPT_TIMEOUT => 10, //Timeout de la requête
    CURLOPT_FOLLOWLOCATION => true, //Suivre les redirections HTTP
    CURLOPT_SSL_VERIFYPEER => false, //Désactive la vérification SSL (à mettre sur true pour une app à publier / risque d'attaque MITM)
    //Ajout des en-têtes HTTP
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'User-Agent: ParkingFinder/1.0'
    ]
]);



//4. Exécution de la requête et gestion des erreurs :

$response = curl_exec($ch); //Exécution de la requête
//Récupération des données de la requête
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch); //Fermeture de la session cURL

// Cas 1 : Erreur réseau
if ($error) {
    http_response_code(500);
    echo json_encode(['error' => 'API request failed', 'details' => $error]);
    exit;
}

// Cas 2 : Erreur HTTP
if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode(['error' => 'API returned error', 'http_code' => $httpCode]);
    exit;
}

// Si tout est ok, on envoie la réponse à l'API
echo $response;
