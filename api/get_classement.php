<?php
// api/get_classement.php
header('Content-Type: application/json');
include "api/check_connected.php";
require 'db.php';

$id_tournoi = $_GET['id_tournoi'] ?? null;

if (!$id_tournoi) {
    echo json_encode(['success' => false, 'error' => 'id_tournoi manquant']);
    exit;
}

try {
    // Récupérer tous les matchs terminés du tournoi avec les noms
    $stmt = $pdo->prepare("
        SELECT mp.*, 
               cat.nom AS nom_categorie,
               p.nom AS nom_poule,
               p2.nom AS nom_poule_2,
               e1.nom AS nom_equipe_1,
               e2.nom AS nom_equipe_2
        FROM match_poule mp
        JOIN categorie cat ON cat.id_tournoi = mp.id_tournoi AND cat.id_categorie = mp.id_categorie
        JOIN poule p ON p.id_tournoi = mp.id_tournoi AND p.id_categorie = mp.id_categorie AND p.id_poule = mp.id_poule
        LEFT JOIN poule p2 ON p2.id_tournoi = mp.id_tournoi AND p2.id_categorie = mp.id_categorie AND p2.id_poule = mp.id_poule_2
        JOIN equipe e1 ON e1.id_tournoi = mp.id_tournoi 
                       AND e1.id_categorie = mp.id_categorie 
                       AND e1.id_poule = mp.id_poule 
                       AND e1.id_equipe = mp.id_equipe_1
        JOIN equipe e2 ON e2.id_tournoi = mp.id_tournoi 
                       AND e2.id_categorie = mp.id_categorie 
                       AND e2.id_poule = COALESCE(mp.id_poule_2, mp.id_poule) 
                       AND e2.id_equipe = mp.id_equipe_2
        WHERE mp.id_tournoi = ?
        ORDER BY cat.id_categorie, p.id_poule, mp.id_match
    ");
    $stmt->execute([$id_tournoi]);
    $matchs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer toutes les équipes du tournoi (pour les inclure même sans match joué)
    $stmtEquipes = $pdo->prepare("
        SELECT e.*, cat.nom AS nom_categorie, p.nom AS nom_poule
        FROM equipe e
        JOIN categorie cat ON cat.id_tournoi = e.id_tournoi AND cat.id_categorie = e.id_categorie
        JOIN poule p ON p.id_tournoi = e.id_tournoi AND p.id_categorie = e.id_categorie AND p.id_poule = e.id_poule
        WHERE e.id_tournoi = ?
        ORDER BY e.id_equipe ASC
    ");
    $stmtEquipes->execute([$id_tournoi]);
    $equipes = $stmtEquipes->fetchAll(PDO::FETCH_ASSOC);

    // Initialiser le classement pour toutes les équipes
    $classement = [];
    foreach ($equipes as $eq) {
        $key = $eq['id_categorie'] . '_' . $eq['id_poule'] . '_' . $eq['id_equipe'];
        $classement[$key] = [
            'id_categorie'   => $eq['id_categorie'],
            'id_poule'       => $eq['id_poule'],
            'id_equipe'      => $eq['id_equipe'],
            'nom_categorie'  => $eq['nom_categorie'],
            'nom_poule'      => $eq['nom_poule'],
            'nom_equipe'     => $eq['nom'],
            'victoire'       => 0,
            'defaite'        => 0,
            'set_gagner'     => 0,
            'set_perdu'      => 0,
            'point_marquer'  => 0,
            'point_encaisser'=> 0,
            'matchs_joues'   => 0
        ];
    }

    // Parcourir les matchs terminés pour calculer les stats
    foreach ($matchs as $m) {
        if ($m['status'] !== 'termine') continue;

        // La poule de l'équipe 2 dépend de id_poule_2 si renseigné
        $poule_equipe_2 = $m['id_poule_2'] ?? $m['id_poule'];

        $key1 = $m['id_categorie'] . '_' . $m['id_poule'] . '_' . $m['id_equipe_1'];
        $key2 = $m['id_categorie'] . '_' . $poule_equipe_2 . '_' . $m['id_equipe_2'];

        if (!isset($classement[$key1]) || !isset($classement[$key2])) continue;

        // Split des scores par set
        $sets1 = explode('*', $m['score_equipe_1'] ?? '0*0*0');
        $sets2 = explode('*', $m['score_equipe_2'] ?? '0*0*0');

        $nbSets = max(count($sets1), count($sets2));

        $totalScore1 = 0;
        $totalScore2 = 0;
        $setsGagnesEquipe1 = 0;
        $setsGagnesEquipe2 = 0;

        for ($i = 0; $i < $nbSets; $i++) {
            $s1 = (int)($sets1[$i] ?? 0);
            $s2 = (int)($sets2[$i] ?? 0);

            $totalScore1 += $s1;
            $totalScore2 += $s2;

            if ($s1 > $s2) {
                $setsGagnesEquipe1++;
            } elseif ($s2 > $s1) {
                $setsGagnesEquipe2++;
            }
        }

        $classement[$key1]['point_marquer']   += $totalScore1;
        $classement[$key1]['point_encaisser'] += $totalScore2;
        $classement[$key2]['point_marquer']   += $totalScore2;
        $classement[$key2]['point_encaisser'] += $totalScore1;

        $classement[$key1]['matchs_joues']++;
        $classement[$key2]['matchs_joues']++;

        $classement[$key1]['set_gagner'] += $setsGagnesEquipe1;
        $classement[$key1]['set_perdu']  += $setsGagnesEquipe2;
        $classement[$key2]['set_gagner'] += $setsGagnesEquipe2;
        $classement[$key2]['set_perdu']  += $setsGagnesEquipe1;

        // Détermination du vainqueur du match selon le nombre de sets gagnés
        if ($totalScore1 > $totalScore2) {
            $classement[$key1]['victoire']++;
            $classement[$key2]['defaite']++;
        } elseif ($totalScore2 > $totalScore1) {
            $classement[$key2]['victoire']++;
            $classement[$key1]['defaite']++;
        }
    }

    // Convertir en tableau simple
    $result = array_values($classement);

    echo json_encode(['success' => true, 'classement' => $result]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}