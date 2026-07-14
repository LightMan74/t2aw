<?php
// Génère un QR code pointant vers l'IP du serveur
$ip = trim(shell_exec("ipconfig | findstr /i \"IPv4\""));
preg_match('/(\d{1,3}\.){3}\d{1,3}/', $ip, $matches);
$localIP = $matches[0] ?? '127.0.0.1';
$url = "http://$localIP:8000";

echo "URL du serveur : $url\n";
echo "Ouvre ce lien pour generer un QR code :\n";
echo "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($url) . "\n";