<?php
/**
 * api/generer_matchs_salade.php
 * --------------------------------------------------------------
 * Retourne la liste des équipes d'une poule SALADE + les
 * métadonnées nécessaires (poule, catégorie, tournoi, nombre de
 * tours calculé) pour que le côté JavaScript puisse générer
 * lui-même les matchs (groupes de 4, sans anti-répétition).
 *
 * La génération des tours et l'insertion en base sont désormais
 * effectuées côté JavaScript. Ce endpoint ne fait QUE lire la
 * poule et ses équipes.
 *
 * Entrée (POST) :
 *   - id_poule   : identifiant de la poule concernée
 *
 * Sortie (JSON) :
 *   - succès : {
 *       success: true,
 *       id_poule, nom_poule,
 *       id_categorie, id_tournoi,
 *       nombre_equipes,
 *       nombre_tours,
 *       equipes: [{ id_equipe, nom }, ...]
 *     }
 *   - échec : { success: false, error: "..." }
 * --------------------------------------------------------------
 */

header('Content-Type: application/json; charset=utf-8');

// On s'appuie sur la connexion PDO fournie par api/db.php
// (variable globale $pdo, mode SQLite en local, MySQL en prod)
require __DIR__ . '/db.php';

// ---------- Lecture du paramètre POST ----------
$id_tournoi = isset($_POST['id_tournoi']) ? (int) $_POST['id_tournoi'] : 0;
$id_categorie = isset($_POST['id_categorie']) ? (int) $_POST['id_categorie'] : 0;
$id_poule = isset($_POST['id_poule']) ? (int) $_POST['id_poule'] : 0;

if ($id_poule <= 0) {
    echo json_encode([
        'success' => false,
        'error'   => "Paramètre 'id_poule' manquant ou invalide (POST attendu).",
    ]);
    exit;
}

try {
    // ---------- 1. Récupérer la poule + son id_categorie + id_tournoi ----------
    // HYPOTHÈSE : la table 'poule' contient id_categorie, et la table
    // 'categorie' contient id_tournoi (cohérent avec l'existant
    // generer_matchs.php / sauvegarder_ordre.php).
    // $sqlPoule = "SELECT p.id_poule,
    //                     p.nom       AS nom_poule,
    //                     p.id_categorie,
    //                     c.id_tournoi
    //              FROM poule p
    //              JOIN categorie c ON c.id_categorie = p.id_categorie
    //              WHERE p.id_poule = :id_poule
    //              LIMIT 1";
    // $stmtPoule = $pdo->prepare($sqlPoule);
    // $stmtPoule->execute([':id_poule' => $id_poule]);
    // $poule = $stmtPoule->fetch(PDO::FETCH_ASSOC);

    // if (!$poule) {
    //     echo json_encode([
    //         'success' => false,
    //         'error'   => "Poule #$id_poule introuvable.",
    //     ]);
    //     exit;
    // }

    // $id_tournoi   = (int) $poule['id_tournoi'];
    // $id_categorie = (int) $poule['id_categorie'];
    // $nom_poule    = (string) $poule['nom_poule'];
    $nom_poule    = (string) 'UNIQUE';

    // ---------- 2. Récupérer la liste des équipes de la poule ----------
    // HYPOTHÈSE : table 'equipe' avec colonnes id_equipe, nom, id_poule.
    $sqlEquipes = "SELECT id_equipe, nom
                   FROM equipe
                   WHERE id_tournoi = :id_tournoi and id_categorie = :id_categorie and id_poule = :id_poule
                   ORDER BY id_equipe ASC";
    $stmtEquipes = $pdo->prepare($sqlEquipes);
    $stmtEquipes->execute([':id_tournoi' => $id_tournoi,':id_categorie' => $id_categorie,':id_poule' => $id_poule]);
    $equipes = $stmtEquipes->fetchAll(PDO::FETCH_ASSOC);

    $n = count($equipes);
    if ($n < 3) {
        // 3 équipes = minimum pour avoir 1 tour (floor((3-1)/2) = 1)
        echo json_encode([
            'success' => false,
            'error'   => "La poule #$id_poule ne contient que $n équipe(s) ; "
                       . "il faut au minimum 3 équipes pour générer des matchs SALADE.",
        ]);
        exit;
    }

    // ---------- 3. Calcul du nombre de tours (indicatif pour le JS) ----------
    // Formule validée avec l'utilisateur :
    //   nombre_tours = floor((n-1)/2)
    // Le JS utilise cette valeur pour savoir combien de fois
    // mélanger / répartir en groupes de 4.
    $nombre_tours = (int) floor(($n - 1) / 2);

    // ---------- 4. Réponse : équipes + métadonnées uniquement ----------
    // La génération des matchs (shuffle + insertion) est désormais
    // faite côté JavaScript. On ne touche plus à la table match_poule.
    echo json_encode([
        'success'        => true,
        'message'        => "Équipes de la poule #$id_poule retournées "
                          . "(génération des matchs effectuée côté JavaScript).",
        'id_poule'       => $id_poule,
        'nom_poule'      => $nom_poule,
        'id_categorie'   => $id_categorie,
        'id_tournoi'     => $id_tournoi,
        'nombre_equipes' => $n,
        'nombre_tours'   => $nombre_tours,
        'equipes'        => $equipes,
    ]);

} catch (PDOException $e) {
    error_log('[generer_matchs_salade] PDOException: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error'   => "Erreur base de données lors du chargement des équipes "
                   . "de la poule SALADE.",
    ]);
} catch (Exception $e) {
    error_log('[generer_matchs_salade] Exception: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error'   => "Erreur inattendue lors du chargement des équipes SALADE.",
    ]);
}