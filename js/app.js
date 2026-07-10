let currentTab = 'matchs';
let refreshTimer = null;
let countdownTimer = null;
let secondsLeft = 10;

document.addEventListener('DOMContentLoaded', () => {
    initTabs();
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
        if (m.status === 'termine' || m.status === 'en_cours') {
            const s1 = m.score_equipe_1.split('*').join(' / ');
            const s2 = m.score_equipe_2.split('*').join(' / ');
            scoreHTML = `<div class="match-score">${s1} - ${s2}</div>`;
        }

        div.innerHTML = `
            <div class="match-equipes">
                ${escapeHTML(m.nom_equipe_1 || '?')} <span style="color:#999;">vs</span> ${escapeHTML(m.nom_equipe_2 || '?')}
                <div class="match-infos">${escapeHTML(m.nom_categorie || '')} - ${escapeHTML(m.nom_poule || '')} ${m.terrain ? '- Terrain ' + m.terrain : ''}</div>
            </div>
            ${scoreHTML}
            <div class="match-heure">${escapeHTML(m.heure_debut || '')}</div>
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

        data.categories.forEach(cat => {
            const catDiv = document.createElement('div');
            catDiv.className = 'categorie-block';

            let html = `<h3>${escapeHTML(cat.nom_categorie)}</h3>`;

            cat.poules.forEach(poule => {
                html += `<div class="poule-block"><h4>${escapeHTML(poule.nom_poule)}</h4>`;
                html += `<table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Équipe</th>
                            <th>J</th>
                            <th>V</th>
                            <th>D</th>
                            <th>Sets +/-</th>
                            <th>Pts +/-</th>
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

                html += `</tbody></table></div>`;
            });

            catDiv.innerHTML = html;
            container.appendChild(catDiv);
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

        // Grouper par catégorie
        const groupes = {};
        data.joueurs.forEach(j => {
            const key = j.nom_categorie || 'Sans catégorie';
            if (!groupes[key]) groupes[key] = [];
            groupes[key].push(j);
        });

        for (const cat in groupes) {
            const catDiv = document.createElement('div');
            catDiv.className = 'categorie-block';

            let html = `<h3>${escapeHTML(cat)}</h3>`;
            html += `<table>
                <thead>
                    <tr><th>Nom</th><th>Poule</th></tr>
                </thead>
                <tbody>`;

            groupes[cat].forEach(j => {
                html += `<tr>
                    <td style="text-align:left;">${escapeHTML(j.nom)}</td>
                    <td>${escapeHTML(j.nom_poule || '-')}</td>
                </tr>`;
            });

            html += `</tbody></table>`;
            catDiv.innerHTML = html;
            container.appendChild(catDiv);
        }
    });
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