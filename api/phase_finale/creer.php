<?php
// api/phase_finale/creer.php

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);

$idTournoi   = (int)($input['id_tournoi'] ?? 0);
$nom         = trim($input['nom'] ?? 'Phase Finale');
$typeBracket = $input['type_bracket'] ?? 'classique';
$nbEquipes   = (int)($input['nb_equipes'] ?? 0);

// Nouveau : liste ordonnée des équipes choisies par l'utilisateur
// Format attendu : [{id_equipe: 12, nom: "Team A"}, {id_equipe: 5, nom: "Team B"}, ...]
// Si non fourni, on génère des équipes temporaires "Équipe X" comme avant
$equipesSelectionnees = $input['equipes'] ?? [];

if ($idTournoi <= 0 || $nbEquipes < 2) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

function forcerPuissanceDe2(int $n): int {
    return (int) pow(2, ceil(log($n, 2)));
}

try {
    $pdo->beginTransaction();

    $nbEquipesArrondi = forcerPuissanceDe2($nbEquipes);
    $nbRounds = (int) log($nbEquipesArrondi, 2);

    // 1. Créer la phase finale
    $stmt = $pdo->prepare("
        INSERT INTO phases_finales (id_tournoi, nom, type_bracket, nb_equipes, nb_equipes_arrondi, nb_rounds, statut)
        VALUES (:id_tournoi, :nom, :type_bracket, :nb_equipes, :nb_equipes_arrondi, :nb_rounds, 'en_cours')
    ");
    $stmt->execute([
        ':id_tournoi' => $idTournoi,
        ':nom' => $nom,
        ':type_bracket' => $typeBracket,
        ':nb_equipes' => $nbEquipes,
        ':nb_equipes_arrondi' => $nbEquipesArrondi,
        ':nb_rounds' => $nbRounds,
    ]);
    $idPhaseFinale = (int) $pdo->lastInsertId();

    // 2. Créer les équipes (temporaires + BYE si besoin)
    // Si l'utilisateur a fourni un ordre personnalisé, on l'utilise (index 0 = seed 1, etc.)
    $equipeIds = []; // seed_position => id
    $stmtEq = $pdo->prepare("
        INSERT INTO equipes_phase_finale (id_tournoi, id_phase_finale, id_equipe_originale, seed_position, nom_equipe, is_bye)
        VALUES (:id_tournoi, :id_phase_finale, :id_equipe_originale, :seed, :nom, :is_bye)
    ");

    for ($i = 1; $i <= $nbEquipesArrondi; $i++) {
        $isBye = $i > $nbEquipes ? 1 : 0;

        $idEquipeOriginale = null;
        $nomEquipe = 'BYE';

        if (!$isBye) {
            if (isset($equipesSelectionnees[$i - 1])) {
                // Utilisation de l'ordre personnalisé fourni par l'utilisateur
                $idEquipeOriginale = (int) $equipesSelectionnees[$i - 1]['id_equipe'];
                $nomEquipe = trim($equipesSelectionnees[$i - 1]['nom']) ?: ('Équipe ' . $i);
            } else {
                $nomEquipe = 'Équipe ' . $i;
            }
        }

        $stmtEq->execute([
            ':id_tournoi' => $idTournoi,
            ':id_phase_finale' => $idPhaseFinale,
            ':id_equipe_originale' => $idEquipeOriginale,
            ':seed' => $i,
            ':nom' => $nomEquipe,
            ':is_bye' => $isBye,
        ]);
        $equipeIds[$i] = (int) $pdo->lastInsertId();
    }

    // 3. Générer les matchs selon le type de bracket
    if ($typeBracket === 'classique') {
        $nbMatchs = genererBracketClassique($pdo, $idTournoi, $idPhaseFinale, $nbEquipesArrondi, $nbRounds, $equipeIds);
    } else {
        $nbMatchs = genererBracketClassementComplet($pdo, $idTournoi, $idPhaseFinale, $nbEquipesArrondi, $nbRounds, $equipeIds);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'id_phase_finale' => $idPhaseFinale,
        'nb_matchs' => $nbMatchs,
        'nb_rounds' => $nbRounds,
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// ============================================================
// BRACKET CLASSIQUE (élimination directe, seeding standard)
// ============================================================

function genererBracketClassique(PDO $pdo, int $idTournoi, int $idPhaseFinale, int $nbEquipes, int $nbRounds, array $equipeIds): int {
    $stmt = $pdo->prepare("
        INSERT INTO matchs_phase_finale
            (id_tournoi, id_phase_finale, round, sub_group, match_num, match_code,
             source_team1, source_team2, equipe1_id, equipe2_id, statut)
        VALUES
            (:id_tournoi, :id_phase_finale, :round, :sub_group, :match_num, :match_code,
             :source_team1, :source_team2, :equipe1_id, :equipe2_id, :statut)
    ");

    $totalMatchs = 0;

    $ordreSeed = genererOrdreSeeding($nbEquipes);

    $nbMatchsRound0 = $nbEquipes / 2;
    for ($m = 1; $m <= $nbMatchsRound0; $m++) {
        $seedA = $ordreSeed[($m - 1) * 2];
        $seedB = $ordreSeed[($m - 1) * 2 + 1];

        $equipe1Id = $equipeIds[$seedA];
        $equipe2Id = $equipeIds[$seedB];

        $matchCode = "R0_S1_M{$m}";

        $stmt->execute([
            ':id_tournoi' => $idTournoi,
            ':id_phase_finale' => $idPhaseFinale,
            ':round' => 0,
            ':sub_group' => 1,
            ':match_num' => $m,
            ':match_code' => $matchCode,
            ':source_team1' => null,
            ':source_team2' => null,
            ':equipe1_id' => $equipe1Id,
            ':equipe2_id' => $equipe2Id,
            ':statut' => 'pret',
        ]);
        $totalMatchs++;
    }

    for ($round = 1; $round < $nbRounds; $round++) {
        $nbMatchsRound = $nbEquipes / pow(2, $round + 1);
        $isFinale = ($round === $nbRounds - 1);

        for ($m = 1; $m <= $nbMatchsRound; $m++) {
            $matchCode = "R{$round}_S1_M{$m}";
            $sourceM1 = ($m - 1) * 2 + 1;
            $sourceM2 = ($m - 1) * 2 + 2;

            $sourceTeam1 = "Win_R" . ($round - 1) . "_S1_M{$sourceM1}";
            $sourceTeam2 = "Win_R" . ($round - 1) . "_S1_M{$sourceM2}";

            $stmt->execute([
                ':id_tournoi' => $idTournoi,
                ':id_phase_finale' => $idPhaseFinale,
                ':round' => $round,
                ':sub_group' => 1,
                ':match_num' => $m,
                ':match_code' => $matchCode,
                ':source_team1' => $sourceTeam1,
                ':source_team2' => $sourceTeam2,
                ':equipe1_id' => null,
                ':equipe2_id' => null,
                ':statut' => 'en_attente',
            ]);
            $totalMatchs++;

            if ($isFinale) {
                $pdo->prepare("UPDATE matchs_phase_finale SET classement_min = 1, classement_max = 2 WHERE id_tournoi = :t AND id_phase_finale = :p AND match_code = :c")
                    ->execute([':t' => $idTournoi, ':p' => $idPhaseFinale, ':c' => $matchCode]);
            }
        }
    }

    return $totalMatchs;
}

function genererOrdreSeeding(int $nbEquipes): array {
    $ordre = [1, 2];
    while (count($ordre) < $nbEquipes) {
        $taille = count($ordre) * 2;
        $nouvelOrdre = [];
        foreach ($ordre as $seed) {
            $nouvelOrdre[] = $seed;
            $nouvelOrdre[] = $taille + 1 - $seed;
        }
        $ordre = $nouvelOrdre;
    }
    return $ordre;
}

// ============================================================
// BRACKET CLASSEMENT COMPLET
// ============================================================

function genererBracketClassementComplet(PDO $pdo, int $idTournoi, int $idPhaseFinale, int $nbEquipes, int $nbRounds, array $equipeIds): int {
    $stmt = $pdo->prepare("
        INSERT INTO matchs_phase_finale
            (id_tournoi, id_phase_finale, round, sub_group, match_num, match_code,
             source_team1, source_team2, equipe1_id, equipe2_id, classement_min, classement_max, statut)
        VALUES
            (:id_tournoi, :id_phase_finale, :round, :sub_group, :match_num, :match_code,
             :source_team1, :source_team2, :equipe1_id, :equipe2_id, :classement_min, :classement_max, :statut)
    ");

    $totalMatchs = 0;

    $arrayRound = [];
    $fnr = $nbEquipes;
    $arrayRound[0] = [$fnr, $nbEquipes / $fnr];
    for ($i = 1; $i < $nbRounds; $i++) {
        $fnr = $fnr / 2;
        $arrayRound[$i] = [$fnr, $nbEquipes / $fnr];
    }

    $ordreSeed = genererOrdreSeeding($nbEquipes);
    $nbMatchsRound0 = $arrayRound[0][0] / 2;

    for ($m = 1; $m <= $nbMatchsRound0; $m++) {
        $seedA = $ordreSeed[($m - 1) * 2];
        $seedB = $ordreSeed[($m - 1) * 2 + 1];

        $matchCode = "R0_S1_M{$m}";

        $stmt->execute([
            ':id_tournoi' => $idTournoi,
            ':id_phase_finale' => $idPhaseFinale,
            ':round' => 0,
            ':sub_group' => 1,
            ':match_num' => $m,
            ':match_code' => $matchCode,
            ':source_team1' => null,
            ':source_team2' => null,
            ':equipe1_id' => $equipeIds[$seedA],
            ':equipe2_id' => $equipeIds[$seedB],
            ':classement_min' => null,
            ':classement_max' => null,
            ':statut' => 'pret',
        ]);
        $totalMatchs++;
    }

    for ($j = 1; $j < count($arrayRound); $j++) {
        $isDernierRound = ($j === count($arrayRound) - 1);
        $classementPlace = 1;

        for ($k = 1; $k <= $arrayRound[$j][1]; $k++) {
            $classementMin = null;
            $classementMax = null;
            if ($isDernierRound) {
                $classementMin = $classementPlace++;
                $classementMax = $classementPlace++;
            }

            $wl = ($k % 2 === 0) ? 'Loss_' : 'Win_';
            $splus = (int) ceil($k / 2);
            $mplus = 0;

            $nbMatchsSub = $arrayRound[$j][0] / 2;

            for ($m = 1; $m <= $nbMatchsSub; $m++) {
                $matchCode = "R{$j}_S{$k}_M{$m}";
                $sourceM1 = $m + $mplus;
                $mplus++;
                $sourceM2 = $m + $mplus;

                $sourceTeam1 = "{$wl}R" . ($j - 1) . "_S{$splus}_M{$sourceM1}";
                $sourceTeam2 = "{$wl}R" . ($j - 1) . "_S{$splus}_M{$sourceM2}";

                $stmt->execute([
                    ':id_tournoi' => $idTournoi,
                    ':id_phase_finale' => $idPhaseFinale,
                    ':round' => $j,
                    ':sub_group' => $k,
                    ':match_num' => $m,
                    ':match_code' => $matchCode,
                    ':source_team1' => $sourceTeam1,
                    ':source_team2' => $sourceTeam2,
                    ':equipe1_id' => null,
                    ':equipe2_id' => null,
                    ':classement_min' => $classementMin,
                    ':classement_max' => $classementMax,
                    ':statut' => 'en_attente',
                ]);
                $totalMatchs++;
            }
        }
    }

    return $totalMatchs;
}