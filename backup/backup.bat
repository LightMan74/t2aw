@echo off
setlocal EnableDelayedExpansion
cd /d "%~dp0"

set "BDD=..\database\t2aw.sqlite"
set "CONFIG=backup_config.json"
set "SQLITE=sqlite3.exe"
if not exist "%SQLITE%" set "SQLITE=sqlite3"

echo ============================================
echo   Sauvegarde SQLite - Ctrl+C pour arreter
echo ============================================

:boucle
:: Relecture de la config a chaque tour (modifiable a chaud)
set "FREQ="
set "GARDER=0"
for /f "usebackq delims=" %%F in (`powershell -NoProfile -Command "(Get-Content '%CONFIG%' -Raw | ConvertFrom-Json).frequence"`) do set "FREQ=%%F"
for /f "usebackq delims=" %%G in (`powershell -NoProfile -Command "(Get-Content '%CONFIG%' -Raw | ConvertFrom-Json).garder"`) do set "GARDER=%%G"

if not defined FREQ (
    echo [ERREUR] Lecture de %CONFIG% impossible
    timeout /t 30 >nul
    goto boucle
)

:: Horodatage independant de la langue Windows
for /f "usebackq delims=" %%D in (`powershell -NoProfile -Command "Get-Date -Format 'yyyy-MM-dd_HH-mm-ss'"`) do set "STAMP=%%D"

if not exist "%BDD%" (
    echo [%time%] [ERREUR] Base introuvable : %BDD%
    goto attente
)

:: Pour chaque emplacement defini dans le JSON
for /f "usebackq delims=" %%E in (`powershell -NoProfile -Command "(Get-Content '%CONFIG%' -Raw | ConvertFrom-Json).emplacements"`) do (
    set "DEST=%%E"
    if not exist "!DEST!" mkdir "!DEST!" 2>nul

    set "FICHIER=!DEST!\t2aw_!STAMP!.sqlite"

    :: Sauvegarde propre (API backup de SQLite)
    "%SQLITE%" "%BDD%" ".timeout 10000" ".backup '!FICHIER!'"

    if exist "!FICHIER!" (
        echo [%time%] OK  : !FICHIER!
        call :rotation "!DEST!"
    ) else (
        echo [%time%] ECHEC : !DEST!
    )
)

:attente
set /a RESTANT=%FREQ%
:decompte
echo [%time%] Prochaine sauvegarde dans !RESTANT! minute(s)...
timeout /t 60 /nobreak >nul
set /a RESTANT-=1
if !RESTANT! GTR 0 goto decompte
goto boucle


:: ------------------------------------------------
:: Rotation : supprime les plus anciennes sauvegardes
:: ------------------------------------------------
:rotation
if %GARDER% LEQ 0 exit /b
for /f "skip=%GARDER% delims=" %%X in ('dir /b /o-d "%~1\t2aw_*.sqlite" 2^>nul') do del "%~1\%%X"
exit /b