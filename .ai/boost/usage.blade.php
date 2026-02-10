@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Guide d'utilisation des outils Laravel Boost

Ce guide explique comment utiliser efficacement les outils MCP (Model Context Protocol) fournis par Laravel Boost pour accélérer votre développement.

## 1. Débogage avec Tinker
L'outil `tinker` vous permet d'exécuter du code PHP arbitraire dans le contexte de votre application Laravel. C'est l'outil le plus puissant pour tester des idées ou déboguer des relations complexes.

@boostsnippet("Utiliser Tinker pour déboguer un modèle", "php")
// Récupérer le dernier utilisateur et ses commandes
$user = \App\Models\User::latest()->first();
return $user->load('orders');
@endboostsnippet

## 2. Recherche Sémantique avec `search-docs`
Avant de demander l'implémentation d'une fonctionnalité complexe, cherchez les meilleures pratiques dans la documentation.

- **Astuce** : Utilisez plusieurs termes larges pour obtenir les meilleurs résultats.
- **Exemple** : `queries=['rate limiting', 'throttle requests']`.

## 3. Inspection de la Base de Données
Utilisez `database-schema` pour comprendre la structure d'une table avant de créer une migration ou un modèle.

@boostsnippet("Inspecter une table", "bash")
# Récupérer les colonnes, index et clés étrangères d'une table
database-schema(filter='users')
@endboostsnippet

Pour de simples lectures, préférez `database-query` qui est optimisé pour les requêtes SQL directes.

## 4. Consultation des Logs
- `ReadLogEntries` : Pour voir les erreurs backend (Laravel logs).
- `BrowserLogs` : Pour capturer les erreurs JavaScript ou les `console.log` qui se produisent dans le navigateur.

## 5. URLs Dynamiques
Utilisez toujours `get-absolute-url` quand vous devez générer un lien vers une route ou un fichier public, pour vous assurer qu'il fonctionne dans votre environnement actuel (Docker, Herd, etc.).

@boostsnippet("Générer une URL de route", "javascript")
// Utile pour les tests ou pour fournir un lien à l'utilisateur
get-absolute-url(route='login')
@endboostsnippet
