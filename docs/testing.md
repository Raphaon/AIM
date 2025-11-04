# Rapha IAM Suite — Guide de tests

Ce document décrit les prérequis et les commandes à exécuter pour vérifier le bon fonctionnement du module IAM.

## 1. Pré-requis

1. PHP 8.2 ou supérieur avec les extensions recommandées par Laravel (`mbstring`, `openssl`, `pdo_mysql` ou `pdo_sqlite`, etc.).
2. Composer 2.6+.
3. Node.js 18+ et npm (uniquement si vous souhaitez recompiler les assets de démo).
4. Une base de données accessible (MySQL, PostgreSQL ou SQLite).

> 💡 Pour des tests rapides, vous pouvez utiliser SQLite en mémoire (`DB_CONNECTION=sqlite` et `DB_DATABASE=:memory:`) ou un fichier `database/database.sqlite`.

## 2. Installation des dépendances

```bash
composer install
npm install # optionnel
```

## 3. Configuration de l'environnement

1. Copiez le fichier d'exemple d'environnement :
   ```bash
   cp .env.example .env
   ```
2. Générez la clé d'application :
   ```bash
   php artisan key:generate
   ```
3. Configurez la connexion base de données dans le fichier `.env`.
4. Activez les tables nécessaires au module IAM :
   ```bash
   php artisan migrate
   ```

## 4. Publication éventuelle des ressources du package

Le package IAM enregistre automatiquement ses migrations. Vous pouvez néanmoins publier la configuration si vous souhaitez la personnaliser :

```bash
php artisan vendor:publish --provider="Aim\\Iam\\IamServiceProvider" --tag=iam-config
```

## 5. Jeux de données pour les tests

Pour tester rapidement les contrôleurs, vous pouvez exécuter les seeders fournis ou créer vos propres utilisateurs/roles :

```bash
php artisan db:seed --class=Database\\Seeders\\DatabaseSeeder
```

> Assurez-vous d'ajouter des rôles et permissions adaptés à vos scénarios de test.

## 6. Lancer la suite de tests automatisés

```bash
php artisan test
```

- Utilisez `php artisan test --testsuite=Feature` pour cibler uniquement les tests d'API IAM.
- Ajoutez `--coverage` si Xdebug est installé et que vous souhaitez un rapport de couverture.

## 7. Tests manuels de l'API IAM

Les routes IAM sont exposées sous le préfixe défini dans `config/iam.php` (`api/iam` par défaut). Voici quelques commandes `HTTPie`/`curl` pour valider rapidement les flux :

### 7.1 Authentification & jeton Sanctum

```bash
http POST http://localhost:8000/api/iam/auth/login email="admin@example.com" password="password"
```

### 7.2 Profil utilisateur

```bash
http GET http://localhost:8000/api/iam/auth/profile "Authorization:Bearer <token>"
```

### 7.3 Vérification d'adresse email

```bash
http POST http://localhost:8000/api/iam/auth/email/verification-notification "Authorization:Bearer <token>"
```

### 7.4 CRUD Utilisateurs

```bash
http POST http://localhost:8000/api/iam/users name="John Doe" email="john@example.com" password="Password!23" roles:='["admin"]' "Authorization:Bearer <token>"
```

Adaptez les URL et entêtes selon votre environnement.

## 8. Nettoyage de la base de données

Après vos tests, vous pouvez réinitialiser la base :

```bash
php artisan migrate:fresh --seed
```

Cela supprime et recrée les tables (y compris celles du module IAM), puis relance les seeders configurés.

---

En cas de problème durant l'exécution des tests, pensez à vérifier :

- Les migrations ont bien été exécutées.
- Les utilisateurs disposent des rôles/permissions requis pour accéder aux endpoints.
- Le fichier `.env` est correctement configuré (guards, broker de mot de passe, etc.).

