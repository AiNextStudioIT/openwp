<?php
defined( 'ABSPATH' ) || exit;

$notes = OpenWP_Memory::search( '' );
?>
<div class="owp-card">
	<h2>Memory</h2>
	<p class="lead">Notes your AI keeps about this site, so it remembers between sessions. They stay on this site.</p>
	<?php if ( ! $notes ) : ?>
		<div class="owp-empty">No notes yet. Ask your AI to remember something, for example &#8220;remember that products stay as drafts&#8221;.</div>
	<?php else : ?>
		<ul class="owp-log">
			<?php foreach ( $notes as $n ) : ?>
				<li>
					<div class="owp-ico"><?php echo OpenWP_Icons::get( 'brain' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<div style="flex:1"><b><?php echo esc_html( $n['key'] ); ?></b><small><?php echo esc_html( $n['note'] ); ?><br><?php echo esc_html( human_time_diff( strtotime( $n['updated'] ), time() ) ); ?> ago</small></div>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="openwp_bridge_forget"><input type="hidden" name="key" value="<?php echo esc_attr( $n['key'] ); ?>">
						<?php wp_nonce_field( 'openwp_bridge_forget' ); ?>
						<button type="submit" class="owp-btn ghost">Forget</button>
					</form>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
