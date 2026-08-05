<?php
/**
 * export_tournoi_pdf.php
 * Export d'un tournoi en PDF avec FPDF
 */

require('fpdf/fpdf.php'); // Adaptez le chemin selon votre installation

$id_tournoi = $_GET['id_tournoi'] ?? null;

if (!$id_tournoi) {
    http_response_code(400);
    die("Erreur : id_tournoi manquant");
}

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
    die("Tournoi introuvable");
}

$now = new DateTime();

// ===== Classe PDF personnalisée =====
class PDF extends FPDF
{
    public $titreTournoi = '';

    function Header()
    {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, utf8_decode($this->titreTournoi), 0, 1, 'C');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 6, utf8_decode('Export genere le ' . date('d/m/Y H:i:s')), 0, 1, 'C');
        $this->Ln(3);
        $this->SetDrawColor(0, 0, 0);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(5);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function TitreSection($texte)
    {
        $this->SetFont('Arial', 'B', 13);
        $this->SetFillColor(40, 40, 40);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 8, utf8_decode($texte), 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(2);
    }

    function TitreCategorie($texte)
    {
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(220, 220, 220);
        $this->Cell(0, 7, utf8_decode($texte), 0, 1, 'L', true);
        $this->Ln(1);
    }

    function TitrePoule($texte)
    {
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(0, 70, 140);
        $this->Cell(0, 6, utf8_decode($texte), 0, 1, 'L');
        $this->SetTextColor(0, 0, 0);
    }

    function CheckPageBreak($hauteur = 10)
    {
        if ($this->GetY() + $hauteur > $this->PageBreakTrigger) {
            $this->AddPage();
        }
    }
}

$pdf = new PDF();
$pdf->titreTournoi = $tournoi['nom'];
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

// ===== CATEGORIES / POULES =====
$stmt = $pdo->prepare("
    SELECT DISTINCT id_categorie, nom 
    FROM categorie 
    WHERE id_tournoi = ? 
    ORDER BY id_categorie
");
$stmt->execute([$id_tournoi]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($categories)) {
    $pdf->TitreSection('PHASE POULES');

    foreach ($categories as $cat) {
        $idCat = $cat['id_categorie'];
        $nomCat = $cat['nom'];

        $pdf->CheckPageBreak(15);
        $pdf->TitreCategorie('Categorie : ' . $nomCat);

        // Poules
        $stmtPoules = $pdo->prepare("
            SELECT DISTINCT id_poule, nom 
            FROM poule 
            WHERE id_categorie = ? AND id_tournoi = ?
            ORDER BY id_poule
        ");
        $stmtPoules->execute([$idCat, $id_tournoi]);
        $poules = $stmtPoules->fetchAll(PDO::FETCH_ASSOC);

        if (empty($poules)) {
            $pdf->SetFont('Arial', 'I', 9);
            $pdf->Cell(0, 6, 'Aucune poule.', 0, 1);
        }

        foreach ($poules as $poule) {
            $idPoule = $poule['id_poule'];
            $nomPoule = $poule['nom'];

            $pdf->CheckPageBreak(20);
            $pdf->TitrePoule('Poule ' . $nomPoule);

            // Equipes
            $stmtEquipes = $pdo->prepare("
                SELECT nom FROM equipe 
                WHERE id_poule = ? AND id_categorie = ? AND id_tournoi = ?
                ORDER BY id_equipe
            ");
            $stmtEquipes->execute([$idPoule, $idCat, $id_tournoi]);
            $equipes = $stmtEquipes->fetchAll(PDO::FETCH_ASSOC);

            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(0, 5, 'Equipes (' . count($equipes) . ') :', 0, 1);
            $pdf->SetFont('Arial', '', 9);
            foreach ($equipes as $eq) {
                $pdf->CheckPageBreak(6);
                $pdf->Cell(5);
                $pdf->Cell(0, 5, '- ' . utf8_decode($eq['nom']), 0, 1);
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

            $pdf->Ln(2);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->CheckPageBreak(6);
            $pdf->Cell(0, 5, 'Matchs (' . count($matchs) . ') :', 0, 1);
            $pdf->SetFont('Arial', '', 9);

            foreach ($matchs as $m) {
                $pdf->CheckPageBreak(10);

                $status = $m['status'] === 'termine' ? '[T]' : ($m['status'] === 'en_cours' ? '[EC]' : '[.]');
                $eq1 = $m['nom_eq1'] ?? '?';
                $eq2 = $m['nom_eq2'] ?? '?';

                $ligne = $status . ' ' . $eq1 . ' vs ' . $eq2;

                if (!empty($m['heure_debut'])) {
                    $ligne .= '  (' . $m['heure_debut'];
                    if (!empty($m['terrain'])) {
                        $ligne .= ' - Terrain ' . $m['terrain'];
                    }
                    $ligne .= ')';
                }

                $pdf->Cell(5);
                $pdf->Cell(140, 5, utf8_decode($ligne), 0, 0);

                if ($m['status'] === 'termine') {
                    $pdf->Cell(0, 5, utf8_decode('Score: ' . formatScore($m['score_equipe_1'], $m['score_equipe_2'])), 0, 1);
                } else {
                    $pdf->Ln();
                }
            }

            // Classement poule (mêmes règles que view_classement.php)
            $stmtClassEquipes = $pdo->prepare("
                SELECT e.id_equipe, e.nom
                FROM equipe e
                WHERE e.id_poule = ? AND e.id_categorie = ? AND e.id_tournoi = ?
            ");
            $stmtClassEquipes->execute([$idPoule, $idCat, $id_tournoi]);
            $equipesClassement = $stmtClassEquipes->fetchAll(PDO::FETCH_ASSOC);

            $stats = [];
            foreach ($equipesClassement as $eq) {
                $stats[$eq['id_equipe']] = [
                    'nom' => $eq['nom'],
                    'id_equipe' => $eq['id_equipe'],
                    'joues' => 0,
                    'victoires' => 0,
                    'defaites' => 0,
                    'sets_gagnes' => 0,
                    'sets_perdus' => 0,
                    'points_marques' => 0,
                    'points_encaisses' => 0,
                ];
            }

            // Matchs terminés de cette poule (y compris les matchs utilisant id_poule_2).
            $stmtClassMatchs = $pdo->prepare("
                SELECT * FROM match_poule
                WHERE id_tournoi = ?
                  AND id_categorie = ?
                  AND (id_poule = ? OR id_poule_2 = ?)
                  AND status = 'termine'
            ");
            $stmtClassMatchs->execute([$id_tournoi, $idCat, $idPoule, $idPoule]);
            $matchsClassement = $stmtClassMatchs->fetchAll(PDO::FETCH_ASSOC);

            foreach ($matchsClassement as $m) {
                $e1 = $m['id_equipe_1'];
                $e2 = $m['id_equipe_2'];

                // Un match peut référencer deux poules : ne comptabiliser que les
                // équipes appartenant réellement à la poule en cours.
                $pouleE1 = $m['id_poule'];
                $pouleE2 = $m['id_poule_2'] ?? $m['id_poule'];
                $e1DansPoule = ($pouleE1 == $idPoule) && isset($stats[$e1]);
                $e2DansPoule = ($pouleE2 == $idPoule) && isset($stats[$e2]);

                if (!$e1DansPoule && !$e2DansPoule) {
                    continue;
                }

                $sets1 = explode('*', (string)$m['score_equipe_1']);
                $sets2 = explode('*', (string)$m['score_equipe_2']);
                $setsGagnes1 = 0;
                $setsGagnes2 = 0;
                $pointsMarques1 = 0;
                $pointsMarques2 = 0;

                // Seuls les sets présents dans les deux scores sont comparés,
                // comme dans view_classement.php.
                $nbSets = min(count($sets1), count($sets2));
                for ($i = 0; $i < $nbSets; $i++) {
                    $p1 = (int)$sets1[$i];
                    $p2 = (int)$sets2[$i];
                    $pointsMarques1 += $p1;
                    $pointsMarques2 += $p2;
                    if ($p1 > $p2) {
                        $setsGagnes1++;
                    } elseif ($p2 > $p1) {
                        $setsGagnes2++;
                    }
                }

                $victoireE1 = $setsGagnes1 > $setsGagnes2;
                $victoireE2 = $setsGagnes2 > $setsGagnes1;

                if ($e1DansPoule) {
                    $stats[$e1]['joues']++;
                    $stats[$e1]['sets_gagnes'] += $setsGagnes1;
                    $stats[$e1]['sets_perdus'] += $setsGagnes2;
                    $stats[$e1]['points_marques'] += $pointsMarques1;
                    $stats[$e1]['points_encaisses'] += $pointsMarques2;
                    if ($victoireE1) {
                        $stats[$e1]['victoires']++;
                    } elseif ($victoireE2) {
                        $stats[$e1]['defaites']++;
                    }
                }

                if ($e2DansPoule) {
                    $stats[$e2]['joues']++;
                    $stats[$e2]['sets_gagnes'] += $setsGagnes2;
                    $stats[$e2]['sets_perdus'] += $setsGagnes1;
                    $stats[$e2]['points_marques'] += $pointsMarques2;
                    $stats[$e2]['points_encaisses'] += $pointsMarques1;
                    if ($victoireE2) {
                        $stats[$e2]['victoires']++;
                    } elseif ($victoireE1) {
                        $stats[$e2]['defaites']++;
                    }
                }
            }

            // Tri identique à view_classement.php : victoires, différence de
            // sets, puis différence de points, toutes décroissantes.
            $classement = array_values($stats);
            usort($classement, function ($a, $b) {
                if ($a['victoires'] !== $b['victoires']) {
                    return $b['victoires'] - $a['victoires'];
                }
                $diffSetsA = $a['sets_gagnes'] - $a['sets_perdus'];
                $diffSetsB = $b['sets_gagnes'] - $b['sets_perdus'];
                if ($diffSetsA !== $diffSetsB) {
                    return $diffSetsB - $diffSetsA;
                }
                $diffPtsA = $a['points_marques'] - $a['points_encaisses'];
                $diffPtsB = $b['points_marques'] - $b['points_encaisses'];
                return $diffPtsB - $diffPtsA;
            });

            if (!empty($classement)) {
                $pdf->Ln(2);
                $pdf->CheckPageBreak(12);
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->Cell(0, 5, 'Classement :', 0, 1);

                // Tableau compact (largeur totale 190 mm, adaptée à l'A4).
                $largeurs = [10, 83, 12, 12, 20, 25, 28];
                $entetes = ['Pos.', 'Equipe', 'V', 'D', 'Sets +/-', 'Pts +/-', 'Joues'];
                $pdf->SetFillColor(220, 220, 220);
                foreach ($entetes as $i => $entete) {
                    $pdf->Cell($largeurs[$i], 6, utf8_decode($entete), 1, 0, $i === 1 ? 'L' : 'C', true);
                }
                $pdf->Ln();

                $pdf->SetFont('Arial', '', 8);
                $pos = 1;
                foreach ($classement as $cl) {
                    $pdf->CheckPageBreak(6);
                    $diffSets = $cl['sets_gagnes'] - $cl['sets_perdus'];
                    $diffPts = $cl['points_marques'] - $cl['points_encaisses'];
                    $valeurs = [
                        (string)$pos++,
                        utf8_decode($cl['nom']),
                        (string)$cl['victoires'],
                        (string)$cl['defaites'],
                        $cl['sets_gagnes'] . ' / ' . $cl['sets_perdus'] . ' (' . ($diffSets >= 0 ? '+' : '') . $diffSets . ')',
                        $cl['points_marques'] . ' / ' . $cl['points_encaisses'] . ' (' . ($diffPts >= 0 ? '+' : '') . $diffPts . ')',
                        (string)$cl['joues'],
                    ];
                    foreach ($valeurs as $i => $valeur) {
                        $pdf->Cell($largeurs[$i], 6, $valeur, 1, 0, $i === 1 ? 'L' : 'C');
                    }
                    $pdf->Ln();
                }
            }

            $pdf->Ln(4);
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
    $pdf->AddPage();
    $pdf->TitreSection('PHASE FINALE');

    foreach ($phasesFinal as $pf) {
        $idPF = $pf['id'];
        $nomCatPF = $pf['nom_categorie'] ?? 'Categorie ' . $pf['id_categorie'];
        $nomPF = $pf['nom'] ?? 'Phase Finale';

        $pdf->CheckPageBreak(15);
        $pdf->TitreCategorie('Categorie : ' . $nomCatPF . ' - ' . $nomPF);

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
            $pdf->SetFont('Arial', 'I', 9);
            $pdf->Cell(0, 6, 'Aucun match phase finale.', 0, 1);
            continue;
        }

        $currentRound = null;
        $roundLabels = ['Finale', 'Demi-finales', 'Quarts', '1/8', '1/16', '1/32', '1/64', '1/128'];
        $maxRound = max(array_column($matchsPF, 'round'));
        $rlcount = $maxRound;

        foreach ($matchsPF as $mpf) {
            if ($currentRound !== $mpf['round']) {
                $currentRound = $mpf['round'];
                $labelRound = $roundLabels[$rlcount--] ?? ('Round ' . $mpf['round']);
                $pdf->CheckPageBreak(10);
                $pdf->Ln(2);
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->SetTextColor(140, 0, 0);
                $pdf->Cell(0, 6, utf8_decode($labelRound), 0, 1);
                $pdf->SetTextColor(0, 0, 0);
            }

            $status = $mpf['statut_match'] === 'termine' ? '[V]' : ($mpf['statut_match'] === 'en_cours' ? '[EC]' : '[.]');
            $eq1 = $mpf['nom_eq1'] ?? $mpf['source_team1'] ?? 'TBD';
            $eq2 = $mpf['nom_eq2'] ?? $mpf['source_team2'] ?? 'TBD';

            $ligne = $status . ' ' . $eq1 . ' vs ' . $eq2;

            if (!empty($mpf['heure_debut'])) {
                $ligne .= ' [' . $mpf['heure_debut'] . ']';
            }
            if (!empty($mpf['terrain'])) {
                $ligne .= ' (Terrain ' . $mpf['terrain'] . ')';
            }

            $pdf->CheckPageBreak(8);
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(5);
            $pdf->Cell(140, 5, utf8_decode($ligne), 0, 0);

            if ($mpf['statut_match'] === 'termine' && !empty($mpf['score1']) && !empty($mpf['score2'])) {
                $pdf->Cell(0, 5, utf8_decode('Score: ' . formatScore($mpf['score1'], $mpf['score2'])), 0, 1);
            } else {
                $pdf->Ln();
            }
        }

        $pdf->Ln(4);
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
    $pdf->AddPage();
    $pdf->TitreSection('CLASSEMENT FINAL');

    foreach ($phasesClassement as $pf) {
        $idPhaseFinale = $pf['id_phase_finale'];
        $nomCatPF = $pf['nom_categorie'];
        $nomPF = $pf['nom_phase'];

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

        ksort($positions);

        $pdf->CheckPageBreak(15);
        $pdf->TitreCategorie('Categorie : ' . $nomCatPF . ' - ' . $nomPF);

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(0, 6, 'Classement :', 0, 1);
        $pdf->SetFont('Arial', '', 9);

        foreach ($positions as $position => $idEpf) {
            $eq = $equipesParId[$idEpf] ?? null;
            $nomEq = $eq ? $eq['nom_equipe'] : '???';
            $isBye = $eq ? (bool)$eq['is_bye'] : false;

            if ($isBye) {
                continue;
            }

            $medal = '';
            if ($position == 1) $medal = '1er - ';
            elseif ($position == 2) $medal = '2eme - ';
            elseif ($position == 3) $medal = '3eme - ';
            else $medal = $position . 'eme - ';

            $pdf->CheckPageBreak(6);
            $pdf->Cell(5);
            $pdf->Cell(0, 5, utf8_decode($medal . $nomEq), 0, 1);
        }

        $pdf->Ln(4);
    }
}

// ===== Génération finale =====
$filename = 'Export_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $tournoi['nom']) . '_' . $now->format('Y-m-d_H-i-s') . '.pdf';

$pdf->Output('D', $filename);