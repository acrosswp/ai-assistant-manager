# AcrossAI Model Manager — AI Agent Instructions

## Overview

WordPress plugin giving site admins control over which AI model is used per capability type (text, image, vision), the global HTTP request timeout, and (hidden UI) generation parameter defaults. Integrates with the WordPress 7.0 built-in AI client via filter hooks. Settings page at **Settings > Model Manager**.

**Key identifiers:**
- Plugin slug / text domain: `acrossai-model-manager`
- PHP namespace root: `AcrossAI_Model_Manager\`
- Constant prefix: `ACAI_MODEL_MANAGER_`
- Option key: `acai_model_manager_preferences`
- Legacy option key (migration source): `aiam_model_preferences`
- Settings page slug: `acrossai-model-manager`
- React mount point: `<div id="acwpms-settings-root">`

**Dependencies:**
- **Model Preferences** requires the [AI plugin](https://wordpress.org/plugins/ai/) (`ai/ai.php`) — provides `wpai_preferred_*_models` filters. When inactive, Model Preferences card is disabled in the UI.
- **HTTP Timeout** requires WordPress 7.0+ (`wp_ai_client_default_request_timeout` filter).

---

## Requirements

| Requirement | Minimum |
|---|---|
| PHP | 7.4 (enforced by Composer) |
| WordPress | 7.0 |
| Node.js | 18 |

> **CRITICAL**: Always run `php -v` before starting work. Composer will refuse to install on PHP < 7.4.

---

## Critical Warnings

- **Never call `get_plugin_data()`** inside `define_constants()` or before `init` — it triggers `_load_textdomain_just_in_time` too early (WP 6.7+ notice). Use `ACAI_MODEL_MANAGER_VERSION` directly.
- **Never add `load_plugin_textdomain()`** to `I18n.php` — WP 4.6+ auto-loads translations for WP.org plugins. `I18n::do_load_textdomain()` is intentionally a no-op.

---

## Execution Flow

```
acrossai-model-manager.php
  └─ defines ACAI_MODEL_MANAGER_PLUGIN_FILE, ACAI_MODEL_MANAGER_VERSION
  └─ registers activation/deactivation hooks
  └─ require includes/Main.php + includes/functions.php
  └─ acai_model_manager_run()
       └─ Main::instance()
            ├─ define_constants()
            ├─ register_autoloader() → spl_autoload_register()
            ├─ load_composer_dependencies() → vendor/autoload.php
            ├─ load_dependencies() → Loader::instance()
            └─ load_hooks()
                 ├─ apply_filters('acrossai_model_manager_load', true)
                 ├─ define_admin_hooks()    ← queued via Loader
                 └─ define_plugin_hooks()   ← direct add_filter() (must fire early)
       └─ add_action('plugins_loaded', [$plugin, 'run'], 0)
            └─ Loader::run() → registers all queued hooks with WP
```

Plugin hooks (`wpai_preferred_*` and `wp_ai_client_default_request_timeout`) are registered **directly** — not via Loader — because they must be active before `plugins_loaded` fires.

See @docs/hooks-reference.md for the complete hook/filter tables.

---

## WordPress Settings Storage

Option: `acai_model_manager_preferences` in `wp_options`.

```php
[
    'text_generation'   => 'openai::gpt-4o',   // provider::model_id or absent
    'image_generation'  => 'openai::dall-e-3',
    'vision'            => 'openai::gpt-4o',
    'request_timeout'   => 60,    // int; absent = WP default (30s)
    'temperature'       => 0.7,   // float; absent = provider default (UI hidden)
    'max_tokens'        => 2048,  // int;   absent = provider default (UI hidden)
    // top_p, top_k, presence_penalty, frequency_penalty — absent when unset
]
```

Keys cleared by the user are **omitted entirely** (not stored as `null`). Saved via `/wp/v2/settings` REST endpoint — no custom routes registered.

---

## Security

- `render_page()` and `enqueue_scripts()` gate on `current_user_can('manage_options')`
- `wp_create_nonce('wp_rest')` passed to JS; `apiFetch` sends it as `X-WP-Nonce`
- `Menu::sanitize_preferences()` validates `provider::model_id` format, float ranges, int minimums
- No direct `$_POST` access — all saves go through WP REST API + Settings API

---

## Adding a New Capability Type

1. Add capability key + label to `$capabilities` in `admin/partials/Menu.php`
2. Add a string property to the REST schema in `register_settings()`
3. Add a filter method to `includes/Model_Preferences.php`
4. Register the filter in `includes/Main.php::define_plugin_hooks()`
5. React UI iterates `window.acaiModelManagerSettings.models` dynamically — no JS changes needed

---

## Enabling the Generation Parameters UI

The PHP is fully functional; only the admin UI is hidden.

1. Open `src/js/backend.js`
2. Remove the `{ false && (` opening and matching `) }` closing around the Generation Parameters card
3. Run `npm run build`

---

## Version Bumping Checklist

- [ ] Update `Version:` header in `acrossai-model-manager.php`
- [ ] Update `ACAI_MODEL_MANAGER_VERSION` constant in `acrossai-model-manager.php`
- [ ] Update `Stable tag:` in `README.txt`
- [ ] Add changelog entry in `README.txt` under `== Changelog ==`
- [ ] Tag the Git commit — triggers `wordpress-plugin-deploy.yml`

---

## Reference Docs

- @docs/hooks-reference.md — all hook/filter tables, define_admin_hooks, define_plugin_hooks
- @docs/class-reference.md — class method tables, apply_preference() logic, merge semantics, usage examples
- @docs/js-frontend.md — `window.acaiModelManagerSettings` shape, React components, SCSS classes, build commands
- @docs/decisions.md — architectural decisions, WP AI client integration rationale, known issues
