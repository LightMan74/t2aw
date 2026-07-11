// js/generation.js

let matchsActuels = [];
let donneesReferentiel = null; // catégories/poules/équipes du tournoi

function afficherMessage(texte, type) {
    const div = document.getElementById('message');
    div.innerHTML = `<div class="msg ${type}">${texte}</div>`;
    setTimeout(() => div.innerHTML = '', 5000);
}

function chargerMatchs() {
    const id_tournoi = document.getElementById('id_tournoi').value;

    const formData = new FormData();
    formData.append('id_tournoi', id_tournoi);

    fetch('api/generer_matchs.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                matchsActuels = data.matchs;
                afficherListeMatchs();
                afficherMessage('Ordre généré avec succès', 'success');
            } else {
                afficherMessage(data.error, 'error');
            }
        })
        .catch(err => afficherMessage('Erreur : ' + err, 'error'));
}

function afficherListeMatchs() {
    const container = document.getElementById('liste-matchs');
    container.innerHTML = '';

    matchsActuels.forEach((m, index) => {
        const div = document.createElement('div');
        div.className = 'match-item';
        div.draggable = true;
        div.dataset.index = index;

        let libellePoule = m.nom_poule;
        if (m.inter_poule) {
            libellePoule = m.libelle_match || 'Inter-poules';
        }

        const badge = m.ajout_manuel ? ' <em>(ajouté)</em>' : '';

        div.innerHTML = `
            <span><strong>${m.nom_categorie}</strong> - ${libellePoule} - Match ${m.num_match_poule}${badge}</span>
            <span>${m.nom_equipe_1}${m.inter_poule ? ' (' + m.nom_poule_equipe_1 + ')' : ''} vs ${m.nom_equipe_2}${m.inter_poule ? ' (' + m.nom_poule_equipe_2 + ')' : ''}</span>
            <button class="btn-suppr-match" onclick="supprimerMatch(${index})" style="margin-left:10px;">✕</button>
        `;

        div.addEventListener('dragstart', dragStart);
        div.addEventListener('dragover', dragOver);
        div.addEventListener('drop', drop);
        div.addEventListener('dragend', dragEnd);

        container.appendChild(div);
    });
}

function supprimerMatch(index) {
    matchsActuels.splice(index, 1);
    afficherListeMatchs();
}

let dragSrcIndex = null;

function dragStart(e) {
    dragSrcIndex = parseInt(this.dataset.index);
    this.classList.add('dragging');
}

function dragOver(e) {
    e.preventDefault();
}

function drop(e) {
    e.preventDefault();
    const targetIndex = parseInt(this.dataset.index);

    if (dragSrcIndex === null || dragSrcIndex === targetIndex) return;

    const item = matchsActuels[dragSrcIndex];
    matchsActuels.splice(dragSrcIndex, 1);
    matchsActuels.splice(targetIndex, 0, item);

    afficherListeMatchs();
}

function dragEnd() {
    this.classList.remove('dragging');
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
    // Remplit à la fois le mode "poule unique" et le mode "inter-poules"
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

    // --- Mode poule unique ---
    const selectPoule = document.getElementById('select-poule');
    selectPoule.innerHTML = '';

    // --- Mode inter-poules ---
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

        // Par défaut, sélectionner une 2ème poule différente pour l'équipe 2 si possible
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
        inter_poule: false
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
        id_poule: idPouleE1,          // poule "principale" = poule de l'équipe 1
        id_poule_2: idPouleE2,        // <-- NOUVEAU : poule de l'équipe 2
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
        libelle_match: libelle
    };

    matchsActuels.push(nouveauMatch);
    afficherListeMatchs();
    afficherMessage('Match inter-poules ajouté à la liste', 'success');

    document.getElementById('libelle-match-inter').value = '';
}

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