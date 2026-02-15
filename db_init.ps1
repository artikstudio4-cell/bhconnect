# BH CONNECT - Database Setup Script (Windows PowerShell)
# Initialise la base de données pour le développement local

param(
    [string]$Host = "localhost",
    [string]$User = "root",
    [string]$Password = "",
    [string]$Database = "bhconnect_db"
)

# Afficher l'aide si pas d'arguments
if ($args.Count -eq 0 -and -not $PSBoundParameters.ContainsKey('Host')) {
    Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Blue
    Write-Host "║    BH CONNECT - Database Setup for Development             ║" -ForegroundColor Blue
    Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Blue
    Write-Host ""
    Write-Host "Usage: powershell -File db_init.ps1 -Host localhost -User root -Password password -Database bhconnect_db" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Examples:" -ForegroundColor Yellow
    Write-Host "  # Avec mot de passe"
    Write-Host "  ..\db_init.ps1 -Host localhost -User root -Password 'mypass' -Database bhconnect_db"
    Write-Host ""
    Write-Host "  # Sans mot de passe"
    Write-Host "  ..\db_init.ps1 -Host localhost -User root -Database bhconnect_db"
    Write-Host ""
    exit 1
}

Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Blue
Write-Host "║    BH CONNECT - Database Setup for Development             ║" -ForegroundColor Blue
Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Blue
Write-Host ""

Write-Host "Configuration détectée:" -ForegroundColor Blue
Write-Host "  Host:     $Host"
Write-Host "  User:     $User"
Write-Host "  Password: $(if ([string]::IsNullOrEmpty($Password)) { '(aucun)' } else { '(défini)' })"
Write-Host "  Database: $Database"
Write-Host ""

# Vérifier si mysql est accessible
Write-Host "Vérification de MySQL/MariaDB..." -ForegroundColor Blue
try {
    $mysqlPath = Get-Command mysql -ErrorAction Stop
    Write-Host "✓ MySQL trouvé: $($mysqlPath.Source)" -ForegroundColor Green
} catch {
    Write-Host "✗ MySQL n'a pas été trouvé dans le PATH" -ForegroundColor Red
    Write-Host ""
    Write-Host "Solutions:" -ForegroundColor Yellow
    Write-Host "  1. Installer MySQL/MariaDB: https://dev.mysql.com/downloads/mysql/"
    Write-Host "  2. Ajouter MySQL au PATH:"
    Write-Host "     - Windows: Control Panel > System > Environment Variables"
    Write-Host "     - Ajouter: C:\Program Files\MySQL\MySQL Server 8.0\bin"
    Write-Host "  3. Redémarrer le terminal PS après modification"
    Write-Host ""
    exit 1
}

# Construire la commande mysql
$mysqlArgs = @("-h", $Host, "-u", $User)
if (-not [string]::IsNullOrEmpty($Password)) {
    $mysqlArgs += @("-p$Password")
}

Write-Host "Vérification de la connexion MySQL..." -ForegroundColor Blue
try {
    $testOutput = & mysql @mysqlArgs -e "SELECT 1;" 2>&1 | Out-String
    Write-Host "✓ Connexion OK" -ForegroundColor Green
} catch {
    Write-Host "✗ Impossible de se connecter à MySQL" -ForegroundColor Red
    Write-Host ""
    Write-Host "Assurez-vous que:" -ForegroundColor Yellow
    Write-Host "  1. MySQL/MariaDB est en cours d'exécution"
    Write-Host "  2. Les identifiants sont corrects"
    Write-Host "  3. Le serveur MySQL est accessible sur $Host"
    Write-Host ""
    exit 1
}

Write-Host ""

# Créer la base de données
Write-Host "Création de la base de données '$Database'..." -ForegroundColor Blue
try {
    & mysql @mysqlArgs -e "DROP DATABASE IF EXISTS \`$Database\`;"
    & mysql @mysqlArgs -e "CREATE DATABASE \`$Database\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    Write-Host "✓ Base de données créée" -ForegroundColor Green
} catch {
    Write-Host "✗ Erreur lors de la création de la base de données" -ForegroundColor Red
    Write-Host $_.Exception.Message
    exit 1
}

# Chercher le fichier SQL
$sqlFile = $null
if (Test-Path "final_db_fix.sql") {
    $sqlFile = "final_db_fix.sql"
} elseif (Test-Path "../final_db_fix.sql") {
    $sqlFile = "../final_db_fix.sql"
} else {
    Write-Host "✗ Fichier final_db_fix.sql non trouvé" -ForegroundColor Red
    Write-Host "Assurez-vous que le fichier se trouve dans le répertoire courant" -ForegroundColor Yellow
    exit 1
}

# Importer le schéma
Write-Host "Import du schéma de base de données..." -ForegroundColor Blue
try {
    & mysql @mysqlArgs $Database < $sqlFile
    Write-Host "✓ Schéma importé avec succès" -ForegroundColor Green
} catch {
    Write-Host "✗ Erreur lors de l'import du schéma" -ForegroundColor Red
    Write-Host $_.Exception.Message
    exit 1
}

Write-Host ""

# Note sur les utilisateurs de test
Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Blue
Write-Host "✓ Configuration base de données terminée!" -ForegroundColor Green
Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Blue
Write-Host ""

Write-Host "Prochaines étapes:" -ForegroundColor Yellow
Write-Host "  1. Éditer .env avec:"
Write-Host "     DB_HOST=$Host"
Write-Host "     DB_USER=$User"
Write-Host "     DB_PASS=$Password"
Write-Host "     DB_NAME=$Database"
Write-Host ""
Write-Host "  2. Lancer le serveur de développement:"
Write-Host "     php -S localhost:8000"
Write-Host ""
Write-Host "  3. Accédez à http://localhost:8000"
Write-Host ""

Write-Host "Note:" -ForegroundColor Yellow
Write-Host "  Les utilisateurs de test n'ont pas pu être créés automatiquement"
Write-Host "  Créez-les manuellement si nécessaire:"
Write-Host "    Username: admin@bhconnect.test / Password: Admin@123"
Write-Host "    Username: agent@bhconnect.test / Password: Agent@123"
Write-Host "    Username: client@bhconnect.test / Password: Client@123"
Write-Host ""
Write-Host "⚠️  IMPORTANT: Changer les mots de passe avant de passer en production!" -ForegroundColor Red
Write-Host ""
