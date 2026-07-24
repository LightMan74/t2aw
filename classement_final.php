<?php
// classement_final.php
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <!-- <title>Classement Final</title> -->
    <link rel="stylesheet" href="css/var.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dark-mode.css">
</head>

<body>
    <div class="container">
        <nav>
            <?php include 'menu.php'; ?>
        </nav>

        <h1>Classement Final</h1>

        <input type="number" id="id_tournoi" value="<?php echo $_GET["id_tournoi"]; ?>" style="width:100px; margin-bottom:15px;" hidden>

        <div id="message"></div>
        <div id="zone-classement"></div>
    </div>

    <script src="js/colors.js"></script>
    <script src="js/classement_final.js"></script>
    <script>
        (function () {
            var input = document.getElementById('id_tournoi');
            if (input) document.body.dataset.idTournoi = input.value;
            chargerClassementFinal();
        })();
    </script>
</body>

</html>