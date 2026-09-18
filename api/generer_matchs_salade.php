<?php
// api/generer_matchs_salade.php
header('Content-Type: application/json');
include __DIR__ . "/check_connected.php";
require 'db.php';

$id_tournoi   = $_POST['id_tournoi']   ?? null;
$id_categorie = $_POST['id_categorie'] ?? null;
$id_poule     = $_POST['id_poule']     ?? null;

if (!$id_tournoi || !$id_categorie || !$id_poule) {
    echo json_encode(['success' => false, 'error' => 'id_tournoi, id_categorie et id_poule requis']);
    exit;
}

try {
    // Vérifie catégorie
    $stmtCat = $pdo->prepare("SELECT * FROM categorie WHERE id_tournoi = :t AND id_categorie = :c");
    $stmtCat->execute(['t' => $id_tournoi, 'c' => $id_categorie]);
    $categorie = $stmtCat->fetch();
    if (!$categorie) {
        echo json_encode(['success' => false, 'error' => 'Catégorie introuvable']);
        exit;
    }

    // Vérifie poule
    $stmtPoule = $pdo->prepare("SELECT * FROM poule WHERE id_tournoi = :t AND id_categorie = :c AND id_poule = :p");
    $stmtPoule->execute(['t' => $id_tournoi, 'c' => $id_categorie, 'p' => $id_poule]);
    $poule = $stmtPoule->fetch();
    if (!$poule) {
        echo json_encode(['success' => false, 'error' => 'Poule introuvable']);
        exit;
    }

    // Récupère toutes les équipes de la poule
    $stmtEq = $pdo->prepare("SELECT * FROM equipe WHERE id_poule = :p");
    $stmtEq->execute(['p' => $id_poule]);
    $equipes = $stmtEq->fetchAll(PDO::FETCH_ASSOC);

    if (count($equipes) < 2) {
        echo json_encode(['success' => false, 'error' => 'Pas assez d\'équipes dans cette poule']);
        exit;
    }

    // Mélange aléatoire
    shuffle($equipes);

    // Regroupement par lots de 4
    $matchsFinal = [];
    $numMatchPoule = 1;
    $groupes = array_chunk($equipes, 4);

    foreach ($groupes as $groupe) {
        $e1 = $groupe[0] ?? null;
        $e2 = $groupe[1] ?? null;
        $e3 = $groupe[2] ?? null;
        $e4 = $groupe[3] ?? null;

        $matchsFinal[] = [
            'id_categorie'    => $id_categorie,
            'nom_categorie'   => $categorie['nom_categorie'] ?? $categorie['nom'] ?? '',
            'id_poule'        => $id_poule,
            'nom_poule'       => $poule['nom_poule'] ?? $poule['nom'] ?? 'Salade',
            'id_equipe_1'     => $e1['id_equipe'] ?? null,
            'nom_equipe_1'    => $e1['nom_equipe'] ?? null,
            'id_equipe_2'     => $e2['id_equipe'] ?? null,
            'nom_equipe_2'    => $e2['nom_equipe'] ?? null,
            'id_equipe_3'     => $e3['id_equipe'] ?? null,
            'nom_equipe_3'    => $e3['nom_equipe'] ?? null,
            'id_equipe_4'     => $e4['id_equipe'] ?? null,
            'nom_equipe_4'    => $e4['nom_equipe'] ?? null,
            'num_match_poule' => $numMatchPoule,
            'ajout_manuel'    => false,
            'inter_poule'     => false,
            'terrain_libre'   => false,
            'libelle_match'   => "Match salade $numMatchPoule",
            'terrain'         => null,
        ];
        $numMatchPoule++;
    }

    echo json_encode(['success' => true, 'matchs' => $matchsFinal]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}