<?php
// include __DIR__ . "/check_connected.php";
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <title>Gestion Tournois Badminton</title> -->
    <link rel="stylesheet" href="css/var.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dark-mode.css">
</head>

<body>
    <div class="container">

        <nav>
            <?php include 'menu.php'; ?>
        </nav>

        <?php if (isset($tournoi_name_menu[0]["nom"]) == "") { ?>
        <!-- Liste des tournois -->
        <section class="section">
            <div class="section-header">
                <h2>Tournois existants</h2>
                <a href="edit_tournoi.php" class="btn btn-primary">+ Ajouter un tournoi</a>
            </div>
            <div id="liste-tournois">
                <p class="loading">Chargement...</p>
            </div>
        </section>

        <!-- <script src="js/test.js"></script> -->
        <script src="js/main.js"></script>
        <script src="js/supprimer_tournoi.js"></script>
        <?php } else { ?>
        <h1>Tournoi actuellement ouvert<br><br>&nbsp;&nbsp;&nbsp;<?php echo $tournoi_name_menu[0]["nom"]; ?></h1>
        <button onclick="telechargerExport()" class="btn-dark" title="Exporter le tournoi">📥 Export</button>
        <?php } ?>

    </div>

</body>


</html>
<?php
$id_tournoi = isset($_GET['id_tournoi']) ? (int)$_GET['id_tournoi'] : 0;
?>
<script>
const ID_TOURNOI = <?php echo json_encode($id_tournoi); ?>;

function telechargerExport() {
    const url = `api/export_tournoi.php?id_tournoi=${encodeURIComponent(ID_TOURNOI)}`;
    const link = document.createElement('a');
    link.href = url;
    link.click();
}
</script>