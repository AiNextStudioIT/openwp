<?php
/**
 * Tools > OpenWP Bridge: what the bridge is, the Developer mode switch and the log.
 */

defined( 'ABSPATH' ) || exit;

class OpenWP_Settings {

	const PAGE = 'openwp-bridge';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_openwp_bridge_dev', array( __CLASS__, 'save' ) );
		add_action( 'admin_notices', array( __CLASS__, 'banner' ) );
	}

	public static function menu() {
		add_management_page( 'OpenWP Bridge', 'OpenWP Bridge', 'manage_options', self::PAGE, array( __CLASS__, 'render' ) );
	}

	/** A warning on every admin page while Developer mode is on. */
	public static function banner() {
		if ( ! current_user_can( 'manage_options' ) || ! OpenWP_Dev::enabled() ) {
			return;
		}

		echo '<div class="notice notice-warning"><p><strong>OpenWP Bridge: Developer mode is ON.</strong> Your AI agent can run code on this site. ';
		echo '<a href="' . esc_url( admin_url( 'tools.php?page=' . self::PAGE ) ) . '">Turn it off</a>.</p></div>';
	}

	public static function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}

		check_admin_referer( 'openwp_bridge_dev' );

		$choice = isset( $_POST['dev'] ) ? sanitize_key( wp_unslash( $_POST['dev'] ) ) : 'off';

		if ( 'off' === $choice ) {
			OpenWP_Dev::turn_off();
		} elseif ( ! OpenWP_Dev::forbidden_for_good() ) {
			$hours = isset( $_POST['hours'] ) ? (int) $_POST['hours'] : 1;
			OpenWP_Dev::turn_on( in_array( $hours, array( 0, 1, 8, 24 ), true ) ? $hours : 1 );
		}

		wp_safe_redirect( admin_url( 'tools.php?page=' . self::PAGE . '&done=1' ) );
		exit;
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$on   = OpenWP_Dev::enabled();
		$left = OpenWP_Dev::seconds_left();
		?>
		<div class="wrap">
			<h1>OpenWP Bridge</h1>
			<?php if ( isset( $_GET['done'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-success is-dismissible"><p>Saved.</p></div>
			<?php endif; ?>

			<?php
			$last = OpenWP_Presence::last();
			if ( $last ) {
				$user = get_userdata( (int) $last['user'] );
				?>
				<p style="font-size:15px"><span style="display:inline-block;width:11px;height:11px;border-radius:50%;margin-right:8px;background:<?php echo OpenWP_Presence::connected() ? '#46b450' : '#8c8f94'; ?>"></span>
					<strong>OpenWP: <?php echo OpenWP_Presence::connected() ? 'connected' : 'not seen in the last 10 minutes'; ?></strong>
					&middot; last request <?php echo esc_html( human_time_diff( (int) $last['time'], time() ) ); ?> ago
					&middot; user <?php echo esc_html( $user ? $user->user_login : (string) $last['user'] ); ?>
					&middot; <?php echo esc_html( $last['client'] ); ?></p>
			<?php } else { ?>
				<p style="font-size:15px"><span style="display:inline-block;width:11px;height:11px;border-radius:50%;margin-right:8px;background:#8c8f94"></span><strong>OpenWP: never connected yet.</strong> Connect OpenWP to this site (see the OpenWP README) and this line turns green.</p>
			<?php } ?>

			<p>This small plugin is the optional companion of <strong>OpenWP</strong>, the free tool that lets an AI agent (Claude, Cursor...) work on your site. With the bridge, the agent can <strong>read and change the layout</strong> of pages built with Elementor, Bricks, Divi or the block editor, and fill the SEO fields of Yoast SEO and Rank Math. Before every change it saves a copy, so you can go back.</p>
			<p>Version <?php echo esc_html( OPENWP_BRIDGE_VERSION ); ?>. Nothing is sent to any server: the agent talks directly to your site with a WordPress Application Password.</p>

			<h2>Connect your AI (nothing to install on your computer)</h2>
			<p>1. In <a href="<?php echo esc_url( admin_url( 'profile.php#application-passwords-section' ) ); ?>">your profile</a> create an <strong>Application Password</strong> (name it &#8220;OpenWP&#8221;) and copy it.<br>
				2. Type your user name and that password here. They stay in this page, in your browser: nothing is sent or saved.</p>
			<p>
				<input type="text" id="openwp-u" placeholder="user name" value="<?php echo esc_attr( wp_get_current_user()->user_login ); ?>" style="width:180px">
				<input type="text" id="openwp-p" placeholder="xxxx xxxx xxxx xxxx xxxx xxxx" style="width:300px" autocomplete="off">
			</p>
			<p>3. Paste this command in a terminal (Claude Code):</p>
			<textarea id="openwp-cmd" readonly rows="3" style="width:100%;max-width:900px;font-family:monospace"></textarea>
			<p>For <strong>Cursor</strong> and other clients: use this address <code><?php echo esc_html( rest_url( 'openwp/v1/mcp' ) ); ?></code> and add the header <code>Authorization: Basic &lt;the base64 of user:password&gt;</code> (the box above builds it). Claude Desktop: not confirmed, use the OpenWP program from the README.</p>
			<script>
			( function () {
				var u = document.getElementById( 'openwp-u' ), p = document.getElementById( 'openwp-p' ), c = document.getElementById( 'openwp-cmd' );
				var url = <?php echo wp_json_encode( rest_url( 'openwp/v1/mcp' ) ); ?>;
				function draw() {
					var b = btoa( u.value + ':' + p.value.replace( /\s+/g, ' ' ).trim() );
					c.value = 'claude mcp add --transport http openwp ' + url + ' --header "Authorization: Basic ' + b + '"';
				}
				u.addEventListener( 'input', draw ); p.addEventListener( 'input', draw ); draw();
			}() );
			</script>

			<h2>Developer mode</h2>
			<p>It lets the agent run PHP code, read the database and read and write files in <code>wp-content</code>. It is powerful and it can break a site: <strong>use it only on a test or staging site</strong>, never on a shop with real customers. It is <strong>off</strong> unless you turn it on, it turns itself off when the time ends, and every call is written in the log below.</p>

			<?php if ( OpenWP_Dev::forbidden_for_good() ) : ?>
				<p><strong>Developer mode is disabled by this site&#8217;s wp-config.php</strong> (OPENWP_BRIDGE_DISABLE_DEV). It cannot be turned on from here.</p>
			<?php else : ?>
				<p>Now: <strong><?php echo $on ? 'ON' : 'OFF'; ?></strong>
					<?php
					if ( $on && $left < 31536000 ) {
						echo ' &middot; turns off in ' . esc_html( human_time_diff( time(), time() + $left ) );
					}
					?>
				</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="openwp_bridge_dev">
					<?php wp_nonce_field( 'openwp_bridge_dev' ); ?>
					<?php if ( $on ) : ?>
						<input type="hidden" name="dev" value="off">
						<?php submit_button( 'Turn Developer mode OFF', 'primary', 'submit', false ); ?>
					<?php else : ?>
						<input type="hidden" name="dev" value="on">
						<label for="openwp-hours">Keep it on for </label>
						<select id="openwp-hours" name="hours">
							<option value="1">1 hour</option>
							<option value="8">8 hours</option>
							<option value="24">24 hours</option>
							<option value="0">until I turn it off</option>
						</select>
						<?php submit_button( 'Turn Developer mode ON', 'secondary', 'submit', false ); ?>
					<?php endif; ?>
				</form>
			<?php endif; ?>

			<h2>What the agent did (last 20)</h2>
			<table class="widefat striped" style="max-width:900px">
				<thead><tr><th>When (UTC)</th><th>User</th><th>Action</th><th>Detail</th></tr></thead>
				<tbody>
				<?php foreach ( array_slice( OpenWP_Audit::all(), 0, 20 ) as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row['time'] ); ?></td>
						<td><?php echo esc_html( (string) $row['user'] ); ?></td>
						<td><?php echo esc_html( $row['action'] ); ?></td>
						<td><code><?php echo esc_html( $row['detail'] ); ?></code></td>
					</tr>
				<?php endforeach; ?>
				<?php if ( ! OpenWP_Audit::all() ) : ?>
					<tr><td colspan="4">Nothing yet.</td></tr>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
