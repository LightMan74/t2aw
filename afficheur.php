<?php
$id_tournoi = isset($_GET['id_tournoi']) ? (int)$_GET['id_tournoi'] : 0;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi Tournoi</title>
    <link rel="stylesheet" href="css/var.css">
    <link rel="stylesheet" href="css/afficheur.css">
</head>

<body>

    <header>
        <h1 id="nom-tournoi">Chargement du tournoi...</h1>
        <div class="infos-refresh">
            <span id="heure-actuelle">--:--:--</span>
            <div class="timer-control">
                <label for="refresh-interval">Rafraîchissement (sec) :</label>
                <input type="number" id="refresh-interval" value="600" min="3" max="120">
                <span id="countdown">600</span>s
            </div>
            <button id="dark-mode-toggle" class="btn-dark" title="Basculer le mode sombre">🌙</button>
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

</body>

</html>