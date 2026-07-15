// app.js

const API_BASE = 'api/phase_finale';

let currentPhaseFinaleId = null;
let currentMatchId = null;
let currentPhaseFinaleIdASupprimer = null;

// Liste des équipes sélectionnées pour l'ordre de départ
let equipesOrdre = []; // [{id_equipe, nom}]

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

// Liste des équipes sélectionnées pour l'ordre de départ
// let equipesOrdre = []; // [{id, id_equipe, id_categorie, id_poule, nom}]

// ---------- Chargement des équipes réelles du tournoi ----------

document.getElementById('btn-charger-equipes').addEventListener('click', async () => {
    const idTournoi = parseInt(document.getElementById('input-tournoi-id').value, 10);

    if (!idTournoi) {
        afficherMessage('msg-creation', 'Veuillez indiquer un ID de tournoi', 'error');
        return;
    }

    try {
        const data = await apiFetch(`${API_BASE}/equipes_tournoi.php?id_tournoi=${idTournoi}`);

        // Stocker les équipes avec TOUS leurs paramètres
        equipesOrdre = data.equipes.map(e => ({
            id_equipe: e.id_equipe,
            id_categorie: e.id_categorie,
            id_poule: e.id_poule,
            id_tournoi: e.id_tournoi,
            nom: e.nom
        }));

        // Mettre à jour automatiquement le nombre d'équipes
        document.getElementById('input-nb-equipes').value = equipesOrdre.length;

        afficherOrdreEquipes();
        document.getElementById('ordre-equipes-panel').classList.remove('hidden');

        afficherMessage('msg-creation', `${equipesOrdre.length} équipes chargées`, 'success');
    } catch (err) {
        afficherMessage('msg-creation', err.message, 'error');
    }
});

function afficherOrdreEquipes() {
    const ul = document.getElementById('liste-ordre-equipes');
    ul.innerHTML = '';

    equipesOrdre.forEach((equipe, index) => {
        const li = document.createElement('li');
        li.innerHTML = `
            <span><span class="seed-num">#${index + 1}</span> ${equipe.nom}</span>
            <span class="ordre-actions">
                <button type="button" data-action="up" data-index="${index}">↑</button>
                <button type="button" data-action="down" data-index="${index}">↓</button>
            </span>
        `;
        ul.appendChild(li);
    });

    ul.querySelectorAll('button[data-action]').forEach(btn => {
        btn.addEventListener('click', () => {
            const index = parseInt(btn.dataset.index, 10);
            const action = btn.dataset.action;

            if (action === 'up' && index > 0) {
                [equipesOrdre[index - 1], equipesOrdre[index]] =
                    [equipesOrdre[index], equipesOrdre[index - 1]];
            } else if (action === 'down' && index < equipesOrdre.length - 1) {
                [equipesOrdre[index + 1], equipesOrdre[index]] =
                    [equipesOrdre[index], equipesOrdre[index + 1]];
            }

            afficherOrdreEquipes();
        });
    });
}

// ---------- Création d'une phase finale ----------

document.getElementById('form-creation').addEventListener('submit', async (e) => {
    e.preventDefault();

    const payload = {
        id_tournoi: parseInt(document.getElementById('input-tournoi-id').value, 10),
        nom: document.getElementById('input-nom').value.trim(),
        type_bracket: document.getElementById('input-type-bracket').value,
        nb_equipes: parseInt(document.getElementById('input-nb-equipes').value, 10),
        equipesSelectionnees: equipesOrdre // Envoyer toutes les données des équipes
    };

    try {
        const data = await apiFetch(`${API_BASE}/creer.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });

        afficherMessage('msg-creation',
            `Phase finale créée ! (${data.nb_matchs} matchs, ${data.nb_rounds} rounds)`,
            'success');

        chargerListePhases(payload.id_tournoi);
        ouvrirBracket(data.id_phase_finale);

    } catch (err) {
        afficherMessage('msg-creation', err.message, 'error');
    }
});

// ---------- Liste des phases finales ----------

document.getElementById('btn-rafraichir-liste').addEventListener('click', () => {
    const tournoiId = parseInt(document.getElementById('input-tournoi-id').value, 10);
    chargerListePhases(tournoiId);
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

// Charger la liste au démarrage
chargerListePhases(parseInt(document.getElementById('input-tournoi-id').value, 10));

// ---------- Suppression d'une phase finale ----------

function demanderSuppressionPhase(idPhaseFinale, nom) {
    currentPhaseFinaleIdASupprimer = idPhaseFinale;
    document.getElementById('modal-suppression-info').textContent =
        `Êtes-vous sûr de vouloir supprimer la phase finale "${nom}" ? Cette action est irréversible.`;
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

        // Si la phase supprimée était affichée dans le bracket, on masque la section
        if (currentPhaseFinaleId === currentPhaseFinaleIdASupprimer) {
            document.getElementById('section-bracket').classList.add('hidden');
            currentPhaseFinaleId = null;
        }

        const idTournoi = parseInt(document.getElementById('input-tournoi-id').value, 10);
        chargerListePhases(idTournoi);

        currentPhaseFinaleIdASupprimer = null;

    } catch (err) {
        afficherMessage('msg-modal-suppression', err.message, 'error');
    }
});

// ---------- Ouverture / affichage du bracket ----------

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

// ---------- Affichage / édition des équipes ----------

function afficherEquipes(equipes) {
    const container = document.getElementById('liste-equipes');
    container.innerHTML = '';

    equipes.forEach(equipe => {
        const div = document.createElement('div');
        div.className = 'equipe-item' + (equipe.is_bye ? ' bye' : '');

        // Si liée à une équipe réelle -> nom automatique (lecture seule)
        // Si BYE ou équipe temporaire -> éditable
        const estLiee = !!equipe.id_equipe_originale;
        const disabled = equipe.is_bye || estLiee ? 'disabled' : '';
        const badge = estLiee ? '<small class="badge-auto">auto</small>' : '';

        div.innerHTML = `
            <small>Seed ${equipe.seed_position}</small>
            <input type="text" value="${equipe.nom_equipe}" data-equipe-id="${equipe.id}" ${disabled}>
            ${badge}
        `;
        container.appendChild(div);
    });

    // Sauvegarde au blur uniquement pour les équipes non liées et non-BYE
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

// ---------- Affichage du bracket (matchs groupés par round/sub) ----------

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
        titre.textContent = 'Round ' + roundKey;
        col.appendChild(titre);

        const subKeys = Object.keys(rounds[roundKey]).sort((a, b) => a - b);

        subKeys.forEach(subKey => {
            if (phase.type_bracket === 'classement_complet') {
                const subTitre = document.createElement('div');
                subTitre.className = 'sub-group-title';
                subTitre.textContent = 'Sub ' + subKey;
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
        ? (match.winner_equipe_id === match.equipe1_id ? 'winner' : 'loser')
        : '';
    const classeTeam2 = match.winner_equipe_id
        ? (match.winner_equipe_id === match.equipe2_id ? 'winner' : 'loser')
        : '';

    let classementHtml = '';
    if (match.classement_min && match.classement_max) {
        classementHtml = `<div class="classement-label">Classement ${match.classement_min}-${match.classement_max}</div>`;
    }

    box.innerHTML = `
        <div class="match-code">${match.match_code}</div>
        <div class="team-line ${classeTeam1}">
            <span>${nom1}</span>
            <span>${match.score1 !== null ? match.score1 : ''}</span>
        </div>
        <div class="team-line ${classeTeam2}">
            <span>${nom2}</span>
            <span>${match.score2 !== null ? match.score2 : ''}</span>
        </div>
        ${classementHtml}
    `;

    if (match.equipe1_id && match.equipe2_id) {
        box.addEventListener('click', () => {
            ouvrirModalScore(match, nom1, nom2);
        });
    }

    return box;
}

// ---------- Modale de saisie de score ----------

function ouvrirModalScore(match, nom1, nom2) {
    currentMatchId = match.id;

    document.getElementById('modal-match-info').textContent = `${match.match_code} : ${nom1} vs ${nom2}`;
    document.getElementById('modal-score1').value = match.score1 ?? 0;
    document.getElementById('modal-score2').value = match.score2 ?? 0;
    document.getElementById('modal-score').classList.remove('hidden');
}

document.getElementById('btn-annuler-score').addEventListener('click', () => {
    document.getElementById('modal-score').classList.add('hidden');
});

document.getElementById('btn-valider-score').addEventListener('click', async () => {
    const score1 = parseInt(document.getElementById('modal-score1').value, 10);
    const score2 = parseInt(document.getElementById('modal-score2').value, 10);

    if (score1 === score2) {
        afficherMessage('msg-modal', 'Les scores ne peuvent pas être égaux (pas de match nul)', 'error');
        return;
    }

    try {
        await apiFetch(`${API_BASE}/saisir_score.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ match_id: currentMatchId, score1, score2 }),
        });

        document.getElementById('modal-score').classList.add('hidden');
        ouvrirBracket(currentPhaseFinaleId);

    } catch (err) {
        afficherMessage('msg-modal', err.message, 'error');
    }
});