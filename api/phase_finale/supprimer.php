<?php
// api/phase_finale/supprimer.php

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);
$idPhaseFinale = (int)($input['id_phase_finale'] ?? 0);

if ($idPhaseFinale <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'id_phase_finale manquant']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Vérifier que la phase existe
    $stmt = $pdo->prepare("SELECT id FROM phases_finales WHERE id = :id");
    $stmt->execute([':id' => $idPhaseFinale]);
    if (!$stmt->fetch()) {
        throw new Exception('Phase finale introuvable');
    }

    // Supprimer les matchs liés
    $stmt = $pdo->prepare("DELETE FROM matchs_phase_finale WHERE id_phase_finale = :id");
    $stmt->execute([':id' => $idPhaseFinale]);

    // Supprimer les équipes liées à la phase finale (table equipes_phase_finale)
    $stmt = $pdo->prepare("DELETE FROM equipes_phase_finale WHERE id_phase_finale = :id");
    $stmt->execute([':id' => $idPhaseFinale]);

    // Supprimer la phase finale elle-même
    $stmt = $pdo->prepare("DELETE FROM phases_finales WHERE id = :id");
    $stmt->execute([':id' => $idPhaseFinale]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}