<?php
// api/get_equipes_tournoi.php
header('Content-Type: application/json');
include "api/check_connected.php";
require 'db.php';

$id_tournoi = $_POST['id_tournoi'] ?? null;

if (!$id_tournoi) {
    echo json_encode(['success' => false, 'error' => 'id_tournoi manquant']);
    exit;
}

try {
    $stmtCat = $pdo->prepare("SELECT * FROM categorie WHERE id_tournoi = ? ORDER BY id_categorie ASC");
    $stmtCat->execute([$id_tournoi]);
    $categories = $stmtCat->fetchAll();

    $result = [];

    foreach ($categories as $cat) {
        $id_categorie = $cat['id_categorie'];

        $stmtPoule = $pdo->prepare("SELECT * FROM poule WHERE id_tournoi = ? AND id_categorie = ? ORDER BY id_poule ASC");
        $stmtPoule->execute([$id_tournoi, $id_categorie]);
        $poules = $stmtPoule->fetchAll();

        $poulesData = [];

        foreach ($poules as $poule) {
            $id_poule = $poule['id_poule'];

            $stmtEquipe = $pdo->prepare("SELECT * FROM equipe WHERE id_tournoi = ? AND id_categorie = ? AND id_poule = ? ORDER BY id_equipe ASC");
            $stmtEquipe->execute([$id_tournoi, $id_categorie, $id_poule]);
            $equipes = $stmtEquipe->fetchAll();

            $poulesData[] = [
                'id_poule' => $id_poule,
                'nom_poule' => $poule['nom'],
                'equipes' => array_map(function($e) {
                    return ['id_equipe' => $e['id_equipe'], 'nom' => $e['nom']];
                }, $equipes)
            ];
        }

        $result[] = [
            'id_categorie' => $id_categorie,
            'nom_categorie' => $cat['nom'],
            'poules' => $poulesData
        ];
    }

    echo json_encode(['success' => true, 'categories' => $result]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}