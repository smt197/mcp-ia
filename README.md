<p align="center">
    MCP-IA
</p>


## Introduction
MCP & IA

Laravel Boost accelerates AI-assisted development by providing the essential context and structure that AI needs to generate high-quality, Laravel-specific code.

## Official Documentation

Documentation for Laravel Boost can be found on the [Laravel website](https://laravel.com/docs/boost).

## Contributing

Thank you for considering contributing to Boost! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/laravel/boost/security/policy) on how to report security vulnerabilities.

# Guide d'implementation de nouvelles fonctionnalites - Laravel Boost

Ce guide documente pas a pas les patterns a suivre pour ajouter de nouvelles fonctionnalites au package Laravel Boost, en s'appuyant sur les conventions existantes du code source.

---

## Table des matieres

1. [Ajouter un outil MCP](#1-ajouter-un-outil-mcp)
2. [Ajouter un agent IA](#2-ajouter-un-agent-ia)
3. [Ajouter des guidelines pour un package](#3-ajouter-des-guidelines-pour-un-package)
4. [Ajouter un prompt MCP](#4-ajouter-un-prompt-mcp)
5. [Ajouter une ressource MCP](#5-ajouter-une-ressource-mcp)
6. [Ajouter un skill](#6-ajouter-un-skill)
7. [Ajouter une commande Artisan](#7-ajouter-une-commande-artisan)
8. [Ajouter un middleware ou service](#8-ajouter-un-middleware-ou-service)
- [Compiler et distribuer le package](#compiler-et-distribuer-le-package)
- [Renommer le package](#renommer-le-package)


---

## Fonctionnalites existantes par defaut

### Outils MCP (`src/Mcp/Tools/`)

15 outils enregistres dans `Boost::discoverTools()` :

| Outil | Description | ReadOnly | Parametres |
|-------|-------------|:--------:|------------|
| `ApplicationInfo` | Infos completes de l'app (PHP, Laravel, packages, models Eloquent) | Oui | aucun |
| `BrowserLogs` | Lire les N derniers logs du navigateur (debug frontend/JS) | Oui | `entries` (int, requis) |
| `GenerateModule` | Permet de generer un module avec tous ces ressources, models, migrations... | Oui | `entries` (int, requis) |
| `DatabaseConnections` | Lister les connexions BDD configurees | Oui | aucun |
| `DatabaseQuery` | Executer une requete SQL en lecture seule | Oui | `query` (string, requis), `database` (string) |
| `DatabaseSchema` | Schema BDD : tables, colonnes, index, cles etrangeres | Oui | `database`, `filter`, `include_views`, `include_routines`, `include_column_details` |
| `GetAbsoluteUrl` | URL absolue pour un chemin relatif ou une route nommee | Oui | `path` (string), `route` (string) |
| `GetConfig` | Valeur d'une config en notation pointee (ex: `app.name`) | Oui | `key` (string, requis) |
| `LastError` | Details de la derniere erreur/exception backend | Oui | aucun |
| `ListArtisanCommands` | Lister toutes les commandes Artisan disponibles | Oui | aucun |
| `ListAvailableConfigKeys` | Lister toutes les cles de config en notation pointee | Oui | aucun |
| `ListAvailableEnvVars` | Lister les variables d'environnement du `.env` | Oui | `filename` (string) |
| `ListRoutes` | Lister toutes les routes (y compris Folio) | Oui | `method`, `action`, `name`, `domain`, `path`, `except_path`, `except_vendor`, `only_vendor` |
| `ReadLogEntries` | Lire les N derniers logs applicatifs (PSR-3) | Oui | `entries` (int, requis) |
| `SearchDocs` | Recherche semantique dans la doc Laravel et packages | Non | `queries` (array, requis), `packages` (array), `token_limit` (int) |
| `Tinker` | Executer du code PHP dans le contexte Laravel | Non | `code` (string, requis), `timeout` (int, requis) |

### Prompts MCP (`src/Mcp/Prompts/`)

Enregistres dans `Boost::discoverPrompts()` :

| Prompt | Nom MCP | Description |
|--------|---------|-------------|
| `LaravelCodeSimplifier` | `laravel-code-simplifier` | Simplifie et affine le code PHP/Laravel pour la clarte et la maintenabilite |
| `UpgradeLivewireV4` | `upgrade-livewire-v4` | Guide pas a pas pour migrer de Livewire v3 a v4 |
| `PackageGuidelinePrompt` | *(dynamique par package)* | Guidelines auto-generees pour chaque package tiers ayant un `.ai/core.blade.php` |

### Ressources MCP (`src/Mcp/Resources/`)

Enregistrees dans `Boost::discoverResources()` :

| Ressource | URI | Description |
|-----------|-----|-------------|
| `ApplicationInfo` | `file://instructions/application-info.md` | Infos completes de l'app (delegue a l'outil `ApplicationInfo` via `ToolExecutor`) |
| `PackageGuidelineResource` | *(dynamique)* | Guidelines auto-generees pour chaque package tiers |

### Agents IA (`src/Install/Agents/`)

7 agents enregistres dans `BoostManager::$agents`. Tous implementent `SupportsGuidelines`, `SupportsMcp` et `SupportsSkills`.

| Agent | Identifiant | Config MCP | Guidelines | Particularites |
|-------|-------------|------------|------------|----------------|
| `ClaudeCode` | `claude_code` | `.mcp.json` | `CLAUDE.md` | - |
| `Cursor` | `cursor` | `.cursor/mcp.json` | `AGENTS.md` | `frontmatter()` retourne `true` |
| `Copilot` | `copilot` | `.vscode/mcp.json` | `AGENTS.md` | `frontmatter()` retourne `true` |
| `Codex` | `codex` | `.codex/config.toml` | `AGENTS.md` | Config TOML, `mcpConfigKey()` = `mcp` |
| `Gemini` | `gemini` | `.gemini/settings.json` | `GEMINI.md` | `transformGuidelines()` echappe les `@` |
| `Junie` | `junie` | `.junie/mcp/mcp.json` | `.junie/guidelines.md` | `useAbsolutePathForMcp()` retourne `true` |
| `OpenCode` | `opencode` | `opencode.json` | `AGENTS.md` | - |

### Commandes Artisan (`src/Console/`)

5 commandes enregistrees dans `BoostServiceProvider::registerCommands()` :

| Commande | Classe | Description |
|----------|--------|-------------|
| `boost:install` | `InstallCommand` | Installation interactive (guidelines, skills, MCP) |
| `boost:update` | `UpdateCommand` | Mettre a jour guidelines et skills vers la derniere version |
| `boost:mcp` | `StartCommand` | Demarrer le serveur MCP (appele depuis mcp.json) |
| `boost:execute-tool` | `ExecuteToolCommand` | Executer un outil MCP en isolation (commande interne) |
| `boost:add-skill` | `AddSkillCommand` | Ajouter des skills depuis un depot GitHub distant |

### Skills (`.ai/`)

10 skills uniques repartis sur plusieurs versions de packages :

| Skill | Package | Versions | Activation |
|-------|---------|----------|------------|
| `livewire-development` | Livewire | v2, v3, v4 | Composants reactifs, directives `wire:*`, tests Livewire |
| `inertia-react-development` | Inertia React | v1, v2 | Pages React, formulaires, navigation avec `Link`/`router` |
| `inertia-vue-development` | Inertia Vue | v1, v2 | Pages Vue, formulaires, navigation |
| `inertia-svelte-development` | Inertia Svelte | v1, v2 | Pages Svelte, formulaires, navigation |
| `volt-development` | Volt | - | Composants Livewire single-file, API fonctionnelle |
| `folio-routing` | Folio | - | Routes file-based, parametres, model binding |
| `pest-testing` | Pest | v3, v4 | Tests unitaires/feature, assertions, architecture tests |
| `mcp-development` | MCP | - | Creation d'outils/ressources/prompts MCP |
| `wayfinder-development` | Wayfinder | - | Routes Laravel depuis le frontend TypeScript |

### Guidelines packages (`.ai/`)

20+ packages avec guidelines Blade dans `.ai/{package}/core.blade.php` :

| Categorie | Packages |
|-----------|----------|
| **Core** | `foundation`, `boost`, `php` (8.2, 8.3, 8.4, 8.5), `laravel` (v11, v12) |
| **Frontend** | `livewire`, `inertia-laravel` (v1, v2), `inertia-react`, `inertia-vue`, `inertia-svelte`, `tailwindcss`, `volt`, `wayfinder` |
| **UI** | `fluxui-free`, `fluxui-pro` |
| **Testing** | `pest`, `phpunit`, `pint` |
| **Outils** | `folio`, `pennant`, `mcp`, `sail`, `herd` |
| **Conditionnel** | `enforce-tests` (active si choisi a l'installation) |

### Middleware & Services

| Type | Classe | Role |
|------|--------|------|
| Middleware | `InjectBoost` | Injecte le script JS de capture des logs navigateur dans les reponses HTML |
| Service | `BrowserLogger` | Genere le JavaScript qui intercepte `console.*`, erreurs globales et rejections, envoie a `POST /_boost/browser-logs` |

### Configuration (`config/boost.php`)

| Cle | Defaut | Description |
|-----|--------|-------------|
| `enabled` | `true` | Interrupteur principal de Boost |
| `browser_logs_watcher` | `true` | Active/desactive la capture des logs navigateur |
| `executable_paths.php` | `null` | Chemin personnalise vers l'executable PHP |
| `executable_paths.composer` | `null` | Chemin personnalise vers Composer |
| `executable_paths.npm` | `null` | Chemin personnalise vers npm |
| `executable_paths.vendor_bin` | `null` | Chemin personnalise vers vendor/bin |

Le filtrage MCP (exclude/include par outil, prompt, ressource) est gere dynamiquement par `Boost::filterPrimitives()` et n'apparait pas dans la config par defaut.

---

## Guide d'implementation

## 1. Ajouter un outil MCP

Les outils MCP permettent aux agents IA d'interagir avec l'application Laravel (requetes BDD, routes, config, etc.).

### Etape 1 : Creer la classe

Creer un fichier dans `src/Mcp/Tools/MonOutil.php` :

```php
<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly] // Ajouter si l'outil ne modifie aucun etat
class MonOutil extends Tool
{
    protected string $description = 'Description claire de ce que fait l\'outil';

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'param_requis' => $schema
                ->string()
                ->description('Description du parametre')
                ->required(),
            'param_optionnel' => $schema
                ->boolean()
                ->description('Description du parametre optionnel'),
        ];
    }

    public function handle(Request $request): Response
    {
        $param = $request->get('param_requis');
        $optionnel = $request->get('param_optionnel', false);

        // Logique metier...

        return Response::json(['resultat' => $data]);
        // OU Response::text('Texte markdown');
        // OU Response::error('Message d\'erreur');
    }
}
```

**Elements cles :**
- Etendre `Laravel\Mcp\Server\Tool`
- `$description` (obligatoire) : decrit l'outil pour l'agent IA
- `schema()` : definit les parametres d'entree avec leur type et description
- `handle()` : recoit un `Request`, retourne un `Response`
- `#[IsReadOnly]` : annotation pour les outils en lecture seule

**Injection de dependances :** Le constructeur supporte l'injection via le container Laravel :

```php
public function __construct(protected Roster $roster)
{
    //
}
```

### Etape 2 : Enregistrer l'outil

Ajouter la classe dans la methode `discoverTools()` de `src/Mcp/Boost.php` :

```php
protected function discoverTools(): array
{
    return $this->filterPrimitives([
        // ... outils existants ...
        MonOutil::class,  // <-- Ajouter ici
    ], 'tools');
}
```

### Etape 3 : Ecrire les tests

Creer `tests/Feature/Mcp/Tools/MonOutilTest.php` :

```php
<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Tools\MonOutil;
use Laravel\Mcp\Request;

test('it returns expected data', function (): void {
    $tool = new MonOutil;
    $response = $tool->handle(new Request(['param_requis' => 'valeur']));

    expect($response)
        ->isToolResult()
        ->toolHasNoError()
        ->toolJsonContentToMatchArray(['resultat' => 'valeur_attendue']);
});

test('it returns error when param is invalid', function (): void {
    $tool = new MonOutil;
    $response = $tool->handle(new Request(['param_requis' => 'invalide']));

    expect($response)
        ->isToolResult()
        ->toolHasError();
});
```

**Expectations personnalisees disponibles :**
- `isToolResult()` : verifie que c'est une `Response`
- `toolHasNoError()` / `toolHasError()` : verifie le statut
- `toolTextContains('texte')` : verifie le contenu texte
- `toolTextDoesNotContain('texte')` : verifie l'absence
- `toolJsonContent(fn ($data) => ...)` : assertion sur le JSON parse
- `toolJsonContentToMatchArray([...])` : correspondance partielle

### Etape 4 : Verifier

```bash
composer test -- --filter=MonOutilTest
composer lint
```

### Configuration optionnelle

L'outil peut etre exclu ou inclus via `config/boost.php` (publiable dans l'app hote) :

```php
'mcp' => [
    'tools' => [
        'exclude' => [MonOutil::class],  // Exclure
        'include' => [AutreOutil::class], // Inclure un outil externe
    ],
],
```

### Execution en sous-processus

Les outils sont executes dans des sous-processus PHP isoles via `ToolExecutor`. Cela signifie :
- Chaque execution demarre un processus PHP frais
- Aucun etat partage entre les executions
- Timeout configurable (defaut : 180s, max : 600s)
- Les modifications de code sont prises en compte entre les appels

---

## 2. Ajouter un agent IA

Un agent represente un assistant IA (IDE ou CLI) que Boost peut configurer.

### Etape 1 : Creer la classe

Creer `src/Install/Agents/MonAgent.php` :

```php
<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\Agents;

use Laravel\Boost\Contracts\SupportsGuidelines;
use Laravel\Boost\Contracts\SupportsMcp;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Enums\McpInstallationStrategy;
use Laravel\Boost\Install\Enums\Platform;

class MonAgent extends Agent implements SupportsGuidelines, SupportsMcp, SupportsSkills
{
    public function name(): string
    {
        return 'mon_agent';  // Identifiant snake_case unique
    }

    public function displayName(): string
    {
        return 'Mon Agent';  // Nom affiche a l'utilisateur
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        return match ($platform) {
            Platform::Darwin, Platform::Linux => [
                'command' => 'command -v monagent',
            ],
            Platform::Windows => [
                'command' => 'where monagent 2>nul',
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.monagent'],
            'files' => ['MONAGENT.md'],
        ];
    }

    public function mcpInstallationStrategy(): McpInstallationStrategy
    {
        return McpInstallationStrategy::FILE;
        // Alternatives : McpInstallationStrategy::SHELL, McpInstallationStrategy::NONE
    }

    public function mcpConfigPath(): string
    {
        return '.monagent/mcp.json';
    }

    public function guidelinesPath(): string
    {
        return config('boost.agents.mon_agent.guidelines_path', 'MONAGENT.md');
    }

    public function skillsPath(): string
    {
        return config('boost.agents.mon_agent.skills_path', '.monagent/skills');
    }
}
```

**Methodes abstraites obligatoires :**
- `name()` : identifiant unique en snake_case
- `displayName()` : nom lisible
- `systemDetectionConfig(Platform)` : comment detecter l'agent sur le systeme
- `projectDetectionConfig()` : comment detecter l'agent dans le projet

**Contrats optionnels :**

| Contrat | Methodes a implementer | Usage |
|---------|------------------------|-------|
| `SupportsGuidelines` | `guidelinesPath()`, `frontmatter()`, `transformGuidelines()` | L'agent recoit des guidelines |
| `SupportsMcp` | `useAbsolutePathForMcp()`, `getPhpPath()`, `getArtisanPath()`, `installMcp()` | L'agent supporte le protocole MCP |
| `SupportsSkills` | `skillsPath()` | L'agent supporte les skills |

> Note : `frontmatter()`, `transformGuidelines()`, `useAbsolutePathForMcp()`, `getPhpPath()`, `getArtisanPath()` et `installMcp()` ont des implementations par defaut dans la classe `Agent`. Il suffit de les surcharger si necessaire.

**Methodes surchargeables :**
- `frontmatter(): bool` - Retourner `true` si le fichier de guidelines necessite du frontmatter YAML (ex: Cursor)
- `transformGuidelines(string $markdown): string` - Transformer le markdown genere (ex: Gemini echappe les `@`)
- `useAbsolutePathForMcp(): bool` - Utiliser des chemins absolus pour le MCP (ex: Junie)
- `mcpConfigKey(): string` - Cle de configuration MCP (defaut : `mcpServers`)
- `defaultMcpConfig(): array` - Configuration MCP par defaut
- `shellMcpCommand(): ?string` - Commande shell pour l'installation MCP (strategie SHELL)

### Etape 2 : Enregistrer l'agent

Ajouter dans le tableau `$agents` de `src/BoostManager.php` :

```php
private array $agents = [
    // ... agents existants ...
    'mon_agent' => MonAgent::class,  // <-- Ajouter ici
];
```

### Etape 3 : Ecrire les tests

```php
<?php

declare(strict_types=1);

use Laravel\Boost\Install\Agents\MonAgent;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;

test('MonAgent returns correct name', function (): void {
    $factory = Mockery::mock(DetectionStrategyFactory::class);
    $agent = new MonAgent($factory);

    expect($agent->name())->toBe('mon_agent')
        ->and($agent->displayName())->toBe('Mon Agent');
});

test('MonAgent returns relative php path by default', function (): void {
    config(['boost.executable_paths.php' => null]);
    $factory = Mockery::mock(DetectionStrategyFactory::class);
    $agent = new MonAgent($factory);

    expect($agent->getPhpPath())->toBe('php');
});
```

### Etape 4 : Verifier

```bash
composer test -- --filter=MonAgent
composer lint
```

---

## 3. Ajouter des guidelines pour un package

Les guidelines fournissent du contexte specifique a un package aux agents IA.

### Etape 1 : Creer la structure de repertoires

```
.ai/
  mon-package/
    core.blade.php              # Guidelines de base (toutes versions)
    1/
      core.blade.php            # Guidelines specifiques a la v1
    2/
      core.blade.php            # Guidelines specifiques a la v2
      skill/
        mon-skill/
          SKILL.blade.php       # Skill associe a la v2
          SKILL.md              # Documentation du skill
```

Le nom du repertoire est le nom du package composer normalise (`vendor/package` -> `package`).

### Etape 2 : Ecrire le template Blade

`.ai/mon-package/core.blade.php` :

```blade
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Mon Package

- Description du package et son role dans l'application.
- Conventions importantes a suivre.

@if($assist->hasPackage(\Laravel\Roster\Enums\Packages::SOME_PACKAGE))
## Integration avec Some Package

- Instructions specifiques quand les deux packages sont utilises ensemble.
@endif
```

**Variables disponibles dans les templates :**
- `$assist` (`GuidelineAssist`) : acces aux packages installes, versions, chemins, configuration

**Methodes utiles de `$assist` :**
- `$assist->hasPackage(Packages::NAME)` : verifie si un package est installe
- `$assist->hasSkillsEnabled()` : verifie si les skills sont actives
- `$assist->skills()` : collection des skills disponibles
- `$assist->inertia()->pagesDirectory()` : repertoire des pages Inertia

**Directives speciales :**

```blade
@boostsnippet('Nom de l\'exemple', 'php')
// Code qui sera preserve tel quel, sans interpretation Blade
$variable = "valeur";
@endboostsnippet
```

`@boostsnippet` protege les blocs de code de l'interpretation Blade. Les backticks et balises `<?php` sont aussi automatiquement proteges.

### Etape 3 : Decouverte automatique

Les guidelines sont decouvertes automatiquement par `GuidelineComposer` :
- Les guidelines dans `.ai/` du package Boost sont integrees directement
- Les packages tiers peuvent fournir leurs propres guidelines dans `.ai/` de leur repertoire
- Les guidelines conditionnelles sont filtrees selon les packages detectes par `Roster`

### Etape 4 : Verifier

```bash
php artisan boost:install --guidelines
```

---

## 4. Ajouter un prompt MCP

Les prompts MCP sont des instructions pre-construites que les agents IA peuvent invoquer.

### Etape 1 : Creer la structure

```
src/Mcp/Prompts/
  MonPrompt/
    MonPrompt.php                # Classe du prompt
    mon-prompt.blade.php         # Contenu du prompt (template Blade)
```

### Etape 2 : Creer la classe

`src/Mcp/Prompts/MonPrompt/MonPrompt.php` :

```php
<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Prompts\MonPrompt;

use Laravel\Boost\Concerns\RendersBladeGuidelines;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;

class MonPrompt extends Prompt
{
    use RendersBladeGuidelines;

    protected string $name = 'mon-prompt';

    protected string $title = 'mon_prompt';

    protected string $description = 'Description de ce que fait ce prompt et quand l\'utiliser.';

    public function handle(): Response
    {
        $content = $this->renderBladeFile(__DIR__.'/mon-prompt.blade.php');

        return Response::text($content);
    }
}
```

### Etape 3 : Creer le template Blade

`src/Mcp/Prompts/MonPrompt/mon-prompt.blade.php` :

```blade
# Instructions pour Mon Prompt

## Objectif

Decrire l'objectif du prompt ici.

## Regles

- Regle 1
- Regle 2

@boostsnippet('Exemple', 'php')
// Code d'exemple
@endboostsnippet
```

### Etape 4 : Enregistrer le prompt

Ajouter dans `discoverPrompts()` de `src/Mcp/Boost.php` :

```php
protected function discoverPrompts(): array
{
    $availablePrompts = [
        LaravelCodeSimplifier::class,
        UpgradeLivewireV4::class,
        MonPrompt::class,  // <-- Ajouter ici
        ...$this->discoverThirdPartyPrimitives(Prompt::class),
    ];

    return $this->filterPrimitives($availablePrompts, 'prompts');
}
```

### Etape 5 : Ecrire les tests

`tests/Feature/Mcp/Prompts/MonPromptTest.php` :

```php
<?php

declare(strict_types=1);

use Laravel\Boost\Mcp\Prompts\MonPrompt\MonPrompt;

beforeEach(function (): void {
    $this->prompt = new MonPrompt;
});

test('it has correct name', function (): void {
    expect($this->prompt->name())->toBe('mon-prompt');
});

test('it has a description', function (): void {
    expect($this->prompt->description())->not->toBeEmpty();
});

test('it returns valid response', function (): void {
    $response = $this->prompt->handle();

    expect($response)
        ->isToolResult()
        ->toolHasNoError()
        ->toolTextContains('Instructions pour Mon Prompt');
});
```

### Etape 6 : Verifier

```bash
composer test -- --filter=MonPromptTest
composer lint
```

---

## 5. Ajouter une ressource MCP

Les ressources MCP exposent des donnees persistantes que les agents IA peuvent consulter.

### Etape 1 : Creer la classe

`src/Mcp/Resources/MonResource.php` :

```php
<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Resources;

use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class MonResource extends Resource
{
    protected string $description = 'Description de la ressource et de son contenu.';

    protected string $uri = 'file://instructions/mon-resource.md';

    protected string $mimeType = 'text/markdown';

    public function handle(): Response
    {
        // Option A : Contenu direct
        return Response::json(['data' => 'valeur']);

        // Option B : Via ToolExecutor (deleguer a un outil existant)
        // $response = $this->toolExecutor->execute(MonOutil::class);
        // return $response;

        // Option C : Via template Blade (utiliser le trait RendersBladeGuidelines)
        // $content = $this->renderBladeFile(__DIR__.'/mon-resource.blade.php');
        // return Response::text($content);
    }
}
```

**Proprietes obligatoires :**
- `$description` : description de la ressource
- `$uri` : URI canonique (format `file://instructions/nom.md`)
- `$mimeType` : type MIME (`text/markdown`, `text/plain`, `application/json`)

### Etape 2 : Enregistrer la ressource

Ajouter dans `discoverResources()` de `src/Mcp/Boost.php` :

```php
protected function discoverResources(): array
{
    $availableResources = [
        Resources\ApplicationInfo::class,
        Resources\MonResource::class,  // <-- Ajouter ici
        ...$this->discoverThirdPartyPrimitives(Resource::class),
    ];

    return $this->filterPrimitives($availableResources, 'resources');
}
```

### Etape 3 : Ecrire les tests et verifier

Meme pattern que les outils et prompts. Utiliser les expectations `isToolResult()`, etc.

```bash
composer test -- --filter=MonResourceTest
composer lint
```

---

## 6. Ajouter un skill

Les skills sont des capacites specialisees que les agents peuvent activer selon le contexte.

### Etape 1 : Creer le fichier skill

`.ai/mon-package/2/skill/mon-skill/SKILL.blade.php` :

```blade
---
name: mon-skill
description: "Activer ce skill quand l'utilisateur travaille avec [contexte specifique]. Fournit des patterns et conventions pour [usage]."
license: MIT
metadata:
  author: laravel
---

# Mon Skill

## Quand activer

Activer ce skill quand :
- L'utilisateur travaille avec [contexte]
- L'utilisateur mentionne [mots-cles]

## Conventions

- Convention 1
- Convention 2

## Exemples

@boostsnippet('Exemple basique', 'php')
// Code d'exemple
@endboostsnippet
```

**Frontmatter obligatoire :**
- `name` : identifiant en kebab-case
- `description` : description de quand activer le skill et ce qu'il fait

**Placement :**
- Skills lies a un package : `.ai/{package}/{version}/skill/{skill-name}/SKILL.blade.php`
- Skills generiques : `.ai/skills/{skill-name}/SKILL.blade.php`

### Etape 2 : Decouverte automatique

Les skills sont automatiquement decouverts par `SkillComposer` qui scanne les repertoires `.ai/` :
- Decouverte dans le repertoire `.ai/` de Boost
- Decouverte dans les packages tiers avec des guidelines Boost
- Decouverte dans les skills personnalises de l'utilisateur

Le `SkillWriter` ecrit les skills dans le repertoire defini par `$agent->skillsPath()` pour chaque agent configure.

### Etape 3 : Verifier

```bash
php artisan boost:install --skills
```

---

## 7. Ajouter une commande Artisan

### Etape 1 : Creer la classe

`src/Console/MaCommand.php` :

```php
<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('boost:ma-commande', 'Description courte de la commande')]
class MaCommand extends Command
{
    public function handle(): int
    {
        // Logique de la commande...

        $this->info('Commande executee avec succes.');

        return self::SUCCESS;
        // OU self::FAILURE en cas d'erreur
    }
}
```

**Regles architecturales (appliquees par `tests/ArchTest.php`) :**
- La classe DOIT etendre `Illuminate\Console\Command`
- Le nom de classe DOIT avoir le suffixe `Command`
- Namespace : `Laravel\Boost\Console`

**Avec options et injection de dependances :**

```php
#[AsCommand('boost:ma-commande', 'Description')]
class MaCommand extends Command
{
    protected $signature = 'boost:ma-commande
        {--option-a : Description de l\'option A}
        {argument : Description de l\'argument}';

    public function handle(MonService $service): int
    {
        $optionA = $this->option('option-a');
        $arg = $this->argument('argument');

        // ...

        return self::SUCCESS;
    }
}
```

### Etape 2 : Enregistrer la commande

Ajouter dans `registerCommands()` de `src/BoostServiceProvider.php` :

```php
protected function registerCommands(): void
{
    if ($this->app->runningInConsole()) {
        $this->commands([
            Console\StartCommand::class,
            Console\InstallCommand::class,
            Console\UpdateCommand::class,
            Console\ExecuteToolCommand::class,
            Console\AddSkillCommand::class,
            Console\MaCommand::class,  // <-- Ajouter ici
        ]);
    }
}
```

### Etape 3 : Ecrire les tests

```php
<?php

declare(strict_types=1);

test('boost:ma-commande executes successfully', function (): void {
    $this->artisan('boost:ma-commande')
        ->assertSuccessful();
});

test('boost:ma-commande with options', function (): void {
    $this->artisan('boost:ma-commande', ['--option-a' => true])
        ->assertSuccessful()
        ->expectsOutput('Resultat attendu');
});
```

### Etape 4 : Verifier

```bash
composer test -- --filter=MaCommandTest
composer lint
```

---

## 8. Ajouter un middleware ou service

### Middleware

**Etape 1 :** Creer `src/Middleware/MonMiddleware.php` :

```php
<?php

declare(strict_types=1);

namespace Laravel\Boost\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MonMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Modifier la reponse apres traitement...

        return $response;
    }
}
```

**Etape 2 :** Enregistrer dans `BoostServiceProvider::boot()` :

```php
// Dans une methode dediee ou directement dans boot()
$this->app->booted(function () use ($router): void {
    $router->pushMiddlewareToGroup('web', MonMiddleware::class);
});
```

### Service

**Etape 1 :** Creer `src/Services/MonService.php` :

```php
<?php

declare(strict_types=1);

namespace Laravel\Boost\Services;

class MonService
{
    public function maMethode(): string
    {
        // Logique...
    }
}
```

**Etape 2 :** Enregistrer dans `BoostServiceProvider::register()` :

```php
$this->app->singleton(MonService::class, fn (): MonService => new MonService);
```

Le service est ensuite injectable dans les constructeurs des outils MCP, commandes, etc.

---

## Rappels importants

### Conventions de code obligatoires

- **`declare(strict_types=1)`** dans tous les fichiers PHP (test d'architecture)
- **Pas de `dd()`, `dump()`, `var_dump()`, `die()`, `ray()`** (test d'architecture)
- **Pas d'appels directs a `env()`** sauf dans `BoostServiceProvider` (test d'architecture)
- Style **Laravel Pint** avec comparaisons strictes (`===`)
- **PHPStan** niveau 5

### Commandes de verification

```bash
composer check     # Lint complet + tests
composer test      # Tests uniquement
composer lint      # Pint + PHPStan + Rector (avec corrections)
composer test:lint # Verification sans modification
composer test:types # PHPStan seul
```

### Structure de test standard

```bash
composer test -- --filter=NomDuTest   # Test specifique
composer test -- tests/Unit           # Suite de tests
composer test -- tests/Feature/Mcp   # Sous-repertoire
```

### Pattern de filtrage MCP

Tous les primitives MCP (outils, prompts, ressources) supportent le filtrage via la configuration `boost.mcp.{type}.exclude` et `boost.mcp.{type}.include`. Ce filtrage est applique automatiquement par `Boost::filterPrimitives()`.

---

## Compiler et distribuer le package

Laravel Boost est un package Composer PHP standard. Il n'y a pas d'etape de "build" ou de compilation : le code source PHP est distribue tel quel.

### Prerequis

```bash
php >= 8.2
composer
```

### Installation des dependances de developpement

```bash
composer install
```

### Cycle de validation avant distribution

```bash
# 1. Corriger le style de code + analyse statique + refactoring
composer lint

# 2. Lancer tous les tests (Unit, Feature, Arch)
composer test

# 3. OU tout en une commande
composer check
```

### Publier sur Packagist (depot public)

**Etape 1 :** Creer un depot Git et pousser le code :

```bash
git init
git remote add origin https://github.com/votre-vendor/votre-package.git
git add .
git commit -m "Initial release"
git push -u origin main
```

**Etape 2 :** Creer un tag de version :

```bash
git tag v1.0.0
git push origin v1.0.0
```

**Etape 3 :** Enregistrer le package sur [packagist.org](https://packagist.org) :
- Se connecter avec son compte GitHub.
- Se rendre sur la page de soumission : [https://packagist.org/packages/submit](https://packagist.org/packages/submit).
- Saisir l'URL du dépôt (ex: `https://github.com/smt197/mcp-ia.git`).
- Cliquer sur le bouton **"Check"** pour valider la structure.
- Si tout est correct, cliquer sur **"Submit"**. Packagist détectera automatiquement les informations du `composer.json`.

**Etape 4 :** Le package est installable par les utilisateurs :

```bash
composer require smt197/mcp-ia --dev
```

### Publier sur un depot Composer prive (Satis, Private Packagist, etc.)

**Option A - Satis :**

Ajouter le depot dans le `satis.json` :

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/votre-vendor/votre-package.git" }
    ]
}
```

**Option B - Depot VCS direct :**

Les utilisateurs ajoutent dans leur `composer.json` :

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/votre-vendor/votre-package.git" }
    ],
    "require-dev": {
        "votre-vendor/votre-package": "^1.0"
    }
}
```

**Option C - Depot local (path) pour le developpement :**

```json
{
    "repositories": [
        { "type": "path", "url": "../chemin-vers-le-package" }
    ],
    "require-dev": {
        "votre-vendor/votre-package": "*"
    }
}
```

### Structure minimale requise pour la distribution

Les fichiers essentiels a inclure dans le package :

```
composer.json          # Identite, dependances, autoload, scripts
config/boost.php       # Configuration publiable
src/                   # Code source PHP
.ai/                   # Templates de guidelines Blade
LICENSE.md             # Licence
```

Les fichiers a exclure de la distribution (via `.gitattributes`) :

```gitattributes
/tests              export-ignore
/.github            export-ignore
/art                export-ignore
phpunit.xml.dist    export-ignore
phpstan.neon.dist   export-ignore
pint.json           export-ignore
rector.php          export-ignore
CHANGELOG.md        export-ignore
UPGRADE.md          export-ignore
```

### Auto-decouverte Laravel

Le package est auto-decouvert grace a la section `extra.laravel` dans `composer.json` :

```json
"extra": {
    "laravel": {
        "providers": [
            "Laravel\\Boost\\BoostServiceProvider"
        ]
    }
}
```

Aucune action manuelle n'est necessaire pour l'utilisateur apres `composer require`.

---

## Renommer le package

Pour forker ou renommer `laravel/boost` en un autre nom (ex: `acme/ai-tools`), voici la liste exhaustive de tous les points d'ancrage a modifier.

### Etape 1 : Identite Composer (`composer.json`)

```diff
- "name": "laravel/boost",
+ "name": "acme/ai-tools",

- "homepage": "https://github.com/laravel/boost",
+ "homepage": "https://github.com/acme/ai-tools",

- "Laravel\\Boost\\": "src/"
+ "Acme\\AiTools\\": "src/"

- "Laravel\\Boost\\BoostServiceProvider"
+ "Acme\\AiTools\\AiToolsServiceProvider"
```

### Etape 2 : Namespace PHP (tous les fichiers dans `src/` et `tests/`)

Renommer le namespace racine dans **tous** les fichiers PHP :

| Ancien | Nouveau |
|--------|---------|
| `namespace Laravel\Boost;` | `namespace Acme\AiTools;` |
| `namespace Laravel\Boost\Mcp;` | `namespace Acme\AiTools\Mcp;` |
| `namespace Laravel\Boost\Mcp\Tools;` | `namespace Acme\AiTools\Mcp\Tools;` |
| `namespace Laravel\Boost\Install\Agents;` | `namespace Acme\AiTools\Install\Agents;` |
| `namespace Laravel\Boost\Console;` | `namespace Acme\AiTools\Console;` |
| `use Laravel\Boost\...;` | `use Acme\AiTools\...;` |
| `namespace Tests;` | *(inchange, ou adapter si voulu)* |

**Commande pour renommer en masse :**

```bash
# Rechercher toutes les occurrences
grep -r "Laravel\\\\Boost" src/ tests/ composer.json --include="*.php" --include="*.json" -l

# Remplacement (adapter selon l'OS)
find src tests -name "*.php" -exec sed -i 's/Laravel\\Boost/Acme\\AiTools/g' {} +
```

### Etape 3 : Noms de classes racine

| Fichier | Ancien | Nouveau |
|---------|--------|---------|
| `src/BoostServiceProvider.php` | `class BoostServiceProvider` | `class AiToolsServiceProvider` |
| `src/Boost.php` | `class Boost extends Facade` | `class AiTools extends Facade` |
| `src/BoostManager.php` | `class BoostManager` | `class AiToolsManager` |
| `src/Mcp/Boost.php` | `class Boost extends Server` | `class AiToolsServer extends Server` |

> Renommer egalement les fichiers PHP pour correspondre aux nouveaux noms de classes.

### Etape 4 : Configuration (`config/boost.php`)

Renommer le fichier et la cle de configuration :

```bash
mv config/boost.php config/ai-tools.php
```

Puis mettre a jour le ServiceProvider :

```diff
- $this->mergeConfigFrom(__DIR__.'/../config/boost.php', 'boost');
+ $this->mergeConfigFrom(__DIR__.'/../config/ai-tools.php', 'ai-tools');

- ], 'boost-config');
+ ], 'ai-tools-config');
```

### Etape 5 : References `config('boost.*')` dans le code source

Rechercher et remplacer toutes les references de configuration. Fichiers concernes :

| Pattern | Fichiers concernes |
|---------|-------------------|
| `config('boost.enabled')` | `BoostServiceProvider.php` |
| `config('boost.browser_logs_watcher')` | `BoostServiceProvider.php` |
| `config('boost.executable_paths.*')` | `Agent.php`, `GuidelineAssist.php` |
| `config('boost.agents.*')` | Toutes les classes Agent (7 fichiers) |
| `config('boost.mcp.*')` | `Mcp/Boost.php`, `Mcp/ToolRegistry.php` |
| `config('boost.hosted.*')` | `Concerns/MakesHttpRequests.php`, `Tools/SearchDocs.php` |
| `config('boost.github.*')` | `Skills/Remote/GitHubSkillProvider.php` |
| `config('boost.purpose')` | `.ai/foundation.blade.php` |

```bash
# Trouver toutes les occurrences
grep -rn "config('boost\." src/ .ai/ --include="*.php" --include="*.blade.php"
```

### Etape 6 : Variables d'environnement

| Ancien | Nouveau |
|--------|---------|
| `BOOST_ENABLED` | `AI_TOOLS_ENABLED` |
| `BOOST_BROWSER_LOGS_WATCHER` | `AI_TOOLS_BROWSER_LOGS_WATCHER` |
| `BOOST_PHP_EXECUTABLE_PATH` | `AI_TOOLS_PHP_EXECUTABLE_PATH` |
| `BOOST_COMPOSER_EXECUTABLE_PATH` | `AI_TOOLS_COMPOSER_EXECUTABLE_PATH` |
| `BOOST_NPM_EXECUTABLE_PATH` | `AI_TOOLS_NPM_EXECUTABLE_PATH` |
| `BOOST_VENDOR_BIN_EXECUTABLE_PATH` | `AI_TOOLS_VENDOR_BIN_EXECUTABLE_PATH` |

### Etape 7 : Commandes Artisan (`src/Console/`)

| Fichier | Ancien | Nouveau |
|---------|--------|---------|
| `StartCommand.php` | `boost:mcp` | `ai-tools:mcp` |
| `InstallCommand.php` | `boost:install` | `ai-tools:install` |
| `UpdateCommand.php` | `boost:update` | `ai-tools:update` |
| `ExecuteToolCommand.php` | `boost:execute-tool` | `ai-tools:execute-tool` |
| `AddSkillCommand.php` | `boost:add-skill` | `ai-tools:add-skill` |

### Etape 8 : Nom du serveur MCP

```diff
# src/BoostServiceProvider.php (ou nouveau nom)
- Mcp::local('laravel-boost', Boost::class);
+ Mcp::local('acme-ai-tools', AiToolsServer::class);

# src/Console/StartCommand.php
- Artisan::call('mcp:start laravel-boost');
+ Artisan::call('mcp:start acme-ai-tools');

# src/Mcp/Boost.php (ou nouveau nom)
- protected string $name = 'Laravel Boost';
+ protected string $name = 'Acme AI Tools';
```

### Etape 9 : Route et nom de route

```diff
# src/BoostServiceProvider.php
- Route::post('/_boost/browser-logs', ...)->name('boost.browser-logs');
+ Route::post('/_ai-tools/browser-logs', ...)->name('ai-tools.browser-logs');

# src/Services/BrowserLogger.php
- route('boost.browser-logs')
+ route('ai-tools.browser-logs')
- '/_boost/browser-logs'
+ '/_ai-tools/browser-logs'
```

### Etape 10 : Directive Blade

```diff
# src/BoostServiceProvider.php
- $bladeCompiler->directive('boostJs', ...);
+ $bladeCompiler->directive('aiToolsJs', ...);
```

> Les utilisateurs devront remplacer `@boostJs` par `@aiToolsJs` dans leurs vues.

### Etape 11 : Cle de cache

```diff
# src/BoostServiceProvider.php
- $cacheKey = 'boost.roster.scan';
+ $cacheKey = 'ai-tools.roster.scan';
```

### Etape 12 : Canal de log

```diff
# src/BoostServiceProvider.php
- 'logging.channels.browser' => [...]
+ 'logging.channels.browser' => [...]  // Peut rester 'browser' car non lie au nom du package
```

### Etape 13 : Tests d'architecture (`tests/ArchTest.php`)

```diff
- ->expect('Laravel\Boost')
+ ->expect('Acme\AiTools')

- ->toBeUsedIn('Laravel\Boost')
+ ->toBeUsedIn('Acme\AiTools')
```

### Etape 14 : Documentation

Mettre a jour les references dans :
- `README.md` : logos, badges, texte
- `CHANGELOG.md` : URLs GitHub
- `UPGRADE.md` : noms de commandes
- `CLAUDE.md` : noms de classes et chemins
- `CONTRIBUTING_GUIDE.md` : ce fichier

### Checklist de verification apres renommage

```bash
# 1. Verifier qu'aucune reference a l'ancien nom ne subsiste
grep -rn "laravel/boost" . --include="*.php" --include="*.json" --include="*.md"
grep -rn "Laravel\\\\Boost" . --include="*.php" --include="*.json"
grep -rn "'boost\." src/ --include="*.php"
grep -rn "boost:" src/Console/ --include="*.php"

# 2. Regenerer l'autoload
composer dump-autoload

# 3. Lancer les tests
composer check
```

## License

Laravel Boost is open-sourced software licensed under the [MIT license](LICENSE.md).
