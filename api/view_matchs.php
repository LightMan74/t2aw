<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db.php';

$id_tournoi = isset($_GET['id_tournoi']) ? (int)$_GET['id_tournoi'] : 0;

if (!$id_tournoi) {
    echo json_encode(['error' => 'id_tournoi manquant']);
    exit;
}

$concatPoule = (DB_DRIVER === 'sqlite') 
    ? "p.nom || ' / ' || p2.nom" 
    : "CONCAT(p.nom, ' / ', p2.nom)";

// --------- Matchs de poule ---------
$sql = "SELECT 
            m.*,
            c.nom AS nom_categorie,
            CASE 
                WHEN m.id_poule_2 IS NOT NULL THEN $concatPoule
                ELSE p.nom 
            END AS nom_poule,
            e1.nom AS nom_equipe_1,
            e2.nom AS nom_equipe_2,
            para.troissets AS troissets,
            'poule' AS type_match
        FROM match_poule m
        LEFT JOIN categorie c ON c.id_tournoi = m.id_tournoi AND c.id_categorie = m.id_categorie
        LEFT JOIN poule p ON p.id_tournoi = m.id_tournoi AND p.id_categorie = m.id_categorie AND p.id_poule = m.id_poule
        LEFT JOIN poule p2 ON p2.id_tournoi = m.id_tournoi AND p2.id_categorie = m.id_categorie AND p2.id_poule = m.id_poule_2
        LEFT JOIN equipe e1 ON e1.id_tournoi = m.id_tournoi 
                            AND e1.id_categorie = m.id_categorie 
                            AND e1.id_poule = m.id_poule 
                            AND e1.id_equipe = m.id_equipe_1
        LEFT JOIN equipe e2 ON e2.id_tournoi = m.id_tournoi 
                            AND e2.id_categorie = m.id_categorie 
                            AND e2.id_poule = COALESCE(m.id_poule_2, m.id_poule) 
                            AND e2.id_equipe = m.id_equipe_2
        LEFT JOIN parametre para ON para.id_tournoi = m.id_tournoi
        WHERE m.id_tournoi = ?
        ORDER BY m.ordre_affichage ASC, m.heure_debut ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_tournoi]);
$matchsPoule = $stmt->fetchAll();

// --------- Matchs de phase finale ---------
$sqlPF = "SELECT
              m.id,
              m.id_phase_finale,
              m.equipe1_id,
              m.equipe2_id,
              m.round,
              m.sub_group,
              m.match_code,
              m.score1,
              m.score2,
              m.winner_equipe_id,
              m.statut AS status,
              m.statut_match,
              m.source_team1,
              m.source_team2,
              m.classement_min,
              m.classement_max,
              m.terrain,
              m.heure_debut,
              c.id_categorie,
              pf.nom AS nom_phase_finale,
              pf.nb_equipes,
              c.nom AS nom_categorie,
              para.troissets AS troissets,
              eq1.nom AS nom_equipe_1,
              eq2.nom AS nom_equipe_2
          FROM matchs_phase_finale m
          JOIN phases_finales pf ON pf.id = m.id_phase_finale
          LEFT JOIN categorie c ON c.id_tournoi = pf.id_tournoi AND c.id_categorie = pf.id_categorie
          LEFT JOIN equipes_phase_finale e1 ON e1.id = m.equipe1_id
          LEFT JOIN equipes_phase_finale e2 ON e2.id = m.equipe2_id
          LEFT JOIN equipe eq1 ON 
              eq1.id_tournoi = e1.id_tournoi
              AND eq1.id_categorie = e1.id_categorie
              AND eq1.id_poule = e1.id_poule
              AND eq1.id_equipe = e1.id_equipe
          LEFT JOIN equipe eq2 ON 
              eq2.id_tournoi = e2.id_tournoi
              AND eq2.id_categorie = e2.id_categorie
              AND eq2.id_poule = e2.id_poule
              AND eq2.id_equipe = e2.id_equipe
          LEFT JOIN parametre para ON para.id_tournoi = pf.id_tournoi
          WHERE pf.id_tournoi = ? AND m.statut <> 'simule'
          ORDER BY m.round ASC, m.sub_group ASC, m.id ASC";
$stmtPF = $pdo->prepare($sqlPF);
$stmtPF->execute([$id_tournoi]);
$matchsPFRaw = $stmtPF->fetchAll();

// Calcul du nombre total de rounds par phase finale (pour déduire le label 1/x, Demi, Finale)
$nbRoundsParPhase = [];
foreach ($matchsPFRaw as $m) {
    $pid = $m['id_phase_finale'];
    if (!isset($nbRoundsParPhase[$pid])) $nbRoundsParPhase[$pid] = [];
    $nbRoundsParPhase[$pid][$m['round']] = true;
}
foreach ($nbRoundsParPhase as $pid => $rounds) {
    $nbRoundsParPhase[$pid] = count($rounds);
}

function labelRoundPhaseFinale($round, $nbRoundsTotal, $class) {
    // round est 0-indexé, dernier round = finale
    $revIdx = $nbRoundsTotal - 1 - $round;
    if ($revIdx === 0 && $class === 1) return ("FINAL");
    if ($revIdx === 0) return ('Place ' . $class . " - " . $class + 1);
    if ($revIdx === 1) return 'Demi-finale';
    if ($revIdx === 2) return 'Quart de finale';
    $den = pow(2, $revIdx);
    return '1/' . $den;
}

$matchsPF = [];
foreach ($matchsPFRaw as $m) {
    $pid = $m['id_phase_finale'];
    $nbRounds = $nbRoundsParPhase[$pid] ?? 1;
    $labelRound = labelRoundPhaseFinale((int)$m['round'], $nbRounds, $m['classement_min']);

    $matchsPF[] = [
        'id_tournoi'        => $id_tournoi,
        'id_categorie'      => $m['id_categorie'],
        'nom_categorie'     => $m['nom_categorie'],
        'nom_poule'         => $labelRound,
        'nom_phase_finale'  => $m['nom_phase_finale'],
        'label_round'       => $labelRound,
        'id_poule'          => null,
        'id_poule_2'        => null,
        'nom_equipe_1'      => $m['nom_equipe_1'] ?: ($m['source_team1'] ?: '?'),
        'nom_equipe_2'      => $m['nom_equipe_2'] ?: ($m['source_team2'] ?: '?'),
        'score_equipe_1'    => $m['score1'],
        'score_equipe_2'    => $m['score2'],
        'classement_min'    => $m['classement_min'],
        'classement_max'    => $m['classement_max'],
        'status'            => $m['status'],
        'terrain'           => $m['terrain'],
        'heure_debut'       => $m['heure_debut'],
        'troissets'         => $m['troissets'],
        'type_match'        => 'phase_finale',
    ];
}

// --------- Fusion et regroupement par statut ---------
$result = [
    'en_cours' => [],
    'a_venir'  => [],
    'termines' => []
];

foreach ($matchsPoule as $m) {
    $m['type_match'] = 'poule';
    if ($m['status'] === 'en_cours') {
        $result['en_cours'][] = $m;
    } elseif ($m['status'] === 'termine') {
        $result['termines'][] = $m;
    } else {
        $result['a_venir'][] = $m;
    }
}

foreach ($matchsPF as $m) {
    if ($m['status'] === 'en_cours') {
        $result['en_cours'][] = $m;
    } elseif ($m['status'] === 'termine') {
        $result['termines'][] = $m;
    } else {
        $result['a_venir'][] = $m;
    }
}

// Les derniers terminés en premier (les plus récents)
$result['termines'] = array_reverse($result['termines']);

echo json_encode($result);