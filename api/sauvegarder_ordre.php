<?php
// api/sauvegarder_ordre.php
header('Content-Type: application/json');
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
        (id_tournoi, id_categorie, id_poule, id_match, terrain, id_equipe_1, id_equipe_2, status, heure_debut, heure_fin, ordre_affichage)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'planifie', ?, ?, ?)");

    $ordreAffichage = 1;
    $terrainActuel = 1;

    foreach ($matchs as $m) {
        $id_categorie = $m['id_categorie'];
        $id_poule = $m['id_poule'];
        $keyPoule = $id_categorie . '_' . $id_poule;

        if (!isset($compteurMatchParPoule[$keyPoule])) {
            $compteurMatchParPoule[$keyPoule] = 1;
        } else {
            $compteurMatchParPoule[$keyPoule]++;
        }
        $id_match = $compteurMatchParPoule[$keyPoule];

        // Attribution du terrain en round robin
        $terrain = $terrainActuel;

        $heureDebutMatch = clone $heuresTerrain[$terrain];
        $heureFinMatch = clone $heureDebutMatch;
        $heureFinMatch->modify("+$tempsMatch minutes");
        $heuresTerrain[$terrain] = clone $heureFinMatch;

        $insert->execute([
            $id_tournoi,
            $id_categorie,
            $id_poule,
            $id_match,
            $terrain,
            $m['id_equipe_1'],
            $m['id_equipe_2'],
            $heureDebutMatch->format('H:i:s'),
            $heureFinMatch->format('H:i:s'),
            $ordreAffichage
        ]);

        $ordreAffichage++;
        $terrainActuel++;
        if ($terrainActuel > $nbTerrains) $terrainActuel = 1;
    }

    // Initialiser le classement pour chaque équipe si non existant
    $stmtEquipes = $pdo->prepare("SELECT * FROM equipe WHERE id_tournoi = ?");
    $stmtEquipes->execute([$id_tournoi]);
    $equipes = $stmtEquipes->fetchAll();

    $stmtCheckClassement = $pdo->prepare("SELECT id FROM classement WHERE id_tournoi=? AND id_categorie=? AND id_poule=? AND id_equipe=?");
    $stmtInsertClassement = $pdo->prepare("INSERT INTO classement (id_tournoi, id_categorie, id_poule, id_equipe, victoire, defaite, set_gagner, point_marquer, point_encaisser) VALUES (?, ?, ?, ?, 0, 0, 0, 0, 0)");

    foreach ($equipes as $eq) {
        $stmtCheckClassement->execute([$id_tournoi, $eq['id_categorie'], $eq['id_poule'], $eq['id_equipe']]);
        if (!$stmtCheckClassement->fetch()) {
            $stmtInsertClassement->execute([$id_tournoi, $eq['id_categorie'], $eq['id_poule'], $eq['id_equipe']]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
