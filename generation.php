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
    <style>
    .zones-terrains {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding-bottom: 10px;
    }

    .zone-terrain {
        min-width: 220px;
        max-width: 220px;
        border: 1px solid #ccc;
        border-radius: 6px;
        padding: 8px;
        background: #f7f7f7;
        flex-shrink: 0;
    }

    .zone-terrain h3 {
        text-align: center;
        font-size: 14px;
        margin: 0 0 8px 0;
        padding-bottom: 5px;
        border-bottom: 1px solid #ccc;
    }

    .zone-terrain.file-attente {
        background: #eef2f7;
    }

    .zone-terrain.drag-over {
        background: #d6e9ff;
    }

    .match-item-terrain {
        border: 1px solid #999;
        border-radius: 4px;
        padding: 6px;
        margin-bottom: 6px;
        background: #fff;
        font-size: 12px;
        cursor: grab;
    }

    .match-item-terrain.dragging {
        opacity: 0.4;
    }

    .match-item-terrain .ligne1 {
        font-weight: bold;
        margin-bottom: 2px;
    }

    .match-item-terrain button.btn-suppr-match {
        float: right;
        font-size: 10px;
        cursor: pointer;
    }

    .match-item-terrain.drag-over-match {
        border-top: 3px solid #007bff;
    }

    .match-item-terrain {
        position: relative;
        border: 1px solid #999;
        border-radius: 4px;
        padding: 6px 28px 6px 8px;
        /* espace à droite pour le bouton */
        margin-bottom: 6px;
        background: #fff;
        cursor: grab;
        box-sizing: border-box;
        height: 56px;
        /* hauteur fixe pour tous les matchs */
        overflow: hidden;
    }

    .match-item-terrain.dragging {
        opacity: 0.5;
    }

    .match-item-terrain.placeholder {
        background: #d6e9ff;
        border: 2px dashed #007bff;
        box-shadow: none;
    }

    .match-content {
        display: flex;
        flex-direction: column;
        justify-content: center;
        height: 100%;
    }

    .match-item-terrain .ligne1 {
        font-size: 11px;
        color: #555;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .match-item-terrain .ligne2 {
        font-size: 13px;
        font-weight: bold;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .badge-ajout {
        font-size: 9px;
        background: #ffc107;
        color: #333;
        padding: 1px 4px;
        border-radius: 3px;
        margin-left: 4px;
        font-style: normal;
    }

    .btn-suppr-match {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 20px;
        height: 20px;
        border: none;
        border-radius: 50%;
        background: #dc3545;
        color: #fff;
        font-size: 12px;
        line-height: 1;
        cursor: pointer;
        padding: 0;
    }
    </style>
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

                <div id="message"></div>
            </div>
        </center>

        <div style="margin: 15px 0; text-align:center;">
            <button onclick="repartitionAutomatique()">⚡ Répartition automatique dans les terrains</button>
            <button onclick="validerOrdre()">💾 Valider et enregistrer les matchs</button>
        </div>

        <h2 style="text-align:center;">Répartition par terrain (glisser-déposer)</h2>
        <div class="zones-terrains" id="zones-terrains">
            <!-- généré en JS -->
        </div>

    </div>

    <script src="js/generation.js"></script>
    <script>
    chargerMatchs();
    </script>
</body>

</html>