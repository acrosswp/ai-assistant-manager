<?php
/**
 * Admin settings page.
 *
 * @package ai-assistant-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the Settings > AI Assistant Manager admin page.
 */
class AAM_Settings_Page {

	const OPTION_KEY = 'aam_model_preferences';
	const PAGE_SLUG  = 'ai-assistant-manager';

	/**
	 * Capability types shown on the settings page.
	 *
	 * @var array<string, string>
	 */
	private static $capabilities = array(
		'text_generation'  => 'Text Generation',
		'image_generation' => 'Image Generation',
		'vision'           => 'Vision / Multimodal',
	);

	/** Registers admin hooks. */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/** Adds the options sub-menu page. */
	public function add_menu(): void {
		add_options_page(
			__( 'AI Assistant Manager', 'ai-assistant-manager' ),
			__( 'AI Assistant Manager', 'ai-assistant-manager' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/** Registers the settings option. */
	public function register_settings(): void {
		register_setting(
			'aam_settings_group',
			self::OPTION_KEY,
			array(
				'sanitize_callback' => array( $this, 'sanitize_preferences' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitizes model preferences before saving.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, string>
	 */
	public function sanitize_preferences( $input ): array {
		$clean = array();
		if ( ! is_array( $input ) ) {
			return $clean;
		}
		foreach ( array_keys( self::$capabilities ) as $cap_key ) {
			if ( empty( $input[ $cap_key ] ) ) {
				continue;
			}
			$value = sanitize_text_field( wp_unslash( $input[ $cap_key ] ) );
			if ( false === strpos( $value, '::' ) ) {
				continue;
			}
			list( $provider, $model ) = explode( '::', $value, 2 );
			$provider                 = sanitize_key( $provider );
			$model                    = sanitize_text_field( $model );
			if ( $provider && $model ) {
				$clean[ $cap_key ] = $provider . '::' . $model;
			}
		}
		return $clean;
	}

	/**
	 * Enqueues admin CSS only on this plugin's settings page.
	 *
	 * @param string $hook Admin page hook.
	 */
	public function enqueue_styles( string $hook ): void {
		if ( 'settings_page_ai-assistant-manager' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'aam-admin', AAM_PLUGIN_URL . 'assets/css/admin.css', array(), AAM_VERSION );
	}

	/** Renders the settings page HTML. */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ai-assistant-manager' ) );
		}
		$preferences = (array) get_option( self::OPTION_KEY, array() );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'AI Assistant Manager', 'ai-assistant-manager' ); ?></h1>
			<p><?php echo esc_html__( 'Choose the preferred AI model for each capability type. These selections override the WordPress defaults.', 'ai-assistant-manager' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'aam_settings_group' ); ?>
				<table class="widefat aam-models-table">
					<thead><tr>
						<th><?php esc_html_e( 'Capability Type', 'ai-assistant-manager' ); ?></th>
						<th><?php esc_html_e( 'Preferred Model', 'ai-assistant-manager' ); ?></th>
					</tr></thead>
					<tbody>
						<?php foreach ( self::$capabilities as $cap_key => $cap_label ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $cap_label ); ?></strong></td>
							<td>
								<select name="aam_model_preferences[<?php echo esc_attr( $cap_key ); ?>]" class="aam-model-select">
									<option value=""><?php esc_html_e( '&mdash; Use WordPress Default &mdash;', 'ai-assistant-manager' ); ?></option>
									<?php
									$current = isset( $preferences[ $cap_key ] ) ? $preferences[ $cap_key ] : '';
									$models  = $this->get_models_for_capability( $cap_key );
									foreach ( $models as $value => $label ) :
										?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'ai-assistant-manager' ); ?>">
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Returns all available AI models from configured providers via the AiClient registry.
	 *
	 * @return array<int, array{provider: string, provider_label: string, id: string, label: string, capabilities: list<string>}>
	 */
	private function get_all_ai_models(): array {
		if ( ! class_exists( \WordPress\AiClient\AiClient::class ) ) {
			return array();
		}

		$models = array();

		try {
			$registry = \WordPress\AiClient\AiClient::defaultRegistry();

			foreach ( $registry->getRegisteredProviderIds() as $provider_id ) {
				if ( ! $registry->isProviderConfigured( $provider_id ) ) {
					continue;
				}

				$class_name    = $registry->getProviderClassName( $provider_id );
				$provider_meta = $class_name::metadata();

				foreach ( $class_name::modelMetadataDirectory()->listModelMetadata() as $model_meta ) {
					$models[] = array(
						'provider'       => (string) $provider_id,
						'provider_label' => (string) $provider_meta->getName(),
						'id'             => (string) $model_meta->getId(),
						'label'          => (string) $model_meta->getName(),
						'capabilities'   => array_map( 'strval', $model_meta->getSupportedCapabilities() ),
					);
				}
			}
		} catch ( \Throwable $e ) {
			return array();
		}

		return $models;
	}

	/**
	 * Returns model options for the given capability key.
	 *
	 * @param string $cap_key One of: text_generation, image_generation, vision.
	 * @return array<string, string>
	 */
	private function get_models_for_capability( string $cap_key ): array {
		// Vision models are text-generation models with multimodal input support.
		$capability_map = array(
			'text_generation'  => \WordPress\AiClient\Providers\Models\Enums\CapabilityEnum::TEXT_GENERATION,
			'image_generation' => \WordPress\AiClient\Providers\Models\Enums\CapabilityEnum::IMAGE_GENERATION,
			'vision'           => \WordPress\AiClient\Providers\Models\Enums\CapabilityEnum::TEXT_GENERATION,
		);

		if ( ! isset( $capability_map[ $cap_key ] ) ) {
			return array();
		}

		$required_capability = $capability_map[ $cap_key ];
		$all_models          = $this->get_all_ai_models();

		$models = array();
		foreach ( $all_models as $model ) {
			if ( ! in_array( $required_capability, $model['capabilities'], true ) ) {
				continue;
			}
			$key            = $model['provider'] . '::' . $model['id'];
			$models[ $key ] = $model['provider_label'] . ': ' . $model['label'];
		}

		return $models;
	}
}
