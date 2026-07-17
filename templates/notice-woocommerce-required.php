<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="error">
	<p>
		<?php
		printf(
			/* translators: %s: plugin name wrapped in <strong> tags. */
			esc_html__( '%s requires WooCommerce to be installed and active.', 'printeers' ),
			'<strong>Printeers</strong>'
		);
		?>
	</p>
</div>
