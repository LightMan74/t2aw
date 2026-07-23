<?php
// matchs.php
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <!-- <title>Gestion des matchs</title> -->
    <link rel="stylesheet" href="css/var.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dark-mode.css">
</head>

<body>
    <div class="container">
        <nav>
            <?php 
            // include __DIR__ . "/check_connected.php";
            include 'menu.php'; 
        $stmt = $pdo->prepare("
            SELECT troissets
            FROM parametre
            WHERE id_tournoi = :id
        ");
        $stmt->execute(['id' => htmlspecialchars(($_GET["id_tournoi"]))]);
        $tournoi_troissets_match = $stmt->fetchAll(PDO::FETCH_ASSOC)[0]['troissets'];
            ?>

        </nav>

        <h1>Gestion des matchs de poule</h1>

        <!-- <label>ID Tournoi :</label> -->
        <!-- <input type="number" id="id_tournoi" value="1" style="width:100px; margin-bottom:15px;"> -->
        <!-- <button onclick="chargerMatchs()">Charger les matchs</button> -->
        <div class="container-heure-message">
            <div class="option-heure-manuelle">

                <input type="time" id="heureManuelleInput" style="display:none;">
                <div class="toggle-btn">
                    <input type="checkbox" id="heureManuelleCheckbox" onchange="toggleHeureManuelle()">
                    <label for="heureManuelleCheckbox" class="toggle-btn-label">Heure manuelle</label>
                </div>

                <div class="toggle-btn">
                    <input type="checkbox" id="matchtermineCheckbox" onchange="togglematchtermine()">
                    <label for="matchtermineCheckbox" class="toggle-btn-label">Voir Match terminé</label>
                </div>

                <!-- <div class="toggle-btn">
                        <input type="checkbox" id="decalageTousTerrainsListePF">
                        <label for="decalageTousTerrainsListePF" class="toggle-btn-label">Appliquer le décalage horaire à tous les terrains</label>
                    </div> -->

                <div class="toggle-btn">
                    <!-- <button onclick="autosaveandreloadListePF()" class="toggle-btn-label">Enregistrer</button> -->
                    <label for="decalageTousTerrains" onclick="autosaveandreload()" class="toggle-btn-label">Enregistrer</label>
                </div>

            </div>

            <div id="message"></div>
        </div>
        <input type="number" id="id_tournoi" value="<?php echo $_GET["id_tournoi"];?>" style="width:100px; margin-bottom:15px;" hidden>

        <table id="table-matchs">
            <thead>
                <tr>
                    <th>
                        <nobr>Catégorie</nobr>
                    </th>
                    <th>
                        <nobr>Poule</nobr>
                    </th>
                    <th>
                        <nobr>Match N°</nobr>
                    </th>
                    <th>
                        <nobr>Equipe 1</nobr>
                    </th>
                    <th>
                        <nobr>SCORE</nobr>
                    </th>
                    <th>
                        <nobr>Equipe 2</nobr>
                    </th>
                    <th>
                        <nobr>Terrain</nobr>
                    </th>
                    <th>
                        <nobr>Statut</nobr>
                    </th>
                    <th>
                        <nobr>Heure début</nobr>
                    </th>
                    <th>------</th> <!-- Colonne pour l'icône de sauvegarde -->
                </tr>
            </thead>
            <tbody id="corps-table"></tbody>
        </table>
    </div>

    <script>
    const tournoi_troissets_match = <?= json_encode($tournoi_troissets_match); ?>;
    </script>
    <script src="js/colors.js"></script>
    <script src="js/matchs.js"></script>
    <script>
    // chargerMatchs();
    </script>
</body>

</html>