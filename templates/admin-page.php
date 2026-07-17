<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap printeers-admin">
	<h1><?php esc_html_e( 'Printeers', 'printeers' ); ?></h1>

	<?php if ( ! empty( $disconnect_failed ) ) : ?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'Could not disconnect from Printeers.', 'printeers' ); ?><?php if ( '' !== $disconnect_error ) : ?> <?php echo esc_html( $disconnect_error ); ?><?php endif; ?></p>
			<p><?php esc_html_e( 'Your store is still connected. Please try again in a moment.', 'printeers' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $connected ) : ?>

		<div class="printeers-status printeers-status--connected">
			<span class="dashicons dashicons-yes-alt"></span>
			<strong><?php esc_html_e( 'Connected to Printeers', 'printeers' ); ?></strong>
		</div>

		<?php if ( $store_url ) : ?>
			<p><?php esc_html_e( 'Store URL:', 'printeers' ); ?> <code><?php echo esc_html( $store_url ); ?></code></p>
		<?php endif; ?>

		<p>
			<?php
			printf(
				/* translators: %s: link to the Printeers Dashboard. */
				esc_html__( 'Manage your store in the %s.', 'printeers' ),
				'<a href="' . esc_url( $dashboard_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Printeers Dashboard', 'printeers' ) . '</a>'
			);
			?>
		</p>

		<form method="post">
			<?php echo $nonce_field; ?>
			<button type="submit" name="printeers_disconnect" class="printeers-text-button">
				<span class="dashicons dashicons-dismiss"></span>
				<?php esc_html_e( 'Disconnect', 'printeers' ); ?>
			</button>
		</form>

	<?php else : ?>

		<div class="printeers-status printeers-status--disconnected">
			<span class="dashicons dashicons-warning"></span>
			<strong><?php esc_html_e( 'Not connected', 'printeers' ); ?></strong>
		</div>

		<p><?php esc_html_e( 'Connect your WooCommerce store to Printeers to start selling print-on-demand products.', 'printeers' ); ?></p>

		<form method="post">
			<?php echo $connect_nonce_field; ?>
			<button type="submit" name="printeers_connect" class="button button-primary button-hero">
				<?php esc_html_e( 'Connect to Printeers', 'printeers' ); ?>
			</button>
		</form>

	<?php endif; ?>
</div>
