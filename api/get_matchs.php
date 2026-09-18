<?php
// api/get_matchs.php
header('Content-Type: application/json');
include __DIR__ . "/check_connected.php";
require 'db.php';

$id_tournoi = $_GET['id_tournoi'] ?? null;

if (!$id_tournoi) {
    echo json_encode(['success' => false, 'error' => 'id_tournoi manquant']);
    exit;
}

try {

$concatPoule = (DB_DRIVER === 'sqlite')
    ? "p.nom || ' / ' || p2.nom"
    : "CONCAT(p.nom, ' / ', p2.nom)";

$sql = "SELECT mp.*,
               c.nom AS nom_categorie,
               CASE
                   WHEN mp.id_poule_2 IS NOT NULL THEN $concatPoule
                   ELSE p.nom
               END AS nom_poule,
               e1.nom AS nom_equipe_1,
               e2.nom AS nom_equipe_2,
               e3.nom AS nom_equipe_3,
               e4.nom AS nom_equipe_4,
               (mp.id_equipe_3 IS NOT NULL AND mp.id_equipe_4 IS NOT NULL) AS is_salade
        FROM match_poule mp
        LEFT JOIN categorie c ON c.id_tournoi = mp.id_tournoi AND c.id_categorie = mp.id_categorie
        LEFT JOIN poule p ON p.id_tournoi = mp.id_tournoi AND p.id_categorie = mp.id_categorie AND p.id_poule = mp.id_poule
        LEFT JOIN poule p2 ON p2.id_tournoi = mp.id_tournoi AND p2.id_categorie = mp.id_categorie AND p2.id_poule = mp.id_poule_2
        LEFT JOIN equipe e1 ON e1.id_tournoi = mp.id_tournoi
                            AND e1.id_categorie = mp.id_categorie
                            AND e1.id_poule = mp.id_poule
                            AND e1.id_equipe = mp.id_equipe_1
        LEFT JOIN equipe e2 ON e2.id_tournoi = mp.id_tournoi
                            AND e2.id_categorie = mp.id_categorie
                            AND e2.id_poule = COALESCE(mp.id_poule_2, mp.id_poule)
                            AND e2.id_equipe = mp.id_equipe_2
        LEFT JOIN equipe e3 ON e3.id_tournoi = mp.id_tournoi
                            AND e3.id_categorie = mp.id_categorie
                            AND e3.id_poule = mp.id_poule
                            AND e3.id_equipe = mp.id_equipe_3
        LEFT JOIN equipe e4 ON e4.id_tournoi = mp.id_tournoi
                            AND e4.id_categorie = mp.id_categorie
                            AND e4.id_poule = mp.id_poule
                            AND e4.id_equipe = mp.id_equipe_4
        WHERE mp.id_tournoi = ?
        ORDER BY mp.ordre_affichage ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_tournoi]);
    $matchs = $stmt->fetchAll();

    // Post-traitement : pour chaque match, exposer un booléen pratique
    // (champ "is_salade") déjà calculé en SQL ci-dessus, et s'assurer que
    // les colonnes id_equipe_3 / id_equipe_4 / nom_equipe_3 / nom_equipe_4
    // sont bien présentes dans la sortie JSON (elles le seront via mp.*).
    // Pour les matchs classiques (2 équipes), id_equipe_3 et id_equipe_4
    // resteront null — comportement strictement inchangé côté front.

    echo json_encode(['success' => true, 'matchs' => $matchs]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
