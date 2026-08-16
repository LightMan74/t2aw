<?php
// api/phase_finale/assigner_equipe_seed.php

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);

$id = (int)($input['id'] ?? 0);
$idEquipe = (int)($input['id_equipe'] ?? 0);
$idCategorie = (int)($input['id_categorie'] ?? 0);
$idPoule = (int)($input['id_poule'] ?? 0);
$nomEquipe = trim($input['nom_equipe'] ?? '');

if ($id <= 0 || $idEquipe <= 0 || $idCategorie <= 0 || $idPoule <= 0 || $nomEquipe === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

try {
    // Le slot doit appartenir à une phase finale et à son tournoi/catégorie.
    $stmt = $pdo->prepare("\
        SELECT epf.id, epf.id_tournoi, epf.id_phase_finale, epf.id_categorie\
        FROM equipes_phase_finale epf\
        INNER JOIN phases_finales pf ON pf.id = epf.id_phase_finale\
        WHERE epf.id = :id\
          AND epf.id_tournoi = pf.id_tournoi\
          AND epf.id_categorie = pf.id_categorie\
        LIMIT 1\
    ");
    $stmt->execute([':id' => $id]);
    $slot = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$slot) {
        throw new Exception('Seed introuvable ou non rattaché à une phase finale valide');
    }

    // L'équipe choisie doit appartenir au même tournoi et à la catégorie de la phase.
    $stmt = $pdo->prepare("\
        SELECT id_equipe, nom, id_tournoi, id_categorie\
        FROM equipe\
        WHERE id_equipe = :id_equipe\
          AND id_tournoi = :id_tournoi\
          AND id_categorie = :id_categorie\
        LIMIT 1\
    ");
    $stmt->execute([
        ':id_equipe' => $idEquipe,
        ':id_tournoi' => $slot['id_tournoi'],
        ':id_categorie' => $idCategorie,
    ]);
    $equipe = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$equipe || (int)$slot['id_categorie'] !== $idCategorie) {
        throw new Exception('Équipe non disponible pour cette phase finale');
    }

    // Une équipe ne peut pas être assignée à deux seeds de la même phase finale.
    $stmt = $pdo->prepare("\
        SELECT id\
        FROM equipes_phase_finale\
        WHERE id_phase_finale = :id_phase_finale\
          AND id_equipe = :id_equipe\
          AND id <> :id\
        LIMIT 1\
    ");
    $stmt->execute([
        ':id_phase_finale' => $slot['id_phase_finale'],
        ':id_equipe' => $idEquipe,
        ':id' => $id,
    ]);

    if ($stmt->fetch()) {
        throw new Exception('Cette équipe est déjà assignée à un autre seed');
    }

    $stmt = $pdo->prepare("\
        UPDATE equipes_phase_finale\
        SET nom_equipe = :nom_equipe,\
            id_categorie = :id_categorie,\
            id_poule = :id_poule,\
            id_equipe = :id_equipe\
        WHERE id = :id\
          AND id_tournoi = :id_tournoi\
          AND id_phase_finale = :id_phase_finale\
    ");
    $stmt->execute([
        ':nom_equipe' => $nomEquipe,
        ':id_categorie' => $idCategorie,
        ':id_poule' => $idPoule,
        ':id_equipe' => $idEquipe,
        ':id' => $id,
        ':id_tournoi' => $slot['id_tournoi'],
        ':id_phase_finale' => $slot['id_phase_finale'],
    ]);

    echo json_encode(['success' => true, 'message' => 'Équipe assignée']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
