# CLAUDE.md — AI Agent Guide for the deploya Drupal Project

**For AI agents only.** Human contributors should use `README.md` instead.

This file provides project context, architecture, and DDEV workflows for AI agents (Claude Code and others) working on this project. Read this file before writing any code.

> **Coding Standards**: All coding standards, conventions, and patterns are in [`DRUPAL_CODING_STANDARDS.md`](DRUPAL_CODING_STANDARDS.md). Follow it for all code generation.

---

## Project Overview

A **Drupal 11 / Drupal CMS 2.0** application serving as a personal portfolio and Drupal skills laboratory for the deploya brand. It demonstrates real-world Drupal development across several domains:

| Area | Purpose |
|---|---|
| Portfolio & Blog | Showcase projects, articles, and deploya brand identity |
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
│   │       ├── deploya_api/                ← Decoupled API enhancements
│   │       └── deploya_commerce/           ← Commerce customizations
│   └── themes/
│       └── custom/
│           └── deploya/
│               ├── components/             ← SDC components
│               └── ...
├── CLAUDE.md                               ← This file
└── DRUPAL_CODING_STANDARDS.md              ← Coding standards and patterns
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
Managed by `pokemon_api` contrib module. **Do not edit manually** — data is synced from PokeAPI.
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

---

## Custom Modules

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

# Theme assets (run from theme directory inside DDEV)
ddev exec bash -c "cd public_html/themes/custom/deploya && npm run format && npm run build"
```

---

## Guardrails

- **Never commit**: `.env`, `settings.local.php`, `.ddev/config.local.yaml`, `vendor/`, `public_html/sites/*/files`
- **Never edit**: Drupal core or contributed modules/themes in place
- **Custom code only in**: `public_html/modules/custom/` and `public_html/themes/custom/`

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
| Drupal Answers | https://drupal.stackexchange.com |
