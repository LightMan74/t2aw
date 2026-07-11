<?php
require 'db.php';

$id_tournoi = isset($_GET['id_tournoi']) ? (int)$_GET['id_tournoi'] : 0;

if (!$id_tournoi) {
    echo json_encode(['error' => 'id_tournoi manquant']);
    exit;
}

$sql = "SELECT 
            m.*,
            c.nom AS nom_categorie,
            CASE 
                WHEN m.id_poule_2 IS NOT NULL THEN CONCAT(p.nom, ' / ', p2.nom)
                ELSE p.nom 
            END AS nom_poule,
            e1.nom AS nom_equipe_1,
            e2.nom AS nom_equipe_2,
            para.troissets AS troissets
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
$matchs = $stmt->fetchAll();

// Regroupement par statut
$result = [
    'en_cours' => [],
    'a_venir' => [],
    'termines' => []
];

foreach ($matchs as $m) {
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