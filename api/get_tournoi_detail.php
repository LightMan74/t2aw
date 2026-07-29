<?php
/**
 * API get_tournoi_detail.php
 * Retourne les détails complets d'un tournoi : tournoi, parametre, categories, poules, equipes
 * GET ?id_tournoi=X
 */
if (ob_get_level()) {
    ob_end_clean();
}
// ob_start();
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . "/check_connected.php";
require_once __DIR__ . '/db.php';

try {
    if (!isset($_GET['id_tournoi']) || !is_numeric($_GET['id_tournoi'])) {
        echo json_encode(['success' => false, 'error' => 'Parametre id_tournoi manquant ou invalide']);
        ob_end_clean();
        exit;
    }

    $id_tournoi = (int)$_GET['id_tournoi'];

    // Récupérer les infos du tournoi
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

if ($driver === 'sqlite') {
    $dateExpr = "strftime('%Y-%m-%d %H:%M:%S', date_creation)";
} else {
    $dateExpr = "DATE_FORMAT(date_creation, '%Y-%m-%d %H:%i:%s')";
}

$stmtTournoi = $pdo->prepare("
    SELECT id, id_tournoi, nom, $dateExpr as date_creation 
    FROM tournoi 
    WHERE id_tournoi = :id_tournoi
");
    $stmtTournoi->execute(['id_tournoi' => $id_tournoi]);
    $tournoi = $stmtTournoi->fetch(PDO::FETCH_ASSOC);

    if (!$tournoi) {
        echo json_encode(['success' => false, 'error' => 'Tournoi introuvable']);
        ob_end_clean();
        exit;
    }

    // Récupérer les paramètres
    $stmtParam = $pdo->prepare("SELECT * FROM parametre WHERE id_tournoi = :id_tournoi");
    $stmtParam->execute(['id_tournoi' => $id_tournoi]);
    $parametre = $stmtParam->fetch(PDO::FETCH_ASSOC);

    // Récupérer les catégories avec leurs poules et équipes
    $stmtCat = $pdo->prepare("SELECT id, id_categorie, nom FROM categorie WHERE id_tournoi = :id_tournoi ORDER BY id_categorie");
    $stmtCat->execute(['id_tournoi' => $id_tournoi]);
    $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

    foreach ($categories as &$cat) {
        // Poules de cette catégorie
        $stmtPoule = $pdo->prepare("SELECT id, id_poule, nom FROM poule WHERE id_tournoi = :id_tournoi AND id_categorie = :id_categorie ORDER BY id_poule");
        $stmtPoule->execute(['id_tournoi' => $id_tournoi, 'id_categorie' => $cat['id_categorie']]);
        $cat['poules'] = $stmtPoule->fetchAll(PDO::FETCH_ASSOC);

        foreach ($cat['poules'] as &$poule) {
            // Équipes de cette poule
            $stmtEquipe = $pdo->prepare("SELECT id, id_equipe, nom FROM equipe WHERE id_tournoi = :id_tournoi AND id_categorie = :id_categorie AND id_poule = :id_poule ORDER BY id_equipe");
            $stmtEquipe->execute([
                'id_tournoi' => $id_tournoi,
                'id_categorie' => $cat['id_categorie'],
                'id_poule' => $poule['id_poule']
            ]);
            $poule['equipes'] = $stmtEquipe->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    $result = [
        'success' => true,
        'tournoi' => $tournoi,
        'parametre' => $parametre ?: [
            'nbre_terrain_poule' => null,
            'nbre_terrain_phasefinal' => null,
            'temps_ou_set' => null,
            'temps_de_match' => null,
            'heure_debut_poule' => null,
            'heure_debut_phasefinal' => null,
            'matchtermine' => null,
            'tournoi_password' => null,
            'tournoi_cacher' => null
        ],
        'categories' => $categories
    ];

    echo json_encode($result);

} catch (PDOException $e) {
    error_log('get_tournoi_detail PDOException: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur base de donnees']);
}

if (ob_get_level()) { 
    ob_end_clean();
}
?>