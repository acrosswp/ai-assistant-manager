# Class Reference

## `Includes\Main` — `includes/Main.php`

Final singleton. Bootstraps the entire plugin.

| Method | Visibility | Description |
|---|---|---|
| `instance()` | public static | Returns/creates singleton |
| `define_constants()` | private | Defines all `ACAI_MODEL_MANAGER_*` constants |
| `register_autoloader()` | private | Creates `Autoloader`, registers via `spl_autoload_register` |
| `load_composer_dependencies()` | private | Loads `vendor/autoload.php` |
| `load_dependencies()` | private | Creates `Loader::instance()` |
| `load_hooks()` | public | Gates all hooks behind `acrossai_model_manager_load` filter |
| `define_admin_hooks()` | private | Queues all admin hooks via Loader |
| `define_plugin_hooks()` | private | Registers AI preference + timeout filters directly |
| `run()` | public | Called on `plugins_loaded`; executes `Loader::run()` |
| `get_plugin_name()` | public | Returns `'acrossai-model-manager'` |
| `get_version()` | public | Returns plugin version string |

---

## `Includes\Loader` — `includes/Loader.php`

Singleton. Collects hook registrations and fires them in bulk on `run()`.

| Method | Visibility | Description |
|---|---|---|
| `instance()` | public static | Returns/creates singleton |
| `add_action($hook, $component, $callback, $priority, $accepted_args)` | public | Queues an action |
| `add_filter($hook, $component, $callback, $priority, $accepted_args)` | public | Queues a filter |
| `run()` | public | Calls `add_action`/`add_filter` for every queued item |

Queue entry shape: `['hook', 'component', 'callback', 'priority', 'accepted_args']`

---

## `Includes\Autoloader` — `includes/Autoloader.php`

PSR-4 autoloader. Registered via `spl_autoload_register`. Tries PascalCase and `class-kebab.php` filename variants.

| Namespace suffix | Directory |
|---|---|
| `Includes\` | `includes/` |
| `Admin\` | `admin/` |
| `Public\` | `public/` |

---

## `Includes\Model_Preferences` — `includes/Model_Preferences.php`

Reads saved preferences and prepends the preferred model to the WP AI model candidate arrays.

| Method | Visibility | Description |
|---|---|---|
| `filter_text_models(array $models): array` | public | Hook callback for `wpai_preferred_text_models` |
| `filter_image_models(array $models): array` | public | Hook callback for `wpai_preferred_image_models` |
| `filter_vision_models(array $models): array` | public | Hook callback for `wpai_preferred_vision_models` |
| `apply_preference(array $models, string $cap_key): array` | private | Core logic |
| `is_provider_connected(string $provider_id): bool` | private | Checks AiClient registry |

**`apply_preference()` logic:**
1. Load option `acai_model_manager_preferences`.
2. Check if a preference exists for `$cap_key`.
3. Parse `provider::model_id` from the saved string.
4. Call `is_provider_connected()` — checks `AiClient::defaultRegistry()->isProviderConfigured($provider_id)`.
5. Apply filter `acai_model_manager_has_ai_credentials` (bool) for external override.
6. If connected: prepend `"provider::model_id"` to front of `$models`.
7. Return modified array.

---

## `Includes\Generation_Params` — `includes/Generation_Params.php`

Manages site-wide AI generation parameter defaults (temperature, max tokens, top-p, etc.).

> **UI STATUS**: Generation Parameters section is **hidden** in React (`{ false && (...) }` guard). PHP is fully functional. To show the UI: remove the `{ false && (` wrapper in `src/js/backend.js` and rebuild.

| Constant | Value |
|---|---|
| `PARAM_KEYS` | `['temperature','max_tokens','top_p','top_k','presence_penalty','frequency_penalty']` |

**`get_model_config()` logic:**
1. Load option `acai_model_manager_preferences`.
2. Cast each param to `float`/`int`, default to `null` if absent.
3. Apply individual WP filter for each param.
4. Set only non-null values on a fresh `ModelConfig` instance.
5. Return the config.

**Why only non-null values:** `ModelConfig::toArray()` omits nulls. `PromptBuilder::usingModelConfig()` merges via `array_merge($provided, $builder)` — builder's explicit values always win.

---

## `Includes\Request_Settings` — `includes/Request_Settings.php`

| Method | Visibility | Description |
|---|---|---|
| `filter_timeout(int $timeout): int` | public static | Returns saved timeout if ≥ 1, else passes through WP default (30 s) |

> **GLOBAL EFFECT**: Applied automatically to every `wp_ai_client_prompt()` call — no opt-in needed. `WP_AI_Client_Prompt_Builder::__construct()` applies `wp_ai_client_default_request_timeout` on every instantiation.

---

## Global helper — `includes/functions.php`

### `acai_model_manager_apply_defaults( WP_AI_Client_Prompt_Builder $builder ): WP_AI_Client_Prompt_Builder`

Applies site-wide generation parameter defaults via `$builder->using_model_config( Generation_Params::get_model_config() )`.

**Merge semantics:** `array_merge($provided_config->toArray(), $builder_config->toArray())` — builder's explicit values win regardless of call order.

```php
// Apply site defaults (fills any unset params):
$result = acai_model_manager_apply_defaults( wp_ai_client_prompt( 'Summarise this.' ) )
    ->generate_text();

// Plugin's explicit temperature (1.5) always wins:
$result = acai_model_manager_apply_defaults(
    wp_ai_client_prompt( 'Be creative.' )->using_temperature( 1.5 )
)->generate_text();

// Override a default programmatically:
add_filter( 'acai_model_manager_default_temperature', fn() => 0.3 );
```

Guard: returns unmodified builder if `Generation_Params` class is unavailable.

---

## `Admin\Main` — `admin/Main.php`

| Method | Visibility | Description |
|---|---|---|
| `enqueue_styles(string $hook)` | public | Always enqueues `build/css/backend.css`; adds `wp-components` on settings page |
| `enqueue_scripts(string $hook)` | public | Enqueues `build/js/backend.js`; localizes `window.acaiModelManagerSettings` on settings page |
| `get_all_ai_models(): array` | private | Queries `AiClient::defaultRegistry()` for all configured providers + models |
| `get_models_grouped_by_capability(): array` | private | Transforms model list into grouped structure for JS selects |
| `add_settings_link(array $links): array` | public | Prepends "Settings" link on the plugins page |

**AI plugin detection:** `defined('WPAI_PLUGIN_FILE')` — zero-overhead, works at any hook stage. When `false`, `models` is always `{}` (no registry query made).

---

## `Admin\Partials\Menu` — `admin/partials/Menu.php`

| Constant | Value |
|---|---|
| `OPTION_KEY` | `'acai_model_manager_preferences'` |
| `LEGACY_OPTION_KEY` | `'aiam_model_preferences'` |
| `PAGE_SLUG` | `'acrossai-model-manager'` |

| Method | Visibility | Description |
|---|---|---|
| `add_menu()` | public | `add_options_page()` → Settings > Model Manager |
| `register_settings()` | public | Migrates legacy prefs; calls `register_setting()` with REST schema + sanitize callback |
| `sanitize_preferences($input): array` | public | Validates model prefs, float ranges, int minimums |
| `migrate_legacy_preferences()` | private | One-time copy from `aiam_model_preferences` → `acai_model_manager_preferences` |
| `render_page()` | public | Checks `manage_options`; renders `<div id="acwpms-settings-root">` for React |

**`sanitize_preferences()` validation rules:**

| Key | Type | Rule |
|---|---|---|
| `text_generation` | string | Must match `provider::model_id` |
| `image_generation` | string | Must match `provider::model_id` |
| `vision` | string | Must match `provider::model_id` |
| `temperature` | float\|null | 0.0–2.0 |
| `top_p` | float\|null | 0.0–1.0 |
| `presence_penalty` | float\|null | -2.0–2.0 |
| `frequency_penalty` | float\|null | -2.0–2.0 |
| `max_tokens` | int\|null | ≥ 1 |
| `top_k` | int\|null | ≥ 1 |
| `request_timeout` | int\|null | ≥ 1 |

`register_setting()`: group `acai_model_manager_settings_group`, type `object`, `show_in_rest: true`.
