// ================= NAVIGATION ENTRE SECTIONS =================
const sectionListe = document.getElementById('section-liste');
const sectionCreation = document.getElementById('section-creation');

document.getElementById('btn-nouveau-tournoi').addEventListener('click', () => {
    sectionListe.classList.add('hidden');
    sectionCreation.classList.remove('hidden');
});

document.getElementById('btn-annuler').addEventListener('click', () => {
    sectionCreation.classList.add('hidden');
    sectionListe.classList.remove('hidden');
    chargerTournois();
});

// ================= CHARGEMENT LISTE TOURNOIS =================
async function chargerTournois() {
    const conteneur = document.getElementById('liste-tournois');
    conteneur.innerHTML = '<p class="loading">Chargement...</p>';

    try {
        const res = await fetch('api/get_tournois.php');
        const data = await res.json();

        if (!data.success) {
            conteneur.innerHTML = `<p class="error">${data.error}</p>`;
            return;
        }

        if (data.tournois.length === 0) {
            conteneur.innerHTML = '<p>Aucun tournoi en cours.</p>';
            return;
        }

        conteneur.innerHTML = '';
        data.tournois.forEach(t => {
            const div = document.createElement('div');
            div.classList.add('tournoi-card');
            div.innerHTML = `
                <h3>${t.nom}</h3>
                <p>Terrains poule: ${t.nbre_terrain_poule} | Terrains finale: ${t.nbre_terrain_phasefinal}</p>
                <p>Début poules: ${t.heure_debut_poule} | Début finale: ${t.heure_debut_phasefinal}</p>
                <p>Durée match: ${t.temps_de_match} min</p>
            `;
            conteneur.appendChild(div);
        });

    } catch (err) {
        conteneur.innerHTML = `<p class="error">Erreur de chargement: ${err}</p>`;
    }
}

chargerTournois();

// ================= GENERATION DYNAMIQUE : CATEGORIES / POULES / EQUIPES =================
const nbreCategorieInput = document.getElementById('nbre_categorie');
const conteneurCategories = document.getElementById('conteneur-categories');

function genererCategories() {
    const nbCat = parseInt(nbreCategorieInput.value) || 0;
    conteneurCategories.innerHTML = '';

    for (let c = 1; c <= nbCat; c++) {
        const divCat = document.createElement('div');
        divCat.classList.add('bloc-categorie');
        divCat.innerHTML = `
            <div class="bloc-titre">Catégorie ${c}</div>
            <label>Nom de la catégorie
                <input type="text" name="categorie_nom_${c}" required>
            </label>
            <label>Nombre de poules
                <input type="number" class="nbre_poule" data-cat="${c}" min="1" value="1" required>
            </label>
            <div class="conteneur-poules" id="poules-cat-${c}"></div>
        `;
        conteneurCategories.appendChild(divCat);
    }

    // Ajout des écouteurs sur les nouveaux champs "nombre de poules"
    document.querySelectorAll('.nbre_poule').forEach(input => {
        input.addEventListener('input', () => genererPoules(input.dataset.cat, input.value));
    });

    // Génération initiale des poules (1 poule par défaut)
    for (let c = 1; c <= nbCat; c++) {
        genererPoules(c, 1);
    }
}

function genererPoules(idCat, nbPoules) {
    nbPoules = parseInt(nbPoules) || 0;
    const conteneur = document.getElementById(`poules-cat-${idCat}`);
    conteneur.innerHTML = '';

    for (let p = 1; p <= nbPoules; p++) {
        const divPoule = document.createElement('div');
        divPoule.classList.add('bloc-poule');
        divPoule.innerHTML = `
            <div class="bloc-titre">Poule ${p} (Catégorie ${idCat})</div>
            <label>Nom de la poule
                <input type="text" name="poule_nom_${idCat}_${p}" value="Poule ${p}" required>
            </label>
            <label>Nombre d'équipes
                <input type="number" class="nbre_equipe" data-cat="${idCat}" data-poule="${p}" min="1" value="4" required>
            </label>
            <div class="conteneur-equipes" id="equipes-cat-${idCat}-poule-${p}"></div>
        `;
        conteneur.appendChild(divPoule);
    }

    document.querySelectorAll(`#poules-cat-${idCat} .nbre_equipe`).forEach(input => {
        input.addEventListener('input', () => genererEquipes(input.dataset.cat, input.dataset.poule, input.value));
        genererEquipes(input.dataset.cat, input.dataset.poule, input.value);
    });
}

function genererEquipes(idCat, idPoule, nbEquipes) {
    nbEquipes = parseInt(nbEquipes) || 0;
    const conteneur = document.getElementById(`equipes-cat-${idCat}-poule-${idPoule}`);
    conteneur.innerHTML = '';

    for (let e = 1; e <= nbEquipes; e++) {
        const label = document.createElement('label');
        label.innerHTML = `Équipe ${e}
            <input type="text" name="equipe_nom_${idCat}_${idPoule}_${e}" value="Equipe ${e}" required>
        `;
        conteneur.appendChild(label);
    }
}

nbreCategorieInput.addEventListener('input', genererCategories);
genererCategories(); // Génération initiale

// ================= SOUMISSION DU FORMULAIRE (SANS RELOAD) =================
document.getElementById('form-tournoi').addEventListener('submit', async (e) => {
    e.preventDefault();

    const messageRetour = document.getElementById('message-retour');
    messageRetour.textContent = '';
    messageRetour.className = '';

    const formData = new FormData(e.target);

    // Construction de la structure JSON pour l'envoi
    const nbCat = parseInt(nbreCategorieInput.value) || 0;
    const categories = [];

    for (let c = 1; c <= nbCat; c++) {
        const nomCat = formData.get(`categorie_nom_${c}`);
        const nbPoules = parseInt(document.querySelector(`.nbre_poule[data-cat="${c}"]`).value) || 0;
        const poules = [];

        for (let p = 1; p <= nbPoules; p++) {
            const nomPoule = formData.get(`poule_nom_${c}_${p}`);
            const nbEquipes = parseInt(document.querySelector(`.nbre_equipe[data-cat="${c}"][data-poule="${p}"]`).value) || 0;
            const equipes = [];

            for (let e = 1; e <= nbEquipes; e++) {
                equipes.push(formData.get(`equipe_nom_${c}_${p}_${e}`));
            }

            poules.push({ id_poule: p, nom: nomPoule, equipes: equipes });
        }

        categories.push({ id_categorie: c, nom: nomCat, poules: poules });
    }

    const payload = {
        nom_tournoi: formData.get('nom_tournoi'),
        nbre_terrain_poule: formData.get('nbre_terrain_poule'),
        nbre_terrain_phasefinal: formData.get('nbre_terrain_phasefinal'),
        temps_de_match: formData.get('temps_de_match'),
        heure_debut_poule: formData.get('heure_debut_poule'),
        heure_debut_phasefinal: formData.get('heure_debut_phasefinal'),
        categories: categories
    };

    // try {
    //     const res = await fetch('api/create_tournoi.php', {
    //         method: 'POST',
    //         headers: { 'Content-Type': 'application/json' },
    //         body: JSON.stringify(payload)
    //     });

    //     const data = await res.json();

    //     if (data.success) {
    //         messageRetour.textContent = 'Tournoi créé avec succès !';
    //         messageRetour.className = 'success';
    //         e.target.reset();
    //         genererCategories();

    //         setTimeout(() => {
    //             sectionCreation.classList.add('hidden');
    //             sectionListe.classList.remove('hidden');
    //             chargerTournois();
    //         }, 1000);
    //     } else {
    //         messageRetour.textContent = 'Erreur: ' + data.error;
    //         messageRetour.className = 'error';
    //     }

    // } catch (err) {
    //     messageRetour.textContent = 'Erreur réseau: ' + err;
    //     messageRetour.className = 'error';
    // }

    try {
        const res = await fetch('api/create_tournoi.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const text = await res.text(); // récupère le texte brut
        console.log('Réponse brute:', text); // debug

        let data;
        try {
            data = JSON.parse(text);
        } catch (parseErr) {
            messageRetour.textContent = 'Réponse serveur invalide (voir console)';
            messageRetour.className = 'error';
            console.error('Impossible de parser JSON:', text);
            return;
        }

        if (data.success) {
            messageRetour.textContent = 'Tournoi créé avec succès !';
            messageRetour.className = 'success';
            e.target.reset();
            genererCategories();

            setTimeout(() => {
                sectionCreation.classList.add('hidden');
                sectionListe.classList.remove('hidden');
                chargerTournois();
            }, 1000);
        } else {
            messageRetour.textContent = 'Erreur: ' + data.error;
            messageRetour.className = 'error';
        }

    } catch (err) {
        messageRetour.textContent = 'Erreur réseau: ' + err;
        messageRetour.className = 'error';
    }


});
