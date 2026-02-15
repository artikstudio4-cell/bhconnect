# 🔒 SECURITY CHECKLIST - Pre-Deployment

Guide de vérification de sécurité avant de déployer en production sur Railway.

## ✅ Configuration de Sécurité

### Variables d'Environnement

- [ ] `APP_DEBUG=false` — Debug mode désactivé
- [ ] `APP_LOG_LEVEL=warning` — Logging au niveau warning
- [ ] `ENVIRONMENT=production` — Environnement production configuré
- [ ] `.env` ajouté à `.gitignore` — Pas de credentials en git
- [ ] Mots de passe DB: 25+ caractères mélangés (lettres, chiffres, symboles)
- [ ] `SESSION_TIMEOUT` configuré (3600s = 1 heure)
- [ ] Timezone correcte: `TIMEZONE=Europe/Paris`

### Vérification .env

```bash
# Ne doit PAS contenir:
# - Mot de passe en clair dans le code
# - API keys
# - Email credentials

# Vérifier
grep -i "password\|api\|secret" *.php config/*.php models/*.php
# Résultat attendu: aucune clé en dur
```

---

## ✅ Code Security

### Protection CSRF
- [ ] CSRFToken::field() utilisé dans tous les formulaires
- [ ] CSRFToken::verify() appelé pour chaque POST/PUT/DELETE
- [ ] Token regeneré après vérification
- [ ] Support AJAX avec getForAjax()

Vérifier:
```bash
grep -r "CSRFToken::" *.php | grep -c "field\|verify"
# Devrait avoir beaucoup de résultats
```

### Authentication
- [ ] Mots de passe hashés avec `password_hash()`
- [ ] `password_verify()` utilisé pour vérification
- [ ] Sessions régénérées après login
- [ ] Rate limiting activé (5 tentatives/5min)

Vérifier:
```bash
grep -r "password_hash\|password_verify" models/
# Devrait montrer les usages
```

### Input Validation
- [ ] Tous les inputs utilisateur validés
- [ ] `htmlspecialchars()` sur les outputs
- [ ] Prepared statements utilisés (pas de SQL injection)
- [ ] Types validés (email, phone, etc)

Vérifier:
```bash
grep -r "SELECT \$\|INSERT \$\|UPDATE \$\|DELETE \$" models/
# Ne devrait RIEN montrer (les ? placeholders sont bons)
```

### Error Handling
- [ ] Messages d'erreur n'exposent pas les détails technique
- [ ] Exceptions loggées mais pas affichées
- [ ] ErrorLogger configuré et activé
- [ ] Logs stockés en dehors du webroot

Vérifier:
```bash
ls -la logs/
# Fichiers doivent exister et être gitignorés
```

---

## ✅ Base de Données

### Permissions
- [ ] DB user peut lire/écrire/modifier (pas DROP/ALTER en production)
- [ ] Pas d'accès root depuis l'app
- [ ] Préserver backup avant changements

### Schema
- [ ] Tables créées avec charset utf8mb4
- [ ] Primary keys sur toutes les tables
- [ ] Foreign keys configurées
- [ ] Indexes sur colonnes fréquemment cherchées

Vérifier avec Railway:
```bash
# Depuis Railway dashboard > Database > View Connection
# Tester avec un client GUI (MySQL Workbench)
```

### Données Sensibles
- [ ] Mots de passe haché (bcrypt)
- [ ] Emails validés avant insertion
- [ ] Qui a accès aux données personnelles contrôlé par rôles
- [ ] Soft deletes activés si approprié

---

## ✅ Environnement Railway

### Configuration
- [ ] Procfile correct: `web: php -S 0.0.0.0:$PORT`
- [ ] railway.json complète avec build/start
- [ ] Environment variables dans Railway dashboard
- [ ] Database service crée (MySQL ou PostgreSQL)

### Déploiement
- [ ] Code pushé sur GitHub/GitLab
- [ ] Railway connecté au repo
- [ ] Déploiement automatique activé
- [ ] Logs accessibles dans Railway dashboard

### Performance
- [ ] Timeouts configurés (120s min)
- [ ] Compression gzip activée
- [ ] Cache headers configurés
- [ ] Connexion BD poolée

---

## ✅ HTTPS & Domaine

### Certificat SSL
- [ ] HTTPS automatique sur railway.app ✅
- [ ] Certificat auto-renouvelé par Railway ✅
- [ ] Redirects HTTP → HTTPS configurés

### Domaine Personnalisé
- [ ] Si nécessaire: ajouter dans Railway > Settings
- [ ] DNS pointe vers Railway
- [ ] SSL fonctionne sur domaine (Railway géré automatiquement)

---

## ✅ Fichiers & Permissions

### Uploads Directory
- [ ] `uploads/` créé avec 755 permissions
- [ ] Gitignore contrôle les uploads
- [ ] Extension fichiers validées (PDF, JPG, PNG, DOC)
- [ ] Taille fichiers limitée (< 10MB)

### Logs Directory
- [ ] `logs/` gitignore (sensitif)
- [ ] Permissions 755
- [ ] Logs rotation configurée si gros volume
- [ ] Archives des logs sauvegardées

### Config Files
- [ ] `config/database.php` — Ne contient pas de credentials
- [ ] `config/config.php` — Charge depuis .env
- [ ] `.htaccess` — Sécurité, timeouts, headers

Vérifier:
```bash
grep -r "DB_PASS\|DBPASS\|dbpass" config/
# Ne devrait RIEN montrer
```

---

## ✅ Fonctionnalité

### Pages Critiques
- [ ] Login fonctionne (/login.php)
- [ ] Registration fonctionne (/register.php)
- [ ] Dashboard accessible après login
- [ ] Dossier client visible avec données
- [ ] RDV peut être créé/modifié
- [ ] Documents peuvent être uploadés/téléchargés
- [ ] Factures générées correctement

### Rôles & Permissions
- [ ] Admin peut accéder à tous les dossiers
- [ ] Agent ne peut accéder qu'à ses dossiers
- [ ] Client ne peut accéder qu'à son dossier
- [ ] Logout supprime la session

### Sessions
- [ ] CSRF token validé sur chaque formulaire
- [ ] Session timeout fonctionne
- [ ] Cookies secure activés (HttpOnly, SameSite)
- [ ] Session data pas exposée en URL

---

## ✅ Monitoring & Logs

### Logging
- [ ] `logs/php_errors.log` capture les erreurs PHP
- [ ] `logs/database_error.log` capture les erreurs BD
- [ ] Logs visibles dans Railway dashboard
- [ ] Rotation logs configurée si gros volume

### Monitoring
- [ ] Uptime Robot monitoring configuré
- [ ] Alertes email si site down
- [ ] Health endpoint accessible (`health-check.php` OR autre)

Exemple:
```bash
curl https://votre-app.railway.app/
# Devrait retourner HTML de la page 200 OK
```

---

## ✅ Avant de Déployer

### Checklist Git
```bash
# Status
git status
# Devrait être clean (No files to commit)

# Vérifier les secrets
git log --all --format=%H | while read hash; do 
  git log -p $hash | grep -i "password\|api_key\|secret"
done
# Devrait rien trouver

# Vérifier .gitignore
cat .gitignore | grep -E "logs|uploads|.env"
# Devrait les inclure
```

### Test Local
```bash
# 1. Créer .env local
cp .env.example .env
# Éditer avec localhost/dev values

# 2. Lancer serveur
php -S localhost:8000

# 3. Tester flow principal
# - Aller à http://localhost:8000
# - Créer compte
# - Login
# - Accéder dossier
# - Upload document

# 4. Vérifier logs
cat logs/php_errors.log
# Devrait être vide ou mini erreurs
```

### Push Final
```bash
# Vérifier les changements
git diff

# Commit avec message clair
git commit -am "Security audit passed - ready for production"

# Push (Railway redéploiera auto)
git push origin main

# Vérifier deployment
# - Aller à railway.app/dashboard
# - Attendre le déploiement (2-3 min)
- Cliquer sur le service
- Voir les logs
```

---

## 🚨 Issues Commun à Éviter

### ❌ Ne PAS faire:
- ❌ Commiter .env avec credentials
- ❌ Laisser APP_DEBUG=true en production
- ❌ Laisser fichiers de test (debug_*.php)
- ❌ Mettre mots de passe en dur dans le code
- ❌ Ne pas valider inputs utilisateur
- ❌ Ne pas hasher mots de passe
- ❌ Exposer detailles erreurs à l'utilisateur

### ✅ À faire:
- ✅ Documenter l'accès (qui peut faire quoi)
- ✅ Monitorer les logs régulièrement
- ✅ Mettre à jour dépendances quand possibles
- ✅ Sauvegarder BD régulièrement
- ✅ Tester le recovery en cas de problème

---

## 📋 Sign-off

Avant de considérer le projet comme "production ready":

- [ ] Tous les points ✅ ci-dessus vérifiés
- [ ] Code review effectuée
- [ ] Tests en local successifs
- [ ] Documentation à jour
- [ ] Team informée du déploiement
- [ ] Plan de rollback en place
- [ ] Monitoring/alertes configurés

**Status:** ✅ READY FOR PRODUCTION  
**Date:** 2026-02-15  
**Reviewed By:** _____________  
**Approved By:** _____________

---

Pour questions ou problèmes, voir:
- README.md - Documentation générale
- DEPLOYMENT_RAILWAY.md - Guide déploiement
- QUICKSTART.md - Démarrage rapide
