<?php
/**
 * Script de suppression d'un tournoi et de toutes ses données associées
 * Supprime les lignes avec id_tournoi spécifique dans toutes les tables concernées
 */
 
header('Content-Type: application/json');

include "db.php";

// Récupération de l'id_tournoi (via POST en JSON)
$data = json_decode(file_get_contents('php://input'), true);
$id_tournoi = isset($data['id_tournoi']) ? intval($data['id_tournoi']) : 0;

if ($id_tournoi <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de tournoi invalide']);
    exit;
}

/**
 * MÉTHODE 1 : Liste manuelle des tables (recommandée - plus sûr et contrôlé)
 * ==========================================================================
 * Ordre important si vous avez des clés étrangères avec contraintes
 * (supprimer d'abord les tables "enfants" avant les tables "parents")
 */

$tables_avec_id_tournoi = [
    'match_poule',
    'poule',
    'equipe',
    'categorie',
    'parametre',
    'tournoi'
];

try {
    $pdo->beginTransaction();

    $resultats_suppression = [];
    $total_lignes_supprimees = 0;

    foreach ($tables_avec_id_tournoi as $table) {
            $sql = "DELETE FROM `$table` WHERE id_tournoi = :id_tournoi";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id_tournoi' => $id_tournoi]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "Tournoi #$id_tournoi supprimé avec succès",
        'total_lignes_supprimees' => $total_lignes_supprimees,
        'details' => $resultats_suppression
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
    ]);
}