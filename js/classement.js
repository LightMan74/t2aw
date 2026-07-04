// js/classement.js

function afficherMessage(texte, type) {
    const div = document.getElementById('message');
    div.innerHTML = `<div class="msg ${type}">${texte}</div>`;
    setTimeout(() => div.innerHTML = '', 4000);
}

function chargerClassement() {
    const id_tournoi = document.getElementById('id_tournoi').value;

    fetch(`api/get_classement.php?id_tournoi=${id_tournoi}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                afficherClassement(data.classement);
            } else {
                afficherMessage(data.error, 'error');
            }
        })
        .catch(err => afficherMessage('Erreur de chargement', 'error'));
}

function afficherClassement(classement) {
    const zone = document.getElementById('zone-classement');
    zone.innerHTML = '';

    // Regrouper par catégorie puis poule
    const groupes = {};
    classement.forEach(c => {
        const key = c.id_categorie + '___' + c.id_poule;
        if (!groupes[key]) {
            groupes[key] = {
                nom_categorie: c.nom_categorie,
                nom_poule: c.nom_poule,
                equipes: []
            };
        }
        groupes[key].equipes.push(c);
    });

    // Trier les groupes par id_categorie puis id_poule (ordre naturel des clés)
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
        // 2. Différence de points (desc)
        // 3. Points marqués (desc)
        lignes.sort((a, b) => {
            if (b.victoire !== a.victoire) return b.victoire - a.victoire;

            const diffA = a.point_marquer - a.point_encaisser;
            const diffB = b.point_marquer - b.point_encaisser;
            if (diffB !== diffA) return diffB - diffA;

            return b.point_marquer - a.point_marquer;
        });

        const h2 = document.createElement('h2');
        h2.textContent = `${groupe.nom_categorie} - ${groupe.nom_poule}`;
        zone.appendChild(h2);

        const table = document.createElement('table');
        table.innerHTML = `
            <thead>
                <tr>
                    <th>Rang</th>
                    <th>Equipe</th>
                    <th>MJ</th>
                    <th>V</th>
                    <th>D</th>
                    <th>Sets G</th>
                    <th>Pts marqués</th>
                    <th>Pts encaissés</th>
                    <th>Diff.</th>
                </tr>
            </thead>
            <tbody></tbody>
        `;

        const tbody = table.querySelector('tbody');
        lignes.forEach((l, index) => {
            const diff = l.point_marquer - l.point_encaisser;
            const tr = document.createElement('tr');

            // Mise en avant du premier de la poule
            if (index === 0) tr.classList.add('premier-poule');

            tr.innerHTML = `
                <td>${index + 1}</td>
                <td>${l.nom_equipe}</td>
                <td>${l.matchs_joues}</td>
                <td>${l.victoire}</td>
                <td>${l.defaite}</td>
                <td>${l.set_gagner}</td>
                <td>${l.point_marquer}</td>
                <td>${l.point_encaisser}</td>
                <td>${diff >= 0 ? '+' + diff : diff}</td>
            `;
            tbody.appendChild(tr);
        });

        zone.appendChild(table);
    });

    if (clesTriees.length === 0) {
        zone.innerHTML = '<p>Aucune donnée de classement pour ce tournoi.</p>';
    }
}
