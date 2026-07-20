<?php
/**
 * API get_tournois.php
 * Retourne la liste de tous les tournois
 * GET
 */
require_once 'db.php';
if (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: application/json');
try {
    $stmt = $pdo->prepare("
        SELECT t.id_tournoi, t.nom
        FROM tournoi t
        WHERE 1
        ORDER BY t.id DESC
    ");
    $stmt->execute();
    $tournois = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'tournois' => $tournois]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}


?>