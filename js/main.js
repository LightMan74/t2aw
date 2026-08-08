/**
 * main.js - Gestion de la liste des tournois
 */

document.addEventListener('DOMContentLoaded', () => {
    chargerTournois();
});

/**
 * Charge et affiche la liste des tournois existants sous forme de tableau
 */
async function chargerTournois() {
    const listeDiv = document.getElementById('liste-tournois');
    listeDiv.innerHTML = '<p>Chargement...</p>';

    try {
        const res = await fetch('api/get_tournois.php');
        const text = await res.text();

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            listeDiv.innerHTML = '<p style="color:red">Réponse invalide du serveur (voir console)</p>';
            console.error(text);
            return;
        }

        if (!data.success) {
            listeDiv.innerHTML = '<p style="color:red">Erreur: ' + data.error + '</p>';
            return;
        }

        if (data.tournois.length === 0) {
            listeDiv.innerHTML = '<p>Aucun tournoi pour le moment.</p>';
            return;
        }

        // Construction du tableau
        const table = document.createElement('table');
        table.className = 'tournois-table';

        table.innerHTML = `
            <thead>
                <tr>
                    <th>Nom du tournoi</th>
                    <th class="actions-col" colspan="3"><center>Actions</center></th>
                </tr>
            </thead>
            <tbody></tbody>
        `;

        const tbody = table.querySelector('tbody');

        data.tournois.forEach(t => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><span style="font-size:75%;color:lightgrey;">(id:${t.id_tournoi})</span> ${escapeHtml(t.nom)}</td>
                <td class="actions-col">
                    <a href="edit_tournoi.php?id_tournoi=${t.id_tournoi}" class="btn btn-mini btn-open">Ouvrir</a>
                </td>
                <td class="actions-col">
                    <a href="edit_tournoi.php?id_tournoi=${t.id_tournoi}" class="btn btn-mini btn-edit">Modifier</a>
                </td>
                <td class="actions-col">
                    <a href="#" class="btn btn-mini btn-delete" onclick="supprimerTournoi('${t.id_tournoi}'); return false;">Supprimer</a>
                </td>
            `;
            tbody.appendChild(tr);
        });

        listeDiv.innerHTML = '';
        listeDiv.appendChild(table);

    } catch (err) {
        listeDiv.innerHTML = '<p style="color:red">Erreur réseau</p>';
        console.error(err);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
async function importerTournoi(fichierInput) {
    const formData = new FormData();
    formData.append('fichier', fichierInput.files[0]);

    try {
        const response = await fetch('api/importer_tournoi.php', {
            method: 'POST',
            body: formData
        });
        const resultat = await response.json();

        if (resultat.success) {
            alert(resultat.message);
        } else {
            alert('Erreur : ' + resultat.message);
        }
    } catch (err) {
        alert('Erreur réseau : ' + err.message);
    }
}