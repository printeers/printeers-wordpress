<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap printeers-admin">
	<h1>Printeers</h1>

	<?php if ( $connected ) : ?>

		<div class="printeers-status printeers-status--connected">
			<span class="dashicons dashicons-yes-alt"></span>
			<strong>Connected to Printeers</strong>
		</div>

		<?php if ( $store_url ) : ?>
			<p>Store URL: <code><?php echo esc_html( $store_url ); ?></code></p>
		<?php endif; ?>

		<p>
			Manage your store in the
			<a href="<?php echo esc_url( $dashboard_url ); ?>" target="_blank">Printeers Dashboard</a>.
		</p>

		<form method="post">
			<?php echo $nonce_field; ?>
			<button type="submit" name="printeers_disconnect" class="printeers-text-button">
				<span class="dashicons dashicons-dismiss"></span>
				Disconnect
			</button>
		</form>

	<?php else : ?>

		<div class="printeers-status printeers-status--disconnected">
			<span class="dashicons dashicons-warning"></span>
			<strong>Not connected</strong>
		</div>

		<p>Connect your WooCommerce store to Printeers to start selling print-on-demand products.</p>

		<a href="<?php echo esc_url( $connect_url ); ?>" class="button button-primary button-hero">
			Connect to Printeers
		</a>

	<?php endif; ?>
</div>
