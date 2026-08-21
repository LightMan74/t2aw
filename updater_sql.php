<?php

// Configuration
$dbPath = __DIR__ . '/database/t2aw.sqlite';

// ============================================
// Définition des tables à créer
// ============================================
$tablesConfig = [
    'preference' => "
        CREATE TABLE IF NOT EXISTS preference (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user varchar(50) NOT NULL,
            largeur varchar(50) NULL
        )
    ",
    'match_ordre' => "
        CREATE TABLE IF NOT EXISTS match_ordre (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            id_tournoi INTEGER NOT NULL,
            ordre text NULL
        )
    ",
];

// 'ma_table' => [
//     'ma_colonne' => ['INTEGER', 0],
//     'autre_colonne' => ['TEXT', "'valeur'"], /!\/!\/!\/!\/!\/!\/!\/!\/!\/!\/!\/!\/!\
// ],
//  Attention pour les TEXT avec valeur par défaut : il faut mettre les guillemets simples dans la string PHP, ex: "'default'".

// ============================================
// Définition des colonnes à ajouter (table => [colonne => [type, defaut]])
// ============================================
$columnsConfig = [
    'parametre' => [
        'afficherheure'   => ['INTEGER', 1],
    ],
    'preference' => [
        'user'   => ['VARCHAR(50)', "'local'"],
        'largeur'   => ['VARCHAR(50)', 'NULL'],
    ],
];

try {
    // Connexion à la base de données SQLite
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- 1) Création des tables ---
    echo "=== Création des tables ===\n";
    foreach ($tablesConfig as $tableName => $createTableSQL) {

        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$tableName'");
        $tableExists = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($tableExists) {
            echo "La table '$tableName' existe déjà.\n";
        } else {
            $pdo->exec($createTableSQL);
            echo "✓ Table '$tableName' créée avec succès.\n";
        }
    }

    // --- 2) Ajout des colonnes ---
    echo "\n=== Ajout des colonnes ===\n";
    foreach ($columnsConfig as $tableName => $columns) {

        // Vérifier que la table existe avant d'ajouter des colonnes
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$tableName'");
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "⚠ La table '$tableName' n'existe pas, colonnes ignorées.\n";
            continue;
        }

        // Récupérer les colonnes existantes de la table
        $stmt = $pdo->query("PRAGMA table_info($tableName)");
        $existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');

        foreach ($columns as $columnName => $def) {
            [$type, $default] = $def;

            if (in_array($columnName, $existingColumns)) {
                echo "La colonne '$columnName' existe déjà dans '$tableName'.\n";
                continue;
            }

            $sql = "ALTER TABLE $tableName ADD COLUMN $columnName $type DEFAULT $default";
            $pdo->exec($sql);
            echo "✓ Colonne '$columnName' ajoutée dans '$tableName' ($type, défaut: $default).\n";
        }
    }

} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nMise a jour sql terminé.\n";