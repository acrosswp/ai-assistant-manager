<?php
/**
 * Plugin bootstrap class.
 *
 * @package ai-assistant-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once AAM_PLUGIN_DIR . 'includes/class-settings-page.php';
require_once AAM_PLUGIN_DIR . 'includes/class-model-preferences.php';

/**
 * Main plugin class.
 */
class AAM_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var AAM_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Gets or creates the singleton instance.
	 *
	 * @return AAM_Plugin
	 */
	public static function get_instance(): AAM_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor registers hooks.
	 */
	private function __construct() {
		new AAM_Settings_Page();
		new AAM_Model_Preferences();
	}
}
