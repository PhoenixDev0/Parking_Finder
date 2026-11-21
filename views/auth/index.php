<?php 
/**
 *Vue de la page de connexion/inscription
*/
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Parking Finder - Trouvez facilement une place de parking près de chez vous">
    <meta name="theme-color" content="#1a1a2e">
    <title>Connexion - Parking Finder</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="auth-container">
        <!-- Logo et titre -->
        <div class="auth-header">
            <div class="logo">
                <svg viewBox="0 0 100 100" class="logo-icon">
                    <circle cx="50" cy="50" r="45" fill="url(#gradient1)" />
                    <text x="50" y="65" text-anchor="middle" fill="white" font-size="40" font-weight="bold">P</text>
                    <defs>
                        <linearGradient id="gradient1" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#667eea" />
                            <stop offset="100%" style="stop-color:#764ba2" />
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <h1 class="app-title">Parking Finder</h1>
            <p class="app-subtitle">Trouvez votre place en un instant</p>
        </div>

        <!-- Bouton de connexion en tant qu'invité -->
        <button type="button" class="btn btn-guest" id="guestBtn">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
            Se connecter en tant qu'invité
        </button>

        <div class="divider">
            <span>ou</span>
        </div>

        <!-- Formulaire de connexion -->
        <form class="auth-form" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div class="form-group">
                <label for="loginEmail">Email</label>
                <div class="input-wrapper">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" class="input-icon">
                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                    </svg>
                    <input type="email" id="loginEmail" name="email" placeholder="votre@email.com" required>
                </div>
            </div>

            <div class="form-group">
                <label for="loginPassword">Mot de passe</label>
                <div class="input-wrapper">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" class="input-icon">
                        <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                    </svg>
                    <input type="password" id="loginPassword" name="password" placeholder="••••••••" required>
                    <button type="button" class="password-toggle" data-target="loginPassword">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" class="eye-icon">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="loginBtn">
                <span class="btn-text">Connexion</span>
                <span class="btn-loader"></span>
            </button>

            <div class="error-message" id="loginError"></div>
        </form>

        <button type="button" class="switch-form-btn" id="showRegister">
            Pas encore de compte ? <strong>Inscription</strong>
        </button>

        <!-- Formulaire d'inscription (caché par défaut) -->
        <form class="auth-form hidden" id="registerForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div class="form-group">
                <label for="registerFirstName">Prénom</label>
                <div class="input-wrapper">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" class="input-icon">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                    <input type="text" id="registerFirstName" name="first_name" placeholder="Votre prénom" required minlength="3">
                </div>
            </div>

            <div class="form-group">
                <label for="registerLastName">Nom</label>
                <div class="input-wrapper">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" class="input-icon">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                    <input type="text" id="registerLastName" name="last_name" placeholder="Votre nom" required minlength="3">
                </div>
            </div>

            <div class="form-group">
                <label for="registerEmail">Email</label>
                <div class="input-wrapper">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" class="input-icon">
                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                    </svg>
                    <input type="email" id="registerEmail" name="email" placeholder="votre@email.com" required>
                </div>
            </div>

            <div class="form-group">
                <label for="registerPassword">Mot de passe</label>
                <div class="input-wrapper">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" class="input-icon">
                        <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                    </svg>
                    <input type="password" id="registerPassword" name="password" placeholder="Minimum 4 caractères" required minlength="4">
                    <button type="button" class="password-toggle" data-target="registerPassword">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" class="eye-icon">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="registerConfirmPassword">Confirmer le mot de passe</label>
                <div class="input-wrapper">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" class="input-icon">
                        <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                    </svg>
                    <input type="password" id="registerConfirmPassword" name="confirm_password" placeholder="••••••••" required>
                    <button type="button" class="password-toggle" data-target="registerConfirmPassword">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" class="eye-icon">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="registerBtn">
                <span class="btn-text">Inscription</span>
                <span class="btn-loader"></span>
            </button>

            <div class="error-message" id="registerError"></div>
        </form>

        <button type="button" class="switch-form-btn hidden" id="showLogin">
            Vous avez déjà un compte ? <strong>Connexion</strong>
        </button>
    </div>

    <script src="assets/js/auth.js"></script>
</body>
</html>
