/**
 * afficheur.js — Page d'affichage/publication des matchs
 * Inclut colors.js pour la coloration catégories/poules
 */

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

        // Badges colorés : catégorie et poule
        const catClass = getCategorieColorClassById(m.id_categorie);
        const catBadge = catClass
            ? `<span class="categorie-badge ${catClass}" title="${escapeHTML(m.nom_categorie || '')}"></span>`
            : '';
        const poleClass = getPouleColorClassById(m.id_poule, m.id_poule_2);
        const poleBadge = poleClass
            ? `<span class="poule-badge ${poleClass}" title="${escapeHTML(m.nom_poule || '')}"></span>`
            : '';

        let scoreHTML = '';
        let heure_debut = '';
        let nomEquipe1 = escapeHTML(m.nom_equipe_1 || '?');
        let nomEquipe2 = escapeHTML(m.nom_equipe_2 || '?');

        if (m.status === 'termine' || m.status === 'en_cours') {
            const sets1 = (m.score_equipe_1 || '0*0*0').split('*');
            const sets2 = (m.score_equipe_2 || '0*0*0').split('*');

            if (m.troissets == '3') {
                const setsHTML = [];
                let victoiresE1 = 0;
                let victoiresE2 = 0;

                for (let i = 0; i < 3; i++) {
                    const v1 = parseInt(sets1[i]);
                    const v2 = parseInt(sets2[i]);

                    if (isNaN(v1) || isNaN(v2)) continue;
                    if (v1 === 0 && v2 === 0 && i === 2) continue; // 3e set non joué

                    let classe1 = '';
                    let classe2 = '';

                    if (v1 > v2) {
                        classe1 = 'set-gagne';
                        classe2 = 'set-perdu';
                        victoiresE1++;
                    } else if (v2 > v1) {
                        classe1 = 'set-perdu';
                        classe2 = 'set-gagne';
                        victoiresE2++;
                    }

                    setsHTML.push(`<span class="${classe1}">${v1}</span> - <span class="${classe2}">${v2}</span>`);
                }

                scoreHTML = `<div class="match-score">${setsHTML.join(' | ')}</div>`;

                // Coloration des noms (basée sur les sets gagnés, même en cours)
                if (victoiresE1 > victoiresE2) {
                    nomEquipe1 = `<span class="equipe-gagnante">${nomEquipe1}</span>`;
                    nomEquipe2 = `<span class="equipe-perdante">${nomEquipe2}</span>`;
                } else if (victoiresE2 > victoiresE1) {
                    nomEquipe1 = `<span class="equipe-perdante">${nomEquipe1}</span>`;
                    nomEquipe2 = `<span class="equipe-gagnante">${nomEquipe2}</span>`;
                }

            } else {
                const v1 = parseInt(sets1[0]);
                const v2 = parseInt(sets2[0]);

                let classe1 = '';
                let classe2 = '';

                if (v1 > v2) {
                    classe1 = 'set-gagne';
                    classe2 = 'set-perdu';
                } else if (v2 > v1) {
                    classe1 = 'set-perdu';
                    classe2 = 'set-gagne';
                }

                scoreHTML = `<div class="match-score"><span class="${classe1}">${v1}</span> - <span class="${classe2}">${v2}</span></div>`;

                // Coloration des noms (basée sur le score actuel, même en cours)
                if (v1 > v2) {
                    nomEquipe1 = `<span class="equipe-gagnante">${nomEquipe1}</span>`;
                    nomEquipe2 = `<span class="equipe-perdante">${nomEquipe2}</span>`;
                } else if (v2 > v1) {
                    nomEquipe1 = `<span class="equipe-perdante">${nomEquipe1}</span>`;
                    nomEquipe2 = `<span class="equipe-gagnante">${nomEquipe2}</span>`;
                }
            }
        }

        if (m.status === 'planifie') {
            heure_debut = `<div class="match-heure">${escapeHTML(m.heure_debut || '')}</div>`;
        }

        // Ligne infos : badges + catégorie textuel + poule textuel + terrain
        const catText = escapeHTML(m.nom_categorie || '');
        const poleText = escapeHTML(m.nom_poule || '');
        const terrain = m.terrain ? ` — Terrain ${m.terrain}` : '';
        const matchInfos = `<div class="match-infos">
            ${catBadge}${catText ? `<span>${catText}</span>` : ''}
            ${poleBadge}${poleText ? `<span>${poleText}</span>` : ''}
            ${terrain}
        </div>`;

        div.innerHTML = `
        <div class="match-equipes">
            ${nomEquipe1} <span style="color:#999;">vs</span> ${nomEquipe2}
            ${matchInfos}
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

                // Badges colorés : catégorie et poule
                const catClass = getCategorieColorClassById(cat.id_categorie);
                const catBadge = catClass ? `<span class="categorie-badge ${catClass}"></span>` : '';
                const poleClass = getPouleColorClassById(poule.id_poule, null);
                const poleBadge = poleClass ? `<span class="poule-badge ${poleClass}"></span>` : '';

                let html = `<div class="poule-header">
                    ${catBadge}${poleBadge}
                    <span class="poule-nom">${escapeHTML(cat.nom_categorie)} — ${escapeHTML(poule.nom_poule)}</span>
                </div>
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
            const catId = j.id_categorie;
            const pole = j.nom_poule || 'Sans poule';
            const poleId = j.id_poule;

            if (!categoriesMap[cat]) categoriesMap[cat] = { id_categorie: catId, poules: {} };
            if (!categoriesMap[cat].poules[pole]) categoriesMap[cat].poules[pole] = { id_poule: poleId, joueurs: [] };

            categoriesMap[cat].poules[pole].joueurs.push(j);
        });

        // Transformation en tableau uniforme pour construireSousOnglets
        const categories = Object.keys(categoriesMap).map(nomCat => ({
            id_categorie: categoriesMap[nomCat].id_categorie,
            nom_categorie: nomCat,
            poules: Object.keys(categoriesMap[nomCat].poules).map(nomPoule => ({
                id_poule: categoriesMap[nomCat].poules[nomPoule].id_poule,
                nom_poule: nomPoule,
                joueurs: categoriesMap[nomCat].poules[nomPoule].joueurs
            }))
        }));

        construireSousOnglets(container, categories, 'joueurs', (cat) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'poules-grid-2col';

            cat.poules.forEach(poule => {
                const pouleDiv = document.createElement('div');
                pouleDiv.className = 'poule-block';

                // Badges colorés
                const catClass = getCategorieColorClassById(cat.id_categorie);
                const catBadge = catClass ? `<span class="categorie-badge ${catClass}"></span>` : '';
                const poleClass = getPouleColorClassById(poule.id_poule, null);
                const poleBadge = poleClass ? `<span class="poule-badge ${poleClass}"></span>` : '';

                let html = `<div class="poule-header">
                    ${catBadge}${poleBadge}
                    <span class="poule-nom">${escapeHTML(cat.nom_categorie)} — ${escapeHTML(poule.nom_poule)}</span>
                </div>
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
function construireSousOnglets(container, categories, prefixId, contenuBuilder) {
    if (categories.length <= 1) {
        const contentDiv = document.createElement('div');
        contentDiv.className = 'sous-onglet-content active';
        if (categories.length === 1) {
            contentDiv.appendChild(contenuBuilder(categories[0]));
        }
        container.appendChild(contentDiv);
        return;
    }

    const nav = document.createElement('nav');
    nav.className = 'sous-tabs';

    const contentsWrapper = document.createElement('div');
    contentsWrapper.className = 'sous-onglets-wrapper';

    const storageKey = `sousOnglet_${prefixId}_${ID_TOURNOI}`;
    let indexActif = parseInt(sessionStorage.getItem(storageKey));
    if (isNaN(indexActif) || indexActif < 0 || indexActif >= categories.length) {
        indexActif = 0;
    }

    categories.forEach((cat, index) => {
        const btn = document.createElement('button');
        btn.className = 'sous-tab-btn' + (index === indexActif ? ' active' : '');
        // Badge coloré sur le bouton de l'onglet catégorie
        const catClass = getCategorieColorClassById(cat.id_categorie);
        if (catClass) {
            const badge = document.createElement('span');
            badge.className = `categorie-badge ${catClass}`;
            badge.style.marginRight = '6px';
            btn.prepend(badge);
        }
        btn.appendChild(document.createTextNode(cat.nom_categorie));
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
