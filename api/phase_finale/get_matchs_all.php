<?php
// api/phase_finale/get_matchs_all.php
header('Content-Type: application/json');
include __DIR__ . "/../check_connected.php";
require_once __DIR__ . '/../db.php';

$id_tournoi = $_GET['id_tournoi'] ?? null;

if (!$id_tournoi) {
    echo json_encode(['success' => false, 'error' => 'id_tournoi manquant']);
    exit;
}

try {
    $sql = "SELECT
                m.id,
                m.id_tournoi,
                m.id_phase_finale,
                m.equipe1_id,
                m.equipe2_id,
                m.round,
                m.sub_group,
                m.match_code,
                m.score1,
                m.score2,
                m.winner_equipe_id,
                m.statut,
                m.statut_match,
                m.source_team1,
                m.source_team2,
                m.terrain,
                m.heure_debut,
                pf.nom AS nom_phase,
                pf.id_categorie,
                pf.type_bracket,
                pf.nb_equipes_arrondi,
                c.nom AS nom_categorie,
                eq1.nom AS nom_equipe1,
                eq2.nom AS nom_equipe2
            FROM matchs_phase_finale m
            JOIN phases_finales pf ON pf.id = m.id_phase_finale
            LEFT JOIN categorie c ON c.id_tournoi = m.id_tournoi AND c.id_categorie = pf.id_categorie
            LEFT JOIN equipes_phase_finale e1 ON e1.id = m.equipe1_id
            LEFT JOIN equipes_phase_finale e2 ON e2.id = m.equipe2_id
            LEFT JOIN equipe eq1 ON
                eq1.id_tournoi = e1.id_tournoi
                AND eq1.id_categorie = e1.id_categorie
                AND eq1.id_poule = e1.id_poule
                AND eq1.id_equipe = e1.id_equipe
            LEFT JOIN equipe eq2 ON
                eq2.id_tournoi = e2.id_tournoi
                AND eq2.id_categorie = e2.id_categorie
                AND eq2.id_poule = e2.id_poule
                AND eq2.id_equipe = e2.id_equipe
            WHERE m.id_tournoi = ?
              AND m.statut <> 'simule'
            ORDER BY m.round ASC, c.nom ASC, pf.nom ASC, m.sub_group ASC, m.id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_tournoi]);
    $matchs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Calcul du round max par phase finale (pour déduire le libellé du tour) ---
    $maxRoundParPhase = [];
    foreach ($matchs as $m) {
        $pid = $m['id_phase_finale'];
        $r = (int)$m['round'];
        if (!isset($maxRoundParPhase[$pid]) || $r > $maxRoundParPhase[$pid]) {
            $maxRoundParPhase[$pid] = $r;
        }
    }

    foreach ($matchs as &$m) {
        $m['round_max_phase'] = $maxRoundParPhase[$m['id_phase_finale']] ?? (int)$m['round'];
    }
    unset($m);

    echo json_encode(['success' => true, 'matchs' => $matchs]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}