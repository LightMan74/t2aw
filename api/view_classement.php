<?php
require 'db.php';

$id_tournoi = isset($_GET['id_tournoi']) ? (int)$_GET['id_tournoi'] : 0;

if (!$id_tournoi) {
    echo json_encode(['error' => 'id_tournoi manquant']);
    exit;
}

// Récupérer toutes les catégories du tournoi
$stmtCat = $pdo->prepare("SELECT * FROM categorie WHERE id_tournoi = ?");
$stmtCat->execute([$id_tournoi]);
$categories = $stmtCat->fetchAll();

$classementFinal = [];

foreach ($categories as $cat) {
    $id_categorie = $cat['id_categorie'];

    // Récupérer les poules de cette catégorie
    $stmtPoule = $pdo->prepare("SELECT * FROM poule WHERE id_tournoi = ? AND id_categorie = ?");
    $stmtPoule->execute([$id_tournoi, $id_categorie]);
    $poules = $stmtPoule->fetchAll();

    $poulesData = [];

    foreach ($poules as $poule) {
        $id_poule = $poule['id_poule'];

        // Récupérer les équipes de cette poule
        $stmtEq = $pdo->prepare("SELECT * FROM equipe WHERE id_tournoi = ? AND id_categorie = ? AND id_poule = ?");
        $stmtEq->execute([$id_tournoi, $id_categorie, $id_poule]);
        $equipes = $stmtEq->fetchAll();

        // Initialiser les stats
        $stats = [];
        foreach ($equipes as $eq) {
            $stats[$eq['id_equipe']] = [
                'nom' => $eq['nom'],
                'id_equipe' => $eq['id_equipe'],
                'joues' => 0,
                'victoires' => 0,
                'defaites' => 0,
                'sets_gagnes' => 0,
                'sets_perdus' => 0,
                'points_marques' => 0,
                'points_encaisses' => 0,
            ];
        }

        // Récupérer les matchs terminés où cette poule est impliquée
        $stmtM = $pdo->prepare("
            SELECT * FROM match_poule 
            WHERE id_tournoi = ? 
              AND id_categorie = ? 
              AND (id_poule = ? OR id_poule_2 = ?) 
              AND status = 'termine'
        ");
        $stmtM->execute([$id_tournoi, $id_categorie, $id_poule, $id_poule]);
        $matchs = $stmtM->fetchAll();

        foreach ($matchs as $m) {
            // Détecter si c'est un match SALADE (4 équipes) ou STANDARD (2 équipes)
            $estSalade = ($m['id_equipe_3'] !== null && $m['id_equipe_3'] !== '');

            if ($estSalade) {
                // ============ MATCH SALADE (4 équipes) ============
                $e1 = $m['id_equipe_1'];
                $e2 = $m['id_equipe_2'];
                $e3 = $m['id_equipe_3'];
                $e4 = $m['id_equipe_4'];

                $poule_e1 = $m['id_poule'];
                $poule_e2 = $m['id_poule_2'] ?? $m['id_poule'];
                $poule_e3 = $m['id_poule'];
                $poule_e4 = $m['id_poule'];

                // Vérifier quelles équipes appartiennent à cette poule
                $e1_dans_cette_poule = ($poule_e1 == $id_poule) && isset($stats[$e1]);
                $e2_dans_cette_poule = ($poule_e2 == $id_poule) && isset($stats[$e2]);
                $e3_dans_cette_poule = ($poule_e3 == $id_poule) && isset($stats[$e3]);
                $e4_dans_cette_poule = ($poule_e4 == $id_poule) && isset($stats[$e4]);

                // Si aucune équipe n'appartient à cette poule, on ignore
                if (!$e1_dans_cette_poule && !$e2_dans_cette_poule && 
                    !$e3_dans_cette_poule && !$e4_dans_cette_poule) {
                    continue;
                }

                // Split des scores
                // Format : score_equipe_1 = équipes 1&2, score_equipe_2 = équipes 3&4
                $sets1 = explode('*', $m['score_equipe_1'] ?? '0*0*0');
                $sets2 = explode('*', $m['score_equipe_2'] ?? '0*0*0');

                $setsGagnes_12 = 0;
                $setsGagnes_34 = 0;
                $pointsMarques_12 = 0;
                $pointsMarques_34 = 0;

                $nbSets = max(count($sets1), count($sets2));
                for ($i = 0; $i < $nbSets; $i++) {
                    $p1 = (int)($sets1[$i] ?? 0);
                    $p2 = (int)($sets2[$i] ?? 0);

                    $pointsMarques_12 += $p1;
                    $pointsMarques_34 += $p2;

                    if ($p1 > $p2) {
                        $setsGagnes_12++;
                    } elseif ($p2 > $p1) {
                        $setsGagnes_34++;
                    }
                }

                $victoire_12 = $pointsMarques_12 > $pointsMarques_34;
                $victoire_34 = $pointsMarques_34 > $pointsMarques_12;

                // Mise à jour des stats pour les équipes 1&2
                if ($e1_dans_cette_poule) {
                    $stats[$e1]['joues']++;
                    $stats[$e1]['sets_gagnes'] += $setsGagnes_12;
                    $stats[$e1]['sets_perdus'] += $setsGagnes_34;
                    $stats[$e1]['points_marques'] += $pointsMarques_12;
                    $stats[$e1]['points_encaisses'] += $pointsMarques_34;
                    if ($victoire_12) $stats[$e1]['victoires']++;
                    elseif ($victoire_34) $stats[$e1]['defaites']++;
                }

                if ($e2_dans_cette_poule) {
                    $stats[$e2]['joues']++;
                    $stats[$e2]['sets_gagnes'] += $setsGagnes_12;
                    $stats[$e2]['sets_perdus'] += $setsGagnes_34;
                    $stats[$e2]['points_marques'] += $pointsMarques_12;
                    $stats[$e2]['points_encaisses'] += $pointsMarques_34;
                    if ($victoire_12) $stats[$e2]['victoires']++;
                    elseif ($victoire_34) $stats[$e2]['defaites']++;
                }

                // Mise à jour des stats pour les équipes 3&4
                if ($e3_dans_cette_poule) {
                    $stats[$e3]['joues']++;
                    $stats[$e3]['sets_gagnes'] += $setsGagnes_34;
                    $stats[$e3]['sets_perdus'] += $setsGagnes_12;
                    $stats[$e3]['points_marques'] += $pointsMarques_34;
                    $stats[$e3]['points_encaisses'] += $pointsMarques_12;
                    if ($victoire_34) $stats[$e3]['victoires']++;
                    elseif ($victoire_12) $stats[$e3]['defaites']++;
                }

                if ($e4_dans_cette_poule) {
                    $stats[$e4]['joues']++;
                    $stats[$e4]['sets_gagnes'] += $setsGagnes_34;
                    $stats[$e4]['sets_perdus'] += $setsGagnes_12;
                    $stats[$e4]['points_marques'] += $pointsMarques_34;
                    $stats[$e4]['points_encaisses'] += $pointsMarques_12;
                    if ($victoire_34) $stats[$e4]['victoires']++;
                    elseif ($victoire_12) $stats[$e4]['defaites']++;
                }

            } else {
                // ============ MATCH STANDARD (2 équipes) ============
                $e1 = $m['id_equipe_1'];
                $e2 = $m['id_equipe_2'];

                $poule_e1 = $m['id_poule'];
                $poule_e2 = $m['id_poule_2'] ?? $m['id_poule'];

                // On ne traite l'équipe que si elle appartient à CETTE poule
                $e1_dans_cette_poule = ($poule_e1 == $id_poule) && isset($stats[$e1]);
                $e2_dans_cette_poule = ($poule_e2 == $id_poule) && isset($stats[$e2]);

                // Si aucune des deux équipes n'appartient à cette poule, on ignore
                if (!$e1_dans_cette_poule && !$e2_dans_cette_poule) continue;

                $sets1 = explode('*', $m['score_equipe_1']);
                $sets2 = explode('*', $m['score_equipe_2']);

                $setsGagnes1 = 0;
                $setsGagnes2 = 0;
                $pointsMarques1 = 0;
                $pointsMarques2 = 0;

                $nbSets = max(count($sets1), count($sets2));
                for ($i = 0; $i < $nbSets; $i++) {
                    $p1 = (int)($sets1[$i] ?? 0);
                    $p2 = (int)($sets2[$i] ?? 0);
                    $pointsMarques1 += $p1;
                    $pointsMarques2 += $p2;
                    if ($p1 > $p2) $setsGagnes1++;
                    elseif ($p2 > $p1) $setsGagnes2++;
                }

                $victoireE1 = $pointsMarques1 > $pointsMarques2;
                $victoireE2 = $pointsMarques2 > $pointsMarques1;

                // Mise à jour des stats uniquement pour l'équipe qui appartient à cette poule
                if ($e1_dans_cette_poule) {
                    $stats[$e1]['joues']++;
                    $stats[$e1]['sets_gagnes'] += $setsGagnes1;
                    $stats[$e1]['sets_perdus'] += $setsGagnes2;
                    $stats[$e1]['points_marques'] += $pointsMarques1;
                    $stats[$e1]['points_encaisses'] += $pointsMarques2;
                    if ($victoireE1) $stats[$e1]['victoires']++;
                    elseif ($victoireE2) $stats[$e1]['defaites']++;
                }

                if ($e2_dans_cette_poule) {
                    $stats[$e2]['joues']++;
                    $stats[$e2]['sets_gagnes'] += $setsGagnes2;
                    $stats[$e2]['sets_perdus'] += $setsGagnes1;
                    $stats[$e2]['points_marques'] += $pointsMarques2;
                    $stats[$e2]['points_encaisses'] += $pointsMarques1;
                    if ($victoireE2) $stats[$e2]['victoires']++;
                    elseif ($victoireE1) $stats[$e2]['defaites']++;
                }
            }
        }

        // Tri : victoires desc, diff sets desc, diff points desc
        $classement = array_values($stats);
        usort($classement, function ($a, $b) {
            if ($a['victoires'] !== $b['victoires']) {
                return $b['victoires'] - $a['victoires'];
            }
            $diffSetsA = $a['sets_gagnes'] - $a['sets_perdus'];
            $diffSetsB = $b['sets_gagnes'] - $b['sets_perdus'];
            if ($diffSetsA !== $diffSetsB) {
                return $diffSetsB - $diffSetsA;
            }
            $diffPtsA = $a['points_marques'] - $a['points_encaisses'];
            $diffPtsB = $b['points_marques'] - $b['points_encaisses'];
            return $diffPtsB - $diffPtsA;
        });

        $poulesData[] = [
            'nom_poule' => $poule['nom'],
            'id_poule' => $id_poule,
            'classement' => $classement
        ];
    }

    $classementFinal[] = [
        'nom_categorie' => $cat['nom'],
        'id_categorie' => $id_categorie,
        'poules' => $poulesData
    ];
}

echo json_encode(['categories' => $classementFinal]);
?>