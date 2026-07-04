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
    $stmt = $pdo->query("
        SELECT t.id_tournoi, t.nom, p.heure_debut_poule, p.heure_debut_phasefinal
        FROM tournoi t
        LEFT JOIN parametre p ON t.id_tournoi = p.id_tournoi
        ORDER BY t.id DESC
    ");
    $tournois = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'tournois' => $tournois]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}


?>