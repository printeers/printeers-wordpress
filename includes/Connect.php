<?php

namespace Printeers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Connect {

	/**
	 * Build the URL that redirects the user to the Printeers Dashboard to log in
	 * and complete the connection. The dashboard validates the store, creates an
	 * authflow, and redirects back to our connect-callback endpoint.
	 */
	public static function get_connect_url(): string {
		$nonce = self::generate_nonce();
		update_option( 'printeers_connect_nonce', $nonce );

		$store_url = home_url();

		return add_query_arg(
			array(
				'store_url' => rawurlencode( $store_url ),
				'nonce'     => rawurlencode( $nonce ),
			),
			PRINTEERS_DASHBOARD_URL . '/woocommerce/connect'
		);
	}

	/**
	 * Called by the Api class when the dashboard redirects back with a nonce.
	 * Verifies the nonce, generates WooCommerce API keys, and sends them to
	 * the Printeers callback API.
	 */
	public static function complete_connect( string $nonce ): array {
		$stored_nonce = get_option( 'printeers_connect_nonce', '' );

		if ( empty( $stored_nonce ) || ! hash_equals( $stored_nonce, $nonce ) ) {
			return array( 'error' => 'Invalid or expired nonce.' );
		}

		// Clear the nonce so it can't be reused.
		delete_option( 'printeers_connect_nonce' );

		// Generate WooCommerce REST API keys.
		$keys = self::create_api_keys();
		if ( is_wp_error( $keys ) ) {
			return array( 'error' => $keys->get_error_message() );
		}

		// Send credentials to the Printeers callback API.
		$result = self::send_credentials( $nonce, $keys['consumer_key'], $keys['consumer_secret'] );
		if ( is_wp_error( $result ) ) {
			return array( 'error' => $result->get_error_message() );
		}

		// Mark as connected.
		update_option( 'printeers_connected', true );
		update_option( 'printeers_store_url', home_url() );

		return array( 'success' => true );
	}

	/**
	 * Disconnect from Printeers. Removes stored credentials and connection state.
	 */
	public static function disconnect() {
		// Remove the API key we created.
		$key_id = get_option( 'printeers_api_key_id', 0 );
		if ( $key_id ) {
			global $wpdb;
			$wpdb->delete( $wpdb->prefix . 'woocommerce_api_keys', array( 'key_id' => $key_id ) );
		}

		delete_option( 'printeers_connected' );
		delete_option( 'printeers_store_url' );
		delete_option( 'printeers_api_key_id' );
		delete_option( 'printeers_connect_nonce' );
	}

	private static function generate_nonce(): string {
		return bin2hex( random_bytes( 22 ) );
	}

	/**
	 * Create WooCommerce REST API keys for Printeers.
	 */
	private static function create_api_keys() {
		global $wpdb;

		$consumer_key    = 'ck_' . bin2hex( random_bytes( 20 ) );
		$consumer_secret = 'cs_' . bin2hex( random_bytes( 20 ) );

		$data = array(
			'user_id'         => get_current_user_id(),
			'description'     => 'Printeers',
			'permissions'     => 'read_write',
			'consumer_key'    => wc_api_hash( $consumer_key ),
			'consumer_secret' => $consumer_secret,
			'truncated_key'   => substr( $consumer_key, -7 ),
		);

		$inserted = $wpdb->insert( $wpdb->prefix . 'woocommerce_api_keys', $data );
		if ( ! $inserted ) {
			return new \WP_Error( 'printeers_key_error', 'Failed to create WooCommerce API keys.' );
		}

		update_option( 'printeers_api_key_id', $wpdb->insert_id );

		return array(
			'consumer_key'    => $consumer_key,
			'consumer_secret' => $consumer_secret,
		);
	}

	/**
	 * Send the generated credentials to the Printeers callback API.
	 */
	private static function send_credentials( string $nonce, string $consumer_key, string $consumer_secret ) {
		$url = PRINTEERS_CALLBACK_URL . '/woocommerce/auth-callback/' . rawurlencode( $nonce );

		$response = wp_remote_post( $url, array(
			'timeout' => 30,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( array(
				'consumer_key'    => $consumer_key,
				'consumer_secret' => $consumer_secret,
			) ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			$body = wp_remote_retrieve_body( $response );
			return new \WP_Error( 'printeers_callback_error', "Printeers returned HTTP $code: $body" );
		}

		return true;
	}
}
