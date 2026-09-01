<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

$id_tournoi = isset($_GET['id_tournoi']) ? (int)$_GET['id_tournoi'] : 0;
$type_match = isset($_GET['type_match']) ? $_GET['type_match'] : '';
$id_match = isset($_GET['id_match']) ? (int)$_GET['id_match'] : 0;

if (!$id_tournoi || !$id_match || !$type_match) {
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

if ($type_match === 'poule') {

    $sql = "SELECT 
                m.*,
                c.nom AS nom_categorie,
                p.nom AS nom_poule,
                e1.nom AS nom_equipe_1,
                e2.nom AS nom_equipe_2,
                para.troissets AS troissets
            FROM match_poule m
            LEFT JOIN categorie c ON c.id_tournoi = m.id_tournoi AND c.id_categorie = m.id_categorie
            LEFT JOIN poule p ON p.id_tournoi = m.id_tournoi AND p.id_categorie = m.id_categorie AND p.id_poule = m.id_poule
            LEFT JOIN equipe e1 ON e1.id_tournoi = m.id_tournoi 
                                AND e1.id_categorie = m.id_categorie 
                                AND e1.id_poule = m.id_poule 
                                AND e1.id_equipe = m.id_equipe_1
            LEFT JOIN equipe e2 ON e2.id_tournoi = m.id_tournoi 
                                AND e2.id_categorie = m.id_categorie 
                                AND e2.id_poule = COALESCE(m.id_poule_2, m.id_poule) 
                                AND e2.id_equipe = m.id_equipe_2
            LEFT JOIN parametre para ON para.id_tournoi = m.id_tournoi
            WHERE m.id_tournoi = ? AND m.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_tournoi, $id_match]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match) {
        echo json_encode(['error' => 'Match non trouvé']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'type_match' => 'poule',
        'id_match' => $match['id'],
        'terrain' => $match['terrain'],
        'nom_categorie' => $match['nom_categorie'],
        'nom_poule' => $match['nom_poule'],
        'nom_equipe_1' => $match['nom_equipe_1'],
        'nom_equipe_2' => $match['nom_equipe_2'],
        'score_equipe_1' => $match['score_equipe_1'],
        'score_equipe_2' => $match['score_equipe_2'],
        'troissets' => $match['troissets']
    ]);

} elseif ($type_match === 'phase_finale') {

    $sql = "SELECT
                m.*,
                c.nom AS nom_categorie,
                pf.nom AS nom_phase_finale,
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
            WHERE pf.id_tournoi = ? AND m.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_tournoi, $id_match]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match) {
        echo json_encode(['error' => 'Match non trouvé']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'type_match' => 'phase_finale',
        'id_match' => $match['id'],
        'terrain' => $match['terrain'],
        'nom_categorie' => $match['nom_categorie'],
        'nom_poule' => $match['nom_phase_finale'],
        'nom_equipe_1' => $match['nom_equipe_1'] ?: ($match['source_team1'] ?: '?'),
        'nom_equipe_2' => $match['nom_equipe_2'] ?: ($match['source_team2'] ?: '?'),
        'score_equipe_1' => $match['score1'],
        'score_equipe_2' => $match['score2'],
        'troissets' => $match['troissets']
    ]);

} else {
    echo json_encode(['error' => 'Type de match invalide']);
}