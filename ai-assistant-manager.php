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

define( 'AI_ASSISTANT_MANAGER_VERSION', '1.0.0' );
define( 'AI_ASSISTANT_MANAGER_PLUGIN_FILE', __FILE__ );
define( 'AI_ASSISTANT_MANAGER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AI_ASSISTANT_MANAGER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'AIAM_VERSION', '1.0.0' );
define( 'AIAM_PLUGIN_FILE', __FILE__ );
define( 'AIAM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AIAM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once AIAM_PLUGIN_DIR . 'includes/class-plugin.php';

/**
 * Initializes the plugin.
 *
 * @return AIAM_Plugin
 */
function aiam_plugin(): AIAM_Plugin {
	return AIAM_Plugin::get_instance();
}

add_action( 'plugins_loaded', 'aiam_plugin' );
