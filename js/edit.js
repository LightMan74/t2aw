/**
 * edit.js - Création ET modification d'un tournoi
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
    initSecuriteInputsNumber();

    if (window.modeCreation) {
        initModeCreation();
    } else {
        chargerDonneesTournoi();
    }
    setupFormulaire();
});

// ==========================================
// UTILITAIRE : index -> lettre (A, B, C...)
// ==========================================
function indexToLettre(index) {
    let lettre = '';
    while (index > 0) {
        const reste = (index - 1) % 26;
        lettre = String.fromCharCode(65 + reste) + lettre;
        index = Math.floor((index - 1) / 26);
    }
    return lettre;
}

// ==========================================
// MODE CRÉATION : générer un bloc catégorie vide par défaut
// ==========================================
function initModeCreation() {
    const container = document.getElementById('categories-container');
    container.innerHTML = '';
    var nbreCatEl = document.getElementById('nbre_categories');
    if (nbreCatEl) nbreCatEl.textContent = '1';

    const block = creerBlocCategorie(1, '');
    const poulesContainer = block.querySelector('.poules-container');
    poulesContainer.innerHTML = '';

    const defaultPoule = creerBlocPoule(1, 1, 'A');
    const eqCont = defaultPoule.querySelector('.equipes-container');
    ajouterChampEquipePreserve(eqCont, 1, 1, '', 'A');
    ajouterChampEquipePreserve(eqCont, 1, 1, '', 'A');
    ajouterChampEquipePreserve(eqCont, 1, 1, '', 'A');
    ajouterChampEquipePreserve(eqCont, 1, 1, '', 'A');
    poulesContainer.appendChild(defaultPoule);

    container.appendChild(block);
}

// ==========================================
// CHARGEMENT DES DONNÉES DU TOURNOI (MODE ÉDITION)
// ==========================================
async function chargerDonneesTournoi() {
    var loadingMsg = document.getElementById('loading-message');
    var editSection = document.getElementById('edit-section');
    var id_tournoi = window.idTournoi;

    if (!id_tournoi) {
        afficherErreur('ID du tournoi manquant dans l\'URL.');
        return;
    }

    try {
        var response = await fetch('api/get_tournoi_detail.php?id_tournoi=' + id_tournoi);
        var data = await response.json();
        // console.table(data);
        if (!data.success) {
            afficherErreur(data.error || 'Tournoi introuvable.');
            return;
        }

        document.getElementById('nom_tournoi').value = data.tournoi.nom || '';
        document.getElementById('nbre_terrain_poule').value = data.parametre.nbre_terrain_poule || '';
        document.getElementById('nbre_terrain_phasefinal').value = data.parametre.nbre_terrain_phasefinal || '';
        document.getElementById('temps_de_match').value = data.parametre.temps_de_match || '';
        document.getElementById('heure_debut_poule').value = data.parametre.heure_debut_poule || '';
        document.getElementById('heure_debut_phasefinal').value = data.parametre.heure_debut_phasefinal || '';
        document.getElementById('matchtermine').value = data.parametre.matchtermine || '';
        document.getElementById('tournoi_password').value = data.parametre.tournoi_password || '';
        document.getElementById('tournoi_cacher').value = data.parametre.tournoi_cacher || '';

        var timerVal = parseInt(data.parametre.timer, 10);
        document.getElementById('show_timer').value = (timerVal === 1) ? '1' : '0';

        var qrcodeVal = parseInt(data.parametre.qrcode, 10);
        document.getElementById('show_qrcode').value = (qrcodeVal === 1) ? '1' : '0';

        // troissets : select -> forcer 1 ou 3
        var troissetsVal = parseInt(data.parametre.troissets, 10);
        document.getElementById('troissets').value = (troissetsVal === 1) ? '1' : '3';

        // terrain_automatique : select -> forcer 1 ou 0
        var terrainAutoVal = parseInt(data.parametre.terrain_automatique, 10);
        document.getElementById('terrain_automatique').value = (terrainAutoVal === 0) ? '0' : '1';

        var matchtermineAutoval = parseInt(data.parametre.matchtermine, 10);
        document.getElementById('matchtermine').value = (matchtermineAutoval === 0) ? '0' : '1';

        var tournoi_cacherAutoval = parseInt(data.parametre.tournoi_cacher, 10);
        document.getElementById('tournoi_cacher').value = (tournoi_cacherAutoval === 0) ? '0' : '1';

        document.getElementById('page-title').textContent = 'Modifier : ' + data.tournoi.nom;

        if (loadingMsg) loadingMsg.style.display = 'none';
        if (editSection) editSection.style.display = 'block';

        genererCategoriesAvecDonnees(data.categories);

    } catch (err) {
        afficherErreur('Erreur réseau : ' + err.message);
    }
}

function afficherErreur(msg) {
    var loadingMsg = document.getElementById('loading-message');
    if (loadingMsg) loadingMsg.style.display = 'none';
    var errorMsg = document.getElementById('error-message');
    if (errorMsg) {
        errorMsg.style.display = 'block';
        errorMsg.textContent = msg;
    }
}

// ==========================================
// GÉNÉRATION DES BLOCS PRÉ-REMPLIS (MODE ÉDITION)
// ==========================================
function genererCategoriesAvecDonnees(categories) {
    var nbre = categories && categories.length > 0 ? categories.length : 1;
    var nbreCatEl = document.getElementById('nbre_categories');
    if (nbreCatEl) nbreCatEl.textContent = nbre;

    var container = document.getElementById('categories-container');
    container.innerHTML = '';

    if (categories && categories.length > 0) {
        categories.forEach(function (cat, idxCat) {
            var id_categorie = idxCat + 1;
            var block = creerBlocCategorie(id_categorie, cat.nom);

            var poulesContainer = block.querySelector('.poules-container');
            poulesContainer.innerHTML = '';

            if (cat.poules && cat.poules.length > 0) {
                cat.poules.forEach(function (poule, idxPoule) {
                    var id_poule = idxPoule + 1;
                    var lettrePoule = indexToLettre(id_poule);
                    var pouleBlock = creerBlocPoule(id_categorie, id_poule, poule.nom);

                    var equipesContainer = pouleBlock.querySelector('.equipes-container');
                    equipesContainer.innerHTML = '';

                    if (poule.equipes && poule.equipes.length > 0) {
                        poule.equipes.forEach(function (eq) {
                            ajouterChampEquipePreserve(equipesContainer, id_categorie, id_poule, eq.nom, lettrePoule);
                        });
                    } else {
                        ajouterChampEquipePreserve(equipesContainer, id_categorie, id_poule, '', lettrePoule);
                        ajouterChampEquipePreserve(equipesContainer, id_categorie, id_poule, '', lettrePoule);
                    }

                    poulesContainer.appendChild(pouleBlock);
                });
            } else {
                var defaultPoule = creerBlocPoule(id_categorie, 1, indexToLettre(1));
                var defEquipes = defaultPoule.querySelector('.equipes-container');
                defEquipes.innerHTML = '';
                ajouterChampEquipePreserve(defEquipes, id_categorie, 1, '', 'A');
                ajouterChampEquipePreserve(defEquipes, id_categorie, 1, '', 'A');
                poulesContainer.appendChild(defaultPoule);
            }

            container.appendChild(block);
        });
    } else {
        var blockVide = creerBlocCategorie(1, '');
        container.appendChild(blockVide);
    }
}

/**
 * Crée un bloc catégorie avec nom pré-rempli
 */
function creerBlocCategorie(id_categorie, nom) {
    var block = document.createElement('div');
    block.className = 'categorie-block';
    block.dataset.idCategorie = id_categorie;

    var lettrePoule = indexToLettre(1);

    block.innerHTML =
        '<div class="categorie-header">' +
        '<h3>Catégorie ' + id_categorie + '</h3>' +
        '<input type="text" name="categorie_nom_' + id_categorie + '" placeholder="Nom de la catégorie" value="' + escapeHtml(nom || '') + '" required>' +
        '<button type="button" class="btn-mini btn-plus" onclick="ajouterPoule(this)">+ Poule</button>' +
        '</div>' +
        '<div class="poules-container" data-id-categorie="' + id_categorie + '">' +
        '<div class="poule-block" data-id-poule="1">' +
        '<div class="poule-header">' +
        '<h4>Poule ' + lettrePoule + '</h4>' +
        '<input type="text" name="poule_nom_' + id_categorie + '_1" placeholder="Nom de la poule" value="' + lettrePoule + '" required>' +
        '<button type="button" class="btn-mini btn-plus" onclick="ajouterEquipe(this)">+ Équipe</button>' +
        '</div>' +
        '<div class="equipes-container"></div>' +
        '</div>' +
        '</div>';

    return block;
}

/**
 * Crée un bloc poule avec nom pré-rempli (lettre auto si nom vide)
 */
function creerBlocPoule(id_categorie, id_poule, nom) {
    var block = document.createElement('div');
    block.className = 'poule-block';
    block.dataset.idPoule = id_poule;

    var lettrePoule = indexToLettre(id_poule);
    var nomAffiche = (nom !== undefined && nom !== null && nom !== '') ? nom : lettrePoule;

    block.innerHTML =
        '<div class="poule-header">' +
        '<h4>Poule ' + lettrePoule + '</h4>' +
        '<input type="text" name="poule_nom_' + id_categorie + '_' + id_poule + '" placeholder="Nom de la poule" value="' + escapeHtml(nomAffiche) + '" required>' +
        '<button type="button" class="btn-mini btn-plus" onclick="ajouterEquipe(this)">+ Équipe</button>' +
        '<button type="button" class="btn-mini btn-minus" onclick="supprimerPoule(this)">−</button>' +
        '</div>' +
        '<div class="equipes-container"></div>';

    return block;
}

/**
 * Ajoute un champ équipe pré-rempli avec un nom (auto si vide)
 */
function ajouterChampEquipePreserve(equipesContainer, id_categorie, id_poule, nom, lettrePoule) {
    var nbreEquipes = equipesContainer.querySelectorAll('.equipe-item').length + 1;
    if (!lettrePoule) lettrePoule = indexToLettre(id_poule);

    var nomAuto = nbreEquipes + lettrePoule;
    var nomFinal = (nom !== undefined && nom !== null && nom !== '') ? nom : nomAuto;

    var div = document.createElement('div');
    div.className = 'equipe-item';

    var input = document.createElement('input');
    input.type = 'text';
    input.name = 'equipe_nom_' + id_categorie + '_' + id_poule + '_' + nbreEquipes;
    input.placeholder = nomAuto;
    input.value = nomFinal;

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
}

// ==========================================
// AJUSTEMENT DU NOMBRE DE CATÉGORIES
// ==========================================
function ajusterCategories(delta) {
    var container = document.getElementById('categories-container');
    var span = document.getElementById('nbre_categories');
    var blocsExistants = container.querySelectorAll('.categorie-block');
    var nbreActuel = blocsExistants.length;

    if (delta > 0) {
        if (nbreActuel >= 30) {
            alert('Maximum 30 catégories');
            return;
        }
        nbreActuel++;
        var nouveauBloc = creerBlocCategorie(nbreActuel, '');
        var poulesContainer = nouveauBloc.querySelector('.poules-container');
        poulesContainer.innerHTML = '';
        var defaultPoule = creerBlocPoule(nbreActuel, 1, 'A');
        var eqCont = defaultPoule.querySelector('.equipes-container');
        ajouterChampEquipePreserve(eqCont, nbreActuel, 1, '', 'A');
        ajouterChampEquipePreserve(eqCont, nbreActuel, 1, '', 'A');
        poulesContainer.appendChild(defaultPoule);

        container.appendChild(nouveauBloc);
    } else {
        if (nbreActuel <= 1) return;
        blocsExistants[blocsExistants.length - 1].remove();
        nbreActuel--;
    }

    if (span) span.textContent = nbreActuel;
}

// ==========================================
// AJOUT / SUPPRESSION POULES
// ==========================================
function ajouterPoule(btn) {
    var categorieBlock = btn.closest('.categorie-block');
    var poulesContainer = categorieBlock.querySelector('.poules-container');
    var id_categorie = parseInt(categorieBlock.dataset.idCategorie);

    var nbrePoules = poulesContainer.querySelectorAll('.poule-block').length + 1;
    if (nbrePoules > 20) {
        alert('Maximum 20 poules par catégorie');
        return;
    }

    var lettrePoule = indexToLettre(nbrePoules);
    var newPoule = creerBlocPoule(id_categorie, nbrePoules, lettrePoule);
    poulesContainer.appendChild(newPoule);

    var eqCont = newPoule.querySelector('.equipes-container');
    ajouterChampEquipePreserve(eqCont, id_categorie, nbrePoules, '', lettrePoule);
    ajouterChampEquipePreserve(eqCont, id_categorie, nbrePoules, '', lettrePoule);
}

function supprimerPoule(btn) {
    var poulesContainer = btn.closest('.poules-container');
    var pouleBlock = btn.closest('.poule-block');
    if (poulesContainer.querySelectorAll('.poule-block').length <= 1) return;
    pouleBlock.remove();
    reordonnerPoules(poulesContainer);
}

/**
 * Réordonne les poules après suppression (lettres + équipes suivent)
 */
function reordonnerPoules(container) {
    var id_categorie = parseInt(container.dataset.idCategorie);
    container.querySelectorAll('.poule-block').forEach(function (p, idx) {
        var newId = idx + 1;
        var newLettre = indexToLettre(newId);
        var ancienId = parseInt(p.dataset.idPoule);

        p.dataset.idPoule = newId;
        p.querySelector('h4').textContent = 'Poule ' + newLettre;

        var input = p.querySelector('.poule-header input[type="text"]');
        if (input) {
            var ancienneLettre = indexToLettre(ancienId);
            if (/^[A-Z]{1,2}$/.test(input.value) || input.value === '') {
                input.value = newLettre;
            }
            input.name = 'poule_nom_' + id_categorie + '_' + newId;
        }

        var equipesContainer = p.querySelector('.equipes-container');
        equipesContainer.querySelectorAll('.equipe-item').forEach(function (item, idxEq) {
            var eqInput = item.querySelector('input');
            var numEquipe = idxEq + 1;
            var ancienNomAuto = numEquipe + ancienneLettre;
            var nouveauNomAuto = numEquipe + newLettre;

            eqInput.name = 'equipe_nom_' + id_categorie + '_' + newId + '_' + numEquipe;
            eqInput.placeholder = nouveauNomAuto;

            if (eqInput.value === ancienNomAuto || eqInput.value === '') {
                eqInput.value = nouveauNomAuto;
            }
        });
    });
}

// ==========================================
// AJOUT / SUPPRESSION ÉQUIPES
// ==========================================
function ajouterEquipe(btn) {
    var pouleBlock = btn.closest('.poule-block');
    var equipesContainer = pouleBlock.querySelector('.equipes-container');
    var id_categorie = parseInt(pouleBlock.closest('.categorie-block').dataset.idCategorie);
    var id_poule = parseInt(pouleBlock.dataset.idPoule);
    var lettrePoule = indexToLettre(id_poule);
    ajouterChampEquipePreserve(equipesContainer, id_categorie, id_poule, '', lettrePoule);
}

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
    var pouleBlock = container.closest('.poule-block');
    var id_poule = pouleBlock ? parseInt(pouleBlock.dataset.idPoule) : 1;
    var lettrePoule = indexToLettre(id_poule);

    container.querySelectorAll('.equipe-item').forEach(function (item, idx) {
        var newId = idx + 1;
        var input = item.querySelector('input');
        var nouveauNomAuto = newId + lettrePoule;

        input.name = input.name.replace(/_(\d+)$/, '_' + newId);

        if (/^\d+[A-Z]{1,2}$/.test(input.value) || input.value === '') {
            input.value = nouveauNomAuto;
        }
        input.placeholder = nouveauNomAuto;
    });
}

// ==========================================
// ENVOI DU FORMULAIRE (CRÉATION OU ÉDITION)
// ==========================================
function setupFormulaire() {
    var form = document.getElementById('form-edit-tournoi');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        var messageEl = document.getElementById('form-message');
        var isCreation = window.modeCreation;

        if (messageEl) {
            messageEl.textContent = isCreation ? 'Création en cours...' : 'Enregistrement en cours...';
            messageEl.className = 'message';
        }

        var payload = collectFormData();
        var url = isCreation ? 'api/create_tournoi.php' : 'api/update_tournoi.php';

        try {
            var response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            var data = await response.json();

            if (data.success) {
                if (isCreation) {
                    if (messageEl) {
                        messageEl.textContent = 'Tournoi créé avec succès !';
                        messageEl.className = 'message success';
                    }
                    setTimeout(function () {
                        window.location.href = 'edit_tournoi.php?id_tournoi=' + data.id_tournoi;
                    }, 800);
                } else {
                    if (messageEl) {
                        messageEl.textContent = 'Modifications enregistrées avec succès !';
                        messageEl.className = 'message success';
                    }
                }
            } else {
                if (messageEl) {
                    messageEl.textContent = 'Erreur : ' + (data.error || 'Erreur inconnue');
                    messageEl.className = 'message error';
                }
            }
        } catch (err) {
            if (messageEl) {
                messageEl.textContent = 'Erreur réseau : ' + err.message;
                messageEl.className = 'message error';
            }
        }
    });
}

/**
 * Collecte toutes les données du formulaire et retourne le payload JSON
 */
function collectFormData() {
    var id_tournoi = document.getElementById('id_tournoi').value;
    var nom = document.getElementById('nom_tournoi').value;
    var nbre_terrain_poule = parseInt(document.getElementById('nbre_terrain_poule').value, 10) || 0;
    var nbre_terrain_phasefinal = parseInt(document.getElementById('nbre_terrain_phasefinal').value, 10) || 0;
    var temps_de_match = parseInt(document.getElementById('temps_de_match').value, 10) || 0;
    var heure_debut_poule = document.getElementById('heure_debut_poule').value;
    var heure_debut_phasefinal = document.getElementById('heure_debut_phasefinal').value;
    var matchtermine = document.getElementById('matchtermine').value;
    var tournoi_cacher = document.getElementById('tournoi_cacher').value;
    var tournoi_password = document.getElementById('tournoi_password').value;


    var show_timerRaw = parseInt(document.getElementById('show_timer').value, 10);
    var show_timer = (show_timerRaw === 1) ? 1 : 0;

    var qrcodeRaw = parseInt(document.getElementById('show_qrcode').value, 10);
    var show_qrcode = (qrcodeRaw === 1) ? 1 : 0;

    // troissets : forcer 1 ou 3 uniquement
    var troissetsRaw = parseInt(document.getElementById('troissets').value, 10);
    var troissets = (troissetsRaw === 1) ? 1 : 3;


    // terrain_automatique : forcer 1 ou 0 uniquement
    var terrainAutoRaw = parseInt(document.getElementById('terrain_automatique').value, 10);
    var terrain_automatique = (terrainAutoRaw === 0) ? 0 : 1;

    var categories = [];

    document.querySelectorAll('.categorie-block').forEach(function (catBlock, idxCat) {
        var id_categorie = idxCat + 1;
        var nomCatInput = catBlock.querySelector('.categorie-header input[type="text"]');
        var nomCat = nomCatInput ? nomCatInput.value : '';

        if (!nomCat) return;

        var poules = [];
        var catPouleBlocks = catBlock.querySelectorAll('.poule-block');

        catPouleBlocks.forEach(function (pouleBlock, idxPoule) {
            var id_poule = idxPoule + 1;
            var nomPouleInput = pouleBlock.querySelector('.poule-header input[type="text"]');
            var nomPoule = nomPouleInput ? nomPouleInput.value : '';

            if (!nomPoule) return;

            var equipes = [];
            var equipesContainer = pouleBlock.querySelector('.equipes-container');
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
        nom_tournoi: nom, // compat create_tournoi.php
        nbre_terrain_poule: nbre_terrain_poule,
        nbre_terrain_phasefinal: nbre_terrain_phasefinal,
        temps_de_match: temps_de_match,
        heure_debut_poule: heure_debut_poule,
        heure_debut_phasefinal: heure_debut_phasefinal,
        troissets: troissets,
        terrain_automatique: terrain_automatique,
        matchtermine: matchtermine,
        tournoi_password: tournoi_password,
        tournoi_cacher: tournoi_cacher,
        categories: categories,
        show_timer: show_timer,
        show_qrcode: show_qrcode
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

/**
 * Empêche la saisie de caractères invalides dans les champs number
 * Autorise : chiffres, Backspace, Delete, Tab, flèches, Entrée
 * Bloque : e, E, +, -, . , , et toute autre lettre
 */
function blockInvalidNumberKeys(event) {
    var touchesAutorisees = [
        'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
        'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'
    ];

    if (touchesAutorisees.indexOf(event.key) !== -1) {
        return true;
    }

    // Autoriser Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
    if (event.ctrlKey || event.metaKey) {
        return true;
    }

    // Bloquer tout ce qui n'est pas un chiffre (0-9)
    if (!/^[0-9]$/.test(event.key)) {
        event.preventDefault();
        return false;
    }

    return true;
}

/**
 * Bloque le collage de contenu non numérique dans les inputs number
 */
function blockInvalidPaste(event) {
    var clipboardData = event.clipboardData || window.clipboardData;
    var pastedText = clipboardData ? clipboardData.getData('text') : '';

    if (!/^[0-9]+$/.test(pastedText)) {
        event.preventDefault();
        return false;
    }

    return true;
}

/**
 * Sécurise un input number en supprimant tout caractère non numérique
 * collé (paste) et en clampant min/max, sans décimales
 */
function securiserInputNumber(input) {
    input.addEventListener('input', function () {
        // Supprime tout ce qui n'est pas un chiffre (bloque aussi les décimales)
        var valeurPropre = this.value.replace(/[^0-9]/g, '');
        this.value = valeurPropre;
    });

    input.addEventListener('blur', function () {
        var min = this.min !== '' ? parseInt(this.min, 10) : null;
        var max = this.max !== '' ? parseInt(this.max, 10) : null;
        var val = parseInt(this.value, 10);

        if (isNaN(val)) return;

        if (min !== null && val < min) this.value = min;
        if (max !== null && val > max) this.value = max;
    });
}

/**
 * Initialise la sécurisation sur tous les inputs number du formulaire
 * (y compris ceux ajoutés dynamiquement grâce à la délégation d'événements)
 */
function initSecuriteInputsNumber() {
    // Sécurise les inputs déjà présents au chargement
    document.querySelectorAll('input[type="number"]').forEach(function (input) {
        securiserInputNumber(input);
    });

    // Délégation d'événements pour tout futur input[type=number] ajouté dynamiquement
    document.addEventListener('keydown', function (event) {
        if (event.target && event.target.type === 'number') {
            blockInvalidNumberKeys(event);
        }
    });

    document.addEventListener('paste', function (event) {
        if (event.target && event.target.type === 'number') {
            blockInvalidPaste(event);
        }
    });

    document.addEventListener('input', function (event) {
        if (event.target && event.target.type === 'number') {
            var valeurPropre = event.target.value.replace(/[^0-9]/g, '');
            if (valeurPropre !== event.target.value) {
                event.target.value = valeurPropre;
            }
        }
    });
}

function exporterTournoi(idTournoi) {
    window.location.href = `api/exporter_tournoi.php?id_tournoi=${idTournoi}`;
}