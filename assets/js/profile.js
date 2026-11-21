/**
 * Gestion de la page de profil (modification email/mot de passe, favoris, contact).
 */
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.getElementById('csrfToken')?.value;
    const appUrl = document.getElementById('appUrl')?.value;

    // Basculement de visibilité des mots de passe
    const passwordToggles = document.querySelectorAll('.password-toggle');
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

    // Affichage temporaire des messages de retour utilisateur
    function showMessage(elementId, message, isSuccess) {
        const messageDiv = document.getElementById(elementId);
        if (messageDiv) {
            messageDiv.textContent = message;
            messageDiv.className = 'form-message ' + (isSuccess ? 'success' : 'error');
            messageDiv.classList.remove('hidden');

            if (isSuccess) {
                setTimeout(() => {
                    messageDiv.classList.add('hidden');
                }, 3000);
            }
        }
    }

    // Mise à jour de l'adresse email
    const emailForm = document.getElementById('emailForm');
    emailForm?.addEventListener('submit', async function (e) {
        e.preventDefault();

        const btn = this.querySelector('button[type="submit"]');
        const email = document.getElementById('newEmail').value;

        btn.classList.add('loading');

        try {
            const response = await fetch('?page=profile&action=updateEmail', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email,
                    csrf_token: csrfToken
                })
            });

            const data = await response.json();
            showMessage('emailMessage', data.message, data.success);

            if (data.success) {
                document.getElementById('newEmail').value = '';
                document.querySelector('.user-email').textContent = email;
            }
        } catch (error) {
            showMessage('emailMessage', 'Une erreur est survenue', false);
        } finally {
            btn.classList.remove('loading');
        }
    });

    // Mise à jour du mot de passe
    const passwordForm = document.getElementById('passwordForm');
    passwordForm?.addEventListener('submit', async function (e) {
        e.preventDefault();

        const btn = this.querySelector('button[type="submit"]');
        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmNewPassword').value;

        btn.classList.add('loading');

        try {
            const response = await fetch('?page=profile&action=updatePassword', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    current_password: currentPassword,
                    new_password: newPassword,
                    confirm_password: confirmPassword,
                    csrf_token: csrfToken
                })
            });

            const data = await response.json();
            showMessage('passwordMessage', data.message, data.success);

            if (data.success) {
                document.getElementById('currentPassword').value = '';
                document.getElementById('newPassword').value = '';
                document.getElementById('confirmNewPassword').value = '';
            }
        } catch (error) {
            showMessage('passwordMessage', 'Une erreur est survenue', false);
        } finally {
            btn.classList.remove('loading');
        }
    });

    // Navigation vers un parking favori depuis la liste
    document.querySelectorAll('.btn-navigate').forEach(btn => {
        btn.addEventListener('click', function () {
            const item = this.closest('.favorite-item');
            const lat = item.dataset.lat;
            const lng = item.dataset.lng;

            sessionStorage.setItem('navigateTo', JSON.stringify({ lat, lng }));
            window.location.href = appUrl + '?page=map';
        });
    });

    // Suppression d'un parking favori
    document.querySelectorAll('.btn-remove').forEach(btn => {
        btn.addEventListener('click', async function () {
            const item = this.closest('.favorite-item');
            const parkingId = item.dataset.parkingId;

            if (!confirm('Supprimer ce parking des favoris ?')) return;

            try {
                const response = await fetch('?page=api&action=removeFavorite', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        parking_id: parkingId,
                        csrf_token: csrfToken
                    })
                });

                const data = await response.json();

                if (data.success) {
                    item.remove();

                    const list = document.querySelector('.favorites-list');
                    if (list && list.children.length === 0) {
                        list.outerHTML = `
                            <div class="empty-state">
                                <svg viewBox="0 0 24 24" width="48" height="48" fill="currentColor">
                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                </svg>
                                <p>Aucun parking favori</p>
                                <small>Ajoutez des parkings à vos favoris depuis la carte</small>
                            </div>
                        `;
                    }
                }
            } catch (error) {
                console.error('Error removing favorite:', error);
            }
        });
    });

    // Envoi du formulaire de contact
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const subject = document.getElementById('contactSubject').value.trim();
            const message = document.getElementById('contactMessage').value.trim();
            const submitBtn = this.querySelector('button[type="submit"]');
            const messageDiv = document.getElementById('contactFormMessage');

            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="spinner-icon" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                    <path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/>
                </svg>
                Envoi en cours...
            `;

            try {
                const response = await fetch('?page=api&action=sendContact', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        subject: subject,
                        message: message,
                        csrf_token: csrfToken
                    })
                });

                const data = await response.json();

                messageDiv.classList.remove('hidden', 'error', 'success');
                messageDiv.classList.add(data.success ? 'success' : 'error');
                messageDiv.textContent = data.message;

                if (data.success) {
                    contactForm.reset();
                }
            } catch (error) {
                console.error('Error sending contact message:', error);
                messageDiv.classList.remove('hidden', 'success');
                messageDiv.classList.add('error');
                messageDiv.textContent = 'Erreur de connexion. Veuillez réessayer.';
            }

            submitBtn.disabled = false;
            submitBtn.innerHTML = `
                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
                Envoyer
            `;
        });
    }
});
