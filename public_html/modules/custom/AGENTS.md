# deploya Custom Modules - Agent Rules

This document contains coding rules and conventions for deploya custom Drupal modules that AI agents should follow when making changes.

> For theme coding standards, see `web/themes/custom/deploya/Agents.md`.

---

## General Principles

### Follow Drupal Coding Standards

**Rule**: All PHP code must comply with [Drupal coding standards](https://www.drupal.org/docs/develop/standards).

- 2-space indentation (no tabs)
- Opening braces on the same line for control structures, new line for classes/functions
- Single blank line between methods
- DocBlocks on all classes, methods, and hooks

**✅ Good:**

```php
<?php

namespace Drupal\deploya_pokemon\Service;

/**
 * Syncs Pokémon data from the PokeAPI.
 */
class PokemonSyncService {

  /**
   * Syncs a single Pokémon by its Pokédex number.
   *
   * @param int $id
   *   The Pokédex number.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function syncById(int $id): bool {
    // Implementation.
  }

}
```

### PSR-4 Autoloading

**Rule**: All PHP classes must follow PSR-4 autoloading. The namespace root for a module `deploya_pokemon` is `Drupal\deploya_pokemon`.

```
web/modules/custom/deploya_pokemon/
└── src/
    ├── Service/
    │   └── PokemonSyncService.php      → Drupal\deploya_pokemon\Service\PokemonSyncService
    ├── Plugin/
    │   └── QueueWorker/
    │       └── PokemonSyncWorker.php   → Drupal\deploya_pokemon\Plugin\QueueWorker\PokemonSyncWorker
    ├── Commands/
    │   └── PokemonCommands.php         → Drupal\deploya_pokemon\Commands\PokemonCommands
    └── Form/
        └── PokemonSyncForm.php         → Drupal\deploya_pokemon\Form\PokemonSyncForm
```

---

## Dependency Injection

### Never Use Static `\Drupal::` Calls Inside Classes

**Rule**: Never call `\Drupal::service()`, `\Drupal::entityTypeManager()`, or any other static `\Drupal::` method inside a class. Always inject dependencies through the constructor.

**❌ Bad:**

```php
public function syncById(int $id): bool {
  $client = \Drupal::httpClient();
  $logger = \Drupal::logger('deploya_pokemon');
  $entity_manager = \Drupal::entityTypeManager();
}
```

**✅ Good:**

```php
use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

class PokemonSyncService {

  public function __construct(
    protected ClientInterface $httpClient,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

}
```

### Static Calls Are Allowed Only in Procedural Code

**Rule**: `\Drupal::` static calls are acceptable only in `.module` files and other procedural contexts (hooks) where a class cannot be used.

**✅ Acceptable in `.module` files:**

```php
/**
 * Implements hook_cron().
 */
function deploya_pokemon_cron(): void {
  \Drupal::service('deploya_pokemon.sync_service')->syncAll();
}
```

---

## Services

### Define All Services in `*.services.yml`

**Rule**: Every custom service, event subscriber, queue worker, and command must be declared in the module's `*.services.yml` file with correct tags and arguments.

**✅ Good (`deploya_pokemon.services.yml`):**

```yaml
services:
  deploya_pokemon.sync_service:
    class: Drupal\deploya_pokemon\Service\PokemonSyncService
    arguments:
      - '@http_client'
      - '@entity_type.manager'
      - '@logger.factory'

  deploya_pokemon.commands:
    class: Drupal\deploya_pokemon\Commands\PokemonCommands
    arguments:
      - '@deploya_pokemon.sync_service'
    tags:
      - { name: drush.command }
```

### Use `create()` for Plugin Classes

**Rule**: For plugin classes (Blocks, QueueWorkers, Forms), use the `create()` static factory method to inject services.

**✅ Good:**

```php
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @QueueWorker(
 *   id = "deploya_pokemon_sync",
 *   title = @Translation("Pokémon sync worker"),
 *   cron = {"time" = 60}
 * )
 */
class PokemonSyncWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected PokemonSyncService $syncService,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('deploya_pokemon.sync_service'),
    );
  }

}
```

---

## Database

### Never Write Raw SQL Strings

**Rule**: Never concatenate variables directly into SQL strings. Use parameterized queries or entity queries.

**❌ Bad:**

```php
$result = $this->database->query("SELECT * FROM node WHERE title = '" . $title . "'");
```

**✅ Good — Entity Query (preferred):**

```php
$nids = $this->entityTypeManager
  ->getStorage('node')
  ->getQuery()
  ->accessCheck(FALSE)
  ->condition('type', 'pokemon')
  ->condition('title', $name)
  ->execute();
```

**✅ Good — Parameterized query (when entity query is insufficient):**

```php
$result = $this->database->select('deploya_pokemon_cache', 'c')
  ->fields('c', ['data', 'updated'])
  ->condition('c.pokemon_id', $id)
  ->execute()
  ->fetchAssoc();
```

### Custom Tables Use `hook_schema()`

**Rule**: Define all custom database tables in `hook_schema()` inside the module's `.install` file. Never create tables manually.

**✅ Good (`deploya_pokemon.install`):**

```php
/**
 * Implements hook_schema().
 */
function deploya_pokemon_schema(): array {
  $schema['deploya_pokemon_cache'] = [
    'description' => 'Stores raw PokeAPI responses for diffing and cache.',
    'fields' => [
      'pokemon_id' => [
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'description' => 'The Pokédex number.',
      ],
      'data' => [
        'type' => 'blob',
        'size' => 'big',
        'not null' => FALSE,
        'description' => 'Serialized JSON response from PokeAPI.',
      ],
      'updated' => [
        'type' => 'int',
        'not null' => TRUE,
        'default' => 0,
        'description' => 'Unix timestamp of last sync.',
      ],
    ],
    'primary key' => ['pokemon_id'],
  ];

  return $schema;
}
```

---

## HTTP / External API Calls

### Use Drupal's HTTP Client

**Rule**: Always use `\GuzzleHttp\ClientInterface` (injected as `@http_client`) for external API requests. Never use `file_get_contents()` or `curl_*` functions directly.

**✅ Good:**

```php
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

public function fetchPokemon(int $id): ?array {
  try {
    $response = $this->httpClient->get("https://pokeapi.co/api/v2/pokemon/{$id}");
    return json_decode((string) $response->getBody(), TRUE);
  }
  catch (RequestException $e) {
    $this->logger->error('Failed to fetch Pokémon @id: @message', [
      '@id' => $id,
      '@message' => $e->getMessage(),
    ]);
    return NULL;
  }
}
```

### Use Queue for Bulk API Operations

**Rule**: Never perform bulk external API calls in a single request or cron run. Use Drupal's Queue API to process items one at a time.

**✅ Good — Enqueue items:**

```php
public function enqueueSyncAll(): void {
  $queue = $this->queueFactory->get('deploya_pokemon_sync');
  for ($i = 1; $i <= 151; $i++) {
    $queue->createItem(['pokemon_id' => $i]);
  }
}
```

**✅ Good — Process in QueueWorker:**

```php
public function processItem(mixed $data): void {
  $this->syncService->syncById((int) $data['pokemon_id']);
}
```

---

## Configuration

### Store Config in `config/install/` YAML

**Rule**: All module configuration must live in YAML files under `config/install/`. Never hard-code configuration values in PHP.

```
deploya_pokemon/
└── config/
    └── install/
        └── deploya_pokemon.settings.yml
```

**✅ Good (`deploya_pokemon.settings.yml`):**

```yaml
pokeapi_base_url: 'https://pokeapi.co/api/v2'
sync_limit: 151
```

**✅ Good — Reading config in PHP:**

```php
$config = $this->configFactory->get('deploya_pokemon.settings');
$baseUrl = $config->get('pokeapi_base_url');
```

---

## Hooks

### Use OOP Hook Classes (Drupal 11.1+)

**Rule**: Implement hooks as methods in dedicated classes inside `src/Hook/`, using the `#[Hook]` attribute (introduced in Drupal 11.1). Do **not** add new hook implementations to `.module` files. The `.module` file should only be kept for hooks that are not yet supported as OOP (e.g., `hook_schema`, `hook_install`, `hook_update_N`).

Hook classes in `src/Hook/` are **automatically registered as autowired services** — no `*.services.yml` entry is needed.

**✅ Good — OOP hook class:**

```php
// src/Hook/PokemonHooks.php

<?php

declare(strict_types=1);

namespace Drupal\deploya_pokemon\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\deploya_pokemon\Service\PokemonSyncService;
use Drupal\node\NodeInterface;

/**
 * Hook implementations for the deploya_pokemon module.
 */
class PokemonHooks {

  public function __construct(
    protected PokemonSyncService $syncService,
  ) {}

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    $this->syncService->enqueueSyncAll();
  }

  /**
   * Implements hook_node_presave().
   */
  #[Hook('node_presave')]
  public function nodepresave(NodeInterface $node): void {
    if ($node->bundle() === 'pokemon') {
      $this->syncService->normalize($node);
    }
  }

}
```

**Multiple hooks per class** — group related hooks together:

```php
/**
 * Implements hook_entity_insert() and hook_entity_update().
 */
#[Hook('entity_insert')]
#[Hook('entity_update')]
public function entitySave(EntityInterface $entity): void {
  // Fires on both insert and update.
}
```

**❌ Bad — procedural hooks for new code:**

```php
// deploya_pokemon.module — do NOT add new hooks here

function deploya_pokemon_cron(): void {
  \Drupal::service('deploya_pokemon.sync_service')->enqueueSyncAll();
}
```

### What Still Goes in `.module`

Only keep the `.module` file for hooks that cannot yet run as OOP classes:

- `hook_schema()` — runs before full container bootstrap
- `hook_install()` / `hook_uninstall()` / `hook_update_N()` — install-time hooks
- Any hook explicitly documented as not yet supporting OOP in the Drupal version in use

```php
// deploya_pokemon.module — install/schema hooks only

/**
 * Implements hook_schema().
 */
function deploya_pokemon_schema(): array {
  // Table definitions.
}
```

### Organising Hook Classes

Group hooks by domain, not by hook type. Each class should relate to one area of functionality:

```
src/Hook/
├── PokemonHooks.php    ← cron, node presave, entity events for pokemon
├── FormHooks.php       ← form_alter implementations
└── ThemeHooks.php      ← theme, preprocess hooks (module only; themes stay procedural)
```

---

## Logging

### Use the Logger Factory, Not `error_log()` or `print`

**Rule**: Always use Drupal's logger service. Inject `@logger.factory` and call `$this->loggerFactory->get('module_name')`.

**✅ Good:**

```php
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

public function __construct(
  protected LoggerChannelFactoryInterface $loggerFactory,
) {}

public function doSomething(): void {
  $logger = $this->loggerFactory->get('deploya_pokemon');
  $logger->info('Synced Pokémon @name.', ['@name' => $name]);
  $logger->error('Sync failed for ID @id.', ['@id' => $id]);
}
```

---

## Drush Commands

### Use Drush 12+ Attribute-Based Commands

**Rule**: Define Drush commands using PHP 8 attributes (Drush 12+ style). Do not use the legacy `$items` array annotation style.

**✅ Good:**

```php
namespace Drupal\deploya_pokemon\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for the deploya_pokemon module.
 */
class PokemonCommands extends DrushCommands {

  public function __construct(
    protected PokemonSyncService $syncService,
  ) {
    parent::__construct();
  }

  /**
   * Syncs Pokémon data from the PokeAPI.
   */
  #[CLI\Command(name: 'deploya:pokemon-sync', aliases: ['dps'])]
  #[CLI\Option(name: 'id', description: 'Sync a single Pokémon by Pokédex number.')]
  #[CLI\Usage(name: 'deploya:pokemon-sync', description: 'Sync all Pokémon.')]
  #[CLI\Usage(name: 'deploya:pokemon-sync --id=25', description: 'Sync only Pikachu.')]
  public function sync(array $options = ['id' => NULL]): void {
    if ($options['id']) {
      $this->syncService->syncById((int) $options['id']);
      $this->logger()->success(dt('Synced Pokémon #@id.', ['@id' => $options['id']]));
    }
    else {
      $this->syncService->enqueueSyncAll();
      $this->logger()->success(dt('All Pokémon queued for sync.'));
    }
  }

}
```

---

## JSON:API / Decoupled API

### Use Config to Control JSON:API Exposure

**Rule**: Control which fields are exposed via JSON:API through configuration YAML, not in PHP. Place configs in `config/install/`.

```yaml
# config/install/jsonapi.resource_type.node--pokemon.yml
resourceType: node--pokemon
entityType: node
bundle: pokemon
disabled: false
fields:
  field_pokemon_types:
    disabled: false
    publicName: types
  field_pokemon_sprite:
    disabled: false
    publicName: sprite
```

### Never Expose Sensitive Data

**Rule**: Always review which fields are included in JSON:API responses. Internal fields (`uid`, revision fields, internal metadata) should be explicitly disabled if not needed by the Next.js frontend.

---

## Security

### Always Use `accessCheck()` in Entity Queries

**Rule**: Always specify `->accessCheck(TRUE)` or `->accessCheck(FALSE)` explicitly in entity queries. Never rely on the default — it varies by Drupal version.

**❌ Bad:**

```php
$query = $this->entityTypeManager->getStorage('node')->getQuery()
  ->condition('type', 'pokemon');
```

**✅ Good:**

```php
$query = $this->entityTypeManager->getStorage('node')->getQuery()
  ->accessCheck(FALSE) // OK for internal sync processes
  ->condition('type', 'pokemon');
```

### Sanitize Output in Twig

**Rule**: Never pass raw HTML from a module to a Twig template. Use `Markup::create()` only when you fully control and trust the source. For user content, always use `{{ variable }}` (auto-escaped by Twig).

---

## Testing

### Write Kernel Tests for Services

**Rule**: Every service class should have a corresponding Kernel test in `tests/src/Kernel/`.

```
deploya_pokemon/
└── tests/
    └── src/
        ├── Unit/
        │   └── PokemonNormalizerTest.php
        └── Kernel/
            └── PokemonSyncServiceTest.php
```

**✅ Good:**

```php
namespace Drupal\Tests\deploya_pokemon\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the PokemonSyncService.
 *
 * @group deploya_pokemon
 */
class PokemonSyncServiceTest extends KernelTestBase {

  protected static $modules = ['deploya_pokemon', 'node', 'user'];

  public function testSyncById(): void {
    // ...
  }

}
```

---

## Module File Structure

Every deploya custom module follows this structure:

```
deploya_[name]/
├── deploya_[name].info.yml         ← Module metadata
├── deploya_[name].module           ← Schema/install hooks only (hook_schema, hook_install, hook_update_N)
├── deploya_[name].services.yml     ← Service definitions (not needed for Hook classes — auto-registered)
├── deploya_[name].routing.yml      ← Routes (if any)
├── deploya_[name].permissions.yml  ← Permissions (if any)
├── deploya_[name].install          ← hook_schema, hook_install, updates
├── config/
│   └── install/                    ← Default configuration YAML
├── src/
│   ├── Commands/                   ← Drush commands
│   ├── Form/                       ← Drupal forms
│   ├── Hook/                       ← OOP Hook classes (auto-registered, no services.yml needed)
│   │   ├── PokemonHooks.php        ← cron, node presave, entity events
│   │   ├── FormHooks.php           ← form_alter implementations
│   │   └── ThemeHooks.php          ← preprocess / theme hooks
│   ├── Plugin/
│   │   └── QueueWorker/            ← Queue workers
│   └── Service/                    ← Business logic services
└── tests/
    └── src/
        ├── Unit/
        └── Kernel/
```

---

## Workflow

### After Making Module Changes

**Rule**: After completing any changes to a custom module, always:

1. **Clear caches**: `drush cr`
2. **Check for config drift**: `drush cst` (config status must be empty or intentional)
3. **Run any pending updates**: `drush updb -y`
4. **Export config if changed**: `drush cex -y`

```bash
drush cr
drush cst
drush updb -y
drush cex -y
```

---

## Summary

1. **Follow Drupal coding standards** — 2-space indent, DocBlocks, proper naming
2. **PSR-4 autoloading** — all classes under `src/` with correct namespaces
3. **No static `\Drupal::` in classes** — use constructor dependency injection
4. **No raw SQL** — use entity queries or parameterized DB queries
5. **No direct HTTP calls** — use `@http_client` (Guzzle) with try/catch
6. **Use Queue API for bulk operations** — never bulk-call external APIs in one go
7. **Config in YAML** — never hard-code configuration in PHP
8. **Hooks as OOP classes** — use `#[Hook]` attribute in `src/Hook/` (Drupal 11.1+); `.module` only for `hook_schema`, `hook_install`, `hook_update_N`
9. **Use logger factory** — never use `error_log()` or `print`
10. **Drush 12+ attributes** — use PHP attribute syntax for commands
11. **`accessCheck()` always explicit** — never rely on default in entity queries
12. **Run `drush cr` and `drush cex` after changes**
