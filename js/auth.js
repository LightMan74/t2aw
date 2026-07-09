document.getElementById('loginForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const user = document.getElementById('user').value.trim();
    const password = document.getElementById('password').value;
    const messageEl = document.getElementById('message');
    const submitBtn = document.getElementById('submitBtn');

    // Reset message
    messageEl.textContent = '';
    messageEl.className = 'message';

    if (!user || !password) {
        messageEl.textContent = 'Veuillez remplir tous les champs';
        messageEl.classList.add('error');
        return;
    }

    // Désactiver le bouton pendant la requête
    submitBtn.disabled = true;
    submitBtn.textContent = 'Connexion...';

    // Envoi des données via fetch (pas de reload)
    fetch('api/login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ user, password })
    })
        .then(response => response.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Se connecter';

            if (data.success) {
                messageEl.textContent = data.message;
                messageEl.classList.add('success');

                // Redirection après connexion réussie
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 500);
            } else {
                messageEl.textContent = data.message;
                messageEl.classList.add('error');
            }
        })
        .catch(error => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Se connecter';
            messageEl.textContent = 'Erreur de connexion au serveur';
            messageEl.classList.add('error');
            console.error('Erreur:', error);
        });
});