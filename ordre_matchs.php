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
                SELECT p.troissets, p.nbre_terrain_poule, p.nbre_terrain_phasefinal, COUNT(`id_categorie`) as nbre_categories
                FROM parametre p
                left join `categorie` c on c.id_tournoi = p.id_tournoi
                WHERE p.id_tournoi = :id
            ");
            $stmt->execute(['id' => $tournoi_id]);
            $parametres = $stmt->fetch(PDO::FETCH_ASSOC);
            $tournoi_troissets_match = $parametres['troissets'] ?? 1;
            $nbre_terrain_poule = $parametres['nbre_terrain_poule'] ?? 1;
            $nbre_terrain_phasefinal = $parametres['nbre_terrain_phasefinal'] ?? 1;
            $nbre_categories = $parametres['nbre_categories'] ?? 1;
            ?>
        </nav>

        <h1>Gestion de l'ordre des matchs (Poules + Phases finales)</h1>

        <div class="container-heure-message">
            <div class="option-heure-manuelle" style="margin-right: auto;margin-left: 5px;">
                <div class="toggle-btn">
                    <label onclick="resetOrdre(id_tournoi_js)" class="toggle-btn-label btn-ordre-save" style="background: red;">Reinitialisé l'ordre</label>
                    <label onclick="enregistrerOrdre()" class="toggle-btn-label btn-ordre-save">Enregistrer l'ordre<span id="icone-modif-ordre" class="icone-modif-attente" hidden title="L'ordre a été modifié et n'est pas encore enregistré">⚠️💾⚠️</span></label>
                    <label>---</label>
                    <label onclick="chargerMatchs(true)" class="toggle-btn-label btn-ordre-save">Inversé ordre Phase Final</label>
                    <label>---</label>
                    <label onclick="resetTerrains()" class="toggle-btn-label btn-ordre-save" style="background: red;">Reinitialisé les terrains</label>
                    <label onclick="reassignerTerrains()" class="toggle-btn-label btn-ordre-save" style="background: #2a7ae2;">Réassigner les terrains</label>
                </div>
            </div>
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
            </div>

            <div id="message"></div>
        </div>

        <input type="number" id="id_tournoi" value="<?php echo $tournoi_id; ?>" style="width:100px; margin-bottom:15px;" hidden>

        <p class="text-muted">Glissez-déposez les lignes (⠿) pour réordonner tous les matchs, puis cliquez sur "Enregistrer l'ordre".</p>
        <table id="table-matchs">
            <thead>
                <tr>
                    <th>
                        <nobr>⠿</nobr>
                    </th>
                    <th>
                        <nobr>Match</nobr>
                    </th>
                    <?php 
                        if ($nbre_categories > 1){?>
                    <th>
                        <nobr>Catégorie</nobr>
                    </th>
                    <?php } ?>
                    <th>
                        <nobr>Poule</nobr>
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
                    <th>
                        <nobr>------</nobr>
                    </th>
                </tr>
            </thead>
            <tbody id="corps-table"></tbody>
        </table>
    </div>
    <script>
    const tournoi_troissets_match = <?= json_encode($tournoi_troissets_match); ?>;
    const id_tournoi_js = <?= json_encode($tournoi_id); ?>;
    const nbre_terrain_poule_js = <?= json_encode((int)$nbre_terrain_poule); ?>;
    const nbre_terrain_phasefinal_js = <?= json_encode((int)$nbre_terrain_phasefinal); ?>;
    const nbre_categories_js = <?= json_encode((int)$nbre_categories); ?>;
    </script>
    <script src="js/colors.js"></script>
    <script src="js/ordre_matchs.js"></script>
</body>

</html>