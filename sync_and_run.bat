@echo off
echo Synchronisation U: vers C: ...
robocopy "U:\Apache\t2aw" "C:\Users\Bureau\Desktop\t2aw" /E /XO /NFL /NDL /NJH /NJS

echo Lancement du serveur local...
cd /d "C:\Users\Bureau\Desktop\t2aw"
start_server.bat 