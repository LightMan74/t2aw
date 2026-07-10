<?php
require 'db.php';

$id_tournoi = isset($_GET['id_tournoi']) ? (int)$_GET['id_tournoi'] : 0;

if (!$id_tournoi) {
    echo json_encode(['error' => 'id_tournoi manquant']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM tournoi WHERE id_tournoi = ?");
$stmt->execute([$id_tournoi]);
$tournoi = $stmt->fetch();

echo json_encode(['tournoi' => $tournoi]);