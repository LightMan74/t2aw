<?php
/**
 * Export d'un tournoi complet en JSON (compatible MySQL et SQLite)
 * Parcourt toutes les tables possédant une colonne id_tournoi
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

/**
 * Retourne la liste des tables possédant une colonne id_tournoi
 * selon le driver (mysql / sqlite)
 */
function getTablesAvecIdTournoi(PDO $pdo): array
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $tables = [];

    if ($driver === 'sqlite') {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'");
        $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($allTables as $table) {
            // PRAGMA ne supporte pas les paramètres liés : on échappe le nom
            $safeTable = str_replace('"', '""', $table);
            $cols = $pdo->query('PRAGMA table_info("' . $safeTable . '")')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $col) {
                if (strtolower($col['name']) === 'id_tournoi') {
                    $tables[] = $table;
                    break;
                }
            }
        }
    } else {
        // MySQL / MariaDB
        $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
        $stmt = $pdo->prepare("
            SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = :dbName
            AND COLUMN_NAME = 'id_tournoi'
        ");
        $stmt->execute(['dbName' => $dbName]);
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    return $tables;
}

/**
 * Échappe un nom de table selon le driver
 */
function quoteIdentifier(PDO $pdo, string $name): string
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        return '"' . str_replace('"', '""', $name) . '"';
    }
    return '`' . str_replace('`', '``', $name) . '`';
}

try {
    $tables = getTablesAvecIdTournoi($pdo);

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
        $tableQuoted = quoteIdentifier($pdo, $table);
        $stmt = $pdo->prepare("SELECT * FROM $tableQuoted WHERE id_tournoi = :id_tournoi");
        $stmt->execute(['id_tournoi' => $id_tournoi]);
        $export['tables'][$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $json = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    if ($json === false) {
        throw new Exception("Erreur d'encodage JSON : " . json_last_error_msg());
    }

    // Nom de fichier nettoyé (évite les caractères interdits dans l'en-tête)
    $nomSafe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $tournoi_nom);
    $nomFichier = "t2aw_export_{$id_tournoi}_{$nomSafe}_" . date('Y-m-d--H-i') . ".json";

    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $nomFichier . '"');
    header('Content-Length: ' . strlen($json));

    echo $json;

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'export : ' . $e->getMessage()]);
}