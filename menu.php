<?php 
if (str_contains($_SERVER['REQUEST_URI'],"index.php")){
?>
<a href="index.php">Accueil</a>
<?php
}else{    
?>
<a href="index.php<?php echo htmlspecialchars((isset($_GET["id_tournoi"])) ? '?id_tournoi='.$_GET["id_tournoi"] : '');?>">Accueil</a>
<?php
}
?>
<?php
if (isset($_GET["id_tournoi"])){
?>
<a href="generation.php<?php echo htmlspecialchars((isset($_GET["id_tournoi"])) ? '?id_tournoi='.$_GET["id_tournoi"] : '');?>">Génération</a>
<a href="matchs.php<?php echo htmlspecialchars((isset($_GET["id_tournoi"])) ? '?id_tournoi='.$_GET["id_tournoi"] : '');?>">Matchs</a>
<a href="classement.php<?php echo htmlspecialchars((isset($_GET["id_tournoi"])) ? '?id_tournoi='.$_GET["id_tournoi"] : '');?>">Classement</a>
<?php
}
?>