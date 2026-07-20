<?php
$id_tournoi = isset($_GET['id_tournoi']) ? (int)$_GET['id_tournoi'] : 0;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournoi All Auto Web</title>
    <link rel="stylesheet" href="css/var.css">
    <link rel="stylesheet" href="css/afficheur.css">
    <?php if ($id_tournoi === 0): ?>
    <link rel="stylesheet" href="css/liste_afficheur.css">
    <?php endif; ?>
</head>

<body>

    <?php if ($id_tournoi === 0): ?>

    <button onclick="location.href='login.php?login'" class="btn-lsiteafficheur">👤 Login</button>
    <!-- Sélecteur de tournoi si aucun id_tournoi fourni -->
    <div class="selecteur-tournois">
        <h1>Sélectionnez un tournoi</h1>
        <div id="liste-tournois" class="liste-tournois">
            <p class="message-vide">Chargement des tournois...</p>
        </div>
    </div>

    <script>
    fetch('api/get_liste_afficheur.php')
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('liste-tournois');
            container.innerHTML = '';

            if (!data.success) {
                container.innerHTML = '<p class="message-erreur">Erreur : ' + (data.error || 'Impossible de charger les tournois') + '</p>';
                return;
            }

            if (!data.tournois || data.tournois.length === 0) {
                container.innerHTML = '<p class="message-vide">Aucun tournoi disponible.</p>';
                return;
            }

            data.tournois.forEach(t => {
                const a = document.createElement('a');
                a.href = 'afficheur.php?id_tournoi=' + encodeURIComponent(t.id_tournoi);
                a.className = 'lien-tournoi';

                let dateInfo = '';
                if (t.heure_debut_poule) {
                    dateInfo = 'Début poules : ' + t.heure_debut_poule;
                }

                a.innerHTML = '<strong>' + (t.nom || ('Tournoi #' + t.id_tournoi)) + '</strong>' +
                    (dateInfo ? '<span class="date-tournoi">' + dateInfo + '</span>' : '');

                container.appendChild(a);
            });
        })
        .catch(err => {
            document.getElementById('liste-tournois').innerHTML =
                '<p class="message-erreur">Erreur de connexion : ' + err.message + '</p>';
        });
    </script>

    <?php else: ?>

    <header>
        <h1 id="nom-tournoi">Chargement du tournoi...</h1>
        <div class="infos-refresh">
            <span id="heure-actuelle">--:--:--</span>
            <div class="timer-control">
                <label for="refresh-interval">Rafraîchissement (sec) :</label>
                <input type="number" id="refresh-interval" value="600" min="3" max="120">
                <span id="countdown">600</span>s
            </div>

            <button onclick="location.href='afficheur.php'" class="btn-dark">↩ liste tournoi</button>
            <button id="dark-mode-toggle" class="btn-dark" title="Basculer le mode sombre">🌙</button>
            <button onclick="location.href='login.php?login'" class="btn-dark">👤 Login</button>
        </div>
    </header>

    <nav class="tabs">
        <button class="tab-btn active" data-tab="matchs">Matchs</button>
        <button class="tab-btn" data-tab="classement">Classement</button>
        <!-- <button class="tab-btn" data-tab="joueurs">Joueurs inscrits</button> -->
        <button class="tab-btn" data-tab="phase_finale">Phase Finale</button>
        <button class="tab-btn" data-tab="classement_final">Classement Final</button>
    </nav>
    <main>
        <!-- Onglet Matchs : 3 colonnes -->
        <section id="tab-matchs" class="tab-content active">
            <div class="grid-matchs">
                <div class="colonne">
                    <h2>Matchs en cours</h2>
                    <div id="matchs-en-cours" class="liste-matchs scroll-colonne"></div>
                </div>
                <div class="colonne">
                    <h2>Matchs à venir</h2>
                    <div id="matchs-a-venir" class="liste-matchs scroll-colonne"></div>
                </div>
                <div class="colonne">
                    <h2>Derniers résultats</h2>
                    <div id="matchs-termines" class="liste-matchs scroll-colonne"></div>
                </div>
            </div>
        </section>

        <!-- Onglet Classement : grille multi-colonnes -->
        <section id="tab-classement" class="tab-content">
            <div id="classement-content" class="grid-multicol scroll-colonne"></div>
        </section>

        <!-- Onglet Joueurs : grille multi-colonnes -->
        <section id="tab-joueurs" class="tab-content">
            <div id="joueurs-content" class="grid-multicol scroll-colonne"></div>
        </section>

        <!-- Onglet Phase Finale : bracket lecture seule -->
        <section id="tab-phase_finale" class="tab-content">
            <div id="phase-finale-content"></div>
        </section>
        <section id="tab-classement_final" class="tab-content">
            <div id="classement-final-content" class="grid-multicol scroll-colonne"></div>
        </section>
    </main>

    <script>
    const ID_TOURNOI = <?php echo json_encode($id_tournoi); ?>;
    </script>
    <!-- <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script> -->
    <script src="js/colors.js"></script>
    <!-- Configuration des liaisons du bracket (couleurs, opacité, etc.) -->
    <script src="js/bracket-lines-config.js"></script>
    <script src="js/leader-line.min.js"></script>
    <script src="js/afficheur.js"></script>

    <?php endif; ?>

</body>

</html>