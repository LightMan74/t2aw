<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

$output = null;
$retval = null;

// $script = __DIR__ . '/database/mysql2sqlite';
$script = 'mysql2sqlite';
$sqliteFile = __DIR__ . '/database/t2aw.sqlite';
$password = 'Azertyuiop!1';

$cmd = sprintf(
    '%s --sqlite-file %s --mysql-database %s --mysql-user %s --mysql-password %s --mysql-host %s --mysql-port %d 2>&1',
    escapeshellarg($script),
    escapeshellarg($sqliteFile),
    escapeshellarg('t2aw'),
    escapeshellarg('siteconnect'),
    escapeshellarg($password),
    escapeshellarg('192.168.3.70'),
    3306
);

exec($cmd, $output, $retval);

echo "Returned with status $retval and output:\n";
print_r($output);
var_dump($output);
exit;

// convert_mysql_to_sqlite.php
// Usage: php convert_mysql_to_sqlite.php t2aw.sql database/t2aw.sqlite
if(php_sapi_name() == 'cli') {
    $newlinedynamique = PHP_EOL;
} else {
    $newlinedynamique = '<br>';
}
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

// Supprime les définitions PRIMARY KEY (xxx) redondantes en fin de table
// si une colonne a déjà été transformée en INTEGER PRIMARY KEY AUTOINCREMENT
$sql = preg_replace('/,\s*PRIMARY KEY\s*\([^)]*\)/i', '', $sql);

$sql = preg_replace('/int(?:\(\d+\))?\s+NOT\s+NULL\s+AUTO_INCREMENT/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
$sql = preg_replace('/int(?:\(\d+\))?\s+AUTO_INCREMENT\s+NOT\s+NULL/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
$sql = preg_replace('/int\(\d+\)/i', 'INTEGER', $sql);
$sql = preg_replace('/\bint\b/i', 'INTEGER', $sql);
$sql = preg_replace('/varchar\(\d+\)/i', 'TEXT', $sql);
$sql = preg_replace('/text\(\d+\)/i', 'TEXT', $sql);
// Supprime les "ON UPDATE CURRENT_TIMESTAMP" (non supporté par SQLite) - à faire AVANT toute autre transformation
$sql = preg_replace('/\s+ON UPDATE CURRENT_TIMESTAMP/i', '', $sql);

// Gère datetime comme timestamp
$sql = preg_replace('/datetime/i', 'TEXT', $sql);

$sql = preg_replace('/timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP/i', 'TEXT DEFAULT CURRENT_TIMESTAMP', $sql);
$sql = preg_replace('/timestamp NOT NULL ON UPDATE CURRENT_TIMESTAMP/i', 'TEXT', $sql); // devient inutile mais sans danger
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
    echo "=== DEBUG CREATE TABLE categorie ===". $newlinedynamique;
    echo $m[0] . $newlinedynamique;
    echo "=====================================". $newlinedynamique . $newlinedynamique;
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
        // echo "❌ Erreur : " . substr(str_replace(["\n", "\r"], ' ', $query), 0, 100) . "...<br>";
        echo "❌ Erreur : " . $query;
        echo "   → " . $e->getMessage() . $newlinedynamique . $newlinedynamique;
    }
}
$pdo->exec('COMMIT;');

echo $newlinedynamique ."=========================================". $newlinedynamique;
echo "Terminé : $success requêtes OK, $errors erreurs". $newlinedynamique;
echo "Fichier créé : $sqliteFile". $newlinedynamique;