<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$printeers_key_id = (int) get_option( 'printeers_api_key_id', 0 );
if ( $printeers_key_id ) {
	global $wpdb;
	$wpdb->delete( $wpdb->prefix . 'woocommerce_api_keys', array( 'key_id' => $printeers_key_id ), array( '%d' ) );
}

delete_option( 'printeers_connected' );
delete_option( 'printeers_store_url' );
delete_option( 'printeers_api_key_id' );
delete_option( 'printeers_connect_nonce' );
