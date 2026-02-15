# Windows PowerShell cleanup script for BH CONNECT
# Supprime les fichiers de test/debug avant déploiement sur Railway

# Se positionner au répertoire du projet
$projectPath = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $projectPath

Write-Host "🧹 Nettoyage du projet BH CONNECT..." -ForegroundColor Cyan
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan

# Fichiers à supprimer
$filesToDelete = @(
    "debug_csrf.php",
    "debug_inscription.php",
    "debug_schema.php",
    "test_config.php",
    "test_login_form.php",
    "test_login_manually.php",
    "diagnostic_complet.php",
    "health-check.php",
    "setup-infinity-free.php",
    "setup_invoice_db.php",
    "setup_quiz_db.php",
    "fix_db_duree.php",
    "fix_db_progression.php",
    "fix_dossiers_destination.php",
    "fix_messages_table.php",
    "fix_creneaux_table.php",
    "final_db_fix.sql",
    "sql_quiz_update.sql",
    "DEPLOIEMENT_INFINITYFREE.md",
    "EVALUATION_INFINITYFREE.md",
    "OPTIMISATION_INFINITYFREE.md",
    "GUIDE_INSCRIPTION.md",
    "TEST_CSRF_GUIDE.md",
    "EXEMPLE_EMAIL_INTEGRATION.php"
)

$deletedCount = 0
$notFoundCount = 0

foreach ($file in $filesToDelete) {
    $filePath = Join-Path -Path $projectPath -ChildPath $file
    
    if (Test-Path -Path $filePath) {
        Remove-Item -Path $filePath -Force -ErrorAction SilentlyContinue
        if ($?) {
            Write-Host "✅ Supprimé: $file" -ForegroundColor Green
            $deletedCount++
        } else {
            Write-Host "⚠️  Erreur: $file" -ForegroundColor Yellow
        }
    } else {
        Write-Host "⊘  Introuvable: $file" -ForegroundColor Gray
        $notFoundCount++
    }
}

Write-Host ""
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan
Write-Host "✅ Nettoyage terminé!" -ForegroundColor Green
Write-Host "   Fichiers supprimés: $deletedCount" -ForegroundColor White
Write-Host "   Fichiers introuvables: $notFoundCount" -ForegroundColor Gray
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan
Write-Host ""
Write-Host "📝 Prochaines étapes:" -ForegroundColor Cyan
Write-Host "  1. git add ." -ForegroundColor White
Write-Host "  2. git commit -m 'Cleanup test files and prepare for Railway deployment'" -ForegroundColor White
Write-Host "  3. git push origin main" -ForegroundColor White
Write-Host ""
Write-Host "🚀 Le projet est prêt pour le déploiement sur Railway!" -ForegroundColor Green
