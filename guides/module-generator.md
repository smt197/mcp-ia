# Guide: Générateur de Modules Laravel (MCP Tool)

Ce guide explique comment utiliser l'outil MCP `generate-module` pour créer automatiquement des modules Laravel complets.

## Vue d'ensemble

L'outil `generate-module` crée automatiquement tous les fichiers nécessaires pour un module CRUD complet:

- **Model** avec support Sluggable et MediaLibrary
- **Migration** avec tous les champs définis
- **Controller** (Orion REST API)
- **Resource** et **Collection**
- **Request** avec validation
- **Policy** pour les autorisations
- **Factory** pour les tests
- **Seeder** avec données de test
- **Routes** API automatiquement ajoutées

## Utilisation de base

### Exemple simple

```json
{
  "module_name": "products",
  "fields": [
    {"name": "name", "type": "string", "required": true},
    {"name": "description", "type": "textarea", "required": false},
    {"name": "price", "type": "number", "required": true},
    {"name": "in_stock", "type": "boolean", "required": true}
  ]
}
```

Cet exemple génère un module `products` avec 4 champs.

### Exemple avec slug

```json
{
  "module_name": "blog_posts",
  "identifier_field": "slug",
  "fields": [
    {"name": "title", "type": "string", "required": true},
    {"name": "content", "type": "quill-editor", "required": true},
    {"name": "published_at", "type": "Date", "required": false}
  ]
}
```

Utilise `slug` comme identifiant au lieu de `id` (nécessite Spatie Sluggable).

### Exemple avec fichiers

```json
{
  "module_name": "documents",
  "fields": [
    {"name": "title", "type": "string", "required": true},
    {"name": "file", "type": "File", "required": true},
    {"name": "category", "type": "string", "required": false}
  ]
}
```

Le type `File` active Spatie Media Library pour la gestion des uploads.

## Types de champs supportés

| Type | Description | Exemple d'utilisation |
|------|-------------|----------------------|
| `string` | Texte court (max 255 caractères) | Nom, titre, email |
| `number` | Nombre entier | Prix, quantité, âge |
| `boolean` | Vrai/Faux | En stock, publié, actif |
| `Date` | Date et heure | Date de création, publication |
| `File` | Fichier uploadé | Image, PDF, document |
| `textarea` | Texte long | Description, commentaire |
| `quill-editor` | Éditeur riche (HTML) | Contenu d'article, page |
| `email` | Adresse email | Email de contact |
| `password` | Mot de passe hashé | Mot de passe utilisateur |

## Paramètres

### `module_name` (requis)

Nom du module au **pluriel** (ex: `products`, `blog_posts`, `users`).

- Utilisé pour le nom de la table
- Utilisé pour les routes API (`/api/products`)
- Converti automatiquement en StudlyCase pour les classes

### `fields` (requis)

Tableau de définitions de champs. Chaque champ doit avoir:

- `name`: Nom du champ (snake_case recommandé)
- `type`: Type de champ (voir tableau ci-dessus)
- `required`: `true` ou `false`

### `identifier_field` (optionnel)

Champ identifiant principal. Valeurs possibles:

- `id` (défaut): Utilise un ID auto-incrémenté
- `slug`: Utilise un slug SEO-friendly (ex: `mon-produit-123`)

> [!NOTE]
> L'utilisation de `slug` nécessite le package `spatie/laravel-sluggable`.

### `roles` (optionnel)

Tableau des rôles autorisés à accéder au module. Défaut: `["user"]`.

```json
{
  "module_name": "admin_settings",
  "roles": ["admin", "super_admin"],
  "fields": [...]
}
```

## Fichiers générés

### Structure

```
app/
├── Models/
│   └── Product.php
├── Http/
│   ├── Controllers/
│   │   └── ProductController.php
│   ├── Resources/
│   │   ├── ProductResource.php
│   │   └── Collections/
│   │       └── ProductCollection.php
│   └── Requests/
│       └── ProductRequest.php
└── Policies/
    └── ProductPolicy.php

database/
├── migrations/
│   └── 2026_02_10_160000_create_products_table.php
├── factories/
│   └── ProductFactory.php
└── seeders/
    └── ProductSeeder.php

routes/
└── api.php (route ajoutée)
```

### Endpoints API générés

Avec Orion REST API, vous obtenez automatiquement:

- `GET /api/products` - Liste paginée
- `POST /api/products` - Créer
- `GET /api/products/{id}` - Détails
- `PUT /api/products/{id}` - Mettre à jour
- `DELETE /api/products/{id}` - Supprimer
- `POST /api/products/search` - Recherche avancée

## Configuration

Dans `config/boost.php`:

```php
'module_generator' => [
    'enabled' => true,              // Activer/désactiver le générateur
    'auto_migrate' => true,         // Exécuter automatiquement les migrations
    'auto_seed' => true,            // Exécuter automatiquement les seeders
    'default_identifier' => 'id',   // Identifiant par défaut
    'default_roles' => ['user'],    // Rôles par défaut
],
```

## Exemples avancés

### Module e-commerce complet

```json
{
  "module_name": "products",
  "identifier_field": "slug",
  "fields": [
    {"name": "name", "type": "string", "required": true},
    {"name": "description", "type": "quill-editor", "required": true},
    {"name": "price", "type": "number", "required": true},
    {"name": "sale_price", "type": "number", "required": false},
    {"name": "stock_quantity", "type": "number", "required": true},
    {"name": "in_stock", "type": "boolean", "required": true},
    {"name": "featured", "type": "boolean", "required": false},
    {"name": "image", "type": "File", "required": false},
    {"name": "published_at", "type": "Date", "required": false}
  ],
  "roles": ["user", "admin"]
}
```

### Module de blog

```json
{
  "module_name": "articles",
  "identifier_field": "slug",
  "fields": [
    {"name": "title", "type": "string", "required": true},
    {"name": "excerpt", "type": "textarea", "required": false},
    {"name": "content", "type": "quill-editor", "required": true},
    {"name": "featured_image", "type": "File", "required": false},
    {"name": "published", "type": "boolean", "required": true},
    {"name": "published_at", "type": "Date", "required": false}
  ]
}
```

## Bonnes pratiques

### Nommage

- **Modules**: Toujours au pluriel (`products`, `users`, `blog_posts`)
- **Champs**: snake_case (`first_name`, `created_at`, `is_active`)
- **Types**: Respecter la casse exacte (`File`, `Date`, pas `file` ou `date`)

### Validation

Les règles de validation sont générées automatiquement:

- `required: true` → `required` en création, `sometimes` en mise à jour
- `type: string` → `string|max:255`
- `type: number` → `numeric`
- `type: boolean` → `boolean`
- `type: Date` → `date`
- `type: email` → `email`

### Fichiers

Pour les champs `File`:

1. Installez Spatie Media Library: `composer require spatie/laravel-medialibrary`
2. Publiez la configuration: `php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider"`
3. Exécutez les migrations: `php artisan migrate`

### Slugs

Pour utiliser `identifier_field: "slug"`:

1. Installez Spatie Sluggable: `composer require spatie/laravel-sluggable`
2. Le slug sera généré automatiquement à partir du premier champ

## Après la génération

### 1. Vérifier les fichiers

Examinez les fichiers générés et personnalisez-les si nécessaire:

- **Controller**: Ajoutez des méthodes personnalisées
- **Resource**: Ajoutez des champs calculés ou des relations
- **Policy**: Ajustez les autorisations selon vos besoins
- **Factory**: Personnalisez les données de test

### 2. Tester l'API

```bash
# Démarrer le serveur
php artisan serve

# Tester les endpoints
curl http://localhost:8000/api/products
```

### 3. Personnaliser

Le code généré est un point de départ. Vous pouvez:

- Ajouter des relations (hasMany, belongsTo, etc.)
- Créer des méthodes personnalisées dans le contrôleur
- Ajouter des scopes dans le modèle
- Enrichir les ressources avec des champs calculés

## Dépannage

### Erreur: "Module generator is disabled"

Activez le générateur dans `config/boost.php`:

```php
'module_generator' => [
    'enabled' => true,
],
```

### Erreur: "Class not found"

Assurez-vous que les packages requis sont installés:

```bash
composer require laravel-orion/orion
composer require spatie/laravel-sluggable  # Si vous utilisez des slugs
composer require spatie/laravel-medialibrary  # Si vous utilisez des fichiers
```

### Migration échoue

Si `auto_migrate` est désactivé, exécutez manuellement:

```bash
php artisan migrate
```

### Seeder échoue

Si `auto_seed` est désactivé, exécutez manuellement:

```bash
php artisan db:seed --class=ProductSeeder
```

## Ressources

- [Laravel Orion Documentation](https://tailflow.github.io/laravel-orion-docs/)
- [Spatie Sluggable](https://github.com/spatie/laravel-sluggable)
- [Spatie Media Library](https://spatie.be/docs/laravel-medialibrary)
