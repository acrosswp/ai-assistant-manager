# Architectural Decisions & Known Issues

## WordPress AI Client Integration

WP 7.0 AI client files:
- `wp-includes/ai-client.php` — `wp_ai_client_prompt()` function
- `wp-includes/ai-client/class-wp-ai-client-prompt-builder.php` — `WP_AI_Client_Prompt_Builder`
- `wp-includes/php-ai-client/src/Builders/PromptBuilder.php` — underlying PHP library
- `wp-includes/php-ai-client/src/Providers/Models/DTO/ModelConfig.php` — generation config DTO

**Why global parameter interception is impossible:** `WP_AI_Client_Prompt_Builder` has no filter for `ModelConfig` parameters. `BeforeGenerateResultEvent` is read-only (no setters) and fires as a WP action (not a filter). Parameters a third-party plugin sets inline cannot be intercepted.

**What IS hookable globally (no opt-in):**

| Hook | Effect |
|---|---|
| `wpai_preferred_text_models` | Prepends saved model to WP AI model selection |
| `wpai_preferred_image_models` | Same for image generation |
| `wpai_preferred_vision_models` | Same for vision |
| `wp_ai_client_default_request_timeout` | Sets HTTP timeout on every `wp_ai_client_prompt()` call |

**What requires opt-in:** Generation parameters — call `acai_model_manager_apply_defaults()` explicitly.

---

## Decision Log

| Decision | Reason |
|---|---|
| `define_plugin_hooks()` uses direct `add_filter()` instead of Loader | Model preference filters and timeout filter must be active before `plugins_loaded` fires and `Loader::run()` is called |
| Priority 1111 on AI preference filters | Ensures this plugin wins over any other plugin filtering at default priority |
| `/wp/v2/settings` instead of custom REST route | Simpler; built-in WP nonce + schema validation; no namespace collision risk |
| `ACAI_MODEL_MANAGER_VERSION` defined as a plain string constant | Avoids `get_plugin_data()` which translates headers and triggers `_load_textdomain_just_in_time` too early (WP 6.7+ bug) |
| `I18n::do_load_textdomain()` is a no-op | `load_plugin_textdomain()` is discouraged since WP 4.6; WP.org auto-loads translations |
| Generation Parameters UI is hidden (`{ false && (...) }`) | Feature is fully functional in PHP but admin UI is not yet ready for exposure |
| `Request_Settings` uses a static callback string | Avoids instantiating the class unnecessarily |
| Generation params require opt-in via `acai_model_manager_apply_defaults()` | No WP core filter exists for `ModelConfig` parameters; transparent global interception is architecturally impossible |
| `includes/functions.php` loaded in global namespace | Helper must be callable by third-party plugins without namespace knowledge |
| AI plugin detection uses `defined('WPAI_PLUGIN_FILE')` | `WPAI_PLUGIN_FILE` is the first constant the AI plugin defines — zero-overhead, works at any hook stage |
| `models` passed as `{}` to JS when AI plugin inactive | Prevents a pointless `AiClient::defaultRegistry()` query; guarantees React select loops render nothing |
| Model Preferences selects are `disabled` (not hidden) when AI plugin inactive | Disabling with a warning is more informative than hiding — shows what becomes available once AI plugin is activated |
