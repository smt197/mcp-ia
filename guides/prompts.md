# Exemples de Prompts - Générateur de Modules

Copiez-collez ces prompts dans Gemini CLI pour tester l'outil `generate-module`.

## 🚀 Prompt de Test Rapide

```
Utilise l'outil generate-module pour créer un module "test_items" avec ces champs :
- name (string, requis)
- description (textarea, optionnel)
- price (number, requis)
```

**Ce que ça va créer** :
- Model, Controller, Migration, Resource, Policy, Factory, Seeder
- Routes API automatiques
- 10 enregistrements de test

---

## 📦 Exemples par Cas d'Usage

### E-commerce - Produits

```
Génère un module "products" avec slug pour mon e-commerce :
- name (texte, requis)
- description (éditeur riche, requis)
- price (nombre, requis)
- sale_price (nombre, optionnel)
- stock_quantity (nombre, requis)
- in_stock (boolean, requis)
- image (fichier, optionnel)

Rôles autorisés : user, admin
```

### Blog - Articles

```
Crée un module "articles" avec slug pour un blog :
- title (texte, requis)
- excerpt (textarea, optionnel)
- content (éditeur riche, requis)
- featured_image (fichier, optionnel)
- published (boolean, requis)
- published_at (date, optionnel)
```

### CRM - Clients

```
Génère un module "customers" pour gérer mes clients :
- company_name (texte, requis)
- contact_name (texte, requis)
- email (email, requis)
- phone (texte, optionnel)
- address (textarea, optionnel)
- is_active (boolean, requis)
```

### Gestion de Documents

```
Crée un module "documents" :
- title (texte, requis)
- file (fichier, requis)
- category (texte, optionnel)
- uploaded_by (texte, requis)
- uploaded_at (date, requis)
```

### Événements

```
Génère un module "events" avec slug :
- name (texte, requis)
- description (éditeur riche, requis)
- location (texte, requis)
- start_date (date, requis)
- end_date (date, requis)
- max_participants (nombre, optionnel)
- is_public (boolean, requis)
```

---

## 🎯 Prompt Minimal (pour tester rapidement)

```
Génère un module "tasks" avec :
- title (string, requis)
- completed (boolean, requis)
```

---

## 💡 Conseils

1. **Redémarrez Gemini CLI** après avoir modifié `settings.json`
2. **Utilisez des noms au pluriel** : "products", "articles", "tasks"
3. **Soyez explicite** sur requis/optionnel
4. **Mentionnez "slug"** si vous voulez des URLs SEO-friendly
5. **Précisez "fichier"** pour les uploads

---

## ✅ Vérification après génération

Après avoir utilisé l'outil, vérifiez dans `ressurex-backend` :

```bash
# Voir les fichiers créés
ls app/Models/
ls app/Http/Controllers/
ls database/migrations/

# Tester l'API
php artisan serve
# Visitez http://localhost:8000/api/[module_name]
```
