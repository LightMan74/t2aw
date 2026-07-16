<?php
// api/phase_finale/check_categorie.php

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$idTournoi   = (int)($_GET['id_tournoi'] ?? 0);
$idCategorie = (int)($_GET['id_categorie'] ?? 0);

if (!$idTournoi || !$idCategorie) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, nom, statut
        FROM phases_finales
        WHERE id_tournoi = :id_tournoi AND id_categorie = :id_categorie
        LIMIT 1
    ");
    $stmt->execute([
        ':id_tournoi' => $idTournoi,
        ':id_categorie' => $idCategorie,
    ]);
    $phase = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'existe' => (bool)$phase,
        'phase' => $phase ?: null,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}