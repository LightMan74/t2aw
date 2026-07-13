/**
 * matchs.js — Gestion / saisie des matchs (page admin)
 * Dépendance : colors.js (palette partagée catégories/poules, chargé AVANT ce fichier)
 * Harmonisé avec afficheur.js — même logique getCategorieColorClass / getPouleColorClass
 */

let matchsData = [];
const modifiedMatchs = new Set();

function afficherMessage(texte, type) {
    const div = document.getElementById('message');
    if (!div) return;
    div.innerHTML = `<div class="msg ${type}">${texte}</div>`;
    setTimeout(() => { div.innerHTML = ''; }, 4000);
}

async function chargerMatchs() {
    const id_tournoi = document.getElementById('id_tournoi').value;

    try {
        const res = await fetch(`api/get_matchs.php?id_tournoi=${id_tournoi}`);
        const data = await res.json();

        if (data.success) {
            matchsData = data.matchs;
            modifiedMatchs.clear();
            afficherTable();
        } else {
            afficherMessage(data.error, 'error');
        }
    } catch (err) {
        afficherMessage('Erreur réseau : ' + err.message, 'error');
    }
}

// Cycle des statuts pour le clic rapide sur le badge
const STATUS_CYCLE = ['planifie', 'en_cours', 'termine'];
const STATUS_LABELS = {
    planifie: 'Planifié',
    en_cours: 'En jeu',
    termine: 'Terminé'
};

function marquerModifie(index) {
    modifiedMatchs.add(index);
    majIconeSave(index);
}

function majIconeSave(index) {
    const icone = document.getElementById(`save-icon-${index}`);
    if (!icone) return;
    if (modifiedMatchs.has(index)) {
        icone.textContent = '💾'; // disquette normale = à sauvegarder
        icone.classList.add('non-sauvegarde');
        icone.title = 'Modifié — non sauvegardé';
    } else {
        icone.textContent = ''; // disquette barrée = rien à sauvegarder (ou utiliser CSS)
        icone.classList.remove('non-sauvegarde');
        icone.title = 'Aucune modification en attente';
    }
}

function afficherTable() {
    const corps = document.getElementById('corps-table');
    if (!corps) { console.error('Élément #corps-table introuvable'); return; }
    corps.innerHTML = '';

    matchsData.forEach((m, index) => {
        const tr = document.createElement('tr');
        const poleClass = getPouleColorClassById(m.id_poule, m.id_poule_2);
        const catClass = getCategorieColorClassById(m.id_categorie);
        tr.className = 'status-' + (m.status ?? '') + ' ' + poleClass;
        tr.id = `row-${index}`;

        const scoresEquipe1 = String(m.score_equipe_1 ?? '0*0*0').split('*');
        const scoresEquipe2 = String(m.score_equipe_2 ?? '0*0*0').split('*');

        const s1set1 = scoresEquipe1[0] ?? 0;
        const s1set2 = scoresEquipe1[1] ?? 0;
        const s1set3 = scoresEquipe1[2] ?? 0;
        const s2set1 = scoresEquipe2[0] ?? 0;
        const s2set2 = scoresEquipe2[1] ?? 0;
        const s2set3 = scoresEquipe2[2] ?? 0;

        const hiddenSets = tournoi_troissets_match > 1 ? '' : 'hidden';

        const statusActuel = m.status ?? 'planifie';

        tr.innerHTML = `
            <td class="categorie-badge ${catClass}">${m.nom_categorie ?? ''}</td>
            <td class="poule-badge ${poleClass}">${m.nom_poule ?? ''}</td>
            <td><input type="number" min="1" value="${terrain_automatique == 1 ? (m.terrain ?? '') : ''}" id="terrain-${index}"></td>
            <td>${m.nom_equipe_1 ?? ''}</td>
            <td><input type="number" min="0" value="${s1set1}" id="score1s1-${index}"> - <input type="number" min="0" value="${s2set1}" id="score2s1-${index}"><span ${hiddenSets}>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
            <input  type="number" min="0" value="${s1set2}" id="score1s2-${index}"> - <input type="number" min="0" value="${s2set2}" id="score2s2-${index}">&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
            <input type="number" min="0" value="${s1set3}" id="score1s3-${index}"> - <input type="number" min="0" value="${s2set3}" id="score2s3-${index}"></span>
            </td>
            <td>${m.nom_equipe_2 ?? ''}</td>
            <td>
                <span class="status-badge status-badge-${statusActuel}" 
                      id="status-badge-${index}" 
                      onclick="cyclerStatus(${index})"
                      title="Cliquer pour changer rapidement le statut">
                    ${STATUS_LABELS[statusActuel] ?? statusActuel}
                </span>
                <select id="status-${index}" style="margin-left:5px;" hidden>
                    <option value="planifie" ${statusActuel === 'planifie' ? 'selected' : ''}>Planifié</option>
                    <option value="en_cours" ${statusActuel === 'en_cours' ? 'selected' : ''}>En_cours</option>
                    <option value="termine" ${statusActuel === 'termine' ? 'selected' : ''}>Terminé</option>
                </select>
            </td>
            <td><input type="time" step="60" value="${(m.heure_debut ?? '').substring(0, 5)}" id="hdebut-${index}"></td>
            <td>
                <span class="save-icon" id="save-icon-${index}" title="Aucune modification en attente" onclick="sauvegarderLigne(${index})"></span>
            </td>
        `;

        corps.appendChild(tr);

        // ── Tracking des modifications ──
        const champsEditables = [
            `terrain-${index}`, `status-${index}`, `hdebut-${index}`,
            `score1s1-${index}`, `score1s2-${index}`, `score1s3-${index}`,
            `score2s1-${index}`, `score2s2-${index}`, `score2s3-${index}`,
        ];

        champsEditables.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', () => marquerModifie(index));
            el.addEventListener('change', () => {
                marquerModifie(index);
                if (id === `status-${index}`) {
                    majBadgeStatus(index, el.value);
                }
            });
        });
    });
}

function majBadgeStatus(index, nouveauStatus) {
    const badge = document.getElementById(`status-badge-${index}`);
    const select = document.getElementById(`status-${index}`);
    const tr = document.getElementById(`row-${index}`);

    if (badge) {
        badge.textContent = STATUS_LABELS[nouveauStatus] ?? nouveauStatus;
        badge.className = `status-badge status-badge-${nouveauStatus}`;
    }
    if (select) {
        select.value = nouveauStatus;
    }
    if (tr) {
        tr.className = 'status-' + nouveauStatus;
    }
}

function cyclerStatus(index) {
    const select = document.getElementById(`status-${index}`);
    if (!select) return;

    const actuel = select.value;
    const idxCycle = STATUS_CYCLE.indexOf(actuel);
    const suivant = STATUS_CYCLE[(idxCycle + 1) % STATUS_CYCLE.length];

    majBadgeStatus(index, suivant);
    marquerModifie(index);
}

async function autosaveandreload() {
    console.log('autosaveandreload — START');

    if (modifiedMatchs.size === 0) {
        afficherMessage('Aucune modification à sauvegarder', 'success');
        return;
    }

    const matchsToSave = [];
    for (const index of modifiedMatchs) {
        const m = matchsData[index];
        if (!m) continue;

        const terrain = document.getElementById(`terrain-${index}`)?.value ?? '';
        const status = document.getElementById(`status-${index}`)?.value ?? m.status;
        const heure_debut = document.getElementById(`hdebut-${index}`)?.value ?? '';

        const score_equipe_1 = [
            document.getElementById(`score1s1-${index}`)?.value ?? 0,
            document.getElementById(`score1s2-${index}`)?.value ?? 0,
            document.getElementById(`score1s3-${index}`)?.value ?? 0,
        ].join('*');

        const score_equipe_2 = [
            document.getElementById(`score2s1-${index}`)?.value ?? 0,
            document.getElementById(`score2s2-${index}`)?.value ?? 0,
            document.getElementById(`score2s3-${index}`)?.value ?? 0,
        ].join('*');

        matchsToSave.push({
            id: m.id,
            terrain,
            status,
            heure_debut,
            score_equipe_1,
            score_equipe_2,
        });
    }

    try {
        const res = await fetch('api/matchs_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update_matchs_bulk',
                matchs: matchsToSave,
            }),
        });

        const data = await res.json();

        if (data.success) {
            modifiedMatchs.clear();
            afficherMessage(`${matchsToSave.length} match(s) sauvegardé(s) ✓`, 'success');
            await chargerMatchs();
        } else {
            afficherMessage(data.error || 'Erreur de sauvegarde', 'error');
        }
    } catch (err) {
        afficherMessage('Erreur réseau : ' + err.message, 'error');
    }
    console.log('autosaveandreload — END');
}

async function sauvegarderLigne(index) {
    if (!modifiedMatchs.has(index)) {
        afficherMessage('Aucune modification pour ce match', 'success');
        return;
    }

    const m = matchsData[index];
    if (!m) return;

    const terrain = document.getElementById(`terrain-${index}`)?.value ?? '';
    const status = document.getElementById(`status-${index}`)?.value ?? m.status;
    const heure_debut = document.getElementById(`hdebut-${index}`)?.value ?? '';

    const score_equipe_1 = [
        document.getElementById(`score1s1-${index}`)?.value ?? 0,
        document.getElementById(`score1s2-${index}`)?.value ?? 0,
        document.getElementById(`score1s3-${index}`)?.value ?? 0,
    ].join('*');

    const score_equipe_2 = [
        document.getElementById(`score2s1-${index}`)?.value ?? 0,
        document.getElementById(`score2s2-${index}`)?.value ?? 0,
        document.getElementById(`score2s3-${index}`)?.value ?? 0,
    ].join('*');

    try {
        const res = await fetch('api/matchs_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update_matchs_bulk',
                matchs: [{
                    id: m.id,
                    terrain,
                    status,
                    heure_debut,
                    score_equipe_1,
                    score_equipe_2,
                }],
            }),
        });

        const data = await res.json();

        if (data.success) {
            modifiedMatchs.delete(index);
            majIconeSave(index);
            afficherMessage('Match sauvegardé ✓', 'success');
        } else {
            afficherMessage(data.error || 'Erreur de sauvegarde', 'error');
        }
    } catch (err) {
        afficherMessage('Erreur réseau : ' + err.message, 'error');
    }
}

document.addEventListener('focus', function (e) {
    if (e.target.tagName === 'INPUT') {
        e.target.select();
    }
}, true);
