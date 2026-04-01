<?php

namespace Printeers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_disconnect' ) );
	}

	public static function add_menu_page() {
		add_menu_page(
			'Printeers',
			'Printeers',
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
		if ( ! wp_verify_nonce( $_POST['printeers_disconnect_nonce'], 'printeers_disconnect' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		Connect::disconnect();
		wp_safe_redirect( admin_url( 'admin.php?page=printeers&disconnected=1' ) );
		exit;
	}

	public static function render_page() {
		$connected     = get_option( 'printeers_connected', false );
		$store_url     = get_option( 'printeers_store_url', '' );
		$connect_url   = Connect::get_connect_url();
		$dashboard_url = PRINTEERS_DASHBOARD_URL;
		$nonce_field   = wp_nonce_field( 'printeers_disconnect', 'printeers_disconnect_nonce', true, false );

		include PRINTEERS_PLUGIN_DIR . 'templates/admin-page.php';
	}
}
