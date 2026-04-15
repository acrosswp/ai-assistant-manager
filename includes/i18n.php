<?php
namespace AWPAI_Model_Preferences\Includes;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Define the internationalization functionality
 *
 * @package    AWPAI_Model_Preferences
 * @subpackage AWPAI_Model_Preferences/includes
 */
class I18n {

	/**
	 * Actually load the plugin textdomain on `init`
	 */
	public function do_load_textdomain() {
		load_plugin_textdomain(
			'ai-model-preferences',
			false,
			plugin_basename( dirname( \AWPAI_MODEL_PREFERENCES_PLUGIN_FILE ) ) . '/languages/'
		);
	}
}
