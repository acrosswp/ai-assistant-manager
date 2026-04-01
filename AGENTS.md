# AI Assistant Manager — Agent Instructions

This file documents the full technical context of the **AI Assistant Manager** WordPress plugin for AI coding agents. Read this before making any changes.

---

## Project Overview

| Field | Value |
|---|---|
| Plugin Name | AI Assistant Manager |
| Slug | `ai-assistant-manager` |
| Version | 1.0.0 |
| Author | okpoojagupta |
| License | GPL-2.0-or-later |
| Requires WordPress | 7.0+ |
| Requires PHP | 7.4+ |
| GitHub Repo | `acrosswp/ai-assistant-manager` |
| WP.org Listing | https://wordpress.org/plugins/ai-assistant-manager/ |
| Text Domain | `ai-assistant-manager` |

**Purpose:** Lets site administrators configure a preferred AI model for each WordPress AI capability type (text generation, image generation, vision/multimodal). The preference is prepended to the WordPress model candidate list via filters, so WordPress uses it first before falling back to its defaults.

---

## File Structure

```
ai-assistant-manager/
├── ai-assistant-manager.php          # Plugin entry point, constants, bootstrap
├── uninstall.php                     # Cleanup on plugin deletion
├── readme.txt                        # WordPress.org plugin directory listing
├── README.md                         # GitHub documentation
├── .gitignore
├── languages/                        # i18n translation files (empty at 1.0.0)
├── assets/
│   └── css/
│       └── admin.css                 # Admin settings page styles
└── includes/
    ├── class-plugin.php              # Singleton bootstrap, registers all hooks
    ├── class-settings-page.php       # Admin settings page (Settings > AI Assistant Manager)
    └── class-model-preferences.php   # Filter hooks that apply the saved preference
```

---

## Constants (defined in `ai-assistant-manager.php`)

| Constant | Value |
|---|---|
| `AAM_VERSION` | `'1.0.0'` |
| `AAM_PLUGIN_FILE` | Absolute path to `ai-assistant-manager.php` |
| `AAM_PLUGIN_DIR` | Absolute path to plugin root directory (trailing slash) |
| `AAM_PLUGIN_URL` | URL to plugin root directory (trailing slash) |

---

## Classes

### `AAM_Plugin` — `includes/class-plugin.php`

Singleton bootstrap. Initialized on the `plugins_loaded` action via `aam_plugin()`.

**Hooks registered:**
- Instantiates `AAM_Settings_Page` and `AAM_Model_Preferences`
- `plugin_action_links_{basename}` → `add_settings_link()` — adds a **Settings** link on the WP Plugins page pointing to `options-general.php?page=ai-assistant-manager`

**Key methods:**
- `AAM_Plugin::get_instance(): AAM_Plugin` — returns the singleton
- `add_settings_link( array $links ): array` — public; prepends the Settings action link

---

### `AAM_Settings_Page` — `includes/class-settings-page.php`

Renders the admin settings page and handles data persistence.

**Constants:**
- `AAM_Settings_Page::OPTION_KEY = 'aam_model_preferences'` — the `wp_options` key where preferences are stored
- `AAM_Settings_Page::PAGE_SLUG = 'ai-assistant-manager'` — the `menu_slug` for `add_options_page()`

**Hooks registered:**
- `admin_menu` → `add_menu()` — adds sub-page under Settings
- `admin_init` → `register_settings()` — registers `aam_settings_group` / `aam_model_preferences`
- `admin_enqueue_scripts` → `enqueue_styles()` — enqueues `assets/css/admin.css` only on hook `settings_page_ai-assistant-manager`

**Capability types (keys used throughout):**

| Key | Label |
|---|---|
| `text_generation` | Text Generation |
| `image_generation` | Image Generation |
| `vision` | Vision / Multimodal |

**Data format stored in `aam_model_preferences`:**
```php
[
    'text_generation'  => 'openai::gpt-4o',      // "{provider_id}::{model_id}"
    'image_generation' => 'google::imagen-3',
    'vision'           => 'anthropic::claude-opus-4',
]
```
Each value is `{provider_id}::{model_id}` — a double-colon-delimited string. Keys not set mean "use WordPress default".

**Sanitization:** `sanitize_preferences()` validates each value contains `::`, splits it, then re-joins after running `sanitize_key()` on the provider and `sanitize_text_field()` on the model ID.

**Model population:** `get_models_for_capability( $cap_key )` uses `\WordPress\AiClient\AiClient::defaultRegistry()` to enumerate only configured providers and filter models by capability. Vision is mapped to `CapabilityEnum::TEXT_GENERATION` with an additional multimodal capability filter.

**Security:** `render_page()` gates on `current_user_can( 'manage_options' )`.

---

### `AAM_Model_Preferences` — `includes/class-model-preferences.php`

Applies the saved preferences via WordPress AI filter hooks.

**Hooks registered (all at priority 1000):**
- `wpai_preferred_text_models` → `filter_text_models()`
- `wpai_preferred_image_models` → `filter_image_models()`
- `wpai_preferred_vision_models` → `filter_vision_models()`

**Logic in `apply_preference( array $models, string $cap_key ): array`:**
1. Read `aam_model_preferences` from options
2. If no preference set for `$cap_key`, return `$models` unchanged
3. Parse `$provider . '::' . $model_id` from the stored string
4. Call `is_provider_connected( $provider )` — if false, return `$models` unchanged
5. `array_unshift( $models, [ $provider, $model_id ] )` — prepend as first candidate
6. Return modified `$models`

**Connector check — `is_provider_connected( string $provider_id ): bool`:**
- Requires `wp_get_connectors()` (WordPress 7.0+ function in `wp-includes/connectors.php`)
- Iterates connectors, finds the one matching `$provider_id` with `type === 'ai_provider'`
- Checks `authentication.method === 'api_key'` and that `get_option( $auth['setting_name'] )` is non-empty
- Setting name pattern: `connectors_ai_{provider_id}_api_key` (e.g. `connectors_ai_openai_api_key`)
- Fires `apply_filters( 'aam_has_ai_credentials', $has_credentials, $connectors )` before returning — allows local/custom providers (e.g. Ollama) to declare themselves connected

---

## Filter / Hook Reference

| Hook | Type | Priority | Description |
|---|---|---|---|
| `wpai_preferred_text_models` | filter | 1000 | Prepends preferred text model |
| `wpai_preferred_image_models` | filter | 1000 | Prepends preferred image model |
| `wpai_preferred_vision_models` | filter | 1000 | Prepends preferred vision model |
| `aam_has_ai_credentials` | filter | — | Override connector check. Args: `(bool $has_credentials, array $connectors)` |
| `plugin_action_links_{basename}` | filter | — | Adds Settings link on Plugins page |
| `admin_menu` | action | — | Registers Settings sub-page |
| `admin_init` | action | — | Registers settings group |
| `admin_enqueue_scripts` | action | — | Enqueues admin CSS |

---

## WordPress 7.0 API Dependencies

This plugin requires WordPress 7.0+ for:

- **`wp_get_connectors()`** — `wp-includes/connectors.php` — returns all registered AI provider connectors keyed by provider ID
- **`wpai_preferred_text_models`**, **`wpai_preferred_image_models`**, **`wpai_preferred_vision_models`** filters
- **`\WordPress\AiClient\AiClient`** — AI client registry for enumerating configured providers and their models
- **`\WordPress\AiClient\Providers\Models\Enums\CapabilityEnum`** — capability constants

### Connector data structure
```php
$connectors['openai'] = [
    'type' => 'ai_provider',
    'authentication' => [
        'method'       => 'api_key',
        'setting_name' => 'connectors_ai_openai_api_key',  // wp_options key
    ],
];
```

---

## Data Storage

| Option Key | Type | Notes |
|---|---|---|
| `aam_model_preferences` | `array` | Saved by `AAM_Settings_Page`. Deleted on plugin uninstall via `uninstall.php`. |

---

## Admin URL

```
options-general.php?page=ai-assistant-manager
```

Generated dynamically via `admin_url()`; never hardcoded.

---

## Assets

**`assets/css/admin.css`:**
- `.aam-models-table` — styles the settings table
- `.aam-model-select` — styles the model `<select>` dropdowns
- Loaded only on hook `settings_page_ai-assistant-manager` (not globally)

---

## Coding Conventions

- **PHP 7.4+** — typed properties and return types used throughout
- **WordPress Coding Standards** — tabs for indentation, Yoda conditions, `array()` not `[]`
- **No external dependencies** — no Composer packages, no npm, no external API calls at runtime
- **No JavaScript** — settings page is pure HTML form + PHP
- **i18n** — all user-facing strings use `__()`, `esc_html__()`, `esc_attr_e()` with text domain `ai-assistant-manager`
- **Escaping** — all output escaped: `esc_html()`, `esc_attr()`, `esc_url()`
- **Nonces** — settings form uses `settings_fields()` which handles nonce verification
- **Capability check** — `manage_options` required for the settings page
- **Constants over magic strings** — use `AAM_Settings_Page::OPTION_KEY`, `AAM_Settings_Page::PAGE_SLUG`

---

## Development Workflow

### Local Environment
- **WordPress version:** 7.0 (Local by Flywheel)
- **Local URL:** `http://localhost:10219`
- **Plugin path:** `/Users/raftaar1191/local-sites/wordpress-7-0/app/public/wp-content/plugins/ai-assistant-manager/`
- **MySQL socket:** `/Users/raftaar1191/Library/Application Support/Local/run/t739Logms/mysql/mysqld.sock`
- **Socket symlink for WP-CLI:** `ln -sf "...mysqld.sock" /tmp/mysql.sock`

### Validate PHP syntax
```bash
php -l includes/class-settings-page.php
php -l includes/class-model-preferences.php
php -l includes/class-plugin.php
php -l ai-assistant-manager.php
```

### Run Plugin Check
```bash
# Requires /tmp/mysql.sock symlink to Local's MySQL socket
wp --path=/path/to/wordpress plugin check ai-assistant-manager
```

### Push to GitHub
Remote: `git@github.com:acrosswp/ai-assistant-manager.git` (branch: `main`)

### WP.org Submission ZIP
Exclude from ZIP: `.git/`, `.gitignore`, `README.md`
```bash
zip -r ai-assistant-manager.zip ai-assistant-manager \
  --exclude "ai-assistant-manager/.git/*" \
  --exclude "ai-assistant-manager/.gitignore" \
  --exclude "ai-assistant-manager/README.md"
```

---

## Version History

| Version | Date | Changes |
|---|---|---|
| 1.0.0 | 2026-04-02 | Initial release — settings page, model preferences, connector check, `aam_has_ai_credentials` filter, Settings action link |
