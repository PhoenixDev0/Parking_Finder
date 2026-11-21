/**
 * Gestion de l'authentification côté client (connexion, inscription, mode invité).
 */
document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const showRegisterBtn = document.getElementById('showRegister');
    const showLoginBtn = document.getElementById('showLogin');
    const guestBtn = document.getElementById('guestBtn');
    const passwordToggles = document.querySelectorAll('.password-toggle');

    // Basculement vers le formulaire d'inscription
    showRegisterBtn?.addEventListener('click', function () {
        loginForm.classList.add('hidden');
        showRegisterBtn.classList.add('hidden');
        registerForm.classList.remove('hidden');
        showLoginBtn.classList.remove('hidden');
    });

    // Basculement vers le formulaire de connexion
    showLoginBtn?.addEventListener('click', function () {
        registerForm.classList.add('hidden');
        showLoginBtn.classList.add('hidden');
        loginForm.classList.remove('hidden');
        showRegisterBtn.classList.remove('hidden');
    });

    // Affichage ou masquage du mot de passe
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('.eye-icon');

            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>';
            }
        });
    });

    // Soumission du formulaire de connexion
    loginForm?.addEventListener('submit', async function (e) {
        e.preventDefault();

        const btn = document.getElementById('loginBtn');
        const errorDiv = document.getElementById('loginError');
        const csrfToken = this.querySelector('[name="csrf_token"]').value;

        btn.classList.add('loading');
        errorDiv.classList.add('hidden');
        errorDiv.textContent = '';

        const email = document.getElementById('loginEmail').value;
        const password = document.getElementById('loginPassword').value;

        try {
            const response = await fetch('?page=auth&action=login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email,
                    password,
                    csrf_token: csrfToken
                })
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = data.redirect;
            } else {
                errorDiv.textContent = data.message;
                errorDiv.classList.remove('hidden');
            }
        } catch (error) {
            errorDiv.textContent = 'Une erreur est survenue. Veuillez réessayer.';
            errorDiv.classList.remove('hidden');
        } finally {
            btn.classList.remove('loading');
        }
    });

    // Soumission du formulaire d'inscription
    registerForm?.addEventListener('submit', async function (e) {
        e.preventDefault();

        const btn = document.getElementById('registerBtn');
        const errorDiv = document.getElementById('registerError');
        const csrfToken = this.querySelector('[name="csrf_token"]').value;

        btn.classList.add('loading');
        errorDiv.classList.add('hidden');
        errorDiv.textContent = '';

        const firstName = document.getElementById('registerFirstName').value;
        const lastName = document.getElementById('registerLastName').value;
        const email = document.getElementById('registerEmail').value;
        const password = document.getElementById('registerPassword').value;
        const confirmPassword = document.getElementById('registerConfirmPassword').value;

        // Validation du format du nom et prénom (caractères alphabétiques uniquement)
        const nameRegex = /^[\p{L}\s\-']+$/u;

        if (firstName.length < 3) {
            errorDiv.textContent = 'Le prénom doit contenir au moins 3 caractères';
            errorDiv.classList.remove('hidden');
            btn.classList.remove('loading');
            return;
        }
        if (!nameRegex.test(firstName)) {
            errorDiv.textContent = 'Le prénom ne doit contenir que des lettres, espaces, tirets ou apostrophes';
            errorDiv.classList.remove('hidden');
            btn.classList.remove('loading');
            return;
        }

        if (lastName.length < 3) {
            errorDiv.textContent = 'Le nom doit contenir au moins 3 caractères';
            errorDiv.classList.remove('hidden');
            btn.classList.remove('loading');
            return;
        }
        if (!nameRegex.test(lastName)) {
            errorDiv.textContent = 'Le nom ne doit contenir que des lettres, espaces, tirets ou apostrophes';
            errorDiv.classList.remove('hidden');
            btn.classList.remove('loading');
            return;
        }

        try {
            const response = await fetch('?page=auth&action=register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    first_name: firstName,
                    last_name: lastName,
                    email,
                    password,
                    confirm_password: confirmPassword,
                    csrf_token: csrfToken
                })
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = data.redirect;
            } else {
                errorDiv.textContent = data.message;
                errorDiv.classList.remove('hidden');
            }
        } catch (error) {
            errorDiv.textContent = 'Une erreur est survenue. Veuillez réessayer.';
            errorDiv.classList.remove('hidden');
        } finally {
            btn.classList.remove('loading');
        }
    });

    // Connexion immédiate en mode invité
    guestBtn?.addEventListener('click', async function () {
        try {
            const response = await fetch('?page=auth&action=guest', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = data.redirect;
            }
        } catch (error) {
            console.error('Guest login error:', error);
        }
    });
});
