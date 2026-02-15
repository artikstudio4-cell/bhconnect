# BH CONNECT - Railway Deployment Configuration

## ✅ Préparation pour Railway

Railway est une plateforme de déploiement moderne compatible avec:
- ✅ PHP 8.1+
- ✅ MySQL 8.0
- ✅ PostgreSQL
- ✅ Variables d'environnement
- ✅ SSL/HTTPS automatique

---

## 1. Configuration Railway

### Créer un nouveau projet

1. Allez sur **railway.app**
2. Connectez votre compte GitHub
3. Créez un nouveau projet

### Ajouter PostgreSQL (ou MySQL)

```bash
# Dans le dashboard Railway:
- Cliquez sur "+ New Service"
- Choisissez "PostgreSQL" ou "MySQL"
- Connectez-le à votre app
```

---

## 2. Variables d'Environnement

**Dans Railway Dashboard:**
Settings → Variables

```env
# Base de données (auto-générée par Railway)
DATABASE_URL=postgresql://user:pass@host:5432/dbname
# ou MySQL:
# DATABASE_URL=mysql://user:pass@host:3306/dbname

# Application
ENVIRONMENT=production
APP_NAME=BH CONNECT
APP_DEBUG=false
APP_LOG_LEVEL=warning
APP_TIMEZONE=Africa/Douala

# Sessions
SESSION_TIMEOUT=3600
SESSION_NAME=bh_connect_session

# Sécurité
CSRF_TOKEN_LENGTH=32
RATE_LIMIT_ATTEMPTS=5
RATE_LIMIT_WINDOW=300

# Upload
MAX_FILE_SIZE=5242880
UPLOAD_DIR=uploads
```

---

## 3. Fichiers de Configuration Railway

### Procfile
```
web: echo "Deploying..." && php -S 0.0.0.0:$PORT -t public
```

Ou avec Apache:
```
web: apache2-foreground
```

### public/index.php (opcional)
```php
<?php
// Router simple pour Railway
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace(getenv('RAILWAY_STATIC_URL') ?: '', '', $uri);

if ($uri === '/' || $uri === '') {
    require __DIR__ . '/../index.php';
    exit;
}

if (file_exists(__DIR__ . $uri)) {
    return false;
}

require __DIR__ . '/../index.php';
?>
```

---

## 4. Migration Base de Données

### Option A: Automatique avec Railway

```bash
# Dans le Procfile, avant le démarrage:
web: php migrate.php && php -S 0.0.0.0:$PORT -t public
```

### Option B: Manuel

1. Allez dans Railway Dashboard
2. Allez dans le service MySQL/PostgreSQL
3. Cliquez sur "Connect"
4. Importez le fichier `final_db_fix.sql`

---

## 5. Structure de Déploiement

```
bhconnect/
├── .github/
│   └── workflows/
│       └── deploy.yml        # CI/CD automatique
├── public/
│   ├── index.php            # Point d'entrée
│   └── ...
├── config/
├── models/
├── includes/
├── Procfile                 # Configuration Railway
├── railway.json             # Configuration déploiement
├── .env.example             # Exemple variables
├── composer.json            # Dépendances (optionnel)
└── README.md
```

---

## 6. Déploiement

### Via GitHub (Recommandé)

1. Poussez votre code sur GitHub
2. Connectez le repo à Railway
3. Railway déploie automatiquement à chaque push

```bash
git add .
git commit -m "Prepare for Railway deployment"
git push origin main
```

### Via Railway CLI

```bash
# Installer Railway CLI
npm i -g @railway/cli

# Se connecter
railway login

# Déployer
railway up
```

---

## 7. Vérification Post-Déploiement

Après le déploiement sur Railway:

1. **Vérifiez l'URL du domaine**
   ```
   https://your-app.railway.app
   ```

2. **Testez la santé du serveur**
   ```
   https://your-app.railway.app/health-check.php
   ```

3. **Vérifiez les logs**
   ```bash
   railway logs
   ```

4. **Testez les fonctionnalités clés**
   - Inscription
   - Connexion
   - Accès aux dossiers

---

## 8. Optimisations Railway

### Cache et Performance

```php
// En production Railway:
- Activer le cache d'OPcache
- Utiliser Redis pour les sessions
- Ajouter un CDN pour les assets statiques
```

### Sécurité

```apache
# .htaccess amélioré pour Railway
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Force HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # Bloquer fichiers sensibles
    RewriteRule "^\.env$" - [F]
    RewriteRule "^config/" - [F]
</IfModule>
```

---

## 9. Domaine Personnalisé

1. Allez dans Railway Dashboard
2. Settings → Domain
3. Ajoutez votre domaine personnalisé
4. Configurez les DNS records

---

## 10. Monitoring

### Avec Railway

- Allez dans "Metrics"
- Surveillez CPU, RAM, Réseau
- Configurez les alertes

### Avec Uptime Robot (Gratuit)

```
https://uptimerobot.com
- URL: https://your-app.railway.app/health-check.php
- Fréquence: 5 minutes
```

---

## Avantages de Railway vs InfinityFree

| Feature | InfinityFree | Railway |
|---------|-------------|---------|
| **Uptime** | ~90% | 99.5%+ |
| **Performance** | Lent, timeouts | Rapide, fiable |
| **Scalabilité** | Limitée | Excellente |
| **SSL/HTTPS** | Gratuit | Gratuit |
| **Logs** | Difficiles | Faciles |
| **Monitoring** | Limité | Excellent |
| **Prix** | Gratuit | À partir de $5 |

---

## Checklist Déploiement Railway

- [ ] Créer compte Railway
- [ ] Créer nouveau projet
- [ ] Ajouter PostgreSQL ou MySQL
- [ ] Configurer variables d'environnement
- [ ] Connecter repo GitHub
- [ ] Importer base de données
- [ ] Tester health-check.php
- [ ] Tester inscription/login
- [ ] Configurer domaine personnalisé
- [ ] Mettre en place Uptime Robot
- [ ] Supprimer fichiers de test (voir cleanup.sh)

---

**Date:** 2026-02-15
**Version:** v1.0 pour Railway
