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
    <link rel="stylesheet" href="css/feuilles_score.css">
    <title>Impression des feuilles de score</title>
</head>

<body>
    <div class="container">
        <nav>
            <?php
            include 'menu.php';
            // Récupération des paramètres tournoi (même requête que ordre_matchs.php)
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

            // Nombre de sets : on dérive un entier 1 ou 3 à partir du paramètre `troissets`
            // de la table `parametre`.
            //  - valeur truthy OU numérique >= 1 -> 3 sets
            //  - valeur falsy / null / 0 -> 1 set
            // $troissets_raw = $parametres['troissets'] ?? null;
            // if ($troissets_raw === null || $troissets_raw === false || $troissets_raw === '' || $troissets_raw === 0) {
            //     $nb_sets = 1;
            // } else {
            //     $nb_sets = 3;
            // }

            // Tentative de récupération d'un nom de tournoi.
            // NB: la table `parametre` vue dans ordre_matchs.php ne contient pas
            // explicitement un nom de tournoi. On essaie sans casser si la colonne
            // n'existe pas : fallback sur "Tournoi #ID".
            $tournoi_nom = 'Tournoi #' . $tournoi_id;
            try {
                $stmtNom = $pdo->prepare("SELECT nom FROM parametre WHERE id_tournoi = :id LIMIT 1");
                $stmtNom->execute(['id' => $tournoi_id]);
                $rowNom = $stmtNom->fetch(PDO::FETCH_ASSOC);
                if ($rowNom && !empty($rowNom['nom'])) {
                    $tournoi_nom = $rowNom['nom'];
                }
            } catch (Exception $e) {
                // silencieux : on garde le placeholder
            }
            ?>
        </nav>

        <h1>Impression des feuilles de score</h1>

        <div class="container-heure-message">
            <div class="option-heure-manuelle zone-selection">
                <div class="toggle-btn">
                    <label onclick="toutSelectionner()" class="toggle-btn-label">Tout sélectionner</label>
                    <label onclick="toutDeselectionner()" class="toggle-btn-label">Tout désélectionner</label>
                    <label>---</label>
                    <label onclick="selectionnerNonTermines()" class="toggle-btn-label">Sélectionner non terminés</label>
                </div>
            </div>

            <div class="option-heure-manuelle zone-filtres">
                <div class="toggle-btn">
                    <label>Filtre type :</label>
                    <select id="filtre-source" class="filtre-select" onchange="appliquerFiltres()">
                        <option value="">Tous</option>
                        <option value="poule">Poules</option>
                        <option value="pf">Phase finale</option>
                    </select>
                </div>
                <div class="toggle-btn">
                    <label>Filtre statut :</label>
                    <select id="filtre-statut" class="filtre-select" onchange="appliquerFiltres()">
                        <option value="">Tous</option>
                        <option value="planifie">Planifié</option>
                        <option value="en_cours">En cours</option>
                        <option value="termine">Terminé</option>
                        <option value="annulee">Annulé</option>
                    </select>
                </div>
                <div class="toggle-btn">
                    <label>Filtre terrain :</label>
                    <select id="filtre-terrain" class="filtre-select" onchange="appliquerFiltres()">
                        <option value="">Tous</option>
                    </select>
                </div>
            </div>

            <div class="option-heure-manuelle zone-boutons">
                <div class="toggle-btn">
                    <label onclick="chargerMatchs()" class="toggle-btn-label">Charger les matchs</label>
                    <label onclick="lancerImpression()" class="toggle-btn-label btn-imprimer" style="background:#2a7ae2; color:#fff;">Imprimer</label>
                </div>
            </div>

            <div id="message"></div>
        </div>

        <input type="number" id="id_tournoi" value="<?php echo (int)$tournoi_id; ?>" style="width:100px; margin-bottom:15px;" hidden>

        <p class="text-muted">Cochez les matchs pour lesquels imprimer une feuille de score, puis cliquez sur "Imprimer".</p>

        <table id="table-matchs">
            <thead>
                <tr>
                    <th>
                        <nobr>✓</nobr>
                    </th>
                    <th>
                        <nobr>Match</nobr>
                    </th>
                    <th>
                        <nobr>Type</nobr>
                    </th>
                    <?php if ($nbre_categories > 1) { ?>
                    <th>
                        <nobr>Catégorie</nobr>
                    </th>
                    <?php } ?>
                    <th>
                        <nobr>Poule / Phase</nobr>
                    </th>
                    <th>
                        <nobr>Équipe 1</nobr>
                    </th>
                    <th>
                        <nobr>vs</nobr>
                    </th>
                    <th>
                        <nobr>Équipe 2</nobr>
                    </th>
                    <th>
                        <nobr>Terrain</nobr>
                    </th>
                    <th>
                        <nobr>Statut</nobr>
                    </th>
                    <th>
                        <nobr>Heure</nobr>
                    </th>
                </tr>
            </thead>
            <tbody id="corps-table"></tbody>
        </table>
    </div>

    <!-- Zone d'impression : cachée à l'écran, affichée à l'impression -->
    <div id="zone-impression" class="zone-impression" aria-hidden="true"></div>

    <script>
    const id_tournoi_js = <?= json_encode($tournoi_id); ?>;
    const tournoi_nom_js = <?= json_encode($tournoi_nom); ?>;
    const tournoi_troissets_match = <?= json_encode((int)$tournoi_troissets_match); ?>;
    const nb_sets_js = <?= json_encode((int)$tournoi_troissets_match); ?>;
    const nbre_terrain_poule_js = <?= json_encode((int)$nbre_terrain_poule); ?>;
    const nbre_terrain_phasefinal_js = <?= json_encode((int)$nbre_terrain_phasefinal); ?>;
    const nbre_categories_js = <?= json_encode((int)$nbre_categories); ?>;
    </script>
    <script src="js/feuilles_score.js"></script>
</body>

</html>