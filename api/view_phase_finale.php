<?php
/**
 * API read-only : vue de toutes les phases finales d'un tournoi
 * GET: id_tournoi (required)
 * Retourne : { categories: [ { id_categorie, nom_categorie, phases_finales: [...] } ] }
 *
 * Tables détectées via phase_final.js / phase_final.php :
 *   - phases_finales   (id, id_tournoi, id_categorie, nom, type_bracket, nb_equipes, statut, nb_equipes_arrondi)
 *   - matchs           (id, id_phase_finale, equipe1_id, equipe2_id, round, sub_group, match_code,
 *                        score1, score2, winner_equipe_id, statut, source_team1, source_team2,
 *                        classement_min, classement_max)
 *   - equipes          (id, nom)
 *   - equipes_phases_finales (id_equipe, id_phase_finale, seed_position, is_bye, id_equipe_originale)
 *   - categories       (id, nom)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

$id_tournoi = isset($_GET['id_tournoi']) ? (int)$_GET['id_tournoi'] : 0;

if (!$id_tournoi) {
    echo json_encode(['success' => false, 'message' => 'id_tournoi manquant']);
    exit;
}

try {
    // --- Phases finales du tournoi, groupées par catégorie ---
    $sql = "SELECT
                pf.id                      AS id_phase_finale,
                pf.id_categorie,
                pf.nom,
                pf.type_bracket,
                pf.nb_equipes,
                pf.statut,
                pf.nb_equipes_arrondi,
                c.nom                     AS nom_categorie
            FROM phases_finales pf
            JOIN categorie c ON c.id = pf.id_categorie
            WHERE pf.id_tournoi = :id_tournoi
            ORDER BY c.nom, pf.nom";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_tournoi' => $id_tournoi]);
    $phases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Équipes par phase finale ---
    $phaseIds = array_column($phases, 'id_phase_finale');
    $equipesMap = [];

    if ($phaseIds) {
        $in = implode(',', array_fill(0, count($phaseIds), '?'));
        $sqlEq = "SELECT
                      epf.id_equipe,
                      epf.id_phase_finale,
                      epf.seed_position,
                      epf.is_bye,
                      epf.id_equipe_originale,
                      e.nom AS nom_equipe
                  FROM equipes_phases_finale epf
                  JOIN equipe e ON e.id = epf.id_equipe
                  WHERE epf.id_phase_finale IN ($in)
                  ORDER BY epf.id_phase_finale, epf.seed_position";
        $stmtEq = $pdo->prepare($sqlEq);
        $stmtEq->execute($phaseIds);

        while ($row = $stmtEq->fetch(PDO::FETCH_ASSOC)) {
            $pid = $row['id_phase_finale'];
            if (!isset($equipesMap[$pid])) $equipesMap[$pid] = [];
            $equipesMap[$pid][] = [
                'id_equipe'            => (int)$row['id_equipe'],
                'id_equipe_originale'  => $row['id_equipe_originale'] ? (int)$row['id_equipe_originale'] : null,
                'nom_equipe'           => $row['nom_equipe'],
                'seed_position'       => (int)$row['seed_position'],
                'is_bye'               => (bool)$row['is_bye'],
            ];
        }
    }

    // --- Matchs par phase finale ---
    $matchsMap = [];
    if ($phaseIds) {
        $in = implode(',', array_fill(0, count($phaseIds), '?'));
        $sqlM = "SELECT
                     m.id,
                     m.id_phase_finale,
                     m.equipe1_id,
                     m.equipe2_id,
                     m.round,
                     m.sub_group,
                     m.match_code,
                     m.score1,
                     m.score2,
                     m.winner_equipe_id,
                     m.statut,
                     m.source_team1,
                     m.source_team2,
                     m.classement_min,
                     m.classement_max,
                     e1.nom AS nom_equipe1,
                     e2.nom AS nom_equipe2
                 FROM match m
                 LEFT JOIN equipe e1 ON e1.id = m.equipe1_id
                 LEFT JOIN equipe e2 ON e2.id = m.equipe2_id
                 WHERE m.id_phase_finale IN ($in)
                 ORDER BY m.id_phase_finale, m.round, m.sub_group, m.id";
        $stmtM = $pdo->prepare($sqlM);
        $stmtM->execute($phaseIds);

        while ($row = $stmtM->fetch(PDO::FETCH_ASSOC)) {
            $pid = $row['id_phase_finale'];
            if (!isset($matchsMap[$pid])) $matchsMap[$pid] = [];
            $matchsMap[$pid][] = [
                'id'                  => (int)$row['id'],
                'equipe1_id'          => $row['equipe1_id'] ? (int)$row['equipe1_id'] : null,
                'equipe2_id'          => $row['equipe2_id'] ? (int)$row['equipe2_id'] : null,
                'nom_equipe1'         => $row['nom_equipe1'],
                'nom_equipe2'         => $row['nom_equipe2'],
                'source_team1'        => $row['source_team1'],
                'source_team2'        => $row['source_team2'],
                'round'               => (int)$row['round'],
                'sub_group'           => (int)$row['sub_group'],
                'match_code'          => $row['match_code'],
                'score1'              => $row['score1'] !== null ? (int)$row['score1'] : null,
                'score2'              => $row['score2'] !== null ? (int)$row['score2'] : null,
                'winner_equipe_id'    => $row['winner_equipe_id'] ? (int)$row['winner_equipe_id'] : null,
                'statut'              => $row['statut'],
                'classement_min'      => $row['classement_min'] ? (int)$row['classement_min'] : null,
                'classement_max'      => $row['classement_max'] ? (int)$row['classement_max'] : null,
            ];
        }
    }

    // --- Construction de la structure par catégorie ---
    $categoriesMap = [];
    foreach ($phases as $p) {
        $catId = $p['id_categorie'];
        $catNom = $p['nom_categorie'];
        $pid = $p['id_phase_finale'];

        if (!isset($categoriesMap[$catId])) {
            $categoriesMap[$catId] = [
                'id_categorie'    => (int)$catId,
                'nom_categorie'   => $catNom,
                'phases_finales'  => [],
            ];
        }

        $categoriesMap[$catId]['phases_finales'][] = [
            'id_phase_finale' => (int)$pid,
            'nom'             => $p['nom'],
            'type_bracket'    => $p['type_bracket'],
            'nb_equipes'      => (int)$p['nb_equipes'],
            'statut'          => $p['statut'],
            'equipes'         => $equipesMap[$pid] ?? [],
            'matchs'          => $matchsMap[$pid] ?? [],
        ];
    }

    $categories = array_values($categoriesMap);

    echo json_encode([
        'success'     => true,
        'categories'  => $categories,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur base de données']);
}