<?php
/**
 * Connexion PDO - Base t2aw
 * Compatible MySQL (production) et SQLite (portage local)
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// init_sqlite.php - à lancer une fois pour créer la base locale
$dbPath = 'database/t2aw.sqlite';
$pdo = new PDO('sqlite:' . $dbPath);
$sql = file_get_contents('database/schema_sqlite.sql');
$pdo->exec($sql);
echo "Base SQLite créée avec succès !";
exit;

// ==========================================
// CONFIGURATION - Choisir le mode ici
// ==========================================
define('DB_DRIVER', 'sqlite'); // 'mysql' ou 'sqlite'

try {
    if (DB_DRIVER === 'sqlite') {
        // --- Mode SQLite (local) ---
        $dbPath = 'database/t2aw.sqlite';
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
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    // --- Requête commune (fonctionne sur les 2 moteurs) ---
    $stmt = $pdo->prepare("
        SELECT nom, uid
        FROM tournoi
        WHERE id_tournoi = :id
    ");
    $stmt->execute(['id' => htmlspecialchars($_GET["id_tournoi"] ?? '')]);
    $tournoi_name_menu = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (isset($_SESSION['user']) && $_SESSION['user'] == "admin") {
        $tournoi_name_menu[0]["uid"] = "%";
    }

} catch (PDOException $e) {
    error_log('Connexion BDD echouee: ' . $e->getMessage());
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Connexion base de donnees impossible']);
    exit;
}

/**
 * Connexion PDO MySQL - Base t2aw
 * À personnaliser avec vos identifiants
 */

// error_reporting(E_ALL);
// ini_set('display_errors', 0);
// ini_set('log_errors', 1);

// try {
//     $pdo = new PDO(
//         'mysql:host=192.168.3.70;dbname=t2aw;charset=utf8mb4',
//         'siteconnect',
//         'Azertyuiop!1',
//         [
//             PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
//             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//             PDO::ATTR_EMULATE_PREPARES => false,
//         ]
//     );
//     $stmt = $pdo->prepare("
//         SELECT nom, uid
//         FROM tournoi
//         WHERE id_tournoi = :id
//     ");
//     $stmt->execute(['id' => htmlspecialchars(($_GET["id_tournoi"]))]);
//     $tournoi_name_menu = $stmt->fetchAll(PDO::FETCH_ASSOC);
//     if ($_SESSION['user'] == "admin"){$tournoi_name_menu[0]["uid"] = "%";}

//     // echo htmlspecialchars(($_GET["id_tournoi"]));
//     // var_dump($tournoi_name_menu);
//     // echo "***".$tournoi_name_menu[0]["nom"];
// } catch (PDOException $e) {
//     error_log('Connexion BDD echouee: ' . $e->getMessage());
//     if (ob_get_level()) {
//     ob_end_clean();
// }
//     header('Content-Type: application/json; charset=utf-8');
//     echo json_encode(['success' => false, 'error' => 'Connexion base de donnees impossible']);
//     exit;
// }