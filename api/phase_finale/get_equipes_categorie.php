<?php
// api/phase_finale/get_equipes_categorie.php

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$idTournoi   = (int)($_GET['id_tournoi'] ?? 0);
$idCategorie = (int)($_GET['id_categorie'] ?? 0);

if (!$idTournoi || !$idCategorie) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'id_tournoi et id_categorie requis']);
    exit;
}

function estPuissanceDe2($n) {
    return ($n > 0) && (($n & ($n - 1)) == 0);
}

// Départage : victoires, puis diff de sets, puis diff de points, puis points marqués
// Départage : victoires, sets gagnés, diff de points, points marqués, points encaissés
function comparerEquipes($a, $b) {
    if ($a['victoires'] !== $b['victoires']) {
        return $b['victoires'] <=> $a['victoires'];       // plus de matchs gagnés
    }
    if ($a['set_gagner'] !== $b['set_gagner']) {
        return $b['set_gagner'] <=> $a['set_gagner'];     // plus de sets gagnés
    }
    if ($a['diff_points'] !== $b['diff_points']) {
        return $b['diff_points'] <=> $a['diff_points'];   // meilleure diff de points
    }
    if ($a['points_marques'] !== $b['points_marques']) {
        return $b['points_marques'] <=> $a['points_marques']; // plus de points marqués
    }
    return $a['points_encaisses'] <=> $b['points_encaisses']; // moins de points encaissés
}

try {
    // 1. Équipes de la catégorie
    $stmt = $pdo->prepare("
        SELECT id_equipe, nom, id_tournoi, id_categorie, id_poule
        FROM equipe
        WHERE id_tournoi = :id_tournoi AND id_categorie = :id_categorie
        ORDER BY id_poule, nom
    ");
    $stmt->execute([':id_tournoi' => $idTournoi, ':id_categorie' => $idCategorie]);
    $equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stats = [];
    foreach ($equipes as $e) {
        $cle = $e['id_poule'] . '_' . $e['id_equipe'];
        $stats[$cle] = [
            'id_equipe'        => $e['id_equipe'],
            'id_categorie'     => $e['id_categorie'],
            'id_poule'         => $e['id_poule'],
            'id_tournoi'       => $e['id_tournoi'],
            'nom'              => $e['nom'],
            'victoires'        => 0,
            'defaites'         => 0,
            'set_gagner'       => 0,
            'set_perdu'        => 0,
            'diff_sets'        => 0,
            'points_marques'   => 0,
            'points_encaisses' => 0,
            'diff_points'      => 0,
            'matchs_joues'     => 0,
        ];
    }

    // 2. Matchs terminés ou annulés (annulee = termine)
    $stmt = $pdo->prepare("
        SELECT id_poule, id_poule_2,
               id_equipe_1, id_equipe_2, id_equipe_3, id_equipe_4,
               score_equipe_1, score_equipe_2
        FROM match_poule
        WHERE id_tournoi = :id_tournoi
          AND id_categorie = :id_categorie
          AND status IN ('termine', 'annulee')
    ");
    $stmt->execute([':id_tournoi' => $idTournoi, ':id_categorie' => $idCategorie]);
    $matchs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Calcul des stats (même logique que get_classement.php)
    foreach ($matchs as $m) {
        $estSalade = ($m['id_equipe_3'] !== null && $m['id_equipe_3'] !== '');
        $poule2    = $m['id_poule_2'] ?? $m['id_poule'];

        $key1 = $m['id_poule'] . '_' . $m['id_equipe_1'];
        $key2 = $poule2        . '_' . $m['id_equipe_2'];

        if ($estSalade) {
            $key3 = $m['id_poule'] . '_' . $m['id_equipe_3'];
            $key4 = $m['id_poule'] . '_' . $m['id_equipe_4'];
            $cote1 = [$key1, $key2]; // score_equipe_1
            $cote2 = [$key3, $key4]; // score_equipe_2
        } else {
            $cote1 = [$key1];        // score_equipe_1
            $cote2 = [$key2];        // score_equipe_2
        }

        // Vérifier que toutes les équipes existent
        $valide = true;
        foreach (array_merge($cote1, $cote2) as $k) {
            if (!isset($stats[$k])) { $valide = false; break; }
        }
        if (!$valide) continue;

        // Scores par set : "21*15*10"
        $scores1 = explode('*', $m['score_equipe_1'] ?? '0*0*0');
        $scores2 = explode('*', $m['score_equipe_2'] ?? '0*0*0');
        $nbSets  = max(count($scores1), count($scores2));

        $total1 = 0; $total2 = 0;
        $sets1  = 0; $sets2  = 0;

        for ($i = 0; $i < $nbSets; $i++) {
            $s1 = (int)($scores1[$i] ?? 0);
            $s2 = (int)($scores2[$i] ?? 0);

            $total1 += $s1;
            $total2 += $s2;

            if ($s1 > $s2)     $sets1++;
            elseif ($s2 > $s1) $sets2++;
        }

        foreach ($cote1 as $k) {
            $stats[$k]['points_marques']   += $total1;
            $stats[$k]['points_encaisses'] += $total2;
            $stats[$k]['set_gagner']       += $sets1;
            $stats[$k]['set_perdu']        += $sets2;
            $stats[$k]['matchs_joues']++;
            if ($sets1 > $sets2)     $stats[$k]['victoires']++;
            elseif ($sets2 > $sets1) $stats[$k]['defaites']++;
        }

        foreach ($cote2 as $k) {
            $stats[$k]['points_marques']   += $total2;
            $stats[$k]['points_encaisses'] += $total1;
            $stats[$k]['set_gagner']       += $sets2;
            $stats[$k]['set_perdu']        += $sets1;
            $stats[$k]['matchs_joues']++;
            if ($sets2 > $sets1)     $stats[$k]['victoires']++;
            elseif ($sets1 > $sets2) $stats[$k]['defaites']++;
        }
    }

    foreach ($stats as &$s) {
        $s['diff_points'] = $s['points_marques'] - $s['points_encaisses'];
        $s['diff_sets']   = $s['set_gagner'] - $s['set_perdu'];
    }
    unset($s);

    // 4. Grouper par poule et classer dans chaque poule
    $poules = [];
    foreach ($stats as $s) {
        $poules[$s['id_poule']][] = $s;
    }

    foreach ($poules as &$liste) {
        usort($liste, 'comparerEquipes');
        foreach ($liste as $idx => &$eq) {
            $eq['rang_poule'] = $idx + 1;
        }
        unset($eq);
    }
    unset($liste);

    $nombreEquipes = 0;
    $maxRang = 0;
    foreach ($poules as $liste) {
        $nombreEquipes += count($liste);
        $maxRang = max($maxRang, count($liste));
    }

    $clesPoulesTriees = array_keys($poules);
    sort($clesPoulesTriees);

    // 5. Classement final
    $puissanceDe2 = estPuissanceDe2($nombreEquipes);
    $classement = [];

    for ($rang = 1; $rang <= $maxRang; $rang++) {
        $duRang = [];
        foreach ($clesPoulesTriees as $cp) {
            foreach ($poules[$cp] as $eq) {
                if ($eq['rang_poule'] === $rang) {
                    $duRang[] = $eq;
                }
            }
        }

        // Puissance de 2 : pas de tri entre poules (ordre des poules conservé)
        // Sinon : cross-poules, meilleurs 1ers puis meilleurs 2èmes, etc.
        if (!$puissanceDe2) {
            usort($duRang, 'comparerEquipes');
        }

        foreach ($duRang as $eq) {
            $classement[] = $eq;
        }
    }

    echo json_encode([
        'success'    => true,
        'equipes'    => $classement,
        'nb_equipes' => count($classement),
        'nb_poules'  => count($poules),
        'modulo'     => $puissanceDe2,
        'croospoule' => $puissanceDe2 ? 'Pas de tri de rang' : 'Tri par rang'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}