<?php

// // Configuration
// $dbPath = __DIR__ . '/database/t2aw.sqlite';
// $tableName = 'parametre'; // À modifier
// $columnName = 'qrcode';   // À modifier
// $defaultValue = 1;

// try {
//     // Connexion à la base de données SQLite
//     $pdo = new PDO('sqlite:' . $dbPath);
//     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//     // Vérifier si la colonne existe déjà
//     $stmt = $pdo->query("PRAGMA table_info($tableName)");
//     $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
//     $columnExists = false;
//     foreach ($columns as $column) {
//         if ($column['name'] === $columnName) {
//             $columnExists = true;
//             break;
//         }
//     }

//     if ($columnExists) {
//         echo "La colonne '$columnName' existe déjà dans la table '$tableName'.\n";
//     } else {
//         // Ajouter la colonne avec valeur par défaut
//         $sql = "ALTER TABLE $tableName ADD COLUMN $columnName INTEGER DEFAULT $defaultValue";
//         $pdo->exec($sql);
        
//         echo "✓ Colonne '$columnName' ajoutée avec succès (INTEGER, défaut: $defaultValue).\n";
//     }

// } catch (PDOException $e) {
//     echo "❌ Erreur : " . $e->getMessage() . "\n";
//     exit(1);
// }

// echo "Mise a jour sql terminé.\n";