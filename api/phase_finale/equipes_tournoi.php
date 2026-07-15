<?php
// api/phase_finale/equipes_tournoi.php

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$idTournoi = (int)($_GET['id_tournoi'] ?? 0);

if ($idTournoi <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'id_tournoi manquant']);
    exit;
}

try {
    // Adapter le nom de colonne id_tournoi selon votre structure réelle de table equipe
    $stmt = $pdo->prepare("
        SELECT id, nom
        FROM equipe
        WHERE id_tournoi = :t
        ORDER BY nom ASC
    ");
    $stmt->execute([':t' => $idTournoi]);
    $equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'equipes' => $equipes]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}