document.getElementById('btn-logout').addEventListener('click', function () {
    fetch('api/logout.php', {
        method: 'POST'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect;
            }
        })
        .catch(error => {
            console.error('Erreur lors de la déconnexion:', error);
        });
});