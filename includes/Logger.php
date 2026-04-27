<?php
namespace AcrossAI_Model_Manager\Includes;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use WordPress\AiClient\Events\BeforeGenerateResultEvent;
use WordPress\AiClient\Events\AfterGenerateResultEvent;

/**
 * Logs AI client requests to the database.
 *
 * Hooks into wp_ai_client_before_generate_result and
 * wp_ai_client_after_generate_result to capture timing, token usage,
 * prompt text, and response text for every successful AI generation call.
 *
 * @since 0.0.4
 * @package AcrossAI_Model_Manager\Includes
 */
class Logger {

	const TABLE_SUFFIX       = 'acai_ai_logs';
	const DB_VERSION_OPTION  = 'acai_model_manager_db_version';
	const CRON_HOOK          = 'acai_model_manager_cleanup_logs';

	/**
	 * Stack of generation context entries for timing and caller tracking.
	 * Each entry: [ 'time' => float, 'caller' => array ].
	 * Uses a stack to correctly handle nested/recursive generation calls.
	 *
	 * @var array[]
	 */
	private static $start_times = array();

	/**
	 * Internal path segments to skip when walking the backtrace to find
	 * the true caller of wp_ai_client_prompt().
	 *
	 * @var string[]
	 */
	private static $internal_paths = array(
		'wp-includes/ai-client',
		'wp-includes/php-ai-client',
		'wp-includes/class-wp-hook.php',
		'wp-includes/plugin.php',
	);

	/**
	 * Returns the full table name with the WordPress table prefix.
	 *
	 * @return string
	 */
	public static function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_SUFFIX;
	}

	/**
	 * Captures the start time and caller info before an AI generation call.
	 * Backtrace must be captured here while the original call stack is intact.
	 *
	 * @param BeforeGenerateResultEvent $event
	 */
	public function on_before_generate( $event ): void {
		self::$start_times[] = array(
			'time'   => microtime( true ),
			'caller' => self::resolve_caller(),
		);
	}

	/**
	 * Logs the completed AI generation call to the database.
	 *
	 * @param AfterGenerateResultEvent $event
	 */
	public function on_after_generate( $event ): void {
		global $wpdb;

		$context     = ! empty( self::$start_times ) ? array_pop( self::$start_times ) : array( 'time' => microtime( true ), 'caller' => self::resolve_caller() );
		$duration_ms = (int) round( ( microtime( true ) - $context['time'] ) * 1000 );
		$caller      = $context['caller'];

		$result     = $event->getResult();
		$tokens     = $result->getTokenUsage();
		$provider   = $result->getProviderMetadata();
		$model      = $result->getModelMetadata();
		$candidates = $result->getCandidates();
		$candidate  = ! empty( $candidates ) ? $candidates[0] : null;

		$capability    = $event->getCapability();
		$prompt_text   = self::extract_prompt_text( $event->getMessages() );
		$response_text = $candidate ? self::extract_message_text( $candidate->getMessage() ) : '';
		$finish_reason = $candidate ? $candidate->getFinishReason()->value : '';
		$thought_tokens = $tokens->getThoughtTokens();

		$data    = array(
			'result_id'         => $result->getId(),
			'capability'        => $capability ? $capability->value : '',
			'provider_id'       => $provider->getId(),
			'provider_name'     => $provider->getName(),
			'model_id'          => $model->getId(),
			'model_name'        => $model->getName(),
			'prompt_text'       => $prompt_text,
			'response_text'     => $response_text,
			'prompt_tokens'     => $tokens->getPromptTokens(),
			'completion_tokens' => $tokens->getCompletionTokens(),
			'total_tokens'      => $tokens->getTotalTokens(),
			'finish_reason'     => $finish_reason,
			'duration_ms'       => $duration_ms,
			'source_type'       => $caller['source_type'],
			'source_name'       => $caller['source_name'],
			'source_file'       => $caller['source_file'],
			'source_line'       => $caller['source_line'],
			'user_id'           => get_current_user_id(),
			'created_at'        => current_time( 'mysql', true ),
		);
		$formats = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%s' );

		// Only include thought_tokens if the provider reported it.
		if ( null !== $thought_tokens ) {
			$data['thought_tokens'] = $thought_tokens;
			$formats[]              = '%d';
		}

		$wpdb->insert( self::get_table_name(), $data, $formats ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Deletes log entries older than the configured retention period.
	 * Called daily via WP-Cron.
	 */
	public static function cleanup_old_logs(): void {
		global $wpdb;

		$prefs          = get_option( 'acai_model_manager_preferences', array() );
		$retention_days = isset( $prefs['log_retention_days'] ) ? (int) $prefs['log_retention_days'] : 30;

		if ( $retention_days < 1 ) {
			return;
		}

		$table = self::get_table_name();
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"DELETE FROM `{$table}` WHERE created_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d DAY)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$retention_days
			)
		);
	}

	/**
	 * Walks the call stack to identify the code that called wp_ai_client_prompt().
	 *
	 * Skips internal frames from the AI client SDK, WP hook system, and this
	 * plugin itself. Classifies the first external frame as plugin/theme/core/mu-plugin.
	 *
	 * @return array{ source_type: string, source_name: string, source_file: string, source_line: int }
	 */
	private static function resolve_caller(): array {
		$default = array(
			'source_type' => 'unknown',
			'source_name' => '',
			'source_file' => '',
			'source_line' => 0,
		);

		$frames    = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		$abspath   = wp_normalize_path( ABSPATH );
		$own_dir   = wp_normalize_path( ACAI_MODEL_MANAGER_PLUGIN_PATH );

		foreach ( $frames as $frame ) {
			if ( empty( $frame['file'] ) ) {
				continue;
			}

			$file = wp_normalize_path( $frame['file'] );

			// Skip this plugin's own files.
			if ( 0 === strpos( $file, $own_dir ) ) {
				continue;
			}

			// Skip WP AI client internals and hook system.
			$is_internal = false;
			foreach ( self::$internal_paths as $internal ) {
				if ( false !== strpos( $file, $internal ) ) {
					$is_internal = true;
					break;
				}
			}
			if ( $is_internal ) {
				continue;
			}

			// Found the external caller — classify it.
			$relative = ltrim( str_replace( $abspath, '', $file ), '/' );
			$line     = isset( $frame['line'] ) ? (int) $frame['line'] : 0;

			// Plugin: wp-content/plugins/{slug}/...
			if ( preg_match( '#^wp-content/plugins/([^/]+)/#', $relative, $m ) ) {
				return array(
					'source_type' => 'plugin',
					'source_name' => $m[1],
					'source_file' => $relative,
					'source_line' => $line,
				);
			}

			// MU-Plugin: wp-content/mu-plugins/...
			if ( 0 === strpos( $relative, 'wp-content/mu-plugins/' ) ) {
				return array(
					'source_type' => 'mu-plugin',
					'source_name' => basename( $file, '.php' ),
					'source_file' => $relative,
					'source_line' => $line,
				);
			}

			// Theme: wp-content/themes/{slug}/...
			if ( preg_match( '#^wp-content/themes/([^/]+)/#', $relative, $m ) ) {
				return array(
					'source_type' => 'theme',
					'source_name' => $m[1],
					'source_file' => $relative,
					'source_line' => $line,
				);
			}

			// WordPress core: wp-includes/... or wp-admin/...
			if ( 0 === strpos( $relative, 'wp-includes/' ) || 0 === strpos( $relative, 'wp-admin/' ) ) {
				return array(
					'source_type' => 'core',
					'source_name' => 'wordpress',
					'source_file' => $relative,
					'source_line' => $line,
				);
			}

			// Anything else (e.g. root-level files).
			return array(
				'source_type' => 'unknown',
				'source_name' => '',
				'source_file' => $relative,
				'source_line' => $line,
			);
		}

		return $default;
	}

	/**
	 * Extracts concatenated text content from an array of Message objects.
	 *
	 * @param array $messages List of WordPress\AiClient\Messages\DTO\Message objects.
	 * @return string
	 */
	private static function extract_prompt_text( array $messages ): string {
		$parts = array();
		foreach ( $messages as $message ) {
			$text = self::extract_message_text( $message );
			if ( '' !== $text ) {
				$parts[] = $text;
			}
		}
		return implode( "\n\n", $parts );
	}

	/**
	 * Extracts text content from a single Message object.
	 *
	 * @param object $message A WordPress\AiClient\Messages\DTO\Message instance.
	 * @return string
	 */
	private static function extract_message_text( $message ): string {
		$texts = array();
		foreach ( $message->getParts() as $part ) {
			$channel = $part->getChannel();
			$text    = $part->getText();
			if ( $channel->isContent() && null !== $text ) {
				$texts[] = $text;
			}
		}
		return implode( ' ', $texts );
	}
}
