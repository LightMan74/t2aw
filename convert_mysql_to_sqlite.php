<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

$output = null;
$retval = null;

$script = 'mysql2sqlite';
$sqliteFile = __DIR__ . '/database/t2aw.sqlite';
$password = 'Azertyuiop!1';
$mysqlDatabase = 't2aw';
$mysqlUser = 'siteconnect';
$mysqlHost = '192.168.3.70';
$mysqlPort = 3306;

if (file_exists($sqliteFile)) {
    unlink($sqliteFile);
}

// --- Export complet (structure + données), sauf user et ompn.old ---
$cmd = sprintf(
    '%s --sqlite-file %s --mysql-database %s --mysql-user %s --mysql-password %s --mysql-host %s --mysql-port %d --exclude-mysql-tables %s %s 2>&1',
    escapeshellarg($script),
    escapeshellarg($sqliteFile),
    escapeshellarg($mysqlDatabase),
    escapeshellarg($mysqlUser),
    escapeshellarg($password),
    escapeshellarg($mysqlHost),
    $mysqlPort,
    escapeshellarg('user'),
    escapeshellarg('ompn.old')
);

exec($cmd, $output, $retval);

echo "Export mysql2sqlite - Statut: $retval\n";
print_r($output);

if ($retval !== 0) {
    echo "Erreur lors de l'export\n";
    exit(1);
}

// --- Post-traitement SQL ---
try {
    $pdo = new PDO('sqlite:' . $sqliteFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier que user_local existe bien
    $exists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='user_local'")->fetch();
    if (!$exists) {
        echo "Erreur : la table user_local n'existe pas dans le fichier exporté.\n";
        exit(1);
    }

    // Renommer user_local -> user
    $pdo->exec('ALTER TABLE user_local RENAME TO user');
    echo "Table user_local renommée en user.\n";

    // Récupérer toutes les tables (hors sqlite internes et hors 'user')
    $tables = $pdo->query("
        SELECT name FROM sqlite_master
        WHERE type='table'
        AND name NOT LIKE 'sqlite_%'
        AND name != 'user'
    ")->fetchAll(PDO::FETCH_COLUMN);

    $pdo->exec('PRAGMA foreign_keys = OFF'); // évite les erreurs de contraintes pendant les DELETE

    foreach ($tables as $table) {
        $pdo->exec("DELETE FROM \"$table\"");
        echo "Table vidée : $table\n";
    }

    $pdo->exec('PRAGMA foreign_keys = ON');

    // Optionnel : VACUUM pour réduire la taille du fichier après suppression des données
    $pdo->exec('VACUUM');

    echo "\nTraitement terminé avec succès.\n";

} catch (PDOException $e) {
    echo "Erreur lors du post-traitement : " . $e->getMessage() . "\n";
    exit(1);
}

exit;