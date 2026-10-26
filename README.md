## 1. Prérequis sur l'autre PC

Installer les outils suivants :

- PHP 8.2 ou plus, avec les extensions :
  - "mbstring"
  - "xml"
  - "curl"
  - "zip"
  - "gd"
  - "intl"
  - "fileinfo"
  - "pdo_mysql"
- Composer
- MySQL (Laragon ou XAMPP permettent d'installer les composants nécessaires sous Windows).
- Node.js, uniquement si le projet utilise un thème personnalisé nécessitant Vite ("viteTheme()").

Vérifier les installations avec les commandes suivantes :

php -v
composer -V
mysql --version

## 2. Installer le projet

Ouvrir un terminal dans le dossier du projet :

cd chemin/vers/le/projet
composer install

Créer le fichier ".env" à partir du fichier d'exemple :

Windows :

copy .env.example .env

Linux / macOS :

cp .env.example .env

Générer la clé de chiffrement Laravel :

php artisan key:generate

## 3. Configurer le fichier ".env"

Configurer les paramètres suivants :

APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=church_land
DB_USERNAME=root
DB_PASSWORD=

«Important : créer d'abord une base de données vide nommée "church_land" dans phpMyAdmin ou en ligne de commande.»

## 4. Créer les tables et le lien des fichiers

Exécuter les migrations :

```bash
php artisan migrate
```
Créer le lien symbolique pour accéder aux fichiers publics :
```bash
php artisan storage:link
```

Sans cette commande, les photos et les documents stockés dans le répertoire public risquent de ne pas s'afficher.

## 5. Installer les assets

Cette étape est nécessaire uniquement si le projet utilise un thème Filament personnalisé nécessitant une compilation avec Vite.

```
npm install
npm run build
```

## 6. Créer le compte administrateur

Créer un utilisateur administrateur Filament :

```
php artisan make:filament-user
```

Générer les rôles et les permissions avec Filament Shield :

```
 artisan shield:generate --all
 ```

Attribuer le rôle de super-administrateur à l'utilisateur ayant l'identifiant "1" :

```
php artisan shield:super-admin --user=1
```

«Attention : sans attribution d'un rôle approprié, le compte peut ne pas disposer des autorisations nécessaires pour accéder aux menus et aux fonctionnalités d'administration.»

Vérifier que l'identifiant "1" correspond bien au compte administrateur créé.

## 7. Lancer l'application

Nettoyer les caches Laravel :

```
php artisan optimize:clear
```

Démarrer le serveur de développement :

```
php artisan serve
```

Ouvrir ensuite l'application dans le navigateur :

"http://127.0.0.1:8000" (http://127.0.0.1:8000)

L'application redirige vers "/admin".

## 8. Recréer les rôles

Si la base de données est vide, les rôles n'existent plus.

Accéder à l'administration, ouvrir la section Rôles et recréer les rôles suivants en respectant exactement leur nom, car le code de l'application les utilise tels quels :

- "district_manager"
- "federation_admin"
- "data_entry"
- "consultant"
- "report_manager"

Pour chaque rôle :

1. Attribuer les permissions appropriées.
2. Créer un compte de test associé au rôle.
3. Pour les rôles "district_manager" et "federation_admin", renseigner la fédération ou le district correspondant.

Vérifier ensuite que chaque utilisateur accède uniquement aux fonctionnalités autorisées.

---

Récupérer les données de l'ancien PC (optionnel)

Si tu souhaites transférer les données existantes, effectuer une sauvegarde de la base de données sur l'ancien PC.

Sur l'ancien PC

Exporter la base de données :

mysqldump -u root church_land > sauvegarde.sql

Copier le fichier "sauvegarde.sql" vers le nouveau PC.

Sur le nouveau PC

Créer la base de données "church_land" si elle n'existe pas, puis importer les données :

mysql -u root church_land < sauvegarde.sql

Copier également le dossier suivant depuis l'ancien PC :

storage/app/public/

Ce dossier contient les photos et les documents stockés par l'application.

«Important : si tu importes une sauvegarde complète de l'ancienne base de données, les rôles et les permissions peuvent déjà être présents. Dans ce cas, il n'est généralement pas nécessaire de les recréer à l'étape 8. Vérifie également que les migrations sont à jour.»

---

Erreurs fréquentes

Erreur| Solution
"No application encryption key"| Exécuter "php artisan key:generate".
"Class ... not found"| Exécuter "composer dump-autoload".
Table "permissions" introuvable| Exécuter "php artisan migrate".
Menus vides| Vérifier les rôles et les permissions, puis exécuter "php artisan shield:super-admin --user=1" si nécessaire.
Photos invisibles| Exécuter "php artisan storage:link" et vérifier les fichiers dans "storage/app/public".
Interface sans style| Exécuter "npm install" puis "npm run build" si le projet utilise Vite.
Comportement anormal après un "git pull"| Exécuter "php artisan optimize:clear", puis installer les éventuelles nouvelles dépendances.