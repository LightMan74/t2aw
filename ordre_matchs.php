<?php
$tournoi_id = isset($_GET['id_tournoi']) ? (int)$_GET['id_tournoi'] : 0;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/var.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dark-mode.css">
    <link rel="stylesheet" href="css/ordre_matchs.css">
</head>

<body>
    <div class="container">
        <nav>
            <?php
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

        <h1>Gestion de l'ordre des matchs (Poules + Phases finales)</h1>

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

                <div class="toggle-btn">
                    <label onclick="autosaveandreload()" class="toggle-btn-label">Enregistrer</label>
                </div>

                <div class="toggle-btn">
                    <label onclick="enregistrerOrdre()" class="toggle-btn-label btn-ordre-save">💾 Enregistrer l'ordre</label>
                </div>
            </div>

            <div id="message"></div>
        </div>

        <input type="number" id="id_tournoi" value="<?php echo $tournoi_id; ?>" style="width:100px; margin-bottom:15px;" hidden>

        <p class="text-muted">Glissez-déposez les lignes (⠿) pour réordonner tous les matchs, puis cliquez sur "Enregistrer l'ordre".</p>

        <table id="table-matchs">
            <thead>
                <tr>
                    <th><nobr>⠿</nobr></th>
                    <th><nobr>Match</nobr></th>
                    <th><nobr>Catégorie</nobr></th>
                    <th><nobr>Poule</nobr></th>
                    <th><nobr>Equipe 1</nobr></th>
                    <th><nobr>SCORE</nobr></th>
                    <th><nobr>Equipe 2</nobr></th>
                    <th><nobr>Terrain</nobr></th>
                    <th><nobr>Statut</nobr></th>
                    <th><nobr>Heure début</nobr></th>
                    <th><nobr>------</nobr></th>
                </tr>
            </thead>
            <tbody id="corps-table"></tbody>
        </table>
    </div>

    <script>
    const tournoi_troissets_match = <?= json_encode($tournoi_troissets_match); ?>;
    const id_tournoi_js = <?= json_encode($tournoi_id); ?>;
    </script>
    <script src="js/colors.js"></script>
    <script src="js/ordre_matchs.js"></script>
</body>

</html>