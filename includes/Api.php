<?php

namespace Printeers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the REST API endpoint that the Printeers Dashboard redirects back
 * to after the partner has logged in. This endpoint receives the nonce,
 * generates API keys, and sends them to the Printeers callback API.
 */
class Api {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route( 'printeers/v1', '/connect-callback', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'handle_connect_callback' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'nonce' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		) );

		register_rest_route( 'printeers/v1', '/products/by-blueprint/(?P<reference>[A-Za-z0-9_-]+)', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'handle_product_by_blueprint' ),
			'permission_callback' => array( __CLASS__, 'authenticate_request' ),
			'args'                => array(
				'reference' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		) );
	}

	/**
	 * Authenticate a request using the Printeers WooCommerce REST API key via
	 * HTTP Basic Auth. WooCommerce's own REST auth only applies to requests
	 * under the wc/* namespace, so routes under printeers/v1 must validate the
	 * key ourselves against the entry created by Connect::create_api_keys().
	 */
	public static function authenticate_request() {
		if ( ! is_ssl() ) {
			return new \WP_Error(
				'printeers_insecure_transport',
				'HTTPS is required for this endpoint.',
				array( 'status' => 426 )
			);
		}

		if ( empty( $_SERVER['PHP_AUTH_USER'] ) || empty( $_SERVER['PHP_AUTH_PW'] ) ) {
			return new \WP_Error(
				'printeers_unauthorized',
				'Authentication required.',
				array( 'status' => 401 )
			);
		}

		$key_id = (int) get_option( 'printeers_api_key_id', 0 );
		if ( $key_id <= 0 ) {
			return new \WP_Error(
				'printeers_unauthorized',
				'Printeers plugin is not connected.',
				array( 'status' => 401 )
			);
		}

		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT user_id, consumer_key, consumer_secret FROM {$wpdb->prefix}woocommerce_api_keys WHERE key_id = %d",
			$key_id
		) );
		if ( ! $row ) {
			return new \WP_Error(
				'printeers_unauthorized',
				'API key not found.',
				array( 'status' => 401 )
			);
		}

		$provided_key_hash = wc_api_hash( $_SERVER['PHP_AUTH_USER'] );
		if ( ! hash_equals( $row->consumer_key, $provided_key_hash ) ||
			! hash_equals( $row->consumer_secret, $_SERVER['PHP_AUTH_PW'] ) ) {
			return new \WP_Error(
				'printeers_unauthorized',
				'Invalid credentials.',
				array( 'status' => 401 )
			);
		}

		// Run the request as the user the API key was issued to, so capability
		// checks in downstream WP/WC calls see the right permissions (e.g.
		// reading draft/private products via get_posts).
		wp_set_current_user( (int) $row->user_id );

		return true;
	}

	/**
	 * Look up a WooCommerce product by its Printeers blueprint reference,
	 * stored in the _printeers_blueprint_reference post meta.
	 *
	 * Used by the Printeers sync worker to recover from cases where a product
	 * was created in WooCommerce but its ID was not persisted on our side,
	 * so we can avoid creating a duplicate on the next sync.
	 */
	public static function handle_product_by_blueprint( \WP_REST_Request $request ): \WP_REST_Response {
		$reference = $request->get_param( 'reference' );

		$posts = get_posts( array(
			'post_type'              => 'product',
			'post_status'            => array( 'publish', 'draft', 'private', 'pending' ),
			'meta_key'               => '_printeers_blueprint_reference',
			'meta_value'             => $reference,
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'cache_results'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		) );

		if ( empty( $posts ) ) {
			return new \WP_REST_Response(
				array( 'message' => 'Product not found.' ),
				404
			);
		}

		return new \WP_REST_Response(
			array( 'product_id' => (int) $posts[0] ),
			200
		);
	}

	/**
	 * Handle the redirect back from the Printeers Dashboard.
	 *
	 * The dashboard redirects here after the partner logs in:
	 *   {store_url}/wp-json/printeers/v1/connect-callback?nonce={nonce}
	 *
	 * We verify the nonce, create API keys, send them to the callback API,
	 * and redirect to the plugin admin page.
	 */
	public static function handle_connect_callback( \WP_REST_Request $request ): \WP_REST_Response {
		$nonce = $request->get_param( 'nonce' );

		$result = Connect::complete_connect( $nonce );

		if ( isset( $result['error'] ) ) {
			return new \WP_REST_Response(
				array( 'message' => $result['error'] ),
				400
			);
		}

		// Redirect to the Printeers Dashboard to create a store.
		$store_url = home_url();
		$redirect  = PRINTEERS_DASHBOARD_URL . '/settings/stores/create?' . http_build_query( array(
			'woocommerce_store_url' => $store_url,
		) );

		return new \WP_REST_Response(
			null,
			302,
			array( 'Location' => $redirect )
		);
	}
}
