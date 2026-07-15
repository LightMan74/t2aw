<?php
// api/phase_finale/modifier_equipe.php

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);

$equipeId  = (int)($input['equipe_id'] ?? 0);
$nomEquipe = trim($input['nom_equipe'] ?? '');

if ($equipeId <= 0 || $nomEquipe === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE equipes_phase_finale
        SET nom_equipe = :nom
        WHERE id = :id
    ");
    $stmt->execute([':nom' => $nomEquipe, ':id' => $equipeId]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}