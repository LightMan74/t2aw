<?php
// api/phase_finale/assigner_equipes_liste.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

function reassignationJson($success, $message, $status = 200, $extra = []) {
    http_response_code($status);
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) reassignationJson(false, 'Corps JSON invalide', 400);

$idPhase = (int)($input['id_phase_finale'] ?? 0);
$equipes = $input['equipes'] ?? null;
if ($idPhase <= 0 || !is_array($equipes)) reassignationJson(false, 'id_phase_finale et tableau equipes requis', 400);

try {
    $phaseStmt = $pdo->prepare('SELECT id, id_tournoi, id_categorie FROM phases_finales WHERE id = :id LIMIT 1');
    $phaseStmt->execute([':id' => $idPhase]);
    $phase = $phaseStmt->fetch(PDO::FETCH_ASSOC);
    if (!$phase) reassignationJson(false, 'Phase finale introuvable', 404);

    $slotStmt = $pdo->prepare('SELECT id, seed_position FROM equipes_phase_finale
        WHERE id_phase_finale = :phase AND (is_bye = 0 OR is_bye IS NULL)
        ORDER BY seed_position ASC');
    $slotStmt->execute([':phase' => $idPhase]);
    $slots = $slotStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($slots) !== count($equipes)) {
        reassignationJson(false, 'Le nombre d\'équipes ne correspond pas aux slots disponibles', 400);
    }

    $normalized = [];
    $seen = [];
    foreach ($equipes as $eq) {
        $idEquipe = (int)($eq['id_equipe'] ?? 0);
        $idPoule = (int)($eq['id_poule'] ?? 0);
        $idCategorie = (int)($eq['id_categorie'] ?? 0);
        $idTournoi = (int)($eq['id_tournoi'] ?? 0);
        
        if ($idEquipe <= 0 || $idPoule <= 0 || $idCategorie <= 0 || $idTournoi <= 0) {
            reassignationJson(false, 'Paramètres d\'équipe incomplets', 400);
        }
        
        $key = "$idEquipe-$idPoule-$idCategorie-$idTournoi";
        if (isset($seen[$key])) reassignationJson(false, 'Doublons détectés', 400);
        $seen[$key] = true;
        
        $normalized[] = [
            'id_equipe' => $idEquipe,
            'id_poule' => $idPoule,
            'id_categorie' => $idCategorie,
            'id_tournoi' => $idTournoi
        ];
    }

    $pdo->beginTransaction();
    $teamStmt = $pdo->prepare('SELECT id_equipe, id_categorie, id_poule, nom FROM equipe
        WHERE id_equipe = :id AND id_tournoi = :tournoi AND id_categorie = :categorie AND id_poule = :poule LIMIT 1');
    $update = $pdo->prepare('UPDATE equipes_phase_finale
        SET id_equipe = :equipe, id_categorie = :categorie, id_poule = :poule, nom_equipe = :nom
        WHERE id = :slot AND id_phase_finale = :phase');
    
    $updated = 0;
    foreach ($normalized as $index => $eq) {
        $teamStmt->execute([
            ':id' => $eq['id_equipe'],
            ':tournoi' => $eq['id_tournoi'],
            ':categorie' => $eq['id_categorie'],
            ':poule' => $eq['id_poule']
        ]);
        $team = $teamStmt->fetch(PDO::FETCH_ASSOC);
        if (!$team) throw new RuntimeException('Une équipe n\'a pas été trouvée');
        
        $update->execute([
            ':equipe' => $team['id_equipe'],
            ':categorie' => $team['id_categorie'],
            ':poule' => $team['id_poule'],
            ':nom' => $team['nom'],
            ':slot' => $slots[$index]['id'],
            ':phase' => $idPhase
        ]);
        $updated++;
    }
    $pdo->commit();
    reassignationJson(true, 'Équipes assignées', 200, ['updated' => $updated]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    reassignationJson(false, $e instanceof RuntimeException ? $e->getMessage() : 'Erreur base de données', 500);
}