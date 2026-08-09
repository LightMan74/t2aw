apt update -y &&
apt upgrade -y &&
rm -rf php &&
mkdir php &&
cd php &&
apt install php &&
curl -o "updater.php" "https://raw.githubusercontent.com/LightMan74/t2aw/refs/heads/main/updater.php" &&
php "updater.php" &&
cat << 'EOF' > "start.sh"
# Récupération de l'adresse IP locale
LOCAL_IP=$(ip addr show wlan0 2>/dev/null | grep "inet " | awk '{print $2}' | cut -d/ -f1)
if [ -z "$LOCAL_IP" ]; then
    LOCAL_IP=$(ifconfig 2>/dev/null | grep "inet " | grep -v "127.0.0.1" | awk '{print $2}' | head -1)
fi
echo "========================================"
echo " Démarrage du serveur PHP"
echo " Accès local  : http://127.0.0.1:8080"
echo " Accès réseau : http://${LOCAL_IP:-IP_INTROUVABLE}:8080"
echo " Utilisateur  : local"
echo " Mot de passe : local"
echo "========================================"
"$PHP_BIN" \
  -d sys_temp_dir="$HOME/php/tmp" \
  -d opcache.enable_cli=0 \
  -d opcache.enable=0 \
  -d error_reporting=0 \
  -d display_errors=0 \
  -d display_startup_errors=0 \
  -S 0.0.0.0:8080 -t "php" 2>/dev/null
EOF
chmod +x "start.sh" &&
./start.sh