<?php
// Test simple pour déboguer l'export

echo "TEST 1: PHP fonctionne\n";

$id_tournoi = $_GET['id_tournoi'] ?? 'VIDE';
echo "TEST 2: id_tournoi = " . $id_tournoi . "\n";

try {
    echo "TEST 3: Avant include db.php\n";
    include 'db.php';
    echo "TEST 4: DB.php inclus OK\n";

    $stmt = $pdo->prepare("SELECT nom FROM tournoi LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "TEST 5: Query OK\n";
    echo "TEST 6: Résultat: " . json_encode($result) . "\n";

} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
?>