@echo off
title Serveur Tournois Badminton
color 0A
setlocal enabledelayedexpansion

echo ========================================
echo   SERVEUR TOURNOIS BADMINTON
echo ========================================
echo.

REM ==========================================
REM Detection de PHP : priorite au PHP portable
REM ==========================================
set PHP_EXE=
if not exist "%~dp0php\php.exe" (
    echo Premiere utilisation - extraction de PHP en cours...
    powershell -Command "Expand-Archive -Path 'php-8.4.23-nts-Win32-vs17-x64.zip' -DestinationPath 'php' -Force"
    echo Extraction terminee !
    if exist "php.ini" (
        echo Copie de php.ini vers php\ ...
        copy /Y "php.ini" "php\php.ini"
        echo Copie terminee !
    ) else (
        echo ATTENTION : php.ini introuvable a la racine !
    )
)

echo Demarrage du serveur...
@REM start "" http://localhost:8000
php-win\php.exe -S localhost:8000 -t www
if exist "%~dp0php\php.exe" (
    set PHP_EXE=%~dp0php\php.exe
    echo [OK] PHP portable detecte dans .\php\
) else (
    where php >nul 2>nul
    if !errorlevel! equ 0 (
        set PHP_EXE=php
        echo [OK] PHP systeme detecte
    ) else (
        echo [ERREUR] Aucun PHP trouve !
        echo.
        echo Solution 1 : Placez PHP portable dans le dossier .\php\
        echo Solution 2 : Installez PHP et ajoutez-le au PATH
        echo Telechargement : https://windows.php.net/download/
        echo.
        pause
        exit /b 1
    )
)

echo Utilisation de : !PHP_EXE!
echo.

REM ==========================================
REM Recuperation de l'adresse IP locale
REM ==========================================
set IP=

for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /i "IPv4"') do (
    if not defined IP (
        set IP=%%a
    )
)
set IP=%IP: =%

REM ==========================================
REM Verification du port disponible
REM ==========================================
set PORT=8000

echo ========================================
echo   SERVEUR EN COURS DE DEMARRAGE...
echo ========================================
echo.
echo Acces depuis CE PC :
echo   http://localhost:%PORT%
echo.
echo Acces depuis TELEPHONE / AUTRE PC (meme WiFi) :
echo   http://%IP%:%PORT%
echo.
echo ========================================
echo Appuyez sur CTRL+C pour arreter le serveur
echo ========================================
echo.
REM Ouvrir le QR code dans le navigateur pour scan facile
@REM start "" "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=http://%IP%:%PORT%"
start "" "http://%IP%:%PORT%"
REM ==========================================
REM Lancement du serveur PHP
REM ==========================================
cd /d "%~dp0"
"!PHP_EXE!" -S 0.0.0.0:%PORT% -t . -c "%~dp0php\php.ini"


pause