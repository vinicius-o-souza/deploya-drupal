# Drupal Coding Standards for AI Code Generation

**For AI agents only.**

This document consolidates all coding standards, conventions, and rules that AI agents must follow when generating Drupal module code. It is tool-agnostic and applies to any Drupal 10.2+ / 11+ project using PHP 8.2+.

---

## Table of Contents

1. [File Structure & Autoloading](#1-file-structure--autoloading)
2. [PHP File Format](#2-php-file-format)
3. [Coding Style](#3-coding-style)
4. [Docblocks](#4-docblocks)
5. [PHP Attributes](#5-php-attributes)
6. [Dependency Injection](#6-dependency-injection)
7. [Services](#7-services)
8. [Hooks](#8-hooks)
9. [Entity Queries & Database](#9-entity-queries--database)
10. [HTTP & External APIs](#10-http--external-apis)
11. [Configuration](#11-configuration)
12. [Error Handling & Logging](#12-error-handling--logging)
13. [Queue API](#13-queue-api)
14. [Drush Commands](#14-drush-commands)
15. [Testing](#15-testing)
16. [PHPStan — Static Analysis](#16-phpstan--static-analysis)
17. [PHPMetrics — Maintainability](#17-phpmetrics--maintainability)
18. [Security](#18-security)
19. [Post-Change Workflow](#19-post-change-workflow)

---

## 1. File Structure & Autoloading

### PSR-4 Autoloading

All PHP classes must follow PSR-4. The namespace root for a module `my_module` is `Drupal\my_module`, mapping to `my_module/src/`.

```
my_module/
├── my_module.info.yml
├── my_module.services.yml
├── my_module.install
├── my_module.module                  ← Only for hooks not yet supported as OOP
├── config/
│   └── install/
│       └── my_module.settings.yml
├── tests/
│   ├── fixtures/                     ← JSON fixtures for API response mocks
│   └── src/
│       ├── Unit/
│       └── Kernel/
└── src/
    ├── Exception/
    ├── Service/
    ├── Resource/                     ← DTOs / value objects
    ├── Hook/                         ← OOP hook classes
    ├── Plugin/
    │   ├── Block/
    │   ├── QueueWorker/
    │   └── Field/
    ├── Form/
    └── Drush/Commands/
```

### Namespace Examples

```
src/Service/SyncService.php         → Drupal\my_module\Service\SyncService
src/Drush/Commands/MyCommands.php   → Drupal\my_module\Drush\Commands\MyCommands
src/Hook/MyModuleHooks.php          → Drupal\my_module\Hook\MyModuleHooks
src/Form/SettingsForm.php           → Drupal\my_module\Form\SettingsForm
```

---

## 2. PHP File Format

Every PHP file must follow this exact header format:

```php
<?php

declare(strict_types=1);

namespace Drupal\my_module\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
// ... other imports ...

/**
 * Class docblock — one-sentence summary ending with a full stop.
 */
class MyService {
```

**Rules:**

| Rule | Requirement |
|---|---|
| Opening tag | `<?php` on line 1 |
| Blank line | Line 2 is blank |
| Strict types | `declare(strict_types=1);` on line 3 |
| Namespace | After blank line following `declare` |
| Use statements | After namespace, grouped and sorted alphabetically |
| File ending | All PHP files must end with a newline |

---

## 3. Coding Style

These rules enforce compliance with `phpcs --standard=Drupal,DrupalPractice`.

| Rule | Requirement |
|---|---|
| Indentation | 2 spaces (no tabs) |
| Opening braces — control structures | Same line (`if (...) {`) |
| Opening braces — classes/functions | New line |
| Blank lines | One blank line between method definitions; no blank line after opening brace |
| Trailing commas | Use in all multi-line arrays and argument lists |
| `TRUE` / `FALSE` / `NULL` | Always uppercase in non-type-hint positions (e.g. `return NULL;`, `=== FALSE`) |
| String concatenation | Space on both sides of `.` operator (`$a . $b`, not `$a.$b`) |
| Short closures | Prefer `static fn` over anonymous `function` for `array_map` / `array_filter` callbacks |
| Class declarations | Use `final` where possible; mark classes as `readonly` when applicable |

**Example:**

```php
<?php

declare(strict_types=1);

namespace Drupal\my_module\Service;

/**
 * Provides sync operations.
 */
final class SyncService {

  /**
   * Syncs a single item by ID.
   *
   * @param int $id
   *   The item ID.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function syncById(int $id): bool {
    if ($id <= 0) {
      return FALSE;
    }

    $names = array_map(
      static fn(array $item): string => $item['name'],
      $items,
    );

    return TRUE;
  }

}
```

---

## 4. Docblocks

Every class, interface, method, and property requires a docblock.

### Format

```php
/**
 * One-sentence summary ending with a full stop.
 *
 * Optional longer description.
 *
 * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
 *   The entity type manager.
 * @param int $id
 *   The item ID.
 *
 * @return \Drupal\node\NodeInterface
 *   The loaded or created node.
 *
 * @throws \Drupal\my_module\Exception\MyException
 *   When the item cannot be found.
 */
```

**Rules:**

- First line: one-sentence summary ending with a full stop.
- Parameter and return tags: use fully-qualified class names prefixed with `\`.
- `{@inheritdoc}`: use only on methods that add no new information. If you override behaviour, write a full docblock.
- Plugin metadata (annotations) must NOT appear in docblocks — use PHP attributes instead.
- Class docblocks describe what the class does; they never contain `@QueueWorker`, `@Block`, etc.

---

## 5. PHP Attributes

Drupal 10.2+ / 11+ uses PHP 8 attributes as the canonical way to declare plugins, hooks, and test metadata. **Always use attributes — never docblock annotations.**

### Plugin Attributes

| Plugin | Attribute class | Import |
|---|---|---|
| QueueWorker | `#[QueueWorker(...)]` | `Drupal\Core\Queue\Attribute\QueueWorker` |
| Block | `#[Block(...)]` | `Drupal\Core\Block\Attribute\Block` |
| FieldType | `#[FieldType(...)]` | `Drupal\Core\Field\Attribute\FieldType` |
| FieldWidget | `#[FieldWidget(...)]` | `Drupal\Core\Field\Attribute\FieldWidget` |
| FieldFormatter | `#[FieldFormatter(...)]` | `Drupal\Core\Field\Attribute\FieldFormatter` |

**Rules:**

- Place the attribute immediately before the `class` keyword, after the docblock.
- Use named argument syntax: `id: '...'`, not positional.
- Replace `@Translation("...")` with `new TranslatableMarkup('...')` — import `Drupal\Core\StringTranslation\TranslatableMarkup`.
- Replace `{"key" = value}` annotation arrays with `['key' => value]` PHP arrays.

```php
use Drupal\Core\Queue\Attribute\QueueWorker;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Processes sync queue items.
 */
#[QueueWorker(
  id: 'my_module_sync',
  title: new TranslatableMarkup('My sync worker'),
  cron: ['time' => 30],
)]
final class SyncWorker extends QueueWorkerBase {}
```

### Hook Attributes

```php
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Implements hook_cron().
 */
#[Hook('cron')]
public function cron(): void {
  // ...
}
```

### PHPUnit Attributes

| Annotation (deprecated) | Attribute (required) | Import |
|---|---|---|
| `@group module` | `#[Group('module')]` | `PHPUnit\Framework\Attributes\Group` |
| `@coversDefaultClass \Foo` | `#[CoversClass(Foo::class)]` | `PHPUnit\Framework\Attributes\CoversClass` |
| `@covers ::method` | `#[CoversMethod(Foo::class, 'method')]` | `PHPUnit\Framework\Attributes\CoversMethod` |
| `@dataProvider name` | `#[DataProvider('name')]` | `PHPUnit\Framework\Attributes\DataProvider` |

- `#[CoversClass]` uses `::class` constant — never a string.
- `#[Group]` and `#[CoversClass]` go on the class; `#[CoversMethod]` and `#[DataProvider]` go on methods.

```php
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the sync service.
 */
#[CoversClass(SyncService::class)]
#[Group('my_module')]
final class SyncServiceTest extends UnitTestCase {

  #[CoversMethod(SyncService::class, 'syncById')]
  public function testSyncById(): void {
    // ...
  }

}
```

---

## 6. Dependency Injection

### Never Use Static `\Drupal::` Calls Inside Classes

All dependencies must be injected through the constructor. Static `\Drupal::` calls are only acceptable in `.module` files and procedural contexts.

```php
// WRONG
public function sync(): void {
  $client = \Drupal::httpClient();
  $logger = \Drupal::logger('my_module');
}

// CORRECT
public function __construct(
  protected ClientInterface $httpClient,
  protected EntityTypeManagerInterface $entityTypeManager,
  protected LoggerChannelFactoryInterface $loggerFactory,
) {}
```

### Plugin Classes Use `create()`

For plugin classes (Blocks, QueueWorkers, Forms), use the `create()` static factory method:

```php
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class MyPlugin extends PluginBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected MyServiceInterface $myService,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('my_module.my_service'),
    );
  }

}
```

### General DI Rules

- Depend on interfaces, not concrete classes.
- Avoid `new ClassName()` inside services — use DI.
- Never use `Node::create()` directly — use `$this->entityTypeManager->getStorage('node')->create(...)`.

---

## 7. Services

### Define All Services in `*.services.yml`

Every custom service, event subscriber, queue worker, and command must be declared in the module's `*.services.yml`.

```yaml
services:
  my_module.sync_service:
    class: Drupal\my_module\Service\SyncService
    arguments:
      - '@http_client'
      - '@entity_type.manager'
      - '@logger.factory'

  my_module.commands:
    class: Drupal\my_module\Drush\Commands\MyCommands
    arguments:
      - '@my_module.sync_service'
    tags:
      - { name: drush.command }
```

### Interface for Every Service

Every concrete service should implement a corresponding interface. This keeps the abstract/concrete ratio healthy for PHPMetrics and improves testability.

---

## 8. Hooks

### OOP Hook Classes (Drupal 11.1+)

Implement hooks as methods in dedicated classes inside `src/Hook/`, using the `#[Hook]` attribute. Do **not** add new hook implementations to `.module` files.

Hook classes in `src/Hook/` are **automatically registered as autowired services** — no `*.services.yml` entry is needed.

```php
<?php

declare(strict_types=1);

namespace Drupal\my_module\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\my_module\Service\SyncServiceInterface;
use Drupal\node\NodeInterface;

/**
 * Hook implementations for the my_module module.
 */
class MyModuleHooks {

  /**
   * Constructs a MyModuleHooks instance.
   *
   * @param \Drupal\my_module\Service\SyncServiceInterface $syncService
   *   The sync service.
   */
  public function __construct(
    protected SyncServiceInterface $syncService,
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
  public function nodePresave(NodeInterface $node): void {
    if ($node->bundle() === 'my_type') {
      $this->syncService->normalize($node);
    }
  }

}
```

**Multiple hooks on one method:**

```php
#[Hook('entity_insert')]
#[Hook('entity_update')]
public function entitySave(EntityInterface $entity): void {
  // Fires on both insert and update.
}
```

### Organising Hook Classes

Group by domain, not by hook type:

```
src/Hook/
├── MyModuleHooks.php    ← cron, entity events
├── FormHooks.php        ← form_alter implementations
└── ThemeHooks.php       ← theme, preprocess hooks
```

### What Still Goes in `.module`

Only keep `.module` for hooks not yet supported as OOP:

- `hook_schema()` — runs before full container bootstrap
- `hook_install()` / `hook_uninstall()` / `hook_update_N()` — install-time hooks

---

## 9. Entity Queries & Database

### Entity Queries (Preferred)

```php
$nids = $this->entityTypeManager
  ->getStorage('node')
  ->getQuery()
  ->accessCheck(FALSE)
  ->condition('type', 'my_type')
  ->condition('field_my_id', $id)
  ->execute();
```

### Always Specify `accessCheck()`

Never rely on the default — it varies by Drupal version:

```php
// WRONG — implicit access check
$query = $storage->getQuery()->condition('type', 'my_type');

// CORRECT — explicit
$query = $storage->getQuery()
  ->accessCheck(FALSE)  // OK for internal/sync processes
  ->condition('type', 'my_type');
```

### Parameterized Queries (When Entity Query Is Insufficient)

```php
$result = $this->database->select('my_table', 't')
  ->fields('t', ['data', 'updated'])
  ->condition('t.item_id', $id)
  ->execute()
  ->fetchAssoc();
```

**Never concatenate variables into raw SQL:**

```php
// NEVER DO THIS
$result = $this->database->query("SELECT * FROM node WHERE title = '" . $title . "'");
```

### Custom Tables Use `hook_schema()`

Define all custom database tables in `hook_schema()` inside the `.install` file. Never create tables manually.

### Entity Type Narrowing

Always add `@var` annotations after `load()` or `create()` calls:

```php
$storage = $this->entityTypeManager->getStorage('node');

/** @var \Drupal\node\NodeInterface $node */
$node = $storage->load((int) reset($existing));

/** @var \Drupal\node\NodeInterface $node */
$node = $storage->create(['type' => 'my_type']);
```

### Upsert Pattern

Always check for existing entities before creating new ones:

```php
/**
 * Loads an existing node by external ID, or creates a new one.
 *
 * @param int $externalId
 *   The external identifier.
 *
 * @return \Drupal\node\NodeInterface
 *   An existing or newly created (unsaved) node.
 */
private function loadOrCreateNode(int $externalId): \Drupal\node\NodeInterface {
  $storage = $this->entityTypeManager->getStorage('node');

  $existing = $storage->getQuery()
    ->accessCheck(FALSE)
    ->condition('type', 'my_type')
    ->condition('field_external_id', $externalId)
    ->execute();

  if (!empty($existing)) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $storage->load((int) reset($existing));
    return $node;
  }

  /** @var \Drupal\node\NodeInterface $node */
  $node = $storage->create(['type' => 'my_type']);
  return $node;
}
```

### Taxonomy Term Upsert

```php
/**
 * Returns the term ID for a given vocabulary term, creating it if necessary.
 *
 * @param string $name
 *   The term name.
 * @param string $vocabulary
 *   The vocabulary machine name.
 *
 * @return int
 *   The taxonomy term ID.
 */
private function upsertTerm(string $name, string $vocabulary): int {
  $storage = $this->entityTypeManager->getStorage('taxonomy_term');

  $existing = $storage->getQuery()
    ->accessCheck(FALSE)
    ->condition('vid', $vocabulary)
    ->condition('name', $name)
    ->execute();

  if (!empty($existing)) {
    return (int) reset($existing);
  }

  /** @var \Drupal\taxonomy\TermInterface $term */
  $term = $storage->create([
    'vid' => $vocabulary,
    'name' => $name,
  ]);
  $term->save();

  return (int) $term->id();
}
```

**PHPStan notes:**

- `getQuery()->execute()` returns `int[]|string[]` — cast with `(int)` before passing to `load()`.
- `load()` returns `EntityInterface|null` — annotate with `@var`.
- `TermInterface::id()` returns `int|string|null` — cast to `(int)`.
- Do not chain `->create()->save()` — split into two statements for PHPStan.

---

## 10. HTTP & External APIs

### Use Drupal's HTTP Client

Always inject `\GuzzleHttp\ClientInterface` (`@http_client`). Never use `file_get_contents()` or `curl_*`.

```php
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Fetches data from the external API.
 *
 * @param int $id
 *   The item ID.
 *
 * @return array<string, mixed>
 *   The decoded API response.
 *
 * @throws \Drupal\my_module\Exception\MyException
 *   When the API request fails.
 */
public function fetch(int $id): array {
  try {
    $response = $this->httpClient->get("{$this->baseUrl}/items/{$id}");
    return json_decode((string) $response->getBody(), TRUE);
  }
  catch (RequestException $e) {
    $this->logger->error('Failed to fetch item @id: @message', [
      '@id' => $id,
      '@message' => $e->getMessage(),
    ]);
    throw new MyException("Failed to fetch item {$id}.", 0, $e);
  }
}
```

### Use DTOs Across Module Boundaries

Never pass raw `array` API responses across module boundaries. Use typed Data Transfer Objects.

---

## 11. Configuration

### Store Config in `config/install/` YAML

All module configuration must live in YAML files under `config/install/`. Never hard-code values in PHP.

```yaml
# config/install/my_module.settings.yml
base_url: 'https://api.example.com/v2'
sync_limit: 100
```

### Reading Config in PHP

```php
$config = $this->configFactory->get('my_module.settings');
$baseUrl = $config->get('base_url');
```

---

## 12. Error Handling & Logging

### Use the Logger Factory

Always use Drupal's logger service. Never use `error_log()`, `print`, or `var_dump()`.

```php
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

public function __construct(
  protected LoggerChannelFactoryInterface $loggerFactory,
) {}

public function doSomething(): void {
  $logger = $this->loggerFactory->get('my_module');
  $logger->info('Synced item @name.', ['@name' => $name]);
  $logger->error('Failed to sync ID @id.', ['@id' => $id]);
}
```

### Typed Exceptions

Use typed, module-specific exception classes:

```php
<?php

declare(strict_types=1);

namespace Drupal\my_module\Exception;

/**
 * Thrown when an API request fails.
 */
final class MyException extends \RuntimeException {}
```

- Keep exception classes minimal — no extra methods or properties.
- Never return `NULL` from repository methods — throw instead.
- Catch Guzzle `RequestException` and rethrow as your typed exception.

---

## 13. Queue API

### Use Queue for Bulk Operations

Never perform bulk external API calls in a single request or cron run.

**Enqueue items:**

```php
public function enqueueSyncAll(): void {
  $queue = $this->queueFactory->get('my_module_sync');
  for ($i = 1; $i <= $limit; $i++) {
    $queue->createItem(['item_id' => $i]);
  }
}
```

**Process in QueueWorker:**

```php
public function processItem(mixed $data): void {
  $this->syncService->syncById((int) $data['item_id']);
}
```

---

## 14. Drush Commands

### Use Drush 12+ Attribute-Based Commands

```php
<?php

declare(strict_types=1);

namespace Drupal\my_module\Drush\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for the my_module module.
 */
class MyCommands extends DrushCommands {

  /**
   * Constructs a MyCommands instance.
   *
   * @param \Drupal\my_module\Service\SyncServiceInterface $syncService
   *   The sync service.
   */
  public function __construct(
    protected SyncServiceInterface $syncService,
  ) {
    parent::__construct();
  }

  /**
   * Syncs data from the external API.
   */
  #[CLI\Command(name: 'my_module:sync', aliases: ['mms'])]
  #[CLI\Option(name: 'id', description: 'Sync a single item by ID.')]
  #[CLI\Usage(name: 'my_module:sync', description: 'Sync all items.')]
  #[CLI\Usage(name: 'my_module:sync --id=25', description: 'Sync item 25 only.')]
  public function sync(array $options = ['id' => NULL]): void {
    if ($options['id']) {
      $this->syncService->syncById((int) $options['id']);
      $this->logger()->success(dt('Synced item #@id.', ['@id' => $options['id']]));
    }
    else {
      $this->syncService->enqueueSyncAll();
      $this->logger()->success(dt('All items queued for sync.'));
    }
  }

}
```

---

## 15. Testing

### Test Structure

```
tests/
├── fixtures/                    ← JSON fixtures for API response mocks
└── src/
    ├── Unit/                    ← Pure unit tests (no Drupal bootstrap)
    │   └── MyServiceTest.php
    └── Kernel/                  ← Kernel tests (services, DB, entity queries)
        └── SyncServiceTest.php
```

### Test Class Example

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\my_module\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\my_module\Service\MyService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the MyService class.
 */
#[CoversClass(MyService::class)]
#[Group('my_module')]
final class MyServiceTest extends UnitTestCase {

  #[CoversMethod(MyService::class, 'process')]
  public function testProcess(): void {
    // ...
  }

}
```

### Test Rules

- Test classes must have a class-level docblock.
- Helper methods must be `private`; they still require a docblock.
- `file_get_contents()` returns `string|false` — cast to `(string)` or assert before use.
- Store API response fixtures as JSON in `tests/fixtures/`.

---

## 16. PHPStan -- Static Analysis

These rules ensure code passes `phpstan analyze` cleanly.

| Rule | Requirement |
|---|---|
| Return types | Every method must declare a return type, including `void`. No `mixed` unless unavoidable. |
| Typed properties | All class properties must be typed. Constructor-promoted properties inherit the type. |
| Nullable types | Use `?Type` syntax, not `Type\|null`, for single-nullable parameters. |
| Array shapes | Annotate complex arrays with `@param array<KeyType, ValueType>` or `@var` docblocks. |
| Null-safe calls | Use `$obj?->method()` where value may be `null`. Never chain when intermediate is non-null by contract. |
| Entity type narrowing | Always `@var` after `load()` / `create()` — PHPStan cannot narrow `EntityInterface` automatically. |
| `CacheBackendInterface::get()` | Returns `object\|false`. Compare with `!== FALSE`, not `!= NULL` or truthiness. |
| `getQuery()->execute()` | Returns `int[]\|string[]`. Cast with `(int)` before `load()`. |
| `TermInterface::id()` | Returns `int\|string\|null`. Always cast to `(int)`. |

---

## 17. PHPMetrics -- Maintainability

| Metric | Limit | How to comply |
|---|---|---|
| Cyclomatic complexity / method | ≤ 5 | Extract nested conditionals into private helpers. |
| Lines of code / method | ≤ 20 | Extract helpers or introduce value objects. |
| Methods / class | ≤ 10 | Split large services into focused collaborators. |
| Coupling between objects | Low | Depend on interfaces, not concrete classes. Use DI. |
| Lack of cohesion (LCOM) | Low | One reason to change per class. Split if methods share no properties. |
| Abstract / concrete ratio | ≥ 0.2 | Every concrete service implements a corresponding interface. |

---

## 18. Security

### Entity Query Access Checks

Always specify `->accessCheck(TRUE)` or `->accessCheck(FALSE)` explicitly.

- `accessCheck(FALSE)`: internal/sync processes with no user context.
- `accessCheck(TRUE)`: any query that surfaces data to a user.

### Output Sanitization

- Never pass raw HTML to Twig templates.
- Use `Markup::create()` only when you fully control the source.
- Use `{{ variable }}` in Twig (auto-escaped).

### SQL Injection Prevention

- Never concatenate variables into SQL strings.
- Use entity queries or parameterized DB API queries.


---

## 19. Post-Change Workflow

After completing any changes to a custom module:

```bash
drush cr              # Clear caches
drush updb -y         # Run any pending updates
drush cex -y          # Export config if changed
```

---

## Quick Reference Checklist

1. `<?php` + blank line + `declare(strict_types=1);` at top of every file
2. 2-space indentation, no tabs
3. Docblocks on all classes, methods, properties
4. PHP attributes for plugins, hooks, and PHPUnit metadata — never annotations
5. Constructor DI — no `\Drupal::` in classes
6. All services declared in `*.services.yml`
7. Hooks as OOP classes in `src/Hook/` with `#[Hook]` attribute
8. `accessCheck()` always explicit in entity queries
9. Entity upsert pattern — always check before create
10. `@http_client` for HTTP — never `file_get_contents()` or `curl_*`
11. Queue API for bulk external operations
12. Config in YAML — never hard-coded in PHP
13. Logger factory — never `error_log()` or `print`
14. Typed exceptions — never return `NULL` from repositories
15. DTOs across module boundaries — never raw arrays
16. `@var` annotations after `load()` / `create()` for PHPStan
17. Methods ≤ 20 lines, ≤ 5 cyclomatic complexity
18. `drush cr` and `drush cex` after changes
