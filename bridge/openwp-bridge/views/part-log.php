<?php
/** List of log rows. Variable: $rows. */
defined( 'ABSPATH' ) || exit;

$icons  = array( 'write_builder' => 'layout', 'write_meta' => 'search', 'ability' => 'plug', 'dev_php' => 'bolt', 'dev_sql' => 'database', 'dev_file_read' => 'file', 'dev_file_write' => 'file-edit', 'dev_file_list' => 'folder', 'dev_mode_on' => 'power', 'dev_mode_off' => 'power', 'prefer_saved' => 'gear' );
$labels = array( 'write_builder' => 'Changed the layout of a page', 'write_meta' => 'Updated SEO fields', 'ability' => 'Used a tool of a plugin', 'dev_php' => 'Ran PHP code', 'dev_sql' => 'Read the database', 'dev_file_read' => 'Read a file', 'dev_file_write' => 'Wrote a file', 'dev_file_list' => 'Listed a folder', 'dev_mode_on' => 'Developer mode turned ON', 'dev_mode_off' => 'Developer mode turned OFF', 'prefer_saved' => 'Plugin tools choice saved' );
?>
<?php if ( ! $rows ) : ?>
	<div class="owp-empty">Nothing yet. When your AI changes something, you will see it here.</div>
<?php else : ?>
	<ul class="owp-log">
		<?php foreach ( $rows as $row ) : ?>
			<li>
				<div class="owp-ico"><?php echo OpenWP_Icons::get( isset( $icons[ $row['action'] ] ) ? $icons[ $row['action'] ] : 'dot' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<div><b><?php echo esc_html( isset( $labels[ $row['action'] ] ) ? $labels[ $row['action'] ] : $row['action'] ); ?></b>
				<small><?php echo esc_html( human_time_diff( strtotime( $row['time'] ), time() ) ); ?> ago &middot; <code><?php echo esc_html( mb_substr( $row['detail'], 0, 110 ) ); ?></code></small></div>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
