@echo off
setlocal EnableDelayedExpansion
cd /d "%~dp0"

:: ============================================
::   PARAMETRES (a modifier ici)
:: ============================================
set "BDD=..\database\t2aw.sqlite" 
set "FREQ=5"
set "GARDER=30"

:: Emplacements de sauvegarde (un par ligne, max 5, laisser vide si inutilise)
set "DEST1=D:\sauvegardes\t2aw"
set "DEST2="
set "DEST3="
set "DEST4="
set "DEST5="
:: ============================================

set "SQLITE=sqlite3.exe"
if not exist "%SQLITE%" set "SQLITE=sqlite3"

echo ============================================
echo   Sauvegarde SQLite - Ctrl+C pour arreter
echo   Frequence : %FREQ% min / Conservation : %GARDER%
echo ============================================

:boucle
:: Horodatage independant de la langue Windows
for /f "usebackq delims=" %%D in (`powershell -NoProfile -Command "Get-Date -Format 'yyyy-MM-dd_HH-mm-ss'"`) do set "STAMP=%%D"

if not exist "%BDD%" (
    echo [%time%] [ERREUR] Base introuvable : %BDD%
    goto attente
)

for %%N in (1 2 3 4 5) do (
    if defined DEST%%N call :sauvegarde "!DEST%%N!"
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
:: Sauvegarde dans un dossier + rotation
:: ------------------------------------------------
:sauvegarde
set "D=%~1"
if not exist "%D%" mkdir "%D%" 2>nul

set "FICHIER=%D%\t2aw_%STAMP%.sqlite"
"%SQLITE%" "%BDD%" ".timeout 10000" ".backup '%FICHIER%'"

if exist "%FICHIER%" (
    echo [%time%] OK  : %FICHIER%
    if %GARDER% GTR 0 (
        for /f "skip=%GARDER% delims=" %%X in ('dir /b /o-d "%D%\t2aw_*.sqlite" 2^>nul') do del "%D%\%%X"
    )
) else (
    echo [%time%] ECHEC : %D%
)
exit /b