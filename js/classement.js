// js/classement.js

function afficherMessage(texte, type) {
    const div = document.getElementById('message');
    div.className = type;
    div.textContent = texte;
    setTimeout(() => div.innerHTML = '', 5000);
}

function chargerClassement() {
    const idTournoi = document.body.dataset.idTournoi || new URLSearchParams(window.location.search).get('id_tournoi');
    if (!idTournoi) {
        afficherMessage('Aucun tournoi sélectionné.', 'error');
        return;
    }
    fetch('api/get_classement.php?id_tournoi=' + idTournoi)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                afficherClassement(data.classement);
            } else {
                afficherMessage(data.error || 'Erreur lors du chargement.', 'error');
            }
        })
        .catch(() => {
            afficherMessage('Erreur de connexion.', 'error');
        });
}

function afficherClassement(classement) {
    const zone = document.getElementById('zone-classement');
    if (!zone) return;
    zone.innerHTML = '';

    // Regrouper par catégorie puis poule
    const groupes = {};
    classement.forEach(c => {
        const key = (c.id_categorie ?? 0) + '___' + (c.id_poule ?? 0);
        if (!groupes[key]) {
            groupes[key] = {
                id_categorie: c.id_categorie,
                id_poule: c.id_poule,
                nom_categorie: c.nom_categorie,
                nom_poule: c.nom_poule,
                equipes: []
            };
        }
        groupes[key].equipes.push(c);
    });

    // Trier par id_categorie puis id_poule
    const clesTriees = Object.keys(groupes).sort((a, b) => {
        const [catA, pA] = a.split('___').map(Number);
        const [catB, pB] = b.split('___').map(Number);
        if (catA !== catB) return catA - catB;
        return pA - pB;
    });

    clesTriees.forEach(key => {
        const groupe = groupes[key];
        let lignes = groupe.equipes;

        // Tri automatique du classement :
        // 1. Nombre de victoires (desc)
        // 2. Différence de sets (desc)
        // 3. Points marqués (desc)
        lignes.sort((a, b) => {
            if (a.victoire !== b.victoire) return b.victoire - a.victoire;
            const diffA = (a.set_gagner ?? 0) - (a.set_perdu ?? 0);
            const diffB = (b.set_gagner ?? 0) - (b.set_perdu ?? 0);
            if (diffB !== diffA) return diffB - diffA;
            return (b.point_marquer ?? 0) - (a.point_marquer ?? 0);
        });

        const badgeCat = `<span class="badge-pill categorie-${((groupe.id_categorie - 1) % 10) + 1}">${groupe.nom_categorie}</span>`;
        const badgePoule = groupe.id_poule
            ? `<span class="badge-pill poule-${((groupe.id_poule - 1) % 10) + 1}">${groupe.nom_poule}</span>`
            : '';

        // === Conteneur unique pour le bloc (en-tête + tableau) ===
        const bloc = document.createElement('div');
        bloc.className = 'classement-bloc';

        const header = document.createElement('div');
        header.className = 'classement-header';
        header.innerHTML = badgeCat + badgePoule;
        bloc.appendChild(header);

        const table = document.createElement('table');
        table.innerHTML = `
        <thead>
            <tr>
                <th>Rang</th>
                <th>Equipe</th>
                <th>Match Joué</th>
                <th>Victoire</th>
                <th>Defaite</th>
                <th>Sets Gagner</th>
                <th>Points marqués</th>
                <th>Points encaissés</th>
                <th>Difference Points</th>
            </tr>
        </thead>
        <tbody></tbody>
    `;

        const tbody = table.querySelector('tbody');
        lignes.forEach((l, index) => {
            const tr = document.createElement('tr');
            if (index === 0) tr.classList.add('premier');
            const diff = (l.point_marquer ?? 0) - (l.point_encaisser ?? 0);
            tr.innerHTML = `
            <td>${index + 1}</td>
            <td>${escapeHTML(l.nom_equipe || '')}</td>
            <td>${l.matchs_joues ?? 0}</td>
            <td>${l.victoire ?? 0}</td>
            <td>${l.defaite ?? 0}</td>
            <td>${l.set_gagner ?? 0}</td>
            <td>${l.point_marquer ?? 0}</td>
            <td>${l.point_encaisser ?? 0}</td>
            <td>${diff >= 0 ? '+' : ''}${diff}</td>
        `;
            tbody.appendChild(tr);
        });

        bloc.appendChild(table);
        zone.appendChild(bloc);
    });

    if (clesTriees.length === 0) {
        zone.innerHTML = '<p>Aucun classement disponible.</p>';
    }
}

// ----- Utilitaire -----
function escapeHTML(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}