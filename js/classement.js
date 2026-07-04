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
        });
}

function afficherClassement(classement) {
    const zone = document.getElementById('zone-classement');
    zone.innerHTML = '';

    // Regrouper par catégorie puis poule
    const groupes = {};
    classement.forEach(c => {
        const key = c.nom_categorie + '___' + c.nom_poule;
        if (!groupes[key]) groupes[key] = [];
        groupes[key].push(c);
    });

    for (const key in groupes) {
        const [nomCat, nomPoule] = key.split('___');
        const lignes = groupes[key];

        const h2 = document.createElement('h2');
        h2.textContent = `${nomCat} - ${nomPoule}`;
        zone.appendChild(h2);

        const table = document.createElement('table');
        table.innerHTML = `
            <thead>
                <tr>
                    <th>Equipe</th>
                    <th>Victoires</th>
                    <th>Défaites</th>
                    <th>Sets gagnés</th>
                    <th>Points marqués</th>
                    <th>Points encaissés</th>
                    <th>Diff.</th>
                </tr>
            </thead>
            <tbody></tbody>
        `;

        const tbody = table.querySelector('tbody');
        lignes.forEach(l => {
            const tr = document.createElement('tr');
            const diff = l.point_marquer - l.point_encaisser;
            tr.innerHTML = `
                <td>${l.nom_equipe}</td>
                <td>${l.victoire}</td>
                <td>${l.defaite}</td>
                <td>${l.set_gagner}</td>
                <td>${l.point_marquer}</td>
                <td>${l.point_encaisser}</td>
                <td>${diff}</td>
            `;
            tbody.appendChild(tr);
        });

        zone.appendChild(table);
    }
}
