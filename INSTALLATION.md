# Guide d'Installation Détaillé - GameSet

Ce document fournit un guide d'installation détaillé étape par étape pour GameSet, incluant l'installation et la configuration complète de MariaDB.

## Table des matières

1. [Vérification des prérequis](#vérification-des-prérequis)
2. [Installation de PHP](#installation-de-php)
3. [Installation de Composer](#installation-de-composer)
4. [Installation et configuration de MariaDB](#installation-et-configuration-de-mariadb)
5. [Configuration du projet GameSet](#configuration-du-projet-gameset)
6. [Configuration avancée de MariaDB](#configuration-avancée-de-mariadb)
7. [Déploiement avec Docker](#déploiement-avec-docker)
8. [Troubleshooting](#troubleshooting)

## Vérification des prérequis

Avant de commencer, vérifiez que vous disposez des droits d'administrateur sur votre système.

### Vérifications système

```bash
# Vérifier le système d'exploitation
uname -a

# Vérifier l'espace disque disponible (minimum 2 GB recommandé)
df -h

# Vérifier la mémoire disponible (minimum 1 GB RAM recommandé)
free -h
```

## Installation de PHP

### Ubuntu/Debian (20.04, 22.04, 24.04)

```bash
# Ajouter le dépôt ondrej/php pour obtenir PHP 8.2
sudo apt update
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update

# Installer PHP 8.2 et les extensions nécessaires
sudo apt install -y \
    php8.2 \
    php8.2-cli \
    php8.2-fpm \
    php8.2-common \
    php8.2-mysql \
    php8.2-xml \
    php8.2-mbstring \
    php8.2-curl \
    php8.2-zip \
    php8.2-gd \
    php8.2-intl \
    php8.2-opcache

# Vérifier l'installation
php -v
php -m | grep -E "pdo|mysql|mbstring|xml|curl|zip|intl"
```

### Configuration de php.ini

Éditez le fichier php.ini pour optimiser les performances :

```bash
# Trouver le fichier php.ini
php --ini

# Éditer php.ini (pour CLI)
sudo nano /etc/php/8.2/cli/php.ini
```

Paramètres recommandés :

```ini
memory_limit = 256M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
date.timezone = Europe/Paris
opcache.enable = 1
opcache.memory_consumption = 128
```

### macOS

```bash
# Installer Homebrew si ce n'est pas déjà fait
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Installer PHP 8.2
brew install php@8.2

# Ajouter PHP au PATH
echo 'export PATH="/usr/local/opt/php@8.2/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc

# Vérifier l'installation
php -v
```

### Windows

1. Téléchargez PHP 8.2 depuis [windows.php.net](https://windows.php.net/download/)
2. Choisissez la version Thread Safe si vous utilisez Apache, ou Non Thread Safe pour Nginx/IIS
3. Extrayez l'archive dans `C:\php`
4. Ajoutez `C:\php` au PATH système
5. Copiez `php.ini-development` vers `php.ini`
6. Activez les extensions nécessaires en décommentant dans php.ini :

```ini
extension=curl
extension=fileinfo
extension=gd
extension=intl
extension=mbstring
extension=mysqli
extension=pdo_mysql
extension=openssl
```

## Installation de Composer

### Linux/macOS

```bash
# Télécharger l'installateur
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

# Vérifier le hash SHA-384 (optionnel mais recommandé)
php -r "if (hash_file('sha384', 'composer-setup.php') === file_get_contents('https://composer.github.io/installer.sig')) { echo 'Installer verified' . PHP_EOL; } else { echo 'Installer corrupt' . PHP_EOL; unlink('composer-setup.php'); } echo PHP_EOL;"

# Installer Composer globalement
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Nettoyer
php -r "unlink('composer-setup.php');"

# Vérifier l'installation
composer --version
```

### Windows

1. Téléchargez [Composer-Setup.exe](https://getcomposer.org/Composer-Setup.exe)
2. Exécutez l'installateur
3. Suivez les instructions et sélectionnez votre installation PHP
4. Vérifiez dans PowerShell :

```powershell
composer --version
```

## Installation et configuration de MariaDB

### Ubuntu/Debian

#### Installation

```bash
# Mettre à jour le système
sudo apt update && sudo apt upgrade -y

# Installer MariaDB Server et Client
sudo apt install -y mariadb-server mariadb-client

# Vérifier la version installée
mysql --version
```

#### Démarrage et activation du service

```bash
# Démarrer MariaDB
sudo systemctl start mariadb

# Activer le démarrage automatique au boot
sudo systemctl enable mariadb

# Vérifier le statut
sudo systemctl status mariadb
```

#### Sécurisation de MariaDB

Exécutez le script de sécurisation :

```bash
sudo mysql_secure_installation
```

Répondez aux questions comme suit :

```
Enter current password for root (enter for none): [Appuyez sur Entrée]
Switch to unix_socket authentication [Y/n]: Y
Change the root password? [Y/n]: Y
New password: [Entrez un mot de passe fort]
Re-enter new password: [Répétez le mot de passe]
Remove anonymous users? [Y/n]: Y
Disallow root login remotely? [Y/n]: Y
Remove test database and access to it? [Y/n]: Y
Reload privilege tables now? [Y/n]: Y
```

#### Configuration pour GameSet

```bash
# Se connecter à MariaDB
sudo mysql -u root -p
```

Exécutez les commandes SQL suivantes :

```sql
-- Créer la base de données avec le bon encodage
CREATE DATABASE gameset 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

-- Créer l'utilisateur pour l'application
CREATE USER 'gameset_user'@'localhost' IDENTIFIED BY 'VotreMotDePasseSecurise123';

-- Accorder tous les privilèges sur la base gameset
GRANT ALL PRIVILEGES ON gameset.* TO 'gameset_user'@'localhost';

-- Appliquer les changements
FLUSH PRIVILEGES;

-- Vérifier la création
SHOW DATABASES;
SELECT User, Host FROM mysql.user WHERE User = 'gameset_user';

-- Quitter MariaDB
EXIT;
```

#### Tester la connexion

```bash
# Se connecter avec le nouvel utilisateur
mysql -u gameset_user -p gameset

# Une fois connecté, lister les tables (vide pour l'instant)
SHOW TABLES;

# Quitter
EXIT;
```

### macOS

#### Installation

```bash
# Installer MariaDB via Homebrew
brew install mariadb

# Démarrer MariaDB
brew services start mariadb

# Vérifier la version
mysql --version
```

#### Sécurisation

```bash
# Exécuter le script de sécurisation
mysql_secure_installation

# Suivre les mêmes étapes que pour Ubuntu
```

#### Configuration pour GameSet

Suivez les mêmes étapes SQL que pour Ubuntu/Debian.

### Windows

#### Installation

1. Téléchargez l'installateur MSI depuis [mariadb.org](https://mariadb.org/download/)
2. Choisissez la version stable la plus récente (11.4 ou supérieure)
3. Exécutez l'installateur MSI
4. Pendant l'installation :
   - Cochez "Use UTF8 as default server's character set"
   - Définissez un mot de passe root fort
   - Cochez "Enable access from remote machines" si nécessaire
   - Installez comme service Windows

#### Configuration pour GameSet

Ouvrez MySQL Client ou HeidiSQL et exécutez :

```sql
-- Créer la base de données
CREATE DATABASE gameset 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

-- Créer l'utilisateur
CREATE USER 'gameset_user'@'localhost' IDENTIFIED BY 'VotreMotDePasseSecurise123';

-- Accorder les privilèges
GRANT ALL PRIVILEGES ON gameset.* TO 'gameset_user'@'localhost';

-- Appliquer
FLUSH PRIVILEGES;
```

## Configuration du projet GameSet

### Clonage et installation des dépendances

```bash
# Cloner le dépôt
git clone https://github.com/lenyLBG/GameSet.git
cd GameSet

# Installer les dépendances avec Composer
composer install

# Si vous rencontrez des problèmes de mémoire :
php -d memory_limit=-1 /usr/local/bin/composer install
```

### Configuration de l'environnement

```bash
# Créer le fichier de configuration local
cp .env .env.local
```

Éditez `.env.local` avec vos paramètres :

```bash
# .env.local

###> symfony/framework-bundle ###
APP_ENV=dev
APP_SECRET=générez_une_clé_secrète_unique_ici
###< symfony/framework-bundle ###

###> doctrine/doctrine-bundle ###
# Pour MariaDB local
DATABASE_URL="mysql://gameset_user:VotreMotDePasseSecurise123@127.0.0.1:3306/gameset?serverVersion=11.4-MariaDB&charset=utf8mb4"
###< doctrine/doctrine-bundle ###
```

Pour générer une clé APP_SECRET :

```bash
php -r "echo bin2hex(random_bytes(16)) . PHP_EOL;"
```

### Création du schéma de base de données

```bash
# Vérifier la connexion à la base de données
php bin/console doctrine:database:create --if-not-exists

# Créer le schéma depuis les entités
php bin/console doctrine:schema:create

# Ou utiliser les migrations si elles existent
php bin/console doctrine:migrations:migrate --no-interaction
```

### Installation des assets

```bash
# Compiler les assets
php bin/console asset-map:compile

# Installer les assets publics
php bin/console assets:install public
```

### Vérification de l'installation

```bash
# Vérifier la configuration Symfony
php bin/console about

# Vérifier que tout fonctionne
php bin/console debug:router
```

## Configuration avancée de MariaDB

### Optimisation des performances

Éditez le fichier de configuration MariaDB :

```bash
# Ubuntu/Debian
sudo nano /etc/mysql/mariadb.conf.d/50-server.cnf

# macOS (Homebrew)
nano /usr/local/etc/my.cnf

# Windows
# Éditez C:\Program Files\MariaDB XX.X\data\my.ini
```

Ajoutez ou modifiez ces paramètres dans la section `[mysqld]` :

```ini
[mysqld]
# Paramètres de base
max_connections = 100
connect_timeout = 10
wait_timeout = 600
max_allowed_packet = 64M
thread_cache_size = 128
sort_buffer_size = 4M
bulk_insert_buffer_size = 16M
tmp_table_size = 64M
max_heap_table_size = 64M

# InnoDB (moteur de stockage par défaut)
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
innodb_log_buffer_size = 16M
innodb_flush_log_at_trx_commit = 2
innodb_file_per_table = 1

# Encodage par défaut
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# Logs (pour le développement)
general_log = 0
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 2
```

Redémarrez MariaDB :

```bash
# Ubuntu/Debian/macOS
sudo systemctl restart mariadb

# Windows
# Redémarrer le service via Services.msc
```

### Configuration de la sauvegarde automatique

Créez un script de sauvegarde :

```bash
# Créer le fichier de script
sudo nano /usr/local/bin/backup-gameset.sh
```

Contenu du script :

```bash
#!/bin/bash
# Script de sauvegarde de la base de données GameSet

# Configuration
BACKUP_DIR="/var/backups/mysql/gameset"
DB_NAME="gameset"
DB_USER="gameset_user"
# Note: Stocker le mot de passe dans un fichier séparé sécurisé est recommandé
# Par exemple: DB_PASS=$(cat /etc/mysql/backup_password)
DB_PASS="VotreMotDePasseSecurise123"
DATE=$(date +%Y%m%d_%H%M%S)

# Créer le répertoire de sauvegarde
mkdir -p "$BACKUP_DIR"

# Effectuer la sauvegarde
mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_DIR/gameset_$DATE.sql.gz"

# Vérifier que la sauvegarde a réussi
if [ $? -eq 0 ]; then
    echo "Sauvegarde effectuée avec succès : gameset_$DATE.sql.gz"
    
    # Supprimer les sauvegardes de plus de 30 jours
    find "$BACKUP_DIR" -type f -name "gameset_*.sql.gz" -mtime +30 -delete
    echo "Anciennes sauvegardes supprimées"
else
    echo "Erreur lors de la sauvegarde!" >&2
    exit 1
fi
```

Rendre le script exécutable :

```bash
sudo chmod +x /usr/local/bin/backup-gameset.sh
```

Configurer une tâche cron pour exécution quotidienne :

```bash
# Éditer crontab
sudo crontab -e

# Ajouter cette ligne pour une sauvegarde quotidienne à 2h du matin
0 2 * * * /usr/local/bin/backup-gameset.sh >> /var/log/gameset-backup.log 2>&1
```

### Restauration depuis une sauvegarde

```bash
# Décompresser et restaurer
gunzip < /var/backups/mysql/gameset/gameset_20250101_020000.sql.gz | mysql -u gameset_user -p gameset
```

## Déploiement avec Docker

### Utilisation de Docker Compose

Le projet inclut un fichier `compose.yaml` configuré pour MariaDB.

```bash
# Démarrer les services
docker compose up -d

# Vérifier les logs
docker compose logs -f database

# Vérifier l'état des services
docker compose ps
```

### Configuration avec Docker

Créez un fichier `.env.local` pour Docker :

```bash
# .env.local pour Docker

DATABASE_URL="mysql://gameset_user:your_password_here@database:3306/gameset?serverVersion=11.4-MariaDB&charset=utf8mb4"

# Variables pour Docker Compose
MARIADB_VERSION=11.4
MYSQL_DATABASE=gameset
MYSQL_USER=gameset_user
MYSQL_PASSWORD=VotreMotDePasseSecurise123
MYSQL_ROOT_PASSWORD=MotDePasseRootSecurise456
```

### Exécuter les migrations dans Docker

```bash
# Accéder au conteneur PHP (si vous en avez un)
docker compose exec php php bin/console doctrine:migrations:migrate

# Ou depuis l'hôte si les ports sont exposés
php bin/console doctrine:migrations:migrate
```

## Troubleshooting

### Problème : "Access denied for user"

**Solution :**

```bash
# Vérifier l'utilisateur et l'hôte
sudo mysql -u root -p
SELECT User, Host FROM mysql.user WHERE User = 'gameset_user';

# Si l'hôte n'est pas correct, recréer l'utilisateur
DROP USER 'gameset_user'@'localhost';
CREATE USER 'gameset_user'@'localhost' IDENTIFIED BY 'VotreMotDePasseSecurise123';
GRANT ALL PRIVILEGES ON gameset.* TO 'gameset_user'@'localhost';
FLUSH PRIVILEGES;
```

### Problème : "Can't connect to MySQL server"

**Solution :**

```bash
# Vérifier que MariaDB est en cours d'exécution
sudo systemctl status mariadb

# Démarrer si nécessaire
sudo systemctl start mariadb

# Vérifier le port d'écoute
sudo netstat -tlnp | grep 3306

# Vérifier la configuration de bind-address
sudo nano /etc/mysql/mariadb.conf.d/50-server.cnf
# Chercher : bind-address = 127.0.0.1
```

### Problème : "SQLSTATE[HY000] [2002] Connection refused"

**Solution :**

```bash
# Vérifier le DATABASE_URL dans .env.local
cat .env.local | grep DATABASE_URL

# Tester la connexion manuellement
mysql -h 127.0.0.1 -u gameset_user -p gameset

# Si ça fonctionne en ligne de commande mais pas avec Symfony :
php bin/console doctrine:query:sql "SELECT 1"
```

### Problème : Erreur d'encodage UTF-8

**Solution :**

```bash
# Vérifier l'encodage de la base de données
mysql -u root -p -e "SELECT default_character_set_name, default_collation_name FROM information_schema.schemata WHERE schema_name = 'gameset';"

# Si incorrect, recréer avec le bon encodage
mysql -u root -p
DROP DATABASE gameset;
CREATE DATABASE gameset CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Problème : Tables non créées après migration

**Solution :**

```bash
# Vérifier les migrations disponibles
php bin/console doctrine:migrations:status

# Forcer la création du schéma
php bin/console doctrine:schema:update --force

# Ou vider le cache
php bin/console cache:clear
php bin/console doctrine:migrations:migrate
```

### Problème : Permissions insuffisantes

**Solution :**

```bash
# Accorder plus de privilèges si nécessaire
mysql -u root -p
GRANT ALL PRIVILEGES ON gameset.* TO 'gameset_user'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
```

## Vérification finale

Checklist complète avant de démarrer l'application :

```bash
# 1. Vérifier PHP
php -v

# 2. Vérifier Composer
composer --version

# 3. Vérifier MariaDB
mysql --version
sudo systemctl status mariadb

# 4. Vérifier la connexion à la base
mysql -u gameset_user -p gameset -e "SELECT 'Connection OK' AS status;"

# 5. Vérifier Symfony
cd /chemin/vers/GameSet
php bin/console about

# 6. Vérifier les migrations
php bin/console doctrine:migrations:status

# 7. Démarrer le serveur
symfony server:start
# ou
php -S localhost:8000 -t public/
```

Si toutes les étapes réussissent, votre installation est complète !

## Ressources additionnelles

- [Documentation MariaDB](https://mariadb.com/kb/en/documentation/)
- [Documentation Symfony](https://symfony.com/doc/current/index.html)
- [Documentation Doctrine](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/)
- [Best Practices Symfony](https://symfony.com/doc/current/best_practices.html)
- [Guide de sécurité MariaDB](https://mariadb.com/kb/en/securing-mariadb/)

---

**Dernière mise à jour :** Décembre 2025  
**Auteur :** @lenyLBG
