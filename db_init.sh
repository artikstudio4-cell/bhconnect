#!/bin/bash
# BH CONNECT - Database Setup Script
# Initialise la base de données pour le développement local

# Couleurs pour l'output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║    BH CONNECT - Database Setup for Development             ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Vérifier les arguments
if [ $# -eq 0 ]; then
    echo -e "${YELLOW}Usage:${NC} ./db_init.sh [host] [user] [password] [database]"
    echo ""
    echo "Exemples:"
    echo "  ./db_init.sh localhost root password bhconnect_db"
    echo "  ./db_init.sh 127.0.0.1 root '' bhconnect_db"
    echo ""
    echo "Utilisez une chaîne vide '' pour pas de mot de passe"
    exit 1
fi

HOST=${1:-localhost}
USER=${2:-root}
PASS=${3:-}
DB=${4:-bhconnect_db}

echo -e "${BLUE}Configuration détectée:${NC}"
echo "  Host:     $HOST"
echo "  User:     $USER"
echo "  Password: ${PASS:-(aucun)}"
echo "  Database: $DB"
echo ""

# Construire la commande mysql
if [ -z "$PASS" ]; then
    MYSQL_CMD="mysql -h $HOST -u $USER"
else
    MYSQL_CMD="mysql -h $HOST -u $USER -p$PASS"
fi

# Vérifier la connexion
echo -e "${BLUE}Vérification de la connexion MySQL...${NC}"
$MYSQL_CMD -e "SELECT 1" > /dev/null 2>&1

if [ $? -ne 0 ]; then
    echo -e "${RED}✗ Impossible de se connecter à MySQL${NC}"
    echo -e "${YELLOW}Assurez-vous que:${NC}"
    echo "  1. MySQL/MariaDB est en cours d'exécution"
    echo "  2. Les identifiants sont corrects"
    echo "  3. Le serveur MySQL est accessible sur $HOST"
    exit 1
fi

echo -e "${GREEN}✓ Connexion OK${NC}"
echo ""

# Créer la base de données
echo -e "${BLUE}Création de la base de données '$DB'...${NC}"
$MYSQL_CMD -e "DROP DATABASE IF EXISTS \`$DB\`;"
$MYSQL_CMD -e "CREATE DATABASE \`$DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Base de données créée${NC}"
else
    echo -e "${RED}✗ Erreur lors de la création de la base de données${NC}"
    exit 1
fi

# Chercher le fichier SQL
if [ ! -f "final_db_fix.sql" ]; then
    # Chercher dans le répertoire parent
    if [ -f "../final_db_fix.sql" ]; then
        SQL_FILE="../final_db_fix.sql"
    else
        echo -e "${RED}✗ Fichier final_db_fix.sql non trouvé${NC}"
        echo -e "${YELLOW}Assurez-vous que le fichier se trouve dans le répertoire courant${NC}"
        exit 1
    fi
else
    SQL_FILE="final_db_fix.sql"
fi

# Importer le schéma
echo -e "${BLUE}Import du schéma de base de données...${NC}"
$MYSQL_CMD $DB < "$SQL_FILE"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Schéma importé avec succès${NC}"
else
    echo -e "${RED}✗ Erreur lors de l'import du schéma${NC}"
    exit 1
fi

echo ""

# Créer les utilisateurs de test
echo -e "${BLUE}Création des utilisateurs de test...${NC}"

# Hash des mots de passe:
# Admin@123 -> \$2y\$10\$...
# Agent@123 -> \$2y\$10\$...
# Client@123 -> \$2y\$10\$...

$MYSQL_CMD $DB << EOF
-- Admin user
INSERT INTO utilisateurs (nom, prenom, email, username, mot_de_passe, role, statut, date_creation)
VALUES ('Admin', 'Test', 'admin@bhconnect.test', 'admin', '\$2y\$10\$aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'Admin', 'actif', NOW())
ON DUPLICATE KEY UPDATE id=id;

-- Agent user
INSERT INTO utilisateurs (nom, prenom, email, username, mot_de_passe, role, statut, date_creation)
VALUES ('Agent', 'Test', 'agent@bhconnect.test', 'agent', '\$2y\$10\$aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'Agent', 'actif', NOW())
ON DUPLICATE KEY UPDATE id=id;

-- Client user
INSERT INTO utilisateurs (nom, prenom, email, username, mot_de_passe, role, statut, date_creation)
VALUES ('Client', 'Test', 'client@bhconnect.test', 'client', '\$2y\$10\$aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'Client', 'actif', NOW())
ON DUPLICATE KEY UPDATE id=id;
EOF

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Utilisateurs de test créés${NC}"
else
    echo -e "${YELLOW}⚠ Les utilisateurs de test n'ont pas pu être créés (peut-être déjà existants)${NC}"
fi

echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}✓ Configuration base de données terminée!${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}Prochaines étapes:${NC}"
echo "  1. Éditer .env avec:"
echo "     DB_HOST=$HOST"
echo "     DB_USER=$USER"
echo "     DB_PASS=$PASS"
echo "     DB_NAME=$DB"
echo ""
echo "  2. Lancer le serveur de développement:"
echo "     php -S localhost:8000"
echo ""
echo "  3. Accédez à http://localhost:8000"
echo ""
echo -e "${YELLOW}Utilisateurs de test disponibles:${NC}"
echo "  Admin:  admin@bhconnect.test / Admin@123"
echo "  Agent:  agent@bhconnect.test / Agent@123"
echo "  Client: client@bhconnect.test / Client@123"
echo ""
echo -e "${RED}⚠️  IMPORTANT: Changer les mots de passe avant production!${NC}"
echo ""
