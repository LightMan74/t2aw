/**
 * feuilles_score.js
 * - Charge les matchs via les 2 API existantes (get_matchs.php / phase_finale/get_matchs_all.php)
 * - Aucun reload : tout passe via fetch + DOM
 * - Filtres écran + cases à cocher + bouton Imprimer
 * - Génère des feuilles A5 (1 par page) côté #zone-impression
 * - Nombre de sets piloté par nb_sets_js (1 ou 3) exposé depuis PHP
 * - Pas de colonne/grille service dans la feuille imprimée
 */

let matchsData = []; // fusion { source: 'poule'|'pf', ... }

const STATUS_LABELS = {
    planifie: 'Planifié',
    en_cours: 'En cours',
    termine: 'Terminé',
    annulee: 'Annulé'
};

// ---------- Helpers ----------
function escapeHtml(s) {
    if (s === null || s === undefined) return '';
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function getStatut(m) {
    return m.source === 'poule' ? (m.status ?? m.statut ?? 'planifie') : (m.statut_match ?? m.statut ?? 'planifie');
}

function getNomEquipe(m, slot) {
    // slot: 1|2 pour les deux sources
    if (m.source === 'poule') {
        return m['nom_equipe_' + slot] ?? m['id_equipe_' + slot] ?? '';
    }
    // phase finale : nom_equipe1 / nom_equipe2
    const k = slot === 1 ? 'nom_equipe1' : 'nom_equipe2';
    return m[k] ?? (slot === 1 ? m.source_team1 : m.source_team2) ?? '';
}

function getNumeroAffichage(m, index) {
    // numéro stable pour affichage utilisateur
    if (m.source === 'pf' && m.match_code) return m.match_code;
    return '#' + (index + 1);
}

function getContexte(m) {
    // pour la colonne "Poule / Phase" du tableau écran
    if (m.source === 'poule') {
        return 'Poule' + ' — ' + m.nom_poule;
    }
    return 'Phase finale';
}

function afficherMessage(texte, type) {
    const div = document.getElementById('message');
    if (!div) return;
    div.innerHTML = `<div class="msg ${type}">${escapeHtml(texte)}</div>`;
    setTimeout(() => { div.innerHTML = ''; }, 5000);
}

// ---------- Helpers pour le nombre de sets ----------
// nb_sets_js vient du PHP : entier 1 ou 3 (dérivé du paramètre `troissets`).
// 1 -> [1] ; 3 -> [1, 2, 3] ; toute autre valeur retombe sur [1, 2] par sécurité.
function getSetsFromNb(nb) {
    const n = Number(nb);
    if (n === 1) return [1];
    if (n === 3) return [1, 2, 3];
    return [1, 2];
}

// ---------- Chargement des matchs (réutilisation des API existantes) ----------
async function chargerMatchs() {
    const id_tournoi = document.getElementById('id_tournoi').value;
    if (!id_tournoi) {
        afficherMessage('Aucun tournoi sélectionné (id_tournoi manquant)', 'error');
        return;
    }
    try {
        const [resPoule, resPF] = await Promise.all([
            fetch(`api/get_matchs.php?id_tournoi=${encodeURIComponent(id_tournoi)}`),
            fetch(`api/phase_finale/get_matchs_all.php?id_tournoi=${encodeURIComponent(id_tournoi)}`)
        ]);
        const dataPoule = await resPoule.json();
        const dataPF = await resPF.json();

        matchsData = [];
        if (dataPoule.success && Array.isArray(dataPoule.matchs)) {
            dataPoule.matchs.forEach(m => matchsData.push(Object.assign({ source: 'poule' }, m)));
        }
        if (dataPF.success && Array.isArray(dataPF.matchs)) {
            dataPF.matchs.forEach(m => matchsData.push(Object.assign({ source: 'pf' }, m)));
        }

        // Peupler le filtre terrain avec les valeurs uniques
        peuplerFiltreTerrain();

        appliquerFiltres();
        afficherMessage('Matchs chargés : ' + matchsData.length, 'success');
    } catch (e) {
        console.error(e);
        afficherMessage('Erreur de chargement des matchs', 'error');
    }
}

function peuplerFiltreTerrain() {
    const sel = document.getElementById('filtre-terrain');
    if (!sel) return;
    const valeurs = Array.from(new Set(
        matchsData
            .map(m => m.terrain)
            .filter(t => t !== null && t !== undefined && t !== '')
    )).sort((a, b) => Number(a) - Number(b));
    const courant = sel.value;
    sel.innerHTML = '<option value="">Tous</option>' +
        valeurs.map(v => `<option value="${escapeHtml(v)}">${escapeHtml(v)}</option>`).join('');
    sel.value = courant;
}

// ---------- Rendu du tableau écran ----------
function appliquerFiltres() {
    const fSource = document.getElementById('filtre-source').value;
    const fStatut = document.getElementById('filtre-statut').value;
    const fTerrain = document.getElementById('filtre-terrain').value;

    const nbreCategories = (typeof nbre_categories_js !== 'undefined') ? nbre_categories_js : 1;

    const tbody = document.getElementById('corps-table');
    if (!tbody) return;

    const fragments = [];
    matchsData.forEach((m, idx) => {
        if (fSource && m.source !== fSource) return;
        const statutActuel = getStatut(m);
        if (fStatut && statutActuel !== fStatut) return;
        if (fTerrain && String(m.terrain ?? '') !== String(fTerrain)) return;

        const nom1 = getNomEquipe(m, 1);
        const nom2 = getNomEquipe(m, 2);
        const num = getNumeroAffichage(m, idx);
        const ctx = getContexte(m);
        const cat = (nbreCategories > 1) ? `<td>${escapeHtml(m.nom_categorie ?? '')}</td>` : '';
        const terrain = (m.terrain !== null && m.terrain !== undefined && m.terrain !== '') ? m.terrain : '';
        const heure = (m.heure_debut !== null && m.heure_debut !== undefined && m.heure_debut !== '') ? m.heure_debut : '';
        const labelStatut = STATUS_LABELS[statutActuel] ?? statutActuel;

        fragments.push(`
            <tr class="match-${statutActuel} ligne-selectionnable ligne-selectionnee" data-idx="${idx}">
                <td><input type="checkbox" class="check-match" data-idx="${idx}" checked></td>
                <td>${escapeHtml(num)}</td>
                <td>${m.source === 'poule' ? 'Poule' : 'Phase finale'}</td>
                ${cat}
                <td>${escapeHtml(ctx)}</td>
                <td>${escapeHtml(nom1)}</td>
                <td>vs</td>
                <td>${escapeHtml(nom2)}</td>
                <td>${escapeHtml(terrain)}</td>
                <td><span class="status-badge status-badge-${statutActuel}">${escapeHtml(labelStatut)}</span></td>
                <td>${escapeHtml(heure)}</td>
            </tr>
        `);
    });

    tbody.innerHTML = fragments.join('');
    if (fragments.length === 0) {
        tbody.innerHTML = `<tr><td colspan="11" style="text-align:center; opacity:0.6;">Aucun match ne correspond aux filtres.</td></tr>`;
    }
    majCompteur();
}

function majVisuelLigne(checkbox) {
    if (!checkbox) return;
    const ligne = checkbox.closest('tr');
    if (ligne) ligne.classList.toggle('ligne-selectionnee', checkbox.checked);
}

function majCompteur() {
    const compteur = document.getElementById('compteur-selection');
    if (!compteur) return;
    const cases = Array.from(document.querySelectorAll('#corps-table .check-match'));
    const selectionnees = cases.filter(cb => cb.checked).length;
    compteur.textContent = `${selectionnees} feuille(s) sélectionnée(s) sur ${cases.length}`;
}

function initClicLigne() {
    const tbody = document.getElementById('corps-table');
    if (!tbody || tbody.dataset.selectionEventsInitialises === 'true') return;
    tbody.dataset.selectionEventsInitialises = 'true';
    tbody.addEventListener('click', event => {
        const cible = event.target;
        if (cible.closest('a, button, input, select, textarea, label')) return;
        const ligne = cible.closest('tr');
        if (!ligne || !tbody.contains(ligne)) return;
        const checkbox = ligne.querySelector('.check-match');
        if (!checkbox || checkbox.disabled) return;
        checkbox.checked = !checkbox.checked;
        majVisuelLigne(checkbox);
        majCompteur();
    });
    tbody.addEventListener('change', event => {
        if (event.target.matches('.check-match')) {
            majVisuelLigne(event.target);
            majCompteur();
        }
    });
}

function toutSelectionner() {
    document.querySelectorAll('#corps-table .check-match').forEach(cb => {
        cb.checked = true;
        majVisuelLigne(cb);
    });
    majCompteur();
}
function toutDeselectionner() {
    document.querySelectorAll('#corps-table .check-match').forEach(cb => {
        cb.checked = false;
        majVisuelLigne(cb);
    });
    majCompteur();
}
function selectionnerNonTermines() {
    document.querySelectorAll('#corps-table .check-match').forEach(cb => {
        const idx = parseInt(cb.getAttribute('data-idx'), 10);
        const m = matchsData[idx];
        if (!m) return;
        const s = getStatut(m);
        cb.checked = (s !== 'termine' && s !== 'annulee');
        majVisuelLigne(cb);
    });
    majCompteur();
}

// ---------- Génération des feuilles A5 + impression ----------
function genererFeuillesA5() {
    const zone = document.getElementById('zone-impression');
    if (!zone) return 0;

    const coches = Array.from(document.querySelectorAll('.check-match'))
        .filter(cb => cb.checked)
        .map(cb => parseInt(cb.getAttribute('data-idx'), 10));

    if (coches.length === 0) {
        zone.innerHTML = '';
        return 0;
    }

    const html = coches.map(idx => {
        const m = matchsData[idx];
        return construireFeuilleA5(m, idx);
    }).join('');

    zone.innerHTML = html;
    return coches.length;
}

function construireFeuilleA5(m, idx) {
    const nom1 = getNomEquipe(m, 1);
    const nom2 = getNomEquipe(m, 2);
    const num = getNumeroAffichage(m, idx);
    const terrain = (m.terrain !== null && m.terrain !== undefined && m.terrain !== '') ? m.terrain : '—';
    const heure = (m.heure_debut !== null && m.heure_debut !== undefined && m.heure_debut !== '') ? m.heure_debut : '—';
    const contexte = getContexte(m);
    const categorie = m.nom_categorie ? m.nom_categorie : '';
    const tournoi = (typeof tournoi_nom_js !== 'undefined' && tournoi_nom_js) ? tournoi_nom_js : ('Tournoi #' + ((typeof id_tournoi_js !== 'undefined') ? id_tournoi_js : ''));
    // Nombre de sets : nb_sets_js est un entier 1 ou 3 exposé par le PHP depuis `parametre.troissets`.
    const nbSets = (typeof nb_sets_js !== 'undefined') ? Number(nb_sets_js) : 3;
    const ptsnbSets = (nbSets > 1) ? 21 : 105;
    const setnbSets = (nbSets > 1) ? 3 : 1;
    const sets = getSetsFromNb(nbSets);
    // Grille service : 21 cases (0 à 21) - on en affiche 21, numérotées
    const casesService = (equipeLabel) => {
        // let html = `<div style="text-align:center; font-size:8pt; margin-bottom:1mm;">${escapeHtml(equipeLabel)}</div>`;
        let html = ``;
        for (let j = 1; j <= setnbSets; j++) {
            html += `<div style="text-align:center; font-size:8pt; margin-bottom:1mm;">SET ${j} - ${escapeHtml(equipeLabel)}</div>`;
            html += '<div class="fs-service">';
            for (let i = 1; i <= ptsnbSets; i++) {
                html += `<div class="case">${i}</div>`;
            }
            html += '</div>';
        }
        return html;
    };
    return `
    <section class="feuille-score">
        <header class="fs-entete">
            <div class="fs-tournoi">${escapeHtml(tournoi)}<br>
                <span class="fs-contexte">${categorie} — ${escapeHtml(contexte)}</span>
            </div>
            <div class="fs-meta">
                <strong>Match ${escapeHtml(num)}</strong><br>
                Terrain : <strong>${escapeHtml(terrain)}</strong><br>
                Heure prévue : <strong>${escapeHtml(heure)}</strong>
            </div>
        </header>

        <div class="fs-equipes">
            <div class="fs-equipe equipe-a">Équipe A<br><span class="fs-equipe-nom">${escapeHtml(nom1)}</span></div>
            <div class="fs-vs">vs</div>
            <div class="fs-equipe equipe-b">Équipe B<br><span class="fs-equipe-nom">${escapeHtml(nom2)}</span></div>
        </div>

        <table class="fs-tableau">
            <thead>
                <tr>
                    <th class="col-set">${(nbSets > 1) ? 'Set' : ''}</th>
                    <th class="col-points">Score Équipe A</th>
                    <th class="col-points">Score Équipe B</th>
                </tr>
            </thead>
            <tbody>
                ${sets.map(s => `
                <tr>
                    <th class="col-set">${(nbSets > 1) ? 'Set ' + s : 'Set'}</th>
                    <td class="col-points"><span class="fs-cell-line">____ points</span></td>
                    <td class="col-points"><span class="fs-cell-line">____ points</span></td>
                </tr>
                `).join('')}
            </tbody>
        </table>

        <div class="fs-vainqueur">
            <strong>Vainqueur :</strong>
            <label><input type="checkbox"> Équipe A</label>
            <label><input type="checkbox"> Équipe B</label>
            <label><input type="checkbox"> Forfait / Annulé</label>
        </div>
     <table class="fs-tableau">
            <tbody>
                <tr>
                    <td>${casesService('A')}</td>
                    <td>${casesService('B')}</td>
                </tr>
            </tbody>
        </table>
    </section>

    `;
}

function lancerImpression() {
    const nb = genererFeuillesA5();
    if (nb === 0) {
        afficherMessage('Aucune feuille sélectionnée', 'error');
        return;
    }
    afficherMessage('Impression de ' + nb + ' feuille(s) A5', 'success');
    // Laisse le DOM se mettre à jour avant print()
    setTimeout(() => window.print(), 50);
}

// ---------- Auto-chargement à l'ouverture ----------
window.addEventListener('DOMContentLoaded', () => {
    initClicLigne();
    if (typeof id_tournoi_js !== 'undefined' && id_tournoi_js && Number(id_tournoi_js) > 0) {
        chargerMatchs();
    } else {
        afficherMessage('Aucun id_tournoi dans l\'URL. Ajoutez ?id_tournoi=…', 'error');
    }
});

/* CSS écran minimal suggéré :
@media screen {
    .ligne-selectionnable { cursor: pointer; opacity: .45; }
    .ligne-selectionnee { opacity: 1; background: #e3f2fd; outline: 2px solid #1976d2; }
}
*/
