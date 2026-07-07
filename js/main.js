/**
 * main.js - Gestion des tournois
 * Liste des tournois + Création dynamique de tournoi
 */

// ==========================================
// INITIALISATION
// ==========================================

document.addEventListener('DOMContentLoaded', () => {
    chargerTournois();
    genererCategories();
    setupFormulaire();
});

// ==========================================
// LISTE DES TOURNOIS
// ==========================================

/**
 * Charge et affiche la liste des tournois existants
 */
async function chargerTournois() {
    const listeDiv = document.getElementById('liste-tournois');
    listeDiv.innerHTML = '<p>Chargement...</p>';

    try {
        const res = await fetch('api/get_tournois.php');
        const text = await res.text();
        console.log('Réponse brute get_tournois:', text);

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            listeDiv.innerHTML = '<p style="color:red">Réponse invalide du serveur (voir console)</p>';
            console.error(text);
            return;
        }

        if (!data.success) {
            listeDiv.innerHTML = '<p style="color:red">Erreur: ' + data.error + '</p>';
            return;
        }

        if (data.tournois.length === 0) {
            listeDiv.innerHTML = '<p>Aucun tournoi pour le moment.</p>';
            return;
        }

        listeDiv.innerHTML = '';
        data.tournois.forEach(t => {
            const div = document.createElement('div');
            div.className = 'tournoi-item';
            div.innerHTML = `
                <span><strong>${t.nom}</strong> (ID: ${t.id_tournoi})</span>
                <a href="index.php?id_tournoi=${t.id_tournoi}">Ouvrir</a>
                <a href="edit_tournoi.php?id_tournoi=${t.id_tournoi}">Modifier</a>
            `;
            listeDiv.appendChild(div);
        });

    } catch (err) {
        listeDiv.innerHTML = '<p style="color:red">Erreur réseau</p>';
        console.error(err);
    }
}

document.addEventListener('DOMContentLoaded', chargerTournois);


/**
 * Configure le formulaire de création
 */
function setupFormulaire() {
    const form = document.getElementById('form-create-tournoi');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const messageEl = document.getElementById('form-message');
        messageEl.textContent = 'Création en cours...';
        messageEl.className = 'message';

        const payload = collectFormData();

        try {
            const response = await fetch('api/create_tournoi.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (data.success) {
                messageEl.textContent = 'Tournoi créé avec succès !';
                messageEl.className = 'message success';
                form.reset();
                document.getElementById('categories-container').innerHTML = '';
                document.getElementById('nbre_categories').textContent = '1';
                document.getElementById('categories-container').appendChild(creerBlocCategorie(1));
                chargerTournois();
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

// ==========================================
// UTILITAIRES DE CONVERSION
// ==========================================

/**
 * Convertit un index (1-based) en lettre alphabétique
 * 1 -> A, 2 -> B, ..., 26 -> Z, 27 -> AA, 28 -> AB, etc.
 */
function indexToLettre(index) {
    let result = '';
    while (index > 0) {
        index--;
        result = String.fromCharCode((index % 26) + 65) + result;
        index = Math.floor(index / 26);
    }
    return result;
}

/**
 * Vérifie si un nom de poule semble auto-généré (ex: A, B, AA)
 */
function estNomPouleAutoGenere(nom) {
    return /^[A-Z]{1,2}$/.test(nom);
}

/**
 * Vérifie si un nom d'équipe semble auto-généré (ex: 1A, 2A, 12AB)
 */
function estNomEquipeAutoGenere(nom) {
    return /^\d+[A-Z]{1,2}$/.test(nom);
}

// ==========================================
// GÉNÉRATION DYNAMIQUE CATÉGORIES / POULES / ÉQUIPES
// ==========================================

/**
 * Génère le bloc catégories initial
 */
function genererCategories() {
    const nbre = parseInt(document.getElementById('nbre_categories').textContent) || 1;
    const container = document.getElementById('categories-container');
    if (!container) return;
    container.innerHTML = '';
    for (let i = 0; i < nbre; i++) {
        container.appendChild(creerBlocCategorie(i + 1));
    }
}

/**
 * Ajuste le nombre de catégories (+1 ou -1)
 */
function ajusterCategories(delta) {
    const container = document.getElementById('categories-container');
    const span = document.getElementById('nbre_categories');
    const blocsExistants = container.querySelectorAll('.categorie-block');
    let nbreActuel = blocsExistants.length;

    if (delta > 0) {
        if (nbreActuel >= 10) return alert('Maximum 10 catégories');
        nbreActuel++;
        container.appendChild(creerBlocCategorie(nbreActuel));
    } else {
        if (nbreActuel <= 1) return;
        blocsExistants[blocsExistants.length - 1].remove();
        nbreActuel--;
    }

    span.textContent = nbreActuel;
}


/**
 * Crée un bloc catégorie complet
 */
function creerBlocCategorie(id_categorie) {
    const block = document.createElement('div');
    block.className = 'categorie-block';
    block.dataset.idCategorie = id_categorie;

    // Première poule = A
    const lettrePoule = indexToLettre(1);

    block.innerHTML = `
        <div class="categorie-header">
            <h3>Catégorie ${id_categorie}</h3>
            <input type="text" name="categorie_nom_${id_categorie}" placeholder="Nom de la catégorie" required>
            <button type="button" class="btn-mini btn-plus" onclick="ajouterPoule(this)">+ Poule</button>
        </div>
        <div class="poules-container" data-id-categorie="${id_categorie}">
            <div class="poule-block" data-id-poule="1">
                <div class="poule-header">
                    <h4>Poule ${lettrePoule}</h4>
                    <input type="text" name="poule_nom_${id_categorie}_1" value="${lettrePoule}" required>
                    <button type="button" class="btn-mini btn-plus" onclick="ajouterEquipe(this)">+ Équipe</button>
                </div>
                <div class="equipes-container"></div>
            </div>
        </div>
    `;

    // Ajouter 4 équipes par défaut
    const firstPoule = block.querySelector('.poule-block');
    ajouterEquipesInitial(firstPoule, 4);

    return block;
}

/**
 * Ajoute une poule à une catégorie
 */
function ajouterPoule(btn) {
    const categorieBlock = btn.closest('.categorie-block');
    const poulesContainer = categorieBlock.querySelector('.poules-container');
    const id_categorie = parseInt(categorieBlock.dataset.idCategorie);

    const nbrePoules = poulesContainer.querySelectorAll('.poule-block').length + 1;
    if (nbrePoules > 20) return alert('Maximum 20 poules par catégorie');

    // Calculer la lettre pour cette nouvelle poule
    const lettrePoule = indexToLettre(nbrePoules);

    const newPoule = document.createElement('div');
    newPoule.className = 'poule-block';
    newPoule.dataset.idPoule = nbrePoules;

    newPoule.innerHTML = `
        <div class="poule-header">
            <h4>Poule ${lettrePoule}</h4>
            <input type="text" name="poule_nom_${id_categorie}_${nbrePoules}" value="${lettrePoule}" required>
            <button type="button" class="btn-mini btn-plus" onclick="ajouterEquipe(this)">+ Équipe</button>
            <button type="button" class="btn-mini btn-minus" onclick="supprimerPoule(this)">−</button>
        </div>
        <div class="equipes-container"></div>
    `;

    poulesContainer.appendChild(newPoule);
    ajouterEquipesInitial(newPoule, 2);
}

/**
 * Supprime une poule
 */
function supprimerPoule(btn) {
    const poulesContainer = btn.closest('.poules-container');
    const pouleBlock = btn.closest('.poule-block');
    if (poulesContainer.querySelectorAll('.poule-block').length <= 1) return;
    pouleBlock.remove();
    reordonnerPoules(poulesContainer);
}

/**
 * Réordonne les indices des poules après suppression
 * Met à jour les lettres ET les noms des équipes (si auto-générés)
 */
function reordonnerPoules(container) {
    const id_categorie = parseInt(container.dataset.idCategorie);
    container.querySelectorAll('.poule-block').forEach((p, idx) => {
        const newId = idx + 1;
        const newLettre = indexToLettre(newId);

        p.dataset.idPoule = newId;

        // Mettre à jour le titre h4
        p.querySelector('h4').textContent = `Poule ${newLettre}`;

        // Mettre à jour l'input du nom de la poule (si auto-généré)
        const inputNomPoule = p.querySelector('.poule-header > input[type="text"]');
        if (inputNomPoule) {
            inputNomPoule.name = `poule_nom_${id_categorie}_${newId}`;
            if (estNomPouleAutoGenere(inputNomPoule.value)) {
                inputNomPoule.value = newLettre;
            }
        }

        // Mettre à jour les équipes de cette poule
        mettreAJourEquipes(p, newLettre);
    });
}

/**
 * Met à jour les noms d'équipes selon la lettre de la poule
 * Ne modifie que les équipes avec un nom auto-généré
 */
function mettreAJourEquipes(pouleBlock, lettrePoule) {
    const equipesContainer = pouleBlock.querySelector('.equipes-container');
    const equipes = equipesContainer.querySelectorAll('.equipe-item');

    equipes.forEach((item, idx) => {
        const input = item.querySelector('input[type="text"]');
        if (input && estNomEquipeAutoGenere(input.value)) {
            // Extraire le numéro d'équipe et construire le nouveau nom
            const match = input.value.match(/^(\d+)/);
            if (match) {
                const numEquipe = match[1];
                input.value = `${numEquipe}${lettrePoule}`;
            }
        }
    });
}

/**
 * Ajoute les équipes initiales par défaut
 */
function ajouterEquipesInitial(pouleBlock, nbre) {
    for (let i = 0; i < nbre; i++) {
        ajouterChampEquipe(pouleBlock);
    }
}

/**
 * Ajoute un champ équipe à une poule
 */
function ajouterEquipe(btn) {
    const pouleBlock = btn.closest('.poule-block');
    ajouterChampEquipe(pouleBlock);
}

/**
 * Crée et ajoute un champ équipe avec nom auto-généré
 */
function ajouterChampEquipe(pouleBlock) {
    const equipesContainer = pouleBlock.querySelector('.equipes-container');
    const nbreEquipes = equipesContainer.querySelectorAll('.equipe-item').length + 1;

    const categorieBlock = pouleBlock.closest('.categorie-block');
    const id_categorie = parseInt(categorieBlock.dataset.idCategorie);
    const id_poule = parseInt(pouleBlock.dataset.idPoule);

    // Récupérer la lettre de la poule
    const inputNomPoule = pouleBlock.querySelector('.poule-header > input[type="text"]');
    const lettrePoule = inputNomPoule ? inputNomPoule.value : indexToLettre(id_poule);

    const div = document.createElement('div');
    div.className = 'equipe-item';

    const input = document.createElement('input');
    input.type = 'text';
    input.name = `equipe_nom_${id_categorie}_${id_poule}_${nbreEquipes}`;
    // Nom auto-généré : numéro + lettre poule (ex: 1A, 2A, 1B)
    input.value = `${nbreEquipes}${lettrePoule}`;

    const btnSuppr = document.createElement('button');
    btnSuppr.type = 'button';
    btnSuppr.className = 'btn-mini btn-minus';
    btnSuppr.textContent = '−';
    btnSuppr.onclick = () => {
        div.remove();
        reordonnerEquipes(equipesContainer);
    };

    div.appendChild(input);
    div.appendChild(btnSuppr);
    equipesContainer.appendChild(div);
}

/**
 * Supprime une équipe
 */
function supprimerEquipe(btn) {
    const equipeItem = btn.closest('.equipe-item');
    const equipesContainer = equipeItem.closest('.equipes-container');
    equipeItem.remove();
    reordonnerEquipes(equipesContainer);
}

/**
 * Réordonne les équipes après suppression
 * Met à jour les noms (si auto-générés) et préserve le numéro de l'équipe
 */
function reordonnerEquipes(container) {
    const pouleBlock = container.closest('.poule-block');

    // Récupérer la lettre de la poule
    const inputNomPoule = pouleBlock.querySelector('.poule-header > input[type="text"]');
    const lettrePoule = inputNomPoule ? inputNomPoule.value : indexToLettre(parseInt(pouleBlock.dataset.idPoule));

    container.querySelectorAll('.equipe-item').forEach((item, idx) => {
        const newId = idx + 1;
        const input = item.querySelector('input[type="text"]');

        // Mettre à jour le name (indices)
        const categorieBlock = pouleBlock.closest('.categorie-block');
        const id_categorie = parseInt(categorieBlock.dataset.idCategorie);
        const id_poule = parseInt(pouleBlock.dataset.idPoule);
        input.name = `equipe_nom_${id_categorie}_${id_poule}_${newId}`;

        // Mettre à jour le nom de l'équipe (si auto-généré)
        if (estNomEquipeAutoGenere(input.value)) {
            input.value = `${newId}${lettrePoule}`;
        }
    });
}

// ==========================================
// COLLECTE ET ENVOI DES DONNÉES
// ==========================================

/**
 * Collecte toutes les données du formulaire et retourne le payload JSON
 */
function collectFormData() {
    const nom = document.getElementById('nom_tournoi').value;
    const nbre_terrain_poule = parseInt(document.getElementById('nbre_terrain_poule').value) || 0;
    const nbre_terrain_phasefinal = parseInt(document.getElementById('nbre_terrain_phasefinal').value) || 0;
    const temps_de_match = parseInt(document.getElementById('temps_de_match').value) || 0;
    const heure_debut_poule = document.getElementById('heure_debut_poule').value;
    const heure_debut_phasefinal = document.getElementById('heure_debut_phasefinal').value;
    const troissets = document.getElementById('troissets').value;

    const categories = [];

    document.querySelectorAll('.categorie-block').forEach((catBlock, idxCat) => {
        const id_categorie = idxCat + 1;
        const nomCat = catBlock.querySelector('input[type="text"]').value;

        if (!nomCat) return;

        const poules = [];
        catBlock.querySelectorAll('.poule-block').forEach((pouleBlock, idxPoule) => {
            const id_poule = idxPoule + 1;
            const allPouleInputs = catBlock.querySelectorAll('input[name^="poule_nom_"]');
            const nomPoule = allPouleInputs[idxPoule] ? allPouleInputs[idxPoule].value : '';

            if (!nomPoule) return;

            const equipes = [];
            const equipesContainer = catBlock.querySelectorAll('.poule-block')[idxPoule].querySelector('.equipes-container');
            equipesContainer.querySelectorAll('.equipe-item input').forEach((input, idxEq) => {
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
        nom_tournoi: nom,
        nbre_terrain_poule: nbre_terrain_poule,
        nbre_terrain_phasefinal: nbre_terrain_phasefinal,
        temps_de_match: temps_de_match,
        heure_debut_poule: heure_debut_poule,
        heure_debut_phasefinal: heure_debut_phasefinal,
        troissets: troissets,
        categories: categories
    };
}

// ==========================================
// UTILITAIRES
// ==========================================

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('fr-FR');
}
