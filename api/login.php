<?php
session_start();
header('Content-Type: application/json');

include 'db.php';

// On accepte uniquement du JSON en POST
$data = json_decode(file_get_contents('php://input'), true);

$response = ['success' => false, 'message' => ''];

if (!isset($data['user']) || !isset($data['password'])) {
    $response['message'] = 'Données manquantes';
    echo json_encode($response);
    exit;
}
 
$username = trim($data['user']);
$password = $data['password'];

if (empty($username) || empty($password)) {
    $response['message'] = 'Veuillez remplir tous les champs';
    echo json_encode($response);
    exit;
}

try {
    // $pdo = getPDO();
    
    $stmt = $pdo->prepare("SELECT user, password, uid, if(expire_date > NOW(), 1, 0) as expire_date FROM user WHERE user = :user LIMIT 1");
    $stmt->execute(['user' => $username]);
    $userData = $stmt->fetch();

    if ($userData && password_verify($password, $userData['password'])) {
        if ($userData['expire_date']){
        // Connexion réussie
        $_SESSION['uid'] = $userData['uid'];
        $_SESSION['user'] = $userData['user'];
        $_SESSION['logged_in'] = true;
        $_SESSION['expire'] = "";
        if ($userData['user'] == "admin"){$_SESSION['uid'] = "%";}
        
        $response['success'] = true;
        $response['message'] = 'Connexion réussie';
        $response['redirect'] = 'dashboard.php';
        }else{
            
            $response['message'] = 'Identifiant expiré';
        }
    } else {
        $response['message'] = 'Identifiant ou mot de passe incorrect';
    }

} catch (Exception $e) {
    $response['message'] = 'Erreur serveur';
}

echo json_encode($response);