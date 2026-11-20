<?php
/**
 * Tools > OpenWP Bridge page. Variables from OpenWP_Settings::render(): $on, $left, $last, $user, $status, $connected.
 */

defined( 'ABSPATH' ) || exit;

$mcp_url = rest_url( 'openwp/v1/mcp' );
$icons   = array(
	'write_builder' => '🧩',
	'write_meta'    => '🔎',
	'dev_php'       => '⚡',
	'dev_sql'       => '🗄️',
	'dev_file_read' => '📄',
	'dev_file_write'=> '📝',
	'dev_file_list' => '📁',
	'dev_mode_on'   => '🟠',
	'dev_mode_off'  => '🟢',
);
$labels  = array(
	'write_builder' => 'Changed the layout of a page',
	'write_meta'    => 'Updated SEO fields',
	'dev_php'       => 'Ran PHP code',
	'dev_sql'       => 'Read the database',
	'dev_file_read' => 'Read a file',
	'dev_file_write'=> 'Wrote a file',
	'dev_file_list' => 'Listed a folder',
	'dev_mode_on'   => 'Developer mode turned ON',
	'dev_mode_off'  => 'Developer mode turned OFF',
);
$b       = isset( $status['builders'] ) ? $status['builders'] : array();
$seo     = isset( $status['seo'] ) ? $status['seo'] : array();
?>
<div class="owp" data-url="<?php echo esc_attr( $mcp_url ); ?>">

	<div class="owp-hero">
		<div class="owp-top">
			<div class="owp-logo">O</div>
			<h1 class="owp-title">OpenWP Bridge <span class="owp-pill">v<?php echo esc_html( OPENWP_BRIDGE_VERSION ); ?></span></h1>
		</div>
		<p class="owp-sub">Let your AI work on this site: read and change page layouts, fill SEO fields and keep notes, with a safety copy before every change. Nothing is sent to anyone else: your AI talks straight to your site.</p>
		<div class="owp-status">
			<span class="owp-dot <?php echo $connected ? 'on' : ''; ?>"></span>
			<?php if ( $last ) : ?>
				<span><?php echo $connected ? 'Your AI is connected' : 'Not seen in the last 10 minutes'; ?>
				<small> &middot; <?php echo esc_html( human_time_diff( (int) $last['time'], time() ) ); ?> ago &middot; <?php echo esc_html( $user ? $user->user_login : '' ); ?> &middot; <?php echo esc_html( $last['client'] ); ?></small></span>
			<?php else : ?>
				<span>Waiting for your AI <small>&middot; follow the 3 steps below</small></span>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( isset( $_GET['done'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
		<div class="notice notice-success is-dismissible" style="margin:16px 0 0"><p>Saved.</p></div>
	<?php endif; ?>

	<div class="owp-grid">

		<div class="owp-card">
			<h2>Connect your AI</h2>
			<p class="lead">No program to install on your computer.</p>
			<div class="owp-flow"><span class="owp-node">Your AI (Claude, Cursor...)</span><span class="owp-arrow">&rarr;</span><span class="owp-node me">This site &middot; OpenWP Bridge</span></div>

			<div class="owp-steps">
				<div class="owp-step"><div class="owp-num">1</div><div>
					<h3>Create an Application Password</h3>
					<p>Open <a href="<?php echo esc_url( admin_url( 'profile.php#application-passwords-section' ) ); ?>">your profile</a>, name it &#8220;OpenWP&#8221; and copy the password WordPress shows you. You can delete it any time to cut the access.</p>
				</div></div>
				<div class="owp-step"><div class="owp-num">2</div><div>
					<h3>Type your user name and that password</h3>
					<p>They stay in this page, in your browser: nothing is sent or saved. They only build the command below.</p>
					<div class="owp-fields">
						<input type="text" id="owp-u" placeholder="user name" value="<?php echo esc_attr( wp_get_current_user()->user_login ); ?>" autocomplete="off">
						<input type="text" id="owp-p" placeholder="xxxx xxxx xxxx xxxx xxxx xxxx" autocomplete="off">
					</div>
				</div></div>
				<div class="owp-step"><div class="owp-num">3</div><div style="min-width:0;flex:1">
					<h3>Copy the command for your AI</h3>
					<div class="owp-tabs">
						<button type="button" class="owp-tab on" data-pane="owp-p1">Claude Code</button>
						<button type="button" class="owp-tab" data-pane="owp-p2">Cursor</button>
						<button type="button" class="owp-tab" data-pane="owp-p3">Other</button>
					</div>
					<div id="owp-p1" class="owp-pane on"><div style="position:relative"><pre class="owp-code" id="owp-cc"></pre><button type="button" class="owp-copy" data-from="owp-cc">Copy</button></div></div>
					<div id="owp-p2" class="owp-pane"><div style="position:relative"><pre class="owp-code" id="owp-cur"></pre><button type="button" class="owp-copy" data-from="owp-cur">Copy</button></div><p class="owp-note">Paste it in Cursor&#8217;s MCP settings (<code>mcp.json</code>).</p></div>
					<div id="owp-p3" class="owp-pane"><div style="position:relative"><pre class="owp-code" id="owp-gen"></pre><button type="button" class="owp-copy" data-from="owp-gen">Copy</button></div><p class="owp-note">Any client that connects to an MCP address with a header works. Claude Desktop: not confirmed, use the OpenWP program from the README.</p></div>
				</div></div>
			</div>
		</div>

		<div class="owp-card">
			<h2>What your AI can do here</h2>
			<p class="lead">All of it is saved in a safety copy first, and you can put it back.</p>
			<div class="owp-tiles">
				<div class="owp-tile"><b>🧩 Edit page layouts</b><span>Elementor, Bricks, Divi and the block editor.</span></div>
				<div class="owp-tile"><b>🔎 Fill SEO fields</b><span>Yoast SEO, Rank Math, All in One SEO, SEOPress.</span></div>
				<div class="owp-tile"><b>🧠 Remember</b><span>Notes about this site, kept here, for every session.</span></div>
				<div class="owp-tile"><b>🛒 Run the store</b><span>Products, orders and customers of WooCommerce.</span></div>
			</div>
			<div class="owp-chips">
				<span class="owp-chip <?php echo ! empty( $b['elementor'] ) ? '' : 'off'; ?>"><i></i>Elementor <?php echo ! empty( $b['elementor'] ) ? esc_html( (string) $b['elementor'] ) : 'not found'; ?></span>
				<span class="owp-chip <?php echo ! empty( $b['bricks'] ) ? '' : 'off'; ?>"><i></i>Bricks <?php echo ! empty( $b['bricks'] ) ? esc_html( (string) $b['bricks'] ) : 'not found'; ?></span>
				<span class="owp-chip <?php echo ! empty( $b['divi'] ) ? '' : 'off'; ?>"><i></i>Divi <?php echo ! empty( $b['divi'] ) ? ( true === $b['divi'] ? 'found' : esc_html( (string) $b['divi'] ) ) : 'not found'; ?></span>
				<span class="owp-chip"><i></i>Block editor</span>
				<span class="owp-chip <?php echo ! empty( $seo['yoast'] ) ? '' : 'off'; ?>"><i></i>Yoast SEO <?php echo ! empty( $seo['yoast'] ) ? '' : 'not found'; ?></span>
				<span class="owp-chip <?php echo ! empty( $seo['rankmath'] ) ? '' : 'off'; ?>"><i></i>Rank Math <?php echo ! empty( $seo['rankmath'] ) ? '' : 'not found'; ?></span>
			</div>
		</div>

		<div class="owp-dev <?php echo $on ? 'on' : ''; ?>">
			<div class="owp-devrow">
				<div style="max-width:560px">
					<h2>Developer mode <span class="owp-badge <?php echo $on ? 'on' : ''; ?>"><?php echo $on ? '● ON' : '○ OFF'; ?></span></h2>
					<p>Lets the AI run PHP code, read the database and read or write files in <code>wp-content</code>. Powerful, and it can break a site. <strong>Test and staging sites only.</strong> It turns itself off when the time ends, and everything is logged below.
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

		<div class="owp-card">
			<h2>What your AI did</h2>
			<p class="lead">The last 20 actions.</p>
			<?php $rows = array_slice( OpenWP_Audit::all(), 0, 20 ); ?>
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
		</div>

	</div>
</div>
