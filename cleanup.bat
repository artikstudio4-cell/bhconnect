@echo off
REM ==========================================
REM Script de Nettoyage - BH CONNECT
REM Supprime les fichiers de test et debug
REM À exécuter avant déploiement (Windows)
REM ==========================================

echo.
echo 🧹 Nettoyage de BH CONNECT...
echo.

REM Fichiers à supprimer
set files_to_remove=(
    debug_csrf.php
    debug_inscription.php
    debug_schema.php
    test_config.php
    test_login_form.php
    test_login_manually.php
    diagnostic_complet.php
    health-check.php
    setup-infinity-free.php
    setup_invoice_db.php
    setup_quiz_db.php
    DEPLOIEMENT_INFINITYFREE.md
    EVALUATION_INFINITYFREE.md
    OPTIMISATION_INFINITYFREE.md
    GUIDE_INSCRIPTION.md
    TEST_CSRF_GUIDE.md
    fix_creneaux_table.php
    fix_db_duree.php
    fix_db_progression.php
    fix_dossiers_destination.php
    fix_messages_table.php
    final_db_fix.sql
    sql_quiz_update.sql
    EXEMPLE_EMAIL_INTEGRATION.php
    EXPLICATION_HTACCESS.md
)

setlocal enabledelayedexpansion
set removed_count=0

for %%F in %files_to_remove% do (
    if exist "%%F" (
        del /Q "%%F"
        echo ✓ Supprimé: %%F
        set /a removed_count+=1
    )
)

echo.
echo ✅ Nettoyage terminé!
echo 📊 Fichiers supprimés: %removed_count%
echo.
echo ⚠️  Dossiers nettoyés:
echo   ✓ logs (contents)
echo   ✓ uploads (contents, .gitkeep conservé)
echo.

REM Vider les logs
for /r logs %%F in (*) do (
    if not "%%~nxF"==".gitkeep" (
        del /Q "%%F"
    )
)

REM Vider les uploads
for /r uploads %%F in (*) do (
    if not "%%~nxF"==".gitkeep" (
        del /Q "%%F"
    )
)

echo 📁 Structure restante:
echo   config/          (Configuration)
echo   models/          (Classes métier)
echo   includes/        (En-têtes, constantes)
echo   controllers/     (Contrôleurs)
echo   css/             (Styles)
echo   js/              (JavaScript)
echo   images/          (Images)
echo   icons/           (Icônes)
echo   quiz/            (Module quiz)
echo   sounds/          (Sons)
echo   admin/           (Administration)
echo.

echo ✨ Prêt pour le déploiement!
echo.
echo Prochaines étapes:
echo   1. git add .
echo   2. git commit -m "Cleanup for Railway deployment"
echo   3. git push origin main
echo.

pause
