<?php
namespace AcrossAI_Model_Manager\Includes;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Fired during plugin activation
 *
 * @link       https://github.com/AcrossWP/acrossai-model-manager
 * @since      0.0.1
 *
 * @package    AcrossAI_Model_Manager
 * @subpackage AcrossAI_Model_Manager/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      0.0.1
 * @package    AcrossAI_Model_Manager
 * @subpackage AcrossAI_Model_Manager/includes
 * @author     WPBoilerplate <contact@wpboilerplate.com>
 */
class Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    0.0.1
	 */
	public static function activate() {
		self::create_tables();
		self::schedule_cron();
	}

	/**
	 * Runs schema upgrades for existing installs.
	 * Called on admin_init when the stored DB version is outdated.
	 */
	public static function maybe_upgrade(): void {
		$installed = (int) get_option( 'acai_model_manager_db_version', 0 );
		if ( $installed < 2 ) {
			self::create_tables();
		}
	}

	/**
	 * Creates the plugin's custom database tables via dbDelta.
	 */
	public static function create_tables(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'acai_ai_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  result_id varchar(255) NOT NULL DEFAULT '',
  capability varchar(50) NOT NULL DEFAULT '',
  provider_id varchar(100) NOT NULL DEFAULT '',
  provider_name varchar(255) NOT NULL DEFAULT '',
  model_id varchar(255) NOT NULL DEFAULT '',
  model_name varchar(255) NOT NULL DEFAULT '',
  prompt_text longtext NOT NULL,
  response_text longtext NOT NULL,
  prompt_tokens int(11) unsigned NOT NULL DEFAULT 0,
  completion_tokens int(11) unsigned NOT NULL DEFAULT 0,
  total_tokens int(11) unsigned NOT NULL DEFAULT 0,
  thought_tokens int(11) unsigned DEFAULT NULL,
  finish_reason varchar(50) NOT NULL DEFAULT '',
  duration_ms int(11) unsigned NOT NULL DEFAULT 0,
  source_type varchar(20) NOT NULL DEFAULT '',
  source_name varchar(255) NOT NULL DEFAULT '',
  source_file varchar(500) NOT NULL DEFAULT '',
  source_line int(11) unsigned NOT NULL DEFAULT 0,
  user_id bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY capability (capability),
  KEY provider_id (provider_id),
  KEY source_type (source_type),
  KEY user_id (user_id),
  KEY created_at (created_at)
) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'acai_model_manager_db_version', 2 );
	}

	/**
	 * Schedules the daily log cleanup cron event.
	 */
	public static function schedule_cron(): void {
		if ( ! wp_next_scheduled( 'acai_model_manager_cleanup_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'acai_model_manager_cleanup_logs' );
		}
	}
}
