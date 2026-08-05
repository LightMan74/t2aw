<?php
header('Content-Type: application/json');
include __DIR__ . "/check_connected.php";
include "db.php";

if (!isset($_FILES['fichier']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Fichier manquant ou erreur d\'upload']);
    exit;
}

$contenu = file_get_contents($_FILES['fichier']['tmp_name']);
$data = json_decode($contenu, true);

if (!$data || !isset($data['tables'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Fichier JSON invalide ou mal formé']);
    exit;
}

$tables = $data['tables'];

$ordre_insertion = [
    'tournoi',
    'parametre',
    'timer',
    'categorie',
    'poule',
    'equipe',
    'match_poule',
    'phases_finales',
    'equipes_phase_finale',
    'matchs_phase_finale'
];

try {
    $pdo->beginTransaction();

    /**
     * --- 1. Récupération du prochain id_tournoi disponible ---
     */
    $stmt = $pdo->query("SELECT MAX(CAST(id_tournoi AS UNSIGNED)) AS max_id FROM tournoi");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $nouveau_id_tournoi = ($row['max_id'] ?? 0) + 1;

    /**
     * --- 2. Récupération du prochain id disponible pour phases_finales ---
     */
    $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM phases_finales");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $nouveau_id_pf = ($row['max_id'] ?? 0) + 1;

    $total_lignes_inserees = 0;
    $details = [];

    // Mapping ancien_id => nouvel_id pour equipes_phase_finale
    $mapping_equipes_pf = [];

    foreach ($ordre_insertion as $table) {
        if (!isset($tables[$table]) || empty($tables[$table])) {
            $details[$table] = 0;
            continue;
        }

        $lignes = $tables[$table];
        $nbInserees = 0;

        foreach ($lignes as $ligne) {
            $ancien_id = $ligne['id'] ?? null;
            unset($ligne['id']);

            // Remplacement de l'id_tournoi par le nouveau, pour toutes les tables
            if (array_key_exists('id_tournoi', $ligne)) {
                $ligne['id_tournoi'] = $nouveau_id_tournoi;
            }

            // phases_finales : id fixe (unique tournoi, une seule ligne normalement)
            if ($table == "phases_finales") {
                $ligne['id'] = $nouveau_id_pf;
            }

            // Toutes les tables qui référencent phases_finales
            if (($table == "matchs_phase_finale" || $table == "equipes_phase_finale")
                && array_key_exists('id_phase_finale', $ligne)) {
                $ligne['id_phase_finale'] = $nouveau_id_pf;
            }

            // Remapping equipe1_id / equipe2_id dans matchs_phase_finale
            if ($table == "matchs_phase_finale") {
                if (array_key_exists('equipe1_id', $ligne) && isset($mapping_equipes_pf[$ligne['equipe1_id']])) {
                    $ligne['equipe1_id'] = $mapping_equipes_pf[$ligne['equipe1_id']];
                }
                if (array_key_exists('equipe2_id', $ligne) && isset($mapping_equipes_pf[$ligne['equipe2_id']])) {
                    $ligne['equipe2_id'] = $mapping_equipes_pf[$ligne['equipe2_id']];
                }
                if (array_key_exists('winner_equipe_id', $ligne) && isset($mapping_equipes_pf[$ligne['winner_equipe_id']])) {
                    $ligne['winner_equipe_id'] = $mapping_equipes_pf[$ligne['winner_equipe_id']];
                }
                if (array_key_exists('loser_equipe_id', $ligne) && isset($mapping_equipes_pf[$ligne['loser_equipe_id']])) {
                    $ligne['loser_equipe_id'] = $mapping_equipes_pf[$ligne['loser_equipe_id']];
                }
            }

            $colonnes = array_keys($ligne);
            $colonnesStr = implode(', ', array_map(fn($c) => "`$c`", $colonnes));
            $placeholders = implode(', ', array_map(fn($c) => ":$c", $colonnes));

            $sql = "INSERT INTO `$table` ($colonnesStr) VALUES ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($ligne);

            // Sauvegarde du mapping pour equipes_phase_finale
            if ($table == "equipes_phase_finale" && $ancien_id !== null) {
                $mapping_equipes_pf[$ancien_id] = $pdo->lastInsertId();
            }

            $nbInserees++;
        }

        $details[$table] = $nbInserees;
        $total_lignes_inserees += $nbInserees;
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "Tournoi importé avec succès sous le nouvel id #$nouveau_id_tournoi",
        'nouveau_id_tournoi' => $nouveau_id_tournoi,
        'total_lignes_inserees' => $total_lignes_inserees,
        'details' => $details
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'import : ' . $e->getMessage()
    ]);
}