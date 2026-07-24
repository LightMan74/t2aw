<?php
/**
 * Connexion PDO - Base t2aw
 * Compatible MySQL (production) et SQLite (portage local)
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// init_sqlite.php - à lancer une fois pour créer la base locale
// include 'convert_mysql_to_sqlite.php';
// echo "Base SQLite créée avec succès !";
// exit;

// ==========================================
// CONFIGURATION - Choisir le mode ici
// ==========================================
if($_SERVER['HTTP_HOST']=='t2aw.lansard.ch'){
define('DB_DRIVER', 'mysql'); // 'mysql' ou 'sqlite'
}else{
define('DB_DRIVER', 'sqlite'); // 'mysql' ou 'sqlite'
}

try {
    if (DB_DRIVER === 'sqlite') {
        // --- Mode SQLite (local) ---
        $dbPath = __DIR__ . '/../database/t2aw.sqlite';


    // Vérif que le fichier existe et est accessible
    if (!file_exists($dbPath)) {
        throw new PDOException("Fichier base introuvable : $dbPath");
    }
    if (!is_writable($dbPath)) {
        throw new PDOException("Fichier base non accessible en écriture : $dbPath");
    }

        $pdo = new PDO(
            'sqlite:' . $dbPath,
            null,
            null,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        // Active les clés étrangères (désactivées par défaut en SQLite)
        $pdo->exec('PRAGMA foreign_keys = ON;');

    } else {        
        // --- Mode MySQL (production) ---
        $pdo = new PDO(
            'mysql:host=192.168.3.70;dbname=t2aw;charset=utf8mb4',
            'siteconnect',
            'Azertyuiop!1',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => true,
            ]
        );
    }

    // --- Requête commune (fonctionne sur les 2 moteurs) ---
    $stmt = $pdo->prepare("
        SELECT t.nom, t.user_uid, p.tournoi_password
        FROM tournoi t, parametre p
        WHERE t.id_tournoi = :id and p.id_tournoi = t.id_tournoi
    ");
    $stmt->execute(['id' => htmlspecialchars($_GET["id_tournoi"] ?? '')]);
    $tournoi_name_menu = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (isset($_SESSION['user']) && $_SESSION['user'] == "admin") {
        $tournoi_name_menu[0]["user_uid"] = "%";
    }
    $tournoi_password =  ($tournoi_name_menu[0]["tournoi_password"] ?  "&password=".$tournoi_name_menu[0]["tournoi_password"]:"");
} catch (PDOException $e) {
    error_log('Connexion BDD echouee: ' . $e->getMessage());
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Connexion base de donnees impossible']);
    exit;
}