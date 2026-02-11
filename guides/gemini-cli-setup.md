# Guide: Utiliser l'outil MCP generate-module avec Gemini CLI

## Configuration (Déjà fait ✓)

Le serveur MCP `laravel-boost` a été ajouté à votre `settings.json` :

```json
{
  "mcpServers": {
    "laravel-boost": {
      "command": "C:\\laragon\\bin\\php\\php-8.3.17-Win32-vs16-x64\\php.exe",
      "args": ["artisan", "mcp:start", "laravel-boost"],
      "cwd": "C:\\laragon\\www\\ressurex-backend"
    }
  }
}
```

## Redémarrer Gemini CLI

Pour que la configuration soit prise en compte, redémarrez Gemini CLI.

## Utiliser l'outil depuis un prompt

### Exemple 1: Module simple

**Prompt** :
```
Génère un module "products" avec les champs suivants :
- name (texte, requis)
- description (textarea, optionnel)
- price (nombre, requis)
- in_stock (boolean, requis)
```

Gemini CLI va automatiquement appeler l'outil `generate-module` avec :
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

### Exemple 2: Module avec slug

**Prompt** :
```
Crée un module "blog_posts" avec un identifiant slug et ces champs :
- title (texte, requis)
- content (éditeur riche, requis)
- published_at (date, optionnel)
```

### Exemple 3: Module avec fichiers

**Prompt** :
```
Génère un module "documents" avec :
- title (texte, requis)
- file (fichier, requis)
- category (texte, optionnel)
```

### Exemple 4: Module e-commerce complet

**Prompt** :
```
Crée un module "products" pour un e-commerce avec slug et ces champs :
- name (texte, requis)
- description (éditeur riche, requis)
- price (nombre, requis)
- sale_price (nombre, optionnel)
- stock_quantity (nombre, requis)
- in_stock (boolean, requis)
- featured (boolean, optionnel)
- image (fichier, optionnel)
- published_at (date, optionnel)

Accessible par les rôles : user et admin
```

## Types de champs disponibles

Utilisez ces termes dans vos prompts :

| Type dans le prompt | Type MCP | Description |
|---------------------|----------|-------------|
| texte, string | `string` | Texte court |
| nombre, number | `number` | Nombre entier |
| boolean, vrai/faux | `boolean` | Booléen |
| date | `Date` | Date et heure |
| fichier, file | `File` | Upload de fichier |
| textarea, texte long | `textarea` | Texte long |
| éditeur riche, quill | `quill-editor` | Éditeur HTML |
| email | `email` | Email |
| password, mot de passe | `password` | Mot de passe |

## Prompts naturels

Vous pouvez utiliser un langage naturel, Gemini comprendra :

```
Crée-moi un module pour gérer des articles de blog avec un titre, 
un contenu riche, une image de couverture, et une date de publication. 
Utilise des slugs pour les URLs.
```

```
J'ai besoin d'un module "customers" avec nom, email, téléphone, 
adresse, et un champ pour savoir s'ils sont actifs ou non.
```

```
Génère un module de gestion de produits avec nom, description, 
prix, prix soldé, quantité en stock, et une image.
```

## Vérifier les fichiers générés

Après la génération, vérifiez dans `ressurex-backend` :

```
app/
├── Models/Product.php
├── Http/
│   ├── Controllers/ProductController.php
│   ├── Resources/ProductResource.php
│   └── Requests/ProductRequest.php
└── Policies/ProductPolicy.php

database/
├── migrations/*_create_products_table.php
├── factories/ProductFactory.php
└── seeders/ProductSeeder.php
```

## Tester l'API générée

```bash
cd C:\laragon\www\ressurex-backend
php artisan serve
```

Puis testez :
- `GET http://localhost:8000/api/products`
- `POST http://localhost:8000/api/products`
- etc.

## Conseils

1. **Soyez précis** : Indiquez clairement les champs requis vs optionnels
2. **Utilisez des noms au pluriel** : "products", "blog_posts", "users"
3. **Mentionnez les slugs** : Si vous voulez des URLs SEO-friendly
4. **Spécifiez les fichiers** : Indiquez clairement les champs de type fichier
5. **Définissez les rôles** : Si nécessaire, précisez qui peut accéder au module
