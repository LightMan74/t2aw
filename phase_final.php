<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Phase Finale - Badminton</title>
    <link rel="stylesheet" href="css/phase_final.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dark-mode.css">
</head>

<body>
    <nav>
        <?php include 'menu.php'; ?>
    </nav>
    <header>
        <h1>🏸 Gestion des Phases Finales</h1>
    </header>

    <main>

        <!-- Création d'une nouvelle phase finale -->
        <section id="section-creation" class="card">
            <h2>Créer une phase finale</h2>
            <form id="form-creation">
                <label>
                    Tournoi ID
                    <input type="number" id="input-tournoi-id" value="<?php echo htmlspecialchars($_GET['id_tournoi'] ?? ''); ?>" min="1" required>
                </label>

                <label>
                    Nom de la phase
                    <input type="text" id="input-nom" value="Phase Finale" required>
                </label>

                <label>
                    Nombre d'équipes
                    <input type="number" id="input-nb-equipes" value="8" min="2" required>
                </label>

                <label>
                    Type de bracket
                    <select id="input-type-bracket">
                        <option value="classement_complet">Classement complet (tous jouent tous les rounds)</option>
                        <option value="classique">Classique (élimination directe, seeding standard)</option>
                    </select>
                </label>

                <button type="button" id="btn-charger-equipes">Charger les équipes (classement des poules)</button>

                <div id="ordre-equipes-panel" class="hidden">
                    <h3>Ordre de départ (seeding)</h3>
                    <p>Glissez-déposez pour réordonner, ou utilisez les flèches ↑ ↓</p>
                    <div id="liste-ordre-equipes" class="ordre-equipes-liste"></div>
                </div>

                <button type="submit">Créer la phase finale</button>
            </form>
            <p id="msg-creation" class="msg"></p>
        </section>

        <!-- Liste des phases finales existantes -->
        <section id="section-liste" class="card">
            <h2>Phases finales existantes</h2>
            <button id="btn-rafraichir-liste">Rafraîchir</button>
            <ul id="liste-phases"></ul>
        </section>

        <!-- Détail / Bracket -->
        <section id="section-bracket" class="card hidden">
            <h2 id="titre-bracket">Bracket</h2>

            <div id="simulation-panel" class="simulation-panel">
                <label>
                    Nombre de rounds à sauter (simulation automatique)
                    <input type="number" id="input-nb-rounds-simuler" min="1" value="1">
                </label>
                <button type="button" id="btn-simuler-rounds">⏩ Simuler et sauter ces rounds</button>
                <p id="msg-simulation" class="msg"></p>
            </div>

            <div id="equipes-panel">
                <h3>Équipes</h3>
                <div id="liste-equipes" class="equipes-grid"></div>
            </div>

            <div id="bracket-container" class="bracket-container"></div>
        </section>

    </main>

    <!-- Modale de saisie de score -->
    <div id="modal-score" class="modal hidden">
        <div class="modal-content">
            <h3>Saisir le score</h3>
            <p id="modal-match-info"></p>
            <label>
                Score équipe 1
                <input type="number" id="modal-score1" min="0" value="0">
            </label>
            <label>
                Score équipe 2
                <input type="number" id="modal-score2" min="0" value="0">
            </label>
            <div class="modal-actions">
                <button id="btn-valider-score">Valider</button>
                <button id="btn-annuler-score">Annuler</button>
            </div>
            <p id="msg-modal" class="msg"></p>
        </div>
    </div>

    <!-- Modale de confirmation de suppression -->
    <div id="modal-suppression" class="modal hidden">
        <div class="modal-content">
            <h3>Supprimer la phase finale</h3>
            <p id="modal-suppression-info">Êtes-vous sûr de vouloir supprimer cette phase finale ? Cette action est irréversible.</p>
            <div class="modal-actions">
                <button id="btn-confirmer-suppression">Supprimer</button>
                <button id="btn-annuler-suppression">Annuler</button>
            </div>
            <p id="msg-modal-suppression" class="msg"></p>
        </div>
    </div>

    <script src="js/phase_final.js"></script>
</body>

</html>