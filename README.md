# Rapha IAM Suite

Rapha IAM Suite est un module Laravel 11 dédié à la gestion des identités et des accès (IAM). Il fournit une base réutilisable pour :

- L'authentification API (Sanctum)
- La gestion des utilisateurs, rôles et permissions (RBAC)
- La vérification e-mail et la réinitialisation de mot de passe
- L'audit des actions sensibles

Le module est conçu pour être empaqueté comme un package Composer (`Aim/Iam`) ou intégré directement dans vos projets Laravel.

## Structure principale

```
packages/Iam/
├── config/iam.php           # Configuration publiée
├── database/migrations/     # Migrations (roles, permissions, audit, etc.)
├── routes/iam.php           # Routes API exposées
├── src/
│   ├── Http/Controllers/    # Contrôleurs Auth, Users, Roles, Permissions, Audit
│   ├── Http/Middleware/     # Middlewares role & permission
│   ├── Models/              # Modèles Role, Permission, AuditLog
│   ├── Services/AuditLogger # Service de journalisation centralisé
│   └── Traits/              # Helpers RBAC pour le modèle User
└── IamServiceProvider.php   # Enregistrement des ressources dans Laravel
```

## Installation rapide (dans un projet Laravel)

1. Ajoutez le namespace du package dans `composer.json` (déjà configuré dans ce dépôt) :
   ```json
   "autoload": {
       "psr-4": {
           "Aim\\Iam\\": "packages/Iam/src/"
       }
   }
   ```
2. Lancez `composer dump-autoload` si nécessaire.
3. Assurez-vous que le `IamServiceProvider` est enregistré (dans `bootstrap/app.php`).
4. Exécutez les migrations :
   ```bash
   php artisan migrate
   ```
5. (Optionnel) Publiez la configuration :
   ```bash
   php artisan vendor:publish --provider="Aim\\Iam\\IamServiceProvider" --tag=iam-config
   ```

## Frontend Angular — Rapha IAM Admin

Un client Angular (standalone components, Angular Material) est disponible dans `frontend/rapha-iam-admin` pour piloter l'API IAM.

### Commandes de démarrage

```bash
cd frontend/rapha-iam-admin
npm install
npm run start
```

L'application écoute sur `http://localhost:4200` et consomme par défaut l'API Laravel exposée sur `http://localhost:8000/api`. Ajustez la variable `apiBaseUrl` dans `src/environments/environment.ts` si besoin.

La documentation spécifique (structure, architecture, guards, etc.) est détaillée dans `frontend/rapha-iam-admin/README.md`.

## Documentation

- [Guide de tests](docs/testing.md)

## Contributions

Les contributions sont les bienvenues pour enrichir la roadmap (sécurité avancée, multi-tenant, intégrations externes). Ouvrez une issue ou une Pull Request avec une description précise de la fonctionnalité envisagée.

## Licence

Ce projet est distribué sous licence MIT.
