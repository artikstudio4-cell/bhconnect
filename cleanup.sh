#!/bin/bash

# ==========================================
# Script de Nettoyage - BH CONNECT
# Supprime les fichiers de test et debug
# À exécuter avant déploiement
# ==========================================

echo "🧹 Nettoyage de BH CONNECT..."
echo ""

# Fichiers de debug et test
files_to_remove=(
    "debug_csrf.php"
    "debug_inscription.php"
    "debug_schema.php"
    "test_config.php"
    "test_login_form.php"
    "test_login_manually.php"
    "diagnostic_complet.php"
    "health-check.php"
    "setup-infinity-free.php"
    "setup_invoice_db.php"
    "setup_quiz_db.php"
    "DEPLOIEMENT_INFINITYFREE.md"
    "EVALUATION_INFINITYFREE.md"
    "OPTIMISATION_INFINITYFREE.md"
    "GUIDE_INSCRIPTION.md"
    "TEST_CSRF_GUIDE.md"
    "fix_creneaux_table.php"
    "fix_db_duree.php"
    "fix_db_progression.php"
    "fix_dossiers_destination.php"
    "fix_messages_table.php"
    "final_db_fix.sql"
    "sql_quiz_update.sql"
    "EXEMPLE_EMAIL_INTEGRATION.php"
    "EXPLICATION_HTACCESS.md"
)

# Supprimer les fichiers
removed_count=0
for file in "${files_to_remove[@]}"; do
    if [ -f "$file" ]; then
        rm "$file"
        echo "✓ Supprimé: $file"
        ((removed_count++))
    fi
done

echo ""
echo "✅ Nettoyage terminé!"
echo "📊 Fichiers supprimés: $removed_count"
echo ""
echo "⚠️  Dossiers nettoyés:"
echo "  ✓ Logs (contents)"
echo "  ✓ Uploads (contents, .gitkeep conservé)"
echo ""

# Vider les logs en conservation les gitkeep
if [ -d "logs" ]; then
    find logs -type f -not -name ".gitkeep" -delete
    echo "  ✓ logs/"
fi

# Vider les uploads en conservation les gitkeep
if [ -d "uploads" ]; then
    find uploads -type f -not -name ".gitkeep" -delete
    echo "  ✓ uploads/"
fi

echo ""
echo "📁 Structure restante:"
echo "  config/          (Configuration)"
echo "  models/          (Classes métier)"
echo "  includes/        (En-têtes, constantes)"
echo "  controllers/     (Contrôleurs)"
echo "  css/             (Styles)"
echo "  js/              (JavaScript)"
echo "  images/          (Images)"
echo "  icons/           (Icônes)"
echo "  quiz/            (Module quiz)"
echo "  sounds/          (Sons)"
echo "  admin/           (Adminisdration)"
echo ""

echo "✨ Prêt pour le déploiement!"
echo ""
echo "Prochaines étapes:"
echo "  1. git add ."
echo "  2. git commit -m 'Cleanup for Railway deployment'"
echo "  3. git push origin main"
echo ""
