<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$id_tournoi   = isset($data['id_tournoi']) ? (int)$data['id_tournoi'] : 0;
$type_match   = isset($data['type_match']) ? $data['type_match'] : '';
$id_match     = isset($data['id_match']) ? (int)$data['id_match'] : 0;
$score1       = isset($data['score_equipe_1']) ? $data['score_equipe_1'] : '';
$score2       = isset($data['score_equipe_2']) ? $data['score_equipe_2'] : '';
$statut       = isset($data['statut']) ? $data['statut'] : null; // optionnel : 'en_cours' / 'termine'

if (!$id_tournoi || !$id_match || !$type_match) {
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

try {

    if ($type_match === 'poule') {

        if ($statut !== null) {
            $sql = "UPDATE match_poule 
                    SET score_equipe_1 = ?, score_equipe_2 = ?, status = ?
                    WHERE id_tournoi = ? AND id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$score1, $score2, $statut, $id_tournoi, $id_match]);
        } else {
            $sql = "UPDATE match_poule 
                    SET score_equipe_1 = ?, score_equipe_2 = ?
                    WHERE id_tournoi = ? AND id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$score1, $score2, $id_tournoi, $id_match]);
        }

    } elseif ($type_match === 'phase_finale') {

        if ($statut !== null) {
            $sql = "UPDATE matchs_phase_finale 
                    SET score1 = ?, score2 = ?, statut_match = ?
                    WHERE id_tournoi = ? AND id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$score1, $score2, $statut, $id_tournoi, $id_match]);
        } else {
            $sql = "UPDATE matchs_phase_finale 
                    SET score1 = ?, score2 = ?
                    WHERE id_tournoi = ? AND id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$score1, $score2, $id_tournoi, $id_match]);
        }

    } else {
        echo json_encode(['error' => 'Type de match invalide']);
        exit;
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}