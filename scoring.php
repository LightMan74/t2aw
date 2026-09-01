<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$id_tournoi = isset($_GET['id_tournoi']) ? (int)$_GET['id_tournoi'] : 0;

if (!$id_tournoi) {
    die("id_tournoi manquant dans l'URL");
}

require 'api/db.php';

// Récupération du nom du tournoi et des paramètres
$stmt = $pdo->prepare("SELECT nom FROM tournoi WHERE id_tournoi = :id LIMIT 1");
$stmt->execute(['id' => $id_tournoi]);
$tournoiInfo = $stmt->fetch(PDO::FETCH_ASSOC);
$nom_tournoi = $tournoiInfo ? $tournoiInfo['nom'] : 'Tournoi';

$tournoi_troissets_match = 3;
$stmt = $pdo->prepare("SELECT troissets FROM parametre WHERE id_tournoi = :id");
$stmt->execute(['id' => $id_tournoi]);
$parametres = $stmt->fetch(PDO::FETCH_ASSOC);
if ($parametres !== false && isset($parametres['troissets'])) {
    $tournoi_troissets_match = $parametres['troissets'];
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arbitrage - <?php echo htmlspecialchars($nom_tournoi); ?></title>
    <link rel="stylesheet" href="css/scoring.css">
</head>

<body>

    <div id="app" data-id-tournoi="<?php echo $id_tournoi; ?>" data-troissets="<?php echo $tournoi_troissets_match; ?>">

        <header class="header-tournoi">
            <h1><?php echo htmlspecialchars($nom_tournoi); ?></h1>
        </header>

        <!-- Sélection du match -->
        <section id="selection-match">
            <label for="select-terrain">Choisir un match en cours :</label>
            <select id="select-terrain">
                <option value="">-- Sélectionner --</option>
            </select>
            <button id="btn-charger-match">Charger le match</button>
        </section>

        <!-- Interface de scoring -->
        <section id="interface-scoring" style="display:none;">

            <div class="info-match">
                <span id="info-terrain"></span> -
                <span id="info-categorie"></span> -
                <span id="info-poule"></span>
            </div>

            <!-- Saisie des noms de joueurs -->
            <div id="saisie-joueurs">
                <div class="equipe-joueurs" id="joueurs-equipe-1">
                    <h3 id="nom-equipe-1-label"></h3>
                    <input type="text" id="joueur1-equipe1" placeholder="Joueur 1">
                    <input type="text" id="joueur2-equipe1" placeholder="Joueur 2 (double)" style="display:none;">
                </div>
                <div class="equipe-joueurs" id="joueurs-equipe-2">
                    <h3 id="nom-equipe-2-label"></h3>
                    <input type="text" id="joueur1-equipe2" placeholder="Joueur 1">
                    <input type="text" id="joueur2-equipe2" placeholder="Joueur 2 (double)" style="display:none;">
                </div>
                <div>
                    <label><input type="radio" name="type-match" value="simple" checked> Simple</label>
                    <label><input type="radio" name="type-match" value="double"> Double</label>
                </div>
                <button id="btn-valider-joueurs">Valider et commencer le match</button>
            </div>

            <!-- Timer (format 1 set) -->
            <div id="timer-container" style="display:none;"></div>

            <!-- Terrain de jeu avec rotation -->
            <div id="terrain-jeu" style="display:none;">

                <div class="options-scoring">
                    <label>
                        <input type="checkbox" id="chk-sync-bdd" checked>
                        Enregistrer le score en temps réel
                    </label>
                    <button id="btn-changer-cote">🔄 Inverser les côtés</button>
                    <button id="btn-erreur-service">⚠️ Corriger le serveur</button>
                </div>

                <div class="score-sets">
                    <div id="sets-equipe-1" class="sets-gagnes"></div>
                    <span>Sets gagnés</span>
                    <div id="sets-equipe-2" class="sets-gagnes"></div>
                </div>

                <div class="terrain-visuel">

                    <!-- Côté A -->
                    <div class="cote cote-gauche" id="cote-A">
                        <div class="position position-haut" id="pos-A-haut"></div>
                        <div class="position position-bas" id="pos-A-bas"></div>
                    </div>

                    <!-- Filet -->
                    <div class="filet"></div>

                    <!-- Côté B -->
                    <div class="cote cote-droite" id="cote-B">
                        <div class="position position-haut" id="pos-B-haut"></div>
                        <div class="position position-bas" id="pos-B-bas"></div>
                    </div>

                </div>

                <div class="score-actuel">
                    <div class="bloc-equipe" id="bloc-equipe-1">
                        <h2 id="nom-equipe-1-score"></h2>
                        <div class="score-value" id="score-equipe-1">0</div>
                        <button class="btn-point" data-equipe="1">+1 Point</button>
                        <button class="btn-annuler" data-equipe="1">Annuler dernier point</button>
                    </div>

                    <div class="separateur-vs">VS</div>

                    <div class="bloc-equipe" id="bloc-equipe-2">
                        <h2 id="nom-equipe-2-score"></h2>
                        <div class="score-value" id="score-equipe-2">0</div>
                        <button class="btn-point" data-equipe="2">+1 Point</button>
                        <button class="btn-annuler" data-equipe="2">Annuler dernier point</button>
                    </div>
                </div>

                <div class="set-info">
                    Set n° <span id="set-numero">1</span> / <span id="set-total"></span>
                </div>

                <div class="historique-sets" id="historique-sets"></div>

                <div class="actions-match">
                    <button id="btn-fin-match" style="display:none;">Terminer le match</button>
                </div>

            </div>

        </section>

    </div>

    <script src="js/timer.js"></script>
    <script src="js/scoring.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        ScoringApp.init({
            idTournoi: <?php echo $id_tournoi; ?>,
            troisSets: <?php echo $tournoi_troissets_match; ?>
        });
    });
    </script>

</body>

</html>