# GameSet

GameSet est une application web développée avec Symfony permettant de gérer et d'organiser des tournois et parties de jeux. Elle propose la création de tournois, la gestion des joueurs, et un système de suivi des parties.

## Table des matières

- [À propos](#à-propos)
- [Fonctionnalités](#fonctionnalités)
- [Prérequis](#prérequis)
- [Installation](#installation)
  - [Installation de PHP](#installation-de-php)
  - [Installation de Composer](#installation-de-composer)
  - [Installation et configuration de MariaDB](#installation-et-configuration-de-mariadb)
  - [Installation du projet](#installation-du-projet)
- [Utilisation](#utilisation)
- [Structure du projet](#structure-du-projet)
- [Tests](#tests)
- [Contribution](#contribution)
- [Licence](#licence)
- [Contact](#contact)

## À propos

GameSet est une application web construite avec Symfony 7.3 qui permet de gérer des tournois de jeux. L'application offre une interface intuitive pour créer des tournois, gérer les participants et suivre les résultats des parties.

## Fonctionnalités

- Création et gestion de tournois
- Gestion des joueurs et participants
- Interface utilisateur réactive avec Symfony UX Turbo
- Système de routing avancé
- Gestion de la sécurité et authentification
- Interface d'administration

## Prérequis

Avant de commencer l'installation, assurez-vous d'avoir les éléments suivants :

- **PHP >= 8.2** avec les extensions suivantes :
  - ext-ctype
  - ext-iconv
  - ext-pdo
  - ext-pdo_mysql (pour MariaDB/MySQL)
- **Composer** (gestionnaire de dépendances PHP)
- **MariaDB >= 10.6** ou **MySQL >= 8.0**
- **Git**
- Un serveur web (Apache, Nginx) ou le serveur web intégré de Symfony

## Installation

> **📖 Guide d'installation détaillé disponible :** Pour des instructions complètes étape par étape incluant tous les détails de configuration de MariaDB, consultez le fichier [INSTALLATION.md](INSTALLATION.md).

### Installation rapide

Voici un résumé des étapes d'installation. Pour plus de détails, référez-vous au [guide d'installation complet](INSTALLATION.md).

### Installation de PHP

#### Sur Ubuntu/Debian :

```bash
sudo apt update
sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-intl
```

#### Sur macOS :

```bash
brew install php@8.2
```

#### Sur Windows :

Téléchargez PHP depuis [windows.php.net](https://windows.php.net/download/) et suivez les instructions d'installation.

Vérifiez l'installation :

```bash
php -v
```

### Installation de Composer

#### Sur Linux/macOS :

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
```

#### Sur Windows :

Téléchargez et exécutez [Composer-Setup.exe](https://getcomposer.org/Composer-Setup.exe)

Vérifiez l'installation :

```bash
composer --version
```

### Installation et configuration de MariaDB

#### Sur Ubuntu/Debian :

```bash
# Installer MariaDB
sudo apt update
sudo apt install -y mariadb-server mariadb-client

# Démarrer le service MariaDB
sudo systemctl start mariadb
sudo systemctl enable mariadb

# Sécuriser l'installation MariaDB
sudo mysql_secure_installation
```

#### Sur macOS :

```bash
# Installer MariaDB via Homebrew
brew install mariadb

# Démarrer le service
brew services start mariadb

# Sécuriser l'installation
mysql_secure_installation
```

#### Sur Windows :

1. Téléchargez MariaDB depuis [mariadb.org](https://mariadb.org/download/)
2. Exécutez l'installateur MSI
3. Suivez l'assistant d'installation
4. Définissez le mot de passe root lors de l'installation

#### Configuration de la base de données

Connectez-vous à MariaDB :

```bash
sudo mysql -u root -p
```

Créez la base de données et l'utilisateur pour GameSet :

```sql
-- Créer la base de données
CREATE DATABASE gameset CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Créer un utilisateur dédié
CREATE USER 'gameset_user'@'localhost' IDENTIFIED BY 'votre_mot_de_passe_securise';

-- Accorder tous les privilèges sur la base de données
GRANT ALL PRIVILEGES ON gameset.* TO 'gameset_user'@'localhost';

-- Recharger les privilèges
FLUSH PRIVILEGES;

-- Quitter MariaDB
EXIT;
```

**Notes de sécurité :**
- Remplacez `votre_mot_de_passe_securise` par un mot de passe fort
- En production, limitez les privilèges aux seules permissions nécessaires
- Ne partagez jamais vos identifiants de base de données

Vérifiez la connexion :

```bash
mysql -u gameset_user -p gameset
```

### Installation du projet

1. **Clonez le dépôt :**

```bash
git clone https://github.com/lenyLBG/GameSet.git
cd GameSet
```

2. **Installez les dépendances PHP :**

```bash
composer install
```

3. **Configurez les variables d'environnement :**

Créez un fichier `.env.local` pour vos configurations locales :

```bash
cp .env .env.local
```

Éditez le fichier `.env.local` et configurez votre connexion à la base de données :

```bash
# .env.local
DATABASE_URL="mysql://gameset_user:votre_mot_de_passe_securise@127.0.0.1:3306/gameset?serverVersion=11.4-MariaDB&charset=utf8mb4"
```

Remplacez :
- `gameset_user` par votre nom d'utilisateur MariaDB
- `votre_mot_de_passe_securise` par votre mot de passe
- `11.4-MariaDB` par votre version de MariaDB (vérifiez avec `mysql --version`)

Vous pouvez également configurer un `APP_SECRET` unique :

```bash
# Générer une clé secrète
php -r "echo bin2hex(random_bytes(16)) . PHP_EOL;"
```

Ajoutez la clé générée dans `.env.local` :

```bash
APP_SECRET=votre_cle_secrete_generee
```

4. **Créez les tables de la base de données :**

```bash
# Créer les tables selon les entités Doctrine
php bin/console doctrine:migrations:migrate

# Ou si aucune migration n'existe encore, créer le schéma
php bin/console doctrine:schema:create
```

5. **Chargez les données de test (optionnel) :**

Si des fixtures sont disponibles :

```bash
php bin/console doctrine:fixtures:load
```

6. **Installez les assets :**

```bash
php bin/console asset-map:compile
```

7. **Configurez les permissions :**

```bash
# Assurez-vous que les répertoires var/ sont accessibles en écriture par le serveur web
# Utilisez le propriétaire du serveur web (www-data sur Ubuntu/Debian, _www sur macOS)
sudo chown -R www-data:www-data var/
sudo chmod -R 775 var/

# Pour le développement local, vous pouvez utiliser votre utilisateur
sudo chown -R $USER:www-data var/
sudo chmod -R 775 var/
```

## Utilisation

### Démarrage en mode développement

Utilisez le serveur web intégré de Symfony :

```bash
symfony server:start
```

Ou si vous n'avez pas le CLI Symfony installé :

```bash
php -S localhost:8000 -t public/
```

L'application sera accessible à l'adresse : `http://localhost:8000`

### Alternative avec Docker

Le projet inclut un fichier `compose.yaml` configuré pour utiliser MariaDB.

```bash
# Démarrer les services Docker
docker compose up -d

# Vérifier les logs
docker compose logs -f database

# Arrêter les services
docker compose down
```

### Configuration Docker avec .env.local

Pour Docker, créez un fichier `.env.local` avec les variables d'environnement :

```bash
# .env.local pour Docker
DATABASE_URL="mysql://gameset_user:votremotdepasse@database:3306/gameset?serverVersion=11.4-MariaDB&charset=utf8mb4"

# Variables pour Docker Compose (optionnel, pour personnaliser)
MARIADB_VERSION=11.4
MYSQL_DATABASE=gameset
MYSQL_USER=gameset_user
MYSQL_PASSWORD=votremotdepasse_securise
MYSQL_ROOT_PASSWORD=rootpassword_securise
```

### Commandes utiles

```bash
# Vider le cache
php bin/console cache:clear

# Créer une nouvelle migration
php bin/console make:migration

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Créer un nouveau contrôleur
php bin/console make:controller

# Créer une nouvelle entité
php bin/console make:entity

# Lister toutes les routes
php bin/console debug:router

# Vérifier la configuration
php bin/console about
```

### Mode production

Pour déployer en production :

1. Configurez les variables d'environnement :

```bash
APP_ENV=prod
APP_DEBUG=0
```

2. Optimisez l'application :

```bash
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console asset-map:compile
```

3. Configurez votre serveur web (Apache/Nginx) pour pointer vers le répertoire `public/`

## Tests

### Lancer les tests

```bash
# Exécuter tous les tests
php bin/phpunit

# Exécuter un fichier de test spécifique
php bin/phpunit tests/Controller/HomeControllerTest.php

# Exécuter les tests avec couverture de code
php bin/phpunit --coverage-html var/coverage
```

### Configuration des tests

Les tests utilisent une base de données séparée. Assurez-vous de configurer votre `.env.test.local` :

```bash
# .env.test.local
DATABASE_URL="mysql://gameset_user:votremotdepasse@127.0.0.1:3306/gameset_test?serverVersion=11.4-MariaDB&charset=utf8mb4"
```

Créez la base de données de test :

```bash
php bin/console --env=test doctrine:database:create
php bin/console --env=test doctrine:schema:create
```

## Structure du projet

```
GameSet/
├── assets/              # Assets JavaScript et CSS
├── bin/                 # Scripts exécutables (console Symfony)
├── config/              # Configuration de l'application
│   ├── packages/        # Configuration des bundles
│   └── routes/          # Configuration des routes
├── migrations/          # Migrations de base de données Doctrine
├── public/              # Point d'entrée web et assets publics
│   └── index.php        # Contrôleur frontal
├── src/
│   ├── Controller/      # Contrôleurs de l'application
│   ├── Entity/          # Entités Doctrine (modèles)
│   ├── Repository/      # Repositories Doctrine
│   └── Kernel.php       # Noyau de l'application
├── templates/           # Templates Twig
├── tests/               # Tests PHPUnit
├── translations/        # Fichiers de traduction
├── var/                 # Fichiers générés (cache, logs)
├── vendor/              # Dépendances Composer
├── .env                 # Variables d'environnement (défaut)
├── .env.local           # Variables d'environnement locales (non versionné)
├── composer.json        # Dépendances PHP
├── compose.yaml         # Configuration Docker Compose
└── symfony.lock         # Fichier de verrouillage Symfony Flex
```

## Contribution

Merci de votre intérêt pour contribuer à GameSet !

### Comment contribuer

1. **Forkez le dépôt**
2. **Créez une branche pour votre fonctionnalité :**
   ```bash
   git checkout -b feat/ma-fonctionnalite
   ```
3. **Commitez vos changements :**
   ```bash
   git commit -m "feat: description de la fonctionnalité"
   ```
4. **Poussez vers votre fork :**
   ```bash
   git push origin feat/ma-fonctionnalite
   ```
5. **Ouvrez une Pull Request**

### Conventions de code

- Suivez les standards PSR-12 pour le code PHP
- Utilisez des noms de variables et de fonctions explicites
- Commentez le code complexe
- Ajoutez des tests pour les nouvelles fonctionnalités
- Mettez à jour la documentation si nécessaire

### Standards de commit

Utilisez les préfixes suivants pour vos commits :
- `feat:` pour une nouvelle fonctionnalité
- `fix:` pour une correction de bug
- `docs:` pour la documentation
- `style:` pour le formatage du code
- `refactor:` pour la refactorisation
- `test:` pour l'ajout de tests
- `chore:` pour les tâches de maintenance

## Dépannage

### Erreur de connexion à la base de données

Si vous rencontrez une erreur de connexion à MariaDB :

```bash
# Vérifiez que MariaDB est en cours d'exécution
sudo systemctl status mariadb

# Vérifiez vos identifiants
mysql -u gameset_user -p gameset

# Vérifiez la configuration dans .env.local
cat .env.local | grep DATABASE_URL
```

### Erreur de permissions

Si vous rencontrez des erreurs de permissions :

```bash
# Assurez-vous que var/ est accessible en écriture par le serveur web
# Option 1: Utiliser le groupe du serveur web
sudo chown -R $USER:www-data var/
sudo chmod -R 775 var/

# Option 2: En développement local, utiliser votre utilisateur
sudo chown -R $USER:$USER var/
sudo chmod -R 755 var/
```

### Erreur "Class not found"

Si vous rencontrez des erreurs de classes non trouvées :

```bash
# Régénérez l'autoloader de Composer
composer dump-autoload

# Videz le cache Symfony
php bin/console cache:clear
```

### Port déjà utilisé

Si le port 8000 est déjà utilisé :

```bash
# Utilisez un autre port
php -S localhost:8080 -t public/

# Ou trouvez quel processus utilise le port
lsof -i :8000
```

## Ressources supplémentaires

- [Documentation Symfony](https://symfony.com/doc/current/index.html)
- [Documentation Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/)
- [Documentation MariaDB](https://mariadb.org/documentation/)
- [Documentation Twig](https://twig.symfony.com/doc/)

## Licence

Ce projet est sous licence propriétaire. Tous droits réservés.

## Contact

Pour toute question, suggestion ou problème, contactez :

- **Auteur :** @lenyLBG
- **GitHub :** [https://github.com/lenyLBG/GameSet](https://github.com/lenyLBG/GameSet)

---

**Note :** Ce README a été complété avec des instructions détaillées d'installation incluant la configuration complète de MariaDB. Assurez-vous d'adapter les informations de connexion à votre environnement spécifique.
