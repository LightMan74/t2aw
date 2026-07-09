async function supprimerTournoi(idTournoi) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce tournoi et toutes ses données ?')) {
        return;
    }

    try {
        const response = await fetch('api/supprimer_tournoi.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id_tournoi: idTournoi })
        });

        const result = await response.json();

        if (result.success) {
            console.log('Suppression réussie :', result);
            alert(`Tournoi supprimé avec succès (${result.total_lignes_supprimees} lignes supprimées)`);
            // Rafraîchir la liste des tournois sans reload
            chargerTournois();
        } else {
            console.error('Erreur :', result.message);
            alert('Erreur : ' + result.message);
        }
    } catch (error) {
        console.error('Erreur réseau :', error);
        alert('Erreur de connexion au serveur');
    }
}
