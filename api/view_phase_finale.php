<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

$id_tournoi = isset($_GET['id_tournoi']) ? (int)$_GET['id_tournoi'] : 0;

if (!$id_tournoi) {
    echo json_encode(['success' => false, 'message' => 'id_tournoi manquant']);
    exit;
}
$sqlall = "";
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
            JOIN categorie c ON c.id_tournoi = :id_tournoi1 AND c.id_categorie = pf.id_categorie
            WHERE pf.id_tournoi = :id_tournoi
            ORDER BY c.nom, pf.nom";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_tournoi' => $id_tournoi,'id_tournoi1' => $id_tournoi]);
    $phases = $stmt->fetchAll(PDO::FETCH_ASSOC);
// $sqlall = $sqlall . $sql;
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
                      e.nom AS nom_equipe
                  FROM equipes_phase_finale epf
                  JOIN equipe e ON e.id_equipe = epf.id_equipe
                  WHERE epf.id_phase_finale IN ($in)
                  ORDER BY epf.id_phase_finale, epf.seed_position";
        $stmtEq = $pdo->prepare($sqlEq);
        $stmtEq->execute($phaseIds);
// $sqlall = $sqlall . $sqlEq;
        while ($row = $stmtEq->fetch(PDO::FETCH_ASSOC)) {
            $pid = $row['id_phase_finale'];
            if (!isset($equipesMap[$pid])) $equipesMap[$pid] = [];
            $equipesMap[$pid][] = [
                'id_equipe'            => (int)$row['id_equipe'],
                'nom_equipe'           => $row['nom_equipe'],
                'seed_position'        => (int)$row['seed_position'],
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
                     eq1.nom AS nom_equipe1,
                     e1.id_tournoi,
                     e1.id_categorie,
                     e1.id_poule,
                     e1.id_equipe,
                     eq2.nom AS nom_equipe2,
                     e2.id_tournoi,
                     e2.id_categorie,
                     e2.id_poule,
                     e2.id_equipe
                 FROM matchs_phase_finale m
                 LEFT JOIN equipes_phase_finale e1 ON e1.id = m.equipe1_id
                 LEFT JOIN equipes_phase_finale e2 ON e2.id = m.equipe2_id
                LEFT JOIN equipe eq1 ON 
                    eq1.id_tournoi = e1.id_tournoi
                    AND eq1.id_categorie = e1.id_categorie
                    AND eq1.id_poule = e1.id_poule
                    AND eq1.id_equipe = e1.id_equipe
                LEFT JOIN equipe eq2 ON 
                    eq2.id_tournoi = e2.id_tournoi
                    AND eq2.id_categorie = e2.id_categorie
                    AND eq2.id_poule = e2.id_poule
                    AND eq2.id_equipe = e2.id_equipe
                 WHERE m.id_phase_finale IN ($in)
                 ORDER BY m.id_phase_finale, m.round, m.sub_group, m.id";
        $stmtM = $pdo->prepare($sqlM);
        $stmtM->execute($phaseIds);
// $sqlall = $sqlall . $sqlM;
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
// $sqlall = str_replace("\n","***",$sqlall);
    echo json_encode([
        'sql'         => $sqlall,
        'success'     => true,
        'categories'  => $categories,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur base de données: ' . $e->getMessage()]);
}
?>