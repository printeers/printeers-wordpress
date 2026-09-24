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
		add_action( 'admin_init', array( __CLASS__, 'handle_remove_local' ) );
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

		// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- the Dashboard is an external host by design.
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
			'data:image/svg+xml;base64,PHN2ZyB2ZXJzaW9uPSIxLjEiIGlkPSJMYXllcl8xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4PSIwcHgiIHk9IjBweCIKCSB2aWV3Qm94PSIwIDAgNTAwIDU4OC45NSIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgNTAwIDU4OC45NTsiIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8Zz4KCTxwYXRoIGQ9Ik01MDAsMjQ1LjI1bC02MC4zMS0zNC44MmwtMTUyLjItODcuODd2MGwtOTIuODEtNTMuNThsMCwwbC0wLjAyLTAuMDFMNzUuMjIsMGwwLDAuMTVMNzQuOTcsMGwwLjIsMzUuNGwtMC4xLDY3LjQ1CgkJbC0wLjEtMC4wNkwwLDU5LjY2djBsMCwwdjM1Mi45OGwwLDBsMCwwLjAzdjEzNy40OGwwLjI1LTAuMTVsMCwwLjE1bDc0LjcyLTQzbDAuMTItMC4wN2wwLjEyLDgxLjg2TDUwMCwzNDMuN2wtODQuOTctNDkuMjMKCQlsNDcuNTYtMjcuNTVMNTAwLDI0NS4yNXogTTE0OS45MywyMTYuMjZsNDUuOTgsMjYuNTVsMCwwbDAsMGw4OS40OCw1MS42NmwtMTM1LjQ3LDc4LjIxdi00Ni42MWgwVjIxNi4yNnoiLz4KPC9nPgo8L3N2Zz4=',
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

	/**
	 * Remove a connection that belongs to another site (the admin page offers
	 * this when the stored store URL is not this site's URL, i.e. the site was
	 * copied or moved). Only local state is removed; Printeers is not told, so
	 * the original site's store stays connected. Afterwards the page shows
	 * "Not connected" and Connect works as a fresh connect for this site.
	 */
	public static function handle_remove_local() {
		if ( ! isset( $_POST['printeers_remove_local'] ) ) {
			return;
		}
		if ( ! isset( $_POST['printeers_remove_local_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['printeers_remove_local_nonce'] ) ), 'printeers_remove_local' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		Connect::remove_local();

		wp_safe_redirect( admin_url( 'admin.php?page=printeers&removed=1' ) );
		exit;
	}

	public static function render_page() {
		$connected     = (bool) get_option( 'printeers_connected', false );
		$store_url     = (string) get_option( 'printeers_store_url', '' );
		$site_url      = home_url();
		$dashboard_url = PRINTEERS_DASHBOARD_URL;

		// The stored store URL is the site's URL at connect time. If it is
		// another site now, the database was copied (or the site moved) and
		// the connection is the other site's: a Disconnect from here would pause
		// that store. Compare normalized, as the Printeers side does.
		$site_mismatch = $connected && '' !== $store_url && Connect::normalize_url( $store_url ) !== Connect::normalize_url( $site_url );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- display-only flags, no state change.
		$disconnect_failed = isset( $_GET['disconnect_error'] );
		$removed           = isset( $_GET['removed'] );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
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
