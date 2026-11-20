<?php
defined( 'ABSPATH' ) || exit;

$rows   = OpenWP_Ours::rows();
$status = isset( $_GET['ours'] ) ? sanitize_key( wp_unslash( $_GET['ours'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$todo   = array_filter( $rows, function ( $r ) {
	return ! $r['installed'] && ! $r['hidden'];
} );
?>
<div class="owp-card">
	<h2>Our plugins</h2>
	<p class="lead">Other products by the makers of OpenWP (AI Next Studio). Only the ones you do not have yet are offered. When one is installed, your AI can use it.</p>
	<?php if ( 'done' === $status ) : ?>
		<div class="owp-empty" style="border-color:#16a34a">Installed and activated. Ask your AI what it can do with it now.</div>
	<?php elseif ( 'installed' === $status ) : ?>
		<div class="owp-empty">Installed, but it did not activate. Open Plugins to activate it.</div>
	<?php elseif ( 'error' === $status ) : ?>
		<div class="owp-empty" style="border-color:#dc2626">It could not be installed from here. Use the link to the site instead.</div>
	<?php endif; ?>

	<?php foreach ( $rows as $r ) : ?>
		<div class="owp-copy-box">
			<div class="owp-devrow">
				<div>
					<b><?php echo esc_html( $r['name'] ); ?></b>
					<?php if ( $r['active'] ) : ?><span class="owp-pill on">Active</span><?php elseif ( $r['installed'] ) : ?><span class="owp-pill">Installed, not active</span><?php elseif ( $r['fits'] && '' !== $r['when'] ) : ?><span class="owp-pill">Fits your site</span><?php endif; ?>
					<small style="display:block"><?php echo esc_html( $r['what'] ); ?></small>
				</div>
				<div style="display:flex;gap:8px;align-items:center">
					<?php if ( ! $r['installed'] ) : ?>
						<?php if ( '' !== $r['slug'] && current_user_can( 'install_plugins' ) ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<input type="hidden" name="action" value="openwp_bridge_ours_install"><input type="hidden" name="key" value="<?php echo esc_attr( $r['key'] ); ?>">
								<?php wp_nonce_field( 'openwp_bridge_ours_install' ); ?>
								<button type="submit" class="owp-btn">Install and activate</button>
							</form>
						<?php else : ?>
							<a class="owp-btn ghost" href="<?php echo esc_url( $r['url'] ); ?>" target="_blank" rel="noopener"><?php echo '' === $r['slug'] ? 'Coming soon, see the site' : 'See the site'; ?></a>
						<?php endif; ?>
						<?php if ( ! $r['hidden'] ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<input type="hidden" name="action" value="openwp_bridge_ours_hide"><input type="hidden" name="key" value="<?php echo esc_attr( $r['key'] ); ?>">
								<?php wp_nonce_field( 'openwp_bridge_ours_hide' ); ?>
								<button type="submit" class="owp-btn ghost">Hide</button>
							</form>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>
		</div>
	<?php endforeach; ?>

	<?php if ( OpenWP_Ours::hidden() ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px">
			<input type="hidden" name="action" value="openwp_bridge_ours_hide"><input type="hidden" name="show" value="1"><input type="hidden" name="key" value="">
			<?php wp_nonce_field( 'openwp_bridge_ours_hide' ); ?>
			<button type="submit" class="owp-btn ghost">Show the hidden ones again</button>
		</form>
	<?php endif; ?>
	<p class="owp-note" style="margin-top:12px">Nothing is sent to us: this page only looks at your own plugin list. The Pro versions are bought on each product's site.</p>
</div>
