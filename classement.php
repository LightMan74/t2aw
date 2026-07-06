<?php
// classement.php
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Classement</title>
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

        <h1>Classement des poules</h1>

        <!-- <label>ID Tournoi :</label> -->
        <input type="number" id="id_tournoi" value="<?php echo $_GET["idtournoi"];?>" style="width:100px; margin-bottom:15px;" hidden>
        <!-- <input type="number" id="id_tournoi" value="1" style="width:100px; margin-bottom:15px;"> -->
        <!-- <button onclick="chargerClassement()">Charger le classement</button> -->

        <div id="message"></div>
        <div id="zone-classement"></div>
    </div>

    <script src="js/classement.js"></script>
    <script>
    chargerClassement();
    </script>
</body>

</html>