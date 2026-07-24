<title>Tournoi All Auto Web</title>
<link rel="icon" type="image/png" sizes="64x64" href="icon_t2aw.png">

<?php 
include __DIR__ . "/api//check_connected.php";
include 'api/db.php'; 
// On récupère les infos de l'utilisateur connecté
$currentUid = $_SESSION['uid'];
$currentUser = $_SESSION['user'];
?>
<script>
// Applique le thème AVANT le rendu du header pour éviter le flash
(function() {
    const saved = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
})();
</script>
<header class="top-header">
    <!-- <h1>Gestion des Tournois de Badminton</h1> -->
    <!-- <img src="logo.png" class="login-logo" alt="Logo"> -->
    <div class="login-logo" role="img" aria-label=""></div>
    <h3>Tournoi Actuel : <?php echo (isset($tournoi_name_menu[0]["nom"]) != "") ? $tournoi_name_menu[0]["nom"]: 'AUCUN'; ?></h3>
    <div class="user-info">
        <?php
            if (DB_DRIVER === 'sqlite') {
                echo 'Base de donnée local';
            }else{            
                echo 'Base de donnée distant';   
            }
        ?>
        <button id="btn-theme-toggle" class="theme-toggle">🌙 Sombre</button>
        <span>👤 <?php echo htmlspecialchars($currentUser); ?></span>
        <button id="btn-logout" class="btn btn-logout">Déconnexion</button>
    </div>
</header>
<?php 
    if ((isset($tournoi_name_menu[0]["uid"]) != $currentUid && isset($_GET["id_tournoi"]))){
        echo "Le tournoi demandé n'existe pas ou n'est pas liée a l'identifiant actuelle !<br><br>";
?>
<a href="dashboard.php">Accueil</a>
<?php
        exit;
    }
    if (str_contains($_SERVER['REQUEST_URI'],"dashboard.php") || str_contains($_SERVER['REQUEST_URI'],"edit_tournoi.php")){
?>
<a href="dashboard.php">Accueil</a>
<?php
    }else{    
?>
<a href="edit_tournoi.php<?php echo htmlspecialchars((isset($_GET["id_tournoi"])) ? '?id_tournoi='.$_GET["id_tournoi"] : '');?>">Accueil</a>
<?php
}
?>
<?php
    if (isset($_GET["id_tournoi"])){
?>
<?php 
        if (str_contains($_SERVER['REQUEST_URI'],"edit_tournoi.php")||str_contains($_SERVER['REQUEST_URI'],"generation.php")){
?>
<a href="generation.php<?php echo htmlspecialchars((isset($_GET["id_tournoi"])) ? '?id_tournoi='.$_GET["id_tournoi"] : '');?>">Génération</a>
<?php
        }   
?>
<a href="poules.php?<?php echo htmlspecialchars((isset($_GET["id_tournoi"])) ? 'id_tournoi='.$_GET["id_tournoi"] : '');?>">Poules</a>
<a href="classement.php?<?php echo htmlspecialchars((isset($_GET["id_tournoi"])) ? 'id_tournoi='.$_GET["id_tournoi"] : '');?>">Classement</a>
<a href="phase_final.php?<?php echo htmlspecialchars((isset($_GET["id_tournoi"])) ? 'id_tournoi='.$_GET["id_tournoi"] : '');?>">Phase Final</a>
<a href="classement_final.php?<?php echo htmlspecialchars((isset($_GET["id_tournoi"])) ? 'id_tournoi='.$_GET["id_tournoi"] : '');?>">Classement Final</a>
<!-- <a target="_blank" href="afficheur.php?option<?php echo htmlspecialchars((isset($_GET["id_tournoi"])) ? '&id_tournoi='.$_GET["id_tournoi"] : '');?>">Afficheur(options)</a> -->
<!-- <a target="_blank" href="afficheur.php?<?php echo htmlspecialchars((isset($_GET["id_tournoi"])) ? 'id_tournoi='.$_GET["id_tournoi"] : '');?>">Afficheur</a> -->
<a target="_blank" id="afficheurhref" href="afficheur.php?<?php echo htmlspecialchars((isset($_GET["id_tournoi"])) ? 'id_tournoi='.$_GET["id_tournoi"] : '');?>">Afficheur, Option -><input type="checkbox" name="checkboxafficheuroption" onchange="afficheurhrefchange();">?</a>
<?php
    }
?>
<script>
function afficheurhrefchange() {
    let affhref = document.getElementById('afficheurhref');
    console.table(affhref);
    if (affhref.href.includes("option&")) {
        affhref.href = affhref.href.replace("afficheur.php?option&", "afficheur.php?")
    } else {
        affhref.href = affhref.href.replace("afficheur.php?", "afficheur.php?option&")
    }
    console.table(affhref);
}
</script>
<script src="js/logout.js"></script>
<script src="js/theme.js"></script>