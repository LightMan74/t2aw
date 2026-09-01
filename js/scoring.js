const ScoringApp = (function () {

    let state = {
        idTournoi: null,
        troisSetsDefault: 3,
        idMatch: null,
        typeMatch: null,
        terrain: null,
        nomEquipe1: '',
        nomEquipe2: '',
        nomCategorie: '',
        nomPoule: '',
        formatMatch: 'simple', // simple ou double
        joueurs: {
            equipe1: ['', ''],
            equipe2: ['', '']
        },
        nbSetsFormat: 1, // 1 ou 3 (troissets de la BDD)
        setsGagnes: { 1: 0, 2: 0 },
        setActuel: 1,
        scoreActuel: { 1: 0, 2: 0 },
        historiqueSets: [], // [{set:1, score1:21, score2:15}]
        cotesInverses: false, // false = equipe1 à gauche
        serveur: 1, // 1 ou 2 (equipe qui sert)
        positionServeur: 'droite', // droite ou gauche selon le score pair/impair
        joueurServeurIndex: 0, // pour double : index 0 ou 1 du joueur qui sert dans l'equipe
        positionsDouble: {
            // en double : position sur le terrain (haut/bas) pour chaque équipe
            equipe1: { haut: 0, bas: 1 }, // index du joueur
            equipe2: { haut: 0, bas: 1 }
        },
        syncBDD: true,
        historiquePoints: [], // pile pour "annuler dernier point"
        timerLance: false
    };

    function init(config) {
        state.idTournoi = config.idTournoi;
        state.troisSetsDefault = config.troisSets;

        chargerListeMatchs();

        document.getElementById('btn-charger-match').addEventListener('click', chargerMatchSelectionne);
        document.getElementById('btn-valider-joueurs').addEventListener('click', validerJoueursEtDemarrer);
        document.getElementById('btn-changer-cote').addEventListener('click', inverserCotesManuel);
        document.getElementById('btn-erreur-service').addEventListener('click', ouvrirCorrectionService);
        document.getElementById('btn-fin-match').addEventListener('click', terminerMatch);
        document.getElementById('chk-sync-bdd').addEventListener('change', function (e) {
            state.syncBDD = e.target.checked;
        });

        document.querySelectorAll('input[name="type-match"]').forEach(function (radio) {
            radio.addEventListener('change', function (e) {
                state.formatMatch = e.target.value;
                toggleAffichageJoueur2();
            });
        });

        document.querySelectorAll('.btn-point').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                const equipe = parseInt(e.target.getAttribute('data-equipe'));
                ajouterPoint(equipe);
            });
        });

        document.querySelectorAll('.btn-annuler').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                annulerDernierPoint();
            });
        });
    }

    function toggleAffichageJoueur2() {
        const isDouble = state.formatMatch === 'double';
        document.getElementById('joueur2-equipe1').style.display = isDouble ? 'inline-block' : 'none';
        document.getElementById('joueur2-equipe2').style.display = isDouble ? 'inline-block' : 'none';
    }

    // -------------------- Chargement liste des matchs --------------------

    function chargerListeMatchs() {
        fetch('api/view_matchs.php?id_tournoi=' + state.idTournoi)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                const select = document.getElementById('select-terrain');
                select.innerHTML = '<option value="">-- Sélectionner --</option>';

                if (data.en_cours && data.en_cours.length > 0) {
                    data.en_cours.forEach(function (m) {
                        const option = document.createElement('option');
                        option.value = JSON.stringify({
                            id_match: m.id,
                            type_match: m.type_match
                        });
                        const terrain = m.terrain ? 'Terrain ' + m.terrain : 'Terrain ?';
                        const eq1 = m.nom_equipe_1 || '?';
                        const eq2 = m.nom_equipe_2 || '?';
                        option.textContent = terrain + ' - ' + eq1 + ' vs ' + eq2 + ' (' + (m.nom_categorie || '') + ')';
                        select.appendChild(option);
                    });
                }
            })
            .catch(function (err) {
                console.error('Erreur chargement matchs', err);
            });
    }

    // -------------------- Chargement des détails du match --------------------

    function chargerMatchSelectionne() {
        const select = document.getElementById('select-terrain');
        if (!select.value) {
            alert('Veuillez sélectionner un match');
            return;
        }

        const infos = JSON.parse(select.value);

        fetch('api/scoring/get_match_details.php?id_tournoi=' + state.idTournoi
            + '&id_match=' + infos.id_match
            + '&type_match=' + infos.type_match)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.error) {
                    alert('Erreur : ' + data.error);
                    return;
                }

                state.idMatch = data.id_match;
                state.typeMatch = data.type_match;
                state.terrain = data.terrain;
                state.nomEquipe1 = data.nom_equipe_1;
                state.nomEquipe2 = data.nom_equipe_2;
                state.nomCategorie = data.nom_categorie;
                state.nomPoule = data.nom_poule;
                state.nbSetsFormat = parseInt(data.troissets) || state.troisSetsDefault;

                afficherInfoMatch();
                afficherSaisieJoueurs();
            })
            .catch(function (err) {
                console.error(err);
                alert('Erreur lors du chargement du match');
            });
    }

    function afficherInfoMatch() {
        document.getElementById('info-terrain').textContent = 'Terrain ' + (state.terrain || '?');
        document.getElementById('info-categorie').textContent = state.nomCategorie || '';
        document.getElementById('info-poule').textContent = state.nomPoule || '';

        document.getElementById('nom-equipe-1-label').textContent = state.nomEquipe1;
        document.getElementById('nom-equipe-2-label').textContent = state.nomEquipe2;

        document.getElementById('selection-match').style.display = 'none';
        document.getElementById('interface-scoring').style.display = 'block';
    }

    function afficherSaisieJoueurs() {
        document.getElementById('saisie-joueurs').style.display = 'block';
        document.getElementById('terrain-jeu').style.display = 'none';
    }

    // -------------------- Validation des joueurs et démarrage --------------------

    function validerJoueursEtDemarrer() {
        state.joueurs.equipe1[0] = document.getElementById('joueur1-equipe1').value.trim() || 'Joueur 1';
        state.joueurs.equipe2[0] = document.getElementById('joueur1-equipe2').value.trim() || 'Joueur 1';

        if (state.formatMatch === 'double') {
            state.joueurs.equipe1[1] = document.getElementById('joueur2-equipe1').value.trim() || 'Joueur 2';
            state.joueurs.equipe2[1] = document.getElementById('joueur2-equipe2').value.trim() || 'Joueur 2';
        }

        document.getElementById('saisie-joueurs').style.display = 'none';
        document.getElementById('terrain-jeu').style.display = 'block';

        // Initialisation du set
        state.setActuel = 1;
        state.setsGagnes = { 1: 0, 2: 0 };
        state.scoreActuel = { 1: 0, 2: 0 };
        state.historiqueSets = [];
        state.serveur = 1;
        state.positionServeur = 'droite';
        state.joueurServeurIndex = 0;
        state.cotesInverses = false;

        document.getElementById('set-total').textContent = state.nbSetsFormat;

        // Timer si format 1 set
        if (state.nbSetsFormat === 1) {
            document.getElementById('timer-container').style.display = 'block';
            if (typeof TournamentTimer !== 'undefined' && !state.timerLance) {
                TournamentTimer.init({
                    idtournoi: state.idTournoi,
                    containerId: 'timer-container',
                    showControls: true,
                    playSound: false
                });
                state.timerLance = true;
            }
        }

        majAffichageComplet();
    }

    // -------------------- Gestion des points --------------------

    function ajouterPoint(equipe) {
        // Sauvegarde état avant modif pour annulation
        state.historiquePoints.push(JSON.parse(JSON.stringify({
            scoreActuel: state.scoreActuel,
            serveur: state.serveur,
            positionServeur: state.positionServeur,
            joueurServeurIndex: state.joueurServeurIndex,
            positionsDouble: state.positionsDouble
        })));

        const autreEquipe = equipe === 1 ? 2 : 1;
        const etaitServeur = (state.serveur === equipe);

        state.scoreActuel[equipe]++;

        gererRotationServeur(equipe, etaitServeur);

        // Vérifier fin de set
        verifierFinSet();

        majAffichageComplet();
    }

    function annulerDernierPoint() {
        if (state.historiquePoints.length === 0) return;

        const dernier = state.historiquePoints.pop();
        state.scoreActuel = dernier.scoreActuel;
        state.serveur = dernier.serveur;
        state.positionServeur = dernier.positionServeur;
        state.joueurServeurIndex = dernier.joueurServeurIndex;
        state.positionsDouble = dernier.positionsDouble;

        majAffichageComplet();
    }

    // -------------------- Règles officielles de rotation --------------------

    function gererRotationServeur(equipeMarquante, etaitServeurAvant) {
        if (etaitServeurAvant) {
            // L'équipe qui servait a marqué : elle continue de servir, change de côté
            state.positionServeur = (state.positionServeur === 'droite') ? 'gauche' : 'droite';

            if (state.formatMatch === 'double') {
                // Le service alterne entre les deux joueurs de la même équipe
                // à chaque point gagné en servant. On échange qui est en haut/bas.
                const key = 'equipe' + equipeMarquante;
                const tmp = state.positionsDouble[key].haut;
                state.positionsDouble[key].haut = state.positionsDouble[key].bas;
                state.positionsDouble[key].bas = tmp;
            }

        } else {
            // Changement de service : l'équipe qui reçoit devient serveur
            state.serveur = equipeMarquante;

            // Position de service déterminée par le score de la nouvelle équipe serveuse
            const scoreServeur = state.scoreActuel[equipeMarquante];
            state.positionServeur = (scoreServeur % 2 === 0) ? 'droite' : 'gauche';

            if (state.formatMatch === 'double') {
                // En double, au changement de service, le joueur qui sert est
                // celui qui se trouve du côté correspondant (règle officielle :
                // ne pas re-permuter les positions, seul le côté déterminé par le score compte)
            }
        }
    }

    function inverserCotesManuel() {
        state.cotesInverses = !state.cotesInverses;
        majAffichageTerrain();
    }

    function ouvrirCorrectionService() {
        const equipeActuelle = state.serveur;
        const nouvelleEquipe = prompt('Quelle équipe sert actuellement ? (1 ou 2)', equipeActuelle);
        if (nouvelleEquipe === '1' || nouvelleEquipe === '2') {
            state.serveur = parseInt(nouvelleEquipe);
            const cote = prompt('Le serveur est-il à droite ou à gauche ? (droite/gauche)', state.positionServeur);
            if (cote === 'droite' || cote === 'gauche') {
                state.positionServeur = cote;
            }
            if (state.formatMatch === 'double') {
                const joueurIdx = prompt('Quel joueur sert ? (1 ou 2 - position dans l\'équipe)', '1');
                // Ajuste positionsDouble pour que le bon joueur soit du côté positionServeur
                const key = 'equipe' + state.serveur;
                if (joueurIdx === '1') {
                    if (state.positionServeur === 'droite') {
                        state.positionsDouble[key].haut = 0;
                        state.positionsDouble[key].bas = 1;
                    } else {
                        state.positionsDouble[key].haut = 1;
                        state.positionsDouble[key].bas = 0;
                    }
                } else if (joueurIdx === '2') {
                    if (state.positionServeur === 'droite') {
                        state.positionsDouble[key].haut = 1;
                        state.positionsDouble[key].bas = 0;
                    } else {
                        state.positionsDouble[key].haut = 0;
                        state.positionsDouble[key].bas = 1;
                    }
                }
            }
            majAffichageComplet();
        }
    }

    // -------------------- Fin de set / fin de match --------------------

    function verifierFinSet() {
        const s1 = state.scoreActuel[1];
        const s2 = state.scoreActuel[2];

        let setTermine = false;

        if (state.nbSetsFormat === 1) {
            // Format au temps, pas de fin automatique par points
            setTermine = false;
        } else {
            // Format 3 sets : 15 points, 2 pts d'écart, max 21
            const seuil = 15;
            const max = 21;

            if ((s1 >= seuil || s2 >= seuil) && Math.abs(s1 - s2) >= 2) {
                setTermine = true;
            } else if (s1 === max || s2 === max) {
                setTermine = true;
            }
        }

        if (setTermine) {
            const gagnantSet = s1 > s2 ? 1 : 2;
            state.setsGagnes[gagnantSet]++;

            state.historiqueSets.push({
                set: state.setActuel,
                score1: s1,
                score2: s2
            });

            const setsPourGagner = Math.ceil(state.nbSetsFormat / 2); // 2 pour format 3

            if (state.setsGagnes[gagnantSet] >= setsPourGagner) {
                // Match terminé
                afficherFinMatch();
            } else {
                // Nouveau set
                demarrerNouveauSet();
            }
        }
    }

    function demarrerNouveauSet() {
        state.setActuel++;
        state.scoreActuel = { 1: 0, 2: 0 };
        state.historiquePoints = [];

        // Changement de côté automatique à chaque set
        state.cotesInverses = !state.cotesInverses;

        // Le vainqueur du set précédent... en réalité au badminton c'est l'équipe qui a perdu
        // le set précédent qui sert en premier au set suivant (règle officielle)
        const dernierSet = state.historiqueSets[state.historiqueSets.length - 1];
        state.serveur = (dernierSet.score1 > dernierSet.score2) ? 2 : 1;
        state.positionServeur = 'droite'; // toujours à droite en début de set (score 0-0)

        majAffichageComplet();
    }

    function afficherFinMatch() {
        document.getElementById('btn-fin-match').style.display = 'inline-block';
        document.querySelectorAll('.btn-point').forEach(function (btn) {
            btn.disabled = true;
        });
    }

    function terminerMatch() {
        sauvegarderScore(true);
        alert('Match terminé et enregistré.');
    }

    // -------------------- Sauvegarde BDD --------------------

    function construireChaineScore(equipe) {
        // format 0*0*0 pour poule, on suppose même format pour phase finale
        const sets = state.historiqueSets.map(function (s) {
            return equipe === 1 ? s.score1 : s.score2;
        });
        // ajoute le set en cours si pas encore fini
        if (state.setActuel > state.historiqueSets.length) {
            sets.push(state.scoreActuel[equipe]);
        }
        while (sets.length < state.nbSetsFormat) {
            sets.push(0);
        }
        return sets.join('*');
    }

    function sauvegarderScore(matchTermine) {
        const body = {
            id_tournoi: state.idTournoi,
            type_match: state.typeMatch,
            id_match: state.idMatch,
            score_equipe_1: construireChaineScore(1),
            score_equipe_2: construireChaineScore(2)
        };

        if (matchTermine) {
            body.statut = 'termine';
        }

        fetch('api/scoring/update_score.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        })
            .then(function (res) { return res.json(); })
            .catch(function (err) { console.error('Erreur sauvegarde', err); });
    }

    // -------------------- Affichage --------------------

    function majAffichageComplet() {
        document.getElementById('score-equipe-1').textContent = state.scoreActuel[1];
        document.getElementById('score-equipe-2').textContent = state.scoreActuel[2];
        document.getElementById('nom-equipe-1-score').textContent = state.nomEquipe1;
        document.getElementById('nom-equipe-2-score').textContent = state.nomEquipe2;
        document.getElementById('set-numero').textContent = state.setActuel;

        document.getElementById('sets-equipe-1').textContent = state.nomEquipe1 + ' : ' + state.setsGagnes[1];
        document.getElementById('sets-equipe-2').textContent = state.nomEquipe2 + ' : ' + state.setsGagnes[2];

        majHistoriqueSets();
        majAffichageTerrain();

        if (state.syncBDD) {
            sauvegarderScore(false);
        }
    }

    function majHistoriqueSets() {
        const container = document.getElementById('historique-sets');
        container.innerHTML = '';
        state.historiqueSets.forEach(function (s) {
            const div = document.createElement('div');
            div.textContent = 'Set ' + s.set + ' : ' + s.score1 + ' - ' + s.score2;
            container.appendChild(div);
        });
    }

    function majAffichageTerrain() {
        // Détermine quelle équipe est affichée à gauche (côté A) ou droite (côté B)
        const equipeGauche = state.cotesInverses ? 2 : 1;
        const equipeDroite = state.cotesInverses ? 1 : 2;

        remplirCote('A', equipeGauche);
        remplirCote('B', equipeDroite);
    }

    function remplirCote(cote, equipe) {
        const posHaut = document.getElementById('pos-' + cote + '-haut');
        const posBas = document.getElementById('pos-' + cote + '-bas');

        posHaut.classList.remove('serveur-actif');
        posBas.classList.remove('serveur-actif');

        if (state.formatMatch === 'simple') {
            const nomJoueur = state.joueurs['equipe' + equipe][0] || ('Équipe ' + equipe);
            posHaut.textContent = nomJoueur;
            posBas.textContent = '';

            if (state.serveur === equipe) {
                // en simple, un seul joueur, mais on simule sa position droite/gauche visuellement
                if (state.positionServeur === 'droite') {
                    posHaut.classList.add('serveur-actif');
                } else {
                    posBas.classList.add('serveur-actif');
                }
            }
        } else {
            const key = 'equipe' + equipe;
            const idxHaut = state.positionsDouble[key].haut;
            const idxBas = state.positionsDouble[key].bas;

            posHaut.textContent = state.joueurs[key][idxHaut] || ('Joueur ' + (idxHaut + 1));
            posBas.textContent = state.joueurs[key][idxBas] || ('Joueur ' + (idxBas + 1));

            if (state.serveur === equipe) {
                if (state.positionServeur === 'droite') {
                    posHaut.classList.add('serveur-actif');
                } else {
                    posBas.classList.add('serveur-actif');
                }
            }
        }
    }

    return {
        init: init
    };

})();