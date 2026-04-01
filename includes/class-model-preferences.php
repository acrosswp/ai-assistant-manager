<?php
/**
 * Model preference filter hooks.
 *
 * @package ai-assistant-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hooks into WordPress AI filters to override the default model selection.
 */
class AAM_Model_Preferences {

	/** Registers all filter hooks. */
	public function __construct() {
		add_filter( 'wpai_preferred_text_models', array( $this, 'filter_text_models' ), 1000 );
		add_filter( 'wpai_preferred_image_models', array( $this, 'filter_image_models' ), 1000 );
		add_filter( 'wpai_preferred_vision_models', array( $this, 'filter_vision_models' ), 1000 );
	}

	/**
	 * Filters text generation preferred models.
	 *
	 * @param array $models Current preferred models.
	 * @return array Filtered preferred models.
	 */
	public function filter_text_models( array $models ): array {
		return $this->apply_preference( $models, 'text_generation' );
	}

	/**
	 * Filters image generation preferred models.
	 *
	 * @param array $models Current preferred models.
	 * @return array Filtered preferred models.
	 */
	public function filter_image_models( array $models ): array {
		return $this->apply_preference( $models, 'image_generation' );
	}

	/**
	 * Filters vision preferred models.
	 *
	 * @param array $models Current preferred models.
	 * @return array Filtered preferred models.
	 */
	public function filter_vision_models( array $models ): array {
		return $this->apply_preference( $models, 'vision' );
	}

	/**
	 * Applies the saved preference for a capability key to the models list.
	 *
	 * If a preference is set, it becomes the sole first entry so WordPress
	 * picks it first when iterating candidate models.
	 *
	 * @param array  $models  Current preferred models list.
	 * @param string $cap_key Capability key: text, image, or vision.
	 * @return array Updated preferred models list.
	 */
	private function apply_preference( array $models, string $cap_key ): array {
		$preferences = (array) get_option( AAM_Settings_Page::OPTION_KEY, array() );

		if ( empty( $preferences[ $cap_key ] ) ) {
			return $models;
		}

		$preference = (string) $preferences[ $cap_key ];
		if ( false === strpos( $preference, '::' ) ) {
			return $models;
		}

		list( $provider, $model_id ) = explode( '::', $preference, 2 );

		$provider = sanitize_key( $provider );
		$model_id = sanitize_text_field( $model_id );
		if ( ! $provider || ! $model_id ) {
			return $models;
		}

		// Only apply preference if the provider is connected with an API key.
		if ( ! $this->is_provider_connected( $provider ) ) {
			return $models;
		}

		$preferred_model = array( $provider, $model_id );

		// Prepend preferred model; keep remaining models as fallbacks.
		array_unshift( $models, $preferred_model );
		return $models;
	}

	/**
	 * Checks whether a specific AI provider has an API key entered via the connector screen.
	 *
	 * @param string $provider_id The provider ID (e.g. 'openai', 'anthropic').
	 * @return bool True if the provider has a non-empty API key saved.
	 */
	private function is_provider_connected( string $provider_id ): bool {
		if ( ! function_exists( 'wp_get_connectors' ) ) {
			return false;
		}

		$connectors = wp_get_connectors();

		$has_credentials = false;

		foreach ( $connectors as $connector_id => $connector_data ) {
			if ( $connector_id !== $provider_id ) {
				continue;
			}

			if ( 'ai_provider' !== $connector_data['type'] ) {
				continue;
			}

			$auth = $connector_data['authentication'];
			if ( 'api_key' !== $auth['method'] || empty( $auth['setting_name'] ) ) {
				continue;
			}

			if ( '' === get_option( $auth['setting_name'], '' ) ) {
				continue;
			}

			$has_credentials = true;
			break;
		}

		return (bool) apply_filters( 'aam_has_ai_credentials', $has_credentials, $connectors );
	}
}
