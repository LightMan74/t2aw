'use strict';

var tirageState = {
    categorieBlock: null,
    idCategorie: null
};

/**
 * Ouvre la modale de tirage pour une catégorie donnée
 */
function ouvrirTirageModal(btn) {
    var categorieBlock = btn.closest('.categorie-block');
    var idCategorie = parseInt(categorieBlock.dataset.idCategorie);
    var nbPoules = categorieBlock.querySelectorAll('.poule-block').length;

    tirageState.categorieBlock = categorieBlock;
    tirageState.idCategorie = idCategorie;

    var modal = document.getElementById('tirage-modal');
    if (!modal) {
        modal = creerModaleTirage();
        document.body.appendChild(modal);
    }

    document.getElementById('tirage-nb-poules').value = nbPoules;
    document.getElementById('tirage-teams-input').value = '';
    document.getElementById('tirage-result').innerHTML = '';
    document.getElementById('tirage-btn-appliquer').style.display = 'none';

    modal.style.display = 'flex';
}

function fermerTirageModal() {
    var modal = document.getElementById('tirage-modal');
    if (modal) modal.style.display = 'none';
}

/**
 * Crée la structure HTML de la modale (une seule fois)
 */
function creerModaleTirage() {
    var modal = document.createElement('div');
    modal.id = 'tirage-modal';
    modal.className = 'modal-overlay';

    modal.innerHTML =
        '<div class="modal-box">' +
        '<div class="modal-header">' +
        '<h3>Tirage au sort</h3>' +
        '<button type="button" class="btn-close" onclick="fermerTirageModal()">×</button>' +
        '</div>' +
        '<div class="modal-body">' +
        '<div class="form-group">' +
        '<label for="tirage-nb-poules">Nombre de poules</label>' +
        '<input type="number" id="tirage-nb-poules" min="1" max="20" value="4">' +
        '</div>' +
        '<div class="form-group">' +
        '<label for="tirage-teams-input">Liste des équipes (une par ligne, * = tête de série)</label>' +
        '<textarea id="tirage-teams-input" rows="12" placeholder="*Équipe A&#10;Équipe B&#10;*Équipe C&#10;Équipe D"></textarea>' +
        '</div>' +
        '<button type="button" class="btn btn-primary" onclick="lancerTirage()">Lancer le tirage</button>' +
        '<div id="tirage-result" class="tirage-result"></div>' +
        '</div>' +
        '<div class="modal-footer">' +
        '<button type="button" id="tirage-btn-appliquer" class="btn btn-primary" style="display:none;" onclick="appliquerTirage()">Appliquer</button>' +
        '<button type="button" class="btn btn-back" onclick="fermerTirageModal()">Annuler</button>' +
        '</div>' +
        '</div>';

    return modal;
}

/**
 * Parse le texte et génère la répartition en poules
 */
var tirageResultat = null;

function lancerTirage() {
    var text = document.getElementById('tirage-teams-input').value;
    var nbPoules = parseInt(document.getElementById('tirage-nb-poules').value, 10);

    if (!nbPoules || nbPoules < 1) {
        alert('Nombre de poules invalide');
        return;
    }

    var lines = text.split('\n').map(function (l) { return l.trim(); }).filter(function (l) { return l !== ''; });

    if (lines.length === 0) {
        alert('Veuillez saisir au moins une équipe');
        return;
    }

    var teams = lines.map(function (line) {
        var isSeeded = line.startsWith('*');
        return {
            name: isSeeded ? line.substring(1).trim() : line,
            seeded: isSeeded
        };
    });

    tirageResultat = genererPoulesTirage(teams, nbPoules);
    afficherApercu(tirageResultat);

    document.getElementById('tirage-btn-appliquer').style.display = 'inline-block';
}

/**
 * Algorithme de répartition avec têtes de série
 */
function genererPoulesTirage(teams, nbPoules) {
    var seeded = teams.filter(function (t) { return t.seeded; });
    var others = shuffleArray(teams.filter(function (t) { return !t.seeded; }));

    var pools = [];
    for (var i = 0; i < nbPoules; i++) pools.push([]);

    // Répartition des têtes de série (une par poule max, dans l'ordre)
    seeded.forEach(function (team, i) {
        pools[i % nbPoules].push(team);
    });

    // Répartition équilibrée des autres équipes (toujours dans la poule la moins remplie)
    others.forEach(function (team) {
        var poolMinIndex = 0;
        var minCount = pools[0].length;
        for (var i = 1; i < pools.length; i++) {
            if (pools[i].length < minCount) {
                minCount = pools[i].length;
                poolMinIndex = i;
            }
        }
        pools[poolMinIndex].push(team);
    });

    // Mélanger l'ordre d'affichage à l'intérieur de chaque poule
    // (la tête de série n'est plus forcément en 1ère position)
    pools = pools.map(function (pool) {
        return shuffleArray(pool);
    });

    return pools;
}

function shuffleArray(array) {
    var arr = array.slice();
    for (var i = arr.length - 1; i > 0; i--) {
        var j = Math.floor(Math.random() * (i + 1));
        var tmp = arr[i];
        arr[i] = arr[j];
        arr[j] = tmp;
    }
    return arr;
}

/**
 * Affiche l'aperçu du tirage dans la modale
 */
function afficherApercu(pools) {
    var container = document.getElementById('tirage-result');
    var html = '<h4>Aperçu du tirage</h4>';

    pools.forEach(function (pool, i) {
        html += '<div class="apercu-poule">';
        html += '<strong>Poule ' + indexToLettre(i + 1) + '</strong><ul>';
        pool.forEach(function (team) {
            html += '<li>' + (team.seeded ? '⭐ ' : '') + escapeHtml(team.name) + '</li>';
        });
        html += '</ul></div>';
    });

    container.innerHTML = html;
}

/**
 * Applique le tirage : recrée les poules de la catégorie avec le résultat
 */
function appliquerTirage() {
    if (!tirageResultat || !tirageState.categorieBlock) return;

    var idCategorie = tirageState.idCategorie;
    var poulesContainer = tirageState.categorieBlock.querySelector('.poules-container');

    // Récupérer les noms de poules existants si possible (garder A, B, C... sinon)
    poulesContainer.innerHTML = '';

    tirageResultat.forEach(function (pool, idxPoule) {
        var idPoule = idxPoule + 1;
        var lettrePoule = indexToLettre(idPoule);
        var pouleBlock = creerBlocPoule(idCategorie, idPoule, lettrePoule);

        var equipesContainer = pouleBlock.querySelector('.equipes-container');
        equipesContainer.innerHTML = '';

        pool.forEach(function (team) {
            ajouterChampEquipePreserve(equipesContainer, idCategorie, idPoule, team.name, lettrePoule);
        });

        poulesContainer.appendChild(pouleBlock);
    });

    fermerTirageModal();
}