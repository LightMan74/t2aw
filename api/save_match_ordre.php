<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // Lecture de l'ordre existant pour un tournoi
        $id_tournoi = isset($_GET['id_tournoi']) ? (int)$_GET['id_tournoi'] : 0;
        if (!$id_tournoi) {
            echo json_encode(['success' => false, 'error' => 'id_tournoi manquant']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT ordre FROM match_ordre WHERE id_tournoi = :id LIMIT 1");
        $stmt->execute(['id' => $id_tournoi]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'ordre' => $row ? json_decode($row['ordre'], true) : []
        ]);
        exit;
    }

    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        $id_tournoi = isset($data['id_tournoi']) ? (int)$data['id_tournoi'] : 0;
        $ordre = isset($data['ordre']) ? $data['ordre'] : null;

        if (!$id_tournoi || !is_array($ordre)) {
            echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
            exit;
        }

        $ordreJson = json_encode($ordre);

        // Une ligne par tournoi -> UPSERT
        $stmt = $pdo->prepare("SELECT id FROM match_ordre WHERE id_tournoi = :id LIMIT 1");
        $stmt->execute(['id' => $id_tournoi]);
        $existant = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existant) {
            $update = $pdo->prepare("UPDATE match_ordre SET ordre = :ordre WHERE id_tournoi = :id");
            $update->execute(['ordre' => $ordreJson, 'id' => $id_tournoi]);
        } else {
            $insert = $pdo->prepare("INSERT INTO match_ordre (id_tournoi, ordre) VALUES (:id, :ordre)");
            $insert->execute(['id' => $id_tournoi, 'ordre' => $ordreJson]);
        }

        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Méthode non supportée']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur DB : ' . $e->getMessage()]);
}