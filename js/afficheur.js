/**
 * afficheur.js — Page d'affichage/publication des matchs (lecture seule)
 * Inclut colors.js pour la coloration catégories/poules
 * + bracket-lines-config.js pour la configuration des liaisons du bracket
 */

let currentTab = 'matchs';
let refreshTimer = null;
let countdownTimer = null;
let secondsLeft = 600;

// ================================================================
// VARIABLES — LIGNES DU BRACKET (leader-line)
// ================================================================
let bracketLines = [];         // Tableau des instances LeaderLine actives
let resizeTimer = null;        // Timer debounce pour le resize
let leaderLineLoaded = false;  // Indique si leader-line.min.js est chargé

// ================================================================
// INITIALISATION
// ================================================================
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

    // Redimensionnement de la fenêtre → redessiner les lignes
    window.addEventListener('resize', () => {
        if (!BracketLinesConfig.enabled) return;
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            redessinerLignesBracket();
        }, 150);
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
    if (currentTab === 'phase_finale') chargerPhaseFinale();
    if (currentTab === 'classement_final') chargerClassementFinal();
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

    const interval = parseInt(document.getElementById('refresh-interval').value) || 600;
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

        // Couleur catégorie et poule (fond sur le texte)
        const catClass = getCategorieColorClassById(m.id_categorie);
        const poleClass = getPouleColorClassById(m.id_poule, m.id_poule_2);

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
                    if (v1 === 0 && v2 === 0 && i === 2) continue;

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

        // Tags couleur : fond coloré directement sur le texte
        const catText = escapeHTML(m.nom_categorie || '');
        const terrain = m.terrain && m.status !== 'termine' ? ` Terrain ${m.terrain}` : '';

        let poleTagHTML = '';
        if (m.type_match === 'phase_finale') {
            const label = (m.label_round ? m.label_round : '');
            poleTagHTML = `<span class="tag-couleur tag-phase-finale">${escapeHTML(label)}</span>`;
        } else {
            const poleClass = getPouleColorClassById(m.id_poule, m.id_poule_2);
            const poleText = escapeHTML(m.nom_poule || '');
            poleTagHTML = poleText ? `<span class="tag-couleur ${poleClass}">${poleText}</span>` : '';
        }

        const matchInfos = `<div class="match-infos">
            ${catText ? `<span class="tag-couleur ${catClass}">${catText}</span>` : ''}
            ${poleTagHTML}
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

                // Tags couleur : catégorie et poule (fond sur le texte)
                const catClass = getCategorieColorClassById(cat.id_categorie);
                const poleClass = getPouleColorClassById(poule.id_poule, null);

                let html = `<div class="poule-header">
                    <span class="tag-couleur ${catClass}">${escapeHTML(cat.nom_categorie)}</span>
                    <span class="tag-couleur ${poleClass}">${escapeHTML(poule.nom_poule)}</span>
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

                // Tags couleur : catégorie et poule (fond sur le texte)
                const catClass = getCategorieColorClassById(cat.id_categorie);
                const poleClass = getPouleColorClassById(poule.id_poule, null);

                let html = `<div class="poule-header">
                    <span class="tag-couleur ${catClass}">${escapeHTML(cat.nom_categorie)}</span>
                    <span class="tag-couleur ${poleClass}">${escapeHTML(poule.nom_poule)}</span>
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

// ================================================================
// PHASE FINALE — lecture seule pour l'afficheur
// ================================================================

function chargerPhaseFinale() {
    const container = document.getElementById('phase-finale-content');
    container.innerHTML = '<div class="vide">Chargement des phases finales...</div>';

    // Nettoyer les lignes du bracket précédent avant de recharger
    effacerLignesBracket();

    fetchJSON(`api/view_phase_finale.php?id_tournoi=${ID_TOURNOI}`, (data) => {
        container.innerHTML = '';

        if (!data.categories || data.categories.length === 0) {
            container.innerHTML = '<div class="vide">Aucune phase finale pour ce tournoi</div>';
            return;
        }

        // Ajouter la légende des statuts
        const legende = document.createElement('div');
        legende.className = 'phase-finale-legende';
        legende.innerHTML = `
            <div class="legende-items">
                <div class="legende-item">
                    <div class="legende-couleur" style="background: #cbcbcb; border-left: 5px solid var(--status-badge-planifie-bg) !important;"></div>
                    <span>Planifié</span>
                </div>
                <div class="legende-item">
                    <div class="legende-couleur" style="background: #cbcbcb; border-left: 5px solid var(--status-badge-en_cours-bg) !important;"></div>
                    <span>En cours</span>
                </div>
                <div class="legende-item">
                    <div class="legende-couleur" style="background: #cbcbcb; border-left: 5px solid var(--status-badge-termine-bg) !important;"></div>
                    <span>Terminé</span>
                </div>
                <div class="legende-item legende-item-ligne">
                    <div class="legende-couleur" style="background: ${BracketLinesConfig.couleurGagnant}; height: 3px; border: none;"></div>
                    <span>Gagnant</span>
                </div>
                <div class="legende-item legende-item-ligne">
                    <div class="legende-couleur" style="background: ${BracketLinesConfig.couleurPerdant}; height: 3px; border: none;"></div>
                    <span>Perdant</span>
                </div>
            </div>
        `;
        container.appendChild(legende);

        // Construire les sous-onglets par catégorie
        construireSousOnglets(container, data.categories, 'phase_finale', (cat) => {
            return creerBracketPhaseFinale(cat, data.categories);
        });

        // Une fois le bracket rendu dans le DOM, dessiner les lignes
        if (BracketLinesConfig.enabled) {
            chargerLeaderLinePuisDessiner(data);
        }
    });
}

/**
 * Charge leader-line.min.js s'il ne l'est pas encore,
 * puis exécute le callback une fois prêt.
 * @param {object} data - Données complètes de phase finale (pour dessiner les lignes après)
 */
function chargerLeaderLinePuisDessiner(data) {
    if (typeof LeaderLine !== 'undefined') {
        // Déjà chargé → dessiner immédiatement
        dessinerLignesBracket(data);
        return;
    }

    chargerScript('js/leader-line.min.js', function () {
        leaderLineLoaded = true;
        // Petit délai pour que leader-line soit pleinement initialisé
        setTimeout(() => {
            dessinerLignesBracket(data);
        }, 50);
    });
}

/**
 * Charge un script JS dynamiquement.
 * @param {string} url - URL du fichier JS
 * @param {function} callback - Fonction appelée une fois le script chargé
 */
function chargerScript(url, callback) {
    const existing = document.querySelector(`script[src="${url}"]`);
    if (existing) {
        // Script déjà en cours de chargement ou chargé
        if (existing.dataset.loaded === 'true') {
            callback();
        } else {
            existing.addEventListener('load', callback);
        }
        return;
    }

    const script = document.createElement('script');
    script.src = url;
    script.dataset.loaded = 'false';
    script.onload = function () {
        script.dataset.loaded = 'true';
        callback();
    };
    document.head.appendChild(script);
}

/**
 * Nettoie toutes les lignes LeaderLine existantes et leurs événements hover.
 */
function effacerLignesBracket() {
    bracketLines.forEach(line => {
        if (line && typeof line.remove === 'function') {
            line.remove();
        }
    });
    bracketLines = [];

    // Supprimer les event listeners hover sur tous les matchbox
    document.querySelectorAll('.match-box-wrapper').forEach(box => {
        box.classList.remove('line-hover');
        box.removeEventListener('mouseenter', onMatchboxHover);
        box.removeEventListener('mouseleave', onMatchboxLeave);
    });
}

/**
 * Redessine les lignes du bracket (appelé après resize).
 */
function redessinerLignesBracket() {
    if (!BracketLinesConfig.enabled) return;

    // Retirer les lignes existantes (sans re-fetch, on les recrée avec les données en cache)
    effacerLignesBracket();

    // Vérifier que leader-line est chargé et qu'on a des données en cache
    if (typeof LeaderLine === 'undefined') return;
    if (!window._bracketDataCache) return;

    dessinerLignesBracket(window._bracketDataCache);
}

/**
 * Dessine toutes les lignes du bracket (vertes = gagnants, rouges = perdants).
 * @param {object} data - Données complètes retournées par view_phase_finale.php
 */
function dessinerLignesBracket(data) {
    if (!BracketLinesConfig.enabled) return;
    if (typeof LeaderLine === 'undefined') return;

    // Cache pour le redessin au resize
    window._bracketDataCache = data;

    // Collecter tous les matchs de toutes les catégories/phases
    const allMatchs = [];
    if (data.categories) {
        data.categories.forEach(cat => {
            if (cat.phases_finales) {
                cat.phases_finales.forEach(phase => {
                    if (phase.matchs) {
                        phase.matchs.forEach(m => {
                            allMatchs.push(m);
                        });
                    }
                });
            }
        });
    }

    // Map match_code → match pour recherche rapide
    const matchMap = {};
    allMatchs.forEach(m => {
        matchMap[m.match_code] = m;
    });

    // Parcourir chaque match et créer les lignes pour source_team1 et source_team2
    allMatchs.forEach(match => {
        const destBoxId = 'matchbox_' + match.match_code;
        const destBox = document.getElementById(destBoxId);
        if (!destBox) return; // Matchbox pas encore dans le DOM (hors onglet actif)

        const sources = [
            { value: match.source_team1, slot: 1 },
            { value: match.source_team2, slot: 2 }
        ];

        sources.forEach(src => {
            if (!src.value) return;

            const parsed = parserSource(src.value);
            if (!parsed) return;

            const sourceMatchCode = parsed.matchCode;
            const sourceMatch = matchMap[sourceMatchCode];
            if (!sourceMatch) return;

            const sourceBoxId = 'matchbox_' + sourceMatchCode;
            const sourceBox = document.getElementById(sourceBoxId);
            if (!sourceBox) return;

            // Ne pas créer de ligne si source et destination sont le même matchbox
            if (sourceBoxId === destBoxId) return;

            // Déterminer couleur selon Win/Loss
            const couleur = parsed.type === 'Win'
                ? BracketLinesConfig.couleurGagnant
                : BracketLinesConfig.couleurPerdant;

            try {
                const line = new LeaderLine(sourceBox, destBox, {
                    color:        couleur,
                    opacity:      BracketLinesConfig.opaciteNormale,
                    size:         BracketLinesConfig.epaisseurNormale,
                    path:         BracketLinesConfig.styleChemin,
                    socket:       BracketLinesConfig.socket,
                    plug:         BracketLinesConfig.plug,
                    startSocketGravity:  15,
                    endSocketGravity:   15
                });

                bracketLines.push(line);

                // Ajouter les événements hover sur source et destination
                [sourceBox, destBox].forEach(box => {
                    if (!box.dataset.lineHoverInit) {
                        box.dataset.lineHoverInit = 'true';
                        box.addEventListener('mouseenter', onMatchboxHover);
                        box.addEventListener('mouseleave', onMatchboxLeave);
                    }
                });

            } catch (e) {
                console.warn('Erreur création ligne bracket:', e);
            }
        });
    });
}

/**
 * Parse une valeur source_team (ex: "Win_R1_S1_M1" ou "Loss_R2_S4_M2").
 * @param {string} sourceValue
 * @returns {{ type: 'Win'|'Loss', matchCode: string }|null}
 */
function parserSource(sourceValue) {
    if (!sourceValue || typeof sourceValue !== 'string') return null;

    const match = sourceValue.match(/^(Win|Loss)_(.+)$/);
    if (!match) return null;

    return {
        type:      match[1],    // 'Win' ou 'Loss'
        matchCode: match[2]      // ex: 'R1_S1_M1'
    };
}

/**
 * Gestionnaire mouseenter : augmenter opacité et épaisseur de TOUTES les lignes du bracket.
 */
function onMatchboxHover() {
    if (!BracketLinesConfig.enabled) return;
    const cfg = BracketLinesConfig;

    document.querySelectorAll('.match-box-wrapper').forEach(box => {
        box.classList.add('line-hover');
    });

    bracketLines.forEach(line => {
        line.setOptions({ opacity: cfg.opaciteSurvol, size: cfg.epaisseurSurvol });
    });
}

/**
 * Gestionnaire mouseleave : revenir à l'opacité et épaisseur normales.
 */
function onMatchboxLeave() {
    if (!BracketLinesConfig.enabled) return;
    const cfg = BracketLinesConfig;

    document.querySelectorAll('.match-box-wrapper').forEach(box => {
        box.classList.remove('line-hover');
    });

    bracketLines.forEach(line => {
        line.setOptions({ opacity: cfg.opaciteNormale, size: cfg.epaisseurNormale });
    });
}

/**
 * Construit le bracket complet d'une catégorie (lecture seule).
 * @param {object} cat - { nom_categorie, phases_finales: [{ nom, type_bracket, equipes, matchs }] }
 */
function creerBracketPhaseFinale(cat, datacategories) {
    const wrapper = document.createElement('div');
    wrapper.className = 'pf-categorie-wrapper';

    if (!cat.phases_finales || cat.phases_finales.length === 0) {
        wrapper.innerHTML = '<div class="vide">Pas de phase finale pour cette catégorie</div>';
        return wrapper;
    }

    // S'il n'y a qu'une phase finale, on l'affiche directement (pas de sous-sélection)
    if (cat.phases_finales.length === 1) {
        wrapper.appendChild(afficherUnePhaseFinale(cat.phases_finales[0], cat.id_categorie, datacategories));
        return wrapper;
    }

    // Plusieurs phases finales : mini-nav pour choisir
    const nav = document.createElement('div');
    nav.className = 'pf-phase-nav';

    const contentPanels = document.createElement('div');
    contentPanels.className = 'pf-phases-panels';

    cat.phases_finales.forEach((phase, idx) => {
        const btn = document.createElement('button');
        btn.className = 'pf-phase-btn' + (idx === 0 ? ' active' : '');
        btn.appendChild(document.createTextNode(phase.nom));
        btn.dataset.idx = idx;

        btn.addEventListener('click', () => {
            nav.querySelectorAll('.pf-phase-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            contentPanels.querySelectorAll('.pf-phase-panel').forEach(p => p.classList.remove('active'));
            contentPanels.children[idx].classList.add('active');

            // Redessiner les lignes après changement de sous-onglet phase finale
            if (BracketLinesConfig.enabled && typeof LeaderLine !== 'undefined' && window._bracketDataCache) {
                setTimeout(() => redessinerLignesBracket(), 80);
            }
        });

        nav.appendChild(btn);

        const panel = document.createElement('div');
        panel.className = 'pf-phase-panel' + (idx === 0 ? ' active' : '');
        panel.appendChild(afficherUnePhaseFinale(phase, cat.id_categorie, datacategories));
        contentPanels.appendChild(panel);
    });

    wrapper.appendChild(nav);
    wrapper.appendChild(contentPanels);
    return wrapper;
}

/**
 * Affiche une phase finale (bracket + équipes).
 */
function afficherUnePhaseFinale(phase, idCategorie, datacategories) {
    const section = document.createElement('div');
    section.className = 'pf-phase-section';

    // Titre
    const titre = document.createElement('div');
    titre.className = 'pf-phase-titre';
    const typeLabel = phase.type_bracket === 'classement_complet' ? 'Classement complet' : 'Bracket classique';
    titre.innerHTML = `<strong>${escapeHTML(phase.nom)}</strong> — ${typeLabel} — ${phase.nb_equipes} équipes`;
    section.appendChild(titre);

    // --- Bracket ---
    if (phase.matchs && phase.matchs.length > 0) {
        const bracketWrapper = document.createElement('div');
        bracketWrapper.className = 'bracket-container';

        // Organisation par rounds et sub_groups (même logique que phase_final.js)
        const rounds = {};
        phase.matchs.forEach(m => {
            const rk = m.round;
            const sk = m.sub_group !== undefined ? m.sub_group : 0;
            if (!rounds[rk]) rounds[rk] = {};
            if (!rounds[rk][sk]) rounds[rk][sk] = [];
            rounds[rk][sk].push(m);
        });

        const roundKeys = Object.keys(rounds).sort((a, b) => Number(a) - Number(b));
        const nbRounds = roundKeys.length;

        // Labels des rounds (derrière en finale)
        let reelround = 0;
        const roundLabels = ['Demi', 'Finale'];
        if (nbRounds > 2) roundLabels.unshift('Quart', '1/8', '1/16', '1/32');
        roundKeys.forEach((roundKey, rIdx) => {
            reelround++;
            const col = document.createElement('div');
            col.className = 'round-column scroll-colonne';

            // Titre du round (étiquette lisible, ex: "Quart", "Demi", "Finale")
            let label = '';
            const revIdx = nbRounds - 1 - rIdx; // 0 = finale
            if (revIdx === 0) label = 'Finale';
            else if (revIdx === 1) label = 'Demi-finales';
            else {
                // 1/4, 1/8, 1/16...
                const den = Math.pow(2, revIdx);
                label = revIdx === 2 ? 'Quart' : `1/${den}`;
            }
            const titreCol = document.createElement('div');
            titreCol.className = 'round-title';
            titreCol.textContent = label;
            col.appendChild(titreCol);
            const subKeys = Object.keys(rounds[roundKey]).sort((a, b) => Number(a) - Number(b));

            let nbre_team = datacategories[0].phases_finales[0].nb_equipes;
            let nb_equipes_arrondi = datacategories[0].phases_finales[0].nb_equipes_arrondi;

            subKeys.forEach(subKey => {
                // Label sub_group seulement si bracket "classement complet" (plusieurs branches)
                if (phase.type_bracket === 'classement_complet' && roundKeys.length >= 1) {
                    const subLabel = document.createElement('div');
                    subLabel.className = 'sub-group-title';
                    const skNum = Number(subKey);

                    // Calcul de la plage de classement pour ce round/subKey
                    const range = calculerPlageClassement(Number(roundKey), skNum, nb_equipes_arrondi);
                    if (reelround > 1) {
                        subLabel.innerHTML = (skNum % 2 === 1)
                            ? 'Classement ' + range + '<br>Vainqueur match précédent'
                            : 'Classement ' + range + '<br>Perdant match précédent';
                    } else {
                        subLabel.innerHTML = 'Classement ' + range;
                    }


                    col.appendChild(subLabel);
                }

                rounds[roundKey][subKey].forEach(match => {
                    col.appendChild(creerMatchBoxLectureSeule(match));
                });

            });

            bracketWrapper.appendChild(col);

        });

        section.appendChild(bracketWrapper);
    } else {
        section.appendChild(Object.assign(document.createElement('div'), {
            className: 'vide', textContent: 'Aucun match défini pour cette phase'
        }));
    }

    // --- Équipes (seeds) ---
    // if (phase.equipes && phase.equipes.length > 0) {
    //     const equipesDiv = document.createElement('div');
    //     equipesDiv.className = 'pf-equipes-section';

    //     const eqHeader = document.createElement('h4');
    //     eqHeader.textContent = 'Équipes qualifiées';
    //     equipesDiv.appendChild(eqHeader);

    //     const eqGrid = document.createElement('div');
    //     eqGrid.className = 'pf-equipes-grid';

    //     phase.equipes.forEach(eq => {
    //         const eqItem = document.createElement('div');
    //         eqItem.className = 'pf-equipe-item' + (eq.is_bye ? ' bye' : '');

    //         const seedBadge = document.createElement('span');
    //         seedBadge.className = 'pf-seed-badge';
    //         seedBadge.textContent = '#' + eq.seed_position;

    //         const nomSpan = document.createElement('span');
    //         nomSpan.className = 'pf-equipe-nom';
    //         nomSpan.textContent = eq.is_bye ? '(BYE)' : eq.nom_equipe;

    //         eqItem.appendChild(seedBadge);
    //         eqItem.appendChild(nomSpan);
    //         eqGrid.appendChild(eqItem);
    //     });

    //     equipesDiv.appendChild(eqGrid);
    //     section.appendChild(equipesDiv);
    // }

    return section;
}

function calculerPlageClassement(round, subKey, nbreTeam) {
    // Nombre de "branches" possibles à ce round = 2^round
    const nbBranches = Math.pow(2, round);

    // Taille de chaque plage à ce round
    const tailleGroupe = nbreTeam / nbBranches;

    // subKey commence à 1 -> index de 0 à nbBranches-1
    const index = subKey - 1;

    const debut = Math.floor(index * tailleGroupe) + 1;
    const fin = Math.floor((index + 1) * tailleGroupe);

    return debut + ' - ' + fin;
}

/**
 * Crée une match-box en lecture seule (aucun click, aucun formulaire).
 * Même structure visuelle que creerMatchBox() de phase_final.js,
 * mais sans addEventListener ni interaction.
 * Inclut terrain et statut de match comme dans phase_final.js.
 */

function creerMatchBoxLectureSeule(match) {
    const wrapper = document.createElement('div');
    wrapper.className = 'match-box-wrapper';
    wrapper.className = 'match-box-wrapper';
    wrapper.setAttribute("id", "matchbox_" + match.match_code);
    const statutJeu = match.statut_match || 'planifie';

    const nom1 = match.nom_equipe1 || match.source_team1 || '???';
    const nom2 = match.nom_equipe2 || match.source_team2 || '???';

    let classeTeam1 = '';
    let classeTeam2 = '';
    if (match.winner_equipe_id) {
        if (match.winner_equipe_id === match.equipe1_id) {
            classeTeam1 = 'winner';
            classeTeam2 = 'loser';
        } else if (match.winner_equipe_id === match.equipe2_id) {
            classeTeam1 = 'loser';
            classeTeam2 = 'winner';
        }
    }

    const score1Str = match.score1 !== null ? String(match.score1) : '';
    const score2Str = match.score2 !== null ? String(match.score2) : '';

    const simuleBadge = match.statut === 'simule'
        ? '<div class="simule-badge">⏩ Simulé</div>' : '';

    // Badge terrain externe (TX) — affiché seulement si terrain défini et match pas terminé
    const terrainBadge = (match.terrain && statutJeu !== 'termine')
        ? `<div class="terrain-badge-externe">Terrain ${escapeHTML(String(match.terrain))}</div>`
        : '';
    const heureBadge = (match.heure_debut && statutJeu !== 'termine')
        ? `<div class="heure-badge-externe">${match.heure_debut}</div>`
        : '';

    wrapper.innerHTML = `
        ${terrainBadge} ${heureBadge}
        <div class="match-box ${match.statut || ''} statut-${statutJeu}">
            ${simuleBadge}
            <div class="team-line ${classeTeam1}">
                <span>${escapeHTML(nom1)}</span>
                <span>${escapeHTML(score1Str)}</span>
            </div>
            <div class="team-line ${classeTeam2}">
                <span>${escapeHTML(nom2)}</span>
                <span>${escapeHTML(score2Str)}</span>
            </div>
            <div class="match-code" style="font-size:50%;">${escapeHTML(match.match_code || '')}</div>
        </div>
    `;

    return wrapper;
}

// ----- Classement Final -----
function chargerClassementFinal() {
    fetchJSON(`api/view_classement_final.php?id_tournoi=${ID_TOURNOI}`, (data) => {
        const container = document.getElementById('classement-final-content');
        container.innerHTML = '';

        if (!data.categories || data.categories.length === 0) {
            container.innerHTML = '<div class="vide">Aucun classement final disponible</div>';
            return;
        }

        construireSousOnglets(container, data.categories, 'classement_final', (cat) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'poule-block-classement';

            const catClass = getCategorieColorClassById(cat.id_categorie);

            let html = `<div class="poule-header">
                <span class="tag-couleur ${catClass}">${escapeHTML(cat.nom_categorie)}</span>
            </div>
                <table>
                    <thead>
                        <tr>
                            <th>Position</th>
                            <th>Équipe</th>
                        </tr>
                    </thead>
                    <tbody>`;

            cat.classement.forEach(eq => {
                if (eq.is_bye) return; // on masque les BYE dans l'affichage final
                html += `<tr>
                    <td>${eq.position}</td>
                    <td style="text-align:left;">${escapeHTML(eq.nom_equipe)}</td>
                </tr>`;
            });

            html += `</tbody></table>`;
            wrapper.innerHTML = html;

            return wrapper;
        });
    });
}


// ================================================================
// Construction générique des sous-onglets par catégorie
// ================================================================

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

        // Tag couleur sur l'onglet catégorie (classe couleur appliquée sur le bouton)
        const catClass = getCategorieColorClassById(cat.id_categorie);
        if (catClass) {
            btn.classList.add(catClass);
        }
        btn.appendChild(document.createTextNode(cat.nom_categorie));
        btn.dataset.index = index;

        btn.addEventListener('click', () => {
            nav.querySelectorAll('.sous-tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            contentsWrapper.querySelectorAll('.sous-onglet-content').forEach(c => c.classList.remove('active'));
            contentsWrapper.children[index].classList.add('active');

            sessionStorage.setItem(storageKey, index);

            // Redessiner les lignes du bracket après changement de catégorie
            if (BracketLinesConfig.enabled && typeof LeaderLine !== 'undefined' && window._bracketDataCache) {
                setTimeout(() => redessinerLignesBracket(), 80);
            }
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
