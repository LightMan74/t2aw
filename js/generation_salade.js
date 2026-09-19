/**
 * generation_salade.js
 * ------------------------------------------------------------------
 * Génération / affichage / sauvegarde des tours SALADE
 * (matchs à 4 équipes).
 *
 * Architecture :
 *   - `genererTournoiSalade()` :
 *       1) fetch des PARAMÈTRES du tournoi (nb terrains) ;
 *       2) fetch des ÉQUIPES de la poule active ;
 *       3) GÉNÉRATION DES MATCHS EN JS (shuffle + assignation
 *          de terrain pendant le shuffle, round-robin) ;
 *       4) affichage ;
 *       5) appel de `sauvegarderMatchsSalade()` qui envoie
 *          l'array au back-end.
 *
 *   - `chargerMatchsSalade()` : conserve le chargement initial
 *     (get_parametres + get_matchs) et l'affichage.
 *
 *   - `creerElementMatch()` : rendu unifié 2 équipes / 4 équipes
 *     avec helper `🥗` salade.
 *
 *   - `afficherMessage()` : helper d'affichage safe (ne casse pas
 *     la version définie dans ordre_matchs.js si elle existe déjà).
 */

let idCategorieActive = 1;
let idPouleSaladeActive = 1;
// let matchsActuelssalade = [];

function _getIdTournoi() {
    const el = document.getElementById('id_tournoi');
    if (el && el.value) return el.value;
    return (typeof id_tournoi_js !== 'undefined') ? id_tournoi_js : null;
}

/* =====================================================================
   AFFICHAGE MESSAGES (safe wrapper)
   ===================================================================== */
function afficherMessage(texte, type) {
    // Si une version globale plus complète existe déjà (ordre_matchs.js),
    // on l'utilise pour ne pas casser l'UX existante.
    if (typeof window.afficherMessage === 'function' && window.afficherMessage !== afficherMessage) {
        window.afficherMessage(texte, type);
        return;
    }
    const div = document.getElementById('message');
    if (!div) {
        console.log(`[${type}] ${texte}`);
        return;
    }
    div.innerHTML = `<div class="msg ${type}">${texte}</div>`;
    setTimeout(() => { div.innerHTML = ''; }, 5000);
}


/* =====================================================================
   CHARGEMENT INITIAL / REFRESH DE LA LISTE DES MATCHS
   ---------------------------------------------------------------------
   - Récupère d'abord le nombre de terrains (api/get_parametres.php).
   - Puis charge tous les matchs du tournoi (api/get_matchs.php).
   - Déclenche enfin l'affichage complet de la liste.
   ===================================================================== */
function chargerMatchsSalade() {
    const idTournoi = _getIdTournoi();
    if (!idTournoi) {
        afficherMessage("Identifiant tournoi manquant (chargerMatchsSalade).", 'error');
        return;
    }

    // 1) Récupération du nombre de terrains.
    const formDataParam = new FormData();
    formDataParam.append('id_tournoi', idTournoi);

    fetch('api/get_parametres.php', { method: 'POST', body: formDataParam })
        .then(res => res.json())
        .then(dataParam => {
            const nb = parseInt(
                (dataParam && (dataParam.nb_terrains || dataParam.nbre_terrain_poule)) || 0,
                10
            );
            if (!isNaN(nb) && nb > 0) {
                if (typeof nbTerrains === 'undefined') window.nbTerrains = nb;
                else nbTerrains = nb;
                if (typeof nbre_terrain_poule_js !== 'undefined') {
                    nbre_terrain_poule_js = nb;
                }
            }

            // 2) Récupération de la liste des matchs du tournoi.
            const formData = new FormData();
            formData.append('id_tournoi', idTournoi);
            return fetch('api/get_matchs.php', { method: 'POST', body: formData });
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                afficherMessage(data.error || "Erreur chargement des matchs.", 'error');
                return;
            }
            matchsActuels = (data.matchs || []).map(m => ({
                ...m,
                terrain: m.terrain || null
            }));

            if (typeof genererZonesTerrains === 'function') genererZonesTerrains();
            if (typeof afficherListeMatchs === 'function') afficherListeMatchs();
            if (typeof afficherLegendeCategories === 'function') afficherLegendeCategories();
            if (typeof afficherLegendePoules === 'function') afficherLegendePoules();
        })
        .catch(err => {
            afficherMessage('Erreur : ' + err, 'error');
        });
}


/* =====================================================================
   GÉNÉRATION D'UN TOUR SALADE (côté JS)
   ---------------------------------------------------------------------
   - Fetch des paramètres du tournoi (nb terrains).
   - Fetch de la liste des équipes de la poule active.
   - Calcul du nombre de tours : floor((n-1) / 2).
   - Pour chaque tour : shuffle + découpage en groupes de 4.
   - Assignation du terrain PENDANT le shuffle (round-robin).
   - Envoi de l'array de matchs à sauvegarder_ordre.php via
     `sauvegarderMatchsSalade()`.
   ===================================================================== */
function genererTournoiSalade() {
    idCategorieActive = 1;
    idPouleSaladeActive = 1;
    const idTournoi = _getIdTournoi();

    if (!idTournoi) {
        afficherMessage("Identifiant tournoi manquant.", 'error');
        return;
    }
    if (!idCategorieActive || !idPouleSaladeActive) {
        afficherMessage("Sélectionnez une catégorie et une poule salade.", 'error');
        return;
    }

    // 1) Fetch des paramètres (nb terrains)
    const fdParams = new FormData();
    fdParams.append('id_tournoi', idTournoi);

    fetch('api/get_parametres.php', { method: 'POST', body: fdParams })
        .then(r => r.json())
        .then(dataParam => {
            const nbTerrains = Math.max(1, parseInt(
                document.getElementById('nb-terrains-auto').value || 1,
                10
            ));

            // 2) Fetch des équipes de la poule active.
            const fdEq = new FormData();
            fdEq.append('id_tournoi', idTournoi);
            fdEq.append('id_categorie', idCategorieActive);
            fdEq.append('id_poule', idPouleSaladeActive);

            return fetch('api/generer_matchs_salade.php', { method: 'POST', body: fdEq })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        afficherMessage(data.error || "Erreur chargement des équipes.", 'error');
                        throw new Error('stop');
                    }
                    const equipes = Array.isArray(data.equipes) ? data.equipes : [];
                    const nomPoule = (equipes[0] && equipes[0].nom_poule) || '';
                    const nomCategorie = (equipes[0] && equipes[0].nom_categorie) || '';
                    console.log('Nombre équipes reçues:', equipes.length, equipes);
                    return { nbTerrains, equipes, nomPoule, nomCategorie };
                });
        })
        .then(({ nbTerrains, equipes, nomPoule, nomCategorie }) => {
            const n = equipes.length;
            if (n < 4) {
                afficherMessage(
                    "Au moins 4 équipes sont nécessaires pour générer des matchs SALADE.",
                    'error'
                );
                return;
            }

            // 3) Nombre de tours FIXE (chaque tour = toutes les équipes jouent 1 fois,
            //    sauf reliquat exempté si n % 4 != 0)
            const NB_TOURS = 10;

            // 4) Génération des matchs en JS avec assignation de terrain
            const matchsGeneres = [];
            let ordreAffichage = 1;
            let idMatch = 1;
            let compteurTerrain = 0;

            for (let numTour = 1; numTour <= NB_TOURS; numTour++) {
                // Shuffle 100% aléatoire, indépendant à chaque tour
                const shuffled = [...equipes];
                for (let i = shuffled.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
                }

                // Découpage STRICT en groupes complets de 4
                const nbGroupesComplets = Math.floor(n / 4);

                for (let g = 0; g < nbGroupesComplets; g++) {
                    const base = g * 4;
                    const e1 = shuffled[base];
                    const e2 = shuffled[base + 1];
                    const e3 = shuffled[base + 2];
                    const e4 = shuffled[base + 3];

                    // Assignation du terrain (round-robin)
                    const terrain = (compteurTerrain % nbTerrains) + 1;
                    compteurTerrain++;

                    matchsGeneres.push({
                        id_tournoi: parseInt(idTournoi, 10),
                        id_categorie: idCategorieActive,
                        nom_categorie: nomCategorie,
                        id_poule: idPouleSaladeActive,
                        nom_poule: nomPoule,
                        id_poule_2: null,
                        id_match: idMatch,
                        num_match_poule: idMatch,
                        numero_tour: numTour,
                        terrain: terrain,
                        id_equipe_1: e1.id_equipe,
                        nom_equipe_1: e1.nom,
                        id_equipe_2: e2.id_equipe,
                        nom_equipe_2: e2.nom,
                        id_equipe_3: e3.id_equipe,
                        nom_equipe_3: e3.nom,
                        id_equipe_4: e4.id_equipe,
                        nom_equipe_4: e4.nom,
                        status: 'planifie',
                        ordre_affichage: ordreAffichage++,
                    });
                    idMatch++;
                }

                // Le reliquat (n % 4 équipes) est exempté ce tour : pas de match créé.
                const reliquat = n % 4;
                if (reliquat > 0) {
                    const exemptees = shuffled.slice(nbGroupesComplets * 4);
                    console.log(
                        `Tour ${numTour} : équipe(s) exemptée(s) ->`,
                        exemptees.map(e => e.nom)
                    );
                }
            }

            // 5) Mise à jour du tableau JS
            matchsActuels = matchsGeneres.map(m => ({ ...m }));

            // 6) Affichage immédiat
            if (typeof genererZonesTerrains === 'function') genererZonesTerrains();
            if (typeof afficherListeMatchs === 'function') afficherListeMatchs();
            if (typeof afficherLegendeCategories === 'function') afficherLegendeCategories();
            if (typeof afficherLegendePoules === 'function') afficherLegendePoules();

            afficherMessage(
                `${matchsGeneres.length} match(s) SALADE généré(s) sur ${NB_TOURS} tours ✓ — envoi au serveur…`,
                'success'
            );

            // 7) Sauvegarde côté backend
            sauvegarderMatchsSalade(matchsGeneres);
        })
        .catch(err => {
            if (err && err.message !== 'stop') {
                afficherMessage('Erreur : ' + err, 'error');
            }
        });
}


/* =====================================================================
   SAUVEGARDE DES MATCHS GÉNÉRÉS VERS LE BACK-END
   ---------------------------------------------------------------------
   - Envoie l'array `matchs` (généré en JS) à
     api/sauvegarder_ordre.php (POST JSON).
   - Le serveur ne fait QUE valider + insérer ; aucune redistribution.
   ===================================================================== */
function sauvegarderMatchsSalade(matchs) {
    const idTournoi = _getIdTournoi();
    if (!idTournoi || !Array.isArray(matchs) || matchs.length === 0) {
        afficherMessage("Aucune donnée à sauvegarder.", 'error');
        return;
    }

    fetch('api/sauvegarder_ordre.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id_tournoi: parseInt(idTournoi, 10),
            matchs: matchs
        })
    })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                afficherMessage(data.error || "Erreur lors de la sauvegarde.", 'error');
                return;
            }
            afficherMessage(
                `Matchs SALADE sauvegardés ✓ (${matchs.length} match(s))`,
                'success'
            );
        })
        .catch(err => {
            afficherMessage('Erreur réseau : ' + err, 'error');
        });
}


/* =====================================================================
   HELPERS SALADE + creerElementMatch
   ===================================================================== */

function estMatchSalade(m) {
    const a3 = (m && (m.nom_equipe_3 != null && m.nom_equipe_3 !== ''))
        || (m && m.id_equipe_3 != null && m.id_equipe_3 !== '');
    return !!a3;
}

function getLigne2HTML(m) {
    if (estMatchSalade(m)) {
        const eq1 = m.nom_equipe_1 || ('Équipe ' + m.id_equipe_1);
        const eq2 = m.nom_equipe_2 || ('Équipe ' + m.id_equipe_2);
        const eq3 = m.nom_equipe_3 || ('Équipe ' + m.id_equipe_3);
        const eq4 = m.nom_equipe_4 || ('Équipe ' + m.id_equipe_4);

        const equipeDroite = (m.nom_equipe_4 || m.id_equipe_4)
            ? `${escapeHtml(eq3)} & ${escapeHtml(eq4)}`
            : escapeHtml(eq3);

        return `${escapeHtml(eq1)} &amp; ${escapeHtml(eq2)} vs ${equipeDroite}`;
    }

    return `${escapeHtml(m.nom_equipe_1)}${m.inter_poule ? ' (' + escapeHtml(m.nom_poule_equipe_1) + ')' : ''} vs ${escapeHtml(m.nom_equipe_2)}${m.inter_poule ? ' (' + escapeHtml(m.nom_poule_equipe_2) + ')' : ''}`;
}

function escapeHtml(s) {
    if (s === null || s === undefined) return '';
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}


/* --- Remplacement complet de creerElementMatch (2 ou 4 équipes) --- */
function creerElementMatch(m, index) {
    const div = document.createElement('div');
    div.className = 'match-item-terrain';
    div.draggable = true;
    div.dataset.index = index;
    div._matchRef = m;

    let libellePoule = m.nom_poule;
    if (m.inter_poule) {
        libellePoule = m.libelle_match || 'Inter-poules';
    }
    if (m.terrain_libre) {
        libellePoule = m.libelle_match || 'Terrain Libre';
    }

    const badge = m.ajout_manuel ? ' <span class="badge-ajout">Ajouté</span>' : '';

    const libelleBouton = m.terrain ? '↩' : '✕';
    const titreBouton = m.terrain
        ? "Renvoyer en file d'attente"
        : "Supprimer le match";

    const boutonEnvoiTerrain = !m.terrain
        ? `<button class="btn-envoi-terrain" title="Envoyer vers le terrain le moins chargé" onclick="onClickEnvoiTerrainMoinsCharge(event, ${index})">➡</button>`
        : '';

    // Couleur de catégorie (bordure gauche)
    if (!m.terrain_libre && typeof getCouleurCategorie === 'function') {
        div.style.borderLeftColor = getCouleurCategorie(m.id_categorie);
    }
    // Couleur de poule (bordure droite)
    if (m.inter_poule) {
        div.style.borderRightColor = 'var(--inter-poule-color)';
    } else {
        if (!m.terrain_libre && typeof getCouleurPoule === 'function') {
            const idPoulePourCouleur = m.id_poule || m.nom_poule;
            div.style.borderRightColor = getCouleurPoule(idPoulePourCouleur);
        }
    }

    const prefixeSalade = estMatchSalade(m) ? '🥗 ' : '';

    if (m.terrain_libre) {
        div.innerHTML = `
        <button class="btn-suppr-match" title="${titreBouton}" onclick="onClickBoutonAction(event, ${index})">${libelleBouton}</button>
        ${boutonEnvoiTerrain}
        <div class="match-content">
            <div class="ligne1">LIBRE</div>
            <div class="ligne2">Terrain Libre</div>
        </div>
        `;
    } else {
        div.innerHTML = `
        <button class="btn-suppr-match" title="${titreBouton}" onclick="onClickBoutonAction(event, ${index})">${libelleBouton}</button>
        ${boutonEnvoiTerrain}
        <div class="match-content">
            <div class="ligne1">${prefixeSalade}${escapeHtml(m.nom_categorie || '')} - ${escapeHtml(libellePoule || '')} - Match ${m.num_match_poule}${badge}</div>
            <div class="ligne2">${getLigne2HTML(m)}</div>
        </div>
        `;
    }

    if (typeof matchDragStart === 'function') div.addEventListener('dragstart', matchDragStart);
    if (typeof matchDragEnd === 'function') div.addEventListener('dragend', matchDragEnd);
    if (typeof matchDragOver === 'function') div.addEventListener('dragover', matchDragOver);
    if (typeof matchDrop === 'function') div.addEventListener('drop', matchDrop);

    return div;
}
