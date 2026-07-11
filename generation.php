<?php
// generation.php
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Génération des matchs - Poules</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dark-mode.css">
</head>

<body>
    <div class="container">
        <nav>
            <?php
include "api/check_connected.php";
include 'menu.php'; ?>
        </nav>
        <h1>Génération de l'ordre des matchs de poule</h1>

        <input type="number" id="id_tournoi" value="<?php echo $_GET["id_tournoi"];?>" style="width:100px; margin-bottom:15px;" hidden>
        <button onclick="chargerMatchs()" hidden>Charger / Générer l'ordre</button>

        <h2>Ordre proposé (glisser-déposer pour réorganiser)</h2>

        <center>
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

                    <!-- Cas normal : une seule poule pour les 2 équipes -->
                    <div id="bloc-poule-unique">
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
                            <label>Intitulé du match (ex: Barrage, 1/4 finale...) :</label><br>
                            <input type="text" id="libelle-match-inter" placeholder="Ex: Barrage" style="width:100%;">
                        </div>
                    </div>

                    <button onclick="ajouterMatchManuel()">Ajouter à la liste</button>
                    <button onclick="fermerFormulaireAjout()">Annuler</button>
                </div>

                <div id="liste-matchs"></div>

                <div id="message"></div>
                <button onclick="validerOrdre()" style="margin-top:15px;">Valider et enregistrer les matchs</button>

            </div>
        </center>
    </div>

    <script src="js/generation.js"></script>
    <script>
    chargerMatchs();
    </script>
</body>

</html>