(function () {

    let currentMatch = null; // objet retourné par get_match_details
    // let state = {
    //     // sets déjà validés/terminés : tableau [{s1, s2}, ...]
    //     setsHistory = null,
    // };

    // ---------- STATE PRINCIPAL ----------
    let match = {
        id_match: null,
        type_match: null,
        nom_equipe_1: '',
        nom_equipe_2: '',
        troissets: 3, // 1 ou 3 (valeur brute de la BDD, on considère 1 = 1 set, sinon 3 sets)
        format1: 'double', // simple / double équipe 1
        format2: 'double', // simple / double équipe 2
        joueurs1: ['', ''],
        joueurs2: ['', ''],
        sets: [0, 0, 0], // scores équipe1 pour set1/2/3
        setsB: [0, 0, 0], // scores équipe2
        setActuel: 0, // index 0,1,2
        setsGagnes1: 0,
        setsGagnes2: 0,
        // côté : 'gauche' ou 'droite' pour équipe1 ; equipe2 = l'inverse
        coteEquipe1: 'gauche',
        // ordre des joueurs affichés côté (position 0 = haut/droit-service, 1 = bas/gauche-service) -- géré via array
        posJoueurs1: [0, 1], // index dans joueurs1 : [positionDroite, positionGauche] selon convention choisie
        posJoueurs2: [0, 1],
        // qui sert : {team: 1 ou 2, joueurIndex: 0 ou 1 (index dans joueurs[team])}
        serveur: { team: 1, joueurIndex: 0 },
        // pour le simple, joueurIndex sera toujours 0 côté "actif" mais on gère la position via le score
        matchTermine: false
    };

    const autoUpdateChk = document.getElementById('chk-auto-update');

    // ---------- CHARGEMENT DE LA LISTE DES MATCHS EN COURS ----------
    function chargerMatchsEnCours() {
        fetch('api/view_matchs.php?id_tournoi=' + ID_TOURNOI)
            .then(r => r.json())
            .then(data => {
                const select = document.getElementById('terrain-select');
                select.innerHTML = '<option value="">-- Choisir un match en cours --</option>';
                if (!data.en_cours) return;
                data.en_cours.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = JSON.stringify({ id: m.id, type: m.type_match });
                    const terrain = m.terrain ? ('Terrain ' + m.terrain) : 'Terrain ?';
                    opt.textContent = terrain + ' - ' + m.nom_equipe_1 + ' vs ' + m.nom_equipe_2;
                    select.appendChild(opt);
                });
            });
    }

    document.getElementById('btn-refresh-matchs').addEventListener('click', chargerMatchsEnCours);

    document.getElementById('terrain-select').addEventListener('change', function () {
        if (!this.value) {
            document.getElementById('scoring-container').style.display = 'none';
            return;
        }
        const infos = JSON.parse(this.value);
        chargerDetailsMatch(infos.id, infos.type);
    });

    // ---------- CHARGEMENT DES DETAILS DU MATCH ----------
    function chargerDetailsMatch(id_match, type_match) {
        fetch('api/scoring/get_match_details.php?id_tournoi=' + ID_TOURNOI + '&id_match=' + id_match + '&type_match=' + type_match)
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    alert('Erreur : ' + data.error);
                    return;
                }
                initMatch(data);
            });
    }

    function initMatch(data) {
        match.id_match = data.id_match;
        match.type_match = data.type_match;
        match.nom_equipe_1 = data.nom_equipe_1;
        match.nom_equipe_2 = data.nom_equipe_2;
        match.troissets = parseInt(data.troissets, 10) || 3;

        // parse score existant s'il y en a
        const parts1 = (data.score_equipe_1 || '0*0*0').split('*').map(v => parseInt(v, 10) || 0);
        const parts2 = (data.score_equipe_2 || '0*0*0').split('*').map(v => parseInt(v, 10) || 0);
        match.sets = [parts1[0] || 0, parts1[1] || 0, parts1[2] || 0];
        match.setsB = [parts2[0] || 0, parts2[1] || 0, parts2[2] || 0];

        match.setActuel = 0;
        match.setsGagnes1 = 0;
        match.setsGagnes2 = 0;
        match.coteEquipe1 = 'gauche';
        match.matchTermine = false;

        document.getElementById('match-info').textContent =
            (data.nom_categorie ? data.nom_categorie + ' - ' : '') +
            (data.nom_poule ? data.nom_poule + ' - ' : '') +
            'Terrain ' + (data.terrain || '?');

        document.getElementById('nom-equipe-1-label').textContent = match.nom_equipe_1;
        document.getElementById('nom-equipe-2-label').textContent = match.nom_equipe_2;

        // reset formulaire setup
        document.querySelectorAll('.joueur-input').forEach(inp => inp.value = '');
        document.querySelectorAll('.format-select').forEach(sel => {
            sel.value = 'double';
            toggleJoueur2Visibility(sel);
        });

        document.getElementById('setup-joueurs').style.display = 'block';
        document.getElementById('match-play').style.display = 'none';
        document.getElementById('scoring-container').style.display = 'block';

        autoUpdateChk.checked = false;
    }

    // ---------- FORMAT SIMPLE/DOUBLE : affichage joueur 2 ----------
    function toggleJoueur2Visibility(selectEl) {
        const team = selectEl.getAttribute('data-team');
        const wrapper = document.querySelector('.joueur2-wrapper[data-team="' + team + '"]');
        wrapper.style.display = (selectEl.value === 'double') ? 'block' : 'none';
    }

    document.querySelectorAll('.format-select').forEach(sel => {
        sel.addEventListener('change', function () {
            toggleJoueur2Visibility(this);
        });
    });

    // ---------- DEMARRAGE DU MATCH (validation des joueurs) ----------
    document.getElementById('btn-start-match').addEventListener('click', function () {
        match.format1 = document.querySelector('.format-select[data-team="1"]').value;
        match.format2 = document.querySelector('.format-select[data-team="2"]').value;

        const j1_1 = document.querySelector('.joueur-input[data-team="1"][data-joueur="1"]').value.trim() || match.nom_equipe_1;
        const j1_2 = document.querySelector('.joueur-input[data-team="1"][data-joueur="2"]').value.trim() || (match.nom_equipe_1 + ' (2)');
        const j2_1 = document.querySelector('.joueur-input[data-team="2"][data-joueur="1"]').value.trim() || match.nom_equipe_2;
        const j2_2 = document.querySelector('.joueur-input[data-team="2"][data-joueur="2"]').value.trim() || (match.nom_equipe_2 + ' (2)');

        match.joueurs1 = (match.format1 === 'double') ? [j1_1, j1_2] : [j1_1];
        match.joueurs2 = (match.format2 === 'double') ? [j2_1, j2_2] : [j2_1];

        match.posJoueurs1 = (match.format1 === 'double') ? [0, 1] : [0];
        match.posJoueurs2 = (match.format2 === 'double') ? [0, 1] : [0];

        // serveur initial = équipe 1, joueur 0
        match.serveur = { team: 1, joueurIndex: 0 };

        document.getElementById('setup-joueurs').style.display = 'none';
        document.getElementById('match-play').style.display = 'block';

        renderAll();
    });

    // ---------- RENDU GLOBAL ----------
    function renderAll() {
        renderSetsInfo();
        renderTerrain();
        renderServeur();
        renderBoutonsSet();
    }

    function renderSetsInfo() {
        document.getElementById('sets-gagnes-1').textContent = match.setsGagnes1;
        document.getElementById('sets-gagnes-2').textContent = match.setsGagnes2;
        document.getElementById('set-actuel-label').textContent =
            ' | Set ' + (match.setActuel + 1) + (match.troissets === 1 ? ' (au temps)' : ' / ' + Math.max(3, match.troissets === 1 ? 1 : 3));
    }

    // Retourne l'équipe (1 ou 2) qui est actuellement affichée à gauche
    function equipeCote(cote) {
        if (cote === 'gauche') {
            return (match.coteEquipe1 === 'gauche') ? 1 : 2;
        } else {
            return (match.coteEquipe1 === 'gauche') ? 2 : 1;
        }
    }

    function scoreEquipe(team) {
        return team === 1 ? match.sets[match.setActuel] : match.setsB[match.setActuel];
    }

    function joueursEquipe(team) {
        return team === 1 ? match.joueurs1 : match.joueurs2;
    }

    function posEquipe(team) {
        return team === 1 ? match.posJoueurs1 : match.posJoueurs2;
    }

    function nomEquipe(team) {
        return team === 1 ? match.nom_equipe_1 : match.nom_equipe_2;
    }

    function renderTerrain() {
        const teamGauche = equipeCote('gauche');
        const teamDroite = equipeCote('droite');

        document.getElementById('nom-equipe-gauche').textContent = nomEquipe(teamGauche);
        document.getElementById('nom-equipe-droite').textContent = nomEquipe(teamDroite);

        document.getElementById('score-gauche').textContent = scoreEquipe(teamGauche);
        document.getElementById('score-droite').textContent = scoreEquipe(teamDroite);

        renderJoueursCote('gauche', teamGauche);
        renderJoueursCote('droite', teamDroite);
    }

    // Affiche les joueurs d'une équipe sur un côté, avec indication de position
    // convention : posEquipe(team)[0] = joueur actuellement "à droite" de son propre côté,
    //              posEquipe(team)[1] = joueur actuellement "à gauche" (uniquement en double)
    function renderJoueursCote(cote, team) {
        const container = document.getElementById('joueurs-' + cote);
        container.innerHTML = '';
        const joueurs = joueursEquipe(team);
        const pos = posEquipe(team);

        if (joueurs.length === 1) {
            const div = document.createElement('div');
            div.className = 'joueur-slot';
            div.textContent = joueurs[0];
            container.appendChild(div);
        } else {
            const divDroit = document.createElement('div');
            divDroit.className = 'joueur-slot slot-droit';
            divDroit.textContent = joueurs[pos[0]] + ' (droite)';
            const divGauche = document.createElement('div');
            divGauche.className = 'joueur-slot slot-gauche';
            divGauche.textContent = joueurs[pos[1]] + ' (gauche)';

            if (team === 1) {
                // équipe 1 : joueur de droite en bas => on insère gauche d'abord, droite ensuite
                container.appendChild(divGauche);
                container.appendChild(divDroit);
            } else {
                // équipe 2 (ou autre) : droite en haut, gauche en bas
                container.appendChild(divDroit);
                container.appendChild(divGauche);
            }
        }
    }

    function renderServeur() {
        const team = match.serveur.team;
        const joueurs = joueursEquipe(team);
        const nom = joueurs[match.serveur.joueurIndex] || joueurs[0];
        document.getElementById('serveur-actuel').textContent = nom + ' (' + nomEquipe(team) + ')';
    }

    // ---------- LOGIQUE DE POINT ----------
    function ajouterPoint(cote) {
        if (match.matchTermine) return;
        const team = equipeCote(cote);
        appliquerPoint(team);
    }

    function retirerPoint(cote) {
        if (match.matchTermine) return;
        const team = equipeCote(cote);
        if (team === 1) {
            if (match.sets[match.setActuel] > 0) match.sets[match.setActuel]--;
        } else {
            if (match.setsB[match.setActuel] > 0) match.setsB[match.setActuel]--;
        }
        renderAll();
        autoSaveIfEnabled();
    }

    function appliquerPoint(team) {
        const wasServeur = match.serveur.team;

        if (team === 1) {
            match.sets[match.setActuel]++;
        } else {
            match.setsB[match.setActuel]++;
        }

        gererRotationApresPoint(team, wasServeur);
        verifierFinDeSet();
        renderAll();
        autoSaveIfEnabled();
    }

    // Règles officielles de rotation badminton
    function gererRotationApresPoint(teamMarqueur, teamServeurAvant) {
        if (teamMarqueur === teamServeurAvant) {
            if (joueursEquipe(teamMarqueur).length === 2) {
                const pos = posEquipe(teamMarqueur);
                pos.reverse();
            }
        } else {
            // Le service passe à l'autre équipe
            match.serveur.team = teamMarqueur;

            const scoreEquipeRecuperant = teamMarqueur === 1
                ? match.sets[match.setActuel]
                : match.setsB[match.setActuel];
            const estPair = (scoreEquipeRecuperant % 2 === 0);

            if (joueursEquipe(teamMarqueur).length === 2) {
                const pos = posEquipe(teamMarqueur);
                // pos[0] = joueur de droite, pos[1] = joueur de gauche
                match.serveur.joueurIndex = estPair ? pos[0] : pos[1];
            } else {
                match.serveur.joueurIndex = 0;
            }
        }
    }

    // ---------- FIN DE SET / FIN DE MATCH ----------
    function scoreSetActuel() {
        return { s1: match.sets[match.setActuel], s2: match.setsB[match.setActuel] };
    }

    function verifierFinDeSet() {
        if (match.troissets === 1) {
            // pas de fin auto, géré manuellement par l'arbitre (bouton "Terminer le match")
            return;
        }

        const { s1, s2 } = scoreSetActuel();
        const max = 21;
        const min = 15;
        let setFini = false;
        let gagnantSet = null;

        if (s1 >= min || s2 >= min) {
            if (s1 >= max) { setFini = true; gagnantSet = 1; }
            else if (s2 >= max) { setFini = true; gagnantSet = 2; }
            else if (s1 >= min && (s1 - s2) >= 2) { setFini = true; gagnantSet = 1; }
            else if (s2 >= min && (s2 - s1) >= 2) { setFini = true; gagnantSet = 2; }
        }

        if (setFini) {
            if (gagnantSet === 1) match.setsGagnes1++;
            else match.setsGagnes2++;

            document.getElementById('btn-set-suivant').style.display = 'inline-block';

            if (match.setsGagnes1 === 2 || match.setsGagnes2 === 2) {
                document.getElementById('btn-terminer-match').style.display = 'inline-block';
            }
        }
    }

    function renderBoutonsSet() {
        // rien de spécifique ici pour l'instant, la logique est dans verifierFinDeSet
    }

    // ---------- PASSAGE AU SET SUIVANT ----------
    document.getElementById('btn-set-suivant').addEventListener('click', function () {
        if (match.setActuel >= 2) return;
        match.setActuel++;

        // Changement de côté automatique à chaque set
        match.coteEquipe1 = (match.coteEquipe1 === 'gauche') ? 'droite' : 'gauche';

        // Le vainqueur du set précédent sert en premier (règle officielle : le vainqueur de l'échange précédent sert)
        // Simplification : l'équipe qui a gagné le set précédent sert en premier au set suivant.
        const setPrecedent = match.setActuel - 1;
        const gagnantPrecedent = match.sets[setPrecedent] > match.setsB[setPrecedent] ? 1 : 2;
        match.serveur = { team: gagnantPrecedent, joueurIndex: 0 };
        posEquipe(1)[0] = 0; posEquipe(1)[1] = 1;
        posEquipe(2)[0] = 0; posEquipe(2)[1] = 1;

        document.getElementById('btn-set-suivant').style.display = 'none';

        renderAll();
        autoSaveIfEnabled();
    });

    // ---------- CHANGEMENT DE COTE MANUEL ----------
    document.getElementById('btn-changer-cote-manuel').addEventListener('click', function () {
        match.coteEquipe1 = (match.coteEquipe1 === 'gauche') ? 'droite' : 'gauche';
        renderAll();
    });

    // ---------- CORRECTION MANUELLE POSITION JOUEURS (erreur arbitre) ----------
    document.getElementById('btn-switch-cote-gauche').addEventListener('click', function () {
        const team = equipeCote('gauche');
        posEquipe(team).reverse();
        renderAll();
    });
    document.getElementById('btn-switch-cote-droite').addEventListener('click', function () {
        const team = equipeCote('droite');
        posEquipe(team).reverse();
        renderAll();
    });

    // ---------- CORRECTION MANUELLE DU SERVEUR (erreur arbitre) ----------
    document.getElementById('btn-change-serveur').addEventListener('click', function () {
        // Construire la liste ordonnée de tous les serveurs possibles sur le terrain
        const candidats = [];
        joueursEquipe(1).forEach((nom, idx) => candidats.push({ team: 1, joueurIndex: idx }));
        joueursEquipe(2).forEach((nom, idx) => candidats.push({ team: 2, joueurIndex: idx }));

        // Trouver la position actuelle du serveur dans la liste
        let currentPos = candidats.findIndex(c =>
            c.team === match.serveur.team && c.joueurIndex === match.serveur.joueurIndex
        );
        if (currentPos === -1) currentPos = 0;

        // Passer au candidat suivant (cycle)
        const nextPos = (currentPos + 1) % candidats.length;
        match.serveur = { team: candidats[nextPos].team, joueurIndex: candidats[nextPos].joueurIndex };

        renderAll();
    });

    // ---------- BOUTONS POINT ----------
    document.querySelectorAll('.btn-point').forEach(btn => {
        btn.addEventListener('click', function () {
            ajouterPoint(this.getAttribute('data-team'));
        });
    });
    document.querySelectorAll('.btn-moins').forEach(btn => {
        btn.addEventListener('click', function () {
            retirerPoint(this.getAttribute('data-team'));
        });
    });

    // ---------- TERMINER LE MATCH ----------
    document.getElementById('btn-terminer-match').addEventListener('click', function () {
        if (!confirm('Confirmer la fin du match ?')) return;
        match.matchTermine = true;
        sauvegarderScore('termine');
        document.getElementById('btn-terminer-match').style.display = 'none';
        document.getElementById('btn-set-suivant').style.display = 'none';
    });

    // ---------- SAUVEGARDE MANUELLE ----------
    document.getElementById('btn-save-manuel').addEventListener('click', function () {
        sauvegarderScore();
    });

    function autoSaveIfEnabled() {
        if (autoUpdateChk.checked) {
            sauvegarderScore();
        }
    }

    function sauvegarderScore(statut) {
        const score_equipe_1 = match.sets.join('*');
        const score_equipe_2 = match.setsB.join('*');

        const payload = {
            id_tournoi: ID_TOURNOI,
            type_match: match.type_match,
            id_match: match.id_match,
            score_equipe_1: score_equipe_1,
            score_equipe_2: score_equipe_2
        };
        if (statut) payload.statut = statut;
        if (!statut) payload.statut = 'en_cours';

        fetch('api/scoring/update_score.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    console.error('Erreur sauvegarde score :', data.error);
                }
            })
            .catch(err => console.error(err));
    }

    // ---------- INIT ----------
    chargerMatchsEnCours();

})();