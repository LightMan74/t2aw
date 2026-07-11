let currentTab = 'matchs';
let refreshTimer = null;
let countdownTimer = null;
let secondsLeft = 10;

document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    initDarkMode();
    updateHorloge();
    setInterval(updateHorloge, 1000);

    chargerTournoiInfo();
    chargerOngletActif();

    initRefresh();

    document.getElementById('refresh-interval').addEventListener('change', (e) => {
        let val = parseInt(e.target.value);
        if (isNaN(val) || val < 3) val = 3;
        e.target.value = val;
        initRefresh();
    });
});

// ----- Dark mode -----
function initDarkMode() {
    const btn = document.getElementById('dark-mode-toggle');
    const saved = localStorage.getItem('darkMode');

    if (saved === 'true') {
        document.body.classList.add('dark');
        btn.textContent = '☀️';
    }

    btn.addEventListener('click', () => {
        document.body.classList.toggle('dark');
        const isDark = document.body.classList.contains('dark');
        localStorage.setItem('darkMode', isDark);
        btn.textContent = isDark ? '☀️' : '🌙';
    });
}

// ----- Onglets -----
function initTabs() {
    const btns = document.querySelectorAll('.tab-btn');
    btns.forEach(btn => {
        btn.addEventListener('click', () => {
            btns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
            const tab = btn.dataset.tab;
            document.getElementById('tab-' + tab).classList.add('active');

            currentTab = tab;
            chargerOngletActif();
        });
    });
}

function chargerOngletActif() {
    if (currentTab === 'matchs') chargerMatchs();
    if (currentTab === 'classement') chargerClassement();
    if (currentTab === 'joueurs') chargerJoueurs();
}

// ----- Horloge -----
function updateHorloge() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2, '0');
    const m = String(now.getMinutes()).padStart(2, '0');
    const s = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('heure-actuelle').textContent = `${h}:${m}:${s}`;
}

// ----- Auto refresh -----
function initRefresh() {
    if (refreshTimer) clearInterval(refreshTimer);
    if (countdownTimer) clearInterval(countdownTimer);

    const interval = parseInt(document.getElementById('refresh-interval').value) || 10;
    secondsLeft = interval;
    document.getElementById('countdown').textContent = secondsLeft;

    countdownTimer = setInterval(() => {
        secondsLeft--;
        if (secondsLeft <= 0) {
            secondsLeft = interval;
            chargerOngletActif();
        }
        document.getElementById('countdown').textContent = secondsLeft;
    }, 1000);
}

// ----- Appel générique fetch -----
function fetchJSON(url, callback) {
    fetch(url)
        .then(res => res.json())
        .then(data => callback(data))
        .catch(err => console.error('Erreur fetch:', err));
}

// ----- Infos tournoi -----
function chargerTournoiInfo() {
    fetchJSON(`api/view_tournoi.php?id_tournoi=${ID_TOURNOI}`, (data) => {
        if (data.tournoi) {
            document.getElementById('nom-tournoi').textContent = data.tournoi.nom;
        } else {
            document.getElementById('nom-tournoi').textContent = 'Tournoi introuvable';
        }
    });
}

// ----- Matchs -----
function chargerMatchs() {
    fetchJSON(`api/view_matchs.php?id_tournoi=${ID_TOURNOI}`, (data) => {
        afficherListeMatchs('matchs-en-cours', data.en_cours, 'Aucun match en cours');
        afficherListeMatchs('matchs-a-venir', data.a_venir, 'Aucun match à venir');
        afficherListeMatchs('matchs-termines', data.termines, 'Aucun résultat disponible');
    });
}

function afficherListeMatchs(containerId, matchs, texteVide) {
    const container = document.getElementById(containerId);
    container.innerHTML = '';

    if (!matchs || matchs.length === 0) {
        container.innerHTML = `<div class="vide">${texteVide}</div>`;
        return;
    }

    matchs.forEach(m => {
        const div = document.createElement('div');
        div.className = `match-card ${m.status}`;

        let scoreHTML = '';
        let heure_debut = '';
        if (m.status === 'termine' || m.status === 'en_cours') {
            if (m.troissets == '3') {
                const s1 = m.score_equipe_1.split('*')[0] + " - " + m.score_equipe_2.split('*')[0];
                const s2 = m.score_equipe_1.split('*')[1] + " - " + m.score_equipe_2.split('*')[1];
                let s3 = "";
                if (m.score_equipe_1.split('*')[2] > '0' && m.score_equipe_2.split('*')[2] > '0') {
                    s3 = " | " + m.score_equipe_1.split('*')[2] + " - " + m.score_equipe_2.split('*')[2];
                }
                scoreHTML = `<div class="match-score">${s1} | ${s2}${s3}</div>`;
            } else {
                const s1 = m.score_equipe_1.split('*')[0];
                const s2 = m.score_equipe_2.split('*')[0];
                scoreHTML = `<div class="match-score">${s1} - ${s2}</div>`;
            }
        }
        if (m.status === 'planifie') {
            heure_debut = `<div class="match-heure">${escapeHTML(m.heure_debut || '')}</div>`
        }
        div.innerHTML = `
            <div class="match-equipes">
                ${escapeHTML(m.nom_equipe_1 || '?')} <span style="color:#999;">vs</span> ${escapeHTML(m.nom_equipe_2 || '?')}
                <div class="match-infos">${escapeHTML(m.nom_categorie || '')} - ${escapeHTML(m.nom_poule || '')} ${m.terrain ? '- Terrain ' + m.terrain : ''}</div>
            </div>
            ${scoreHTML}
            ${heure_debut}
        `;

        container.appendChild(div);
    });
}

// ----- Classement -----
function chargerClassement() {
    fetchJSON(`api/view_classement.php?id_tournoi=${ID_TOURNOI}`, (data) => {
        const container = document.getElementById('classement-content');
        container.innerHTML = '';

        if (!data.categories || data.categories.length === 0) {
            container.innerHTML = '<div class="vide">Aucun classement disponible</div>';
            return;
        }

        construireSousOnglets(container, data.categories, 'classement', (cat) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'poules-grid-2col';

            cat.poules.forEach(poule => {
                const pouleDiv = document.createElement('div');
                pouleDiv.className = 'poule-block-classement';

                let html = `<div class="poule-header"><span class="poule-nom">${escapeHTML(cat.nom_categorie)} -- ${escapeHTML(poule.nom_poule)}</span></div>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Équipe</th>
                                <th>J</th>
                                <th>V</th>
                                <th>D</th>
                                <th>Sets</th>
                                <th>Pts</th>
                            </tr>
                        </thead>
                        <tbody>`;

                poule.classement.forEach((eq, index) => {
                    html += `<tr>
                        <td>${index + 1}</td>
                        <td style="text-align:left;">${escapeHTML(eq.nom)}</td>
                        <td>${eq.joues}</td>
                        <td>${eq.victoires}</td>
                        <td>${eq.defaites}</td>
                        <td>${eq.sets_gagnes}/${eq.sets_perdus}</td>
                        <td>${eq.points_marques}/${eq.points_encaisses}</td>
                    </tr>`;
                });

                html += `</tbody></table>`;
                pouleDiv.innerHTML = html;
                wrapper.appendChild(pouleDiv);
            });

            return wrapper;
        });
    });
}

// ----- Joueurs inscrits -----
function chargerJoueurs() {
    fetchJSON(`api/view_joueurs.php?id_tournoi=${ID_TOURNOI}`, (data) => {
        const container = document.getElementById('joueurs-content');
        container.innerHTML = '';

        if (!data.joueurs || data.joueurs.length === 0) {
            container.innerHTML = '<div class="vide">Aucun joueur inscrit</div>';
            return;
        }

        // Groupement catégorie -> poule -> joueurs
        const categoriesMap = {};
        data.joueurs.forEach(j => {
            const cat = j.nom_categorie || 'Sans catégorie';
            const poule = j.nom_poule || 'Sans poule';

            if (!categoriesMap[cat]) categoriesMap[cat] = {};
            if (!categoriesMap[cat][poule]) categoriesMap[cat][poule] = [];

            categoriesMap[cat][poule].push(j);
        });

        // Transformation en tableau uniforme pour construireSousOnglets
        const categories = Object.keys(categoriesMap).map(nomCat => ({
            nom_categorie: nomCat,
            poules: Object.keys(categoriesMap[nomCat]).map(nomPoule => ({
                nom_poule: nomPoule,
                joueurs: categoriesMap[nomCat][nomPoule]
            }))
        }));

        construireSousOnglets(container, categories, 'joueurs', (cat) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'poules-grid-2col';

            cat.poules.forEach(poule => {
                const pouleDiv = document.createElement('div');
                pouleDiv.className = 'poule-block';

                let html = `<div class="poule-header"><span class="poule-nom">${escapeHTML(cat.nom_categorie)} -- ${escapeHTML(poule.nom_poule)}</span></div>
                    <table>
                        <thead>
                            <tr><th>Nom</th></tr>
                        </thead>
                        <tbody>`;

                poule.joueurs.forEach(j => {
                    html += `<tr>
                        <td style="text-align:left;">${escapeHTML(j.nom)}</td>
                    </tr>`;
                });

                html += `</tbody></table>`;
                pouleDiv.innerHTML = html;
                wrapper.appendChild(pouleDiv);
            });

            return wrapper;
        });
    });
}

// ----- Construction générique des sous-onglets par catégorie -----
// contenuBuilder(cat) doit retourner un élément DOM (le contenu à afficher pour cette catégorie)
function construireSousOnglets(container, categories, prefixId, contenuBuilder) {
    // Si une seule catégorie, on affiche directement sans sous-onglets
    if (categories.length <= 1) {
        const contentDiv = document.createElement('div');
        contentDiv.className = 'sous-onglet-content active';
        if (categories.length === 1) {
            contentDiv.appendChild(contenuBuilder(categories[0]));
        }
        container.appendChild(contentDiv);
        return;
    }

    // Barre de sous-onglets
    const nav = document.createElement('nav');
    nav.className = 'sous-tabs';

    const contentsWrapper = document.createElement('div');
    contentsWrapper.className = 'sous-onglets-wrapper';

    // Mémorise l'onglet actif précédemment sélectionné (persistance simple par prefixId)
    const storageKey = `sousOnglet_${prefixId}_${ID_TOURNOI}`;
    let indexActif = parseInt(sessionStorage.getItem(storageKey));
    if (isNaN(indexActif) || indexActif < 0 || indexActif >= categories.length) {
        indexActif = 0;
    }

    categories.forEach((cat, index) => {
        const btn = document.createElement('button');
        btn.className = 'sous-tab-btn' + (index === indexActif ? ' active' : '');
        btn.textContent = cat.nom_categorie;
        btn.dataset.index = index;

        btn.addEventListener('click', () => {
            nav.querySelectorAll('.sous-tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            contentsWrapper.querySelectorAll('.sous-onglet-content').forEach(c => c.classList.remove('active'));
            contentsWrapper.children[index].classList.add('active');

            sessionStorage.setItem(storageKey, index);
        });

        nav.appendChild(btn);

        const contentDiv = document.createElement('div');
        contentDiv.className = 'sous-onglet-content' + (index === indexActif ? ' active' : '');
        contentDiv.appendChild(contenuBuilder(cat));
        contentsWrapper.appendChild(contentDiv);
    });

    container.appendChild(nav);
    container.appendChild(contentsWrapper);
}

// ----- Utilitaire -----
function escapeHTML(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&')
        .replace(/</g, '<')
        .replace(/>/g, '>')
        .replace(/"/g, '"');
}