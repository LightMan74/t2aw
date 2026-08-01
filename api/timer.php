<?php
header('Content-Type: application/json');
require_once 'db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$idtournoi = $_GET['idtournoi'] ?? $_POST['idtournoi'] ?? '';

switch ($action) {

    case 'get':
        $stmt = $pdo->query("SELECT * FROM timer WHERE id_tournoi = $idtournoi");
        $timer = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'timer' => $timer]);
        break;

    case 'start':
        $duration = (int)($_POST['duration'] ?? 0);
        if ($duration <= 0) {
            echo json_encode(['success' => false, 'error' => 'Durée invalide']);
            exit;
        }
        $now = round(microtime(true) * 1000);
        $stmt = $pdo->prepare("UPDATE timer SET duration = ?, start_time = ?, paused_at = NULL, status = 'running' WHERE id_tournoi = $idtournoi");
        $stmt->execute([$duration, $now]);
        echo json_encode(['success' => true]);
        break;

    case 'pause':
        $stmt = $pdo->query("SELECT * FROM timer WHERE id_tournoi = $idtournoi");
        $timer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($timer['status'] === 'running') {
            $elapsed = (round(microtime(true) * 1000) - $timer['start_time']) / 1000;
            $elapsed = min($elapsed, $timer['duration']);
            $stmt = $pdo->prepare("UPDATE timer SET paused_at = ?, status = 'paused', start_time = NULL WHERE id_tournoi = $idtournoi");
            $stmt->execute([(int)$elapsed]);
        }
        echo json_encode(['success' => true]);
        break;

    case 'resume':
        $stmt = $pdo->query("SELECT * FROM timer WHERE id_tournoi = $idtournoi");
        $timer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($timer['status'] === 'paused') {
            $now = round(microtime(true) * 1000) - ($timer['paused_at'] * 1000);
            $stmt = $pdo->prepare("UPDATE timer SET start_time = ?, status = 'running', paused_at = NULL WHERE id_tournoi = $idtournoi");
            $stmt->execute([$now]);
        }
        echo json_encode(['success' => true]);
        break;

    case 'stop':
        $stmt = $pdo->prepare("UPDATE timer SET status = 'stopped', start_time = NULL, paused_at = NULL, duration = 0 WHERE id_tournoi = $idtournoi");
        $stmt->execute();
        echo json_encode(['success' => true]);
        break;

    case 'toggle_sound':
        $enabled = (int)($_POST['enabled'] ?? 1);
        $stmt = $pdo->prepare("UPDATE timer SET sound_enabled = ? WHERE id_tournoi = $idtournoi");
        $stmt->execute([$enabled]);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue']);
}