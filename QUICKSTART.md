# 🚀 QUICKSTART - BH CONNECT

Guide de démarrage rapide pour développeurs et administrateurs.

## 📦 Installation Locale (Développement)

### Prérequis
- PHP 8.0+
- MySQL 8.0+ ou PostgreSQL
- Composer (optionnel)
- Git

### 1️⃣ Cloner & Configurer

```bash
# Cloner le repo
git clone <your-repo-url>
cd bhconnect

# Créer le fichier .env
cp .env.example .env

# Éditer .env avec vos variables
nano .env    # Linux/Mac
notepad .env # Windows
```

### 2️⃣ Configurer la Base de Données

```bash
# Créer la base de données
mysql -u root -p < final_db_fix.sql

# Ou manuellement:
# 1. Ouvrir MySQL Workbench/phpMyAdmin
# 2. Créer DB: bhconnect_db
# 3. Importer: final_db_fix.sql
```

### 3️⃣ Lancer le Serveur Local

```bash
# Avec le serveur PHP intégré
php -S localhost:8000

# Puis accédez à:
# http://localhost:8000
```

## 🏭 Déploiement Production

### Sur Railway (⭐ Recommandé)

```bash
# 1. Nettoyer les fichiers de test
./cleanup.sh      # Linux/Mac
cleanup.bat       # Windows

# 2. Pusher vers GitHub
git add .
git commit -m "Cleanup test files"
git push origin main

# 3. Sur Railway.app
# - Créer nouveau projet
# - Connecter GitHub
# - Railway déploiera automatiquement
# - ✅ Configuré dans Procfile et railway.json

# 4. Variables d'environnement
# Railway génère DATABASE_URL automatiquement
# Ajouter les autres vars dans Railway dashboard
```

### Sur InfinityFree (Alternative)

Voir [DEPLOIEMENT_INFINITYFREE.md](./DEPLOIEMENT_INFINITYFREE.md)

## 🔑 Comptes de Test

Après installation, utilisez ces identifiants:

```
ADMIN:
- Utilisateur: admin@bhconnect.test
- Mot de passe: Admin@123

AGENT:
- Utilisateur: agent@bhconnect.test
- Mot de passe: Agent@123

CLIENT:
- Utilisateur: client@bhconnect.test
- Mot de passe: Client@123
```

**⚠️ À changer en production!**

## 📋 Structure des Pages

| Page | URL | Accès | Description |
|------|-----|-------|-------------|
| Accueil | `/index.php` | Tous | Page d'accueil |
| Login | `/login.php` | Publique | Authentification |
| Register | `/register.php` | Publique | Inscription clients |
| Dashboard Admin | `/dashboard.php` | Admin | Vue admin |
| Dashboard Agent | `/dashboard-agent.php` | Agent | Vue agent |
| Dashboard Client | `/dashboard-client.php` | Client | Tableau de bord perso |
| Mes Dossiers | `/mon-dossier.php` | Client | Détails dossier |
| Gestion Dossiers | `/dossiers.php` | Admin+Agent | Tous les dossiers |
| Documents | `/documents.php` | Tous | Upload/téléchargement |
| Rendez-vous | `/rendez-vous.php` | Tous | Gestion RDV |
| Factures | `/factures.php` | Admin+Agent | Facturation |
| Messages | `/messages.php` | Tous | Messaging |

## 🔍 Diagnostics & Troubleshooting

### Test Rapide

```bash
# Vérifier l'état du serveur
curl http://localhost:8000/health-check.php

# Réponse attendue:
# {"status":"ok","php_version":"8.0+","db":"connected","timestamp":"..."}
```

### Diagnostic Complet

Accédez à: `http://localhost:8000/diagnostic_complet.php`

Affiche:
- ✅/❌ Configuration PHP
- ✅/❌ Connexion BD
- ✅/❌ Dossiers logs
- ✅/❌ Sessions
- ✅/❌ CSRF tokens
- ✅/❌ Permissions fichiers

### Logs d'Erreur

```bash
# Erreurs PHP
tail -f logs/php_errors.log

# Erreurs BD
tail -f logs/database_error.log

# Logs d'emails
tail -f logs/emails.log
```

## 🆘 Problèmes Courants

### ❌ "HTTP 500 Internal Server Error"
```
✓ Vérifier logs/php_errors.log
✓ Vérifier .env (DB_HOST, DB_PASS)
✓ Vérifier permissionsconfig/config.php:
  require_once 'EnvLoader.php'; (pas env.php)
```

### ❌ "Jeton de sécurité invalide"
```
✓ Vérifier sessions PHP actives
✓ Vérifier cookies activés dans navigateur
✓ Accès à http://localhost:8000/debug_csrf.php
```

### ❌ "Erreur lors de l'inscription"
```
✓ Vérifier création table utilisateurs
✓ Vérifier création table clients
✓ Vérifier permissions BDD
✓ Vérifier destinée_id n'existe pas dans dossiers
```

### ❌ "Ce site est inaccessible (ERR_FAILED)"
```
✓ Sur InfinityFree: ajouter timeouts .htaccess
✓ Sur Railway: augmenter ressources
✓ Vérifier health-check.php
✓ Vérifier logs/database_error.log
```

## 📱 Déployer un Update

Une fois en production:

```bash
# 1. Faire vos changements local
# 2. Tester localement
# 3. Pusher
git add .
git commit -m "Description du changement"
git push origin main

# Railway redéploiera automatiquement
# (Voir statut dans Railway dashboard)
```

## 🔐 Sécurité - Checklist

Avant de passer en production:

- [ ] APP_DEBUG=false dans .env
- [ ] Mot de passe DB fort (25+ caractères)
- [ ] CSRF tokens activés
- [ ] Rate limiting activé
- [ ] HTTPS activé (Railway: automatique)
- [ ] Logs sensibles gitignorés
- [ ] Tests fichiers supprimés (./cleanup.sh)
- [ ] Comptes de test supprimés
- [ ] Monitoring configuré (Uptime Robot)

## 📞 Support & Issues

Si problème:

1. Vérifier **Logs**: `logs/*.log`
2. Exécuter **Diagnostic**: `/diagnostic_complet.php`
3. Lire **Documentation**:
   - DEPLOYMENT_RAILWAY.md
   - DEPLOIEMENT_INFINITYFREE.md
4. **Gitignore**: Check logs/ et uploads/ pas committés

## 🎯 Prochaines Étapes

- [ ] Configurer domaine personnalisé
- [ ] Configurer emails (SMTP)
- [ ] Ajouter certificat SSL (InfinityFree)
- [ ] Configurer backups BD
- [ ] Ajouter monitoring (Uptime Robot)
- [ ] Documenter processus support client

---

**Besoin d'aide?** Consultez la documentation dans:
- /DEPLOYMENT_RAILWAY.md
- /DEPLOIEMENT_INFINITYFREE.md
- /README.md
