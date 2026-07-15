<?php
// api/phase_finale/classement_equipes.php

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$idTournoi = (int)($_GET['id_tournoi'] ?? 0);

if (!$idTournoi) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'id_tournoi requis']);
    exit;
}

try {
    // 1. Charger toutes les équipes du tournoi
    $stmt = $pdo->prepare("
        SELECT 
            e.id_equipe,
            e.nom,
            e.id_tournoi,
            e.id_categorie,
            e.id_poule
        FROM equipe e
        WHERE e.id_tournoi = :id_tournoi
    ");
    $stmt->execute([':id_tournoi' => $idTournoi]);
    $equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialiser les stats de chaque équipe
    $stats = [];
    foreach ($equipes as $e) {
        $cle = $e['id_categorie'] . '_' . $e['id_poule'] . '_' . $e['id_equipe'];
        $stats[$cle] = [
            'id_equipe' => $e['id_equipe'],
            'id_categorie' => $e['id_categorie'],
            'id_poule' => $e['id_poule'],
            'id_tournoi' => $e['id_tournoi'],
            'nom' => $e['nom'],
            'victoires' => 0,
            'defaites' => 0,
            'points_marques' => 0,
            'points_encaisses' => 0,
            'diff_points' => 0,
            'matchs_joues' => 0,
        ];
    }

    // 2. Charger tous les matchs de poule joués (avec scores)
    $stmt = $pdo->prepare("
        SELECT 
            mp.id_categorie,
            mp.id_poule,
            mp.id_poule_2,
            mp.id_equipe_1,
            mp.id_equipe_2,
            mp.score_equipe_1,
            mp.score_equipe_2
        FROM match_poule mp
        WHERE mp.id_tournoi = :id_tournoi
          AND mp.score_equipe_1 IS NOT NULL
          AND mp.score_equipe_2 IS NOT NULL
    ");
    $stmt->execute([':id_tournoi' => $idTournoi]);
    $matchs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Calculer les stats à partir des matchs joués
    foreach ($matchs as $m) {
        $poule1 = $m['id_poule'];
        $poule2 = $m['id_poule_2'] ?: $m['id_poule'];

        $cle1 = $m['id_categorie'] . '_' . $poule1 . '_' . $m['id_equipe_1'];
        $cle2 = $m['id_categorie'] . '_' . $poule2 . '_' . $m['id_equipe_2'];

        if (!isset($stats[$cle1]) || !isset($stats[$cle2])) {
            continue; // sécurité si données incohérentes
        }

        $score1 = (int)$m['score_1'];
        $score2 = (int)$m['score_2'];

        $stats[$cle1]['matchs_joues']++;
        $stats[$cle2]['matchs_joues']++;

        $stats[$cle1]['points_marques'] += $score1;
        $stats[$cle1]['points_encaisses'] += $score2;

        $stats[$cle2]['points_marques'] += $score2;
        $stats[$cle2]['points_encaisses'] += $score1;

        if ($score1 > $score2) {
            $stats[$cle1]['victoires']++;
            $stats[$cle2]['defaites']++;
        } else {
            $stats[$cle2]['victoires']++;
            $stats[$cle1]['defaites']++;
        }
    }

    // Calcul différence de points
    foreach ($stats as &$s) {
        $s['diff_points'] = $s['points_marques'] - $s['points_encaisses'];
    }
    unset($s);

    // 4. Grouper par poule (catégorie + poule) et trier chaque poule
    $poules = [];
    foreach ($stats as $s) {
        $clePoule = $s['id_categorie'] . '_' . $s['id_poule'];
        if (!isset($poules[$clePoule])) {
            $poules[$clePoule] = [];
        }
        $poules[$clePoule][] = $s;
    }

    // Tri intra-poule : victoires desc, diff_points desc, points_marques desc
    foreach ($poules as &$listeEquipes) {
        usort($listeEquipes, function ($a, $b) {
            if ($a['victoires'] !== $b['victoires']) {
                return $a['victoires'] <=> $b['victoires'];
            }
            if ($a['diff_points'] !== $b['diff_points']) {
                return $a['diff_points'] <=> $b['diff_points'];
            }
            return $a['points_marques'] <=> $b['points_marques'];
        });
        // Ajouter le rang dans la poule
        foreach ($listeEquipes as $idx => &$eq) {
            $eq['rang_poule'] = $idx + 1;
        }
        unset($eq);
    }
    unset($listeEquipes);

    // 5. Construire le classement général façon "cross-poules"
    // Ordre : tous les 1ers (triés par diff_points), puis tous les 2èmes, etc.
    $clesPoulesTriees = array_keys($poules);
    sort($clesPoulesTriees); // tri stable par catégorie/poule

    $maxRang = 0;
    foreach ($poules as $listeEquipes) {
        $maxRang = max($maxRang, count($listeEquipes));
    }

    $classementFinal = [];
    for ($rang = 1; $rang <= $maxRang; $rang++) {
        $equipesDuRang = [];
        foreach ($clesPoulesTriees as $clePoule) {
            foreach ($poules[$clePoule] as $eq) {
                if ($eq['rang_poule'] === $rang) {
                    $equipesDuRang[] = $eq;
                }
            }
        }

        // Trier les équipes de même rang entre elles (par victoires puis diff_points)
        usort($equipesDuRang, function ($a, $b) {
            if ($a['victoires'] !== $b['victoires']) {
                return $a['victoires'] <=> $b['victoires'];
            }
            if ($a['diff_points'] !== $b['diff_points']) {
                return $a['diff_points'] <=> $b['diff_points'];
            }
            return $a['points_marques'] <=> $b['points_marques'];
        });

        foreach ($equipesDuRang as $eq) {
            $classementFinal[] = $eq;
        }
    }

    echo json_encode([
        'success' => true,
        'classement' => $classementFinal
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}