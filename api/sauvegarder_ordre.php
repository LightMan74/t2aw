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

    /* -------------------------------------------------------------
       ETAPE 1 : Répartir les matchs par terrain, dans l'ordre où
       ils ont été placés (ordre du tableau JS = ordre voulu sur
       CE terrain précis).
       Les matchs "sans terrain" (file d'attente) sont traités comme
       s'ils étaient sur un terrain automatique en round-robin.
    ------------------------------------------------------------- */
    $matchsParTerrain = []; // [terrain => [match, match, ...]]
    for ($t = 1; $t <= $nbTerrains; $t++) {
        $matchsParTerrain[$t] = [];
    }

    $terrainAutoActuel = 1; // pour l'attribution automatique des matchs sans terrain fixé

    foreach ($matchs as $m) {
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

        $matchsParTerrain[$terrain][] = $m;
    }

    /* -------------------------------------------------------------
       ETAPE 2 : Reconstituer un planning "par tour" en alternant
       terrain 1, terrain 2, ..., terrain N, puis on repart au tour
       suivant. Cela garantit que ordre_affichage (et donc les heures)
       reflètent bien la simultanéité réelle entre terrains.
    ------------------------------------------------------------- */
    $maxMatchsParTerrain = 0;
    foreach ($matchsParTerrain as $liste) {
        $maxMatchsParTerrain = max($maxMatchsParTerrain, count($liste));
    }

    $planningOrdonne = []; // liste finale [ ['terrain' => x, 'match' => m], ... ]
    for ($i = 0; $i < $maxMatchsParTerrain; $i++) {
        for ($t = 1; $t <= $nbTerrains; $t++) {
            if (isset($matchsParTerrain[$t][$i])) {
                $planningOrdonne[] = ['terrain' => $t, 'match' => $matchsParTerrain[$t][$i]];
            }
        }
    }

    /* -------------------------------------------------------------
       ETAPE 3 : Calcul des heures par terrain + insertion
    ------------------------------------------------------------- */
    $dateDebut = new DateTime($heureDebut);
    $heuresTerrain = [];
    for ($t = 1; $t <= $nbTerrains; $t++) {
        $heuresTerrain[$t] = clone $dateDebut;
    }

    $stmt = $pdo->prepare("
            SELECT terrain_automatique
            FROM parametre
            WHERE id_tournoi = :id
        ");
        $stmt->execute(['id' => $id_tournoi]);
        $terrain_automatique = $stmt->fetchAll(PDO::FETCH_ASSOC)[0]['terrain_automatique'];

    $insert = $pdo->prepare("INSERT INTO match_poule 
        (id_tournoi, id_categorie, id_poule, id_poule_2, id_match, terrain, id_equipe_1, id_equipe_2, status, heure_debut, heure_fin, ordre_affichage)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'planifie', ?, ?, ?)");
       
    $ordreAffichage = 1;

    foreach ($planningOrdonne as $item) {
            $terrain = $item['terrain'];
        if ($terrain_automatique){
            $terrainbis = $item['terrain'];
        }else{
            $terrainbis = null;
        }
        $m = $item['match'];

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
            $terrainbis,
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