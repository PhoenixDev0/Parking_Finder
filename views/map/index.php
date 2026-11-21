<?php
/**
 *Vue de la carte interactive
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Parking Finder - Carte interactive pour trouver votre parking">
    <meta name="theme-color" content="#1a1a2e">
    <title>Carte - Parking Finder</title>
    
    <!-- Importation du CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Importation de Leaflet (pour la carte) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    
    <!-- Importation de Leaflet MarkerCluster CSS (pour ajouter un cluster : amélioration de l'ergonomie pour l'utilisateur) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css">
</head>
<body class="map-page">
    <!-- Bannière d'erreur de localisation -->
    <div class="location-banner hidden" id="locationBanner">
        <div class="banner-content">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
            </svg>
            <span id="locationMessage">Localisation impossible</span>
        </div>
        <button class="banner-btn" id="retryLocation">Réessayer</button>
    </div>

    <!-- Barre de recherche -->
    <div class="search-container">
        <div class="search-wrapper">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" class="search-icon">
                <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
            </svg>
            <input type="text" id="searchInput" placeholder="Rechercher un parking..." autocomplete="off">
            <button class="search-clear hidden" id="searchClear">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </button>
        </div>
        <div class="search-results hidden" id="searchResults"></div>
    </div>

    <!-- Carte -->
    <div id="map"></div>

    <!-- Contrôles de la carte -->
    <div class="map-controls">
        <!-- Bouton de changement de thème (clair/sombre) -->
        <button class="theme-toggle" id="themeToggle" title="Changer le thème">
            <svg class="sun-icon" viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                <path d="M6.76 4.84l-1.8-1.79-1.41 1.41 1.79 1.79 1.42-1.41zM4 10.5H1v2h3v-2zm9-9.95h-2V3.5h2V.55zm7.45 3.91l-1.41-1.41-1.79 1.79 1.41 1.41 1.79-1.79zm-3.21 13.7l1.79 1.8 1.41-1.41-1.8-1.79-1.4 1.4zM20 10.5v2h3v-2h-3zm-8-5c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm-1 16.95h2V19.5h-2v2.95zm-7.45-3.91l1.41 1.41 1.79-1.8-1.41-1.41-1.79 1.8z"/>
            </svg>
            <svg class="moon-icon" viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                <path d="M9 2c-1.05 0-2.05.16-3 .46 4.06 1.27 7 5.06 7 9.54 0 4.48-2.94 8.27-7 9.54.95.3 1.95.46 3 .46 5.52 0 10-4.48 10-10S14.52 2 9 2z"/>
            </svg>
        </button>
        
        <!-- Bouton de filtre -->
        <button class="control-btn" id="filterBtn" title="Filtres">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                <path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z"/>
            </svg>
        </button>
        
        <!-- Bouton de recentrage sur la position utilisateur -->
        <button class="control-btn" id="recenterBtn" title="Recentrer sur ma position">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                <path d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm8.94 3c-.46-4.17-3.77-7.48-7.94-7.94V1h-2v2.06C6.83 3.52 3.52 6.83 3.06 11H1v2h2.06c.46 4.17 3.77 7.48 7.94 7.94V23h2v-2.06c4.17-.46 7.48-3.77 7.94-7.94H23v-2h-2.06zM12 19c-3.87 0-7-3.13-7-7s3.13-7 7-7 7 3.13 7 7-3.13 7-7 7z"/>
            </svg>
        </button>
        
        <!-- Bouton pour activer/désactiver la vue satellite -->
        <button class="control-btn" id="satelliteBtn" onclick="toggleMapStyle()" title="Vue satellite">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
            </svg>
        </button>
    </div>

    <!-- Bouton de test de localisation (à supprimer pour publier l'app) -->
    <div class="location-test-buttons">
        <span class="test-label">Test:</span>
        <button class="test-btn" id="testMetz" title="Simuler Metz">Metz</button>
        <button class="test-btn" id="testLondon" title="Simuler Londres">London</button>
        <button class="test-btn active" id="testReal" title="Position réelle">Réelle</button>
    </div>

    <!-- Widget parking le plus proche -->
    <div class="nearest-widget" id="nearestWidget">
        <button class="widget-toggle" id="widgetToggle" title="Parking le plus proche">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                <path d="M13 3H6v18h4v-6h3c3.31 0 6-2.69 6-6s-2.69-6-6-6zm.2 8H10V7h3.2c1.1 0 2 .9 2 2s-.9 2-2 2z"/>
            </svg>
            <span class="widget-badge" id="widgetBadge">--</span>
        </button>
        <div class="widget-panel hidden" id="widgetPanel">
            <div class="widget-header">
                <span class="widget-title">Parking le plus proche</span>
                <button class="widget-close" id="widgetClose">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </button>
            </div>
            <div class="widget-body" id="widgetBody">
                <div class="widget-loading">
                    <span>Recherche en cours...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bouton de navigation -->
    <button class="navigate-btn" id="navigateBtn">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
            <path d="M21.71 11.29l-9-9c-.39-.39-1.02-.39-1.41 0l-9 9c-.39.39-.39 1.02 0 1.41l9 9c.39.39 1.02.39 1.41 0l9-9c.39-.38.39-1.01 0-1.41zM14 14.5V12h-4v3H8v-4c0-.55.45-1 1-1h5V7.5l3.5 3.5-3.5 3.5z"/>
        </svg>
        <span>Guidage</span>
    </button>

    <!-- Modale de filtre -->
    <div class="modal hidden" id="filterModal">
        <div class="modal-backdrop"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>Filtres</h2>
                <button class="modal-close" id="closeFilterModal">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <!-- Filtre de rayon de chargement des parkings (entre 1 et 15 km) -->
                <div class="filter-group">
                    <label>Rayon de recherche: <span id="radiusValue">1</span> km</label>
                    <input type="range" id="radiusFilter" min="1" max="15" value="1" class="slider">
                </div>

                <!-- Filtre de budget horaire (entre 0 et 20 €) -->
                <div class="filter-group">
                    <label>Budget horaire: <span id="budgetValue">Tout</span></label>
                    <input type="range" id="budgetFilter" min="0" max="20" value="0" class="slider">
                </div>

                <!-- Filtre de motorisation -->
                <div class="filter-group">
                    <label>Motorisation</label>
                    <select id="motorFilter" class="select">
                        <option value="all">Tous</option>
                        <option value="thermal">Thermique</option>
                        <option value="hybrid">Hybride</option>
                        <option value="electric">Électrique</option>
                    </select>
                </div>

                <!-- Filtre de type de places -->
                <div class="filter-group">
                    <label>Type de places</label>
                    <select id="placeFilter" class="select">
                        <option value="all">Tout</option>
                        <option value="pmr">PMR (Handicapé)</option>
                        <option value="family">Familiale</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelFilters">Annuler</button>
                <button class="btn btn-primary" id="applyFilters">Appliquer</button>
            </div>
        </div>
    </div>

    <!-- Panel de navigation (mode guidage) -->
    <div class="navigation-panel hidden" id="navigationPanel">
        <div class="nav-header">
            <div class="nav-info">
                <div class="nav-duration">
                    <span id="navDuration">--</span>
                    <small>min</small>
                </div>
                <div class="nav-eta">
                    Arrivée: <span id="navEta">--:--</span>
                </div>
            </div>
            <div class="nav-actions">
                <button class="btn btn-icon active" id="voiceBtn" onclick="toggleVoice()" title="Désactiver la voix">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                        <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
                    </svg>
                </button>
                <button class="btn btn-danger" id="stopNavigation">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                        <path d="M6 6h12v12H6z"/>
                    </svg>
                    Arrêter
                </button>
            </div>
        </div>
        <div class="nav-instruction">
            <div class="instruction-icon" id="instructionIcon">
                <svg viewBox="0 0 24 24" width="32" height="32" fill="currentColor">
                    <path d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71z"/>
                </svg>
            </div>
            <div class="instruction-text">
                <span id="instructionText">En attente...</span>
                <small id="instructionDistance">--</small>
            </div>
        </div>
    </div>

    <!-- Barre de navigation en bas de page -->
    <nav class="bottom-nav" id="bottomNav">
        <a href="?page=map" class="nav-item active">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                <path d="M20.5 3l-.16.03L15 5.1 9 3 3.36 4.9c-.21.07-.36.25-.36.48V20.5c0 .28.22.5.5.5l.16-.03L9 18.9l6 2.1 5.64-1.9c.21-.07.36-.25.36-.48V3.5c0-.28-.22-.5-.5-.5zM15 19l-6-2.11V5l6 2.11V19z"/>
            </svg>
            <span>Carte</span>
        </a>
        <a href="?page=profile" class="nav-item">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
            <span>Profil</span>
        </a>
    </nav>

    <!-- Données cachées -->
    <input type="hidden" id="csrfToken" value="<?php echo $csrfToken; ?>">
    <input type="hidden" id="appUrl" value="<?php echo APP_URL; ?>">
    <input type="hidden" id="userFavorites" value='<?php echo json_encode(array_column($favorites, "parking_id")); ?>'>
    <input type="hidden" id="isGuest" value="<?php echo isset($user['is_guest']) && $user['is_guest'] ? '1' : '0'; ?>">

    <!-- Scripts utilisés -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script src="assets/js/map.js"></script>
</body>
</html>
