<?php
/**
 * API update_tournoi.php
 * Met à jour un tournoi existant : tournoi, parametre, categories, poules, equipes
 * POST JSON : {id_tournoi, nom, nbre_terrain_poule, nbre_terrain_phasefinal, temps_de_match, heure_debut_poule, heure_debut_phasefinal, troissets, terrain_automatique, categories:[...]}
 *
 * ALTER TABLE parametre ADD COLUMN terrain_automatique TINYINT(1) NOT NULL DEFAULT 1;
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

try {
    // Validation id_tournoi obligatoire
    if (!isset($data['id_tournoi']) || !is_numeric($data['id_tournoi'])) {
        echo json_encode(['success' => false, 'error' => 'id_tournoi manquant']);
        ob_end_clean();
        exit;
    }

    $id_tournoi = (int)$data['id_tournoi'];
    $nom = trim($data['nom'] ?? '');
    $nbre_terrain_poule = (int)($data['nbre_terrain_poule'] ?? 0);
    $nbre_terrain_phasefinal = (int)($data['nbre_terrain_phasefinal'] ?? 0);
    $temps_de_match = (int)($data['temps_de_match'] ?? 0);
    $heure_debut_poule = trim($data['heure_debut_poule'] ?? '');
    $heure_debut_phasefinal = trim($data['heure_debut_phasefinal'] ?? '');
    $matchtermine = trim($data['matchtermine'] ?? '');
    $tournoi_password = trim($data['tournoi_password'] ?? '');
    $tournoi_cacher = trim($data['tournoi_cacher'] ?? '');

    // Forcer troissets à 1 ou 3 uniquement
    $troissets_raw = trim($data['troissets'] ?? '');
    $troissets = in_array($troissets_raw, ['1', '3'], true) ? (int)$troissets_raw : 3;

    // Forcer terrain_automatique à 0 ou 1 uniquement
    $terrain_automatique_raw = $data['terrain_automatique'] ?? null;
    if ($terrain_automatique_raw === 'true' || $terrain_automatique_raw === true || $terrain_automatique_raw === '1' || $terrain_automatique_raw === 1) {
        $terrain_automatique = 1;
    } elseif ($terrain_automatique_raw === 'false' || $terrain_automatique_raw === false || $terrain_automatique_raw === '0' || $terrain_automatique_raw === 0) {
        $terrain_automatique = 0;
    } else {
        $terrain_automatique = 1; // défaut
    }

    $categories = $data['categories'] ?? [];

    if (empty($nom)) {
        echo json_encode(['success' => false, 'error' => 'Le nom du tournoi est requis']);
        ob_end_clean();
        exit;
    }

    $pdo->beginTransaction();

    // Mettre à jour le nom du tournoi
    $stmtUpdateTournoi = $pdo->prepare("UPDATE tournoi SET nom = :nom WHERE id_tournoi = :id_tournoi");
    $stmtUpdateTournoi->execute(['nom' => $nom, 'id_tournoi' => $id_tournoi]);

        // Mise à jour avec terrain_automatique
        $stmtUpdateParam = $pdo->prepare("
            UPDATE parametre SET
                nbre_terrain_poule = :nbre_terrain_poule,
                nbre_terrain_phasefinal = :nbre_terrain_phasefinal,
                temps_de_match = :temps_de_match,
                heure_debut_poule = :heure_debut_poule,
                heure_debut_phasefinal = :heure_debut_phasefinal,
                troissets = :troissets,
                terrain_automatique = :terrain_automatique,
                matchtermine = :matchtermine,
                tournoi_password = :tournoi_password,
                tournoi_cacher = :tournoi_cacher
            WHERE id_tournoi = :id_tournoi
        ");
        $stmtUpdateParam->execute([
            'nbre_terrain_poule' => $nbre_terrain_poule,
            'nbre_terrain_phasefinal' => $nbre_terrain_phasefinal,
            'temps_de_match' => $temps_de_match,
            'heure_debut_poule' => $heure_debut_poule,
            'heure_debut_phasefinal' => $heure_debut_phasefinal,
            'troissets' => $troissets,
            'terrain_automatique' => $terrain_automatique,
            'matchtermine' => $matchtermine,
            'tournoi_password' => $tournoi_password,
            'tournoi_cacher' => $tournoi_cacher,
            'id_tournoi' => $id_tournoi
        ]);   

    // Stratégie delete/re-INSERT : supprimer dans l'ordre FK (equipes > poules > categories)
    $stmtDeleteEquipe = $pdo->prepare("DELETE FROM equipe WHERE id_tournoi = :id_tournoi");
    $stmtDeleteEquipe->execute(['id_tournoi' => $id_tournoi]);

    $stmtDeletePoule = $pdo->prepare("DELETE FROM poule WHERE id_tournoi = :id_tournoi");
    $stmtDeletePoule->execute(['id_tournoi' => $id_tournoi]);

    $stmtDeleteCat = $pdo->prepare("DELETE FROM categorie WHERE id_tournoi = :id_tournoi");
    $stmtDeleteCat->execute(['id_tournoi' => $id_tournoi]);

    // Réinsérer dans l'ordre inverse (catégories > poules > équipes)
    $stmtInsertCat = $pdo->prepare("INSERT INTO categorie (id_tournoi, id_categorie, nom) VALUES (:id_tournoi, :id_categorie, :nom)");
    $stmtInsertPoule = $pdo->prepare("INSERT INTO poule (id_tournoi, id_categorie, id_poule, nom) VALUES (:id_tournoi, :id_categorie, :id_poule, :nom)");
    $stmtInsertEquipe = $pdo->prepare("INSERT INTO equipe (id_tournoi, id_categorie, id_poule, id_equipe, nom) VALUES (:id_tournoi, :id_categorie, :id_poule, :id_equipe, :nom)");

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

    $stmtInsertTimer = $pdo->prepare("
        UPDATE `timer` SET `duration`= :duration WHERE `id_tournoi` = :id_tournoi
    ");
    $stmtInsertTimer->execute([
        'duration' => $temps_de_match * 60,
        'id_tournoi' => $id_tournoi
    ]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('update_tournoi PDOException: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur base de donnees']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('update_tournoi Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

if (ob_get_level()) { 
    ob_end_clean();
}
?>