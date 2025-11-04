# Rapha IAM Admin (Angular)

Rapha IAM Admin est une application Angular 17+ basée sur des composants standalone et Angular Material. Elle consomme l'API JSON fournie par le module Laravel « Rapha IAM Suite ».

## 1. Création du projet (référence CLI)

```bash
# Génération du squelette Angular
ng new rapha-iam-admin --standalone --routing false --style=scss

cd rapha-iam-admin

# Ajout d'Angular Material et configuration du thème
ng add @angular/material
```

Les fichiers contenus dans ce répertoire reflètent le résultat attendu après personnalisation (structure `core/`, `features/`, `shared/`, services IAM, guards, etc.).

## 2. Installation & lancement

```bash
npm install
npm run start
```

L'application est exposée par défaut sur [http://localhost:4200](http://localhost:4200). Pensez à configurer l'URL de l'API dans `src/environments/environment.ts` si votre backend Laravel n'est pas accessible sur `http://localhost:8000/api`.

## 3. Structure principale

```
frontend/rapha-iam-admin/
├── src/
│   ├── app/
│   │   ├── app.component.ts          # Shell + navigation principale
│   │   ├── app.config.ts             # Bootstrap providers (router, HTTP, interceptors)
│   │   ├── app.routes.ts             # Définition des routes & guards
│   │   ├── core/
│   │   │   ├── services/             # AuthService, UserService, RoleService, PermissionService
│   │   │   ├── guards/               # AuthGuard, RoleGuard
│   │   │   ├── interceptors/         # AuthInterceptor
│   │   │   └── models/               # Interfaces TypeScript (User, Role, Permission, etc.)
│   │   ├── features/
│   │   │   ├── auth/login            # Page de connexion
│   │   │   ├── dashboard             # Dashboard de base (statistiques)
│   │   │   ├── users                 # Liste & formulaire utilisateur (MatDialog)
│   │   │   ├── roles                 # Liste & formulaire rôle + permissions
│   │   │   └── permissions          # Liste & formulaire permission
│   │   └── shared/                   # Composants mutualisables (placeholders pour la suite)
│   └── environments/                 # Configuration API
└── package.json                      # Scripts Angular (start/build/test)
```

## 4. Configuration API

L'URL de base de l'API est centralisée dans `src/environments/environment.ts` :

```ts
export const environment = {
  production: false,
  apiBaseUrl: 'http://localhost:8000/api'
};
```

Adaptez cette valeur à votre environnement (Docker, staging, etc.). Toutes les routes IAM construites par les services consomment `apiBaseUrl`.

## 5. Tests & lint

Les scripts standards Angular sont disponibles :

```bash
npm run test     # Tests unitaires (Karma/Jasmine)
npm run lint     # Lint via ESLint (si configuré)
npm run build    # Build de production
```

## 6. Étapes suivantes

- Ajouter la gestion des sessions actives et des logs d'audit côté UI.
- Mettre en place un système de notifications centralisé.
- Intégrer un lazy loading plus fin par feature module si l'application grossit.
- Connecter un store (NGRX/Signals Store) si besoin d'états complexes.
