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

		// Redirect to the admin page to show the connected state.
		return new \WP_REST_Response(
			null,
			302,
			array( 'Location' => admin_url( 'admin.php?page=printeers&connected=1' ) )
		);
	}
}
