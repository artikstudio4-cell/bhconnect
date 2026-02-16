# 🔗 CONFIGURER INFINITYFREE AVEC RAILWAY

Guide pour utiliser votre BD InfinityFree existante avec votre application déployée sur Railway.

---

## 📊 Vos Credentials InfinityFree

À partir de votre base de données:

```
HOST:     sql309.infinityfree.com
PORT:     3306
DATABASE: if0_40862714_cabinet_immigration
USER:     if0_40862714
PASSWORD: koWEQ4akLhQ
```

**Avantage:** Vos données existantes (12 users, 10 clients, 3 dossiers) sont déjà là! ✅

---

## 🚀 Étape 1: Configurer les Variables Railway

### Via Railway Dashboard

1. Allez à https://railway.app/dashboard
2. Cliquez sur votre projet **BH CONNECT**
3. Cliquez sur le service **web**
4. Onglet **Variables**
5. Ajoutez ces variables:

```
DB_HOST=sql309.infinityfree.com
DB_PORT=3306
DB_NAME=if0_40862714_cabinet_immigration
DB_USER=if0_40862714
DB_PASS=koWEQ4akLhQ
DB_CHARSET=utf8mb4
ENVIRONMENT=production
APP_DEBUG=false
```

![Ajouter Variables Railway]

### Ou via Fichier .env Local (pour test)

Éditez `c:\Users\Franck Mevaa\Documents\bhconnect\.env`:

```env
ENVIRONMENT=production
APP_DEBUG=false
APP_NAME=BH CONNECT

# Base de Données InfinityFree
DB_HOST=sql309.infinityfree.com
DB_PORT=3306
DB_NAME=if0_40862714_cabinet_immigration
DB_USER=if0_40862714
DB_PASS=koWEQ4akLhQ
DB_CHARSET=utf8mb4

# Sessions
SESSION_TIMEOUT=3600
SESSION_NAME=bh_connect_session

# Security
CSRF_TOKEN_LENGTH=32
RATE_LIMIT_ATTEMPTS=5
RATE_LIMIT_WINDOW=300

# Mail (InfinityFree utilise PHP mail)
MAIL_DRIVER=php
MAIL_FROM=noreply@bhconnect.epizy.com

# Timezone
APP_TIMEZONE=Africa/Douala
```

---

## 🧪 Étape 2: Tester la Connexion Locale

Avant de déployer sur Railway, testez que votre code se connecte à InfinityFree:

### Créer `test_infinityfree_connection.php`

```php
<?php
// Test de connexion à InfinityFree
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "<h1>Test de Connexion InfinityFree</h1>";
echo "<hr>";

echo "<h2>1. Configuration détectée:</h2>";
echo "DB_HOST: " . getenv('DB_HOST') . "<br>";
echo "DB_NAME: " . getenv('DB_NAME') . "<br>";
echo "DB_USER: " . getenv('DB_USER') . "<br>";
echo "<br>";

try {
    echo "<h2>2. Tentative de connexion...</h2>";
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "✅ <strong>Connexion réussie!</strong><br><br>";
    
    // Test des tables
    echo "<h2>3. Vérification des données:</h2>";
    
    $tables = [
        'utilisateurs' => 'SELECT COUNT(*) as count FROM utilisateurs',
        'clients' => 'SELECT COUNT(*) as count FROM clients',
        'dossiers' => 'SELECT COUNT(*) as count FROM dossiers',
        'rendez_vous' => 'SELECT COUNT(*) as count FROM rendez_vous',
        'factures' => 'SELECT COUNT(*) as count FROM factures',
    ];
    
    foreach ($tables as $table => $query) {
        try {
            $stmt = $conn->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = $result['count'] ?? 0;
            echo "✅ <strong>$table:</strong> $count enregistrements<br>";
        } catch (Exception $e) {
            echo "⚠️ <strong>$table:</strong> Erreur - " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<br><h2 style='color:green'>✅ Configuration InfinityFree OK!</h2>";
    
} catch (Exception $e) {
    echo "<h2 style='color:red'>❌ Erreur de connexion:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<p><strong>Solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Vérifier que DB_HOST, DB_USER, DB_PASS sont corrects</li>";
    echo "<li>Vérifier que .env existe et contient les bonnes valeurs</li>";
    echo "<li>Vérifier que InfinityFree autorise les connexions distantes</li>";
    echo "<li>Vérifier que le port 3306 est accessible</li>";
    echo "</ul>";
}
?>
```

### Lancer le test

```bash
# Depuis VS Code Terminal
php -S localhost:8000
# Puis visitez:
# http://localhost:8000/test_infinityfree_connection.php
```

**Résultat attendu:**
```
✅ Connexion réussie!
✅ utilisateurs: 12 enregistrements
✅ clients: 10 enregistrements
✅ dossiers: 3 enregistrements
✅ rendez_vous: 6 enregistrements
✅ factures: 3 enregistrements
```

---

## 🔒 Étape 3: Vérifier InfinityFree Accepte Connexions Distantes

InfinityFree peut bloquer les connexions distantes par défaut.

### Vérifier/Activer depuis phpMyAdmin InfinityFree

1. Allez à https://[votre-site].epizy.com/phpmyadmin
2. Connectez-vous avec vos credentials
3. Onglet **Utilisateurs**
4. Vérifiez que votre user a:
   - **Host:** `%` (accepte toutes les connexions)
   - Ou spécifier Railway IP

### Tester via MySQLWorkbench

1. Ouvrez MySQLWorkbench
2. Créer nouvelle connexion:
   - **Hostname:** `sql309.infinityfree.com`
   - **Port:** 3306
   - **Username:** `if0_40862714`
   - **Password:** `koWEQ4akLhQ`
3. Cliquez **Test Connection**
4. Si OK → InfinityFree accepte connexions distantes ✅

### Tester via Ligne de Commande

```bash
mysql -h sql309.infinityfree.com -u if0_40862714 -p if0_40862714_cabinet_immigration

# Entrez le password: koWEQ4akLhQ
# Si connecté: vous verrez prompt mysql>
```

---

## 📤 Étape 4: Pousser vers GitHub et Déployer Railway

### 1. Mettre à jour le code

```bash
cd "c:\Users\Franck Mevaa\Documents\bhconnect"

# L'application utilise déjà les variables d'environnement
# Aucun changement de code nécessaire!

# Vérifiez juste que config/database.php lit les env vars:
grep "getenv('DB_" config/database.php
# Doit montrer les 4 variables
```

### 2. Pousser vers GitHub

```bash
git add .
git commit -m "Configure InfinityFree database for Railway deployment

- Use existing InfinityFree database (sql309.infinityfree.com)
- Keep data: 12 users, 10 clients, 3 dossiers
- Test connection file included
- Ready for production"
git push origin main
```

### 3. Configurer Variables sur Railway

Railway Dashboard → Votre App → Variables:

Ajouter:
```
DB_HOST=sql309.infinityfree.com
DB_PORT=3306
DB_NAME=if0_40862714_cabinet_immigration
DB_USER=if0_40862714
DB_PASS=koWEQ4akLhQ
```

Railway redéploiera automatiquement (~2 minutes)

### 4. Vérifier que ça fonctionne

Accédez à: `https://your-app.railway.app/test_infinityfree_connection.php`

Devrait afficher:
```
✅ Connexion réussie!
✅ utilisateurs: 12 enregistrements
```

---

## 🔄 Étape 5: Tester l'Authentification

### Login avec vos users InfinityFree

**Admin:**
```
Email: admin@cabinet.com
Password: hashed (vérifier depuis phpmyadmin)
```

**Agent:**
```
Email: patrickmbeumo@gmail.com
Password: hashed
```

**Client:**
```
Email: franckmevaa21@gmail.com
Password: hashed
```

Pour reset un password si oublié:
```php
// Générer hash depuis PHPMyAdmin
password_hash('NewPassword123', PASSWORD_DEFAULT)
// Puis coller dans field mot_de_passe
```

---

## 🚨 Dépannage InfinityFree

### Problème: "Connection refused"

**Cause:** InfinityFree bloque les connexions distantes

**Solutions:**
1. Vérifier dans phpMyAdmin InfinityFree → Utilisateurs → Host = `%`
2. Contacter support InfinityFree pour autoriser connexions distantes
3. Ajouter l'IP de Railway à la whitelist (si disponible)

### Problème: "Unknown database"

**Cause:** Nom BD incorrect

**Solution:** Vérifier dans phpMyAdmin InfinityFree
- Vrai nom: `if0_40862714_cabinet_immigration`
- Pas `cabinet_immigration` ni `if0_40862714`

### Problème: "Access denied for user"

**Causes possibles:**
1. Password incorrect (copy-paste depuis phpmyadmin)
2. User n'existe pas
3. User bloqué

**Solutions:**
1. Tester password sur phpMyAdmin: `sql309.infinityfree.com/phpmyadmin`
2. Créer nouvel utilisateur si besoin
3. Contacter support InfinityFree

### Problème: "Lost connection during query"

**Cause:** Timeout (InfinityFree tue connexions longues)

**Solutions:**
1. Ajouter timeout dans config/database.php:
   ```php
   PDO::ATTR_TIMEOUT => 10,  // 10 secondes
   ```
2. Optimiser les queries (ajouter indexes)
3. Considérer migration vers Railway (meilleure performance)

---

## 📊 Avantages de cette Config

| Aspect | Bénéfice |
|--------|---------|
| **Données existantes** | 💾 Gardez tout: users, clients, dossiers |
| **Pas de migration** | ⚡ Pas besoin de réimporter |
| **Gratuit** | 💰 InfinityFree = gratuit |
| **Accessible** | 🌐 Accès depuis Railway ou local |
| **Backup** | ✅ Vos backups InfinityFree restent |

---

## ⚠️ Limitations InfinityFree

| Limitation | Impact | Workaround |
|-----------|--------|-----------|
| **Timeouts** | Requêtes longues échouent | Optimiser queries |
| **Performance** | Plus lent que Railway | Acceptable pour <100 users |
| **CPU limité** | Peut être throttled | Éviter les gros uploads |
| **Connexions** | Max ~50 concurrent | OK pour la plupart |
| **Pas de backups auto** | Risque de perte | Télécharger manuellement |

---

## 🛡️ Sécurité Conseils

### ✅ À faire

1. **Changer les credentials super faibles**
   ```sql
   -- Dans phpMyAdmin InfinityFree
   GRANT ALL ON if0_40862714_cabinet_immigration.* 
   TO 'if0_40862714_app'@'%' IDENTIFIED BY 'NewStrongPassword123!';
   ```

2. **Ne pas partager credentials**
   - Utiliser variables d'environnement Railway
   - Ne pas commiter .env sur GitHub

3. **Limiter accès par IP** (si possible)
   - Ask InfinityFree à whitelister Railway IPs

### ❌ À ne pas faire

1. ❌ Mettre password dans le code
2. ❌ Mettre password dans .env committed
3. ❌ Utiliser password générique
4. ❌ Donner credentials à tous les devs

---

## ✨ Checklist Déploiement

- [ ] `.env` configuré localement avec InfinityFree credentials
- [ ] Test local réussit: `http://localhost:8000/test_infinityfree_connection.php`
- [ ] MySQLWorkbench peut se connecter à sql309.infinityfree.com
- [ ] Code poussé sur GitHub
- [ ] Variables ajoutées dans Railway Dashboard
- [ ] Railway redéployé (attendre 2 min)
- [ ] Test sur Railway réussit: `https://app.railway.app/test_infinityfree_connection.php`
- [ ] Login fonctionne avec vos users
- [ ] Dossiers clients visibles
- [ ] Factures accessibles

---

## 🎯 Résumé

**Vous utilisez InfinityFree avec Railway!**

```
┌─── Local Dev ───┐
│ config/database.php → Lit DB_HOST/USER/PASS ✅
│ .env → InfinityFree credentials
└─────────────────┘
           ↓
┌─── GitHub ──────┐
│ Code poussé ✅
└─────────────────┘
           ↓
┌─── Railway ─────┐
│ Variables ajoutées ✅
│ sql309.infinityfree.com ← connexion distante
└─────────────────┘
```

---

**Êtes-vous prêt pour déployer?** 🚀

Confirmez:
- [ ] Credentials InfinityFree copiés correctement
- [ ] Prêt à pousser sur GitHub
- [ ] Prêt à configurer Railway

Je peux vous guider pour les prochaines étapes!
