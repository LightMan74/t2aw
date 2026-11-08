<?php
// api/phase_finale/saisir_score.php

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

// ---------------------------------------------------------
// Parse une chaîne de scores par sets "9*15*10" => [9, 15, 10]
// Accepte aussi un nombre simple "21" => [21]
// ---------------------------------------------------------
function parseSets($value): array {
    if ($value === null || $value === '') {
        return [];
    }
    $parts = explode('*', (string)$value);
    $sets = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p === '' || !is_numeric($p)) {
            continue;
        }
        $sets[] = (int)$p;
    }
    return $sets;
}

// ---------------------------------------------------------
// Calcule le résultat à partir des sets
// Retourne ['sets1' => int, 'sets2' => int, 'winner' => 1|2|null]
// ---------------------------------------------------------
function calculerResultatSets($score1, $score2): array {
    $s1 = parseSets($score1);
    $s2 = parseSets($score2);

    $nb = min(count($s1), count($s2));
    $sets1 = 0;
    $sets2 = 0;

    for ($i = 0; $i < $nb; $i++) {
        if ($s1[$i] > $s2[$i]) {
            $sets1++;
        } elseif ($s2[$i] > $s1[$i]) {
            $sets2++;
        }
    }

    $winner = null;
    if ($sets1 > $sets2) {
        $winner = 1;
    } elseif ($sets2 > $sets1) {
        $winner = 2;
    } else {
        // Égalité en sets : départage au total de points
        $total1 = array_sum($s1);
        $total2 = array_sum($s2);
        if ($total1 > $total2) {
            $winner = 1;
        } elseif ($total2 > $total1) {
            $winner = 2;
        }
    }

    return ['sets1' => $sets1, 'sets2' => $sets2, 'winner' => $winner];
}

// ---------------------------------------------------------
// Propagation vers les matchs suivants
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
            $stmtUpdate = $pdo->prepare("UPDATE matchs_phase_finale SET equipe1_id = :equipe_id WHERE id = :id");
            $stmtUpdate->execute([':equipe_id' => $equipeId, ':id' => $idMatch]);
        }

        if ($m['source_team2'] === $sourceCode) {
            $stmtUpdate = $pdo->prepare("UPDATE matchs_phase_finale SET equipe2_id = :equipe_id WHERE id = :id");
            $stmtUpdate->execute([':equipe_id' => $equipeId, ':id' => $idMatch]);
        }

        $stmtCheck = $pdo->prepare("SELECT equipe1_id, equipe2_id, statut FROM matchs_phase_finale WHERE id = :id");
        $stmtCheck->execute([':id' => $idMatch]);
        $matchMaj = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($matchMaj
            && $matchMaj['equipe1_id'] !== null
            && $matchMaj['equipe2_id'] !== null
            && $matchMaj['statut'] === 'en_attente'
        ) {
            $stmtStatut = $pdo->prepare("UPDATE matchs_phase_finale SET statut = 'pret' WHERE id = :id");
            $stmtStatut->execute([':id' => $idMatch]);
        }
    }
}

// ---------------------------------------------------------
// Script principal
// ---------------------------------------------------------
$input = json_decode(file_get_contents('php://input'), true);

$matchId     = (int)($input['match_id'] ?? 0);
$score1      = $input['score1'] ?? null;   // ex: "9*15*10"
$score2      = $input['score2'] ?? null;   // ex: "15*6*15"
$statutMatch = $input['statut_match'] ?? null;
$terrain     = array_key_exists('terrain', $input) ? $input['terrain'] : null;
$heure_debut = array_key_exists('heure_debut', $input) ? $input['heure_debut'] : null;

if ($matchId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM matchs_phase_finale WHERE id = :id");
    $stmt->execute([':id' => $matchId]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match) {
        throw new Exception('Match introuvable');
    }

    if (!$match['equipe1_id'] || !$match['equipe2_id']) {
        $score1 = null;
        $score2 = null;
        $statutMatch = "planifie";
    }

    $idTournoi     = (int)$match['id_tournoi'];
    $idPhaseFinale = (int)$match['id_phase_finale'];

    // Détermination du vainqueur par sets
    $winnerId = null;
    $loserId  = null;
    $sets1 = 0;
    $sets2 = 0;

    if ($score1 !== null && $score2 !== null) {
        $res = calculerResultatSets($score1, $score2);
        $sets1 = $res['sets1'];
        $sets2 = $res['sets2'];

        if ($res['winner'] === 1) {
            $winnerId = $match['equipe1_id'];
            $loserId  = $match['equipe2_id'];
        } elseif ($res['winner'] === 2) {
            $winnerId = $match['equipe2_id'];
            $loserId  = $match['equipe1_id'];
        }
    }

    $stmt = $pdo->prepare("
        UPDATE matchs_phase_finale
        SET score1 = :score1, score2 = :score2,
            winner_equipe_id = :winner_id, loser_equipe_id = :loser_id,
            statut = 'pret', statut_match = :statut_match,
            terrain = :terrain, heure_debut = :heure_debut,
            dernier_modifiant = 'admin'
        WHERE id = :id
    ");
    $stmt->execute([
        ':score1'       => $score1,
        ':score2'       => $score2,
        ':winner_id'    => $winnerId,
        ':loser_id'     => $loserId,
        ':id'           => $matchId,
        ':statut_match' => $statutMatch,
        ':terrain'      => $terrain,
        ':heure_debut'  => $heure_debut,
    ]);

    // Propager uniquement si le match est terminé et qu'il y a un vainqueur
    if ($statutMatch === 'termine' && $winnerId && $loserId) {
        propagerVersMatchsSuivants($pdo, $idTournoi, $idPhaseFinale, "Win_{$match['match_code']}", (int)$winnerId);
        propagerVersMatchsSuivants($pdo, $idTournoi, $idPhaseFinale, "Loss_{$match['match_code']}", (int)$loserId);
    }

    $pdo->commit();

    echo json_encode([
        'success'          => true,
        'winner_equipe_id' => $winnerId,
        'loser_equipe_id'  => $loserId,
        'sets_equipe1'     => $sets1,
        'sets_equipe2'     => $sets2,
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}