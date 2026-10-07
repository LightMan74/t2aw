<?php
// api/ajouter_match.php
// === AJOUT MANUEL DE MATCH - DEBUT ===
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . '/check_connected.php';
require __DIR__ . '/db.php';

function reponse(bool $success, string $message = '', array $match = []): never {
    echo json_encode(['success' => $success] + ($success ? ['match' => $match] : ['error' => $message]), JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw ?: '', true);
if (!is_array($input)) $input = $_POST;

$idTournoi = trim((string)($input['id_tournoi'] ?? ''));
$idCategorie = filter_var($input['id_categorie'] ?? null, FILTER_VALIDATE_INT);
$idPoule = filter_var($input['id_poule'] ?? null, FILTER_VALIDATE_INT);
$idPoule2 = filter_var($input['id_poule_2'] ?? null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
$idEquipe1 = filter_var($input['id_equipe_1'] ?? null, FILTER_VALIDATE_INT);
$idEquipe2 = filter_var($input['id_equipe_2'] ?? null, FILTER_VALIDATE_INT);
$idEquipe3 = filter_var($input['id_equipe_3'] ?? null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
$idEquipe4 = filter_var($input['id_equipe_4'] ?? null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
$isSalade = ($idEquipe3 !== null && $idEquipe3 !== false && $idEquipe4 !== null && $idEquipe4 !== false);

if ($idTournoi === '' || $idCategorie === false || $idPoule === false || $idEquipe1 === false || $idEquipe2 === false) {
    reponse(false, 'Tournoi, catégorie, poule et deux équipes sont obligatoires.');
}

if ($isSalade) {
    $ids = [$idEquipe1, $idEquipe2, $idEquipe3, $idEquipe4];
    if (count(array_unique($ids)) !== 4) {
        reponse(false, 'Les 4 équipes doivent être différentes.');
    }
    $idPoule2 = false; // un match salade n'est pas inter-poule
} elseif ($idEquipe1 === $idEquipe2 && ($idPoule2 === false || $idPoule2 === null || $idPoule2 === $idPoule)) {
    reponse(false, 'Les deux équipes doivent être différentes.');
}

$isInter = ($idPoule2 !== false && $idPoule2 !== null && $idPoule2 > 0);

if (!$isInter) $idPoule2 = null;

try {
    $pdo->beginTransaction();

    $cat = $pdo->prepare('SELECT nom FROM categorie WHERE id_tournoi = ? AND id_categorie = ?');
    $cat->execute([$idTournoi, $idCategorie]);
    $categorie = $cat->fetch(PDO::FETCH_ASSOC);
    if (!$categorie) throw new RuntimeException('Catégorie introuvable pour ce tournoi.');

    $poule = $pdo->prepare('SELECT nom FROM poule WHERE id_tournoi = ? AND id_categorie = ? AND id_poule = ?');
    $poule->execute([$idTournoi, $idCategorie, $idPoule]);
    $poule1 = $poule->fetch(PDO::FETCH_ASSOC);
    if (!$poule1) throw new RuntimeException('Poule introuvable pour cette catégorie.');
    $poule2 = null;
    if ($isInter) {
        $poule->execute([$idTournoi, $idCategorie, $idPoule2]);
        $poule2 = $poule->fetch(PDO::FETCH_ASSOC);
        if (!$poule2) throw new RuntimeException('Seconde poule introuvable pour cette catégorie.');
    }

    $eq = $pdo->prepare('SELECT id_equipe, nom, id_poule FROM equipe WHERE id_tournoi = ? AND id_categorie = ? AND id_poule = ? AND id_equipe = ?');
    $eq->execute([$idTournoi, $idCategorie, $idPoule, $idEquipe1]);
    $equipe1 = $eq->fetch(PDO::FETCH_ASSOC);
    $eq->execute([$idTournoi, $idCategorie, $isInter ? $idPoule2 : $idPoule, $idEquipe2]);
    $equipe2 = $eq->fetch(PDO::FETCH_ASSOC);
    if (!$equipe1 || !$equipe2) throw new RuntimeException('Une équipe ne correspond pas à la poule sélectionnée.');
    $equipe3 = $equipe4 = null;
    if ($isSalade) {
        $eq->execute([$idTournoi, $idCategorie, $idPoule, $idEquipe3]);
        $equipe3 = $eq->fetch(PDO::FETCH_ASSOC);
        $eq->execute([$idTournoi, $idCategorie, $idPoule, $idEquipe4]);
        $equipe4 = $eq->fetch(PDO::FETCH_ASSOC);
        if (!$equipe3 || !$equipe4) throw new RuntimeException('Une équipe ne correspond pas à la poule sélectionnée.');
    }

    // id_match est le numéro dans la poule; ordre_affichage est global au tournoi.
    $q = $pdo->prepare('SELECT COALESCE(MAX(id_match), 0) + 1 FROM match_poule WHERE id_tournoi = ? AND id_categorie = ? AND id_poule = ?');
    $q->execute([$idTournoi, $idCategorie, $idPoule]);
    $numMatch = (int)$q->fetchColumn();
    $q = $pdo->prepare('SELECT COALESCE(MAX(ordre_affichage), 0) + 1 FROM match_poule WHERE id_tournoi = ?');
    $q->execute([$idTournoi]);
    $ordre = (int)$q->fetchColumn();

    $insert = $pdo->prepare("INSERT INTO match_poule
        (id_tournoi, id_categorie, id_poule, id_poule_2, id_match, terrain,
         id_equipe_1, id_equipe_2, id_equipe_3, id_equipe_4, status,
         score_equipe_1, score_equipe_2, heure_debut, heure_fin,
         ordre_affichage, dernier_modifiant, numero_tour)
        VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, 'planifie', '0*0*0', '0*0*0', NULL, NULL, ?, '', NULL)");
    $insert->execute([$idTournoi, $idCategorie, $idPoule, $idPoule2, $numMatch, $idEquipe1, $idEquipe2, $isSalade ? $idEquipe3 : null, $isSalade ? $idEquipe4 : null, $ordre]);
    $id = (int)$pdo->lastInsertId();
    $pdo->commit();

    reponse(true, '', [
        'id' => $id, 'id_tournoi' => $idTournoi, 'id_categorie' => (int)$idCategorie,
        'id_poule' => (int)$idPoule, 'id_poule_2' => $idPoule2,
        'id_match' => $numMatch, 'num_match_poule' => $numMatch,
        'ordre_affichage' => $ordre, 'terrain' => null, 'status' => 'planifie',
        'score_equipe_1' => '0*0*0', 'score_equipe_2' => '0*0*0',
        'id_equipe_1' => (int)$idEquipe1, 'id_equipe_2' => (int)$idEquipe2,
        'nom_categorie' => $categorie['nom'], 'nom_poule' => $isInter ? ($poule1['nom'] . ' / ' . $poule2['nom']) : $poule1['nom'],
        'nom_equipe_1' => $equipe1['nom'], 'nom_equipe_2' => $equipe2['nom'],
        'id_equipe_3' => $isSalade ? (int)$idEquipe3 : null,
        'id_equipe_4' => $isSalade ? (int)$idEquipe4 : null,
        'nom_equipe_3' => $equipe3['nom'] ?? null,
        'nom_equipe_4' => $equipe4['nom'] ?? null,
        'ajout_manuel' => true,
        'inter_poule' => $isInter, 'terrain_libre' => false
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    reponse(false, $e->getMessage());
}
// === AJOUT MANUEL DE MATCH - FIN ===