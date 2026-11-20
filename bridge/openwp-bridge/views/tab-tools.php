<?php
defined( 'ABSPATH' ) || exit;

$ns = OpenWP_Abilities::namespaces();
?>
<div class="owp-card">
	<h2>Plugin tools</h2>
	<p class="lead">Some plugins bring their own tools for AI (WordPress calls them &#8220;abilities&#8221;): WooCommerce, Elementor, Bricks and more. Choose who does the job.</p>
	<?php if ( ! OpenWP_Abilities::supported() ) : ?>
		<p class="owp-note">This WordPress version does not have the Abilities API (it comes with WordPress 6.9).</p>
	<?php else : ?>
		<div class="owp-chips" style="margin:0 0 14px">
			<?php foreach ( $ns as $n => $count ) : ?>
				<span class="owp-chip"><i></i><?php echo esc_html( $n . ' · ' . $count ); ?></span>
			<?php endforeach; ?>
		</div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="openwp_bridge_prefer">
			<?php wp_nonce_field( 'openwp_bridge_prefer' ); ?>
			<div class="owp-tiles">
				<?php foreach ( OpenWP_Abilities::areas() as $key => $a ) : $cur = OpenWP_Abilities::choice( $key ); $has = isset( $ns[ $a['ns'] ] ) ? (int) $ns[ $a['ns'] ] : 0; ?>
					<div class="owp-tile">
						<b><?php echo esc_html( $a['label'] ); ?></b>
						<span><?php echo $has ? esc_html( $has . ' tools of the plugin found' ) : 'The plugin has not published tools yet (Bricks: Bricks > AI; Elementor: its settings).'; ?></span>
						<p style="margin:10px 0 0">
							<label style="display:block"><input type="radio" name="<?php echo esc_attr( $key ); ?>" value="openwp" <?php checked( $cur, 'openwp' ); ?>> OpenWP</label>
							<label style="display:block"><input type="radio" name="<?php echo esc_attr( $key ); ?>" value="vendor" <?php checked( $cur, 'vendor' ); ?>> The plugin&#8217;s own</label>
							<label style="display:block"><input type="radio" name="<?php echo esc_attr( $key ); ?>" value="both" <?php checked( $cur, 'both' ); ?>> Both</label>
						</p>
					</div>
				<?php endforeach; ?>
			</div>
			<p style="margin:14px 0 0"><button type="submit" class="owp-btn">Save</button></p>
		</form>
		<p class="owp-note">Every other tool from your plugins (Yoast, ACF, forms...) is offered to your AI automatically.</p>
	<?php endif; ?>
</div>
