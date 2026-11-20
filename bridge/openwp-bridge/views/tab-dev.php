<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="owp-dev <?php echo $on ? 'on' : ''; ?>">
	<div class="owp-devrow">
		<div style="max-width:560px">
			<h2>Developer mode <span class="owp-badge <?php echo $on ? 'on' : ''; ?>"><?php echo ( $on ? OpenWP_Icons::get( 'dot', 10 ) : OpenWP_Icons::get( 'ring', 10 ) ) . ( $on ? ' ON' : ' OFF' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span></h2>
			<p>Lets the AI run PHP code, read the database and read or write files in <code>wp-content</code>. Powerful, and it can break a site. <strong>Test and staging sites only.</strong> It turns itself off when the time ends, and everything is logged.
			<?php
			if ( $on && $left < 31536000 ) {
				echo '<br><strong>Turns off in ' . esc_html( human_time_diff( time(), time() + $left ) ) . '.</strong>';
			}
			?>
			</p>
		</div>
		<?php if ( OpenWP_Dev::forbidden_for_good() ) : ?>
			<p><strong>Disabled by wp-config.php</strong> (OPENWP_BRIDGE_DISABLE_DEV).</p>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="owp-sw">
				<input type="hidden" name="action" value="openwp_bridge_dev">
				<?php wp_nonce_field( 'openwp_bridge_dev' ); ?>
				<?php if ( $on ) : ?>
					<input type="hidden" name="dev" value="off">
					<button type="submit" class="owp-btn stop">Turn OFF</button>
				<?php else : ?>
					<input type="hidden" name="dev" value="on">
					<select name="hours" aria-label="How long">
						<option value="1">for 1 hour</option>
						<option value="8">for 8 hours</option>
						<option value="24">for 24 hours</option>
						<option value="0">until I turn it off</option>
					</select>
					<button type="submit" class="owp-btn">Turn ON</button>
				<?php endif; ?>
			</form>
		<?php endif; ?>
	</div>
	<div class="owp-warn">Never use it on a store with real customers. Forbid it for good with <code>define( 'OPENWP_BRIDGE_DISABLE_DEV', true );</code> in wp-config.php.</div>
</div>
