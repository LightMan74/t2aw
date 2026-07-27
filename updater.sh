#!/data/data/com.termux/files/usr/bin/bash

echo "=========================================="
echo "  Installation PHP + SQLite + Projet Web"
echo "=========================================="

# --- Variables à personnaliser ---
REPO_URL="https://github.com/LightMan74/t2aw.git"   # <-- CHANGE ICI
PROJECT_DIR="$HOME/www"
PROJECT_NAME="t2aw"   # <-- nom du dossier final, adapte si besoin

# --- 1. Mise à jour des paquets ---
echo ""
echo ">>> Mise à jour des paquets Termux..."
pkg update -y && pkg upgrade -y

# --- 2. Installation des paquets nécessaires ---
echo ""
echo ">>> Installation de git, php, sqlite..."
pkg install -y git php php-sqlite3 sqlite

# --- 3. Vérification des extensions PHP ---
echo ""
echo ">>> Vérification des extensions PHP..."
php -m | grep -i sqlite

# --- 4. Autoriser l'accès au stockage (si besoin) ---
if [ ! -d "$HOME/storage" ]; then
    echo ""
    echo ">>> Configuration de l'accès au stockage..."
    termux-setup-storage
    sleep 2
fi

# --- 5. Cloner le projet GitHub ---
echo ""
echo ">>> Clonage du projet depuis GitHub..."
mkdir -p "$PROJECT_DIR"
cd "$PROJECT_DIR" || exit

if [ -d "$PROJECT_NAME" ]; then
    echo "Le dossier existe déjà, mise à jour (git pull)..."
    cd "$PROJECT_NAME" || exit
    git pull
else
    git clone "$REPO_URL" "$PROJECT_NAME"
    cd "$PROJECT_NAME" || exit
fi

# --- 6. Vérifier la structure et les permissions ---
echo ""
echo ">>> Vérification du dossier database..."
if [ -d "$PROJECT_DIR/$PROJECT_NAME/database" ]; then
    chmod -R 755 "$PROJECT_DIR/$PROJECT_NAME/database"
    echo "Dossier database trouvé et permissions ajustées."
else
    echo "⚠️  Dossier 'database' non trouvé, vérifie la structure du projet."
fi

# --- 7. Afficher le résumé ---
echo ""
echo "=========================================="
echo "  Installation terminée !"
echo "=========================================="
echo ""
echo "Projet cloné dans : $PROJECT_DIR/$PROJECT_NAME"
echo ""
echo "Pour démarrer le serveur PHP, lance :"
echo "  cd $PROJECT_DIR/$PROJECT_NAME"
echo "  php -S 0.0.0.0:8080"
echo ""
echo "Puis ouvre dans ton navigateur Android :"
echo "  http://localhost:8080"
echo ""
echo "=========================================="