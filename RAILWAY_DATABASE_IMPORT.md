# 📊 GUIDE D'IMPORTATION BD SUR RAILWAY

Guide complet pour importer votre base de données réelle sur Railway.app

---

## 🚀 Étape 1: Créer le Service de Base de Données sur Railway

### Via le Dashboard Railway

1. Allez à https://railway.app/dashboard
2. Cliquez sur votre projet BH CONNECT
3. Cliquez sur "+ New Service" (coin supérieur droit)
4. Sélectionnez **MySQL** (ou PostgreSQL si vous préférez)
5. Attendez 1-2 minutes que la BD s'initialise

### Configuration Automatique
Railway génère automatiquement:
- ✅ Root password (sécurisé)
- ✅ Database URL (variable `DATABASE_URL`)
- ✅ Host, Port, Username, Password
- ✅ Connexion réseau (accessible depuis votre service web)

---

## 💾 Étape 2: Exporter Vos Données Locales

### Option A: Depuis phpMyAdmin (Recommandé)

1. Ouvrez phpMyAdmin local (http://localhost/phpmyadmin)
2. Sélectionnez la base `cabinet_immigration`
3. Allez à l'onglet **Export**
4. Choisissez:
   - Format: **SQL**
   - Options:
     - ✅ Structure
     - ✅ Data
     - ✅ Add DROP TABLE
5. Cliquez **Go** → Enregistrez le fichier

### Option B: Via Ligne de Commande

```bash
mysqldump -u root -p cabinet_immigration > cabinet_immigration.sql
# Entrez votre mot de passe quand demandé
```

### Résultat
File: `cabinet_immigration.sql` (1-5 MB selon volume)

---

## 🔐 Étape 3: Accéder à votre BD Railway

### Récupérer les Credentials

1. Railway Dashboard → Votre Projet → Base de données (MySQL)
2. Cliquez sur **Connect**
3. Copiez les informations:
   ```
   MYSQL_HOST=xyz.railway.internal
   MYSQL_PORT=3306
   MYSQL_USER=root
   MYSQL_PASSWORD=xxx (généré automatiquement)
   MYSQL_ROOT_PASSWORD=xxx
   RAILWAY_DATABASE_URL=mysql://root:xxx@host:3306/railway
   ```

**Note:** Le nom de la BD est `railway` par défaut

---

## 📤 Étape 4: Importer vos Données

### Option A: Via MySQLWorkbench (Idéal)

1. Ouvrez MySQL Workbench
2. Créez une nouvelle connexion:
   - **Connection Name:** Railway Cabinet
   - **Hostname:** `xyz.railway.internal` (du dashboard)
   - **Port:** 3306
   - **Username:** root
   - **Password:** [Collez le password généré]
   - Cliquez **Test Connection** → OK
3. Double-cliquez sur la connexion pour ouvrir
4. Menu: **Server** → **Data Import**
5. Sélectionnez votre fichier `cabinet_immigration.sql`
6. Cliquez **Start Import**
7. ✅ Données importées dans Railway!

### Option B: Via PhpMyAdmin Web

Si Railway expose phpMyAdmin (certains plans):
1. Aller à l'URL fournie par Railway
2. Se connecter avec les credentials du Dashboard
3. Importer le SQL file via l'interface

### Option C: Via Ligne de Commande

```bash
mysql -h xyz.railway.internal -u root -p railway < cabinet_immigration.sql
# Enter password quand demandé
```

**Important:** Utilisez le nom `railway` (pas `cabinet_immigration`)

---

## 🔧 Étape 5: Vérifier l'Importation

### Via MySQLWorkbench

```sql
-- Vérifier que les données sont là
USE railway;
SELECT COUNT(*) as total_users FROM utilisateurs;
SELECT COUNT(*) as total_clients FROM clients;
SELECT COUNT(*) as total_dossiers FROM dossiers;
```

Expected results:
- utilisateurs: 12 rows
- clients: 10 rows
- dossiers: 3 rows

### Via Ligne de Commande

```bash
mysql -h xyz.railway.internal -u root -p railway -e "SELECT COUNT(*) as users FROM utilisateurs;"
```

---

## 🔗 Étape 6: Configurer votre Application PHP

### .env Configuration

Éditez votre fichier `.env` côté serveur (ou variables Railway):

```env
# DATABASE CONFIGURATION
DB_HOST=xyz.railway.internal
DB_PORT=3306
DB_NAME=railway
DB_USER=root
DB_PASS=xyz (le password généré)
DB_CHARSET=utf8mb4

# Alternative: DATABASE_URL (utilisé automatiquement par PDO)
DATABASE_URL=mysql://root:password@xyz.railway.internal:3306/railway
```

### Ou utiliser DATABASE_URL directement

Railway fournit `DATABASE_URL` automatiquement. Dans `config/database.php`:

```php
// Récupérer depuis la variable d'environnement
$databaseUrl = getenv('DATABASE_URL') ?: 'mysql://localhost/cabinet_immigration';

// Parser l'URL
$parsed = parse_url($databaseUrl);
$host = $parsed['host'];
$user = $parsed['user'];
$pass = $parsed['pass'];
$dbname = ltrim($parsed['path'], '/');
```

---

## ✅ Étape 7: Tester la Connexion

### Via Page de Test

Créez `test_railway_db.php`:

```php
<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("SELECT COUNT(*) as users FROM utilisateurs");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "✅ Connexion OK!<br>";
    echo "Utilisateurs: " . $result['users'] . "<br>";
    echo "Database: " . $dbname;
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
?>
```

Accédez à: `https://your-app.railway.app/test_railway_db.php`

### Via Terminal (SSH in Railway)

```bash
# Dans Railway, accédez au terminal de votre web service
mysql -h localhost -u root -p railway -e "SELECT COUNT(*) FROM utilisateurs;"
```

---

## 🚨 Troubleshooting

### ❌ "Connection refused"

Causes possibles:
- ❌ BD n'est pas encore initialisée (attendre 2-3 min)
- ❌ Credentials invalides (vérifier dans le Dashboard)
- ❌ Firewall bloque la connexion

Solutions:
1. Vérifier que le service MySQL est running (Dashboard → Status)
2. Vérifier les credentials (copier-coller du Dashboard)
3. Tester depuis MySQLWorkbench d'abord

### ❌ "Access denied for user 'root'"

Solutions:
- Vérifier que le password est exact (copy-paste du Dashboard)
- Réinitialiser le service: Dashboard → Delete → Recréer

### ❌ "Unknown database 'cabinet_immigration'"

Solutions:
- Utiliser `railway` (pas `cabinet_immigration`)
- Ou créer la BD manuellement:
  ```sql
  CREATE DATABASE cabinet_immigration CHARACTER SET utf8mb4;
  ```

### ❌ "Commands out of sync"

Solutions:
- Affecter à cause des transactions incompatibles
- Recréer le service et réimporter

---

## 🔄 Étape 8: Déployer votre Application

### 1. Ajouter DATABASE_URL aux Variables Railway

Railway Dashboard → Votre web service → Variables:

```
DATABASE_URL=mysql://root:password@xyz.railway.internal:3306/railway
```

Railway ajoute automatiquement cette variable quand vous liez la BD.

### 2. Pousser le Code

```bash
git push origin main
```

Railway redéploiera automatiquement (~2 min)

### 3. Vérifier que tout fonctionne

```
https://your-app.railway.app
```

- [ ] Login page se charge
- [ ] Pouvez vous connecter (admin@cabinet.com)
- [ ] Pouvez accéder aux dossiers
- [ ] Pouvez voir les clients

---

## 📊 Vérification Complète

### Checklist Post-Déploiement

```
✅ Base de données accessible
✅ Données importées (utilisateurs, clients, dossiers)
✅ Application se charge
✅ Login fonctionne
✅ Dossiers visibles
✅ Documents affichés
✅ RDV chargés
✅ Factures accessibles
```

### Vérifier Logs

Si problème:
1. Railway Dashboard → Logs tab
2. Voir les erreurs PHP/MySQL
3. Vérifier `logs/database_error.log`

---

## 🎯 Scénarios Courants

### Scénario 1: Petite BD (< 10 MB)

**Recommandé:**
1. Exporter via MySQLWorkbench
2. Importer via MySQLWorkbench
3. Tester via SQL queries
4. Déployer

**Temps total:** 15 minutes

### Scénario 2: Grosse BD (> 50 MB)

**Recommandé:**
1. Exporter en chunks via mysqldump
2. Importer en ligne de commande
3. Vérifier avec des indexes

```bash
mysqldump -u root -p cabinet_immigration > export.sql
# SSH into Railway
mysql -h localhost -u root -p railway < export.sql
```

**Temps total:** 30-45 minutes

### Scénario 3: Synchronisation Continue

**Pour les environments de dev/staging:**

```bash
# Chaque jour, exporter production
mysqldump -h prod.server -u root -p prod_db > latest.sql

# Importer dans Railway
mysql -h xyz.railway.internal -u root -p railway < latest.sql
```

---

## 🔐 Sécurité BD

### Bonnes Pratiques

1. **Mots de passe forts:**
   - ✅ Railway génère automatiquement des passwords forts
   - Ne pas les changer manuellement

2. **Limiter les accès:**
   - ❌ Ne pas partager les credentials
   - ✅ Utiliser les variables d'environnement Railway
   - ✅ Configurer des users spécifiques par application

3. **Firewall:**
   - ✅ Railway isole BD - pas accessible depuis internet
   - ✅ Connexion intranet uniquement entre services

4. **Sauvegarde:**
   - ✅ Railway offre des backups automatiques
   - Dashboard → Backups tab
   - Télécharger manuellement si besoin

---

## 📈 Scaling & Performance

### Si Application Grandit

**Phase 1 (Démarrage):**
- ✅ Plan gratuit/hobby de Railway
- ✅ BD: MySQL 1 GB RAM
- ✅ Sufficient for < 100k records

**Phase 2 (Croissance):**
- Upgrade à plan payant
- Ajouter index sur colonnes fréquemment cherchées
- Activer query caching

**Phase 3 (Production):**
- Considérer PostgreSQL (plus scalable)
- Ajouter read replicas
- Monitoring et alertes

---

## 📞 Aide Supplémentaire

### Resources

- Railway Docs: https://docs.railway.app/databases/mysql
- MySQL Documentation: https://dev.mysql.com/doc/
- Your app health: Check `logs/database_error.log`

### Contacts

- Railway Support: https://discord.gg/railway (Discord)
- Votre admin local DB: [à remplir]

---

## ✨ Résumé

| Étape | Action | Durée |
|-------|--------|-------|
| 1 | Créer service BD Railway | 2 min |
| 2 | Exporter BD locale | 1 min |
| 3 | Récupérer credentials | 1 min |
| 4 | Importer données | 5 min |
| 5 | Vérifier données | 2 min |
| 6 | Configurer .env | 2 min |
| 7 | Tester connexion | 2 min |
| 8 | Déployer app | 3 min |

**Total: ~20 minutes**

---

**Vous êtes prêt! Commencez par l'Étape 1. 🚀**

Besoin d'aide? Voir Troubleshooting ou contactez le support Railway.
