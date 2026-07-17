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

    // Equipes : jointure avec la table "equipe" pour récupérer le nom à jour
    // COALESCE : si id_equipe_originale existe, on prend le nom actuel de la table equipe
    // sinon on garde le nom_equipe stocké (cas des BYE ou équipes temporaires)
$stmt = $pdo->prepare("
    SELECT
        epf.id,
        epf.seed_position,
        epf.is_bye,
        epf.id_equipe,
        epf.id_categorie,
        epf.id_poule,
        COALESCE(eq.nom, epf.nom_equipe) AS nom_equipe2,
        eqn.nom AS nom_equipe
    FROM equipes_phase_finale epf
    LEFT JOIN equipe eq ON 
        eq.id_tournoi = epf.id_tournoi
        AND eq.id_categorie = epf.id_categorie
        AND eq.id_poule = epf.id_poule
        AND eq.id_equipe = epf.id_equipe
    LEFT JOIN equipe eqn ON 
        eqn.id_tournoi = epf.id_tournoi
        AND eqn.id_categorie = epf.id_categorie
        AND eqn.id_poule = epf.id_poule
        AND eqn.id_equipe = epf.id_equipe
    WHERE epf.id_phase_finale = :id
    ORDER BY epf.seed_position ASC
");

    $stmt->execute([':id' => $idPhaseFinale]);
    $equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Index équipes par id pour résoudre les noms des matchs
    $equipesParId = [];
    foreach ($equipes as $e) {
        $equipesParId[$e['id']] = $e['nom_equipe'];
    }

    // Matchs
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