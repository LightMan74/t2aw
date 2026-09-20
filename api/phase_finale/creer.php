<?php
// api/phase_finale/creer.php

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);

$idTournoi   = (int)($input['id_tournoi'] ?? 0);
$idCategorie = (int)($input['id_categorie'] ?? 0);
$nom         = trim($input['nom'] ?? 'Phase Finale');
$typeBracket = $input['type_bracket'] ?? 'classique';
$nbEquipes   = (int)($input['nb_equipes'] ?? 0);

$resetTerrainRound = !empty($input['reset_terrain_round']);

$equipesSelectionnees = $input['equipesSelectionnees'] ?? [];

if ($idTournoi <= 0 || $idCategorie <= 0 || $nbEquipes < 2) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

function forcerPuissanceDe2(int $n): int {
    return (int) pow(2, ceil(log($n, 2)));
}

try {
    $pdo->beginTransaction();

    // ----------------------------------------------------------
    // Vérification : une seule phase finale par catégorie/tournoi
    // ----------------------------------------------------------
    $stmtCheck = $pdo->prepare("
        SELECT id, nom, statut
        FROM phases_finales
        WHERE id_tournoi = :id_tournoi AND id_categorie = :id_categorie
        LIMIT 1
    ");
    $stmtCheck->execute([
        ':id_tournoi' => $idTournoi,
        ':id_categorie' => $idCategorie,
    ]);
    $phaseExistante = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($phaseExistante) {
        $pdo->rollBack();
        http_response_code(409); // Conflict
        echo json_encode([
            'success' => false,
            'message' => "Une phase finale existe déjà pour cette catégorie (« {$phaseExistante['nom']} »). Supprimez-la avant d'en créer une nouvelle.",
            'id_phase_finale_existante' => (int)$phaseExistante['id'],
        ]);
        exit;
    }

    $nbEquipesArrondi = forcerPuissanceDe2($nbEquipes);
    $nbRounds = (int) log($nbEquipesArrondi, 2);

    // 1. Créer la phase finale (avec id_categorie)
    $stmt = $pdo->prepare("
        INSERT INTO phases_finales (id_tournoi, id_categorie, nom, type_bracket, nb_equipes, nb_equipes_arrondi, nb_rounds, statut)
        VALUES (:id_tournoi, :id_categorie, :nom, :type_bracket, :nb_equipes, :nb_equipes_arrondi, :nb_rounds, 'en_cours')
    ");
    $stmt->execute([
        ':id_tournoi' => $idTournoi,
        ':id_categorie' => $idCategorie,
        ':nom' => $nom,
        ':type_bracket' => $typeBracket,
        ':nb_equipes' => $nbEquipes,
        ':nb_equipes_arrondi' => $nbEquipesArrondi,
        ':nb_rounds' => $nbRounds,
    ]);
    $idPhaseFinale = (int) $pdo->lastInsertId();

    // 2. Créer les équipes avec liaison stable aux équipes du tournoi
    $equipeIds = [];
    $stmtEq = $pdo->prepare("
        INSERT INTO equipes_phase_finale 
        (id_tournoi, id_phase_finale, id_categorie, id_poule, id_equipe, seed_position, nom_equipe, is_bye)
        VALUES 
        (:id_tournoi, :id_phase_finale, :id_categorie, :id_poule, :id_equipe, :seed, :nom, :is_bye)
    ");

    for ($i = 1; $i <= $nbEquipesArrondi; $i++) {
        $isBye = $i > $nbEquipes ? 1 : 0;

        $idEquipe = null;
        $idCategorieEquipe = null;
        $idPoule = null;
        $nomEquipe = 'BYE';

        if (!$isBye && isset($equipesSelectionnees[$i - 1])) {
            // $eq = $equipesSelectionnees[$i - 1];
            // $idEquipe = (int)($eq['id_equipe'] ?? 0);
            // $idCategorieEquipe = (int)($eq['id_categorie'] ?? 0);
            // $idPoule = (int)($eq['id_poule'] ?? 0);
            $eq = $equipesSelectionnees[$i - 1];
            $idEquipe = (int)(0);
            $idCategorieEquipe = (int)(0);
            $idPoule = (int)(0);
            // Le nom réel est attribué après la création depuis l'interface d'assignation.
            $nomEquipe = 'Seed ' . $i;
        } elseif (!$isBye) {
            $nomEquipe = 'Seed ' . $i;
        }

        $stmtEq->execute([
            ':id_tournoi' => $idTournoi,
            ':id_phase_finale' => $idPhaseFinale,
            ':id_categorie' => $idCategorieEquipe,
            ':id_poule' => $idPoule,
            ':id_equipe' => $idEquipe,
            ':seed' => $i,
            ':nom' => $nomEquipe,
            ':is_bye' => $isBye,
        ]);
        $equipeIds[$i] = (int) $pdo->lastInsertId();
    }

    // 3. Générer les matchs selon le type de bracket
if ($typeBracket === 'classique') {
    $nbMatchs = genererBracketClassique($pdo, $idTournoi, $idPhaseFinale, $nbEquipesArrondi, $nbRounds, $equipeIds, $resetTerrainRound);
} else {
    $nbMatchs = genererBracketClassementComplet($pdo, $idTournoi, $idPhaseFinale, $nbEquipesArrondi, $nbRounds, $equipeIds, $resetTerrainRound);
}

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'id_phase_finale' => $idPhaseFinale,
        'nb_matchs' => $nbMatchs,
        'nb_rounds' => $nbRounds,
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// ... (le reste des fonctions genererBracketClassique, genererBracketClassementComplet, genererOrdreSeeding restent identiques)

// ============================================================
// BRACKET CLASSIQUE (élimination directe, seeding standard)
// ============================================================

function genererBracketClassique(PDO $pdo, int $idTournoi, int $idPhaseFinale, int $nbEquipes, int $nbRounds, array $equipeIds, bool $resetTerrainRound = false): int {
    $stmt = $pdo->prepare("
        SELECT 
            IF(`terrain_automatique` = 1, `nbre_terrain_phasefinal`, 0) as nbre_terrain_phasefinal,
            `heure_debut_phasefinal`,
            `temps_de_match`
        FROM `parametre` WHERE id_tournoi = :id
    ");
    $stmt->execute(['id' => $idTournoi]);
    $params = $stmt->fetch(PDO::FETCH_ASSOC);

    $terrainph = (int) ($params['nbre_terrain_phasefinal'] ?? 0);
    $heureDebut = $params['heure_debut_phasefinal'] ?? null;
    $tempsDeMatch = (int) ($params['temps_de_match'] ?? 20);

    if ($terrainph == 0) {
        $terraincurrent = null;
    } else {
        $terraincurrent = 1;
    }

    $heureParTerrain = [];
    if ($terrainph > 0 && $heureDebut) {
        for ($t = 1; $t <= $terrainph; $t++) {
            $heureParTerrain[$t] = $heureDebut;
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO matchs_phase_finale
            (id_tournoi, id_phase_finale, round, sub_group, match_num, match_code,
             source_team1, source_team2, equipe1_id, equipe2_id, statut, terrain, heure_debut)
        VALUES
            (:id_tournoi, :id_phase_finale, :round, :sub_group, :match_num, :match_code,
             :source_team1, :source_team2, :equipe1_id, :equipe2_id, :statut, :terrain, :heure_debut)
    ");

    $totalMatchs = 0;

    // Seeding standard : ordre des positions pour bracket équilibré
    $ordreSeed = genererOrdreSeeding($nbEquipes);

    // Round 0 : les vrais matchs avec les équipes (selon seeding standard)
    $nbMatchsRound0 = $nbEquipes / 2;
    for ($m = 1; $m <= $nbMatchsRound0; $m++) {
        $seedA = $ordreSeed[($m - 1) * 2];
        $seedB = $ordreSeed[($m - 1) * 2 + 1];

        $equipe1Id = $equipeIds[$seedA];
        $equipe2Id = $equipeIds[$seedB];

        $matchCode = "R0_S1_M{$m}";

        $terrainAssigne = $terraincurrent;
        $heureAssignee = null;

        if ($terrainAssigne !== null && isset($heureParTerrain[$terrainAssigne])) {
            $heureAssignee = $heureParTerrain[$terrainAssigne];
            $heureParTerrain[$terrainAssigne] = ajouterMinutesHeure($heureAssignee, $tempsDeMatch);
        }

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
            ':terrain' => $terrainAssigne,
            ':heure_debut' => $heureAssignee,
        ]);
        $totalMatchs++;

        if ($terraincurrent !== null) {
            $terraincurrent++;
            if ($terraincurrent > $terrainph) $terraincurrent = 1;
        }
    }

    for ($round = 1; $round < $nbRounds; $round++) {
        // Réinitialisation du terrain à chaque nouveau round si demandé
        if ($resetTerrainRound && $terraincurrent !== null) {
            $terraincurrent = 1;
        }

        $nbMatchsRound = $nbEquipes / pow(2, $round + 1);
        $isFinale = ($round === $nbRounds - 1);

        for ($m = 1; $m <= $nbMatchsRound; $m++) {
            $matchCode = "R{$round}_S1_M{$m}";
            $sourceM1 = ($m - 1) * 2 + 1;
            $sourceM2 = ($m - 1) * 2 + 2;

            $sourceTeam1 = "Win_R" . ($round - 1) . "_S1_M{$sourceM1}";
            $sourceTeam2 = "Win_R" . ($round - 1) . "_S1_M{$sourceM2}";

            $terrainAssigne = $terraincurrent;
            $heureAssignee = null;

            if ($terrainAssigne !== null && isset($heureParTerrain[$terrainAssigne])) {
                $heureAssignee = $heureParTerrain[$terrainAssigne];
                $heureParTerrain[$terrainAssigne] = ajouterMinutesHeure($heureAssignee, $tempsDeMatch);
            }

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
                ':terrain' => $terrainAssigne,
                ':heure_debut' => $heureAssignee,
            ]);
            $totalMatchs++;

            if ($terraincurrent !== null) {
                $terraincurrent++;
                if ($terraincurrent > $terrainph) $terraincurrent = 1;
            }

            // Ajout des classements sur la finale
            if ($isFinale) {
                $pdo->prepare("UPDATE matchs_phase_finale SET classement_min = 1, classement_max = 2 WHERE id_tournoi = :t AND id_phase_finale = :p AND match_code = :c")
                    ->execute([':t' => $idTournoi, ':p' => $idPhaseFinale, ':c' => $matchCode]);
            }
        }
    }

    return $totalMatchs;
}

// ============================================================
// BRACKET CLASSEMENT COMPLET
// ============================================================

function genererBracketClassementComplet(PDO $pdo, int $idTournoi, int $idPhaseFinale, int $nbEquipes, int $nbRounds, array $equipeIds, bool $resetTerrainRound = false): int {
    $stmt = $pdo->prepare("
        SELECT 
            IF(`terrain_automatique` = 1, `nbre_terrain_phasefinal`, 0) as nbre_terrain_phasefinal,
            `heure_debut_phasefinal`,
            `temps_de_match`
        FROM `parametre` WHERE id_tournoi = :id
    ");
    $stmt->execute(['id' => $idTournoi]);
    $params = $stmt->fetch(PDO::FETCH_ASSOC);

    $terrainph = (int) ($params['nbre_terrain_phasefinal'] ?? 0);
    $heureDebut = $params['heure_debut_phasefinal'] ?? null;
    $tempsDeMatch = (int) ($params['temps_de_match'] ?? 20);

    $terraincurrent = ($terrainph == 0) ? null : 1;

    $heureParTerrain = [];
    if ($terrainph > 0 && $heureDebut) {
        for ($t = 1; $t <= $terrainph; $t++) {
            $heureParTerrain[$t] = $heureDebut;
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO matchs_phase_finale
            (id_tournoi, id_phase_finale, round, sub_group, match_num, match_code,
             source_team1, source_team2, equipe1_id, equipe2_id, classement_min, classement_max, statut, terrain, heure_debut)
        VALUES
            (:id_tournoi, :id_phase_finale, :round, :sub_group, :match_num, :match_code,
             :source_team1, :source_team2, :equipe1_id, :equipe2_id, :classement_min, :classement_max, :statut, :terrain, :heure_debut)
    ");

    $totalMatchs = 0;

    // Structure des rounds
    $arrayRound = [];
    $fnr = $nbEquipes;
    $arrayRound[0] = [$fnr, $nbEquipes / $fnr];
    for ($i = 1; $i < $nbRounds; $i++) {
        $fnr = $fnr / 2;
        $arrayRound[$i] = [$fnr, $nbEquipes / $fnr];
    }

    $ordreSeed = genererOrdreSeeding($nbEquipes);

    // ------------------------------------------------------------------
    // ÉTAPE PRÉALABLE : simulation virtuelle pour détecter les BYE
    // On simule round par round si chaque match/branche contient au
    // moins une équipe réelle (non-BYE), et on propage l'info Win_/Loss_
    // ------------------------------------------------------------------

    // isBye[seed] = true/false selon equipeIds fourni (on suppose que
    // l'appelant sait quelles seeds sont BYE ; ici on le déduit via
    // un tableau passé en paramètre ou déduit de la table). 
    // Pour rester générique, on demande un tableau $byeSeeds en param
    // (voir plus bas: on le récupère depuis la BDD equipes_phase_finale).

    $stmtBye = $pdo->prepare("
        SELECT seed_position, is_bye 
        FROM equipes_phase_finale 
        WHERE id_phase_finale = :p
    ");
    $stmtBye->execute([':p' => $idPhaseFinale]);
    $byeMap = [];
    foreach ($stmtBye->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $byeMap[(int)$row['seed_position']] = (bool)$row['is_bye'];
    }

    // Round 0 : état réel de chaque match (contient une équipe réelle ?)
    // et on note si le match est "byeMatch" (une équipe VS BYE = auto-win)
    $nbMatchsRound0 = $arrayRound[0][0] / 2;

    // matchReal[round][sub_group][match_num] = bool (au moins 1 vraie équipe)
    // Pour round 0, on connait déjà via byeMap
    $realState = []; // $realState[0][1][$m] = true/false (le match existe réellement)
    $winIsReal = [];  // winIsReal[0][1][$m] = le vainqueur est une vraie équipe (toujours vrai sauf 2 BYE)
    $lossIsReal = []; // lossIsReal[0][1][$m] = le perdant est réel (donc le match Loss_ existe)

    for ($m = 1; $m <= $nbMatchsRound0; $m++) {
        $seedA = $ordreSeed[($m - 1) * 2];
        $seedB = $ordreSeed[($m - 1) * 2 + 1];
        $byeA = $byeMap[$seedA] ?? false;
        $byeB = $byeMap[$seedB] ?? false;

        // Le match "existe" si au moins une des deux équipes est réelle
        $realState[0][1][$m] = !($byeA && $byeB);
        // Le winner existe toujours tant que le match existe (sauf 2 bye = rien du tout, cas extrême)
        $winIsReal[0][1][$m] = !($byeA && $byeB);
        // Le loser n'existe (comme "vraie équipe perdante") que si LES DEUX sont réelles
        $lossIsReal[0][1][$m] = (!$byeA && !$byeB);
    }

    // Propagation pour les rounds suivants (Win_/Loss_)
    for ($j = 1; $j < count($arrayRound); $j++) {
        $nbSousGroupesPrec = $arrayRound[$j-1][1];
        $nbMatchsSubPrec = $arrayRound[$j-1][0] / 2;

        for ($k = 1; $k <= $arrayRound[$j][1]; $k++) {
            $wl = ($k % 2 === 0) ? 'loss' : 'win';
            $splus = (int) ceil($k / 2);
            $mplus = 0;
            $nbMatchsSub = $arrayRound[$j][0] / 2;

            for ($m = 1; $m <= $nbMatchsSub; $m++) {
                $sourceM1 = $m + $mplus;
                $mplus++;
                $sourceM2 = $m + $mplus;

                if ($wl === 'win') {
                    $team1Real = $winIsReal[$j-1][$splus][$sourceM1] ?? false;
                    $team2Real = $winIsReal[$j-1][$splus][$sourceM2] ?? false;
                } else {
                    $team1Real = $lossIsReal[$j-1][$splus][$sourceM1] ?? false;
                    $team2Real = $lossIsReal[$j-1][$splus][$sourceM2] ?? false;
                }

                // Le match existe si au moins une équipe réelle arrive
                $matchExiste = ($team1Real || $team2Real);
                $realState[$j][$k][$m] = $matchExiste;
                $winIsReal[$j][$k][$m] = $matchExiste; // le gagnant existe si le match existe
                $lossIsReal[$j][$k][$m] = ($team1Real && $team2Real); // perdant réel seulement si duel réel
            }
        }
    }

    // ------------------------------------------------------------------
    // GÉNÉRATION RÉELLE DES MATCHS (en sautant ceux inexistants)
    // ------------------------------------------------------------------

    for ($j = 0; $j < count($arrayRound); $j++) {
        if ($resetTerrainRound && $terraincurrent !== null) {
            $terraincurrent = 1;
        }

        $isDernierRound = ($j === count($arrayRound) - 1);
        $classementPlace = 1;

        for ($k = 1; $k <= $arrayRound[$j][1]; $k++) {
            $classementMin = null;
            $classementMax = null;

            $wl = ($k % 2 === 0) ? 'Loss_' : 'Win_';
            $splus = (int) ceil($k / 2);
            $mplus = 0;
            $nbMatchsSub = $arrayRound[$j][0] / 2;

            for ($m = 1; $m <= $nbMatchsSub; $m++) {

                // ---- Vérification existence du match ----
                $matchExiste = $realState[$j][$k][$m] ?? true;

                // Calcul systématique du classement pour garder l'incrémentation cohérente
                if ($isDernierRound) {
                    $cMin = $classementPlace++;
                    $cMax = $classementPlace++;
                }

                if (!$matchExiste) {
                    // On ignore complètement ce match (pas d'INSERT)
                    $mplus++; // il faut quand même avancer mplus pour garder l'alignement des sources
                    continue;
                }

                $matchCode = "R{$j}_S{$k}_M{$m}";
                $sourceM1 = $m + $mplus;
                $mplus++;
                $sourceM2 = $m + $mplus;

                if ($j == 0) {
                    $sourceTeam1 = null;
                    $sourceTeam2 = null;
                    $seedA = $ordreSeed[($m - 1) * 2];
                    $seedB = $ordreSeed[($m - 1) * 2 + 1];
                    $equipe1_id = $equipeIds[$seedA];
                    $equipe2_id = $equipeIds[$seedB];
                } else {
                    $sourceTeam1 = "{$wl}R" . ($j - 1) . "_S{$splus}_M{$sourceM1}";
                    $sourceTeam2 = "{$wl}R" . ($j - 1) . "_S{$splus}_M{$sourceM2}";
                    $equipe1_id = null;
                    $equipe2_id = null;
                }

                if ($isDernierRound) {
                    $classementMin = $cMin;
                    $classementMax = $cMax;
                }

                $terrainAssigne = $terraincurrent;
                $heureAssignee = null;

                if ($terrainAssigne !== null && isset($heureParTerrain[$terrainAssigne])) {
                    $heureAssignee = $heureParTerrain[$terrainAssigne];
                    $heureParTerrain[$terrainAssigne] = ajouterMinutesHeure($heureAssignee, $tempsDeMatch);
                }

                // Statut : si round 0 et un des deux est BYE -> déjà "pret" mais on pourrait
                // même le marquer "termine" directement avec le vainqueur auto (à voir selon vos besoins)
                $statut = ($j === 0) ? 'pret' : 'en_attente';

                $stmt->execute([
                    ':id_tournoi' => $idTournoi,
                    ':id_phase_finale' => $idPhaseFinale,
                    ':round' => $j,
                    ':sub_group' => $k,
                    ':match_num' => $m,
                    ':match_code' => $matchCode,
                    ':source_team1' => $sourceTeam1,
                    ':source_team2' => $sourceTeam2,
                    ':equipe1_id' => $equipe1_id,
                    ':equipe2_id' => $equipe2_id,
                    ':classement_min' => $classementMin,
                    ':classement_max' => $classementMax,
                    ':statut' => $statut,
                    ':terrain' => $terrainAssigne,
                    ':heure_debut' => $heureAssignee,
                ]);
                $totalMatchs++;

                if ($terraincurrent !== null) {
                    $terraincurrent++;
                    if ($terraincurrent > $terrainph) $terraincurrent = 1;
                }
            }
        }
    }

    return $totalMatchs;
}

// ============================================================
// Génère l'ordre de seeding standard pour bracket équilibré
// Ex pour 8 : [1,8,4,5,2,7,3,6]
// ============================================================

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

function ajouterMinutesHeure(string $heure, int $minutes): string {
    $dt = DateTime::createFromFormat('H:i:s', strlen($heure) === 5 ? $heure . ':00' : $heure);
    if (!$dt) {
        $dt = DateTime::createFromFormat('H:i', $heure);
    }
    $dt->modify("+{$minutes} minutes");
    return $dt->format('H:i');
}