<?php
defined( 'ABSPATH' ) || exit;

$log     = OpenWP_Audit::all();
$week    = 0;
foreach ( $log as $row ) {
	if ( strtotime( $row['time'] ) > time() - 7 * DAY_IN_SECONDS && 0 !== strpos( $row['action'], 'dev_mode' ) ) {
		$week++;
	}
}
$copies  = 0;
foreach ( OpenWP_Settings::posts_with_copies() as $list ) {
	$copies += count( $list );
}
$offered = count( OpenWP_Mcp_Tools::definitions() ) + count( OpenWP_Abilities::tools() ) - count( OpenWP_Abilities::hidden_ours() );
$match   = OpenWP_Skills::detect();
$has_pw  = class_exists( 'WP_Application_Passwords' ) && count( (array) WP_Application_Passwords::get_user_application_passwords( get_current_user_id() ) ) > 0;
$steps   = array(
	array( 'Install OpenWP Bridge', true, '' ),
	array( 'Create an Application Password', $has_pw, OpenWP_Settings::url( 'connect' ) ),
	array( 'Connect your AI', (bool) $last, OpenWP_Settings::url( 'connect' ) ),
	array( 'Ask your AI to change something', $week > 0, '' ),
);
?>
<div class="owp-hero">
	<div class="owp-top"><h1 class="owp-title">Dashboard</h1></div>
	<p class="owp-sub">Let your AI work on this site: read and change page layouts, fill SEO fields and keep notes, with a safety copy before every change. Your AI talks straight to your site: nothing goes to anyone else.</p>
	<div class="owp-status">
		<span class="owp-dot <?php echo $connected ? 'on' : ''; ?>"></span>
		<?php if ( $last ) : ?>
			<span><?php echo $connected ? 'Your AI is connected' : 'Not seen in the last 10 minutes'; ?>
			<small> &middot; <?php echo esc_html( human_time_diff( (int) $last['time'], time() ) ); ?> ago &middot; <?php echo esc_html( $user ? $user->user_login : '' ); ?> &middot; <?php echo esc_html( $last['client'] ); ?></small></span>
		<?php else : ?>
			<span>Waiting for your AI <small>&middot; <a href="<?php echo esc_url( OpenWP_Settings::url( 'connect' ) ); ?>" style="color:#fff">connect it in 3 steps</a></small></span>
		<?php endif; ?>
	</div>
</div>

<div class="owp-stats">
	<div class="owp-stat"><b><?php echo (int) $week; ?></b><span>changes by your AI, last 7 days</span></div>
	<div class="owp-stat"><b><?php echo (int) $copies; ?></b><span>safety copies kept</span></div>
	<div class="owp-stat"><b><?php echo (int) $offered; ?></b><span>tools offered to your AI</span></div>
	<div class="owp-stat"><b><?php echo count( $match ); ?></b><span>skills matching this site</span></div>
</div>

<div class="owp-two">
	<div class="owp-card">
		<h2>Get started</h2>
		<ul class="owp-check">
			<?php foreach ( $steps as $s ) : ?>
				<li class="<?php echo $s[1] ? 'done' : ''; ?>"><span class="owp-tick"><?php echo $s[1] ? '✓' : ''; ?></span>
					<?php echo $s[2] && ! $s[1] ? '<a href="' . esc_url( $s[2] ) . '">' . esc_html( $s[0] ) . '</a>' : esc_html( $s[0] ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<div class="owp-card">
		<h2>Latest activity</h2>
		<?php $rows = array_slice( $log, 0, 5 ); require OPENWP_BRIDGE_DIR . 'views/part-log.php'; ?>
		<p style="margin:12px 0 0"><a href="<?php echo esc_url( OpenWP_Settings::url( 'log' ) ); ?>">See all activity</a></p>
	</div>
</div>
