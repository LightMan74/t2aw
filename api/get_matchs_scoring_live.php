<?php
// api/get_matchs_scoring_live.php
header('Content-Type: application/json');
include __DIR__ . "/check_connected.php";
require 'db.php';

$id_tournoi = $_GET['id_tournoi'] ?? null;

if (!$id_tournoi) {
    echo json_encode(['success' => false, 'error' => 'id_tournoi manquant']);
    exit;
}

try {
    $matchs = [];

    // ---- Matchs de poule ----
    $sqlPoule = "SELECT id, score_equipe_1 AS score1, score_equipe_2 AS score2
                 FROM match_poule
                 WHERE id_tournoi = ?
                --    AND status = 'en_cours'
                   AND dernier_modifiant = 'scoring'";
    $stmt = $pdo->prepare($sqlPoule);
    $stmt->execute([$id_tournoi]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $matchs[] = [
            'source' => 'poule',
            'id' => $row['id'],
            'score1' => $row['score1'],
            'score2' => $row['score2'],
        ];
    }

    // ---- Matchs de phase finale ----
    $sqlPF = "SELECT id, score1, score2
              FROM matchs_phase_finale
              WHERE id_tournoi = ?
                -- AND statut_match = 'en_cours'
                AND dernier_modifiant = 'scoring'";
    $stmt = $pdo->prepare($sqlPF);
    $stmt->execute([$id_tournoi]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $matchs[] = [
            'source' => 'pf',
            'id' => $row['id'],
            'score1' => $row['score1'],
            'score2' => $row['score2'],
        ];
    }

    echo json_encode(['success' => true, 'matchs' => $matchs]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}