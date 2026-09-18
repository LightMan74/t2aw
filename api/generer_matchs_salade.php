<?php
/**
 * api/generer_matchs_salade.php
 * --------------------------------------------------------------
 * Génération de tous les tours de matchs SALADE pour une poule.
 *
 * Type de tournoi SALADE :
 *   - Chaque match regroupe 4 équipes (au lieu de 2).
 *   - Le nombre de tours est : nombre_tours = floor((n-1)/2),
 *     où n est le nombre d'équipes dans la poule.
 *   - Tirage 100% aléatoire à chaque tour (shuffle), indépendant
 *     des tours précédents (pas d'anti-répétition).
 *   - Si n n'est pas un multiple de 4, le DERNIER groupe du tour
 *     peut être incomplet (1, 2 ou 3 équipes) : on crée alors un
 *     match "réduit" en laissant id_equipe_3 et/ou id_equipe_4 à
 *     NULL.
 *
 * IMPORTANT :
 *   Les id_poule / id_categorie repartent à 1 pour chaque tournoi
 *   (et id_poule repart à 1 pour chaque catégorie). Il est donc
 *   INDISPENSABLE de recevoir les 3 identifiants en POST pour
 *   cibler la bonne poule sans ambiguïté :
 *       id_tournoi, id_categorie, id_poule
 *
 * Entrée (POST) :
 *   - id_tournoi   : identifiant du tournoi
 *   - id_categorie : identifiant de la catégorie (dans ce tournoi)
 *   - id_poule     : identifiant de la poule (dans cette catégorie)
 *
 * Sortie (JSON) :
 *   - succès : { success: true, id_poule, id_categorie, id_tournoi,
 *                nombre_equipes, nombre_tours, nombre_matchs, message }
 *   - échec  : { success: false, error: "..." }
 * --------------------------------------------------------------
 */

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/db.php';

// ---------- Lecture des paramètres POST ----------
$id_tournoi   = isset($_POST['id_tournoi'])   ? trim((string) $_POST['id_tournoi']) : '';
$id_categorie = isset($_POST['id_categorie']) ? (int) $_POST['id_categorie']        : 0;
$id_poule     = isset($_POST['id_poule'])     ? (int) $_POST['id_poule']            : 0;

if ($id_tournoi === '' || $id_categorie <= 0 || $id_poule <= 0) {
    echo json_encode([
        'success' => false,
        'error'   => "Paramètres manquants ou invalides (id_tournoi, id_categorie, id_poule requis en POST).",
    ]);
    exit;
}

try {
    // ---------- 1. Vérifier l'existence de la poule ciblée ----------
    // HYPOTHÈSE : la table 'poule' est identifiée par
    // (id_tournoi, id_categorie, id_poule) et pas par un id global.
    $sqlPoule = "SELECT p.id_poule, p.nom AS nom_poule
                 FROM poule p
                 WHERE p.id_tournoi = :id_tournoi
                   AND p.id_categorie = :id_categorie
                   AND p.id_poule = :id_poule
                 LIMIT 1";
    $stmtPoule = $pdo->prepare($sqlPoule);
    $stmtPoule->execute([
        ':id_tournoi'   => $id_tournoi,
        ':id_categorie' => $id_categorie,
        ':id_poule'     => $id_poule,
    ]);
    $poule = $stmtPoule->fetch(PDO::FETCH_ASSOC);

    if (!$poule) {
        echo json_encode([
            'success' => false,
            'error'   => "Poule introuvable pour id_tournoi=$id_tournoi, id_categorie=$id_categorie, id_poule=$id_poule.",
        ]);
        exit;
    }

    // ---------- 2. Récupérer la liste des équipes de la poule ----------
    // HYPOTHÈSE : table 'equipe' avec colonnes id_equipe, nom,
    // id_tournoi, id_categorie, id_poule (mêmes clés composites).
    $sqlEquipes = "SELECT id_equipe, nom
                   FROM equipe
                   WHERE id_tournoi = :id_tournoi
                     AND id_categorie = :id_categorie
                     AND id_poule = :id_poule
                   ORDER BY id_equipe ASC";
    $stmtEquipes = $pdo->prepare($sqlEquipes);
    $stmtEquipes->execute([
        ':id_tournoi'   => $id_tournoi,
        ':id_categorie' => $id_categorie,
        ':id_poule'     => $id_poule,
    ]);
    $equipes = $stmtEquipes->fetchAll(PDO::FETCH_ASSOC);

    $n = count($equipes);
    if ($n < 3) {
        echo json_encode([
            'success' => false,
            'error'   => "La poule ne contient que $n équipe(s) ; "
                       . "il faut au minimum 3 équipes pour générer des matchs SALADE.",
        ]);
        exit;
    }

    // ---------- 3. Calcul du nombre de tours ----------
    $nombre_tours = (int) floor(($n - 1) / 2);
    if ($nombre_tours < 1) {
        echo json_encode([
            'success' => false,
            'error'   => "Nombre de tours calculé invalide ($nombre_tours) pour n=$n.",
        ]);
        exit;
    }

    // ---------- 4. Transaction : purge puis insertion ----------
    $pdo->beginTransaction();

    $stmtDelete = $pdo->prepare(
        "DELETE FROM match_poule
         WHERE id_tournoi = :id_tournoi
           AND id_categorie = :id_categorie
           AND id_poule = :id_poule"
    );
    $stmtDelete->execute([
        ':id_tournoi'   => $id_tournoi,
        ':id_categorie' => $id_categorie,
        ':id_poule'     => $id_poule,
    ]);

    // ---------- 5. Préparation de l'INSERT ----------
    // Colonnes réelles de match_poule (cf. structure fournie) :
    //   id (PK), id_tournoi, id_categorie, id_poule, id_poule_2,
    //   id_match, terrain, id_equipe_1, id_equipe_2,
    //   id_equipe_3, id_equipe_4, status,
    //   score_equipe_1, score_equipe_2,
    //   heure_debut, heure_fin, ordre_affichage,
    //   dernier_modifiant, numero_tour
    //
    // NOTE : id_equipe_2 est NOT NULL -> on met 0 si absent (4e/3e
    // équipe manquante gérée via id_equipe_3/id_equipe_4 qui eux
    // acceptent NULL). e2 ne devrait manquer que si n < 2, donc
    // normalement jamais dans notre cas (n >= 3).
    $sqlInsert = "INSERT INTO match_poule
        (id_tournoi, id_categorie, id_poule, id_poule_2,
         id_match, terrain,
         id_equipe_1, id_equipe_2, id_equipe_3, id_equipe_4,
         status, score_equipe_1, score_equipe_2,
         heure_debut, heure_fin, ordre_affichage,
         dernier_modifiant, numero_tour)
        VALUES
        (:id_tournoi, :id_categorie, :id_poule, :id_poule_2,
         :id_match, :terrain,
         :id_equipe_1, :id_equipe_2, :id_equipe_3, :id_equipe_4,
         'planifie', '0*0*0', '0*0*0',
         :heure_debut, :heure_fin, :ordre_affichage,
         :dernier_modifiant, :numero_tour)";

    $stmtInsert = $pdo->prepare($sqlInsert);

    $total_matchs      = 0;
    $ordre_affichage   = 1;
    $id_match_compteur = 1;

    // ---------- 6. Boucle sur les tours ----------
    for ($numero_tour = 1; $numero_tour <= $nombre_tours; $numero_tour++) {
        // Tirage 100% aléatoire, indépendant à chaque tour.
        $shuffled = $equipes;
        shuffle($shuffled);

        // Découpage en groupes de 4 équipes
        for ($i = 0; $i < $n; $i += 4) {
            $e1 = isset($shuffled[$i])     ? (int) $shuffled[$i]['id_equipe']     : null;
            $e2 = isset($shuffled[$i + 1]) ? (int) $shuffled[$i + 1]['id_equipe'] : null;
            $e3 = isset($shuffled[$i + 2]) ? (int) $shuffled[$i + 2]['id_equipe'] : null;
            $e4 = isset($shuffled[$i + 3]) ? (int) $shuffled[$i + 3]['id_equipe'] : null;

            if ($e1 === null) {
                // sécurité : ne devrait jamais arriver (n >= 3)
                continue;
            }

            $stmtInsert->execute([
                ':id_tournoi'        => $id_tournoi,
                ':id_categorie'      => $id_categorie,
                ':id_poule'          => $id_poule,
                ':id_poule_2'        => null,    // SALADE = intra-poule
                ':id_match'          => $id_match_compteur++,
                ':terrain'           => null,    // à planifier plus tard
                ':id_equipe_1'       => $e1,
                ':id_equipe_2'       => $e2 ?? 0, // NOT NULL en base
                ':id_equipe_3'       => $e3,
                ':id_equipe_4'       => $e4,
                ':heure_debut'       => null,
                ':heure_fin'         => null,
                ':ordre_affichage'   => $ordre_affichage++,
                ':dernier_modifiant' => '',
                ':numero_tour'       => $numero_tour,
            ]);

            $total_matchs++;
        }
    }

    $pdo->commit();

    echo json_encode([
        'success'        => true,
        'message'        => "Génération SALADE terminée pour la poule.",
        'id_tournoi'     => $id_tournoi,
        'id_categorie'   => $id_categorie,
        'id_poule'       => $id_poule,
        'nombre_equipes' => $n,
        'nombre_tours'   => $nombre_tours,
        'nombre_matchs'  => $total_matchs,
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[generer_matchs_salade] PDOException: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error'   => "Erreur base de données lors de la génération SALADE : " . $e->getMessage(),
    ]);
}