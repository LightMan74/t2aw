<?php
// include __DIR__ . "/api/check_connected.php";

// Si id_tournoi absent ou = 0 -> mode création
$id_tournoi = isset($_GET['id_tournoi']) ? (int)$_GET['id_tournoi'] : 0;
$mode_creation = $id_tournoi === 0;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <title><?php echo $mode_creation ? 'Créer un tournoi' : 'Modifier le tournoi'; ?> - Gestion Tournois Badminton</title> -->
    <link rel="stylesheet" href="css/var.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dark-mode.css">
</head>

<body>
    <div class="container">
        <nav>
            <?php include 'menu.php'; ?>
        </nav>

        <a href="dashboard.php" class="btn btn-back">← Retour</a>

        <h1 id="page-title"><?php echo $mode_creation ? 'Créer un nouveau tournoi' : 'Modifier le tournoi'; ?></h1>

        <div id="loading-message" style="<?php echo $mode_creation ? 'display:none;' : ''; ?>">
            Chargement des données...
        </div>

        <section class="section" id="edit-section" style="<?php echo $mode_creation ? '' : 'display:none;'; ?>">
            <form id="form-edit-tournoi">
                <input type="hidden" id="id_tournoi" value="<?php echo $id_tournoi; ?>">

                <div class="form-group">
                    <label for="nom_tournoi">Nom du tournoi</label>
                    <input type="text" id="nom_tournoi" name="nom_tournoi" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="terrain_automatique">Attribution automatique des terrains</label>
                        <select id="terrain_automatique" name="terrain_automatique" required>
                            <option value="1" selected>Vrai</option>
                            <option value="0">Faux</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nbre_terrain_poule">Nombre de terrains (poules)</label>
                        <input type="number" id="nbre_terrain_poule" name="nbre_terrain_poule" min="1" max="50" step="1" value="4" required inputmode="numeric" pattern="[0-9]*" onkeydown="return blockInvalidNumberKeys(event)" onpaste="return blockInvalidPaste(event)">
                    </div>
                    <div class="form-group">
                        <label for="nbre_terrain_phasefinal">Nombre de terrains (phase finale)</label>
                        <input type="number" id="nbre_terrain_phasefinal" name="nbre_terrain_phasefinal" min="1" max="50" step="1" value="2" required inputmode="numeric" pattern="[0-9]*" onkeydown="return blockInvalidNumberKeys(event)" onpaste="return blockInvalidPaste(event)">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="troissets">Nombre de sets</label>
                        <select id="troissets" name="troissets" required>
                            <option value="1">1 set</option>
                            <option value="3" selected>3 sets</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="temps_de_match">Temps de match (minutes)</label>
                        <input type="number" id="temps_de_match" name="temps_de_match" min="5" max="120" step="1" value="15" required inputmode="numeric" pattern="[0-9]*" onkeydown="return blockInvalidNumberKeys(event)" onpaste="return blockInvalidPaste(event)">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="heure_debut_poule">Heure de début (poules)</label>
                        <input type="time" id="heure_debut_poule" name="heure_debut_poule" value="">
                    </div>
                    <div class="form-group">
                        <label for="heure_debut_phasefinal">Heure de début (phase finale)</label>
                        <input type="time" id="heure_debut_phasefinal" name="heure_debut_phasefinal" value="">
                    </div>
                </div>
                <div class="form-group">
                    <label>Nombre de catégories</label>
                    <div class="counter-control">
                        <button type="button" class="btn-minus" onclick="ajusterCategories(-1)">−</button>
                        <span id="nbre_categories">1</span>
                        <button type="button" class="btn-plus" onclick="ajusterCategories(1)">+</button>
                    </div>
                </div>
                <div id="categories-container"></div>

                <div id="error-message" class="error-message" style="display:none;"></div>
                <p id="form-message" class="message"></p>
                <button type="submit" class="btn btn-primary">
                    <?php echo $mode_creation ? 'Créer le tournoi' : 'Enregistrer les modifications'; ?>
                </button>
            </form>
        </section>

    </div>

    <script>
    window.idTournoi = <?php echo (int)$id_tournoi; ?>;
    window.modeCreation = <?php echo $mode_creation ? 'true' : 'false'; ?>;
    </script>
    <script src="js/edit.js"></script>
</body>

</html>