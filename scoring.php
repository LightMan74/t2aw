<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/api/db.php';

$id_tournoi = isset($_GET['id_tournoi']) ? (int)$_GET['id_tournoi'] : 0;

if (!$id_tournoi) {
    die('id_tournoi manquant');
}

// Récupération infos tournoi
$stmt = $pdo->prepare("SELECT nom FROM tournoi WHERE id = :id");
$stmt->execute(['id' => $id_tournoi]);
$tournoi = $stmt->fetch(PDO::FETCH_ASSOC);
$nom_tournoi = $tournoi['nom'] ?? 'Tournoi';

// Paramètres tournoi
$tournoi_troissets_match = 3;
$show_timer = false;
$stmt = $pdo->prepare("SELECT troissets, timer FROM parametre WHERE id_tournoi = :id");
$stmt->execute(['id' => $id_tournoi]);
$parametres = $stmt->fetch(PDO::FETCH_ASSOC);
if ($parametres !== false) {
    if (isset($parametres['troissets'])) $tournoi_troissets_match = (int)$parametres['troissets'];
    if (isset($parametres['timer'])) $show_timer = ((int)$parametres['timer'] === 1);
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">

<head>
    <meta charset="UTF-8">
    <title>Arbitrage - <?php echo htmlspecialchars($nom_tournoi); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/var.css">
    <link rel="stylesheet" href="css/dark-mode.css">
    <link rel="stylesheet" href="css/scoring.css">
</head>

<body class="scoring-page">

    <div class="scoring-header">
        <h1><?php echo htmlspecialchars($nom_tournoi); ?> - Table d'arbitrage</h1>

        <div id="court-selector">
            <label for="terrain-select">Terrain / Match : </label>
            <select id="terrain-select">
                <option value="">-- Choisir un match en cours --</option>
            </select>
            <button id="btn-refresh-matchs" type="button" class="btn-action secondaire">Rafraîchir la liste</button>
        </div>

        <button class="theme-toggle" id="theme-toggle">🌓 Thème</button>
    </div>

    <div id="scoring-container" style="display:none;">

        <h2 id="match-info"></h2>

        <div id="setup-joueurs">
            <h3>Configuration des joueurs</h3>

            <div class="team-setup" id="setup-team1">
                <h4 id="nom-equipe-1-label"></h4>
                <label>Format :
                    <select class="format-select" data-team="1">
                        <option value="double" selected>Double</option>
                        <option value="simple">Simple</option>
                    </select>
                </label>
                <div>
                    <label>Joueur 1 : <input type="text" class="joueur-input" data-team="1" data-joueur="1" placeholder="Nom joueur 1"></label>
                </div>
                <div class="joueur2-wrapper" data-team="1">
                    <label>Joueur 2 : <input type="text" class="joueur-input" data-team="1" data-joueur="2" placeholder="Nom joueur 2"></label>
                </div>
            </div>

            <div class="team-setup" id="setup-team2">
                <h4 id="nom-equipe-2-label"></h4>
                <label>Format :
                    <select class="format-select" data-team="2">
                        <option value="double" selected>Double</option>
                        <option value="simple">Simple</option>
                    </select>
                </label>
                <div>
                    <label>Joueur 1 : <input type="text" class="joueur-input" data-team="2" data-joueur="1" placeholder="Nom joueur 1"></label>
                </div>
                <div class="joueur2-wrapper" data-team="2">
                    <label>Joueur 2 : <input type="text" class="joueur-input" data-team="2" data-joueur="2" placeholder="Nom joueur 2"></label>
                </div>
            </div>

            <button id="btn-start-match" type="button" class="btn-action">Démarrer le match</button>
        </div>

        <div id="match-play" style="display:none;">

            <div id="timer-container"></div>

            <div id="sets-info">
                <span>Sets gagnés : </span>
                <span id="sets-gagnes-1">0</span> - <span id="sets-gagnes-2">0</span>
                <span id="set-actuel-label"></span>
            </div>
            <div id="historique-sets" class="historique-sets"></div>
            <div id="options">
                <label>
                    <input type="checkbox" id="chk-auto-update">
                    Mise à jour auto de la BDD à chaque point
                </label>
                <button id="btn-save-manuel" type="button" class="btn-action secondaire">Sauvegarder le score maintenant</button>
            </div>

            <div id="terrain-plan" class="terrain">
                <!-- Plan du terrain avec 2 côtés -->
                <div class="cote" id="cote-gauche">
                    <h4 id="nom-equipe-gauche"></h4>
                    <div class="score" id="score-gauche">0</div>
                    <div class="joueurs-cote" id="joueurs-gauche">
                        <!-- positions générées en JS : position-haut / position-bas -->
                    </div>
                    <button type="button" id="btn-switch-cote-gauche" class="btn-action warning">Changer position joueurs (erreur)</button>
                    <div>
                        <button type="button" class="btn-point btn-plus" data-team="gauche">+1 Point</button>
                        <button type="button" class="btn-moins btn-minus" data-team="gauche">-1 Point</button>
                    </div>
                </div>

                <div class="filet"></div>

                <div class="cote" id="cote-droite">
                    <h4 id="nom-equipe-droite"></h4>
                    <div class="score" id="score-droite">0</div>
                    <div class="joueurs-cote" id="joueurs-droite">
                    </div>
                    <button type="button" id="btn-switch-cote-droite" class="btn-action warning">Changer position joueurs (erreur)</button>
                    <div>
                        <button type="button" class="btn-point btn-plus" data-team="droite">+1 Point</button>
                        <button type="button" class="btn-moins btn-minus" data-team="droite">-1 Point</button>
                    </div>
                </div>
            </div>

            <div id="serveur-info">
                <strong>Au service : </strong><span id="serveur-actuel">-</span>
                <button type="button" id="btn-change-serveur" class="btn-action warning">Corriger le serveur (erreur)</button>
            </div>

            <div id="controles-set" class="controles-generaux">
                <button type="button" id="btn-changer-cote-manuel" class="btn-action secondaire">Changer de côté (manuel)</button>
                <button type="button" id="btn-set-suivant" class="btn-action" style="display:none;">Set suivant</button>
                <button type="button" id="btn-terminer-match" class="btn-action" style="display:none;">Terminer le match</button>
            </div>

        </div>

    </div>

    <script>
    const ID_TOURNOI = <?php echo (int)$id_tournoi; ?>;
    const TOURNOI_TROISSETS = <?php echo (int)$tournoi_troissets_match; ?>;
    const SHOW_TIMER = <?php echo $show_timer ? 'true' : 'false'; ?>;
    </script>
    <script src="js/scoring.js"></script>
    <?php if ($show_timer): ?>
    <script src="js/timer.js"></script>
    <script>
    TournamentTimer.init({
        idtournoi: <?php echo (int)$id_tournoi; ?>,
        containerId: 'timer-container',
        showControls: false,
        playSound: false
    });
    </script>
    <?php endif; ?>

    <script>
    document.getElementById('theme-toggle').addEventListener('click', function() {
        const html = document.documentElement;
        const current = html.getAttribute('data-theme');
        html.setAttribute('data-theme', current === 'dark' ? 'light' : 'dark');
    });
    </script>

</body>

</html>