<?php
/**
 * Connexion PDO MySQL - Base t2aw
 * À personnaliser avec vos identifiants
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
while (ob_get_level()) {
    ob_end_clean();
}
try {
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
} catch (PDOException $e) {
    error_log('Connexion BDD echouee: ' . $e->getMessage());
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Connexion base de donnees impossible']);
    exit;
}
?>