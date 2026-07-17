<?php
// api/phase_finale/maj_statut_match.php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $matchId = isset($input['match_id']) ? (int)$input['match_id'] : 0;
    $statutMatch = $input['statut_match'] ?? '';

    $statutsValides = ['planifie', 'en_cours', 'termine'];

    if (!$matchId || !in_array($statutMatch, $statutsValides, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
        exit;
    }

    $stmt = $pdo->prepare(
        "UPDATE matchs_phase_finale SET statut_match = :statut WHERE id = :id"
    );
    $stmt->execute([
        ':statut' => $statutMatch,
        ':id' => $matchId
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}