<?php
// api/sauvegarder_ordre.php
header('Content-Type: application/json');
include "api/check_connected.php";
require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

$id_tournoi = $data['id_tournoi'] ?? null;
$matchs = $data['matchs'] ?? [];

if (!$id_tournoi || empty($matchs)) {
    echo json_encode(['success' => false, 'error' => 'Données manquantes']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Récupérer les paramètres du tournoi (terrains, heures, temps de match)
    $stmtParam = $pdo->prepare("SELECT * FROM parametre WHERE id_tournoi = ?");
    $stmtParam->execute([$id_tournoi]);
    $param = $stmtParam->fetch();

    $nbTerrains = $param['nbre_terrain_poule'] ?? 1;
    $tempsMatch = $param['temps_de_match'] ?? 15; // minutes
    $heureDebut = $param['heure_debut_poule'] ?? '09:00:00';

    // Supprimer les anciens matchs de poule pour ce tournoi (regénération)
    $stmtDel = $pdo->prepare("DELETE FROM match_poule WHERE id_tournoi = ?");
    $stmtDel->execute([$id_tournoi]);

    // Compteur d'id_match par poule (id_match doit incrémenter par poule)
    $compteurMatchParPoule = [];

    // Calcul de l'heure de début pour chaque terrain (roulement round-robin par terrain)
    $dateDebut = new DateTime($heureDebut);
    $heuresTerrain = [];
    for ($t = 1; $t <= $nbTerrains; $t++) {
        $heuresTerrain[$t] = clone $dateDebut;
    }

    $insert = $pdo->prepare("INSERT INTO match_poule 
        (id_tournoi, id_categorie, id_poule, id_poule_2, id_match, terrain, id_equipe_1, id_equipe_2, status, heure_debut, heure_fin, ordre_affichage)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'planifie', ?, ?, ?)");

    $ordreAffichage = 1;
    $terrainAutoActuel = 1; // pour l'attribution automatique des matchs sans terrain fixé

    foreach ($matchs as $m) {
        $id_categorie = $m['id_categorie'];
        $id_poule = $m['id_poule'];
        $id_poule_2 = $m['id_poule_2'] ?? null;
        $keyPoule = $id_categorie . '_' . $id_poule;

        if (!isset($compteurMatchParPoule[$keyPoule])) {
            $compteurMatchParPoule[$keyPoule] = 1;
        } else {
            $compteurMatchParPoule[$keyPoule]++;
        }
        $id_match = $compteurMatchParPoule[$keyPoule];

        // --- Détermination du terrain ---
        // Si un terrain a été fixé manuellement (drag&drop), on l'utilise.
        // Sinon on utilise le round-robin automatique.
        $terrainManuel = isset($m['terrain']) && $m['terrain'] !== null && $m['terrain'] !== '' 
            ? (int)$m['terrain'] 
            : null;

        if ($terrainManuel !== null && $terrainManuel >= 1 && $terrainManuel <= $nbTerrains) {
            $terrain = $terrainManuel;
        } else {
            $terrain = $terrainAutoActuel;
            $terrainAutoActuel++;
            if ($terrainAutoActuel > $nbTerrains) $terrainAutoActuel = 1;
        }

        $heureDebutMatch = clone $heuresTerrain[$terrain];
        $heureFinMatch = clone $heureDebutMatch;
        $heureFinMatch->modify("+$tempsMatch minutes");
        $heuresTerrain[$terrain] = clone $heureFinMatch;

        $insert->execute([
            $id_tournoi,
            $id_categorie,
            $id_poule,
            $id_poule_2,
            $id_match,
            $terrain,
            $m['id_equipe_1'],
            $m['id_equipe_2'],
            $heureDebutMatch->format('H:i:s'),
            $heureFinMatch->format('H:i:s'),
            $ordreAffichage
        ]);

        $ordreAffichage++;
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}