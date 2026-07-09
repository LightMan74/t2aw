<?php
// matchs.php
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Gestion des matchs</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="container">
        <nav>
            <?php 
            include "api/check_connected.php";
            include 'menu.php'; ?>
        </nav>

        <h1>Gestion des matchs de poule</h1>
        <button onclick="autosaveandreload()">Enregistrer</button>
        <!-- <label>ID Tournoi :</label> -->
        <input type="number" id="id_tournoi" value="<?php echo $_GET["id_tournoi"];?>" style="width:100px; margin-bottom:15px;" hidden>
        <!-- <input type="number" id="id_tournoi" value="1" style="width:100px; margin-bottom:15px;"> -->
        <!-- <button onclick="chargerMatchs()">Charger les matchs</button> -->

        <div id="message"></div>

        <table id="table-matchs">
            <thead>
                <tr>
                    <th>Catégorie</th>
                    <th>Poule</th>
                    <th>Terrain</th>
                    <th>Equipe 1</th>
                    <th>SET 1</th>
                    <th>SET 2</th>
                    <th>SET 3</th>
                    <th>vs</th>
                    <th>SET 1</th>
                    <th>SET 2</th>
                    <th>SET 3</th>
                    <th>Equipe 2</th>
                    <th>Statut</th>
                    <th>Heure début</th>
                </tr>
            </thead>
            <tbody id="corps-table"></tbody>
        </table>
    </div>

    <script src="js/matchs.js"></script>
    <script>
    chargerMatchs();
    </script>
</body>

</html>