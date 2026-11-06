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
require __DIR__ . "/db.php";

$data = json_decode(file_get_contents('php://input'), true);

$id_tournoi = isset($data['id_tournoi']) ? (int)$data['id_tournoi'] : 0;
$matchs     = isset($data['matchs']) && is_array($data['matchs']) ? $data['matchs'] : [];

// Trier sur ordre_global fourni sans le réécrire ; les rangs manquants sont
// placés à la fin et les égalités conservent l'ordre initial du payload.
foreach ($matchs as $index => &$match) {
    $match['_ordre_payload'] = $index;
}
unset($match);
usort($matchs, static function ($a, $b) {
    $ordreA = isset($a['ordre_global']) && is_numeric($a['ordre_global'])
        ? (float)$a['ordre_global']
        : PHP_INT_MAX;
    $ordreB = isset($b['ordre_global']) && is_numeric($b['ordre_global'])
        ? (float)$b['ordre_global']
        : PHP_INT_MAX;
    $comparaison = $ordreA <=> $ordreB;
    return $comparaison !== 0 ? $comparaison : ($a['_ordre_payload'] <=> $b['_ordre_payload']);
});
foreach ($matchs as &$match) {
    unset($match['_ordre_payload']);
}
unset($match);

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
    $estTerrainLibre = !empty($m['terrain_libre']);
    if (!$estTerrainLibre && (
        !isset($m['id_categorie'], $m['id_poule']) ||
        (int)$m['id_categorie'] <= 0 ||
        (int)$m['id_poule'] <= 0
    )) {
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

    // 2) Récupération des paramètres du tournoi (heures et durée)
    $stmtParam = $pdo->prepare("SELECT * FROM parametre WHERE id_tournoi = ?");
    $stmtParam->execute([$id_tournoi]);
    $param = $stmtParam->fetch(PDO::FETCH_ASSOC);

    $tempsMatch         = isset($param['temps_de_match'])      ? (int)$param['temps_de_match']     : 15;
    $heureDebut         = $param['heure_debut_poule']          ?? '09:00:00';
    // Les colonnes descriptives sont ajoutées si elles existent dans le schéma.
    // Cela conserve les flags quand la table les supporte, sans rendre l'API
    // incompatible avec les installations plus anciennes.
    $colonnesTable = $pdo->query("SHOW COLUMNS FROM match_poule")->fetchAll(PDO::FETCH_COLUMN);
    $colonnesOptionnelles = array_values(array_intersect(
        ['terrain_libre', 'ajout_manuel', 'num_tour'],
        $colonnesTable
    ));
    $colonnesSupplementaires = $colonnesOptionnelles
        ? ', ' . implode(', ', $colonnesOptionnelles)
        : '';
    $placeholdersSupplementaires = $colonnesOptionnelles
        ? ', ' . implode(', ', array_fill(0, count($colonnesOptionnelles), '?'))
        : '';

    // 3) Prepared statements : 2 versions (standard 2 équipes / SALADE 4 équipes)
    $insertStd = $pdo->prepare(
        "INSERT INTO match_poule
            (id_tournoi, id_categorie, id_poule, id_poule_2, id_match, terrain,
             id_equipe_1, id_equipe_2, status, heure_debut, heure_fin, ordre_affichage{$colonnesSupplementaires})
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'planifie', ?, ?, ?{$placeholdersSupplementaires})"
    );

    $insertSalade = $pdo->prepare(
        "INSERT INTO match_poule
            (id_tournoi, id_categorie, id_poule, id_poule_2, id_match, terrain,
             id_equipe_1, id_equipe_2, id_equipe_3, id_equipe_4, status, heure_debut, heure_fin, ordre_affichage{$colonnesSupplementaires})
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'planifie', ?, ?, ?{$placeholdersSupplementaires})"
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

        // Conserver le numéro fourni par le générateur/DOM ; recalculer seulement
        // pour les anciens clients qui n'envoient aucun numéro de match.
        $keyPoule = $id_categorie . '_' . $id_poule;
        $numeroMatchFourni = $m['num_match_poule'] ?? $m['id_match'] ?? null;
        if ($numeroMatchFourni !== null && $numeroMatchFourni !== '' && is_numeric($numeroMatchFourni)) {
            $id_match = (int)$numeroMatchFourni;
        } else {
            $compteurMatchParPoule[$keyPoule] = ($compteurMatchParPoule[$keyPoule] ?? 0) + 1;
            $id_match = $compteurMatchParPoule[$keyPoule];
        }

        // Calcul des heures par terrain (chaque terrain a son propre compteur horaire)
        $terrainKey = $terrain !== null ? $terrain : 0;
        if (!isset($heuresParTerrain[$terrainKey])) {
            $heuresParTerrain[$terrainKey] = clone $dateDebut;
        }
        $heureDebutMatch = clone $heuresParTerrain[$terrainKey];
        $heureFinMatch   = clone $heureDebutMatch;
        $heureFinMatch->modify("+$tempsMatch minutes");
        $heuresParTerrain[$terrainKey] = clone $heureFinMatch;

        // Le terrain fourni par l'interface est conservé tel quel.
        $terrainPersiste = $terrain;
        $valeursOptionnelles = [];
        foreach ($colonnesOptionnelles as $colonne) {
            if ($colonne === 'terrain_libre') {
                $valeursOptionnelles[] = !empty($m['terrain_libre']) ? 1 : 0;
            } elseif ($colonne === 'ajout_manuel') {
                $valeursOptionnelles[] = !empty($m['ajout_manuel']) ? 1 : 0;
            } elseif ($colonne === 'num_tour') {
                $valeursOptionnelles[] = isset($m['num_tour']) && $m['num_tour'] !== ''
                    ? (int)$m['num_tour']
                    : null;
            }
        }

        // Détection SALADE : présence de id_equipe_3 ou id_equipe_4
        $id_equipe_3 = (isset($m['id_equipe_3']) && $m['id_equipe_3'] !== null && $m['id_equipe_3'] !== '')
            ? (int)$m['id_equipe_3']
            : null;
        $id_equipe_4 = (isset($m['id_equipe_4']) && $m['id_equipe_4'] !== null && $m['id_equipe_4'] !== '')
            ? (int)$m['id_equipe_4']
            : null;
        $estSalade = ($id_equipe_3 !== null || $id_equipe_4 !== null);
        // if(!$m['terrain_libre']){
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
                    $ordreAffichage,
                    ...$valeursOptionnelles
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
                    $ordreAffichage,
                    ...$valeursOptionnelles
                ]);
            }
        // }

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