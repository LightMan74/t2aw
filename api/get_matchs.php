<?php
// api/get_matchs.php
header('Content-Type: application/json');
require 'db.php';

$id_tournoi = $_GET['id_tournoi'] ?? null;

if (!$id_tournoi) {
    echo json_encode(['success' => false, 'error' => 'id_tournoi manquant']);
    exit;
}

try {
    $sql = "SELECT mp.*, 
                   c.nom AS nom_categorie, 
                   p.nom AS nom_poule,
                   e1.nom AS nom_equipe_1,
                   e2.nom AS nom_equipe_2
            FROM match_poule mp
            LEFT JOIN categorie c ON c.id_tournoi = mp.id_tournoi AND c.id_categorie = mp.id_categorie
            LEFT JOIN poule p ON p.id_tournoi = mp.id_tournoi AND p.id_categorie = mp.id_categorie AND p.id_poule = mp.id_poule
            LEFT JOIN equipe e1 ON e1.id_tournoi = mp.id_tournoi AND e1.id_categorie = mp.id_categorie AND e1.id_poule = mp.id_poule AND e1.id_equipe = mp.id_equipe_1
            LEFT JOIN equipe e2 ON e2.id_tournoi = mp.id_tournoi AND e2.id_categorie = mp.id_categorie AND e2.id_poule = mp.id_poule AND e2.id_equipe = mp.id_equipe_2
            WHERE mp.id_tournoi = ?
            ORDER BY mp.ordre_affichage ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_tournoi]);
    $matchs = $stmt->fetchAll();

    echo json_encode(['success' => true, 'matchs' => $matchs]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
