<?php
/**
 * Export d'un tournoi complet en JSON
 * Parcourt toutes les tables de la base possédant une colonne id_tournoi
 * et exporte toutes les lignes correspondantes
 */

include __DIR__ . "/check_connected.php";
include "db.php";

$id_tournoi = isset($_GET['id_tournoi']) ? intval($_GET['id_tournoi']) : 0;

if ($id_tournoi <= 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de tournoi invalide']);
    exit;
}

try {
    // Récupération du nom de la base de données courante
    $dbNameStmt = $pdo->query("SELECT DATABASE()");
    $dbName = $dbNameStmt->fetchColumn();

    // Recherche de toutes les tables possédant une colonne id_tournoi
    $sqlTables = "
        SELECT TABLE_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = :dbName 
        AND COLUMN_NAME = 'id_tournoi'
    ";
    $stmtTables = $pdo->prepare($sqlTables);
    $stmtTables->execute(['dbName' => $dbName]);
    $tables = $stmtTables->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        throw new Exception("Aucune table avec id_tournoi trouvée");
    }

    $export = [
        'export_info' => [
            'id_tournoi' => $id_tournoi,
            'nom' => $tournoi_nom,
            'date_export' => date('Y-m-d H:i:s')
        ],
        'tables' => []
    ];

    foreach ($tables as $table) {
        $sql = "SELECT * FROM `$table` WHERE id_tournoi = :id_tournoi";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id_tournoi' => $id_tournoi]);
        $lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $export['tables'][$table] = $lignes;
    }

    $json = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $nomFichier = "t2aw_export_{$id_tournoi}_{$tournoi_nom}_" . date('Y-m-d--H-i') . ".json";

    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $nomFichier . '"');
    header('Content-Length: ' . strlen($json));

    echo $json;

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'export : ' . $e->getMessage()]);
}