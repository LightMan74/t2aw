<?php
// api/view_classement_final.php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$idTournoi = (int)($_GET['id_tournoi'] ?? 0);

if ($idTournoi <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'id_tournoi manquant']);
    exit;
}

try {
    // Récupérer toutes les catégories ayant une phase finale "classement_complet"
    $stmt = $pdo->prepare("
        SELECT pf.id AS id_phase_finale, pf.id_categorie, pf.nom AS nom_phase, c.nom AS nom_categorie
        FROM phases_finales pf
        JOIN categorie c ON c.id_categorie = pf.id_categorie AND c.id_tournoi = pf.id_tournoi
        WHERE pf.id_tournoi = :id_tournoi
          AND pf.type_bracket = 'classement_complet'
    ");
    $stmt->execute([':id_tournoi' => $idTournoi]);
    $phases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categoriesResult = [];

    foreach ($phases as $phase) {
        $idPhaseFinale = $phase['id_phase_finale'];

        // Récupérer tous les matchs terminés de cette phase finale
        $stmtMatchs = $pdo->prepare("
            SELECT id, winner_equipe_id, loser_equipe_id, classement_min, classement_max, statut_match
            FROM matchs_phase_finale
            WHERE id_phase_finale = :id_phase_finale
              AND winner_equipe_id IS NOT NULL
              AND classement_min IS NOT NULL
              AND classement_max IS NOT NULL
        ");
        $stmtMatchs->execute([':id_phase_finale' => $idPhaseFinale]);
        $matchs = $stmtMatchs->fetchAll(PDO::FETCH_ASSOC);

        if (empty($matchs)) {
            continue;
        }

        // Construire la liste des positions : id_equipe_phase_finale => position
        $positions = []; // [position => id_epf]

        foreach ($matchs as $m) {
            if ($m['winner_equipe_id'] && $m['classement_min'] !== null) {
                $positions[(int)$m['classement_min']] = (int)$m['winner_equipe_id'];
            }
            if ($m['loser_equipe_id'] && $m['classement_max'] !== null) {
                $positions[(int)$m['classement_max']] = (int)$m['loser_equipe_id'];
            }
        }

        if (empty($positions)) {
            continue;
        }

        // Récupérer les infos + noms à jour des équipes concernées
        $idsEpf = array_unique(array_values($positions));
        $in = implode(',', array_fill(0, count($idsEpf), '?'));

        $stmtEquipes = $pdo->prepare("
            SELECT
                epf.id,
                epf.is_bye,
                COALESCE(eq.nom, epf.nom_equipe) AS nom_equipe
            FROM equipes_phase_finale epf
            LEFT JOIN equipe eq ON
                eq.id_tournoi = epf.id_tournoi
                AND eq.id_categorie = epf.id_categorie
                AND eq.id_poule = epf.id_poule
                AND eq.id_equipe = epf.id_equipe
            WHERE epf.id IN ($in)
        ");
        $stmtEquipes->execute($idsEpf);
        $equipesRows = $stmtEquipes->fetchAll(PDO::FETCH_ASSOC);

        $equipesParId = [];
        foreach ($equipesRows as $e) {
            $equipesParId[$e['id']] = $e;
        }

        // Construire le classement trié par position croissante (1 = meilleur)
        ksort($positions);

        $classement = [];
        foreach ($positions as $position => $idEpf) {
            $eq = $equipesParId[$idEpf] ?? null;
            $classement[] = [
                'position' => $position,
                'nom_equipe' => $eq ? $eq['nom_equipe'] : '???',
                'is_bye' => $eq ? (bool)$eq['is_bye'] : false,
            ];
        }

        $categoriesResult[] = [
            'id_categorie' => $phase['id_categorie'],
            'nom_categorie' => $phase['nom_categorie'],
            'nom_phase' => $phase['nom_phase'],
            'classement' => $classement,
        ];
    }

    echo json_encode([
        'success' => true,
        'categories' => $categoriesResult,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}