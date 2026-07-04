<?php
// api/generer_matchs.php
// Retourne l'ordre proposé des matchs (sans les inserer en base) 
// avec alternance categorie / poule

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

    $matchsParPouleParCat = []; // structure: [id_categorie][id_poule] => [liste de matchs]

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

            // Récupérer le pattern d'ordre selon le nombre d'equipes
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

            $matchsPoule = [];
            $numMatch = 1;
            foreach ($matches as $m) {
                $pos1 = (int)$m[1]; // position dans la poule (1-indexed)
                $pos2 = (int)$m[2];

                // Retrouver les équipes correspondantes (equipes ordonnées par id_equipe)
                $equipe1 = $equipes[$pos1 - 1] ?? null;
                $equipe2 = $equipes[$pos2 - 1] ?? null;

                if (!$equipe1 || !$equipe2) continue;

                $matchsPoule[] = [
                    'num_match_poule' => $numMatch,
                    'id_categorie' => $id_categorie,
                    'nom_categorie' => $cat['nom'],
                    'id_poule' => $id_poule,
                    'nom_poule' => $poule['nom'],
                    'id_equipe_1' => $equipe1['id_equipe'],
                    'nom_equipe_1' => $equipe1['nom'],
                    'id_equipe_2' => $equipe2['id_equipe'],
                    'nom_equipe_2' => $equipe2['nom'],
                ];
                $numMatch++;
            }

            $matchsParPouleParCat[$id_categorie][$id_poule] = $matchsPoule;
        }
    }

    // Algorithme d'alternance : cat1-poule1, cat1-poule2, cat2-poule1, cat2-poule2...
    // On tourne round-robin sur (categorie, poule) et on prend un match a chaque tour
    $listeCatPoule = [];
    foreach ($matchsParPouleParCat as $id_cat => $poulesArr) {
        foreach ($poulesArr as $id_poule => $matchsArr) {
            $listeCatPoule[] = ['id_categorie' => $id_cat, 'id_poule' => $id_poule];
        }
    }

    $matchsFinal = [];
    $indexParPoule = []; // pointeur pour savoir où on en est dans chaque poule
    foreach ($listeCatPoule as $cp) {
        $indexParPoule[$cp['id_categorie'] . '_' . $cp['id_poule']] = 0;
    }

    $resteMatchs = true;
    $ordreGlobal = 1;

    while ($resteMatchs) {
        $resteMatchs = false;
        foreach ($listeCatPoule as $cp) {
            $key = $cp['id_categorie'] . '_' . $cp['id_poule'];
            $idx = $indexParPoule[$key];
            $matchsDePoule = $matchsParPouleParCat[$cp['id_categorie']][$cp['id_poule']];

            if ($idx < count($matchsDePoule)) {
                $match = $matchsDePoule[$idx];
                $match['ordre_global'] = $ordreGlobal;
                $matchsFinal[] = $match;
                $ordreGlobal++;
                $indexParPoule[$key]++;
                $resteMatchs = true;
            }
        }
    }

    echo json_encode(['success' => true, 'matchs' => $matchsFinal]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
