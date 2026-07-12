// js/generation.js

let matchsActuels = [];
let donneesReferentiel = null; // catégories/poules/équipes du tournoi
let nbTerrains = 1;

function afficherMessage(texte, type) {
    const div = document.getElementById('message');
    div.innerHTML = `<div class="msg ${type}">${texte}</div>`;
    setTimeout(() => div.innerHTML = '', 5000);
}

function chargerMatchs() {
    const id_tournoi = document.getElementById('id_tournoi').value;

    const formDataParam = new FormData();
    formDataParam.append('id_tournoi', id_tournoi);

    fetch('api/get_parametres.php', {
        method: 'POST',
        body: formDataParam
    })
        .then(res => res.json())
        .then(dataParam => {
            if (dataParam.success) {
                nbTerrains = parseInt(dataParam.nbre_terrain_poule) || 1;
            }

            const formData = new FormData();
            formData.append('id_tournoi', id_tournoi);

            return fetch('api/generer_matchs.php', {
                method: 'POST',
                body: formData
            });
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                matchsActuels = data.matchs.map(m => ({ ...m, terrain: null }));
                genererZonesTerrains();
                afficherListeMatchs();
                afficherMessage('Ordre généré avec succès', 'success');
            } else {
                afficherMessage(data.error, 'error');
            }
        })
        .catch(err => afficherMessage('Erreur : ' + err, 'error'));
}

/* ------------------------------------------------------ */
/* --------------- ZONES TERRAINS (DRAG&DROP) ------------ */
/* ------------------------------------------------------ */

function genererZonesTerrains() {
    const container = document.getElementById('zones-terrains');
    container.innerHTML = '';

    // Zone "file d'attente" = matchs sans terrain (ordre auto, réordonnable)
    const zoneAttente = document.createElement('div');
    zoneAttente.className = 'zone-terrain file-attente';
    zoneAttente.dataset.terrain = ''; // vide = pas de terrain
    zoneAttente.innerHTML = `<h3>File d'attente (auto)</h3><div class="zone-content" id="zone-content-attente"></div>`;
    zoneAttente.addEventListener('dragover', zoneDragOver);
    zoneAttente.addEventListener('dragleave', zoneDragLeave);
    zoneAttente.addEventListener('drop', zoneDrop);
    container.appendChild(zoneAttente);

    for (let t = 1; t <= nbTerrains; t++) {
        const zone = document.createElement('div');
        zone.className = 'zone-terrain';
        zone.dataset.terrain = t;
        zone.innerHTML = `<h3>Terrain ${t}</h3><div class="zone-content" id="zone-content-terrain-${t}"></div>`;
        zone.addEventListener('dragover', zoneDragOver);
        zone.addEventListener('dragleave', zoneDragLeave);
        zone.addEventListener('drop', zoneDrop);
        container.appendChild(zone);
    }
}

function afficherListeMatchs() {
    // Vider toutes les zones
    document.getElementById('zone-content-attente').innerHTML = '';
    for (let t = 1; t <= nbTerrains; t++) {
        const el = document.getElementById(`zone-content-terrain-${t}`);
        if (el) el.innerHTML = '';
    }

    matchsActuels.forEach((m, index) => {
        const div = creerElementMatch(m, index);

        if (m.terrain) {
            const zone = document.getElementById(`zone-content-terrain-${m.terrain}`);
            if (zone) zone.appendChild(div);
        } else {
            document.getElementById('zone-content-attente').appendChild(div);
        }
    });
    afficherLegendeCategories();
    afficherLegendePoules();
}

const PALETTE_CATEGORIES = [
    'var(--categorie-color-1)',
    'var(--categorie-color-2)',
    'var(--categorie-color-3)',
    'var(--categorie-color-4)',
    'var(--categorie-color-5)',
    'var(--categorie-color-6)',
    'var(--categorie-color-7)',
    'var(--categorie-color-8)',
    'var(--categorie-color-9)',
    'var(--categorie-color-10)',
];

let mapCouleurCategories = {}; // { id_categorie: couleur }
let indexCouleurCategorieSuivant = 0;

function getCouleurCategorie(id_categorie) {
    if (!(id_categorie in mapCouleurCategories)) {
        mapCouleurCategories[id_categorie] = PALETTE_CATEGORIES[indexCouleurCategorieSuivant % PALETTE_CATEGORIES.length];
        indexCouleurCategorieSuivant++;
    }
    return mapCouleurCategories[id_categorie];
}


const PALETTE_POULES = [
    'var(--poule-color-1)',
    'var(--poule-color-2)',
    'var(--poule-color-3)',
    'var(--poule-color-4)',
    'var(--poule-color-5)',
    'var(--poule-color-6)',
    'var(--poule-color-7)',
    'var(--poule-color-8)',
    'var(--poule-color-9)',
    'var(--poule-color-10)',
];

let mapCouleurPoules = {}; // { id_poule: couleur }
let indexCouleurSuivant = 0;

function getCouleurPoule(id_poule) {
    if (!(id_poule in mapCouleurPoules)) {
        mapCouleurPoules[id_poule] = PALETTE_POULES[indexCouleurSuivant % PALETTE_POULES.length];
        indexCouleurSuivant++;
    }
    return mapCouleurPoules[id_poule];
}

function afficherLegendeCategories() {
    const container = document.getElementById('legende-categories');
    if (!container) return;

    let html = '';
    Object.entries(mapCouleurCategories).forEach(([id, couleur]) => {
        const matchTrouve = matchsActuels.find(m => m.id_categorie == id);
        const nomCategorie = matchTrouve ? matchTrouve.nom_categorie : `Catégorie ${id}`;
        html += `<span class="legende-item"><span class="legende-couleur" style="background:${couleur}"></span>${nomCategorie}</span>`;
    });

    container.innerHTML = html;
}

function afficherLegendePoules() {
    const container = document.getElementById('legende-poules');
    if (!container) return;

    let html = '';
    let aInterPoule = false;

    // Légende pour chaque poule (dans l'ordre d'apparition)
    Object.entries(mapCouleurPoules).forEach(([id, couleur]) => {
        const matchTrouve = matchsActuels.find(m => (m.id_poule || m.nom_poule) == id && !m.inter_poule);
        const nomPoule = matchTrouve ? matchTrouve.nom_poule : `Poule ${id}`;
        html += `<span class="legende-item"><span class="legende-couleur" style="background:${couleur}"></span>${nomPoule}</span>`;
    });

    // Vérifie s'il existe au moins un match inter-poule
    aInterPoule = matchsActuels.some(m => m.inter_poule);
    if (aInterPoule) {
        html += `<span class="legende-item"><span class="legende-couleur" style="background:var(--inter-poule-color)"></span>Inter-poules</span>`;
    }

    container.innerHTML = html;
}

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

    const badge = m.ajout_manuel ? ' <span class="badge-ajout">Ajouté</span>' : '';

    const libelleBouton = m.terrain ? '↩' : '✕';
    const titreBouton = m.terrain ? "Renvoyer en file d'attente" : "Supprimer le match";

    // Couleur de catégorie (bordure gauche)
    div.style.borderLeftColor = getCouleurCategorie(m.id_categorie);

    // Couleur de poule (bordure droite) : spécifique si inter-poule, sinon selon la poule
    if (m.inter_poule) {
        div.style.borderRightColor = 'var(--inter-poule-color)';
    } else {
        const idPoulePourCouleur = m.id_poule || m.nom_poule;
        div.style.borderRightColor = getCouleurPoule(idPoulePourCouleur);
    }


    div.innerHTML = `
        <button class="btn-suppr-match" title="${titreBouton}" onclick="onClickBoutonAction(event, ${index})">${libelleBouton}</button>
        <div class="match-content">
            <div class="ligne1">${m.nom_categorie} - ${libellePoule} - Match ${m.num_match_poule}${badge}</div>
            <div class="ligne2">${m.nom_equipe_1}${m.inter_poule ? ' (' + m.nom_poule_equipe_1 + ')' : ''} vs ${m.nom_equipe_2}${m.inter_poule ? ' (' + m.nom_poule_equipe_2 + ')' : ''}</div>
        </div>
    `;

    div.addEventListener('dragstart', matchDragStart);
    div.addEventListener('dragend', matchDragEnd);
    div.addEventListener('dragover', matchDragOver);
    div.addEventListener('drop', matchDrop);

    return div;
}

/* --- Action du bouton (suppression ou renvoi file d'attente) --- */
function onClickBoutonAction(event, index) {
    event.stopPropagation(); // éviter de déclencher un drag/drop parasite

    const m = matchsActuels[index];

    if (m.terrain) {
        // Le match est sur un terrain -> on le renvoie en file d'attente
        m.terrain = null;
        afficherListeMatchs();
        afficherMessage('Match renvoyé en file d\'attente', 'success');
    } else {
        // Le match est déjà en file d'attente -> suppression réelle
        matchsActuels.splice(index, 1);
        afficherListeMatchs();
        afficherMessage('Match supprimé', 'success');
    }
}

/* --- Drag & drop entre zones (terrains / file d'attente) + réordonnancement --- */

/* ------------------------------------------------------ */
/* --------------- DRAG & DROP (version fluide) --------- */
/* ------------------------------------------------------ */

let dragSrcIndex = null;
let placeholder = null;

function creerPlaceholder() {
    const ph = document.createElement('div');
    ph.className = 'match-item-terrain placeholder';
    return ph;
}

function matchDragStart(e) {
    dragSrcIndex = parseInt(this.dataset.index);
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';

    placeholder = creerPlaceholder();
    placeholder.style.height = this.offsetHeight + 'px';

    // On insère le placeholder juste après l'élément déplacé
    this.parentNode.insertBefore(placeholder, this.nextSibling);

    // Léger délai pour laisser l'image de drag se générer avant de cacher l'original
    setTimeout(() => {
        this.style.display = 'none';
    }, 0);
}

function matchDragEnd() {
    this.style.display = '';
    this.classList.remove('dragging');

    document.querySelectorAll('.zone-terrain').forEach(z => z.classList.remove('drag-over'));

    if (placeholder && placeholder.parentNode) {
        placeholder.parentNode.removeChild(placeholder);
    }
    placeholder = null;
    dragSrcIndex = null;
}

function matchDragOver(e) {
    e.preventDefault();
    e.stopPropagation();

    if (!placeholder || dragSrcIndex === null) return;

    const rect = this.getBoundingClientRect();
    const insertAfter = (e.clientY - rect.top) > (rect.height / 2);

    const zoneContent = this.parentNode;

    if (insertAfter) {
        zoneContent.insertBefore(placeholder, this.nextSibling);
    } else {
        zoneContent.insertBefore(placeholder, this);
    }
}

function matchDrop(e) {
    e.preventDefault();
    e.stopPropagation();
}

function zoneDragOver(e) {
    e.preventDefault();
    this.classList.add('drag-over');

    if (!placeholder || dragSrcIndex === null) return;

    const zoneContent = this.querySelector('.zone-content');

    // Si on survole directement le fond de la zone (pas un match précis),
    // on place le placeholder à la fin
    if (e.target === this || e.target === zoneContent) {
        zoneContent.appendChild(placeholder);
    }
}

function zoneDragLeave(e) {
    this.classList.remove('drag-over');
}

function zoneDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    this.classList.remove('drag-over');

    if (dragSrcIndex === null || !placeholder) return;

    const terrainCible = this.dataset.terrain ? parseInt(this.dataset.terrain) : null;
    const zoneContent = this.querySelector('.zone-content');

    // Construire le nouvel ordre en lisant le DOM de CETTE zone (placeholder inclus)
    const draggedItem = matchsActuels[dragSrcIndex];

    // Retirer l'élément déplacé du tableau global
    matchsActuels.splice(dragSrcIndex, 1);
    draggedItem.terrain = terrainCible;

    // Trouver la position d'insertion : on compte les match-item-terrain
    // (hors placeholder) qui précèdent le placeholder DANS CETTE ZONE
    const enfants = Array.from(zoneContent.children);
    const posPlaceholder = enfants.indexOf(placeholder);

    // Récupérer les indices (dans matchsActuels) des matchs qui précèdent le placeholder dans cette zone
    let matchAvant = null;
    for (let i = posPlaceholder - 1; i >= 0; i--) {
        if (enfants[i].dataset.index !== undefined) {
            matchAvant = enfants[i]._matchRef;
            break;
        }
    }

    let insertIndex;
    if (matchAvant) {
        insertIndex = matchsActuels.indexOf(matchAvant) + 1;
    } else {
        // Aucun match avant dans cette zone -> on insère au début de cette zone
        // On cherche le premier match de cette zone dans le tableau global
        const premierMatchZone = matchsActuels.find(m => (m.terrain || null) === terrainCible);
        insertIndex = premierMatchZone ? matchsActuels.indexOf(premierMatchZone) : matchsActuels.length;
    }

    matchsActuels.splice(insertIndex, 0, draggedItem);

    afficherListeMatchs();
    dragSrcIndex = null;
    placeholder = null;
}


/* ------------------------------------------------------ */
/* --------------- REPARTITION AUTOMATIQUE ---------------- */
/* ------------------------------------------------------ */

function repartitionAutomatique() {
    const enAttente = matchsActuels.filter(m => !m.terrain);

    let terrainActuel = 1;
    enAttente.forEach(m => {
        m.terrain = terrainActuel;
        terrainActuel++;
        if (terrainActuel > nbTerrains) terrainActuel = 1;
    });

    afficherListeMatchs();
    afficherMessage('Répartition automatique effectuée', 'success');
}

/* ------------------------------------------------------ */
/* --------------- AJOUT DE MATCH MANUEL ----------------- */
/* ------------------------------------------------------ */

function ouvrirFormulaireAjout() {
    document.getElementById('form-ajout-match').style.display = 'block';

    if (donneesReferentiel) {
        remplirSelectCategories();
        return;
    }

    const id_tournoi = document.getElementById('id_tournoi').value;
    const formData = new FormData();
    formData.append('id_tournoi', id_tournoi);

    fetch('api/get_equipes_generer.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                donneesReferentiel = data.categories;
                remplirSelectCategories();
            } else {
                afficherMessage(data.error, 'error');
            }
        })
        .catch(err => afficherMessage('Erreur : ' + err, 'error'));
}

function fermerFormulaireAjout() {
    document.getElementById('form-ajout-match').style.display = 'none';
}

function remplirSelectCategories() {
    const selectCat = document.getElementById('select-categorie');
    selectCat.innerHTML = '';

    donneesReferentiel.forEach(cat => {
        const opt = document.createElement('option');
        opt.value = cat.id_categorie;
        opt.textContent = cat.nom_categorie;
        selectCat.appendChild(opt);
    });

    onCategorieChange();
}

function onCategorieChange() {
    remplirSelectsPoules();
}

function onInterPouleChange() {
    const isInter = document.getElementById('check-inter-poule').checked;
    document.getElementById('bloc-poule-unique').style.display = isInter ? 'none' : 'block';
    document.getElementById('bloc-inter-poule').style.display = isInter ? 'block' : 'none';
}

function getCategorieSelectionnee() {
    const idCategorie = parseInt(document.getElementById('select-categorie').value);
    return donneesReferentiel.find(c => c.id_categorie == idCategorie);
}

function remplirSelectsPoules() {
    const cat = getCategorieSelectionnee();

    const selectPoule = document.getElementById('select-poule');
    selectPoule.innerHTML = '';

    const selectPouleE1 = document.getElementById('select-poule-e1');
    const selectPouleE2 = document.getElementById('select-poule-e2');
    selectPouleE1.innerHTML = '';
    selectPouleE2.innerHTML = '';

    if (cat) {
        cat.poules.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id_poule;
            opt.textContent = p.nom_poule;
            selectPoule.appendChild(opt);

            const opt1 = opt.cloneNode(true);
            selectPouleE1.appendChild(opt1);

            const opt2 = opt.cloneNode(true);
            selectPouleE2.appendChild(opt2);
        });

        if (cat.poules.length > 1) {
            selectPouleE2.selectedIndex = 1;
        }
    }

    onPouleChange();
    onPouleE1Change();
    onPouleE2Change();
}

function onPouleChange() {
    const cat = getCategorieSelectionnee();
    const idPoule = parseInt(document.getElementById('select-poule').value);
    const poule = cat ? cat.poules.find(p => p.id_poule == idPoule) : null;

    const selectE1 = document.getElementById('select-equipe1');
    const selectE2 = document.getElementById('select-equipe2');
    selectE1.innerHTML = '';
    selectE2.innerHTML = '';

    if (poule) {
        poule.equipes.forEach(e => {
            const opt1 = document.createElement('option');
            opt1.value = e.id_equipe;
            opt1.textContent = e.nom;
            selectE1.appendChild(opt1);

            const opt2 = opt1.cloneNode(true);
            selectE2.appendChild(opt2);
        });

        if (poule.equipes.length > 1) {
            selectE2.selectedIndex = 1;
        }
    }
}

function onPouleE1Change() {
    const cat = getCategorieSelectionnee();
    const idPoule = parseInt(document.getElementById('select-poule-e1').value);
    const poule = cat ? cat.poules.find(p => p.id_poule == idPoule) : null;

    const selectE1 = document.getElementById('select-equipe1-bis');
    selectE1.innerHTML = '';

    if (poule) {
        poule.equipes.forEach(e => {
            const opt = document.createElement('option');
            opt.value = e.id_equipe;
            opt.textContent = e.nom;
            selectE1.appendChild(opt);
        });
    }
}

function onPouleE2Change() {
    const cat = getCategorieSelectionnee();
    const idPoule = parseInt(document.getElementById('select-poule-e2').value);
    const poule = cat ? cat.poules.find(p => p.id_poule == idPoule) : null;

    const selectE2 = document.getElementById('select-equipe2-bis');
    selectE2.innerHTML = '';

    if (poule) {
        poule.equipes.forEach(e => {
            const opt = document.createElement('option');
            opt.value = e.id_equipe;
            opt.textContent = e.nom;
            selectE2.appendChild(opt);
        });
    }
}

function ajouterMatchManuel() {
    const isInter = document.getElementById('check-inter-poule').checked;

    if (isInter) {
        ajouterMatchInterPoule();
    } else {
        ajouterMatchPouleUnique();
    }
}

function ajouterMatchPouleUnique() {
    const idCategorie = parseInt(document.getElementById('select-categorie').value);
    const idPoule = parseInt(document.getElementById('select-poule').value);
    const idEquipe1 = parseInt(document.getElementById('select-equipe1').value);
    const idEquipe2 = parseInt(document.getElementById('select-equipe2').value);

    if (!idCategorie || !idPoule || !idEquipe1 || !idEquipe2) {
        afficherMessage('Veuillez sélectionner toutes les valeurs', 'error');
        return;
    }

    if (idEquipe1 === idEquipe2) {
        afficherMessage('Les deux équipes doivent être différentes', 'error');
        return;
    }

    const cat = donneesReferentiel.find(c => c.id_categorie == idCategorie);
    const poule = cat.poules.find(p => p.id_poule == idPoule);
    const equipe1 = poule.equipes.find(e => e.id_equipe == idEquipe1);
    const equipe2 = poule.equipes.find(e => e.id_equipe == idEquipe2);

    const nbMatchsPoule = matchsActuels.filter(m => m.id_poule == idPoule && !m.inter_poule).length;

    const nouveauMatch = {
        id_categorie: idCategorie,
        nom_categorie: cat.nom_categorie,
        id_poule: idPoule,
        nom_poule: poule.nom_poule,
        id_equipe_1: equipe1.id_equipe,
        nom_equipe_1: equipe1.nom,
        id_equipe_2: equipe2.id_equipe,
        nom_equipe_2: equipe2.nom,
        num_match_poule: nbMatchsPoule + 1,
        ajout_manuel: true,
        inter_poule: false,
        terrain: null
    };

    matchsActuels.push(nouveauMatch);
    afficherListeMatchs();
    afficherMessage('Match ajouté à la liste', 'success');
}

function ajouterMatchInterPoule() {
    const idCategorie = parseInt(document.getElementById('select-categorie').value);
    const idPouleE1 = parseInt(document.getElementById('select-poule-e1').value);
    const idPouleE2 = parseInt(document.getElementById('select-poule-e2').value);
    const idEquipe1 = parseInt(document.getElementById('select-equipe1-bis').value);
    const idEquipe2 = parseInt(document.getElementById('select-equipe2-bis').value);
    const libelle = document.getElementById('libelle-match-inter').value.trim() || 'Inter-poules';

    if (!idCategorie || !idPouleE1 || !idPouleE2 || !idEquipe1 || !idEquipe2) {
        afficherMessage('Veuillez sélectionner toutes les valeurs', 'error');
        return;
    }

    if (idEquipe1 === idEquipe2 && idPouleE1 === idPouleE2) {
        afficherMessage('Les deux équipes doivent être différentes', 'error');
        return;
    }

    const cat = donneesReferentiel.find(c => c.id_categorie == idCategorie);
    const pouleE1 = cat.poules.find(p => p.id_poule == idPouleE1);
    const pouleE2 = cat.poules.find(p => p.id_poule == idPouleE2);
    const equipe1 = pouleE1.equipes.find(e => e.id_equipe == idEquipe1);
    const equipe2 = pouleE2.equipes.find(e => e.id_equipe == idEquipe2);

    const nbMatchsInter = matchsActuels.filter(m => m.inter_poule).length;

    const nouveauMatch = {
        id_categorie: idCategorie,
        nom_categorie: cat.nom_categorie,
        id_poule: idPouleE1,
        id_poule_2: idPouleE2,
        nom_poule: libelle,
        id_equipe_1: equipe1.id_equipe,
        nom_equipe_1: equipe1.nom,
        id_equipe_2: equipe2.id_equipe,
        nom_equipe_2: equipe2.nom,
        id_poule_equipe_1: pouleE1.id_poule,
        nom_poule_equipe_1: pouleE1.nom_poule,
        id_poule_equipe_2: pouleE2.id_poule,
        nom_poule_equipe_2: pouleE2.nom_poule,
        num_match_poule: nbMatchsInter + 1,
        ajout_manuel: true,
        inter_poule: true,
        libelle_match: libelle,
        terrain: null
    };

    matchsActuels.push(nouveauMatch);
    afficherListeMatchs();
    afficherMessage('Match inter-poules ajouté à la liste', 'success');
    document.getElementById('libelle-match-inter').value = '';
}

/* ------------------------------------------------------ */
/* --------------- VALIDATION / SAUVEGARDE ---------------- */
/* ------------------------------------------------------ */

function validerOrdre() {
    const id_tournoi = document.getElementById('id_tournoi').value;

    fetch('api/sauvegarder_ordre.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id_tournoi: id_tournoi,
            matchs: matchsActuels
        })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                afficherMessage('Matchs enregistrés en base de données !', 'success');
            } else {
                afficherMessage(data.error, 'error');
            }
        })
        .catch(err => afficherMessage('Erreur : ' + err, 'error'));
}