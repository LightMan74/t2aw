sudo apt update -y && \
sudo apt install -y php-cli php-curl php-mbstring php-zip php-sqlite3 php-mysql zstd tidy curl && \
rm -rf "$HOME/t2aw" && \
mkdir -p "$HOME/t2aw" && \
cd "$HOME/t2aw" && \
curl -s -o "$HOME/t2aw/updater.php" "https://raw.githubusercontent.com/LightMan74/t2aw/refs/heads/main/updater.php" && \
php "$HOME/t2aw/updater.php" < /dev/null && \
echo '#!/bin/bash
PROJECT_DIR="$HOME/t2aw"
mkdir -p "$PROJECT_DIR/tmp"
export TMPDIR="$PROJECT_DIR/tmp"
export TEMP="$PROJECT_DIR/tmp"
export TMP="$PROJECT_DIR/tmp"

LOCAL_IP=$(hostname -I 2>/dev/null | awk '\''{print $1}'\'')
if [ -z "$LOCAL_IP" ]; then
    LOCAL_IP=$(ip route get 1.1.1.1 2>/dev/null | awk '\''{print $7}'\'')
fi

echo "========================================"
echo " Démarrage du serveur PHP"
echo " Accès local  : http://127.0.0.1:8080"
echo " Accès réseau : http://${LOCAL_IP:-127.0.0.1}:8080"
echo " Utilisateur  : local"
echo " Mot de passe : local"
echo "========================================"

php \
  -d sys_temp_dir="$PROJECT_DIR/tmp" \
  -d opcache.enable_cli=0 \
  -d opcache.enable=0 \
  -d error_reporting=0 \
  -d display_errors=0 \
  -d display_startup_errors=0 \
  -S 0.0.0.0:8080 -t "$PROJECT_DIR" 2>/dev/null
' > "$HOME/t2aw/start.sh" && \
chmod +x "$HOME/t2aw/start.sh" && \
"$HOME/t2aw/start.sh"