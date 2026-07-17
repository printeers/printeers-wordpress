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
				'store_url' => $store_url,
				'nonce'     => $nonce,
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
			return array( 'error' => __( 'Invalid or expired nonce.', 'printeers' ) );
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
	 * Disconnect from Printeers. Notifies Printeers first so the store is freed
	 * for a future reconnect, then removes the local API key and connection
	 * state. If Printeers can't be reached the local state is left intact so the
	 * user can retry; otherwise the store would be stuck connected on the
	 * Printeers side. Returns true on success or a WP_Error on failure.
	 */
	public static function disconnect() {
		$key_id          = (int) get_option( 'printeers_api_key_id', 0 );
		$consumer_secret = self::get_consumer_secret( $key_id );

		// Only notify when we still have the credential Printeers needs to
		// authenticate the request; otherwise there is nothing to free remotely.
		if ( $consumer_secret ) {
			$result = self::notify_disconnect( get_option( 'printeers_store_url', home_url() ), $consumer_secret );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		// Remove the API key we created.
		if ( $key_id ) {
			global $wpdb;
			$wpdb->delete( $wpdb->prefix . 'woocommerce_api_keys', array( 'key_id' => $key_id ) );
		}

		delete_option( 'printeers_connected' );
		delete_option( 'printeers_store_url' );
		delete_option( 'printeers_api_key_id' );
		delete_option( 'printeers_connect_nonce' );

		return true;
	}

	/**
	 * Read the plaintext consumer secret WooCommerce stored for our API key.
	 */
	private static function get_consumer_secret( int $key_id ): string {
		if ( ! $key_id ) {
			return '';
		}
		global $wpdb;
		$secret = $wpdb->get_var( $wpdb->prepare(
			"SELECT consumer_secret FROM {$wpdb->prefix}woocommerce_api_keys WHERE key_id = %d",
			$key_id
		) );
		return is_string( $secret ) ? $secret : '';
	}

	/**
	 * Tell the Printeers callback API to mark this store as disconnected.
	 */
	private static function notify_disconnect( string $store_url, string $consumer_secret ) {
		$url = PRINTEERS_CALLBACK_URL . '/woocommerce/disconnect';

		$response = wp_remote_post( $url, array(
			'timeout' => 30,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( array(
				'store_url'       => $store_url,
				'consumer_secret' => $consumer_secret,
			) ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			$message = wp_remote_retrieve_response_message( $response );
			return new \WP_Error( 'printeers_disconnect_error', trim( sprintf(
				/* translators: 1: HTTP status code, 2: HTTP status message. */
				__( 'Printeers returned HTTP %1$s %2$s', 'printeers' ),
				$code,
				$message
			) ) );
		}

		return true;
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

		// Use the first administrator user. We can't use get_current_user_id()
		// because this runs during a REST callback with no WordPress session.
		// The API key's user determines WooCommerce REST API capabilities —
		// an admin user is required for webhook management.
		$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID', 'order' => 'ASC' ) );
		if ( empty( $admins ) ) {
			return new \WP_Error( 'printeers_no_admin', __( 'No administrator user found.', 'printeers' ) );
		}

		$data = array(
			'user_id'         => $admins[0]->ID,
			'description'     => 'Printeers',
			'permissions'     => 'read_write',
			'consumer_key'    => wc_api_hash( $consumer_key ),
			'consumer_secret' => $consumer_secret,
			'truncated_key'   => substr( $consumer_key, -7 ),
		);

		$inserted = $wpdb->insert( $wpdb->prefix . 'woocommerce_api_keys', $data );
		if ( ! $inserted ) {
			return new \WP_Error( 'printeers_key_error', __( 'Failed to create WooCommerce API keys.', 'printeers' ) );
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
			return new \WP_Error( 'printeers_callback_error', sprintf(
				/* translators: 1: HTTP status code, 2: response body. */
				__( 'Printeers returned HTTP %1$s: %2$s', 'printeers' ),
				$code,
				$body
			) );
		}

		return true;
	}
}
