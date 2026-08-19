<?php
// api/phase_finale/saisir_score.php

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

// ---------------------------------------------------------
// Déclaration de la fonction AVANT toute utilisation
// ---------------------------------------------------------
function propagerVersMatchsSuivants(PDO $pdo, int $idTournoi, int $idPhaseFinale, string $sourceCode, int $equipeId): void {

    $stmt = $pdo->prepare("
        SELECT id, source_team1, source_team2, equipe1_id, equipe2_id
        FROM matchs_phase_finale
        WHERE id_tournoi = :t AND id_phase_finale = :p
          AND (source_team1 = :source1 OR source_team2 = :source2)
    ");
    $stmt->execute([
        ':t' => $idTournoi,
        ':p' => $idPhaseFinale,
        ':source1' => $sourceCode,
        ':source2' => $sourceCode,
    ]);
    $matchsSuivants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($matchsSuivants as $m) {
        $idMatch = (int)$m['id'];

        if ($m['source_team1'] === $sourceCode) {
            $stmtUpdate = $pdo->prepare("
                UPDATE matchs_phase_finale
                SET equipe1_id = :equipe_id
                WHERE id = :id
            ");
            $stmtUpdate->execute([':equipe_id' => $equipeId, ':id' => $idMatch]);
        }

        if ($m['source_team2'] === $sourceCode) {
            $stmtUpdate = $pdo->prepare("
                UPDATE matchs_phase_finale
                SET equipe2_id = :equipe_id
                WHERE id = :id
            ");
            $stmtUpdate->execute([':equipe_id' => $equipeId, ':id' => $idMatch]);
        }

        $stmtCheck = $pdo->prepare("
            SELECT equipe1_id, equipe2_id, statut
            FROM matchs_phase_finale
            WHERE id = :id
        ");
        $stmtCheck->execute([':id' => $idMatch]);
        $matchMaj = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($matchMaj
            && $matchMaj['equipe1_id'] !== null
            && $matchMaj['equipe2_id'] !== null
            && $matchMaj['statut'] === 'en_attente'
        ) {
            $stmtStatut = $pdo->prepare("
                UPDATE matchs_phase_finale
                SET statut = 'pret'
                WHERE id = :id
            ");
            $stmtStatut->execute([':id' => $idMatch]);
        }
    }
}

// ---------------------------------------------------------
// Script principal
// ---------------------------------------------------------
$input = json_decode(file_get_contents('php://input'), true);

$matchId = (int)($input['match_id'] ?? 0);
// $score1  = (int)($input['score1'] ?? -1);
// $score2  = (int)($input['score2'] ?? -1);
$score1  = $input['score1'] ?? null;
$score2  = $input['score2'] ?? null;
$statutMatch = $input['statut_match'] ?? null;
$terrain = array_key_exists('terrain', $input) ? $input['terrain'] : null;
$heure_debut = array_key_exists('heure_debut', $input) ? $input['heure_debut'] : null;

// if ($matchId <= 0 || $score1 < 0 || $score2 < 0 || $score1 === $score2) {
//     http_response_code(400);
//     echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
//     exit;
// }

try {
    $pdo->beginTransaction();

    // Récupérer le match (attention : recherche par id, pas id_tournoi)
    $stmt = $pdo->prepare("SELECT * FROM matchs_phase_finale WHERE id = :id");
    $stmt->execute([':id' => $matchId]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match) {
        throw new Exception('Match introuvable');
    }
    if (!$match['equipe1_id'] || !$match['equipe2_id']) {

        // throw new Exception('Les 2 équipes ne sont pas encore définies');
        $score1 = null;
        $score2 = null;
        $statutMatch = "planifie";
    }

    $idTournoi     = (int) $match['id_tournoi'];
    $idPhaseFinale = (int) $match['id_phase_finale'];

    // $winnerId = $score1 > $score2 ? $match['equipe1_id'] : $match['equipe2_id'];
    // $loserId  = $score1 > $score2 ? $match['equipe2_id'] : $match['equipe1_id'];

    if ($score1 > $score2){
        $winnerId = $match['equipe1_id'];
        $loserId  = $match['equipe2_id'];
    }elseif($score1 < $score2){
        $winnerId = $match['equipe2_id'];
        $loserId  = $match['equipe1_id'];
    }else{
        $winnerId = null;
        $loserId  = null;
    }

    // Mettre à jour le match courant
    $stmt = $pdo->prepare("
        UPDATE matchs_phase_finale
        SET score1 = :score1, score2 = :score2,
            winner_equipe_id = :winner_id, loser_equipe_id = :loser_id,
            statut = 'pret',statut_match = :statut_match, terrain = :terrain, heure_debut = :heure_debut
        WHERE id = :id
    ");
    $stmt->execute([
        ':score1' => $score1,
        ':score2' => $score2,
        ':winner_id' => $winnerId,
        ':loser_id' => $loserId,
        ':id' => $matchId,
        ':statut_match' => $statutMatch,
        ':terrain' => $terrain,
        ':heure_debut' => $heure_debut,
    ]);
    if ($winnerId != 0 && $loserId !=0){
    // Propager vers les matchs suivants
    $codeWin = "Win_{$match['match_code']}";
    propagerVersMatchsSuivants($pdo, $idTournoi, $idPhaseFinale, $codeWin, $winnerId);

    $codeLoss = "Loss_{$match['match_code']}";
    propagerVersMatchsSuivants($pdo, $idTournoi, $idPhaseFinale, $codeLoss, $loserId);
    }
    $pdo->commit();

    // 1. On démarre la capture du flux de sortie
    ob_start();
    $stmt->debugDumpParams();
    // 2. On récupère le texte capturé dans une variable et on stoppe la capture
    $debugDump = ob_get_clean();

    echo json_encode([
        'success' => true,
        'winner_equipe_id'  => $winnerId,
        'loser_equipe_id'   => $loserId,
        'sqllog'            => $debugDump,
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}