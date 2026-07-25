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

        function formatScore($score1, $score2)
        {
            $sets1 = explode('*', $score1);
            $sets2 = explode('*', $score2);

            $nbSets = max(count($sets1), count($sets2));
            $parts = [];

            for ($i = 0; $i < $nbSets; $i++) {
                $s1 = $sets1[$i] ?? '0';
                $s2 = $sets2[$i] ?? '0';

                if ($s1 === '0' && $s2 === '0' && !empty($parts)) {
                    continue;
                }

                $parts[] = $s1 . ' - ' . $s2;
            }

            return implode(' | ', $parts);
        }

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
                        LEFT JOIN equipe e1 ON e1.id_equipe = mp.id_equipe_1 
                            AND e1.id_tournoi = mp.id_tournoi
                            AND e1.id_categorie = mp.id_categorie
                            AND e1.id_poule = mp.id_poule
                        LEFT JOIN equipe e2 ON e2.id_equipe = mp.id_equipe_2 
                            AND e2.id_tournoi = mp.id_tournoi
                            AND e2.id_categorie = mp.id_categorie
                            AND e2.id_poule = mp.id_poule
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
                            $content .= "       Score: " . formatScore($m['score_equipe_1'], $m['score_equipe_2']) . "\n";
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
                $roundLabels = ['Finale', 'Demi-finales', 'Quarts', '1/8', '1/16', '1/32', '1/64', '1/128'];
                $maxRound = max(array_column($matchsPF, 'round'));
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
                        $content .= "       Score: " . formatScore($mpf['score1'], $mpf['score2']) . "\n";
                    }
                }
            }
        }
    }

    // ===== CLASSEMENT FINAL =====
    $stmtClassFinal = $pdo->prepare("
        SELECT pf.id AS id_phase_finale, pf.id_categorie, pf.nom AS nom_phase, c.nom AS nom_categorie
        FROM phases_finales pf
        JOIN categorie c ON c.id_categorie = pf.id_categorie AND c.id_tournoi = pf.id_tournoi
        WHERE pf.id_tournoi = ?
          AND pf.type_bracket = 'classement_complet'
        ORDER BY pf.id_categorie
    ");
    $stmtClassFinal->execute([$id_tournoi]);
    $phasesClassement = $stmtClassFinal->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($phasesClassement)) {
        $content .= "\n" . str_repeat("=", 80) . "\n";
        $content .= "CLASSEMENT FINAL\n";
        $content .= str_repeat("=", 80) . "\n";

        foreach ($phasesClassement as $pf) {
            $idPhaseFinale = $pf['id_phase_finale'];
            $nomCatPF = $pf['nom_categorie'];
            $nomPF = $pf['nom_phase'];

            // Récupérer tous les matchs terminés avec classement
            $stmtMatchs = $pdo->prepare("
                SELECT winner_equipe_id, loser_equipe_id, classement_min, classement_max
                FROM matchs_phase_finale
                WHERE id_phase_finale = ?
                  AND winner_equipe_id IS NOT NULL
                  AND classement_min IS NOT NULL
                  AND classement_max IS NOT NULL
            ");
            $stmtMatchs->execute([$idPhaseFinale]);
            $matchs = $stmtMatchs->fetchAll(PDO::FETCH_ASSOC);

            if (empty($matchs)) {
                continue;
            }

            // Construire les positions
            $positions = [];
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

            // Récupérer les noms des équipes
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

            // Trier par position croissante
            ksort($positions);

            $content .= "\n" . str_repeat("-", 80) . "\n";
            $content .= "CATÉGORIE : " . $nomCatPF . " — " . $nomPF . "\n";
            $content .= str_repeat("-", 80) . "\n";

            $content .= "  🥇 CLASSEMENT:\n";
            foreach ($positions as $position => $idEpf) {
                $eq = $equipesParId[$idEpf] ?? null;
                $nomEq = $eq ? $eq['nom_equipe'] : '???';
                $isBye = $eq ? (bool)$eq['is_bye'] : false;

                // Ne pas afficher les BYE
                if ($isBye) {
                    continue;
                }

                $medal = '';
                if ($position == 1) $medal = '🥇 ';
                elseif ($position == 2) $medal = '🥈 ';
                elseif ($position == 3) $medal = '🥉 ';

                $content .= sprintf("    %s%2d. %-50s\n", $medal, $position, $nomEq);
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