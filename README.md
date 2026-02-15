# 🏛️ BH CONNECT - Cabinet Immigration Management System

Système de gestion complet pour cabinets d'immigration avec gestion des dossiers, rendez-vous, documents et facturation.

## ✨ Fonctionnalités

### 👥 Gestion des Utilisateurs
- ✅ 3 rôles: Admin, Agent, Client
- ✅ Authentification sécurisée (password_hash)
- ✅ Token CSRF pour tous les formulaires
- ✅ Rate limiting contre les attaques par force brute
- ✅ Sessions sécurisées

### 📁 Gestion des Dossiers
- ✅ Création et suivi des dossiers clients
- ✅ Statuts de progression (Nouveau → Visa accordé/refusé)
- ✅ Historique complet avec timestamps
- ✅ Documents associés (PDF, images)
- ✅ Notes et commentaires

### 📅 Rendez-vous
- ✅ Création et gestion des créneaux
- ✅ Planification des RDV
- ✅ Confirmation et suivi
- ✅ Notifications

### 📊 Facturation
- ✅ Génération de factures
- ✅ Suivi du paiement
- ✅ Historique des transactions

### 📱 Features Supplémentaires
- ✅ Quiz/Tests pour clients
- ✅ Notifications en temps réel
- ✅ Search/Filtrage avancé
- ✅ Import/Export données
- ✅ Responsive design (mobile-friendly)

## 🚀 Déploiement

### Sur Railway (Recommandé)

```bash
# 1. Nettoyer les fichiers de test
./cleanup.sh    # Linux/Mac
cleanup.bat     # Windows

# 2. Configurer git
git init
git add .
git commit -m "Initial commit"

# 3. Déployer sur Railway
# Allez sur https://railway.app
# Connectez votre repo GitHub
# Railway déploiera automatiquement
```

### Sur InfinityFree

Voir [DEPLOIEMENT_INFINITYFREE.md](./DEPLOIEMENT_INFINITYFREE.md)

## 📋 Configuration

### Variables d'Environnement (.env)

```env
ENVIRONMENT=production
APP_NAME=BH CONNECT
APP_DEBUG=false
APP_LOG_LEVEL=warning

# Base de données (Railway génère DATABASE_URL)
DB_HOST=sql309.infinityfree.com
DB_PORT=3306
DB_NAME=if0_XXXXX_cabinet_immigration
DB_USER=if0_XXXXX
DB_PASS=xxxxx

# Sessions
SESSION_TIMEOUT=3600
SESSION_NAME=bh_connect_session

# Sécurité
CSRF_TOKEN_LENGTH=32
RATE_LIMIT_ATTEMPTS=5
RATE_LIMIT_WINDOW=300
```

## 📊 Architecture

```
bhconnect/
├── config/              # Configuration & classes helpers
│   ├── config.php       # Configuration principale
│   ├── database.php     # Connexion BD (Singleton)
│   ├── EnvLoader.php    # Chargement variables .env
│   ├── CSRFToken.php    # Gestion tokens CSRF
│   ├── RateLimiter.php  # Limitation des tentatives
│   └── ErrorLogger.php  # Logging des erreurs
├── models/              # Classes métier
│   ├── AuthModel.php
│   ├── ClientModel.php
│   ├── DossierModel.php
│   ├── DocumentModel.php
│   └── ...
├── controllers/         # Contrôleurs
├── includes/            # Fichiers partagés
│   ├── Constants.php    # Constantes globales
│   ├── header.php       # En-tête HTML
│   └── footer.php       # Pied de page
├── css/                 # Feuilles de style
├── js/                  # JavaScript
├── images/              # Images & icônes
├── uploads/             # Dossier uploads (gitignoré)
├── logs/                # Logs application (gitignoré)
│
├── index.php            # Page d'accueil
├── login.php            # Authentification
├── register.php         # Inscription
├── dashboard*.php       # Tableaux de bord
├── mon-dossier.php      # Suivi dossier client
├── dossiers.php         # Gestion dossiers
├── documents.php        # Gestion documents
├── rendez-vous.php      # Gestion RDV
├── factures.php         # Gestion facturation
├── messages.php         # Messaging
│
├── .env                 # Variables (git-ignoré)
├── .htaccess            # Configuration Apache
├── Procfile             # Configuration Railway
├── railway.json         # Configuration déploiement
├── composer.json        # Dépendances PHP
└── README.md            # Cette file
```

## 🔒 Sécurité

- ✅ Password hashing avec PASSWORD_DEFAULT (bcrypt)
- ✅ Protection CSRF sur tous les formulaires
- ✅ Rate limiting contre brute force
- ✅ Sessions sécurisées (HttpOnly, SameSite)
- ✅ Prepared statements (prévention SQL injection)
- ✅ XSS protection (htmlspecialchars)
- ✅ Fichiers sensibles protégés (.htaccess)
- ✅ Logging des erreurs sans révéler détails

## 📈 Performance

- ✅ Reconnexion automatique BD (3 tentatives)
- ✅ Vérification active de connexion
- ✅ Timeouts configurés (InfinityFree/Railway)
- ✅ Gzip compression (.htaccess)
- ✅ Cache navigateur pour assets
- ✅ Prepared statements optimisés

## 🧪 Tests & Diagnostic

Fichiers de diagnostic (à supprimer avant déploiement):
- `health-check.php` - État du serveur
- `diagnostic_complet.php` - Diagnostic détaillé
- `debug_csrf.php` - Debug tokens CSRF
- `test_login_form.php` - Test login/CSRF

## 📚 Documentation

- [DEPLOYMENT_RAILWAY.md](./DEPLOYMENT_RAILWAY.md) - Guide complet Railway
- [.env.example](./.env.example) - Template variables
- Code commenté en français

## 🛠️ Maintenance

### Health Check
```
GET /health-check.php
Réponse: JSON avec état du serveur
```

### Logs
```
logs/php_errors.log        # Erreurs PHP
logs/database_error.log    # Erreurs BD
logs/emails.log            # Envoi d'emails
```

### Monitoring
Utilisez Uptime Robot (gratuit):
1. Allez sur uptimerobot.com
2. Ajoutez monitoring: https://your-app.railway.app/health-check.php
3. Fréquence: 5 minutes
4. Recevez les alertes par email

## 📞 Support

- Vérifiez les logs d'erreur
- Utilisez le diagnostic_complet.php
- Contactez le support (Railway ou InfinityFree)

## 📄 Licence

Propriétaire - BH CONNECT Cabinet Immigration

---

**Version:** 1.0.0  
**Date:** 2026-02-15  
**Dernière mise à jour:** 2026-02-15
