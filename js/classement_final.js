// js/classement_final.js

function afficherMessage(texte, type) {
    const div = document.getElementById('message');
    div.className = type;
    div.textContent = texte;
    setTimeout(() => div.innerHTML = '', 5000);
}

function chargerClassementFinal() {
    const idTournoi = document.body.dataset.idTournoi || new URLSearchParams(window.location.search).get('id_tournoi');
    if (!idTournoi) {
        afficherMessage('Aucun tournoi sélectionné.', 'error');
        return;
    }
    fetch('api/view_classement_final.php?id_tournoi=' + idTournoi)
        .then(res => res.json())
        .then(data => {
            if (data.categories) {
                afficherClassementFinal(data.categories);
            } else {
                afficherMessage(data.error || 'Erreur lors du chargement.', 'error');
            }
        })
        .catch(() => {
            afficherMessage('Erreur de connexion.', 'error');
        });
}

function afficherClassementFinal(categories) {
    const zone = document.getElementById('zone-classement');
    if (!zone) return;
    zone.innerHTML = '';

    if (!categories || categories.length === 0) {
        zone.innerHTML = '<div class="vide">Aucun classement final disponible</div>';
        return;
    }

    categories.forEach(cat => {
        const catClass = getCategorieColorClassById(cat.id_categorie);

        // === Conteneur unique pour le bloc (en-tête + tableau) ===
        const bloc = document.createElement('div');
        bloc.className = 'classement-bloc';

        const header = document.createElement('div');
        header.className = 'classement-header';
        header.innerHTML = `<span class="badge-pill ${catClass}">${escapeHTML(cat.nom_categorie)}</span>`;
        bloc.appendChild(header);

        const table = document.createElement('table');
        table.innerHTML = `
        <thead>
            <tr>
                <th>Position</th>
                <th>Équipe</th>
            </tr>
        </thead>
        <tbody></tbody>
    `;

        const tbody = table.querySelector('tbody');

        const equipesAffichees = (cat.classement || []).filter(eq => !eq.is_bye);

        if (equipesAffichees.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2">Aucun classement disponible</td></tr>';
        } else {
            equipesAffichees.forEach((eq, index) => {
                const tr = document.createElement('tr');
                if (index === 0) tr.classList.add('premier');

                tr.innerHTML = `
                <td>${eq.position}</td>
                <td>${escapeHTML(eq.nom_equipe || '')}</td>
            `;
                tbody.appendChild(tr);
            });
        }

        bloc.appendChild(table);
        zone.appendChild(bloc);
    });
}

// ----- Utilitaire -----
function escapeHTML(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&')
        .replace(/</g, '<')
        .replace(/>/g, '>')
        .replace(/"/g, '"');
}