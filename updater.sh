mkdir php &&
cd php &&
apt download php &&
dpkg-deb -x php_*.deb ./env_php &&
rm php_*.deb &&
apt download zstd &&
dpkg-deb -x zstd_*.deb ./env_php &&
rm zstd_*.deb &&
apt download tidy tidy-static &&
apt download $(apt-cache depends php | grep "Depends:" | awk '{print $2}') &&
for deb in *.deb; do dpkg-deb -x "$deb" ./env_php; done &&
rm *.deb &&
curl -o "$HOME/php/updater.php" "https://raw.githubusercontent.com/LightMan74/t2aw/refs/heads/main/updater.php" &&
BASE_DIR="$HOME/php/env_php/data/data/com.termux/files/usr" &&
PHP_BIN="$BASE_DIR/bin/php" &&
export LD_LIBRARY_PATH="$BASE_DIR/lib:$LD_LIBRARY_PATH" &&
"$PHP_BIN" "$HOME/php/updater.php" &&
cat << 'EOF' > start.sh
#!/data/data/com.termux/files/usr/bin/bash
BASE_DIR="$HOME/php/env_php/data/data/com.termux/files/usr"
PHP_INI="$HOME/php/php.ini"
mkdir -p "$HOME/php/tmp"
export TMPDIR="$HOME/php/tmp"
export TEMP="$HOME/php/tmp"
export TMP="$HOME/php/tmp"
export LD_LIBRARY_PATH="$BASE_DIR/lib:$LD_LIBRARY_PATH"
PHP_BIN="$BASE_DIR/bin/php"
$PHP_BIN \
  -c "$PHP_INI" \
  -d sys_temp_dir="$HOME/php/tmp" \
  -d opcache.enable_cli=0 \
  -d opcache.enable=0 \
  -S 0.0.0.0:8080 -t $HOME/php
EOF
chmod +x start.sh &&
./start.sh