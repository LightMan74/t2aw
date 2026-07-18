<?php
// api/get_parametres.php
header('Content-Type: application/json');
include __DIR__ . "/check_connected.php";
require 'db.php';
 
$id_tournoi = $_POST['id_tournoi'] ?? null;

if (!$id_tournoi) {
    echo json_encode(['success' => false, 'error' => 'id_tournoi manquant']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM parametre WHERE id_tournoi = ?");
    $stmt->execute([$id_tournoi]);
    $param = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'nbre_terrain_poule' => $param['nbre_terrain_poule'] ?? 1
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}