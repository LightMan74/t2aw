<?php
/**
 * export_tournoi.php
 * Export d'un tournoi en format texte lisible
 */

$id_tournoi = $_GET['id_tournoi'] ?? null;

if (!$id_tournoi) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    die("Erreur : id_tournoi manquant");
}

try {
    include 'db.php';

    // ===== INFOS TOURNOI =====
    $stmt = $pdo->prepare("SELECT * FROM tournoi WHERE id_tournoi = ?");
    $stmt->execute([$id_tournoi]);
    $tournoi = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tournoi) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        die("Tournoi introuvable");
    }

    $now = new DateTime();
    $filename = 'Export_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $tournoi['nom']) . '_' . $now->format('Y-m-d_H-i-s') . '.txt';
    
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // Construction du contenu
    $content = '';
    $content .= str_repeat("=", 80) . "\n";
    $content .= "EXPORT TOURNOI DE BADMINTON\n";
    $content .= str_repeat("=", 80) . "\n";
    $content .= "Nom : " . $tournoi['nom'] . "\n";
    $content .= "Date d'export : " . $now->format('d/m/Y H:i:s') . "\n";
    $content .= str_repeat("=", 80) . "\n\n";

    // ===== CATEGORIES =====
    $stmt = $pdo->prepare("
        SELECT DISTINCT id_categorie, nom 
        FROM categorie 
        WHERE id_tournoi = ? 
        ORDER BY id_categorie
    ");
    $stmt->execute([$id_tournoi]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($categories)) {
        $content .= "Aucune catégorie trouvée.\n";
    } else {
        $content .= "\nPHASE POULES\n";
        $content .= str_repeat("=", 80) . "\n";

        foreach ($categories as $cat) {
            $idCat = $cat['id_categorie'];
            $nomCat = $cat['nom'];

            $content .= "\n" . str_repeat("-", 80) . "\n";
            $content .= "CATÉGORIE : " . $nomCat . "\n";
            $content .= str_repeat("-", 80) . "\n";

            // Poules de cette catégorie
            $stmtPoules = $pdo->prepare("
                SELECT DISTINCT id_poule, nom 
                FROM poule 
                WHERE id_categorie = ? AND id_tournoi = ?
                ORDER BY id_poule
            ");
            $stmtPoules->execute([$idCat, $id_tournoi]);
            $poules = $stmtPoules->fetchAll(PDO::FETCH_ASSOC);

            if (empty($poules)) {
                $content .= "\n  Aucune poule.\n";
            } else {
                foreach ($poules as $poule) {
                    $idPoule = $poule['id_poule'];
                    $nomPoule = $poule['nom'];

                    $content .= "\n  📊 Poule " . $nomPoule . ":\n";
                    $content .= "  " . str_repeat("-", 76) . "\n";

                    // Équipes
                    $stmtEquipes = $pdo->prepare("
                        SELECT nom FROM equipe 
                        WHERE id_poule = ? AND id_categorie = ? AND id_tournoi = ?
                        ORDER BY id_equipe
                    ");
                    $stmtEquipes->execute([$idPoule, $idCat, $id_tournoi]);
                    $equipes = $stmtEquipes->fetchAll(PDO::FETCH_ASSOC);

                    $content .= "  Équipes (" . count($equipes) . ") :\n";
                    foreach ($equipes as $eq) {
                        $content .= "    • " . $eq['nom'] . "\n";
                    }

                    // Matchs
                    $stmtMatchs = $pdo->prepare("
                        SELECT mp.*, e1.nom as nom_eq1, e2.nom as nom_eq2
                        FROM match_poule mp
                        LEFT JOIN equipe e1 ON e1.id_equipe = mp.id_equipe_1 AND e1.id_tournoi = mp.id_tournoi
                        LEFT JOIN equipe e2 ON e2.id_equipe = mp.id_equipe_2 AND e2.id_tournoi = mp.id_tournoi
                        WHERE mp.id_poule = ? AND mp.id_categorie = ? AND mp.id_tournoi = ?
                        ORDER BY mp.id_match
                    ");
                    $stmtMatchs->execute([$idPoule, $idCat, $id_tournoi]);
                    $matchs = $stmtMatchs->fetchAll(PDO::FETCH_ASSOC);

                    $content .= "\n  Matchs (" . count($matchs) . ") :\n";
                    foreach ($matchs as $m) {
                        $status = $m['status'] === 'termine' ? '✅' : ($m['status'] === 'en_cours' ? '🔴' : '⏳');
                        $eq1 = $m['nom_eq1'] ?? '?';
                        $eq2 = $m['nom_eq2'] ?? '?';

                        $content .= "    " . $status . " " . $eq1 . " vs " . $eq2 . "\n";

                        if (!empty($m['heure_debut'])) {
                            $content .= "       Heure: " . $m['heure_debut'];
                            if (!empty($m['terrain'])) {
                                $content .= " [Terrain " . $m['terrain'] . "]";
                            }
                            $content .= "\n";
                        }

                        if ($m['status'] === 'termine') {
                            $content .= "       Score: " . $m['score_equipe_1'] . " - " . $m['score_equipe_2'] . "\n";
                        }
                    }

                    // Classement poule (SIMPLIFIÉ)
                    $stmtClass = $pdo->prepare("
                        SELECT e.id_equipe, e.nom
                        FROM equipe e
                        WHERE e.id_poule = ? AND e.id_categorie = ? AND e.id_tournoi = ?
                        ORDER BY e.nom
                    ");
                    $stmtClass->execute([$idPoule, $idCat, $id_tournoi]);
                    $classement = $stmtClass->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($classement)) {
                        $content .= "\n  📋 Classement:\n";
                        $pos = 1;
                        foreach ($classement as $cl) {
                            $content .= sprintf("    %2d. %-40s\n", $pos++, $cl['nom']);
                        }
                    }

                    $content .= "\n";
                }
            }
        }
    }

    // ===== PHASE FINALE =====
    $stmtPF = $pdo->prepare("
        SELECT DISTINCT pf.id, pf.id_categorie, pf.nom, c.nom as nom_categorie
        FROM phases_finales pf
        LEFT JOIN categorie c ON c.id_categorie = pf.id_categorie AND c.id_tournoi = pf.id_tournoi
        WHERE pf.id_tournoi = ?
        ORDER BY pf.id_categorie
    ");
    $stmtPF->execute([$id_tournoi]);
    $phasesFinal = $stmtPF->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($phasesFinal)) {
        $content .= "\n" . str_repeat("=", 80) . "\n";
        $content .= "PHASE FINALE\n";
        $content .= str_repeat("=", 80) . "\n";

        foreach ($phasesFinal as $pf) {
            $idPF = $pf['id'];
            $nomCatPF = $pf['nom_categorie'] ?? 'Catégorie ' . $pf['id_categorie'];
            $nomPF = $pf['nom'] ?? 'Phase Finale';

            $content .= "\n" . str_repeat("-", 80) . "\n";
            $content .= "CATÉGORIE : " . $nomCatPF . " — " . $nomPF . "\n";
            $content .= str_repeat("-", 80) . "\n";

            // Matchs phase finale
// Matchs phase finale
$stmtMatchsPF = $pdo->prepare("
    SELECT mpf.*, 
           (SELECT nom_equipe FROM equipes_phase_finale WHERE id = mpf.equipe1_id) as nom_eq1,
           (SELECT nom_equipe FROM equipes_phase_finale WHERE id = mpf.equipe2_id) as nom_eq2
    FROM matchs_phase_finale mpf
    WHERE mpf.id_tournoi = ? AND mpf.id_phase_finale = ?
    ORDER BY mpf.round, mpf.sub_group, mpf.match_num
");
$stmtMatchsPF->execute([$id_tournoi, $idPF]);
$matchsPF = $stmtMatchsPF->fetchAll(PDO::FETCH_ASSOC);

if (empty($matchsPF)) {
    $content .= "  Aucun match phase finale.\n";
} else {
    $currentRound = null;
    // Mapping inversé : plus grand round = finale
$roundLabels = ['Finale', 'Demi-finales', 'Quarts', '1/8', '1/16', '1/32', '1/64', '1/128'];
$maxRound = 0;
if (!empty($matchsPF)) {
    $maxRound = max(array_column($matchsPF, 'round'));
}

    $rlcount = $maxRound;
    foreach ($matchsPF as $mpf) {
        if ($currentRound !== $mpf['round']) {
            $currentRound = $mpf['round'];
            $labelRound = $roundLabels[$rlcount--];
            $content .= "\n  🏆 " . $labelRound . ":\n";
        }

        $status = $mpf['statut_match'] === 'termine' ? '✅' : ($mpf['statut_match'] === 'en_cours' ? '🔴' : '⏳');
        $eq1 = $mpf['nom_eq1'] ?? $mpf['source_team1'] ?? 'TBD';
        $eq2 = $mpf['nom_eq2'] ?? $mpf['source_team2'] ?? 'TBD';

        $content .= "    " . $status . " " . $eq1 . " vs " . $eq2;

        if (!empty($mpf['heure_debut'])) {
            $content .= " [" . $mpf['heure_debut'] . "]";
        }
        if (!empty($mpf['terrain'])) {
            $content .= " (Terrain " . $mpf['terrain'] . ")";
        }
        $content .= "\n";

        if ($mpf['statut_match'] === 'termine' && !empty($mpf['score1']) && !empty($mpf['score2'])) {
            $content .= "       Score: " . $mpf['score1'] . " - " . $mpf['score2'] . "\n";
        }
    }
}
        }
    }

    $content .= "\n" . str_repeat("=", 80) . "\n";
    $content .= "Fin de l'export — " . $now->format('d/m/Y H:i:s') . "\n";
    $content .= str_repeat("=", 80) . "\n";

    echo $content;

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Erreur: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
?>