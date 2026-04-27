# Hooks Reference

## Actions registered

| Hook | Callback | Priority | Notes |
|---|---|---|---|
| `register_activation_hook` | `acai_model_manager_activate()` | — | Calls `Activator::activate()` |
| `register_deactivation_hook` | `acai_model_manager_deactivate()` | — | Calls `Deactivator::deactivate()` |
| `plugins_loaded` | `Main::run()` | 0 | Executes Loader |
| `admin_enqueue_scripts` | `Admin\Main::enqueue_styles()` | 10 | |
| `admin_enqueue_scripts` | `Admin\Main::enqueue_scripts()` | 10 | Localizes JS on settings page |
| `admin_menu` | `Menu::add_menu()` | 10 | Adds Settings > Model Manager |
| `init` | `Menu::register_settings()` | 10 | Registers option + REST schema |

## Filters registered

| Hook | Callback | Priority | Notes |
|---|---|---|---|
| `acrossai_model_manager_load` | _(external)_ | — | Return false to prevent all hooks loading |
| `plugin_action_links_{BASENAME}` | `Admin\Main::add_settings_link()` | 10 | Adds Settings link on plugins page |
| `wpai_preferred_text_models` | `Model_Preferences::filter_text_models()` | 1111 | Prepends saved text model preference |
| `wpai_preferred_image_models` | `Model_Preferences::filter_image_models()` | 1111 | Prepends saved image model preference |
| `wpai_preferred_vision_models` | `Model_Preferences::filter_vision_models()` | 1111 | Prepends saved vision model preference |
| `wp_ai_client_default_request_timeout` | `Request_Settings::filter_timeout()` | 10 | Returns saved timeout (seconds); global, no opt-in needed |
| `acai_model_manager_has_ai_credentials` | _(external)_ | — | Override provider connectivity check (bool) |
| `acai_model_manager_default_temperature` | _(external)_ | — | Override saved temperature default (float\|null) |
| `acai_model_manager_default_max_tokens` | _(external)_ | — | Override saved max tokens default (int\|null) |
| `acai_model_manager_default_top_p` | _(external)_ | — | Override saved top-p default (float\|null) |
| `acai_model_manager_default_top_k` | _(external)_ | — | Override saved top-k default (int\|null) |
| `acai_model_manager_default_presence_penalty` | _(external)_ | — | Override saved presence penalty default (float\|null) |
| `acai_model_manager_default_frequency_penalty` | _(external)_ | — | Override saved frequency penalty default (float\|null) |

## define_admin_hooks() — queued via Loader

| Hook | Component | Method | Priority |
|---|---|---|---|
| `admin_enqueue_scripts` | `Admin\Main` | `enqueue_styles` | 10 |
| `admin_enqueue_scripts` | `Admin\Main` | `enqueue_scripts` | 10 |
| `admin_menu` | `Admin\Partials\Menu` | `add_menu` | 10 |
| `init` | `Admin\Partials\Menu` | `register_settings` | 10 |
| `plugin_action_links_{BASENAME}` | `Admin\Main` | `add_settings_link` | 10 |

## define_plugin_hooks() — registered directly (NOT via Loader)

These must fire before `plugins_loaded`, so they bypass the Loader queue.

| Hook | Component | Method | Priority |
|---|---|---|---|
| `wpai_preferred_text_models` | `Model_Preferences` | `filter_text_models` | 1111 |
| `wpai_preferred_image_models` | `Model_Preferences` | `filter_image_models` | 1111 |
| `wpai_preferred_vision_models` | `Model_Preferences` | `filter_vision_models` | 1111 |
| `wp_ai_client_default_request_timeout` | `Request_Settings` | `filter_timeout` | 10 |

> Priority 1111 ensures this plugin's model preference wins over any other plugin filtering these hooks at default priority.
