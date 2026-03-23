# CLAUDE.md — AI Agent Guide for the deploya Drupal Project

**For AI agents only.** Human contributors should use `README.md` instead.

This file provides complete context, architecture, conventions, and DDEV workflows for AI agents (Claude Code and others) working on this project. Read this file before writing any code.

> **Linked Agent Rule Files**
> - Theme coding standards → `public_html/themes/custom/deploya/Agents.md`
> - Custom module coding standards → `public_html/modules/custom/Agents.md`

---

## Project Overview

A **Drupal 11 / Drupal CMS 2.0** application serving as a personal portfolio and Drupal skills laboratory for the deploya brand. It demonstrates real-world Drupal development across several domains:

| Area | Purpose |
|---|---|
| Portfolio & Blog | Showcase projects, articles, and deploya brand identity |
| Pokémon Sync | Custom module integrating PokeAPI via REST; filterable listing |
| Commerce | Drupal Commerce orders (no payment gateway) |
| Decoupled API | JSON:API endpoints consumed by a Next.js frontend |

---

## Tech Stack

| Layer | Technology |
|---|---|
| CMS | Drupal 11 + Drupal CMS 2.0 |
| PHP | 8.4+ |
| Database | MySQL / MariaDB (managed by DDEV) |
| Web server | Nginx-FPM (managed by DDEV) |
| Dev environment | DDEV (Docker-based) |
| Theme base | Mercury Drupal Theme |
| Custom theme | `deploya` (SDC components, Tailwind CSS) |
| Component system | Drupal Canvas + SDC (Single Directory Components) |
| CSS framework | Tailwind CSS |
| Frontend decoupled | Next.js (consumes Drupal JSON:API) |
| API integration | PokeAPI (`https://pokeapi.co/`) via custom Drupal module |
| Commerce | Drupal Commerce (orders only, no payment gateway) |
| Dev tools | Composer, Drush 12+, Git, DDEV CLI |

---

## Directory Structure

```
/                                           ← Project root (run all DDEV commands here)
├── .ddev/
│   └── config.yaml
├── config/
│   └── sync/                               ← Drupal config export
├── public_html/                            ← Drupal docroot
│   ├── modules/
│   │   └── custom/
│   │       ├── Agents-Modules.md           ← Module coding standards
│   │       ├── deploya_pokemon/            ← Pokémon PokeAPI sync module
│   │       ├── deploya_api/                ← Decoupled API enhancements
│   │       └── deploya_commerce/           ← Commerce customizations
│   └── themes/
│       └── custom/
│           └── deploya/
│               ├── Agents.md               ← Theme coding standards
│               ├── components/             ← SDC components
│               └── ...
└── CLAUDE.md                               ← This file
```

> **Important**: The docroot is `public_html`. All DDEV commands run from the project root. Use `ddev drush` or `ddev exec drush` — never bare `drush`.

---

## Content Types

### `page` — Utility Page
Static content (About, Contact).

### `article` — Blog Post
Fields: title, body, tags, image, published date. Exposed via JSON:API.

### `portfolio_case` — Portfolio Case
Fields: title, summary, body, technologies (taxonomy), screenshots, external URL. Exposed via JSON:API.

### `pokemon` — Pokémon
Managed by `deploya_pokemon` module. **Do not edit manually** — data is synced from PokeAPI.
- Fields: name, pokedex_number (integer), types (entity reference → taxonomy), sprite (image), stats (paragraph or JSON field), height, weight, flavor_text
- Filterable by Type via Views + exposed filters
- Exposed via JSON:API

### `product` — Commerce Product
Managed by Drupal Commerce. Product catalog: **Drupal Development Services** (consulting packages — Basic, Standard, Premium). Orders only, no payment gateway.

---

## Taxonomy Vocabularies

| Vocabulary | Machine name | Used by |
|---|---|---|
| Tags | `tags` | Blog articles |
| Technologies | `technologies` | Portfolio cases |
| Pokémon Type | `pokemon_type` | Pokémon content type |

---

## Custom Modules

### `deploya_pokemon`
Syncs Pokémon data from `https://pokeapi.co/api/v2/`.

- Drush command `deploya:pokemon-sync` (full or `--id`)
- Queue-based processing (`QueueWorkerInterface`) — one item per Pokémon
- HTTP via Guzzle (`@http_client`) with try/catch
- Creates/updates `pokemon` nodes and `pokemon_type` terms
- Caches raw API response in `deploya_pokemon_cache` custom DB table

### `deploya_api`
Configures and extends the decoupled API layer.

- JSON:API resource config (allowed fields, includes)
- CORS configuration for Next.js
- Simple OAuth 2.0 (client credentials for Next.js)

### `deploya_commerce`
Customizations on top of Drupal Commerce core.

- Product display configuration
- Order workflow customization
- Custom order receipt email template

---

## Decoupled / Next.js Integration

- **Format**: JSON:API (primary)
- **Auth**: Simple OAuth 2.0 (client credentials)
- **Key endpoints**:
  - `GET /jsonapi/node/article` — Blog posts
  - `GET /jsonapi/node/portfolio_case` — Portfolio cases
  - `GET /jsonapi/node/pokemon?filter[field_pokemon_type.name]=Fire` — Pokémon by type
  - `GET /jsonapi/commerce_product/default` — Commerce products
- **Images**: Drupal image styles exposed via JSON:API `links`
- **Preview**: Drupal Content Preview with Next.js draft mode

---

## DDEV Environment

### Prerequisites
```bash
ddev --version   # Verify DDEV is installed
```

### Initial Setup
```bash
git clone git@github.com:vinicius-o-souza/deploya-drupal.git deploya
cd deploya

ddev config --project-type=drupal11 --docroot=public_html
ddev start
ddev composer install
ddev drush cr
ddev launch
```

### `.ddev/config.yaml`
```yaml
type: drupal
docroot: public_html
php_version: "8.4"
webserver_type: nginx-fpm
router_http_port: "80"
router_https_port: "443"
xdebug_enabled: false

web_environment:
  - DRUSH_OPTIONS_URI=https://deploya.ddev.site
```

### Essential DDEV Commands
```bash
# Environment lifecycle
ddev start | stop | restart | delete

# Database snapshots
ddev snapshot                    # Create snapshot before risky changes
ddev restore-snapshot
ddev import-db / ddev export-db

# Utilities
ddev ssh                         # SSH into web container
ddev logs                        # Container logs
ddev describe                    # Environment details and URLs
ddev launch                      # Open site in browser
```

---

## Common Drupal Workflows

```bash
# Add a contrib module
ddev composer require drupal/<project>
ddev drush pm:enable --yes <module_machine_name>
ddev drush cr

# Apply database updates after code changes
ddev drush updatedb --yes

# Configuration management
ddev drush config:import --yes          # Import repo config into running site
ddev drush config:export --yes          # Export site config back to repo

# Pokémon sync
ddev drush deploya:pokemon-sync         # Enqueue all Pokémon
ddev drush deploya:pokemon-sync --id=25 # Sync only Pikachu
ddev drush queue:run deploya_pokemon_sync

# Theme assets (run from theme directory inside DDEV)
ddev exec bash -c "cd public_html/themes/custom/deploya && npm run format && npm run build"
```

---

## Guardrails

- **Never commit**: `.env`, `settings.local.php`, `.ddev/config.local.yaml`, `vendor/`, `public_html/sites/*/files`
- **Never edit**: Drupal core or contributed modules/themes in place
- **Custom code only in**: `public_html/modules/custom/` and `public_html/themes/custom/`
- **Never use `\Drupal::` static calls inside classes** — use dependency injection
- **Never write raw SQL strings** — use entity queries or parameterized DB queries
- **Never bulk-call external APIs synchronously** — use the Queue API

---

## Code Style and Standards

Adhere to [Drupal coding standards](https://www.drupal.org/docs/develop/standards) (PSR-12 with Drupal extensions). Enforce with Coder + PHPCS + PHPStan + PHPUnit.

### PHP
- 2-space indentation (no tabs)
- Line length ≤ 80 characters
- CamelCase for classes/methods; snake_case for variables/functions
- Always use braces; prefer early returns
- Full PHPDoc blocks with `@param`, `@return`, `@throws`

### YAML
- 2-space indentation, lowercase keys

### Twig
- `{{ }}` for output, `{% %}` for logic
- Always escape untrusted output with `|e`
- No inline conditionals in `class` attributes — use CVA (see `Agents.md`)

### Linting
```bash
ddev exec vendor/bin/phpcs --standard=Drupal,DrupalPractice --extensions=php,module,install,info,yml public_html/modules/custom/
ddev exec vendor/bin/phpstan
ddev exec vendor/bin/phpunit
```

**Reject any code that fails Drupal Coder sniffs.**

---

## Drupal Development Patterns

### Services & Dependency Injection

- Define services in `modulename.services.yml`
- Inject via constructor; never use static `\Drupal::` calls inside classes
- For plugins (Blocks, QueueWorkers, Forms), use `create()` static factory

```php
// ✅ Constructor injection
public function __construct(
  protected ClientInterface $httpClient,
  protected EntityTypeManagerInterface $entityTypeManager,
  protected LoggerChannelFactoryInterface $loggerFactory,
) {}

// ✅ Static calls only acceptable in .module procedural hooks
function deploya_pokemon_cron(): void {
  \Drupal::service('deploya_pokemon.sync_service')->enqueueSyncAll();
}
```

### Entity API & Queries

Always specify `->accessCheck()` explicitly — never rely on the default.

```php
// ✅ Entity query
$nids = $this->entityTypeManager
  ->getStorage('node')
  ->getQuery()
  ->accessCheck(FALSE)          // explicit — FALSE is OK for internal sync
  ->condition('type', 'pokemon')
  ->condition('title', $name)
  ->execute();

// ✅ Parameterized DB query (when entity query is insufficient)
$result = $this->database->select('deploya_pokemon_cache', 'c')
  ->fields('c', ['data', 'updated'])
  ->condition('c.pokemon_id', $id)
  ->execute()
  ->fetchAssoc();
```

### Plugin System

- Place plugins in `src/Plugin/Type/` (e.g., `src/Plugin/QueueWorker/`)
- Extend appropriate base classes (`QueueWorkerBase`, `BlockBase`, etc.)
- Use `ContainerFactoryPluginInterface` + `create()` for DI

### Hooks (OOP, Drupal 11.1+)

Implement hooks as methods in dedicated classes under `src/Hook/`, using the `#[Hook]` attribute. Hook classes are **auto-registered as autowired services** — no `*.services.yml` entry needed. Do not add new hooks to `.module` files.

```php
// src/Hook/PokemonHooks.php
namespace Drupal\deploya_pokemon\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\deploya_pokemon\Service\PokemonSyncService;

class PokemonHooks {

  public function __construct(
    protected PokemonSyncService $syncService,
  ) {}

  #[Hook('cron')]
  public function cron(): void {
    $this->syncService->enqueueSyncAll();
  }

  #[Hook('node_presave')]
  public function nodePresave(NodeInterface $node): void {
    if ($node->bundle() === 'pokemon') {
      $this->syncService->normalize($node);
    }
  }

}
```

The `.module` file is kept only for hooks that cannot run as OOP classes: `hook_schema()`, `hook_install()`, `hook_uninstall()`, `hook_update_N()`.

### Forms API

- Extend `FormBase` for simple forms, `ConfigFormBase` for config forms
- Implement `validateForm()` and `submitForm()`
- Use `#ajax` for dynamic form behavior
- Use Drupal Form API render arrays — never raw `<form>` HTML

### Routes & Controllers

- Define routes in `modulename.routing.yml`
- Controllers extend `ControllerBase` in `src/Controller/`
- Apply `_permission` or custom access callbacks on all routes
- Convention: `modulename.action` for route names

### HTTP / External API (PokeAPI)

```php
// ✅ Always use @http_client (Guzzle) with exception handling
public function fetchPokemon(int $id): ?array {
  try {
    $response = $this->httpClient->get("https://pokeapi.co/api/v2/pokemon/{$id}");
    return json_decode((string) $response->getBody(), TRUE);
  }
  catch (RequestException $e) {
    $this->loggerFactory->get('deploya_pokemon')->error(
      'Failed to fetch Pokémon @id: @msg',
      ['@id' => $id, '@msg' => $e->getMessage()]
    );
    return NULL;
  }
}
```

### Queue API (Pokémon sync)

```php
// ✅ Enqueue — never bulk-call APIs synchronously
public function enqueueSyncAll(): void {
  $queue = $this->queueFactory->get('deploya_pokemon_sync');
  for ($i = 1; $i <= 151; $i++) {
    $queue->createItem(['pokemon_id' => $i]);
  }
}

// ✅ QueueWorker processes one item at a time
public function processItem(mixed $data): void {
  $this->syncService->syncById((int) $data['pokemon_id']);
}
```

### Batch API

Use for data migrations, bulk updates, or multi-step processes that risk PHP timeouts. Provides a real-time progress bar and processes data in chunks to prevent memory exhaustion.

### Configuration

```yaml
# config/install/deploya_pokemon.settings.yml
pokeapi_base_url: 'https://pokeapi.co/api/v2'
sync_limit: 151
```

```php
// ✅ Read via config factory
$baseUrl = $this->configFactory->get('deploya_pokemon.settings')->get('pokeapi_base_url');
```

### Logging

```php
// ✅ Inject @logger.factory — never error_log() or print
$this->loggerFactory->get('deploya_pokemon')->info('Synced @name.', ['@name' => $name]);
$this->loggerFactory->get('deploya_pokemon')->error('Failed ID @id.', ['@id' => $id]);
```

### Drush Commands (Drush 12+ — PHP attribute syntax)

```php
#[CLI\Command(name: 'deploya:pokemon-sync', aliases: ['dps'])]
#[CLI\Option(name: 'id', description: 'Pokédex number to sync.')]
#[CLI\Usage(name: 'deploya:pokemon-sync', description: 'Sync all Pokémon.')]
#[CLI\Usage(name: 'deploya:pokemon-sync --id=25', description: 'Sync Pikachu only.')]
public function sync(array $options = ['id' => NULL]): void {
  // ...
}
```

---

## Security & Performance

### Security Requirements

- **Sanitize input**: Use `#plain_text` for untrusted content
- **CSRF**: Include `#token` for forms with side effects
- **Permissions**: Access checks on all routes
- **SQL injection**: Entity queries or parameterized queries only
- **XSS**: `|e` in Twig; `#markup` only for fully trusted HTML
- **JSON:API**: Explicitly disable internal/sensitive fields in resource config

### Performance Best Practices

- **Render caching**: Add `#cache` with `tags` and `contexts` to all render arrays
- **Cache tags**: Entity tags (`node:123`) or list tags (`node_list`)
- **Cache contexts**: `user.roles` or other contexts for personalized content
- **Lazy loading**: `#lazy_builder` for expensive operations
- **Bulk loading**: Load multiple entities at once when possible
- **Queue for bulk**: Never call external APIs synchronously in bulk

---

## Debugging in DDEV

### Core Debugging Commands

| Command | Purpose |
|---|---|
| `ddev exec drush status` | Verify Drupal root, DB connection, Drush version |
| `ddev exec drush watchdog:show` | Read Drupal logs (`--severity=Error`, `--type=php`) |
| `ddev exec drush watchdog:delete all` | Clear watchdog when logs are large |
| `ddev exec drush php:eval "code"` | Run arbitrary PHP in Drupal context |

### Cache Debugging
```bash
ddev exec drush cr
ddev exec drush cache:get config:core.extension
ddev exec drush cache:clear render
```

### Configuration Debugging
```bash
ddev exec drush config:get system.site
ddev exec drush config:set system.site name "Test"
ddev exec drush config:export
ddev exec drush config:import
ddev exec drush config:delete <name>        # Remove orphaned config
```

### Module / Theme Debugging
```bash
ddev exec drush pm:list --type=module --status=enabled
ddev exec drush pm:enable <module>
ddev exec drush pm:uninstall <module>
ddev exec drush twig:debug                  # Template suggestions
```

### Database & Entity Debugging
```bash
ddev exec drush sql:connect
ddev exec drush sql:query "SELECT ..."
ddev exec drush entity:info
ddev exec drush php                         # Interactive PHP shell (Drupal bootstrapped)
ddev exec drush php:eval "var_dump(\Drupal::state()->get('system.cron_last'));"
```

### DDEV-Specific Debugging
```bash
# Enable Xdebug: set xdebug_enabled: true in .ddev/config.yaml, then ddev restart

ddev logs -f web                            # Follow web container logs
ddev logs -f db                             # Follow DB container logs
ddev describe                               # Full environment status

ddev exec tail -f /var/log/php/error.log
```

### Common DDEV Issues
```bash
# Won't start
ddev poweroff && ddev start

# Port conflict — edit .ddev/config.yaml
router_http_port: "8080"
router_https_port: "8443"

# PHP memory — add to .ddev/php/php.ini
memory_limit = 512M

# Composer memory
ddev exec php -d memory_limit=-1 /usr/local/bin/composer install
```

---

## Testing & Quality Assurance

Aim for ≥ 80% code coverage.

### Test Types

| Type | Base Class | Location | Speed | Use For |
|---|---|---|---|---|
| Unit | `UnitTestCase` | `tests/src/Unit/` | Fast | Service logic, utilities, transformations |
| Kernel | `KernelTestBase` | `tests/src/Kernel/` | Medium | Entity CRUD, config, service registration |
| Functional | `BrowserTestBase` | `tests/src/Functional/` | Slow | Forms, permissions, full page loads |

### Running Tests
```bash
# Full suite with coverage
ddev exec vendor/bin/phpunit -v --coverage-html coverage/

# By type
ddev exec vendor/bin/phpunit --testsuite unit
ddev exec vendor/bin/phpunit --testsuite kernel
ddev exec vendor/bin/phpunit --testsuite functional

# Specific module
ddev exec vendor/bin/phpunit public_html/modules/custom/deploya_pokemon/tests/

# Single test class
ddev exec vendor/bin/phpunit --filter PokemonSyncServiceTest
```

### Static Analysis
```bash
ddev exec vendor/bin/phpstan analyse
ddev exec composer audit                    # Security advisories
ddev exec vendor/bin/drupal-check           # Deprecated code
```

### Pre-Commit Checklist
```bash
ddev exec vendor/bin/phpcs --standard=Drupal,DrupalPractice --extensions=php,module,install,info,yml public_html/modules/custom/
ddev exec vendor/bin/phpstan
ddev exec vendor/bin/phpunit
ddev drush cr
ddev drush updatedb
```

---

## Version Control Workflow

- **Commit format**: `[#123456] Brief descriptive title`
- **Branch from**: `develop` for all features
- **Atomic commits**: One logical change per commit
- **Before pushing**: Linting and tests must pass

---

## Agent Workflow — Required Steps After Changes

### After theme changes:
```bash
ddev exec bash -c "cd public_html/themes/custom/deploya && npm run format && npm run build"
ddev drush cr
```

### After module changes:
```bash
ddev drush cr
ddev drush config:status                    # Must be clean or intentional
ddev drush updatedb --yes
ddev drush config:export --yes
```

### After any UI config changes:
```bash
ddev drush config:export --yes              # Always export so changes are tracked in Git
```

---

## Additional Resources

| Resource | URL |
|---|---|
| DDEV Docs | https://ddev.readthedocs.io |
| DDEV Drupal Guide | https://ddev.readthedocs.io/en/stable/users/topics/drupal/ |
| Drupal API | https://api.drupal.org |
| Drupal Coding Standards | https://www.drupal.org/docs/develop/standards |
| Drupal Security | https://www.drupal.org/docs/develop/security |
| PokeAPI Docs | https://pokeapi.co/docs/v2 |
| Drupal Answers | https://drupal.stackexchange.com |
