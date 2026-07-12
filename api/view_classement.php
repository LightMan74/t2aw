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
        // soit comme poule principale (id_poule), soit comme poule 2 (id_poule_2)
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
            $e1 = $m['id_equipe_1'];
            $e2 = $m['id_equipe_2'];

            // Déterminer à quelle poule appartient réellement chaque équipe
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

            $nbSets = min(count($sets1), count($sets2));
            for ($i = 0; $i < $nbSets; $i++) {
                $p1 = (int)$sets1[$i];
                $p2 = (int)$sets2[$i];
                $pointsMarques1 += $p1;
                $pointsMarques2 += $p2;
                if ($p1 > $p2) $setsGagnes1++;
                elseif ($p2 > $p1) $setsGagnes2++;
            }

            $victoireE1 = $setsGagnes1 > $setsGagnes2;
            $victoireE2 = $setsGagnes2 > $setsGagnes1;

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