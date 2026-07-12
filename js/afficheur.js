let currentTab = 'matchs';
let refreshTimer = null;
let countdownTimer = null;
let secondsLeft = 10;

/* ======================================================
   PALETTE DE COULEURS & FONCTIONS DE HACHAGE STABLE
   ======================================================

   Palette категорий и пул:
   - 10 couleurs разработаны для гармоничного, различного вида.
   - Couleurs réutilisées depuis style.css (même teinte,EB0 hue).

   Garantie de stabilité couleur → nom :
   Алгоритм хэширования:
   1. Для категории:  index = hash(nom_categorie) % 10  → {cat-1..cat-10}
   2. Pour poules:    index = hash(nom_poule) % 10       → {poule-1..poule-10}
   Хэш-функция: djb2 (Дэниел Дж. Бернштейн, 1991).
   djb2("ABCD") = ((((5381 · 33 + 65) · 33 + 66) · 33 + 67) · 33 + 68
   Результат: всегда одно и то же целое число для одной и той же строки.
   Порядок добавления/удаления категорий или пул не влияет.

   Палитра:
   cat-color-1  = #e74c3c  rouge
   cat-color-2  = #3498db  bleu
   cat-color-3  = #f1c40f  jaune
   cat-color-4  = #2ecc71  vert
   cat-color-5  = #9b59b6  violet
   cat-color-6  = #1abc9c  turquoise
   cat-color-7  = #e67e22  orange
   cat-color-8  = #34495e  gris-bleu
   cat-color-9  = #ff6b9d  rose
   cat-color-10 = #16a085  vert foncé

   poule-color-1 = #3498db  bleu
   poule-color-2 = #9b59b6  violet
   poule-color-3 = #27ae60  vert
   poule-color-4 = #e67e22  orange
   poule-color-5 = #e74c3c  rouge
   poule-color-6 = #1abc9c  turquoise
   poule-color-7 = #f39c12  doré
   poule-color-8 = #2c3e50  bleu nuit
   poule-color-9 = #d35400 orange foncé
   poule-color-10 = #8e44ad violet foncé
   ====================================================== */

const PALETTE_CAT   = 10;
const PALETTE_POULE = 10;

/**
 * хэш-функция djb2
 * @param {string} str
 * @returns {number} hash stable entre 0 et 2^32
 */
function djb2Hash(str) {
    let hash = 5381;
    for (let i = 0; i < str.length; i++) {
        hash = ((hash << 5) + hash) + str.charCodeAt(i);
        hash = hash & hash; // 32-bit overflow
    }
    return Math.abs(hash);
}

/**
 * Renvoie la classe CSS catégorie stable pour un nom de catégorie.
 * ex: getCatClass("U11") → "cat-badge cat-3"
 *
 * @param {string|null} nomCat
 * @returns {string} classe badge couleur, ou chaîne vide
 */
function getCatClass(nomCat) {
    if (!nomCat) return '';
    const idx = (djb2Hash(nomCat) % PALETTE_CAT) + 1;
    return `cat-badge cat-${idx}`;
}

/**
 * Renvoie la classe CSS poule stable pour un nom de poule.
 * ex: getPouleClass("Poule A") → "poule-badge poule-2"
 *
 * @param {string|null} nomPoule
 * @returns {string} classe badge couleur, ou chaîne vide
 */
function getPouleClass(nomPoule) {
    if (!nomPoule) return '';
    const idx = (djb2Hash(nomPoule) % PALETTE_POULE) + 1;
    return `poule-badge poule-${idx}`;
}

/**
 * Renvoie le code hexadécimal de la couleur catégorie.
 *
 * @param {string|null} nomCat
 * @returns {string} code hex ou ''
 */
function getCatColor(nomCat) {
    if (!nomCat) return '';
    const idx = (djb2Hash(nomCat) % PALETTE_CAT) + 1;
    const map = {
        1: '#e74c3c', 2: '#3498db', 3: '#f1c40f', 4: '#2ecc71', 5: '#9b59b6',
        6: '#1abc9c', 7: '#e67e22', 8: '#34495e', 9: '#ff6b9d', 10: '#16a085'
    };
    return map[idx] || '';
}

/**
 * Renvoie le code hexadécimal de la couleur poule.
 *
 * @param {string|null} nomPoule
 * @returns {string} code hex ou ''
 */
function getPouleColor(nomPoule) {
    if (!nomPoule) return '';
    const idx = (djb2Hash(nomPoule) % PALETTE_POULE) + 1;
    const map = {
        1: '#3498db', 2: '#9b59b6', 3: '#27ae60', 4: '#e67e22', 5: '#e74c3c',
        6: '#1abc9c', 7: '#f39c12', 8: '#2c3e50', 9: '#d35400', 10: '#8e44ad'
    };
    return map[idx] || '';
}

/* ======================================================
   INITIALISATION
   ====================================================== */

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
        let nomEquipe1 = escapeHTML(m.nom_equipe_1 || '?');
        let nomEquipe2 = escapeHTML(m.nom_equipe_2 || '?');

        if (m.status === 'termine' || m.status === 'en_cours') {
            const sets1 = m.score_equipe_1.split('*');
            const sets2 = m.score_equipe_2.split('*');

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

        // --- Badges couleur catégorie & poule ---
        const catBadge = getCatClass(m.nom_categorie);
        const pouleBadge = getPouleClass(m.nom_poule);
        const catBadgeHTML = catBadge ? `<span class="${catBadge}">${escapeHTML(m.nom_categorie || '')}</span>` : '';
        const pouleBadgeHTML = pouleBadge ? `<span class="${pouleBadge}">${escapeHTML(m.nom_poule || '')}</span>` : '';

        const infosExtra = m.terrain
            ? ` - Terrain ${escapeHTML(m.terrain)}`
            : '';

        div.innerHTML = `
        <div class="match-equipes">
            ${nomEquipe1} <span style="color:#999;">vs</span> ${nomEquipe2}
            <div class="match-infos">
                ${catBadgeHTML}${pouleBadgeHTML}${infosExtra}
            </div>
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

            // Badge couleur catégorie
            const catBadge = getCatClass(cat.nom_categorie);
            const catBadgeHTML = catBadge
                ? `<span class="${catBadge}">${escapeHTML(cat.nom_categorie)}</span>`
                : escapeHTML(cat.nom_categorie);

            cat.poules.forEach(poule => {
                const pouleDiv = document.createElement('div');
                pouleDiv.className = 'poule-block-classement';

                // Badge couleur poule
                const pBadge = getPouleClass(poule.nom_poule);
                const pBadgeHTML = pBadge
                    ? `<span class="${pBadge}">${escapeHTML(poule.nom_poule)}</span>`
                    : escapeHTML(poule.nom_poule);

                let html = `
                    <div class="poule-header">
                        <span class="poule-nom">
                            ${catBadgeHTML} &mdash; ${pBadgeHTML}
                        </span>
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

            // Badge couleur catégorie
            const catBadge = getCatClass(cat.nom_categorie);
            const catBadgeHTML = catBadge
                ? `<span class="${catBadge}">${escapeHTML(cat.nom_categorie)}</span>`
                : escapeHTML(cat.nom_categorie);

            cat.poules.forEach(poule => {
                const pouleDiv = document.createElement('div');
                pouleDiv.className = 'poule-block';

                // Badge couleur poule
                const pBadge = getPouleClass(poule.nom_poule);
                const pBadgeHTML = pBadge
                    ? `<span class="${pBadge}">${escapeHTML(poule.nom_poule)}</span>`
                    : escapeHTML(poule.nom_poule);

                let html = `
                    <div class="poule-header">
                        <span class="poule-nom">
                            ${catBadgeHTML} &mdash; ${pBadgeHTML}
                        </span>
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

    // Barre de sous-onglets — chaque bouton porte le badge couleur de la catégorie
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
        btn.dataset.index = index;

        // Bouton de sous-onglet : badge couleur catégorie + nom
        const catBadge = getCatClass(cat.nom_categorie);
        const catBadgeHTML = catBadge
            ? `<span class="${catBadge}">${escapeHTML(cat.nom_categorie)}</span>`
            : escapeHTML(cat.nom_categorie);
        btn.innerHTML = catBadgeHTML;

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
