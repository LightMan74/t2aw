<?php
include "api/check_connected.php";

// Récupérer l'id_tournoi depuis l'URL
$id_tournoi = isset($_GET['id_tournoi']) ? (int)$_GET['id_tournoi'] : 0;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le tournoi - Gestion Tournois Badminton</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dark-mode.css">
</head>
<div class="container">
    <nav>
        <?php include 'menu.php'; ?>
    </nav>
</div>

<body>
    <div class="container">
        <!-- <a href="index.php" class="btn btn-back">← Retour</a> -->

        <h1>Modifier le tournoi</h1>

        <div id="loading-message">Chargement des données...</div>

        <section class="section" id="edit-section" style="display:none;">
            <form id="form-edit-tournoi">
                <input type="hidden" id="id_tournoi" value="<?php echo $id_tournoi; ?>">

                <div class="form-group">
                    <label for="nom_tournoi">Nom du tournoi</label>
                    <input type="text" id="nom_tournoi" name="nom_tournoi" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nbre_terrain_poule">Nombre de terrains (poules)</label>
                        <input type="number" id="nbre_terrain_poule" name="nbre_terrain_poule" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="nbre_terrain_phasefinal">Nombre de terrains (phase finale)</label>
                        <input type="number" id="nbre_terrain_phasefinal" name="nbre_terrain_phasefinal" min="1" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="troissets">Nombre de set</label>
                        <input type="number" id="troissets" name="troissets" min="1" max="3" value="3" step="2" required>
                    </div>
                    <div class="form-group">
                        <label for="temps_de_match">Temps de match (minutes)</label>
                        <input type="number" id="temps_de_match" name="temps_de_match" min="5" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="heure_debut_poule">Heure début poules</label>
                        <input type="time" id="heure_debut_poule" name="heure_debut_poule" required>
                    </div>
                    <div class="form-group">
                        <label for="heure_debut_phasefinal">Heure début phase finale</label>
                        <input type="time" id="heure_debut_phasefinal" name="heure_debut_phasefinal" required>
                    </div>
                </div>

                <!-- Catégories dynamiques -->
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
                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            </form>
        </section>

    </div>

    <script>
    // Passer l'ID du tournoi en JS
    window.idTournoi = <?php echo $id_tournoi; ?>;
    </script>
    <script src="js/edit.js"></script>
</body>

</html>