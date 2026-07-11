// js/matchs.js

let matchsData = [];

function afficherMessage(texte, type) {
    // const div = document.getElementById('message');
    // div.innerHTML = `<div class="msg ${type}">${texte}</div>`;
    // setTimeout(() => div.innerHTML = '', 4000);
}

async function chargerMatchs() {
    const id_tournoi = document.getElementById('id_tournoi').value;

    return fetch(`api/get_matchs.php?id_tournoi=${id_tournoi}`)
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

async function autosaveandreload() {
    console.log("Exécution...START");
    await Promise.all(matchsData.map((m, index) => sauverMatch(index)));
    await chargerMatchs();
    console.log("Exécution...END");
}

function afficherTable() {
    const corps = document.getElementById('corps-table');
    corps.innerHTML = '';

    matchsData.forEach((m, index) => {
        const tr = document.createElement('tr');
        tr.className = 'status-' + m.status;

        const scoresEquipe1 = String(m.score_equipe_1 ?? '0;0;0').split('*');
        const scoresEquipe2 = String(m.score_equipe_2 ?? '0;0;0').split('*');

        const s1set1 = scoresEquipe1[0] ?? 0;
        const s1set2 = scoresEquipe1[1] ?? 0;
        const s1set3 = scoresEquipe1[2] ?? 0;

        const s2set1 = scoresEquipe2[0] ?? 0;
        const s2set2 = scoresEquipe2[1] ?? 0;
        const s2set3 = scoresEquipe2[2] ?? 0;
        const hiddenSets = tournoi_troissets_match > 1 ? '' : 'hidden';
        tr.innerHTML = `
    <td>${m.nom_categorie}</td>
    <td>${m.nom_poule}</td>
    <td><input type="number" min="1" value="${m.terrain ?? ''}" id="terrain-${index}"></td>
    <td>${m.nom_equipe_1}</td>
    <td><input type="number" min="0" value="${s1set1}" id="score1s1-${index}"></td>   
    <td ${hiddenSets}><input type="number" min="0" value="${s1set2}" id="score1s2-${index}"></td>   
    <td ${hiddenSets}><input type="number" min="0" value="${s1set3}" id="score1s3-${index}"></td>            
    <td>vs</td>
    <td><input type="number" min="0" value="${s2set1}" id="score2s1-${index}"></td>
    <td ${hiddenSets}><input type="number" min="0" value="${s2set2}" id="score2s2-${index}"></td>
    <td ${hiddenSets}><input type="number" min="0" value="${s2set3}" id="score2s3-${index}"></td>
    <td>${m.nom_equipe_2}</td>
    <td>
        <select id="status-${index}">
            <option value="planifie" ${m.status === 'planifie' ? 'selected' : ''}>Planifié</option>
            <option value="en_cours" ${m.status === 'en_cours' ? 'selected' : ''}>En cours</option>
            <option value="termine" ${m.status === 'termine' ? 'selected' : ''}>Terminé</option>
        </select>
    </td>
    <td><input type="text" value="${m.heure_debut ?? ''}" id="hdebut-${index}"></td>
`;

        corps.appendChild(tr);
    });
}

async function sauverMatch(index) {
    const m = matchsData[index];

    const payload = {
        action: 'update_match',
        id: m.id,
        terrain: document.getElementById(`terrain-${index}`).value,
        status: document.getElementById(`status-${index}`).value,
        score_equipe_1: document.getElementById(`score1s1-${index}`).value + "*" + document.getElementById(`score1s2-${index}`).value + "*" + document.getElementById(`score1s3-${index}`).value,
        score_equipe_2: document.getElementById(`score2s1-${index}`).value + "*" + document.getElementById(`score2s2-${index}`).value + "*" + document.getElementById(`score2s3-${index}`).value,
        heure_debut: document.getElementById(`hdebut-${index}`).value,
    };

    return fetch('api/matchs_actions.php', {
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