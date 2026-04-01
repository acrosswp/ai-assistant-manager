<?php
/**
 * Plugin Name:       AI Assistant Manager
 * Plugin URI:        https://wordpress.org/plugins/ai-assistant-manager/
 * Description:       Configure preferred AI models per capability type and override WordPress default model selection.
 * Version:           1.0.0
 * Requires at least: 7.0
 * Requires PHP:      7.4
 * Author:            okpoojagupta
 * Author URI:        https://profiles.wordpress.org/okpoojagupta/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ai-assistant-manager
 * Domain Path:       /languages
 *
 * @package ai-assistant-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AAM_VERSION', '1.0.0' );
define( 'AAM_PLUGIN_FILE', __FILE__ );
define( 'AAM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AAM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once AAM_PLUGIN_DIR . 'includes/class-plugin.php';

/**
 * Initializes the plugin.
 *
 * @return AAM_Plugin
 */
function aam_plugin(): AAM_Plugin {
	return AAM_Plugin::get_instance();
}

add_action( 'plugins_loaded', 'aam_plugin' );
