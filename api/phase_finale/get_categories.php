<?php
// api/phase_finale/get_categories.php

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$idTournoi = (int)($_GET['id_tournoi'] ?? 0);

if (!$idTournoi) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'id_tournoi requis']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id_categorie, nom
    FROM categorie
    WHERE id_tournoi = :id_tournoi
    ORDER BY nom ASC
");

$stmt->execute([':id_tournoi' => $idTournoi]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'categories' => $categories
]);
