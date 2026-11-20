<?php
defined( 'ABSPATH' ) || exit;

$active = wp_list_pluck( OpenWP_Skills::detect()['matching_skills'], 'name' );
?>
<div class="owp-card">
	<h2>Skills</h2>
	<p class="lead">Short guides your AI reads before it works with a builder or a plugin. The ones matching this site are marked.</p>
	<div class="owp-tiles">
		<?php foreach ( OpenWP_Skills::all() as $s ) : ?>
			<div class="owp-tile">
				<b><?php echo esc_html( $s['title'] ?: $s['name'] ); ?></b>
				<span class="owp-chip <?php echo 'verified' === $s['status'] ? '' : 'off'; ?>" style="margin-top:8px"><i></i><?php echo 'verified' === $s['status'] ? 'verified' : 'not verified'; ?></span>
				<?php if ( in_array( $s['name'], $active, true ) ) : ?><span class="owp-chip" style="margin-top:8px;background:#dcfce7;color:#166534"><i></i>on this site</span><?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
