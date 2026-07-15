<?php
// api/phase_finale/simuler_rounds.php

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);

$idPhaseFinale = (int)($input['id_phase_finale'] ?? 0);
$nbRoundsASimuler = (int)($input['nb_rounds'] ?? 0); // ex: 2 pour sauter 1/16 et 1/8

if ($idPhaseFinale <= 0 || $nbRoundsASimuler <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Récupérer les infos de la phase finale
    $stmt = $pdo->prepare("SELECT * FROM phases_finales WHERE id = :id");
    $stmt->execute([':id' => $idPhaseFinale]);
    $phase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$phase) {
        throw new Exception('Phase finale introuvable');
    }

    $idTournoi = (int)$phase['id_tournoi'];

    if ($nbRoundsASimuler >= (int)$phase['nb_rounds']) {
        throw new Exception('Impossible de simuler tous les rounds (il doit rester au moins un round à jouer)');
    }

    $nbMatchsSimules = 0;

    // On simule round par round, dans l'ordre (0, 1, 2, ...)
    for ($round = 0; $round < $nbRoundsASimuler; $round++) {

        // Récupérer tous les matchs de ce round, avec le seed des équipes
        $stmt = $pdo->prepare("
            SELECT m.*,
                   e1.seed_position AS seed1,
                   e2.seed_position AS seed2
            FROM matchs_phase_finale m
            LEFT JOIN equipes_phase_finale e1 ON e1.id = m.equipe1_id
            LEFT JOIN equipes_phase_finale e2 ON e2.id = m.equipe2_id
            WHERE m.id_phase_finale = :p AND m.round = :r
            ORDER BY m.sub_group ASC, m.match_num ASC
        ");
        $stmt->execute([':p' => $idPhaseFinale, ':r' => $round]);
        $matchsRound = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($matchsRound as $match) {

            // Si déjà terminé, on passe
            if ($match['statut'] === 'termine') {
                continue;
            }

            // Il faut que les deux équipes soient définies pour pouvoir simuler
            if (!$match['equipe1_id'] || !$match['equipe2_id']) {
                // Peut arriver si un match précédent (même round, autre sub_group) 
                // n'a pas encore été propagé -> on skip pour l'instant,
                // il sera traité correctement car la boucle round par round
                // garantit que le round précédent est déjà résolu avant.
                continue;
            }

            $seed1 = $match['seed1'];
            $seed2 = $match['seed2'];

            // Le seed le plus petit = le meilleur seed = gagnant automatique
            // (seed_position 1 = tête de série n°1)
            if ($seed1 === null || $seed2 === null) {
                // Sécurité : si pas de seed dispo, on prend equipe1 par défaut
                $winnerId = $match['equipe1_id'];
                $loserId  = $match['equipe2_id'];
                $score1 = 21;
                $score2 = 0;
            } elseif ($seed1 <= $seed2) {
                $winnerId = $match['equipe1_id'];
                $loserId  = $match['equipe2_id'];
                $score1 = 21;
                $score2 = 0;
            } else {
                $winnerId = $match['equipe2_id'];
                $loserId  = $match['equipe1_id'];
                $score1 = 0;
                $score2 = 21;
            }

            // Mise à jour du match : statut spécial "simule"
            $stmtUpdate = $pdo->prepare("
                UPDATE matchs_phase_finale
                SET score1 = :score1, score2 = :score2,
                    winner_equipe_id = :winner_id, loser_equipe_id = :loser_id,
                    statut = 'simule'
                WHERE id = :id
            ");
            $stmtUpdate->execute([
                ':score1' => $score1,
                ':score2' => $score2,
                ':winner_id' => $winnerId,
                ':loser_id' => $loserId,
                ':id' => $match['id'],
            ]);

            // Propager le gagnant/perdant vers les matchs suivants
            propagerSimulation($pdo, $idTournoi, $idPhaseFinale, "Win_{$match['match_code']}", $winnerId);
            propagerSimulation($pdo, $idTournoi, $idPhaseFinale, "Loss_{$match['match_code']}", $loserId);

            $nbMatchsSimules++;
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'nb_matchs_simules' => $nbMatchsSimules,
        'nb_rounds_simules' => $nbRoundsASimuler,
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Identique à la logique de saisir_score.php, mais isolée ici
 * pour ne pas dépendre d'un autre fichier.
 */
function propagerSimulation(PDO $pdo, int $idTournoi, int $idPhaseFinale, string $sourceCode, int $equipeId): void {

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
            $pdo->prepare("UPDATE matchs_phase_finale SET equipe1_id = :eq WHERE id = :id")
                ->execute([':eq' => $equipeId, ':id' => $idMatch]);
        }
        if ($m['source_team2'] === $sourceCode) {
            $pdo->prepare("UPDATE matchs_phase_finale SET equipe2_id = :eq WHERE id = :id")
                ->execute([':eq' => $equipeId, ':id' => $idMatch]);
        }

        $stmtCheck = $pdo->prepare("SELECT equipe1_id, equipe2_id, statut FROM matchs_phase_finale WHERE id = :id");
        $stmtCheck->execute([':id' => $idMatch]);
        $matchMaj = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($matchMaj && $matchMaj['equipe1_id'] !== null && $matchMaj['equipe2_id'] !== null && $matchMaj['statut'] === 'en_attente') {
            $pdo->prepare("UPDATE matchs_phase_finale SET statut = 'pret' WHERE id = :id")
                ->execute([':id' => $idMatch]);
        }
    }
}