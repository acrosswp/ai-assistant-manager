<?php
namespace AcrossAI_Model_Manager\Includes;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Fired during plugin deactivation
 *
 * @link       https://github.com/AcrossWP/acrossai-model-manager
 * @since      0.0.1
 *
 * @package    AcrossAI_Model_Manager
 * @subpackage AcrossAI_Model_Manager/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      0.0.1
 * @package    AcrossAI_Model_Manager
 * @subpackage AcrossAI_Model_Manager/includes
 * @author     WPBoilerplate <contact@wpboilerplate.com>
 */
class Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    0.0.1
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'acai_model_manager_cleanup_logs' );
	}
}
