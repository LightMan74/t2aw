function genererTournoiSalade() {
    idCategorieActive = 1;
    idPouleSaladeActive = 1;
    if (!id_tournoi_js || !idCategorieActive || !idPouleSaladeActive) {
        afficherMessage('Sélectionnez une catégorie et une poule salade', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('id_tournoi', id_tournoi_js);
    formData.append('id_categorie', idCategorieActive);
    formData.append('id_poule', idPouleSaladeActive);

    fetch('api/generer_matchs_salade.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                afficherMessage(data.error, 'error');
                return;
            }
            matchsActuels = data.matchs.map(m => ({ ...m, terrain: null }));
            genererZonesTerrains();
            afficherListeMatchs();
            afficherLegendeCategories();
            afficherLegendePoules();
        })
        .catch(err => afficherMessage('Erreur : ' + err, 'error'));
}

/* =====================================================================
   BLOC JS "SALADE" À INTÉGRER DANS generation.js
   ---------------------------------------------------------------------
   Objectif : afficher un match à 4 équipes (format salade) avec
   "Equipe1 & Equipe2  vs  Equipe3 & Equipe4" lorsque le match reçu
   depuis le back contient une équipe 3 (et/ou 4). Si ce n'est pas le
   cas, l'affichage 2 équipes reste STRICTEMENT identique à l'original.

   Le bloc est autonome : aucune dépendance externe, aucune fonction
   non définie ici. Il se substitue à l'ancien `creerElementMatch`
   en gardant EXACTEMENT le même comportement pour les matchs à 2
   équipes.

   Intégration : remplacer simplement la fonction existante
   `creerElementMatch(m, index)` de generation.js par celle définie
   ci-dessous (garder le même nom pour ne rien casser ailleurs :
   `afficherListeMatchs`, drag&drop, etc.).
   ===================================================================== */


/* --- Helper : détecte si un match est au format salade (4 équipes) --- */
function estMatchSalade(m) {
    // On accepte nom_equipe_3 OU id_equipe_3 (selon ce que renvoie l'API)
    const a3 = (m && (m.nom_equipe_3 != null && m.nom_equipe_3 !== ''))
        || (m && m.id_equipe_3 != null && m.id_equipe_3 !== '');
    return !!a3;
}

/* --- Helper : produit le HTML de la 2e ligne selon le format --- */
function getLigne2HTML(m) {
    if (estMatchSalade(m)) {
        const eq1 = m.nom_equipe_1 || ('Équipe ' + m.id_equipe_1);
        const eq2 = m.nom_equipe_2 || ('Équipe ' + m.id_equipe_2);
        const eq3 = m.nom_equipe_3 || ('Équipe ' + m.id_equipe_3);
        const eq4 = m.nom_equipe_4 || ('Équipe ' + m.id_equipe_4);

        // Si l'une des équipes 3/4 manque (dernier lot incomplet),
        // on dégrade en affichage "Équipe1 & Équipe2 vs Équipe3".
        const equipeDroite = (m.nom_equipe_4 || m.id_equipe_4)
            ? `${escapeHtml(eq3)} & ${escapeHtml(eq4)}`
            : escapeHtml(eq3);

        return `${escapeHtml(eq1)} &amp; ${escapeHtml(eq2)} vs ${equipeDroite}`;
    }

    // Format classique 2 équipes (comportement original, intact)
    return `${escapeHtml(m.nom_equipe_1)}${m.inter_poule ? ' (' + escapeHtml(m.nom_poule_equipe_1) + ')' : ''} vs ${escapeHtml(m.nom_equipe_2)}${m.inter_poule ? ' (' + escapeHtml(m.nom_poule_equipe_2) + ')' : ''}`;
}

/* --- Helper : échappement HTML pour éviter l'injection --- */
function escapeHtml(s) {
    if (s === null || s === undefined) return '';
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}


/* --- Remplacement complet de creerElementMatch --- */
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
    const titreBouton = m.terrain ? "Renvoyer en file d'attente" : "Supprimer le match";

    // Bouton d'envoi vers le terrain le moins chargé (seulement si le match n'est pas déjà affecté)
    const boutonEnvoiTerrain = !m.terrain
        ? `<button class="btn-envoi-terrain" title="Envoyer vers le terrain le moins chargé" onclick="onClickEnvoiTerrainMoinsCharge(event, ${index})">➡</button>`
        : '';

    // Couleur de catégorie (bordure gauche)
    if (!m.terrain_libre) {
        div.style.borderLeftColor = getCouleurCategorie(m.id_categorie);
    }
    // Couleur de poule (bordure droite) : spécifique si inter-poule, sinon selon la poule
    if (m.inter_poule) {
        div.style.borderRightColor = 'var(--inter-poule-color)';
    } else {
        if (!m.terrain_libre) {
            const idPoulePourCouleur = m.id_poule || m.nom_poule;
            div.style.borderRightColor = getCouleurPoule(idPoulePourCouleur);
        }
    }

    /* [AJOUT SALADE] Petit indicateur visuel optionnel sur la ligne 1 */
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
            <div class="ligne1">${prefixeSalade}${escapeHtml(m.nom_categorie)} - ${escapeHtml(libellePoule)} - Match ${m.num_match_poule}${badge}</div>
            <div class="ligne2">${getLigne2HTML(m)}</div>
        </div>
        `;
    }
    div.addEventListener('dragstart', matchDragStart);
    div.addEventListener('dragend', matchDragEnd);
    div.addEventListener('dragover', matchDragOver);
    div.addEventListener('drop', matchDrop);

    return div;
}


/* =====================================================================
   EXEMPLE D'UTILISATION (côté front, déjà fonctionnel avec le back
   generer_matchs_salade.php fourni dans le projet) :

       // 1) Le back renvoie un match "salade" :
       //    {
       //      id_categorie, nom_categorie,
       //      id_poule,      nom_poule,
       //      id_equipe_1,   nom_equipe_1,
       //      id_equipe_2,   nom_equipe_2,
       //      id_equipe_3,   nom_equipe_3,
       //      id_equipe_4,   nom_equipe_4,
       //      num_match_poule,
       //      ...
       //    }
       //
       // 2) On l'injecte dans le tableau JS :
       //    matchsActuels = data.matchs.map(m => ({ ...m, terrain: null }));
       //    genererZonesTerrains();
       //    afficherListeMatchs();   // -> utilise creerElementMatch ci-dessus
       //
       // 3) Le rendu visuel devient :
       //    🥗 <NomCat> - <NomPoule> - Match 1
       //    Alice & Bob  vs  Chloé & David
       //
       // 4) Pour un match classique (pas de nom_equipe_3) :
       //    <NomCat> - <NomPoule> - Match 1
       //    Alice  vs  Bob
       //    (affichage strictement identique à l'ancien code)
   ===================================================================== */
