<?php
defined( 'ABSPATH' ) || exit;

$posts = OpenWP_Settings::posts_with_copies();
?>
<div class="owp-card">
	<h2>Safety copies</h2>
	<p class="lead">Before your AI changes a layout, a copy of the old one is saved (the last 10 for each page). Put one back here.</p>
	<?php if ( ! $posts ) : ?>
		<div class="owp-empty">No copies yet. They appear when your AI changes a page.</div>
	<?php else : ?>
		<?php foreach ( $posts as $id => $list ) : ?>
			<div class="owp-copy-box">
				<div class="owp-devrow">
					<div><b><?php echo esc_html( get_the_title( $id ) ?: '(no title)' ); ?></b> <small>#<?php echo (int) $id; ?> &middot; <a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>">edit</a> &middot; <a href="<?php echo esc_url( get_permalink( $id ) ); ?>" target="_blank" rel="noopener">view</a></small></div>
				</div>
				<ul class="owp-log">
					<?php foreach ( array_slice( $list, 0, 5 ) as $b ) : ?>
						<li>
							<div class="owp-ico">📋</div>
							<div style="flex:1"><b>Copy <?php echo (int) $b['id']; ?> &middot; <?php echo esc_html( $b['builder'] ); ?></b><small><?php echo esc_html( human_time_diff( strtotime( $b['time'] ), time() ) ); ?> ago<?php echo $b['note'] ? ' &middot; ' . esc_html( $b['note'] ) : ''; ?></small></div>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<input type="hidden" name="action" value="openwp_bridge_restore"><input type="hidden" name="post" value="<?php echo (int) $id; ?>"><input type="hidden" name="backup" value="<?php echo (int) $b['id']; ?>">
								<?php wp_nonce_field( 'openwp_bridge_restore' ); ?>
								<button type="submit" class="owp-btn ghost">Put it back</button>
							</form>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endforeach; ?>
		<p class="owp-note">Putting a copy back also saves the layout that is there now, so you can undo it.</p>
	<?php endif; ?>
</div>
