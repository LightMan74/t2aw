<?php
// header('Content-Type: application/json');
require_once 'db.php';

$response = ['success' => false];

$id_tournoi = isset($_GET['id_tournoi']) ? (int)$_GET['id_tournoi'] : 0;
$password = isset($_GET['password']) ? $_GET['password'] : '';

// try {
//     $stmt = $pdo->prepare('SELECT tournoi_password FROM parametre WHERE id_tournoi = :id_tournoi');
//     $stmt->execute(['id_tournoi' => $id_tournoi]);
//     $tournoi = $stmt->fetch(PDO::FETCH_ASSOC);
// if ($tournoi['tournoi_password'] === $password) {
if ($tournoi_name_menu[0]["tournoi_password"] != $password) {
// }else{
     echo '<link rel="stylesheet" href="css/liste_afficheur.css"><script>alert("Mot de passe incorrect");     
         let pass = prompt("Merci de saisir le mot de passe");
         if (pass == null || pass == "") {
            alert("Mot de passe incorrect, retour au menu");
            document.location.href="https://'.$_SERVER['HTTP_HOST'].'/afficheur.php";
         } else {
            document.location.href="https://'.$_SERVER['HTTP_HOST'].'/afficheur.php?id_tournoi='.$id_tournoi.'&password="+pass;
         }
      //   document.location.href="https://'.$_SERVER['HTTP_HOST'].'/afficheur.php?id_tournoi='.$id_tournoi.'";
        </script>';
       exit;
    }

// } catch (PDOException $e) {
//      echo '<link rel="stylesheet" href="css/liste_afficheur.css"><script>alert("ERREUR");
//         document.location.href="https://'.$_SERVER['HTTP_HOST'].'/afficheur.php";
//         </script>';
//        exit;
//     }