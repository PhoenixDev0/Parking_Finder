// Variables globales
let map; // Instance de la carte (Singleton)
let userMarker; // Marqueur de la position GPS de l'utilisateur
let parkingMarkers; // Gestionnaire des clusters de marqueurs de parking
let routingControl = null; // Contrôle d'itinéraire
let userPosition = null; // Position GPS de l'utilisateur (latitude, longitude)
let parkingsData = []; // Données des parkings
let filteredParkings = []; // Données des parkings après filtrage
let inactivityTimer = null; // Timer d'inactivité pour rencentrage automatique
let watchId = null; // ID du processus de surveillance de localisation
let isNavigating = false; // Indique si une navigation est en cours
let currentTheme = localStorage.getItem('theme') || 'dark'; // Thème actuel (sombre, clair)
let currentMapStyle = localStorage.getItem('mapStyle') || 'standard'; // Style de la carte (standard, satellite)
let baseLayers = {};
let voiceEnabled = localStorage.getItem('voiceNav') !== 'false'; // Activer/desactiver la navigation vocale
let currentVoice = null; // Instance de la navigation vocale

// Configuration de l'application
const config = {
    // Position par defaut de la carte
    defaultLat: 48.8566,
    defaultLng: 2.3522,
    defaultZoom: 15,

    // Delai d'inactivité avant rencentrage automatique
    inactivityTimeout: 20000, // 20 secondes

    // APIs
    overpassApi: 'https://overpass-api.de/api/interpreter', // API Overpass (parkings)
    osrmApi: 'https://router.project-osrm.org/route/v1/driving', // API OSRM (itinéraire)

    // API spécifiques des villes (prioritaire sur Overpass)
    cityApis: {
        // Metz : Utilisation de l'API Eurométropole Metz
        metz: {
            name: 'Metz',
            bounds: { minLat: 49.04, maxLat: 49.17, minLng: 6.07, maxLng: 6.28 },
            apiUrl: 'https://maps.eurometropolemetz.eu/ows?service=WFS&version=2.0.0&request=GetFeature&typeName=public:pub_tsp_sta&srsName=EPSG:4326&outputFormat=json',
            enabled: true
        },
        // Londres : API TfL (désactivée - nécessite une clé d'authentification)
        london: {
            name: 'London',
            bounds: { minLat: 51.28, maxLat: 51.69, minLng: -0.51, maxLng: 0.33 },
            apiUrl: 'https://api.tfl.gov.uk/Occupancy/CarPark',
            enabled: false  // Désactivé : requiert app_id et app_key
        }
    }
};

// Valeurs initiales des filtres
let filters = {
    radius: 1000, // rayon de recherche en mètres
    budget: 0, // budget en euros (0 = tous)
    motor: 'all', // type de motorisation
    placeType: 'all' // type de place
};

// Récupération des données du DOM
const csrfToken = document.getElementById('csrfToken')?.value;
const appUrl = document.getElementById('appUrl')?.value;
const userFavorites = JSON.parse(document.getElementById('userFavorites')?.value || '[]');
const isGuest = document.getElementById('isGuest')?.value === '1';

// Initialisation de l'application
document.addEventListener('DOMContentLoaded', function () {
    initTheme(); // Initialisation du thème
    initMap(); // Initialisation de la carte (Leaflet)
    initEventListeners(); // Initialisation des événements
    requestGeolocation(); // Demande de permission GPS
    checkNavigationIntent(); // Vérification de l'intention de navigation
});

// Initialisation de la carte (Leaflet)
function initMap() {
    // Création de la carte centrée sur la localisation par defaut
    map = L.map('map', {
        center: [config.defaultLat, config.defaultLng],
        zoom: config.defaultZoom,
        zoomControl: false
    });

    // Définition des couches de base
    baseLayers = {
        // Couche de base sombre (CartoDB)
        dark: L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 19
        }),
        // Couche de base claire (CartoDB)
        light: L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 19
        }),
        // Couche de base satellite (ArcGIS)
        satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri &mdash; Source: Esri, Maxar, Earthstar Geographics',
            maxZoom: 19
        })
    };

    // Sélection de la couche de base en fonction des préférences persistantes
    if (currentMapStyle === 'satellite') {
        baseLayers.satellite.addTo(map);
    } else if (currentTheme === 'light') {
        baseLayers.light.addTo(map);
    } else {
        baseLayers.dark.addTo(map);
    }

    // Ajout du contrôleur de zoom en bas à gauche
    L.control.zoom({ position: 'bottomleft' }).addTo(map);


    // Initialisation du Clustering (optimisation des performances)
    parkingMarkers = L.markerClusterGroup({
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        iconCreateFunction: function (cluster) {
            const count = cluster.getChildCount();
            // Classe CSS dynamique en fonction du nombre de marqueurs
            return L.divIcon({
                html: '<div>' + count + '</div>',
                className: 'marker-cluster marker-cluster-' + (count < 10 ? 'small' : count < 50 ? 'medium' : 'large'),
                iconSize: L.point(40, 40)
            });
        }
    });
    map.addLayer(parkingMarkers);

    // Réinitialisation du timer d'inactivité lors des interactions avec la carte
    map.on('move', resetInactivityTimer);
    map.on('click', resetInactivityTimer);
}

// Initialisation des événements
function initEventListeners() {
    // Bouton de recentrage
    document.getElementById('recenterBtn')?.addEventListener('click', recenterMap);

    // Bouton de filtres
    document.getElementById('filterBtn')?.addEventListener('click', openFilterModal);
    document.getElementById('closeFilterModal')?.addEventListener('click', closeFilterModal);
    document.getElementById('cancelFilters')?.addEventListener('click', closeFilterModal);
    document.getElementById('applyFilters')?.addEventListener('click', applyFilters);

    // Fermeture du modal en cliquant sur le fond
    document.querySelector('.modal-backdrop')?.addEventListener('click', closeFilterModal);

    // Filtres
    document.getElementById('radiusFilter')?.addEventListener('input', updateRadiusLabel);
    document.getElementById('budgetFilter')?.addEventListener('input', updateBudgetLabel);

    // Bouton de navigation
    document.getElementById('navigateBtn')?.addEventListener('click', navigateToNearest);

    // Arrêt de la navigation
    document.getElementById('stopNavigation')?.addEventListener('click', stopNavigation);

    // Re tentative de localisation
    document.getElementById('retryLocation')?.addEventListener('click', requestGeolocation);

    // Recherche
    const searchInput = document.getElementById('searchInput');
    const searchClear = document.getElementById('searchClear');

    // Gestion de la recherche
    searchInput?.addEventListener('input', debounce(handleSearch, 300));
    searchInput?.addEventListener('focus', () => {
        if (searchInput.value) showSearchResults();
    });

    // Nettoyage de la recherche
    searchClear?.addEventListener('click', clearSearch);

    // Fermeture des résultats de recherche en cliquant en dehors
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-container')) {
            hideSearchResults();
        }
    });

    // Boutons de test de localisation
    document.getElementById('testMetz')?.addEventListener('click', () => simulateLocation('metz'));
    document.getElementById('testLondon')?.addEventListener('click', () => simulateLocation('london'));
    document.getElementById('testReal')?.addEventListener('click', () => simulateLocation('real'));

    // Widget parking le plus proche
    document.getElementById('widgetToggle')?.addEventListener('click', toggleNearestWidget);
    document.getElementById('widgetClose')?.addEventListener('click', closeNearestWidget);
}

// Demande de permission de localisation
function requestGeolocation() {
    const banner = document.getElementById('locationBanner');
    const message = document.getElementById('locationMessage');

    // Vérification de la support de la géolocalisation
    if (!navigator.geolocation) {
        showLocationError('Géolocalisation non supportée par votre navigateur');
        return;
    }

    // Masquer la bannière pendant le chargement
    banner.classList.add('hidden');

    // Obtenir la position initiale
    navigator.geolocation.getCurrentPosition(
        (position) => {
            handlePositionUpdate(position);
            startWatchingPosition();
        },
        (error) => {
            handleGeolocationError(error);
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}

// Démarrage de la surveillance de la position en temps réel
function startWatchingPosition() {
    if (watchId) navigator.geolocation.clearWatch(watchId);

    watchId = navigator.geolocation.watchPosition(
        handlePositionUpdate,
        handleGeolocationError,
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 5000 }
    );
}

// Mise à jour de la position
function handlePositionUpdate(position) {
    const { latitude, longitude } = position.coords;
    userPosition = { lat: latitude, lng: longitude };

    // Masquer la bannière d'erreur
    document.getElementById('locationBanner').classList.add('hidden');

    // Gestion du marqueur utilisateur
    if (userMarker) {
        userMarker.setLatLng([latitude, longitude]);
    } else {
        userMarker = L.marker([latitude, longitude], {
            icon: L.divIcon({
                className: 'user-marker',
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            })
        }).addTo(map);

        // Centrage de la carte sur la position utilisateur
        map.setView([latitude, longitude], config.defaultZoom);

        // Récupération des parkings
        fetchParkings();
    }

    // Démarrage du timer d'inactivité
    resetInactivityTimer();

    // Mise à jour de la navigation si active
    if (isNavigating) {
        updateNavigationInfo();
    }
}

// Gestion des erreurs de localisation
function handleGeolocationError(error) {
    let message = '';

    switch (error.code) {
        case error.PERMISSION_DENIED:
            message = 'Localisation impossible : Permission non accordée';
            break;
        case error.POSITION_UNAVAILABLE:
            message = 'Localisation impossible : Position non trouvée';
            setTimeout(requestGeolocation, 5000); // On retente la localisation
            break;
        case error.TIMEOUT:
            message = 'Localisation impossible : Délai dépassé';
            setTimeout(requestGeolocation, 3000); // On retente la localisation
            break;
        default:
            message = 'Localisation impossible : Erreur inconnue';
    }

    showLocationError(message);
}

// Affichage de la bannière d'erreur de localisation
function showLocationError(message) {
    const banner = document.getElementById('locationBanner');
    const messageEl = document.getElementById('locationMessage');

    messageEl.textContent = message;
    banner.classList.remove('hidden');
}

// Récupération des parkings
async function fetchParkings() {
    if (!userPosition) return;

    const { lat, lng } = userPosition;
    const radius = filters.radius;

    // On regarde si l'utilisateur est dans une ville avec une API spécifique
    const city = detectCity(lat, lng);

    if (city) {
        // Si oui, on récupère les parkings de la ville via l'API spécifique locale
        if (city.id === 'metz') {
            parkingsData = await fetchMetzParkings();
        } else if (city.id === 'london') {
            parkingsData = await fetchLondonParkings();
        }

        // On récupère aussi les données via l'API Overpass pour compléter
        const overpassData = await fetchOverpassParkings(lat, lng, radius);
        // On fusionne les données
        parkingsData = [...parkingsData, ...overpassData];
    } else {
        // Si non, on récupère les parkings via l'API Overpass
        parkingsData = await fetchOverpassParkings(lat, lng, radius);
    }

    applyFiltersToData();
}

// Récupération des parkings via l'API Overpass via le proxy
async function fetchOverpassParkings(lat, lng, radius) {
    try {
        // On récupère les parkings via le proxy
        const proxyUrl = `${appUrl || ''}?page=api&action=overpassProxy&lat=${lat}&lng=${lng}&radius=${radius}`;
        const response = await fetch(proxyUrl);
        const data = await response.json();

        if (data.error) {
            console.error('Overpass proxy error:', data.error);
            return [];
        }

        return processOverpassData(data.elements || []);

    } catch (error) {
        console.error('Error fetching parkings from Overpass:', error);
        return [];
    }
}

// Traitement des données de l'API Overpass
function processOverpassData(elements) {
    return elements.map((el) => {
        // On récupère les coordonnées
        let lat, lng;
        if (el.type === 'node') {
            lat = el.lat;
            lng = el.lon;
        } else if (el.center) {
            lat = el.center.lat;
            lng = el.center.lon;
        } else {
            return null;
        }

        const tags = el.tags || {};

        // On récupère la capacité
        const capacity = tags.capacity ? parseInt(tags.capacity) : null;

        // On récupère l'heure d'ouverture
        let status = 'open';
        let statusText = 'Statut inconnu';

        if (tags.opening_hours) {
            if (tags.opening_hours.toLowerCase().includes('24/7')) {
                status = 'open';
                statusText = 'Ouvert 24h/24';
            } else {
                statusText = tags.opening_hours;
            }
        }

        // On récupère le prix
        let hourlyRate = null;
        let hourlyRateText = 'Non disponible';
        let isFree = null;
        let isPaid = null;

        // On vérifie le tag fee
        if (tags.fee === 'no' || tags.fee === 'none') {
            isFree = true;
            isPaid = false;
            hourlyRate = 0;
            hourlyRateText = 'Gratuit';
        } else if (tags.fee === 'yes') {
            isFree = false;
            isPaid = true;
            hourlyRateText = 'Payant';
        }

        // On vérifie le tag charge pour le prix
        if (tags.charge) {
            const chargeStr = tags.charge.toLowerCase();
            const priceMatch = chargeStr.match(/(\d+(?:[.,]\d+)?)/);
            if (priceMatch) {
                hourlyRate = parseFloat(priceMatch[1].replace(',', '.'));

                // On détermine le prix par heure
                if (chargeStr.includes('hour') || chargeStr.includes('/h') || chargeStr.includes('per hour')) {
                    hourlyRateText = hourlyRate.toFixed(2) + '€/h';
                } else if (chargeStr.includes('day') || chargeStr.includes('/day')) {
                    hourlyRateText = hourlyRate.toFixed(2) + '€/jour';
                } else if (chargeStr.includes('minute') || chargeStr.includes('/min')) {
                    hourlyRateText = (hourlyRate * 60).toFixed(2) + '€/h';
                    hourlyRate = hourlyRate * 60;
                } else {
                    hourlyRateText = hourlyRate.toFixed(2) + '€';
                }
                isPaid = true;
                isFree = false;
            }
        }

        // On vérifie le tag parking:fee (alternative)
        if (hourlyRate === null && tags['parking:fee']) {
            if (tags['parking:fee'] === 'no') {
                isFree = true;
                isPaid = false;
                hourlyRate = 0;
                hourlyRateText = 'Gratuit';
            } else if (tags['parking:fee'] === 'yes') {
                isPaid = true;
                isFree = false;
                hourlyRateText = 'Payant';
            }
        }

        // Détection des équipements (type de place disponible)
        const hasPMR = tags.wheelchair === 'yes' || tags['capacity:disabled'] ? true : null;
        const hasEV = tags['socket:type2'] || tags['capacity:charging'] ? true : null;
        const hasFamily = tags['capacity:parent'] ? true : null;

        // On retourne les données
        return {
            id: el.id.toString(),
            name: tags.name || 'Parking',
            lat,
            lng,
            capacity: capacity,
            capacityText: capacity !== null ? capacity + ' places' : 'Non disponible',
            occupancy: null, // Pas de données en temps réel sur Overpass
            available: null,
            availableText: 'Non disponible',
            status: status,
            statusText: statusText,
            openingHours: tags.opening_hours || 'Non disponible',
            hourlyRate: hourlyRate,
            hourlyRateText: hourlyRateText,
            isFree: isFree,
            isPaid: isPaid,
            hasPMR: hasPMR,
            hasEV: hasEV,
            hasFamily: hasFamily,
            operator: tags.operator || null,
            surface: tags.surface || null,
            access: tags.access || 'yes',
            dataSource: 'OpenStreetMap',
            realTimeData: false // Drapeau indiquant que les données ne sont pas en temps réel
        };
    }).filter(p => p !== null);
}

// Filtrage des données
function applyFiltersToData() {
    filteredParkings = parkingsData.filter(parking => {
        // Filtrage par distance (rayon)
        if (userPosition) {
            const distance = getDistance(
                userPosition.lat, userPosition.lng,
                parking.lat, parking.lng
            );
            if (distance > filters.radius) {
                return false;
            }
        }

        // Filtrage du budget
        if (filters.budget > 0 && parking.hourlyRate > filters.budget) {
            return false;
        }

        // Filtrage du type de véhicule
        if (filters.motor === 'electric' && !parking.hasEV) {
            return false;
        }

        // Filtrage du type de place
        if (filters.placeType === 'pmr' && !parking.hasPMR) {
            return false;
        }
        if (filters.placeType === 'family' && !parking.hasFamily) {
            return false;
        }

        return true;
    });

    updateMarkers();
    refreshNearestWidget();
}

// Mise à jour des marqueurs en fonction des données filtrées
function updateMarkers() {
    parkingMarkers.clearLayers();

    filteredParkings.forEach(parking => {
        const marker = createParkingMarker(parking);
        parkingMarkers.addLayer(marker);
    });
}

// Création d'un marqueur Leaflet avec icône personnalisée et popup
function createParkingMarker(parking) {
    // Determination de la couleur du marqueur
    let colorClass = 'marker-gray'; // Par défaut: statut inconnu (navigable)

    if (parking.realTimeData) {
        // Données en temps réel disponibles, on utilise le statut réel
        if (parking.status === 'closed' || (parking.available !== null && parking.available <= 0)) {
            colorClass = 'marker-red'; // Statut fermé ou sans places disponibles
        } else if (parking.status === 'closing' || (parking.available !== null && parking.available < parking.capacity * 0.1)) {
            colorClass = 'marker-orange'; // Statut en fermeture ou avec moins de 10% de places disponibles
        } else if (parking.status === 'open') {
            colorClass = 'marker-green'; // Statut ouvert
        }
    } else {
        // Données en temps réel non disponibles, on utilise le statut disponible
        if (parking.status === 'closed') {
            colorClass = 'marker-red';
        } else if (parking.status === 'open') {
            colorClass = 'marker-green';
        }
    }

    // Création de l'icône SVG
    const icon = L.divIcon({
        className: 'custom-marker ' + colorClass,
        html: `<svg viewBox="0 0 24 24" width="20" height="20" fill="white">
            <path d="M13 3H6v18h4v-6h3c3.31 0 6-2.69 6-6s-2.69-6-6-6zm.2 8H10V7h3.2c1.1 0 2 .9 2 2s-.9 2-2 2z"/>
        </svg>`,
        iconSize: [44, 52],
        iconAnchor: [22, 52],
        popupAnchor: [0, -52]
    });

    const marker = L.marker([parking.lat, parking.lng], { icon });

    // Injection du contenu de la popup
    const isFavorite = userFavorites.includes(parking.id);
    const popupContent = createPopupContent(parking, isFavorite);

    marker.bindPopup(popupContent, {
        maxWidth: 300,
        className: 'parking-popup-wrapper'
    });

    // Ajout des données du parking au marqueur
    marker.parkingData = parking;

    return marker;
}

// Création du contenu de la popup
function createPopupContent(parking, isFavorite) {
    const statusClass = parking.status === 'open' ? 'open' :
        parking.status === 'closing' ? 'closing' : 'closed';

    // Badges des équipements
    let featuresHtml = '';
    if (parking.hasPMR === true) {
        featuresHtml += '<span class="parking-feature">PMR</span>';
    }
    if (parking.hasEV === true) {
        featuresHtml += '<span class="parking-feature">Recharge</span>';
    }
    if (parking.hasFamily === true) {
        featuresHtml += '<span class="parking-feature">Familiale</span>';
    }

    // Affichage de l'occupation
    let availabilityText = 'Données non disponibles';

    if (parking.available !== null && parking.capacity !== null && parking.capacity > 0) {
        // Données en temps réel disponibles
        const fillPercent = Math.round(((parking.capacity - parking.available) / parking.capacity) * 100);
        availabilityText = `${parking.available}/${parking.capacity} places (${fillPercent}% rempli)`;
    } else if (parking.capacity !== null && parking.capacity > 0) {
        // Données en temps réel indisponibles
        availabilityText = `?/${parking.capacity} places (temps réel indisponible)`;
    } else if (parking.capacityText && parking.capacityText !== 'Non disponible') {
        // Texte de capacité sans nombre
        availabilityText = parking.capacityText + ' (temps réel indisponible)';
    }

    // Affichage du tarif horaire
    const rateText = parking.hourlyRateText ||
        (parking.hourlyRate === 0 ? 'Gratuit' :
            (parking.hourlyRate !== null ? parking.hourlyRate + '€/h' : 'Non disponible'));

    // Badge de la source des données
    const dataBadge = parking.realTimeData
        ? '<span class="data-badge live">Temps réel</span>'
        : '<span class="data-badge static">OSM</span>';

    // Affichage de la popup
    return `
        <div class="parking-popup">
            <div class="parking-popup-header">
                <h3>${escapeHtml(parking.name)}</h3>
                <span class="parking-status ${statusClass}">${parking.statusText}</span>
            </div>
            
            <div class="parking-popup-info">
                <div class="parking-info-row">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14l-5-5 1.41-1.41L12 14.17l4.59-4.58L18 11l-6 6z"/>
                    </svg>
                    <span>${availabilityText}</span>
                </div>
                
                <div class="parking-info-row">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                        <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                    </svg>
                    <span>${parking.openingHours}</span>
                </div>
                
                <div class="parking-info-row">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                        <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                    </svg>
                    <span>${rateText}</span>
                </div>
            </div>
            
            ${featuresHtml ? `<div class="parking-features">${featuresHtml}</div>` : ''}
            ${parking.dataSource ? `<div class="parking-source">${dataBadge} ${parking.dataSource}</div>` : ''}
            
            <div class="parking-popup-actions">
                <button class="btn btn-secondary btn-sm" onclick="openStreetView(${parking.lat}, ${parking.lng})" title="Voir Street View">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                        <path d="M12 6.5c1.38 0 2.5 1.12 2.5 2.5 0 .74-.33 1.39-.83 1.85l3.63 6.3c.21-.05.42-.08.65-.08 1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3c0-.44.1-.86.27-1.23L12 15.5l-3.22 3.42c.17.37.27.79.27 1.23 0 1.66-1.34 3-3 3s-3-1.34-3-3 1.34-3 3-3c.23 0 .44.03.65.08l3.63-6.3c-.5-.46-.83-1.11-.83-1.85 0-1.38 1.12-2.5 2.5-2.5z"/>
                    </svg>
                    Street View
                </button>
                <button class="btn btn-primary btn-sm" onclick="navigateToParking('${parking.id}')" title="Lancer le guidage">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                        <path d="M21.71 11.29l-9-9c-.39-.39-1.02-.39-1.41 0l-9 9c-.39.39-.39 1.02 0 1.41l9 9c.39.39 1.02.39 1.41 0l9-9c.39-.38.39-1.01 0-1.41z"/>
                    </svg>
                    Y aller
                </button>
                <button class="btn btn-favorite btn-sm ${isFavorite ? 'active' : ''}" 
                        onclick="toggleFavorite('${parking.id}', '${escapeHtml(parking.name)}', ${parking.lat}, ${parking.lng}, this)"
                        title="${isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'}">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                    </svg>
                </button>
                <button class="btn btn-share btn-sm" 
                        onclick="shareParking('${parking.id}', '${escapeHtml(parking.name)}', ${parking.lat}, ${parking.lng})"
                        title="Partager ce parking">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                        <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/>
                    </svg>
                </button>
            </div>
        </div>
    `;
}

// Ouvrir Street View
function openStreetView(lat, lng) {
    const url = `https://www.google.com/maps/@?api=1&map_action=pano&viewpoint=${lat},${lng}`;
    window.open(url, '_blank');
}

// Partager le parking (via applications, sinon copie du lien dans le presse-papiers)
async function shareParking(parkingId, name, lat, lng) {
    const googleMapsUrl = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
    const shareText = `${googleMapsUrl}`;
    const shareData = {
        title: `Parking: ${name}`,
        text: `Je te partage ce parking: ${name}`,
        url: googleMapsUrl
    };

    // Tentative de partage via l'API Web Share
    if (navigator.share && navigator.canShare && navigator.canShare(shareData)) {
        try {
            await navigator.share(shareData);
            showShareFeedback('Partagé avec succès !', 'success');
            return;
        } catch (err) {
            if (err.name === 'AbortError') return; // L'utilisateur a annulé
        }
    }

    // Copie du lien dans le presse-papiers si API Web Share n'est pas disponible/utilisable
    try {
        await navigator.clipboard.writeText(shareText);
        showShareFeedback('Lien copié dans le presse-papier !', 'success');
    } catch (err) {
        // Fallback pour les anciens navigateurs
        const textArea = document.createElement('textarea');
        textArea.value = shareText;
        textArea.style.position = 'fixed';
        textArea.style.opacity = '0';
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showShareFeedback('Lien copié !', 'success');
    }
}

// On affiche un message de feedback temporaire
function showShareFeedback(message, type) {
    const existingToast = document.querySelector('.share-toast');
    if (existingToast) existingToast.remove();

    // On crée le toast
    const toast = document.createElement('div');
    toast.className = `share-toast ${type}`;
    toast.innerHTML = `
        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
        </svg>
        <span>${message}</span>
    `;
    document.body.appendChild(toast);

    // On anime le toast
    requestAnimationFrame(() => toast.classList.add('show'));

    // On le supprime après 3 secondes
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Initialisation du guidage vers un parking spécifique
function navigateToParking(parkingId) {
    const parking = [...parkingsData, ...filteredParkings].find(p => p.id === parkingId);
    if (parking) {
        startNavigation(parking);
    }
}

// Initialisation du guidage vers le parking le plus proche
function navigateToNearest() {
    if (!userPosition) {
        alert('Position non disponible');
        return;
    }

    // Filtrage des parkings navigables éligibles
    const navigableParkings = filteredParkings.filter(p => {
        // On exclut les parkings fermés
        if (p.status === 'closed') return false;
        // On exclut les parkings pleins
        if (p.available !== null && p.available <= 0) return false;
        return true;
    });

    // Si aucun parking possible
    if (navigableParkings.length === 0) {
        alert('Aucun parking disponible correspondant à vos critères');
        return;
    }

    // On trouve le parking le plus proche
    let nearest = null;
    let minDistance = Infinity;

    navigableParkings.forEach(parking => {
        const distance = getDistance(
            userPosition.lat, userPosition.lng,
            parking.lat, parking.lng
        );
        if (distance < minDistance) {
            minDistance = distance;
            nearest = parking;
        }
    });

    if (nearest) {
        startNavigation(nearest);
    }
}

// Démarrage de la navigation
async function startNavigation(parking) {
    if (!userPosition) return;

    isNavigating = true;

    // Masquage des éléments de l'interface utilisateur
    document.getElementById('navigateBtn').classList.add('hidden');
    document.getElementById('filterBtn').classList.add('hidden');
    document.querySelector('.search-container').classList.add('hidden');
    document.getElementById('bottomNav').classList.add('hidden');

    // Affichage du panneau de navigation
    document.getElementById('navigationPanel').classList.remove('hidden');

    // Fermeture des popups
    map.closePopup();

    try {
        // On récupère le parcours depuis OSRM
        const response = await fetch(
            `${config.osrmApi}/${userPosition.lng},${userPosition.lat};${parking.lng},${parking.lat}?overview=full&geometries=geojson&steps=true`
        );
        const data = await response.json();

        if (data.routes && data.routes.length > 0) {
            const route = data.routes[0];

            // On dessine le parcours sur la carte
            if (routingControl) {
                map.removeLayer(routingControl);
            }

            routingControl = L.geoJSON(route.geometry, {
                style: {
                    color: '#667eea',
                    weight: 5,
                    opacity: 0.8
                }
            }).addTo(map);

            // On ajuste la vue de la carte pour afficher le parcours
            map.fitBounds(routingControl.getBounds(), { padding: [50, 50] });

            // Mise à jour du panneau de navigation
            updateNavigationPanel(route, parking);

            // Annonce vocale pour le début de la navigation
            const duration = Math.round(route.duration / 60);
            speak(`Navigation vers ${parking.name}. Durée estimée: ${duration} minutes.`);

            // Stockage des données de navigation actuelles
            window.currentNavigation = { route, parking };
        }
    } catch (error) {
        console.error('Navigation error:', error);
        stopNavigation();
        alert('Erreur lors du calcul de l\'itinéraire');
    }
}

// Mise à jour du panneau de navigation
function updateNavigationPanel(route, parking) {
    const duration = Math.round(route.duration / 60); // On calcule la durée en minutes du trajet
    const now = new Date(); // On récupère la date actuelle
    const eta = new Date(now.getTime() + route.duration * 1000); // On calcule la date d'arrivée
    const etaString = eta.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }); // On formate la date d'arrivée

    document.getElementById('navDuration').textContent = duration; // On affiche la durée
    document.getElementById('navEta').textContent = etaString; // On affiche l'heure d'arrivée

    // On affiche la première instruction
    if (route.legs && route.legs[0] && route.legs[0].steps) {
        const firstStep = route.legs[0].steps[0];
        updateInstructionDisplay(firstStep);
    }
}

// Mise à jour de l'instruction affichée
function updateInstructionDisplay(step) {
    const instruction = step.maneuver?.instruction || 'Continuez tout droit';
    const distance = step.distance > 1000
        ? (step.distance / 1000).toFixed(1) + ' km'
        : Math.round(step.distance) + ' m';

    document.getElementById('instructionText').textContent = instruction;
    document.getElementById('instructionDistance').textContent = distance;

    // Mise à jour de l'icône de l'instruction
    updateInstructionIcon(step.maneuver?.type);
}

// Mise à jour de l'icône de l'instruction
function updateInstructionIcon(type) {
    const iconContainer = document.getElementById('instructionIcon');
    let iconPath = '';

    switch (type) {
        case 'turn':
            iconPath = '<path d="M9 5v2h6.59L4 18.59 5.41 20 17 8.41V15h2V5z"/>';
            break;
        case 'arrive':
            iconPath = '<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>';
            break;
        default:
            iconPath = '<path d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71z"/>';
    }

    iconContainer.innerHTML = `<svg viewBox="0 0 24 24" width="32" height="32" fill="currentColor">${iconPath}</svg>`;
}

// Arrêt de la navigation
function stopNavigation() {
    isNavigating = false;

    // Suppression du parcours de navigation de la carte
    if (routingControl) {
        map.removeLayer(routingControl);
        routingControl = null;
    }

    // Affichage des éléments de l'interface utilisateur
    document.getElementById('navigateBtn').classList.remove('hidden');
    document.getElementById('filterBtn').classList.remove('hidden');
    document.querySelector('.search-container').classList.remove('hidden');
    document.getElementById('bottomNav').classList.remove('hidden');

    // Masquage du panneau de navigation
    document.getElementById('navigationPanel').classList.add('hidden');

    // Recentrer sur la position de l'utilisateur
    if (userPosition) {
        map.setView([userPosition.lat, userPosition.lng], config.defaultZoom);
    }

    window.currentNavigation = null;
}

// Gestion des favoris (passage en favori ou en non favori)
async function toggleFavorite(parkingId, name, lat, lng, button) {
    // Si l'utilisateur est un invité, on lui demande de se connecter
    if (isGuest) {
        alert('Connectez-vous pour ajouter des favoris');
        return;
    }

    // Si le bouton est actif, on retire le favori, sinon on ajoute le favori
    const isActive = button.classList.contains('active');
    const action = isActive ? 'removeFavorite' : 'addFavorite';

    try {
        // On envoie une requête POST à l'API pour ajouter ou retirer le favori
        const response = await fetch(`?page=api&action=${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                parking_id: parkingId,
                name,
                lat,
                lng,
                csrf_token: csrfToken
            })
        });

        const data = await response.json();

        if (data.success) {
            button.classList.toggle('active');

            // On met à jour le tableau des favoris
            if (isActive) {
                const index = userFavorites.indexOf(parkingId);
                if (index > -1) userFavorites.splice(index, 1);
            } else {
                userFavorites.push(parkingId);
            }
        }
    } catch (error) {
        console.error('Error toggling favorite:', error);
    }
}

// Ouverture de la modale de filtre
function openFilterModal() {
    document.getElementById('filterModal').classList.remove('hidden');

    // On met à jour les valeurs du filtre
    document.getElementById('radiusFilter').value = filters.radius / 1000;
    document.getElementById('budgetFilter').value = filters.budget;
    document.getElementById('motorFilter').value = filters.motor;
    document.getElementById('placeFilter').value = filters.placeType;

    updateRadiusLabel();
    updateBudgetLabel();
}

// Fermeture de la modale de filtre
function closeFilterModal() {
    document.getElementById('filterModal').classList.add('hidden');
}

// Mise à jour de l'étiquette de rayon
function updateRadiusLabel() {
    const value = document.getElementById('radiusFilter').value;
    document.getElementById('radiusValue').textContent = value;
}

// Mise à jour de l'étiquette de budget
function updateBudgetLabel() {
    const value = document.getElementById('budgetFilter').value;
    document.getElementById('budgetValue').textContent = value == 0 ? 'Tout' : value + '€/h';
}

// Application des filtres
function applyFilters() {
    filters.radius = parseInt(document.getElementById('radiusFilter').value) * 1000;
    filters.budget = parseInt(document.getElementById('budgetFilter').value);
    filters.motor = document.getElementById('motorFilter').value;
    filters.placeType = document.getElementById('placeFilter').value;

    closeFilterModal();

    // Rechargement des parkings avec le nouveau rayon
    fetchParkings();
}

// Gestion de la recherche
async function handleSearch() {
    const query = document.getElementById('searchInput').value.trim();
    const clearBtn = document.getElementById('searchClear');

    if (query.length === 0) {
        clearBtn.classList.add('hidden');
        hideSearchResults();
        return;
    }

    clearBtn.classList.remove('hidden');

    // Recherche dans les parkings locaux
    const localResults = filteredParkings.filter(p =>
        p.name.toLowerCase().includes(query.toLowerCase())
    ).slice(0, 5); // On limite les résultats à 5

    // Affichage des résultats
    displaySearchResults(localResults);
}

// Affichage des résultats de la recherche
function displaySearchResults(results) {
    const container = document.getElementById('searchResults');

    if (results.length === 0) {
        container.innerHTML = '<div class="search-result-item"><span>Aucun résultat</span></div>';
    } else {
        container.innerHTML = results.map(parking => `
            <div class="search-result-item" data-parking-id="${parking.id}">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                    <path d="M13 3H6v18h4v-6h3c3.31 0 6-2.69 6-6s-2.69-6-6-6zm.2 8H10V7h3.2c1.1 0 2 .9 2 2s-.9 2-2 2z"/>
                </svg>
                <div class="search-result-info">
                    <div class="search-result-name">${escapeHtml(parking.name)}</div>
                    <div class="search-result-address">${parking.available} places • ${parking.hourlyRate === 0 ? 'Gratuit' : parking.hourlyRate + '€/h'}</div>
                </div>
            </div>
        `).join('');

        // Ajout des gestionnaires d'événements
        container.querySelectorAll('.search-result-item').forEach(item => {
            item.addEventListener('click', () => {
                const parkingId = item.dataset.parkingId;
                const parking = filteredParkings.find(p => p.id === parkingId);
                if (parking) {
                    map.setView([parking.lat, parking.lng], 18);
                    hideSearchResults();
                    clearSearch();

                    // Ouverture de la popup pour ce parking automatiquement
                    parkingMarkers.eachLayer(marker => {
                        if (marker.parkingData && marker.parkingData.id === parkingId) {
                            marker.openPopup();
                        }
                    });
                }
            });
        });
    }

    showSearchResults();
}

// Affichage des résultats de la recherche
function showSearchResults() {
    document.getElementById('searchResults').classList.remove('hidden');
}

// Masquage des résultats de la recherche
function hideSearchResults() {
    document.getElementById('searchResults').classList.add('hidden');
}

// Nettoyage de la recherche
function clearSearch() {
    document.getElementById('searchInput').value = '';
    document.getElementById('searchClear').classList.add('hidden');
    hideSearchResults();
}

// Recentrer la carte sur la position de l'utilisateur
function recenterMap() {
    if (userPosition) {
        map.setView([userPosition.lat, userPosition.lng], config.defaultZoom);
    }
}

// Reset du timer d'inactivité
function resetInactivityTimer() {
    if (inactivityTimer) {
        clearTimeout(inactivityTimer);
    }

    if (!isNavigating) {
        inactivityTimer = setTimeout(() => {
            recenterMap();
        }, config.inactivityTimeout);
    }
}

// Vérification de l'intention de navigation depuis la page de profil
function checkNavigationIntent() {
    const navData = sessionStorage.getItem('navigateTo');
    if (navData) {
        const { lat, lng } = JSON.parse(navData);
        sessionStorage.removeItem('navigateTo');

        // Attente de la position de l'utilisateur avant de commencer la navigation
        const checkPosition = setInterval(() => {
            if (userPosition) {
                clearInterval(checkPosition);
                const parking = {
                    id: 'nav_' + Date.now(),
                    name: 'Destination',
                    lat: parseFloat(lat),
                    lng: parseFloat(lng)
                };
                startNavigation(parking);
            }
        }, 500);
    }
}

// Mise à jour des informations de navigation
function updateNavigationInfo() {
    if (window.currentNavigation) {
        // Mise à jour de l'ETA
        const duration = window.currentNavigation.route.duration;
        const now = new Date();
        const eta = new Date(now.getTime() + duration * 1000);
        const etaString = eta.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        document.getElementById('navEta').textContent = etaString;
    }
}

// Initialisation du thème
function initTheme() {
    if (currentTheme === 'light') {
        document.body.classList.add('light-mode');
    }

    // Gestion du bouton de thèmes
    document.getElementById('themeToggle')?.addEventListener('click', toggleTheme);
}

// Basculer entre le thème clair et sombre
function toggleTheme() {
    document.body.classList.toggle('light-mode');
    currentTheme = document.body.classList.contains('light-mode') ? 'light' : 'dark';
    localStorage.setItem('theme', currentTheme);

    // Mise à jour des tuiles de la carte
    updateMapTiles();
}

// Mise à jour des tuiles de la carte
function updateMapTiles() {
    if (!map) return;

    // Si on est en mode satellite, on ne change pas les tuiles
    if (currentMapStyle === 'satellite') return;

    // Suppression de toutes les couches de base actuelles
    Object.values(baseLayers).forEach(layer => {
        if (map.hasLayer(layer)) {
            map.removeLayer(layer);
        }
    });

    // Ajout de la couche appropriée selon le thème
    if (currentTheme === 'light') {
        baseLayers.light.addTo(map);
    } else {
        baseLayers.dark.addTo(map);
    }
}

// Détection de la ville
function detectCity(lat, lng) {
    for (const [cityId, cityConfig] of Object.entries(config.cityApis)) {
        if (cityConfig.enabled &&
            lat >= cityConfig.bounds.minLat && lat <= cityConfig.bounds.maxLat &&
            lng >= cityConfig.bounds.minLng && lng <= cityConfig.bounds.maxLng) {
            return { id: cityId, config: cityConfig };
        }
    }
    return null;
}

// Récupération des parkings de Metz (API Eurométropole)
async function fetchMetzParkings() {
    try {
        // Utilisation du proxy pour éviter les problèmes de CORS
        const proxyUrl = (appUrl || '') + '?page=api&action=parkingProxy&city=metz';
        const response = await fetch(proxyUrl);
        const data = await response.json();

        if (data.error) {
            console.error('Metz API error:', data.error);
            return [];
        }

        if (!data.features || !Array.isArray(data.features)) {
            console.error('Metz API: Invalid response format');
            return [];
        }

        return data.features.map(feature => {
            const props = feature.properties;
            const coords = feature.geometry?.coordinates;

            // Extraction des coordonnées (format GeoJSON: [lng, lat])
            const lng = coords ? coords[0] : null;
            const lat = coords ? coords[1] : null;

            // Extraction des données de places
            const capacity = parseInt(props.place_total) || 0;
            const available = parseInt(props.place_libre) || 0;
            const occupancy = capacity > 0 ? capacity - available : 0;

            // Calcul du pourcentage de remplissage
            const fillPercent = capacity > 0 ? Math.round((occupancy / capacity) * 100) : 0;

            // Détermination du statut
            let status = 'open';
            let statusText = capacity > 0
                ? `${available}/${capacity} places (${fillPercent}% rempli)`
                : 'Données non disponibles';

            if (capacity > 0 && available <= 0) {
                status = 'closed';
                statusText = 'Complet (100%)';
            } else if (capacity > 0 && available < capacity * 0.1) {
                status = 'closing';
                statusText = `${available} places (${fillPercent}% rempli)`;
            }

            // Détermination du tarif
            const isFree = props.cout === 'gratuit';
            const isPaid = props.cout === 'payant';

            // Retour des données du parking
            return {
                id: 'metz_' + props.fid,
                name: props.lib || 'Parking',
                lat: lat,
                lng: lng,
                capacity: capacity,
                capacityText: capacity > 0 ? capacity + ' places' : 'Non renseigné',
                occupancy: occupancy,
                available: available,
                availableText: capacity > 0 ? `${available}/${capacity} places` : 'Non renseigné',
                fillPercent: fillPercent,
                status: status,
                statusText: statusText,
                openingHours: props.place_update ? 'MAJ: ' + new Date(props.place_update).toLocaleTimeString('fr-FR') : 'Données statiques',
                hourlyRate: null,
                hourlyRateText: isPaid ? 'Payant' : (isFree ? 'Gratuit' : 'Non renseigné'),
                isFree: isFree,
                isPaid: isPaid,
                parkingType: props.typ || 'aérien',
                hasPMR: null,
                hasEV: null,
                hasFamily: null,
                dataSource: 'Eurométropole Metz',
                realTimeData: capacity > 0 && props.place_update !== null
            };
        }).filter(p => p.lat !== null && p.lng !== null);
    } catch (error) {
        console.error('Error fetching Metz parkings:', error);
        return [];
    }
}

// Récupération des parkings de Londres
async function fetchLondonParkings() {
    try {
        // Utilisation du proxy pour éviter les problèmes de CORS
        const proxyUrl = (appUrl || '') + '?page=api&action=parkingProxy&city=london';
        const response = await fetch(proxyUrl);
        const data = await response.json();

        if (data.error) {
            console.error('London API error:', data.error);
            return [];
        }

        return data.map(carPark => {
            const capacity = carPark.bays?.length || 0;
            let available = 0;
            let occupancy = 0;

            if (carPark.bays) {
                carPark.bays.forEach(bay => {
                    if (bay.occupied === false) available++;
                    else occupancy++;
                });
            }

            // Calcul du pourcentage de remplissage
            const fillPercent = capacity > 0 ? Math.round((occupancy / capacity) * 100) : 0;

            // Détermination du statut
            let status = 'open';
            let statusText = `${available} spaces (${fillPercent}% full)`;
            if (available <= 0) {
                status = 'closed';
                statusText = 'Full (100%)';
            } else if (available < capacity * 0.1) {
                status = 'closing';
                statusText = `${available} spaces (${fillPercent}% full)`;
            }

            // Retour des données du parking
            return {
                id: 'london_' + carPark.id,
                name: carPark.name || 'Car Park',
                lat: carPark.lat,
                lng: carPark.lon,
                capacity: capacity,
                capacityText: capacity + ' spaces',
                occupancy: occupancy,
                available: available,
                availableText: `${available}/${capacity} spaces`,
                fillPercent: fillPercent,
                status: status,
                statusText: statusText,
                openingHours: 'Real-time data',
                hourlyRate: null,
                hourlyRateText: 'Not available',
                isFree: null,
                isPaid: null,
                hasPMR: null,
                hasEV: null,
                hasFamily: null,
                dataSource: 'TfL Open Data',
                realTimeData: true
            };
        }).filter(p => p.lat && p.lng);
    } catch (error) {
        console.error('Error fetching London parkings:', error);
        return [];
    }
}

// Calcul de la distance entre deux points (formule de Haversine)
function getDistance(lat1, lon1, lat2, lon2) {
    const R = 6371e3;
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
        Math.cos(φ1) * Math.cos(φ2) *
        Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c;
}

// Fonction de protection contre les attaques XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Fonction de débogage
function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

let realUserPosition = null;
let isSimulatingLocation = false;

// Simulation de la localisation pour tester les APIs spécifiques à la ville
function simulateLocation(location) {
    document.querySelectorAll('.test-btn').forEach(btn => btn.classList.remove('active'));

    const testLocations = {
        metz: { lat: 49.1193, lng: 6.1757, name: 'Metz (Centre-ville)' },
        london: { lat: 51.5074, lng: -0.1278, name: 'London (Westminster)' },
        real: null
    };

    if (location === 'real') {
        document.getElementById('testReal')?.classList.add('active');
        isSimulatingLocation = false;

        if (realUserPosition) {
            // Restauration de la position réelle
            userPosition = { ...realUserPosition };
            updateSimulatedMarker(userPosition.lat, userPosition.lng);
            map.setView([userPosition.lat, userPosition.lng], config.defaultZoom);
            fetchParkings();
        } else {
            // Requête de nouvelle localisation
            requestGeolocation();
        }
    } else {
        const loc = testLocations[location];
        if (loc) {
            document.getElementById(`test${location.charAt(0).toUpperCase() + location.slice(1)}`)?.classList.add('active');

            // Sauvegarde de la position réelle si elle n'est pas déjà sauvegardée
            if (userPosition && !isSimulatingLocation) {
                realUserPosition = { ...userPosition };
            }

            isSimulatingLocation = true;
            userPosition = { lat: loc.lat, lng: loc.lng };

            updateSimulatedMarker(loc.lat, loc.lng);
            map.setView([loc.lat, loc.lng], config.defaultZoom);
            fetchParkings();
        }
    }
}

// Mise à jour de la position du marqueur utilisateur lors de la simulation
function updateSimulatedMarker(lat, lng) {
    if (userMarker) {
        userMarker.setLatLng([lat, lng]);
    } else {
        userMarker = L.marker([lat, lng], {
            icon: L.divIcon({
                className: 'user-marker',
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            })
        }).addTo(map);
    }
}

// Basculement entre le style standard et le style satellite
function toggleMapStyle() {
    const btn = document.getElementById('satelliteBtn');

    // Suppression de toutes les couches de base
    Object.values(baseLayers).forEach(layer => {
        if (map.hasLayer(layer)) {
            map.removeLayer(layer);
        }
    });

    if (currentMapStyle === 'satellite') {
        // Passage au style standard (selon le thème)
        currentMapStyle = 'standard';
        localStorage.setItem('mapStyle', 'standard');

        if (currentTheme === 'light') {
            baseLayers.light.addTo(map);
        } else {
            baseLayers.dark.addTo(map);
        }

        if (btn) {
            btn.classList.remove('active');
            btn.title = 'Vue satellite';
        }
    } else {
        // Passage au style satellite
        currentMapStyle = 'satellite';
        localStorage.setItem('mapStyle', 'satellite');
        baseLayers.satellite.addTo(map);

        if (btn) {
            btn.classList.add('active');
            btn.title = 'Vue standard';
        }
    }
}

// Initialisation de la synthèse vocale et sélection de la voix française
function initVoice() {
    if (!('speechSynthesis' in window)) {
        console.warn('Voice synthesis not supported');
        return;
    }

    // Attente du chargement des voix
    const loadVoices = () => {
        const voices = speechSynthesis.getVoices();
        // Préférence pour la voix française
        currentVoice = voices.find(v => v.lang.startsWith('fr')) ||
            voices.find(v => v.lang.startsWith('en')) ||
            voices[0];
    };

    // Chargement des voix
    loadVoices();
    speechSynthesis.onvoiceschanged = loadVoices;
}

// Faire parler le navigateur
function speak(text) {
    if (!voiceEnabled || !('speechSynthesis' in window)) return;

    // Annulation de la parole en cours
    speechSynthesis.cancel();

    // Paramètres de la voix
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'fr-FR';
    utterance.rate = 1.0;
    utterance.pitch = 1.0;
    utterance.volume = 1.0;

    if (currentVoice) {
        utterance.voice = currentVoice;
    }

    speechSynthesis.speak(utterance);
}

// Activation/désactivation de la navigation vocale
function toggleVoice() {
    voiceEnabled = !voiceEnabled;
    localStorage.setItem('voiceNav', voiceEnabled);

    const btn = document.getElementById('voiceBtn');
    if (btn) {
        btn.classList.toggle('active', voiceEnabled);
        btn.title = voiceEnabled ? 'Désactiver la voix' : 'Activer la voix';
    }

    if (voiceEnabled) {
        speak('Navigation vocale activée');
    }
}

// Initialisation de la navigation vocale au chargement
document.addEventListener('DOMContentLoaded', initVoice);

// Widget parking le plus proche

let nearestParking = null;

// Toggle du widget
function toggleNearestWidget() {
    const panel = document.getElementById('widgetPanel');
    if (panel.classList.contains('hidden')) {
        openNearestWidget();
    } else {
        closeNearestWidget();
    }
}

// Ouvrir le widget
function openNearestWidget() {
    const panel = document.getElementById('widgetPanel');
    panel.classList.remove('hidden');
    updateNearestWidget();
}

// Fermer le widget
function closeNearestWidget() {
    const panel = document.getElementById('widgetPanel');
    panel.classList.add('hidden');
}

// Trouver le parking disponible le plus proche
function findNearestAvailableParking() {
    if (!userPosition || filteredParkings.length === 0) {
        return null;
    }

    // Filtrer les parkings disponibles (pas complets)
    const availableParkings = filteredParkings.filter(p => {
        if (p.status === 'closed') return false;
        if (p.available !== null && p.available <= 0) return false;
        return true;
    });

    if (availableParkings.length === 0) {
        return null;
    }

    // Trouver le plus proche
    let nearest = null;
    let minDistance = Infinity;

    availableParkings.forEach(parking => {
        const distance = getDistance(
            userPosition.lat, userPosition.lng,
            parking.lat, parking.lng
        );
        if (distance < minDistance) {
            minDistance = distance;
            nearest = { ...parking, distance };
        }
    });

    return nearest;
}

// Mettre à jour le widget
function updateNearestWidget() {
    const body = document.getElementById('widgetBody');
    const badge = document.getElementById('widgetBadge');

    nearestParking = findNearestAvailableParking();

    if (!nearestParking) {
        badge.textContent = '--';
        body.innerHTML = `
            <div class="widget-empty">
                <p>Aucun parking disponible à proximité</p>
            </div>
        `;
        return;
    }

    // Formater la distance
    const distanceText = nearestParking.distance > 1000
        ? (nearestParking.distance / 1000).toFixed(1) + ' km'
        : Math.round(nearestParking.distance) + ' m';

    // Mettre à jour le badge
    badge.textContent = distanceText;

    // Générer le contenu
    body.innerHTML = `
        <div class="widget-content">
            <div>
                <div class="widget-parking-name">${escapeHtml(nearestParking.name)}</div>
                ${nearestParking.parkingType ? `<span class="widget-parking-type">${nearestParking.parkingType}</span>` : ''}
            </div>
            
            <div class="widget-info-row">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
                <span>${distanceText}</span>
            </div>
            
            ${nearestParking.available !== null && nearestParking.capacity > 0 ? `
            <div class="widget-info-row">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                    <path d="M13 3H6v18h4v-6h3c3.31 0 6-2.69 6-6s-2.69-6-6-6zm.2 8H10V7h3.2c1.1 0 2 .9 2 2s-.9 2-2 2z"/>
                </svg>
                <span class="widget-status ${nearestParking.status}">${nearestParking.available} places libres</span>
            </div>
            ` : ''}
            
            ${nearestParking.hourlyRateText ? `
            <div class="widget-info-row">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                    <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                </svg>
                <span>${nearestParking.hourlyRateText}</span>
            </div>
            ` : ''}
            
            <button class="widget-nav-btn" onclick="navigateToWidgetParking()">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                    <path d="M21.71 11.29l-9-9c-.39-.39-1.02-.39-1.41 0l-9 9c-.39.39-.39 1.02 0 1.41l9 9c.39.39 1.02.39 1.41 0l9-9c.39-.38.39-1.01 0-1.41zM14 14.5V12h-4v3H8v-4c0-.55.45-1 1-1h5V7.5l3.5 3.5-3.5 3.5z"/>
                </svg>
                Y aller
            </button>
        </div>
    `;
}

// Naviguer vers le parking du widget
function navigateToWidgetParking() {
    if (nearestParking) {
        closeNearestWidget();
        startNavigation(nearestParking);
    }
}

// Mettre à jour le widget quand les parkings changent
function refreshNearestWidget() {
    const panel = document.getElementById('widgetPanel');
    const badge = document.getElementById('widgetBadge');

    // Mettre à jour le badge même si le widget est fermé
    const nearest = findNearestAvailableParking();
    if (nearest) {
        const distanceText = nearest.distance > 1000
            ? (nearest.distance / 1000).toFixed(1) + ' km'
            : Math.round(nearest.distance) + ' m';
        badge.textContent = distanceText;
    } else {
        badge.textContent = '--';
    }

    // Si le widget est ouvert, mettre à jour le contenu
    if (!panel.classList.contains('hidden')) {
        updateNearestWidget();
    }
}
