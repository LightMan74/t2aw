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

try {
    // 1. Charger les équipes de cette catégorie pour ce tournoi
    $stmt = $pdo->prepare("
        SELECT
            e.id_equipe,
            e.nom,
            e.id_tournoi,
            e.id_categorie,
            e.id_poule
        FROM equipe e
        WHERE e.id_tournoi = :id_tournoi AND e.id_categorie = :id_categorie
        ORDER BY e.id_poule, e.nom
    ");
    $stmt->execute([':id_tournoi' => $idTournoi, ':id_categorie' => $idCategorie]);
    $equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialiser les stats
    $stats = [];
    foreach ($equipes as $e) {
        $cle = $e['id_poule'] . '_' . $e['id_equipe'];
        $stats[$cle] = [
            'id_equipe'    => $e['id_equipe'],
            'id_categorie' => $e['id_categorie'],
            'id_poule'     => $e['id_poule'],
            'id_tournoi'   => $e['id_tournoi'],
            'nom'          => $e['nom'],
            'victoires'    => 0,
            'points_marques'    => 0,
            'points_encaisses'  => 0,
            'diff_points'  => 0,
            'matchs_joues' => 0,
        ];
    }

    // 2. Charger les matchs de poule joués (score non nul)
    $stmt = $pdo->prepare("
        SELECT
            mp.id_poule,
            mp.id_poule_2,
            mp.id_equipe_1,
            mp.id_equipe_2,
            mp.score_equipe_1,
            mp.score_equipe_2
        FROM match_poule mp
        WHERE mp.id_tournoi = :id_tournoi
          AND mp.id_categorie = :id_categorie
          AND mp.score_equipe_1 IS NOT NULL
          AND mp.score_equipe_2 IS NOT NULL
          AND mp.status  = 'termine'
    ");
    $stmt->execute([':id_tournoi' => $idTournoi, ':id_categorie' => $idCategorie]);
    $matchs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Calculer les stats
    foreach ($matchs as $m) {
        $poule1 = $m['id_poule'];
        $poule2 = $m['id_poule_2'] ?: $m['id_poule'];

        $cle1 = $poule1 . '_' . $m['id_equipe_1'];
        $cle2 = $poule2 . '_' . $m['id_equipe_2'];

        if (!isset($stats[$cle1]) || !isset($stats[$cle2])) continue;

        $score1 = (int)$m['score_equipe_1'];
        $score2 = (int)$m['score_equipe_2'];

        $stats[$cle1]['matchs_joues']++;
        $stats[$cle2]['matchs_joues']++;
        $stats[$cle1]['points_marques']   += $score1;
        $stats[$cle1]['points_encaisses']  += $score2;
        $stats[$cle2]['points_marques']   += $score2;
        $stats[$cle2]['points_encaisses']  += $score1;

        if ($score1 > $score2) {
            $stats[$cle1]['victoires']++;
        } elseif ($score1 < $score2) {
            $stats[$cle2]['victoires']++;
        }
    }

    foreach ($stats as &$s) {
        $s['diff_points'] = $s['points_marques'] - $s['points_encaisses'];
    }
    unset($s);

    // 4. Grouper par poule et trier
    $poules = [];
    foreach ($stats as $s) {
        $clePoule = $s['id_poule'];
        if (!isset($poules[$clePoule])) $poules[$clePoule] = [];
        $poules[$clePoule][] = $s;
    }

    foreach ($poules as &$listeEquipes) {
        usort($listeEquipes, function ($a, $b) {
            if ($a['victoires'] !== $b['victoires']) return $b['victoires'] <=> $a['victoires'];
            if ($a['diff_points'] !== $b['diff_points']) return $b['diff_points'] <=> $a['diff_points'];
            return $b['points_marques'] <=> $a['points_marques'];
        });
        foreach ($listeEquipes as $idx => &$eq) {
            $eq['rang_poule'] = $idx + 1;
        }
        unset($eq);
    }
    unset($listeEquipes);

    // 5. Classement cross-poules : tous les 1ers, puis tous les 2èmes...
    $clesPoulesTriees = array_keys($poules);
    sort($clesPoulesTriees);

    $maxRang = 0;
    foreach ($poules as $lp) $maxRang = max($maxRang, count($lp));

    $classement = [];
    for ($rang = 1; $rang <= $maxRang; $rang++) {
        $duRang = [];
        foreach ($clesPoulesTriees as $cp) {
            foreach ($poules[$cp] as $eq) {
                if ($eq['rang_poule'] === $rang) $duRang[] = $eq;
            }
        }
        usort($duRang, function ($a, $b) {
            if ($a['victoires'] !== $b['victoires']) return $b['victoires'] <=> $a['victoires'];
            if ($a['diff_points'] !== $b['diff_points']) return $b['diff_points'] <=> $a['diff_points'];
            return $b['points_marques'] <=> $a['points_marques'];
        });
        foreach ($duRang as $eq) $classement[] = $eq;
    }

    echo json_encode([
        'success' => true,
        'equipes' => $classement,
        'nb_equipes' => count($classement),
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}