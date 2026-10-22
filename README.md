1. Prérequis sur l’autre PC
PHP 8.2 ou plus, avec les extensions mbstring, xml, curl, zip, gd, intl, fileinfo et pdo_mysql
Composer
MySQL (Laragon ou XAMPP font tout d’un coup sous Windows)
Node.js seulement si tu as un thème personnalisé (->viteTheme())

Vérifie avec php -v, composer -V et mysql --version.

2. Installer le projet
bash
cd chemin/vers/le/projet
composer install
copy .env.example .env        # Windows (cp sur Linux/Mac)
php artisan key:generate
3. Configurer .env
env
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=church_land
DB_USERNAME=root
DB_PASSWORD=

Crée d’abord une base vide du même nom (church_land) dans phpMyAdmin ou en ligne de commande.

4. Créer les tables et le lien des fichiers
bash
php artisan migrate
php artisan storage:link

Sans storage:link, les photos et documents ne s’affichent pas.

5. Assets (seulement si tu as un thème Filament personnalisé)
bash
npm install
npm run build
6. Créer le compte administrateur
bash
php artisan make:filament-user
php artisan shield:generate --all
php artisan shield:super-admin --user=1

Sans la dernière commande, le compte n’a aucun rôle et les menus restent vides.

7. Lancer
bash
php artisan optimize:clear
php artisan serve

Ouvre http://127.0.0.1:8000, qui redirige vers /admin.

8. Recréer les rôles

La base est vide, donc les rôles n’existent plus. Va dans Rôles et crée-les avec exactement ces noms, car le code les utilise tels quels :

district_manager
federation_admin
data_entry
consultant
report_manager

Coche leurs permissions, puis crée un compte de test par rôle. Pour district_manager et federation_admin, renseigne la fédération ou le district.

Récupérer tes données de l’autre PC (optionnel)

Sur l’ancien PC :

bash
mysqldump -u root church_land > sauvegarde.sql

Sur le nouveau PC :

bash
mysql -u root church_land < sauvegarde.sql

Copie aussi le dossier storage/app/public pour récupérer les photos et documents. Dans ce cas, tu sautes l’étape 8, puisque les rôles sont déjà dans la base.

Erreurs fréquentes
Erreur	Solution
No application encryption key	php artisan key:generate
Class ... not found	composer dump-autoload
Table permissions introuvable	php artisan migrate
Menus vides	php artisan shield:super-admin --user=1
Photos invisibles	php artisan storage:link
Interface sans style	npm install && npm run build
Comportement bizarre après un pull	php artisan optimize:clear