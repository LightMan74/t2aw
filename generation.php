<?php
// generation.php
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Génération des matchs - Poules</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <nav>
        <a href="index.php">Accueil</a>
        <a href="generation.php">Génération</a>
        <a href="matchs.php">Matchs</a>
        <a href="classement.php">Classement</a>
    </nav>

    <h1>Génération de l'ordre des matchs de poule</h1>

    <label>ID Tournoi :</label>
    <input type="number" id="id_tournoi" value="1" style="width:100px; margin-bottom:15px;">
    <button onclick="chargerMatchs()">Charger / Générer l'ordre</button>

    <div id="message"></div>

    <h2>Ordre proposé (glisser-déposer pour réorganiser)</h2>
    <div id="liste-matchs"></div>

    <button onclick="validerOrdre()" style="margin-top:15px;">Valider et enregistrer les matchs</button>
</div>

<script src="js/generation.js"></script>
</body>
</html>
