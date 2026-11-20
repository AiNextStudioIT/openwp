<?php
/** List of log rows. Variable: $rows. */
defined( 'ABSPATH' ) || exit;

$icons  = array( 'write_builder' => '🧩', 'write_meta' => '🔎', 'ability' => '🔌', 'dev_php' => '⚡', 'dev_sql' => '🗄️', 'dev_file_read' => '📄', 'dev_file_write' => '📝', 'dev_file_list' => '📁', 'dev_mode_on' => '🟠', 'dev_mode_off' => '🟢', 'prefer_saved' => '⚙️' );
$labels = array( 'write_builder' => 'Changed the layout of a page', 'write_meta' => 'Updated SEO fields', 'ability' => 'Used a tool of a plugin', 'dev_php' => 'Ran PHP code', 'dev_sql' => 'Read the database', 'dev_file_read' => 'Read a file', 'dev_file_write' => 'Wrote a file', 'dev_file_list' => 'Listed a folder', 'dev_mode_on' => 'Developer mode turned ON', 'dev_mode_off' => 'Developer mode turned OFF', 'prefer_saved' => 'Plugin tools choice saved' );
?>
<?php if ( ! $rows ) : ?>
	<div class="owp-empty">Nothing yet. When your AI changes something, you will see it here.</div>
<?php else : ?>
	<ul class="owp-log">
		<?php foreach ( $rows as $row ) : ?>
			<li>
				<div class="owp-ico"><?php echo isset( $icons[ $row['action'] ] ) ? esc_html( $icons[ $row['action'] ] ) : '•'; ?></div>
				<div><b><?php echo esc_html( isset( $labels[ $row['action'] ] ) ? $labels[ $row['action'] ] : $row['action'] ); ?></b>
				<small><?php echo esc_html( human_time_diff( strtotime( $row['time'] ), time() ) ); ?> ago &middot; <code><?php echo esc_html( mb_substr( $row['detail'], 0, 110 ) ); ?></code></small></div>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
