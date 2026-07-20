<!DOCTYPE html>
<html lang="fr">
<?php 
// var_dump($_SESSION);
// var_dump($_GET);
    // exit;
if (isset($_SESSION['logged_in']) || $_SESSION['logged_in'] == true) {
    header('Location: dashboard.php');
    exit;
}else{  
    if (!isset($_GET['login'])) {  
        header('Location: afficheur.php');
    exit;
    }
}
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <title>Connexion - Gestion Tournois Badminton</title> -->
    <title>Tournoi All Auto Web</title>
    <link rel="icon" type="image/png" sizes="64x64" href="logo.png">
    <!-- <link rel="stylesheet" href="css/var.css"> -->
    <link rel="stylesheet" href="css/login.css">
    <!-- <link rel="stylesheet" href="css/dark-mode.css"> -->
    <style>
    .login-logo {
        display: block;
        width: 80%;
        /* Prend 80% de la largeur du conteneur */
        max-width: 300px;
        /* Ne dépassera pas 300px de large */
        height: auto;
        margin: 0 auto 20px auto;
    }
    </style>
</head>
 
<body>

    <div class="login-container"><img src="logo.png" class="login-logo" alt="Logo">
        <h1> Connexion</h1>

        <form id="loginForm">
            <div class="form-group">
                <label for="user">Utilisateur</label>
                <input type="text" id="user" name="user" required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>

            <button type="submit" id="submitBtn">Se connecter</button>

            <p id="message" class="message"></p>
        </form>
    </div>

    <script src="js/auth.js"></script>

</body>

</html>