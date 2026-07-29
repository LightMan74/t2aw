<?php
/**
 * API create_tournoi.php
 * Crée un nouveau tournoi avec paramètres, catégories, poules et équipes
 * POST JSON
 */

if (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');

include __DIR__ . "/check_connected.php";

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'error' => 'JSON invalide']);
    ob_end_clean();
    exit;
}

require_once __DIR__ . '/db.php';
function generateUUIDv4() {
    $data = random_bytes(16);
    
    // Définir la version (0100) et les bits de variant (10)
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
try {
    $nom = trim($data['nom_tournoi'] ?? '');
    $nbre_terrain_poule = (int)($data['nbre_terrain_poule'] ?? 0);
    $nbre_terrain_phasefinal = (int)($data['nbre_terrain_phasefinal'] ?? 0);
    $temps_de_match = (int)($data['temps_de_match'] ?? 0);
    $heure_debut_poule = trim($data['heure_debut_poule'] ?? '');
    $heure_debut_phasefinal = trim($data['heure_debut_phasefinal'] ?? '');
    $matchtermine = trim($data['matchtermine'] ?? '');
    $tournoi_password = trim($data['tournoi_password'] ?? '');
    $tournoi_cacher = trim($data['tournoi_cacher'] ?? '');

    // troissets : forcer 1 ou 3 uniquement
    $troissets = (int)($data['troissets'] ?? 3);
    if (!in_array($troissets, [1, 3], true)) {
        $troissets = 3;
    }

    // terrain_automatique : forcer 1 ou 0 uniquement
    $terrain_automatique = (int)($data['terrain_automatique'] ?? 1);
    if (!in_array($terrain_automatique, [0, 1], true)) {
        $terrain_automatique = 1;
    }

    $categories = $data['categories'] ?? [];

    if (empty($nom)) {
        echo json_encode(['success' => false, 'error' => 'Le nom du tournoi est requis']);
        ob_end_clean();
        exit;
    }

    $pdo->beginTransaction();

    // Générer un nouvel id_tournoi logique
    $stmtMaxId = $pdo->prepare("SELECT COALESCE(MAX(id_tournoi), 0) + 1 as next_id FROM tournoi");
    $stmtMaxId->execute();
    $id_tournoi = $stmtMaxId->fetch(PDO::FETCH_ASSOC)['next_id'];

    // Insérer le tournoi
    $stmtInsertTournoi = $pdo->prepare("INSERT INTO tournoi (id_tournoi, nom, user_uid) VALUES (:id_tournoi, :nom, :user_uid)");
    $stmtInsertTournoi->execute(['id_tournoi' => $id_tournoi, 'nom' => $nom, 'user_uid' => $_SESSION['user_uid']]);

    // Insérer les paramètres
    // ATTENTION : si la colonne terrain_automatique n'existe pas encore dans la table `parametre`,
    // exécuter au préalable :
    // ALTER TABLE parametre ADD COLUMN terrain_automatique TINYINT(1) NOT NULL DEFAULT 1;
    $stmtInsertParam = $pdo->prepare("
        INSERT INTO parametre (id_tournoi, nbre_terrain_poule, nbre_terrain_phasefinal, temps_de_match, heure_debut_poule, heure_debut_phasefinal, troissets, terrain_automatique, matchtermine, tournoi_password, tournoi_cacher)
        VALUES (:id_tournoi, :nbre_terrain_poule, :nbre_terrain_phasefinal, :temps_de_match, :heure_debut_poule, :heure_debut_phasefinal, :troissets, :terrain_automatique, :matchtermine, :tournoi_password, :tournoi_cacher)
    ");
    $stmtInsertParam->execute([
        'id_tournoi' => $id_tournoi,
        'nbre_terrain_poule' => $nbre_terrain_poule,
        'nbre_terrain_phasefinal' => $nbre_terrain_phasefinal,
        'temps_de_match' => $temps_de_match,
        'heure_debut_poule' => $heure_debut_poule,
        'heure_debut_phasefinal' => $heure_debut_phasefinal,
        'troissets' => $troissets,
        'terrain_automatique' => $terrain_automatique,
        'matchtermine' => $matchtermine,
        'tournoi_cacher' => $tournoi_cacher,
        'tournoi_password' => $tournoi_password
    ]);

    // Préparer les requêtes d'insertion
    $stmtInsertCat = $pdo->prepare("INSERT INTO categorie (id_tournoi, id_categorie, nom) VALUES (:id_tournoi, :id_categorie, :nom);");
    $stmtInsertPoule = $pdo->prepare("INSERT INTO poule (id_tournoi, id_categorie, id_poule, nom) VALUES (:id_tournoi, :id_categorie, :id_poule, :nom);");
    $stmtInsertEquipe = $pdo->prepare("INSERT INTO equipe (id_tournoi, id_categorie, id_poule, id_equipe, nom) VALUES (:id_tournoi, :id_categorie, :id_poule, :id_equipe, :nom);");

    // Insérer les catégories, poules et équipes
    foreach ($categories as $cat) {
        $id_categorie = (int)$cat['id_categorie'];
        $nom_cat = trim($cat['nom'] ?? '');
        if ($nom_cat === '') continue;

        $stmtInsertCat->execute([
            'id_tournoi' => $id_tournoi,
            'id_categorie' => $id_categorie,
            'nom' => $nom_cat
        ]);

        $poules = $cat['poules'] ?? [];
        foreach ($poules as $poule) {
            $id_poule = (int)$poule['id_poule'];
            $nom_poule = trim($poule['nom'] ?? '');
            if ($nom_poule === '') continue;

            $stmtInsertPoule->execute([
                'id_tournoi' => $id_tournoi,
                'id_categorie' => $id_categorie,
                'id_poule' => $id_poule,
                'nom' => $nom_poule
            ]);

            $equipes = $poule['equipes'] ?? [];
            foreach ($equipes as $equipe) {
                $id_equipe = (int)$equipe['id_equipe'];
                $nom_equipe = trim($equipe['nom'] ?? '');
                if ($nom_equipe === '') continue;

                $stmtInsertEquipe->execute([
                    'id_tournoi' => $id_tournoi,
                    'id_categorie' => $id_categorie,
                    'id_poule' => $id_poule,
                    'id_equipe' => $id_equipe,
                    'nom' => $nom_equipe
                ]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'id_tournoi' => $id_tournoi]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('create_tournoi PDOException: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur base de donnees']);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('create_tournoi Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

if (ob_get_level()) { 
    ob_end_clean();
}
?>