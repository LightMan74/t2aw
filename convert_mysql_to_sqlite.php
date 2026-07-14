<?php
// convert_mysql_to_sqlite.php
// Usage: php convert_mysql_to_sqlite.php t2aw.sql database/t2aw.sqlite

$sqlFile = $argv[1] ?? 'database/t2aw.sql';
$sqliteFile = $argv[2] ?? 'database/t2aw.sqlite';

if (file_exists($sqliteFile)) {
    unlink($sqliteFile);
}

$pdo = new PDO('sqlite:' . $sqliteFile);
$pdo->exec('PRAGMA foreign_keys = OFF;');

$sql = file_get_contents($sqlFile);

// ==========================================
// Nettoyage global du dump MySQL
// ==========================================

// Supprime les commentaires de ligne -- ...
$sql = preg_replace('/^--.*$/m', '', $sql);
// Supprime les commentaires /*! ... */ et /* ... */
$sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
// Supprime les instructions MySQL spécifiques
$sql = preg_replace('/^(SET|START TRANSACTION|COMMIT|LOCK TABLES|UNLOCK TABLES).*?;/mi', '', $sql);

// Adaptation des types et options MySQL -> SQLite
$sql = preg_replace('/\)\s*ENGINE\s*=\s*\w+/i', ')', $sql);
$sql = preg_replace('/DEFAULT\s+CHARSET\s*=\s*\w+/i', '', $sql);
$sql = preg_replace('/COLLATE\s*=?\s*\w+/i', '', $sql);
$sql = preg_replace('/CHARACTER\s+SET\s*=?\s*\w+/i', '', $sql);
$sql = preg_replace('/AUTO_INCREMENT\s*=\s*\d+/i', '', $sql);

$sql = preg_replace('/int\(\d+\)\s+NOT NULL AUTO_INCREMENT/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
$sql = preg_replace('/int\s+NOT NULL AUTO_INCREMENT/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
$sql = preg_replace('/int\(\d+\)/i', 'INTEGER', $sql);
$sql = preg_replace('/\bint\b/i', 'INTEGER', $sql);
$sql = preg_replace('/varchar\(\d+\)/i', 'TEXT', $sql);
$sql = preg_replace('/text\(\d+\)/i', 'TEXT', $sql);
$sql = preg_replace('/timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP/i', 'TEXT DEFAULT CURRENT_TIMESTAMP', $sql);
$sql = preg_replace('/timestamp NOT NULL ON UPDATE CURRENT_TIMESTAMP/i', 'TEXT', $sql);
$sql = preg_replace('/timestamp/i', 'TEXT', $sql);

// Ancienne version (à supprimer) :
// $sql = preg_replace('/ALTER TABLE\s+`?\w+`?\s+ADD PRIMARY KEY.*?;/is', '', $sql);
// $sql = preg_replace('/ALTER TABLE\s+`?\w+`?\s+MODIFY.*?;/is', '', $sql);
// $sql = preg_replace('/ALTER TABLE\s+`?\w+`?\s+ADD (UNIQUE )?KEY.*?;/is', '', $sql);
// $sql = preg_replace('/ALTER TABLE\s+`?\w+`?\s+ADD CONSTRAINT.*?;/is', '', $sql);

// Nouvelle version (une seule ligne, plus robuste) :
$sql = preg_replace('/ALTER TABLE[^;]*;/is', '', $sql);

// Renommage table avec point (ompn.old -> ompn_old)
$sql = str_replace('`ompn.old`', '`ompn_old`', $sql);
$sql = str_replace('ompn.old', 'ompn_old', $sql);

// Suppression des backticks
$sql = str_replace('`', '', $sql);

// ==========================================
// Découpage intelligent des requêtes
// (respecte les guillemets simples/doubles et parenthèses)
// ==========================================
function splitSqlStatements($sql) {
    $statements = [];
    $current = '';
    $len = strlen($sql);
    $inString = false;
    $stringChar = '';

    for ($i = 0; $i < $len; $i++) {
        $char = $sql[$i];
        $current .= $char;

        if ($inString) {
            // Gestion des échappements \' ou ''
            if ($char === $stringChar) {
                // Vérifie si c'est un échappement '' (doublé)
                if ($stringChar === "'" && ($sql[$i + 1] ?? '') === "'") {
                    $current .= $sql[$i + 1];
                    $i++;
                } else {
                    $inString = false;
                }
            }
        } else {
            if ($char === "'" || $char === '"') {
                $inString = true;
                $stringChar = $char;
            } elseif ($char === ';') {
                $statements[] = trim($current);
                $current = '';
            }
        }
    }
    if (trim($current) !== '') {
        $statements[] = trim($current);
    }
    return $statements;
}

$queries = splitSqlStatements($sql);
$queries = array_filter($queries, function ($q) {
    return trim($q) !== '';
});

// ==========================================
// Exécution
// ==========================================
$errors = 0;
$success = 0;
// DEBUG : afficher le premier CREATE TABLE nettoyé
if (preg_match('/CREATE TABLE categorie.*?;/s', $sql, $m)) {
    echo "=== DEBUG CREATE TABLE categorie ===\n";
    echo $m[0] . "\n";
    echo "=====================================\n\n";
}
$pdo->exec('BEGIN TRANSACTION;');
foreach ($queries as $query) {
    $query = trim($query);
    if ($query === '') continue;

    try {
        $pdo->exec($query);
        $success++;
    } catch (PDOException $e) {
        $errors++;
        echo "❌ Erreur : " . substr(str_replace(["\n", "\r"], ' ', $query), 0, 100) . "...<br>";
        echo "   → " . $e->getMessage() . "\n\n";
    }
}
$pdo->exec('COMMIT;');

echo "\n=========================================\n";
echo "Terminé : $success requêtes OK, $errors erreurs\n";
echo "Fichier créé : $sqliteFile\n";