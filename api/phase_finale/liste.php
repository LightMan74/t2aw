<?php
// api/phase_finale/liste.php

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$idTournoi = (int)($_GET['id_tournoi'] ?? 0);

if ($idTournoi <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'id_tournoi manquant']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, nom, type_bracket, nb_equipes, nb_equipes_arrondi, nb_rounds, statut, date_creation
        FROM phases_finales
        WHERE id_tournoi = :t
        ORDER BY date_creation DESC
    ");
    $stmt->execute([':t' => $idTournoi]);
    $phases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'phases' => $phases]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}