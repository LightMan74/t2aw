<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Miroir Déporté</title>
    <style>
    body,
    html {
        margin: 0;
        padding: 0;
        width: 100%;
        height: 100%;
        background-color: #000;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    video {
        width: 100%;
        height: 100%;
        object-fit: contain;
        /* Conserve les proportions sans déformation */
    }

    #btn-start {
        position: absolute;
        z-index: 10;
        padding: 15px 25px;
        font-size: 18px;
        font-weight: bold;
        background: #0066cc;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
    }
    </style>
</head>

<body>

    <button id="btn-start">Sélectionner l'onglet à dupliquer</button>
    <video id="video-miroir" autoplay playsinline></video>

    <script>
    const btn = document.getElementById('btn-start');
    const video = document.getElementById('video-miroir');

    btn.addEventListener('click', async () => {
        try {
            // Demande la capture (onglet ou fenêtre) avec le curseur inclus
            const stream = await navigator.mediaDevices.getDisplayMedia({
                video: {
                    cursor: "always" // Affiche la souris à l'écran
                },
                audio: false
            });

            video.srcObject = stream;
            btn.style.display = 'none'; // Cache le bouton une fois lancé

            // Réaffiche le bouton si le partage est coupé
            stream.getVideoTracks()[0].addEventListener('ended', () => {
                btn.style.display = 'block';
            });

        } catch (err) {
            console.error("Annulé ou erreur :", err);
        }
    });
    </script>
</body>

</html>