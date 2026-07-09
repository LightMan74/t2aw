<?php 
include 'api/db.php'; ?>
<header class="top-header">
    <h1>Gestion des Tournois de Badminton</h1>
    <h3>Tournoi Actuel -> <?php echo ($tournoi_name_menu[0]["nom"] != "") ? $tournoi_name_menu[0]["nom"]: 'AUCUN'; ?></h3>
    <div class="user-info">
        <span>👤 <?php echo htmlspecialchars($currentUser); ?></span>
        <button id="btn-logout" class="btn btn-logout">Déconnexion</button>
    </div>
</header>
<?php 
if (str_contains($_SERVER['REQUEST_URI'],"dashboard.php")){
?>
<a href="dashboard.php">Accueil</a>
<?php
}else{    
?>
<a href="dashboard.php<?php echo htmlspecialchars((isset($_GET["id_tournoi"])) ? '?id_tournoi='.$_GET["id_tournoi"] : '');?>">Accueil</a>
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
<a href="matchs.php<?php echo htmlspecialchars((isset($_GET["id_tournoi"])) ? '?id_tournoi='.$_GET["id_tournoi"] : '');?>">Matchs</a>
<a href="classement.php<?php echo htmlspecialchars((isset($_GET["id_tournoi"])) ? '?id_tournoi='.$_GET["id_tournoi"] : '');?>">Classement</a>
<?php
}
?>