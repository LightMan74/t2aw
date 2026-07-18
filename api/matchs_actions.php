<?php
header('Content-Type: application/json');
include __DIR__ . "/check_connected.php";
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

            if ($status === 'termine') {
                // recalculerClassementMatch($pdo, $id);
            }

            echo json_encode(['success' => true]);
            break;

        case 'update_matchs_bulk':
            $matchs = $data['matchs'] ?? [];

            if (!is_array($matchs) || count($matchs) === 0) {
                echo json_encode(['success' => false, 'error' => 'Aucun match à mettre à jour']);
                break;
            }

            $pdo->beginTransaction();

            try {
                // Préparer la requête une seule fois (hors boucle) pour la performance
                $stmtBulk = $pdo->prepare("UPDATE match_poule SET terrain=?, status=?, score_equipe_1=?, score_equipe_2=?, heure_debut=?, heure_fin=? WHERE id=?");

                foreach ($matchs as $match) {
                    $terrain   = $match['terrain'] ?? null;
                    $status    = $match['status'] ?? null;
                    $score1    = $match['score_equipe_1'] ?? null;
                    $score2    = $match['score_equipe_2'] ?? null;
                    $hDebut    = $match['heure_debut'] ?? null;
                    $hFin      = $match['heure_fin'] ?? null;
                    $id        = $match['id'] ?? null;

                    $stmtBulk->execute([$terrain, $status, $score1, $score2, $hDebut, $hFin, $id]);

                    if ($status === 'termine') {
                        // recalculerClassementMatch($pdo, $id);
                    }
                }

                $pdo->commit();
                echo json_encode(['success' => true, 'updated' => count($matchs)]);

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e; // laisse remonter au catch global
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Action inconnue']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}