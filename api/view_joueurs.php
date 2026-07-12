<?php
require 'db.php';

$id_tournoi = isset($_GET['id_tournoi']) ? (int)$_GET['id_tournoi'] : 0;

if (!$id_tournoi) {
    echo json_encode(['error' => 'id_tournoi manquant']);
    exit;
}

$sql = "SELECT 
            e.*,
            e.id_categorie,
            e.id_poule,
            c.nom AS nom_categorie,
            p.nom AS nom_poule
        FROM equipe e
        LEFT JOIN categorie c ON c.id_tournoi = e.id_tournoi AND c.id_categorie = e.id_categorie
        LEFT JOIN poule p ON p.id_tournoi = e.id_tournoi AND p.id_categorie = e.id_categorie AND p.id_poule = e.id_poule
        WHERE e.id_tournoi = ?
        ORDER BY c.nom ASC, p.nom ASC, e.nom ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_tournoi]);
$joueurs = $stmt->fetchAll();

echo json_encode(['joueurs' => $joueurs]);
