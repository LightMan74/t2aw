<?php
$id_tournoi = isset($_GET['id_tournoi']) ? (int)$_GET['id_tournoi'] : 0;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournoi All Auto Web</title>
    <link rel="icon" type="image/png" sizes="64x64" href="icon_t2aw.png">
    <link rel="stylesheet" href="css/var.css">
    <link rel="stylesheet" href="css/afficheur.css">
    <?php if ($id_tournoi === 0): ?>
    <link rel="stylesheet" href="css/liste_afficheur.css">
    <?php endif; ?>

</head>

<body>

    <?php  
if ($id_tournoi === 0): ?>

    <!-- Sélecteur de tournoi si aucun id_tournoi fourni -->
    <div class="login-logo" role="img" aria-label="Logo" style="width:200px;max-width: 200px;margin: 25px auto;"></div>
    <button onclick="location.href='login.php?login'" class="btn-lsiteafficheur">👤 Login</button><br><br>
    <div class="selecteur-tournois">
        <h1>Sélectionnez un tournoi</h1>
        <div id="liste-tournois" class="liste-tournois">
            <p class="message-vide">Chargement des tournois...</p>
        </div>
    </div>

    <!-- Modal mot de passe -->
    <div id="modal-password" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <h2>Tournoi protégé</h2>
            <p>Ce tournoi nécessite un mot de passe pour y accéder.</p>
            <input type="password" id="modal-password-input" placeholder="Mot de passe" autocomplete="off">
            <p id="modal-password-error" class="message-erreur" style="display:none;">Mot de passe requis</p>
            <div class="modal-actions">
                <button id="modal-password-cancel" class="btn-dark">Annuler</button>
                <button id="modal-password-valider" class="btn-dark">Valider</button>
            </div>
        </div>
    </div>
    <script>
    let tournoiIdSelectionne = null;

    function ouvrirModalPassword(idTournoi) {
        tournoiIdSelectionne = idTournoi;
        document.getElementById('modal-password-input').value = '';
        document.getElementById('modal-password-error').style.display = 'none';
        document.getElementById('modal-password').style.display = 'flex';
        document.getElementById('modal-password-input').focus();
    }

    function fermerModalPassword() {
        document.getElementById('modal-password').style.display = 'none';
        tournoiIdSelectionne = null;
    }

    document.getElementById('modal-password-cancel').addEventListener('click', fermerModalPassword);

    document.getElementById('modal-password-valider').addEventListener('click', () => {
        const pass = document.getElementById('modal-password-input').value;
        if (!pass) {
            document.getElementById('modal-password-error').style.display = 'block';
            return;
        }
        if (tournoiIdSelectionne !== null) {
            window.location.href = 'afficheur.php?id_tournoi=' + encodeURIComponent(tournoiIdSelectionne) + '&password=' + encodeURIComponent(pass);
        }
    });

    document.getElementById('modal-password-input').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            document.getElementById('modal-password-valider').click();
        }
    });

    document.getElementById('modal-password').addEventListener('click', (e) => {
        if (e.target.id === 'modal-password') {
            fermerModalPassword();
        }
    });

    fetch('api/get_liste_afficheur.php')
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('liste-tournois');
            container.innerHTML = '';

            if (!data.success) {
                container.innerHTML = '<p class="message-erreur">Erreur : ' + (data.error || 'Impossible de charger les tournois') + '</p>';
                return;
            }

            if (!data.tournois || data.tournois.length === 0) {
                container.innerHTML = '<p class="message-vide">Aucun tournoi disponible.</p>';
                return;
            }

            data.tournois.forEach(t => {
                const a = document.createElement('a');
                a.className = 'lien-tournoi';

                let dateInfo = '';
                if (t.heure_debut_poule) {
                    dateInfo = 'Début poules : ' + t.heure_debut_poule;
                }
                let havepass = "";
                if (t.tournoi_password) {
                    havepass = " 🔒";
                }
                a.innerHTML = '<strong>' + (t.nom || ('Tournoi #' + t.id_tournoi)) + havepass + '</strong>' +
                    (dateInfo ? '<span class="date-tournoi">' + dateInfo + '</span>' : '');

                a.href = 'afficheur.php?id_tournoi=' + encodeURIComponent(t.id_tournoi);
                if (t.tournoi_password) {
                    // a.href = '#';
                    a.addEventListener('click', (e) => {
                        e.preventDefault();
                        ouvrirModalPassword(t.id_tournoi);
                    });
                } else {
                    // a.href = 'afficheur.php?id_tournoi=' + encodeURIComponent(t.id_tournoi);
                }

                container.appendChild(a);
            });
        })
        .catch(err => {
            document.getElementById('liste-tournois').innerHTML =
                '<p class="message-erreur">Erreur de connexion : ' + err.message + '</p>';
        });
    </script>

    <?php else: 
        include 'api/verify_password.php';
        ?>


    <header>
        <div class="login-logo" role="img" aria-label="Logo"></div>
        <h1 id="nom-tournoi">Chargement du tournoi...</h1>
        <div class="qrcode" role="img" aria-label="qrcode"></div>
        <div class="infos-refresh">
            <div class="timer-control">
                <center>
                    <span id="heure-actuelle">--:--:--</span><br>
                    <!-- <label for="refresh-interval">Rafraîchissement (sec) :</label> -->
                    <!-- <nobr><span id="countdown">60</span>s -->
                    <!-- <input type="number" id="refresh-interval" value="60" min="10" max="600"> -->
                    </nobr>
                    <center>
            </div>

            <button onclick="location.href='afficheur.php'" class="btn-dark">↩ liste tournoi</button>
            <button id="dark-mode-toggle" class="btn-dark" title="Basculer le mode sombre">🌙</button>
            <button onclick="location.href='login.php?login'" class="btn-dark">👤 Login</button>
        </div>
    </header>

    <nav class="tabs">
        <button class="tab-btn active" data-tab="matchs">Matchs</button>
        <button class="tab-btn" data-tab="classement">Classement</button>
        <!-- <button class="tab-btn" data-tab="joueurs">Joueurs inscrits</button> -->
        <button class="tab-btn" data-tab="phase_finale">Phase Finale</button>
        <button class="tab-btn" data-tab="classement_final">Classement Final</button>
    </nav>
    <main>
        <!-- Onglet Matchs : 3 colonnes -->
        <section id="tab-matchs" class="tab-content active">
            <div class="grid-matchs">
                <div class="colonne">
                    <h2>Matchs en cours</h2>
                    <div id="matchs-en-cours" class="liste-matchs scroll-colonne"></div>
                </div>
                <div class="colonne">
                    <h2>Matchs à venir</h2>
                    <div id="matchs-a-venir" class="liste-matchs scroll-colonne"></div>
                </div>
                <div class="colonne">
                    <h2>Derniers résultats</h2>
                    <div id="matchs-termines" class="liste-matchs scroll-colonne"></div>
                </div>
            </div>
        </section>

        <!-- Onglet Classement : grille multi-colonnes -->
        <section id="tab-classement" class="tab-content">
            <div id="classement-content" class="grid-multicol scroll-colonne"></div>
        </section>

        <!-- Onglet Joueurs : grille multi-colonnes -->
        <section id="tab-joueurs" class="tab-content">
            <div id="joueurs-content" class="grid-multicol scroll-colonne"></div>
        </section>

        <!-- Onglet Phase Finale : bracket lecture seule -->
        <section id="tab-phase_finale" class="tab-content">
            <div id="phase-finale-content"></div>
        </section>
        <section id="tab-classement_final" class="tab-content">
            <div id="classement-final-content" class="grid-multicol scroll-colonne"></div>
        </section>
    </main>

    <script>
    const ID_TOURNOI = <?php echo json_encode($id_tournoi); ?>;

    async function loadQrCode(data) {
        if (window.innerWidth <= 900) {
            return; // ne rien faire sur mobile
        }
        const response = await fetch(`api/qrcode.php?data=${encodeURIComponent(data)}`);
        const svgText = await response.text();

        const container = document.querySelector('.qrcode');
        container.innerHTML = svgText;
        container.style = "background: #d2d2d2;";
    }
    <?php $urlcode =  str_replace("option&","","https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");?>
    loadQrCode("<?php echo $urlcode;?>");

    document.querySelectorAll('#config-rotation input').forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            const selected = Array.from(
                document.querySelectorAll('#config-rotation input:checked')
            ).map(cb => cb.value);

            rotator.setRotationList(selected);
        });
    });
    </script>


    <script src="js/colors.js"></script>
    <!-- Configuration des liaisons du bracket (couleurs, opacité, etc.) -->
    <script src="js/bracket-lines-config.js"></script>
    <script src="js/leader-line.min.js"></script>
    <script src="js/afficheur.js"></script>


    <?php
    if (isset($_GET["option"])){ ?>
    <link rel="stylesheet" href="css/rotation-menu.css">
    <div class="rotation-menu-container">
        <button class="rotation-menu-toggle" type="button">⚙️</button>
        <div class="rotation-menu-panel" id="rotation-menu-panel">
        </div>
    </div>

    <script src="js/tab-rotator.js"></script>
    <script>
    const rotator = new TabRotator({
        // mainInterval = 10000,
        // subInterval = 10000,
        // scrollSpeed = 40,
        // scrollPauseAtStart = 2000,
        // scrollPauseAtEnd = 2000,
        noScrollTabs: ['Matchs'], // si data-tab="match"
        noScrollSelectors: ['#tab-matchs']
        // noScrollSelectors: ['.match-tab', '#tab-matchs'] // ou par classe/id du bouton onglet
        // noScrollSelectors: ['#matchs-a-venir'] // ou par classe/id du bouton onglet
    });

    generateRotationConfig(rotator, '#rotation-menu-panel');
    </script>
    <?php } 
endif; ?>
</body>

</html>