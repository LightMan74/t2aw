<?php
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if(!str_contains($_SERVER['REQUEST_URI'],"classement.php")) {
        header('Location: login.php');
        exit;
    }
}
?>