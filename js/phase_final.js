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
const STATUS_CYCLE = ['planifie', 'en_cours'];
const STATUS_LABELS = {
    planifie: 'Planifié',
    en_cours: 'En jeu'
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
    box.dataset.round = match.round;
    box.dataset.matchNum = match.match_num;
    box.dataset.terrain = match.terrain ?? '';

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
                 data-statutJeu="${statutJeu}"
                 title="Cliquer pour changer le statut">
             ${STATUS_LABELS[statutJeu] ?? statutJeu}
           </span>`
        : '';

    // Terrain (toujours éditable, même si les équipes ne sont pas encore connues)
    const terrainHtml = `<input type="number" min="1" class="terrain-input"
                  value="${match.terrain ?? ''}"
                  placeholder="Terrain"
                  data-match-id="${match.id}"
                  title="Terrain">`;

    // Heure de début + bouton "maintenant" (toujours visible)
    const heureHtml = `<div class="heure-ligne">
                <input type="time" class="hdebut-input"
                       value="${match.heure_debut ?? ''}"
                       data-match-id="${match.id}"
                       title="Heure de début">
                <button type="button" class="btn-heure-actuelle"
                        data-match-id="${match.id}"
                        title="Mettre l'heure actuelle et décaler les suivants">🕐</button>
           </div>`;

    const infosLigne = `<div class="match-infos-ligne" onclick="event.stopPropagation()">
                <div class="ligne1">
                    ${terrainHtml}
                    ${statutBadgeHtml}
                </div>
                ${heureHtml}
           </div>`;

    const { score1aff, score2aff } = filtrerScores(match.score1, match.score2);

    box.innerHTML = `
        <div class="match-code">${match.match_code}</div>
        ${simuleBadge}
        <div class="team-line ${classeTeam1}">
            <span>${nom1}</span>
            <span>${score1aff}</span>
        </div>
        <div class="team-line ${classeTeam2}">
            <span>${nom2}</span>
            <span>${score2aff}</span>
        </div>
        ${classementHtml}
        ${infosLigne}
    `;

    // Clic sur la boîte = ouvrir modale de score (seulement si les 2 équipes sont connues)
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
    }

    // Changement du terrain (au blur, sans ouvrir la modale) — toujours actif
    const terrainInput = box.querySelector('.terrain-input');
    if (terrainInput) {
        terrainInput.addEventListener('click', (e) => e.stopPropagation());
        terrainInput.addEventListener('blur', () => {
            sauvegarderTerrain(match.id, terrainInput.value);
        });
        terrainInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') terrainInput.blur();
        });
        terrainInput.addEventListener('input', () => {
            box.dataset.terrain = terrainInput.value;
        });
    }

    // Heure de début (au blur, sans ouvrir la modale) — toujours actif
    const hdebutInput = box.querySelector('.hdebut-input');
    if (hdebutInput) {
        hdebutInput.addEventListener('click', (e) => e.stopPropagation());
        hdebutInput.addEventListener('blur', () => {
            sauvegarderHeureDebutPF(match.id, hdebutInput.value);
        });
        hdebutInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') hdebutInput.blur();
        });
    }

    // Bouton "heure actuelle" avec décalage des matchs suivants (même terrain) — toujours actif
    const btnHeureActuelle = box.querySelector('.btn-heure-actuelle');
    if (btnHeureActuelle) {
        btnHeureActuelle.addEventListener('click', (e) => {
            e.stopPropagation();
            mettreHeureActuellePF(match.id, btnHeureActuelle);
        });
    }

    return box;
}
function toggleHeureManuellePF() {
    const checkbox = document.getElementById('heureManuelleCheckboxPF');
    const input = document.getElementById('heureManuelleInputPF');
    if (!checkbox || !input) return;

    if (checkbox.checked) {
        input.style.display = 'inline-block';
        // Pré-remplir avec l'heure actuelle par défaut
        input.value = formatHeureMinutePF(new Date());
    } else {
        input.style.display = 'none';
    }
}

function getHeureReferencePF() {
    const checkbox = document.getElementById('heureManuelleCheckboxPF');
    const input = document.getElementById('heureManuelleInputPF');

    if (checkbox && checkbox.checked && input && input.value) {
        return input.value;
    }
    return formatHeureMinutePF(new Date());
}



// ---------- Cycle rapide du statut de jeu (planifie -> en_cours -> termine) ----------

async function cyclerStatutMatch(matchId, statutActuel, badgeEl) {
    statutActuel = badgeEl.getAttribute('data-statutJeu');
    const idxCycle = STATUS_CYCLE.indexOf(statutActuel);
    const suivant = STATUS_CYCLE[(idxCycle + 1) % STATUS_CYCLE.length];
    // console.log(idxCycle + " " + suivant);
    // Mise à jour optimiste de l'UI
    badgeEl.setAttribute('data-statutJeu', suivant);
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

let currentMatchNoms = { nom1: '', nom2: '' };

function ouvrirModalScore(match, nom1, nom2) {
    currentMatchId = match.id;
    currentMatchNoms = { nom1, nom2 };

    document.getElementById('modal-match-info').textContent = `${match.match_code} : ${nom1} vs ${nom2}`;

    // Mise à jour des labels avec les noms des équipes 
    document.getElementById('label-equipe1').textContent = nom1;
    document.getElementById('label-equipe2').textContent = nom2;

    // document.getElementById('modal-score1').value = match.score1 ?? 0;
    // document.getElementById('modal-score2').value = match.score2 ?? 0;

    const scoresEquipe1 = String(match.score1 ?? '0*0*0').split('*');
    const scoresEquipe2 = String(match.score2 ?? '0*0*0').split('*');

    const s1set1 = scoresEquipe1[0] ?? 0;
    const s1set2 = scoresEquipe1[1] ?? 0;
    const s1set3 = scoresEquipe1[2] ?? 0;
    const s2set1 = scoresEquipe2[0] ?? 0;
    const s2set2 = scoresEquipe2[1] ?? 0;
    const s2set3 = scoresEquipe2[2] ?? 0;
    document.getElementById('modal-score1s1').value = s1set1;
    document.getElementById('modal-score1s2').value = s1set2;
    document.getElementById('modal-score1s3').value = s1set3;
    document.getElementById('modal-score2s1').value = s2set1;
    document.getElementById('modal-score2s2').value = s2set2;
    document.getElementById('modal-score2s3').value = s2set3;


    // const selectStatut = document.getElementById('modal-statut-match');
    const selectStatut = "termine";
    if (selectStatut) selectStatut.value = match.statut_match ?? 'planifie';

    const inputTerrain = document.getElementById('modal-terrain');
    if (inputTerrain) inputTerrain.value = match.terrain ?? '';

    document.getElementById('modal-score').classList.remove('hidden');
}

document.getElementById('btn-annuler-score').addEventListener('click', () => {
    document.getElementById('modal-score').classList.add('hidden');
});

document.getElementById('btn-valider-score').addEventListener('click', () => {
    const score1 = parseInt(document.getElementById('modal-score1s1').value, 10) + parseInt(document.getElementById('modal-score1s2').value, 10) + parseInt(document.getElementById('modal-score1s3').value, 10);
    const score2 = parseInt(document.getElementById('modal-score2s1').value, 10) + parseInt(document.getElementById('modal-score2s2').value, 10) + parseInt(document.getElementById('modal-score2s3').value, 10);
    const selectStatut = document.getElementById('modal-statut-match');

    if (isNaN(score1) || isNaN(score2)) {
        afficherMessage('msg-modal', 'Veuillez saisir des scores valides', 'error');
        return;
    }

    if (score1 === score2 && selectStatut.value === 'termine') {
        afficherMessage('msg-modal', 'Les scores ne peuvent pas être égaux (pas de match nul)', 'error');
        return;
    }

    console.log(selectStatut.value);

    // Si le match est marqué comme terminé, on demande confirmation du vainqueur
    if (selectStatut.value === 'termine') {
        const gagnant = score1 > score2 ? currentMatchNoms.nom1 : currentMatchNoms.nom2;
        const perdant = score1 > score2 ? currentMatchNoms.nom2 : currentMatchNoms.nom1;

        document.getElementById('modal-confirmation-texte').innerHTML =
            `< strong > ${currentMatchNoms.nom1}</strong > vs < strong > ${currentMatchNoms.nom2}</strong > <br><br>` +
            `🏆 <strong>${gagnant}</strong> gagne (${score1} - ${score2}) contre ${perdant}`;

        document.getElementById('modal-confirmation-vainqueur').classList.remove('hidden');
    } else {
        // Pas de confirmation nécessaire si le match n'est pas "terminé"
        validerScoreFinal(score1, score2);
    }
});

// ---------- Confirmation du vainqueur ----------

document.getElementById('btn-annuler-vainqueur').addEventListener('click', () => {
    document.getElementById('modal-confirmation-vainqueur').classList.add('hidden');
});

document.getElementById('btn-confirmer-vainqueur').addEventListener('click', async () => {
    document.getElementById('modal-confirmation-vainqueur').classList.add('hidden');

    // const score1 = parseInt(document.getElementById('modal-score1').value, 10);
    // const score2 = parseInt(document.getElementById('modal-score2').value, 10);

    const score1 = [
        document.getElementById(`modal-score1s1`)?.value ?? 0,
        document.getElementById(`modal-score1s2`)?.value ?? 0,
        document.getElementById(`modal-score1s3`)?.value ?? 0,
    ].join('*');

    const score2 = [
        document.getElementById(`modal-score2s1`)?.value ?? 0,
        document.getElementById(`modal-score2s2`)?.value ?? 0,
        document.getElementById(`modal-score2s3`)?.value ?? 0,
    ].join('*');

    await validerScoreFinal(score1, score2);
});

// ---------- Envoi effectif du score ----------

async function validerScoreFinal(score1, score2) {
    const selectStatut = document.getElementById('modal-statut-match');
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
}

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

// Fonction utilitaire pour filtrer les sets où les deux scores sont à 0
function filtrerScores(score1, score2) {
    if (score1 === null || score2 === null) {
        return {
            score1aff: score1 !== null ? score1.split('*').join(' | ') : '',
            score2aff: score2 !== null ? score2.split('*').join(' | ') : ''
        };
    }

    const sets1 = score1.split('*');
    const sets2 = score2.split('*');

    const filtres1 = [];
    const filtres2 = [];

    sets1.forEach((s1, i) => {
        const s2 = sets2[i] ?? '';
        // On ignore ce set si les deux valeurs sont 0
        if (s1 === '0' && s2 === '0') return;
        filtres1.push(s1);
        filtres2.push(s2);
    });

    return {
        score1aff: filtres1.join(' | '),
        score2aff: filtres2.join(' | ')
    };
}

// Variable globale pour le temps de match
let tempsDeMatchPF = null;
const terrainInput = null;

async function chargerTempsDeMatchPF() {
    const idTournoi = parseInt(inputTournoiId.value, 10);
    try {
        const res = await fetch(`api/get_parametres.php?id_tournoi=${idTournoi}`);
        const data = await res.json();
        if (data.success) {
            tempsDeMatchPF = parseInt(data.temps_de_match, 10) || 20;
        }
    } catch (err) {
        console.error('Erreur chargement paramètres', err);
        tempsDeMatchPF = 20;
    }
}

function formatHeureMinutePF(date) {
    const h = String(date.getHours()).padStart(2, '0');
    const m = String(date.getMinutes()).padStart(2, '0');
    return `${h}:${m}`;
}

function ajouterMinutesPF(heureStr, minutesAAjouter) {
    const [h, m] = heureStr.split(':').map(Number);
    const date = new Date();
    date.setHours(h, m, 0, 0);
    date.setMinutes(date.getMinutes() + minutesAAjouter);
    return formatHeureMinutePF(date);
}

// ---------- Mise à jour de l'heure de début ----------

async function sauvegarderHeureDebutPF(matchId, heureValue) {
    try {
        await apiFetch(`${API_BASE}/maj_heure_match.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ match_id: matchId, heure_debut: heureValue || null }),
        });
    } catch (err) {
        alert('Erreur mise à jour de l\'heure: ' + err.message);
    }
}

// ---------- Bouton "heure actuelle" + décalage des matchs suivants du même terrain ----------

async function mettreHeureActuellePF(matchId, btnEl) {
    if (tempsDeMatchPF === null) {
        await chargerTempsDeMatchPF();
    }

    const box = btnEl.closest('.match-box');
    if (!box) return;

    const hdebutInput = box.querySelector('.hdebut-input');
    const terrainInput = box.querySelector('.terrain-input');

    const terrain = terrainInput?.value ?? '';
    if (!terrain) {
        afficherMessage('msg-simulation', 'Aucun terrain défini pour ce match', 'error');
        return;
    }

    // 1. Mettre l'heure de référence (actuelle ou manuelle) sur le match cliqué
    const heureActuelle = getHeureReferencePF();

    if (hdebutInput) {
        hdebutInput.value = heureActuelle;
        sauvegarderHeureDebutPF(matchId, heureActuelle);
    }

    // 2. Récupérer tous les matchs du même terrain (lecture live du DOM)
    const toutesLesBoxes = Array.from(document.querySelectorAll('.match-box'));

    const matchsMemeTerrain = toutesLesBoxes
        .map(b => {
            const hInput = b.querySelector('.hdebut-input');
            const tInput = b.querySelector('.terrain-input');
            const statutEl = b.querySelector('.status-badge');
            return {
                box: b,
                matchId: parseInt(b.dataset.matchId, 10),
                terrain: tInput?.value ?? '',
                hInput,
                statut: statutEl?.getAttribute('data-statutJeu') ?? 'planifie',
                round: parseInt(b.dataset.round, 10) || 0,
                matchNum: parseInt(b.dataset.matchNum, 10) || 0
            };
        })
        .filter(m => m.hInput && String(m.terrain) === String(terrain))
        .sort((a, b) => (a.round - b.round) || (a.matchNum - b.matchNum));

    const positionActuelle = matchsMemeTerrain.findIndex(m => m.matchId === matchId);
    if (positionActuelle === -1) return;

    let heurePrecedente = heureActuelle;

    for (let i = positionActuelle + 1; i < matchsMemeTerrain.length; i++) {
        const m = matchsMemeTerrain[i];

        if (m.statut === 'en_cours' || m.statut === 'termine') {
            if (m.hInput.value) heurePrecedente = m.hInput.value;
            continue;
        }

        const nouvelleHeure = ajouterMinutesPF(heurePrecedente, tempsDeMatchPF);
        m.hInput.value = nouvelleHeure;
        sauvegarderHeureDebutPF(m.matchId, nouvelleHeure);
        heurePrecedente = nouvelleHeure;
    }

    afficherMessage('msg-simulation', 'Heures mises à jour ✓', 'success');
}

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