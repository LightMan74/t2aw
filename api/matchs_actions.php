<?php
// api/matchs_actions.php
header('Content-Type: application/json');
include "api/check_connected.php";
require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? null;

try {
    switch ($action) {

        case 'update_match':
            $id = $data['id'];
            $terrain = $data['terrain'];
            $status = $data['status'];
            $score1 = $data['score_equipe_1'];
            $score2 = $data['score_equipe_2'];
            $heure_debut = $data['heure_debut'] ?? null;
            $heure_fin = $data['heure_fin'] ?? null;

            $stmt = $pdo->prepare("UPDATE match_poule SET terrain=?, status=?, score_equipe_1=?, score_equipe_2=?, heure_debut=?, heure_fin=? WHERE id=?");
            $stmt->execute([$terrain, $status, $score1, $score2, $heure_debut, $heure_fin, $id]);

            // Si le match passe à "termine", recalculer le classement
            if ($status === 'termine') {
                // recalculerClassementMatch($pdo, $id);
            }

            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Action inconnue']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function recalculerClassementMatch($pdo, $id_match) {
    // Récupérer le match
    $stmt = $pdo->prepare("SELECT * FROM match_poule WHERE id = ?");
    $stmt->execute([$id_match]);
    $match = $stmt->fetch();

    if (!$match) return;

    $id_tournoi = $match['id_tournoi'];
    $id_categorie = $match['id_categorie'];
    $id_poule = $match['id_poule'];

    // Recalcul complet du classement de la poule à partir de tous les matchs terminés
    $stmt = $pdo->prepare("SELECT * FROM match_poule WHERE id_tournoi=? AND id_categorie=? AND id_poule=? AND status='termine'");
    $stmt->execute([$id_tournoi, $id_categorie, $id_poule]);
    $matchsTermines = $stmt->fetchAll();

    // Reset classement de la poule
    $stmt = $pdo->prepare("UPDATE classement SET victoire=0, defaite=0, set_gagner=0, point_marquer=0, point_encaisser=0 
                            WHERE id_tournoi=? AND id_categorie=? AND id_poule=?");
    $stmt->execute([$id_tournoi, $id_categorie, $id_poule]);

    foreach ($matchsTermines as $m) {
        $s1 = $m['score_equipe_1'];
        $s2 = $m['score_equipe_2'];

        // Equipe 1
        $stmtGet = $pdo->prepare("SELECT * FROM classement WHERE id_tournoi=? AND id_categorie=? AND id_poule=? AND id_equipe=?");
        $stmtGet->execute([$id_tournoi, $id_categorie, $id_poule, $m['id_equipe_1']]);
        $c1 = $stmtGet->fetch();

        $stmtGet->execute([$id_tournoi, $id_categorie, $id_poule, $m['id_equipe_2']]);
        $c2 = $stmtGet->fetch();

        if (!$c1 || !$c2) continue;

        $victoire1 = $s1 > $s2 ? 1 : 0;
        $victoire2 = $s2 > $s1 ? 1 : 0;

        $stmtUpdate = $pdo->prepare("UPDATE classement SET 
            victoire = victoire + ?, 
            defaite = defaite + ?, 
            set_gagner = set_gagner + ?, 
            point_marquer = point_marquer + ?, 
            point_encaisser = point_encaisser + ? 
            WHERE id = ?");

        // Equipe 1
        $stmtUpdate->execute([$victoire1, $victoire1 ? 0 : 1, $victoire1, $s1, $s2, $c1['id']]);
        // Equipe 2
        $stmtUpdate->execute([$victoire2, $victoire2 ? 0 : 1, $victoire2, $s2, $s1, $c2['id']]);
    }
}