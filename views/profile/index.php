<?php
/**
 *Vue de la page de profil
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Parking Finder - Gérez votre profil">
    <meta name="theme-color" content="#1a1a2e">
    <title>Profil - Parking Finder</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="profile-page">
    <div class="profile-container">
        <!-- En-tête du profil -->
        <div class="profile-header">
            <div class="avatar">
                <svg viewBox="0 0 24 24" width="48" height="48" fill="currentColor">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
            </div>
            <?php if (!empty($user['is_guest'])): ?>
                <h1 class="user-name">Invité</h1>
                <span class="badge badge-warning">Mode Invité</span>
            <?php else: ?>
                <h1 class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
            <?php endif; ?>
        </div>

        <!-- Section du compte (cachée pour les invités) -->
        <?php if (empty($user['is_guest'])): ?>
        <section class="profile-section">
            <h2 class="section-title">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                </svg>
                Compte
            </h2>

            <!-- Formulaire de modification de l'email -->
            <form class="profile-form" id="emailForm">
                <div class="form-group">
                    <label for="newEmail">Nouvelle adresse email</label>
                    <div class="input-wrapper">
                        <input type="email" id="newEmail" placeholder="nouveau@email.com" required>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <span class="btn-text">Modifier</span>
                            <span class="btn-loader"></span>
                        </button>
                    </div>
                </div>
                <div class="form-message" id="emailMessage"></div>
            </form>

            <!-- Formulaire de modification du mot de passe -->
            <form class="profile-form" id="passwordForm">
                <div class="form-group">
                    <label for="currentPassword">Mot de passe actuel</label>
                    <div class="input-wrapper">
                        <input type="password" id="currentPassword" placeholder="••••••••" required>
                        <button type="button" class="password-toggle" data-target="currentPassword">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" class="eye-icon">
                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="newPassword">Nouveau mot de passe</label>
                    <div class="input-wrapper">
                        <input type="password" id="newPassword" placeholder="Min. 4 caractères" required minlength="4">
                        <button type="button" class="password-toggle" data-target="newPassword">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" class="eye-icon">
                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirmNewPassword">Confirmer le nouveau mot de passe</label>
                    <div class="input-wrapper">
                        <input type="password" id="confirmNewPassword" placeholder="••••••••" required>
                        <button type="button" class="password-toggle" data-target="confirmNewPassword">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" class="eye-icon">
                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <span class="btn-text">Modifier le mot de passe</span>
                    <span class="btn-loader"></span>
                </button>
                <div class="form-message" id="passwordMessage"></div>
            </form>
        </section>
        <?php endif; ?>

        <!-- Section des favoris -->
        <section class="profile-section">
            <h2 class="section-title">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                </svg>
                Favoris
            </h2>

            <!-- Si l'utilisateur est invité, on affiche un message, sinon on affiche la liste des favoris -->
            <?php if (empty($user['is_guest'])): ?>
                <?php if (empty($favorites)): ?>
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" width="48" height="48" fill="currentColor">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                        <p>Aucun parking favori</p>
                        <small>Ajoutez des parkings à vos favoris depuis la carte</small>
                    </div>
                <?php else: ?>
                    <ul class="favorites-list">
                        <?php foreach ($favorites as $fav): ?>
                            <li class="favorite-item" data-parking-id="<?php echo htmlspecialchars($fav['parking_id']); ?>" data-lat="<?php echo $fav['latitude']; ?>" data-lng="<?php echo $fav['longitude']; ?>">
                                <div class="favorite-info">
                                    <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor" class="parking-icon">
                                        <path d="M13 3H6v18h4v-6h3c3.31 0 6-2.69 6-6s-2.69-6-6-6zm.2 8H10V7h3.2c1.1 0 2 .9 2 2s-.9 2-2 2z"/>
                                    </svg>
                                    <span class="favorite-name"><?php echo htmlspecialchars($fav['parking_name']); ?></span>
                                </div>
                                <div class="favorite-actions">
                                    <button class="btn btn-icon btn-navigate" title="Y aller">
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                            <path d="M21.71 11.29l-9-9c-.39-.39-1.02-.39-1.41 0l-9 9c-.39.39-.39 1.02 0 1.41l9 9c.39.39 1.02.39 1.41 0l9-9c.39-.38.39-1.01 0-1.41zM14 14.5V12h-4v3H8v-4c0-.55.45-1 1-1h5V7.5l3.5 3.5-3.5 3.5z"/>
                                        </svg>
                                    </button>
                                    <button class="btn btn-icon btn-remove" title="Supprimer">
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                        </svg>
                                    </button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <!-- Bouton pour exporter les parkings favoris (en JSON ou en CSV) -->
                    <div class="export-buttons">
                        <span class="export-label">Exporter :</span>
                        <a href="?page=api&action=exportFavorites&format=json" class="btn btn-sm btn-secondary" title="Télécharger en JSON">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
                            </svg>
                            JSON
                        </a>
                        <a href="?page=api&action=exportFavorites&format=csv" class="btn btn-sm btn-secondary" title="Télécharger en CSV (Excel)">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
                            </svg>
                            CSV
                        </a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" width="48" height="48" fill="currentColor">
                        <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                    </svg>
                    <p>Connectez-vous pour sauvegarder vos favoris</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Section de contact -->
        <section class="profile-section">
            <h2 class="section-title">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
                Nous contacter
            </h2>
            
            <form id="contactForm" class="contact-form">
                <div class="form-group">
                    <label for="contactSubject">Sujet</label>
                    <input type="text" id="contactSubject" name="subject" class="input" placeholder="Ex: Problème technique, Suggestion..." required>
                </div>
                
                <div class="form-group">
                    <label for="contactMessage">Message</label>
                    <textarea id="contactMessage" name="message" class="input textarea" rows="4" placeholder="Décrivez votre demande..." required></textarea>
                </div>
                
                <div id="contactFormMessage" class="form-message hidden"></div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                    Envoyer
                </button>
            </form>
        </section>

        <!-- Section de gestion des données (Connexion/Déconnexion) -->
        <section class="profile-section">
            <h2 class="section-title">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                    <path d="M19.14 12.94c.04-.31.06-.63.06-.94 0-.31-.02-.63-.06-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
                </svg>
                Gestion
            </h2>

            <?php if (!empty($user['is_guest'])): ?>
                <a href="?page=auth&action=logout" class="btn btn-primary btn-block">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                        <path d="M11 7L9.6 8.4l2.6 2.6H2v2h10.2l-2.6 2.6L11 17l5-5-5-5zm9 12h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-8v2h8v14z"/>
                    </svg>
                    Se connecter / S'inscrire
                </a>
            <?php else: ?>
                <a href="?page=auth&action=logout" class="btn btn-danger btn-block">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                        <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                    </svg>
                    Déconnexion
                </a>
            <?php endif; ?>
        </section>
    </div>

    <!-- Barre de navigation en bas de page -->
    <nav class="bottom-nav">
        <a href="?page=map" class="nav-item">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                <path d="M20.5 3l-.16.03L15 5.1 9 3 3.36 4.9c-.21.07-.36.25-.36.48V20.5c0 .28.22.5.5.5l.16-.03L9 18.9l6 2.1 5.64-1.9c.21-.07.36-.25.36-.48V3.5c0-.28-.22-.5-.5-.5zM15 19l-6-2.11V5l6 2.11V19z"/>
            </svg>
            <span>Carte</span>
        </a>
        <a href="?page=profile" class="nav-item active">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
            <span>Profil</span>
        </a>
    </nav>

    <!-- Données cachées -->
    <input type="hidden" id="csrfToken" value="<?php echo $csrfToken; ?>">
    <input type="hidden" id="appUrl" value="<?php echo APP_URL; ?>">

    <script src="assets/js/profile.js"></script>
</body>
</html>
