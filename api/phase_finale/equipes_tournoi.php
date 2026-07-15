<?php
// api/phase_finale/equipes_tournoi.php

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$idTournoi = (int)($_GET['id_tournoi'] ?? 0);

if (!$idTournoi) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'id_tournoi requis']);
    exit;
}

// Charger toutes les équipes du tournoi avec leurs catégories et poules
$stmt = $pdo->prepare("
    SELECT 
        e.id_equipe,
        e.nom,
        e.id_tournoi,
        e.id_categorie,
        e.id_poule
    FROM equipe e
    WHERE e.id_tournoi = :id_tournoi
    ORDER BY e.id_categorie, e.id_poule, e.nom
");

$stmt->execute([':id_tournoi' => $idTournoi]);
$equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'equipes' => $equipes
]);