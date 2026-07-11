<!DOCTYPE html>
<html lang="fr">
<?php 
// var_dump($_SESSION);
if (isset($_SESSION['logged_in']) || $_SESSION['logged_in'] == true) {
    header('Location: dashboard.php');
    exit;
}
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Gestion Tournois Badminton</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/dark-mode.css">
</head>

<body>

    <div class="login-container">
        <h1>🏸 Connexion</h1>

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
    <script src="js/theme.js"></script>
    <button id="btn-theme-toggle" class="theme-toggle" style="position:absolute; top:15px; right:15px;">🌙 Sombre</button>

</body>

</html>