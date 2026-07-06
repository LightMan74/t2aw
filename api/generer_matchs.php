<?php
// api/generer_matchs.php
// Retourne l'ordre proposé des matchs (sans les insérer en base)
// avec alternance tour par tour entre les poules (et catégories)

header('Content-Type: application/json');
require 'db.php';

$id_tournoi = $_POST['id_tournoi'] ?? null;

if (!$id_tournoi) {
    echo json_encode(['success' => false, 'error' => 'id_tournoi manquant']);
    exit;
}

try {
    // Récupérer catégories du tournoi
    $stmtCat = $pdo->prepare("SELECT * FROM categorie WHERE id_tournoi = ? ORDER BY id_categorie ASC");
    $stmtCat->execute([$id_tournoi]);
    $categories = $stmtCat->fetchAll();

    // Structure : $donneesPoules[id_categorie][id_poule] = [
    //     'nom_categorie' => ..., 'nom_poule' => ...,
    //     'tours' => [ [match, match, ...], [match, match, ...], ... ] // découpé par tour
    // ]
    $donneesPoules = [];
    $maxToursGlobal = 0;

    foreach ($categories as $cat) {
        $id_categorie = $cat['id_categorie'];

        // Récupérer les poules de cette catégorie
        $stmtPoule = $pdo->prepare("SELECT * FROM poule WHERE id_tournoi = ? AND id_categorie = ? ORDER BY id_poule ASC");
        $stmtPoule->execute([$id_tournoi, $id_categorie]);
        $poules = $stmtPoule->fetchAll();

        foreach ($poules as $poule) {
            $id_poule = $poule['id_poule'];

            // Récupérer les équipes de cette poule
            $stmtEquipe = $pdo->prepare("SELECT * FROM equipe WHERE id_tournoi = ? AND id_categorie = ? AND id_poule = ? ORDER BY id_equipe ASC");
            $stmtEquipe->execute([$id_tournoi, $id_categorie, $id_poule]);
            $equipes = $stmtEquipe->fetchAll();

            $nbEquipes = count($equipes);
            if ($nbEquipes < 2) continue;

            // Récupérer le pattern d'ordre selon le nombre d'équipes
            $stmtOrdre = $pdo->prepare("SELECT ordre FROM ordre_match_poule WHERE nbre_equipe = ?");
            $stmtOrdre->execute([$nbEquipes]);
            $ordreRow = $stmtOrdre->fetch();

            if (!$ordreRow) {
                // Pas de pattern défini pour ce nombre d'équipes
                continue;
            }

            // Parser le pattern "match1(1;2);match2(3;4);..."
            $pattern = $ordreRow['ordre'];
            preg_match_all('/match\d+\((\d+);(\d+)\)/', $pattern, $matches, PREG_SET_ORDER);

            // Construire la liste plate des matchs de la poule (dans l'ordre du pattern)
            $matchsPoulePlats = [];
            foreach ($matches as $m) {
                $pos1 = (int)$m[1];
                $pos2 = (int)$m[2];

                $equipe1 = $equipes[$pos1 - 1] ?? null;
                $equipe2 = $equipes[$pos2 - 1] ?? null;

                if (!$equipe1 || !$equipe2) continue;

                $matchsPoulePlats[] = [
                    'id_categorie' => $id_categorie,
                    'nom_categorie' => $cat['nom'],
                    'id_poule' => $id_poule,
                    'nom_poule' => $poule['nom'],
                    'id_equipe_1' => $equipe1['id_equipe'],
                    'nom_equipe_1' => $equipe1['nom'],
                    'id_equipe_2' => $equipe2['id_equipe'],
                    'nom_equipe_2' => $equipe2['nom'],
                ];
            }

            // Découper en tours : 
            // - poule paire  (N équipes) => N/2 matchs par tour
            // - poule impaire (N équipes) => (N-1)/2 matchs par tour
            $matchsParTour = ($nbEquipes % 2 === 0) ? intdiv($nbEquipes, 2) : intdiv($nbEquipes - 1, 2);

            $tours = array_chunk($matchsPoulePlats, $matchsParTour);

            $donneesPoules[$id_categorie][$id_poule] = [
                'nom_categorie' => $cat['nom'],
                'nom_poule' => $poule['nom'],
                'tours' => $tours,
                'num_match_poule' => 1, // compteur "Match X" affiché pour cette poule
            ];

            $maxToursGlobal = max($maxToursGlobal, count($tours));
        }
    }

    // Alternance : Tour1 (toutes les poules, catégorie par catégorie), puis Tour2, etc.
    $matchsFinal = [];
    $ordreGlobal = 1;

    for ($numTour = 0; $numTour < $maxToursGlobal; $numTour++) {
        foreach ($donneesPoules as $id_categorie => &$poulesArr) {
            foreach ($poulesArr as $id_poule => &$dataPoule) {
                if (!isset($dataPoule['tours'][$numTour])) {
                    continue; // cette poule n'a plus de tour à ce niveau
                }

                foreach ($dataPoule['tours'][$numTour] as $match) {
                    $match['num_match_poule'] = $dataPoule['num_match_poule'];
                    $match['num_tour'] = $numTour + 1;
                    $match['id_poule'] = $id_poule;
                    $match['ordre_global'] = $ordreGlobal;

                    $matchsFinal[] = $match;

                    $dataPoule['num_match_poule']++;
                    $ordreGlobal++;
                }
            }
            unset($dataPoule);
        }
        unset($poulesArr);
    }

    echo json_encode(['success' => true, 'matchs' => $matchsFinal]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}