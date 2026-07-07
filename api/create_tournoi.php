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

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'error' => 'JSON invalide']);
    ob_end_clean();
    exit;
}

require_once __DIR__ . '/db.php';

try {
    $nom = trim($data['nom_tournoi'] ?? '');
    $nbre_terrain_poule = (int)($data['nbre_terrain_poule'] ?? 0);
    $nbre_terrain_phasefinal = (int)($data['nbre_terrain_phasefinal'] ?? 0);
    $temps_de_match = (int)($data['temps_de_match'] ?? 0);
    $heure_debut_poule = trim($data['heure_debut_poule'] ?? '');
    $heure_debut_phasefinal = trim($data['heure_debut_phasefinal'] ?? '');
    $troissets = trim($data['troissets'] ?? '');
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
    $stmtInsertTournoi = $pdo->prepare("INSERT INTO tournoi (id_tournoi, nom) VALUES (:id_tournoi, :nom)");
    $stmtInsertTournoi->execute(['id_tournoi' => $id_tournoi, 'nom' => $nom]);

    // Insérer les paramètres
    $stmtInsertParam = $pdo->prepare("
        INSERT INTO parametre (id_tournoi, nbre_terrain_poule, nbre_terrain_phasefinal, temps_de_match, heure_debut_poule, heure_debut_phasefinal, troissets)
        VALUES (:id_tournoi, :nbre_terrain_poule, :nbre_terrain_phasefinal, :temps_de_match, :heure_debut_poule, :heure_debut_phasefinal, :troissets)
    ");
    $stmtInsertParam->execute([
        'id_tournoi' => $id_tournoi,
        'nbre_terrain_poule' => $nbre_terrain_poule,
        'nbre_terrain_phasefinal' => $nbre_terrain_phasefinal,
        'temps_de_match' => $temps_de_match,
        'heure_debut_poule' => $heure_debut_poule,
        'heure_debut_phasefinal' => $heure_debut_phasefinal,
        'troissets' => $troissets
    ]);

    // Préparer les requêtes d'insertion
    $stmtInsertCat = $pdo->prepare("INSERT INTO categorie (id_tournoi, id_categorie, nom) VALUES (:id_tournoi, :id_categorie, :nom)");
    $stmtInsertPoule = $pdo->prepare("INSERT INTO poule (id_tournoi, id_categorie, id_poule, nom) VALUES (:id_tournoi, :id_categorie, :id_poule, :nom)");
    $stmtInsertEquipe = $pdo->prepare("INSERT INTO equipe (id_tournoi, id_categorie, id_poule, id_equipe, nom) VALUES (:id_tournoi, :id_categorie, :id_poule, :id_equipe, :nom)");

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
}

ob_end_clean();
?>