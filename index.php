<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Tournois Badminton</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="container">
        <h1>Gestion des Tournois de Badminton</h1>
        <div class="container">
            <nav>
                <?php include 'menu.php'; ?>
            </nav>
        </div>
        <!-- Liste des tournois -->
        <section class="section">
            <h2>Tournois existants</h2>
            <div id="tournoi-list">
                <p class="loading">Chargement...</p>
            </div>
            <div id="liste-tournois"></div>
        </section>


        <!-- Création d'un nouveau tournoi -->
        <section class="section">
            <h2>Créer un nouveau tournoi</h2>
            <form id="form-create-tournoi">
                <div class="form-group">
                    <label for="nom_tournoi">Nom du tournoi</label>
                    <input type="text" id="nom_tournoi" name="nom_tournoi" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nbre_terrain_poule">Nombre de terrains (poules)</label>
                        <input type="number" id="nbre_terrain_poule" name="nbre_terrain_poule" min="1" value="4" required>
                    </div>
                    <div class="form-group">
                        <label for="nbre_terrain_phasefinal">Nombre de terrains (phase finale)</label>
                        <input type="number" id="nbre_terrain_phasefinal" name="nbre_terrain_phasefinal" min="1" value="2" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="temps_de_match">Temps de match (minutes)</label>
                    <input type="number" id="temps_de_match" name="temps_de_match" min="5" value="15" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="heure_debut_poule">Heure début poules</label>
                        <input type="time" id="heure_debut_poule" name="heure_debut_poule" value="09:00" required>
                    </div>
                    <div class="form-group">
                        <label for="heure_debut_phasefinal">Heure début phase finale</label>
                        <input type="time" id="heure_debut_phasefinal" name="heure_debut_phasefinal" value="14:00" required>
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

                <button type="submit" class="btn btn-primary">Créer le tournoi</button>
                <p id="form-message" class="message"></p>
            </form>
        </section>
    </div>

    <script src="js/main.js"></script>
</body>

</html>