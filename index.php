<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion Tournois Badminton</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <h1>🏸 Gestion des Tournois de Badminton</h1>
</header>

<main>

    <!-- ================= LISTE DES TOURNOIS ================= -->
    <section id="section-liste">
        <div class="section-header">
            <h2>Tournois en cours</h2>
            <button id="btn-nouveau-tournoi">+ Nouveau tournoi</button>
        </div>
        <div id="liste-tournois" class="liste-tournois">
            <p class="loading">Chargement...</p>
        </div>
    </section>

    <!-- ================= FORMULAIRE CREATION ================= -->
    <section id="section-creation" class="hidden">
        <div class="section-header">
            <h2>Créer un nouveau tournoi</h2>
            <button id="btn-annuler">← Retour</button>
        </div>

        <form id="form-tournoi">

            <fieldset>
                <legend>Informations générales</legend>

                <label>Nom du tournoi
                    <input type="text" name="nom_tournoi" required>
                </label>

                <label>Nombre de terrains (poule)
                    <input type="number" name="nbre_terrain_poule" min="1" value="1" required>
                </label>

                <label>Nombre de terrains (phase finale)
                    <input type="number" name="nbre_terrain_phasefinal" min="1" value="1" required>
                </label>

                <label>Durée d'un match (minutes)
                    <input type="number" name="temps_de_match" min="1" value="15" required>
                </label>

                <label>Heure de début des poules
                    <input type="time" name="heure_debut_poule" value="09:00" required>
                </label>

                <label>Heure de début de la phase finale
                    <input type="time" name="heure_debut_phasefinal" value="14:00" required>
                </label>

            </fieldset>

            <fieldset>
                <legend>Structure du tournoi</legend>

                <label>Nombre de catégories
                    <input type="number" id="nbre_categorie" min="1" value="1" required>
                </label>

                <div id="conteneur-categories"></div>

            </fieldset>

            <button type="submit" id="btn-submit">Créer le tournoi</button>
            <p id="message-retour"></p>
        </form>
    </section>

</main>

<script src="js/main.js"></script>
</body>
</html>
