<?php
require_once 'db.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT t.id_tournoi, t.nom, p.nbre_terrain_poule, p.nbre_terrain_phasefinal,
               p.temps_de_match, p.heure_debut_poule, p.heure_debut_phasefinal
        FROM tournoi t
        JOIN parametre p ON t.id_tournoi = p.id_tournoi
        ORDER BY t.id DESC
    ");
    $tournois = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'tournois' => $tournois]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}