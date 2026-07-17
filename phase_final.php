<?php
$tournoi_id = isset($_GET['id_tournoi']) ? (int)$_GET['id_tournoi'] : 0;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phase Finale - Badminton</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dark-mode.css">
    <link rel="stylesheet" href="css/phase_final.css">
</head>

<body>
    <div class="container">
        <nav>
            <?php include 'menu.php'; ?>
        </nav>

        <header>
            <h1>🏸 Gestion des Phases Finales</h1>
        </header>
        <!-- <main class="container"> -->
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
                        <input type="number" id="input-nb-equipes" min="2" required readonly placeholder="—">
                    </div>
                </div>

                <!-- Ordre de départ (seeding) -->
                <div id="ordre-equipes-panel" class="hidden">
                    <h3>Ordre de départ (seeding)</h3>
                    <p class="text-muted">Glissez-déposez pour réordonner, ou utilisez les flèches ↑ ↓</p>
                    <ul id="liste-ordre-equipes" class="ordre-equipes-liste"></ul>
                </div>

                <div class="form-group">
                    <button type="submit" id="btn-creer" class="btn btn-primary">Créer la phase finale</button>
                </div>
            </form>
            <p id="msg-creation" class="msg"></p>
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

            <div id="equipes-panel">
                <h3>Équipes</h3>
                <div id="liste-equipes" class="equipes-grid"></div>
            </div>
        </section>
        <!-- </main> -->

        <!-- Modale de saisie de score -->
        <div id="modal-score" class="modal hidden">
            <div class="modal-content">
                <p id="modal-match-info"></p>

                <label>Score équipe 1 :
                    <input type="number" id="modal-score1" min="0">
                </label>
                <label>Score équipe 2 :
                    <input type="number" id="modal-score2" min="0">
                </label>

                <label style="display:none">Statut du match :
                    <select id="modal-statut-match" style="display:none">
                        <option value="planifie">Planifié</option>
                        <option value="en_cours">En jeu</option>
                        <option value="termine" selected>Terminé</option>
                    </select>
                </label>

                <label style="display:none">Terrain :
                    <input type="number" id="modal-terrain" min="1" placeholder="Terrain" style="display:none">
                </label>

                <div id="msg-modal" class="msg"></div>

                <button id="btn-valider-score">Valider</button>
                <button id="btn-annuler-score">Annuler</button>
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
    </div>
    <script src="js/theme.js"></script>
    <script src="js/phase_final.js"></script>


</body>

</html>