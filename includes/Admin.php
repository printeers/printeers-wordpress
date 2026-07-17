<?php

namespace Printeers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_connect' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_disconnect' ) );
	}

	public static function handle_connect() {
		if ( ! isset( $_POST['printeers_connect'] ) ) {
			return;
		}
		if ( ! isset( $_POST['printeers_connect_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['printeers_connect_nonce'] ) ), 'printeers_connect' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Not wp_safe_redirect: the Dashboard is an external host by design.
		wp_redirect( Connect::get_connect_url() );
		exit;
	}

	public static function add_menu_page() {
		add_menu_page(
			__( 'Printeers', 'printeers' ),
			__( 'Printeers', 'printeers' ),
			'manage_woocommerce',
			'printeers',
			array( __CLASS__, 'render_page' ),
			'dashicons-store',
			58
		);
	}

	public static function enqueue_styles( $hook ) {
		if ( 'toplevel_page_printeers' !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'printeers-admin',
			PRINTEERS_PLUGIN_URL . 'assets/admin.css',
			array(),
			PRINTEERS_VERSION
		);
	}

	public static function handle_disconnect() {
		if ( ! isset( $_POST['printeers_disconnect'] ) ) {
			return;
		}
		if ( ! isset( $_POST['printeers_disconnect_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['printeers_disconnect_nonce'] ) ), 'printeers_disconnect' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$result = Connect::disconnect();
		if ( is_wp_error( $result ) ) {
			// Keep the detail server-side; the query arg is only a flag so we
			// don't leak it through browser history/logs or hit URL limits.
			set_transient(
				'printeers_disconnect_error_' . get_current_user_id(),
				$result->get_error_message(),
				MINUTE_IN_SECONDS
			);
			wp_safe_redirect( add_query_arg(
				array(
					'page'             => 'printeers',
					'disconnect_error' => 1,
				),
				admin_url( 'admin.php' )
			) );
			exit;
		}
		wp_safe_redirect( admin_url( 'admin.php?page=printeers&disconnected=1' ) );
		exit;
	}

	public static function render_page() {
		$connected           = get_option( 'printeers_connected', false );
		$store_url           = get_option( 'printeers_store_url', '' );
		$dashboard_url       = PRINTEERS_DASHBOARD_URL;
		$nonce_field         = wp_nonce_field( 'printeers_disconnect', 'printeers_disconnect_nonce', true, false );
		$connect_nonce_field = wp_nonce_field( 'printeers_connect', 'printeers_connect_nonce', true, false );

		$disconnect_failed = isset( $_GET['disconnect_error'] );
		$disconnect_error  = '';
		if ( $disconnect_failed ) {
			$transient_key = 'printeers_disconnect_error_' . get_current_user_id();
			$stored        = get_transient( $transient_key );
			delete_transient( $transient_key );
			$disconnect_error = is_string( $stored ) ? $stored : '';
		}

		include PRINTEERS_PLUGIN_DIR . 'templates/admin-page.php';
	}
}
