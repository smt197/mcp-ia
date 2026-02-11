# Guide de Test Rapide - Générateur de Modules

## ✅ Tests Réussis

Le service `ModuleGeneratorService` a été testé avec succès ! Tous les tests passent :

- ✓ Instanciation du service
- ✓ Transformation des noms (pluriel → singulier, StudlyCase)
- ✓ Support des identifiants slug
- ✓ Détection des champs File
- ✓ Mapping des types de champs

## Comment tester localement

### Option 1: Tests manuels (déjà fait ✓)

```bash
C:\laragon\bin\php\php-8.3.17-Win32-vs16-x64\php.exe test-module-generator.php
```

**Résultat**: ✅ Tous les tests passent !

### Option 2: Tests Pest (recommandé pour le développement)

```bash
cd C:\laragon\www\laravel-boost
C:\laragon\bin\php\php-8.3.17-Win32-vs16-x64\php.exe vendor/bin/pest --filter=ModuleGeneratorServiceTest
```

### Option 3: Tester dans un projet Laravel réel

1. **Créer un projet de test**:
   ```bash
   cd C:\laragon\www
   laravel new test-boost
   cd test-boost
   ```

2. **Installer laravel-boost en mode développement local**:
   
   Éditez `composer.json` du projet `test-boost`:
   ```json
   {
     "repositories": [
       {
         "type": "path",
         "url": "../laravel-boost"
       }
     ],
     "require": {
       "thumanics/laravel-boost": "@dev"
     }
   }
   ```

3. **Installer les dépendances**:
   ```bash
   composer install
   ```

4. **Installer Orion (requis)**:
   ```bash
   composer require laravel-orion/orion
   ```

5. **Publier la configuration**:
   ```bash
   php artisan vendor:publish --tag=boost-config
   ```

6. **Tester la génération d'un module**:
   
   Créez un fichier `test-generate.php` dans le projet `test-boost`:
   ```php
   <?php
   
   require __DIR__ . '/vendor/autoload.php';
   
   $app = require_once __DIR__ . '/bootstrap/app.php';
   $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
   
   use Laravel\Boost\Services\ModuleGeneratorService;
   
   $generator = new ModuleGeneratorService(
       'products',
       [
           ['name' => 'name', 'type' => 'string', 'required' => true],
           ['name' => 'description', 'type' => 'textarea', 'required' => false],
           ['name' => 'price', 'type' => 'number', 'required' => true],
           ['name' => 'in_stock', 'type' => 'boolean', 'required' => true],
       ]
   );
   
   $result = $generator->generate();
   
   print_r($result);
   ```

7. **Exécuter**:
   ```bash
   php test-generate.php
   ```

8. **Vérifier les fichiers créés**:
   - `app/Models/Product.php`
   - `app/Http/Controllers/ProductController.php`
   - `database/migrations/*_create_products_table.php`
   - etc.

9. **Tester l'API**:
   ```bash
   php artisan migrate
   php artisan db:seed --class=ProductSeeder
   php artisan serve
   ```
   
   Puis visitez: `http://localhost:8000/api/products`

## Tester l'outil MCP

### Prérequis

1. Avoir un client MCP configuré (Claude Desktop, Gemini CLI, etc.)
2. Le serveur MCP de `laravel-boost` doit être enregistré

### Configuration MCP

Dans votre fichier de configuration MCP (ex: `settings.json` pour Gemini CLI):

```json
{
  "mcpServers": {
    "laravel-boost": {
      "command": "php",
      "args": ["artisan", "mcp:start"],
      "cwd": "C:/laragon/www/test-boost"
    }
  }
}
```

### Utiliser l'outil

Depuis votre client MCP, appelez l'outil `generate-module`:

```json
{
  "module_name": "products",
  "fields": [
    {"name": "name", "type": "string", "required": true},
    {"name": "price", "type": "number", "required": true}
  ]
}
```

## Prochaines étapes

1. ✅ Tests unitaires passent
2. ⏳ Tester dans un projet Laravel réel
3. ⏳ Tester via MCP client
4. ⏳ Publier sur Packagist

## Dépannage

### Erreur: "Class not found"

Assurez-vous que `composer install` a été exécuté et que l'autoload est à jour:
```bash
composer dump-autoload
```

### Erreur: "Orion not found"

Installez Laravel Orion:
```bash
composer require laravel-orion/orion
```

### Les fichiers ne sont pas créés

Vérifiez les permissions d'écriture sur les dossiers:
- `app/`
- `database/`
- `routes/`
