<?php
require_once 'db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Génération d'un nouvel id_tournoi (max + 1)
    $stmt = $pdo->query("SELECT COALESCE(MAX(id_tournoi), 0) + 1 AS next_id FROM tournoi");
    $idTournoi = $stmt->fetch(PDO::FETCH_ASSOC)['next_id'];

    // Insertion du tournoi
    $stmt = $pdo->prepare("INSERT INTO tournoi (id_tournoi, nom) VALUES (?, ?)");
    $stmt->execute([$idTournoi, $data['nom_tournoi']]);

    // Insertion des paramètres
    $stmt = $pdo->prepare("
        INSERT INTO parametre 
        (id_tournoi, nbre_terrain_poule, nbre_terrain_phasefinal, temps_ou_set, temps_de_match, heure_debut_poule, heure_debut_phasefinal)
        VALUES (?, ?, ?, 'temps', ?, ?, ?)
    ");
    $stmt->execute([
        $idTournoi,
        $data['nbre_terrain_poule'],
        $data['nbre_terrain_phasefinal'],
        $data['temps_de_match'],
        $data['heure_debut_poule'],
        $data['heure_debut_phasefinal']
    ]);

    // Insertion des catégories, poules, équipes
    $stmtCat = $pdo->prepare("INSERT INTO categorie (id_tournoi, id_categorie, nom) VALUES (?, ?, ?)");
    $stmtPoule = $pdo->prepare("INSERT INTO poule (id_tournoi, id_categorie, id_poule, nom) VALUES (?, ?, ?, ?)");
    $stmtEquipe = $pdo->prepare("INSERT INTO equipe (id_tournoi, id_categorie, id_poule, id_equipe, nom) VALUES (?, ?, ?, ?, ?)");

    foreach ($data['categories'] as $cat) {
        $stmtCat->execute([$idTournoi, $cat['id_categorie'], $cat['nom']]);

        foreach ($cat['poules'] as $poule) {
            $stmtPoule->execute([$idTournoi, $cat['id_categorie'], $poule['id_poule'], $poule['nom']]);

            $idEquipe = 1;
            foreach ($poule['equipes'] as $nomEquipe) {
                $stmtEquipe->execute([$idTournoi, $cat['id_categorie'], $poule['id_poule'], $idEquipe, $nomEquipe]);
                $idEquipe++;
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'id_tournoi' => $idTournoi]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}