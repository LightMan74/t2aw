// phase_final.js

const API_BASE = 'api/phase_finale';

let currentPhaseFinaleId = null;
let currentMatchId = null;
let currentPhaseFinaleIdASupprimer = null;
let selectedCategorieId = null;

// Équipes pour l'ordre de départ
let equipesOrdre = [];
let draggedIndex = null;

// Cycle des statuts (même logique que matchs.js)
const STATUS_CYCLE = ['planifie', 'en_cours', 'termine'];
const STATUS_LABELS = {
    planifie: 'Planifié',
    en_cours: 'En jeu',
    termine: 'Terminé'
};

// ---------- Utilitaires ----------

function afficherMessage(elementId, texte, type = 'success') {
    const el = document.getElementById(elementId);
    el.textContent = texte;
    el.className = 'msg ' + type;
    setTimeout(() => { el.textContent = ''; el.className = 'msg'; }, 4000);
}

async function apiFetch(url, options = {}) {
    const res = await fetch(url, options);
    const data = await res.json();
    if (!res.ok || data.success === false) {
        throw new Error(data.message || 'Erreur inconnue');
    }
    return data;
}

// ---------- Chargement des catégories au démarrage ----------

const inputTournoiId = document.getElementById('input-tournoi-id');
const selectCategorie = document.getElementById('input-categorie');
const inputNom = document.getElementById('input-nom');
const inputNbEquipes = document.getElementById('input-nb-equipes');
const ordreEquipesPanel = document.getElementById('ordre-equipes-panel');
const msgCreation = document.getElementById('msg-creation');

async function chargerCategories() {
    const idTournoi = parseInt(inputTournoiId.value, 10);
    if (!idTournoi) return;

    try {
        const data = await apiFetch(`${API_BASE}/get_categories.php?id_tournoi=${idTournoi}`);

        selectCategorie.innerHTML = '<option value="">— Choisir une catégorie —</option>';
        data.categories.forEach(cat => {
            const opt = document.createElement('option');
            opt.value = cat.id_categorie;
            opt.textContent = cat.nom;
            selectCategorie.appendChild(opt);
        });

    } catch (err) {
        console.error('Erreur chargement catégories:', err.message);
    }
}

// ---------- Événement : sélection d'une catégorie ----------

selectCategorie.addEventListener('change', async () => {
    const idTournoi = parseInt(inputTournoiId.value, 10);
    const idCategorie = parseInt(selectCategorie.value, 10);

    selectedCategorieId = idCategorie || null;

    if (!idCategorie) {
        inputNom.value = '';
        inputNom.placeholder = 'Choisir une catégorie d\'abord';
        inputNbEquipes.value = '';
        inputNbEquipes.placeholder = '—';
        equipesOrdre = [];
        afficherOrdreEquipes();
        ordreEquipesPanel.classList.add('hidden');
        document.getElementById('btn-creer').disabled = false;
        return;
    }

    try {
        const checkData = await apiFetch(
            `${API_BASE}/check_categorie.php?id_tournoi=${idTournoi}&id_categorie=${idCategorie}`
        );

        if (checkData.existe) {
            afficherMessage(
                'msg-creation',
                `Une phase finale existe déjà pour cette catégorie (« ${checkData.phase.nom} »). Supprimez-la avant d'en créer une nouvelle.`,
                'error'
            );
            document.getElementById('btn-creer').disabled = true;

            inputNom.value = '';
            inputNbEquipes.value = '';
            equipesOrdre = [];
            afficherOrdreEquipes();
            ordreEquipesPanel.classList.add('hidden');
            return;
        } else {
            document.getElementById('btn-creer').disabled = false;
        }
    } catch (err) {
        console.error('Erreur vérification catégorie:', err.message);
    }

    const nomCat = selectCategorie.options[selectCategorie.selectedIndex].text;
    inputNom.value = 'Phase Finale - ' + nomCat;
    inputNom.readOnly = true;

    try {
        const data = await apiFetch(
            `${API_BASE}/get_equipes_categorie.php?id_tournoi=${idTournoi}&id_categorie=${idCategorie}`
        );

        equipesOrdre = data.equipes.map(e => ({
            id_equipe: e.id_equipe,
            id_categorie: e.id_categorie,
            id_poule: e.id_poule,
            id_tournoi: e.id_tournoi,
            nom: e.nom,
            rang_poule: e.rang_poule,
            victoires: e.victoires,
            diff_points: e.diff_points
        }));

        inputNbEquipes.value = equipesOrdre.length;
        inputNbEquipes.placeholder = equipesOrdre.length;

        afficherOrdreEquipes();
        ordreEquipesPanel.classList.remove('hidden');

        if (equipesOrdre.length < 2) {
            afficherMessage('msg-creation', 'Cette catégorie n\'a pas assez d\'équipes (minimum 2)', 'error');
        } else {
            afficherMessage('msg-creation', `${equipesOrdre.length} équipes chargées pour la catégorie « ${nomCat} »`, 'success');
        }

    } catch (err) {
        equipesOrdre = [];
        inputNbEquipes.value = '';
        afficherOrdreEquipes();
        ordreEquipesPanel.classList.add('hidden');
        afficherMessage('msg-creation', err.message, 'error');
    }
});

// ---------- Affichage de l'ordre de départ (drag & drop + boutons ▲▼) ----------

function afficherOrdreEquipes() {
    const container = document.getElementById('liste-ordre-equipes');
    container.innerHTML = '';

    equipesOrdre.forEach((equipe, index) => {
        const div = document.createElement('div');
        div.className = 'ordre-item';
        div.draggable = true;
        div.dataset.index = index;

        div.innerHTML = `
            <span class="drag-handle">☰</span>
            <span class="seed-num">#${index + 1}</span>
            <div class="equipe-nom-ordre">
                <strong>${equipe.nom}</strong>
                <span class="rang-info">
                    Poule ${equipe.id_poule ?? '-'} — Rang ${equipe.rang_poule ?? '-'}
                    ${equipe.victoires !== undefined ? ` — ${equipe.victoires} victoire(s)` : ''}
                </span>
            </div>
            <div class="ordre-actions">
                <button type="button" data-action="up" data-index="${index}" title="Monter">↑</button>
                <button type="button" data-action="down" data-index="${index}" title="Descendre">↓</button>
            </div>
        `;

        div.addEventListener('dragstart', e => {
            draggedIndex = index;
            div.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', String(index));
        });

        div.addEventListener('dragend', () => {
            div.classList.remove('dragging');
            draggedIndex = null;
        });

        div.addEventListener('dragover', e => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            div.classList.add('drag-over');
        });

        div.addEventListener('dragleave', () => {
            div.classList.remove('drag-over');
        });

        div.addEventListener('drop', e => {
            e.preventDefault();
            div.classList.remove('drag-over');
            const targetIndex = index;
            if (draggedIndex === null || draggedIndex === targetIndex) return;
            const [item] = equipesOrdre.splice(draggedIndex, 1);
            equipesOrdre.splice(targetIndex, 0, item);
            draggedIndex = null;
            afficherOrdreEquipes();
        });

        container.appendChild(div);
    });

    container.querySelectorAll('button[data-action]').forEach(btn => {
        btn.addEventListener('click', () => {
            const idx = parseInt(btn.dataset.index, 10);
            const action = btn.dataset.action;
            if (action === 'up' && idx > 0) {
                [equipesOrdre[idx - 1], equipesOrdre[idx]] = [equipesOrdre[idx], equipesOrdre[idx - 1]];
            } else if (action === 'down' && idx < equipesOrdre.length - 1) {
                [equipesOrdre[idx + 1], equipesOrdre[idx]] = [equipesOrdre[idx], equipesOrdre[idx + 1]];
            }
            afficherOrdreEquipes();
        });
    });
}

// ---------- Création d'une phase finale ----------

document.getElementById('form-creation').addEventListener('submit', async e => {
    e.preventDefault();

    const idTournoi = parseInt(inputTournoiId.value, 10);
    const idCategorie = parseInt(selectCategorie.value, 10);

    if (!idTournoi || !idCategorie) {
        afficherMessage('msg-creation', 'Veuillez sélectionner une catégorie', 'error');
        return;
    }

    if (equipesOrdre.length < 2) {
        afficherMessage('msg-creation', 'Il faut au moins 2 équipes pour créer une phase finale', 'error');
        return;
    }

    const payload = {
        id_tournoi: idTournoi,
        id_categorie: idCategorie,
        nom: inputNom.value.trim(),
        type_bracket: document.getElementById('input-type-bracket').value,
        nb_equipes: equipesOrdre.length,
        equipesSelectionnees: equipesOrdre
    };

    try {
        const data = await apiFetch(`${API_BASE}/creer.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });

        afficherMessage('msg-creation',
            `Phase finale créée ! (${data.nb_matchs} matchs, ${data.nbRounds} rounds)`,
            'success');

        chargerListePhases(idTournoi);
        ouvrirBracket(data.id_phase_finale);

    } catch (err) {
        afficherMessage('msg-creation', err.message, 'error');
    }
});

// ---------- Liste des phases finales ----------

document.getElementById('btn-rafraichir-liste').addEventListener('click', () => {
    chargerListePhases(parseInt(inputTournoiId.value, 10));
});

async function chargerListePhases(idTournoi) {
    const listeEl = document.getElementById('liste-phases');
    listeEl.innerHTML = '<li>Chargement...</li>';

    try {
        const data = await apiFetch(`${API_BASE}/liste.php?id_tournoi=${idTournoi}`);

        if (data.phases.length === 0) {
            listeEl.innerHTML = '<li>Aucune phase finale pour ce tournoi</li>';
            return;
        }

        listeEl.innerHTML = '';
        data.phases.forEach(phase => {
            const li = document.createElement('li');
            li.innerHTML = `
                <span>
                    <strong>${phase.nom}</strong>
                    — ${phase.type_bracket === 'classique' ? 'Classique' : 'Classement complet'}
                    — ${phase.nb_equipes} équipes (${phase.nb_equipes_arrondi} avec BYE)
                    — ${phase.statut}
                </span>
                <span>
                    <button data-id="${phase.id}" class="btn-ouvrir">Ouvrir</button>
                    <button data-id="${phase.id}" data-nom="${phase.nom}" class="btn-supprimer-phase">Supprimer</button>
                </span>
            `;
            li.querySelector('.btn-ouvrir').addEventListener('click', () => {
                ouvrirBracket(phase.id);
            });
            li.querySelector('.btn-supprimer-phase').addEventListener('click', () => {
                demanderSuppressionPhase(phase.id, phase.nom);
            });
            listeEl.appendChild(li);
        });

    } catch (err) {
        listeEl.innerHTML = `<li>Erreur: ${err.message}</li>`;
    }
}

// ---------- Suppression ----------

function demanderSuppressionPhase(id, nom) {
    currentPhaseFinaleIdASupprimer = id;
    document.getElementById('modal-suppression-info').textContent =
        `Êtes-vous sûr de vouloir supprimer la phase finale « ${nom} » ? Cette action est irréversible.`;
    document.getElementById('modal-suppression').classList.remove('hidden');
}

document.getElementById('btn-annuler-suppression').addEventListener('click', () => {
    document.getElementById('modal-suppression').classList.add('hidden');
    currentPhaseFinaleIdASupprimer = null;
});

document.getElementById('btn-confirmer-suppression').addEventListener('click', async () => {
    if (!currentPhaseFinaleIdASupprimer) return;

    try {
        await apiFetch(`${API_BASE}/supprimer.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_phase_finale: currentPhaseFinaleIdASupprimer }),
        });

        document.getElementById('modal-suppression').classList.add('hidden');

        if (currentPhaseFinaleId === currentPhaseFinaleIdASupprimer) {
            document.getElementById('section-bracket').classList.add('hidden');
            currentPhaseFinaleId = null;
        }

        chargerListePhases(parseInt(inputTournoiId.value, 10));
        currentPhaseFinaleIdASupprimer = null;

    } catch (err) {
        afficherMessage('msg-modal-suppression', err.message, 'error');
    }
});

// ---------- Bracket ----------

async function ouvrirBracket(idPhaseFinale) {
    currentPhaseFinaleId = idPhaseFinale;
    document.getElementById('section-bracket').classList.remove('hidden');

    try {
        const data = await apiFetch(`${API_BASE}/detail.php?id_phase_finale=${idPhaseFinale}`);

        document.getElementById('titre-bracket').textContent =
            `${data.phase.nom} — ${data.phase.type_bracket === 'classique' ? 'Bracket classique' : 'Classement complet'}`;

        afficherEquipes(data.equipes);
        afficherBracket(data.matchs, data.phase);

    } catch (err) {
        alert('Erreur chargement bracket: ' + err.message);
    }
}

// ---------- Équipes ----------

function afficherEquipes(equipes) {
    const container = document.getElementById('liste-equipes');
    container.innerHTML = '';

    equipes.forEach(equipe => {
        const div = document.createElement('div');
        div.className = 'equipe-item' + (equipe.is_bye ? ' bye' : '');

        const disabled = equipe.is_bye ? 'disabled' : '';
        const badge = equipe.id_equipe_originale ? '<small class="badge-auto">auto</small>' : '';

        div.innerHTML = `
            <small>Seed ${equipe.seed_position}</small>
            <input type="text" value="${equipe.nom_equipe}" data-equipe-id="${equipe.id}" ${disabled}>
            ${badge}
        `;
        container.appendChild(div);
    });

    container.querySelectorAll('input[data-equipe-id]:not([disabled])').forEach(input => {
        input.addEventListener('blur', async () => {
            const equipeId = parseInt(input.dataset.equipeId, 10);
            const nomEquipe = input.value.trim();
            if (!nomEquipe) return;

            try {
                await apiFetch(`${API_BASE}/modifier_equipe.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ equipe_id: equipeId, nom_equipe: nomEquipe }),
                });
                ouvrirBracket(currentPhaseFinaleId);
            } catch (err) {
                alert('Erreur mise à jour équipe: ' + err.message);
            }
        });
    });
}

// ---------- Affichage du bracket ----------

function afficherBracket(matchs, phase) {
    const container = document.getElementById('bracket-container');
    container.innerHTML = '';

    const rounds = {};
    matchs.forEach(m => {
        if (!rounds[m.round]) rounds[m.round] = {};
        if (!rounds[m.round][m.sub_group]) rounds[m.round][m.sub_group] = [];
        rounds[m.round][m.sub_group].push(m);
    });

    const roundKeys = Object.keys(rounds).sort((a, b) => a - b);

    roundKeys.forEach(roundKey => {
        const col = document.createElement('div');
        col.className = 'round-column';

        const titre = document.createElement('div');
        titre.className = 'round-title';
        const nbRounds = roundKeys.length;
        const revIdx = nbRounds - 1 - roundKeys.indexOf(roundKey);
        let label = '';
        if (revIdx === 0) label = 'Finale';
        else if (revIdx === 1) label = 'Demi-finales';
        else {
            const den = Math.pow(2, revIdx);
            label = revIdx === 2 ? 'Quart' : `1/${den}`;
        }
        titre.textContent = label;
        col.appendChild(titre);

        const subKeys = Object.keys(rounds[roundKey]).sort((a, b) => a - b);

        subKeys.forEach(subKey => {
            if (phase.type_bracket === 'classement_complet') {
                const subTitre = document.createElement('div');
                subTitre.className = 'sub-group-title';
                const skNum = Number(subKey);
                const range = calculerPlageClassement(Number(roundKey), skNum, phase.nb_equipes);
                const reelround = roundKeys.indexOf(roundKey) + 1;
                if (reelround > 1) {
                    subTitre.innerHTML = (skNum % 2 === 1)
                        ? 'Classement ' + range + '<br>Vainqueur match précédent'
                        : 'Classement ' + range + '<br>Perdant match précédent';
                } else {
                    subTitre.innerHTML = 'Classement ' + range;
                }
                col.appendChild(subTitre);
            }

            rounds[roundKey][subKey].forEach(match => {
                col.appendChild(creerMatchBox(match));
            });
        });

        container.appendChild(col);
    });
}

function creerMatchBox(match) {
    const box = document.createElement('div');
    box.className = 'match-box ' + match.statut;
    box.dataset.matchId = match.id;

    const nom1 = match.nom_equipe1 || (match.source_team1 || '???');
    const nom2 = match.nom_equipe2 || (match.source_team2 || '???');

    const classeTeam1 = match.winner_equipe_id
        ? (match.winner_equipe_id === match.equipe1_id ? 'winner' : 'loser') : '';
    const classeTeam2 = match.winner_equipe_id
        ? (match.winner_equipe_id === match.equipe2_id ? 'winner' : 'loser') : '';

    let classementHtml = '';
    if (match.classement_min && match.classement_max) {
        classementHtml = `<div class="classement-label">Classement ${match.classement_min}-${match.classement_max}</div>`;
    }

    const simuleBadge = match.statut === 'simule'
        ? '<div class="simule-badge">⏩ Simulé</div>' : '';

    // ── Statut de jeu (planifie / en_cours / termine) : indépendant du statut structurel du bracket ──
    const statutJeu = match.statut_match ?? 'planifie';
    const peutJouer = match.equipe1_id && match.equipe2_id;

    // Badge de statut cliquable (seulement si les 2 équipes sont connues)
    const statutBadgeHtml = peutJouer
        ? `<span class="status-badge status-badge-${statutJeu}"
                 data-match-id="${match.id}"
                 title="Cliquer pour changer le statut">
             ${STATUS_LABELS[statutJeu] ?? statutJeu}
           </span>`
        : '';

    // Terrain (éditable uniquement si les 2 équipes sont connues)
    const terrainHtml = peutJouer
        ? `<input type="number" min="1" class="terrain-input"
                  value="${match.terrain ?? ''}"
                  placeholder="Terrain"
                  data-match-id="${match.id}"
                  title="Terrain">`
        : '';

    const infosLigne = (statutBadgeHtml || terrainHtml)
        ? `<div class="match-infos-ligne" onclick="event.stopPropagation()">
                ${terrainHtml}
                ${statutBadgeHtml}
           </div>`
        : '';

    box.innerHTML = `
        <div class="match-code">${match.match_code}</div>
        ${simuleBadge}
        <div class="team-line ${classeTeam1}">
            <span>${nom1}</span>
            <span>${match.score1 !== null ? match.score1 : ''}</span>
        </div>
        <div class="team-line ${classeTeam2}">
            <span>${nom2}</span>
            <span>${match.score2 !== null ? match.score2 : ''}</span>
        </div>
        ${classementHtml}
        ${infosLigne}
    `;

    // Clic sur la boîte = ouvrir modale de score (sauf sur les infos)
    if (peutJouer) {
        box.addEventListener('click', () => {
            ouvrirModalScore(match, nom1, nom2);
        });

        // Clic sur le badge de statut = cycle rapide (sans ouvrir la modale)
        const badgeEl = box.querySelector('.status-badge');
        if (badgeEl) {
            badgeEl.addEventListener('click', (e) => {
                e.stopPropagation();
                cyclerStatutMatch(match.id, statutJeu, badgeEl);
            });
        }

        // Changement du terrain (au blur, sans ouvrir la modale)
        const terrainInput = box.querySelector('.terrain-input');
        if (terrainInput) {
            terrainInput.addEventListener('click', (e) => e.stopPropagation());
            terrainInput.addEventListener('blur', () => {
                sauvegarderTerrain(match.id, terrainInput.value);
            });
            terrainInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') terrainInput.blur();
            });
        }
    }

    return box;
}

// ---------- Cycle rapide du statut de jeu (planifie -> en_cours -> termine) ----------

async function cyclerStatutMatch(matchId, statutActuel, badgeEl) {
    const idxCycle = STATUS_CYCLE.indexOf(statutActuel);
    const suivant = STATUS_CYCLE[(idxCycle + 1) % STATUS_CYCLE.length];

    // Mise à jour optimiste de l'UI
    badgeEl.textContent = STATUS_LABELS[suivant];
    badgeEl.className = `status-badge status-badge-${suivant}`;
    badgeEl.dataset.matchId = matchId;

    try {
        await apiFetch(`${API_BASE}/maj_statut_match.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ match_id: matchId, statut_match: suivant }),
        });
    } catch (err) {
        alert('Erreur mise à jour du statut: ' + err.message);
        // Rollback visuel simple : on recharge le bracket
        ouvrirBracket(currentPhaseFinaleId);
    }
}

// ---------- Mise à jour du terrain ----------

async function sauvegarderTerrain(matchId, terrainValue) {
    try {
        await apiFetch(`${API_BASE}/maj_terrain_match.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ match_id: matchId, terrain: terrainValue || null }),
        });
    } catch (err) {
        alert('Erreur mise à jour du terrain: ' + err.message);
    }
}

// ---------- Modale de saisie de score ----------

function ouvrirModalScore(match, nom1, nom2) {
    currentMatchId = match.id;
    document.getElementById('modal-match-info').textContent = `${match.match_code} : ${nom1} vs ${nom2}`;
    document.getElementById('modal-score1').value = match.score1 ?? 0;
    document.getElementById('modal-score2').value = match.score2 ?? 0;

    // Champs supplémentaires dans la modale (statut + terrain), si présents dans le DOM
    const selectStatut = document.getElementById('modal-statut-match');
    if (selectStatut) selectStatut.value = match.statut_match ?? 'planifie';

    const inputTerrain = document.getElementById('modal-terrain');
    if (inputTerrain) inputTerrain.value = match.terrain ?? '';

    document.getElementById('modal-score').classList.remove('hidden');
}

document.getElementById('btn-annuler-score').addEventListener('click', () => {
    document.getElementById('modal-score').classList.add('hidden');
});

document.getElementById('btn-valider-score').addEventListener('click', async () => {
    const score1 = parseInt(document.getElementById('modal-score1').value, 10);
    const score2 = parseInt(document.getElementById('modal-score2').value, 10);
    const selectStatut = document.getElementById('modal-statut-match');

    if (score1 === score2 && selectStatut.value == 'termine') {
        afficherMessage('msg-modal', 'Les scores ne peuvent pas être égaux (pas de match nul)', 'error');
        return;
    }

    // const selectStatut = document.getElementById('modal-statut-match');
    const inputTerrain = document.getElementById('modal-terrain');

    const payload = {
        match_id: currentMatchId,
        score1,
        score2
    };

    if (selectStatut) payload.statut_match = selectStatut.value;
    if (inputTerrain) payload.terrain = inputTerrain.value || null;

    try {
        await apiFetch(`${API_BASE}/saisir_score.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });

        document.getElementById('modal-score').classList.add('hidden');
        ouvrirBracket(currentPhaseFinaleId);

    } catch (err) {
        afficherMessage('msg-modal', err.message, 'error');
    }
});

// ---------- Simulation ----------

document.getElementById('btn-simuler-rounds').addEventListener('click', async () => {
    if (!currentPhaseFinaleId) {
        afficherMessage('msg-simulation', 'Aucune phase finale ouverte', 'error');
        return;
    }

    const nbRounds = parseInt(document.getElementById('input-nb-rounds-simuler').value, 10);

    if (!nbRounds || nbRounds < 1) {
        afficherMessage('msg-simulation', 'Nombre de rounds invalide', 'error');
        return;
    }

    const confirmation = confirm(
        `Confirmer la simulation de ${nbRounds} round(s) ?\n` +
        `Les équipes avec le meilleur seed gagneront automatiquement (15-0).\n` +
        `Cette action est irréversible.`
    );
    if (!confirmation) return;

    try {
        const data = await apiFetch(`${API_BASE}/simuler_rounds.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_phase_finale: currentPhaseFinaleId, nbRounds }),
        });

        afficherMessage('msg-simulation',
            `${data.nb_matchs_simules} match(s) simulé(s) sur ${data.nbRounds_simules} round(s)`,
            'success');

        ouvrirBracket(currentPhaseFinaleId);

    } catch (err) {
        afficherMessage('msg-simulation', err.message, 'error');
    }
});

/**
 * Calcule la plage de classement pour un sub_group donné.
 */
function calculerPlageClassement(round, subKey, nbreTeam) {
    const nbBranches = Math.pow(2, round);
    const tailleGroupe = nbreTeam / nbBranches;
    const index = subKey - 1;
    const debut = Math.floor(index * tailleGroupe) + 1;
    const fin = Math.floor((index + 1) * tailleGroupe);
    return debut + ' - ' + fin;
}

// ---------- Initialisation au chargement ----------

(async () => {
    await chargerCategories();
    chargerListePhases(parseInt(inputTournoiId.value, 10));
})();