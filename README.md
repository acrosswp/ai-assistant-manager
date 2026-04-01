# AI Assistant Manager

Configure preferred AI models per capability type and override WordPress default model selection.

---

## Description

AI Assistant Manager lets you choose exactly which AI model WordPress uses for each capability type — text generation, image generation, and vision/multimodal tasks — without touching code.

It hooks into the WordPress AI model selection filters at priority 1000 and prepends your chosen model to the candidate list, so WordPress always tries it first before falling back to its built-in defaults.

**Features:**

- Choose a preferred model for text generation, image generation, and vision/multimodal tasks independently from **Settings > AI Assistant Manager**.
- Preference is only applied when the selected provider has a valid API key entered on the Connectors screen (**Settings > Connectors**).
- Falls back gracefully to WordPress defaults if the provider is not connected.
- Lightweight — no external dependencies, no JavaScript.

---

## Requirements

- WordPress 7.0 or later
- PHP 7.4 or later
- At least one AI provider plugin installed and configured (OpenAI, Anthropic, Google, etc.)

---

## Installation

1. Upload the `ai-assistant-manager` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings > Connectors** and enter an API key for your preferred AI provider.
4. Go to **Settings > AI Assistant Manager** and select a model for each capability type.
5. Click **Save Changes**.

---

## Frequently Asked Questions

### Which WordPress version is required?

WordPress 7.0 or later, because this plugin relies on `wp_get_connectors()` and the `wpai_preferred_*_models` filters introduced in WordPress 7.0.

### Does this plugin work without an AI provider plugin?

No. You need at least one AI provider plugin installed and configured with a valid API key via **Settings > Connectors**.

### What happens if I pick a model but then remove the API key?

The plugin checks whether the provider has a connected API key at runtime. If the key is missing, the saved preference is ignored and WordPress falls back to its normal model selection order.

### Can I override the connector check for local/custom providers?

Yes. The plugin fires the `aam_has_ai_credentials` filter, matching the same pattern as the core `wpai_has_ai_credentials` filter. Hook into it to declare a provider as connected even when it does not use an API key (e.g. Ollama):

```php
add_filter( 'aam_has_ai_credentials', function( $has_credentials, $connectors ) {
    if ( isset( $connectors['ollama'] ) ) {
        return true;
    }
    return $has_credentials;
}, 10, 2 );
```

### Will this affect every AI feature on my site?

It overrides the candidate model list for text generation, image generation, and vision tasks — any WordPress feature that uses the standard `wpai_preferred_*_models` filters will respect your preference.

---

## Changelog

### 1.0.0
- Initial release.
- Settings page under **Settings > AI Assistant Manager**.
- Preferred model selection for text generation, image generation, and vision.
- Connector check: preference is only applied when the provider has an API key.
- `aam_has_ai_credentials` filter for local/custom provider overrides.
