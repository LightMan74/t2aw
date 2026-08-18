/**
 * ordre_matchs.js — Fusion des matchs de poule et de phase finale,
 * affichage unifié avec drag & drop pour réordonner, et gestion
 * terrain/heure/statut/score comme dans matchs.js et phase_final_matchs.js
 */

let matchsData = []; // fusion { source: 'poule'|'pf', ...donnéesOriginales, code }
const modifiedMatchs = new Set();
let draggedIndex = null;

function afficherMessage(texte, type) {
    const div = document.getElementById('message');
    if (!div) return;
    div.innerHTML = `<div class="msg ${type}">${texte}</div>`;
    setTimeout(() => { div.innerHTML = ''; }, 5000);
}

// ---------- Chargement / fusion des données ----------

async function chargerMatchs() {
    const id_tournoi = document.getElementById('id_tournoi').value;

    try {
        const [resPoule, resPF, resOrdre] = await Promise.all([
            fetch(`api/get_matchs.php?id_tournoi=${id_tournoi}`),
            fetch(`api/phase_finale/get_matchs_all.php?id_tournoi=${id_tournoi}`),
            fetch(`api/save_match_ordre.php?id_tournoi=${id_tournoi}`)
        ]);

        const dataPoule = await resPoule.json();
        const dataPF = await resPF.json();
        const dataOrdre = await resOrdre.json();

        let listePoule = [];
        let listePF = [];
        let numbmatch = 0;

        if (dataPoule.success) {
            listePoule = dataPoule.matchs.map((m, i) => ({
                source: 'poule',
                code: `P_${++numbmatch}`,
                ...m
            }));
        }
        numbmatch = 0;
        if (dataPF.success) {
            listePF = dataPF.matchs.map((m, i) => ({
                source: 'pf',
                code: `F_${++numbmatch}`,
                ...m
            }));
        }

        matchsData = [...listePoule, ...listePF];

        // Application de l'ordre sauvegardé s'il existe
        if (dataOrdre.success && Array.isArray(dataOrdre.ordre) && dataOrdre.ordre.length > 0) {
            const ordreMap = new Map();
            dataOrdre.ordre.forEach((code, idx) => ordreMap.set(code, idx));

            matchsData.sort((a, b) => {
                const posA = ordreMap.has(a.code) ? ordreMap.get(a.code) : Infinity;
                const posB = ordreMap.has(b.code) ? ordreMap.get(b.code) : Infinity;
                return posA - posB;
            });
        }

        modifiedMatchs.clear();
        afficherTable();
    } catch (err) {
        afficherMessage('Erreur réseau : ' + err.message, 'error');
    }
}

// ---------- Statuts ----------

const STATUS_CYCLE = ['planifie', 'en_cours', 'termine'];
const STATUS_LABELS = {
    planifie: 'Planifié',
    en_cours: 'En jeu',
    termine: 'Terminé'
};

function getStatut(m) {
    return m.source === 'poule' ? (m.status ?? 'planifie') : (m.statut_match ?? 'planifie');
}

function marquerModifie(index) {
    modifiedMatchs.add(index);
    majIconeSave(index);
}

function majIconeSave(index) {
    const icone = document.getElementById(`save-icon-${index}`);
    if (!icone) return;
    if (modifiedMatchs.has(index)) {
        icone.textContent = '💾';
        icone.classList.add('non-sauvegarde');
        icone.title = 'Modifié — non sauvegardé';
    } else {
        icone.textContent = '';
        icone.classList.remove('non-sauvegarde');
        icone.title = 'Aucune modification en attente';
    }
}

// ---------- Affichage du tableau ----------

function afficherTable() {
    const corps = document.getElementById('corps-table');
    if (!corps) { console.error('Élément #corps-table introuvable'); return; }
    corps.innerHTML = '';

    const tr2 = document.createElement('tr');
    tr2.innerHTML = `<td colspan="11" id="numbermatchshidden">0 Matchs terminé caché.</td>`;
    corps.appendChild(tr2);

    matchsData.forEach((m, index) => {
        const tr = document.createElement('tr');
        tr.draggable = true;
        tr.dataset.index = index;

        const statutActuel = getStatut(m);
        const catClass = (typeof getCategorieColorClassById === 'function')
            ? getCategorieColorClassById(m.id_categorie)
            : '';
        const poleClass = (m.source === 'poule' && typeof getPouleColorClassById === 'function')
            ? getPouleColorClassById(m.id_poule, m.id_poule_2)
            : '';

        tr.className = 'status-' + statutActuel;

        const nom1 = m.source === 'poule' ? (m.nom_equipe_1 ?? '') : (m.nom_equipe1 ?? m.source_team1 ?? '???');
        const nom2 = m.source === 'poule' ? (m.nom_equipe_2 ?? '') : (m.nom_equipe2 ?? m.source_team2 ?? '???');

        const scoreRaw1 = m.source === 'poule' ? m.score_equipe_1 : m.score1;
        const scoreRaw2 = m.source === 'poule' ? m.score_equipe_2 : m.score2;

        const scoresEquipe1 = String(scoreRaw1 ?? '0*0*0').split('*');
        const scoresEquipe2 = String(scoreRaw2 ?? '0*0*0').split('*');

        const s1set1 = scoresEquipe1[0] ?? 0;
        const s1set2 = scoresEquipe1[1] ?? 0;
        const s1set3 = scoresEquipe1[2] ?? 0;
        const s2set1 = scoresEquipe2[0] ?? 0;
        const s2set2 = scoresEquipe2[1] ?? 0;
        const s2set3 = scoresEquipe2[2] ?? 0;

        const hiddenSets = (typeof tournoi_troissets_match !== 'undefined' && tournoi_troissets_match > 1) ? '' : 'hidden';

        const disabledScore = (m.source === 'pf' && statutActuel !== 'termine') ? 'disabled' : '';

        const heure = m.source === 'poule' ? m.heure_debut : m.heure_debut;

        const nomPoule = m.source === 'poule' ? (m.nom_poule ?? '') : '';

        tr.innerHTML = `
            <td class="drag-handle">⠿</td>
            <td class="match-code-label">${m.code}</td>
            <td class="categorie-badge ${catClass}">${m.nom_categorie ?? ''}</td>
            <td class="poule-badge ${poleClass}">${nomPoule}</td>
            <td>${nom1}</td>
            <td>
            <span style="display: block ruby;">
            <input type="number" min="0" value="${s1set1}" id="score1s1-${index}" ${disabledScore}> - <input type="number" min="0" value="${s2set1}" id="score2s1-${index}" ${disabledScore}><span ${hiddenSets}>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
            <input type="number" min="0" value="${s1set2}" id="score1s2-${index}" ${disabledScore}> - <input type="number" min="0" value="${s2set2}" id="score2s2-${index}" ${disabledScore}>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
            <input type="number" min="0" value="${s1set3}" id="score1s3-${index}" ${disabledScore}> - <input type="number" min="0" value="${s2set3}" id="score2s3-${index}" ${disabledScore}></span>
            </span>
            </td>
            <td>${nom2}</td>
            <td><input type="number" min="1" value="${m.terrain ?? ''}" id="terrain-${index}"></td>
            <td>
                <span class="status-badge status-badge-${statutActuel}"
                    id="status-badge-${index}"
                    onclick="cyclerStatus(${index})"
                    title="Cliquer pour changer rapidement le statut">
                    ${STATUS_LABELS[statutActuel] ?? statutActuel}
                </span>
                <select id="status-${index}" style="margin-left:5px;" hidden>
                    <option value="planifie" ${statutActuel === 'planifie' ? 'selected' : ''}>Planifié</option>
                    <option value="en_cours" ${statutActuel === 'en_cours' ? 'selected' : ''}>En_cours</option>
                    <option value="termine" ${statutActuel === 'termine' ? 'selected' : ''}>Terminé</option>
                </select>
            </td>
            <td>
                <input type="time" step="60" value="${(heure ?? '').substring(0, 5)}" id="hdebut-${index}">
                <button type="button" class="btn-now" id="btn-now-${index}" title="Mettre à l'heure actuelle et décaler les matchs suivants sur ce terrain" onclick="mettreHeureActuelle(${index})">⏱️</button>
            </td>
            <td>
                <span class="save-icon" id="save-icon-${index}" title="Aucune modification en attente" onclick="sauvegarderLigne(${index})"></span>
            </td>
        `;

        corps.appendChild(tr);

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

        // Drag & drop
        tr.addEventListener('dragstart', onDragStart);
        tr.addEventListener('dragover', onDragOver);
        tr.addEventListener('dragleave', onDragLeave);
        tr.addEventListener('drop', onDrop);
        tr.addEventListener('dragend', onDragEnd);
    });
}

function majBadgeStatus(index, nouveauStatus) {
    const badge = document.getElementById(`status-badge-${index}`);
    const select = document.getElementById(`status-${index}`);
    const tr = document.querySelector(`tr[data-index="${index}"]`);
    const m = matchsData[index];

    if (badge) {
        badge.textContent = STATUS_LABELS[nouveauStatus] ?? nouveauStatus;
        badge.className = `status-badge status-badge-${nouveauStatus}`;
    }
    if (select) {
        select.value = nouveauStatus;
    }
    if (tr) {
        const poleClass = (m.source === 'poule' && typeof getPouleColorClassById === 'function')
            ? getPouleColorClassById(m.id_poule, m.id_poule_2)
            : '';
        tr.className = 'status-' + nouveauStatus + ' ' + poleClass;
    }

    // Pour les matchs PF, activer/désactiver le score selon le statut
    if (m.source === 'pf') {
        const disabled = nouveauStatus !== 'termine';
        ['1s1', '1s2', '1s3', '2s1', '2s2', '2s3'].forEach(suffix => {
            const el = document.getElementById(`score${suffix}-${index}`);
            if (el) el.disabled = disabled;
        });
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

// ---------- Drag & Drop ----------

function onDragStart(e) {
    draggedIndex = parseInt(e.currentTarget.dataset.index, 10);
    e.currentTarget.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
}

function onDragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add('drag-over');
    e.dataTransfer.dropEffect = 'move';
}

function onDragLeave(e) {
    e.currentTarget.classList.remove('drag-over');
}

function onDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('drag-over');

    const targetIndex = parseInt(e.currentTarget.dataset.index, 10);
    if (draggedIndex === null || draggedIndex === targetIndex) return;

    // Réorganisation du tableau matchsData
    const [item] = matchsData.splice(draggedIndex, 1);
    matchsData.splice(targetIndex, 0, item);

    // On réinitialise les modifications en cours car les index changent
    modifiedMatchs.clear();

    afficherTable();
}

function onDragEnd(e) {
    e.currentTarget.classList.remove('dragging');
    document.querySelectorAll('tr.drag-over').forEach(tr => tr.classList.remove('drag-over'));
}

// ---------- Sauvegarde de l'ordre ----------

async function enregistrerOrdre() {
    const id_tournoi = document.getElementById('id_tournoi').value;
    const ordre = matchsData.map(m => m.code);

    try {
        const res = await fetch('api/save_match_ordre.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_tournoi: parseInt(id_tournoi, 10),
                ordre
            })
        });

        const data = await res.json();

        if (data.success) {
            afficherMessage('Ordre des matchs enregistré ✓', 'success');
        } else {
            afficherMessage(data.error || 'Erreur lors de l\'enregistrement de l\'ordre', 'error');
        }
    } catch (err) {
        afficherMessage('Erreur réseau : ' + err.message, 'error');
    }
}

// ---------- Sauvegarde des modifications (score/terrain/heure/statut) ----------

function construireScoresDepuisInputs(index) {
    const score1 = [
        document.getElementById(`score1s1-${index}`)?.value ?? 0,
        document.getElementById(`score1s2-${index}`)?.value ?? 0,
        document.getElementById(`score1s3-${index}`)?.value ?? 0,
    ].join('*');

    const score2 = [
        document.getElementById(`score2s1-${index}`)?.value ?? 0,
        document.getElementById(`score2s2-${index}`)?.value ?? 0,
        document.getElementById(`score2s3-${index}`)?.value ?? 0,
    ].join('*');

    return { score1, score2 };
}

async function sauvegarderLigne(index) {
    if (!modifiedMatchs.has(index)) {
        afficherMessage('Aucune modification pour ce match', 'success');
        return;
    }

    const m = matchsData[index];
    if (!m) return;

    const terrain = document.getElementById(`terrain-${index}`)?.value ?? '';
    const statut = document.getElementById(`status-${index}`)?.value ?? getStatut(m);
    const heure_debut = document.getElementById(`hdebut-${index}`)?.value ?? '';
    const { score1, score2 } = construireScoresDepuisInputs(index);

    try {
        let res;
        if (m.source === 'poule') {
            res = await fetch('api/matchs_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update_matchs_bulk',
                    matchs: [{
                        id: m.id,
                        terrain,
                        status: statut,
                        heure_debut,
                        score_equipe_1: score1,
                        score_equipe_2: score2,
                    }],
                }),
            });
        } else {
            let s1 = score1, s2 = score2;
            if (statut !== 'termine') { s1 = null; s2 = null; }

            res = await fetch('api/phase_finale/saisir_score.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    match_id: m.id,
                    score1: s1,
                    score2: s2,
                    statut_match: statut,
                    terrain: terrain || null,
                    heure_debut: heure_debut || null,
                }),
            });
        }

        const data = await res.json();

        if (data.success) {
            modifiedMatchs.delete(index);
            majIconeSave(index);
            afficherMessage('Match sauvegardé ✓', 'success');
        } else {
            afficherMessage(data.error || data.message || 'Erreur de sauvegarde', 'error');
        }
    } catch (err) {
        afficherMessage('Erreur réseau : ' + err.message, 'error');
    }
}

async function autosaveandreload() {
    if (modifiedMatchs.size === 0) {
        afficherMessage('Aucune modification à sauvegarder', 'success');
        return;
    }

    const indexesASauver = [...modifiedMatchs];
    const matchsPouleBulk = [];
    const matchsPFAEnvoyer = [];

    for (const index of indexesASauver) {
        const m = matchsData[index];
        if (!m) continue;

        const terrain = document.getElementById(`terrain-${index}`)?.value ?? '';
        const statut = document.getElementById(`status-${index}`)?.value ?? getStatut(m);
        const heure_debut = document.getElementById(`hdebut-${index}`)?.value ?? '';
        const { score1, score2 } = construireScoresDepuisInputs(index);

        if (m.source === 'poule') {
            matchsPouleBulk.push({
                id: m.id,
                terrain,
                status: statut,
                heure_debut,
                score_equipe_1: score1,
                score_equipe_2: score2,
            });
        } else {
            let s1 = score1, s2 = score2;
            if (statut !== 'termine') { s1 = null; s2 = null; }

            matchsPFAEnvoyer.push({
                index,
                payload: {
                    match_id: m.id,
                    score1: s1,
                    score2: s2,
                    statut_match: statut,
                    terrain: terrain || null,
                    heure_debut: heure_debut || null,
                }
            });
        }
    }

    let nbOk = 0;

    try {
        if (matchsPouleBulk.length > 0) {
            const res = await fetch('api/matchs_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update_matchs_bulk',
                    matchs: matchsPouleBulk,
                }),
            });
            const data = await res.json();
            if (data.success) nbOk += matchsPouleBulk.length;
        }

        for (const { payload } of matchsPFAEnvoyer) {
            const res = await fetch('api/phase_finale/saisir_score.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (data.success) nbOk++;
        }

        modifiedMatchs.clear();
        afficherMessage(`${nbOk} match(s) sauvegardé(s) ✓`, 'success');
        await chargerMatchs();
        togglematchtermine();
    } catch (err) {
        afficherMessage('Erreur réseau : ' + err.message, 'error');
    }
}

// ---------- Heure manuelle / décalage horaire par terrain ----------

let tempsDeMatch = null;
let nombreTerrains = null;
let matchtermine = null;

async function chargerParametres() {
    const id_tournoi = document.getElementById('id_tournoi').value;
    try {
        const res = await fetch(`api/get_parametres.php?id_tournoi=${id_tournoi}`);
        const data = await res.json();
        if (data.success && data.temps_de_match) {
            tempsDeMatch = parseInt(data.temps_de_match, 10) || 20;
            nombreTerrains = parseInt(data.nbre_terrain_poule, 10) || 1;
            matchtermine = data.matchtermine;
        } else {
            tempsDeMatch = 20;
            nombreTerrains = 1;
            matchtermine = 0;
        }
    } catch (err) {
        tempsDeMatch = 20;
        nombreTerrains = 1;
        matchtermine = 0;
    }
}

function formatHeureMinute(date) {
    const h = String(date.getHours()).padStart(2, '0');
    const m = String(date.getMinutes()).padStart(2, '0');
    return `${h}:${m}`;
}

function ajouterMinutes(heureStr, minutesAAjouter) {
    if (!heureStr || isNaN(minutesAAjouter)) return heureStr;
    const [h, m] = heureStr.split(':').map(Number);
    const date = new Date();
    date.setHours(h, m, 0, 0);
    date.setMinutes(date.getMinutes() + minutesAAjouter);
    return formatHeureMinute(date);
}

function getTerrainVirtuel(index) {
    const ordre = index;
    let rt = (ordre % nombreTerrains);
    if (rt == 0) rt = nombreTerrains;
    return rt;
}

function getTerrainEffectif(idx, mm) {
    const t = document.getElementById(`terrain-${idx}`)?.value ?? mm.terrain;
    return t ? t : getTerrainVirtuel(idx);
}

async function mettreHeureActuelle(index) {
    if (tempsDeMatch === null) await chargerParametres();
    if (nombreTerrains === null) await chargerParametres();

    const m = matchsData[index];
    if (!m) return;

    const terrainInput = document.getElementById(`terrain-${index}`);
    let terrain = terrainInput?.value ?? m.terrain;

    if (!terrain) {
        terrain = getTerrainVirtuel(index);
    }

    const statusActuelSelect = document.getElementById(`status-${index}`);
    const statutActuel = statusActuelSelect?.value ?? getStatut(m);

    if (statutActuel === 'termine') {
        afficherMessage('Ce match est déjà terminé, heure non modifiée', 'error');
        return;
    }

    const tousLesTerrains = document.getElementById('decalageTousTerrains')?.checked ?? false;

    const heureManuelleCheckbox = document.getElementById('heureManuelleCheckbox');
    const heureManuelleInput = document.getElementById('heureManuelleInput');

    let nouvelleHeureMatch;
    if (heureManuelleCheckbox?.checked && heureManuelleInput?.value) {
        nouvelleHeureMatch = heureManuelleInput.value;
    } else {
        nouvelleHeureMatch = formatHeureMinute(new Date());
    }

    const hdebutInput = document.getElementById(`hdebut-${index}`);
    if (hdebutInput) {
        hdebutInput.value = nouvelleHeureMatch;
        marquerModifie(index);
    }

    let nbModifies = 0;

    const terrainsATraiter = tousLesTerrains
        ? [...new Set(matchsData.map((mm, idx) => document.getElementById(`terrain-${idx}`)?.value ?? mm.terrain))]
        : [terrain];

    for (const terrainCourant of terrainsATraiter) {
        const matchsTerrain = matchsData
            .map((mm, idx) => ({ mm, idx }))
            .filter(({ mm, idx }) => {
                const t = getTerrainEffectif(idx, mm);
                return String(t) === String(terrainCourant);
            })
            .sort((a, b) => a.idx - b.idx);

        const positionActuelle = matchsTerrain.findIndex(({ idx }) => idx === index);
        let heurePrecedente = nouvelleHeureMatch;

        const depart = String(terrainCourant) === String(terrain) ? positionActuelle + 1 : 0;

        for (let i = depart; i < matchsTerrain.length; i++) {
            const { mm, idx } = matchsTerrain[i];
            const statusSelect = document.getElementById(`status-${idx}`);
            const statutCourant = statusSelect?.value ?? getStatut(mm);

            const hInput = document.getElementById(`hdebut-${idx}`);

            if (statutCourant === 'en_cours' || statutCourant === 'termine') {
                if (hInput?.value) heurePrecedente = hInput.value;
                continue;
            }

            const nouvelleHeure = ajouterMinutes(heurePrecedente, tempsDeMatch);

            if (hInput) {
                hInput.value = nouvelleHeure;
                marquerModifie(idx);
                nbModifies++;
            }

            heurePrecedente = nouvelleHeure;
        }
    }

    const portee = tousLesTerrains ? 'tous les terrains' : `le terrain ${terrain}`;
    afficherMessage(`Heures mises à jour ✓ (${nbModifies} match(s) décalé(s) sur ${portee})`, 'success');
}

function toggleHeureManuelle() {
    const checkbox = document.getElementById('heureManuelleCheckbox');
    const input = document.getElementById('heureManuelleInput');
    input.style.display = checkbox.checked ? 'inline-block' : 'none';

    if (checkbox.checked && !input.value) {
        input.value = formatHeureMinute(new Date());
    }
}

// ---------- Masquer les matchs terminés ----------

function togglematchtermine() {
    const lignesTerminees = document.querySelectorAll('tr.status-termine');
    const checkbox = document.getElementById('matchtermineCheckbox');
    let countmachshide = 0;
    if (checkbox.checked) {
        lignesTerminees.forEach(ligne => {
            ligne.style.display = 'none';
            countmachshide++;
        });
    } else {
        lignesTerminees.forEach(ligne => {
            ligne.style.display = '';
        });
    }
    const label = document.getElementById('numbermatchshidden');
    if (label) label.textContent = countmachshide + " Matchs terminé caché.";
}

// ---------- Initialisation ----------

document.addEventListener('DOMContentLoaded', async () => {
    await chargerMatchs();
    await chargerParametres();

    if (matchtermine) {
        document.getElementById('matchtermineCheckbox').checked = true;
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                togglematchtermine();
            });
        });
    }
});

document.addEventListener('focus', function (e) {
    if (e.target.tagName === 'INPUT') {
        e.target.select();
    }
}, true);