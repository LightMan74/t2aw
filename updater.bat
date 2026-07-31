@echo off
setlocal enabledelayedexpansion

REM ==========================================
REM CONFIGURATION (identique au script PHP)
REM ==========================================
set "OWNER=LightMan74"
set "REPO=t2aw"
set "BRANCH=main"
set "INSTALL_DIR=%~dp0"
if "%INSTALL_DIR:~-1%"=="\" set "INSTALL_DIR=%INSTALL_DIR:~0,-1%"

set "TEMP_DIR=%TEMP%\gh_install_%RANDOM%"
set "VERSION_FILE=%INSTALL_DIR%\.version"
set "ZIP_URL=https://github.com/%OWNER%/%REPO%/archive/refs/heads/%BRANCH%.zip"
set "API_URL=https://api.github.com/repos/%OWNER%/%REPO%/commits/%BRANCH%"

REM Liste des elements a preserver (equivalent $preserve du PHP)
set "PRESERVE_1=configuserlogin.php"
set "PRESERVE_2=.gitignore"
set "PRESERVE_3=.git"
set "PRESERVE_4=.version"
set "PRESERVE_5=database"

echo ===============================================
echo   Installation/Mise a jour depuis GitHub
echo   Repo    : %OWNER%/%REPO%
echo   Branche : %BRANCH%
echo   Dossier : %INSTALL_DIR%
echo ===============================================
echo.

REM ==========================================
REM VERIFICATION DES OUTILS NECESSAIRES
REM ==========================================
where curl >nul 2>nul
if errorlevel 1 (
    echo [ERREUR] curl n'est pas disponible sur ce systeme.
    goto :error
)

where powershell >nul 2>nul
if errorlevel 1 (
    echo [ERREUR] PowerShell n'est pas disponible sur ce systeme.
    goto :error
)

REM ==========================================
REM RECUPERATION DE LA VERSION LOCALE
REM ==========================================
set "LOCAL_VERSION="
if exist "%VERSION_FILE%" (
    set /p LOCAL_VERSION=<"%VERSION_FILE%"
)
set LOCAL_VERSION=%LOCAL_VERSION: =%
echo Version locale  : !LOCAL_VERSION!

REM ==========================================
REM RECUPERATION DE LA VERSION DISTANTE (SHA)
REM ==========================================
echo Verification de la derniere version distante...

set "TMP_JSON=%TEMP%\gh_commit_%RANDOM%.json"
curl -s -H "User-Agent: PHP-Installer" "%API_URL%" -o "%TMP_JSON%"

if not exist "%TMP_JSON%" (
    echo [ERREUR] Impossible de contacter l'API GitHub.
    goto :error
)

for /f "delims=" %%A in ('powershell -NoProfile -Command ^
    "(Get-Content -Raw '%TMP_JSON%' | ConvertFrom-Json).sha"') do (
    set "REMOTE_VERSION=%%A"
)

del "%TMP_JSON%" >nul 2>nul

if "!REMOTE_VERSION!"=="" (
    echo [ERREUR] Impossible de recuperer le SHA distant. Verifiez owner/repo/branch.
    goto :error
)

echo Version distante : !REMOTE_VERSION!
echo.

REM ==========================================
REM COMPARAISON DES VERSIONS (equivalent au if PHP)
REM ==========================================
if "!LOCAL_VERSION!"=="!REMOTE_VERSION!" (
    echo Deja a jour ^(commit: !REMOTE_VERSION!^)    
    pause
    goto :end
)

echo Mise a jour disponible : !LOCAL_VERSION! -^> !REMOTE_VERSION!
echo.

REM ==========================================
REM TELECHARGEMENT DU ZIP
REM ==========================================
set "ZIP_PATH=%TEMP%\gh_%RANDOM%.zip"

echo Telechargement depuis : %ZIP_URL%
curl -L -s -H "User-Agent: PHP-Installer" -o "%ZIP_PATH%" "%ZIP_URL%"

if not exist "%ZIP_PATH%" (
    echo [ERREUR] Echec du telechargement du ZIP.
    goto :error
)

REM Verifie que le fichier n'est pas vide / erreur (ex: repo introuvable -> HTML 404)
for %%F in ("%ZIP_PATH%") do set "ZIP_SIZE=%%~zF"
if !ZIP_SIZE! LSS 1000 (
    echo [ERREUR] Le fichier telecharge semble invalide ^(taille: !ZIP_SIZE! octets^).
    goto :error
)

echo.

REM ==========================================
REM EXTRACTION DU ZIP (equivalent extractZip)
REM ==========================================
echo Extraction en cours...

mkdir "%TEMP_DIR%" >nul 2>nul

powershell -NoProfile -Command ^
    "Expand-Archive -Path '%ZIP_PATH%' -DestinationPath '%TEMP_DIR%' -Force"

if errorlevel 1 (
    echo [ERREUR] Echec de l'extraction du ZIP.
    goto :error
)

REM Trouve le sous-dossier extrait (format repo-branch), equivalent glob GLOB_ONLYDIR
set "SOURCE_DIR="
for /d %%D in ("%TEMP_DIR%\*") do (
    if "!SOURCE_DIR!"=="" set "SOURCE_DIR=%%D"
)

if "!SOURCE_DIR!"=="" (
    echo [ERREUR] Dossier source introuvable apres extraction.
    goto :error
)

echo Dossier source : !SOURCE_DIR!
echo.

REM ==========================================
REM SYNCHRONISATION DES FICHIERS (equivalent syncFiles)
REM ==========================================
if not exist "%INSTALL_DIR%" mkdir "%INSTALL_DIR%"

echo Copie des fichiers vers %INSTALL_DIR% ...
echo Elements preserves : %PRESERVE_1%, %PRESERVE_2%, %PRESERVE_3%, %PRESERVE_4%

REM robocopy : /XF exclut des fichiers, /XD exclut des dossiers
REM On exclut les fichiers ET dossiers portant ces noms, ou qu'ils soient
robocopy "!SOURCE_DIR!" "%INSTALL_DIR%" /E /XF "%PRESERVE_1%" "%PRESERVE_2%" "%PRESERVE_4%" /XD "%PRESERVE_3%" /NFL /NDL /NJH /NJS /NC /NS /NP

REM robocopy renvoie un code >0 même en cas de succes (0-7 = OK), on filtre les vraies erreurs
if %ERRORLEVEL% GEQ 8 (
    echo [ERREUR] Echec de la synchronisation des fichiers ^(code robocopy: %ERRORLEVEL%^).
    goto :error
)

echo.
echo Synchronisation terminee.
echo.

REM ==========================================
REM MISE A JOUR DU FICHIER VERSION (equivalent saveVersion)
REM ==========================================
echo !REMOTE_VERSION! > "%VERSION_FILE%"

REM ==========================================
REM NETTOYAGE (equivalent unlink + removeDirectory)
REM ==========================================
del "%ZIP_PATH%" >nul 2>nul
rmdir /S /Q "%TEMP_DIR%" >nul 2>nul

echo.
echo ===============================================
echo   Installation terminee avec succes.
echo   Version installee : !REMOTE_VERSION!
echo ===============================================
goto :end

:error
echo.
echo ===============================================
echo   ECHEC DE L'INSTALLATION / MISE A JOUR
echo ===============================================
if exist "%ZIP_PATH%" del "%ZIP_PATH%" >nul 2>nul
if exist "%TEMP_DIR%" rmdir /S /Q "%TEMP_DIR%" >nul 2>nul
pause
exit /b 1

:end
endlocal
exit /b 0