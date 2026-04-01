=== AI Assistant Manager ===
Contributors:      okpoojagupta
Tags:              ai, artificial intelligence, model, openai, anthropic, google
Requires at least: 7.0
Tested up to:      7.0
Requires PHP:      7.4
Stable tag:        1.0.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Configure preferred AI models per capability type and override WordPress default model selection.

== Description ==

AI Assistant Manager lets site administrators choose which AI model WordPress uses for each capability type - text generation, image generation, and vision/multimodal tasks.

Once a provider is connected via the WordPress Connectors screen (Settings > Connectors), you can pin a specific model for each capability. The plugin hooks into the WordPress AI model selection system and places your preferred model first in the candidate list, so WordPress always tries it before falling back to its defaults.

Features:

* Set a preferred model for text generation, image generation, and vision/multimodal tasks independently.
* Preference is only applied when the selected provider has a valid API key entered on the Connectors screen.
* Falls back gracefully to WordPress defaults if a provider is not connected.
* Lightweight - no external dependencies, no JavaScript.

Requirements: This plugin requires WordPress 7.0 or later and at least one AI provider plugin installed and configured.

== Installation ==

1. Upload the ai-assistant-manager folder to your /wp-content/plugins/ directory.
2. Activate the plugin through the Plugins screen in WordPress.
3. Navigate to Settings > Connectors and enter an API key for your preferred AI provider.
4. Navigate to Settings > AI Assistant Manager and select a preferred model for each capability type.
5. Click Save Changes.

== Frequently Asked Questions ==

= Does this plugin work without an AI provider plugin? =

No. You need at least one AI provider plugin installed and configured with a valid API key.

= What happens if I select a model but the API key is removed? =

The preference is ignored and WordPress falls back to its normal model selection.

= Which WordPress version is required? =

WordPress 7.0 or later, because this plugin relies on wp_get_connectors() introduced in 7.0.

== Screenshots ==

1. The AI Assistant Manager settings page.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
