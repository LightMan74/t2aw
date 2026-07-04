<?php
// api/get_classement.php
header('Content-Type: application/json');
require 'db.php';

$id_tournoi = $_GET['id_tournoi'] ?? null;

if (!$id_tournoi) {
    echo json_encode(['success' => false, 'error' => 'id_tournoi manquant']);
    exit;
}

try {
    $sql = "SELECT cl.*, 
                   c.nom AS nom_categorie,
                   p.nom AS nom_poule,
                   e.nom AS nom_equipe
            FROM classement cl
            LEFT JOIN categorie c ON c.id_tournoi = cl.id_tournoi AND c.id_categorie = cl.id_categorie
            LEFT JOIN poule p ON p.id_tournoi = cl.id_tournoi AND p.id_categorie = cl.id_categorie AND p.id_poule = cl.id_poule
            LEFT JOIN equipe e ON e.id_tournoi = cl.id_tournoi AND e.id_categorie = cl.id_categorie AND e.id_poule = cl.id_poule AND e.id_equipe = cl.id_equipe
            WHERE cl.id_tournoi = ?
            ORDER BY cl.id_categorie ASC, cl.id_poule ASC, cl.victoire DESC, (cl.point_marquer - cl.point_encaisser) DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_tournoi]);
    $classement = $stmt->fetchAll();

    echo json_encode(['success' => true, 'classement' => $classement]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
