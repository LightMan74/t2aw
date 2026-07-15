<?php
// api/phase_finale/detail.php

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$idPhaseFinale = (int)($_GET['id_phase_finale'] ?? 0);

if ($idPhaseFinale <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'id_phase_finale manquant']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM phases_finales WHERE id = :id");
    $stmt->execute([':id' => $idPhaseFinale]);
    $phase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$phase) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Phase finale introuvable']);
        exit;
    }

    // Equipes
    $stmt = $pdo->prepare("
        SELECT id, seed_position, nom_equipe, is_bye
        FROM equipes_phase_finale
        WHERE id_phase_finale = :id
        ORDER BY seed_position ASC
    ");
    $stmt->execute([':id' => $idPhaseFinale]);
    $equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Index équipes par id pour faire la jointure manuellement en PHP
    $equipesParId = [];
    foreach ($equipes as $e) {
        $equipesParId[$e['id']] = $e['nom_equipe'];
    }

    // Matchs (sans JOIN SQL, on résout les noms en PHP)
    $stmt = $pdo->prepare("
        SELECT *
        FROM matchs_phase_finale
        WHERE id_phase_finale = :id
        ORDER BY round ASC, sub_group ASC, match_num ASC
    ");
    $stmt->execute([':id' => $idPhaseFinale]);
    $matchs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($matchs as &$m) {
        $m['nom_equipe1'] = $m['equipe1_id'] ? ($equipesParId[$m['equipe1_id']] ?? null) : null;
        $m['nom_equipe2'] = $m['equipe2_id'] ? ($equipesParId[$m['equipe2_id']] ?? null) : null;
    }
    unset($m);

    echo json_encode([
        'success' => true,
        'phase' => $phase,
        'equipes' => $equipes,
        'matchs' => $matchs,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}