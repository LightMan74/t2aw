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
        SELECT t.id_tournoi, t.nom, IF(CHAR_LENGTH(p.tournoi_password)>0,1,0) as tournoi_password
        FROM tournoi t, parametre p
        WHERE t.id_tournoi = p.id_tournoi and p.tournoi_cacher = 0
        ORDER BY t.id_tournoi DESC
    ");
    $stmt->execute();
    $tournois = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'tournois' => $tournois]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}


?>