<?php
/**
 * api/sauvegarder_ordre.php
 * ------------------------------------------------------------------
 * Reçoit directement l'array de matchs (générés en JS) et les insère
 * en base via prepared statements.
 *
 * Plus aucune logique de redistribution côté serveur :
 *   - les terrains sont déjà assignés en JS pendant le shuffle ;
 *   - le PHP ne fait QUE valider + insérer.
 *
 * Entrée (POST JSON) :
 *   {
 *     "id_tournoi": <int>,
 *     "matchs": [
 *       {
 *         "id_categorie": <int>,
 *         "id_poule": <int>,
 *         "id_poule_2": <int|null>,
 *         "id_match": <int>,
 *         "terrain": <int|null>,
 *         "id_equipe_1": <int>,
 *         "id_equipe_2": <int>,
 *         "id_equipe_3": <int|null>,   // SALADE uniquement
 *         "id_equipe_4": <int|null>,   // SALADE uniquement
 *         "numero_tour": <int|null>
 *       },
 *       ...
 *     ]
 *   }
 *
 * Sortie (JSON) :
 *   - succès : { success: true, nombre_matchs: N }
 *   - échec  : { success: false, error: "..." }
 */

header('Content-Type: application/json; charset=utf-8');
include __DIR__ . "/check_connected.php";
require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

$id_tournoi = isset($data['id_tournoi']) ? (int)$data['id_tournoi'] : 0;
$matchs     = isset($data['matchs']) && is_array($data['matchs']) ? $data['matchs'] : [];

/* ------------------------------------------------------------------
   VALIDATIONS ESSENTIELLES (id_tournoi, id_categorie, id_poule)
   ------------------------------------------------------------------ */
if ($id_tournoi <= 0) {
    echo json_encode(['success' => false, 'error' => "Identifiant tournoi manquant ou invalide."]);
    exit;
}
if (empty($matchs)) {
    echo json_encode(['success' => false, 'error' => "Aucun match à sauvegarder."]);
    exit;
}

foreach ($matchs as $i => $m) {
    if (!isset($m['id_categorie']) || !isset($m['id_poule'])
        || (int)$m['id_categorie'] <= 0 || (int)$m['id_poule'] <= 0) {
        echo json_encode([
            'success' => false,
            'error'   => "Match #$i invalide : id_categorie et id_poule sont obligatoires."
        ]);
        exit;
    }
}

try {
    $pdo->beginTransaction();

    // 1) Purge des anciens matchs de ce tournoi (régénération propre)
    $stmtDel = $pdo->prepare("DELETE FROM match_poule WHERE id_tournoi = ?");
    $stmtDel->execute([$id_tournoi]);

    // 2) Récupération des paramètres du tournoi (heures, durée, terrain_automatique)
    $stmtParam = $pdo->prepare("SELECT * FROM parametre WHERE id_tournoi = ?");
    $stmtParam->execute([$id_tournoi]);
    $param = $stmtParam->fetch(PDO::FETCH_ASSOC);

    $tempsMatch         = isset($param['temps_de_match'])      ? (int)$param['temps_de_match']     : 15;
    $heureDebut         = $param['heure_debut_poule']          ?? '09:00:00';
    $terrainAutomatique = isset($param['terrain_automatique']) ? ((int)$param['terrain_automatique'] === 1) : false;

    // 3) Prepared statements : 2 versions (standard 2 équipes / SALADE 4 équipes)
    $insertStd = $pdo->prepare(
        "INSERT INTO match_poule
            (id_tournoi, id_categorie, id_poule, id_poule_2, id_match, terrain,
             id_equipe_1, id_equipe_2, status, heure_debut, heure_fin, ordre_affichage)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'planifie', ?, ?, ?)"
    );

    $insertSalade = $pdo->prepare(
        "INSERT INTO match_poule
            (id_tournoi, id_categorie, id_poule, id_poule_2, id_match, terrain,
             id_equipe_1, id_equipe_2, id_equipe_3, id_equipe_4, status, heure_debut, heure_fin, ordre_affichage)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'planifie', ?, ?, ?)"
    );

    // 4) Compteurs et calcul des heures par terrain (terrain déjà fixé par le JS)
    $compteurMatchParPoule = [];
    $heuresParTerrain      = [];
    $dateDebut             = new DateTime($heureDebut);
    $ordreAffichage        = 1;

    // 5) Boucle d'insertion
    foreach ($matchs as $m) {
        $id_categorie = (int)$m['id_categorie'];
        $id_poule     = (int)$m['id_poule'];
        $id_poule_2   = isset($m['id_poule_2']) && $m['id_poule_2'] !== null && $m['id_poule_2'] !== ''
            ? (int)$m['id_poule_2']
            : null;
        $terrain      = isset($m['terrain']) && $m['terrain'] !== null && $m['terrain'] !== ''
            ? (int)$m['terrain']
            : null;

        // Compteur id_match par poule (id_match incrémente par poule)
        $keyPoule = $id_categorie . '_' . $id_poule;
        if (!isset($compteurMatchParPoule[$keyPoule])) {
            $compteurMatchParPoule[$keyPoule] = 1;
        } else {
            $compteurMatchParPoule[$keyPoule]++;
        }
        $id_match = $compteurMatchParPoule[$keyPoule];

        // Calcul des heures par terrain (chaque terrain a son propre compteur horaire)
        $terrainKey = $terrain !== null ? $terrain : 0;
        if (!isset($heuresParTerrain[$terrainKey])) {
            $heuresParTerrain[$terrainKey] = clone $dateDebut;
        }
        $heureDebutMatch = clone $heuresParTerrain[$terrainKey];
        $heureFinMatch   = clone $heureDebutMatch;
        $heureFinMatch->modify("+$tempsMatch minutes");
        $heuresParTerrain[$terrainKey] = clone $heureFinMatch;

        // Le terrain n'est persisté que si terrain_automatique est activé
        $terrainPersiste = $terrainAutomatique ? $terrain : null;

        // Détection SALADE : présence de id_equipe_3 ou id_equipe_4
        $id_equipe_3 = (isset($m['id_equipe_3']) && $m['id_equipe_3'] !== null && $m['id_equipe_3'] !== '')
            ? (int)$m['id_equipe_3']
            : null;
        $id_equipe_4 = (isset($m['id_equipe_4']) && $m['id_equipe_4'] !== null && $m['id_equipe_4'] !== '')
            ? (int)$m['id_equipe_4']
            : null;
        $estSalade = ($id_equipe_3 !== null || $id_equipe_4 !== null);

        if ($estSalade) {
            $insertSalade->execute([
                $id_tournoi,
                $id_categorie,
                $id_poule,
                $id_poule_2,
                $id_match,
                $terrainPersiste,
                (int)$m['id_equipe_1'],
                (int)$m['id_equipe_2'],
                $id_equipe_3,
                $id_equipe_4,
                $heureDebutMatch->format('H:i:s'),
                $heureFinMatch->format('H:i:s'),
                $ordreAffichage
            ]);
        } else {
            $insertStd->execute([
                $id_tournoi,
                $id_categorie,
                $id_poule,
                $id_poule_2,
                $id_match,
                $terrainPersiste,
                (int)$m['id_equipe_1'],
                (int)$m['id_equipe_2'],
                $heureDebutMatch->format('H:i:s'),
                $heureFinMatch->format('H:i:s'),
                $ordreAffichage
            ]);
        }

        $ordreAffichage++;
    }

    $pdo->commit();
    echo json_encode([
        'success'        => true,
        'nombre_matchs'  => count($matchs)
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
