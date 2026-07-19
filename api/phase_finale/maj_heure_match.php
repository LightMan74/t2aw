<?php
// api/phase_finale/maj_heure_match.php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $matchId = isset($input['match_id']) ? (int)$input['match_id'] : 0;
    $heureDebut = $input['heure_debut'] ?? null;

    if (!$matchId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
        exit;
    }

    // Validation format HH:MM si non null
    if ($heureDebut !== null && $heureDebut !== '' && !preg_match('/^\d{2}:\d{2}$/', $heureDebut)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Format d\'heure invalide']);
        exit;
    }

    if ($heureDebut === '') {
        $heureDebut = null;
    }

    $stmt = $pdo->prepare(
        "UPDATE matchs_phase_finale SET heure_debut = :heure WHERE id = :id"
    );
    $stmt->execute([
        ':heure' => $heureDebut,
        ':id' => $matchId
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}