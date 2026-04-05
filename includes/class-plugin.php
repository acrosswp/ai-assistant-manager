<?php
/**
 * Plugin bootstrap class.
 *
 * @package ai-assistant-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once AIAM_PLUGIN_DIR . 'includes/class-settings-page.php';
require_once AIAM_PLUGIN_DIR . 'includes/class-model-preferences.php';

/**
 * Main plugin class.
 */
class AIAM_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var AIAM_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Gets or creates the singleton instance.
	 *
	 * @return AIAM_Plugin
	 */
	public static function get_instance(): AIAM_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor registers hooks.
	 */
	private function __construct() {
		new AIAM_Settings_Page();
		new AIAM_Model_Preferences();
		add_filter( 'plugin_action_links_' . plugin_basename( AIAM_PLUGIN_FILE ), array( $this, 'add_settings_link' ) );
	}

	/**
	 * Adds a Settings link to the plugin action links on the Plugins page.
	 *
	 * @param string[] $links Existing action links.
	 * @return string[] Modified action links.
	 */
	public function add_settings_link( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=ai-assistant-manager' ) ),
			esc_html__( 'Settings', 'ai-assistant-manager' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}
}
