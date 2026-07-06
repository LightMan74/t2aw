// js/matchs.js

let matchsData = [];

function afficherMessage(texte, type) {
    // const div = document.getElementById('message');
    // div.innerHTML = `<div class="msg ${type}">${texte}</div>`;
    // setTimeout(() => div.innerHTML = '', 4000);
}

function chargerMatchs() {
    const id_tournoi = document.getElementById('id_tournoi').value;

    fetch(`api/get_matchs.php?id_tournoi=${id_tournoi}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                matchsData = data.matchs;
                afficherTable();
            } else {
                afficherMessage(data.error, 'error');
            }
        });
}

function autosaveandreload() {
    console.log("Exécution...START");
    matchsData.forEach((m, index) => {
        sauverMatch(index);
    });
    chargerMatchs();
    console.log("Exécution...END");
}

function afficherTable() {
    const corps = document.getElementById('corps-table');
    corps.innerHTML = '';

    matchsData.forEach((m, index) => {
        const tr = document.createElement('tr');
        tr.className = 'status-' + m.status;

        tr.innerHTML = `
            <td>${m.nom_categorie}</td>
            <td>${m.nom_poule}</td>
            <td>${m.id_match}</td>
            <td><input type="number" min="1" value="${m.terrain ?? ''}" id="terrain-${index}"></td>
            <td>${m.nom_equipe_1}</td>
            <td><input type="number" min="0" value="${m.score_equipe_1 ?? 0}" id="score1-${index}"></td>
            <td><input type="number" min="0" value="${m.score_equipe_2 ?? 0}" id="score2-${index}"></td>
            <td>${m.nom_equipe_2}</td>
            <td>
                <select id="status-${index}">
                    <option value="planifie" ${m.status === 'planifie' ? 'selected' : ''}>Planifié</option>
                    <option value="en_cours" ${m.status === 'en_cours' ? 'selected' : ''}>En cours</option>
                    <option value="termine" ${m.status === 'termine' ? 'selected' : ''}>Terminé</option>
                </select>
            </td>
            <td><input type="text" value="${m.heure_debut ?? ''}" id="hdebut-${index}"></td>
            <td><input type="text" value="${m.heure_fin ?? ''}" id="hfin-${index}"></td>
        `;

        // <td><button onclick="sauverMatch(${index})">Enregistrer</button></td>
        corps.appendChild(tr);
    });
}

function sauverMatch(index) {
    const m = matchsData[index];

    const payload = {
        action: 'update_match',
        id: m.id,
        terrain: document.getElementById(`terrain-${index}`).value,
        status: document.getElementById(`status-${index}`).value,
        score_equipe_1: document.getElementById(`score1-${index}`).value,
        score_equipe_2: document.getElementById(`score2-${index}`).value,
        heure_debut: document.getElementById(`hdebut-${index}`).value,
        heure_fin: document.getElementById(`hfin-${index}`).value
    };

    fetch('api/matchs_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                afficherMessage('Match mis à jour', 'success');
                // chargerMatchs(); // recharge sans reload de page
            } else {
                afficherMessage(data.error, 'error');
            }
        });
}
