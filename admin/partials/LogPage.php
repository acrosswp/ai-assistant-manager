<?php
namespace AcrossAI_Model_Manager\Admin\Partials;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use AcrossAI_Model_Manager\Includes\Logger;

/**
 * Admin log viewer page for AI request logs.
 *
 * Registers a sub-page under Settings and renders a WP_List_Table
 * with sorting, filtering, pagination, and a detail view.
 *
 * @since 0.0.4
 * @package AcrossAI_Model_Manager\Admin\Partials
 */
class LogPage {

	const PAGE_SLUG = 'acrossai-model-manager-logs';

	/** @var string */
	private $plugin_name;

	/** @var string */
	private $version;

	/**
	 * @param string $plugin_name The plugin slug.
	 * @param string $version     The plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/** Adds the AI Logs sub-page under Settings. */
	public function add_menu(): void {
		add_submenu_page(
			'acrossai-model-manager',
			__( 'AI Request Logs', 'acrossai-model-manager' ),
			__( 'AI Logs', 'acrossai-model-manager' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/** Renders the log list page or detail view. */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'acrossai-model-manager' ) );
		}

		// Handle single-entry detail view.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['view'] ) && is_numeric( $_GET['view'] ) ) {
			$this->render_detail_view( (int) $_GET['view'] );
			return;
		}

		// Handle "Clear All Logs" form submission.
		if ( isset( $_POST['acai_clear_logs'] ) ) {
			check_admin_referer( 'acai_clear_logs_nonce' );
			global $wpdb;
			$table = Logger::get_table_name();
			$wpdb->query( "TRUNCATE TABLE `{$table}`" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'All logs cleared.', 'acrossai-model-manager' ) . '</p></div>';
		}

		$list_table = new AI_Log_List_Table();
		$list_table->process_bulk_action();
		$list_table->prepare_items();

		$back_url = admin_url( 'admin.php?page=' . Menu::PAGE_SLUG );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'AI Request Logs', 'acrossai-model-manager' ); ?></h1>
			<p>
				<a href="<?php echo esc_url( $back_url ); ?>" class="button">&larr; <?php esc_html_e( 'Model Manager Settings', 'acrossai-model-manager' ); ?></a>
				&nbsp;
				<form method="post" style="display:inline;">
					<?php wp_nonce_field( 'acai_clear_logs_nonce' ); ?>
					<input
						type="submit"
						name="acai_clear_logs"
						class="button button-secondary"
						value="<?php esc_attr_e( 'Clear All Logs', 'acrossai-model-manager' ); ?>"
						onclick="return confirm('<?php esc_attr_e( 'Are you sure? This will permanently delete all log entries.', 'acrossai-model-manager' ); ?>')"
					>
				</form>
			</p>
			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>">
				<?php $list_table->display(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders the detail view for a single log entry.
	 *
	 * @param int $id Log entry ID.
	 */
	private function render_detail_view( int $id ): void {
		global $wpdb;

		$table = Logger::get_table_name();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $row ) {
			echo '<div class="wrap"><p>' . esc_html__( 'Log entry not found.', 'acrossai-model-manager' ) . '</p></div>';
			return;
		}

		$back_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		?>
		<div class="wrap">
			<h1>
				<?php esc_html_e( 'Log Entry', 'acrossai-model-manager' ); ?> #<?php echo absint( $row->id ); ?>
			</h1>
			<p><a href="<?php echo esc_url( $back_url ); ?>" class="button">&larr; <?php esc_html_e( 'Back to Logs', 'acrossai-model-manager' ); ?></a></p>

			<table class="widefat striped" style="margin-top:16px;max-width:800px;">
				<tbody>
					<tr><th style="width:160px;"><?php esc_html_e( 'Date (UTC)', 'acrossai-model-manager' ); ?></th><td><?php echo esc_html( $row->created_at ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Capability', 'acrossai-model-manager' ); ?></th><td><?php echo esc_html( $row->capability ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Provider', 'acrossai-model-manager' ); ?></th><td><?php echo esc_html( $row->provider_name . ' (' . $row->provider_id . ')' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Model', 'acrossai-model-manager' ); ?></th><td><?php echo esc_html( $row->model_name . ' (' . $row->model_id . ')' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Finish Reason', 'acrossai-model-manager' ); ?></th><td><?php echo esc_html( $row->finish_reason ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Duration', 'acrossai-model-manager' ); ?></th><td><?php echo absint( $row->duration_ms ); ?> ms</td></tr>
					<tr><th><?php esc_html_e( 'Prompt Tokens', 'acrossai-model-manager' ); ?></th><td><?php echo absint( $row->prompt_tokens ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Completion Tokens', 'acrossai-model-manager' ); ?></th><td><?php echo absint( $row->completion_tokens ); ?></td></tr>
					<?php if ( null !== $row->thought_tokens ) : ?>
						<tr><th><?php esc_html_e( 'Thought Tokens', 'acrossai-model-manager' ); ?></th><td><?php echo absint( $row->thought_tokens ); ?></td></tr>
					<?php endif; ?>
					<tr><th><?php esc_html_e( 'Total Tokens', 'acrossai-model-manager' ); ?></th><td><?php echo absint( $row->total_tokens ); ?></td></tr>
					<tr><th><?php esc_html_e( 'User ID', 'acrossai-model-manager' ); ?></th><td><?php echo absint( $row->user_id ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Result ID', 'acrossai-model-manager' ); ?></th><td><?php echo esc_html( $row->result_id ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Source Type', 'acrossai-model-manager' ); ?></th><td><?php echo esc_html( $row->source_type ?: '—' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Source Name', 'acrossai-model-manager' ); ?></th><td><?php echo esc_html( $row->source_name ?: '—' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Source File', 'acrossai-model-manager' ); ?></th><td><code><?php echo esc_html( $row->source_file ?: '—' ); ?></code></td></tr>
					<tr><th><?php esc_html_e( 'Source Line', 'acrossai-model-manager' ); ?></th><td><?php echo $row->source_line ? absint( $row->source_line ) : '—'; ?></td></tr>
				</tbody>
			</table>

			<h2 style="margin-top:24px;"><?php esc_html_e( 'Prompt', 'acrossai-model-manager' ); ?></h2>
			<div style="background:#fff;border:1px solid #ddd;padding:16px;max-width:800px;max-height:400px;overflow-y:auto;white-space:pre-wrap;font-family:monospace;font-size:13px;">
				<?php echo esc_html( $row->prompt_text ); ?>
			</div>

			<h2 style="margin-top:24px;"><?php esc_html_e( 'Response', 'acrossai-model-manager' ); ?></h2>
			<div style="background:#fff;border:1px solid #ddd;padding:16px;max-width:800px;max-height:400px;overflow-y:auto;white-space:pre-wrap;font-family:monospace;font-size:13px;">
				<?php echo esc_html( $row->response_text ); ?>
			</div>
		</div>
		<?php
	}
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * WP_List_Table subclass for displaying AI request logs.
 *
 * Defined in the same file as LogPage to avoid autoloader ambiguity.
 *
 * @since 0.0.4
 */
class AI_Log_List_Table extends \WP_List_Table {

	/** @inheritDoc */
	public function get_columns(): array {
		return array(
			'cb'           => '<input type="checkbox">',
			'created_at'   => __( 'Date', 'acrossai-model-manager' ),
			'capability'   => __( 'Capability', 'acrossai-model-manager' ),
			'provider_id'  => __( 'Provider', 'acrossai-model-manager' ),
			'model_id'     => __( 'Model', 'acrossai-model-manager' ),
			'source_type'  => __( 'Source', 'acrossai-model-manager' ),
			'total_tokens' => __( 'Tokens', 'acrossai-model-manager' ),
			'duration_ms'  => __( 'Duration', 'acrossai-model-manager' ),
			'finish_reason'=> __( 'Finish', 'acrossai-model-manager' ),
		);
	}

	/** @inheritDoc */
	protected function get_sortable_columns(): array {
		return array(
			'created_at'   => array( 'created_at', true ),
			'source_type'  => array( 'source_type', false ),
			'total_tokens' => array( 'total_tokens', false ),
			'duration_ms'  => array( 'duration_ms', false ),
		);
	}

	/** @inheritDoc */
	protected function get_bulk_actions(): array {
		return array(
			'delete' => __( 'Delete', 'acrossai-model-manager' ),
		);
	}

	/** @inheritDoc */
	public function process_bulk_action(): void {
		if ( 'delete' !== $this->current_action() ) {
			return;
		}

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'bulk-' . $this->_args['plural'] ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'acrossai-model-manager' ) );
		}

		$ids = isset( $_POST['log'] ) ? array_map( 'absint', (array) $_POST['log'] ) : array();
		if ( empty( $ids ) ) {
			return;
		}

		global $wpdb;
		$table       = Logger::get_table_name();
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"DELETE FROM `{$table}` WHERE id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$ids
			)
		);
	}

	/** @inheritDoc */
	public function prepare_items(): void {
		global $wpdb;

		$table    = Logger::get_table_name();
		$per_page = 20;
		$page     = $this->get_pagenum();
		$offset   = ( $page - 1 ) * $per_page;

		// Sorting.
		$sortable  = $this->get_sortable_columns();
		$orderby   = isset( $_GET['orderby'] ) && array_key_exists( sanitize_key( $_GET['orderby'] ), $sortable ) // phpcs:ignore WordPress.Security.NonceVerification
			? sanitize_key( $_GET['orderby'] ) // phpcs:ignore WordPress.Security.NonceVerification
			: 'created_at';
		$order     = isset( $_GET['order'] ) && 'asc' === strtolower( sanitize_key( $_GET['order'] ) ) // phpcs:ignore WordPress.Security.NonceVerification
			? 'ASC'
			: 'DESC';

		// Total count.
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Page of results.
		$this->items = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT * FROM `{$table}` ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$per_page,
				$offset
			),
			ARRAY_A
		);

		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total / $per_page ),
			)
		);

		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
	}

	/** @inheritDoc */
	protected function column_cb( $item ): string {
		return sprintf( '<input type="checkbox" name="log[]" value="%d">', absint( $item['id'] ) );
	}

	/** @inheritDoc */
	protected function column_default( $item, $column_name ): string {
		switch ( $column_name ) {
			case 'created_at':
				$view_url = admin_url( 'admin.php?page=' . LogPage::PAGE_SLUG . '&view=' . absint( $item['id'] ) );
				return sprintf(
					'<a href="%s"><strong>%s</strong></a>',
					esc_url( $view_url ),
					esc_html( $item['created_at'] )
				);

			case 'capability':
				return esc_html( str_replace( '_', ' ', $item['capability'] ) );

			case 'source_type':
				$type = $item['source_type'] ?? '';
				$name = $item['source_name'] ?? '';
				if ( '' === $type ) {
					return '—';
				}
				$label = $name ? esc_html( $type . ': ' . $name ) : esc_html( $type );
				$file  = $item['source_file'] ?? '';
				$line  = ! empty( $item['source_line'] ) ? (int) $item['source_line'] : 0;
				$title = $file ? esc_attr( $file . ( $line ? ':' . $line : '' ) ) : '';
				return $title
					? '<span title="' . $title . '">' . $label . '</span>'
					: $label;

			case 'provider_id':
				return esc_html( $item['provider_name'] ?: $item['provider_id'] );

			case 'model_id':
				return esc_html( $item['model_name'] ?: $item['model_id'] );

			case 'total_tokens':
				return sprintf(
					'<span title="%s">%s</span>',
					esc_attr( sprintf(
						/* translators: 1: prompt tokens, 2: completion tokens */
						__( 'Prompt: %1$d / Completion: %2$d', 'acrossai-model-manager' ),
						absint( $item['prompt_tokens'] ),
						absint( $item['completion_tokens'] )
					) ),
					absint( $item['total_tokens'] )
				);

			case 'duration_ms':
				return absint( $item['duration_ms'] ) . ' ms';

			case 'finish_reason':
				return esc_html( $item['finish_reason'] );

			default:
				return esc_html( $item[ $column_name ] ?? '' );
		}
	}
}
