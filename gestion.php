<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion du tournoi</title>
    <link rel="stylesheet" href="css/var.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dark-mode.css">
    <link rel="stylesheet" href="css/phase_final.css">
    <style>
    .tournoi-tab-section {
        display: none
    }

    .tournoi-tab-section.is-active {
        display: block
    }

    .tournoi-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin: 12px 0 20px
    }

    .tournoi-tabs button[aria-selected="true"] {
        font-weight: 700
    }
    </style>
</head>

<body>
    <div class="container">
        <nav><?php include 'menu.php'; ?></nav>

        <?php
$tournoi_id = isset($_GET['id_tournoi']) ? (int) $_GET['id_tournoi'] : 0;
$tournoi_troissets_match = 1;
if ($tournoi_id > 0) {
    $stmt = $pdo->prepare("SELECT troissets FROM parametre WHERE id_tournoi = :id");
    $stmt->execute(['id' => $tournoi_id]);
    $parametres = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($parametres !== false && isset($parametres['troissets'])) $tournoi_troissets_match = $parametres['troissets'];
}
$hiddenSets = ((int) $tournoi_troissets_match > 1) ? '' : 'hidden';
?>


        <input type="number" id="id_tournoi" value="<?= htmlspecialchars((string)$tournoi_id, ENT_QUOTES, 'UTF-8') ?>" hidden>
        <div class="tournoi-tabs" role="tablist" aria-label="Sections du tournoi">
            <button type="button" role="tab" data-tournoi-tab="generation" aria-controls="section-generation">Génération Matchs de Poule</button>
            <!-- <button type="button" role="tab" data-tournoi-tab="poules" aria-controls="section-poules">Matchs de poule</button> -->
            <button type="button" role="tab" data-tournoi-tab="phase-final" aria-controls="section-phase-final">Génération Matchs de Phase finale</button>
        </div>
        <section id="section-poules" class="tournoi-tab-section" data-tournoi-section="poules">
            <h1>Gestion des matchs de poule</h1>

            <!-- <label>ID Tournoi :</label> -->
            <!-- <input type="number" id="id_tournoi" value="1" style="width:100px; margin-bottom:15px;"> -->
            <!-- <button onclick="chargerMatchs()">Charger les matchs</button> -->
            <div class="container-heure-message">
                <div class="option-heure-manuelle">

                    <input type="time" id="heureManuelleInput" style="display:none;">
                    <div class="toggle-btn">
                        <input type="checkbox" id="heureManuelleCheckbox" onchange="toggleHeureManuelle()">
                        <label for="heureManuelleCheckbox" class="toggle-btn-label">Heure manuelle</label>
                    </div>

                    <div class="toggle-btn">
                        <input type="checkbox" id="matchtermineCheckbox" onchange="togglematchtermine()">
                        <label for="matchtermineCheckbox" class="toggle-btn-label">Voir Match terminé</label>
                    </div>

                    <!-- <div class="toggle-btn">
                        <input type="checkbox" id="decalageTousTerrainsListePF_phase_1">
                        <label for="decalageTousTerrainsListePF_phase_1" class="toggle-btn-label">Appliquer le décalage horaire à tous les terrains</label>
                    </div> -->

                    <div class="toggle-btn">
                        <!-- <button onclick="autosaveandreloadListePF()" class="toggle-btn-label">Enregistrer</button> -->
                        <label for="decalageTousTerrains" onclick="autosaveandreload()" class="toggle-btn-label">Enregistrer</label>
                    </div>

                </div>

                <div id="message"></div>
            </div>

            <table id="table-matchs">
                <thead>
                    <tr>
                        <th>
                            <nobr>Catégorie</nobr>
                        </th>
                        <th>
                            <nobr>Poule</nobr>
                        </th>
                        <th>
                            <nobr>Match N°</nobr>
                        </th>
                        <th>
                            <nobr>Equipe 1</nobr>
                        </th>
                        <th>
                            <nobr>SCORE</nobr>
                        </th>
                        <th>
                            <nobr>Equipe 2</nobr>
                        </th>
                        <th>
                            <nobr>Terrain</nobr>
                        </th>
                        <th>
                            <nobr>Statut</nobr>
                        </th>
                        <th>
                            <nobr>Heure début</nobr>
                        </th>
                        <th>------</th> <!-- Colonne pour l'icône de sauvegarde -->
                    </tr>
                </thead>
                <tbody id="corps-table"></tbody>
            </table>
        </section>
        <section id="section-generation" class="tournoi-tab-section" data-tournoi-section="generation">
            <h1>Génération de l'ordre des matchs de poule</h1>
            <button onclick="chargerMatchs()" hidden>Charger / Générer l'ordre</button>

            <center>
                <div id="legende-categories" style="margin-bottom:5px; display:flex; flex-wrap:wrap; gap:10px;"></div>
                <div id="legende-poules" style="margin-bottom:10px; display:flex; flex-wrap:wrap; gap:10px;"></div>
                <div style="max-width:30%">

                    <button onclick="ouvrirFormulaireAjout()" style="margin-bottom:15px;">+ Ajouter un match</button>

                    <!-- Formulaire d'ajout de match (masqué par défaut) -->
                    <div id="form-ajout-match" style="display:none; border:1px solid #ccc; padding:10px; margin-bottom:15px; text-align:left;">

                        <div style="margin-bottom:8px;">
                            <label>Catégorie :</label><br>
                            <select id="select-categorie" onchange="onCategorieChange()" style="width:100%;"></select>
                        </div>

                        <div style="margin-bottom:8px;">
                            <label>
                                <input type="checkbox" id="check-inter-poule" onchange="onInterPouleChange()">
                                Match inter-poules
                            </label>
                        </div>

                        <div style="margin-bottom:8px;">
                            <label>
                                <input type="checkbox" id="check-terrainlibre" onchange="onterrainlibreChange()" checked>
                                Terrain libre
                            </label>
                        </div>

                        <!-- Cas normal : une seule poule pour les 2 équipes -->
                        <div id="bloc-poule-unique" style="display:none;">
                            <div style="margin-bottom:8px;">
                                <label>Poule :</label><br>
                                <select id="select-poule" onchange="onPouleChange()" style="width:100%;"></select>
                            </div>
                            <div style="margin-bottom:8px;">
                                <label>Équipe 1 :</label><br>
                                <select id="select-equipe1" style="width:100%;"></select>
                            </div>
                            <div style="margin-bottom:8px;">
                                <label>Équipe 2 :</label><br>
                                <select id="select-equipe2" style="width:100%;"></select>
                            </div>
                        </div>

                        <!-- Cas inter-poules : poule et équipe séparées -->
                        <div id="bloc-inter-poule" style="display:none;">
                            <div style="border:1px dashed #999; padding:8px; margin-bottom:8px;">
                                <strong>Équipe 1</strong>
                                <div style="margin-bottom:8px;">
                                    <label>Poule :</label><br>
                                    <select id="select-poule-e1" onchange="onPouleE1Change()" style="width:100%;"></select>
                                </div>
                                <div>
                                    <label>Équipe :</label><br>
                                    <select id="select-equipe1-bis" style="width:100%;"></select>
                                </div>
                            </div>

                            <div style="border:1px dashed #999; padding:8px; margin-bottom:8px;">
                                <strong>Équipe 2</strong>
                                <div style="margin-bottom:8px;">
                                    <label>Poule :</label><br>
                                    <select id="select-poule-e2" onchange="onPouleE2Change()" style="width:100%;"></select>
                                </div>
                                <div>
                                    <label>Équipe :</label><br>
                                    <select id="select-equipe2-bis" style="width:100%;"></select>
                                </div>
                            </div>

                            <div style="margin-bottom:8px;">
                                <label>Intitulé du match (ex: Barrage, Inter-poule) :</label><br>
                                <input type="text" id="libelle-match-inter" placeholder="Ex: Barrage" style="width:100%;">
                            </div>
                        </div>

                        <button onclick="ajouterMatchManuel()">Ajouter à la liste</button>
                        <button onclick="fermerFormulaireAjout()">Annuler</button>
                    </div>

                    <div id="message-generation"></div>
                </div>
            </center>

            <div style="margin: 15px 0; text-align:center;">
                <label for="nb-terrains-auto">Nombre de terrains à utiliser pour la répartition auto :</label>
                <input type="number" id="nb-terrains-auto" min="1" style="width:60px;">
                <button onclick="repartitionAutomatique()">⚡ Répartition automatique dans les terrains</button>
                <button onclick="validerOrdre()">💾 Valider et enregistrer les matchs</button>
            </div>

            <h2 style="text-align:center;">Répartition par terrain (glisser-déposer)</h2>
            <div class="zones-terrains" id="zones-terrains">
                <!-- généré en JS -->
            </div>
        </section>
        <section id="section-phase-final" class="tournoi-tab-section" data-tournoi-section="phase-final">
            <header>
                <h1>🏸 Gestion des Phases Finales</h1>
            </header>
            <!-- <main class="container"> -->

            <!-- NOUVEAU : Liste de tous les matchs de phase finale (toutes phases confondues) -->
            <section id="section-liste-matchs-pf" class="section" hidden>
                <div class="section-header">
                    <h2>Tous les matchs de phase finale</h2>
                </div>
                <div class="container-heure-message">
                    <div class="option-heure-manuelle">

                        <input type="time" id="heureManuelleInputListePF" style="display:none;">
                        <div class="toggle-btn">
                            <input type="checkbox" id="heureManuelleCheckboxListePF" onchange="toggleHeureManuelleListePF()">
                            <label for="heureManuelleCheckboxListePF" class="toggle-btn-label">Heure manuelle</label>
                        </div>

                        <div class="toggle-btn">
                            <input type="checkbox" id="matchtermineCheckboxPF" onchange="togglematchtermine()">
                            <label for="matchtermineCheckboxPF" class="toggle-btn-label">Voir Match terminé</label>
                        </div>

                        <!-- <div class="toggle-btn">
                        <input type="checkbox" id="decalageTousTerrainsListePF_phase">
                        <label for="decalageTousTerrainsListePF_phase" class="toggle-btn-label">Appliquer le décalage horaire à tous les terrains</label>
                    </div> -->

                        <div class="toggle-btn">
                            <!-- <button onclick="autosaveandreloadListePF()" class="toggle-btn-label">Enregistrer</button> -->
                            <label for="decalageTousTerrainsListePF_phase" onclick="autosaveandreloadListePF()" class="toggle-btn-label">Enregistrer</label>
                        </div>

                    </div>

                    <div id="message-liste-matchs-pf"></div>
                </div>
                <table id="table-matchs-pf">
                    <thead>
                        <tr>
                            <th>
                                <nobr>Catégorie</nobr>
                            </th>
                            <th>
                                <nobr>Match N°</nobr>
                            </th>
                            <th>
                                <nobr>Round</nobr>
                            </th>
                            <th>
                                <nobr>Equipe 1</nobr>
                            </th>
                            <th>
                                <nobr>SCORE</nobr>
                            </th>
                            <th>
                                <nobr>Equipe 2</nobr>
                            </th>
                            <th>
                                <nobr>Terrain</nobr>
                            </th>
                            <th>
                                <nobr>Statut</nobr>
                            </th>
                            <th>
                                <nobr>Heure début</nobr>
                            </th>
                            <th>
                                <nobr>------</nobr>
                            </th> <!-- Colonne pour l'icône de sauvegarde -->
                        </tr>
                    </thead>
                    <tbody id="corps-table-pf"></tbody>
                </table>
            </section>

            <!-- Liste des phases finales existantes -->
            <section id="section-liste" class="section">
                <div class="section-header">
                    <h2>Phases finales existantes</h2>
                </div>
                <button id="btn-rafraichir-liste" class="btn btn-back hidden" type="button">Rafraîchir</button>
                <ul id="liste-phases"></ul>
            </section>

            <!-- Détail / Bracket -->
            <section id="section-bracket" class="section hidden">
                <div class="section-header">
                    <h2 id="titre-bracket">Bracket</h2>
                </div>

                <div id="equipes-panel">
                    <div class="section-header">
                        <h3>Équipes</h3>
                        <button type="button" id="btn-assigner-equipes" class="btn btn-modifier">Assigner les équipes</button>
                    </div>
                    <div id="liste-equipes" class="equipes-grid"></div>
                </div>

                <!-- Modal d'ajout d'équipe personnalisée -->
                <!-- Modal d'ajout d'équipe personnalisée -->
                <div id="modal-ajout-equipe" class="modal hidden">
                    <div class="modal-content">
                        <h2>Ajouter une équipe</h2>

                        <!-- Option 1 : Choisir une équipe existante -->
                        <div>
                            <label for="select-equipe-existante">Équipe existante :</label>
                            <select id="select-equipe-existante">
                                <option value="">— Créer une nouvelle équipe —</option>
                            </select>
                        </div>

                        <!-- Option 2 : Créer une nouvelle équipe -->
                        <div>
                            <label for="input-nom-equipe-custom">Nom de l'équipe :</label>
                            <input type="text" id="input-nom-equipe-custom" placeholder="Ex: Équipe X, Non renseignée..." autofocus>
                        </div>

                        <div>
                            <label for="select-poule-custom">Poule :</label>
                            <select id="select-poule-custom">
                                <option value="1">Poule 1</option>
                            </select>
                        </div>

                        <div class="modal-actions">
                            <button id="btn-confirmer-equipe-custom" class="btn btn-primary">Ajouter</button>
                            <button id="btn-annuler-equipe-custom" class="btn btn-secondary">Annuler</button>
                        </div>
                    </div>
                </div>

                <div id="reassignation-panel" class="hidden">
                    <div class="reassignation-container">
                        <!-- Équipes disponibles à gauche -->
                        <div class="equipes-disponibles-section">
                            <h3>Équipes disponibles</h3>
                            <ul id="liste-equipes-disponibles"></ul>
                        </div>

                        <!-- Équipes assignées à droite -->
                        <div class="equipes-assignees-section">
                            <h3>Ordre de départ</h3>
                            <ul id="liste-reassignation-equipes"></ul>
                            <div id="msg-assignation-equipes" class="msg"></div>
                        </div>
                    </div>
                    <button id="btn-enregistrer-reassignation" class="btn btn-primary">Enregistrer</button>
                </div>

                <div id="bracket-container" class="bracket-container"></div>

                <div id="simulation-panel" class="simulation-panel">
                    <label for="input-nb-rounds-simuler">
                        Nombre de rounds à sauter (simulation automatique)
                    </label>
                    <div class="form-row" style="align-items: flex-end; gap: 10px;">
                        <div class="form-group" style="margin-bottom:0">
                            <input type="number" id="input-nb-rounds-simuler" min="1" value="1">
                        </div>
                        <button type="button" id="btn-simuler-rounds" class="btn btn-modifier">⏩ Simuler et sauter ces rounds</button>
                    </div>
                    <p id="msg-simulation" class="msg"></p>
                </div>

            </section>
            <!-- Création d'une nouvelle phase finale -->
            <section id="section-creation" class="section">
                <div class="section-header">
                    <h2>Créer une phase finale</h2>
                </div>
                <form id="form-creation" class="form-group">

                    <input type="hidden" id="input-tournoi-id" value="<?= $tournoi_id ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="input-categorie">Catégorie</label>
                            <select id="input-categorie" required>
                                <option value="">— Choisir une catégorie —</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="input-type-bracket">Type de bracket</label>
                            <select id="input-type-bracket">
                                <option value="classement_complet">Classement complet (tous jouent tous les rounds)</option>
                                <option value="classique">Classique (élimination directe, seeding standard)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="input-nom">Nom de la phase</label>
                            <input type="text" id="input-nom" required readonly placeholder="Choisir une catégorie d'abord">
                        </div>

                        <div class="form-group">
                            <label for="input-nb-equipes">Nombre d'équipes</label>
                            <input type="number" id="input-nb-equipes" min="2" required placeholder="—">
                        </div>
                        <div class="form-group">
                            <label for="input_debut_ph">Debut de phase final par :</label>
                            <select id="input_debut_ph" required>
                            </select>
                            <label>
                                <span><input type="checkbox" id="input-reset-terrain" checked>
                                    Réinitialiser les terrains à 1 à chaque round</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" id="btn-creer" class="btn btn-primary">Créer la phase finale</button>
                    </div><!-- Ordre de départ (seeding) -->
                    <div id="ordre-equipes-panel" class="hidden" style="display:none;">
                        <h3>Ordre de départ (seeding)</h3>
                        <p class="text-muted">Glissez-déposez pour réordonner, ou utilisez les flèches ↑ ↓</p>
                        <ul id="liste-ordre-equipes" class="ordre-equipes-liste"></ul>
                    </div>


                </form>
                <p id="msg-creation" class="msg"></p>
            </section>

            <!-- </main> -->

            <!-- Modale de saisie de score -->
            <div id="modal-score" class="modal hidden">
                <div class="modal-content">
                    <p id="modal-match-info"></p><label>
                        <span id="label-equipe1" class="score-label-equipe">Équipe 1</span><input type="number" id="modal-score1s1" min="0"><span <?php echo $hiddenSets;?>> - <input type="number" id="modal-score1s2" min="0"> - <input type="number" id="modal-score1s3" min="0"></span>
                    </label>
                    <label>
                        <input type="number" id="modal-score2s1" min="0"><span <?php echo $hiddenSets;?>> - <input type="number" id="modal-score2s2" min="0"> - <input type="number" id="modal-score2s3" min="0"></span>
                        <span id="label-equipe2" class="score-label-equipe">Équipe 2</span>
                    </label>
                    <label style="">Terrain :
                        <input type="number" id="modal-terrain" min="1" placeholder="Terrain" style="display:">
                    </label>
                    <label style="display:">Statut du match :
                        <select id="modal-statut-match" style="display:">
                            <option value="planifie">Planifié</option>
                            <option value="en_cours">En jeu</option>
                            <option value="termine" selected>Terminé</option>
                        </select>
                    </label>

                    <div id="msg-modal" class="msg"></div>

                    <button id="btn-valider-score">Valider</button>
                    <button id="btn-annuler-score">Annuler</button>
                </div>
            </div><!-- Modale de confirmation du vainqueur -->
            <div id="modal-confirmation-vainqueur" class="modal hidden">
                <div class="modal-content">
                    <h3>Confirmer le résultat</h3>
                    <p id="modal-confirmation-texte"></p>
                    <div class="modal-actions">
                        <button id="btn-confirmer-vainqueur" type="button">Confirmer</button>
                        <button id="btn-annuler-vainqueur" type="button">Annuler</button>
                    </div>
                </div>
            </div>

            <!-- Modale de confirmation de suppression -->
            <div id="modal-suppression" class="modal hidden">
                <div class="modal-content">
                    <h3>Supprimer la phase finale</h3>
                    <p id="modal-suppression-info">Êtes-vous sûr de vouloir supprimer cette phase finale ? Cette action est irréversible.</p>
                    <div class="modal-actions">
                        <button id="btn-confirmer-suppression" type="button">Supprimer</button>
                        <button id="btn-annuler-suppression" type="button">Annuler</button>
                    </div>
                    <p id="msg-modal-suppression" class="msg"></p>
                </div>
            </div>
        </section>
    </div>
    <script>
    const tournoi_troissets_match = <?= json_encode($tournoi_troissets_match); ?>;
    const id_tournoi_js = <?= json_encode($tournoi_id); ?>;
    </script>
    <script src="js/colors.js"></script>
    <script src="js/matchs.js"></script>
    <script src="js/generation.js"></script>
    <script src="js/phase_final.js"></script>
    <script src="js/phase_final_matchs.js"></script>
    <script>
    (function() {
        const sections = document.querySelectorAll('[data-tournoi-section]'),
            tabs = document.querySelectorAll('[data-tournoi-tab]');

        function show(n, hash) {
            const s = document.querySelector('[data-tournoi-section="' + n + '"]') || sections[0];
            sections.forEach(x => x.classList.toggle('is-active', x === s));
            tabs.forEach(t => t.setAttribute('aria-selected', t.dataset.tournoiTab === s.dataset.tournoiSection ? 'true' : 'false'));
            if (hash && history.replaceState) history.replaceState(null, '', '#' + s.dataset.tournoiSection);
            if (s.dataset.tournoiSection === 'generation' && typeof chargerMatchs === 'function') chargerMatchs()
        }
        tabs.forEach(t => t.addEventListener('click', () => show(t.dataset.tournoiTab, true)));
        const n = (location.hash || '').slice(1);
        show(['poules', 'generation', 'phase-final'].includes(n) ? n : 'poules', false)
    })();
    </script>
</body>

</html>