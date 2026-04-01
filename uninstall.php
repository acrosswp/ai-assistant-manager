<?php
/**
 * Uninstall routine - removes all plugin data from the database.
 *
 * @package ai-assistant-manager
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'aam_model_preferences' );
