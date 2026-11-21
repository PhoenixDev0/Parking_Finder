<?php
/**
 * Proxy HTTP autonome vers les APIs de données temps réel (Metz, Londres).
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$city = $_GET['city'] ?? '';

// Liste blanche des services autorisés
$apis = [
    'metz' => 'https://data.metz.fr/api/records/1.0/search/?dataset=stationnement-en-ouvrage-temps-reel&rows=100',
    'london' => 'https://api.tfl.gov.uk/Occupancy/CarPark'
];

if (!isset($apis[$city])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid city', 'available_cities' => array_keys($apis)]);
    exit;
}

$url = $apis[$city];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
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
    http_response_code(500);
    echo json_encode(['error' => 'API request failed', 'details' => $error]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode(['error' => 'API returned error', 'http_code' => $httpCode]);
    exit;
}

echo $response;
