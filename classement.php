<?php
// classement.php
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Classement</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dark-mode.css">
</head>

<body>
    <div class="container">
        <nav>
            <?php include 'menu.php'; ?>
        </nav>

        <h1>Classement des poules</h1>

        <input type="number" id="id_tournoi" value="<?php echo $_GET["id_tournoi"];?>" style="width:100px; margin-bottom:15px;" hidden>

        <div id="message"></div>
        <div id="zone-classement"></div>
    </div>

    <script src="js/colors.js"></script>
    <script src="js/classement.js"></script>
    <script>
        (function () {
            var input = document.getElementById('id_tournoi');
            if (input) document.body.dataset.idTournoi = input.value;
            chargerClassement();
        })();
    </script>
</body>

</html>