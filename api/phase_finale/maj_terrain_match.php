<?php
// api/phase_finale/maj_terrain_match.php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $matchId = isset($input['match_id']) ? (int)$input['match_id'] : 0;
    $terrain = $input['terrain'] ?? null;

    if (!$matchId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Match invalide']);
        exit;
    }

    $terrain = ($terrain === null || $terrain === '') ? null : (int)$terrain;

    $stmt = $pdo->prepare(
        "UPDATE matchs_phase_finale SET terrain = :terrain WHERE id = :id"
    );
    $stmt->execute([
        ':terrain' => $terrain,
        ':id' => $matchId
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}