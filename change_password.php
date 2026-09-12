<?php
session_start();
// ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/debug.log');
include __DIR__ . "/api/check_connected.php";
require_once 'api/db.php';

// Traitement de la requête AJAX (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $old_password = $data['old_password'] ?? '';
    $new_password = $data['new_password'] ?? '';
    $confirm_password = $data['confirm_password'] ?? '';
    $user = $_SESSION['user'];

    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'error' => 'Tous les champs sont requis']);
        exit;
    }

    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'error' => 'Les nouveaux mots de passe ne correspondent pas']);
        exit;
    }

    if (strlen($new_password) < 1) {
        echo json_encode(['success' => false, 'error' => 'Le mot de passe doit contenir au moins 6 caractères']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT password FROM user WHERE user = :user");
        $stmt->execute(['user' => $user]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // error_log("USER: " . $user);
        // error_log("HASH EN BASE: " . ($row['password'] ?? 'AUCUN'));
        // error_log("MDP SAISI: " . $old_password);
        // error_log("VERIFY: " . var_export(password_verify($old_password, $row['password'] ?? ''), true));

        if (!$row) {
            echo json_encode(['success' => false, 'error' => 'Utilisateur introuvable']);
            exit;
        }

        if (!password_verify($old_password, $row['password'])) {
            echo json_encode(['success' => false, 'error' => 'Ancien mot de passe incorrect']);
            exit;
        }

        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE user SET password = :pwd WHERE user = :user");
        $stmt->execute(['pwd' => $new_hash, 'user' => $user]);

        echo json_encode(['success' => true, 'message' => 'Mot de passe mis à jour avec succès']);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Modifier le mot de passe</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background: #f4f4f4;
    }

    .form-container {
        max-width: 400px;
        margin: 50px auto;
        padding: 20px;
        background: #fff;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

    .form-container h2 {
        margin-top: 0;
    }

    .form-container label {
        display: block;
        margin-top: 10px;
    }

    .form-container input {
        width: 100%;
        padding: 8px;
        margin-top: 5px;
        box-sizing: border-box;
    }

    .form-container button {
        margin-top: 15px;
        padding: 10px 20px;
        cursor: pointer;
    }

    .success {
        color: green;
        margin-top: 10px;
    }

    .error {
        color: red;
        margin-top: 10px;
    }
    </style>
</head>

<body>

    <div class="form-container">
        <h2>Modifier le mot de passe</h2>
        <form id="changePasswordForm">
            <label for="old_password">Ancien mot de passe</label>
            <input type="password" id="old_password" name="old_password" required>

            <label for="new_password">Nouveau mot de passe</label>
            <input type="password" id="new_password" name="new_password" required>

            <label for="confirm_password">Confirmer le nouveau mot de passe</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <button type="submit">Valider</button>
        </form>

        <p id="message"></p>
    </div>

    <script>
    document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const old_password = document.getElementById('old_password').value;
        const new_password = document.getElementById('new_password').value;
        const confirm_password = document.getElementById('confirm_password').value;
        const messageEl = document.getElementById('message');

        messageEl.textContent = '';
        messageEl.className = '';

        fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    old_password,
                    new_password,
                    confirm_password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageEl.textContent = data.message;
                    messageEl.className = 'success';
                    document.getElementById('changePasswordForm').reset();
                    window.location.replace("dashboard.php");
                } else {
                    messageEl.textContent = data.error;
                    messageEl.className = 'error';
                }
            })
            .catch(err => {
                messageEl.textContent = 'Erreur de connexion au serveur';
                messageEl.className = 'error';
                console.error(err);
            });
    });
    </script>

</body>

</html>