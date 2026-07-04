/**
 * edit.js - Modification d'un tournoi existant
 * Charge les données via API, génère les blocs pré-remplis, envoie les modifs via AJAX
 */

'use strict';

// ==========================================
// INITIALISATION
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    chargerDonneesTournoi();
    setupFormulaireModification();
});

// ==========================================
// CHARGEMENT DES DONNÉES DU TOURNOI
// ==========================================

/**
 * Charge les données complètes du tournoi via get_tournoi_detail.php
 */
async function chargerDonneesTournoi() {
    const loadingMsg = document.getElementById('loading-message');
    const editSection = document.getElementById('edit-section');
    const errorMsg = document.getElementById('error-message');
    const id_tournoi = window.idTournoi;

    if (!id_tournoi) {
        afficherErreur('ID du tournoi manquant dans l\'URL.');
        return;
    }

    try {
        const response = await fetch('api/get_tournoi_detail.php?id_tournoi=' + id_tournoi);
        const data = await response.json();

        if (!data.success) {
            afficherErreur(data.error || 'Tournoi introuvable.');
            return;
        }

        // Remplir les champs de base
        document.getElementById('nom_tournoi').value = data.tournoi.nom || '';
        document.getElementById('nbre_terrain_poule').value = data.parametre.nbre_terrain_poule || '';
        document.getElementById('nbre_terrain_phasefinal').value = data.parametre.nbre_terrain_phasefinal || '';
        document.getElementById('temps_de_match').value = data.parametre.temps_de_match || '';
        document.getElementById('heure_debut_poule').value = data.parametre.heure_debut_poule || '';
        document.getElementById('heure_debut_phasefinal').value = data.parametre.heure_debut_phasefinal || '';

        // Titre
        document.querySelector('h1').textContent = 'Modifier : ' + data.tournoi.nom;

        // Afficher le formulaire
        loadingMsg.style.display = 'none';
        editSection.style.display = 'block';

        // Générer les catégories pré-remplies
        genererCategoriesAvecDonnees(data.categories);

    } catch (err) {
        afficherErreur('Erreur réseau : ' + err.message);
    }
}

/**
 * Affiche un message d'erreur et masque le chargement
 */
function afficherErreur(msg) {
    document.getElementById('loading-message').style.display = 'none';
    var errorMsg = document.getElementById('error-message');
    errorMsg.style.display = 'block';
    errorMsg.textContent = msg;
}

// ==========================================
// GÉNÉRATION DES BLOCS PRÉ-REMPLIS
// ==========================================

/**
 * Génère les catégories/poules/équipes depuis les données JSON
 */
function genererCategoriesAvecDonnees(categories) {
    var nbre = categories && categories.length > 0 ? categories.length : 1;
    document.getElementById('nbre_categories').textContent = nbre;

    var container = document.getElementById('categories-container');
    container.innerHTML = '';

    if (categories && categories.length > 0) {
        categories.forEach(function (cat, idxCat) {
            var id_categorie = idxCat + 1;
            var block = creerBlocCategorie(id_categorie, cat.nom);

            var poulesContainer = block.querySelector('.poules-container');
            // Supprimer la poule par défaut générée par creerBlocCategorie
            poulesContainer.innerHTML = '';

            if (cat.poules && cat.poules.length > 0) {
                cat.poules.forEach(function (poule, idxPoule) {
                    var id_poule = idxPoule + 1;
                    var pouleBlock = creerBlocPoule(id_categorie, id_poule, poule.nom);

                    var equipesContainer = pouleBlock.querySelector('.equipes-container');
                    equipesContainer.innerHTML = '';

                    if (poule.equipes && poule.equipes.length > 0) {
                        poule.equipes.forEach(function (eq, idxEq) {
                            ajouterChampEquipePreserve(equipesContainer, id_categorie, id_poule, eq.nom);
                        });
                    } else {
                        // 2 équipes vides par défaut
                        ajouterChampEquipePreserve(equipesContainer, id_categorie, id_poule, '');
                        ajouterChampEquipePreserve(equipesContainer, id_categorie, id_poule, '');
                    }

                    poulesContainer.appendChild(pouleBlock);
                });
            } else {
                // Une poule vide
                var defaultPoule = creerBlocPoule(id_categorie, 1, '');
                var defEquipes = defaultPoule.querySelector('.equipes-container');
                defEquipes.innerHTML = '';
                ajouterChampEquipePreserve(defEquipes, id_categorie, 1, '');
                ajouterChampEquipePreserve(defEquipes, id_categorie, 1, '');
                poulesContainer.appendChild(defaultPoule);
            }

            container.appendChild(block);
        });
    } else {
        container.appendChild(creerBlocCategorie(1, ''));
    }
}

/**
 * Crée un bloc catégorie avec nom pré-rempli
 */
function creerBlocCategorie(id_categorie, nom) {
    var block = document.createElement('div');
    block.className = 'categorie-block';
    block.dataset.idCategorie = id_categorie;

    block.innerHTML =
        '<div class="categorie-header">' +
        '<h3>Catégorie ' + id_categorie + '</h3>' +
        '<input type="text" name="categorie_nom_' + id_categorie + '" placeholder="Nom de la catégorie" value="' + escapeHtml(nom || '') + '" required>' +
        '<button type="button" class="btn-mini btn-plus" onclick="ajouterPoule(this)">+ Poule</button>' +
        '</div>' +
        '<div class="poules-container" data-id-categorie="' + id_categorie + '">' +
        '<div class="poule-block" data-id-poule="1">' +
        '<div class="poule-header">' +
        '<h4>Poule 1</h4>' +
        '<input type="text" name="poule_nom_' + id_categorie + '_1" placeholder="Nom de la poule" required>' +
        '<button type="button" class="btn-mini btn-plus" onclick="ajouterEquipe(this)">+ Équipe</button>' +
        '</div>' +
        '<div class="equipes-container"></div>' +
        '</div>' +
        '</div>';

    return block;
}

/**
 * Crée un bloc poule avec nom pré-rempli
 */
function creerBlocPoule(id_categorie, id_poule, nom) {
    var block = document.createElement('div');
    block.className = 'poule-block';
    block.dataset.idPoule = id_poule;

    block.innerHTML =
        '<div class="poule-header">' +
        '<h4>Poule ' + id_poule + '</h4>' +
        '<input type="text" name="poule_nom_' + id_categorie + '_' + id_poule + '" placeholder="Nom de la poule" value="' + escapeHtml(nom || '') + '" required>' +
        '<button type="button" class="btn-mini btn-plus" onclick="ajouterEquipe(this)">+ Équipe</button>' +
        '<button type="button" class="btn-mini btn-minus" onclick="supprimerPoule(this)">−</button>' +
        '</div>' +
        '<div class="equipes-container"></div>';

    return block;
}

/**
 * Ajoute un champ équipe pré-rempli avec un nom
 */
function ajouterChampEquipePreserve(equipesContainer, id_categorie, id_poule, nom) {
    var nbreEquipes = equipesContainer.querySelectorAll('.equipe-item').length + 1;

    var div = document.createElement('div');
    div.className = 'equipe-item';

    var input = document.createElement('input');
    input.type = 'text';
    input.name = 'equipe_nom_' + id_categorie + '_' + id_poule + '_' + nbreEquipes;
    input.placeholder = 'Équipe ' + nbreEquipes;
    input.value = nom || '';

    var btnSuppr = document.createElement('button');
    btnSuppr.type = 'button';
    btnSuppr.className = 'btn-mini btn-minus';
    btnSuppr.textContent = '−';
    btnSuppr.onclick = function () {
        div.remove();
        reordonnerEquipes(equipesContainer);
    };

    div.appendChild(input);
    div.appendChild(btnSuppr);
    equipesContainer.appendChild(div);
} ajouterPoule()

// ==========================================
// GÉNÉRATION VIDE (sans données)
// ==========================================

/**
 * Ajuste le nombre de catégories (boutons + / -)
 */

function ajusterCategories(delta) {
    var container = document.getElementById('categories-container');
    var span = document.getElementById('nbre_categories');
    var blocsExistants = container.querySelectorAll('.categorie-block');
    var nbreActuel = blocsExistants.length;

    if (delta > 0) {
        if (nbreActuel >= 10) {
            alert('Maximum 10 catégories');
            return;
        }
        nbreActuel++;
        var nouveauBloc = creerBlocCategorie(nbreActuel, '');

        // Ajoute une poule par défaut avec 2 équipes vides
        var poulesContainer = nouveauBloc.querySelector('.poules-container');
        poulesContainer.innerHTML = '';
        var defaultPoule = creerBlocPoule(nbreActuel, 1, '');
        var eqCont = defaultPoule.querySelector('.equipes-container');
        ajouterChampEquipePreserve(eqCont, nbreActuel, 1, '');
        ajouterChampEquipePreserve(eqCont, nbreActuel, 1, '');
        poulesContainer.appendChild(defaultPoule);

        container.appendChild(nouveauBloc);
    } else {
        if (nbreActuel <= 1) return;
        blocsExistants[blocsExistants.length - 1].remove();
        nbreActuel--;
    }

    span.textContent = nbreActuel;
}


// ==========================================
// FONCTIONS DYNAMIQUES (ajout / suppression)
// ==========================================

/**
 * Ajoute une poule à une catégorie
 */
function ajouterPoule(btn) {
    var categorieBlock = btn.closest('.categorie-block');
    var poulesContainer = categorieBlock.querySelector('.poules-container');
    var id_categorie = parseInt(categorieBlock.dataset.idCategorie);

    var nbrePoules = poulesContainer.querySelectorAll('.poule-block').length + 1;
    if (nbrePoules > 6) {
        alert('Maximum 6 poules par catégorie');
        return;
    }

    var newPoule = creerBlocPoule(id_categorie, nbrePoules, '');
    poulesContainer.appendChild(newPoule);

    // 2 équipes par défaut
    var eqCont = newPoule.querySelector('.equipes-container');
    ajouterChampEquipePreserve(eqCont, id_categorie, nbrePoules, '');
    ajouterChampEquipePreserve(eqCont, id_categorie, nbrePoules, '');
}

/**
 * Supprime une poule (au moins 1 doit rester)
 */
function supprimerPoule(btn) {
    var poulesContainer = btn.closest('.poules-container');
    var pouleBlock = btn.closest('.poule-block');
    if (poulesContainer.querySelectorAll('.poule-block').length <= 1) return;
    pouleBlock.remove();
    reordonnerPoules(poulesContainer);
}

/**
 * Réordonne les indices des poules après suppression
 */
function reordonnerPoules(container) {
    var id_categorie = parseInt(container.dataset.idCategorie);
    container.querySelectorAll('.poule-block').forEach(function (p, idx) {
        var newId = idx + 1;
        p.dataset.idPoule = newId;
        p.querySelector('h4').textContent = 'Poule ' + newId;
        var input = p.querySelector('input[type="text"]');
        if (input) {
            input.name = 'poule_nom_' + id_categorie + '_' + newId;
        }
    });
}

/**
 * Ajoute un champ équipe à une poule
 */
function ajouterEquipe(btn) {
    var pouleBlock = btn.closest('.poule-block');
    var equipesContainer = pouleBlock.querySelector('.equipes-container');
    var id_categorie = parseInt(pouleBlock.closest('.categorie-block').dataset.idCategorie);
    var id_poule = parseInt(pouleBlock.dataset.idPoule);
    ajouterChampEquipePreserve(equipesContainer, id_categorie, id_poule, '');
}

/**
 * Supprime une équipe
 */
function supprimerEquipe(btn) {
    var equipeItem = btn.closest('.equipe-item');
    var equipesContainer = equipeItem.closest('.equipes-container');
    equipeItem.remove();
    reordonnerEquipes(equipesContainer);
}

/**
 * Réordonne les équipes après suppression
 */
function reordonnerEquipes(container) {
    container.querySelectorAll('.equipe-item').forEach(function (item, idx) {
        var newId = idx + 1;
        var input = item.querySelector('input');
        // Remplacer le dernier segment numérique du name
        input.name = input.name.replace(/_(\d+)$/, '_' + newId);
        input.placeholder = 'Équipe ' + newId;
    });
}

// ==========================================
// ENVOI DU FORMULAIRE
// ==========================================

/**
 * Configure l'écouteur de soumission du formulaire
 */
function setupFormulaireModification() {
    var form = document.getElementById('form-edit-tournoi');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        var messageEl = document.getElementById('form-message');
        messageEl.textContent = 'Enregistrement en cours...';
        messageEl.className = 'message';

        var payload = collectFormData();

        try {
            var response = await fetch('api/update_tournoi.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            var data = await response.json();

            if (data.success) {
                messageEl.textContent = 'Modifications enregistrées avec succès !';
                messageEl.className = 'message success';
            } else {
                messageEl.textContent = 'Erreur : ' + (data.error || 'Erreur inconnue');
                messageEl.className = 'message error';
            }
        } catch (err) {
            messageEl.textContent = 'Erreur réseau : ' + err.message;
            messageEl.className = 'message error';
        }
    });
}

/**
 * Collecte toutes les données du formulaire et retourne le payload JSON
 */
function collectFormData() {
    var id_tournoi = document.getElementById('id_tournoi').value;
    var nom = document.getElementById('nom_tournoi').value;
    var nbre_terrain_poule = parseInt(document.getElementById('nbre_terrain_poule').value) || 0;
    var nbre_terrain_phasefinal = parseInt(document.getElementById('nbre_terrain_phasefinal').value) || 0;
    var temps_de_match = parseInt(document.getElementById('temps_de_match').value) || 0;
    var heure_debut_poule = document.getElementById('heure_debut_poule').value;
    var heure_debut_phasefinal = document.getElementById('heure_debut_phasefinal').value;

    var categories = [];

    document.querySelectorAll('.categorie-block').forEach(function (catBlock, idxCat) {
        var id_categorie = idxCat + 1;
        var allCatInputs = catBlock.querySelectorAll('input[type="text"]');
        var nomCat = allCatInputs[0].value;

        if (!nomCat) return;

        var poules = [];
        var catPouleBlocks = catBlock.querySelectorAll('.poule-block');
        var allPouleInputs = catBlock.querySelectorAll('input[name^="poule_nom_"]');

        catPouleBlocks.forEach(function (pouleBlock, idxPoule) {
            var id_poule = idxPoule + 1;
            var nomPoule = allPouleInputs[idxPoule] ? allPouleInputs[idxPoule].value : '';

            if (!nomPoule) return;

            var equipes = [];
            var equipesContainer = catPouleBlocks[idxPoule].querySelector('.equipes-container');
            equipesContainer.querySelectorAll('.equipe-item input').forEach(function (input, idxEq) {
                if (input.value.trim()) {
                    equipes.push({
                        id_equipe: idxEq + 1,
                        nom: input.value.trim()
                    });
                }
            });

            poules.push({
                id_poule: id_poule,
                nom: nomPoule,
                equipes: equipes
            });
        });

        if (poules.length > 0) {
            categories.push({
                id_categorie: id_categorie,
                nom: nomCat,
                poules: poules
            });
        }
    });

    return {
        id_tournoi: id_tournoi,
        nom: nom,
        nbre_terrain_poule: nbre_terrain_poule,
        nbre_terrain_phasefinal: nbre_terrain_phasefinal,
        temps_de_match: temps_de_match,
        heure_debut_poule: heure_debut_poule,
        heure_debut_phasefinal: heure_debut_phasefinal,
        categories: categories
    };
}

// ==========================================
// UTILITAIRES
// ==========================================

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}