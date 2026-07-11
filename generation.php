<?php
// generation.php
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Génération des matchs - Poules</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dark-mode.css">
</head>

<body>
    <div class="container">
        <nav>
            <?php
include "api/check_connected.php";
include 'menu.php'; ?>
        </nav>

        <h1>Génération de l'ordre des matchs de poule</h1>

        <!-- <label>ID Tournoi :</label> -->
        <input type="number" id="id_tournoi" value="<?php echo $_GET["id_tournoi"];?>" style="width:100px; margin-bottom:15px;" hidden>
        <!-- <input type="number" id="id_tournoi" value="1" style="width:100px; margin-bottom:15px;"> -->
        <button onclick="chargerMatchs()" hidden>Charger / Générer l'ordre</button>



        <h2>Ordre proposé (glisser-déposer pour réorganiser)</h2>
        <div id="liste-matchs"></div>

        <div id="message"></div>
        <button onclick="validerOrdre()" style="margin-top:15px;">Valider et enregistrer les matchs</button>


    </div>

    <script src="js/generation.js"></script>
    <script>
    chargerMatchs();
    </script>
</body>

</html>